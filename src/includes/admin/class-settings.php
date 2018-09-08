<?php
/**
 * Settings.
 *
 * @package @@plugin_name
 */

/**
 * Settings Class
 */
class DocsPress_Settings {
    /**
     * Construct
     */
    public function __construct() {
        $this->settings_api = new DocsPress_Settings_API();

        add_action( 'admin_init', array( $this, 'admin_init' ) );
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
    }

    /**
     * Initialize the settings
     */
    public function admin_init() {

        // set the settings.
        $this->settings_api->set_sections( $this->get_settings_sections() );
        $this->settings_api->set_fields( $this->get_settings_fields() );

        // initialize settings.
        $this->settings_api->admin_init();
    }

    /**
     * Register the admin settings menu
     */
    public function admin_menu() {
        add_submenu_page( '@@text_domain', __( 'DocsPress Settings', '@@text_domain' ), __( 'Settings', '@@text_domain' ), 'manage_options', 'docspress-settings', array( $this, 'plugin_page' ) );
    }

    /**
     * Plugin settings sections
     *
     * @return array
     */
    public function get_settings_sections() {
        $sections = array(
            array(
                'id'    => 'docspress_settings',
                'title' => __( 'General', '@@text_domain' ),
            ),
            array(
                'id'    => 'docspress_single',
                'title' => __( 'Single Document', '@@text_domain' ),
            ),
            array(
                'id'    => 'docspress_archive',
                'title' => __( 'Archive', '@@text_domain' ),
            ),
            array(
                'id'    => 'docspress_export',
                'title' => __( 'Export', '@@text_domain' ),
            ),
        );

        return $sections;
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    public function get_settings_fields() {
        include_once dirname( __FILE__ ) . '/../class-export.php';
        $export_class = new DocsPress_Export();

        $settings_fields = array(
            'docspress_settings' => array(
                array(
                    'name'    => 'docs_page_id',
                    'label'   => __( 'Documentation archive page', '@@text_domain' ),
                    'desc'    => __( 'Page to show documentations list. <br> If you see the 404 error, please go to Settings > Permalinks and press "Save Changes" button.', '@@text_domain' ),
                    'type'    => 'select',
                    'options' => $this->get_pages(),
                ),
            ),
            'docspress_single' => array(
                array(
                    'name'    => 'show_feedback_buttons',
                    'label'   => __( 'Show feedback buttons', '@@text_domain' ),
                    'desc'    => __( 'Helpful feedback', '@@text_domain' ),
                    'type'    => 'checkbox',
                    'default' => 'on',
                ),
                array(
                    'name'    => 'show_feedback_buttons_likes',
                    'desc'    => __( 'Show likes/dislikes count', '@@text_domain' ),
                    'type'    => 'checkbox',
                    'default' => 'on',
                ),
                array(
                    'name'    => 'sidebar',
                    'label'   => __( 'Sidebar', '@@text_domain' ),
                    'type'    => 'html',
                ),
                array(
                    'name'    => 'sidebar_show_nav_childs',
                    'label'   => __( 'Show child links', '@@text_domain' ),
                    'desc'    => __( 'Always show child navigation links (by default showed only for active)', '@@text_domain' ),
                    'type'    => 'checkbox',
                    'default' => 'off',
                ),
                array(
                    'name'    => 'sidebar_show_nav_number_of_childs',
                    'label'   => __( 'Show number of childs', '@@text_domain' ),
                    'desc'    => __( 'Show in the title of parent link the number of childs', '@@text_domain' ),
                    'type'    => 'checkbox',
                    'default' => 'on',
                ),

                array(
                    'name'    => 'ajax',
                    'label'   => __( 'AJAX loading', '@@text_domain' ),
                    'type'    => 'checkbox',
                    'default' => 'on',
                ),
                array(
                    'name'    => 'ajax_custom_js',
                    'label'   => __( 'AJAX custom JS', '@@text_domain' ),
                    'desc'    => __( 'Run custom JS after document loaded via AJAX', '@@text_domain' ),
                    'type'    => 'textarea',
                    'size'    => 'large',
                    'default' => "/*\n * New page content loaded via ajax you can get in variable 'new_page'\n * Example: console.log(new_page);\n */",
                ),
            ),
            'docspress_archive' => array(
                array(
                    'name'    => 'show_articles',
                    'label'   => __( 'Show articles', '@@text_domain' ),
                    'desc'    => __( 'Top level articles list', '@@text_domain' ),
                    'type'    => 'checkbox',
                    'default' => 'on',
                ),
                array(
                    'name'    => 'articles_number',
                    'label'   => __( 'Number of articles', '@@text_domain' ),
                    'desc'    => __( 'Type -1 to show all available articles', '@@text_domain' ),
                    'type'    => 'number',
                    'default' => 3,
                ),
            ),
            'docspress_export' => array(
                array(
                    'name'    => 'custom_css',
                    'label'   => __( 'Custom CSS', '@@text_domain' ),
                    'desc'    => __( 'Added in exported HTML files', '@@text_domain' ),
                    'type'    => 'textarea',
                    'size'    => 'large',
                    'default' => $export_class->custom_css,
                ),
                array(
                    'name'    => 'custom_js',
                    'label'   => __( 'Custom JS', '@@text_domain' ),
                    'desc'    => __( 'Added in exported HTML files', '@@text_domain' ),
                    'type'    => 'textarea',
                    'size'    => 'large',
                    'default' => $export_class->custom_js,
                ),
                array(
                    'name'    => 'clean_html',
                    'label'   => __( 'Clean HTML RegExp', '@@text_domain' ),
                    'desc'    => __( 'Each regexp on new line (change it only if you understand what you do)', '@@text_domain' ),
                    'type'    => 'textarea',
                    'size'    => 'large',
                    'default' => str_replace( '\'', "\\'", $export_class->clean_html_regexp ),
                ),
            ),
        );

        return $settings_fields;
    }

    /**
     * The plguin page handler
     *
     * @return void
     */
    public function plugin_page() {
        echo '<div class="wrap">';

        $this->settings_api->show_navigation();
        $this->settings_api->show_forms();

        $this->scripts();

        echo '</div>';
    }
    /**
     * Get all the pages
     *
     * @return array page names with key value pairs
     */
    public function get_pages() {
        $pages_options = array( '' => __( '&mdash; Select Page &mdash;', '@@text_domain' ) );
        $pages = get_pages(
            array(
                'numberposts' => -1, // phpcs:ignore
            )
        );

        if ( $pages ) {
            foreach ( $pages as $page ) {
                $pages_options[ $page->ID ] = $page->post_title;
            }
        }
        return $pages_options;
    }

    /**
     * Scripts
     *
     * @return void
     */
    public function scripts() {
        ?>
        <script type="text/javascript">
            jQuery(function($) {
                $('input[name="docspress_settings[ajax]"]:checkbox').on( 'change', function() {

                    if ( $(this).is(':checked' ) ) {
                        $('tr.ajax_custom_js').show();
                    } else {
                        $('tr.ajax_custom_js').hide();
                    }

                }).change();

                $('input[name="docspress_archive[show_articles]"]:checkbox').on( 'change', function() {

                    if ( $(this).is(':checked' ) ) {
                        $('tr.articles_number').show();
                    } else {
                        $('tr.articles_number').hide();
                    }

                }).change();
            });
        </script>
        <?php
    }

}
