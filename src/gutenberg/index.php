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
            'docspress-archive-block',
            docspress()->plugin_url . 'gutenberg/blocks/archive/script.min.js',
            array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-editor', 'wp-block-editor' ),
            '@@plugin_version',
            false
        );
        wp_register_script(
            'docspress-single-block',
            docspress()->plugin_url . 'gutenberg/blocks/single/script.min.js',
            array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-editor', 'wp-block-editor' ),
            '@@plugin_version',
            false
        );

        register_block_type_from_metadata(
            docspress()->plugin_path . 'gutenberg/blocks/archive',
            array(
                'render_callback' => array( $this, 'gutenberg_archive_block_render_callback' ),
                'editor_script'   => 'docspress-archive-block',
            )
        );
        register_block_type_from_metadata(
            docspress()->plugin_path . 'gutenberg/blocks/single',
            array(
                'render_callback' => array( $this, 'gutenberg_single_block_render_callback' ),
                'editor_script'   => 'docspress-single-block',
            )
        );
    }

    /**
     * Render single doc block.
     *
     * @param array $attributes - block attributes.
     *
     * @return string
     */
    public function gutenberg_single_block_render_callback( $attributes ) {
        ob_start();

        docspress()->get_template_part( 'single/page' );

        /* Restore original Post Data */
        wp_reset_postdata();

        $output = ob_get_clean();

        $wrapper_attributes = get_block_wrapper_attributes();

        return sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $output );
    }

    /**
     * Render archive doc block.
     *
     * @param array $attributes - block attributes.
     *
     * @return string
     */
    public function gutenberg_archive_block_render_callback( $attributes ) {
        ob_start();

        docspress()->get_template_part( 'archive/title' );
        docspress()->get_template_part( 'archive/page' );

        /* Restore original Post Data */
        wp_reset_postdata();

        $output = ob_get_clean();

        $wrapper_attributes = get_block_wrapper_attributes();

        return sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $output );
    }
}
new DocsPress_Gutenberg();
