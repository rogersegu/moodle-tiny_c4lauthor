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
 * Extract structured blocks from editor HTML for AI analysis.
 *
 * @module      tiny_c4lauthor/ai_content
 * @copyright   2026 Roger Segú <rogersegu@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const clean = (s) => (s || '').replace(/\s+/g, ' ').trim();

const C4L_CLASS_MAP = {
    'c4lv-keyconcept': 'keyconcept',
    'c4lv-tip': 'tip',
    'c4lv-reminder': 'reminder',
    'c4lv-attention': 'attention',
    'c4lv-learningoutcomes': 'learning_outcomes',
    'c4lv-quote': 'quote',
    'c4lv-dodontcards': 'dodontcards',
    'c4lv-readingcontext': 'readingcontext',
    'c4lv-example': 'example',
    'c4lv-figure': 'figure',
    'c4lv-tag': 'tag',
    'c4lv-inlinetag': 'inlinetag',
    'c4lv-estimatedtime': 'estimatedtime',
    'c4lv-duedate': 'duedate',
    'c4lv-proceduralcontext': 'proceduralcontext',
    'c4lv-gradingvalue': 'gradingvalue',
    'c4lv-expectedfeedback': 'expectedfeedback',
    'c4lv-allpurposecard': 'allpurposecard',
    'c4lv-conceptreview': 'conceptreview',
    'c4lv-furtherreading': 'furtherreading',
    'c4lv-aiuseallowed': 'aiuseallowed',
    'c4lv-aiusenotallowed': 'aiusenotallowed',
    'c4lv-aiusereported': 'aiusereported',
};

const detectC4LComponent = (node) => {
    if (!node.classList) {
        return null;
    }
    for (const [cls, comp] of Object.entries(C4L_CLASS_MAP)) {
        if (node.classList.contains(cls)) {
            return comp;
        }
    }
    return null;
};

export const extractBlocks = (html) => {
    const doc = new DOMParser().parseFromString(`<div id="root">${html || ''}</div>`, 'text/html');
    const root = doc.querySelector('#root');
    if (!root) {
        return [];
    }

    const allPs = Array.from(root.querySelectorAll('p'));
    const pPositionMap = new Map();
    allPs.forEach((p, i) => pPositionMap.set(p, i));

    const blocks = [];
    let c4lIndex = 0;

    // eslint-disable-next-line complexity
    const walk = (node) => {
        if (!node || node.nodeType !== 1) {
            return;
        }

        const tag = node.tagName.toLowerCase();
        if (/^h[1-6]$/.test(tag)) {
            const text = clean(node.textContent);
            if (text) {
                blocks.push({type: tag, text});
            }
            return;
        }

        if (tag === 'p' && node.classList.contains('c4l-spacer')) {
            return;
        }

        // Detect C4L components before tag-specific checks (components use div, p, figure, span...).
        const component = detectC4LComponent(node);
        if (component) {
            let text;
            if (component === 'learning_outcomes') {
                const items = Array.from(node.querySelectorAll('.c4l-learningoutcomes-list li'));
                text = items.map((li) => clean(li.textContent)).join('; ');
            }
            if (!text) {
                text = clean(node.textContent);
            }
            const parentIsInlineGroup = node.parentNode
                && node.parentNode.classList
                && node.parentNode.classList.contains('c4l-inline-group');
            blocks.push({
                type: 'c4l',
                text,
                html: parentIsInlineGroup ? node.parentNode.innerHTML : node.innerHTML,
                c4lComponent: component,
                c4lIndex: c4lIndex++,
                hasInlineGroup: parentIsInlineGroup,
            });
            return;
        }

        if (tag === 'p') {
            const text = clean(node.textContent);
            if (!text) {
                return;
            }
            blocks.push({type: 'p', text, pIndex: pPositionMap.get(node)});
            return;
        }

        if (tag === 'math' || tag.includes('-')) {
            const annotation = node.querySelector('annotation[encoding="application/x-tex"]');
            const text = annotation ? clean(annotation.textContent) : clean(node.textContent);
            const blockHtml = node.outerHTML || '';
            if (text) {
                blocks.push({type: 'math', text, html: blockHtml});
            }
            return;
        }

        if (tag === 'ul' || tag === 'ol') {
            const items = Array.from(node.querySelectorAll(':scope > li'));
            items.forEach((li) => {
                const text = clean(li.textContent);
                if (text) {
                    blocks.push({type: 'li', text});
                }
            });
            return;
        }

        const children = Array.from(node.children || []);
        children.forEach(walk);
    };

    Array.from(root.children || []).forEach(walk);

    return blocks;
};

/**
 * CSS selector matching ALL C4L component classes.
 * @returns {string}
 */
export const getC4LSelector = () =>
    Object.keys(C4L_CLASS_MAP).map((cls) => '.' + cls).join(', ');

/**
 * Array of all C4L component names.
 * @returns {string[]}
 */
export const getComponentNames = () => Object.values(C4L_CLASS_MAP);
