<?php
defined( 'ABSPATH' ) || exit;

add_action( 'admin_init', function () {
    register_setting( 'wptw_group', WPTW_OPTION, [ 'sanitize_callback' => 'wptw_sanitize_settings' ] );
} );

add_action( 'admin_menu', function () {
    add_options_page( 'Cr8vstacks Table of Contents', 'Cr8v TOC', 'manage_options', 'cr8vstacks-table-of-contents', 'wptw_render_settings_page' );
} );

/**
 * DO NOT enqueue wp-color-picker or wp-pointer — they conflict.
 * We use native <input type="color"> instead. Zero JS dependencies for admin.
 */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( $hook !== 'settings_page_cr8vstacks-table-of-contents' ) return;

    wp_enqueue_style( 'wptw-admin', WPTW_URL . 'assets/admin.css', [], WPTW_VERSION );

    if ( function_exists( 'wptw_render_toc_styles' ) ) {
        wp_add_inline_style( 'wptw-admin', wptw_render_toc_styles( wptw_get() ) );
    }

    $def_colors = array_filter( wptw_defaults(), static function ( $k ) {
        return strpos( (string) $k, 'color_' ) === 0;
    }, ARRAY_FILTER_USE_KEY );

    wp_enqueue_script( 'wptw-admin', WPTW_URL . 'assets/admin.js', [], WPTW_VERSION, true );
    wp_localize_script( 'wptw-admin', 'wptwAdminSettings', [
        'presets'         => array_map( static fn( $p ) => $p['colors'], wptw_color_presets() ),
        'defaults'        => $def_colors,
        'defaultLayout'   => wptw_defaults()['toc_layout'],
        'optionName'      => WPTW_OPTION,
        // Nonce verification is handled by options.php before this redirect flag is added.
        'settingsUpdated' => ! empty( $_GET['settings-updated'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    ] );
} );

add_filter( 'admin_footer_text', function ( $text ) {
    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    return $screen && $screen->id === 'settings_page_cr8vstacks-table-of-contents' ? '' : $text;
}, 20 );

add_filter( 'update_footer', function ( $text ) {
    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    return $screen && $screen->id === 'settings_page_cr8vstacks-table-of-contents' ? '' : $text;
}, 20 );

/* ── Sanitise ─────────────────────────────────────────────── */
function wptw_sanitize_settings( $raw ): array {
    $d = wptw_defaults();

    $clean['post_types']        = isset( $raw['post_types'] ) && is_array( $raw['post_types'] )
                                    ? array_values( array_map( 'sanitize_text_field', $raw['post_types'] ) ) : [ 'post' ];
    $clean['min_headings']      = wptw_clamp( $raw['min_headings']   ?? 2,   1, 20 );
    $clean['exclude_ids']       = sanitize_text_field( $raw['exclude_ids'] ?? '' );
    $clean['heading_levels']    = isset( $raw['heading_levels'] ) && is_array( $raw['heading_levels'] )
                                    ? array_values( array_intersect( $raw['heading_levels'], [ 'h2','h3','h4','h5','h6' ] ) )
                                    : [ 'h2','h3','h4' ];
    $clean['anchor_prefix']     = sanitize_key( $raw['anchor_prefix'] ?? 'section' ) ?: 'section';
    $clean['toc_title']         = sanitize_text_field( $raw['toc_title'] ?? 'Contents' );
    $clean['toc_layout']        = array_key_exists( $raw['toc_layout'] ?? 'manuscript', wptw_toc_layouts() )
                                    ? $raw['toc_layout'] : 'manuscript';
    $clean['position']          = in_array( $raw['position'] ?? '', [ 'before_first_heading','after_first_paragraph','shortcode_only' ], true )
                                    ? $raw['position'] : 'before_first_heading';
    $clean['default_state']     = ( $raw['default_state'] ?? 'open' ) === 'closed' ? 'closed' : 'open';
    $clean['alignment']         = in_array( $raw['alignment'] ?? 'center', [ 'left', 'center', 'right' ], true )
                                    ? $raw['alignment'] : 'center';
    $clean['show_numbers']      = ! empty( $raw['show_numbers'] );
    $clean['smooth_scroll']     = ! empty( $raw['smooth_scroll'] );
    $clean['scroll_offset']     = wptw_clamp( $raw['scroll_offset']  ?? 80,  0, 500 );
    $clean['highlight_active']  = ! empty( $raw['highlight_active'] );
    $clean['back_to_top']       = ! empty( $raw['back_to_top'] );
    $clean['reading_time']      = ! empty( $raw['reading_time'] );
    $clean['reading_progress']  = ! empty( $raw['reading_progress'] );
    $clean['reading_wpm']       = wptw_clamp( $raw['reading_wpm']    ?? 200, 50, 1000 );
    $clean['sticky_header']     = ! empty( $raw['sticky_header'] );
    $clean['sticky_top_offset'] = wptw_clamp( $raw['sticky_top_offset'] ?? 20, 0, 300 );
    $clean['label_show']         = sanitize_text_field( $raw['label_show'] ?? 'Show' ) ?: 'Show';
    $clean['label_hide']         = sanitize_text_field( $raw['label_hide'] ?? 'Hide' ) ?: 'Hide';
    $clean['sticky_mobile_only'] = ! empty( $raw['sticky_mobile_only'] );

    $color_keys = [
        'color_bg','color_border','color_header_bg',
        'color_label','color_rt','color_rt_bar','color_rt_bar_bg',
        'color_toggle_bg','color_toggle_fg','color_toggle_border',
        'color_link','color_link_hover','color_active_bar','color_active_bg','color_number',
        'color_back_top_bg','color_back_top_fg',
    ];
    foreach ( $color_keys as $k ) {
        $clean[ $k ] = wptw_sanitize_color( $raw[ $k ] ?? '', $d[ $k ] );
    }
    $clean = wptw_normalize_color_rules( $clean, $clean['toc_layout'] );

    $allowed_fonts = array_keys( wptw_available_fonts() );
    $clean['font_family']          = in_array( $raw['font_family'] ?? 'system', $allowed_fonts, true ) ? $raw['font_family'] : 'system';
    $clean['font_size_link']       = wptw_clamp( $raw['font_size_link']      ?? 14, 10, 24 );
    $clean['font_size_sub']        = wptw_clamp( $raw['font_size_sub']       ?? 13, 10, 24 );
    $clean['font_size_label']      = wptw_clamp( $raw['font_size_label']     ?? 10,  8, 20 );
    $clean['font_size_rt']         = wptw_clamp( $raw['font_size_rt']        ?? 10,  8, 20 );
    $clean['font_size_num']        = wptw_clamp( $raw['font_size_num']       ?? 11,  8, 20 );
    $clean['letter_spacing_label'] = wptw_clamp( $raw['letter_spacing_label'] ?? 13, 0, 50 );
    $clean['text_transform_label'] = in_array( $raw['text_transform_label'] ?? 'uppercase', [ 'uppercase','none','capitalize' ], true )
                                        ? $raw['text_transform_label'] : 'uppercase';
    $clean['border_radius']        = wptw_clamp( $raw['border_radius']       ?? 4,   0, 24 );
    return $clean;
}

