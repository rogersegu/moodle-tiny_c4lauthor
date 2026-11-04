---
audience: teacher, manager
pagetypes:
  - mod-page-mod
  - mod-label-mod
  - mod-book-mod
  - mod-book-edit
  - mod-assign-mod
  - mod-forum-mod
  - mod-forum-post
  - mod-quiz-mod
  - mod-lesson-mod
  - mod-lesson-editpage
  - mod-wiki-mod
  - mod-wiki-edit
  - mod-folder-mod
  - mod-url-mod
  - mod-resource-mod
  - course-edit
  - course-editsection
---

# C4L Author — teacher guide

C4L Author lets you build course content from ready-made components, such as key
concepts, tips, timelines and tabs, without writing any HTML.

## Opening C4L Author

Wherever you see the TinyMCE editor, select the **C4L Author** button in its toolbar. A
large editor opens with your content in the middle and the components on the left.

If your site uses the overlay, a panel covers the editor when you open a form. Select it
to open C4L Author, or dismiss it to use the normal editor.

When you are done, select **Apply** to bring your changes back to the normal editor, and
then save the page as usual. **Cancel** leaves your content as it was.

## Inserting a component

1. Place the cursor where the component should go.
2. Find the component in the sidebar. The tabs above the list filter it by purpose:
   contextual, procedural, evaluative and helpers.
3. Select the component.

To learn what a component is for, hold the pointer over it for two seconds, if your site
has documentation tooltips switched on.

**To turn existing text into a component,** select the text first. It moves into the
component, as plain text. A component with no place for text, such as a figure, is
inserted after your selection, and your text stays where it is.

You can also insert a component with the quick insert button in the editor's toolbar,
without using the sidebar.

## Changing a component

Hold the pointer over a component in the editor to show its toolbar:

- **Variants** change its look, for example full width or a caption. Some variants work
  as a group, where exactly one is active, such as numbered or checkmarks on a panel
  list. Others exclude each other, such as full width and split on a timeline.
- **Move up** and **Move down** move the component.
- **Delete** removes it.

Undo works for all of these.

To change a component into another one, place the cursor in it and use the **Convert to**
button in the toolbar. Only components with a compatible structure are offered.

## Editing through a form: precision mode

Switch to **Precision** at the top of C4L Author and select a component in the preview. A
form shows its fields, such as a timeline's years and texts, a figure's description and
source, or an estimated time's value. Fixed labels, such as "min", stay as they are.

The preview does not run scripts, so a component that needs JavaScript to look right can
look unfinished there.

## Tabs, carousels and collapsibles

These components show one thing at a time on the page. In the editor, they have controls
of their own:

- **Tabs:** switch between tabs, rename a tab in its title line, add and delete tabs.
- **Carousel:** browse, add and delete slides, and add or change a slide's image.
- **Collapsible:** edit its title and its content directly.

These controls are never saved with your content. On the page, learners can use the
mouse or the keyboard. If JavaScript is not available, and in the Moodle app, everything
is shown one after the other, so nothing is hidden from anyone.

## Link blocks

A link block accepts web and mail addresses only: `http://`, `https://` and `mailto:`.
Anything else, including a relative address such as `/course/view.php`, leaves the link
going nowhere. Use the full address.

## Suggestions from AI

If your site has AI suggest switched on, an **AI suggest** button appears at the top of
C4L Author. It reads your text and proposes components for it. You choose which
suggestions to apply.

- The first time, Moodle asks you to accept the site's AI policy.
- Your text is sent to the AI provider your site has configured.
- Very long content is analysed in part, and C4L Author tells you when that happens.
- The same text can get different suggestions on different runs. Read them before you
  apply them.

The button does not appear if your site, course or activity has AI switched off, or if
your role does not allow it.

## What students see

Students who can use the editor, for example in a forum, see only the components your
administrator has listed for them. Components are plain HTML, so this guides what they
are offered rather than restricting what they can write.
