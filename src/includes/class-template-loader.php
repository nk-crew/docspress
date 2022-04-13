<?php
/**
 * Template loader.
 *
 * @package @@plugin_name
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Template Loader
 *
 * @class       DocsPress_Template_Loader
 * @package     docspress
 */
class DocsPress_Template_Loader {
    /**
     * Docs archive page ID
     *
     * @var int
     */
    private static $docs_archive_id = 0;

    /**
     * Hook in methods.
     */
    public static function init() {
        self::$docs_archive_id = docspress()->get_option( 'docs_page_id', 'docspress_settings', false );

        add_filter( 'template_include', array( __CLASS__, 'template_loader' ) );
    }

    /**
     * Load a template.
     *
     * Handles template usage so that we can use our own templates instead of the themes.
     *
     * Templates are in the 'templates' folder. docspress looks for theme.
     * overrides in /theme/docspress/ by default.
     *
     * @param string $template - template name.
     * @return string
     */
    public static function template_loader( $template ) {
        if ( is_embed() ) {
            return $template;
        }

        $default_file = self::get_template_loader_default_file();

        if ( $default_file ) {
            /**
             * Filter hook to choose which files to find before DocsPress does it's own logic.
             *
             * @var array
             */
            $search_files = self::get_template_loader_files( $default_file );
            $template     = locate_template( $search_files );

            if ( ! $template ) {
                $template = docspress()->template_path . $default_file;
            }
        }

        return $template;
    }

    /**
     * Checks whether a block template with that name exists.
     *
     * **Note: ** This checks both the `templates` and `block-templates` directories
     * as both conventions should be supported.
     *
     * @since  5.5.0
     * @param string $template_name Template to check.
     * @return boolean
     */
    private static function has_block_template( $template_name ) {
        if ( ! $template_name ) {
            return false;
        }

        $has_template      = false;
        $template_filename = $template_name . '.html';
        // Since Gutenberg 12.1.0, the conventions for block templates directories have changed,
        // we should check both these possible directories for backwards-compatibility.
        $possible_templates_dirs = array( 'templates', 'block-templates' );

        // Combine the possible root directory names with either the template directory
        // or the stylesheet directory for child themes, getting all possible block templates
        // locations combinations.
        $filepath        = DIRECTORY_SEPARATOR . 'templates/fse-templates' . DIRECTORY_SEPARATOR . $template_filename;
        $legacy_filepath = DIRECTORY_SEPARATOR . 'block-templates' . DIRECTORY_SEPARATOR . $template_filename;
        $possible_paths  = array(
            get_stylesheet_directory() . $filepath,
            get_stylesheet_directory() . $legacy_filepath,
            get_template_directory() . $filepath,
            get_template_directory() . $legacy_filepath,
        );

        // Check the first matching one.
        foreach ( $possible_paths as $path ) {
            if ( is_readable( $path ) ) {
                $has_template = true;
                break;
            }
        }

        /**
         * Filters the value of the result of the block template check.
         *
         * @since x.x.x
         *
         * @param boolean $has_template value to be filtered.
         * @param string $template_name The name of the template.
         */
        return (bool) apply_filters( 'docspress_has_block_template', $has_template, $template_name );
    }

    /**
     * Get the default filename for a template.
     *
     * @return string
     */
    private static function get_template_loader_default_file() {
        $default_file = '';

        if ( is_singular( 'docs' ) ) {
            docspress()->is_single = true;

            if ( ! self::has_block_template( 'single-docs' ) ) {
                $default_file = 'single.php';
            }
        } elseif ( ( is_post_type_archive( 'docs' ) || self::$docs_archive_id && is_page( self::$docs_archive_id ) ) ) {
            docspress()->is_archive = true;

            if ( ! self::has_block_template( 'archive-docs' ) ) {
                $default_file = 'archive.php';
            }

            // Add query for page docs.
            global $wp_query;
            $args = array(
                'post_type'      => 'docs',
                'posts_per_page' => -1, // phpcs:ignore
                'post_parent'    => 0,
                'orderby'        => array(
                    'menu_order' => 'ASC',
                    'date'       => 'DESC',
                ),
            );

            // prepare args for search page.
            if ( ! is_admin() && is_search() ) {
                $default_file = 'search.php';

                unset( $args['posts_per_page'] );
                unset( $args['post_parent'] );

                $args['s'] = get_search_query();

                // phpcs:ignore
                $parent = isset( $_GET['child_of'] ) ? sanitize_text_field( wp_unslash( $_GET['child_of'] ) ) : false;
                $parent = intval( $parent );

                // we need to get all docs IDs and the use it in WP_Query as we need get also all childrens.
                if ( $parent ) {
                    $post__in      = array( $parent => $parent );
                    $children_docs = get_pages(
                        array(
                            'child_of'  => $parent,
                            'post_type' => 'docs',
                            'depth'     => -1,
                        )
                    );
                    if ( $children_docs ) {
                        $post__in = array_merge( $post__in, wp_list_pluck( $children_docs, 'ID' ) );
                    }
                    $args['post__in'] = $post__in;
                }
            }

            // make order by term.
            if ( 'archive.php' === $default_file || self::has_block_template( 'archive-docs' ) ) {
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
                        $docs_by_cat[ $cat->slug ] = array();
                    }

                    // get parent docs.
                    $parent_docs = get_pages(
                        array(
                            'post_type'   => 'docs',
                            'parent'      => 0,
                            'sort_column' => 'menu_order',
                        )
                    );
                    if ( $parent_docs ) {
                        // set all doc IDs to array by terms.
                        foreach ( $parent_docs as $doc ) {
                            $term = get_the_terms( $doc, 'docs_category' );

                            if ( $term && ! empty( $term ) ) {
                                $term = $term[0]->slug;
                            } else {
                                $term = 0;
                            }

                            $docs_by_cat[ $term ][] = $doc->ID;
                        }

                        // add posts IDs in post__in.
                        if ( count( $docs_by_cat ) >= 2 ) {
                            $args['post__in'] = array();
                            foreach ( $docs_by_cat as $docs ) {
                                $args['post__in'] = array_merge( $args['post__in'], $docs );
                            }
                            $args['orderby'] = 'post__in';
                        }
                    }
                }
            }

            $wp_query = new WP_Query( $args ); // phpcs:ignore
        }

        return $default_file;
    }

    /**
     * Get an array of filenames to search for a given template.
     *
     * @param  string $default_file The default file name.
     * @return string[]
     */
    private static function get_template_loader_files( $default_file ) {
        $search_files = apply_filters( 'docspress_template_loader_files', array(), $default_file );

        if ( is_page_template() ) {
            $search_files[] = get_page_template_slug();
        }

        $search_files[] = '/docspress/' . $default_file;
        return array_unique( $search_files );
    }
}

DocsPress_Template_Loader::init();
