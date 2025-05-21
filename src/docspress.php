<?php
/**
 * Plugin Name:  DocsPress
 * Description:  Online Documentation Engine for WordPress
 * Version:      @@plugin_version
 * Author:       nK
 * Author URI:   https://nkdev.info
 * License:      GPLv2 or later
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:  @@text_domain
 *
 * @package @@plugin_name
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * DocsPress class
 *
 * @class DocsPress The class that holds the entire DocsPress plugin
 */
class DocsPress {
    /**
     * The single class instance.
     *
     * @var $instance
     */
    private static $instance = null;

    /**
     * Path to the plugin directory
     *
     * @var $plugin_path
     */
    public $plugin_path;

    /**
     * URL to the plugin directory
     *
     * @var $plugin_url
     */
    public $plugin_url;

    /**
     * Theme templates directory path
     *
     * @var $theme_dir_path
     */
    public $theme_dir_path;

    /**
     * Path to template folder
     *
     * @var $theme_dir_path
     */
    public $template_path;

    /**
     * Post type name for documents
     *
     * @var $post_type
     */
    public $post_type = 'docs';

    /**
     * Current Page - is Docs Archive. Will be changed from Template Loader class
     *
     * @var $post_type
     */
    public $is_archive = false;

    /**
     * Current Page - is Docs Single. Will be changed from Template Loader class
     *
     * @var $post_type
     */
    public $is_single = false;

    /**
     * Main Instance
     * Ensures only one instance of this class exists in memory at any one time.
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
            self::$instance->plugin_init();
        }
        return self::$instance;
    }

    /**
     * Plugin init.
     */
    public function plugin_init() {
        $this->plugin_path    = plugin_dir_path( __FILE__ );
        $this->plugin_url     = plugin_dir_url( __FILE__ );
        $this->theme_dir_path = 'docspress/';
        $this->template_path  = $this->plugin_path . '/templates/';

        $this->include_dependencies();

        $this->maybe_setup();

        $this->add_image_sizes();

        // load textdomain.
        add_action( 'init', array( $this, 'load_textdomain' ) );

        // custom post type register.
        add_action( 'init', array( $this, 'add_role_caps' ) );
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'init', array( $this, 'register_post_meta' ) );

