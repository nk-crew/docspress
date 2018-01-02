<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Template Loader
 *
 * @class 		DocsPress_Template_Loader
 * @package		docspress
 */
class DocsPress_Template_Loader {
    /**
     * Docs archive page ID
     * @var
     */
    private static $docs_archive_id = 0;

    /**
     * Hook in methods.
     */
    public static function init() {
        self::$docs_archive_id = docspress()->get_option( 'docs_page_id','docspress_settings', false );

        add_filter( 'template_include', array( __CLASS__, 'template_loader' ) );
    }

    /**
     * Load a template.
     *
     * Handles template usage so that we can use our own templates instead of the themes.
     *
     * Templates are in the 'templates' folder. esports looks for theme.
     * overrides in /theme/esports/ by default.
     *
     * @param mixed $template
     * @return string
     */
    public static function template_loader( $template ) {
        if ( is_embed() ) {
            return $template;
        }

        if ( $default_file = self::get_template_loader_default_file() ) {
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
     * Get the default filename for a template.
     *
     * @return string
     */
    private static function get_template_loader_default_file() {
        $default_file = '';

        if ( is_singular( 'docs' ) ) {
            $default_file = 'single.php';

            docspress()->is_single = true;
        } else if ( is_post_type_archive( 'docs' ) || self::$docs_archive_id && is_page( self::$docs_archive_id ) ) {
            $default_file = 'archive.php';

            docspress()->is_archive = true;

            // Add query for page docs
            global $wp_query;
            $args = array(
                'post_type'      => 'docs',
                'posts_per_page' => -1,
                'post_parent'    => 0,
                'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
            );
            $wp_query = new WP_Query($args);
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
        $search_files   = apply_filters( 'docspress_template_loader_files', array(), $default_file );

        if ( is_page_template() ) {
            $search_files[] = get_page_template_slug();
        }

        $search_files[] = '/docspress/' . $default_file;
        return array_unique( $search_files );
    }
}

DocsPress_Template_Loader::init();
