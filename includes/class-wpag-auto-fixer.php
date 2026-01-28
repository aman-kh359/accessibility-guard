<?php
/**
 * Auto-fixer for common accessibility issues.
 *
 * @package WP_Accessibility_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Applies automatic accessibility fixes to front-end output.
 */
class WPAG_Auto_Fixer {

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
     * Register hooks based on active settings.
     */
    private function register_hooks() {
        if ( is_admin() ) {
            return;
        }

        $settings = get_option( 'wpag_settings', wpag_default_settings() );

        if ( ! empty( $settings['auto_fix_skip_link'] ) ) {
            add_action( 'wp_body_open', array( $this, 'inject_skip_link' ), 1 );
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_skip_link_css' ) );
        }

        if ( ! empty( $settings['auto_fix_lang_attr'] ) ) {
            add_filter( 'language_attributes', array( $this, 'ensure_lang_attr' ) );
        }

        if ( ! empty( $settings['auto_fix_empty_headings'] ) ) {
            add_filter( 'the_content', array( $this, 'fix_empty_headings' ), 999 );
        }

        if ( ! empty( $settings['auto_fix_form_labels'] ) ) {
            add_filter( 'the_content', array( $this, 'fix_form_labels' ), 999 );
        }
    }

    /**
     * Inject skip-to-content link after <body>.
     */
    public function inject_skip_link() {
        // Avoid duplicate if Pojo Accessibility is active.
        if ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'pojo-accessibility/pojo-accessibility.php' ) ) {
            return;
        }

        echo '<a class="wpag-skip-link screen-reader-text" href="#content">'
            . esc_html__( 'Skip to content', 'wp-accessibility-guard' )
            . '</a>';
    }

    /**
     * Enqueue skip link CSS using wp_add_inline_style.
     */
    public function enqueue_skip_link_css() {
        wp_register_style( 'wpag-skip-link', false );
        wp_enqueue_style( 'wpag-skip-link' );
        wp_add_inline_style( 'wpag-skip-link',
            '.wpag-skip-link {
                position: absolute;
                top: -100%;
                left: 0;
                z-index: 999999;
                padding: 8px 16px;
                background: #000;
                color: #fff;
                text-decoration: none;
                font-size: 14px;
                line-height: 1.5;
                clip: rect(1px, 1px, 1px, 1px);
                clip-path: inset(50%);
                overflow: hidden;
                white-space: nowrap;
                width: 1px;
                height: 1px;
            }
            .wpag-skip-link:focus {
                position: fixed;
                top: 6px;
                left: 6px;
                clip: auto;
                clip-path: none;
                width: auto;
                height: auto;
                overflow: visible;
                outline: 2px solid #0073aa;
                outline-offset: 2px;
                border-radius: 3px;
            }'
        );
    }

    /**
     * Ensure the HTML element has a lang attribute.
     *
     * @param string $attributes Existing language attributes string.
     * @return string
     */
    public function ensure_lang_attr( $attributes ) {
        if ( strpos( $attributes, 'lang=' ) !== false ) {
            return $attributes;
        }

        $lang = get_bloginfo( 'language' );
        if ( $lang ) {
            $attributes .= ' lang="' . esc_attr( $lang ) . '"';
        }

        return $attributes;
    }

    /**
     * Remove empty heading elements from content.
     *
     * @param string $content Post content.
     * @return string
     */
    public function fix_empty_headings( $content ) {
        if ( empty( $content ) ) {
            return $content;
        }

        // Remove empty headings: <h1></h1>, <h2>  </h2>, <h3>&nbsp;</h3> etc.
        $content = preg_replace(
            '/<h([1-6])[^>]*>\s*(&nbsp;|\xC2\xA0)?\s*<\/h\1>/i',
            '',
            $content
        );

        return $content;
    }

    /**
     * Add labels to unlabeled form fields in content.
     *
     * @param string $content Post content.
     * @return string
     */
    public function fix_form_labels( $content ) {
        if ( empty( $content ) || strpos( $content, '<input' ) === false && strpos( $content, '<select' ) === false && strpos( $content, '<textarea' ) === false ) {
            return $content;
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors( true );
        $dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="wpag-wrap">' . $content . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $xpath   = new DOMXPath( $dom );
        $fields  = $xpath->query( '//input[not(@type="hidden") and not(@type="submit") and not(@type="button") and not(@type="image") and not(@type="reset")]|//select|//textarea' );
        $changed = false;

        foreach ( $fields as $field ) {
            // Skip if already has label association.
            if ( $field->hasAttribute( 'aria-label' ) || $field->hasAttribute( 'aria-labelledby' ) ) {
                continue;
            }

            $id = $field->getAttribute( 'id' );
            if ( $id ) {
                $safe_id = str_replace( array( '"', "'", '\\' ), '', $id );
                $label   = $xpath->query( '//label[@for="' . $safe_id . '"]' );
                if ( $label->length > 0 ) {
                    continue;
                }
            }

            // Check if wrapped in label.
            $parent = $field->parentNode;
            $in_label = false;
            while ( $parent ) {
                if ( $parent->nodeName === 'label' ) {
                    $in_label = true;
                    break;
                }
                $parent = $parent->parentNode;
            }
            if ( $in_label ) {
                continue;
            }

            if ( $field->hasAttribute( 'title' ) ) {
                continue;
            }

            // Determine label text.
            $label_text = '';
            if ( $field->hasAttribute( 'placeholder' ) ) {
                $label_text = $field->getAttribute( 'placeholder' );
            } elseif ( $field->hasAttribute( 'name' ) ) {
                $label_text = ucwords( str_replace( array( '_', '-', '[]' ), array( ' ', ' ', '' ), $field->getAttribute( 'name' ) ) );
            }

            if ( ! $label_text ) {
                continue;
            }

            // Ensure the field has an ID.
            if ( ! $id ) {
                $id = 'wpag-field-' . wp_rand( 1000, 9999 );
                $field->setAttribute( 'id', $id );
            }

            // Create label element.
            $label_el = $dom->createElement( 'label' );
            $label_el->setAttribute( 'for', $id );
            $label_el->setAttribute( 'class', 'wpag-auto-label screen-reader-text' );
            $label_el->textContent = $label_text;

            $field->parentNode->insertBefore( $label_el, $field );
            $changed = true;
        }

        if ( ! $changed ) {
            return $content;
        }

        // Extract content from wrapper div.
        $wrapper = $dom->getElementById( 'wpag-wrap' );
        if ( ! $wrapper ) {
            return $content;
        }

        $html = '';
        foreach ( $wrapper->childNodes as $child ) {
            $html .= $dom->saveHTML( $child );
        }

        return $html;
    }
}
