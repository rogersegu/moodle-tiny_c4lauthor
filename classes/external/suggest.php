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

namespace tiny_c4lauthor\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;

defined('MOODLE_INTERNAL') || die();

/**
 * Web service for AI-based C4L component suggestions.
 *
 * @package    tiny_c4lauthor
 * @copyright  2026 Roger Segú <rogersegu@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class suggest extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'contextid'  => new external_value(PARAM_INT, 'Context id'),
            'paragraphs' => new external_value(PARAM_RAW, 'JSON: [{index, text}, ...]'),
            'lang'       => new external_value(PARAM_ALPHANUMEXT, 'Language code', VALUE_DEFAULT, 'en'),
        ]);
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'suggestions' => new external_multiple_structure(
                new external_single_structure([
                    'component'   => new external_value(PARAM_ALPHANUMEXT, 'Component key'),
                    'targettype'  => new external_value(PARAM_ALPHA, 'Target type'),
                    'targetindex' => new external_value(PARAM_INT, '0-based index'),
                    'confidence'  => new external_value(PARAM_FLOAT, '0..1'),
                    'rationale'   => new external_value(PARAM_TEXT, 'Short rationale'),
                    'preview'     => new external_value(PARAM_RAW, 'Preview text'),
                ])
            ),
            'warnings' => new external_multiple_structure(
                new external_value(PARAM_TEXT, 'Warning message'),
                'Warnings',
                VALUE_DEFAULT,
                []
            ),
        ]);
    }

    public static function execute(int $contextid, string $paragraphs, string $lang = 'en'): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'contextid' => $contextid,
            'paragraphs' => $paragraphs,
            'lang' => $lang,
        ]);

        $context = \context::instance_by_id($params['contextid'], MUST_EXIST);
        self::validate_context($context);
        require_capability('tiny/c4lauthor:aisuggest', $context);

        $items = json_decode($params['paragraphs'], true);
        if (!is_array($items)) {
            return ['suggestions' => [], 'warnings' => ['Invalid paragraphs JSON.']];
        }

        // Require a configured AI provider.
        if (!\tiny_c4lauthor\ai_classifier::is_available()) {
            return [
                'suggestions' => [],
                'warnings' => [get_string('ai_no_provider', 'tiny_c4lauthor')],
            ];
        }

        return \tiny_c4lauthor\ai_classifier::classify($params['contextid'], $items, $params['lang']);
    }
}
