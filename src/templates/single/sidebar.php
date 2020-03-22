<?php
/**
 * Single docs sidebar template
 *
 * This template can be overridden by copying it to yourtheme/docspress/single/sidebar.php.
 *
 * @author  nK
 * @package @@plugin_name/Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// phpcs:ignore
$show_parents = docspress()->get_option( 'sidebar_show_nav_parents', 'docspress_single', false );

?>

<div class="docspress-single-sidebar">
    <div class="docspress-single-sidebar-wrap">
        <?php if ( docspress()->get_option( 'sidebar_show_search', 'docspress_single', true ) ) : ?>
            <form role="search" method="get" class="docspress-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                <input type="search" class="docspress-search-field" placeholder="<?php echo esc_attr__( 'Type to search', '@@text_domain' ); ?>" value="<?php echo get_search_query(); ?>" name="s" autocomplete="off">
                <input type="hidden" name="post_type" value="docs">
                <?php if ( ! $show_parents ) : ?>
                    <input type="hidden" name="child_of" value="<?php echo esc_attr( docspress()->get_current_doc_id() ); ?>">
                <?php endif; ?>
            </form>
            <div class="docspress-search-form-result"></div>
        <?php endif; ?>

        <?php
        // phpcs:ignore
        $nav_list = wp_list_pages(
            array(
                'title_li'  => '',
                'order'     => 'menu_order',
                'child_of'  => $show_parents ? 0 : docspress()->get_current_doc_id(),
                'echo'      => false,
                'post_type' => 'docs',
                'walker'    => new DocsPress_Walker_Docs(),
            )
        );
        if ( $nav_list ) {
            // phpcs:ignore
            $show_childs = docspress()->get_option( 'sidebar_show_nav_childs', 'docspress_single', false );
            ?>
            <ul class="docspress-nav-list<?php echo ( $show_childs ? ' docspress-nav-list-show-childs' : '' ); ?>">
                <?php
                // phpcs:ignore
                echo $nav_list;
                ?>
            </ul>
        <?php } ?>
    </div>
</div>
