<?php
/**
 * Docs archive loop title template
 *
 * This template can be overridden by copying it to yourtheme/docspress/archive/loop-title.php.
 *
 * @author  nK
 * @package DocsPress/Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$articles = get_pages( array( 'child_of' => get_the_ID(), 'post_type' => 'docs'));
$articles_count = count($articles);

?>

<a href="<?php the_permalink(); ?>" class="docspress-archive-list-item-title">
    <?php the_post_thumbnail( 'thumbnail' ); ?>
    <span><?php printf( _n( '%s Article', '%s Articles', $articles_count, DOCSPRESS_DOMAIN ), $articles_count ); ?></span>
    <strong><?php the_title(); ?></strong>
</a>