function wptw_normalize_color_rules( array $c, string $layout = 'default' ): array {
    $bg      = $c['color_bg'] ?? '#ffffff';
    $head_bg = $c['color_header_bg'] ?? '#fafaf9';

    if ( abs( wptw_color_luminance( $bg ) - wptw_color_luminance( $head_bg ) ) < 0.06 ) {
        $head_bg = wptw_color_luminance( $bg ) < 0.5
            ? wptw_color_blend( '#ffffff', $bg, 0.10 )
            : wptw_color_blend( '#0f172a', $bg, 0.06 );
        $c['color_header_bg'] = $head_bg;
    }

    $c['color_label'] = wptw_color_contrast( $c['color_label'], $head_bg ) >= 3.0 ? $c['color_label'] : wptw_secondary_on( $head_bg );
    $c['color_rt']    = wptw_color_contrast( $c['color_rt'], $head_bg ) >= 3.0 ? $c['color_rt'] : wptw_secondary_on( $head_bg );

    $c['color_link']       = wptw_color_contrast( $c['color_link'], $bg ) >= 4.5 ? $c['color_link'] : wptw_primary_on( $bg );
    $c['color_link_hover'] = wptw_color_contrast( $c['color_link_hover'], $bg ) >= 5.0 ? $c['color_link_hover'] : wptw_primary_on( $bg );
    $c['color_number']     = wptw_color_contrast( $c['color_number'], $bg ) >= 2.3 ? $c['color_number'] : wptw_color_blend( wptw_primary_on( $bg ), $bg, 0.48 );

    if ( abs( wptw_color_luminance( $c['color_active_bg'] ) - wptw_color_luminance( $bg ) ) < 0.04 ) {
        $c['color_active_bg'] = wptw_color_luminance( $bg ) < 0.5
            ? wptw_color_blend( '#ffffff', $bg, 0.09 )
            : wptw_color_blend( '#0f172a', $bg, 0.05 );
    }
    if ( min( wptw_color_contrast( $c['color_active_bar'], $bg ), wptw_color_contrast( $c['color_active_bar'], $head_bg ) ) < 2.4 ) {
        $c['color_active_bar'] = $layout === 'brutalist'
            ? ( wptw_color_luminance( $bg ) < 0.5 ? '#ffffff' : '#111827' )
            : ( wptw_color_luminance( $bg ) < 0.5 ? '#d97706' : '#111827' );
    }
    if ( wptw_color_contrast( $c['color_rt_bar'], $bg ) < 2.4 ) {
        $c['color_rt_bar'] = $c['color_active_bar'];
    }

    $c['color_toggle_fg'] = wptw_color_contrast( $c['color_toggle_fg'], $c['color_toggle_bg'] ) >= 4.5 ? $c['color_toggle_fg'] : wptw_primary_on( $c['color_toggle_bg'] );

    return $c;
}

function wptw_primary_on( string $bg ): string {
    return wptw_color_luminance( $bg ) < 0.5 ? '#ffffff' : '#0f172a';
}

function wptw_secondary_on( string $bg ): string {
    return wptw_color_blend( wptw_primary_on( $bg ), $bg, 0.66 );
}

