<?php

/**
 * Settings Class
 *
 * @since 1.1
 */
class DocsPress_Settings {

    public function __construct() {
        $this->settings_api = new DocsPress_Settings_API();

        add_action( 'admin_init', array($this, 'admin_init') );
        add_action( 'admin_menu', array($this, 'admin_menu') );
    }

    /**
     * Initialize the settings
     *
     * @return void
     */
    function admin_init() {

        //set the settings
        $this->settings_api->set_sections( $this->get_settings_sections() );
        $this->settings_api->set_fields( $this->get_settings_fields() );

        //initialize settings
        $this->settings_api->admin_init();
    }

    /**
     * Register the admin settings menu
     *
     * @return void
     */
    function admin_menu() {
        add_submenu_page( DOCSPRESS_DOMAIN, __( 'DocsPress Settings', DOCSPRESS_DOMAIN ), __( 'Settings', DOCSPRESS_DOMAIN ), 'manage_options', 'docspress-settings', array( $this, 'plugin_page' ) );
    }

    /**
     * Plugin settings sections
     *
     * @return array
     */
    function get_settings_sections() {
        $sections = array(
            array(
                'id'    => 'docspress_settings',
                'title' => __( 'General', DOCSPRESS_DOMAIN )
            ),
            array(
                'id'    => 'docspress_single',
                'title' => __( 'Single Document', DOCSPRESS_DOMAIN )
            ),
            array(
                'id'    => 'docspress_archive',
                'title' => __( 'Archive', DOCSPRESS_DOMAIN )
            ),
            array(
                'id'    => 'docspress_export',
                'title' => __( 'Export', DOCSPRESS_DOMAIN )
            ),
        );

        return $sections;
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    function get_settings_fields() {
        include_once dirname( __FILE__ ) . '/../class-export.php';
        $export_class = new DocsPress_Export();

        $settings_fields = array(
            'docspress_settings' => array(
                array(
                    'name'    => 'docs_page_id',
                    'label'   => __( 'Documentation Archive Page', DOCSPRESS_DOMAIN ),
                    'desc'    => __( 'Page to show documentations list', DOCSPRESS_DOMAIN ),
                    'type'    => 'select',
                    'options' => $this->get_pages()
                ),
            ),
            'docspress_single' => array(
                array(
                    'name'    => 'show_feedback_buttons',
                    'label'   => __( 'Show Feedback Buttons', DOCSPRESS_DOMAIN ),
                    'desc'    => __( 'Helpful feedback', DOCSPRESS_DOMAIN ),
                    'type'    => 'checkbox',
                    'default' => 'on'
                ),
                array(
                    'name'    => 'ajax',
                    'label'   => __( 'AJAX Loading', DOCSPRESS_DOMAIN ),
                    'type'    => 'checkbox',
                    'default' => 'on'
                ),
                array(
                    'name'    => 'ajax_custom_js',
                    'label'   => __( 'AJAX Custom JS', DOCSPRESS_DOMAIN ),
                    'desc'    => __( 'Run custom JS after document loaded via AJAX', DOCSPRESS_DOMAIN ),
                    'type'    => 'textarea',
                    'size'    => 'large',
                    'default' => "/*\n * New page content loaded via ajax you can get in variable 'new_page'\n * Example: console.log(new_page);\n */"
                ),

                array(
                    'name'    => 'sidebar',
                    'label'   => __( 'Sidebar', DOCSPRESS_DOMAIN ),
                    'type'    => 'html',
                ),
                array(
                    'name'    => 'sidebar_show_nav_childs',
                    'label'   => __( 'Show Child Links', DOCSPRESS_DOMAIN ),
                    'desc'    => __( 'Always show child navigation links (by default showed only for active)', DOCSPRESS_DOMAIN ),
                    'type'    => 'checkbox',
                    'default' => 'off'
                ),
                array(
                    'name'    => 'sidebar_show_nav_number_of_childs',
                    'label'   => __( 'Show Number of Childs', DOCSPRESS_DOMAIN ),
                    'desc'    => __( 'Show in the title of parent link the number of childs', DOCSPRESS_DOMAIN ),
                    'type'    => 'checkbox',
                    'default' => 'on'
                ),
            ),
            'docspress_archive' => array(
                array(
                    'name'    => 'show_articles',
                    'label'   => __( 'Show Articles', DOCSPRESS_DOMAIN ),
                    'desc'    => __( 'Top level articles list', DOCSPRESS_DOMAIN ),
                    'type'    => 'checkbox',
                    'default' => 'on'
                ),
                array(
                    'name'    => 'articles_number',
                    'label'   => __( 'Number of Articles', DOCSPRESS_DOMAIN ),
                    'desc'    => __( 'Type -1 to show all available articles', DOCSPRESS_DOMAIN ),
                    'type'    => 'number',
                    'default' => 3
                ),
            ),
            'docspress_export' => array(
                array(
                    'name'    => 'custom_css',
                    'label'   => __( 'Custom CSS', DOCSPRESS_DOMAIN ),
                    'desc'    => __( 'Added in exported HTML files', DOCSPRESS_DOMAIN ),
                    'type'    => 'textarea',
                    'size'    => 'large',
                    'default' => $export_class->custom_css
                ),
                array(
                    'name'    => 'custom_js',
                    'label'   => __( 'Custom JS', DOCSPRESS_DOMAIN ),
                    'desc'    => __( 'Added in exported HTML files', DOCSPRESS_DOMAIN ),
                    'type'    => 'textarea',
                    'size'    => 'large',
                    'default' => $export_class->custom_js
                ),
                array(
                    'name'    => 'clean_html',
                    'label'   => __( 'Clean HTML RegExp', DOCSPRESS_DOMAIN ),
                    'desc'    => __( 'Each regexp on new line (change it only if you understand what you do)', DOCSPRESS_DOMAIN ),
                    'type'    => 'textarea',
                    'size'    => 'large',
                    'default' => str_replace('\'', "\\'", $export_class->clean_html_regexp)
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
    function plugin_page() {
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
    function get_pages() {
        $pages_options = array( '' => __( '&mdash; Select Page &mdash;', DOCSPRESS_DOMAIN ) );
        $pages = get_pages( array(
            'numberposts'  => -1
        ) );

        if ( $pages ) {
            foreach ($pages as $page) {
                $pages_options[$page->ID] = $page->post_title;
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
