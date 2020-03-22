<?php
/**
 * Single docs adjacent links template
 *
 * This template can be overridden by copying it to yourtheme/docspress/single/adjacent-links.php.
 *
 * @author  nK
 * @package @@plugin_name/Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// phpcs:disable
$prev_post_id = docspress()->get_previous_adjacent_doc_id();
$next_post_id = docspress()->get_next_adjacent_doc_id();
// phpcs:enable

if ( $prev_post_id || $next_post_id ) {
    ?>
    <nav class="docspress-single-adjacent-nav">
        <h3 class="docspress-sr-only"><?php echo esc_html__( 'Doc navigation', '@@text_domain' ); ?></h3>
        <?php if ( $prev_post_id ) : ?>
            <span class="nav-previous">
                <a href="<?php echo esc_url( get_the_permalink( $prev_post_id ) ); ?>" class="docspress-btn docspress-btn-md"><span class="icon">&lt;</span> <?php echo esc_html( get_the_title( $prev_post_id ) ); ?></a>
            </span>
        <?php endif; ?>

        <?php if ( $next_post_id ) : ?>
            <span class="nav-next">
                <a href="<?php echo esc_url( get_the_permalink( $next_post_id ) ); ?>" class="docspress-btn docspress-btn-md"><?php echo esc_html( get_the_title( $next_post_id ) ); ?> <span class="icon">&gt;</span></a>
            </span>
        <?php endif; ?>
    </nav>
    <?php
}
