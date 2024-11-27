<?php
/**
 * Single docs comments template
 *
 * This template can be overridden by copying it to yourtheme/docspress/single/comments.php.
 *
 * @author  nK
 * @package @@plugin_name/Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// If comments are open or we have at least one comment, load up the comment template.
if ( comments_open() || get_comments_number() ) {
    // Prevent deprecated warning.
    // Thanks to https://github.com/WordPress/gutenberg/blob/d9eb6d9a6d64650f08ee68792b88f914de62cda1/packages/block-library/src/comments/index.php#L53-L60 .
    add_filter( 'deprecated_file_trigger_error', '__return_false' );
    comments_template();
    remove_filter( 'deprecated_file_trigger_error', '__return_false' );
}
