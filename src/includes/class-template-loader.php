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

        add_filter( 'template_include', array( __CLASS__, 'wp_head' ), 1 );
        add_filter( 'template_include', array( __CLASS__, 'template_loader' ) );
        add_action( 'rest_api_init', array( __CLASS__, 'register_search_route' ) );
    }

    /**
     * Register the live documentation search route.
     */
    public static function register_search_route() {
        register_rest_route(
            'docspress/v1',
            '/search',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( __CLASS__, 'render_live_search' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    's'        => array(
                        'type'              => 'string',
                        'required'          => true,
                        'validate_callback' => static function ( $value ) {
                            return is_string( $value ) && '' !== trim( $value ) && strlen( wp_slash( $value ) ) <= 1600;
                        },
                    ),
                    'child_of' => array(
                        'type'    => 'integer',
                        'minimum' => 0,
                        'default' => 0,
                    ),
                ),
            )
        );
    }

    /**
     * Render the results list for live search.
     *
     * @param WP_REST_Request $request Search request.
     * @return string Search results HTML.
     */
    public static function render_live_search( $request ) {
        $query = new WP_Query( self::get_search_args( $request['s'], $request['child_of'] ) );

        ob_start();
        docspress()->get_template_part( 'search/list', array( 'query' => $query ) );
        wp_reset_postdata();

        return ob_get_clean();
    }

    /**
     * Build the shared documentation search query.
     *
     * @param string $search Search terms.
     * @param int    $root_id Root document ID.
     * @return array Search query arguments.
     */
    private static function get_search_args( $search, $root_id ) {
        $args = array(
            'post_type' => 'docs',
            's'         => wp_slash( $search ),
            'orderby'   => array(
                'menu_order' => 'ASC',
                'date'       => 'DESC',
            ),
        );

        if ( $root_id ) {
            $children = get_pages(
                array(
                    'child_of'  => $root_id,
                    'post_type' => 'docs',
                    'depth'     => -1,
                )
            );

            $args['post__in'] = array_merge( array( $root_id ), wp_list_pluck( (array) $children, 'ID' ) );
        }

        return $args;
    }

    /**
     * A simple fix for archive template max num pages.
     * Primarily it is used to prevent Yoast to displaying paged meta tags rel="next" and rel="prev"
     *
     * @param string $template - template name.
     * @return string
     */
    public static function wp_head( $template ) {
        if ( is_post_type_archive( 'docs' ) || ( self::$docs_archive_id && is_page( self::$docs_archive_id ) ) ) {
            global $wp_query;

            $wp_query->max_num_pages = 0;
        }

        return $template;
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
        $filepath        = DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $template_filename;
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
        } elseif ( is_post_type_archive( 'docs' ) || ( self::$docs_archive_id && is_page( self::$docs_archive_id ) ) ) {
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

                // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $parent = isset( $_GET['child_of'] ) && is_scalar( $_GET['child_of'] ) ? (int) $_GET['child_of'] : 0;
                $args   = self::get_search_args( get_search_query( false ), $parent );
            }

            // make order by term.
            if ( 'search.php' !== $default_file ) {
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
