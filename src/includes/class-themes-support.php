<?php
/**
 * Themes support.
 *
 * @package @@plugin_name
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Themes Support.
 *
 * @class       DocsPress_Themes_Support
 * @package     docspress
 */
class DocsPress_Themes_Support {

    /**
     * Construct.
     */
    public function __construct() {
        add_action( 'body_class', array( $this, 'add_theme_body_class' ), 1 );
        add_action( 'admin_body_class', array( $this, 'add_theme_body_class' ), 1 );
    }

    /**
     * Add body classes to the frontend and within admin.
     *
     * @param string|array $classes Array or string of CSS classnames.
     * @return string|array Modified classnames.
     */
    public function add_theme_body_class( $classes ) {
        $class = 'theme-' . get_template();

        if ( is_array( $classes ) ) {
            $classes[] = $class;
        } else {
            $classes .= ' ' . $class . ' ';
        }

        return $classes;
    }
}

new DocsPress_Themes_Support();
