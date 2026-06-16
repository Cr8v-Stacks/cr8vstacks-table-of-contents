<?php
defined( 'ABSPATH' ) || exit;

add_action( 'init', function() {
    if ( did_action( 'elementor/loaded' ) ) {
        add_action( 'elementor/documents/register_controls', 'wptw_register_elementor_controls' );
    }
} );

function wptw_register_elementor_controls( $document ) {
    if ( ! class_exists( '\Elementor\Controls_Manager' ) ) {
        return;
    }

    if ( ! $document instanceof \Elementor\Core\DocumentTypes\PageBase ) {
        return;
    }

    $document->start_controls_section(
        'wptw_toc_section',
        [
            'label' => __( 'Table of Contents', 'cr8vstacks-table-of-contents' ),
            'tab'   => \Elementor\Controls_Manager::TAB_SETTINGS,
        ]
    );

    $document->add_control(
        'wptw_disable_toc',
        [
            'label'        => __( 'Disable TOC', 'cr8vstacks-table-of-contents' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => __( 'Yes', 'cr8vstacks-table-of-contents' ),
            'label_off'    => __( 'No', 'cr8vstacks-table-of-contents' ),
            'return_value' => 'yes',
            'default'      => '',
            'description'  => __( 'Hides the TOC on this post/page regardless of global settings.', 'cr8vstacks-table-of-contents' ),
        ]
    );

    $document->end_controls_section();
}
