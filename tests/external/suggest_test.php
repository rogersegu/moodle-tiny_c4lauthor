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

use core_ai\aiactions\responses\response_generate_text;
use core_ai\manager;
use core_external\external_api;

/**
 * Tests for the AI suggest web service.
 *
 * @package    tiny_c4lauthor
 * @category   test
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tiny_c4lauthor\external\suggest
 * @covers     \tiny_c4lauthor\ai_classifier
 */
final class suggest_test extends \advanced_testcase {
    /** @var \stdClass The course used by the tests. */
    private \stdClass $course;

    /** @var \context_course Its context. */
    private \context_course $context;

    /**
     * Create a course with AI suggest switched on and log in as an editing teacher.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        set_config('ai_enabled', 1, 'tiny_c4lauthor');
        $this->course = $this->getDataGenerator()->create_course();
        $this->context = \context_course::instance($this->course->id);
        $teacher = $this->getDataGenerator()->create_and_enrol($this->course, 'editingteacher');
        $this->setUser($teacher);
    }

    /**
     * Call the service the way the web service layer does.
     *
     * @param array $paragraphs
     * @return array
     */
    private function call(array $paragraphs): array {
        $result = suggest::execute($this->context->id, $paragraphs, 'en');
        return external_api::clean_returnvalue(suggest::execute_returns(), $result);
    }

    /**
     * Build a list of paragraphs.
     *
     * @param int $count
     * @param int $length characters per paragraph
     * @return array
     */
    private function paragraphs(int $count, int $length = 60): array {
        $items = [];
        for ($i = 0; $i < $count; $i++) {
            $items[] = ['index' => $i, 'text' => str_repeat('a', $length)];
        }
        return $items;
    }

    /**
     * Accept the AI policy for the current user.
     */
    private function accept_policy(): void {
        global $USER;
        manager::user_policy_accepted($USER->id, $this->context->id);
    }

    /**
     * Skip the test where the AI manager cannot be mocked.
     *
     * Moodle 4.5 declares the availability check as a static method, which cannot be
     * mocked, so tests with a fake AI provider only run on 5.0 and later.
     */
    private function require_mockable_ai(): void {
        if ((new \ReflectionMethod(manager::class, 'is_action_available'))->isStatic()) {
            $this->markTestSkipped('The AI manager cannot be mocked on this Moodle version.');
        }
    }

    /**
     * Replace the AI manager with a mock provider.
     *
     * @param response_generate_text|null $response what the provider answers; null when it must not be called
     * @param bool $enabledincontext whether the course and activity allow the action
     */
    private function mock_ai(?response_generate_text $response, bool $enabledincontext = true): void {
        $manager = $this->createMock(manager::class);
        $manager->method('is_action_available')->willReturn(true);
        if (method_exists(manager::class, 'is_action_enabled_in_context')) {
            // Added after Moodle 5.0.
            $manager->method('is_action_enabled_in_context')->willReturn($enabledincontext);
        }
        if ($response) {
            $manager->method('process_action')->willReturn($response);
        } else {
            $manager->expects($this->never())->method('process_action');
        }
        \core\di::set(manager::class, $manager);
    }

    /**
     * Build a successful provider response.
     *
     * @param string $content the generated text
     * @return response_generate_text
     */
    private function response(string $content): response_generate_text {
        $response = new response_generate_text(success: true);
        $response->set_response_data(['generatedcontent' => $content]);
        return $response;
    }

    public function test_requires_capability(): void {
        $student = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $this->setUser($student);

        $this->expectException(\required_capability_exception::class);
        $this->call($this->paragraphs(3));
    }

    public function test_refused_when_switched_off(): void {
        $this->require_mockable_ai();
        set_config('ai_enabled', 0, 'tiny_c4lauthor');
        $this->accept_policy();
        $this->mock_ai(null);

        $result = $this->call($this->paragraphs(3));

        $this->assertSame([], $result['suggestions']);
        $this->assertSame([get_string('ai_not_enabled', 'tiny_c4lauthor')], $result['warnings']);
    }

    public function test_reports_missing_provider(): void {
        $this->accept_policy();

        $result = $this->call($this->paragraphs(3));

        $this->assertSame([get_string('ai_no_provider', 'tiny_c4lauthor')], $result['warnings']);
    }

    public function test_requires_accepted_policy(): void {
        $this->require_mockable_ai();
        $this->mock_ai(null);

        $result = $this->call($this->paragraphs(3));

        $this->assertSame([], $result['suggestions']);
        $this->assertSame([get_string('ai_policy_not_accepted', 'tiny_c4lauthor')], $result['warnings']);
    }

