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

use core\hook\output\before_standard_head_html_generation;

/**
 * Tests for the CSS the plugin sends with every page.
 *
 * @package    tiny_c4lauthor
 * @category   test
 * @copyright  2026 Roger Segú <rogersegu@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tiny_c4lauthor\hook_callbacks::before_standard_head_html_generation
 */
final class hook_callbacks_test extends \advanced_testcase {
    /**
     * Start every test with no custom CSS stored.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * The head content the plugin contributes, given what the administrator stored.
     *
     * @param string|null $css The custompreviewcss setting, or null to leave it unset.
     * @return string
     */
    private function head(?string $css): string {
        global $PAGE;

        if ($css !== null) {
            set_config('custompreviewcss', $css, 'tiny_c4lauthor');
        }

        $hook = new before_standard_head_html_generation($PAGE->get_renderer('core'));
        hook_callbacks::before_standard_head_html_generation($hook);

        return $hook->get_output();
    }

    /**
     * A site that has stored nothing, or only blank space, gets no stylesheet.
     *
     * @param string|null $css
     * @dataProvider nothing_to_send_provider
     */
    public function test_nothing_to_send(?string $css): void {
        $this->assertSame('', $this->head($css));
    }

    /**
     * Settings values that are not CSS.
     *
     * @return array[]
     */
    public static function nothing_to_send_provider(): array {
        return [
            'never set' => [null],
            'emptied' => [''],
            'blank space' => ["  \n\t "],
        ];
    }

    /**
     * The administrator's CSS is sent with the page, wrapped by the plugin.
     */
    public function test_css_is_sent_with_the_page(): void {
        $css = '.c4l-keyconcept { --c4l-keyconcept-accent: #1cc460; }';

        $this->assertSame('<style>' . $css . '</style>', $this->head($css));
    }

    /**
     * Administration pages are left alone.
     *
     * A rule that hid half the document would otherwise hide the page holding the field
     * where that rule is written, and only the command line could undo it.
     */
    public function test_administration_pages_are_left_alone(): void {
        global $PAGE;

        $PAGE->set_url('/admin/settings.php');
        $PAGE->set_context(\core\context\system::instance());
        $PAGE->set_pagelayout('admin');

        $this->assertSame('', $this->head('.c4l-keyconcept { display: none; }'));
    }

    /**
     * A closing tag in the setting stays inside the stylesheet.
     *
     * Left alone it would end the element early and the rest of the page would be
     * printed as text, which is what an administrator writing the tags by hand risked.
     */
    public function test_closing_tag_does_not_end_the_stylesheet(): void {
        $output = $this->head('.a { color: red; }</style><h1>out</h1>');

        // One closing tag, the plugin's own, and it is the last thing in the output.
        $this->assertSame(1, substr_count(strtolower($output), '</style'));
        $this->assertStringEndsWith('</style>', $output);
        $this->assertStringContainsString('<\/style><h1>out</h1>', $output);
    }

    /**
     * Case and spelling do not get a closing tag past the guard.
     *
     * @param string $tag
     * @dataProvider closing_tag_provider
     */
    public function test_closing_tag_variants(string $tag): void {
        $output = $this->head('.a { color: red; }' . $tag);

        $this->assertSame(1, substr_count(strtolower($output), '</style'));
    }

    /**
     * Ways of writing the closing tag that a browser accepts.
     *
     * @return array[]
     */
    public static function closing_tag_provider(): array {
        return [
            'lowercase' => ['</style>'],
            'uppercase' => ['</STYLE>'],
            'mixed case' => ['</Style>'],
            'no bracket' => ['</style'],
            'space before the bracket' => ['</style >'],
        ];
    }
}
