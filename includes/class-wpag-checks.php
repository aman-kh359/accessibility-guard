<?php
/**
 * WCAG 2.2 check definitions.
 *
 * @package WP_Accessibility_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Static data class holding all accessibility check definitions.
 */
class WPAG_Checks {

    /**
     * Get all check definitions.
     *
     * @return array
     */
    public static function get_checks() {
        return array(
            array(
                'id'          => 'missing_alt_text',
                'title'       => __( 'Missing image alt text', 'wp-accessibility-guard' ),
                'wcag'        => '1.1.1',
                'level'       => 'A',
                'severity'    => 'error',
                'description' => __( 'Images must have an alt attribute that describes the image content. Decorative images should use an empty alt attribute (alt="").', 'wp-accessibility-guard' ),
                'auto_fixable' => false,
            ),
            array(
                'id'          => 'empty_links_buttons',
                'title'       => __( 'Empty links or buttons', 'wp-accessibility-guard' ),
                'wcag'        => '2.4.4',
                'level'       => 'A',
                'severity'    => 'error',
                'description' => __( 'Links and buttons must have discernible text content or an accessible label.', 'wp-accessibility-guard' ),
                'auto_fixable' => false,
            ),
            array(
                'id'          => 'color_contrast',
                'title'       => __( 'Insufficient color contrast', 'wp-accessibility-guard' ),
                'wcag'        => '1.4.3',
                'level'       => 'AA',
                'severity'    => 'warning',
                'description' => __( 'Text must have a contrast ratio of at least 4.5:1 for normal text and 3:1 for large text against its background.', 'wp-accessibility-guard' ),
                'auto_fixable' => false,
            ),
            array(
                'id'          => 'heading_hierarchy',
                'title'       => __( 'Skipped heading level', 'wp-accessibility-guard' ),
                'wcag'        => '1.3.1',
                'level'       => 'A',
                'severity'    => 'warning',
                'description' => __( 'Heading levels should not be skipped (e.g., H1 followed by H3 without H2). This helps screen reader users navigate the page structure.', 'wp-accessibility-guard' ),
                'auto_fixable' => false,
            ),
            array(
                'id'          => 'missing_form_labels',
                'title'       => __( 'Missing form field labels', 'wp-accessibility-guard' ),
                'wcag'        => '1.3.1',
                'level'       => 'A',
                'severity'    => 'error',
                'description' => __( 'Form fields (input, select, textarea) must have an associated label element, aria-label, or aria-labelledby attribute.', 'wp-accessibility-guard' ),
                'auto_fixable' => true,
            ),
            array(
                'id'          => 'missing_lang_attr',
                'title'       => __( 'Missing document language', 'wp-accessibility-guard' ),
                'wcag'        => '3.1.1',
                'level'       => 'A',
                'severity'    => 'error',
                'description' => __( 'The HTML element must have a lang attribute specifying the document language.', 'wp-accessibility-guard' ),
                'auto_fixable' => true,
            ),
            array(
                'id'          => 'missing_focus_indicators',
                'title'       => __( 'Removed focus indicators', 'wp-accessibility-guard' ),
                'wcag'        => '2.4.7',
                'level'       => 'AA',
                'severity'    => 'warning',
                'description' => __( 'Focus indicators (outline) must not be removed. Users navigating with keyboards rely on visible focus styles.', 'wp-accessibility-guard' ),
                'auto_fixable' => false,
            ),
            array(
                'id'          => 'missing_landmarks',
                'title'       => __( 'Missing ARIA landmark regions', 'wp-accessibility-guard' ),
                'wcag'        => '1.3.1',
                'level'       => 'A',
                'severity'    => 'warning',
                'description' => __( 'Pages should include landmark regions (main, nav, header, footer) to help assistive technology users navigate.', 'wp-accessibility-guard' ),
                'auto_fixable' => false,
            ),
            array(
                'id'          => 'empty_table_headers',
                'title'       => __( 'Empty table headers', 'wp-accessibility-guard' ),
                'wcag'        => '1.3.1',
                'level'       => 'A',
                'severity'    => 'error',
                'description' => __( 'Table header cells (th) must contain text so screen readers can associate data cells with their headers.', 'wp-accessibility-guard' ),
                'auto_fixable' => false,
            ),
            array(
                'id'          => 'missing_skip_nav',
                'title'       => __( 'Missing skip navigation link', 'wp-accessibility-guard' ),
                'wcag'        => '2.4.1',
                'level'       => 'A',
                'severity'    => 'warning',
                'description' => __( 'A skip navigation link should be the first focusable element so keyboard users can bypass repetitive navigation.', 'wp-accessibility-guard' ),
                'auto_fixable' => true,
            ),
            array(
                'id'          => 'missing_page_title',
                'title'       => __( 'Missing or empty page title', 'wp-accessibility-guard' ),
                'wcag'        => '2.4.2',
                'level'       => 'A',
                'severity'    => 'error',
                'description' => __( 'Every page must have a descriptive title element that identifies the page content.', 'wp-accessibility-guard' ),
                'auto_fixable' => false,
            ),
            array(
                'id'          => 'duplicate_ids',
                'title'       => __( 'Duplicate element IDs', 'wp-accessibility-guard' ),
                'wcag'        => '4.1.1',
                'level'       => 'A',
                'severity'    => 'error',
                'description' => __( 'Element IDs must be unique within a page. Duplicate IDs break label associations and ARIA references.', 'wp-accessibility-guard' ),
                'auto_fixable' => false,
            ),
            array(
                'id'          => 'empty_headings',
                'title'       => __( 'Empty headings', 'wp-accessibility-guard' ),
                'wcag'        => '1.3.1',
                'level'       => 'A',
                'severity'    => 'warning',
                'description' => __( 'Heading elements must contain text content. Empty headings confuse screen reader users navigating by headings.', 'wp-accessibility-guard' ),
                'auto_fixable' => true,
            ),
            array(
                'id'          => 'images_missing_dimensions',
                'title'       => __( 'Images missing width/height', 'wp-accessibility-guard' ),
                'wcag'        => '1.4.4',
                'level'       => 'AA',
                'severity'    => 'notice',
                'description' => __( 'Images should have explicit width and height attributes to prevent layout shift (CLS).', 'wp-accessibility-guard' ),
                'auto_fixable' => false,
            ),
            array(
                'id'          => 'missing_link_text',
                'title'       => __( 'Links without accessible text', 'wp-accessibility-guard' ),
                'wcag'        => '2.4.4',
                'level'       => 'A',
                'severity'    => 'error',
                'description' => __( 'Links must have discernible text, either as text content, aria-label, aria-labelledby, or via an image with alt text.', 'wp-accessibility-guard' ),
                'auto_fixable' => false,
            ),
            array(
                'id'          => 'tabindex_positive',
                'title'       => __( 'Positive tabindex values', 'wp-accessibility-guard' ),
                'wcag'        => '2.4.3',
                'level'       => 'A',
                'severity'    => 'warning',
                'description' => __( 'Avoid tabindex values greater than 0. They disrupt the natural tab order and confuse keyboard users.', 'wp-accessibility-guard' ),
                'auto_fixable' => false,
            ),
            array(
                'id'          => 'auto_playing_media',
                'title'       => __( 'Auto-playing media', 'wp-accessibility-guard' ),
                'wcag'        => '1.4.2',
                'level'       => 'A',
                'severity'    => 'error',
                'description' => __( 'Audio or video must not play automatically. Users must be able to control media playback.', 'wp-accessibility-guard' ),
                'auto_fixable' => false,
            ),
            array(
                'id'          => 'missing_viewport_meta',
                'title'       => __( 'Missing or restrictive viewport meta', 'wp-accessibility-guard' ),
                'wcag'        => '1.4.4',
                'level'       => 'AA',
                'severity'    => 'warning',
                'description' => __( 'The viewport meta tag should be present and must not disable user scaling (user-scalable=no or maximum-scale=1).', 'wp-accessibility-guard' ),
                'auto_fixable' => false,
            ),
            array(
                'id'          => 'inline_styles_a11y',
                'title'       => __( 'Accessibility-affecting inline styles', 'wp-accessibility-guard' ),
                'wcag'        => '1.3.1',
                'level'       => 'A',
                'severity'    => 'notice',
                'description' => __( 'Inline styles that hide content (display:none) without aria-hidden, or set very small font sizes, may create accessibility barriers.', 'wp-accessibility-guard' ),
                'auto_fixable' => false,
            ),
            array(
                'id'          => 'missing_list_structure',
                'title'       => __( 'Navigation without list structure', 'wp-accessibility-guard' ),
                'wcag'        => '1.3.1',
                'level'       => 'A',
                'severity'    => 'notice',
                'description' => __( 'Navigation regions should use list elements (ul/ol) to group links, helping screen readers announce the number of items.', 'wp-accessibility-guard' ),
                'auto_fixable' => false,
            ),
        );
    }

    /**
     * Get a single check by its ID.
     *
     * @param string $id Check ID.
     * @return array|null
     */
    public static function get_check( $id ) {
        foreach ( self::get_checks() as $check ) {
            if ( $check['id'] === $id ) {
                return $check;
            }
        }
        return null;
    }

    /**
     * Get checks filtered by severity.
     *
     * @param string $severity Severity level (error, warning, notice).
     * @return array
     */
    public static function get_checks_by_severity( $severity ) {
        return array_filter(
            self::get_checks(),
            function ( $check ) use ( $severity ) {
                return $check['severity'] === $severity;
            }
        );
    }
}
