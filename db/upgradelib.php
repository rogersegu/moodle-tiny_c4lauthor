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

/**
 * Upgrade helpers for the C4L Author plugin.
 *
 * @package    tiny_c4lauthor
 * @copyright  2026 Roger Segú <rogersegu@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tiny_c4lauthor\components;

/**
 * Carry the two student-availability settings over to the per-component one.
 *
 * aimedatstudents and notintendedforstudents each listed the components students
 * could use, and the editor took the union of the two; staff were never restricted.
 * So a component named in either list becomes available to everyone, and one named
 * in neither becomes ACCESS_STAFF, which is exactly what it was.
 *
 * Without this, an upgraded site would fall back to the new defaults and an admin
 * who had deliberately withheld a component from students would find it offered to
 * them again, with nothing in the interface to say so.
 *
 * @return void
 */
function tiny_c4lauthor_migrate_component_access(): void {
    $aimed = get_config('tiny_c4lauthor', 'aimedatstudents');
    $notintended = get_config('tiny_c4lauthor', 'notintendedforstudents');

    // A setting that was never saved reads back as false, and one that was reads
    // back as a string — including the empty string, which is an admin unticking
    // every box and has to be honoured. With neither ever saved there is no choice
    // to carry over, and the new defaults already reproduce the intended behaviour.
    if ($aimed === false && $notintended === false) {
        return;
    }

    $listed = array_merge(
        is_string($aimed) ? explode(',', $aimed) : [],
        is_string($notintended) ? explode(',', $notintended) : []
    );
    $listed = array_filter($listed, fn($name) => $name !== '');

    $access = [];
    foreach (components::names() as $name) {
        $access[$name] = in_array($name, $listed, true)
            ? components::ACCESS_ALL
            : components::ACCESS_STAFF;
    }

    set_config(components::SETTING, components::encode($access), 'tiny_c4lauthor');

    // These only stay gone once settings.php stops declaring them: the
    // admin_apply_default_settings() pass that runs after the upgrade steps puts
    // back any declared setting that has no stored value. Unsetting them here is
    // still right, and the rows are unread either way.
    unset_config('aimedatstudents', 'tiny_c4lauthor');
    unset_config('notintendedforstudents', 'tiny_c4lauthor');
}
