<?php
/**
 * Gutenberg blocks.
 *
 * @package @@plugin_name
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Gutenberg
 *
 * @class       DocsPress_Gutenberg
 * @package     docspress
 */
class DocsPress_Gutenberg {
    /**
     * DocsPress_Gutenberg constructor.
     */
    public function __construct() {
        add_action( 'init', array( $this, 'gutenberg_register_blocks' ) );
    }

    /**
     * Register single and archive doc blocks.
     *
     * @return void
     */
    public function gutenberg_register_blocks() {
        wp_register_script(
            'docspress-archive',
            docspress()->plugin_url . 'gutenberg/blocks/archive/build.min.js',
            array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-editor' ),
            '@@plugin_version',
            false
        );
        wp_register_script(
            'docspress-single',
            docspress()->plugin_url . 'gutenberg/blocks/single/build.min.js',
            array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-editor' ),
            '@@plugin_version',
            false
        );
        register_block_type(
            'docspress/archive',
            array(
                'render_callback' => array( $this, 'gutenberg_archive_block_render_callback' ),
                'editor_script'   => 'docspress-archive',
            )
        );
        register_block_type(
            'docspress/single',
            array(
                'render_callback' => array( $this, 'gutenberg_single_block_render_callback' ),
                'editor_script'   => 'docspress-single',
            )
        );
    }

    /**
     * Render single doc block.
     *
     * @return string
     */
    public function gutenberg_single_block_render_callback() {
        ob_start();

        docspress()->get_template_part( 'single/page' );

        /* Restore original Post Data */
        wp_reset_postdata();

        return ob_get_clean();
    }

    /**
     * Render archive doc block.
     *
     * @return string
     */
    public function gutenberg_archive_block_render_callback() {
        ob_start();

        docspress()->get_template_part( 'archive/page' );

        /* Restore original Post Data */
        wp_reset_postdata();

        return ob_get_clean();
    }
}
new DocsPress_Gutenberg();
