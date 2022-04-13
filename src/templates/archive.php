<?php
/**
 * Docs archive template
 *
 * This template can be overridden by copying it to yourtheme/docspress/archive.php.
 *
 * @author  nK
 * @package @@plugin_name/Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

docspress()->get_template_part( 'global/wrap-start' );

docspress()->get_template_part( 'archive/title' );

docspress()->get_template_part( 'archive/page' );

docspress()->get_template_part( 'global/wrap-end' );
