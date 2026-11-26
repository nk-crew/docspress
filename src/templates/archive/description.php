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

$docspress_description = docspress()->get_docs_page_content();

if ( $docspress_description ) : ?>
    <div class="docspress-archive-description">
        <?php
        // phpcs:ignore
        echo $docspress_description;
        ?>
    </div>
    <?php
endif;
