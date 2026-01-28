<?php
/**
 * WCAG 2.2 Scanner Engine.
 *
 * @package WP_Accessibility_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * DOM-based accessibility scanner.
 */
class WPAG_Scanner {

    /** @var string Raw HTML. */
    private $html;

    /** @var DOMDocument */
    private $dom;

    /** @var DOMXPath */
    private $xpath;

    /** @var string Scanned URL. */
    private $url;

    /** @var int WordPress post ID. */
    private $post_id;

    /** @var array Collected issues. */
    private $issues = array();

    /**
     * Named CSS colors mapped to hex for contrast checking.
     *
     * @var array
     */
    private static $named_colors = array(
        'black'   => '#000000',
        'white'   => '#ffffff',
        'red'     => '#ff0000',
        'green'   => '#008000',
        'blue'    => '#0000ff',
        'yellow'  => '#ffff00',
        'gray'    => '#808080',
        'grey'    => '#808080',
        'silver'  => '#c0c0c0',
        'maroon'  => '#800000',
        'navy'    => '#000080',
        'olive'   => '#808000',
        'purple'  => '#800080',
        'teal'    => '#008080',
        'aqua'    => '#00ffff',
        'fuchsia' => '#ff00ff',
        'lime'    => '#00ff00',
        'orange'  => '#ffa500',
    );

