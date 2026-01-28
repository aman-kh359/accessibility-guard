<?php
/**
 * Plugin Name: Accessibility Guard
 * Plugin URI:  https://github.com/aman-kh359/accessibility-guard
 * Description: WCAG 2.2 compliance scanner with auto-fixes and accessibility statement generation.
 * Version:     1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author:      Digiminati
 * Author URI:  https://github.com/aman-kh359
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-accessibility-guard
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants.
define( 'WPAG_VERSION', '1.0.0' );
define( 'WPAG_PLUGIN_FILE', __FILE__ );
define( 'WPAG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPAG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPAG_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Include class files.
require_once WPAG_PLUGIN_DIR . 'includes/class-wpag-checks.php';
require_once WPAG_PLUGIN_DIR . 'includes/class-wpag-scanner.php';
require_once WPAG_PLUGIN_DIR . 'includes/class-wpag-admin.php';
require_once WPAG_PLUGIN_DIR . 'includes/class-wpag-ajax.php';
require_once WPAG_PLUGIN_DIR . 'includes/class-wpag-auto-fixer.php';
require_once WPAG_PLUGIN_DIR . 'includes/class-wpag-statement.php';

/**
 * Return default plugin settings.
 *
 * @return array
 */
function wpag_default_settings() {
    return array(
        'auto_fix_skip_link'      => true,
        'auto_fix_lang_attr'      => true,
        'auto_fix_empty_headings' => false,
        'auto_fix_form_labels'    => false,
        'statement_page_id'       => 0,
        'scan_post_types'         => array( 'page', 'post' ),
        'severity_filter'         => 'all',
        'statement_org_name'      => '',
        'statement_email'         => '',
        'statement_conformance'   => 'AA',
        'statement_limitations'   => '',
    );
}

/**
 * Load plugin text domain for translations.
 */
function wpag_load_textdomain() {
    load_plugin_textdomain( 'wp-accessibility-guard', false, dirname( WPAG_PLUGIN_BASENAME ) . '/languages' );
}
add_action( 'init', 'wpag_load_textdomain' );

/**
 * Initialize plugin classes.
 */
function wpag_init() {
    WPAG_Admin::instance();
    WPAG_Ajax::instance();
    WPAG_Auto_Fixer::instance();
    WPAG_Statement::instance();
}
add_action( 'plugins_loaded', 'wpag_init' );

/**
 * Plugin activation.
 */
function wpag_activate() {
    add_option( 'wpag_settings', wpag_default_settings() );
    add_option( 'wpag_version', WPAG_VERSION );
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'wpag_activate' );

/**
 * Plugin deactivation.
 */
function wpag_deactivate() {
    global $wpdb;
    $wpdb->query( $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
        $wpdb->esc_like( '_transient_wpag_scan_' ) . '%',
        $wpdb->esc_like( '_transient_timeout_wpag_scan_' ) . '%'
    ) );
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'wpag_deactivate' );
