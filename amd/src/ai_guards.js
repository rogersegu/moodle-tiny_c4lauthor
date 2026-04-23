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
 * Guards and filters applied to AI suggestions.
 *
 * @module      tiny_c4lauthor/ai_guards
 * @copyright   2026 Roger Segú <rogersegu@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {extractBlocks, getC4LSelector} from './ai_content';

export const contentFingerprint = (html) => {
    const str = (html || '').trim();
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
        hash = ((hash * 31) + str.charCodeAt(i)) % 2147483647;
    }
    return `${str.length}:${hash.toString(36)}`;
};

export const STORAGE_PREFIX = 'c4lauthor_analysed_';

export const checkAlreadyAnalysed = (storageKey, html) => {
    try {
        return localStorage.getItem(storageKey) === contentFingerprint(html);
    } catch (e) {
        return false;
    }
};

export const extractParagraphs = (html) => {
    const doc = new DOMParser().parseFromString(`<div id="root">${html || ''}</div>`, 'text/html');
    const root = doc.querySelector('#root');
    if (!root) {
        return [];
    }
    const allPs = Array.from(root.querySelectorAll('p'));
    const consumed = new Set();
    return allPs
        .map((p, i) => {
            if (consumed.has(i)) {
                return null;
            }
            if (p.classList.contains('c4l-spacer')) {
                return null;
            }
            if (p.closest(getC4LSelector())) {
                return null;
            }
            let text = (p.textContent || '').replace(/\s+/g, ' ').trim();
            if (!text) {
                return null;
            }
            const next = p.nextElementSibling;
            if (next && (next.tagName === 'UL' || next.tagName === 'OL')) {
                const items = Array.from(next.querySelectorAll(':scope > li'));
                const listText = items.map((li) => '- ' + (li.textContent || '').replace(/\s+/g, ' ').trim()).join('\n');
                if (listText) {
                    text += '\n' + listText;
                }
            } else if (text.endsWith(':') && next) {
                let nextText = '';
                const annotation = next.querySelector && next.querySelector('annotation[encoding="application/x-tex"]');
                if (annotation) {
                    nextText = (annotation.textContent || '').trim();
                } else {
                    nextText = (next.textContent || '').replace(/\s+/g, ' ').trim();
                }
                if (nextText) {
                    text += '\n' + nextText;
                }
                if (next.tagName === 'P') {
                    const nextIdx = allPs.indexOf(next);
                    if (nextIdx !== -1) {
                        consumed.add(nextIdx);
                    }
                } else {
                    const fallback = next.nextElementSibling;
                    if (fallback && fallback.tagName === 'P') {
                        const fallbackIdx = allPs.indexOf(fallback);
                        if (fallbackIdx !== -1) {
                            consumed.add(fallbackIdx);
                        }
                    }
                }
            }
            return {index: i, text};
        })
        .filter(Boolean);
};

export const validateContent = (html) => {
    const paragraphs = extractParagraphs(html);
    const blocks = extractBlocks(html);

    const freeParagraphs = paragraphs.length;
    const existingComponents = {};
    let totalExisting = 0;

    blocks.filter((b) => b.type === 'c4l').forEach((b) => {
        existingComponents[b.c4lComponent] = (existingComponents[b.c4lComponent] || 0) + 1;
        totalExisting++;
    });

    if (freeParagraphs < 3) {
        return {canAnalyse: false, reason: 'too_short', existingComponents, freeParagraphs};
    }
    if (totalExisting >= freeParagraphs) {
        return {canAnalyse: false, reason: 'saturated', existingComponents, freeParagraphs};
    }

    return {canAnalyse: true, existingComponents, freeParagraphs};
};

export const getProtectedIndices = (html) => {
    const blocks = extractBlocks(html);
    const protectedSet = new Set();

    const firstBlock = blocks[0];
    if (firstBlock && firstBlock.type === 'p') {
        protectedSet.add(firstBlock.pIndex);
    }

    return protectedSet;
};

export const getAdjacentToC4LIndices = (html) => {
    const blocks = extractBlocks(html);
    const adjacent = new Set();

    for (let i = 0; i < blocks.length; i++) {
        if (blocks[i].type === 'c4l') {
            if (i > 0 && blocks[i - 1].type === 'p') {
                adjacent.add(blocks[i - 1].pIndex);
            }
            if (i < blocks.length - 1 && blocks[i + 1].type === 'p') {
                adjacent.add(blocks[i + 1].pIndex);
            }
        }
    }

    return adjacent;
};

export const getSeparatedIndices = (html) => {
    const doc = new DOMParser().parseFromString(`<div id="root">${html || ''}</div>`, 'text/html');
    const root = doc.querySelector('#root');
    if (!root) {
        return new Set();
    }
    const allPs = Array.from(root.querySelectorAll('p'));
    const separated = new Set();
    allPs.forEach((p, i) => {
        const next = p.nextElementSibling;
        if (next && next.tagName !== 'P') {
            separated.add(i);
        }
    });
    return separated;
};

/**
 * @param {Array} suggestions - Raw suggestions from AI.
 * @param {Object} existingComponents - Map of component name to existing count.
 * @param {number} freeParagraphs - Number of free (non-C4L) paragraphs.
 * @param {Set} [adjacentToC4L] - pIndex values adjacent to existing C4L components.
 * @param {Set} [listSeparated] - pIndex values followed by a non-P element.
 * @param {Object} [aiRates] - Per-component rate limits {component: maxPer10Paragraphs}.
 * @returns {Array} Filtered suggestions.
 */
export const filterSuggestions = (
    suggestions, existingComponents, freeParagraphs,
    adjacentToC4L = new Set(), listSeparated = new Set(), aiRates = {}
) => {
    const totalExisting = Object.values(existingComponents).reduce((sum, n) => sum + n, 0);
    const totalContent = freeParagraphs + totalExisting;
    const maxTotal = Math.max(0, Math.ceil(totalContent / 2) - totalExisting);

    // Per-component caps: maxRate per 10 paragraphs, scaled to actual paragraph count.
    const RATE_BASE = 10;
    const componentCounts = {};
    const getMaxForComponent = (comp) => {
        const rate = aiRates[comp];
        if (rate === undefined || rate === null) {
            return Infinity;
        }
        const existing = existingComponents[comp] || 0;
        return Math.max(0, Math.ceil(totalContent * rate / RATE_BASE) - existing);
    };

    const filtered = [];
    const acceptedIndices = new Set();

    for (const s of suggestions) {
        if (filtered.length >= maxTotal) {
            break;
        }
        if (adjacentToC4L.has(s.targetindex)) {
            continue;
        }
        const prevConsecutive = acceptedIndices.has(s.targetindex - 1) && !listSeparated.has(s.targetindex - 1);
        const nextConsecutive = acceptedIndices.has(s.targetindex + 1) && !listSeparated.has(s.targetindex);
        if (prevConsecutive || nextConsecutive) {
            continue;
        }
        // Per-component rate limit check.
        const compCount = componentCounts[s.component] || 0;
        if (compCount >= getMaxForComponent(s.component)) {
            continue;
        }
        componentCounts[s.component] = compCount + 1;
        acceptedIndices.add(s.targetindex);
        filtered.push(s);
    }

    return filtered;
};
