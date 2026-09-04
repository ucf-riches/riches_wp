# CLAUDE.md — UCF-WordPress-Theme-child

Guidance specific to the child theme. The repository-root `CLAUDE.md` governs
the overall directory edit policy and the theme-modification guard; this file
covers child-theme conventions for anyone working inside this directory.

---

## Design guidelines

Before writing or modifying **any** code here, consult
**[`DESIGN_GUIDELINES.md`](../../DESIGN_GUIDELINES.md)** in the repository root
and follow the practices recorded there — child-theme safety, security/escaping,
asset loading & performance, accessibility, internationalization, coding
standards, custom-field (ACF) conventions, and the project's own established
patterns. When a change would deviate from those guidelines, call that out and
get agreement first.

---

## Architecture overview

This is a **classic PHP-template child theme** of `UCF-WordPress-Theme` (parent).
Both `functions.php` files load on every request (child first), so the child
adds and overrides behavior through hooks, filters, and template overrides
without touching the parent.

> **Maps:** the Leaflet map feature (`[riches_map]`, RICHES Maps / Map Pins post
> types) lives in the plugin `wp-content/plugins/leaflet-pinner/`, not in this theme.
> Style overrides only, via `.riches-map` selectors in `src/css/`.

> **Data and hooks:** ACF field groups for posts and aggregator pages, the
> YouTube featured-image sideloader, card link-bar resolution and its site
> options, and the shortcodes `[bannerstrip]`, `[riches_category_row]`,
> `[riches_queue_tabs]` live in `wp-content/plugins/riches-core/`. The theme
> calls plugin functions behind `function_exists()` guards and renders a plain
> page when the plugin is inactive. Plugin shortcodes call this theme's
> `riches_render_*` functions when present.

### Entry points

| File | Purpose |
|------|---------|
| `functions.php` | Wires all `includes/` files via `include_once` |
| `style.css` | Theme declaration header; minimal base styles |
| `footer.php` | Child footer override |
| `home.php` | Posts page (blog index) rendered with the aggregator cards and filter bar |
| `template-home.php` | "Home" page template |
| `template-home-aggregate.php` | Legacy home-aggregate page template |
| `template-aggregator.php` | Aggregator page template (category + filter/sort) |

### `includes/` — functional modules

| File | Purpose |
|------|---------|
| `config.php` | Theme constants, style enqueue, Font Awesome 5 forcing filter |
| `header-functions.php` | Parent "Page Header Fields" ACF group registration; common-banner fallback |
| `nav-functions.php` | Navigation / sticky-nav helpers |
| `footer-functions.php` | Footer render helpers |
| `category-queue.php` | `riches_render_category_queue()` — card-deck renderer used by the plugin's shortcodes |
| `omeka-bar.php` | `riches_render_omeka_bar()` — card link-bar markup (URL/label come from riches-core) |
| `aggregator.php` | `riches_render_aggregator()` and the filter-script enqueue (ACF config lives in riches-core) |

### `template-parts/`

| File | Purpose |
|------|---------|
| `sticky-nav.php` | Sticky navigation bar partial |
| `riches-queue-tabs.php` | Queue-tabs inner partial (consumed by the riches-core plugin's queue-tabs shortcode) |

### CSS pipeline

Source: `src/css/input.scss` (imports `_riches-queue-tabs.scss` and
`_riches-aggregator.scss`). Compiled output: **`static/css/output.css`** — this
is the file that WordPress enqueues. The compiler is the **VS Code Live Sass
Compiler** extension (not a Node/gulp build; `gulpfile.js` is a stale leftover).

When making CSS changes:
1. Edit the relevant `src/css/_*.scss` partial (or `input.scss` directly).
2. Compile via VS Code Live Sass Compiler **or** hand-mirror the compiled
   output into `static/css/output.css` when working headless.
3. Keep both files in sync — `output.css` is what runs in the browser.

### JavaScript

Assets live in `static/js/`. All scripts are **vanilla ES5 IIFEs** — no jQuery,
no build step, no transpiler. Enqueued conditionally via `wp_register_script` at
priority 5 and `wp_enqueue_script` at priority 20 (gated by
`is_page_template()` or `is_home()`), versioned with `filemtime()`.

| File | Purpose |
|------|---------|
| `riches-aggregator-filter.js` | Client-side filter/sort for the Aggregator template |

---

## Working conventions

- **Classic (PHP template) child theme only** — not a block/FSE theme.
  See `DESIGN_GUIDELINES.md §11` for why partial conversion is off-limits.
- **Docker verification:** the running container is `riches-wordpress-main`
  (container ID varies; confirm with `docker ps`). Container theme path:
  `/var/www/html/wp-content/themes/UCF-WordPress-Theme-child/`.
  - Lint: `docker exec <id> php -l <container-path>`
  - Runtime: bootstrap `wp-load.php` in a throwaway script, remove it immediately
    after. Never leave harness scripts in the theme.
  - End-to-end: `bash wp-content/plugins/riches-core/tests/run.sh` runs the smoke tests (PHP in the container, HTTP checks on the host); run it after any theme change.
- **Commit or push only when the user asks.**
- Wire new `includes/*.php` modules via `include_once` in `functions.php`.
- Prefix all globals: `riches_*` (functions/vars), `group_riches_*` (ACF groups),
  `field_riches_*` (ACF fields), `riches-*` (CSS classes, asset handles).
- **Deploy with riches-core.** The theme and `wp-content/plugins/riches-core/`
  ship together; see the plugin README's Deployment section for the order
  (theme first, then activate the plugin).
