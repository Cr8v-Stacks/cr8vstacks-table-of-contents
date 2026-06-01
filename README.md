# TableWise

TableWise is a customizable WordPress table of contents plugin for posts and pages. It turns page headings into a styled, accessible navigation block with multiple layouts, layout-aware color presets, live preview, reading progress, sticky behavior, and per-post controls.

## Repository

- GitHub: https://github.com/Cr8v-Stacks/Tablewise
- Website: https://cr8vstacks.com/dev-playground/tablewise-table-of-content/
- Support: cr8vstacks@gmail.com

## What TableWise Does

- Builds a table of contents from selected heading levels, from H2 through H6.
- Places the TOC before the first heading, after the first paragraph, or only where the `[wptw_toc]` shortcode is used.
- Supports public post types, minimum heading thresholds, excluded post IDs, and custom anchor prefixes.
- Preserves existing heading IDs when possible and generates anchors for headings that do not already have one.
- Adds smooth scrolling, active section highlighting, optional numbering, optional back-to-top, and accessible expand/collapse behavior.
- Outputs crawlable heading anchor links that can support long-form content navigation and SEO-friendly page structure.

## Layouts

TableWise includes four selectable frontend layouts. Each layout has its own markup treatment, spacing, child heading design, reading progress styling, and layout-native default color direction.

- **Manuscript**: dark editorial layout with chapter-style timeline markers, amber progress, right-aligned reading time, and depth-aware child heading treatment.
- **Soft Editorial**: clean editorial card with guided sections, dark/soft-dark typography, completed-state checkmarks, and nested child rows.
- **Brutalist**: bold typographic layout with dark body, darker header, offset border extension, squared controls, high-contrast active rows, and nested child styling.
- **Minimalist**: the original clean light card layout, kept for users who want the classic TableWise look.

## Dashboard And Live Preview

The settings dashboard is organized into tabs for Visibility, Headings, Layouts, Display, Colours, Typography, and Advanced settings.

- Wider live preview that mirrors the frontend TOC stylesheet.
- Sticky admin header for easier navigation while editing.
- Desktop/mobile preview toggle.
- Saved Active badges for the current layout and color preset.
- Shortcut links between Layouts and Colours.
- Custom admin footer on the TableWise settings screen.

## Design Customization

TableWise is designed so each layout can keep its own personality while still being customizable.

- Six color presets: Default, Light, Dark, Ocean, Forest, and Rose.
- Default preset restores each layout's native colors instead of forcing every layout into one shared palette.
- Layout-aware Dark preset so dark mode fits each design.
- Seventeen individual color controls for card, border, header, label, reading time, progress, toggle button, links, active state, numbers, and back-to-top button.
- Contrast normalization on save and in preview to reduce text/background clashes.
- Typography controls for font family, link text, child text, label text, reading time, numbers, label letter spacing, label transform, and border radius.
- Built-in font stack choices include system fonts plus Inter, DM Sans, Lato, Nunito, Open Sans, Poppins, Raleway, Roboto, Source Sans 3, Work Sans, Playfair Display, Merriweather, DM Mono, Fira Mono, and JetBrains Mono.
- No remote font files are loaded by TableWise; font choices use local/theme-available font stacks.
- Custom CSS field for deeper theme-specific adjustments.

## Reader Features

- Reading time estimate with configurable words-per-minute.
- Scroll-based reading progress indicator that matches the active layout.
- Sticky TOC header with configurable top offset for fixed site headers.
- Active heading tracking with read/done states.
- Layout-specific Hide/Show controls.
- Optional hierarchical section numbering.
- Optional back-to-top floating button.

## Per-Post Controls

Global settings can be overridden per post.

- Classic Editor meta box.
- Gutenberg sidebar panel.
- Gutenberg-friendly shortcode placement through the Shortcode block.
- Quick Edit support from the posts list.
- Per-post controls for disabling the TOC, title, initial state, placement, numbers, sticky header, and reading time.

## Gutenberg Support

TableWise works with headings created in the WordPress Block Editor. It adds a Gutenberg sidebar panel for per-post settings, supports manual placement through the Shortcode block, and keeps global dashboard settings and per-post overrides working together.

## SEO And Content Navigation

TableWise can support SEO by creating a crawlable table of contents with stable heading anchors. This helps long-form content become easier to scan and navigate, and gives search engines clearer internal jump links to the sections on a page.

TableWise does not replace a dedicated SEO plugin. It does not manage meta titles, meta descriptions, schema, XML sitemaps, or content scoring.

## Shortcode

Use:

```text
[wptw_toc]
```

Set the global or per-post position to shortcode-only when you want full manual placement. The shortcode uses the same layout, color, typography, display, and per-post settings as the automatic TOC.

## Accessibility And Theme Hardening

- TOC renders as navigation markup with ARIA labels.
- Toggle buttons use `aria-expanded` and `aria-controls`.
- Progress indicators expose progressbar ARIA values.
- Frontend JavaScript does not require jQuery.
- TOC links, lists, buttons, numbers, labels, and layout surfaces are hardened against common WordPress theme defaults.

## Installation

1. Upload the `tablewise` folder to `/wp-content/plugins/`.
2. Activate TableWise in WordPress.
3. Go to **Settings > TableWise** to configure global behavior.
4. Optionally override settings per post from the editor sidebar/meta box or Quick Edit.

## Development Notes

- Main plugin file: `tablewise.php`
- Settings dashboard and sanitization: `includes/settings-page.php`
- Frontend rendering, CSS, and JS: `includes/frontend.php`
- Defaults and layout definitions: `includes/defaults.php`
- Color presets and helpers: `includes/helpers.php`
- Shortcode: `includes/shortcode.php`
- Classic/Gutenberg per-post controls: `includes/meta-box.php`
- Quick Edit integration: `includes/quick-edit.php`

## License

GPL-2.0-or-later.
