<?php
/**
 * AJAX.
 *
 * @package @@plugin_name
 */

/**
 * Ajax Class
 */
class DocsPress_Ajax {
    /**
     * Post Type Object
     *
     * @var object
     */
    public $post_type_object;

    /**
     * Bind actions
     */
    public function __construct() {
        add_action( 'wp_ajax_docspress_create_doc', array( $this, 'create_doc' ) );
        add_action( 'wp_ajax_docspress_clone_doc', array( $this, 'clone_doc' ) );
        add_action( 'wp_ajax_docspress_remove_doc', array( $this, 'remove_doc' ) );
        add_action( 'wp_ajax_docspress_export_doc', array( $this, 'export_doc' ) );
        add_action( 'wp_ajax_docspress_admin_get_docs', array( $this, 'get_docs' ) );
        add_action( 'wp_ajax_docspress_sortable_docs', array( $this, 'sort_docs' ) );

        // feedback.
        add_action( 'wp_ajax_docspress_ajax_feedback', array( $this, 'handle_feedback' ) );
        add_action( 'wp_ajax_nopriv_docspress_ajax_feedback', array( $this, 'handle_feedback' ) );

        // feedback suggestion.
        add_action( 'wp_ajax_docspress_ajax_feedback_suggestion', array( $this, 'handle_feedback_suggestion' ) );
        add_action( 'wp_ajax_nopriv_docspress_ajax_feedback_suggestion', array( $this, 'handle_feedback_suggestion' ) );
    }

    /**
     * Get post type object with caps.
     *
     * @return object
     */
    public function get_post_type_object() {
        if ( ! $this->post_type_object ) {
            $this->post_type_object = get_post_type_object( 'docs' );
        }
        return $this->post_type_object;
    }

    /**
     * Get post data to use in Vue.
     *
     * @param object $post - post data.
     *
     * @return array
     */
    public function get_post_data( $post ) {
        $cat_id   = 0;
        $cat_name = '';

        // get category.
        $terms = wp_get_post_terms( $post->ID, 'docs_category' );
        if ( ! empty( $terms ) && isset( $terms[0] ) ) {
            $cat_id   = $terms[0]->term_id;
            $cat_name = $terms[0]->name;
        }

        return array(
            'id'       => $post->ID,
            'title'    => $post->post_title,
            'name'     => $post->post_name,
            'status'   => $post->post_status,
            'thumb'    => get_the_post_thumbnail_url( $post, 'docspress_archive_sm' ),
            'order'    => $post->menu_order,
            'cat_id'   => $cat_id,
            'cat_name' => $cat_name,
            'caps'     => array(
                'edit'   => current_user_can( $this->get_post_type_object()->cap->edit_post, $post->ID ),
                'delete' => current_user_can( $this->get_post_type_object()->cap->delete_post, $post->ID ),
            ),
        );
    }

    /**
     * Create a new doc
     *
     * @return void
     */
    public function create_doc() {
        check_ajax_referer( 'docspress-admin-nonce' );

        $title  = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
        $status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'publish';
        $parent = isset( $_POST['parent'] ) ? absint( $_POST['parent'] ) : 0;
        $order  = isset( $_POST['order'] ) ? absint( $_POST['order'] ) : 0;

        if ( ! current_user_can( $this->get_post_type_object()->cap->publish_posts ) ) {
            $status = 'pending';
        }

        $post_id = wp_insert_post(
            array(
                'post_title'  => $title,
                'post_type'   => 'docs',
                'post_status' => $status,
                'post_parent' => $parent,
                'post_author' => get_current_user_id(),
                'menu_order'  => $order,
            )
        );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error();
        }

