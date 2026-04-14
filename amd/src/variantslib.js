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
 * Variants helper for C4L Author plugin.
 *
 * @module      tiny_c4lauthor/variantslib
 * @copyright   2023 Marc Català <reskit@gmail.com>
 * @copyright   2026 Roger Segú
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import {variants as VARIANTS} from './variants';

const variantsPreferenceName = 'c4lauthor_components_variants';
let variantPreferences = {};

export const loadVariantPreferences = async(components) => {
    const request = {
        methodname: 'core_user_get_user_preferences',
        args: {
            name: variantsPreferenceName
        }
    };

    return Ajax.call([request])[0]
        .then(result => {
            let comp = {};
            let rawPreferences = {};
            let variantComp = {};
            let variantObj = {};
            try {
                rawPreferences = JSON.parse(result.preferences[0].value);
            } catch (e) {
                Notification.exception(e);
            }

            if (rawPreferences !== null) {
                Object.keys(rawPreferences).forEach(preference => {
                    comp = components.find(component => component.id == preference);
                    if (comp != undefined) {
                        variantPreferences[comp.name] = [];
                        rawPreferences[preference].forEach((variant) => {
                            variantObj = VARIANTS.find(element => element.id == variant);
                            if (variantObj != undefined) {
                                variantComp = comp.variants.find(element => element == variantObj.name);
                                if (variantComp != undefined) {
                                    variantPreferences[comp.name].push(variantObj.name);
                                }
                            }
                        });
                    }
                });
            }
    }).catch(Notification.exception);
};

export const saveVariantPreferences = (components) => {
    let comp = {};
    let rawPreferences = {};
    let variantObj = {};
    Object.keys(variantPreferences).forEach(preference => {
        comp = components.find(component => component.name == preference);
        if (comp != undefined) {
            rawPreferences[comp.id] = [];
            variantPreferences[preference].forEach((variant) => {
                variantObj = VARIANTS.find(element => element.name == variant);
                if (variantObj != undefined) {
                    rawPreferences[comp.id].push(variantObj.id);
                }
            });
        }
    });

    const request = {
        methodname: 'core_user_update_user_preferences',
        args: {
            preferences: [
                {
                    type: variantsPreferenceName,
                    value: JSON.stringify(rawPreferences)
                }
            ]
        }
    };

    return Ajax.call([request])[0].catch(Notification.exception);
};

export const variantExists = (component, variant) => {
    return variantPreferences?.[component] && variantPreferences[component].indexOf(variant) !== -1;
};

export const getVariantsClass = (component) => {
    let variants = [];
    if (variantPreferences?.[component]) {
        variantPreferences[component].forEach(variant => {
            variants.push('c4l-' + variant + '-variant');
        });
    }
    return variants;
};

export const getVariantsHtml = (component) => {
    let variantsHtml = '';
    let variantObj = {};
    if (variantPreferences?.[component]) {
        variantPreferences[component].forEach(variant => {
            variantObj = VARIANTS.find(element => element.name == variant);
            if (variantObj != undefined) {
                variantsHtml += variantObj.html;
            }
        });
    }
    return variantsHtml;
};

export const getVariantHtml = (variant) => {
    let variantHtml = [];
    let variantObj = {};
    variantObj = VARIANTS.find(element => element.name == variant);
    if (variantObj != undefined) {
        variantHtml = variantObj.html;
    }
    return variantHtml;
};

export const addVariant = (component, variant) => {
    if (!variantPreferences?.[component]) {
        variantPreferences[component] = [];
    }
    if (!variantExists(component, variant)) {
        variantPreferences[component].push(variant);
    }
};

export const removeVariant = (component, variant) => {
    const index = variantPreferences[component].indexOf(variant);
    if (index !== -1) {
        delete variantPreferences[component][index];
    }
};