        // Loads frontend scripts and styles.
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        register_deactivation_hook( __FILE__, array( $this, 'deactivation_hook' ) );
        register_activation_hook( __FILE__, array( $this, 'activation_hook' ) );
    }

    /**
     * Activation hook.
     */
    public function activation_hook() {
        // Create Docs page if not created.
        $settings = get_option( 'docspress_settings', array() );
        if ( ! $settings || ! $settings['docs_page_id'] ) {
            $docspress_page = wp_insert_post(
                array(
                    'post_title'  => 'Documentation',
                    'post_type'   => 'page',
                    'post_author' => get_current_user_id(),
                    'post_status' => 'publish',
                    'post_name'   => 'docs',
                )
            );

            if ( ! is_wp_error( $docspress_page ) ) {
                $settings['docs_page_id'] = $docspress_page;

                update_option( 'docspress_settings', $settings );
            }
        }

        // need to flush rules to reset permalinks.
        add_option( 'docspress_setup', 'pending' );
    }

    /**
     * Deactivation hook.
     */
    public function deactivation_hook() {
        /* Deactivation actions */
    }

    /**
     * Maybe run setup code and rewrite rules.
     */
    public function maybe_setup() {
        $docspress_archive_id = docspress()->get_option( 'docs_page_id', 'docspress_settings', false );
        $docs_page            = $docspress_archive_id ? get_post( $docspress_archive_id ) : false;
        $slug                 = $docs_page ? get_post_field( 'post_name', $docs_page ) : 'docs';

        if (
            get_option( 'docspress_setup', false ) === 'pending' ||
            get_option( 'docspress_current_slug', 'docs' ) !== $slug
        ) {
            add_action( 'init', 'flush_rewrite_rules', 11, 0 );
            add_action( 'admin_init', 'flush_rewrite_rules', 11, 0 );

            delete_option( 'docspress_setup' );
            update_option( 'docspress_current_slug', $slug );
        }
    }

    /**
     * Add image sizes.
     */
    public function add_image_sizes() {
        // custom image sizes.
        add_image_size( 'docspress_archive', 40, 40, true );
        add_filter( 'image_size_names_choose', array( $this, 'image_size_names_choose' ) );
    }

    /**
     * Custom image sizes
     *
     * @param array $sizes - registered image sizes.
     *
     * @return array
     */
    public function image_size_names_choose( $sizes ) {
        return array_merge(
            $sizes,
            array(
                'docspress_archive' => esc_html__( 'Archive Thumbnail (DocsPress)', '@@text_domain' ),
            )
        );
    }

    /**
     * Include dependencies
     */
    public function include_dependencies() {
        include_once docspress()->plugin_path . 'includes/class-block-template-utils.php';
        include_once docspress()->plugin_path . 'includes/class-block-template-controller.php';
        include_once docspress()->plugin_path . 'includes/class-template-loader.php';
        include_once docspress()->plugin_path . 'includes/class-walker-docs.php';
        include_once docspress()->plugin_path . 'includes/class-suggestion.php';
        include_once docspress()->plugin_path . 'includes/class-themes-support.php';
        include_once docspress()->plugin_path . 'gutenberg/index.php';

        if ( is_admin() ) {
            include_once docspress()->plugin_path . 'includes/admin/class-admin.php';
        }

        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            include_once docspress()->plugin_path . 'includes/class-ajax.php';
        }
    }

    /**
     * Enqueue admin scripts
     *
     * Allows plugin assets to be loaded.
     *
     * @uses wp_enqueue_script()
     * @uses wp_localize_script()
     * @uses wp_enqueue_style
     */
    public function enqueue_scripts() {
        if ( ! ( $this->is_archive || $this->is_single ) ) {
            return;
        }

        $css_deps = array();
        $js_deps  = array();

        $show_search = docspress()->get_option( 'sidebar_show_search', 'docspress_single', true );

        if ( 'docsearch' === $show_search ) {
            wp_register_style( 'docsearch', 'https://cdn.jsdelivr.net/npm/@docsearch/css@3', array(), '3' );
            $css_deps[] = 'docsearch';

            wp_register_script( 'docsearch', 'https://cdn.jsdelivr.net/npm/@docsearch/js@3', array(), '3', true );
            $js_deps[] = 'docsearch';
        }

        if ( docspress()->get_option( 'show_anchor_links', 'docspress_single', true ) ) {
            wp_register_script( 'anchor-js', docspress()->plugin_url . 'assets/vendor/anchor-js/anchor.min.js', array(), '4.2.2', true );
            $js_deps[] = 'anchor-js';
        }

        wp_register_script( 'ivent', docspress()->plugin_url . 'assets/vendor/ivent/dist/ivent.min.js', array(), '0.2.0', true );
        $js_deps[] = 'ivent';

        wp_enqueue_style( 'docspress', docspress()->plugin_url . 'assets/css/style.min.css', $css_deps, '@@plugin_version' );
        wp_style_add_data( 'docspress', 'rtl', 'replace' );
        wp_style_add_data( 'docspress', 'suffix', '.min' );

        wp_enqueue_script( 'docspress', docspress()->plugin_url . 'assets/js/script.min.js', $js_deps, '@@plugin_version', true );
        wp_localize_script(
            'docspress',
            'docspress_vars',
            array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'docspress-ajax' ),
            )
        );

        // Custom script for AJAX.
        $ajax      = docspress()->get_option( 'ajax', 'docspress_single', true );
        $custom_js = docspress()->get_option( 'ajax_custom_js', 'docspress_single', '' );
        if ( $ajax && $custom_js ) {
            wp_add_inline_script(
                'docspress',
                '
                (function (ivent) {
                    ivent.on(document, "docspress_ajax_loaded", function (event) {
                        // Fallback.
                        const new_page = event?.data;

                        ' . $custom_js . '
                    });
                }(ivent));
            '
            );
        }
    }

    /**
     * Load plugin textdomain.
     */
    public function load_textdomain() {
        load_plugin_textdomain( '@@text_domain', false, basename( dirname( __FILE__ ) ) . '/languages' );
    }

    /**
     * Add capabilities to roles
     */
    public function add_role_caps() {
        if ( ! is_blog_installed() ) {
            return;
        }

        global $wp_version;

        $check_string = 'Plugin: @@plugin_version WP: ' . $wp_version;

        if ( get_option( 'docspress_updated_caps' ) === $check_string ) {
            return;
        }

        $wp_roles = wp_roles();

        if ( ! isset( $wp_roles ) || empty( $wp_roles ) || ! $wp_roles ) {
            return;
        }

        $author = get_role( 'author' );
        $admin  = get_role( 'administrator' );

        /* Add docspress manager role */
        $docspress_manager = add_role( 'docspress_manager', __( 'DocsPress Manager', '@@text_domain' ), $author->capabilities );

        $docspress_cap = array(
            'read_doc',
            'read_private_doc',
            'read_private_docs',
            'edit_doc',
            'edit_docs',
            'edit_others_docs',
            'edit_private_docs',
            'edit_published_docs',
            'delete_doc',
            'delete_docs',
            'delete_others_docs',
            'delete_private_docs',
            'delete_published_docs',
            'publish_docs',
        );

        /**
         * Add full capacities to admin and docs manager roles
         */
        foreach ( $docspress_cap as $cap ) {
            $wp_roles->add_cap( 'docspress_manager', $cap );
            $wp_roles->add_cap( 'administrator', $cap );
        }

        update_option( 'docspress_updated_caps', $check_string );
    }

    /**
     * Register the post type
     *
     * @return void
     */
    public function register_post_type() {
        $docspress_archive_id = docspress()->get_option( 'docs_page_id', 'docspress_settings', false );
        $docs_page            = $docspress_archive_id ? get_post( $docspress_archive_id ) : false;

        $labels = array(
            'name'               => $docs_page ? get_the_title( $docs_page ) : _x( 'DocsPress', 'Post Type General Name', '@@text_domain' ),
            'singular_name'      => _x( 'Doc', 'Post Type Singular Name', '@@text_domain' ),
            'menu_name'          => __( 'Documentation', '@@text_domain' ),
            'parent_item_colon'  => __( 'Parent Doc', '@@text_domain' ),
            'all_items'          => __( 'All Documentations', '@@text_domain' ),
            'view_item'          => __( 'View Documentation', '@@text_domain' ),
            'add_new_item'       => __( 'Add Documentation', '@@text_domain' ),
            'add_new'            => __( 'Add New', '@@text_domain' ),
            'edit_item'          => __( 'Edit Documentation', '@@text_domain' ),
            'update_item'        => __( 'Update Documentation', '@@text_domain' ),
            'search_items'       => __( 'Search Documentation', '@@text_domain' ),
            'not_found'          => __( 'Not documentation found', '@@text_domain' ),
            'not_found_in_trash' => __( 'Not found in Trash', '@@text_domain' ),
        );

        $rewrite = array(
            'slug'       => $docs_page ? get_post_field( 'post_name', $docs_page ) : 'docs',
            'with_front' => false,

            // Since our archive is custom made and we output all available docs here,
            // we don't need to add pages support. This also solves issue with created
            // meta tags for next page like /page/2 .
            'pages'      => false,
            'feeds'      => true,
        );

        $args = array(
            'labels'              => $labels,
            'hierarchical'        => true,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => true,
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-media-document',
            'can_export'          => true,
            'has_archive'         => $docs_page ? urldecode( get_page_uri( $docspress_archive_id ) ) : 'docs',
            'exclude_from_search' => false,
            'show_in_rest'        => true,
            'rewrite'             => $rewrite,
            'map_meta_cap'        => true,
            'capability_type'     => array( 'doc', 'docs' ),
            'supports'            => array(
                'title',
                'editor',
                'thumbnail',
                'revisions',
                'page-attributes',
                'comments',
                'custom-fields',
            ),
        );

        register_post_type( $this->post_type, $args );

        register_taxonomy(
            'docs_category',
            $this->post_type,
            array(
                'label'              => esc_html__( 'Docs Categories', '@@text_domain' ),
                'labels'             => array(
                    'menu_name' => esc_html__( 'Categories', '@@text_domain' ),
                ),
                'rewrite'            => array(
                    'slug' => 'docs-category',
                ),
                'hierarchical'       => false,
                'publicly_queryable' => false,
                'show_in_nav_menus'  => false,
                'show_in_rest'       => true,
                'show_admin_column'  => true,
            )
        );
    }

    /**
     * Register helpfulness meta.
     */
    public function register_post_meta() {
        register_meta(
            'post',
            'nav_title',
            array(
                'object_subtype' => 'docs',
                'type'           => 'string',
                'single'         => true,
                'show_in_rest'   => true,
                'auth_callback'  => array( __CLASS__, 'rest_auth' ),
            )
        );
        register_meta(
            'post',
            'positive',
            array(
                'object_subtype' => 'docs',
                'type'           => 'string',
                'single'         => true,
                'show_in_rest'   => true,
                'auth_callback'  => array( __CLASS__, 'rest_auth' ),
            )
        );
        register_meta(
            'post',
            'negative',
            array(
                'object_subtype' => 'docs',
                'type'           => 'string',
                'single'         => true,
                'show_in_rest'   => true,
                'auth_callback'  => array( __CLASS__, 'rest_auth' ),
            )
        );
    }

    /**
     * Determines REST API authentication.
     *
     * @param bool   $allowed Whether it is allowed.
     * @param string $meta_key The meta key being checked.
     * @param int    $post_id The post ID being checked.
     * @param int    $user_id The user ID being checked.
     * @param string $cap The current capability.
     * @param array  $caps All capabilities.
     * @return bool Whether the user can do it.
     */
    // phpcs:ignore
    public static function rest_auth( $allowed, $meta_key, $post_id, $user_id, $cap, $caps ) {
        return user_can( $user_id, 'edit_post', $post_id );
    }

    /**
     * Get the value of a settings field
     *
     * @param string $option settings field name.
     * @param string $section the section name this field belongs to.
     * @param string $default default text if it's not found.
     *
     * @return mixed
     */
    public function get_option( $option, $section, $default = '' ) {

        $options = get_option( $section );

        if ( isset( $options[ $option ] ) ) {
            return 'off' === $options[ $option ] ? false : ( 'on' === $options[ $option ] ? true : $options[ $option ] );
        }

        return $default;
    }

    /**
     * Get template part or docs templates
     * Looks at the theme directory first
     *
     * @param string $name template file name.
     * @param array  $data template data.
     */
    public function get_template_part( $name, $data = array() ) {
        $name = (string) $name;

        // lookup at docspress/name.php.
        $template = locate_template(
            array(
                docspress()->theme_dir_path . "{$name}.php",
            )
        );

        // fallback to plugin default template.
        if ( ! $template && $name && file_exists( docspress()->template_path . "{$name}.php" ) ) {
            $template = docspress()->template_path . "{$name}.php";
        }

        if ( $template ) {
            $this->load_template( $template, $data );
        }
    }

    /**
     * Load template with additional data.
     *
     * @param string $template_path - template path.
     * @param array  $template_data - template data array.
     */
    public function load_template( $template_path, $template_data ) {
        if ( isset( $template_data ) && is_array( $template_data ) ) {
            // phpcs:ignore
            extract( $template_data );
        }

        if ( file_exists( $template_path ) ) {
            include $template_path;
        }
    }

    /**
     * Is Archive
     *
     * @return bool
     */
    public function is_archive() {
        return $this->is_archive;
    }

    /**
     * Is Single
     *
     * @return bool
     */
    public function is_single() {
        return $this->is_single;
    }

    /**
     * Get current document ID
     *
     * @return int
     */
    public function get_current_doc_id() {
        global $post;

        if ( $post->post_parent ) {
            $ancestors = get_post_ancestors( $post->ID );
            $root      = count( $ancestors ) - 1;
            $parent    = $ancestors[ $root ];
        } else {
            $parent = $post->ID;
        }

        return apply_filters( 'docspress_current_doc_id', $parent );
    }

    /**
     * Get document page title
     *
     * @return string
     */
    public function get_docs_page_title() {
        $title        = esc_html__( 'Documentation', '@@text_domain' );
        $docs_page_id = docspress()->get_option( 'docs_page_id', 'docspress_settings' );

        if ( $docs_page_id ) {
            $title = get_the_title( $docs_page_id );
        }

        return apply_filters( 'docspress_page_title', $title );
    }

    /**
     * Get document page content
     *
     * @return string
     */
    public function get_docs_page_content() {
        $content      = '';
        $docs_page_id = docspress()->get_option( 'docs_page_id', 'docspress_settings' );

        if ( $docs_page_id ) {
            $content = get_post_field( 'post_content', $docs_page_id );
        }

        return apply_filters( 'docspress_page_content', $content );
    }

    /**
     * Get breadcrumbs array
     *
     * @return array
     */
    public function get_breadcrumbs_array() {
        global $post;

        $result       = array();
        $docs_page_id = docspress()->get_option( 'docs_page_id', 'docspress_settings' );

        $result[] = array(
            'label'    => __( 'Home', '@@text_domain' ),
            'url'      => home_url( '/' ),
        );

        if ( $docs_page_id ) {
            $result[] = array(
                'label' => get_the_title( $docs_page_id ) ? get_the_title( $docs_page_id ) : __( 'Docs', '@@text_domain' ),
                'url'   => get_permalink( $docs_page_id ),
            );
        }

        if ( 'docs' === $post->post_type && $post->post_parent ) {
            $parent_id   = $post->post_parent;
            $temp_crumbs = array();

            while ( $parent_id ) {
                $page          = get_post( $parent_id );
                $temp_crumbs[] = array(
                    'label'    => get_the_title( $page->ID ),
                    'url'      => get_permalink( $page->ID ),
                );
                $parent_id     = $page->post_parent;
            }

            $temp_crumbs = array_reverse( $temp_crumbs );

            foreach ( $temp_crumbs as $crumb ) {
                $result[] = $crumb;
            }
        }

        return apply_filters( 'docspress_breadcrumbs_array', $result );
    }

    /**
     * Next doc ID for the current doc page
     *
     * @return int
     */
    public function get_next_adjacent_doc_id() {
        global $post, $wpdb;

        $next_query = "SELECT ID FROM $wpdb->posts
        WHERE post_parent = $post->post_parent and post_type = 'docs' and post_status = 'publish' and menu_order > $post->menu_order
        ORDER BY menu_order ASC
        LIMIT 0, 1";

        // phpcs:ignore
        return (int) $wpdb->get_var( $next_query );
    }

    /**
     * Previous doc ID for the current doc page
     *
     * @return int
     */
    public function get_previous_adjacent_doc_id() {
        global $post, $wpdb;

        $prev_query = "SELECT ID FROM $wpdb->posts
        WHERE post_parent = $post->post_parent and post_type = 'docs' and post_status = 'publish' and menu_order < $post->menu_order
        ORDER BY menu_order DESC
        LIMIT 0, 1";

        // phpcs:ignore
        return (int) $wpdb->get_var( $prev_query );
    }

} // DocsPress

/**
 * Initialize the plugin
 *
 * @return \DocsPress
 */
function docspress() {
    return DocsPress::instance();
}
docspress();