    public function test_respects_course_ai_setting(): void {
        $this->require_mockable_ai();
        if (!method_exists(manager::class, 'is_action_enabled_in_context')) {
            $this->markTestSkipped('This Moodle version has no per-course AI setting.');
        }
        $this->accept_policy();
        $this->mock_ai(null, false);

        $result = $this->call($this->paragraphs(3));

        $this->assertSame([get_string('ai_not_enabled_here', 'tiny_c4lauthor')], $result['warnings']);
    }

    public function test_returns_suggestions(): void {
        $this->require_mockable_ai();
        $this->accept_policy();
        $this->mock_ai($this->response('[{"component": "tip", "index": 1, "confidence": 0.9, "rationale": "A hint."}]'));

        $result = $this->call($this->paragraphs(3));

        $this->assertSame([], $result['warnings']);
        $this->assertCount(1, $result['suggestions']);
        $this->assertSame('tip', $result['suggestions'][0]['component']);
        $this->assertSame(1, $result['suggestions'][0]['targetindex']);
    }

    public function test_accepts_text_that_looks_like_a_tag(): void {
        $this->require_mockable_ai();
        $this->accept_policy();
        $this->mock_ai($this->response('[]'));

        $result = $this->call([['index' => 0, 'text' => 'An <array> holds values, and </div> closes a block.']]);

        $this->assertSame([], $result['warnings']);
    }

    public function test_keeps_only_enabled_components(): void {
        $this->require_mockable_ai();
        $this->accept_policy();
        set_config('ai_comp_enabled_attention', 0, 'tiny_c4lauthor');
        $this->mock_ai($this->response('[{"component": "attention", "index": 0, "confidence": 0.9, "rationale": "A"},'
            . '{"component": "tip", "index": 1, "confidence": 0.9, "rationale": "B"}]'));

        $result = $this->call($this->paragraphs(3));

        $this->assertCount(1, $result['suggestions']);
        $this->assertSame('tip', $result['suggestions'][0]['component']);
    }

    public function test_no_enabled_components_never_reach_the_provider(): void {
        $this->require_mockable_ai();
        $this->accept_policy();
        foreach (['attention', 'tip', 'keyconcept', 'reminder'] as $component) {
            set_config('ai_comp_enabled_' . $component, 0, 'tiny_c4lauthor');
        }
        $this->mock_ai(null);

        $result = $this->call($this->paragraphs(3));

        $this->assertSame([get_string('ai_no_components_enabled', 'tiny_c4lauthor')], $result['warnings']);
    }

    public function test_tells_the_user_when_paragraphs_are_left_out(): void {
        $this->require_mockable_ai();
        $this->accept_policy();
        set_config('ai_max_paragraphs', 2, 'tiny_c4lauthor');
        $this->mock_ai($this->response('[]'));

        $result = $this->call($this->paragraphs(3));

        $this->assertSame([
            get_string('ai_payload_capped', 'tiny_c4lauthor', (object) ['analysed' => 2, 'total' => 3]),
        ], $result['warnings']);
    }

    public function test_tells_the_user_when_paragraphs_are_shortened(): void {
        $this->require_mockable_ai();
        $this->accept_policy();
        set_config('ai_max_paragraph_chars', 20, 'tiny_c4lauthor');
        $this->mock_ai($this->response('[]'));

        $result = $this->call($this->paragraphs(1));

        $this->assertSame([
            get_string('ai_payload_truncated', 'tiny_c4lauthor', (object) ['count' => 1, 'chars' => 20]),
        ], $result['warnings']);
    }

    public function test_hides_provider_errors(): void {
        $this->require_mockable_ai();
        $this->accept_policy();
        $args = ['success' => false, 'errorcode' => 500, 'errormessage' => 'Secret provider detail'];
        $constructor = new \ReflectionMethod(response_generate_text::class, '__construct');
        if (in_array('error', array_map(fn($param) => $param->getName(), $constructor->getParameters()))) {
            // Moodle 5.1 and later require an error name as well.
            $args['error'] = 'Internal server error';
        }
        $this->mock_ai(new response_generate_text(...$args));

        $result = $this->call($this->paragraphs(3));

        $this->assertSame([get_string('ai_error', 'tiny_c4lauthor')], $result['warnings']);
        $this->assertDebuggingCalled('tiny_c4lauthor: AI provider returned an error: Secret provider detail');
    }
}
