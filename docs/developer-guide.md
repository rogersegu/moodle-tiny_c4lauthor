---
audience: developer
---

# C4L Author — developer guide

This guide is for developers adding components to C4L Author, or extending it from another
plugin.

## How it is put together

| Part | Where | What it does |
| --- | --- | --- |
| Declarations | `db/components.php` | Every component and variant, as data. `\tiny_c4lauthor\local\components` documents each key. |
| Markup | `templates/components/`, `templates/variants/` | What a component or variant inserts. |
| Registry | `amd/src/registry.js`, web service `tiny_c4lauthor_get_components` | The editor loads the declarations and their strings once per page and context. |
| Modal | `amd/src/commands.js` | The editor modal and its views. |
| Editor modules | `amd/src/sidebar.js`, `variant_toolbar.js`, `convert.js`, `dropdown.js`, `icons.js`, `component_html.js` | One job each. |
| Precision mode | `amd/src/precise_ui.js`, `precise_handlers.js` | Reads its fields from the declarations. |
| Extension hook | `\tiny_c4lauthor\hook\extend_components` | How other plugins add components. |

## Adding a component

Each component is two files:

- its declaration in `db/components.php`: id, category, sidebar icon, default text,
  docs strings, variants, its position in the "Convert to" menu and the fields
  precision mode offers. `\tiny_c4lauthor\local\components` documents every key.
- its markup in `templates/components/<name>.mustache`. The template gets
  `placeholder` (the text span, already HTML), `variantclasses`, `variantshtml`
  (HTML the active variants add, such as a caption) and `uniqid`. Strings use
  `{{#str}}key, tiny_c4lauthor{{/str}}`.

Add the strings to `lang/en/tiny_c4lauthor.php` and the styles to `scss/`. A variant
that adds HTML has its own template in `templates/variants/`. Precision fields that a
CSS selector alone cannot read or write name a handler from `amd/src/precise_handlers.js`.

`tests/local/components_test.php` checks that the declarations are consistent and
that each component renders exactly the markup in `tests/fixtures/components/`.
Changing a component's markup means updating its fixtures in the same commit.

`students => true` offers a component to students by default. The two student
settings list every component; one an admin has not seen there yet counts with its
default.

## Adding components from another plugin

Another plugin adds components through the `\tiny_c4lauthor\hook\extend_components`
hook, without changing C4L Author. Register a callback in its `db/hooks.php`:

```php
$callbacks = [
    [
        'hook' => \tiny_c4lauthor\hook\extend_components::class,
        'callback' => [\local_example\hook_callbacks::class, 'extend_c4lauthor'],
    ],
];
```

and add components and editor stylesheets in the callback:

```php
public static function extend_c4lauthor(\tiny_c4lauthor\hook\extend_components $hook): void {
    $hook->add_component('local_example', 'banner', [
        'category' => 'templates',
        'template' => 'local_example/banner',
        'menuicon' => 'banner',
        'text' => 'Welcome to the course',
        'docs' => ['description' => 'banner_desc'],
        'variants' => ['full-width'],
        'precision' => [
            ['selector' => 'span[data-id]', 'label' => 'banner_text', 'type' => 'textarea', 'fallback' => true],
        ],
        'students' => false,
    ]);
    $hook->add_editor_stylesheet(new \moodle_url('/local/example/editor.css'));
}
```

A declaration takes the keys of `db/components.php`, except `id` and `convertible`.
Its template, icon (in the plugin's `pix/`) and strings belong to the plugin that adds
it; a string key may name another plugin as `component/key`. The label is the string
named like the component, or the one in `label`. The category may also be `templates`.
The name must be unique: lower case letters, digits and underscores, not used by
C4L Author. Its label and documentation strings are shown as text, without markup. A declaration C4L Author cannot use is left out with a developer debugging
message.

The plugin's `styles.css` styles the component on the page. The editor's content does
not load it, so add the same rules as an editor stylesheet.

Instead of its template, a component can insert what an AMD module of the plugin
returns, for example after the teacher has picked something:

```php
'inserter' => 'local_example/banner_picker',
```

```js
export const insert = async({editor, component, selectedText, render}) => {
    // render() resolves with the component's own markup.
    return '<div class="c4lv-banner">...</div>'; // Or null to insert nothing.
};
```

The components can be filtered by the hook's `context`, the context of the editor.
The hook is called on every page with an editor, so keep the callback cheap.

## Tests

- `tests/local/components_test.php` checks the declarations and compares every component
  byte for byte with `tests/fixtures/components/`.
- `tests/hook/extend_components_test.php` covers what other plugins can add.
- `tests/external/` covers the web services, including the gates and limits of AI suggest.
- `tests/behat/` covers the modal, inserting components, precision mode, and stored scripts
  staying inert.

CI runs on pull requests and on pushes to `main`, for Moodle 4.5 to 5.3. Opening the modal
and inserting a component are tracked with `core/pending` so Behat waits for them. Keep
that when changing the modal, or the tests become flaky.

## Things to watch

- The precision preview is a sandboxed frame without scripts. Editor content must never
  reach the top document as markup.
- `mobileapp/styles.css` is maintained by hand. A change to a helper's SCSS needs the same
  change there, and a new `version` in `db/mobile.php`, because the app caches by it.
- Moodle's cleaning removes `role`, `aria-*`, `data-*` and `<button>` from some content, so
  a component must be recognisable from its classes alone.
- Moodle 4.5 has Bootstrap 4 and `--primary`; 5.x has Bootstrap 5 and `--bs-primary`. The
  brand tokens read both. Inside the editor the page's colour is passed in by `brand.js`,
  because the theme's editor CSS does not have it.
