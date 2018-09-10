<?php
/**
 * Docs archive template
 *
 * This template can be overridden by copying it to yourtheme/docspress/archive.php.
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
<?php docspress()->get_template_part( 'archive/title' ); ?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

    <div class="entry-content">
        <?php docspress()->get_template_part( 'archive/description' ); ?>

        <div class="docspress-archive">
            <ul class="docspress-archive-list">
                <?php
                $current_term = false;

                if ( have_posts() ) :
                    while ( have_posts() ) :
                        the_post();

                        $terms = wp_get_post_terms( get_the_ID(), 'docs_category' );
                        if (
                            $terms &&
                            ! empty( $terms ) &&
                            isset( $terms[0]->name ) &&
                            $current_term !== $terms[0]->name
                        ) {
                            $current_term = $terms[0]->name;
                            ?>
                            <li class="docspress-archive-list-category">
                                <?php echo esc_html( $terms[0]->name ); ?>
                            </li>
                            <?php
                        }

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
