<?php
defined( 'ABSPATH' ) || exit;

/* â”€â”€â”€ Content filter â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
add_filter( 'the_content', 'wptw_inject_toc', 999 );

function wptw_inject_toc( string $content ): string {
    if ( ! is_singular() ) return $content;

    global $wp_query;
    if ( ! is_main_query() || ! in_the_loop() ) {
        return $content;
    }
    if ( get_the_ID() !== $wp_query->get_queried_object_id() ) {
        return $content;
    }

    $post_id   = get_the_ID();
    $post_type = get_post_type( $post_id );
    $types     = (array) wptw_get( 'post_types' );
    if ( ! in_array( $post_type, $types, true ) ) return $content;

    $excluded = array_filter( array_map( 'intval', explode( ',', wptw_get( 'exclude_ids' ) ) ) );
    if ( in_array( $post_id, $excluded, true ) ) return $content;

    $meta = wptw_post_meta( $post_id );
    if ( ! empty( $meta['disable'] ) ) return $content;

    $position = ( $meta['position'] ?? '' ) ?: wptw_get( 'position' );

    $toc = wptw_build_toc( $content, $post_id );
    if ( $toc === null ) return $content;

    $marker = '<!-- wptw_toc_placeholder -->';
    if ( strpos( $content, $marker ) !== false ) {
        return str_replace( $marker, $toc, $content );
    }

    if ( $position === 'shortcode_only' ) return $content;

    if ( $position === 'after_first_paragraph' ) {
        $p = wptw_find_root_paragraph( $content, (array) wptw_get( 'heading_levels' ) );
        if ( $p !== false ) {
            return substr_replace( $content, '</p>' . $toc, $p, 4 );
        }
    }

    $levels = (array) wptw_get( 'heading_levels' );
    $h = wptw_find_root_heading( $content, $levels );
    if ( $h !== false ) {
        return substr_replace( $content, $toc, $h, 0 );
    }

    return $toc . $content;
}

/* â”€â”€â”€ Build TOC HTML â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
function wptw_build_toc( string &$content, int $post_id = 0 ): ?string {
    $levels = (array) wptw_get( 'heading_levels' );
    if ( empty( $levels ) ) return null;

    $nums    = implode( '', array_map( fn($h) => substr($h,1), $levels ) );
    $pattern = '/<h([' . $nums . '])([^>]*)>(.*?)<\/h[' . $nums . ']>/is';
    preg_match_all( $pattern, $content, $matches, PREG_SET_ORDER );
    if ( empty( $matches ) ) return null;

    $h2_count = 0;
    foreach ( $matches as $m ) { if ( $m[1] === '2' ) $h2_count++; }
    if ( $h2_count < (int) wptw_get( 'min_headings' ) ) return null;

    $eff = fn( string $k ) => wptw_effective( $k, $post_id );

    $state     = $eff('default_state') === 'closed' ? 'closed' : 'open';
    $expanded  = $state === 'open';
    $show_nums = (bool)( $eff('show_numbers') !== '' ? (int)$eff('show_numbers') : wptw_get('show_numbers') );
    $show_rt   = (bool)( $eff('reading_time') !== '' ? (int)$eff('reading_time') : wptw_get('reading_time') );
    $show_prog = (bool) wptw_get('reading_progress');
    $sticky    = (bool)( $eff('sticky_header') !== '' ? (int)$eff('sticky_header') : wptw_get('sticky_header') );
    $toc_title = trim((string)$eff('toc_title')) ?: wptw_get('toc_title');
    $layout    = array_key_exists( (string) wptw_get('toc_layout'), wptw_toc_layouts() ) ? (string) wptw_get('toc_layout') : 'manuscript';
    $prefix    = sanitize_key( wptw_get('anchor_prefix') );
    $pid_sfx   = $post_id ? '-' . $post_id : '';
    $list_id   = 'wptw-list' . $pid_sfx;
    $toc_id    = 'wptw-toc' . $pid_sfx;

    /* Reading time â€” stored in data attr so JS can count down */
    $total_mins = wptw_reading_time( $content, (int) wptw_get('reading_wpm') );
    $rt_html = '';
    if ( $show_rt ) {
        $rt_text = sprintf( esc_html__( '%d min read', 'cr8vstacks-table-of-contents' ), $total_mins );
        $rt_html = '<span class="wptw-toc__rt" data-total-mins="' . $total_mins . '">' . esc_html( $rt_text ) . '</span>';
    }

    /* Progress bar markup */
    $prog_html = '';
    if ( $show_prog ) {
        $prog_html = '<div class="wptw-toc__prog" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="wptw-toc__prog-fill"></div></div>';
    }
    $ms_prog_html = $show_prog ? '<div class="toc-ms-footer"><span class="toc-ms-footer-label">' . esc_html__( 'Progress', 'cr8vstacks-table-of-contents' ) . '</span><div class="wptw-toc__prog toc-ms-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="wptw-toc__prog-fill toc-ms-track-fill"></div></div></div>' : '';
    $ed_prog_html = $show_prog ? '<div class="toc-ed-footer"><div class="wptw-toc__prog toc-ed-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="wptw-toc__prog-fill toc-ed-progress-fill"></div></div><span class="toc-ed-progress-label">0% done</span></div>' : '';
    $brut_prog_html = $show_prog ? '<div class="wptw-toc__prog toc-brut-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="wptw-toc__prog-fill toc-brut-progress-fill"></div></div>' : '';

    $aria_exp   = $expanded ? 'true' : 'false';
    $label_show = wptw_get( 'label_show' ) ?: 'Show';
    $label_hide = wptw_get( 'label_hide' ) ?: 'Hide';
    $tog_label  = $expanded ? $label_hide : $label_show;
    $list_style = $expanded ? '' : ' style="height:0;opacity:0;padding-top:0;padding-bottom:0;"';
    $sticky_cls = $sticky   ? ' wptw-toc--sticky' : '';

    /*
     * HTML structure:
     *   .wptw-toc                â€” outer card
     *     .wptw-toc__head        â€” header bar (source of truth for sticky clone)
     *     .wptw-toc__prog        â€” progress bar (also cloned into sticky bar)
     *     .wptw-toc__body        â€” overflow:hidden collapse region
     *       ol.wptw-toc__list
     */
    $counters  = [ 0, 0, 0, 0, 0 ];
    $min_depth = (int) substr( $levels[0], 1 );
    $items     = [];

    foreach ( $matches as $i => $match ) {
        $depth  = (int) $match[1];
        $attrs  = $match[2];
        $inner  = $match[3];
        $title  = trim( wp_strip_all_tags( $inner ) );
        if ( $title === '' ) continue;
        $anchor = $prefix . '-' . $i . $pid_sfx;

        $idx = $depth - 2;
        $counters[$idx]++;
        for ($j = $idx+1; $j <= 4; $j++) $counters[$j] = 0;

        $num_str = '';
        if ( $show_nums && ! preg_match('/^\d+\.\s/',$title) ) {
            $num_str = implode('.', array_slice($counters,0,$depth-1)) . '.';
        }

        $rel = $depth - $min_depth;
        $cls = 'wptw-toc__item' . ( $rel > 0 ? ' wptw-toc__item--sub wptw-toc__item--d' . $depth : '' );

        $href = '#' . $anchor;

        if ( strpos($attrs,'id=') === false ) {
            $new_h = '<h' . $depth . ' id="' . esc_attr($anchor) . '"' . $attrs . '>' . $inner . '</h' . $depth . '>';
        } else {
            preg_match('/id=["\']([^"\']+)["\']/', $attrs, $id_m);
            if ( ! empty($id_m[1]) ) $href = '#' . $id_m[1];
            $new_h = $match[0];
        }
        $content = str_replace( $match[0], $new_h, $content );

        $items[] = [
            'index' => $i,
            'depth' => $depth,
            'rel'   => $rel,
            'class' => $cls,
            'href'  => $href,
            'num'   => $num_str,
            'title' => $title,
        ];
    }

    $toggle  = '<button type="button" class="wptw-toc__toggle" aria-expanded="' . $aria_exp . '" aria-controls="' . esc_attr( $list_id ) . '">';
    $toggle .= '<span class="wptw-toc__tog-text">' . esc_html( $tog_label ) . '</span>';
    $toggle .= '<svg class="wptw-toc__tog-icon" width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true"><path d="M2 4.5L6 8.5L10 4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    $toggle .= '</button>';

    $section_groups = [];
    foreach ( $items as $item ) {
        if ( $item['depth'] <= 2 || empty( $section_groups ) ) {
            $section_groups[] = [
                'parent'   => $item,
                'children' => [],
            ];
            continue;
        }
        $last = count( $section_groups ) - 1;
        $section_groups[ $last ]['children'][] = $item;
    }

    $out  = '<div class="wptw-toc-wrapper">';
    $out .= '<div class="wptw-toc wptw-toc--layout-' . esc_attr( $layout ) . $sticky_cls . '" id="' . esc_attr( $toc_id ) . '" role="navigation" aria-label="' . esc_attr__('Table of contents','cr8vstacks-table-of-contents') . '">';

    if ( $layout === 'default' ) {
        $out .= '<div class="wptw-toc__head">';
        $out .= '<div class="wptw-toc__head-left"><span class="wptw-toc__label">' . esc_html( $toc_title ) . '</span>' . $rt_html . '</div>';
        $out .= $toggle . '</div>' . $prog_html;
        $out .= '<div class="wptw-toc__body"><ol class="wptw-toc__list" id="' . esc_attr( $list_id ) . '" role="list"' . $list_style . '>';
        foreach ( $items as $item ) {
            $out .= '<li class="' . esc_attr( $item['class'] ) . '" style="--i:' . (int) $item['index'] . '">';
            $out .= '<a class="wptw-toc__link" href="' . esc_url( $item['href'] ) . '">';
            if ( $item['num'] !== '' ) $out .= '<span class="wptw-toc__num" aria-hidden="true">' . esc_html( $item['num'] ) . '</span>';
            $out .= '<span class="wptw-toc__text">' . esc_html( $item['title'] ) . '</span>';
            $out .= '</a></li>';
        }
        $out .= '</ol></div>';
    } elseif ( $layout === 'manuscript' ) {
        $out .= '<div class="toc-manuscript-eyebrow"><div class="wptw-toc__head-left"><span class="wptw-toc__label">' . esc_html( $toc_title ) . '</span></div><span class="toc-ms-actions">' . $rt_html . $toggle . '</span></div>';
        $out .= '<div class="wptw-toc__body"><ol class="wptw-toc__list toc-manuscript-list" id="' . esc_attr( $list_id ) . '" role="list"' . $list_style . '>';
        $roman = [ 'I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII' ];
        foreach ( $section_groups as $pos => $group ) {
            $item = $group['parent'];
            $label = $show_nums ? ( $roman[ $pos ] ?? (string) ( $pos + 1 ) ) : '';
            $out .= '<li class="' . esc_attr( $item['class'] ) . ' toc-ms-item" style="--i:' . (int) $item['index'] . '">';
            $out .= '<span class="toc-ms-node" aria-hidden="true"><span class="toc-ms-node-inner"></span></span>';
            $out .= '<span class="toc-ms-content">';
            if ( $label !== '' ) $out .= '<span class="wptw-toc__num toc-ms-roman" aria-hidden="true">' . esc_html( $label ) . '</span>';
            $out .= '<a class="wptw-toc__link toc-ms-main" href="' . esc_url( $item['href'] ) . '"><span class="wptw-toc__text toc-ms-title">' . esc_html( $item['title'] ) . '</span></a>';
            if ( ! empty( $group['children'] ) ) {
                $out .= '<span class="toc-ms-sub">';
                foreach ( $group['children'] as $child ) {
                    $child_cls = 'wptw-toc__link toc-ms-sub-link toc-child-d' . (int) $child['depth'];
                    $out .= '<a class="' . esc_attr( $child_cls ) . '" href="' . esc_url( $child['href'] ) . '">' . esc_html( $child['title'] ) . '</a>';
                }
                $out .= '</span>';
            }
            $out .= '</span></li>';
        }
        $out .= '</ol></div>' . $ms_prog_html;
    } elseif ( $layout === 'brutalist' ) {
        $out .= '<div class="wptw-toc__head toc-brut-header"><div class="wptw-toc__head-left"><span class="wptw-toc__label toc-brut-title">' . esc_html( $toc_title ) . '</span>' . $rt_html . '</div><div class="wptw-toc__actions toc-brut-actions">' . $toggle . '</div></div>';
        $out .= '<div class="wptw-toc__body"><ol class="wptw-toc__list" id="' . esc_attr( $list_id ) . '" role="list"' . $list_style . '>';
        foreach ( $section_groups as $pos => $group ) {
            $item = $group['parent'];
            $num = $show_nums ? (string) ( $pos + 1 ) : '';
            $out .= '<li class="' . esc_attr( $item['class'] ) . ' toc-brut-item" style="--i:' . (int) $item['index'] . '">';
            $out .= '<span class="toc-brut-row">';
            if ( $num !== '' ) $out .= '<span class="toc-brut-step"><span class="wptw-toc__num toc-brut-num" aria-hidden="true">' . esc_html( trim( $num, '.' ) ) . '</span></span>';
            $out .= '<span class="toc-brut-body"><a class="wptw-toc__link toc-brut-main" href="' . esc_url( $item['href'] ) . '"><span class="wptw-toc__text toc-brut-name">' . esc_html( $item['title'] ) . '</span></a>';
            if ( ! empty( $group['children'] ) ) {
                $out .= '<span class="toc-brut-subs">';
                foreach ( $group['children'] as $child ) {
                    $child_cls = 'wptw-toc__link toc-brut-sub-link toc-child-d' . (int) $child['depth'];
                    $out .= '<a class="' . esc_attr( $child_cls ) . '" href="' . esc_url( $child['href'] ) . '">' . esc_html( $child['title'] ) . '</a>';
                }
                $out .= '</span>';
            }
            $out .= '<span class="toc-brut-pill">' . esc_html__( 'Reading now', 'cr8vstacks-table-of-contents' ) . '</span></span>';
            $out .= '<span class="toc-brut-check" aria-hidden="true"><svg width="8" height="6" viewBox="0 0 8 6" fill="none"><path d="M1 3l2 2 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span>';
            $out .= '</span></li>';
        }
        $out .= '</ol></div>' . $brut_prog_html;
    } else {
        $out .= '<div class="wptw-toc__head toc-ed-header"><div class="wptw-toc__head-left toc-ed-header-left"><span class="toc-ed-icon" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M2 4h12M2 8h8M2 12h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></span><span class="wptw-toc__label toc-ed-label">' . esc_html( $toc_title ) . '</span></div><div class="wptw-toc__actions toc-ed-actions"><span class="toc-ed-badge">' . count( $section_groups ) . ' sections</span>' . $rt_html . $toggle . '</div></div>';
        $out .= '<div class="wptw-toc__body toc-ed-body"><ol class="wptw-toc__list" id="' . esc_attr( $list_id ) . '" role="list"' . $list_style . '>';
        foreach ( $section_groups as $pos => $group ) {
            $item = $group['parent'];
            $num = $show_nums ? (string) ( $pos + 1 ) : '';
            $out .= '<li class="' . esc_attr( $item['class'] ) . ' toc-ed-item" style="--i:' . (int) $item['index'] . '">';
            $out .= '<span class="toc-ed-gutter" aria-hidden="true"><span class="toc-ed-dot">' . esc_html( $num ) . '</span></span>';
            $out .= '<span class="toc-ed-row">';
            $out .= '<a class="wptw-toc__link toc-ed-main" href="' . esc_url( $item['href'] ) . '"><span class="wptw-toc__text toc-ed-title">' . esc_html( $item['title'] ) . '</span></a>';
            if ( $show_rt ) $out .= '<span class="toc-ed-meta"><span class="toc-ed-mins">' . esc_html( max( 1, (int) ceil( $total_mins / max( 1, count( $section_groups ) ) ) ) . ' min' ) . '</span></span>';
            if ( ! empty( $group['children'] ) ) {
                $out .= '<span class="toc-ed-sub">';
                foreach ( $group['children'] as $child ) {
                    $child_cls = 'wptw-toc__link toc-ed-sub-link toc-child-d' . (int) $child['depth'];
                    $out .= '<a class="' . esc_attr( $child_cls ) . '" href="' . esc_url( $child['href'] ) . '">' . esc_html( $child['title'] ) . '</a>';
                }
                $out .= '</span>';
            }
            $out .= '</span></li>';
        }
        $out .= '</ol></div>' . $ed_prog_html;
    }

    $out .= '</div>'; /* /.wptw-toc */
    $out .= '</div>'; /* /.wptw-toc-wrapper */

    static $btt_done = false;
    if ( ! $btt_done && (bool) wptw_get('back_to_top') ) {
        $out     .= '<button type="button" class="wptw-btt" aria-label="' . esc_attr__('Back to contents','cr8vstacks-table-of-contents') . '" hidden>â†‘</button>';
        $btt_done = true;
    }

    return $out;
}

