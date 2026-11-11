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
 * Upgrade steps for the C4L Author plugin.
 *
 * @package    tiny_c4lauthor
 * @copyright  2026 Roger Segú <rogersegu@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Run the plugin's upgrade steps.
 *
 * @param int $oldversion The version the site is upgrading from.
 * @return bool
 */
function xmldb_tiny_c4lauthor_upgrade($oldversion): bool {
    require_once(__DIR__ . '/upgradelib.php');

    if ($oldversion < 2026111100) {
        tiny_c4lauthor_migrate_component_access();
        upgrade_plugin_savepoint(true, 2026111100, 'tiny', 'c4lauthor');
    }

    return true;
}
