# Changelog

## 1.4.1-beta

### Changed

- The styles written in *CSS code* are sent with every page by the plugin itself. The
  field takes plain CSS, the plugin writes the style element around it, and there is
  nothing left to copy into Additional HTML; the field description says as much, and
  the administrator guide has the rest. Administration pages are left out, so a rule
  that hides too much cannot also hide the page where it is written.
- Those styles also reach the editor inside the C4L Author modal, where custom
  components used to appear as unstyled text.
- The `additionalhtml` string, which named the link to the Additional HTML page, is
  deprecated.

### Fixed

- The help text for *CSS code* asked for the styles to be wrapped in `<style>...<style>`
  and copied into Additional HTML. The second tag was missing its slash, and a browser
  given an element that never closes swallows the rest of the page. Nothing asks for
  tags any more, and a closing tag written inside the field is escaped, so it cannot end
  the stylesheet early. Reported by Hochschule Hannover.
- The Convert to icon on the component toolbar was drawn at twice the size of the
  others. The same drawing serves as the editor's menu icon, at its own size.

## 1.4.0-beta

### Security

- The plugin loads only for users who hold both `tiny/c4lauthor:use` and
  `tiny/c4lauthor:viewplugin`. `use` was not checked at all, and `viewplugin` only hid
  the toolbar button in JavaScript, which is a decoration rather than a restriction.
- Which components the sidebar offers is decided by a capability of the plugin's own,
  `tiny/c4lauthor:useallcomponents`, with the same default roles as before. It used to
  follow `gradereport/grader:view`, another plugin's capability, which says nothing
  about authoring. Note that this steers authoring and is not access control: the
  markup can still be typed by hand in the source view.
- Custom component HTML from the settings page is cleaned with `clean_text()`. It went
  through `format_text()`, which also ran the site's filters over it.
- File serving matches the plugin's own file areas exactly and returns false for
  anything else.
- Uninstalling removes the plugin's user preference.

### Added

- Nine helper components. Panel list and timeline, contributed by bdecent from the
  eduHub project: panel list is numbered or with checkmarks, optionally compact or full
  width, and timeline has events with a year and a text, optionally full width or split,
  with continuity marks, with precision mode editing each event. And combo, styled list,
  link block, tabs, carousel, collapsible and styled table, with their variants.
- Tabs, carousel and collapsible use markup made of classes only, so it survives
  Moodle's HTML cleaning and works with Bootstrap 4 and 5. Without JavaScript, and in
  the Moodle app, all their content shows. A page script adds the buttons, ARIA
  attributes and keyboard support, and converts earlier Bootstrap-based markup of these
  components when the page loads. In the editor, tabs and slides can be switched, added
  and deleted, and a slide gets its image through the image dialog.
- Link blocks keep their address in the link, and accept only http, https and mailto
  addresses.
- A standalone fallback: an admin page offers the content styles and the page script to
  copy into the site before uninstalling, so content keeps its look and its tabs,
  carousels and collapsibles keep working. The styles are 102 KB (22 KB compressed).
- A component category of its own for the timeline, *Sequential*, which until now sat
  under *Helper* alongside the all-purpose card. Helper says what a component is built
  out of; Sequential says what the timeline is for.
- Variants can form groups (exactly one active) and exclude others. A component can
  declare its own wrapper class instead of a `c4lv-` one.
- A component can name the regions of its text, in `flow`, where Enter writes on in a
  new paragraph rather than leaving the component, which a second Enter on the empty
  paragraph does. In a list, Enter adds an item and the empty one leaves. The
  all-purpose card, the panel list, the styled list, the combo, the tabs and the
  collapsible name theirs.
- Other plugins can add components and editor stylesheets through a new hook,
  `\tiny_c4lauthor\hook\extend_components`, without changing C4L Author. Their
  components use their own templates, strings and icons, appear under a *Templates*
  category, work with variants and precision mode, and can insert what an AMD module of
  theirs returns. Their labels and docs are shown as text. The hook can also add page
  modules, for the behaviour of interactive components.
- The combo takes a text background and a whole image. The panel its text sits in
  becomes a choice of its own, and the image can be shown entire, at its own aspect
  ratio, instead of cropped to fill its cell.
- A tab's title is drawn as the field it is, with a caption naming it, so it reads as
  the name of the tab rather than as a heading of the tab's content.
- A collapsible can be worked in the editor: its title takes the look of the trigger
  the page draws, and a control beside it folds the content away and brings it back.
- A Remove style button on the component toolbar, next to the delete button. It strips
  the C4L markup from a component and keeps the authored content as plain HTML: the
  `c4lv-*` and `c4l-*` classes, the `role` and `aria-label` attributes, the layout
  containers, the decorative elements that hold no content of their own, the generated
  titles and the surrounding spacer paragraphs all go. The element stops being a C4L
  component, so the toolbar no longer applies to it. Deleting a component removes its
  content; this keeps it.
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
- Precision mode shows the whole text of the all-purpose card, the combo and the
  collapsible, now that these take more than one paragraph: one box, a blank line
  between the paragraphs, which is how they go back into the component. It used to
  show the first paragraph alone, or, in the collapsible, all of them run together.
