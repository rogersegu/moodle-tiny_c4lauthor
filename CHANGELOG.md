# Changelog

## Unreleased

### Added

- A Remove style button on the component toolbar, next to the delete button. It strips
  the C4L markup from a component and keeps the authored content as plain HTML: the
  `c4lv-*` and `c4l-*` classes, the `role` and `aria-label` attributes, the layout
  containers, the decorative elements that hold no content of their own, the generated
  titles and the surrounding spacer paragraphs all go. The element stops being a C4L
  component, so the toolbar no longer applies to it. Deleting a component removes its
  content; this keeps it.

### Changed

- *Component availability* replaces *Aimed at students* and *Not intended for students*
  with one table: every component on a row, and a choice of **Everyone**, **Capability**
  or **Nobody**. Nobody withdraws a component from the plugin for all users, site
  administrators included, which the old pair could not express — they only ever
  restricted students. Content that already uses a withdrawn component keeps rendering
  and can still be edited, converted or stripped of its style; only inserting a new one
  stops being offered.

  Capability means the new `tiny/c4lauthor:useallcomponents`, allowed by default for
  teacher, non-editing teacher and manager. The plugin used to ask whether the user
  could view the gradebook, so an administrator who wanted to give someone every
  component had to grant them the gradebook as well. The plugin's own capability
  isolates the decision, and can be overridden for a category, course or activity.
  The column heading lists the roles that hold it on this site rather than naming
  roles in a fixed label, which would stop being true as soon as the site's
  permissions differ from the stock ones.

  Nothing changes on upgrade. An upgrade step translates the stored settings — a
  component either list named becomes Everyone, one in neither becomes Capability —
  so an admin who had withheld a component from students keeps that. Without it the
  new defaults would have applied and the component would have been offered to
  students again, silently. The same three roles qualified under the old check, so
  on a site with stock permissions nobody gains or loses a component.

  The component list now comes from `tiny_c4lauthor\components` rather than being
  written out in `settings.php`, where it had drifted: Concept review, Further reading,
  Statement and Assessment were in neither setting, which left them unusable for
  students with no way for an admin to turn them on. They appear in the table as
  Capability, which is what they were in practice, and can now be changed.

  Which components a user may insert is decided on the server and sent to the editor as
  one list, so the editor no longer carries any role logic of its own.
- Convert to has moved from the selection quickbar to the component toolbar, beside
  delete and remove style. It only appears on components that have compatible targets,
  so it no longer has to report that there is nothing to convert.
- The component picker no longer appears in the quickbar while the selection sits
  inside a component, where inserting a component into a component made no sense. It
  behaves as before on content that carries no component yet.

### Fixed

- Converting a component now rebuilds the inner markup the target's styling relies on.
  Only the root class was swapped before, so a Tip converted to a Quote lost the rule,
  the quote marks and the italics, all of which are styled through `.c4l-quote-body`,
  `.c4l-quote-line` and `.c4l-quote-text` rather than through the root. Concept review
  likewise regains its title, Example its heading slot, and scaffolding belonging to the
  component being converted away from is no longer left behind.
- The quickbar's component picker now honours *Component availability*. The setting
  was enforced only where the modal's sidebar is built, so a student opening the modal
  could insert any component from the quickbar, including every one the admin had left
  unchecked. Both now go through one shared gate. Note that neither is a security
  boundary: the markup can still be typed by hand in the source view, which no role
  restriction covers.

### Funding