    /**
     * Constructor.
     *
     * @param string $html    Raw HTML content.
     * @param string $url     URL of the page.
     * @param int    $post_id WordPress post ID.
     */
    public function __construct( $html, $url = '', $post_id = 0 ) {
        $this->html    = $html;
        $this->url     = $url;
        $this->post_id = $post_id;

        $this->dom = new DOMDocument();
        libxml_use_internal_errors( true );

        // Prepend UTF-8 charset meta so DOMDocument handles encoding correctly.
        $this->dom->loadHTML(
            '<?xml encoding="UTF-8">' . $html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();

        $this->xpath = new DOMXPath( $this->dom );
    }

    /**
     * Fetch rendered HTML from a URL.
     *
     * @param string $url Page URL.
     * @return string|WP_Error HTML string or error.
     */
    public static function fetch_page_html( $url ) {
        $response = wp_remote_get(
            $url,
            array(
                'timeout'   => 30,
                'sslverify' => ! defined( 'WP_DEBUG' ) || ! WP_DEBUG,
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            return new WP_Error(
                'wpag_fetch_error',
                sprintf(
                    /* translators: 1: HTTP status code, 2: URL */
                    __( 'HTTP %1$d when fetching %2$s', 'accessibility-guard' ),
                    $code,
                    $url
                )
            );
        }

        $body = wp_remote_retrieve_body( $response );
        if ( empty( $body ) ) {
            return new WP_Error( 'wpag_empty_body', __( 'Empty response body.', 'accessibility-guard' ) );
        }

        // Enforce 2 MB limit.
        if ( strlen( $body ) > 2 * 1024 * 1024 ) {
            return new WP_Error( 'wpag_too_large', __( 'Page HTML exceeds 2 MB limit.', 'accessibility-guard' ) );
        }

        return $body;
    }

    /**
     * Run all checks.
     *
     * @param bool $force Skip cache.
     * @return array Issues.
     */
    public function scan( $force = false ) {
        if ( ! $force && $this->post_id ) {
            $cached = get_transient( 'wpag_scan_' . $this->post_id );
            if ( false !== $cached ) {
                $this->issues = $cached;
                return $cached;
            }
        }

        $this->issues = array();

        $this->check_missing_alt_text();
        $this->check_empty_links_buttons();
        $this->check_color_contrast();
        $this->check_heading_hierarchy();
        $this->check_missing_form_labels();
        $this->check_missing_lang_attr();
        $this->check_missing_focus_indicators();
        $this->check_missing_landmarks();
        $this->check_empty_table_headers();
        $this->check_missing_skip_nav();
        $this->check_missing_page_title();
        $this->check_duplicate_ids();
        $this->check_empty_headings();
        $this->check_images_missing_dimensions();
        $this->check_missing_link_text();
        $this->check_tabindex_positive();
        $this->check_auto_playing_media();
        $this->check_missing_viewport_meta();
        $this->check_inline_styles_a11y();
        $this->check_missing_list_structure();

        if ( $this->post_id ) {
            set_transient( 'wpag_scan_' . $this->post_id, $this->issues, DAY_IN_SECONDS );
        }

        return $this->issues;
    }

    /**
     * Get collected issues.
     *
     * @return array
     */
    public function get_issues() {
        return $this->issues;
    }

    /**
     * Get summary counts by severity.
     *
     * @return array
     */
    public function get_summary() {
        $summary = array(
            'error'   => 0,
            'warning' => 0,
            'notice'  => 0,
            'total'   => count( $this->issues ),
        );

        foreach ( $this->issues as $issue ) {
            if ( isset( $summary[ $issue['severity'] ] ) ) {
                $summary[ $issue['severity'] ]++;
            }
        }

        return $summary;
    }

    /**
     * Add an issue to the list.
     *
     * @param string $check_id Check identifier.
     * @param string $message  Human-readable message.
     * @param string $element  HTML snippet (truncated).
     */
    private function add_issue( $check_id, $message, $element = '' ) {
        $check = WPAG_Checks::get_check( $check_id );
        if ( ! $check ) {
            return;
        }

        $this->issues[] = array(
            'check_id' => $check_id,
            'severity' => $check['severity'],
            'wcag'     => $check['wcag'],
            'level'    => $check['level'],
            'message'  => $message,
            'element'  => mb_substr( $element, 0, 500 ),
        );
    }

    /**
     * Get outer HTML of a DOM node (truncated).
     *
     * @param DOMNode $node DOM node.
     * @return string
     */
    private function get_outer_html( $node ) {
        return $this->dom->saveHTML( $node );
    }

    // -------------------------------------------------------------------------
    // Individual Checks
    // -------------------------------------------------------------------------

    /**
     * Check 1: Missing image alt text.
     */
    private function check_missing_alt_text() {
        $images = $this->xpath->query( '//img' );
        foreach ( $images as $img ) {
            if ( ! $img->hasAttribute( 'alt' ) ) {
                // Skip decorative images.
                if ( $img->getAttribute( 'role' ) === 'presentation' || $img->getAttribute( 'aria-hidden' ) === 'true' ) {
                    continue;
                }
                $this->add_issue(
                    'missing_alt_text',
                    __( 'Image is missing the alt attribute.', 'accessibility-guard' ),
                    $this->get_outer_html( $img )
                );
            }
        }
    }

    /**
     * Check 2: Empty links or buttons.
     */
    private function check_empty_links_buttons() {
        // Empty links.
        $links = $this->xpath->query( '//a' );
        foreach ( $links as $link ) {
            if ( $this->is_element_empty( $link ) && ! $this->has_accessible_name( $link ) ) {
                $this->add_issue(
                    'empty_links_buttons',
                    __( 'Link has no text content or accessible name.', 'accessibility-guard' ),
                    $this->get_outer_html( $link )
                );
            }
        }

        // Empty buttons.
        $buttons = $this->xpath->query( '//button' );
        foreach ( $buttons as $btn ) {
            if ( $this->is_element_empty( $btn ) && ! $this->has_accessible_name( $btn ) ) {
                $this->add_issue(
                    'empty_links_buttons',
                    __( 'Button has no text content or accessible name.', 'accessibility-guard' ),
                    $this->get_outer_html( $btn )
                );
            }
        }
    }

    /**
     * Check 3: Color contrast (inline styles only — best effort).
     */
    private function check_color_contrast() {
        $elements = $this->xpath->query( '//*[@style]' );
        foreach ( $elements as $el ) {
            $style = $el->getAttribute( 'style' );

            $fg = $this->extract_css_property( $style, 'color' );
            $bg = $this->extract_css_property( $style, 'background-color' );

            if ( ! $fg || ! $bg ) {
                continue;
            }

            $fg_rgb = $this->parse_color( $fg );
            $bg_rgb = $this->parse_color( $bg );

            if ( ! $fg_rgb || ! $bg_rgb ) {
                continue;
            }

            $ratio = $this->contrast_ratio( $fg_rgb, $bg_rgb );

            // WCAG AA: 4.5:1 for normal text, 3:1 for large text.
            // We use 4.5:1 as default since we can't reliably determine font size.
            if ( $ratio < 4.5 ) {
                $this->add_issue(
                    'color_contrast',
                    sprintf(
                        /* translators: %s: contrast ratio */
                        __( 'Contrast ratio is %s:1 (minimum 4.5:1 required for normal text).', 'accessibility-guard' ),
                        round( $ratio, 2 )
                    ),
                    $this->get_outer_html( $el )
                );
            }
        }
    }

    /**
     * Check 4: Heading hierarchy.
     */
    private function check_heading_hierarchy() {
        $headings = $this->xpath->query( '//h1|//h2|//h3|//h4|//h5|//h6' );

        $prev_level = 0;
        $has_h1     = false;

        foreach ( $headings as $heading ) {
            $tag   = strtolower( $heading->nodeName );
            $level = (int) substr( $tag, 1 );

            if ( $level === 1 ) {
                $has_h1 = true;
            }

            if ( $prev_level > 0 && $level > $prev_level + 1 ) {
                $this->add_issue(
                    'heading_hierarchy',
                    sprintf(
                        /* translators: 1: previous heading tag, 2: current heading tag */
                        __( 'Heading level skipped: H%1$d to H%2$d.', 'accessibility-guard' ),
                        $prev_level,
                        $level
                    ),
                    $this->get_outer_html( $heading )
                );
            }

            $prev_level = $level;
        }

        if ( $headings->length > 0 && ! $has_h1 ) {
            $this->add_issue(
                'heading_hierarchy',
                __( 'Page has headings but is missing an H1 element.', 'accessibility-guard' ),
                ''
            );
        }
    }

    /**
     * Check 5: Missing form labels.
     */
    private function check_missing_form_labels() {
        $fields = $this->xpath->query( '//input[not(@type="hidden") and not(@type="submit") and not(@type="button") and not(@type="image") and not(@type="reset")]|//select|//textarea' );

        foreach ( $fields as $field ) {
            // Check for aria-label or aria-labelledby.
            if ( $field->hasAttribute( 'aria-label' ) || $field->hasAttribute( 'aria-labelledby' ) ) {
                continue;
            }

            // Check for associated label via id.
            $id = $field->getAttribute( 'id' );
            if ( $id ) {
                $label = $this->xpath->query( '//label[@for="' . $this->escape_xpath( $id ) . '"]' );
                if ( $label->length > 0 ) {
                    continue;
                }
            }

            // Check if wrapped in a label.
            $parent = $field->parentNode;
            while ( $parent ) {
                if ( $parent->nodeName === 'label' ) {
                    break;
                }
                $parent = $parent->parentNode;
            }
            if ( $parent && $parent->nodeName === 'label' ) {
                continue;
            }

            // Check for title attribute as fallback.
            if ( $field->hasAttribute( 'title' ) ) {
                continue;
            }

            $this->add_issue(
                'missing_form_labels',
                __( 'Form field has no associated label or accessible name.', 'accessibility-guard' ),
                $this->get_outer_html( $field )
            );
        }
    }

    /**
     * Check 6: Missing document language.
     */
    private function check_missing_lang_attr() {
        $html = $this->xpath->query( '//html' );
        if ( $html->length === 0 ) {
            return;
        }

        $el = $html->item( 0 );
        if ( ! $el->hasAttribute( 'lang' ) && ! $el->hasAttribute( 'xml:lang' ) ) {
            $this->add_issue(
                'missing_lang_attr',
                __( 'The HTML element is missing a lang attribute.', 'accessibility-guard' ),
                '<html>'
            );
        }
    }

    /**
     * Check 7: Removed focus indicators.
     */
    private function check_missing_focus_indicators() {
        // Check <style> blocks for outline:none or outline:0 on :focus.
        $styles = $this->xpath->query( '//style' );
        foreach ( $styles as $style_el ) {
            $css = $style_el->textContent;
            if ( preg_match( '/:focus\s*\{[^}]*(outline\s*:\s*(none|0))/i', $css, $matches ) ) {
                $this->add_issue(
                    'missing_focus_indicators',
                    __( 'CSS rule removes focus outline. Keyboard users need visible focus indicators.', 'accessibility-guard' ),
                    $matches[0]
                );
            }
        }

        // Check inline styles.
        $elements = $this->xpath->query( '//*[contains(@style, "outline")]' );
        foreach ( $elements as $el ) {
            $style = $el->getAttribute( 'style' );
            if ( preg_match( '/outline\s*:\s*(none|0)\b/i', $style ) ) {
                $this->add_issue(
                    'missing_focus_indicators',
                    __( 'Inline style removes outline. This may hide focus indicators.', 'accessibility-guard' ),
                    $this->get_outer_html( $el )
                );
            }
        }
    }

    /**
     * Check 8: Missing ARIA landmark regions.
     */
    private function check_missing_landmarks() {
        $landmarks = array(
            'main'        => array( '//main', '//*[@role="main"]' ),
            'navigation'  => array( '//nav', '//*[@role="navigation"]' ),
            'banner'      => array( '//header', '//*[@role="banner"]' ),
            'contentinfo' => array( '//footer', '//*[@role="contentinfo"]' ),
        );

        foreach ( $landmarks as $name => $queries ) {
            $found = false;
            foreach ( $queries as $query ) {
                $result = $this->xpath->query( $query );
                if ( $result->length > 0 ) {
                    $found = true;
                    break;
                }
            }

            if ( ! $found ) {
                $this->add_issue(
                    'missing_landmarks',
                    sprintf(
                        /* translators: %s: landmark region name */
                        __( 'Missing "%s" landmark region.', 'accessibility-guard' ),
                        $name
                    ),
                    ''
                );
            }
        }
    }

    /**
     * Check 9: Empty table headers.
     */
    private function check_empty_table_headers() {
        $ths = $this->xpath->query( '//th' );
        foreach ( $ths as $th ) {
            if ( trim( $th->textContent ) === '' ) {
                $this->add_issue(
                    'empty_table_headers',
                    __( 'Table header cell is empty.', 'accessibility-guard' ),
                    $this->get_outer_html( $th )
                );
            }
        }
    }

    /**
     * Check 10: Missing skip navigation.
     */
    private function check_missing_skip_nav() {
        $body = $this->xpath->query( '//body' );
        if ( $body->length === 0 ) {
            return;
        }

        $body_el  = $body->item( 0 );
        $children = $body_el->childNodes;
        $found    = false;

        $checked = 0;
        foreach ( $children as $child ) {
            if ( $child->nodeType !== XML_ELEMENT_NODE ) {
                continue;
            }

            $checked++;
            if ( $checked > 10 ) {
                break;
            }

            if ( $this->is_skip_link( $child ) ) {
                $found = true;
                break;
            }

            // Check first-level children of this element too.
            if ( $child->hasChildNodes() ) {
                foreach ( $child->childNodes as $grandchild ) {
                    if ( $grandchild->nodeType === XML_ELEMENT_NODE && $this->is_skip_link( $grandchild ) ) {
                        $found = true;
                        break 2;
                    }
                }
            }
        }

        if ( ! $found ) {
            $this->add_issue(
                'missing_skip_nav',
                __( 'No skip navigation link found near the top of the page.', 'accessibility-guard' ),
                ''
            );
        }
    }

    /**
     * Check 11: Missing page title.
     */
    private function check_missing_page_title() {
        $title = $this->xpath->query( '//title' );
        if ( $title->length === 0 || trim( $title->item( 0 )->textContent ) === '' ) {
            $this->add_issue(
                'missing_page_title',
                __( 'Page is missing a title element or the title is empty.', 'accessibility-guard' ),
                ''
            );
        }
    }

    /**
     * Check 12: Duplicate IDs.
     */
    private function check_duplicate_ids() {
        $elements = $this->xpath->query( '//*[@id]' );
        $ids      = array();

        foreach ( $elements as $el ) {
            $id = $el->getAttribute( 'id' );
            if ( $id === '' ) {
                continue;
            }
            if ( isset( $ids[ $id ] ) ) {
                $this->add_issue(
                    'duplicate_ids',
                    sprintf(
                        /* translators: %s: the duplicate ID value */
                        __( 'Duplicate ID found: "%s".', 'accessibility-guard' ),
                        $id
                    ),
                    $this->get_outer_html( $el )
                );
            }
            $ids[ $id ] = true;
        }
    }

    /**
     * Check 13: Empty headings.
     */
    private function check_empty_headings() {
        for ( $i = 1; $i <= 6; $i++ ) {
            $headings = $this->xpath->query( "//h{$i}" );
            foreach ( $headings as $heading ) {
                if ( trim( $heading->textContent ) === '' ) {
                    $this->add_issue(
                        'empty_headings',
                        sprintf(
                            /* translators: %s: heading tag name */
                            __( 'Empty %s element found.', 'accessibility-guard' ),
                            'H' . $i
                        ),
                        $this->get_outer_html( $heading )
                    );
                }
            }
        }
    }

    /**
     * Check 14: Images missing width/height.
     */
    private function check_images_missing_dimensions() {
        $images = $this->xpath->query( '//img' );
        foreach ( $images as $img ) {
            if ( ! $img->hasAttribute( 'width' ) || ! $img->hasAttribute( 'height' ) ) {
                $this->add_issue(
                    'images_missing_dimensions',
                    __( 'Image is missing explicit width and/or height attributes.', 'accessibility-guard' ),
                    $this->get_outer_html( $img )
                );
            }
        }
    }

    /**
     * Check 15: Missing link text.
     */
    private function check_missing_link_text() {
        $links = $this->xpath->query( '//a' );
        foreach ( $links as $link ) {
            $text = trim( $link->textContent );
            if ( $text !== '' ) {
                continue;
            }

            // Check for aria-label / aria-labelledby.
            if ( $link->hasAttribute( 'aria-label' ) || $link->hasAttribute( 'aria-labelledby' ) ) {
                continue;
            }

            // Check for image with alt inside the link.
            $imgs = $this->xpath->query( './/img[@alt]', $link );
            if ( $imgs->length > 0 ) {
                $alt = trim( $imgs->item( 0 )->getAttribute( 'alt' ) );
                if ( $alt !== '' ) {
                    continue;
                }
            }

            // Check for title attribute.
            if ( $link->hasAttribute( 'title' ) && trim( $link->getAttribute( 'title' ) ) !== '' ) {
                continue;
            }

            $this->add_issue(
                'missing_link_text',
                __( 'Link has no accessible text content.', 'accessibility-guard' ),
                $this->get_outer_html( $link )
            );
        }
    }

    /**
     * Check 16: Positive tabindex.
     */
    private function check_tabindex_positive() {
        $elements = $this->xpath->query( '//*[@tabindex]' );
        foreach ( $elements as $el ) {
            $val = (int) $el->getAttribute( 'tabindex' );
            if ( $val > 0 ) {
                $this->add_issue(
                    'tabindex_positive',
                    sprintf(
                        /* translators: %d: tabindex value */
                        __( 'Element has tabindex="%d". Positive tabindex disrupts natural tab order.', 'accessibility-guard' ),
                        $val
                    ),
                    $this->get_outer_html( $el )
                );
            }
        }
    }

    /**
     * Check 17: Auto-playing media.
     */
    private function check_auto_playing_media() {
        $media = $this->xpath->query( '//video[@autoplay]|//audio[@autoplay]' );
        foreach ( $media as $el ) {
            $this->add_issue(
                'auto_playing_media',
                sprintf(
                    /* translators: %s: element tag name */
                    __( '<%s> has autoplay attribute. Users must control media playback.', 'accessibility-guard' ),
                    $el->nodeName
                ),
                $this->get_outer_html( $el )
            );
        }

        // Check iframes for autoplay parameter.
        $iframes = $this->xpath->query( '//iframe[contains(@src, "autoplay=1") or contains(@src, "autoplay=true")]' );
        foreach ( $iframes as $iframe ) {
            $this->add_issue(
                'auto_playing_media',
                __( 'Iframe source includes autoplay parameter.', 'accessibility-guard' ),
                $this->get_outer_html( $iframe )
            );
        }
    }

    /**
     * Check 18: Missing or restrictive viewport meta.
     */
    private function check_missing_viewport_meta() {
        $viewport = $this->xpath->query( '//meta[@name="viewport"]' );

        if ( $viewport->length === 0 ) {
            $this->add_issue(
                'missing_viewport_meta',
                __( 'Page is missing a viewport meta tag.', 'accessibility-guard' ),
                ''
            );
            return;
        }

        $content = $viewport->item( 0 )->getAttribute( 'content' );
        if ( preg_match( '/user-scalable\s*=\s*no/i', $content ) ) {
            $this->add_issue(
                'missing_viewport_meta',
                __( 'Viewport meta disables user scaling (user-scalable=no).', 'accessibility-guard' ),
                $this->get_outer_html( $viewport->item( 0 ) )
            );
        }

        if ( preg_match( '/maximum-scale\s*=\s*1(\.0)?(\s|;|,|$)/i', $content ) ) {
            $this->add_issue(
                'missing_viewport_meta',
                __( 'Viewport meta restricts scaling to maximum-scale=1.', 'accessibility-guard' ),
                $this->get_outer_html( $viewport->item( 0 ) )
            );
        }
    }

    /**
     * Check 19: Inline styles affecting accessibility.
     */
    private function check_inline_styles_a11y() {
        $elements = $this->xpath->query( '//*[@style]' );
        foreach ( $elements as $el ) {
            $style = $el->getAttribute( 'style' );

            // display:none without aria-hidden.
            if ( preg_match( '/display\s*:\s*none/i', $style ) && $el->getAttribute( 'aria-hidden' ) !== 'true' ) {
                // Skip common patterns like dropdowns.
                $tag = strtolower( $el->nodeName );
                if ( in_array( $tag, array( 'script', 'template', 'noscript' ), true ) ) {
                    continue;
                }

                $text = trim( $el->textContent );
                if ( $text !== '' ) {
                    $this->add_issue(
                        'inline_styles_a11y',
                        __( 'Element with text content is hidden via display:none without aria-hidden="true".', 'accessibility-guard' ),
                        $this->get_outer_html( $el )
                    );
                }
            }

            // Very small font size.
            if ( preg_match( '/font-size\s*:\s*(\d+)(px|pt)/i', $style, $matches ) ) {
                $size = (float) $matches[1];
                $unit = strtolower( $matches[2] );
                if ( ( $unit === 'px' && $size < 10 ) || ( $unit === 'pt' && $size < 8 ) ) {
                    $this->add_issue(
                        'inline_styles_a11y',
                        sprintf(
                            /* translators: %s: font-size value */
                            __( 'Very small font size (%s) may be unreadable for users with low vision.', 'accessibility-guard' ),
                            $matches[0]
                        ),
                        $this->get_outer_html( $el )
                    );
                }
            }
        }
    }

    /**
     * Check 20: Navigation without list structure.
     */
    private function check_missing_list_structure() {
        $navs = $this->xpath->query( '//nav|//*[@role="navigation"]' );
        foreach ( $navs as $nav ) {
            $lists = $this->xpath->query( './/ul|.//ol', $nav );
            $links = $this->xpath->query( './/a', $nav );

            if ( $links->length > 1 && $lists->length === 0 ) {
                $this->add_issue(
                    'missing_list_structure',
                    __( 'Navigation contains multiple links but no list (ul/ol) structure.', 'accessibility-guard' ),
                    '<nav>...' . $links->length . ' links...</nav>'
                );
            }
        }
    }

    // -------------------------------------------------------------------------
    // Helper Methods
    // -------------------------------------------------------------------------

    /**
     * Check if an element has no meaningful text content.
     *
     * @param DOMNode $node DOM node.
     * @return bool
     */
    private function is_element_empty( $node ) {
        return trim( $node->textContent ) === '';
    }

    /**
     * Check if an element has an accessible name via ARIA or image alt.
     *
     * @param DOMNode $node DOM node.
     * @return bool
     */
    private function has_accessible_name( $node ) {
        if ( $node->hasAttribute( 'aria-label' ) && trim( $node->getAttribute( 'aria-label' ) ) !== '' ) {
            return true;
        }
        if ( $node->hasAttribute( 'aria-labelledby' ) ) {
            return true;
        }
        if ( $node->hasAttribute( 'title' ) && trim( $node->getAttribute( 'title' ) ) !== '' ) {
            return true;
        }

        // Check for image with alt text inside.
        $imgs = $this->xpath->query( './/img[@alt]', $node );
        foreach ( $imgs as $img ) {
            if ( trim( $img->getAttribute( 'alt' ) ) !== '' ) {
                return true;
            }
        }

        // Check for SVG with title.
        $svgs = $this->xpath->query( './/svg', $node );
        foreach ( $svgs as $svg ) {
            $title = $this->xpath->query( './/title', $svg );
            if ( $title->length > 0 && trim( $title->item( 0 )->textContent ) !== '' ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a node is a skip link.
     *
     * @param DOMNode $node DOM node.
     * @return bool
     */
    private function is_skip_link( $node ) {
        if ( strtolower( $node->nodeName ) !== 'a' ) {
            return false;
        }
        $href = $node->getAttribute( 'href' );
        $text = strtolower( trim( $node->textContent ) );

        return ( strpos( $href, '#' ) === 0 && strpos( $text, 'skip' ) !== false );
    }

    /**
     * Extract a CSS property value from an inline style string.
     *
     * @param string $style    Inline style string.
     * @param string $property CSS property name.
     * @return string|null
     */
    private function extract_css_property( $style, $property ) {
        $pattern = '/(^|;)\s*' . preg_quote( $property, '/' ) . '\s*:\s*([^;]+)/i';
        if ( preg_match( $pattern, $style, $matches ) ) {
            return trim( $matches[2] );
        }
        return null;
    }

    /**
     * Parse a CSS color value to an RGB array.
     *
     * @param string $color CSS color value (hex, rgb, named).
     * @return array|null Array of [r, g, b] or null.
     */
    private function parse_color( $color ) {
        $color = strtolower( trim( $color ) );

        // Named color.
        if ( isset( self::$named_colors[ $color ] ) ) {
            $color = self::$named_colors[ $color ];
        }

        // Hex (#RGB or #RRGGBB).
        if ( preg_match( '/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $color, $m ) ) {
            $hex = $m[1];
            if ( strlen( $hex ) === 3 ) {
                $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
            }
            return array(
                hexdec( substr( $hex, 0, 2 ) ),
                hexdec( substr( $hex, 2, 2 ) ),
                hexdec( substr( $hex, 4, 2 ) ),
            );
        }

        // rgb(r, g, b).
        if ( preg_match( '/rgb\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)/', $color, $m ) ) {
            return array( (int) $m[1], (int) $m[2], (int) $m[3] );
        }

        return null;
    }

    /**
     * Calculate relative luminance per WCAG formula.
     *
     * @param array $rgb RGB array.
     * @return float
     */
    private function relative_luminance( $rgb ) {
        $channels = array();
        foreach ( $rgb as $val ) {
            $s = $val / 255.0;
            $channels[] = ( $s <= 0.03928 ) ? ( $s / 12.92 ) : pow( ( $s + 0.055 ) / 1.055, 2.4 );
        }
        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    /**
     * Calculate contrast ratio between two RGB colors.
     *
     * @param array $fg Foreground RGB.
     * @param array $bg Background RGB.
     * @return float
     */
    private function contrast_ratio( $fg, $bg ) {
        $l1 = $this->relative_luminance( $fg );
        $l2 = $this->relative_luminance( $bg );

        $lighter = max( $l1, $l2 );
        $darker  = min( $l1, $l2 );

        return ( $lighter + 0.05 ) / ( $darker + 0.05 );
    }

    /**
     * Escape a string for safe use inside XPath queries.
     *
     * @param string $value Raw value.
     * @return string Escaped value.
     */
    private function escape_xpath( $value ) {
        return str_replace( array( '"', "'", '\\' ), '', $value );
    }
}
