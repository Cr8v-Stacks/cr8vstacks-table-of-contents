<?php
defined( 'ABSPATH' ) || exit;

/* â”€â”€â”€ Register meta box â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
add_action( 'add_meta_boxes', function () {
    $post_types = (array) wptw_get( 'post_types' );
    foreach ( $post_types as $pt ) {
        add_meta_box(
            'wptw-meta-box',
            'Cr8vstacks Table of Contents',
            'wptw_render_meta_box',
            $pt,
            'side',
            'default'
        );
    }
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) return;

    $screen = get_current_screen();
    $types  = (array) wptw_get( 'post_types' );
    if ( ! $screen || ! in_array( $screen->post_type, $types, true ) ) return;

    wp_enqueue_style( 'wptw-meta-box', WPTW_URL . 'assets/meta-box.css', [], WPTW_VERSION );
    wp_enqueue_script( 'wptw-meta-box', WPTW_URL . 'assets/meta-box.js', [], WPTW_VERSION, true );
} );

/* â”€â”€â”€ Render meta box â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
function wptw_render_meta_box( WP_Post $post ) {
    $meta  = wptw_post_meta( $post->ID );
    $g     = wptw_get();
    wp_nonce_field( 'wptw_meta_save', 'wptw_meta_nonce' );
    ?>
    <div class="wptw-meta">

        <!-- Disable TOC -->
        <div class="wptw-meta-field">
            <label class="wptw-meta-toggle">
                <input type="hidden" name="wptw_meta[disable]" value="0">
                <input type="checkbox" name="wptw_meta[disable]" value="1"
                       id="wptw-meta-disable"
                       <?php checked( ! empty( $meta['disable'] ) ); ?>>
                <span class="wptw-meta-knob"></span>
                <span class="wptw-meta-toggle-label">Disable TOC on this post</span>
            </label>
            <p class="wptw-meta-help">Hides the TOC regardless of global settings.</p>
        </div>

        <div id="wptw-meta-options" class="<?php echo ! empty( $meta['disable'] ) ? 'wptw-meta-dimmed' : ''; ?>">

            <!-- Initial state override -->
            <div class="wptw-meta-field">
                <label class="wptw-meta-label" for="wptw-meta-state">Initial TOC state</label>
                <select name="wptw_meta[default_state]" id="wptw-meta-state" class="wptw-meta-select">
                    <option value="" <?php selected( $meta['default_state'] ?? '', '' ); ?>>
                        â€” Use global setting (<?php echo esc_html( $g['default_state'] ); ?>)
                    </option>
                    <option value="open"   <?php selected( $meta['default_state'] ?? '', 'open'   ); ?>>Open</option>
                    <option value="closed" <?php selected( $meta['default_state'] ?? '', 'closed' ); ?>>Closed</option>
                </select>
            </div>

            <!-- Position override -->
            <div class="wptw-meta-field">
                <label class="wptw-meta-label" for="wptw-meta-pos">TOC position</label>
                <select name="wptw_meta[position]" id="wptw-meta-pos" class="wptw-meta-select">
                    <option value="" <?php selected( $meta['position'] ?? '', '' ); ?>>â€” Use global setting</option>
                    <option value="before_first_heading"  <?php selected( $meta['position'] ?? '', 'before_first_heading'  ); ?>>Before first heading</option>
                    <option value="after_first_paragraph" <?php selected( $meta['position'] ?? '', 'after_first_paragraph' ); ?>>After first paragraph</option>
                    <option value="shortcode_only"        <?php selected( $meta['position'] ?? '', 'shortcode_only'        ); ?>>Manual â€” shortcode only</option>
                </select>
            </div>

            <!-- Custom title override -->
            <div class="wptw-meta-field">
                <label class="wptw-meta-label" for="wptw-meta-title">TOC title</label>
                <input type="text" name="wptw_meta[toc_title]" id="wptw-meta-title"
                       value="<?php echo esc_attr( $meta['toc_title'] ?? '' ); ?>"
                       placeholder="<?php echo esc_attr( $g['toc_title'] ); ?>"
                       class="wptw-meta-input">
                <p class="wptw-meta-help">Leave blank to use global title.</p>
            </div>

            <!-- Show/hide numbers -->
            <div class="wptw-meta-field">
                <label class="wptw-meta-label" for="wptw-meta-nums">Section numbers</label>
                <select name="wptw_meta[show_numbers]" id="wptw-meta-nums" class="wptw-meta-select">
                    <option value="" <?php selected( $meta['show_numbers'] ?? '', '' ); ?>>â€” Use global setting</option>
                    <option value="1" <?php selected( $meta['show_numbers'] ?? '', '1' ); ?>>Show numbers</option>
                    <option value="0" <?php selected( $meta['show_numbers'] ?? '', '0' ); ?>>Hide numbers</option>
                </select>
            </div>

            <!-- Sticky header override -->
            <div class="wptw-meta-field">
                <label class="wptw-meta-label" for="wptw-meta-sticky">Sticky TOC header</label>
                <select name="wptw_meta[sticky_header]" id="wptw-meta-sticky" class="wptw-meta-select">
                    <option value="" <?php selected( $meta['sticky_header'] ?? '', '' ); ?>>â€” Use global setting</option>
                    <option value="1" <?php selected( $meta['sticky_header'] ?? '', '1' ); ?>>Enabled</option>
                    <option value="0" <?php selected( $meta['sticky_header'] ?? '', '0' ); ?>>Disabled</option>
                </select>
            </div>

            <!-- Reading time override -->
            <div class="wptw-meta-field">
                <label class="wptw-meta-label" for="wptw-meta-rt">Reading time</label>
                <select name="wptw_meta[reading_time]" id="wptw-meta-rt" class="wptw-meta-select">
                    <option value="" <?php selected( $meta['reading_time'] ?? '', '' ); ?>>â€” Use global setting</option>
                    <option value="1" <?php selected( $meta['reading_time'] ?? '', '1' ); ?>>Show</option>
                    <option value="0" <?php selected( $meta['reading_time'] ?? '', '0' ); ?>>Hide</option>
                </select>
            </div>

        </div><!-- /#wptw-meta-options -->
    </div>

    <?php
}

/* â”€â”€â”€ Save meta box â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
add_action( 'save_post', function ( $post_id ) {
    if ( ! isset( $_POST['wptw_meta_nonce'] ) ) return;
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wptw_meta_nonce'] ) ), 'wptw_meta_save' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $raw = isset( $_POST['wptw_meta'] ) ? map_deep( wp_unslash( $_POST['wptw_meta'] ), 'sanitize_text_field' ) : [];
    $clean = [];

    $clean['disable']       = ! empty( $raw['disable'] ) ? 1 : 0;
    $clean['default_state'] = in_array( $raw['default_state'] ?? '', [ 'open','closed','' ], true ) ? $raw['default_state'] : '';
    $clean['position']      = in_array( $raw['position'] ?? '', [ 'before_first_heading','after_first_paragraph','shortcode_only','' ], true ) ? $raw['position'] : '';
    $clean['toc_title']     = sanitize_text_field( $raw['toc_title'] ?? '' );
    $clean['show_numbers']  = in_array( $raw['show_numbers'] ?? '', [ '0','1','' ], true ) ? $raw['show_numbers'] : '';
    $clean['sticky_header'] = in_array( $raw['sticky_header'] ?? '', [ '0','1','' ], true ) ? $raw['sticky_header'] : '';
    $clean['reading_time']  = in_array( $raw['reading_time'] ?? '', [ '0','1','' ], true ) ? $raw['reading_time'] : '';

    update_post_meta( $post_id, WPTW_META, $clean );
} );

/* â”€â”€â”€ Gutenberg sidebar panel via REST + block editor sidebar â”€ */
add_action( 'init', 'wptw_register_post_meta' );
function wptw_register_post_meta(): void {
    $types = array_unique( array_filter( array_merge( [ 'post', 'page' ], (array) wptw_get( 'post_types' ) ) ) );

    foreach ( $types as $pt ) {
        register_post_meta( $pt, WPTW_META, [
            'show_in_rest'  => [
                'schema' => [
                    'type'                 => 'object',
                    'additionalProperties' => false,
                    'properties'           => [
                        'disable'       => [ 'type' => 'integer' ],
                        'default_state' => [ 'type' => 'string' ],
                        'position'      => [ 'type' => 'string' ],
                        'toc_title'     => [ 'type' => 'string' ],
                        'show_numbers'  => [ 'type' => 'string' ],
                        'sticky_header' => [ 'type' => 'string' ],
                        'reading_time'  => [ 'type' => 'string' ],
                    ],
                ],
            ],
            'single'        => true,
            'type'          => 'object',
            'auth_callback' => function ( bool $allowed, string $meta_key, int $post_id ): bool {
                return $post_id ? current_user_can( 'edit_post', $post_id ) : current_user_can( 'edit_posts' );
            },
        ] );
    }
}