Component availability, and the Remove style and Convert to actions on the component
toolbar, were funded by Hochschule Hannover (https://www.hs-hannover.de).

## 1.3.0-beta

### Security

- The plugin wrote editor content into the page in four places without treating it as
  untrusted: the precision preview's iframe was not sandboxed, the modal body was built
  by interpolating the content into a `<textarea>`, and the AI cards rendered it
  unescaped. It relied on the content having been cleaned before it arrived. On a
  default site that held — Moodle cleans content on its way into an editor, and the
  payloads tested did not survive that journey — but relying on the caller was the
  wrong design. The preview iframe is now sandboxed without `allow-scripts`, the
  textarea takes its content through `.value`, and the cards render text. No action is
  needed beyond upgrading, and there is nothing to look for in existing content.
- AI suggest called Moodle's AI subsystem directly, which bypassed the checks a core AI
  placement makes: whether AI is enabled for the course and the activity, and whether
  the user has accepted the site's AI policy. It now follows the same chain core uses,
  and the policy dialog appears before the first analysis. Sites with AI switched off
  for a course were still being offered the button there; they no longer are.
- **Enable AI suggest**, and the four per-component toggles beside it, only decided
  whether the browser drew the button. The web service never read them, so anyone
  holding *Use AI suggestions* could call it directly and have course content sent to
  the configured provider at the site's expense with the feature switched off. Both are
  now enforced on the server, and the enabled component types shape the prompt.

### Added

- Three settings that bound one AI analysis: the number of paragraphs analysed, the
  characters kept from a single paragraph, and the total characters sent. Previously a
  long page went to the provider whole, so the cost of a click was unpredictable and a
  large response could be cut short, which loses the whole analysis rather than its
  tail. Defaults are 80 paragraphs, 1500 characters per paragraph and 40000 in total;
  when a cap trims the document the user is told.
- A declared dependency on `tiny_html`. The code view imports its CodeMirror module, so
  it never worked on a site without that plugin, and now says so.

### Changed

- **AI suggest is off on new installs.** Existing sites keep whatever they have set.
  Analysing content sends it to the provider configured for the site and uses its
  credit, so it should be switched on deliberately. The button also stays hidden unless
  the user holds *Use AI suggestions*, a provider offers text generation, and AI is
  enabled for the course and activity.
- An analysis asks for half as many suggestions as there are paragraphs, which is the
  most the editor ever displayed. With the new defaults the worst case fell from 5012
  input and 6601 output tokens to 3203 and 1761.
- Internal error text no longer reaches the user. A failed provider response and a
  caught exception were both shown verbatim, so people saw things like a PHP TypeError
  from inside the provider. The message is now translated, and the detail goes to
  developer debugging; core already records it for the administrator.
- The suggest web service takes structured parameters instead of a JSON string, is
  declared a write rather than a read, and no longer returns a preview field that
  nothing read.
- Component sample text, the sidebar toggle and the figure caption's *Source* label are
  translatable. The figure's placeholder alt text is now *Describe the image here*
  rather than lorem ipsum, which said nothing useful to a screen reader.
- The settings page is the one core provides rather than a second page built over the
  top of it, so the admin tree no longer lists this plugin twice. The section name is
  unchanged.
- `tiny/c4lauthor:viewplugin` is declared a read capability. It decides whether the
  toolbar button is offered and nothing else.
- The mobile app styles version in `db/mobile.php` was left behind when the stylesheets
  were expanded, so devices that had cached the styles never received those changes.

### Fixed

- Inserting *statement*, *assessment* or *figure* while text was selected deleted that
  text and left the component's own sample in its place.
- Text that looks like a tag — `<array>`, `</div>` — was swallowed when a component was
  applied to a selection, and when a learning outcomes component was unwrapped. The
  unwrap also flattened bold, italics and links in each item.
- Applying a variant from the toolbar wrote the literal text `{{#caption}}` into the
  content, could not be undone, and could scroll the editor to the top of the document.
- In precision mode, the figure's source field included the *Source:* label in the
  editable value and dropped its markup on save.
- A stored variant preference was overwritten with an empty one on the next save,
  because the lookup behind it had been reading an undefined value since the variants
  module stopped exporting it by name.
- The estimated time, grading value and quote author labels lost their wrapper when the
  content was saved, because an attribute-less span is unwrapped by the editor.

## 1.2.0-beta

### Added

- Dark mode. The plugin now follows Bootstrap's colour-mode attribute, so on Boost it
  tracks *Enable colour modes* with no configuration: the components a course renders,
  the component picker's icons, the modal interface, the AI, code and precision views,
  and the editor content itself. See the Dark mode section of the README.
- A colour token layer in `scss/_tokens.scss`: the palette as CSS custom properties in
  two tiers, so a site can recolour a single component by overriding one alias without
  rebuilding anything. The same layer ships in `tiny_c4l`, so a component renders
  identically in both plugins.
- `package.json`, so `npm run sass` rebuilds `styles.css` and `editor_styles.css`
  reproducibly. See the Building from source section of the README.
- `CHANGELOG.md` — this file. Releases before 1.2.0-beta are not recorded here.

### Changed

- `styles.css` and `editor_styles.css` ship expanded rather than minified, which is
  what every tiny plugin in core does and what lets stylelint check them. Minifying
  here saved nothing, since Moodle minifies the aggregated CSS when serving it. The
  declarations are unchanged; the diffs are now readable.
- Component badges and glyphs are drawn as alpha masks rather than coloured SVGs, so
  one asset serves both colour modes and takes its colour from a token. Component
  rendering in light mode is unchanged.
- The component picker's interface greys now come from the shared token layer. Four of
  them shift slightly in light mode: label and pill text from `#495057` to `#424b62`,
  group headers from `#6e767e` to `#535d76`, component button labels from `#1e1e1e` to
  `#424b62`, and button and menu hover from `#f0f3f7` to `#e7ebef`.
- The focus ring on precision-view fields is more visible, having moved to the shared
  focus token.
- The inner editor uses TinyMCE's `oxide-dark` skin on a dark page. Because TinyMCE
  cannot swap a skin on a live instance, the skin is fixed when the modal opens: a page
  that changes colour mode with the modal open keeps the skin it started with until the
  modal is reopened.

### Fixed

- Learning outcomes: the fold under the title tab sat 12px too high. The value came from
  `tiny_c4l`, where the title is an `<h6>` beside a nested `<ul>`; here it is an `<li>`
  of the outer list.
- Learning outcomes and Statement with the `full-width` variant overflowed their
  container, the first because of the title tab that hangs off the left edge, the second
  because of its shadow and tag. They are now capped at 99% and 96%.
- Figure, `caption` variant: a missing closing quote on the caption's `aria-label` swallowed
  the "Source:" line into the attribute, so it never rendered.

### Known gaps

- `pix/statement-tag.svg` keeps its colour baked in; it is two-tone and used through
  `content: url(...)` rather than as a mask, so it does not follow the colour mode.
- `mobileapp/styles.css` has no dark mode. The Moodle app does not use
  `data-bs-theme`; its dark mode comes from Ionic and needs a separate approach.
- Seventeen of the picker's icons are two-tone in light mode and lose their pale
  backing shape in dark, where a single-channel mask cannot express two colours.
