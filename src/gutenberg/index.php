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
        // Change priority to add category to the end of the blocks list.
        add_filter( 'block_categories_all', array( $this, 'block_categories_all' ), 11 );
        add_action( 'init', array( $this, 'gutenberg_register_blocks' ) );
        add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
    }

    /**
     * Register DocsPress blocks category
     *
     * @param array $categories - available categories.
     * @return array
     */
    public function block_categories_all( $categories ) {
        return array_merge(
            $categories,
            array(
                array(
                    'slug'  => 'docspress',
                    'icon'  => 'media-document',
                    'title' => __( 'DocsPress', '@@text_domain' ),
                ),
            )
        );
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

    /**
     * Enqueue block editor assets.
     */
    public function enqueue_block_editor_assets() {
        if ( 'docs' !== get_post_type() ) {
            return;
        }

        wp_enqueue_script(
            'docspress-page-options',
            docspress()->plugin_url . 'gutenberg/page-options/script.min.js',
            array( 'wp-i18n', 'wp-plugins', 'wp-components', 'wp-edit-post', 'wp-core-data' ),
            '@@plugin_version',
            false
        );
        wp_enqueue_style(
            'docspress-page-options',
            docspress()->plugin_url . 'gutenberg/page-options/style.min.css',
            array(),
            '@@plugin_version'
        );
    }
}
new DocsPress_Gutenberg();
