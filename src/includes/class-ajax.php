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
     * Limit a requested doc status to the ones the current user may set.
     *
     * @param string $status - requested status.
     *
     * @return string
     */
    public function allowed_status( $status ) {
        if ( ! in_array( $status, array( 'publish', 'private', 'pending', 'draft' ), true ) ) {
            return 'draft';
        }

        if ( ( 'publish' === $status || 'private' === $status ) && ! current_user_can( $this->get_post_type_object()->cap->publish_posts ) ) {
            return 'pending';
        }

        return $status;
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
            'thumb'    => get_the_post_thumbnail_url( $post, 'docspress_archive' ),
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

        if ( ! current_user_can( $this->get_post_type_object()->cap->edit_posts ) ) {
            wp_send_json_error( __( 'You are not allowed to create this item.', '@@text_domain' ) );
        }

        $title  = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
        $status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'publish';
        $parent = isset( $_POST['parent'] ) ? absint( $_POST['parent'] ) : 0;
        $order  = isset( $_POST['order'] ) ? absint( $_POST['order'] ) : 0;

        if ( $parent && 'docs' !== get_post_type( $parent ) ) {
            wp_send_json_error( __( 'You are not allowed to create this item.', '@@text_domain' ) );
        }

        $status = $this->allowed_status( $status );

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

        if (
            ! current_user_can( $this->get_post_type_object()->cap->edit_posts ) ||
            'docs' !== get_post_type( $clone_from ) ||
            ! current_user_can( 'edit_doc', $clone_from )
        ) {
            wp_send_json_error( __( 'You are not allowed to clone this item.', '@@text_domain' ) );
        }

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
                    'post_status'           => $this->allowed_status( $clone_from_post->post_status ),
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
                    'post_status'           => $this->allowed_status( $clone_from_post->post_status ),
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

        $post_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $ids     = 'docs' === get_post_type( $post_id ) ? $this->get_child_doc_ids( $post_id ) : array();

        // Children come first, so they are trashed before their parents.
        $ids[] = $post_id;

        foreach ( $ids as $id ) {
            if ( ! current_user_can( 'delete_doc', $id ) || 'docs' !== get_post_type( $id ) ) {
                wp_send_json_error( __( 'You are not allowed to delete this item.', '@@text_domain' ) );
            }
        }

        foreach ( $ids as $id ) {
            wp_trash_post( $id );
        }

        wp_send_json_success();
    }

    /**
     * Get IDs of all nested child docs, deepest first.
     *
     * @param integer $parent_id - post id.
     *
     * @return array
     */
    public function get_child_doc_ids( $parent_id ) {
        $ids       = array();
        $childrens = get_children(
            array(
                'post_parent' => $parent_id,
                'post_type'   => 'docs',
            )
        );

        foreach ( $childrens as $child_post ) {
            $ids   = array_merge( $ids, $this->get_child_doc_ids( $child_post->ID ) );
            $ids[] = $child_post->ID;
        }

        return $ids;
    }

    /**
     * Export as HTML
     *
     * @return void
     */
    public function export_doc() {
        check_ajax_referer( 'docspress-admin-nonce' );

        // phpcs:ignore
        $doc_id = isset( $_GET['doc_id'] ) ? absint( $_GET['doc_id'] ) : 0;

        if ( ! current_user_can( 'edit_doc', $doc_id ) ) {
            wp_send_json_error( __( 'You are not allowed to export this item.', '@@text_domain' ) );
        }

        if ( $doc_id ) {
            include_once __DIR__ . '/class-export.php';
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

        if ( ! current_user_can( $this->get_post_type_object()->cap->edit_posts ) ) {
            wp_send_json_error( __( 'You are not allowed to view these items.', '@@text_domain' ) );
        }

        $docs = new WP_Query(
            array(
                'post_type'      => 'docs',
                'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
                'posts_per_page' => -1, // phpcs:ignore
                'orderby'        => array(
                    'menu_order' => 'ASC',
                    'date'       => 'DESC',
                ),
            )
        );

        $terms = get_terms(
            array(
                'taxonomy'   => 'docs_category',
                'hide_empty' => false,
            )
        );

        $readable = array();
        foreach ( $docs->posts as $doc ) {
            if ( 'publish' === $doc->post_status || current_user_can( 'read_post', $doc->ID ) ) {
                $readable[] = $doc;
            }
        }

        $arranged = $this->build_tree( $readable );

        wp_send_json_success(
            array(
                'docs'  => $arranged,
                'terms' => $terms,
            )
        );
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
                    // Only the two feedback meta keys may come back from the cookie.
                    $previous[ (string) $id[0] ] = isset( $id[1] ) && in_array( $id[1], array( 'positive', 'negative' ), true ) ? $id[1] : 'unknown';
                }
            }
        }

        $post_id = isset( $_POST['post_id'] ) ? (string) absint( $_POST['post_id'] ) : '0';
        $type    = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : false;

        if (
            'docs' !== get_post_type( (int) $post_id ) ||
            ( 'publish' !== get_post_status( (int) $post_id ) && ! current_user_can( 'read_post', (int) $post_id ) )
        ) {
            wp_send_json_error( __( 'Sorry, something went wrong on the server side!', '@@text_domain' ) );
        }

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

        // Honeypot: bots that fill the hidden field get the usual success reply and no email.
        if ( ! empty( $_POST['docspress_website'] ) ) {
            wp_send_json_success( __( 'Thank you for suggestions!', '@@text_domain' ) );
        }

        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

        if (
            'docs' !== get_post_type( $post_id ) ||
            ( 'publish' !== get_post_status( $post_id ) && ! current_user_can( 'read_post', $post_id ) )
        ) {
            wp_send_json_error( __( 'Sorry, something went wrong on the server side!', '@@text_domain' ) );
        }

        $post          = get_post( $post_id );
        $feedback_type = isset( $_POST['feedback_type'] ) ? sanitize_text_field( wp_unslash( $_POST['feedback_type'] ) ) : '';
        $feedback_type = in_array( $feedback_type, array( 'positive', 'negative' ), true ) ? $feedback_type : '';
        $suggestion    = isset( $_POST['suggestion'] ) ? nl2br( htmlspecialchars( mb_substr( wp_unslash( $_POST['suggestion'] ), 0, 5000 ) ) ) : ''; // phpcs:ignore
        $from          = isset( $_POST['from'] ) && ! empty( $_POST['from'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST['from'] ) ), 0, 200 ) : '';

        if ( $feedback_type && $suggestion ) {
            if ( ! $this->suggestion_allowed() ) {
                wp_send_json_error( __( 'Too many suggestions, please try again later.', '@@text_domain' ) );
            }

            $is_sent = DocsPress_Suggestion::send(
                array(
                    'post'          => $post,
                    'from'          => $from,
                    'feedback_type' => $feedback_type,
                    'suggestion'    => $suggestion,
                )
            );

            if ( ! $is_sent ) {
                // Nothing went out, so the attempt does not use up the limits.
                $this->count_suggestion( -1 );
                $response = __( 'Sorry, something went wrong with the mail server, your suggestions were not sent!', '@@text_domain' );
                wp_send_json_error( $response );
                return;
            }
        }

        $response = __( 'Thank you for suggestions!', '@@text_domain' );
        wp_send_json_success( $response );
    }

    /**
     * Count a suggestion email against the per-client and the site-wide daily limits.
     *
     * @return bool - false when a limit is already reached.
     */
    public function suggestion_allowed() {
        return $this->count_suggestion( 1 );
    }

    /**
     * Add a step to the per-client and the daily suggestion counters.
     *
     * @param int $step - 1 to reserve a suggestion, -1 to give it back.
     *
     * @return bool - false when a reservation is refused by a limit.
     */
    private function count_suggestion( $step ) {
        global $wpdb;

        $limits = apply_filters(
            'docspress_suggestion_limits',
            array(
                'per_client'    => 3,
                'client_window' => 10 * MINUTE_IN_SECONDS,
                'per_day'       => 50,
            )
        );

        $now    = time();
        $ip     = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        $client = 'docspress_suggestion_' . substr( wp_hash( $ip ), 0, 32 );
        $day    = 'docspress_suggestions_' . gmdate( 'Ymd', $now );

        // Overlapping requests would read the same counts and all pass, so they take turns on the daily counter.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        if ( '1' !== (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK( %s, 5 )', $day ) ) ) {
            return false;
        }

        // Each counter is array( count, window start ); a transient refresh keeps the window's original end.
        $client_hits = get_transient( $client );
        $client_hits = is_array( $client_hits ) ? $client_hits : array( 0, $now );
        $day_count   = (int) get_transient( $day );
        $allowed     = $step < 0 || ( $client_hits[0] < (int) $limits['per_client'] && $day_count < (int) $limits['per_day'] );

        if ( $allowed ) {
            set_transient( $client, array( max( 0, $client_hits[0] + $step ), $client_hits[1] ), max( 1, $client_hits[1] + (int) $limits['client_window'] - $now ) );
            set_transient( $day, max( 0, $day_count + $step ), DAY_IN_SECONDS );
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK( %s )', $day ) );

        return $allowed;
    }

    /**
     * Sort docs
     *
     * @return void
     */
    public function sort_docs() {
        check_ajax_referer( 'docspress-admin-nonce' );

        $doc_ids = isset( $_POST['ids'] ) && is_array( $_POST['ids'] ) ? array_map( 'absint', $_POST['ids'] ) : array();

        if ( $doc_ids ) {
            foreach ( $doc_ids as $order => $id ) {
                if ( 'docs' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) {
                    continue;
                }

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
     * @param  integer $parent_id - post id.
     *
     * @return array
     */
    public function build_tree( $docs, $parent_id = 0 ) {
        $result = array();

        if ( ! $docs ) {
            return $result;
        }

        foreach ( $docs as $key => $doc ) {
            if ( (int) $doc->post_parent === (int) $parent_id ) {
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
