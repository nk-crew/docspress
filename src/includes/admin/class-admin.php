<?php
/**
 * Admin functionality.
 *
 * @package @@plugin_name
 */

/**
 * Admin Class
 */
class DocsPress_Admin {
    /**
     * Construct
     */
    public function __construct() {
        $this->include_dependencies();
        $this->init_actions();
        $this->init_classes();
    }

    /**
     * Include dependencies.
     */
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
        add_filter( 'parent_file', array( $this, 'menu_highlight' ) );

        add_filter( 'display_post_states', array( $this, 'display_post_states' ), 10, 2 );

        add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );
    }

    /**
     * Initialize classes.
     */
    public function init_classes() {
        new DocsPress_Settings();
        new DocsPress_Docs_List_Table();
    }

    /**
     * Load admin scripts and styles
     *
     * @param string $hook - hook name.
     */
    public function admin_scripts( $hook ) {
        if ( 'toplevel_page_docspress' !== $hook ) {
            return;
        }

        wp_enqueue_script( 'vuejs', docspress()->plugin_url . 'assets/vendor/vue/vue.min.js', array(), '2.5.13', true );
        wp_enqueue_script( 'sweetalert', docspress()->plugin_url . 'assets/vendor/sweetalert/js/sweetalert.min.js', array( 'jquery' ), '1.1.3', true );
        wp_enqueue_script( 'docspress-admin', docspress()->plugin_url . 'assets/admin/js/script.min.js', array( 'jquery', 'jquery-ui-sortable', 'wp-util' ), docspress()->plugin_version, true );
        wp_localize_script(
            'docspress-admin',
            'docspress_admin_vars',
            array(
                'nonce'    => wp_create_nonce( 'docspress-admin-nonce' ),
                'editurl'  => admin_url( 'post.php?action=edit&post=' ),
                'viewurl'  => home_url( '/?p=' ),
                '__'       => array(
                    'enter_doc_title'           => __( 'Enter doc title', '@@text_domain' ),
                    'enter_section_title'       => __( 'Enter section title', '@@text_domain' ),
                    // translators: %s - copy.
                    'clone_default_title'       => __( '%s Copy', '@@text_domain' ),
                    'remove_doc_title'          => __( 'Are you sure?', '@@text_domain' ),
                    'remove_doc_text'           => __( 'Are you sure to delete the entire documentation? Sections and articles inside this doc will be deleted too!', '@@text_domain' ),
                    'remove_doc_button_yes'     => __( 'Yes, delete it!', '@@text_domain' ),
                    'remove_section_title'      => __( 'Are you sure?', '@@text_domain' ),
                    'remove_section_text'       => __( 'Are you sure to delete the entire section? Articles inside this section will be deleted too!', '@@text_domain' ),
                    'remove_section_button_yes' => __( 'Yes, delete it!', '@@text_domain' ),
                    'remove_article_title'      => __( 'Are you sure?', '@@text_domain' ),
                    'remove_article_text'       => __( 'Are you sure to delete the article?', '@@text_domain' ),
                    'remove_article_button_yes' => __( 'Yes, delete it!', '@@text_domain' ),
                    'post_deleted_text'         => __( 'This post has been deleted', '@@text_domain' ),
                    'export_doc_text'           => __( 'This process may take a while depending on your documentation size.', '@@text_domain' ),
                    // translators: %s - export.
                    'export_doc_title'          => __( 'Export %s?', '@@text_domain' ),
                    'export_doc_button_yes'     => __( 'Export!', '@@text_domain' ),
                    'exporting_doc_title'       => __( 'Exporting...', '@@text_domain' ),
                    'exporting_doc_text'        => __( 'Starting', '@@text_domain' ),
                    'exported_doc_title'        => __( 'Successfully Exported', '@@text_domain' ),
                    'exported_doc_download'     => __( 'Download ZIP', '@@text_domain' ),
                    'exported_doc_cancel'       => __( 'Close', '@@text_domain' ),
                ),
            )
        );

        wp_enqueue_style( 'sweetalert', docspress()->plugin_url . 'assets/vendor/sweetalert/css/sweetalert.css', array(), '1.1.3' );
        wp_enqueue_style( 'docspress-admin', docspress()->plugin_url . 'assets/admin/css/style.min.css', array(), docspress()->plugin_version );
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
        add_menu_page(
            __( 'DocsPress', '@@text_domain' ),
            __( 'DocsPress', '@@text_domain' ),
            'publish_posts',
            '@@text_domain',
            array( $this, 'page_index' ),
            'dashicons-media-document',
            $this->get_menu_position()
        );
        add_submenu_page(
            '@@text_domain',
            __( 'Documentations', '@@text_domain' ),
            __( 'Documentations', '@@text_domain' ),
            'publish_posts',
            '@@text_domain',
            array( $this, 'page_index' )
        );
        add_submenu_page(
            '@@text_domain',
            __( 'Categories', '@@text_domain' ),
            __( 'Categories', '@@text_domain' ),
            'publish_posts',
            'edit-tags.php?taxonomy=docs_category&post_type=docs'
        );
    }

    /**
     * Highlight the proper top level menu
     *
     * @link http://wordpress.org/support/topic/moving-taxonomy-ui-to-another-main-menu?replies=5#post-2432769
     *
     * @global obj $current_screen
     * @param string $parent_file - parent file.
     *
     * @return string
     */
    public function menu_highlight( $parent_file ) {
        global $current_screen;

        if ( 'docs' === $current_screen->post_type ) {
            $parent_file = 'docspress';
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

        if ( 'page' === $post->post_type && $documents_page_id && intval( $documents_page_id ) === $post->ID ) {
            $post_states[] = esc_html__( 'DocsPress', '@@text_domain' );
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
     * @param string $footer_text - footer text.
     *
     * @return string
     */
    public function admin_footer_text( $footer_text ) {
        $current_screen = get_current_screen();
        $pages          = array( 'toplevel_page_docspress', 'edit-docs' );

        // Check to make sure we're on a docs admin page.
        if ( isset( $current_screen->id ) && apply_filters( 'docspress_display_admin_footer_text', in_array( $current_screen->id, $pages, true ) ) ) {
            $footer_text .= ' ' . __( 'Thank you for using <strong>DocsPress</strong>.', '@@text_domain' );

            // translators: %s - docs page url.
            $footer_text .= ' ' . sprintf( __( 'Use the <a href="%s">classic UI</a>.', '@@text_domain' ), admin_url( 'edit.php?post_type=docs' ) );
        }

        return $footer_text;
    }

}

return new DocsPress_Admin();
