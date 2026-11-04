---
audience: admin
pagetypes:
  - admin-setting-tiny_c4lauthor_settings
  - admin-lib-editor-tiny-plugins-c4lauthor-*
  - lib-editor-tiny-plugins-c4lauthor-*
---

# C4L Author — administrator guide

This guide is for site administrators installing and configuring C4L Author.

## Requirements

- Moodle 4.5 (LTS) to 5.3.
- The `tiny_html` plugin, which ships with Moodle core. The code view uses it.

## Installing and upgrading

Install C4L Author from *Site administration > Plugins > Install plugins*, or place it in
`lib/editor/tiny/plugins/c4lauthor` (`public/lib/editor/tiny/plugins/c4lauthor` on Moodle
5.1 and later) and run the upgrade.

An upgrade keeps your saved settings and the choices in the two student lists. Components
that were in neither list before are listed as not intended for students, and stay
unticked, so students do not get them until you tick them.

## Settings

Go to *Site administration > Plugins > Text editors > TinyMCE editor > C4L Author*.

### General

- **Show overlay** — a clickable overlay on top of the editor that opens C4L Author. It
  can be dismissed to use the normal editor.
- **Enable docs tooltips** — a description of a component after holding the pointer over
  it in the sidebar for two seconds.
- **Aimed at students** and **Not intended for students** — together, the components
  offered to users who do not hold `tiny/c4lauthor:useallcomponents`. Both lists show
  every component, including those other plugins add. A component you have not seen in
  these lists yet counts with its own default, until you save the settings.

### AI suggest

AI suggest is off on new installs, because analysing content sends it to your AI provider
and uses its credit. Existing sites keep their setting.

The button appears only if all of these hold, and the web service checks them too:

1. **Enable AI suggest** is on.
2. The user holds `tiny/c4lauthor:aisuggest`.
3. An AI provider offers text generation (*Site administration > AI*).
4. AI is enabled for the course and activity (Moodle 5.1 and later).

Users accept the site's AI policy before their first analysis.

| Setting | Default | Effect |
| --- | --- | --- |
| Maximum paragraphs analysed | 80 | The main cost control. Further paragraphs are skipped, and the user is told. |
| Maximum characters per paragraph | 1500 | Longer paragraphs are classified from their opening. |
| Maximum characters analysed | 40000 | Limits the size of one request. |

The per-component switches decide which component types the provider is asked about, so
switching one off also makes the request smaller.

If the provider's *Generate text* action has a system instruction asking for plain text,
that can conflict with the structured answer AI suggest needs. Users then see a message
that the response could not be read.

### Custom components

Up to 12 components with your own HTML. In the code, `{{PLACEHOLDER}}` marks where the
text goes and `{{CUSTOMCLASS}}` adds the component's classes. The HTML is cleaned when it
is used.

## Capabilities

| Capability | Decides | Default roles |
| --- | --- | --- |
| `tiny/c4lauthor:use` | Whether the plugin loads for the user | Authenticated user |
| `tiny/c4lauthor:viewplugin` | Whether the plugin loads for the user | Student, teacher, editing teacher, manager |
| `tiny/c4lauthor:useallcomponents` | Which components the sidebar offers | Teacher, editing teacher, manager |
| `tiny/c4lauthor:aisuggest` | Whether the user may use AI suggest | Teacher, editing teacher, manager |

A user needs both `use` and `viewplugin` for the plugin to load.

`useallcomponents` steers authoring and is not access control: components are plain HTML
that anyone can also write in the code view.

## Moodle's HTML cleaning

For forum posts, untrusted users and sites with `forceclean`, Moodle removes `role`,
`aria-*`, `data-*` and `<button>` from content. Components are recognised by their
classes, which stay. Tabs, carousels and collapsibles get their buttons and ARIA
attributes from a script on the page, so they keep working in cleaned content.

That script loads on every page and does nothing where these components are absent.

## The Moodle app

The app uses its own stylesheet, `mobileapp/styles.css`. Tabs, carousels and collapsibles
show all their content there, one after the other.

## Before you uninstall

Uninstalling takes the styles and the page script with it. Content stays in your courses,
but it loses its look, and tabs, carousels and collapsibles stop switching.

To avoid that, copy both into your site first, from *Site administration > Plugins > Text
editors > TinyMCE editor > C4L Author standalone fallback*:

1. Paste the **styles** into your theme's Raw SCSS, which serves them as a cached
   stylesheet.
2. Add the **script** to *Additional HTML*, before BODY is closed.
3. Uninstall C4L Author.

The script needs no Moodle JavaScript, and it carries its strings in the site's language
as of when you copied it.

Uninstalling removes the plugin's user preference.
