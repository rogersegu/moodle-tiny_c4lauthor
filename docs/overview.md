---
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
  - admin-setting-tiny_c4lauthor_settings
---

# C4L Author — overview

C4L Author is a TinyMCE editor plugin for Moodle. It opens your content in a large,
distraction-free editor and lets you build it from [Components for Learning](https://componentsforlearning.org):
visual components designed for learning, such as key concepts, tips and timelines.

Components are plain HTML in your content. They need no special activity type, and they
work wherever Moodle offers the TinyMCE editor: pages, labels, books, forum posts and
more.

## What you can do with it

- **Insert components** from a sidebar, grouped by purpose, with a short description of
  each one.
- **Change a component's look** with variants, such as full width or a caption.
- **Convert a component** into another compatible one without retyping it.
- **Edit a component through a form** in precision mode, without touching its markup.
- **Get suggestions from AI**, if your site has switched this on: C4L Author reads your
  text and proposes components for it.

## The guides

| Guide | For |
| --- | --- |
| [Teacher guide](teacher-guide.md) | Writing content with components |
| [Administrator guide](admin-guide.md) | Installing, settings, capabilities, AI suggest, uninstalling |
| [Developer guide](developer-guide.md) | Adding components, in this plugin or from another plugin |

## The components

### Contextual

| Component | What it is for |
| --- | --- |
| Key concept | Highlights a concept or idea. |
| Tip | Offers advice, a suggestion or a clue. |
| Reminder | Brings back a concept that helps with the current subject. |
| Quote | Highlights a quote from an author or a work outside your content. |
| Do/don't cards | Shows the right and the wrong way side by side. |
| Reading context | Gives a fragment of text a visual context suited to reading. |
| Example | Shows a case that helps learners grasp an idea. |
| Figure | Places an image in the content, with an optional footnote and source. |
| Tag | Sets the kind of subject or category. |
| Inline tag | Refers to a category inside a paragraph. |
| Concept review | Brings back a concept learned earlier, ahead of a new one. |
| Further reading | Lists resources that deepen or extend the content. |

### Procedural

| Component | What it is for |
| --- | --- |
| Attention | Draws attention to something important. |
| Estimated time | Shows how long something should take. |
| Due date | Shows when a task is due. |
| Procedural context | Describes a procedure to follow in a given context. |
| Learning outcomes | Lists what learners should gain from a course or activity. |
| Statement | The statement section of a complex task. |

### Evaluative

| Component | What it is for |
| --- | --- |
| Grading value | Shows what a task is worth in the grade. |
| Expected feedback | Gives feedback or information about a task. |
| AI use allowed | States that AI tools may be used. |
| AI use not allowed | States that AI tools may not be used. |
| AI use reported | States that AI tools may be used, but must be declared. |
| Assessment | The assessment section of a complex task. |

### Helpers

| Component | What it is for |
| --- | --- |
| All-purpose card | Gives a piece of content its own visual context. |
| Panel list | A styled list with numbered or checkmark bullets. |
| Timeline | A vertical timeline of events, each with a year and a text. |

Your site may offer more: administrators can define custom components, and other
plugins can add their own under *Templates*.
