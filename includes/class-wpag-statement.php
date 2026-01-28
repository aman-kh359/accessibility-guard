<?php
/**
 * Accessibility statement generator.
 *
 * @package WP_Accessibility_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Manages the accessibility statement page.
 */
class WPAG_Statement {

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
     * Register hooks.
     */
    private function register_hooks() {
        add_action( 'admin_menu', array( $this, 'add_submenu' ) );
        add_action( 'wp_ajax_wpag_generate_statement', array( $this, 'ajax_generate_statement' ) );
    }

    /**
     * Add the statement submenu page.
     */
    public function add_submenu() {
        add_submenu_page(
            'wpag-dashboard',
            __( 'Accessibility Statement', 'accessibility-guard' ),
            __( 'Statement', 'accessibility-guard' ),
            'manage_options',
            'wpag-statement',
            array( $this, 'render_page' )
        );
    }

    /**
     * Render the statement admin page.
     */
    public function render_page() {
        $settings = get_option( 'wpag_settings', wpag_default_settings() );
        $page_id  = ! empty( $settings['statement_page_id'] ) ? absint( $settings['statement_page_id'] ) : 0;
        $page     = $page_id ? get_post( $page_id ) : null;

        ?>
        <div class="wrap wpag-wrap">
            <h1><?php esc_html_e( 'Accessibility Statement Generator', 'accessibility-guard' ); ?></h1>

            <?php if ( $page ) : ?>
                <div class="notice notice-success">
                    <p>
                        <?php esc_html_e( 'Statement page exists:', 'accessibility-guard' ); ?>
                        <a href="<?php echo esc_url( get_permalink( $page_id ) ); ?>" target="_blank"><?php echo esc_html( $page->post_title ); ?></a>
                        &mdash;
                        <a href="<?php echo esc_url( get_edit_post_link( $page_id ) ); ?>"><?php esc_html_e( 'Edit', 'accessibility-guard' ); ?></a>
                    </p>
                </div>
            <?php endif; ?>

            <form id="wpag-statement-form" class="wpag-statement-form">
                <?php wp_nonce_field( 'wpag_statement_nonce', 'wpag_statement_nonce_field' ); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="wpag_org_name"><?php esc_html_e( 'Organization Name', 'accessibility-guard' ); ?></label></th>
                        <td><input type="text" id="wpag_org_name" name="org_name" value="<?php echo esc_attr( $settings['statement_org_name'] ?? '' ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wpag_website_url"><?php esc_html_e( 'Website URL', 'accessibility-guard' ); ?></label></th>
                        <td><input type="url" id="wpag_website_url" name="website_url" value="<?php echo esc_url( home_url() ); ?>" class="regular-text" readonly></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wpag_email"><?php esc_html_e( 'Contact Email', 'accessibility-guard' ); ?></label></th>
                        <td><input type="email" id="wpag_email" name="email" value="<?php echo esc_attr( $settings['statement_email'] ?? '' ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wpag_conformance"><?php esc_html_e( 'Conformance Target', 'accessibility-guard' ); ?></label></th>
                        <td>
                            <select id="wpag_conformance" name="conformance">
                                <option value="A" <?php selected( $settings['statement_conformance'] ?? 'AA', 'A' ); ?>>WCAG 2.2 Level A</option>
                                <option value="AA" <?php selected( $settings['statement_conformance'] ?? 'AA', 'AA' ); ?>>WCAG 2.2 Level AA</option>
                                <option value="AAA" <?php selected( $settings['statement_conformance'] ?? 'AA', 'AAA' ); ?>>WCAG 2.2 Level AAA</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wpag_limitations"><?php esc_html_e( 'Known Limitations', 'accessibility-guard' ); ?></label></th>
                        <td>
                            <textarea id="wpag_limitations" name="limitations" rows="5" class="large-text"><?php echo esc_textarea( $settings['statement_limitations'] ?? '' ); ?></textarea>
                            <p class="description"><?php esc_html_e( 'Describe any known accessibility limitations (one per line).', 'accessibility-guard' ); ?></p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="button" class="button button-primary" id="wpag-generate-statement">
                        <?php echo $page ? esc_html__( 'Update Statement Page', 'accessibility-guard' ) : esc_html__( 'Generate Statement Page', 'accessibility-guard' ); ?>
                    </button>
                    <span class="spinner" id="wpag-statement-spinner"></span>
                </p>
            </form>

            <!-- Preview -->
            <h2><?php esc_html_e( 'Preview', 'accessibility-guard' ); ?></h2>
            <div class="wpag-statement-preview" id="wpag-statement-preview">
                <?php echo wp_kses_post( $this->generate_statement_html( $settings ) ); ?>
            </div>
        </div>

        <!-- Statement JS handled in wpag-admin.js -->
        <?php
    }

