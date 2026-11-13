# Changelog

## 1.4.0-beta

Contributed by bdecent.

### Security

- The plugin loads only for users who hold both `tiny/c4lauthor:use` and
  `tiny/c4lauthor:viewplugin`. `use` was never checked, and `viewplugin` only hid the
  button in JavaScript.
- A new capability, `tiny/c4lauthor:useallcomponents`, decides which components the
  sidebar offers, with the same default roles, instead of a guess from
  `gradereport/grader:view`. It steers authoring and is not access control.
- Admin custom component HTML is cleaned with `clean_text()`. `format_text()` also ran
  filters over it.
- File serving matches the plugin's own file areas exactly and returns false for
  anything else.
- Uninstalling removes the plugin's user preference.

### Added

- Panel list and timeline, contributed by bdecent from the eduHub project. Panel list is
  numbered or with checkmarks, optionally compact or full width. Timeline has events
  with a year and a text, optionally full width or split, with continuity marks;
  precision mode edits each event.
- Variants can form groups (exactly one active) and exclude others. A component can
  declare its own wrapper class instead of a `c4lv-` one.
- Other plugins can add components and editor stylesheets through a new hook,
  `\tiny_c4lauthor\hook\extend_components`, without changing C4L Author. Their
  components use their own templates, strings and icons, appear under a *Templates*
  category, work with variants and precision mode, and can insert what an AMD module of
  theirs returns. Their labels and docs are shown as text.
- Documentation in `docs/`, for teachers, administrators and developers. On a site with
  the Documentation Center (`tool_mdocs`) it appears in Moodle itself.
- PHPUnit and Behat tests, and CI for Moodle 4.5 to 5.3.

### Changed

- Components are declared in `db/components.php` and their markup lives in Mustache
  templates (`templates/components/`), instead of in JavaScript. The editor fetches the
  declarations through a new web service, `tiny_c4lauthor_get_components`. The inserted
  markup is unchanged; a test compares every component with the markup 1.3.0-beta
  inserted.
- Precision mode reads its fields from the declarations. Fields that need custom code
  name a handler in `precise_handlers.js`.
- `commands.js` is split into modules: the convert menu, the sidebar, the variant
  toolbar, the dropdown, the icons and the component markup each have their own.
- The two student settings list every component, including added ones, and each
  component declares whether it is aimed at students. Saved choices stay as they are.
  Components that were in neither list are listed as not intended for students, so
  students still do not get them until an admin ticks them.

### Fixed

- The first analysis after accepting the AI policy could be refused, because core does
  not wait for the server to store the acceptance. It now waits.

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
