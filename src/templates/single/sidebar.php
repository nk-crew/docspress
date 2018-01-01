<?php
/**
 * Single docs sidebar template
 *
 * This template can be overridden by copying it to yourtheme/docspress/single/sidebar.php.
 *
 * @author  nK
 * @package DocsPress/Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>

<div class="docspress-single-sidebar">
    <?php
    $nav_list = wp_list_pages( array(
        'title_li'  => '',
        'order'     => 'menu_order',
        'child_of'  => docspress()->get_current_doc_id(),
        'echo'      => false,
        'post_type' => 'docs',
        'walker'    => new DocsPress_Walker_Docs(),
    ) );
    ?>

    <?php if ($nav_list) {
        $show_childs = docspress()->get_option( 'sidebar_show_nav_childs', 'docspress_single', false );
        ?>
        <ul class="docspress-nav-list<?php echo ($show_childs ? ' docspress-nav-list-show-childs' : ''); ?>">
            <?php echo $nav_list; ?>
        </ul>
    <?php } ?>
</div>