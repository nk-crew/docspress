<?php
/**
 * Docs archive description template
 *
 * This template can be overridden by copying it to yourtheme/docspress/archive/description.php.
 *
 * @author  nK
 * @package @@plugin_name/Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( docspress()->get_docs_page_content() ) : ?>
    <div class="docspress-archive-description">
        <?php echo docspress()->get_docs_page_content(); // WP XSS OK. ?>
    </div>
<?php
endif;
