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

use core\context;

/**
 * Who may use each component: everyone, staff only, or nobody.
 *
 * One setting holds a level per component. The components and their default levels
 * come from the declarations, so a component added in a later release is usable
 * without an administrator resaving anything.
 *
 * Component availability was funded by Hochschule Hannover <https://www.hs-hannover.de>.
 *
 * @package    tiny_c4lauthor
 * @copyright  2026 Roger Segú <rogersegu@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class component_access {
    /** @var string Anyone who may use the plugin. */
    public const ACCESS_ALL = 'all';

    /** @var string Only users the plugin does not treat as students. */
    public const ACCESS_STAFF = 'staff';

    /** @var string Nobody: the component is withdrawn from the plugin. */
    public const ACCESS_NONE = 'none';

    /** @var string The config setting holding the per-component access levels. */
    public const SETTING = 'componentaccess';

    /**
     * @var string What separates staff from students.
     *
     * The plugin's own capability, so an administrator decides this directly instead
     * of it following from whether the user can see the gradebook — another plugin's
     * capability, which a course creator fails, leaving them counted as a student.
     * A capability rather than a list of roles on purpose: it answers the question in
     * the context the editor is open in, so it respects an override at category,
     * course or activity level and a user holding several roles.
     */
    public const STAFF_CAPABILITY = 'tiny/c4lauthor:useallcomponents';

    /**
     * The three access levels, in order of decreasing reach.
     *
     * @return string[]
     */
    public static function levels(): array {
        return [self::ACCESS_ALL, self::ACCESS_STAFF, self::ACCESS_NONE];
    }

    /**
     * The level each component defaults to, keyed by name.
     *
     * A component declaring students is for everyone; the rest are staff only.
     *
     * @param context $context Where the editor is used.
     * @return string[]
     */
    public static function defaults(context $context): array {
        $defaults = [];
        foreach (components::get_components($context) as $name => $component) {
            $defaults[$name] = empty($component['students']) ? self::ACCESS_STAFF : self::ACCESS_ALL;
        }
        return $defaults;
    }

    /**
     * Unpack the stored value into a level for every component.
     *
     * What the value does not mention keeps that component's default, and a value of
     * false — which is what get_config() returns for a setting never saved — yields
     * the defaults for everything.
     *
     * @param string|false|null $value The stored setting value.
     * @param context $context Where the editor is used.
     * @return string[] Access level keyed by component name.
     */
    public static function decode($value, context $context): array {
        $access = self::defaults($context);
        if (!is_string($value) || $value === '') {
            return $access;
        }
        foreach (explode(',', $value) as $pair) {
            $parts = explode(':', trim($pair), 2);
            if (count($parts) !== 2) {
                continue;
            }
            [$name, $level] = $parts;
            if (isset($access[$name]) && in_array($level, self::levels(), true)) {
                $access[$name] = $level;
            }
        }
        return $access;
    }

    /**
     * Pack a name => level map into the single value the setting stores.
     *
     * @param string[] $access Access level keyed by component name.
     * @return string
     */
    public static function encode(array $access): string {
        $pairs = [];
        foreach ($access as $name => $level) {
            if (in_array($level, self::levels(), true)) {
                $pairs[] = $name . ':' . $level;
            }
        }
        return implode(',', $pairs);
    }

    /**
     * The components a user may insert, given who they are.
     *
     * @param bool $isstudent Whether the plugin treats this user as a student.
     * @param context $context Where the editor is used.
     * @return string[] Component names.
     */
    public static function usable_by(bool $isstudent, context $context): array {
        $usable = [];
        foreach (self::decode(get_config('tiny_c4lauthor', self::SETTING), $context) as $name => $level) {
            if ($level === self::ACCESS_NONE) {
                continue;
            }
            if ($isstudent && $level !== self::ACCESS_ALL) {
                continue;
            }
            $usable[] = $name;
        }
        return $usable;
    }

    /**
     * The roles that count as staff on this site, by their localised names.
     *
     * @return string[]
     */
    public static function staff_role_names(): array {
        $context = \core\context\system::instance();
        $roles = get_roles_with_capability(self::STAFF_CAPABILITY, CAP_ALLOW, $context);
        if (!$roles) {
            return [];
        }
        return array_values(role_fix_names($roles, $context, ROLENAME_ORIGINAL, true));
    }
}