function wptw_color_rgb( string $hex ): array {
    $hex = ltrim( trim( $hex ), '#' );
    if ( strlen( $hex ) === 3 ) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if ( strlen( $hex ) !== 6 || preg_match( '/[^0-9a-f]/i', $hex ) ) {
        $hex = 'ffffff';
    }
    return [
        hexdec( substr( $hex, 0, 2 ) ),
        hexdec( substr( $hex, 2, 2 ) ),
        hexdec( substr( $hex, 4, 2 ) ),
    ];
}

function wptw_color_luminance( string $hex ): float {
    $rgb = array_map( static function ( $channel ) {
        $v = $channel / 255;
        return $v <= 0.03928 ? $v / 12.92 : ( ( $v + 0.055 ) / 1.055 ) ** 2.4;
    }, wptw_color_rgb( $hex ) );

    return ( 0.2126 * $rgb[0] ) + ( 0.7152 * $rgb[1] ) + ( 0.0722 * $rgb[2] );
}

function wptw_color_contrast( string $a, string $b ): float {
    $l1 = wptw_color_luminance( $a ) + 0.05;
    $l2 = wptw_color_luminance( $b ) + 0.05;
    return max( $l1, $l2 ) / min( $l1, $l2 );
}

function wptw_color_blend( string $fg, string $bg, float $amount ): string {
    $fg_rgb = wptw_color_rgb( $fg );
    $bg_rgb = wptw_color_rgb( $bg );
    $amount = max( 0, min( 1, $amount ) );
    $out = [];
    foreach ( [ 0, 1, 2 ] as $i ) {
        $out[] = (int) round( ( $fg_rgb[ $i ] * $amount ) + ( $bg_rgb[ $i ] * ( 1 - $amount ) ) );
    }
    return sprintf( '#%02x%02x%02x', $out[0], $out[1], $out[2] );
}