/* â”€â”€â”€ Google Font â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
add_action( 'wp_enqueue_scripts', function () {
    if ( ! is_singular( (array) wptw_get('post_types') ) ) return;
    $url = wptw_google_font_url( wptw_get('font_family') );
    if ( $url ) wp_enqueue_style( 'wptw-font', $url, [], WPTW_VERSION );
} );

/* â”€â”€â”€ Frontend styles â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
add_action( 'wp_enqueue_scripts', 'wptw_frontend_styles', 20 );

function wptw_frontend_styles() {
    if ( ! is_singular( (array) wptw_get('post_types') ) ) return;

    wp_register_style( 'wptw-styles', false, [], WPTW_VERSION );
    wp_enqueue_style( 'wptw-styles' );
    wp_add_inline_style( 'wptw-styles', wptw_render_toc_styles() );
}

function wptw_render_toc_styles( ?array $settings = null ): string {
    $o    = $settings ?: wptw_get();
    $font = wptw_font_stack( $o['font_family'] );
    $ls   = round( $o['letter_spacing_label'] / 100, 4 );
    $tog_border = $o['color_toggle_border'] ?? $o['color_toggle_bg'];

    $alignment = $o['alignment'] ?? 'center';
    if ( $alignment === 'left' ) {
        $margin_rule = '2.75rem auto 2.25rem 0';
    } elseif ( $alignment === 'right' ) {
        $margin_rule = '2.75rem 0 2.25rem auto';
    } else {
        $margin_rule = '2.75rem auto 2.25rem';
    }
    ob_start();
    ?>
    .wptw-toc {
        --wptw-bg:          <?php echo esc_html( $o['color_bg'] ); ?>;
        --wptw-border:      <?php echo esc_html( $o['color_border'] ); ?>;
        --wptw-radius:      <?php echo (int) $o['border_radius']; ?>px;
        --wptw-head-bg:     <?php echo esc_html( $o['color_header_bg'] ); ?>;
        --wptw-label-c:     <?php echo esc_html( $o['color_label'] ); ?>;
        --wptw-label-sz:    <?php echo (int) $o['font_size_label']; ?>px;
        --wptw-label-ls:    <?php echo esc_html( $ls ); ?>em;
        --wptw-label-tt:    <?php echo esc_html( $o['text_transform_label'] ); ?>;
        --wptw-rt-c:        <?php echo esc_html( $o['color_rt'] ); ?>;
        --wptw-rt-sz:       <?php echo (int) $o['font_size_rt']; ?>px;
        --wptw-rtbar-fill:  <?php echo esc_html( $o['color_rt_bar'] ); ?>;
        --wptw-rtbar-bg:    <?php echo esc_html( $o['color_rt_bar_bg'] ); ?>;
        --wptw-tog-bg:      <?php echo esc_html( $o['color_toggle_bg'] ); ?>;
        --wptw-tog-fg:      <?php echo esc_html( $o['color_toggle_fg'] ); ?>;
        --wptw-tog-bdr:     <?php echo esc_html( $tog_border ); ?>;
        --wptw-link:        <?php echo esc_html( $o['color_link'] ); ?>;
        --wptw-link-hov:    <?php echo esc_html( $o['color_link_hover'] ); ?>;
        --wptw-bar:         <?php echo esc_html( $o['color_active_bar'] ); ?>;
        --wptw-act-bg:      <?php echo esc_html( $o['color_active_bg'] ); ?>;
        --wptw-num-c:       <?php echo esc_html( $o['color_number'] ); ?>;
        --wptw-num-sz:      <?php echo (int) $o['font_size_num']; ?>px;
        --wptw-flink:       <?php echo (int) $o['font_size_link']; ?>px;
        --wptw-fsub:        <?php echo (int) $o['font_size_sub']; ?>px;
        --wptw-btt-bg:      <?php echo esc_html( $o['color_back_top_bg'] ); ?>;
        --wptw-btt-fg:      <?php echo esc_html( $o['color_back_top_fg'] ); ?>;
        --wptw-font:        <?php echo esc_html( $font ); ?>;
        --wptw-mono:        'DM Mono','Fira Mono','Courier New',monospace;
        --wptw-ease:        200ms cubic-bezier(0.4,0,0.2,1);
        --wptw-sticky-top:  <?php echo (int) $o['sticky_top_offset']; ?>px;
        --wptw-sticky-z:    <?php echo (int) ( $o['sticky_z_index'] ?? 9999 ); ?>;
    }

    /* â”€â”€ Card â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .wptw-toc-wrapper {
        clear:         both;
        display:       flow-root;
    }
    .wptw-toc {
        font-family:   var(--wptw-font);
        background:    var(--wptw-bg);
        border:        1px solid var(--wptw-border);
        border-color:  color-mix(in srgb, var(--wptw-border) 72%, var(--wptw-link) 28%);
        border-radius: var(--wptw-radius);
        margin:        <?php echo esc_html( $margin_rule ); ?>;
        position:      relative;
        max-width:     820px;
    }
    .wptw-toc-wrapper,
    .wptw-toc,
    .wptw-sticky-bar {
        box-sizing: border-box !important;
    }
    .wptw-toc-wrapper *,
    .wptw-toc *,
    .wptw-sticky-bar * {
        box-sizing: border-box !important;
    }
    .wptw-toc-wrapper .wptw-toc ol,
    .wptw-toc-wrapper .wptw-toc ul,
    .wptw-toc-wrapper .wptw-toc li,
    .wptw-toc-wrapper .wptw-toc p,
    .wptw-toc ol,
    .wptw-toc ul,
    .wptw-toc li,
    .wptw-toc p,
    .wptw-sticky-bar ol,
    .wptw-sticky-bar ul,
    .wptw-sticky-bar li,
    .wptw-sticky-bar p {
        margin: 0 !important;
        margin-left: 0 !important;
        padding-block: 0;
    }
    .wptw-toc-wrapper .wptw-toc ol,
    .wptw-toc-wrapper .wptw-toc ul,
    .wptw-toc ol,
    .wptw-toc ul,
    .wptw-sticky-bar ol,
    .wptw-sticky-bar ul {
        list-style: none !important;
        padding-left: 0 !important;
        margin-left: 0 !important;
    }
    .wptw-toc-wrapper .wptw-toc li,
    .wptw-toc li,
    .wptw-sticky-bar li {
        list-style: none !important;
        margin: 0 !important;
        margin-bottom: 0 !important;
        padding: 0;
        font-size: inherit;
        line-height: normal;
    }
    .wptw-toc-wrapper .wptw-toc a,
    .wptw-toc-wrapper .wptw-toc a:visited,
    .wptw-toc-wrapper .wptw-toc a:hover,
    .wptw-toc-wrapper .wptw-toc a:focus,
    .wptw-toc a,
    .wptw-toc a:visited,
    .wptw-toc a:hover,
    .wptw-toc a:focus,
    .wptw-sticky-bar a,
    .wptw-sticky-bar a:visited,
    .wptw-sticky-bar a:hover,
    .wptw-sticky-bar a:focus {
        border-bottom: none !important;
        box-shadow: none !important;
        text-decoration: none !important;
    }
    .wptw-toc-wrapper .wptw-toc .toc-ed-title,
    .wptw-toc-wrapper .wptw-toc .toc-ed-main,
    .wptw-toc-wrapper .wptw-toc .toc-ed-sub-link,
    .wptw-toc-wrapper .wptw-toc .toc-ms-title,
    .wptw-toc-wrapper .wptw-toc .toc-ms-main,
    .wptw-toc-wrapper .wptw-toc .toc-ms-sub-link,
    .wptw-toc-wrapper .wptw-toc .toc-brut-name,
    .wptw-toc-wrapper .wptw-toc .toc-brut-main,
    .wptw-toc-wrapper .wptw-toc .wptw-toc__link {
        border-bottom: 0 !important;
        text-decoration: none !important;
    }
    .wptw-toc a:focus-visible,
    .wptw-toc button:focus-visible,
    .wptw-sticky-bar button:focus-visible {
        outline: 2px solid var(--wptw-bar, currentColor) !important;
        outline-offset: 1px !important;
    }
    .wptw-toc button,
    .wptw-sticky-bar button {
        appearance: none;
        box-shadow: none !important;
        min-height: 0;
    }

    /* â”€â”€ Header â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .wptw-toc__head {
        display:         flex !important;
        align-items:     center;
        justify-content: space-between;
        padding:         11px 18px;
        background:      var(--wptw-head-bg);
        border-bottom:   1px solid var(--wptw-border);
        border-radius:   var(--wptw-radius) var(--wptw-radius) 0 0;
        position:        relative;
        z-index:         2;
    }
    .wptw-toc__head-left {
        display:     flex;
        align-items: center;
        gap:         12px;
        flex:        1;
        min-width:   0;
    }

    /* â”€â”€ .wptw-toc__label â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .wptw-toc__label {
        font-family:    var(--wptw-mono);
        font-size:      var(--wptw-label-sz);
        font-weight:    500;
        letter-spacing: var(--wptw-label-ls);
        text-transform: var(--wptw-label-tt);
        color:          var(--wptw-label-c);
        white-space:    nowrap;
    }

    /* â”€â”€ .wptw-toc__rt (reading time + countdown) â”€â”€â”€ */
    .wptw-toc__rt {
        font-family: var(--wptw-mono);
        font-size:   var(--wptw-rt-sz);
        color:       var(--wptw-rt-c);
        white-space: nowrap;
        padding-left: 10px;
        border-left:  1px solid var(--wptw-border);
        transition:   color 0.3s ease;
    }

    /* â”€â”€ Progress bar â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .wptw-toc__prog {
        height:     3px;
        background: var(--wptw-rtbar-bg);
        position:   relative;
        overflow:   hidden;
    }
    .wptw-toc__prog-fill {
        position:   absolute;
        top:        0; left: 0;
        height:     100%;
        width:      0%;
        background: var(--wptw-rtbar-fill);
        transition: width 120ms linear;
    }

    /* â”€â”€ .wptw-toc__toggle â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .wptw-toc__toggle {
        display:        flex;
        align-items:    center;
        gap:            5px;
        background:     var(--wptw-tog-bg) !important; /* override theme */
        color:          var(--wptw-tog-fg) !important;
        border:         1px solid var(--wptw-tog-bdr) !important; /* explicit: prevents theme border */
        border-radius:  calc(var(--wptw-radius) / 2 + 1px);
        padding:        5px 11px;
        cursor:         pointer;
        font-family:    var(--wptw-mono);
        font-size:      9px;
        font-weight:    500;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        line-height:    1;
        flex-shrink:    0;
        transition:     opacity var(--wptw-ease);
        outline:        none;
    }
    .wptw-toc__toggle:hover { opacity: .72; }
    .wptw-toc__tog-icon { transition: transform var(--wptw-ease); flex-shrink:0; }
    .wptw-toc__toggle[aria-expanded="false"] .wptw-toc__tog-icon { transform: rotate(-90deg); }

    /* â”€â”€ Body â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .wptw-toc__body {
        overflow:      hidden;
        border-radius: 0 0 var(--wptw-radius) var(--wptw-radius);
    }

    /* â”€â”€ List â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .wptw-toc__list {
        list-style: none;
        margin:     0;
        padding:    10px 0;
        transition: height var(--wptw-ease), opacity var(--wptw-ease), padding var(--wptw-ease);
    }

    /* â”€â”€ Item animations â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .wptw-toc__item {
        opacity:         0;
        transform:       translateY(5px);
        animation:       wptw-in 260ms ease forwards;
        animation-delay: calc(var(--i,0) * 22ms + 35ms);
    }
    @keyframes wptw-in { to { opacity:1; transform:none; } }

    .wptw-toc__item--d3 .wptw-toc__link { padding-left: 36px; }
    .wptw-toc__item--d4 .wptw-toc__link { padding-left: 54px; }
    .wptw-toc__item--d5 .wptw-toc__link { padding-left: 72px; }
    .wptw-toc__item--d6 .wptw-toc__link { padding-left: 90px; }

    /* â”€â”€ .wptw-toc__link â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .wptw-toc__link {
        display:         flex;
        align-items:     baseline;
        gap:             8px;
        padding:         7px 18px;
        text-decoration: none;
        color:           var(--wptw-link);
        font-size:       var(--wptw-flink);
        line-height:     1.45;
        border-left:     2px solid transparent;
        transition:      color var(--wptw-ease), border-color var(--wptw-ease), background var(--wptw-ease);
    }
    .wptw-toc__link:hover {
        color:              var(--wptw-link-hov);
        background:         color-mix(in srgb, var(--wptw-head-bg) 65%, transparent);
        border-left-color:  var(--wptw-border);
        text-decoration:    none;
    }
    .wptw-toc__item.is-active > .wptw-toc__link {
        color:              var(--wptw-link-hov);
        border-left-color:  var(--wptw-bar);
        background:         var(--wptw-act-bg);
        font-weight:        500;
    }
    .wptw-toc__item.is-active .wptw-toc__num { color: var(--wptw-bar); }

    /* â”€â”€ .wptw-toc__num â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .wptw-toc__num {
        font-family: var(--wptw-mono);
        font-size:   var(--wptw-num-sz);
        color:       var(--wptw-num-c);
        flex-shrink: 0;
        min-width:   20px;
        transition:  color var(--wptw-ease);
    }
    .wptw-toc__text { flex:1; }

    .wptw-toc__item--sub > .wptw-toc__link {
        font-size: var(--wptw-fsub);
        color:     color-mix(in srgb, var(--wptw-link) 72%, transparent);
    }
    .wptw-toc__item--sub > .wptw-toc__link:hover { color: var(--wptw-link-hov); }

    .wptw-toc--layout-default {
        box-shadow: 0 1px 2px rgba(0,0,0,.04), 0 12px 34px rgba(15,23,42,.08);
        overflow: hidden;
    }
    .wptw-toc--layout-default .wptw-toc__head {
        padding: 14px 18px;
    }
    .wptw-toc--layout-default .wptw-toc__toggle {
        border-radius: max(4px, calc(var(--wptw-radius) / 2));
        font-weight: 500;
    }
    .wptw-toc--layout-default .wptw-toc__label {
        color: var(--wptw-label-c);
        font-weight: 700;
    }
    .wptw-toc--layout-default .wptw-toc__list {
        padding: 12px 0;
    }
    .wptw-toc--layout-default .wptw-toc__link {
        margin: 2px 10px;
        border-left: 0;
        border: 1px solid transparent;
        border-radius: max(3px, calc(var(--wptw-radius) / 2));
        padding: 8px 12px;
    }
    .wptw-toc--layout-default .wptw-toc__item--d3 .wptw-toc__link { margin-left: 22px; padding-left: 12px; }
    .wptw-toc--layout-default .wptw-toc__item--d4 .wptw-toc__link { margin-left: 34px; padding-left: 12px; }
    .wptw-toc--layout-default .wptw-toc__item--d5 .wptw-toc__link { margin-left: 46px; padding-left: 12px; font-size: calc(var(--wptw-fsub) - 1px); }
    .wptw-toc--layout-default .wptw-toc__item--d6 .wptw-toc__link { margin-left: 58px; padding-left: 12px; font-size: calc(var(--wptw-fsub) - 1px); opacity: .86; }
    .wptw-toc--layout-default .wptw-toc__item.is-active > .wptw-toc__link {
        background: var(--wptw-act-bg);
        border-color: color-mix(in srgb, var(--wptw-bar) 28%, var(--wptw-border));
        color: var(--wptw-link-hov);
    }

    /* Layout variants */
    .wptw-toc--layout-editorial {
        border: 1px solid color-mix(in srgb, var(--wptw-border) 72%, var(--wptw-link) 28%);
        border-radius: max(8px, var(--wptw-radius));
        box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 4px 24px rgba(0,0,0,.06);
        overflow: hidden;
    }
    .wptw-toc--layout-editorial .wptw-toc__head {
        padding: 20px 24px 18px;
        background: var(--wptw-head-bg);
        border-bottom: 1px solid var(--wptw-border);
    }
    .wptw-toc--layout-editorial .wptw-toc__head-left::before {
        content: '';
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: var(--wptw-act-bg);
        box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--wptw-bar) 18%, transparent);
        flex-shrink: 0;
    }
    .wptw-toc--layout-editorial .wptw-toc__rt {
        border-left: 0;
        padding-left: 0;
        background: color-mix(in srgb, var(--wptw-head-bg) 82%, var(--wptw-bg));
        border: 1px solid var(--wptw-border);
        border-radius: 20px;
        padding: 3px 9px;
    }
    .wptw-toc--layout-editorial .wptw-toc__toggle {
        border-radius: 999px;
        font-weight: 500;
        padding: 5px 9px;
    }
    .wptw-toc--layout-editorial .wptw-toc__list { padding: 12px 0; }
    .wptw-toc--layout-editorial .wptw-toc__item {
        position: relative;
        display: flex;
    }
    .wptw-toc--layout-editorial .wptw-toc__item::before {
        content: '';
        width: 48px;
        flex-shrink: 0;
        background:
            radial-gradient(circle at 50% 20px, var(--wptw-bg) 0 8px, transparent 9px),
            linear-gradient(var(--wptw-border), var(--wptw-border)) 50% 31px / 1px calc(100% - 31px) no-repeat;
    }
    .wptw-toc--layout-editorial .wptw-toc__item::after {
        content: '';
        position: absolute;
        left: 14px;
        top: 12px;
        width: 20px;
        height: 20px;
        border: 1.5px solid var(--wptw-border);
        border-radius: 50%;
        background: var(--wptw-bg);
    }
    .wptw-toc--layout-editorial .wptw-toc__item.is-active::after {
        background: var(--wptw-bar);
        border-color: var(--wptw-bar);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--wptw-bar) 16%, transparent);
    }
    .wptw-toc--layout-editorial .wptw-toc__item.is-done::after {
        background: var(--wptw-act-bg);
        border-color: var(--wptw-bar);
    }
    .wptw-toc--layout-editorial .wptw-toc__link {
        flex: 1;
        border-left: 0;
        padding: 10px 24px 10px 4px;
    }
    .wptw-toc--layout-editorial .wptw-toc__link:hover {
        background: color-mix(in srgb, var(--wptw-head-bg) 55%, transparent);
    }
    .wptw-toc--layout-editorial .wptw-toc__item.is-active > .wptw-toc__link {
        background: transparent;
        color: var(--wptw-link-hov);
    }
    .wptw-toc--layout-editorial .wptw-toc__prog {
        margin: 12px 24px 14px;
        border-radius: 3px;
    }

    .wptw-toc--layout-brutalist {
        background: var(--wptw-bg);
        border: 2px solid color-mix(in srgb, var(--wptw-border) 72%, var(--wptw-link) 28%);
        border-radius: 0;
        box-shadow: 5px 5px 0 color-mix(in srgb, var(--wptw-border) 72%, var(--wptw-link) 28%);
    }
    .wptw-toc--layout-brutalist .wptw-toc__head {
        background: var(--wptw-head-bg);
        border-bottom: 2px solid color-mix(in srgb, var(--wptw-border) 72%, var(--wptw-link) 28%);
        border-radius: 0;
        padding: 12px 16px;
    }
    .wptw-toc--layout-brutalist .wptw-toc__label {
        color: var(--wptw-link-hov);
        font-family: var(--wptw-font);
        font-size: calc(var(--wptw-label-sz) + 3px);
        letter-spacing: .02em;
        text-transform: none;
    }
    .wptw-toc--layout-brutalist .wptw-toc__rt {
        color: var(--wptw-rt-c);
        border-left-color: color-mix(in srgb, var(--wptw-rt-c) 28%, transparent);
    }
    .wptw-toc--layout-brutalist .wptw-toc__toggle {
        border-radius: 0;
        box-shadow: 3px 3px 0 color-mix(in srgb, var(--wptw-border) 75%, var(--wptw-link) 25%);
        font-family: var(--wptw-font);
        font-size: 9.5px;
        font-weight: 600;
        letter-spacing: .01em;
        padding: 6px 9px;
        text-transform: uppercase;
    }
    .wptw-toc--layout-brutalist .wptw-toc__list { padding: 0; }
    .wptw-toc--layout-brutalist .wptw-toc__item {
        border-bottom: 1px solid var(--wptw-border);
    }
    .wptw-toc--layout-brutalist .wptw-toc__item:last-child {
        border-bottom: 0;
    }
    .wptw-toc--layout-brutalist .wptw-toc__link {
        align-items: stretch;
        border-left: 0;
        gap: 0;
        padding: 0;
    }
    .wptw-toc--layout-brutalist .wptw-toc__num {
        align-items: center;
        border-right: 1px solid var(--wptw-border);
        color: var(--wptw-num-c);
        display: flex;
        font-family: var(--wptw-font);
        font-size: calc(var(--wptw-num-sz) + 10px);
        justify-content: center;
        min-width: 48px;
        padding: 14px 6px;
    }
    .wptw-toc--layout-brutalist .wptw-toc__text {
        padding: 13px 14px;
    }
    .wptw-toc--layout-brutalist .wptw-toc__item.is-active > .wptw-toc__link {
        background: var(--wptw-act-bg);
        color: var(--wptw-link-hov);
    }
    .wptw-toc--layout-brutalist .wptw-toc__item.is-active .wptw-toc__num {
        color: var(--wptw-bar);
    }
    .wptw-toc--layout-brutalist .wptw-toc__item.is-done .wptw-toc__text {
        color: var(--wptw-num-c);
        text-decoration: line-through;
        text-decoration-color: var(--wptw-border);
    }

    .wptw-toc--layout-editorial .toc-ed-header {
        align-items: center;
        gap: 10px;
    }
    .wptw-toc__actions {
        align-items: center;
        display: flex;
        flex-shrink: 0;
        gap: 8px;
        justify-content: flex-end;
    }
    .wptw-toc--layout-brutalist .toc-brut-actions {
        align-self: flex-start;
    }
    .wptw-toc--layout-editorial .toc-ed-actions {
        margin-left: auto;
    }
    .wptw-toc--layout-editorial { width: 100%; }
    .wptw-toc--layout-editorial .toc-ed-header-left::before {
        display: none;
    }
    .wptw-toc--layout-editorial .toc-ed-icon {
        align-items: center;
        background: var(--wptw-act-bg);
        border-radius: 8px;
        color: var(--wptw-bar);
        display: inline-flex;
        flex: 0 0 32px;
        height: 32px;
        justify-content: center;
        width: 32px;
    }
    .wptw-toc--layout-editorial .toc-ed-badge {
        background: var(--wptw-head-bg);
        border: 1px solid var(--wptw-border);
        border-radius: 20px;
        color: var(--wptw-rt-c);
        font-family: var(--wptw-mono);
        font-size: var(--wptw-rt-sz);
        padding: 3px 9px;
        white-space: nowrap;
    }
    .wptw-toc--layout-editorial .wptw-toc__toggle {
        padding: 5px 8px;
    }
    .wptw-toc--layout-editorial .toc-ed-item::before {
        display: none;
    }
    .wptw-toc--layout-editorial .toc-ed-gutter {
        align-items: flex-start;
        display: flex;
        flex: 0 0 48px;
        justify-content: center;
        padding-top: 12px;
        position: relative;
    }
    .wptw-toc--layout-editorial .toc-ed-gutter::after {
        background: var(--wptw-border);
        bottom: 0;
        content: '';
        left: 50%;
        position: absolute;
        top: 32px;
        transform: translateX(-50%);
        width: 1px;
    }
    .wptw-toc--layout-editorial .toc-ed-item:last-child .toc-ed-gutter::after {
        display: none;
    }
    .wptw-toc--layout-editorial .toc-ed-dot {
        align-items: center;
        background: var(--wptw-bg);
        border: 1.5px solid color-mix(in srgb, var(--wptw-border) 40%, var(--wptw-link) 60%);
        border-radius: 50%;
        color: var(--wptw-rt-c);
        display: flex;
        font-family: var(--wptw-mono);
        font-size: var(--wptw-rt-sz);
        height: 20px;
        justify-content: center;
        position: relative;
        width: 20px;
        z-index: 1;
    }
    .wptw-toc--layout-editorial .is-active .toc-ed-dot {
        background: var(--wptw-bar);
        border-color: var(--wptw-bar);
        color: var(--wptw-bg);
    }
    .wptw-toc--layout-editorial .toc-ed-row {
        display: block;
        padding: 10px 24px 10px 4px;
    }
    .wptw-toc--layout-editorial .toc-ed-meta {
        display: flex;
        margin-top: 3px;
    }
    .wptw-toc--layout-editorial .toc-ed-mins {
        color: var(--wptw-rt-c);
        font-family: var(--wptw-mono);
        font-size: var(--wptw-rt-sz);
    }
    .wptw-toc--layout-editorial .wptw-toc__prog {
        margin: 12px 24px 14px;
    }

    .wptw-toc--layout-brutalist {
        border: 2px solid color-mix(in srgb, var(--wptw-border) 72%, var(--wptw-link) 28%);
        box-shadow: none;
        overflow: visible;
        position: relative;
        width: 100%;
    }
    .wptw-toc--layout-brutalist::after {
        background: transparent;
        border: 2px solid color-mix(in srgb, var(--wptw-border) 72%, var(--wptw-link) 28%);
        content: '';
        inset: 7px -8px -8px 7px;
        pointer-events: none;
        position: absolute;
        z-index: 0;
    }
    .wptw-toc--layout-brutalist > * {
        position: relative;
        z-index: 1;
    }
    .wptw-toc--layout-brutalist .toc-brut-header {
        background: var(--wptw-head-bg);
        border-bottom: 2px solid color-mix(in srgb, var(--wptw-border) 72%, var(--wptw-link) 28%);
        justify-content: space-between;
    }
    .wptw-toc--layout-brutalist .toc-brut-title {
        color: var(--wptw-link-hov);
    }
    .wptw-toc--layout-brutalist .wptw-toc__toggle {
        background: var(--wptw-tog-bg) !important;
        border-color: var(--wptw-tog-bdr) !important;
        color: var(--wptw-tog-fg) !important;
    }
    .wptw-toc--layout-editorial .wptw-toc__toggle {
        background: var(--wptw-tog-bg) !important;
        border-color: var(--wptw-tog-bdr) !important;
        color: var(--wptw-tog-fg) !important;
    }
    .wptw-toc--layout-brutalist .toc-brut-item {
        border-bottom: 1px solid var(--wptw-border);
    }
    .wptw-toc--layout-brutalist .toc-brut-item:last-child {
        border-bottom: 0;
    }
    .wptw-toc--layout-brutalist .wptw-toc__link {
        align-items: stretch;
        gap: 0;
    }
    .wptw-toc--layout-brutalist .toc-brut-step {
        align-items: flex-start;
        border-right: 1px solid var(--wptw-border);
        display: flex;
        flex: 0 0 44px;
        justify-content: center;
        padding-top: 14px;
    }
    .wptw-toc--layout-brutalist .toc-brut-num {
        color: var(--wptw-num-c);
        font-family: var(--wptw-font);
        font-size: calc(var(--wptw-num-sz) + 10px);
        line-height: 1;
    }
    .wptw-toc--layout-brutalist .toc-brut-body {
        flex: 1;
        padding: 12px 14px;
    }
    .wptw-toc--layout-brutalist .toc-brut-check {
        align-items: center;
        border: 1.5px solid color-mix(in srgb, var(--wptw-border) 40%, var(--wptw-link) 60%);
        border-radius: 2px;
        display: flex;
        flex: 0 0 14px;
        height: 14px;
        justify-content: center;
        margin: auto 12px auto 0;
        width: 14px;
    }
    .wptw-toc--layout-brutalist .toc-brut-check svg {
        display: none;
    }
    .wptw-toc--layout-brutalist .is-done .toc-brut-check {
        background: var(--wptw-act-bg);
        border-color: var(--wptw-bar);
        color: var(--wptw-bar);
    }
    .wptw-toc--layout-brutalist .is-done .toc-brut-check svg {
        display: block;
    }

    .wptw-toc--layout-editorial .wptw-toc__item::before,
    .wptw-toc--layout-editorial .wptw-toc__item::after {
        display: none !important;
    }
    .wptw-toc--layout-brutalist .toc-brut-row {
        display: flex;
        width: 100%;
    }
    .wptw-toc--layout-editorial .toc-ed-main,
    .wptw-toc--layout-editorial .toc-ed-sub-link,
    .wptw-toc--layout-brutalist .toc-brut-main,
    .wptw-toc--layout-brutalist .toc-brut-sub-link {
        border-left: 0 !important;
        box-shadow: none !important;
        display: block;
        padding: 0;
        text-decoration: none !important;
    }
    .wptw-toc--layout-brutalist .toc-brut-main,
    .wptw-toc--layout-editorial .toc-ed-main {
        color: inherit !important;
    }
    .wptw-toc--layout-editorial .toc-ed-row {
        display: block;
        flex: 1;
        padding: 10px 24px 10px 4px;
        transition: background 150ms ease;
    }
    .wptw-toc--layout-editorial .toc-ed-item:hover .toc-ed-row {
        background: color-mix(in srgb, var(--wptw-head-bg) 55%, transparent);
    }
    .wptw-toc--layout-editorial .toc-ed-item.is-done .toc-ed-dot {
        background: var(--wptw-act-bg);
        border-color: var(--wptw-bar);
        color: var(--wptw-bar);
    }
    .wptw-toc--layout-editorial .toc-ed-item.is-done .toc-ed-title {
        color: color-mix(in srgb, var(--wptw-rt-c) 48%, transparent);
        text-decoration: line-through;
        text-decoration-color: color-mix(in srgb, var(--wptw-rt-c) 38%, transparent);
    }
    .wptw-toc--layout-editorial .toc-ed-title {
        color: var(--wptw-link);
        display: block;
        font-size: var(--wptw-flink);
        line-height: 1.35;
    }
    .wptw-toc--layout-editorial .is-active .toc-ed-title {
        color: var(--wptw-link-hov);
        font-weight: 600;
    }
    .wptw-toc--layout-editorial .toc-ed-sub {
        display: flex;
        flex-direction: column;
        gap: 2px;
        margin-top: 6px;
    }
    .wptw-toc--layout-editorial .toc-ed-sub-link {
        border-left: 2px solid var(--wptw-border) !important;
        color: color-mix(in srgb, var(--wptw-rt-c) 72%, transparent) !important;
        font-size: var(--wptw-fsub);
        line-height: 1.35;
        padding: 2px 0 2px 10px;
    }
    .wptw-toc--layout-editorial .toc-ed-sub-link.toc-child-d3 {
        margin-left: 0;
    }
    .wptw-toc--layout-editorial .toc-ed-sub-link.toc-child-d4 {
        border-left-style: dashed !important;
        margin-left: 14px;
        padding-left: 10px;
    }
    .wptw-toc--layout-editorial .toc-ed-sub-link.toc-child-d5 {
        border-left-width: 1px !important;
        margin-left: 28px;
        opacity: .9;
        padding-left: 9px;
    }
    .wptw-toc--layout-editorial .toc-ed-sub-link.toc-child-d6 {
        border-left-width: 1px !important;
        margin-left: 42px;
        opacity: .78;
        padding-left: 8px;
    }
    .wptw-toc--layout-editorial .is-active .toc-ed-sub-link {
        border-left-color: var(--wptw-act-bg) !important;
        color: var(--wptw-rt-c) !important;
    }
    .wptw-toc--layout-editorial .toc-ed-footer {
        align-items: center;
        border-top: 1px solid var(--wptw-border);
        display: flex;
        gap: 10px;
        padding: 14px 24px;
        margin-top: 12px;
    }
    .wptw-toc--layout-editorial .toc-ed-progress {
        background: var(--wptw-rtbar-bg);
        border-radius: 3px;
        flex: 1;
        height: 3px;
        margin: 0;
    }
    .wptw-toc--layout-editorial .toc-ed-progress-fill {
        background: var(--wptw-rtbar-fill);
        border-radius: inherit;
    }
    .wptw-toc--layout-editorial .toc-ed-progress-label {
        color: var(--wptw-rt-c);
        font-family: var(--wptw-mono);
        font-size: var(--wptw-rt-sz);
        white-space: nowrap;
    }
    .wptw-toc--layout-brutalist .wptw-toc__link {
        color: inherit !important;
    }
    .wptw-toc--layout-brutalist .toc-brut-row {
        align-items: stretch;
    }
    .wptw-toc--layout-brutalist .toc-brut-name {
        color: var(--wptw-link);
        display: block;
        font-size: var(--wptw-flink);
        font-weight: 600;
        letter-spacing: 0;
        line-height: 1.3;
    }
    .wptw-toc--layout-brutalist .is-active .toc-brut-name {
        color: var(--wptw-link-hov);
    }
    .wptw-toc--layout-brutalist .toc-brut-item:not(.is-active) .wptw-toc__link:hover .toc-brut-name {
        color: var(--wptw-link);
    }
    .wptw-toc--layout-brutalist .is-done .toc-brut-name {
        color: color-mix(in srgb, var(--wptw-rt-c) 58%, transparent);
        text-decoration: line-through;
    }
    .wptw-toc--layout-brutalist .is-active {
        background: var(--wptw-act-bg);
    }
    .wptw-toc--layout-brutalist .is-active .toc-brut-step {
        border-right-color: color-mix(in srgb, var(--wptw-bar) 30%, var(--wptw-border));
    }
    .wptw-toc--layout-brutalist .is-active .toc-brut-num {
        color: var(--wptw-link-hov);
    }
    .wptw-toc--layout-brutalist .is-done .toc-brut-num {
        color: color-mix(in srgb, var(--wptw-rt-c) 44%, transparent);
    }
    .wptw-toc--layout-brutalist .toc-brut-subs {
        display: flex;
        flex-direction: column;
        gap: 2px;
        margin-top: 5px;
    }
    .wptw-toc--layout-brutalist .toc-brut-sub-link {
        color: color-mix(in srgb, var(--wptw-rt-c) 70%, transparent) !important;
        font-family: var(--wptw-mono);
        font-size: var(--wptw-fsub);
        letter-spacing: .02em;
        line-height: 1.35;
    }
    .wptw-toc--layout-brutalist .toc-brut-sub-link::before {
        content: '> ';
    }
    .wptw-toc--layout-brutalist .toc-brut-sub-link.toc-child-d4 {
        margin-left: 12px;
    }
    .wptw-toc--layout-brutalist .toc-brut-sub-link.toc-child-d4::before {
        content: '>> ';
    }
    .wptw-toc--layout-brutalist .toc-brut-sub-link.toc-child-d5 {
        margin-left: 24px;
        opacity: .9;
    }
    .wptw-toc--layout-brutalist .toc-brut-sub-link.toc-child-d5::before {
        content: '>>> ';
    }
    .wptw-toc--layout-brutalist .toc-brut-sub-link.toc-child-d6 {
        margin-left: 36px;
        opacity: .78;
    }
    .wptw-toc--layout-brutalist .toc-brut-sub-link.toc-child-d6::before {
        content: '>>>> ';
    }
    .wptw-toc--layout-brutalist .is-active .toc-brut-sub-link {
        color: color-mix(in srgb, var(--wptw-link-hov) 72%, var(--wptw-act-bg)) !important;
    }
    .wptw-toc--layout-brutalist .toc-brut-pill {
        align-items: center;
        background: var(--wptw-bar);
        color: var(--wptw-bg);
        display: none;
        font-family: var(--wptw-mono);
        font-size: 9px;
        font-weight: 600;
        gap: 4px;
        margin-top: 6px;
        padding: 2px 7px;
        text-transform: uppercase;
        width: max-content;
    }
    .wptw-toc--layout-brutalist .is-active .toc-brut-pill {
        display: inline-flex;
    }
    .wptw-toc--layout-brutalist .toc-brut-progress {
        background: var(--wptw-rtbar-bg);
        height: 4px;
        margin: 0;
    }
    .wptw-toc--layout-brutalist .toc-brut-progress-fill {
        background: var(--wptw-rtbar-fill);
    }

    /* â•â•â• STICKY BAR â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     * Fixed-position clone of the TOC header.
     * Matches the TOC card width and position â€” not full viewport.
     * JS sets left/width to mirror the card's getBoundingClientRect().
     * â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
    /* Final Manuscript rules: keep frontend and admin preview on one source of truth. */
    .wptw-toc--layout-manuscript {
        --wptw-ms-accent: var(--wptw-bar, #d97706);
        --wptw-ms-accent-2: var(--wptw-rtbar-fill, #f59e0b);
        --wptw-ms-pad-x: 28px;
        --wptw-ms-node: 28px;
        --wptw-ms-line-x: calc(var(--wptw-ms-pad-x) + (var(--wptw-ms-node) / 2));
        --wptw-ms-ink: var(--wptw-bg);
        --wptw-ms-head: var(--wptw-head-bg);
        --wptw-ms-text: var(--wptw-link-hov);
        --wptw-ms-muted: color-mix(in srgb, var(--wptw-link) 72%, transparent);
        --wptw-ms-hover: color-mix(in srgb, var(--wptw-link-hov) 88%, transparent);
        --wptw-ms-faint: color-mix(in srgb, var(--wptw-link) 38%, transparent);
        --wptw-ms-read: color-mix(in srgb, var(--wptw-link) 42%, transparent);
        --wptw-ms-line: color-mix(in srgb, var(--wptw-link) 14%, transparent);
        background: var(--wptw-ms-ink);
        border: 0;
        border-radius: max(2px, var(--wptw-radius));
        box-shadow: none;
        color: var(--wptw-ms-text);
        overflow: hidden;
        padding: 0;
        width: 100%;
    }
    .wptw-toc--layout-manuscript .toc-manuscript-eyebrow {
        align-items: center;
        background:
            linear-gradient(180deg, color-mix(in srgb, var(--wptw-ms-text) 5%, transparent), transparent 72%),
            var(--wptw-ms-head);
        border-bottom: 1px solid color-mix(in srgb, var(--wptw-ms-text) 8%, transparent);
        color: var(--wptw-ms-accent);
        display: flex !important;
        font-family: var(--wptw-mono);
        font-size: var(--wptw-label-sz);
        justify-content: space-between;
        letter-spacing: var(--wptw-label-ls);
        line-height: 1.2;
        margin: 0;
        min-height: 72px;
        padding: 30px var(--wptw-ms-pad-x) 18px;
        position: relative;
        text-transform: var(--wptw-label-tt);
    }
    .wptw-toc--layout-manuscript .toc-manuscript-eyebrow::after {
        background: linear-gradient(90deg, var(--wptw-ms-accent), transparent 74%);
        bottom: -1px;
        content: '';
        height: 1px;
        left: var(--wptw-ms-pad-x);
        position: absolute;
        width: 92px;
    }
    .wptw-toc--layout-manuscript::before {
        content: '';
        display: block;
        height: 3px;
        background: linear-gradient(90deg, var(--wptw-ms-accent) 0%, var(--wptw-ms-accent-2) 100%);
    }
    .wptw-toc--layout-manuscript .wptw-toc__head {
        background: transparent;
        border: 0;
        border-radius: 0;
        display: block;
        min-height: 0;
        padding: 32px 28px 20px;
        position: relative;
    }
    .wptw-toc--layout-manuscript .wptw-toc__head-left {
        display: block;
        min-width: 0;
    }
    .wptw-toc--layout-manuscript .wptw-toc__label {
        color: var(--wptw-ms-accent);
        font-family: var(--wptw-mono);
        font-size: var(--wptw-label-sz);
        letter-spacing: var(--wptw-label-ls);
        line-height: 1.2;
        text-transform: var(--wptw-label-tt);
    }
    .wptw-toc--layout-manuscript .wptw-toc__rt {
        background: color-mix(in srgb, var(--wptw-ms-text) 4%, transparent);
        border: 1px solid color-mix(in srgb, var(--wptw-ms-text) 9%, transparent);
        border-radius: 999px;
        color: color-mix(in srgb, var(--wptw-ms-text) 46%, transparent);
        display: inline-flex;
        font-size: var(--wptw-rt-sz);
        letter-spacing: .08em;
        line-height: 1;
        padding: 5px 8px;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .wptw-toc--layout-manuscript .toc-ms-actions {
        align-items: center;
        display: inline-flex;
        gap: 8px;
        margin-left: 16px;
    }
    .wptw-toc--layout-manuscript .wptw-toc__toggle {
        background: transparent !important;
        border-color: color-mix(in srgb, var(--wptw-ms-accent) 62%, transparent) !important;
        border-radius: 999px;
        color: var(--wptw-ms-accent) !important;
        font-weight: 500;
        padding: 4px 8px;
    }
    .wptw-toc--layout-manuscript .wptw-toc__body {
        background: transparent;
        border: 0;
        overflow: hidden;
        padding: 0;
    }
    .wptw-toc--layout-manuscript .wptw-toc__list {
        list-style: none !important;
        margin: 0;
        padding: 0 var(--wptw-ms-pad-x) 20px !important;
        position: relative;
    }
    .wptw-toc--layout-manuscript .wptw-toc__list::before {
        background: var(--wptw-ms-line);
        bottom: 28px;
        content: '';
        left: var(--wptw-ms-line-x);
        position: absolute;
        top: 8px;
        width: 1px;
        z-index: 0;
    }
    .wptw-toc--layout-manuscript .toc-ms-item {
        align-items: flex-start;
        display: grid !important;
        gap: 16px !important;
        grid-template-columns: var(--wptw-ms-node) minmax(0, 1fr) !important;
        list-style: none !important;
        margin: 0 !important;
        padding: 10px 0 !important;
        position: relative;
        z-index: 1;
    }
    .wptw-toc--layout-manuscript .toc-ms-item::before,
    .wptw-toc--layout-manuscript .toc-ms-item::after,
    .wptw-toc--layout-manuscript .wptw-toc__item::before,
    .wptw-toc--layout-manuscript .wptw-toc__item::after {
        content: none !important;
        display: none !important;
    }
    .wptw-toc--layout-manuscript .toc-ms-row {
        display: contents;
        width: 100%;
    }
    .wptw-toc--layout-manuscript .toc-ms-node {
        align-items: center;
        display: flex;
        flex: none;
        grid-column: 1;
        height: var(--wptw-ms-node) !important;
        justify-content: center;
        position: relative;
        z-index: 1;
        width: var(--wptw-ms-node) !important;
    }
    .wptw-toc--layout-manuscript .toc-ms-node-inner {
        background: color-mix(in srgb, var(--wptw-ms-text) 6%, transparent);
        border: 1.5px solid color-mix(in srgb, var(--wptw-ms-text) 24%, transparent);
        border-radius: 50%;
        box-sizing: border-box;
        display: block;
        height: 10px !important;
        width: 10px !important;
    }
    .wptw-toc--layout-manuscript .is-active .toc-ms-node-inner {
        background: var(--wptw-ms-accent);
        border-color: var(--wptw-ms-accent);
        box-shadow: 0 0 0 4px color-mix(in srgb, var(--wptw-ms-accent) 20%, transparent);
        height: 12px !important;
        width: 12px !important;
    }
    .wptw-toc--layout-manuscript .is-done .toc-ms-node-inner {
        background: color-mix(in srgb, var(--wptw-ms-text) 16%, transparent);
        border-color: color-mix(in srgb, var(--wptw-ms-text) 34%, transparent);
    }
    .wptw-toc--layout-manuscript .toc-ms-content {
        display: flex;
        flex: 1;
        flex-direction: column;
        grid-column: 2;
        min-width: 0;
        padding: 4px 0 0;
    }
    .wptw-toc--layout-manuscript .toc-ms-main,
    .wptw-toc--layout-manuscript .toc-ms-sub-link {
        background: transparent !important;
        border-left: 0 !important;
        box-shadow: none !important;
        margin: 0 !important;
        padding: 0 !important;
        text-decoration: none !important;
    }
    .wptw-toc--layout-manuscript .toc-ms-main {
        color: var(--wptw-ms-muted) !important;
    }
    .wptw-toc--layout-manuscript .toc-ms-main:hover {
        color: var(--wptw-ms-hover) !important;
    }
    .wptw-toc--layout-manuscript .is-active .toc-ms-main {
        color: var(--wptw-ms-text) !important;
    }
    .wptw-toc--layout-manuscript .toc-ms-roman {
        color: var(--wptw-ms-accent);
        font-size: var(--wptw-num-sz);
        letter-spacing: .1em;
        margin-bottom: 2px;
        min-width: 0;
        opacity: .78;
    }
    .wptw-toc--layout-manuscript .toc-ms-title {
        color: inherit;
        font-family: "DM Serif Display", Georgia, "Times New Roman", serif;
        font-size: max(var(--wptw-flink), 14px);
        font-weight: 400;
        line-height: 1.3;
    }
    .wptw-toc--layout-manuscript .is-done .toc-ms-title {
        color: var(--wptw-ms-read);
    }
    .wptw-toc--layout-manuscript .toc-ms-sub {
        display: flex;
        flex-direction: column;
        gap: 3px;
        margin-top: 6px;
    }
    .wptw-toc--layout-manuscript .toc-ms-sub-link {
        align-items: center;
        color: var(--wptw-ms-faint) !important;
        display: flex;
        font-size: var(--wptw-fsub);
        gap: 6px;
        line-height: 1.35;
    }
    .wptw-toc--layout-manuscript .toc-ms-sub-link::before {
        background: currentColor;
        content: '';
        flex: 0 0 12px;
        height: 1px;
    }
    .wptw-toc--layout-manuscript .toc-ms-sub-link.toc-child-d4 {
        margin-left: 16px;
    }
    .wptw-toc--layout-manuscript .toc-ms-sub-link.toc-child-d4::before {
        flex-basis: 9px;
    }
    .wptw-toc--layout-manuscript .toc-ms-sub-link.toc-child-d5 {
        font-size: calc(var(--wptw-fsub) - 1px);
        margin-left: 32px;
        opacity: .9;
    }
    .wptw-toc--layout-manuscript .toc-ms-sub-link.toc-child-d5::before {
        flex-basis: 7px;
    }
    .wptw-toc--layout-manuscript .toc-ms-sub-link.toc-child-d6 {
        font-size: calc(var(--wptw-fsub) - 1px);
        margin-left: 48px;
        opacity: .78;
    }
    .wptw-toc--layout-manuscript .toc-ms-sub-link.toc-child-d6::before {
        flex-basis: 5px;
    }
    .wptw-toc--layout-manuscript .is-active .toc-ms-sub-link {
        color: color-mix(in srgb, var(--wptw-ms-text) 50%, transparent) !important;
    }
    .wptw-toc--layout-manuscript .toc-ms-footer {
        align-items: center;
        border-top: 1px solid color-mix(in srgb, var(--wptw-ms-text) 8%, transparent);
        display: flex;
        gap: 16px;
        justify-content: space-between;
        margin: 4px var(--wptw-ms-pad-x) 28px;
        padding-top: 16px;
    }
    .wptw-toc--layout-manuscript .toc-ms-footer-label {
        color: var(--wptw-ms-faint);
        font-family: var(--wptw-mono);
        font-size: 9px;
        letter-spacing: .08em;
        text-transform: uppercase;
    }
    .wptw-toc--layout-manuscript .toc-ms-track {
        background: var(--wptw-ms-line);
        border-radius: 2px;
        flex: 0 0 80px;
        height: 2px;
        margin: 0;
    }
    .wptw-toc--layout-manuscript .toc-ms-track-fill {
        background: var(--wptw-ms-accent);
        border-radius: inherit;
    }

    .wptw-sticky-bar {
        position:        fixed;
        /* left/width set dynamically by JS to match card width */
        top:             0; /* overridden by JS with sticky_top offset */
        z-index:         var(--wptw-sticky-z, 9999);
        display:         flex;
        align-items:     center;
        justify-content: space-between;
        padding:         11px 18px;
        background:      var(--wptw-head-bg);
        border:          1px solid var(--wptw-border);
        border-radius:   var(--wptw-radius);
        box-shadow:      0 4px 18px rgba(0,0,0,0.13);
        opacity:         0;
        transform:       translateY(-6px);
        transition:      opacity 200ms ease, transform 200ms ease;
        pointer-events:  none;
        font-family:     var(--wptw-font);
        box-sizing:      border-box;
        overflow:        visible !important;
    }
    .wptw-sticky-bar.is-visible {
        opacity:        1;
        transform:      none;
        pointer-events: auto;
    }
    /* Progress bar inside sticky bar */
    .wptw-sticky-bar .wptw-toc__prog {
        position: absolute;
        bottom:   0; left: 0; right: 0;
        height:   3px;
        border-radius: 0;
    }
    /* Inherit toggle styles inside bar */
    .wptw-sticky-bar .wptw-toc__toggle {
        background:  var(--wptw-tog-bg) !important;
        color:       var(--wptw-tog-fg) !important;
        border:      1px solid var(--wptw-tog-bdr) !important;
    }
    .wptw-sticky-bar.wptw-toc--layout-manuscript,
    .wptw-sticky-bar.wptw-toc--layout-brutalist {
        background: var(--wptw-ms-ink, #0f172a);
        border-color: var(--wptw-link-hov);
        border-radius: 0;
    }
    .wptw-sticky-bar.wptw-toc--layout-brutalist {
        background: var(--wptw-head-bg);
    }
    .wptw-sticky-bar.wptw-toc--layout-editorial {
        background: var(--wptw-head-bg);
        border-color: var(--wptw-border);
        padding: 18px 18px;
    }
    .wptw-sticky-bar.wptw-toc--layout-manuscript .wptw-toc__toggle,
    .wptw-sticky-bar.wptw-toc--layout-brutalist .wptw-toc__toggle {
        background: var(--wptw-tog-bg) !important;
        border-color: var(--wptw-tog-bdr) !important;
        color: var(--wptw-tog-fg) !important;
    }
    .wptw-sticky-bar.wptw-toc--layout-editorial .wptw-toc__toggle {
        background: var(--wptw-tog-bg) !important;
        border-color: var(--wptw-tog-bdr) !important;
        color: var(--wptw-tog-fg) !important;
    }
    .wptw-sticky-bar.wptw-toc--layout-manuscript .wptw-toc__prog {
        background: color-mix(in srgb, var(--wptw-bg) 10%, transparent);
        height: 2px;
    }
    .wptw-sticky-bar.wptw-toc--layout-manuscript .wptw-toc__prog-fill {
        background: var(--wptw-bar);
    }
    .wptw-sticky-bar.wptw-toc--layout-editorial .wptw-toc__prog {
        background: var(--wptw-rtbar-bg);
        height: 3px;
    }
    .wptw-sticky-bar.wptw-toc--layout-brutalist .wptw-toc__prog {
        background: var(--wptw-rtbar-bg);
        height: 4px;
    }
    .wptw-sticky-bar.wptw-toc--layout-brutalist .wptw-toc__prog-fill {
        background: var(--wptw-rtbar-fill);
    }
    .wptw-sticky-bar .wptw-toc__head-left {
        display:     flex;
        align-items: center;
        gap:         12px;
        flex:        1;
        min-width:   0;
    }
    .wptw-sticky-bar__body {
        position:      absolute !important;
        top:           100% !important;
        left:          -1px !important;
        right:         -1px !important;
        background:    var(--wptw-bg) !important;
        border:        1px solid var(--wptw-border) !important;
        border-top:    none !important;
        border-radius: 0 0 var(--wptw-radius) var(--wptw-radius) !important;
        box-shadow:    0 8px 24px rgba(0,0,0,0.15) !important;
        max-height:    400px !important;
        overflow-y:    auto !important;
        display:       none;
        padding:       12px 18px !important;
        box-sizing:    border-box !important;
        z-index:       9998 !important;
    }

    /* â”€â”€ Back-to-top â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .wptw-btt {
        position:      fixed;
        bottom:        28px;
        right:         28px;
        width:         42px;
        height:        42px;
        background:    var(--wptw-btt-bg) !important;
        color:         var(--wptw-btt-fg) !important;
        border:        none !important;
        border-radius: 50%;
        font-size:     17px;
        cursor:        pointer;
        opacity:       0;
        transform:     translateY(10px) scale(0.9);
        transition:    opacity 260ms ease, transform 260ms ease;
        z-index:       10000;
        box-shadow:    0 3px 12px rgba(0,0,0,0.20);
        line-height:   1;
    }
    .wptw-btt.is-visible { opacity:1; transform:none; }
    .wptw-btt:hover { opacity:0.8; }

    /* â”€â”€ Mobile â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    @media (max-width:768px) {
        .wptw-toc                           { margin:1.5rem 0; }
        .wptw-toc__head                     { padding:9px 14px; }
        .wptw-toc__link                     { padding:7px 14px; }
        .wptw-toc__item--d3 .wptw-toc__link { padding-left:28px; }
        .wptw-toc__item--d4 .wptw-toc__link { padding-left:42px; }
        .wptw-toc--layout-editorial .wptw-toc__head { padding:18px 16px 14px; }
        .wptw-toc--layout-brutalist::after { inset:5px -6px -6px 5px; }
        .wptw-sticky-bar,
        .wptw-sticky-bar.wptw-toc--layout-editorial { padding:17px 14px; }
        .wptw-btt                           { bottom:16px; right:16px; width:38px; height:38px; font-size:15px; }
    }

    <?php
    return (string) ob_get_clean();
}

/* â”€â”€â”€ Frontend scripts â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
add_action( 'wp_enqueue_scripts', 'wptw_frontend_scripts', 20 );

function wptw_frontend_scripts() {
    if ( ! is_singular( (array) wptw_get('post_types') ) ) return;
    $o = wptw_get();
    $post_id = get_the_ID();
    $eff = fn( string $k ) => wptw_effective( $k, $post_id );
    $eff_offset = ( $eff('sticky_top_offset') !== '' && $eff('sticky_top_offset') !== null ) ? (int) $eff('sticky_top_offset') : (int) $o['sticky_top_offset'];

    $cfg = [
        'smoothScroll'    => (bool) $o['smooth_scroll'],
        'scrollOffset'    => (int)  $o['scroll_offset'],
        'highlightActive' => (bool) $o['highlight_active'],
        'backToTop'       => (bool) $o['back_to_top'],
        'readingProgress' => (bool) $o['reading_progress'],
        'anchorPrefix'    => sanitize_key( $o['anchor_prefix'] ),
        'stickyTop'       => $eff_offset,
        'stickyZIndex'    => (int)  ( $o['sticky_z_index'] ?? 9999 ),
        'headerSelector'  => sanitize_text_field( $o['sticky_header_selector'] ?? '' ),
        'readingWpm'      => (int)  $o['reading_wpm'],
        'stickyMobileOnly'=> (bool) $o['sticky_mobile_only'],
        'labels'          => [
            'show' => esc_attr( $o['label_show'] ?: 'Show' ),
            'hide' => esc_attr( $o['label_hide'] ?: 'Hide' ),
            'read' => esc_attr__( 'min read', 'cr8vstacks-table-of-contents' ),
            'left' => esc_attr__( 'min left', 'cr8vstacks-table-of-contents' ),
            'done' => esc_attr__( 'Done', 'cr8vstacks-table-of-contents' ),
        ],
    ];
    ob_start();
    ?>
    (function(){
        'use strict';
        var cfg = <?php echo wp_json_encode($cfg); ?>;

        document.addEventListener('DOMContentLoaded', function(){
            /* Adjust offsets dynamically for WordPress Admin Bar if present */
            var adminBar = document.getElementById('wpadminbar');
            if (adminBar) {
                var barHeight = adminBar.offsetHeight || 0;
                cfg.stickyTop += barHeight;
                cfg.scrollOffset += barHeight;
            }

            /* Adjust offsets dynamically for custom header selector if present */
            if (cfg.headerSelector) {
                try {
                    var customHeader = document.querySelector(cfg.headerSelector);
                    if (customHeader) {
                        var headHeight = customHeader.offsetHeight || 0;
                        cfg.stickyTop += headHeight;
                    }
                } catch(e){}
            }

            /* â•â• Helper: get live rect of TOC card â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
            function getCardRect(toc) {
                return toc.getBoundingClientRect();
            }

            /* â•â• Per TOC â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
            document.querySelectorAll('.wptw-toc').forEach(function(toc){
                var head     = toc.querySelector('.wptw-toc__head') || toc.querySelector('.toc-manuscript-eyebrow');
                var body     = toc.querySelector('.wptw-toc__body');
                var toggle   = toc.querySelector('.wptw-toc__toggle');
                var list     = toc.querySelector('.wptw-toc__list');
                var ttext    = toc.querySelector('.wptw-toc__tog-text');
                var progFill = toc.querySelector('.wptw-toc__prog-fill');
                var rtSpan   = toc.querySelector('.wptw-toc__rt');
                if (!list || !body) return;


                /* Layout state interaction: mirrors the design mockup click behaviour. */
                var tocItems = Array.prototype.slice.call(toc.querySelectorAll('.wptw-toc__item'));
                function setTocProgress(activeItem) {
                    var activeIndex = tocItems.indexOf(activeItem);
                    tocItems.forEach(function(item, idx){
                        item.classList.toggle('is-active', idx === activeIndex);
                        item.classList.toggle('is-done', activeIndex > -1 && idx < activeIndex);
                    });
                    toc.querySelectorAll('.toc-ed-dot').forEach(function(dot, idx){
                        dot.textContent = idx < activeIndex ? '\u2713' : String(idx + 1);
                    });
                }
                tocItems.forEach(function(item){
                    item.querySelectorAll('.wptw-toc__link').forEach(function(link){
                        link.addEventListener('click', function(){ setTocProgress(item); });
                    });
                });

                /* â”€â”€ Collapse / expand â”€â”€ */
                if (toggle) {
                    toggle.addEventListener('click', function(){
                        var open = toggle.getAttribute('aria-expanded') === 'true';
                        if (open) {
                            list.style.height = list.scrollHeight + 'px';
                            requestAnimationFrame(function(){
                                list.style.height       = '0px';
                                list.style.opacity      = '0';
                                list.style.paddingTop   = '0';
                                list.style.paddingBottom= '0';
                            });
                            toggle.setAttribute('aria-expanded','false');
                            if (ttext) ttext.textContent = cfg.labels.show;
                            syncBarToggle(false);
                        } else {
                            list.style.paddingTop    = '';
                            list.style.paddingBottom = '';
                            list.style.opacity = '1';
                            list.style.height  = list.scrollHeight + 'px';
                            toggle.setAttribute('aria-expanded','true');
                            if (ttext) ttext.textContent = cfg.labels.hide;
                            list.addEventListener('transitionend', function rst(){
                                list.style.height = 'auto';
                                list.removeEventListener('transitionend',rst);
                            });
                        }
                    });
                }

                /* â• â•  STICKY BAR â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• 
                 *
                 * BEHAVIOUR:
                 *   When TOC is OPEN â†’ sticky triggers when the TOC HEADER
                 *   scrolls out of view (hybrid feel â€” header was visible).
                 *   When TOC is CLOSED â†’ sticky triggers when the whole
                 *   (small) card exits viewport.
                 *
                 *   Either way, the bar matches the card's pixel width and
                 *   horizontal position â€” NOT full viewport width.
                 *
                 *   Bar also contains a synced progress bar fill.
                 * â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
                var stickyBar    = null;
                var barProgFill  = null;
                var lastBarLeft  = -1;
                var lastBarWidth = -1;

                function positionBar() {
                    if (!stickyBar) return;
                    /* Get current card position relative to viewport */
                    var rect = toc.getBoundingClientRect();
                    var left = Math.round(rect.left + window.scrollX);
                    var w    = Math.round(rect.width);
                    if (left !== lastBarLeft || w !== lastBarWidth) {
                        stickyBar.style.left  = left + 'px';
                        stickyBar.style.width = w + 'px';
                        lastBarLeft  = left;
                        lastBarWidth = w;
                    }
                }

                if (toc.classList.contains('wptw-toc--sticky') && head) {

                    /* Build bar â€” clone header content */
                    stickyBar = document.createElement('div');
                    stickyBar.className = 'wptw-sticky-bar';
                    Array.prototype.slice.call(toc.classList).forEach(function(cls){
                        if (cls.indexOf('wptw-toc--layout-') === 0) stickyBar.classList.add(cls);
                    });
                    stickyBar.style.top = cfg.stickyTop + 'px';
                    if (cfg.stickyZIndex) {
                        stickyBar.style.zIndex = cfg.stickyZIndex;
                    }

                    /* Inherit CSS custom properties from toc element */
                    var tocStyle = window.getComputedStyle(toc);
                    [
                        '--wptw-head-bg','--wptw-border','--wptw-radius',
                        '--wptw-label-c','--wptw-label-sz','--wptw-label-ls','--wptw-label-tt',
                        '--wptw-rt-c','--wptw-rt-sz',
                        '--wptw-tog-bg','--wptw-tog-fg','--wptw-tog-bdr',
                        '--wptw-rtbar-fill','--wptw-rtbar-bg',
                        '--wptw-bg','--wptw-link-hov','--wptw-bar',
                        '--wptw-font','--wptw-mono','--wptw-ease',
                        '--wptw-link','--wptw-act-bg','--wptw-num-c',
                        '--wptw-num-sz','--wptw-flink','--wptw-fsub'
                    ].forEach(function(v){
                        var val = tocStyle.getPropertyValue(v);
                        if (val !== null && val !== undefined) {
                            stickyBar.style.setProperty(v, val.trim());
                        }
                    });

                    /* Clone header HTML */
                    stickyBar.innerHTML = head.innerHTML;

                    /* Add progress bar clone inside sticky bar */
                    if (progFill) {
                        var barProg = document.createElement('div');
                        barProg.className = 'wptw-toc__prog';
                        barProg.innerHTML = '<div class="wptw-toc__prog-fill"></div>';
                        stickyBar.appendChild(barProg);
                        barProgFill = barProg.querySelector('.wptw-toc__prog-fill');
                    }

                    /* Clone body for sticky dropdown */
                    if (!body) return;
                    var stickyBody = body.cloneNode(true);
                    stickyBody.classList.add('wptw-sticky-bar__body');
                    stickyBody.style.display = 'none';
                    stickyBody.style.height = '';
                    stickyBody.style.opacity = '';
                    stickyBody.style.paddingTop = '';
                    stickyBody.style.paddingBottom = '';
                    /* Reset inline styles on cloned children to prevent inherited collapsed state */
                    stickyBody.querySelectorAll('.wptw-toc__list').forEach(function(el) {
                        el.style.height = '';
                        el.style.opacity = '';
                        el.style.paddingTop = '';
                        el.style.paddingBottom = '';
                    });
                    stickyBar.appendChild(stickyBody);

                    var barToggle = stickyBar.querySelector('.wptw-toc__toggle');
                    if (barToggle) {
                        barToggle.setAttribute('aria-expanded', 'false');
                        barToggle.addEventListener('click', function(e) {
                            e.preventDefault();
                            var isExpanded = barToggle.getAttribute('aria-expanded') === 'true';
                            setStickyBodyExpanded(!isExpanded);
                        });
                    }

                    stickyBody.querySelectorAll('a').forEach(function(link) {
                        link.addEventListener('click', function() {
                            setStickyBodyExpanded(false);
                        });
                    });

                    function setStickyBodyExpanded(expanded) {
                        if (!stickyBar) return;
                        var bt = stickyBar.querySelector('.wptw-toc__tog-text');
                        var si = stickyBar.querySelector('.wptw-toc__toggle');
                        if (si) si.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                        if (bt) {
                            bt.textContent = expanded ? cfg.labels.hide : cfg.labels.show;
                        }
                        var icon = si ? si.querySelector('.wptw-toc__tog-icon') : null;
                        if (icon) icon.style.transform = expanded ? '' : 'rotate(-90deg)';
                        
                        if (stickyBody) {
                            stickyBody.style.display = expanded ? 'block' : 'none';
                        }
                    }

                    document.body.appendChild(stickyBar);
                    positionBar();

                    /* Keep bar positioned and updated on resize */
                    window.addEventListener('resize', checkSticky, { passive:true });

                    /* â”€â”€ Scroll-based sticky trigger (hybrid) â”€â”€
                     *
                     * Strategy: watch the TOC HEADER (not the card).
                     * - When header scrolls above sticky-top offset â†’ show bar.
                     * - When header is below that point â†’ hide bar.
                     * This means: if TOC is open, you get the sticky feeling
                     * immediately when the header leaves. If TOC is closed
                     * (header IS the whole card), same behaviour.
                     * â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
                    var isBarVisible = false;

                    function checkSticky() {
                        var headRect  = head.getBoundingClientRect();
                        var shouldShow = headRect.bottom < cfg.stickyTop;
                        if (cfg.stickyMobileOnly && window.innerWidth > 768) {
                            shouldShow = false;
                        }
                        if (shouldShow !== isBarVisible) {
                            isBarVisible = shouldShow;
                            stickyBar.classList.toggle('is-visible', shouldShow);
                            if (!shouldShow) {
                                setStickyBodyExpanded(false);
                            }
                        }
                        positionBar();
                    }

                    window.addEventListener('scroll', checkSticky, { passive:true });
                    checkSticky(); /* run once on load */
                }

                /* Keep bar toggle text/state in sync */
                function syncBarToggle(isOpen) {
                    if (!stickyBar) return;
                    var bt = stickyBar.querySelector('.wptw-toc__tog-text');
                    var si = stickyBar.querySelector('.wptw-toc__toggle');
                    if (bt) bt.textContent = isOpen ? cfg.labels.hide : cfg.labels.show;
                    if (si) si.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                    /* also sync icon rotation */
                    var icon = si ? si.querySelector('.wptw-toc__tog-icon') : null;
                    if (icon) icon.style.transform = isOpen ? '' : 'rotate(-90deg)';
                }

                /* â•â• READING PROGRESS + TIME COUNTDOWN â•â•â•â•â•â•â•â•â•â•â•â•â• */
                if (cfg.readingProgress || rtSpan) {
                    var totalMins = rtSpan ? parseFloat(rtSpan.dataset.totalMins) || 0 : 0;

                    window.addEventListener('scroll', function(){
                        /* Progress: scroll from bottom-of-card to bottom-of-page */
                        var tocBottom  = toc.offsetTop + toc.offsetHeight;
                        var docH       = document.documentElement.scrollHeight;
                        var winH       = window.innerHeight;
                        var scrollable = docH - winH - tocBottom;
                        if (scrollable <= 0) return;

                        var scrolled = Math.max(0, window.scrollY - tocBottom);
                        var pct      = Math.min(100, scrolled / scrollable);
                        var pctInt   = Math.round(pct * 100);

                        /* Update progress bars (card + sticky) */
                        if (cfg.readingProgress) {
                            var w = pctInt + '%';
                            if (progFill)    progFill.style.width    = w;
                            if (barProgFill) barProgFill.style.width = w;
                            var progressLabel = toc.querySelector('.toc-ed-progress-label');
                            if (progressLabel) progressLabel.textContent = pctInt + '% done';
                            /* Update aria */
                            var prog = toc.querySelector('.wptw-toc__prog');
                            if (prog) prog.setAttribute('aria-valuenow', pctInt);
                        }

                        /* Update reading time countdown */
                        if (rtSpan && totalMins > 0) {
                            var remaining = Math.ceil(totalMins * (1 - pct));
                            remaining = Math.max(0, remaining);
                            var label;
                            if (pctInt >= 98) {
                                label = '✓ ' + cfg.labels.done;
                            } else if (pct < 0.02) {
                                label = totalMins + ' ' + cfg.labels.read;
                            } else {
                                label = remaining + ' ' + cfg.labels.left;
                            }
                            /* Update in card */
                            if (rtSpan.textContent !== label) rtSpan.textContent = label;
                            /* Update in sticky bar */
                            if (stickyBar) {
                                var barRt = stickyBar.querySelector('.wptw-toc__rt');
                                if (barRt && barRt.textContent !== label) barRt.textContent = label;
                            }
                        }

                    }, { passive:true });
                }

            }); /* forEach .wptw-toc */

            /* â•â• Smooth scroll â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
            if (cfg.smoothScroll) {
                document.querySelectorAll('.wptw-toc__link').forEach(function(link){
                    link.addEventListener('click', function(e){
                        e.preventDefault();
                        var id  = this.getAttribute('href').slice(1);
                        var el  = document.getElementById(id);
                        if (!el) return;
                        var top = el.getBoundingClientRect().top + window.pageYOffset - cfg.scrollOffset;
                        window.scrollTo({ top:top, behavior:'smooth' });
                    });
                });
            }

            /* â•â• Active section highlight â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
            if (cfg.highlightActive && 'IntersectionObserver' in window) {
                var p        = cfg.anchorPrefix;
                var headings = document.querySelectorAll('h2[id^="'+p+'"],h3[id^="'+p+'"],h4[id^="'+p+'"],h5[id^="'+p+'"],h6[id^="'+p+'"]');
                var items    = document.querySelectorAll('.wptw-toc__item');
                var activeId = null;

                var obs = new IntersectionObserver(function(entries){
                    entries.forEach(function(e){ if(e.isIntersecting) activeId = e.target.id; });
                    var activeIndex = -1;
                    items.forEach(function(item, idx){
                        var links = Array.prototype.slice.call(item.querySelectorAll('.wptw-toc__link'));
                        var isActive = links.some(function(a){ return a.getAttribute('href') === '#'+activeId; });
                        item.classList.toggle('is-active', isActive);
                        if (isActive) activeIndex = idx;
                    });
                    items.forEach(function(item, idx){
                        item.classList.toggle('is-done', activeIndex > -1 && idx < activeIndex);
                    });
                    document.querySelectorAll('.wptw-toc--layout-editorial').forEach(function(toc){
                        var tocItems = Array.prototype.slice.call(toc.querySelectorAll('.wptw-toc__item'));
                        var tocActiveIndex = tocItems.findIndex(function(item){ return item.classList.contains('is-active'); });
                        toc.querySelectorAll('.toc-ed-dot').forEach(function(dot, idx){
                            dot.textContent = idx < tocActiveIndex ? '\u2713' : String(idx + 1);
                        });
                    });
                }, { rootMargin: '-'+cfg.scrollOffset+'px 0px -55% 0px', threshold:0 });

                headings.forEach(function(h){ obs.observe(h); });
            }

            /* â•â• Back-to-top â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
            if (cfg.backToTop) {
                var btn   = document.querySelector('.wptw-btt');
                var tocEl = document.querySelector('.wptw-toc');
                if (btn && tocEl) {
                    btn.removeAttribute('hidden');
                    window.addEventListener('scroll', function(){
                        var past = window.pageYOffset > (tocEl.offsetTop + tocEl.offsetHeight + 150);
                        btn.classList.toggle('is-visible', past);
                    }, { passive:true });
                    btn.addEventListener('click', function(){
                        window.scrollTo({ top: tocEl.offsetTop - cfg.scrollOffset, behavior:'smooth' });
                    });
                }
            }

        }); /* DOMContentLoaded */
    })();
    <?php
    $script = (string) ob_get_clean();

    wp_register_script( 'wptw-script', false, [], WPTW_VERSION, true );
    wp_enqueue_script( 'wptw-script' );
    wp_add_inline_script( 'wptw-script', $script, 'after' );
}