add_action( 'enqueue_block_editor_assets', function () {
    $screen = get_current_screen();
    $types  = (array) wptw_get( 'post_types' );
    if ( ! $screen || ! in_array( $screen->post_type, $types, true ) ) return;

    $post_id = get_the_ID();
    $meta    = wptw_post_meta( $post_id );
    $g       = wptw_get();

    wp_register_script(
        'wptw-gutenberg-sidebar',
        false,
        [ 'wp-plugins', 'wp-editor', 'wp-element', 'wp-components', 'wp-data', 'wp-i18n' ],
        WPTW_VERSION,
        true
    );
    wp_enqueue_script( 'wptw-gutenberg-sidebar' );
    wp_add_inline_script( 'wptw-gutenberg-sidebar', wptw_gutenberg_sidebar_js( $meta, $g ), 'after' );
} );

function wptw_gutenberg_sidebar_js( array $meta, array $g ): string {
    $global_state = esc_js( $g['default_state'] );
    $global_title = esc_js( $g['toc_title'] );
    $meta_key     = WPTW_META;

    ob_start();
    ?>
(function(){
    var el   = wp.element.createElement;
    var __   = wp.i18n.__;
    var frag = wp.element.Fragment;

    var { registerPlugin } = wp.plugins;
    var editorPackage = wp.editor || wp.editPost;
    var { PluginSidebar, PluginSidebarMoreMenuItem } = editorPackage;
    var { PanelBody, SelectControl, TextControl, ToggleControl, Tip } = wp.components;
    var { useSelect, useDispatch } = wp.data;

    function Cr8vTocSidebar(){
        var postId = useSelect(function(s){ return s('core/editor').getCurrentPostId(); });
        var metaRaw = useSelect(function(s){
            return s('core/editor').getEditedPostAttribute('meta') || {};
        });

        var { editPost } = useDispatch('core/editor');

        var meta = metaRaw['<?php echo esc_js( $meta_key ); ?>'] || {};

        function setMeta(key, val){
            var updated = Object.assign({}, metaRaw['<?php echo esc_js( $meta_key ); ?>'] || {});
            updated[key] = val;
            var metaUpdate = {};
            metaUpdate['<?php echo esc_js( $meta_key ); ?>'] = updated;
            editPost({ meta: metaUpdate });
        }

        return el(frag, null,
            el(PluginSidebarMoreMenuItem, { target: 'wptw-sidebar' }, __('Cr8vstacks Table of Contents', 'cr8vstacks-table-of-contents')),
            el(PluginSidebar, { name: 'wptw-sidebar', title: __('Cr8vstacks Table of Contents', 'cr8vstacks-table-of-contents'), icon: el('svg',{width:16,height:16,viewBox:'0 0 16 16',fill:'none'},el('rect',{x:.5,y:.5,width:15,height:15,rx:3,stroke:'currentColor','strokeWidth':1.2}),el('path',{d:'M4 5h4M4 8h8M4 11h6',stroke:'currentColor','strokeWidth':1.2,'strokeLinecap':'round'})) },
                el(PanelBody, { title: __('Per-post Settings', 'cr8vstacks-table-of-contents'), initialOpen: true },

                    el(ToggleControl, {
                        label: __('Disable TOC on this post', 'cr8vstacks-table-of-contents'),
                        checked: !!meta.disable,
                        onChange: function(v){ setMeta('disable', v ? 1 : 0); },
                        help: __('Hides the TOC regardless of global settings.', 'cr8vstacks-table-of-contents')
                    }),

                    el(SelectControl, {
                        label: __('Initial TOC state', 'cr8vstacks-table-of-contents'),
                        value: meta.default_state || '',
                        options: [
                            { value: '', label: __('Use global setting', 'cr8vstacks-table-of-contents') + ' (' + '<?php echo esc_js( $global_state ); ?>' + ')' },
                            { value: 'open',   label: __('Open', 'cr8vstacks-table-of-contents')   },
                            { value: 'closed', label: __('Closed', 'cr8vstacks-table-of-contents') },
                        ],
                        onChange: function(v){ setMeta('default_state', v); }
                    }),

                    el(SelectControl, {
                        label: __('TOC position', 'cr8vstacks-table-of-contents'),
                        value: meta.position || '',
                        options: [
                            { value: '', label: __('Use global setting', 'cr8vstacks-table-of-contents') },
                            { value: 'before_first_heading',  label: __('Before first heading', 'cr8vstacks-table-of-contents')   },
                            { value: 'after_first_paragraph', label: __('After first paragraph', 'cr8vstacks-table-of-contents')  },
                            { value: 'shortcode_only',        label: __('Shortcode only', 'cr8vstacks-table-of-contents')         },
                        ],
                        onChange: function(v){ setMeta('position', v); }
                    }),

                    el(TextControl, {
                        label: __('TOC title', 'cr8vstacks-table-of-contents'),
                        value: meta.toc_title || '',
                        placeholder: '<?php echo esc_js( $global_title ); ?>',
                        onChange: function(v){ setMeta('toc_title', v); },
                        help: __('Leave blank to use global title.', 'cr8vstacks-table-of-contents')
                    }),

                    el(SelectControl, {
                        label: __('Section numbers', 'cr8vstacks-table-of-contents'),
                        value: meta.show_numbers !== undefined ? String(meta.show_numbers) : '',
                        options: [
                            { value: '',  label: __('Use global setting', 'cr8vstacks-table-of-contents') },
                            { value: '1', label: __('Show', 'cr8vstacks-table-of-contents') },
                            { value: '0', label: __('Hide', 'cr8vstacks-table-of-contents') },
                        ],
                        onChange: function(v){ setMeta('show_numbers', v); }
                    }),

                    el(SelectControl, {
                        label: __('Sticky TOC header', 'cr8vstacks-table-of-contents'),
                        value: meta.sticky_header !== undefined ? String(meta.sticky_header) : '',
                        options: [
                            { value: '',  label: __('Use global setting', 'cr8vstacks-table-of-contents') },
                            { value: '1', label: __('Enabled', 'cr8vstacks-table-of-contents')  },
                            { value: '0', label: __('Disabled', 'cr8vstacks-table-of-contents') },
                        ],
                        onChange: function(v){ setMeta('sticky_header', v); }
                    }),

                    el(SelectControl, {
                        label: __('Reading time', 'cr8vstacks-table-of-contents'),
                        value: meta.reading_time !== undefined ? String(meta.reading_time) : '',
                        options: [
                            { value: '',  label: __('Use global setting', 'cr8vstacks-table-of-contents') },
                            { value: '1', label: __('Show', 'cr8vstacks-table-of-contents') },
                            { value: '0', label: __('Hide', 'cr8vstacks-table-of-contents') },
                        ],
                        onChange: function(v){ setMeta('reading_time', v); }
                    }),

                    el(Tip, null, __('Use [wptw_toc] shortcode for manual TOC placement.', 'cr8vstacks-table-of-contents'))
                )
            )
        );
    }

    registerPlugin('cr8vstacks-table-of-contents', { render: Cr8vTocSidebar });
})();
    <?php
    return (string) ob_get_clean();
}
