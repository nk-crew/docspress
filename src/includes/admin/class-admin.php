<?php

/**
 * Admin Class
 */
class DocsPress_Admin {

    function __construct() {
        $this->include_dependencies();
        $this->init_actions();
        $this->init_classes();
    }

    public function include_dependencies() {
        require_once docspress()->plugin_path . 'includes/class-settings-api.php';
        require_once docspress()->plugin_path . 'includes/admin/class-settings.php';
        require_once docspress()->plugin_path . 'includes/admin/class-docs-list-table.php';
    }

    /**
     * Initialize action hooks
     *
     * @return void
     */
    public function init_actions() {
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_filter( 'parent_file', array($this, 'menu_highlight' ) );

        add_filter( 'display_post_states', array( $this, 'display_post_states' ), 10, 2 );

        add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );
    }

    public function init_classes() {
        new DocsPress_Settings();
        new DocsPress_Docs_List_Table();
    }

    /**
     * Load admin scripts and styles
     *
     * @param  string
     *
     * @return void
     */
    public function admin_scripts( $hook ) {
        if ( 'toplevel_page_docspress' != $hook ) {
            return;
        }

        wp_enqueue_script( 'vuejs', docspress()->plugin_url . 'assets/vendor/vue/vue.min.js', array(), '2.5.13' );
        wp_enqueue_script( 'sweetalert', docspress()->plugin_url . 'assets/vendor/sweetalert/js/sweetalert.min.js', array( 'jquery' ), '1.1.3' );
        wp_enqueue_script( 'docspress-admin', docspress()->plugin_url . 'assets/admin/js/script.js', array( 'jquery', 'jquery-ui-sortable', 'wp-util' ), docspress()->plugin_version, true );
        wp_localize_script( 'docspress-admin', 'docspress_admin_vars', array(
            'nonce'    => wp_create_nonce( 'docspress-admin-nonce' ),
            'editurl'  => admin_url( 'post.php?action=edit&post=' ),
            'viewurl'  => home_url( '/?p=' ),
            '__'       => array(
                'enter_doc_title'       => __( 'Enter doc title', DOCSPRESS_DOMAIN ),
                'enter_section_title'   => __( 'Enter section title', DOCSPRESS_DOMAIN ),
                'clone_default_title'   => __( '%s Copy', DOCSPRESS_DOMAIN ),
                'remove_doc_title'      => __( 'Are you sure?', DOCSPRESS_DOMAIN ),
                'remove_doc_text'       => __( 'Are you sure to delete the entire documentation? Sections and articles inside this doc will be deleted too!', DOCSPRESS_DOMAIN ),
                'remove_doc_button_yes' => __( 'Yes, delete it!', DOCSPRESS_DOMAIN ),
                'remove_section_title'      => __( 'Are you sure?', DOCSPRESS_DOMAIN ),
                'remove_section_text'       => __( 'Are you sure to delete the entire section? Articles inside this section will be deleted too!', DOCSPRESS_DOMAIN ),
                'remove_section_button_yes' => __( 'Yes, delete it!', DOCSPRESS_DOMAIN ),
                'remove_article_title'      => __( 'Are you sure?', DOCSPRESS_DOMAIN ),
                'remove_article_text'       => __( 'Are you sure to delete the article?', DOCSPRESS_DOMAIN ),
                'remove_article_button_yes' => __( 'Yes, delete it!', DOCSPRESS_DOMAIN ),
                'post_deleted_text'     => __( 'This post has been deleted', DOCSPRESS_DOMAIN ),
                'export_doc_text'       => __( 'This process may take a while depending on your documentation size.', DOCSPRESS_DOMAIN ),
                'export_doc_title'       => __( 'Export %s?', DOCSPRESS_DOMAIN ),
                'export_doc_button_yes'  => __( 'Export!', DOCSPRESS_DOMAIN ),
                'exporting_doc_title'    => __( 'Exporting...', DOCSPRESS_DOMAIN ),
                'exporting_doc_text'     => __( 'Starting', DOCSPRESS_DOMAIN ),
                'exported_doc_title'     => __( 'Successfully Exported', DOCSPRESS_DOMAIN ),
                'exported_doc_download'  => __( 'Download ZIP', DOCSPRESS_DOMAIN ),
                'exported_doc_cancel'    => __( 'Close', DOCSPRESS_DOMAIN ),
            ),
        ) );

        wp_enqueue_style( 'sweetalert', docspress()->plugin_url . 'assets/vendor/sweetalert/css/sweetalert.css', array(), '1.1.3' );
        wp_enqueue_style( 'docspress-admin', docspress()->plugin_url . 'assets/admin/css/style.css', array(), docspress()->plugin_version );
    }

    /**
     * Get the admin menu position
     *
     * @return int the position of the menu
     */
    public function get_menu_position() {
        return apply_filters( 'docspress_menu_position', 48 );
    }

    /**
     * Add menu items
     *
     * @return void
     */
    public function admin_menu() {
        add_menu_page( __( 'DocsPress', DOCSPRESS_DOMAIN ), __( 'DocsPress', DOCSPRESS_DOMAIN ), 'publish_posts', DOCSPRESS_DOMAIN, array( $this, 'page_index' ), 'dashicons-media-document', $this->get_menu_position() );
        add_submenu_page( DOCSPRESS_DOMAIN, __( 'Documentations', DOCSPRESS_DOMAIN ), __( 'Documentations', DOCSPRESS_DOMAIN ), 'publish_posts', DOCSPRESS_DOMAIN, array( $this, 'page_index' ) );
    }

    /**
     * Highlight the proper top level menu
     *
     * @link http://wordpress.org/support/topic/moving-taxonomy-ui-to-another-main-menu?replies=5#post-2432769
     *
     * @global obj $current_screen
     * @param string $parent_file
     *
     * @return string
     */
    function menu_highlight( $parent_file ) {
        global $current_screen;

        if ( $current_screen->post_type == 'docs' ) {
            $parent_file = DOCSPRESS_DOMAIN;
        }

        return $parent_file;
    }

    /**
     * Add a post display state for special Documents in the page list table.
     *
     * @param array   $post_states An array of post display states.
     * @param WP_Post $post        The current post object.
     * @return array $post_states  An array of post display states.
     */
    public function display_post_states( $post_states, $post ) {
        $documents_page_id = docspress()->get_option( 'docs_page_id', 'docspress_settings' );

        if ( $post->post_type === 'page' && $documents_page_id && intval( $documents_page_id ) === $post->ID ) {
            $post_states[] = esc_html__( 'DocsPress', DOCSPRESS_DOMAIN );
        }

        return $post_states;
    }

    /**
     * UI Page handler
     *
     * @return void
     */
    public function page_index() {
        include dirname( __FILE__ ) . '/template-vue.php';
    }

    /**
     * Change the admin footer text on docs admin pages
     *
     * @param  string $footer_text
     * @return string
     */
    public function admin_footer_text( $footer_text ) {
        $current_screen = get_current_screen();
        $pages          = array( 'toplevel_page_docspress', 'edit-docs' );

        // Check to make sure we're on a docs admin page
        if ( isset( $current_screen->id ) && apply_filters( 'docspress_display_admin_footer_text', in_array( $current_screen->id, $pages ) ) ) {

            $footer_text .= ' ' . __( 'Thank you for using <strong>DocsPress</strong>.', DOCSPRESS_DOMAIN );

            $footer_text .= ' ' . sprintf( __( 'Use the <a href="%s">classic UI</a>.', DOCSPRESS_DOMAIN ), admin_url( 'edit.php?post_type=docs' ) );
        }

        return $footer_text;
    }

}

return new DocsPress_Admin();
