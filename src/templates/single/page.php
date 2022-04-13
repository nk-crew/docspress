<?php
/**
 * Single docs page template
 *
 * This template can be overridden by copying it to yourtheme/docspress/single/page.php.
 *
 * @author  nK
 * @package @@plugin_name/Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

while ( have_posts() ) :
    the_post(); ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class( 'docspress-single' . ( docspress()->get_option( 'ajax', 'docspress_single', true ) ? ' docspress-single-ajax' : '' ) ); ?>>

        <?php docspress()->get_template_part( 'single/sidebar' ); ?>

        <div class="docspress-single-content">
            <?php
            docspress()->get_template_part( 'single/content-breadcrumbs' );

            docspress()->get_template_part( 'single/content-title' );
            ?>

            <div class="entry-content">
                <?php
                the_content();

                wp_link_pages(
                    array(
                        'before' => '<div class="page-links">' . esc_html__( 'Pages:', '@@text_domain' ),
                        'after'  => '</div>',
                    )
                );

                docspress()->get_template_part( 'single/content-articles' );
                ?>
            </div><!-- .entry-content -->

            <?php

            docspress()->get_template_part( 'single/footer' );

            docspress()->get_template_part( 'single/adjacent-links' );

            docspress()->get_template_part( 'single/feedback' );

            docspress()->get_template_part( 'single/feedback-suggestion' );

            if ( docspress()->get_option( 'show_comments', 'docspress_single', true ) ) {
                docspress()->get_template_part( 'single/comments' );
            }

            ?>
        </div><!-- .docspress-single-content -->
    </article><!-- #post-## -->
    <?php

endwhile;
