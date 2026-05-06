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
 * Tiny C4L Author plugin configuration.
 *
 * @module      tiny_c4lauthor/configuration
 * @copyright   2022 Marc Català <reskit@gmail.com>
 * @copyright   2026 Roger Segú <rogersegu@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {component as buttonName, quickInsertMenuName, convertMenuName} from './common';

const configureToolbar = (toolbar) => {
    return toolbar.map((section) => {
        if (section.name === 'content') {
            section.items.push(buttonName);
        }
        return section;
    });
};

const configureQuickbars = (selectionToolbar) => {
    // Append the C4L quick-insert and convert menu names to the quickbar config.
    // The actual menu buttons are only registered on the inner editor (inside
    // the modal), so TinyMCE silently skips them on the outer editor.
    if (selectionToolbar && selectionToolbar.indexOf(quickInsertMenuName) === -1) {
        return selectionToolbar + ' | ' + quickInsertMenuName + ' ' + convertMenuName;
    }
    return selectionToolbar;
};

export const configure = (instanceConfig) => {
    // Enable TinyMCE's built-in accordion plugin for details/summary support.
    const plugins = instanceConfig.plugins || [];
    if (!plugins.includes('accordion')) {
        plugins.push('accordion');
    }

    /* eslint-disable camelcase */
    return {
        toolbar: configureToolbar(instanceConfig.toolbar),
        plugins,
        quickbars_selection_toolbar: configureQuickbars(
            instanceConfig.quickbars_selection_toolbar
        ),
    };
    /* eslint-enable camelcase */
};
