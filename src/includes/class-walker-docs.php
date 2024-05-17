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
     * Show parents option.
     *
     * @var bool
     */
    public static $show_parents = false;

    /**
     * Current term name.
     *
     * @var string
     */
    public static $current_term = '';

    /**
     * Replace the title to the custom one from meta data.
     *
     * @param string $title - title.
     * @param int    $post_id - post ID.
     *
     * @return string
     */
    public function replace_title( $title, $post_id ) {
        $custom_title = (string) get_post_meta( $post_id, 'nav_title', true );

        if ( $custom_title ) {
            $title = $custom_title;
        }

        return $title;
    }

    /**
     * Elements walk.
     *
     * @param array   $elements - elements in walker.
     * @param integer $max_depth - max depth for walk.
     * @param mixed   ...$args - other args.
     *
     * @return string
     */
    public function walk( $elements, $max_depth, ...$args ) {
        self::$show_parents = docspress()->get_option( 'sidebar_show_nav_parents', 'docspress_single', false );

        add_filter( 'the_title', array( $this, 'replace_title' ), 10, 2 );

        // Order by terms.
        if ( self::$show_parents ) {
            $categories = get_terms(
                array(
                    'taxonomy'   => 'docs_category',
                    'hide_empty' => false,
                )
            );

            // we need to make query and loop over items and sort it by term.
            if ( ! empty( $categories ) ) {
                $docs_by_cat = array(
                    0 => array(),
                );

                // get all available terms in array.
                foreach ( $categories as $cat ) {
                    $docs_by_cat[ $cat->name ] = array();
                }

                if ( $elements ) {
                    // set all doc IDs to array by terms.
                    foreach ( $elements as &$el ) {
                        $term = $el->post_parent ? false : get_the_terms( $el, 'docs_category' );

                        if ( $term && ! empty( $term ) ) {
                            $el->doc_cat_name = $term[0]->name;

                            $term = $el->doc_cat_name;
                        } else {
                            $term = 0;
                        }

                        $docs_by_cat[ $term ][] = $el;
                    }

                    $overwrite_elements = array();

                    foreach ( $docs_by_cat as $inner_docs ) {
                        foreach ( $inner_docs as $doc ) {
                            $overwrite_elements[] = $doc;
                        }
                    }

                    $elements = $overwrite_elements;
                }
            }
        }

        $result = parent::walk( $elements, $max_depth, ...$args );

        remove_filter( 'the_title', array( $this, 'replace_title' ) );

        return $result;
    }

    /**
     * Start level.
     *
     * @param string  $output - output.
     * @param integer $depth - depth.
     * @param array   $args - arguments.
     */
    public function start_lvl( &$output, $depth = 0, $args = array() ) {
        $indent  = str_repeat( "\t", $depth );
        $output .= "\n$indent<ul class='children'>\n";
    }

    /**
     * Start element.
     *
     * @param string  $output - output.
     * @param object  $page - page data.
     * @param integer $depth - depth.
     * @param array   $args - arguments.
     * @param integer $current_page - current page id.
     */
    public function start_el( &$output, $page, $depth = 0, $args = array(), $current_page = 0 ) {
        if ( 0 === $depth ) {
            self::$parent_item = $page;
        }

        if ( $page->ID === $current_page ) {
            self::$parent_item_class = 'current_page_item';
        } else {
            self::$parent_item_class = '';
        }

        // Add the number of childrens.
        $show_number_childrens = isset( $args['pages_with_children'][ $page->ID ] ) && docspress()->get_option( 'sidebar_show_nav_number_of_childs', 'docspress_single', true );
        if ( $show_number_childrens ) {
            $childs             = get_pages(
                array(
                    'child_of'  => $page->ID,
                    'post_type' => $page->post_type,
                )
            );
            $count              = count( $childs );
            $args['link_after'] = ( isset( $args['link_after'] ) ? $args['link_after'] : '' ) . ' <sup>[' . $count . ']</sup>';
        }

        // Add category label.
        if ( self::$show_parents && 0 === $depth && isset( $page->doc_cat_name ) && self::$current_term !== $page->doc_cat_name ) {
            self::$current_term = $page->doc_cat_name;

            $output .= '<li class="docspress-nav-list-category">' . $page->doc_cat_name . '</li>';
        }

        parent::start_el( $output, $page, $depth, $args, $current_page );
    }
}