- Editing a text in precision mode keeps the formatting it has. The change is written
  where it lies, so bold, italics and links elsewhere in the text stay. Formatting
  still cannot be added there: the boxes hold plain text. A field that falls back to
  the component, because the text span it looks for is not there, no longer replaces
  everything the component holds — a title of its own, for one, used to go.
- The panel list and the styled list draw the same checkmark, a ring with the tick
  stroked rather than filled in, and a little thinner than it was. They were drawing
  two different marks, which looked like two different sets of components.
- Each box in precision mode is named by its label, which until now sat beside it
  without being tied to it, so a screen reader had nothing to announce.
- Precision mode edits the cell of a styled table that the author clicks, the field
  being the part clicked rather than the first the selector matches. Components with
  a wrapper class of their own, the styled table among them, could not be selected
  there at all.
- Precision mode draws the tabs, carousels and collapsibles of its preview as the
  content view draws them, one tab or slide at a time, where they used to be shown all
  at once: the preview runs no scripts of its own.
- `commands.js` is split into modules: the convert menu, the sidebar, the variant
  toolbar, the dropdown, the icons and the component markup each have their own.
- *Component availability* replaces *Aimed at students* and *Not intended for students*
  with one table: every component on a row, and a choice of **Everyone**, **Capability**
  or **Nobody**. Nobody withdraws a component from the plugin for all users, site
  administrators included, which the old pair could not express — they only ever
  restricted students. Content that already uses a withdrawn component keeps rendering
  and can still be edited, converted or stripped of its style; only inserting a new one
  stops being offered.

  Capability means `tiny/c4lauthor:useallcomponents`, and the column heading lists the
  roles that hold it on this site rather than naming roles in a fixed label, which would
  stop being true as soon as the site's permissions differ from the stock ones. Being a
  capability, it can be overridden for a category, a course or a single activity.

  Nothing changes on upgrade. An upgrade step translates the stored settings — a
  component either list named becomes Everyone, one in neither becomes Capability — so
  an admin who had withheld a component from students keeps that. Without it the
  defaults would have applied and the component would have been offered to students
  again, silently. Once the values are carried over, both old settings are deleted from
  the database, and their strings are listed in `lang/en/deprecated.txt`.

  The rows come from the declarations rather than being written out in `settings.php`,
  where they had drifted: Concept review, Further reading, Statement and Assessment were
  in neither setting, which left them unusable for students with no way for an admin to
  turn them on. They appear in the table as Capability, which is what they were in
  practice, and can now be changed. Which components a user may insert is decided on the
  server and sent to the editor as one list, so the editor carries no role logic of its
  own.
- Convert to has moved from the selection quickbar to the component toolbar, beside
  delete and remove style. It only appears on components that have compatible targets,
  so it no longer has to report that there is nothing to convert.
- The component picker no longer appears in the quickbar while the selection sits inside
  a component, where inserting a component into a component made no sense. It behaves as
  before on content that carries no component yet.

### Fixed

- What other TinyMCE plugins add to the menus is there inside the C4L Author modal:
  the editor it opens inherits the outer editor's menus, context menu and table
  toolbar, where it used to fall back to TinyMCE's own. A font colour plugin's items,
  for one, were missing from Format. The items of the plugins the modal does without
  — the original C4L and tiny_html — are taken out of everything it inherits.
- Converting a component now rebuilds the inner markup the target's styling relies on.
  Swapping the root class alone left, for instance, a quote without its rule, its quote
  marks or its italics.
- The first analysis after accepting the AI policy could be refused, because core does
  not wait for the server to store the acceptance. It now waits.

### Contributed

bdecent gmbh (https://bdecent.de) contributed, from the eduHub project: the panel list
and timeline components; the components moved from JavaScript to declarations in PHP
with their markup in Mustache templates, and the web service that serves them; the hook
other plugins add components through; the standalone fallback; the documentation for
teachers, administrators and developers; and the PHPUnit, Behat and CI setup. The fix
for the AI policy, and the security findings that remained open after 1.3.0-beta, are
theirs too.

### Funding

Component availability, and the Remove style and Convert to actions on the component
toolbar, were funded by Hochschule Hannover (https://www.hs-hannover.de).

### Acknowledgements

A special thank you to the Institut Obert de Catalunya
(https://ioc.xtec.cat/educacio/), which promoted and sponsored Components for Learning
and made the whole project possible. The combo, styled list, link block, tabs,
carousel, collapsible and styled table grew out of components built there years
earlier, with the professors who designed and tested them.

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
