<?php
/**
 * Accessibility statement page template.
 *
 * This template can be used by themes to render the accessibility statement.
 * It is not loaded automatically — the statement content is stored as page content.
 *
 * @package WP_Accessibility_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<main id="content" class="wpag-statement-page">
    <article>
        <?php
        while ( have_posts() ) :
            the_post();
            the_content();
        endwhile;
        ?>
    </article>
</main>

<?php
get_footer();