/* ── Render page ──────────────────────────────────────────── */
function wptw_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    $o       = wptw_get();
    $pts     = wptw_public_post_types();
    $presets = wptw_color_presets();
    $fonts   = wptw_available_fonts();
    $layouts = wptw_toc_layouts();
    $default_layout = wptw_defaults()['toc_layout'];

    $tabs = [
        'visibility' => [ 'label'=>'Visibility', 'svg'=>'<svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M7.5 3C4 3 1 7.5 1 7.5S4 12 7.5 12 14 7.5 14 7.5 11 3 7.5 3Z" stroke="currentColor" stroke-width="1.3"/><circle cx="7.5" cy="7.5" r="2" stroke="currentColor" stroke-width="1.3"/></svg>' ],
        'headings'   => [ 'label'=>'Headings',   'svg'=>'<svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M2 3v9M2 7.5h11M13 3v9" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>' ],
        'layouts'    => [ 'label'=>'Layouts',    'svg'=>'<svg width="15" height="15" viewBox="0 0 15 15" fill="none"><rect x="2" y="2" width="11" height="4" rx="1.2" stroke="currentColor" stroke-width="1.3"/><path d="M3 9h9M3 11.5h7" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>' ],
        'display'    => [ 'label'=>'Display',    'svg'=>'<svg width="15" height="15" viewBox="0 0 15 15" fill="none"><rect x="1" y="1" width="13" height="13" rx="2.5" stroke="currentColor" stroke-width="1.3"/><path d="M4 5h7M4 7.5h5M4 10h6" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>' ],
        'colours'    => [ 'label'=>'Colours',    'svg'=>'<svg width="15" height="15" viewBox="0 0 15 15" fill="none"><circle cx="7.5" cy="7.5" r="6" stroke="currentColor" stroke-width="1.3"/><circle cx="5" cy="6" r="1.2" fill="currentColor"/><circle cx="10" cy="6" r="1.2" fill="currentColor"/><circle cx="7.5" cy="10.2" r="1.2" fill="currentColor"/></svg>' ],
        'typography' => [ 'label'=>'Typography', 'svg'=>'<svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M2 3h11M7.5 3v9M5 12h5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>' ],
        'advanced'   => [ 'label'=>'Advanced',   'svg'=>'<svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M7.5 1v2M7.5 12v2M1 7.5h2M12 7.5h2" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/><circle cx="7.5" cy="7.5" r="2.8" stroke="currentColor" stroke-width="1.3"/></svg>' ],
    ];
    ?>
    <div class="wrap wptw-wrap">
        <header class="wptw-ph">
            <div class="wptw-logo">
                <svg width="28" height="28" viewBox="0 0 28 28" fill="none"><rect x="1" y="1" width="26" height="26" rx="6" fill="#111"/><path d="M7 9h6M7 14h12M7 19h9" stroke="#fff" stroke-width="1.8" stroke-linecap="round"/></svg>
                <div><span class="wptw-pname">Cr8vstacks Table of Contents</span><span class="wptw-pver">v<?php echo esc_html( WPTW_VERSION ); ?></span></div>
            </div>
            <a href="https://cr8vstacks.com" target="_blank" rel="noopener noreferrer" class="wptw-by">by Cr8v Stacks ↗</a>
        </header>

        <div class="wptw-layout">
            <nav class="wptw-tabs" role="tablist">
                <?php foreach ( $tabs as $tid => $t ) : ?>
                <button type="button" class="wptw-tab" role="tab" data-tab="<?php echo esc_attr( $tid ); ?>" aria-selected="false">
                    <?php echo $t['svg']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( $t['label'] ); ?></span>
                </button>
                <?php endforeach; ?>
            </nav>

            <form method="post" action="options.php" id="wptw-form">
                <?php settings_fields( 'wptw_group' ); ?>
                <div class="wptw-workbench">
                <div class="wptw-editor">

                <!-- ══ VISIBILITY ══ -->
                <section class="wptw-panel" data-panel="visibility">
                    <?php wptw_ph('Visibility','Control where and when the TOC appears.'); ?>
                    <div class="wptw-fields">
                        <div class="wptw-field">
                            <label class="wptw-label">Post types</label>
                            <div class="wptw-checkgroup">
                                <?php foreach($pts as $slug=>$lbl): ?>
                                <label class="wptw-check"><input type="checkbox" name="<?php echo esc_attr( WPTW_OPTION ); ?>[post_types][]" value="<?php echo esc_attr($slug);?>" <?php checked(in_array($slug,(array)$o['post_types'],true));?>><span><?php echo esc_html($lbl);?></span></label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="wptw-field">
                            <label class="wptw-label">Minimum H2 headings to show TOC</label>
                            <input type="number" name="<?php echo esc_attr( WPTW_OPTION ); ?>[min_headings]" value="<?php echo esc_attr($o['min_headings']);?>" min="1" max="20" class="wptw-num">
                        </div>
                        <div class="wptw-field">
                            <label class="wptw-label">Exclude post IDs</label>
                            <input type="text" name="<?php echo esc_attr( WPTW_OPTION ); ?>[exclude_ids]" value="<?php echo esc_attr($o['exclude_ids']);?>" class="wptw-input" placeholder="42, 107, 300">
                            <p class="wptw-help">Comma-separated. TOC suppressed on these posts regardless of other settings.</p>
                        </div>
                        <div class="wptw-field">
                            <label class="wptw-label">Default position</label>
                            <div class="wptw-radio-group">
                                <?php foreach ( [ 'before_first_heading' => 'Before first heading', 'after_first_paragraph' => 'After first paragraph', 'shortcode_only' => 'Manual — [wptw_toc] shortcode only' ] as $v => $l ) : ?>
                                <label class="wptw-radio"><input type="radio" name="<?php echo esc_attr( WPTW_OPTION ); ?>[position]" value="<?php echo esc_attr( $v ); ?>" <?php checked( $o['position'], $v ); ?>><span><?php echo esc_html( $l ); ?></span></label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ══ HEADINGS ══ -->
                <section class="wptw-panel" data-panel="headings">
                    <?php wptw_ph('Headings','Which heading levels appear in the TOC.'); ?>
                    <div class="wptw-fields">
                        <div class="wptw-field">
                            <label class="wptw-label">Include heading levels</label>
                            <div class="wptw-hpicker">
                                <?php foreach ( [ 'h2', 'h3', 'h4', 'h5', 'h6' ] as $h ) : ?>
                                <label class="wptw-hpick <?php echo in_array( $h, (array) $o['heading_levels'], true ) ? 'on' : ''; ?>">
                                    <input type="checkbox" name="<?php echo esc_attr( WPTW_OPTION ); ?>[heading_levels][]" value="<?php echo esc_attr( $h ); ?>" <?php checked( in_array( $h, (array) $o['heading_levels'], true ) ); ?>>
                                    <span><?php echo esc_html( strtoupper( $h ) ); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <p class="wptw-help">H2 strongly recommended as top-level entry.</p>
                        </div>
                        <div class="wptw-field">
                            <label class="wptw-label">Anchor prefix</label>
                            <div class="wptw-affixwrap">
                                <span class="wptw-affix">#</span>
                                <input type="text" name="<?php echo esc_attr( WPTW_OPTION ); ?>[anchor_prefix]" value="<?php echo esc_attr($o['anchor_prefix']);?>" class="wptw-affixinput" placeholder="section">
                                <span class="wptw-affix wptw-affixr">-0</span>
                            </div>
                            <p class="wptw-help">Generates anchors like <code>#section-0</code>, <code>#section-1</code>.</p>
                        </div>
                    </div>
                </section>

                <!-- ══ DISPLAY ══ -->
                <section class="wptw-panel" data-panel="layouts">
                    <?php wptw_ph('Layouts','Choose the frontend TOC structure. All layouts still use the same colour, typography, display, and heading controls.'); ?>
                    <div class="wptw-panel-shortcuts">
                        <button type="button" class="wptw-jump" data-jump-tab="colours">Tune colours</button>
                    </div>
                    <div class="wptw-layout-grid">
                        <?php foreach ( $layouts as $lid => $layout ) : ?>
                        <?php $active_layout = array_key_exists( (string) $o['toc_layout'], $layouts ) ? $o['toc_layout'] : $default_layout; ?>
                        <label class="wptw-layout-card <?php echo $active_layout === $lid ? 'on is-saved-active' : ''; ?>" data-layout-id="<?php echo esc_attr( $lid ); ?>">
                            <input type="radio" name="<?php echo esc_attr( WPTW_OPTION ); ?>[toc_layout]" value="<?php echo esc_attr( $lid ); ?>" <?php checked( $active_layout, $lid ); ?>>
                            <span class="wptw-card-badges">
                                <span class="wptw-badge wptw-badge--active">Active</span>
                            </span>
                            <span class="wptw-layout-mini wptw-layout-mini--<?php echo esc_attr( $lid ); ?>">
                                <span></span><span></span><span></span>
                            </span>
                            <strong><?php echo esc_html( $layout['label'] ); ?></strong>
                            <small><?php echo esc_html( $layout['desc'] ); ?></small>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="wptw-panel" data-panel="display">
                    <?php wptw_ph('Display','Behaviour, features, and interaction settings.'); ?>
                    <div class="wptw-fields">
                        <div class="wptw-field">
                            <label class="wptw-label">TOC title</label>
                            <input type="text" name="<?php echo esc_attr( WPTW_OPTION ); ?>[toc_title]" value="<?php echo esc_attr($o['toc_title']);?>" class="wptw-input">
                        </div>
                        <div class="wptw-field">
                            <label class="wptw-label">Default TOC state</label>
                            <div class="wptw-seg">
                                <label class="wptw-segopt <?php echo $o['default_state']==='open'?'on':'';?>">
                                    <input type="radio" name="<?php echo esc_attr( WPTW_OPTION ); ?>[default_state]" value="open" <?php checked($o['default_state'],'open');?>>
                                    ▾ Open
                                </label>
                                <label class="wptw-segopt <?php echo $o['default_state']==='closed'?'on':'';?>">
                                    <input type="radio" name="<?php echo esc_attr( WPTW_OPTION ); ?>[default_state]" value="closed" <?php checked($o['default_state'],'closed');?>>
                                    ▸ Closed
                                </label>
                            </div>
                            <p class="wptw-help">Can be overridden per post in the editor.</p>
                        </div>

                        <div class="wptw-field">
                            <label class="wptw-label">TOC alignment</label>
                            <div class="wptw-seg">
                                <label class="wptw-segopt <?php echo ($o['alignment'] ?? 'center') === 'left' ? 'on' : ''; ?>">
                                    <input type="radio" name="<?php echo esc_attr( WPTW_OPTION ); ?>[alignment]" value="left" <?php checked($o['alignment'] ?? 'center', 'left'); ?>>
                                    Left
                                </label>
                                <label class="wptw-segopt <?php echo ($o['alignment'] ?? 'center') === 'center' ? 'on' : ''; ?>">
                                    <input type="radio" name="<?php echo esc_attr( WPTW_OPTION ); ?>[alignment]" value="center" <?php checked($o['alignment'] ?? 'center', 'center'); ?>>
                                    Center
                                </label>
                                <label class="wptw-segopt <?php echo ($o['alignment'] ?? 'center') === 'right' ? 'on' : ''; ?>">
                                    <input type="radio" name="<?php echo esc_attr( WPTW_OPTION ); ?>[alignment]" value="right" <?php checked($o['alignment'] ?? 'center', 'right'); ?>>
                                    Right
                                </label>
                            </div>
                            <p class="wptw-help">Set the horizontal alignment of the TOC block on the page.</p>
                        </div>

                        <div class="wptw-field">
                            <label class="wptw-label">Show button label</label>
                            <input type="text" name="<?php echo esc_attr( WPTW_OPTION ); ?>[label_show]" value="<?php echo esc_attr($o['label_show']);?>" class="wptw-input">
                            <p class="wptw-help">Custom text label for the "Show" toggle button.</p>
                        </div>
                        <div class="wptw-field">
                            <label class="wptw-label">Hide button label</label>
                            <input type="text" name="<?php echo esc_attr( WPTW_OPTION ); ?>[label_hide]" value="<?php echo esc_attr($o['label_hide']);?>" class="wptw-input">
                            <p class="wptw-help">Custom text label for the "Hide" toggle button.</p>
                        </div>

                        <?php foreach ( [
                            'show_numbers'     => [ 'Section numbers',         'Show 1. / 1.1. / 2. numbering beside each entry.' ],
                            'smooth_scroll'    => [ 'Smooth scroll',           'Animate page scroll when a TOC link is clicked.' ],
                            'highlight_active' => [ 'Highlight active section', 'Track scroll position and highlight current section.' ],
                            'back_to_top'      => [ 'Back-to-top button',      'Floating button that appears after scrolling past the TOC.' ],
                            'reading_time'     => [ 'Reading time estimate',   'Show "X min read" in the TOC header.' ],
                            'reading_progress' => [ 'Reading progress bar',    'Thin bar below the TOC header that fills as the reader scrolls through the article. Uses the reading speed setting below.' ],
                        ] as $key => [ $label, $desc ] ) : ?>
                        <div class="wptw-field wptw-togfield">
                            <div class="wptw-togrow">
                                <label class="wptw-sw">
                                    <input type="hidden" name="<?php echo esc_attr( WPTW_OPTION ); ?>[<?php echo esc_attr( $key ); ?>]" value="0">
                                    <input type="checkbox" name="<?php echo esc_attr( WPTW_OPTION ); ?>[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $o[ $key ] ) ); ?>>
                                    <span class="wptw-swknob"></span>
                                </label>
                                <div><span class="wptw-swlabel"><?php echo esc_html( $label ); ?></span><p class="wptw-help"><?php echo esc_html( $desc ); ?></p></div>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <div class="wptw-field">
                            <label class="wptw-label">Reading speed (words per minute)</label>
                            <input type="number" name="<?php echo esc_attr( WPTW_OPTION ); ?>[reading_wpm]" value="<?php echo esc_attr($o['reading_wpm']);?>" min="50" max="1000" class="wptw-num">
                            <p class="wptw-help">Controls both the "X min read" estimate and how fast the reading progress bar fills. Average adult reads ~200 wpm.</p>
                        </div>

                        <hr class="wptw-hr">

                        <div class="wptw-field wptw-togfield">
                            <div class="wptw-togrow">
                                <label class="wptw-sw">
                                    <input type="hidden" name="<?php echo esc_attr( WPTW_OPTION ); ?>[sticky_header]" value="0">
                                    <input type="checkbox" name="<?php echo esc_attr( WPTW_OPTION ); ?>[sticky_header]" value="1" id="wptw-sticky-toggle" <?php checked(!empty($o['sticky_header']));?>>
                                    <span class="wptw-swknob"></span>
                                </label>
                                <div>
                                    <span class="wptw-swlabel">Sticky TOC header</span>
                                    <p class="wptw-help">Once you scroll past the TOC, the header bar (title + reading time + toggle) becomes <strong>fixed to the viewport</strong> — open or closed. It hides when you scroll back above the TOC.</p>
                                </div>
                            </div>
                        </div>
                        <div class="wptw-field wptw-stickyex" id="wptw-sticky-sub">
                            <label class="wptw-label">Sticky top offset (px)</label>
                            <div class="wptw-slrow">
                                <input type="range" id="wptw-sticky-range" min="0" max="200" value="<?php echo esc_attr($o['sticky_top_offset']);?>" class="wptw-range">
                                <output id="wptw-sticky-out" class="wptw-rval"><?php echo esc_html($o['sticky_top_offset']);?>px</output>
                            </div>
                            <input type="number" id="wptw-sticky-num" name="<?php echo esc_attr( WPTW_OPTION ); ?>[sticky_top_offset]" value="<?php echo esc_attr($o['sticky_top_offset']);?>" min="0" max="300" class="wptw-num" style="margin-top:6px">
                            <p class="wptw-help">Distance from viewport top when fixed. Set to your site's fixed navigation height.</p>
                        </div>
                        <div class="wptw-field wptw-togfield">
                            <div class="wptw-togrow">
                                <label class="wptw-sw">
                                    <input type="hidden" name="<?php echo esc_attr( WPTW_OPTION ); ?>[sticky_mobile_only]" value="0">
                                    <input type="checkbox" name="<?php echo esc_attr( WPTW_OPTION ); ?>[sticky_mobile_only]" value="1" <?php checked(!empty($o['sticky_mobile_only']));?>>
                                    <span class="wptw-swknob"></span>
                                </label>
                                <div>
                                    <span class="wptw-swlabel">Sticky TOC on mobile only</span>
                                    <p class="wptw-help">Disables the sticky header on desktop viewports, keeping it active only on mobile viewports (widths under 768px).</p>
                                </div>
                            </div>
                        </div>
                        <div class="wptw-field">
                            <label class="wptw-label">Smooth scroll offset (px)</label>
                            <input type="number" name="<?php echo esc_attr( WPTW_OPTION ); ?>[scroll_offset]" value="<?php echo esc_attr($o['scroll_offset']);?>" min="0" max="500" class="wptw-num">
                            <p class="wptw-help">Clearance when jumping to a section — set to your site header height + sticky TOC bar height combined.</p>
                        </div>
                    </div>
                </section>

                <!-- ══ COLOURS ══ -->
                <section class="wptw-panel" data-panel="colours">
                    <?php wptw_ph('Colours','All 16 colour controls. Presets cover every control with matched, readable combinations.'); ?>
                    <div class="wptw-panel-shortcuts">
                        <button type="button" class="wptw-jump" data-jump-tab="layouts">Choose layout</button>
                    </div>
                    <div class="wptw-fields">
                        <div class="wptw-field">
                            <label class="wptw-label">Presets</label>
                            <div class="wptw-presets" id="wptw-presets">
                                <?php foreach ( $presets as $pid => $p ) : ?>
                                <button type="button" class="wptw-pbtn" data-preset="<?php echo esc_attr( $pid ); ?>">
                                    <span><?php echo esc_html( $p['emoji'] ); ?> <?php echo esc_html( $p['label'] ); ?></span>
                                    <span class="wptw-badge wptw-badge--active">Active</span>
                                </button>
                                <?php endforeach; ?>
                                <button type="button" class="wptw-pbtn wptw-reset" data-preset="__reset">↩ Reset</button>
                            </div>
                        </div>

                        <?php
                        $cgroups=[
                            'Card'           =>['color_bg'=>'Background','color_border'=>'Border'],
                            'Header bar'     =>['color_header_bg'=>'Header background','color_label'=>'Title label text','color_rt'=>'Reading time text','color_rt_bar'=>'Progress bar fill','color_rt_bar_bg'=>'Progress bar track'],
                            'Toggle button'  =>['color_toggle_bg'=>'Button background','color_toggle_fg'=>'Button text / icon','color_toggle_border'=>'Button border'],
                            'List items'     =>['color_link'=>'Link text','color_link_hover'=>'Link hover','color_active_bar'=>'Active / progress accent','color_active_bg'=>'Active background','color_number'=>'Section numbers'],
                            'Back-to-top'    =>['color_back_top_bg'=>'Button background','color_back_top_fg'=>'Button icon'],
                        ];
                        foreach ( $cgroups as $grp => $fields ) : ?>
                        <div class="wptw-cgroup">
                            <p class="wptw-cglabel"><?php echo esc_html( $grp ); ?></p>
                            <div class="wptw-crow">
                                <?php foreach($fields as $ck=>$cl): ?>
                                <div class="wptw-cfield">
                                    <label class="wptw-clabel"><?php echo esc_html($cl);?></label>
                                    <div class="wptw-cswatch">
                                        <input type="color" name="<?php echo esc_attr( WPTW_OPTION ); ?>[<?php echo esc_attr( $ck ); ?>]"
                                               value="<?php echo esc_attr($o[$ck]);?>"
                                               data-key="<?php echo esc_attr( $ck ); ?>"
                                               class="wptw-color"
                                               data-default="<?php echo esc_attr( wptw_defaults()[ $ck ] ); ?>">
                                        <span class="wptw-chex"><?php echo esc_html($o[$ck]);?></span>
                                        <button type="button" class="wptw-creset" title="Reset to default" data-key="<?php echo esc_attr( $ck ); ?>" data-default="<?php echo esc_attr( wptw_defaults()[ $ck ] ); ?>">↺</button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- ══ TYPOGRAPHY ══ -->
                <section class="wptw-panel" data-panel="typography">
                    <?php wptw_ph('Typography','Font family, sizes, spacing, and border radius for every text element.'); ?>
                    <div class="wptw-fields">
                        <div class="wptw-field">
                            <label class="wptw-label">Font family</label>
                            <select name="<?php echo esc_attr( WPTW_OPTION ); ?>[font_family]" id="wptw-font-family" class="wptw-input">
                                <?php foreach($fonts as $fv=>$fl): ?>
                                <option value="<?php echo esc_attr($fv);?>" <?php selected($o['font_family'],$fv);?>><?php echo esc_html($fl);?></option>
                                <?php endforeach; ?>
                            </select>
                            <span id="wptw-font-preview" style="display:block;margin-top:8px;font-size:13px;color:#555;min-height:18px"></span>
                            <p class="wptw-help">Applies to link text in the list. System default inherits your theme's font, and all font choices use local or theme-available font stacks.</p>
                        </div>

                        <p class="wptw-subhead">Link list</p>
                        <div class="wptw-twofield">
                            <?php wptw_sf('font_size_link','Link font size (px)',$o['font_size_link'],10,24); ?>
                            <?php wptw_sf('font_size_sub','Sub-heading size (px)',$o['font_size_sub'],10,24); ?>
                        </div>

                        <hr class="wptw-hr">
                        <p class="wptw-subhead">Header bar elements</p>
                        <p class="wptw-help" style="margin-top:-10px">Controls <code>.wptw-toc__label</code> (title), <code>.wptw-toc__rt</code> (reading time), <code>.wptw-toc__num</code> (section numbers)</p>

                        <div class="wptw-twofield">
                            <?php wptw_sf('font_size_label','Title label size (px)',$o['font_size_label'],8,20); ?>
                            <?php wptw_sf('font_size_rt','Reading time size (px)',$o['font_size_rt'],8,20); ?>
                        </div>
                        <?php wptw_sf('font_size_num','Section number size (px)',$o['font_size_num'],8,20); ?>

                        <div class="wptw-field">
                            <label class="wptw-label">Title label letter-spacing</label>
                            <div class="wptw-slrow">
                                <input type="range" class="wptw-range wptw-slsync" data-num="wptw-num-lsp" min="0" max="50" value="<?php echo esc_attr($o['letter_spacing_label']);?>">
                                <output class="wptw-rval"><?php echo esc_html($o['letter_spacing_label']);?></output>
                            </div>
                            <input type="number" id="wptw-num-lsp" name="<?php echo esc_attr( WPTW_OPTION ); ?>[letter_spacing_label]" value="<?php echo esc_attr($o['letter_spacing_label']);?>" min="0" max="50" class="wptw-num" style="margin-top:6px">
                            <p class="wptw-help">In hundredths of em. 13 = 0.13em. Controls tracking on the "Contents" title label.</p>
                        </div>

                        <div class="wptw-field">
                            <label class="wptw-label">Title label text transform</label>
                            <div class="wptw-seg">
                                <?php foreach ( [ 'uppercase' => 'UPPERCASE', 'capitalize' => 'Capitalize', 'none' => 'none' ] as $tv => $tl ) : ?>
                                <label class="wptw-segopt <?php echo $o['text_transform_label'] === $tv ? 'on' : ''; ?>">
                                    <input type="radio" name="<?php echo esc_attr( WPTW_OPTION ); ?>[text_transform_label]" value="<?php echo esc_attr( $tv ); ?>" <?php checked( $o['text_transform_label'], $tv ); ?>>
                                    <?php echo esc_html( $tl ); ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <hr class="wptw-hr">
                        <p class="wptw-subhead">Card shape</p>
                        <?php wptw_sf('border_radius','Border radius (px)',$o['border_radius'],0,24); ?>
                    </div>
                </section>

                <!-- ══ ADVANCED ══ -->
                <section class="wptw-panel" data-panel="advanced">
                    <?php wptw_ph('Advanced','Shortcode and manual placement reference.'); ?>
                    <div class="wptw-fields">
                        <div class="wptw-field">
                            <label class="wptw-label">Shortcode</label>
                            <div class="wptw-codebox"><code>[wptw_toc]</code><button type="button" class="wptw-copybtn" data-copy="[wptw_toc]">Copy</button></div>
                            <p class="wptw-help">Use this shortcode when Display > Position is set to shortcode only, or place it in a Shortcode block for manual placement.</p>
                        </div>
                    </div>
                </section>

                <div class="wptw-footer">
                    <?php submit_button('Save settings','primary wptw-savebtn','submit',false); ?>
                    <span id="wptw-saved" class="wptw-saved"><span class="wptw-toast-icon">✓</span> Saved</span>
                </div>
                </div>

                <aside class="wptw-preview" aria-label="Live table of contents preview">
                    <div class="wptw-preview__bar">
                        <span>Live preview</span>
                        <button type="button" class="wptw-preview__toggle" id="wptw-preview-toggle">Desktop</button>
                    </div>
                    <div class="wptw-preview__canvas">
                        <div class="wptw-toc wptw-preview-toc wptw-toc--layout-<?php echo esc_attr( array_key_exists( (string) $o['toc_layout'], $layouts ) ? $o['toc_layout'] : $default_layout ); ?>">
                            <div class="wptw-toc__head">
                                <div class="wptw-toc__head-left">
                                    <span class="wptw-toc__label"><?php echo esc_html( $o['toc_title'] ); ?></span>
                                    <span class="wptw-toc__rt">5 min read</span>
                                </div>
                                <button type="button" class="wptw-toc__toggle" aria-expanded="true">
                                    <span class="wptw-toc__tog-text">Hide</span>
                                    <svg class="wptw-toc__tog-icon" width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true"><path d="M2 4.5L6 8.5L10 4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                            </div>
                            <div class="wptw-toc__prog" role="presentation"><div class="wptw-toc__prog-fill" style="width:42%"></div></div>
                            <div class="wptw-toc__body">
                                <ol class="wptw-toc__list" role="list">
                                    <li class="wptw-toc__item is-done" style="--i:0"><a class="wptw-toc__link" href="#"><span class="wptw-toc__num">1.</span><span class="wptw-toc__text">Getting started</span></a></li>
                                    <li class="wptw-toc__item is-done wptw-toc__item--sub wptw-toc__item--d3" style="--i:1"><a class="wptw-toc__link" href="#"><span class="wptw-toc__num">1.1.</span><span class="wptw-toc__text">Setup checklist</span></a></li>
                                    <li class="wptw-toc__item is-active" style="--i:2"><a class="wptw-toc__link" href="#"><span class="wptw-toc__num">2.</span><span class="wptw-toc__text">Design decisions</span></a></li>
                                    <li class="wptw-toc__item wptw-toc__item--sub wptw-toc__item--d3" style="--i:3"><a class="wptw-toc__link" href="#"><span class="wptw-toc__num">2.1.</span><span class="wptw-toc__text">Responsive behavior</span></a></li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </aside>
                </div>
            </form>
        </div>
        <footer class="wptw-admin-footer">
            <span>Cr8vstacks Table of Contents <?php echo esc_html( WPTW_VERSION ); ?></span>
            <span>Built by <a href="https://cr8vstacks.com" target="_blank" rel="noopener noreferrer">Cr8v Stacks</a></span>
        </footer>
    </div>

    <?php
}

function wptw_ph( string $t, string $d ): void {
    echo '<div class="wptw-panel-header"><h2>' . esc_html($t) . '</h2><p>' . esc_html($d) . '</p></div>';
}

function wptw_sf( string $key, string $label, $val, int $min, int $max ): void {
    $id = 'wptw-num-' . $key;
    echo '<div class="wptw-field">';
    echo '<label class="wptw-label">' . esc_html( $label ) . '</label>';
    echo '<div class="wptw-slrow">';
    echo '<input type="range" class="wptw-range wptw-slsync" data-num="' . esc_attr( $id ) . '" min="' . (int) $min . '" max="' . (int) $max . '" value="' . esc_attr( $val ) . '">';
    echo '<output class="wptw-rval">' . esc_html( $val ) . '</output>';
    echo '</div>';
    echo '<input type="number" id="' . esc_attr( $id ) . '" name="' . esc_attr( WPTW_OPTION ) . '[' . esc_attr( $key ) . ']" value="' . esc_attr( $val ) . '" min="' . (int) $min . '" max="' . (int) $max . '" class="wptw-num" style="margin-top:6px">';
    echo '</div>';
}