/**
 * Smart scanner to find the first paragraph at the root level (depth 0).
 *
 * Tracks depth across ALL HTML5 block-level container elements so that
 * paragraphs injected by any third-party plugin wrapper are skipped,
 * regardless of which element name that plugin uses.
 *
 * @param string $content HTML content.
 * @param array  $levels  Participating heading levels (h2-h6).
 * @return int|false      The offset of the closing </p> tag, or false.
 */
function wptw_find_root_paragraph( string $content, array $levels ): int|false {
    // All HTML5 block containers that can wrap <p> tags.
    // Void/inline elements intentionally excluded â€” they cannot nest <p>.
    $containers = 'div|section|article|aside|main|nav|header|footer|figure|figcaption|blockquote|details|summary|fieldset|form|dialog';

    $pattern = '/(<\/?(?:' . $containers . '|p|h[2-6])\b[^>]*>)/i';
    $tokens  = preg_split( $pattern, $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE );

    $depth = 0;
    foreach ( $tokens as $token ) {
        $tag    = $token[0];
        $offset = $token[1];

        if ( ! isset( $tag[0] ) || $tag[0] !== '<' ) continue;

        if ( preg_match( '/^<(' . $containers . ')\b/i', $tag ) ) {
            $depth++;
        } elseif ( preg_match( '/^<\/(' . $containers . ')\b/i', $tag ) ) {
            $depth = max( 0, $depth - 1 );
        } elseif ( $depth === 0 && preg_match( '/^<\/p\b/i', $tag ) ) {
            return $offset;
        } elseif ( $depth === 0 && preg_match( '/^<h[2-6]\b/i', $tag ) ) {
            return false;
        }
    }

    return false;
}

