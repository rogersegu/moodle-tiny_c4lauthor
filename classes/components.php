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
 * The components the plugin ships, and who may use them.
 *
 * The markup of each component lives in amd/src/components.js, which PHP cannot
 * read. This class holds the part the server side needs — the names and the access
 * each one defaults to — so that the settings page, the editor configuration and
 * the upgrade step work from one list instead of three. The two settings this
 * replaces were written out by hand in settings.php and had already drifted: four
 * components were missing from both, which left them unusable for students with no
 * way for an admin to turn them on.
 *
 * Component availability was funded by Hochschule Hannover <https://www.hs-hannover.de>.
 *
 * @package    tiny_c4lauthor
 * @copyright  2026 Roger Segú <rogersegu@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class components {
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
     *
     * A capability rather than a list of roles on purpose. It answers the question in
     * the context the editor is open in, so it respects an override at category,
     * course or activity level and a user holding several roles; a fixed list of roles
     * can express none of that, and would store role ids that mean nothing on another
     * site. The settings page still lists whoever holds it here, so the label cannot
     * claim more than the check delivers.
     */
    public const STAFF_CAPABILITY = 'tiny/c4lauthor:useallcomponents';

    /**
     * The roles that count as staff on this site, by their localised names.
     *
     * @return string[]
     */
    public static function staff_role_names(): array {
        $context = \context_system::instance();
        $roles = get_roles_with_capability(self::STAFF_CAPABILITY, CAP_ALLOW, $context);
        if (!$roles) {
            return [];
        }
        return array_values(role_fix_names($roles, $context, ROLENAME_ORIGINAL, true));
    }

    /**
     * Every component, with the type it is filed under and the access it defaults
     * to. The defaults reproduce what the aimedatstudents and notintendedforstudents
     * settings did, so that neither a new site nor an upgraded one changes behaviour:
     * the twelve that were aimed at students default to everyone, and the rest —
     * including the four that were missing from both settings — to ACCESS_STAFF.
     *
     * Access does not follow the type: attention is procedural and allpurposecard is
     * a helper, yet both were aimed at students.
     */
    private const COMPONENTS = [
        'keyconcept' => ['type' => 'contextual', 'access' => self::ACCESS_ALL],
        'tip' => ['type' => 'contextual', 'access' => self::ACCESS_ALL],
        'reminder' => ['type' => 'contextual', 'access' => self::ACCESS_ALL],
        'quote' => ['type' => 'contextual', 'access' => self::ACCESS_ALL],
        'dodontcards' => ['type' => 'contextual', 'access' => self::ACCESS_ALL],
        'readingcontext' => ['type' => 'contextual', 'access' => self::ACCESS_ALL],
        'example' => ['type' => 'contextual', 'access' => self::ACCESS_ALL],
        'figure' => ['type' => 'contextual', 'access' => self::ACCESS_ALL],
        'tag' => ['type' => 'contextual', 'access' => self::ACCESS_ALL],
        'inlinetag' => ['type' => 'contextual', 'access' => self::ACCESS_ALL],
        'conceptreview' => ['type' => 'contextual', 'access' => self::ACCESS_STAFF],
        'furtherreading' => ['type' => 'contextual', 'access' => self::ACCESS_STAFF],
        'attention' => ['type' => 'procedural', 'access' => self::ACCESS_ALL],
        'estimatedtime' => ['type' => 'procedural', 'access' => self::ACCESS_STAFF],
        'duedate' => ['type' => 'procedural', 'access' => self::ACCESS_STAFF],
        'proceduralcontext' => ['type' => 'procedural', 'access' => self::ACCESS_STAFF],
        'learningoutcomes' => ['type' => 'procedural', 'access' => self::ACCESS_STAFF],
        'statement' => ['type' => 'procedural', 'access' => self::ACCESS_STAFF],
        'gradingvalue' => ['type' => 'evaluative', 'access' => self::ACCESS_STAFF],
        'expectedfeedback' => ['type' => 'evaluative', 'access' => self::ACCESS_STAFF],
        'aiuseallowed' => ['type' => 'evaluative', 'access' => self::ACCESS_STAFF],
        'aiusenotallowed' => ['type' => 'evaluative', 'access' => self::ACCESS_STAFF],
        'aiusereported' => ['type' => 'evaluative', 'access' => self::ACCESS_STAFF],
        'assessment' => ['type' => 'evaluative', 'access' => self::ACCESS_STAFF],
        'allpurposecard' => ['type' => 'helper', 'access' => self::ACCESS_ALL],
    ];

    /**
     * The component names, in the order they are declared.
     *
     * @return string[]
     */
    public static function names(): array {
        return array_keys(self::COMPONENTS);
    }

    /**
     * The type each component is filed under, keyed by name.
     *
     * @return string[]
     */
    public static function types(): array {
        return array_map(fn($info) => $info['type'], self::COMPONENTS);
    }

    /**
     * The access level each component defaults to, keyed by name.
     *
     * @return string[]
     */
    public static function default_access(): array {
        return array_map(fn($info) => $info['access'], self::COMPONENTS);
    }

    /**
     * The three access levels, in order of decreasing reach.
     *
     * @return string[]
     */
    public static function access_levels(): array {
        return [self::ACCESS_ALL, self::ACCESS_STAFF, self::ACCESS_NONE];
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
            $pairs[] = $name . ':' . $level;
        }
        return implode(',', $pairs);
    }

    /**
     * Unpack the stored value into a level for every known component.
     *
     * Anything the value does not mention falls back to that component's default,
     * so a component added in a later release is usable without the admin having to
     * resave the setting. A value of false, which is what get_config() returns when
     * the setting has never been saved, yields the defaults for everything.
     *
     * @param string|false|null $value The stored setting value.
     * @return string[] Access level keyed by component name.
     */
    public static function decode($value): array {
        $access = self::default_access();
        if (!is_string($value) || $value === '') {
            return $access;
        }
        foreach (explode(',', $value) as $pair) {
            $parts = explode(':', trim($pair), 2);
            if (count($parts) !== 2) {
                continue;
            }
            [$name, $level] = $parts;
            if (isset($access[$name]) && in_array($level, self::access_levels(), true)) {
                $access[$name] = $level;
            }
        }
        return $access;
    }

    /**
     * The components a user may insert, given who they are.
     *
     * @param bool $isstudent Whether the plugin treats this user as a student.
     * @param string|false|null $value The stored setting value.
     * @return string[] Component names.
     */
    public static function usable_by($isstudent, $value): array {
        $usable = [];
        foreach (self::decode($value) as $name => $level) {
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
}
