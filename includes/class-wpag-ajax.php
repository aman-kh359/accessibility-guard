<?php
/**
 * AJAX handlers for async scanning.
 *
 * @package WP_Accessibility_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AJAX handler class.
 */
class WPAG_Ajax {

    /** @var self|null */
    private static $instance = null;

    /**
     * Get singleton instance.
     *
     * @return self
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->register_hooks();
        }
        return self::$instance;
    }

    /**
     * Register AJAX hooks.
     */
    private function register_hooks() {
        add_action( 'wp_ajax_wpag_scan_page', array( $this, 'ajax_scan_page' ) );
        add_action( 'wp_ajax_wpag_scan_all', array( $this, 'ajax_scan_all' ) );
        add_action( 'wp_ajax_wpag_clear_results', array( $this, 'ajax_clear_results' ) );
    }

    /**
     * Scan a single page.
     */
    public function ajax_scan_page() {
        check_ajax_referer( 'wpag_scan_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'wp-accessibility-guard' ) ) );
        }

        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        if ( ! $post_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid post ID.', 'wp-accessibility-guard' ) ) );
        }

        $url = get_permalink( $post_id );
        if ( ! $url ) {
            wp_send_json_error( array( 'message' => __( 'Could not determine URL for this post.', 'wp-accessibility-guard' ) ) );
        }

        $html = WPAG_Scanner::fetch_page_html( $url );

        if ( is_wp_error( $html ) ) {
            wp_send_json_error( array( 'message' => $html->get_error_message() ) );
        }

        $scanner = new WPAG_Scanner( $html, $url, $post_id );
        $issues  = $scanner->scan( true );
        $summary = $scanner->get_summary();

        update_post_meta( $post_id, '_wpag_last_scan', current_time( 'mysql' ) );
        update_post_meta( $post_id, '_wpag_issue_count', count( $issues ) );
        update_post_meta( $post_id, '_wpag_error_count', $summary['error'] );

        wp_send_json_success( array(
            'issues'  => $issues,
            'summary' => $summary,
            'post_id' => $post_id,
        ) );
    }

    /**
     * Scan all pages in batches.
     */
    public function ajax_scan_all() {
        check_ajax_referer( 'wpag_scan_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'wp-accessibility-guard' ) ) );
        }

        $offset     = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
        $batch_size = 5;
        $settings   = get_option( 'wpag_settings', wpag_default_settings() );
        $post_types = ! empty( $settings['scan_post_types'] ) ? $settings['scan_post_types'] : array( 'page', 'post' );

        $query = new WP_Query( array(
            'post_type'      => $post_types,
            'post_status'    => 'publish',
            'posts_per_page' => $batch_size,
            'offset'         => $offset,
            'fields'         => 'ids',
        ) );

        $total   = $query->found_posts;
        $results = array();

        foreach ( $query->posts as $pid ) {
            $url  = get_permalink( $pid );
            $html = WPAG_Scanner::fetch_page_html( $url );

            if ( is_wp_error( $html ) ) {
                $results[] = array(
                    'post_id' => $pid,
                    'error'   => $html->get_error_message(),
                );
                continue;
            }

            $scanner = new WPAG_Scanner( $html, $url, $pid );
            $issues  = $scanner->scan( true );
            $summary = $scanner->get_summary();

            update_post_meta( $pid, '_wpag_last_scan', current_time( 'mysql' ) );
            update_post_meta( $pid, '_wpag_issue_count', count( $issues ) );
            update_post_meta( $pid, '_wpag_error_count', $summary['error'] );

            $results[] = array(
                'post_id' => $pid,
                'summary' => $summary,
            );
        }

        wp_send_json_success( array(
            'results'  => $results,
            'total'    => $total,
            'offset'   => $offset + $batch_size,
            'complete' => ( $offset + $batch_size ) >= $total,
        ) );
    }

    /**
     * Clear all scan results.
     */
    public function ajax_clear_results() {
        check_ajax_referer( 'wpag_scan_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'wp-accessibility-guard' ) ) );
        }

        global $wpdb;

        // Delete all transients.
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            $wpdb->esc_like( '_transient_wpag_scan_' ) . '%',
            $wpdb->esc_like( '_transient_timeout_wpag_scan_' ) . '%'
        ) );

        // Delete all post meta.
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
            $wpdb->esc_like( '_wpag_' ) . '%'
        ) );

        wp_send_json_success( array( 'message' => __( 'All results cleared.', 'wp-accessibility-guard' ) ) );
    }
}
