# C4L Author

A TinyMCE editor plugin for Moodle that extends the original [Components for Learning (C4L)](https://moodle.org/plugins/tiny_c4l) with an enhanced authoring experience. It opens a modal with a WYSIWYG editable view of the editor content, constrained to a readable column width, and provides a full set of C4L visual components designed explicitly for learning.

C4L Author is built on top of the [Components for Learning](https://componentsforlearning.org) project and includes all its components, plus additional features such as:

- A distraction-free modal editor with comfortable reading width.
- Convert to: transform any component into another compatible one.
- AI suggest: AI-powered analysis that suggests C4L components for your content.
- Component variants and full-width options.
- Custom components defined by the administrator.
- Documentation tooltips for each component.

## Installation

Install the plugin from the Site Administration area (Plugins > Install plugins > Install plugin from ZIP file).

Once installed, a button will appear in the TinyMCE editor toolbar. Clicking it opens the C4L Author modal where you can edit content and insert components.

## Configuration

Settings are available at Site Administration > Plugins > Text Editors > TinyMCE editor > C4L Author:

- **General**: enable/disable component preview on hover, documentation tooltips, overlay mode, and configure which components are available to students.
- **AI suggest**: enable/disable the AI suggestion feature and configure component types and rates.
- **Custom components**: define additional components with custom HTML and CSS.

## AI suggest requirements

The AI suggest feature uses Moodle's built-in AI subsystem (available from Moodle 4.5). The site administrator must configure an AI provider (such as OpenAI or Ollama) and supply their own API key at Site Administration > AI. No API key is bundled with this plugin.

If no AI provider is configured, the AI suggest button will display a message prompting the administrator to set one up. The AI suggest feature can also be disabled entirely from the plugin settings.

## Capabilities

- `tiny/c4lauthor:viewplugin` — controls plugin visibility for any role.
- `tiny/c4lauthor:use` — allows using the plugin.
- `tiny/c4lauthor:aisuggest` — allows using the AI suggest feature.

## Fonts

This plugin includes the Figtree font family (SIL Open Font License 1.1), inherited from the Components for Learning project.

## Icons

Icons authored by Roger Segú, except for the following, licensed under Creative Commons CCBY: [Glasses](https://thenounproject.com/icon/70907/) by Austin Condiff, [Estimate](https://thenounproject.com/icon/1061038/) by xwoodhillx, [Quote](https://thenounproject.com/icon/77920/) by Rohith M S, [Pin](https://thenounproject.com/icon/689105/) by Icons fest, [Bulb](https://thenounproject.com/icon/1175583/) by Adrien Coquet, [Date](https://thenounproject.com/icon/1272092/) by Karan, [Success](https://thenounproject.com/icon/3405499/) by Alice Design, [Clock](https://thenounproject.com/icon/2310543/) by Aybige, [Feedback](https://thenounproject.com/icon/651868/) by dilayorganci, [Star](https://thenounproject.com/icon/1368720/) by Zaff Studio, [Tag](https://thenounproject.com/icon/938953/) by Ananth, Redo and Book Open by [Unicons](https://github.com/Iconscout/unicons).

## Related projects

- [Components for Learning](https://componentsforlearning.org) — the parent project with documentation, usage recommendations and examples for all components.
- [tiny_c4l](https://moodle.org/plugins/tiny_c4l) — the original TinyMCE plugin for Moodle.
- [atto_c4l](https://moodle.org/plugins/atto_c4l) — the original Atto editor plugin.

## License

Licensed under the [GNU GPL v3 or later](http://www.gnu.org/copyleft/gpl.html).
