<?php
/**
 * Admin dashboard and settings.
 *
 * @package WP_Accessibility_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin class — menu, dashboard, settings.
 */
class WPAG_Admin {

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
     * Register WordPress hooks.
     */
    private function register_hooks() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_filter( 'manage_pages_columns', array( $this, 'add_scan_column' ) );
        add_action( 'manage_pages_custom_column', array( $this, 'render_scan_column' ), 10, 2 );
        add_filter( 'manage_posts_columns', array( $this, 'add_scan_column' ) );
        add_action( 'manage_posts_custom_column', array( $this, 'render_scan_column' ), 10, 2 );
    }

    /**
     * Add admin menu pages.
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Accessibility Guard', 'wp-accessibility-guard' ),
            __( 'A11y Guard', 'wp-accessibility-guard' ),
            'manage_options',
            'wpag-dashboard',
            array( $this, 'render_dashboard_page' ),
            'dashicons-universal-access-alt',
            80
        );

        add_submenu_page(
            'wpag-dashboard',
            __( 'Dashboard', 'wp-accessibility-guard' ),
            __( 'Dashboard', 'wp-accessibility-guard' ),
            'manage_options',
            'wpag-dashboard',
            array( $this, 'render_dashboard_page' )
        );

        add_submenu_page(
            'wpag-dashboard',
            __( 'Settings', 'wp-accessibility-guard' ),
            __( 'Settings', 'wp-accessibility-guard' ),
            'manage_options',
            'wpag-settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register settings.
     */
    public function register_settings() {
        register_setting(
            'wpag_settings_group',
            'wpag_settings',
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this, 'sanitize_settings' ),
                'default'           => wpag_default_settings(),
            )
        );

        // Auto-fix section.
        add_settings_section(
            'wpag_auto_fix_section',
            __( 'Auto-Fix Options', 'wp-accessibility-guard' ),
            function () {
                echo '<p>' . esc_html__( 'Enable automatic fixes applied to the front-end output.', 'wp-accessibility-guard' ) . '</p>';
            },
            'wpag-settings'
        );

        $auto_fixes = array(
            'auto_fix_skip_link'      => __( 'Inject skip navigation link', 'wp-accessibility-guard' ),
            'auto_fix_lang_attr'      => __( 'Ensure HTML lang attribute', 'wp-accessibility-guard' ),
            'auto_fix_empty_headings' => __( 'Remove empty headings', 'wp-accessibility-guard' ),
            'auto_fix_form_labels'    => __( 'Add missing form labels', 'wp-accessibility-guard' ),
        );

        foreach ( $auto_fixes as $key => $label ) {
            add_settings_field(
                'wpag_' . $key,
                $label,
                array( $this, 'render_checkbox_field' ),
                'wpag-settings',
                'wpag_auto_fix_section',
                array( 'key' => $key )
            );
        }

        // Scan settings section.
        add_settings_section(
            'wpag_scan_section',
            __( 'Scan Settings', 'wp-accessibility-guard' ),
            function () {
                echo '<p>' . esc_html__( 'Configure which post types to include in scans.', 'wp-accessibility-guard' ) . '</p>';
            },
            'wpag-settings'
        );

        add_settings_field(
            'wpag_scan_post_types',
            __( 'Post types to scan', 'wp-accessibility-guard' ),
            array( $this, 'render_post_types_field' ),
            'wpag-settings',
            'wpag_scan_section'
        );
    }

    /**
     * Render a checkbox settings field.
     *
     * @param array $args Field args containing 'key'.
     */
    public function render_checkbox_field( $args ) {
        $settings = get_option( 'wpag_settings', wpag_default_settings() );
        $key      = $args['key'];
        $checked  = ! empty( $settings[ $key ] );
        ?>
        <label>
            <input type="checkbox" name="wpag_settings[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( $checked ); ?>>
            <?php esc_html_e( 'Enabled', 'wp-accessibility-guard' ); ?>
        </label>
        <?php
    }

    /**
     * Render post types checkboxes.
     */
    public function render_post_types_field() {
        $settings   = get_option( 'wpag_settings', wpag_default_settings() );
        $selected   = isset( $settings['scan_post_types'] ) ? $settings['scan_post_types'] : array( 'page', 'post' );
        $post_types = get_post_types( array( 'public' => true ), 'objects' );

        foreach ( $post_types as $pt ) {
            if ( $pt->name === 'attachment' ) {
                continue;
            }
            $checked = in_array( $pt->name, $selected, true );
            ?>
            <label style="display:block;margin-bottom:6px;">
                <input type="checkbox" name="wpag_settings[scan_post_types][]" value="<?php echo esc_attr( $pt->name ); ?>" <?php checked( $checked ); ?>>
                <?php echo esc_html( $pt->label ); ?>
            </label>
            <?php
        }
    }

    /**
     * Sanitize settings on save.
     *
     * @param array $input Raw input.
     * @return array Sanitized.
     */
    public function sanitize_settings( $input ) {
        $defaults  = wpag_default_settings();
        $sanitized = array();

        // Booleans.
        $sanitized['auto_fix_skip_link']      = ! empty( $input['auto_fix_skip_link'] );
        $sanitized['auto_fix_lang_attr']      = ! empty( $input['auto_fix_lang_attr'] );
        $sanitized['auto_fix_empty_headings'] = ! empty( $input['auto_fix_empty_headings'] );
        $sanitized['auto_fix_form_labels']    = ! empty( $input['auto_fix_form_labels'] );

        // Post types.
        $sanitized['scan_post_types'] = array();
        if ( isset( $input['scan_post_types'] ) && is_array( $input['scan_post_types'] ) ) {
            $sanitized['scan_post_types'] = array_map( 'sanitize_text_field', $input['scan_post_types'] );
        }

        // Severity filter.
        $sanitized['severity_filter'] = isset( $input['severity_filter'] ) ? sanitize_text_field( $input['severity_filter'] ) : 'all';

        // Statement fields (preserve from existing).
        $existing = get_option( 'wpag_settings', $defaults );
        $sanitized['statement_page_id']     = isset( $existing['statement_page_id'] ) ? absint( $existing['statement_page_id'] ) : 0;
        $sanitized['statement_org_name']    = isset( $input['statement_org_name'] ) ? sanitize_text_field( $input['statement_org_name'] ) : ( $existing['statement_org_name'] ?? '' );
        $sanitized['statement_email']       = isset( $input['statement_email'] ) ? sanitize_email( $input['statement_email'] ) : ( $existing['statement_email'] ?? '' );
        $sanitized['statement_conformance'] = isset( $input['statement_conformance'] ) ? sanitize_text_field( $input['statement_conformance'] ) : ( $existing['statement_conformance'] ?? 'AA' );
        $sanitized['statement_limitations'] = isset( $input['statement_limitations'] ) ? sanitize_textarea_field( $input['statement_limitations'] ) : ( $existing['statement_limitations'] ?? '' );

        return $sanitized;
    }

    /**
     * Enqueue admin CSS and JS.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_assets( $hook ) {
        // Load on our admin pages and post list screens.
        $our_pages = array( 'toplevel_page_wpag-dashboard', 'a11y-guard_page_wpag-settings', 'a11y-guard_page_wpag-statement' );
        $is_ours   = in_array( $hook, $our_pages, true );
        $is_list   = in_array( $hook, array( 'edit.php' ), true );

        if ( ! $is_ours && ! $is_list ) {
            return;
        }

        wp_enqueue_style(
            'wpag-admin',
            WPAG_PLUGIN_URL . 'assets/css/wpag-admin.css',
            array(),
            WPAG_VERSION
        );

        if ( $is_ours ) {
            wp_enqueue_script(
                'wpag-admin',
                WPAG_PLUGIN_URL . 'assets/js/wpag-admin.js',
                array( 'jquery' ),
                WPAG_VERSION,
                true
            );

            wp_localize_script( 'wpag-admin', 'wpag_data', array(
                'ajax_url'           => admin_url( 'admin-ajax.php' ),
                'nonce'              => wp_create_nonce( 'wpag_scan_nonce' ),
                'scanning_text'      => __( 'Scanning...', 'wp-accessibility-guard' ),
                'scan_text'          => __( 'Scan', 'wp-accessibility-guard' ),
                'rescan_text'        => __( 'Re-scan', 'wp-accessibility-guard' ),
                'complete_text'      => __( 'Scan Complete! Reloading...', 'wp-accessibility-guard' ),
                'confirm_clear'      => __( 'Are you sure you want to clear all scan results?', 'wp-accessibility-guard' ),
                'scan_failed'        => __( 'Scan failed.', 'wp-accessibility-guard' ),
                'request_failed'     => __( 'Request failed. Please try again.', 'wp-accessibility-guard' ),
                'clear_failed'       => __( 'Failed to clear results.', 'wp-accessibility-guard' ),
                'error_generating'   => __( 'Error generating statement.', 'wp-accessibility-guard' ),
            ) );
        }
    }

    /**
     * Render the main dashboard page.
     */
    public function render_dashboard_page() {
        // Check for detail view.
        $detail_post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;

        if ( $detail_post_id ) {
            $this->render_detail_view( $detail_post_id );
            return;
        }

        $settings   = get_option( 'wpag_settings', wpag_default_settings() );
        $post_types = ! empty( $settings['scan_post_types'] ) ? $settings['scan_post_types'] : array( 'page', 'post' );

        // Get all scanned pages.
        $query = new WP_Query( array(
            'post_type'      => $post_types,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_key'       => '_wpag_last_scan',
            'orderby'        => 'meta_value',
            'order'          => 'DESC',
        ) );

        $scanned_posts = $query->posts;

        // Get all published posts count.
        $total_query = new WP_Query( array(
            'post_type'      => $post_types,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ) );
        $total_pages = $total_query->found_posts;

        // Calculate totals.
        $total_errors   = 0;
        $total_warnings = 0;
        $total_notices  = 0;
        $total_issues   = 0;

        foreach ( $scanned_posts as $post ) {
            $count  = (int) get_post_meta( $post->ID, '_wpag_issue_count', true );
            $errors = (int) get_post_meta( $post->ID, '_wpag_error_count', true );
            $total_issues += $count;
            $total_errors += $errors;

            $cached = get_transient( 'wpag_scan_' . $post->ID );
            if ( is_array( $cached ) ) {
                foreach ( $cached as $issue ) {
                    if ( $issue['severity'] === 'warning' ) {
                        $total_warnings++;
                    } elseif ( $issue['severity'] === 'notice' ) {
                        $total_notices++;
                    }
                }
            }
        }

        $auto_fixes_count = 0;
        foreach ( array( 'auto_fix_skip_link', 'auto_fix_lang_attr', 'auto_fix_empty_headings', 'auto_fix_form_labels' ) as $fix_key ) {
            if ( ! empty( $settings[ $fix_key ] ) ) {
                $auto_fixes_count++;
            }
        }

        ?>
        <div class="wrap wpag-wrap">
            <h1><?php esc_html_e( 'Accessibility Guard', 'wp-accessibility-guard' ); ?></h1>

            <!-- Summary Cards -->
            <div class="wpag-summary-cards">
                <div class="wpag-card wpag-card-errors">
                    <div class="wpag-card-number"><?php echo esc_html( $total_issues ); ?></div>
                    <div class="wpag-card-label"><?php esc_html_e( 'Total Issues', 'wp-accessibility-guard' ); ?></div>
                    <div class="wpag-card-breakdown">
                        <span class="wpag-badge-error"><?php echo esc_html( $total_errors ); ?> <?php esc_html_e( 'errors', 'wp-accessibility-guard' ); ?></span>
                        <span class="wpag-badge-warning"><?php echo esc_html( $total_warnings ); ?> <?php esc_html_e( 'warnings', 'wp-accessibility-guard' ); ?></span>
                        <span class="wpag-badge-notice"><?php echo esc_html( $total_notices ); ?> <?php esc_html_e( 'notices', 'wp-accessibility-guard' ); ?></span>
                    </div>
                </div>
                <div class="wpag-card">
                    <div class="wpag-card-number"><?php echo esc_html( count( $scanned_posts ) ); ?> / <?php echo esc_html( $total_pages ); ?></div>
                    <div class="wpag-card-label"><?php esc_html_e( 'Pages Scanned', 'wp-accessibility-guard' ); ?></div>
                </div>
                <div class="wpag-card">
                    <div class="wpag-card-number"><?php echo esc_html( $auto_fixes_count ); ?></div>
                    <div class="wpag-card-label"><?php esc_html_e( 'Auto-Fixes Active', 'wp-accessibility-guard' ); ?></div>
                </div>
                <div class="wpag-card">
                    <div class="wpag-card-number">
                        <?php
                        if ( $total_pages > 0 && count( $scanned_posts ) > 0 ) {
                            $clean = 0;
                            foreach ( $scanned_posts as $post ) {
                                if ( (int) get_post_meta( $post->ID, '_wpag_error_count', true ) === 0 ) {
                                    $clean++;
                                }
                            }
                            echo esc_html( round( ( $clean / count( $scanned_posts ) ) * 100 ) ) . '%';
                        } else {
                            echo '—';
                        }
                        ?>
                    </div>
                    <div class="wpag-card-label"><?php esc_html_e( 'Error-Free Pages', 'wp-accessibility-guard' ); ?></div>
                </div>
            </div>

            <!-- Actions -->
            <div class="wpag-actions">
                <button type="button" class="button button-primary wpag-scan-all">
                    <span class="dashicons dashicons-search" style="margin-top:3px;margin-right:4px;"></span>
                    <?php esc_html_e( 'Scan All Pages', 'wp-accessibility-guard' ); ?>
                </button>
                <button type="button" class="button wpag-clear-results">
                    <?php esc_html_e( 'Clear All Results', 'wp-accessibility-guard' ); ?>
                </button>
                <div class="wpag-progress" style="display:none;">
                    <div class="wpag-progress-bar"><div class="wpag-progress-fill"></div></div>
                    <span class="wpag-progress-text"></span>
                </div>
            </div>

            <!-- Results Table -->
            <?php if ( ! empty( $scanned_posts ) ) : ?>
            <table class="widefat wpag-results-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Page', 'wp-accessibility-guard' ); ?></th>
                        <th><?php esc_html_e( 'Type', 'wp-accessibility-guard' ); ?></th>
                        <th class="wpag-col-center"><?php esc_html_e( 'Errors', 'wp-accessibility-guard' ); ?></th>
                        <th class="wpag-col-center"><?php esc_html_e( 'Warnings', 'wp-accessibility-guard' ); ?></th>
                        <th class="wpag-col-center"><?php esc_html_e( 'Notices', 'wp-accessibility-guard' ); ?></th>
                        <th><?php esc_html_e( 'Last Scanned', 'wp-accessibility-guard' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'wp-accessibility-guard' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $scanned_posts as $post ) :
                        $errors   = (int) get_post_meta( $post->ID, '_wpag_error_count', true );
                        $total    = (int) get_post_meta( $post->ID, '_wpag_issue_count', true );
                        $last     = get_post_meta( $post->ID, '_wpag_last_scan', true );
                        $warnings = 0;
                        $notices  = 0;

                        $cached = get_transient( 'wpag_scan_' . $post->ID );
                        if ( is_array( $cached ) ) {
                            foreach ( $cached as $issue ) {
                                if ( $issue['severity'] === 'warning' ) {
                                    $warnings++;
                                } elseif ( $issue['severity'] === 'notice' ) {
                                    $notices++;
                                }
                            }
                        }

                        $post_type_obj = get_post_type_object( $post->post_type );
                    ?>
                    <tr>
                        <td>
                            <strong>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpag-dashboard&post_id=' . $post->ID ) ); ?>">
                                    <?php echo esc_html( $post->post_title ); ?>
                                </a>
                            </strong>
                        </td>
                        <td><?php echo esc_html( $post_type_obj ? $post_type_obj->labels->singular_name : $post->post_type ); ?></td>
                        <td class="wpag-col-center">
                            <?php if ( $errors > 0 ) : ?>
                                <span class="wpag-badge-error"><?php echo esc_html( $errors ); ?></span>
                            <?php else : ?>
                                <span class="wpag-badge-ok">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="wpag-col-center">
                            <?php if ( $warnings > 0 ) : ?>
                                <span class="wpag-badge-warning"><?php echo esc_html( $warnings ); ?></span>
                            <?php else : ?>
                                <span class="wpag-badge-ok">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="wpag-col-center">
                            <?php if ( $notices > 0 ) : ?>
                                <span class="wpag-badge-notice"><?php echo esc_html( $notices ); ?></span>
                            <?php else : ?>
                                <span class="wpag-badge-ok">0</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html( $last ? human_time_diff( strtotime( $last ), time() ) . ' ago' : '—' ); ?></td>
                        <td>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpag-dashboard&post_id=' . $post->ID ) ); ?>" class="button button-small">
                                <?php esc_html_e( 'Details', 'wp-accessibility-guard' ); ?>
                            </a>
                            <button type="button" class="button button-small wpag-scan-page" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
                                <?php esc_html_e( 'Re-scan', 'wp-accessibility-guard' ); ?>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else : ?>
            <div class="wpag-empty-state">
                <span class="dashicons dashicons-universal-access-alt" style="font-size:48px;width:48px;height:48px;color:#ccc;"></span>
                <h2><?php esc_html_e( 'No scan results yet', 'wp-accessibility-guard' ); ?></h2>
                <p><?php esc_html_e( 'Click "Scan All Pages" to check your site for accessibility issues.', 'wp-accessibility-guard' ); ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render the detail view for a single page.
     *
     * @param int $post_id Post ID.
     */
    private function render_detail_view( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post ) {
            echo '<div class="wrap"><h1>' . esc_html__( 'Post not found.', 'wp-accessibility-guard' ) . '</h1></div>';
            return;
        }

        $issues = get_transient( 'wpag_scan_' . $post_id );
        if ( ! is_array( $issues ) ) {
            $issues = array();
        }

        $last_scan = get_post_meta( $post_id, '_wpag_last_scan', true );

        ?>
        <div class="wrap wpag-wrap">
            <h1>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpag-dashboard' ) ); ?>" class="wpag-back">&larr; <?php esc_html_e( 'Dashboard', 'wp-accessibility-guard' ); ?></a>
                &nbsp;/&nbsp;
                <?php echo esc_html( $post->post_title ); ?>
            </h1>

            <div class="wpag-detail-meta">
                <span><strong><?php esc_html_e( 'URL:', 'wp-accessibility-guard' ); ?></strong> <a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" target="_blank"><?php echo esc_url( get_permalink( $post_id ) ); ?></a></span>
                <span><strong><?php esc_html_e( 'Last scanned:', 'wp-accessibility-guard' ); ?></strong> <?php echo esc_html( $last_scan ? $last_scan : '—' ); ?></span>
                <button type="button" class="button button-primary wpag-scan-page" data-post-id="<?php echo esc_attr( $post_id ); ?>">
                    <?php esc_html_e( 'Re-scan', 'wp-accessibility-guard' ); ?>
                </button>
            </div>

            <?php if ( empty( $issues ) ) : ?>
                <div class="wpag-empty-state">
                    <span class="dashicons dashicons-yes-alt" style="font-size:48px;width:48px;height:48px;color:#46b450;"></span>
                    <h2><?php esc_html_e( 'No issues found!', 'wp-accessibility-guard' ); ?></h2>
                </div>
            <?php else : ?>
            <table class="widefat wpag-issues-table">
                <thead>
                    <tr>
                        <th style="width:80px;"><?php esc_html_e( 'Severity', 'wp-accessibility-guard' ); ?></th>
                        <th><?php esc_html_e( 'Check', 'wp-accessibility-guard' ); ?></th>
                        <th><?php esc_html_e( 'WCAG', 'wp-accessibility-guard' ); ?></th>
                        <th><?php esc_html_e( 'Message', 'wp-accessibility-guard' ); ?></th>
                        <th><?php esc_html_e( 'Element', 'wp-accessibility-guard' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $issues as $issue ) :
                        $check = WPAG_Checks::get_check( $issue['check_id'] );
                    ?>
                    <tr class="wpag-issue-row wpag-severity-<?php echo esc_attr( $issue['severity'] ); ?>">
                        <td>
                            <span class="wpag-badge-<?php echo esc_attr( $issue['severity'] ); ?>">
                                <?php echo esc_html( ucfirst( $issue['severity'] ) ); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html( $check ? $check['title'] : $issue['check_id'] ); ?></td>
                        <td><?php echo esc_html( $issue['wcag'] . ' (' . $issue['level'] . ')' ); ?></td>
                        <td><?php echo esc_html( $issue['message'] ); ?></td>
                        <td>
                            <?php if ( mb_strlen( $issue['element'] ) > 80 ) : ?>
                                <div class="wpag-element-wrap">
                                    <code class="wpag-element-short"><?php echo esc_html( mb_substr( $issue['element'], 0, 80 ) ); ?>…</code>
                                    <button type="button" class="wpag-toggle-element" title="<?php esc_attr_e( 'Show full element', 'wp-accessibility-guard' ); ?>">
                                        <span class="dashicons dashicons-visibility"></span>
                                    </button>
                                    <code class="wpag-element-full"><?php echo esc_html( $issue['element'] ); ?></code>
                                </div>
                            <?php elseif ( $issue['element'] ) : ?>
                                <code class="wpag-element-snippet"><?php echo esc_html( $issue['element'] ); ?></code>
                            <?php else : ?>
                                <span class="wpag-no-element">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render the settings page.
     */
    public function render_settings_page() {
        ?>
        <div class="wrap wpag-wrap">
            <h1><?php esc_html_e( 'Accessibility Guard Settings', 'wp-accessibility-guard' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'wpag_settings_group' );
                do_settings_sections( 'wpag-settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Add A11y column to post/page list tables.
     *
     * @param array $columns Existing columns.
     * @return array
     */
    public function add_scan_column( $columns ) {
        $columns['wpag_status'] = __( 'A11y', 'wp-accessibility-guard' );
        return $columns;
    }

    /**
     * Render the A11y column content.
     *
     * @param string $column  Column name.
     * @param int    $post_id Post ID.
     */
    public function render_scan_column( $column, $post_id ) {
        if ( $column !== 'wpag_status' ) {
            return;
        }

        $last_scan = get_post_meta( $post_id, '_wpag_last_scan', true );
        if ( ! $last_scan ) {
            echo '<span class="wpag-dot wpag-dot-gray" title="' . esc_attr__( 'Not scanned', 'wp-accessibility-guard' ) . '">&#9679;</span>';
            return;
        }

        $errors = (int) get_post_meta( $post_id, '_wpag_error_count', true );
        $issues = (int) get_post_meta( $post_id, '_wpag_issue_count', true );

        if ( $errors > 0 ) {
            $class = 'wpag-dot-red';
            $title = sprintf( __( '%d errors', 'wp-accessibility-guard' ), $errors );
        } elseif ( $issues > 0 ) {
            $class = 'wpag-dot-yellow';
            $title = sprintf( __( '%d warnings/notices', 'wp-accessibility-guard' ), $issues );
        } else {
            $class = 'wpag-dot-green';
            $title = __( 'No issues', 'wp-accessibility-guard' );
        }

        printf(
            '<a href="%s" class="wpag-dot %s" title="%s">&#9679;</a>',
            esc_url( admin_url( 'admin.php?page=wpag-dashboard&post_id=' . $post_id ) ),
            esc_attr( $class ),
            esc_attr( $title )
        );
    }
}
