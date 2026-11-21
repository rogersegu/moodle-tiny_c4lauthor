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
 * Reducing a component to the content the author wrote.
 *
 * Removing a component's style and converting it to another both need the same
 * thing: the content, out of its scaffolding.
 *
 * Component availability was funded by Hochschule Hannover <https://www.hs-hannover.de>.
 *
 * @module      tiny_c4lauthor/plain_content
 * @copyright   2026 Roger Segú <rogersegu@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Elements that only carry C4L layout: they are dropped and their content kept.
const layoutTags = 'div, section, article, aside, header, footer, figure';

// Elements that can hold content on their own, so loose text next to them must not
// be swallowed into the same paragraph.
const blockTags = /^(p|h1|h2|h3|h4|h5|h6|ul|ol|dl|li|dt|dd|table|blockquote|pre|hr|figcaption)$/i;

// Attributes that exist only to style or label a C4L component.
const styleAttrs = ['class', 'id', 'style', 'role', 'aria-label', 'contenteditable'];

// Labels the plugin generates itself, as opposed to content the author wrote: the
// non-editable list headings, and the c4l-*-title headings.
const chromeSelector = '[contenteditable="false"], [class*="c4l-"][class*="-title"]';

/**
 * Wrap the loose text and inline nodes of an element into paragraphs, so that the
 * block structure survives once its container is dropped.
 *
 * @param {HTMLElement} parent - The element whose children are normalised.
 * @param {Document} doc - The document the element belongs to.
 */
const wrapLooseChildren = (parent, doc) => {
    let paragraph = null;
    [...parent.childNodes].forEach((node) => {
        // Layout containers are boundaries too: they get dropped later, and their own
        // children are normalised at that point.
        if (node.nodeType === 1 &&
                (blockTags.test(node.tagName) || node.matches(layoutTags))) {
            paragraph = null;
            return;
        }
        if (node.nodeType === 3 && !node.textContent.trim()) {
            node.remove();
            return;
        }
        if (!paragraph) {
            paragraph = doc.createElement('p');
            node.before(paragraph);
        }
        paragraph.appendChild(node);
    });
};

/**
 * Reduce a component to the content the author actually wrote: no C4L classes, no
 * layout scaffolding, no decoration and no generated labels, normalised into
 * block-level elements.
 *
 * @param {HTMLElement} compEl - The component element.
 * @param {Document} doc - The document the element belongs to.
 * @returns {HTMLElement} A detached div holding the content.
 */
export const extractComponentContent = (compEl, doc) => {
    const holder = doc.createElement('div');
    holder.appendChild(compEl.cloneNode(true));

    // Drop the generated titles, which are component chrome rather than content.
    holder.querySelectorAll(chromeSelector).forEach((el) => el.remove());

    // Drop the decorative elements, which hold no content of their own.
    [...holder.querySelectorAll('*')].reverse().forEach((el) => {
        if (el.textContent.trim() ||
            /^(img|video|audio|iframe|br|input|hr)$/i.test(el.tagName) ||
            el.querySelector('img, video, audio, iframe, br, input')) {
            return;
        }
        el.remove();
    });

    // A figcaption is only valid inside a figure, so turn it into a paragraph.
    holder.querySelectorAll('figcaption').forEach((caption) => {
        const paragraph = doc.createElement('p');
        paragraph.innerHTML = caption.innerHTML;
        caption.replaceWith(paragraph);
    });

    // Drop the layout containers, keeping their content in place.
    let container = holder.querySelector(layoutTags);
    while (container) {
        wrapLooseChildren(container, doc);
        container.replaceWith(...container.childNodes);
        container = holder.querySelector(layoutTags);
    }
    wrapLooseChildren(holder, doc);

    // Remove every presentational attribute left behind.
    holder.querySelectorAll('*').forEach((el) => {
        styleAttrs.forEach((attr) => el.removeAttribute(attr));
    });

    return holder;
};
