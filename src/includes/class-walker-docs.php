<?php
/**
 * Custom page walker for docs.
 *
 * @package @@plugin_name
 */

/**
 * DocsPress Docs Walker
 */
class DocsPress_Walker_Docs extends Walker_Page {
    /**
     * Parent doc.
     *
     * @var mixed
     */
    public static $parent_item = false;

    /**
     * Parent doc class.
     *
     * @var string
     */
    public static $parent_item_class = '';

    /**
     * Start level.
     *
     * @param string  $output - output.
     * @param integer $depth - depth.
     * @param array   $args - arguments.
     */
    public function start_lvl( &$output, $depth = 0, $args = array() ) {
        $indent = str_repeat( "\t", $depth );
        $output .= "\n$indent<ul class='children'>\n";

        if ( $args['has_children'] && 0 === $depth ) {
            $classes = array( 'page_item', 'page-item-' . self::$parent_item->ID );

            if ( self::$parent_item_class ) {
                $classes[] = self::$parent_item_class;
            }
        }
    }

    /**
     * Start element.
     *
     * @param string  $output - output.
     * @param int     $page - page id.
     * @param integer $depth - depth.
     * @param array   $args - arguments.
     * @param integer $current_page - current page id.
     */
    public function start_el( &$output, $page, $depth = 0, $args = array(), $current_page = 0 ) {

        if ( 0 === $depth ) {
            self::$parent_item = $page;
        }

        if ( $page->ID == $current_page ) {
            self::$parent_item_class = 'current_page_item';
        } else {
            self::$parent_item_class = '';
        }

        // add the number of childrens.
        $show_number_childrens = isset( $args['pages_with_children'][ $page->ID ] ) && docspress()->get_option( 'sidebar_show_nav_number_of_childs', 'docspress_single', true );
        if ( $show_number_childrens ) {
            $childs = get_pages(
                array(
                    'child_of' => $page->ID,
                    'post_type' => $page->post_type,
                )
            );
            $count = count( $childs );
            $args['link_after'] = ( isset( $args['link_after'] ) ? $args['link_after'] : '' ) . ' <sup>[' . $count . ']</sup>';
        }

        parent::start_el( $output, $page, $depth, $args, $current_page );
    }
}
