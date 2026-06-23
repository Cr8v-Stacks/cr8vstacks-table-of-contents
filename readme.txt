=== Cr8vstacks Table of Contents ===
Contributors: cr8vstacks
Tags: table of contents, toc, gutenberg, reading time, seo
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.3.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Free forever. High-contrast Table of Contents with custom layouts and a prominent sticky TOC header on scroll to keep navigation always accessible.

== Description ==

[Official Plugin Homepage & Demo](https://cr8vstacks.com/dev-playground/cr8vstacks-table-of-contents/)

Cr8vstacks Table of Contents automatically generates a polished, accessible table of contents for WordPress posts and pages. It builds links from your headings, adds stable anchors where needed, and gives readers a clearer way to move through long-form content.

The plugin includes four frontend layouts: Manuscript, Soft Editorial, Brutalist, and Minimalist. Each layout has its own header treatment, body structure, child-heading styling, reading progress style, and Hide/Show button design.

Cr8vstacks Table of Contents is built for Gutenberg and classic publishing workflows. It works with Block Editor headings, adds a Block Editor sidebar for per-post controls, includes a Classic Editor meta box, supports Quick Edit, and can be placed manually with the `[wptw_toc]` shortcode.

For SEO and content navigation, the plugin outputs crawlable anchor links and structured on-page navigation. This can help readers and search engines understand long articles, but it does not replace a dedicated SEO plugin for metadata, schema, sitemaps, or content scoring.

= Key Features =

* Auto-generates a TOC from selected heading levels, H2 through H6.
* Preserves existing heading IDs when possible.
* Adds generated anchors for headings that need them.
* Configurable placement before the first heading, after the first paragraph, or shortcode only.
* Four selectable layouts: Manuscript, Soft Editorial, Brutalist, and Minimalist.
* Live dashboard preview using the same frontend stylesheet.
* Color presets plus individual controls for card, header, links, numbers, progress, toggle, and back-to-top colors.
* Typography controls for font stack, link size, child size, label size, reading time size, number size, label spacing, label transform, and border radius.
* Reading time estimate with configurable words per minute.
* Scroll reading progress indicator that matches the active layout.
* Active-section highlighting and read/done states.
* Sticky TOC header with configurable top offset.
* Optional hierarchical numbering.
* Optional back-to-top button.
* Per-post controls for disabling the TOC, title, state, placement, numbers, sticky header, and reading time.
* No remote font files are loaded; font choices use local or theme-available font stacks.
* Frontend behavior does not require jQuery.

= Layouts =

* Manuscript: dark editorial chapter style with timeline markers, amber progress, and depth-aware child links.
* Soft Editorial: clean card layout with guided sections, completed-state checkmarks, and nested child rows.
* Brutalist: bold dark layout with darker header, offset border extension, squared controls, and high-contrast active rows.
* Minimalist: original clean light card layout for a simple classic TOC.

= Gutenberg, Elementor And Per-Post Controls =

Cr8vstacks Table of Contents supports headings created in the WordPress Block Editor and Elementor page builder. The Block Editor sidebar and Elementor Page Settings panel let editors override TOC settings per post/page (such as disabling the TOC) without changing global defaults.

Per-post controls are also available in the Classic Editor meta box and Quick Edit. Manual placement is available through the Shortcode block or Elementor shortcode widget with `[wptw_toc]`.

= Design Controls =

The settings dashboard includes Visibility, Headings, Layouts, Display, Colours, Typography, and Advanced tabs. The live preview updates before saving so site owners can test layouts, color presets, typography, display options, progress indicators, and visibility behavior.

The Default color preset restores each layout's native palette. Light, Dark, Ocean, Forest, and Rose presets can be customized through the same color controls.

= SEO And Content Navigation =

The plugin can help SEO indirectly by creating crawlable heading anchor links and clearer internal jump navigation for long-form content. Better structure can improve scanability and reader engagement. It does not manage meta titles, descriptions, schema, XML sitemaps, or keyword analysis.

== Installation ==

1. Upload the `cr8vstacks-table-of-contents` folder to `/wp-content/plugins/`.
2. Activate the plugin through the Plugins menu in WordPress.
3. Go to Settings > Cr8v TOC to configure global behavior.
4. Optionally override settings per post through the editor sidebar, meta box, or Quick Edit.

== Shortcode ==

Use `[wptw_toc]` anywhere in your content when Position is set to "shortcode only". Layout, color, typography, display, and per-post settings still apply.

== Frequently Asked Questions ==

= Will the TOC always appear? =

No. The TOC only appears if the post type is enabled, the post meets the minimum heading count, the post ID is not excluded, and TOC is not disabled for that post.

= Can I switch between TOC designs? =

Yes. Go to Settings > Cr8v TOC > Layouts and choose Manuscript, Soft Editorial, Brutalist, or Minimalist.

= Can I preview changes before saving? =

Yes. The dashboard includes a live preview for layout, color, typography, display, progress, and visibility settings.

= Does it work with Gutenberg? =

Yes. It works with Block Editor headings, adds a sidebar panel for per-post controls, and can be placed manually with the Shortcode block.

= Does a table of contents help SEO? =

It can help indirectly by creating crawlable anchor links and clearer on-page navigation. It is not a replacement for an SEO plugin that manages metadata, schema, sitemaps, or content analysis.

= Does the frontend require jQuery? =

No. The frontend script is written without jQuery.

== Changelog ==

= 1.3.1 =
* Added an option to enable the sticky TOC header only on mobile viewports (disables it on desktop screens larger than 768px).
* Fixed CSS variable/custom property inheritance for the sticky TOC scroll dropdown links.

= 1.3.0 =
* Fixed sticky TOC Show/Hide button on scroll to toggle a dropdown menu containing the list of headings directly from the sticky header.
* Added configurable text fields for customizing the "Show" and "Hide" toggle button labels.
* Added an option to enable the sticky TOC header only on mobile viewports (disables it on desktop screens larger than 768px).
* Added Elementor Page Settings integration (on/off switcher to disable the TOC per post/page within the Elementor editor).
* Added frontend alignment settings allowing users to align the Table of Contents card (Left, Center, Right) using block margins.
* Fixed low-contrast scroll progress bars in Brutalist and Manuscript layouts by removing background-diluting color-mix logic that rendered them invisible on most presets.
* Updated check and dot border contrast rules for Brutalist and Soft Editorial layouts, blending the borders with the active text color using CSS color-mix to guarantee legibility on both light and dark presets.
* Fixed a layout clipping bug where the last list item inside expanded TOC cards got cut off at the bottom due to a hardcoded load-time pixel height calculation.
* Added dynamic offset detection for the WordPress admin bar to adjust scroll, sticky top, and active tracking.
* Added :focus-visible outline styles to TOC links and buttons for keyboard accessibility.
* Filtered out empty or whitespace-only headings (like headings with only image tags) to prevent blank TOC entries.
* Constrained the Table of Contents card to a maximum width of 820px and centered it horizontally on wide/full-width templates.
* Hardened layout header visibility by using display: flex !important, preventing third-party title-disabling/hiding scripts from hiding TOC headers on H1-less pages.
* Added a container wrapper with clear: both and display: flow-root to prevent margin collapsing below cleared floated elements and ensure top margin clearance applies properly.
* Adjusted the TOC top margin to 2.75rem to create adequate clearance between the TOC and preceding cleared floated images.
* Fixed a bug where left-aligned or right-aligned images before the first heading would overlap with or float next to the TOC.
* Renamed the public plugin identity to Cr8vstacks Table of Contents and updated the WordPress.org text domain to `cr8vstacks-table-of-contents`.
* Removed the arbitrary CSS override field and output path for WordPress.org repository compliance.
* Moved Quick Edit and Classic Editor meta box CSS and JavaScript into enqueued asset files.
* Changed the TOC stylesheet builder to return CSS for `wp_add_inline_style()`.
* Added selectable TOC layouts: Minimalist, Manuscript, Soft Editorial, and Brutalist.
* Made Manuscript the default layout.
* Added saved Active badges for layouts and colour presets.
* Redesigned the settings dashboard with a wider live preview and dedicated Layouts tab.
* Reworked layouts so H2 entries render as primary sections and H3-H6 entries render as depth-aware child links.
* Matched layout-specific reading progress indicators across frontend, sticky header, and admin preview.
* Hardened TOC text, link, list, and button styles against theme defaults.
* Restyled Hide/Show buttons per layout.
* Added sticky settings-page header, admin footer, and cross-links between Layouts and Colours.
* Added Gutenberg, SEO, and content-navigation documentation.

= 1.2.0 =
* Improved sticky header logic.
* Added per-post settings via Gutenberg sidebar.
* Enhanced Quick Edit integration.
* Refined reading time estimation.
* Added performance and accessibility improvements.

= 1.1.0 =
* Added sticky TOC header, per-post controls, Quick Edit support, color presets, reading time, back-to-top button, and active highlighting.

= 1.0.0 =
* Initial release.

== Screenshots ==

1. Settings Dashboard - Visibility, Headings, and General Layout settings with Live Preview.
2. Minimalist Layout - The classic clean card style TOC.
3. Manuscript Layout - Dark editorial chapter style with timeline nodes, progress bar, and reading time.
4. Soft Editorial Layout - Clean card layout with guided steps and completed section checkmarks.
5. Brutalist Layout - Bold typographic layout with offset borders and high-contrast styling.
6. Mobile Layout - Responsive rendering optimized for smaller viewports.
7. Per-Post Controls - Gutenberg block editor sidebar for post-level overrides.
