<?php
/**
 * Docs archive loop title template
 *
 * This template can be overridden by copying it to yourtheme/docspress/archive/loop-title.php.
 *
 * @author  nK
 * @package @@plugin_name/Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$articles = get_pages(
    array(
        'child_of' => get_the_ID(),
        'post_type' => 'docs',
    )
);
$articles_count = count( $articles );

?>

<a href="<?php the_permalink(); ?>" class="docspress-archive-list-item-title">
    <?php the_post_thumbnail( 'docspress_archive' ); ?>
    <span>
        <span>
            <?php
            // translators: %s articles count.
            printf( esc_html( _n( '%s Article', '%s Articles', $articles_count, '@@text_domain' ) ), esc_html( $articles_count ) );
            ?>
        </span>
        <h2><?php the_title(); ?></h2>
    </span>
</a>
