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
 * Reading and writing a component's text as the plain text precision mode shows.
 *
 * A box in precision mode holds text, and the component holds markup. These two turn
 * one into the other without losing what the author did not touch: a bold word or a
 * link they left alone stays, because only what changed is written, and it is written
 * where it lies.
 *
 * Used by precise_ui for the paragraphs field type, and by the handlers in
 * precise_handlers that write a region of their own.
 *
 * @module      tiny_c4lauthor/precise_text
 * @copyright   2026 Roger Segú <rogersegu@gmail.com>, bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Read blocks as one text, a blank line between them, the way paragraphs are written.
 * A block with nothing in it is not a paragraph of the text and is left out.
 *
 * @param {Iterable<HTMLElement>} blocks
 * @returns {string}
 */
export const blocksToText = (blocks) => Array.from(blocks)
    .map((block) => block.textContent.trim())
    .filter((text) => text !== '')
    .join('\n\n');

/**
 * Write a text into a block, keeping the markup of what did not change. The author
 * edits plain text here, so a bold word or a link they left alone has to survive.
 *
 * @param {HTMLElement} block
 * @param {string} text
 */
export const setBlockText = (block, text) => {
    // The box holds the text trimmed, so the comparison is with the trimmed text, and
    // what the markup is indented with is left out of the change and survives it.
    const raw = block.textContent;
    const lead = raw.length - raw.trimStart().length;
    const current = raw.trim();
    if (current === text) {
        return;
    }
    // What changed is what lies between the common start and the common end.
    let start = 0;
    while (start < current.length && start < text.length && current[start] === text[start]) {
        start++;
    }
    let end = 0;
    while (end < current.length - start && end < text.length - start &&
        current[current.length - 1 - end] === text[text.length - 1 - end]) {
        end++;
    }
    const inserted = text.slice(start, text.length - end);
    if (!spliceBlockText(block, lead + start, current.length - start - end, inserted)) {
        block.textContent = text;
    }
};

/**
 * Replace a range of a block's text where it lives, in its text nodes, so that the
 * elements around them stay. Elements left with nothing in them go.
 *
 * @param {HTMLElement} block
 * @param {number} start — where the change begins, in characters of the block's text
 * @param {number} removed — how many characters go
 * @param {string} inserted — what takes their place
 * @returns {boolean} false when the block has no text node to write into
 */
const spliceBlockText = (block, start, removed, inserted) => {
    const walker = block.ownerDocument.createTreeWalker(block, NodeFilter.SHOW_TEXT);
    const nodes = [];
    while (walker.nextNode()) {
        nodes.push(walker.currentNode);
    }
    if (nodes.length === 0) {
        return false;
    }
    const end = start + removed;
    let offset = 0;
    let written = false;
    nodes.forEach((node) => {
        const length = node.data.length;
        const from = Math.min(Math.max(start - offset, 0), length);
        const to = Math.min(Math.max(end - offset, 0), length);
        // What was added goes into the first node the change reaches.
        if (from < to || (!written && start < offset + length)) {
            node.data = node.data.slice(0, from) + (written ? '' : inserted) + node.data.slice(to);
            written = true;
        }
        offset += length;
    });
    if (!written) {
        // Added at the very end: it belongs to the block, not to the markup the last
        // word happens to wear.
        block.appendChild(block.ownerDocument.createTextNode(inserted));
    }
    block.querySelectorAll('*').forEach((el) => {
        if (!el.textContent && !el.querySelector('img, br') && !['BR', 'IMG'].includes(el.tagName)) {
            el.remove();
        }
    });
    return true;
};

/**
 * Write a text back as the blocks it is made of: one per paragraph, with the tag the
 * blocks had and in the place they were, their parent being free to hold more than
 * them. A block with nothing in it goes, the box not having shown it; a text emptied
 * altogether keeps one block, so that there is somewhere to write.
 *
 * @param {HTMLElement} parent the element the blocks belong to
 * @param {Array<HTMLElement>} blocks the blocks the text was read from, in order
 * @param {string} text
 * @param {string} [tagname] the tag to make blocks with when there are none to copy
 */
export const setParagraphs = (parent, blocks, text, tagname) => {
    const texts = String(text).split(/\n\s*\n/).map((one) => one.trim()).filter((one) => one !== '');
    // A text emptied altogether still leaves the block the author writes in.
    const wanted = texts.length ? texts : [''];
    const tag = (blocks[0] && blocks[0].tagName) || tagname || 'P';
    const after = blocks.length ? blocks[blocks.length - 1].nextSibling : null;
    // The blocks the box showed, in its order; an empty one was not shown.
    const shown = blocks.filter((block) => block.textContent.trim() !== '');
    blocks.filter((block) => !shown.includes(block)).forEach((block) => block.remove());
    const written = wanted.map((one, index) => {
        const block = shown[index] || parent.ownerDocument.createElement(tag);
        setBlockText(block, one);
        return block;
    });
    // Paragraphs the author took out.
    shown.slice(wanted.length).forEach((block) => block.remove());
    // Put them in order, from the back, moving only what is out of place.
    let anchor = after;
    for (let index = written.length - 1; index >= 0; index--) {
        if (written[index].parentNode !== parent || written[index].nextSibling !== anchor) {
            parent.insertBefore(written[index], anchor);
        }
        anchor = written[index];
    }
};
