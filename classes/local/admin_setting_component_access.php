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

namespace tiny_c4lauthor\local;

use admin_setting;
use core\context;
use html_writer;

/**
 * Who may use each component: one row per component, one choice per row.
 *
 * Stored as a single config value, so the whole matrix is one setting rather than one
 * per component. The rows come from the declarations, so a component cannot be left
 * out of this page by accident.
 *
 * Component availability was funded by Hochschule Hannover <https://www.hs-hannover.de>.
 *
 * @package    tiny_c4lauthor
 * @copyright  2026 Roger Segú <rogersegu@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_component_access extends admin_setting {
    /** @var string[] Categories in the order the sidebar shows them. */
    private const CATEGORY_ORDER = ['contextual', 'procedural', 'evaluative', 'sequential', 'helper', 'templates'];

    /**
     * Build the setting.
     *
     * @param string $name Unqualified setting name.
     * @param string $visiblename Localised name.
     * @param string $description Localised description.
     */
    public function __construct($name, $visiblename, $description) {
        parent::__construct($name, $visiblename, $description, component_access::defaults($this->context()));
    }

    /**
     * The context the component list is read in.
     *
     * The settings page is a site page, and a component added for one course only is
     * still a component an administrator should see a row for.
     *
     * @return context
     */
    private function context(): context {
        return \core\context\system::instance();
    }

    /**
     * The components, grouped by category and labelled.
     *
     * @return array[] name => ['category' => string, 'label' => string]
     */
    private function rows(): array {
        $context = $this->context();
        $rows = [];
        $components = components::get_components($context);
        $categories = array_unique(array_merge(
            self::CATEGORY_ORDER,
            array_column($components, 'category')
        ));
        foreach ($categories as $category) {
            foreach ($components as $name => $component) {
                if (($component['category'] ?? '') === $category) {
                    $rows[$name] = [
                        'category' => $category,
                        'label' => components::get_label($name, $component),
                    ];
                }
            }
        }
        return $rows;
    }

    /**
     * The stored access map, or null when nothing has been stored yet.
     *
     * Returning null is what makes admin_apply_default_settings() write the defaults
     * on install, so a fresh site gets real rows rather than relying on every reader
     * to fall back.
     *
     * @return array|null
     */
    public function get_setting() {
        $value = $this->config_read($this->name);
        if ($value === null) {
            return null;
        }
        return component_access::decode($value, $this->context());
    }

    /**
     * Store the submitted choices.
     *
     * @param array|string $data Level keyed by component name, or an encoded value.
     * @return string Empty on success, a localised error otherwise.
     */
    public function write_setting($data) {
        $context = $this->context();
        if (is_string($data)) {
            $data = component_access::decode($data, $context);
        }
        if (!is_array($data)) {
            return get_string('componentaccess_invalid', 'tiny_c4lauthor');
        }

        $levels = component_access::levels();
        $defaults = component_access::defaults($context);
        $access = [];
        foreach (array_keys($defaults) as $name) {
            $level = $data[$name] ?? null;
            $access[$name] = in_array($level, $levels, true) ? $level : $defaults[$name];
        }

        return $this->config_write($this->name, component_access::encode($access))
            ? ''
            : get_string('errorsetting', 'admin');
    }

    /**
     * Render the matrix.
     *
     * @param array|null $data Current access map.
     * @param string $query Admin search query.
     * @return string
     */
    public function output_html($data, $query = '') {
        if (!is_array($data)) {
            $data = component_access::defaults($this->context());
        }

        $levels = component_access::levels();
        $rows = html_writer::tag('tr', $this->head($levels));

        $lastcategory = null;
        foreach ($this->rows() as $name => $row) {
            // A group heading whenever the category changes, so the rows stay readable.
            if ($row['category'] !== $lastcategory) {
                $lastcategory = $row['category'];
                $rows .= html_writer::tag('tr', html_writer::tag(
                    'th',
                    $this->category_label($lastcategory),
                    ['colspan' => count($levels) + 1, 'scope' => 'colgroup']
                ));
            }
            $rows .= html_writer::tag('tr', $this->row($name, $row['label'], $levels, $data[$name] ?? null));
        }

        $table = html_writer::tag(
            'table',
            $rows,
            ['class' => 'table table-sm generaltable tiny_c4lauthor-componentaccess']
        );

        return format_admin_setting($this, $this->visiblename, $table, $this->description, false, '', null, $query);
    }

    /**
     * The header cells.
     *
     * @param string[] $levels
     * @return string
     */
    private function head(array $levels): string {
        $head = html_writer::tag('th', get_string('componentaccess_component', 'tiny_c4lauthor'), ['scope' => 'col']);
        foreach ($levels as $level) {
            $heading = get_string('componentaccess_' . $level, 'tiny_c4lauthor');
            // Say who staff are, the plugin working it out from a capability rather
            // than from roles. Listing whoever holds it on this site keeps the heading
            // true where the site's permissions are not the stock ones.
            if ($level === component_access::ACCESS_STAFF) {
                $who = component_access::staff_role_names();
                if ($who) {
                    $heading .= html_writer::div(
                        get_string('componentaccess_staff_who', 'tiny_c4lauthor', implode(', ', $who)),
                        'text-muted small font-weight-normal fw-normal'
                    );
                }
            }
            $head .= html_writer::tag('th', $heading, ['scope' => 'col', 'class' => 'text-center']);
        }
        return $head;
    }

    /**
     * One component's row: its label and a radio per level.
     *
     * @param string $name Component name.
     * @param string $label Localised label.
     * @param string[] $levels
     * @param string|null $chosen The level stored for this component.
     * @return string
     */
    private function row(string $name, string $label, array $levels, ?string $chosen): string {
        $cells = html_writer::tag('td', $label);
        foreach ($levels as $level) {
            $id = $this->get_id() . '_' . $name . '_' . $level;
            $attrs = [
                'type' => 'radio',
                'id' => $id,
                'name' => $this->get_full_name() . '[' . $name . ']',
                'value' => $level,
            ];
            if ($chosen === $level) {
                $attrs['checked'] = 'checked';
            }
            $cells .= html_writer::tag('td', html_writer::empty_tag('input', $attrs)
                // The visible label is the row heading, so this one is for screen readers.
                . html_writer::label(
                    $label . ': ' . get_string('componentaccess_' . $level, 'tiny_c4lauthor'),
                    $id,
                    false,
                    ['class' => 'sr-only visually-hidden']
                ), ['class' => 'text-center']);
        }
        return $cells;
    }

    /**
     * The heading of a category group.
     *
     * @param string $category
     * @return string
     */
    private function category_label(string $category): string {
        return get_string_manager()->string_exists($category, 'tiny_c4lauthor')
            ? get_string($category, 'tiny_c4lauthor')
            : $category;
    }
}
