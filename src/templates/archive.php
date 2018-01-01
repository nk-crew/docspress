<?php
/**
 * Docs archive template
 *
 * This template can be overridden by copying it to yourtheme/docspress/archive.php.
 *
 * @author  nK
 * @package DocsPress/Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

docspress()->get_template_part( 'global/wrap-start' );

?>
<?php docspress()->get_template_part( 'archive/title' ); ?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

    <div class="entry-content">
        <?php docspress()->get_template_part( 'archive/description' ); ?>

        <div class="docspress-archive">
            <ul class="docspress-archive-list">
                <?php
                if ( have_posts() ) :
                    while ( have_posts() ) : the_post();
                        ?>
                        <li class="docspress-archive-list-item">
                            <?php docspress()->get_template_part( 'archive/loop-title' ); ?>

                            <?php docspress()->get_template_part( 'archive/loop-articles' ); ?>
                        </li>
                        <?php
                    endwhile;
                endif;
                ?>
            </ul>
        </div>

        <?php
            wp_link_pages( array(
                'before' => '<div class="page-links">' . __( 'Pages:', DOCSPRESS_DOMAIN ),
                'after'  => '</div>',
            ) );
        ?>
    </div>
</article>

<?php

docspress()->get_template_part( 'global/wrap-end' );
