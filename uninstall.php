<?php
/**
 * Uninstall handler.
 *
 * Fired when the plugin is deleted via the WordPress admin.
 *
 * @package WP_Accessibility_Guard
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Get settings BEFORE deleting (need statement_page_id).
$settings = get_option( 'wpag_settings' );

// Delete the statement page if it exists.
if ( is_array( $settings ) && ! empty( $settings['statement_page_id'] ) ) {
    wp_delete_post( absint( $settings['statement_page_id'] ), true );
}

// Delete options.
delete_option( 'wpag_settings' );
delete_option( 'wpag_version' );

// Delete all post meta.
global $wpdb;
$wpdb->query( $wpdb->prepare(
    "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
    $wpdb->esc_like( '_wpag_' ) . '%'
) );

// Delete all transients.
$wpdb->query( $wpdb->prepare(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
    $wpdb->esc_like( '_transient_wpag_scan_' ) . '%',
    $wpdb->esc_like( '_transient_timeout_wpag_scan_' ) . '%'
) );