        $post = get_post( $post_id );
        wp_send_json_success(
            array(
                'post'  => $this->get_post_data( $post ),
                'child' => array(),
            )
        );
    }

    /**
     * Clone a doc
     *
     * @return void
     */
    public function clone_doc() {
        check_ajax_referer( 'docspress-admin-nonce' );

        $title      = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
        $clone_from = isset( $_POST['clone_from'] ) ? absint( $_POST['clone_from'] ) : 0;

        $result = array();

        if ( $clone_from ) {
            $clone_from_post = get_post( $clone_from );

            if ( is_wp_error( $clone_from_post ) ) {
                wp_send_json_error();
            }

            $clone_post_meta = get_post_custom( $clone_from_post->ID );

            $new_post_id = wp_insert_post(
                array(
                    'post_title'            => $title,
                    'post_type'             => 'docs',
                    'post_status'           => 'publish',
                    'post_content'          => $clone_from_post->post_content,
                    'post_content_filtered' => $clone_from_post->post_content_filtered,
                    'post_excerpt'          => $clone_from_post->post_excerpt,
                    'post_author'           => get_current_user_id(),
                    'comment_status'        => $clone_from_post->comment_status,
                    'ping_status'           => $clone_from_post->ping_status,
                    'to_ping'               => $clone_from_post->to_ping,
                )
            );

            if ( is_wp_error( $new_post_id ) ) {
                wp_send_json_error();
            }

            // Copy post metadata.
            foreach ( $clone_post_meta as $key => $values ) {
                if ( 'positive' === $key || 'negative' === $key ) {
                    continue;
                }

                foreach ( $values as $value ) {
                    add_post_meta( $new_post_id, $key, $value );
                }
            }

            $new_post = get_post( $new_post_id );

            $result = array(
                'post'  => $this->get_post_data( $new_post ),
                'child' => $this->clone_child_docs( $clone_from_post->ID, $new_post_id ),
            );
        }

        wp_send_json_success( $result );
    }

    /**
     * Clone child docs.
     *
     * @param int $clone_from - post id.
     * @param int $clone_to - post id.
     *
     * @return array
     */
    public function clone_child_docs( $clone_from, $clone_to ) {
        $childrens = new WP_Query(
            array(
                'post_type'      => 'docs',
                'posts_per_page' => -1, // phpcs:ignore
                'post_parent'    => $clone_from,
                'orderby'        => array(
                    'menu_order' => 'ASC',
                    'date'       => 'DESC',
                ),
            )
        );
        $result    = array();

        while ( $childrens->have_posts() ) :
            $childrens->the_post();
            $clone_from_post = $childrens->post;

            if ( is_wp_error( $clone_from_post ) ) {
                wp_send_json_error();
            }

            $clone_post_meta = get_post_custom( $clone_from_post->ID );

            $new_post_id = wp_insert_post(
                array(
                    'post_title'            => $clone_from_post->post_title,
                    'post_type'             => $clone_from_post->post_type,
                    'post_status'           => $clone_from_post->post_status,
                    'post_content'          => $clone_from_post->post_content,
                    'post_content_filtered' => $clone_from_post->post_content_filtered,
                    'post_excerpt'          => $clone_from_post->post_excerpt,
                    'post_author'           => get_current_user_id(),
                    'post_parent'           => $clone_to,
                    'menu_order'            => $clone_from_post->menu_order,
                    'comment_status'        => $clone_from_post->comment_status,
                    'ping_status'           => $clone_from_post->ping_status,
                    'to_ping'               => $clone_from_post->to_ping,
                )
            );

            if ( is_wp_error( $new_post_id ) ) {
                wp_send_json_error();
            }

            // Copy post metadata.
            foreach ( $clone_post_meta as $key => $values ) {
                if ( 'positive' === $key || 'negative' === $key ) {
                    continue;
                }

                foreach ( $values as $value ) {
                    add_post_meta( $new_post_id, $key, $value );
                }
            }

            $new_post = get_post( $new_post_id );

            // add new subitems.
            $result[] = array(
                'post'  => $this->get_post_data( $new_post ),
                'child' => $this->clone_child_docs( $clone_from_post->ID, $new_post_id ),
            );
        endwhile;
        wp_reset_postdata();

        return $result;
    }

    /**
     * Delete a doc
     *
     * @return void
     */
    public function remove_doc() {
        check_ajax_referer( 'docspress-admin-nonce' );

        $force_delete = false;
        $post_id      = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

        if ( ! current_user_can( 'delete_post', $post_id ) ) {
            wp_send_json_error( __( 'You are not allowed to delete this item.', '@@text_domain' ) );
        }

        if ( $post_id ) {
            // delete childrens first if found.
            $this->remove_child_docs( $post_id, $force_delete );

            // delete main doc.
            wp_delete_post( $post_id, $force_delete );
        }

        wp_send_json_success();
    }

    /**
     * Remove child docs
     *
     * @param integer $parent_id - post id.
     * @param boolean $force_delete - force delete.
     */
    public function remove_child_docs( $parent_id = 0, $force_delete ) {
        $childrens = get_children( array( 'post_parent' => $parent_id ) );

        if ( $childrens ) {
            foreach ( $childrens as $child_post ) {
                // recursively delete.
                $this->remove_child_docs( $child_post->ID, $force_delete );

                wp_delete_post( $child_post->ID, $force_delete );
            }
        }
    }

    /**
     * Export as HTML
     *
     * @return void
     */
    public function export_doc() {
        // phpcs:ignore
        $doc_id = isset( $_GET['doc_id'] ) ? absint( $_GET['doc_id'] ) : 0;

        if ( $doc_id ) {
            include_once dirname( __FILE__ ) . '/class-export.php';
            $export_class = new DocsPress_Export();
            $export_class->run( $doc_id );
        }

        exit;
    }

    /**
     * Get all docs
     *
     * @return void
     */
    public function get_docs() {
        check_ajax_referer( 'docspress-admin-nonce' );

        $docs = new WP_Query(
            array(
                'post_type'      => 'docs',
                'post_status'    => array( 'publish', 'draft', 'pending' ),
                'posts_per_page' => -1, // phpcs:ignore
                'orderby'        => array(
                    'menu_order' => 'ASC',
                    'date'       => 'DESC',
                ),
            )
        );

        $arranged = $this->build_tree( $docs->posts );

        wp_send_json_success( $arranged );
    }

    /**
     * Store feedback for an article
     *
     * @return void
     */
    public function handle_feedback() {
        check_ajax_referer( 'docspress-ajax' );

        $previous = array();

        if ( isset( $_COOKIE['docspress_response'] ) ) {
            $cookies_data = explode( ',', sanitize_text_field( wp_unslash( $_COOKIE['docspress_response'] ) ) );

            foreach ( $cookies_data as $data ) {
                $id = explode( '|', $data );

                if ( isset( $id[0] ) ) {
                    $previous[ (string) $id[0] ] = isset( $id[1] ) ? $id[1] : 'unknown';
                }
            }
        }

        $post_id = isset( $_POST['post_id'] ) ? (string) sanitize_text_field( wp_unslash( $_POST['post_id'] ) ) : 0;
        $type    = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : false;

        if ( $type && ! in_array( $type, array( 'positive', 'negative' ), true ) ) {
            $type = false;
        }

        // check previous response.
        if (
            isset( $previous[ $post_id ] ) &&
            (
                'unknown' === $previous[ $post_id ] ||
                $type === $previous[ $post_id ]
            )
        ) {
            $message = __( 'Sorry, you\'ve already recorded your feedback!', '@@text_domain' );
            wp_send_json_error( $message );
        }

        // seems new.
        if ( $type ) {
            $count = (int) get_post_meta( $post_id, $type, true );
            update_post_meta( $post_id, $type, $count + 1 );

            // remove previous feedback.
            if ( isset( $previous[ $post_id ] ) && 'unknown' !== $previous[ $post_id ] ) {
                $count = (int) get_post_meta( $post_id, $previous[ $post_id ], true );
                update_post_meta( $post_id, $previous[ $post_id ], $count - 1 );
            }

            $previous[ $post_id ] = $post_id . '|' . $type;
            $cookie_val           = implode( ',', $previous );

            setcookie( 'docspress_response', $cookie_val, time() + WEEK_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
        }

        $message = __( 'Thank you for feedback!', '@@text_domain' );
        wp_send_json_success( $message );
    }

    /**
     * Prepare feedback suggestion to email.
     */
    public function handle_feedback_suggestion() {
        check_ajax_referer( 'docspress-ajax' );

        $email = docspress()->get_option( 'show_feedback_suggestion_email', 'docspress_single', '' );

        if ( ! $email ) {
            $email = get_option( 'admin_email' );
        }

        if ( ! $email ) {
            $response = __( 'Sorry, something went wrong on the server side!', '@@text_domain' );
            wp_send_json_error( $response );
            return;
        }

        $post_id       = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        $post          = $post_id ? get_post( $post_id ) : false;
        $feedback_type = isset( $_POST['feedback_type'] ) ? sanitize_text_field( wp_unslash( $_POST['feedback_type'] ) ) : '';
        $suggestion    = isset( $_POST['suggestion'] ) ? nl2br( htmlspecialchars( wp_unslash( $_POST['suggestion'] ) ) ) : ''; // phpcs:ignore
        $from          = isset( $_POST['from'] ) && ! empty( $_POST['from'] ) ? sanitize_text_field( wp_unslash( $_POST['from'] ) ) : '';

        if ( $post && $feedback_type && $suggestion ) {
            $is_sent = DocsPress_Suggestion::send(
                array(
                    'post'          => $post,
                    'from'          => $from,
                    'feedback_type' => $feedback_type,
                    'suggestion'    => $suggestion,
                )
            );

            if ( ! $is_sent ) {
                $response = __( 'Sorry, something went wrong with the mail server, your suggestions were not sent!', '@@text_domain' );
                wp_send_json_error( $response );
                return;
            }
        }

        $response = __( 'Thank you for suggestions!', '@@text_domain' );
        wp_send_json_success( $response );
    }

    /**
     * Sort docs
     *
     * @return void
     */
    public function sort_docs() {
        check_ajax_referer( 'docspress-admin-nonce' );

        $doc_ids = isset( $_POST['ids'] ) ? array_map( 'absint', $_POST['ids'] ) : array();

        if ( $doc_ids ) {
            foreach ( $doc_ids as $order => $id ) {
                wp_update_post(
                    array(
                        'ID'         => $id,
                        'menu_order' => $order,
                    )
                );
            }
        }

        exit;
    }

    /**
     * Build a tree of docs with parent-child relation
     *
     * @param  array   $docs - docs list.
     * @param  integer $parent - post id.
     *
     * @return array
     */
    public function build_tree( $docs, $parent = 0 ) {
        $result = array();

        if ( ! $docs ) {
            return $result;
        }

        foreach ( $docs as $key => $doc ) {
            if ( (int) $doc->post_parent === (int) $parent ) {
                unset( $docs[ $key ] );

                $cat_id   = 0;
                $cat_name = '';

                // get category.
                $terms = wp_get_post_terms( $doc->ID, 'docs_category' );
                if ( ! empty( $terms ) && isset( $terms[0] ) ) {
                    $cat_id   = $terms[0]->term_id;
                    $cat_name = $terms[0]->name;
                }

                // build tree and sort.
                $child = $this->build_tree( $docs, $doc->ID );

                $result[] = array(
                    'post'  => $this->get_post_data( $doc ),
                    'child' => $child,
                );
            }
        }

        return $result;
    }

    /**
     * Sort callback for sorting posts with their menu order
     *
     * @param array $a - 1 post.
     * @param array $b - 2 post.
     *
     * @return int
     */
    public function sort_callback( $a, $b ) {
        return $a['post']['order'] - $b['post']['order'];
    }
}

new DocsPress_Ajax();