    /**
     * AJAX: Generate or update the statement page.
     */
    public function ajax_generate_statement() {
        check_ajax_referer( 'wpag_statement_nonce', 'wpag_statement_nonce_field' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'accessibility-guard' ) ) );
        }

        $settings = get_option( 'wpag_settings', wpag_default_settings() );

        // Update statement settings.
        // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized below.
        $settings['statement_org_name']    = isset( $_POST['org_name'] ) ? sanitize_text_field( wp_unslash( $_POST['org_name'] ) ) : '';
        $settings['statement_email']       = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $settings['statement_conformance'] = isset( $_POST['conformance'] ) ? sanitize_text_field( wp_unslash( $_POST['conformance'] ) ) : 'AA';
        $settings['statement_limitations'] = isset( $_POST['limitations'] ) ? sanitize_textarea_field( wp_unslash( $_POST['limitations'] ) ) : '';
        // phpcs:enable

        $html    = $this->generate_statement_html( $settings );
        $page_id = ! empty( $settings['statement_page_id'] ) ? absint( $settings['statement_page_id'] ) : 0;

        if ( $page_id && get_post( $page_id ) ) {
            // Update existing page.
            wp_update_post( array(
                'ID'           => $page_id,
                'post_content' => $html,
            ) );
            $message = __( 'Accessibility statement page updated.', 'accessibility-guard' );
        } else {
            // Create new page.
            $page_id = wp_insert_post( array(
                'post_title'   => __( 'Accessibility Statement', 'accessibility-guard' ),
                'post_content' => $html,
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_name'    => 'accessibility-statement',
            ) );

            if ( is_wp_error( $page_id ) ) {
                wp_send_json_error( array( 'message' => $page_id->get_error_message() ) );
            }

            $settings['statement_page_id'] = $page_id;
            $message = __( 'Accessibility statement page created.', 'accessibility-guard' );
        }

        update_option( 'wpag_settings', $settings );

        wp_send_json_success( array(
            'message' => $message,
            'page_id' => $page_id,
            'url'     => get_permalink( $page_id ),
        ) );
    }

    /**
     * Generate the accessibility statement HTML.
     *
     * @param array $settings Plugin settings.
     * @return string HTML content.
     */
    public function generate_statement_html( $settings ) {
        $org_name    = ! empty( $settings['statement_org_name'] ) ? $settings['statement_org_name'] : get_bloginfo( 'name' );
        $email       = ! empty( $settings['statement_email'] ) ? $settings['statement_email'] : get_option( 'admin_email' );
        $conformance = ! empty( $settings['statement_conformance'] ) ? $settings['statement_conformance'] : 'AA';
        $limitations = ! empty( $settings['statement_limitations'] ) ? $settings['statement_limitations'] : '';
        $site_url    = home_url();
        $date        = current_time( 'F j, Y' );

        $html = '<h2>' . esc_html__( 'Accessibility Statement', 'accessibility-guard' ) . '</h2>';

        $html .= '<p>' . sprintf(
            /* translators: 1: organization name, 2: site URL */
            esc_html__( '%1$s is committed to ensuring digital accessibility for people with disabilities. We are continually improving the user experience for everyone, and applying the relevant accessibility standards.', 'accessibility-guard' ),
            '<strong>' . esc_html( $org_name ) . '</strong>'
        ) . '</p>';

        $html .= '<h3>' . esc_html__( 'Conformance Status', 'accessibility-guard' ) . '</h3>';
        $html .= '<p>' . sprintf(
            /* translators: 1: site URL, 2: WCAG level */
            esc_html__( 'The Web Content Accessibility Guidelines (WCAG) defines requirements for designers and developers to improve accessibility for people with disabilities. It defines three levels of conformance: Level A, Level AA, and Level AAA. %1$s is partially conformant with WCAG 2.2 Level %2$s.', 'accessibility-guard' ),
            '<a href="' . esc_url( $site_url ) . '">' . esc_html( $org_name ) . '</a>',
            esc_html( $conformance )
        ) . '</p>';

        if ( $limitations ) {
            $html .= '<h3>' . esc_html__( 'Known Limitations', 'accessibility-guard' ) . '</h3>';
            $html .= '<p>' . esc_html__( 'Despite our best efforts, some areas may not yet be fully accessible:', 'accessibility-guard' ) . '</p>';
            $html .= '<ul>';
            $lines = array_filter( array_map( 'trim', explode( "\n", $limitations ) ) );
            foreach ( $lines as $line ) {
                $html .= '<li>' . esc_html( $line ) . '</li>';
            }
            $html .= '</ul>';
        }

        $html .= '<h3>' . esc_html__( 'Feedback', 'accessibility-guard' ) . '</h3>';
        $html .= '<p>' . sprintf(
            /* translators: %s: contact email */
            esc_html__( 'We welcome your feedback on the accessibility of this website. If you encounter accessibility barriers, please contact us at %s.', 'accessibility-guard' ),
            '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>'
        ) . '</p>';

        $html .= '<h3>' . esc_html__( 'Assessment Approach', 'accessibility-guard' ) . '</h3>';
        $html .= '<p>' . esc_html__( 'This website is assessed using the Accessibility Guard plugin, which performs automated WCAG 2.2 scanning and provides remediation guidance.', 'accessibility-guard' ) . '</p>';

        $html .= '<p><em>' . sprintf(
            /* translators: %s: date */
            esc_html__( 'This statement was last updated on %s.', 'accessibility-guard' ),
            esc_html( $date )
        ) . '</em></p>';

        return $html;
    }
}
