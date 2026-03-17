<?php
/**
 * Plugin Name: Disable default pages
 */
add_filter( 'woocommerce_create_pages', 'disable_default_pages' );

function disable_default_pages( $pages ) {
    unset( $pages['shop'] );

    unset( $pages['myaccount'] );
    unset( $pages['refund_returns'] );

    return $pages;
}

add_action( 'init', 'setup_pages' );

function setup_pages() {
    $sample = get_page_by_path('sample-page');
    if ($sample) {
        wp_delete_post($sample->ID, true);
    }

    $home = get_page_by_path('namespace');
    if ($home) { 
        update_option('show_on_front', 'page'); 

        update_option('page_on_front', $home->ID); 
    }

}


