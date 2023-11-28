<?php
/**
 * Admin docspress list.
 *
 * @package @@plugin_name
 */

/**
 * Modifier functions in docs list table
 */
class DocsPress_Docs_List_Table {
    /**
     * Construct
     */
    public function __construct() {
        add_filter( 'manage_docs_posts_columns', array( $this, 'docs_list_columns' ) );
        add_action( 'manage_docs_posts_custom_column', array( $this, 'docs_list_columns_row' ), 10, 2 );
        add_filter( 'manage_edit-docs_sortable_columns', array( $this, 'docs_sortable_columns' ) );

        add_action( 'load-edit.php', array( $this, 'edit_docs_load' ) );

        add_action( 'admin_print_styles-edit.php', array( $this, 'helpfulness_css' ) );
    }

    /**
     * Votes styles in posts list.
     */
    public function helpfulness_css() {
        if ( get_current_screen()->post_type !== 'docs' ) {
            return;
        }
        ?>
        <style type="text/css">
            .column-docspress-votes {
                width: 120px;
            }
            .docspress-votes {
                display: flex;
                gap: 3px;
            }
            .docspress-votes .docspress-positive {
                color: #00a32a;
                font-weight: 600;
            }
            .docspress-votes .docspress-negative {
                color: #d63638;
                font-weight: 600;
            }
        </style>
        <?php
    }

    /**
     * Votes column in the class UI
     *
     * @param array $columns current docs list columns.
     *
     * @return array
     */
    public function docs_list_columns( $columns ) {
        $vote = array( 'docspress-votes' => __( 'Votes', '@@text_domain' ) );

        // insert before last element, date.
        // remove first 3 items and store to $first_items, date remains to $columns.
        $first_items = array_splice( $columns, 0, 3 );

        $new_columns = array_merge( $first_items, $vote, $columns );

        return $new_columns;
    }

    /**
     * Make votes column sortable.
     *
     * @param array $columns current docs list columns.
     *
     * @return array
     */
    public function docs_sortable_columns( $columns ) {
        $columns['docspress-votes'] = array( 'docspress-votes', true );

        return $columns;
    }

    /**
     * Undocumented function
     *
     * @param string $column_name - column name.
     * @param int    $post_id - post id.
     */
    public function docs_list_columns_row( $column_name, $post_id ) {
        if ( 'docspress-votes' === $column_name ) {
            $positive = get_post_meta( $post_id, 'positive', true );
            $negative = get_post_meta( $post_id, 'negative', true );

            printf( '<span class="docspress-positive">%d</span>/<span class="docspress-negative">%d</span>', esc_html( $positive ), esc_html( $negative ) );
        }
    }

    /**
     * Add request filter.
     */
    public function edit_docs_load() {
        add_filter( 'request', array( $this, 'sort_docs' ) );
    }

    /**
     * Sort the docs.
     *
     * @param array $vars - sort variables.
     * @return array
     */
    public function sort_docs( $vars ) {
        // Check if we're viewing the 'docs' post type.
        if ( isset( $vars['post_type'] ) && 'docs' === $vars['post_type'] ) {
            // Check if 'orderby' is set to 'docspress-votes'.
            if ( isset( $vars['orderby'] ) && 'docspress-votes' === $vars['orderby'] ) {
                $vars = array_merge(
                    $vars,
                    array(
                        // phpcs:ignore
                        'meta_key' => 'positive',
                        'orderby'  => 'meta_value_num',
                    )
                );
            }
        }

        return $vars;
    }
}
