<?php
/**
 * Docs search template
 *
 * This template can be overridden by copying it to yourtheme/docspress/search.php.
 *
 * @author  nK
 * @package @@plugin_name/Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

docspress()->get_template_part( 'global/wrap-start' );

?>
<header class="page-header">
    <h1 class="page-title">
        <?php
        // translators: %s search query.
        printf( esc_html__( 'Documentation search: "%s"', '@@text_domain' ), esc_html( get_search_query() ) );
        ?>
    </h1>
</header><!-- .page-header -->

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

    <div class="entry-content">
        <div class="docspress-search">
            <?php docspress()->get_template_part( 'search/list', array( 'query' => $GLOBALS['wp_query'] ) ); ?>
        </div>

        <?php
            wp_link_pages(
                array(
                    'before' => '<div class="page-links">' . __( 'Pages:', '@@text_domain' ),
                    'after'  => '</div>',
                )
            );
            ?>
    </div>
</article>

<?php

docspress()->get_template_part( 'global/wrap-end' );
