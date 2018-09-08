<?php
/**
 * Single docs footer template
 *
 * This template can be overridden by copying it to yourtheme/docspress/single/footer.php.
 *
 * @author  nK
 * @package @@plugin_name/Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>

<footer class="entry-footer">
    <div itemprop="author" itemscope itemtype="https://schema.org/Person">
        <meta itemprop="name" content="<?php echo esc_attr( get_the_author() ); ?>" />
        <meta itemprop="url" content="<?php echo esc_attr( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>" />
    </div>

    <meta itemprop="datePublished" content="<?php echo esc_attr( get_the_time( 'c' ) ); ?>"/>
    <time itemprop="dateModified" datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>">
        <?php
        // translators: %s - last time modified.
        printf( esc_html__( 'Last modified %s', '@@text_domain' ), esc_html( get_the_modified_date() ) );
        ?>
    </time>
</footer>
