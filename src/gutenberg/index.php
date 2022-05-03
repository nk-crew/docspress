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
            array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-editor' ),
            '@@plugin_version',
            false
        );
        wp_register_script(
            'docspress-single-block',
            docspress()->plugin_url . 'gutenberg/blocks/single/script.min.js',
            array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-editor' ),
            '@@plugin_version',
            false
        );
        register_block_type(
            'docspress/archive',
            array(
                'render_callback' => array( $this, 'gutenberg_archive_block_render_callback' ),
                'editor_script'   => 'docspress-archive-block',
            )
        );
        register_block_type(
            'docspress/single',
            array(
                'render_callback' => array( $this, 'gutenberg_single_block_render_callback' ),
                'editor_script'   => 'docspress-single-block',
            )
        );
    }

    /**
     * Get block classname based on block attributes.
     *
     * @param string $classname - default classname.
     * @param array  $attributes - block attributes.
     * @return string
     */
    public function get_block_classname( $classname, $attributes ) {
        $attributes = array_merge(
            array(
                'align'     => '',
                'className' => '',
            ),
            $attributes
        );

        if ( $attributes['align'] ) {
            $classname .= ' align' . $attributes['align'];
        }
        if ( $attributes['className'] ) {
            $classname .= ' ' . $attributes['className'];
        }

        return $classname;
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

        $classname = $this->get_block_classname( 'wp-block-docspress-single-article', $attributes );

        echo '<div class="' . esc_attr( $classname ) . '">';

            docspress()->get_template_part( 'single/page' );

            /* Restore original Post Data */
            wp_reset_postdata();

        echo '</div>';

        return ob_get_clean();
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

        $classname = $this->get_block_classname( 'wp-block-docspress-archive', $attributes );

        echo '<div class="' . esc_attr( $classname ) . '">';

            docspress()->get_template_part( 'archive/title' );

            docspress()->get_template_part( 'archive/page' );

            /* Restore original Post Data */
            wp_reset_postdata();

        echo '</div>';

        return ob_get_clean();
    }
}
new DocsPress_Gutenberg();
