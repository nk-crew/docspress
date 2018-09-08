<?php
/**
 * Single docs content title template
 *
 * This template can be overridden by copying it to yourtheme/docspress/single/content-title.php.
 *
 * @author  nK
 * @package @@plugin_name/Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>
<header class="entry-header">
    <?php the_title( '<h1 class="entry-title" itemprop="headline">', '</h1>' ); ?>
</header><!-- .entry-header -->