/**
 * Depth-aware scanner to find the first heading at the root level (depth 0).
 *
 * Uses the same container set as wptw_find_root_paragraph() so that headings
 * injected inside any third-party plugin wrapper are skipped.
 *
 * @param string $content HTML content.
 * @param array  $levels  Participating heading levels (e.g. ['h2','h3']).
 * @return int|false      The offset of the opening <hN> tag, or false.
 */
function wptw_find_root_heading( string $content, array $levels ): int|false {
    $nums = implode( '', array_map( fn( $h ) => substr( $h, 1 ), $levels ) );
    if ( $nums === '' ) return false;

    $containers = 'div|section|article|aside|main|nav|header|footer|figure|figcaption|blockquote|details|summary|fieldset|form|dialog';

    $pattern = '/(<\/?(?:' . $containers . '|h[' . $nums . '])\b[^>]*>)/i';
    $tokens  = preg_split( $pattern, $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE );

    $depth = 0;
    foreach ( $tokens as $token ) {
        $tag    = $token[0];
        $offset = $token[1];

        if ( ! isset( $tag[0] ) || $tag[0] !== '<' ) continue;

        if ( preg_match( '/^<(' . $containers . ')\b/i', $tag ) ) {
            $depth++;
        } elseif ( preg_match( '/^<\/(' . $containers . ')\b/i', $tag ) ) {
            $depth = max( 0, $depth - 1 );
        } elseif ( $depth === 0 && preg_match( '/^<h[' . $nums . ']\b/i', $tag ) ) {
            return $offset;
        }
    }

    return false;
}
