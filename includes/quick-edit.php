<?php
defined( 'ABSPATH' ) || exit;

/* ─── Add custom column to post list ─────────────────────── */
add_filter( 'manage_posts_columns',       'wptw_add_list_column' );
add_filter( 'manage_pages_columns',       'wptw_add_list_column' );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( $hook !== 'edit.php' ) return;

    $screen = get_current_screen();
    $types  = (array) wptw_get( 'post_types' );
    if ( ! $screen || ! in_array( $screen->post_type, $types, true ) ) return;

    wp_enqueue_style( 'wptw-quick-edit', WPTW_URL . 'assets/quick-edit.css', [], WPTW_VERSION );
    wp_enqueue_script( 'wptw-quick-edit', WPTW_URL . 'assets/quick-edit.js', [ 'jquery' ], WPTW_VERSION, true );
    wp_localize_script( 'wptw-quick-edit', 'wptwQuickEdit', [
        'nonce' => wp_create_nonce( 'wptw_qe_save' ),
    ] );
} );

function wptw_add_list_column( array $cols ): array {
    $types = (array) wptw_get( 'post_types' );
    $screen = get_current_screen();
    if ( $screen && in_array( $screen->post_type, $types, true ) ) {
        $cols['wptw_toc'] = '<span title="Cr8vstacks Table of Contents">TOC</span>';
    }
    return $cols;
}

/* ─── Populate column ─────────────────────────────────────── */
add_action( 'manage_posts_custom_column', 'wptw_render_list_column', 10, 2 );
add_action( 'manage_pages_custom_column', 'wptw_render_list_column', 10, 2 );

function wptw_render_list_column( string $col, int $post_id ): void {
    if ( $col !== 'wptw_toc' ) return;
    $meta = wptw_post_meta( $post_id );

    if ( ! empty( $meta['disable'] ) ) {
        echo '<span class="wptw-col-off" title="TOC disabled">✕</span>';
        return;
    }
    $state = $meta['default_state'] ?? '';
    if ( $state === 'open' ) {
        echo '<span class="wptw-col-open" title="Open by default">▾ Open</span>';
    } elseif ( $state === 'closed' ) {
        echo '<span class="wptw-col-closed" title="Closed by default">▸ Closed</span>';
    } else {
        echo '<span class="wptw-col-global" title="Using global setting">Global</span>';
    }
}

/* ─── Quick Edit fields ───────────────────────────────────── */
add_action( 'quick_edit_custom_box', function ( $col, $post_type ) {
    if ( $col !== 'wptw_toc' ) return;
    $types = (array) wptw_get( 'post_types' );
    if ( ! in_array( $post_type, $types, true ) ) return;
    $g = wptw_get();
    ?>
    <fieldset class="inline-edit-col-right wptw-qe-fieldset">
        <div class="inline-edit-col">
            <label class="wptw-qe-heading">
                <svg width="13" height="13" viewBox="0 0 13 13" fill="none" style="vertical-align:-2px"><rect x=".5" y=".5" width="12" height="12" rx="2.5" stroke="currentColor" stroke-width="1.2"/><path d="M3 4h4M3 6.5h7M3 9h5" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg>
                Cr8v TOC
            </label>

            <div class="wptw-qe-row">
                <label class="wptw-qe-label">
                    <input type="checkbox" name="wptw_qe[disable]" value="1" class="wptw-qe-disable">
                    <span>Disable TOC</span>
                </label>
            </div>

            <div class="wptw-qe-row">
                <label class="wptw-qe-label">Initial state</label>
                <select name="wptw_qe[default_state]" class="wptw-qe-select">
                    <option value="">— Global (<?php echo esc_html( $g['default_state'] ); ?>)</option>
                    <option value="open">Open</option>
                    <option value="closed">Closed</option>
                </select>
            </div>

            <div class="wptw-qe-row">
                <label class="wptw-qe-label">Position</label>
                <select name="wptw_qe[position]" class="wptw-qe-select">
                    <option value="">— Global</option>
                    <option value="before_first_heading">Before first heading</option>
                    <option value="after_first_paragraph">After first paragraph</option>
                    <option value="shortcode_only">Shortcode only</option>
                </select>
            </div>

            <div class="wptw-qe-row">
                <label class="wptw-qe-label">Section numbers</label>
                <select name="wptw_qe[show_numbers]" class="wptw-qe-select">
                    <option value="">— Global</option>
                    <option value="1">Show</option>
                    <option value="0">Hide</option>
                </select>
            </div>

            <input type="hidden" name="wptw_qe_nonce" value="">
            <input type="hidden" name="wptw_qe_post_id" value="">
        </div>
    </fieldset>
    <?php
}, 10, 2 );

/* ─── Populate Quick Edit via JS ──────────────────────────── */
/* ─── Embed data attrs in each row (for JS pre-population) ── */
add_action( 'manage_posts_custom_column', function( $col, $post_id ) {
    if ( $col !== 'wptw_toc' ) return;
    $meta = wptw_post_meta( $post_id );
    $nums = isset( $meta['show_numbers'] ) ? $meta['show_numbers'] : '';
    echo '<span class="wptw-qe-data" style="display:none"'
        . ' data-disable="'  . esc_attr( $meta['disable'] ?? 0 )      . '"'
        . ' data-state="'    . esc_attr( $meta['default_state'] ?? '' ) . '"'
        . ' data-position="' . esc_attr( $meta['position'] ?? '' )     . '"'
        . ' data-nums="'     . esc_attr( $nums )                       . '"'
        . '></span>';
}, 20, 2 );

/* ─── AJAX handler for Quick Edit save ───────────────────── */
add_action( 'wp_ajax_wptw_quick_edit_save', function () {
    check_ajax_referer( 'wptw_qe_save', 'nonce' );

    $post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
    if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
        wp_send_json_error( 'Unauthorized' );
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) wp_send_json_error( 'Permissions' );

    $existing = wptw_post_meta( $post_id );

    $existing['disable']       = ! empty( $_POST['disable'] ) ? 1 : 0;
    $existing['default_state'] = in_array( sanitize_text_field( wp_unslash( $_POST['default_state'] ?? '' ) ), [ 'open', 'closed', '' ], true ) ? sanitize_text_field( wp_unslash( $_POST['default_state'] ) ) : '';
    $existing['position']      = in_array( sanitize_text_field( wp_unslash( $_POST['position'] ?? '' ) ), [ 'before_first_heading', 'after_first_paragraph', 'shortcode_only', '' ], true ) ? sanitize_text_field( wp_unslash( $_POST['position'] ) ) : '';
    $existing['show_numbers']  = in_array( sanitize_text_field( wp_unslash( $_POST['show_numbers'] ?? '' ) ), [ '0', '1', '' ], true ) ? sanitize_text_field( wp_unslash( $_POST['show_numbers'] ) ) : '';

    update_post_meta( $post_id, WPTW_META, $existing );
    wp_send_json_success();
} );
