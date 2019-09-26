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
        add_action( 'load-post.php', array( $this, 'add_meta_box' ) );

        add_action( 'save_post_docs', array( $this, 'save_helpfulness_meta_box' ) );

        // load css.
        add_action( 'admin_print_styles-post.php', array( $this, 'helpfulness_css' ) );
        add_action( 'admin_print_styles-edit.php', array( $this, 'helpfulness_css' ) );
    }

    /**
     * Add helpfulness metabox.
     */
    public function add_meta_box() {
        add_meta_box( 'op-menu-meta-box-id', __( 'Helpfulness', '@@text_domain' ), array( $this, 'helpfulness_metabox' ), 'docs', 'side', 'core' );
    }

    /**
     * Helpfulness metabox styles.
     */
    public function helpfulness_css() {
        if ( get_current_screen()->post_type !== 'docs' ) {
            return;
        }
        ?>
        <style type="text/css">
            .docspress-helpfulness-form {
                display: flex;
                flex-wrap: wrap;
            }
            .docspress-helpfulness-form > div {
                display: flex;
                flex: 1;
                align-items: center;
            }
            .docspress-helpfulness-form input[type="number"] {
                width: 45px;
                margin-left: 10px;
                text-align: center;
            }
            .docspress-helpfulness-form .docspress-positive {
                color: green;
            }
            .docspress-helpfulness-form .docspress-negative {
                color: red;
                justify-content: flex-end;
            }
        </style>
        <?php
    }

    /**
     * Helpfulness metabox content.
     */
    public function helpfulness_metabox() {
        global $post;

        ?>
        <div class="docspress-helpfulness-form">
            <div class="docspress-positive">
                <span class="dashicons dashicons-thumbs-up"></span>
                <input
                    type="number"
                    name="positive"
                    placeholder="0"
                    value="<?php echo esc_attr( get_post_meta( $post->ID, 'positive', true ) ); ?>"
                />
            </div>

            <div class="docspress-negative">
                <span class="dashicons dashicons-thumbs-down"></span>
                <input
                    type="number"
                    name="negative"
                    placeholder="0"
                    value="<?php echo esc_attr( get_post_meta( $post->ID, 'negative', true ) ); ?>"
                />
            </div>
        </div>
        <?php
    }

    /**
     * Save helpfulness meta data.
     *
     * @param int $post_id - post id.
     */
    public function save_helpfulness_meta_box( $post_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check the user's permissions.
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $fields = [
            'positive',
            'negative',
        ];

        foreach ( $fields as $field ) {
            // phpcs:ignore
            if ( array_key_exists( $field, $_POST ) ) {
                // phpcs:ignore
                update_post_meta( $post_id, $field, sanitize_text_field( $_POST[ $field ] ) );
            }
        }
    }

    /**
     * Vote column in the class UI
     *
     * @param array $columns current docs list columns.
     *
     * @return array
     */
    public function docs_list_columns( $columns ) {
        $vote  = array( 'votes' => __( 'Votes', '@@text_domain' ) );

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
        $columns['votes'] = array( 'votes', true );

        return $columns;
    }

    /**
     * Undocumented function
     *
     * @param string $column_name - column name.
     * @param int    $post_id - post id.
     */
    public function docs_list_columns_row( $column_name, $post_id ) {
        if ( 'votes' === $column_name ) {
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
            // Check if 'orderby' is set to 'votes'.
            if ( isset( $vars['orderby'] ) && 'votes' === $vars['orderby'] ) {
                $vars = array_merge(
                    $vars,
                    array(
                        'meta_key' => 'positive',
                        'orderby'  => 'meta_value_num',
                    )
                );
            }
        }

        return $vars;
    }
}
