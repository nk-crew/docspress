<?php
/**
 * Documentation search results.
 *
 * @package @@plugin_name/Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$keys = implode(
    '|',
    array_map(
        static function ( $key ) {
            return preg_quote( $key, '/' );
        },
        array_filter( explode( ' ', $query->get( 's' ) ), 'strlen' )
    )
);

?>
<ul class="docspress-search-list">
    <?php
    if ( $query->have_posts() ) :
        while ( $query->have_posts() ) :
            $query->the_post();
            // highlight search terms in title.
            // phpcs:disable
            $title   = wp_trim_words( get_the_title(), 10 );
            $title   = $keys ? preg_replace( '/(' . $keys . ')/iu', '<mark>\0</mark>', $title ) : $title;
            $excerpt = wp_trim_words( get_the_excerpt(), 10 );
            $excerpt = $keys ? preg_replace( '/(' . $keys . ')/iu', '<mark>\0</mark>', $excerpt ) : $excerpt;
            // phpcs:enable
            ?>
            <li class="docspress-search-list-item">
                <a href="<?php the_permalink(); ?>">
                    <span class="docspress-search-list-item-title">
                        <strong><?php echo wp_kses( $title, array( 'mark' => array() ) ); ?></strong>
                    </span>
                    <span class="docspress-search-list-item-excerpt"><?php echo wp_kses( $excerpt, array( 'mark' => array() ) ); ?></span>
                </a>
            </li>
            <?php
        endwhile;
    endif;
    ?>
</ul>
