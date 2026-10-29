<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace tiny_c4lauthor;

/**
 * AI-based classifier for C4L components using Moodle's core_ai subsystem.
 *
 * @package    tiny_c4lauthor
 * @copyright  2026 Roger Segú <rogersegu@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ai_classifier {
    /** @var string[] Valid C4L component types. */
    private const VALID_COMPONENTS = ['attention', 'tip', 'keyconcept', 'reminder'];

    /** @var float Minimum confidence threshold. */
    private const MIN_CONFIDENCE = 0.4;


    /** @var int Minimum paragraph length to send to LLM. */
    private const MIN_PARAGRAPH_LENGTH = 10;

    /** @var int Maximum size in bytes of the raw paragraphs payload accepted by the web service. */
    public const MAX_PAYLOAD_BYTES = 1048576;

    /** @var int Default maximum characters kept from a single paragraph; the rest is not sent to the LLM. */
    private const DEFAULT_MAX_PARAGRAPH_LENGTH = 1500;

    /** @var int Default maximum number of paragraphs sent to the LLM. */
    private const DEFAULT_MAX_PARAGRAPHS = 80;

    /** @var int Default maximum total characters of paragraph text sent to the LLM. */
    private const DEFAULT_MAX_TOTAL_CHARS = 40000;

    /** @var array AI-specific definitions with inline use cases. No lang string dependencies. */
    private const COMPONENT_DEFS = [
        'attention' => [
            'desc' => 'Warnings, common errors, precautions, or critical notes that alert the student to avoid mistakes.',
            'uses' => [
                'To warn the learner of a relevant aspect that he must consider related to a learning subject.',
                'In the context of a predefined learning flow, it attracts the learner\'s attention over a '
                    . 'recommended (or expected) way to proceed in a certain context.',
            ],
        ],
        'keyconcept' => [
            'desc' => 'A succinct definition, synthesis, or formula that highlights an important concept or idea. '
                . 'Must be brief (1-2 sentences). Not for lengthy explanations.',
            'uses' => [
                'Succinct definition of a relevant concept.',
                'Brief synthesis of a particular concept that has been explained above.',
                'In Science materials, to display formulas or abstract mathematical outputs.',
            ],
        ],
        'reminder' => [
            'desc' => 'A reference to previously learned concepts that helps the learner understand the current subject. '
                . 'Used to reduce cognitive load by surfacing relevant prior knowledge.',
            'uses' => [
                'When introducing a new concept that requires a particular previous knowledge.',
                'After explaining a complex concept, to summarise the essential part the learner is encouraged to remember.',
                'Refresh the learner\'s memory regarding a theoretical concept needed to solve an exercise.',
            ],
        ],
        'tip' => [
            'desc' => 'Short practical advice, recommendations, or helpful hints for the student.',
            'uses' => [
                'To offer advice or guidance (on the content itself, not learning-related procedural aspects).',
                'To disambiguate a concept.',
                'To help prevent a usual mistake (a particular concept or idea is often misunderstood or leads to confusion).',
            ],
        ],
    ];

    /**
     * Build the component category descriptions for the prompt.
     *
     * @return string Formatted category list for the AI prompt.
     */
    private static function build_category_descriptions(): string {
        $lines = [];
        foreach (self::COMPONENT_DEFS as $comp => $def) {
            $line = "- **{$comp}**: {$def['desc']}";
            if (!empty($def['uses'])) {
                $line .= " Typical use cases: " . implode('; ', $def['uses']);
            }
            $lines[] = $line;
        }
        return implode("\n", $lines);
    }

    /**
     * Build the full prompt text.
     *
     * @param string $paragraphstext Numbered paragraphs.
     * @param string $lang Language code.
     * @param int $maxsuggestions Maximum number of suggestions.
     * @return string Complete prompt.
     */
    private static function build_prompt(string $paragraphstext, string $lang, int $maxsuggestions): string {
        $categories = self::build_category_descriptions();

        $prompt = "You are an educational content classifier. Analyse the following numbered paragraphs "
            . "and classify each one that clearly fits one of these categories:\n\n"
            . $categories . "\n\n"
            . "When a paragraph mentions an author and their theory or contribution, "
            . "suggest keyconcept to highlight their most important idea.\n\n"
            . "When a paragraph introduces a formula, chemical expression, or mathematical expression "
            . "(typically ending with \":\" followed by the formula), always suggest keyconcept. "
            . "Formulas are essential knowledge anchors, even if the paragraph contains introductory text.\n\n"
            . "Only classify paragraphs where you are confident. Skip paragraphs that do not clearly match any category.\n\n"
            . "Paragraphs (language: {$lang}):\n{$paragraphstext}\n\n"
            . "Respond ONLY with a JSON array (no extra text). Each element must have exactly these fields:\n"
            . "- \"index\": the paragraph number (integer, as shown in brackets)\n"
            . "- \"component\": one of \"" . implode('", "', self::VALID_COMPONENTS) . "\"\n"
            . "- \"confidence\": a float between 0.0 and 1.0\n"
            . "- \"rationale\": a short explanation (1 sentence, in the same language as the paragraphs)\n\n"
            . "Maximum " . $maxsuggestions . " suggestions. Example format:\n"
            . '[{"index": 0, "component": "tip", "confidence": 0.85, "rationale": "Practical advice for the student."}]';

        return $prompt;
    }

    /**
     * Check whether core_ai has a provider configured for generate_text.
     *
     * @return bool
     */
    public static function is_available(): bool {
        try {
            $manager = \core\di::get(\core_ai\manager::class);
            return $manager->is_action_available(\core_ai\aiactions\generate_text::class);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Classify paragraphs using core_ai generate_text.
     *
     * @param int $contextid The context id.
     * @param array $items Array of [{index: int, text: string}] from JS.
     * @param string $lang Language code for the prompt.
     * @return array{suggestions: array, warnings: string[]}
     */
    public static function classify(int $contextid, array $items, string $lang): array {
        global $USER;

        $warnings = [];

        // Upper bounds on what is sent to the provider, so a large document cannot
        // produce an unbounded prompt. Admin-configurable, with sane fallbacks.
        $maxparagraphs = (int) get_config('tiny_c4lauthor', 'ai_max_paragraphs');
        if ($maxparagraphs <= 0) {
            $maxparagraphs = self::DEFAULT_MAX_PARAGRAPHS;
        }
        $maxtotalchars = (int) get_config('tiny_c4lauthor', 'ai_max_total_chars');
        if ($maxtotalchars <= 0) {
            $maxtotalchars = self::DEFAULT_MAX_TOTAL_CHARS;
        }
        $maxparagraphchars = (int) get_config('tiny_c4lauthor', 'ai_max_paragraph_chars');
        if ($maxparagraphchars <= 0) {
            $maxparagraphchars = self::DEFAULT_MAX_PARAGRAPH_LENGTH;
        }
        // A single paragraph must never be allowed to exhaust the whole budget.
        $maxparagraphchars = min($maxparagraphchars, $maxtotalchars);

        // Filter out items too short for the LLM, and cap the total payload.
        $eligible = [];
        $indexmap = [];
        $seq = 0;
        $totalchars = 0;
        $candidates = 0;
        $truncated = 0;
        $dropped = false;
        foreach ($items as $item) {
            $text = $item['text'] ?? '';
            $origindex = (int) ($item['index'] ?? -1);
            if ($origindex < 0 || $text === '' || mb_strlen($text) < self::MIN_PARAGRAPH_LENGTH) {
                continue;
            }
            $candidates++;

            if ($seq >= $maxparagraphs) {
                $dropped = true;
                continue;
            }

            // Classifying a paragraph does not need all of it; keep the opening.
            $wastruncated = mb_strlen($text) > $maxparagraphchars;
            if ($wastruncated) {
                $text = mb_substr($text, 0, $maxparagraphchars);
            }

            if ($totalchars + mb_strlen($text) > $maxtotalchars) {
                $dropped = true;
                continue;
            }

            // Only count a paragraph as truncated once it is actually sent.
            if ($wastruncated) {
                $truncated++;
            }
            $totalchars += mb_strlen($text);
            $eligible[$seq] = $text;
            $indexmap[$seq] = $origindex;
            $seq++;
        }

        if (empty($eligible)) {
            return ['suggestions' => [], 'warnings' => []];
        }

        if ($dropped) {
            $warnings[] = get_string('ai_payload_capped', 'tiny_c4lauthor', (object) [
                'analysed' => count($eligible),
                'total' => $candidates,
            ]);
        }
        if ($truncated > 0) {
            $warnings[] = get_string('ai_payload_truncated', 'tiny_c4lauthor', (object) [
                'count' => $truncated,
                'chars' => $maxparagraphchars,
            ]);
        }

        // Build numbered paragraph list for the prompt.
        $numbered = [];
        foreach ($eligible as $s => $text) {
            $numbered[] = "[{$s}] {$text}";
        }
        $paragraphstext = implode("\n", $numbered);

        $maxsuggestions = self::max_suggestions(count($eligible));

        // Build the classification prompt.
        $prompttext = self::build_prompt($paragraphstext, $lang, $maxsuggestions);

        // Append admin-defined prompt extension.
        $promptextra = get_config('tiny_c4lauthor', 'ai_prompt_extra');
        if (!empty($promptextra)) {
            $prompttext .= "\n\nIMPORTANT — Additional instructions from the administrator " .
                "(these override any conflicting rules above):\n" . $promptextra;
        }

        // Call core_ai.
        try {
            $action = new \core_ai\aiactions\generate_text(
                contextid: $contextid,
                userid: $USER->id,
                prompttext: $prompttext,
            );

            $manager = \core\di::get(\core_ai\manager::class);
            $response = $manager->process_action($action);

            if (!$response->get_success()) {
                $warnings[] = get_string('ai_error', 'tiny_c4lauthor', $response->get_errormessage());
                return ['suggestions' => [], 'warnings' => $warnings];
            }

            $content = $response->get_response_data()['generatedcontent'] ?? '';
        } catch (\Throwable $e) {
            $warnings[] = get_string('ai_error', 'tiny_c4lauthor', $e->getMessage());
            return ['suggestions' => [], 'warnings' => $warnings];
        }

        // Post-validation.
        $json = self::extract_json($content);
        if ($json === null) {
            $warnings[] = get_string('ai_parse_error', 'tiny_c4lauthor');
            return ['suggestions' => [], 'warnings' => $warnings];
        }

        $suggestions = self::validate_suggestions($json, $eligible, $indexmap);

        return ['suggestions' => $suggestions, 'warnings' => $warnings];
    }

    /**
     * Extract a JSON array from LLM output.
     *
     * @param string $text Raw LLM response.
     * @return array|null Decoded array or null if parsing failed.
     */
    private static function extract_json(string $text): ?array {
        $backticks = str_repeat(chr(96), 3);
        if (preg_match('/' . $backticks . '(?:json)?\s*(\[[\s\S]*?\])\s*' . $backticks . '/', $text, $matches)) {
            $decoded = json_decode($matches[1], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        if (preg_match('/(\[[\s\S]*\])/', $text, $matches)) {
            $decoded = json_decode($matches[1], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $decoded = json_decode(trim($text), true);
        if (is_array($decoded)) {
            return $decoded;
        }

        return null;
    }

    /**
     * Validate and filter suggestions from the LLM response.
     *
     * @param array $raw Raw decoded JSON array.
     * @param array $eligible Sequential-indexed texts (for preview).
     * @param array $indexmap Sequential AI index → original JS paragraph index.
     * @return array Valid suggestions.
     */
    private static function validate_suggestions(array $raw, array $eligible, array $indexmap): array {
        $suggestions = [];
        $seen = [];
        $maxseq = count($indexmap) - 1;

        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }

            $component = $item['component'] ?? null;
            $seqindex = $item['index'] ?? ($item['targetindex'] ?? null);
            $confidence = $item['confidence'] ?? null;
            $rationale = $item['rationale'] ?? '';

            if ($component === null || $seqindex === null || $confidence === null) {
                continue;
            }

            if (!in_array($component, self::VALID_COMPONENTS, true)) {
                continue;
            }

            $seqindex = (int) $seqindex;
            if ($seqindex < 0 || $seqindex > $maxseq) {
                continue;
            }
            $origindex = $indexmap[$seqindex] ?? null;
            if ($origindex === null) {
                continue;
            }

            $confidence = (float) $confidence;
            if ($confidence < self::MIN_CONFIDENCE || $confidence > 1.0) {
                continue;
            }

            $key = "{$origindex}:{$component}";
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $text = $eligible[$seqindex] ?? '';
            $preview = mb_strlen($text) > 140 ? mb_substr($text, 0, 139) . '…' : $text;

            $suggestions[] = [
                'component' => $component,
                'targettype' => 'p',
                'targetindex' => $origindex,
                'confidence' => round($confidence, 2),
                'rationale' => clean_param($rationale, PARAM_TEXT),
                'preview' => $preview,
            ];
        }

        usort($suggestions, function ($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });

        return array_slice($suggestions, 0, self::max_suggestions(count($eligible)));
    }

    /**
     * Largest number of suggestions worth asking for, and worth keeping.
     *
     * The editor never displays more than half as many suggestions as there are
     * paragraphs: filterSuggestions() in amd/src/ai_guards.js caps the list at
     * ceil(totalContent / 2). Asking the provider for one suggestion per paragraph
     * would pay for output the UI discards before the user ever sees it, so the
     * ask is aligned with that ceiling. Short documents keep the configurable floor.
     *
     * @param int $eligiblecount Number of paragraphs sent to the LLM.
     * @return int Maximum suggestions.
     */
    private static function max_suggestions(int $eligiblecount): int {
        $minsuggestions = (int) (get_config('tiny_c4lauthor', 'ai_min_suggestions') ?: 12);
        return max($minsuggestions, (int) ceil($eligiblecount / 2));
    }
}
