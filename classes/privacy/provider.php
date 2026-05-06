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

namespace tiny_c4lauthor\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\writer;

/**
 * Privacy API implementation for the C4L Author plugin.
 *
 * @package     tiny_c4lauthor
 * @category    privacy
 * @copyright   2023 Marc Català <reskit@gmail.com>
 * @copyright   2026 Roger Segú
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\user_preference_provider {
    /**
     * Returns metadata about the user data stored by this plugin.
     *
     * @param collection $collection The collection to add metadata to.
     * @return collection The updated collection.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_user_preference(
            'c4lauthor_components_variants',
            'privacy:preference:components_variants'
        );
        return $collection;
    }

    /**
     * Export user preferences for this plugin.
     *
     * @param int $userid The user ID.
     */
    public static function export_user_preferences(int $userid) {
        $variants = get_user_preferences('c4lauthor_components_variants', null, $userid);
        if ($variants !== null) {
            writer::export_user_preference(
                'tiny_c4lauthor',
                'c4lauthor_components_variants',
                $variants,
                get_string('privacy:preference:components_variants', 'tiny_c4lauthor')
            );
        }
    }
}
