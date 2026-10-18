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

namespace tiny_c4lauthor\admin;

use admin_setting;
use html_writer;
use tiny_c4lauthor\components;

/**
 * Who may use each component: one row per component, one choice per row.
 *
 * Stored as a single config value, so the whole matrix is one setting rather than
 * one per component. The component list comes from tiny_c4lauthor\components, so
 * a component cannot be left out of this page by accident.
 *
 * @package    tiny_c4lauthor
 * @copyright  2026 Roger Segú <rogersegu@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setting_componentaccess extends admin_setting {
    /**
     * Build the setting.
     *
     * @param string $name Unqualified setting name.
     * @param string $visiblename Localised name.
     * @param string $description Localised description.
     */
    public function __construct($name, $visiblename, $description) {
        parent::__construct($name, $visiblename, $description, components::default_access());
    }

    /**
     * The stored access map, or null when nothing has been stored yet.
     *
     * Returning null is what makes admin_apply_default_settings() write the
     * defaults on install, so a fresh site gets real rows rather than relying on
     * every reader to fall back.
     *
     * @return array|null
     */
    public function get_setting() {
        $value = $this->config_read($this->name);
        if ($value === null) {
            return null;
        }
        return components::decode($value);
    }

    /**
     * Store the submitted choices.
     *
     * @param array|string $data Level keyed by component name, or an encoded value.
     * @return string Empty on success, a localised error otherwise.
     */
    public function write_setting($data) {
        if (is_string($data)) {
            $data = components::decode($data);
        }
        if (!is_array($data)) {
            return get_string('componentaccess_invalid', 'tiny_c4lauthor');
        }

        $levels = components::access_levels();
        $defaults = components::default_access();
        $access = [];
        foreach (components::names() as $name) {
            $level = $data[$name] ?? null;
            $access[$name] = in_array($level, $levels, true) ? $level : $defaults[$name];
        }

        return $this->config_write($this->name, components::encode($access)) ? '' : get_string('errorsetting', 'admin');
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
            $data = components::default_access();
        }

        $levels = components::access_levels();
        $types = components::types();

        $head = html_writer::tag('th', get_string('componentaccess_component', 'tiny_c4lauthor'), ['scope' => 'col']);
        foreach ($levels as $level) {
            $heading = get_string('componentaccess_' . $level, 'tiny_c4lauthor');
            // Say who staff are, since the plugin works it out from a capability
            // rather than from roles. Listing whoever holds it on this site keeps the
            // heading true when the site's permissions are not the stock ones.
            if ($level === components::ACCESS_STAFF) {
                $who = components::staff_role_names();
                if ($who) {
                    $heading .= html_writer::div(
                        get_string('componentaccess_staff_who', 'tiny_c4lauthor', implode(', ', $who)),
                        'text-muted small font-weight-normal fw-normal'
                    );
                }
            }
            $head .= html_writer::tag('th', $heading, ['scope' => 'col', 'class' => 'text-center']);
        }
        $rows = html_writer::tag('tr', $head);

        $lasttype = null;
        foreach (components::names() as $name) {
            // A group header whenever the type changes, so twenty-five rows stay readable.
            if ($types[$name] !== $lasttype) {
                $lasttype = $types[$name];
                $rows .= html_writer::tag('tr', html_writer::tag(
                    'th',
                    get_string($lasttype, 'tiny_c4lauthor'),
                    ['colspan' => count($levels) + 1, 'scope' => 'colgroup']
                ));
            }

            $label = get_string($name, 'tiny_c4lauthor');
            $cells = html_writer::tag('td', $label);
            foreach ($levels as $level) {
                $id = $this->get_id() . '_' . $name . '_' . $level;
                $attrs = [
                    'type' => 'radio',
                    'id' => $id,
                    'name' => $this->get_full_name() . '[' . $name . ']',
                    'value' => $level,
                ];
                if (($data[$name] ?? null) === $level) {
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
            $rows .= html_writer::tag('tr', $cells);
        }

        $table = html_writer::tag(
            'table',
            $rows,
            ['class' => 'table table-sm generaltable tiny_c4lauthor-componentaccess']
        );

        return format_admin_setting($this, $this->visiblename, $table, $this->description, false, '', null, $query);
    }
}
