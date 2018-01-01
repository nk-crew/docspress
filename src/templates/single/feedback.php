<?php
/**
 * Single docs feedback template
 *
 * This template can be overridden by copying it to yourtheme/docspress/single/feedback.php.
 *
 * @author  nK
 * @package DocsPress/Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! docspress()->get_option( 'show_feedback_buttons', 'docspress_single', true ) ) {
    return;
}
?>

<div class="docspress-single-feedback">
    <?php
    $positive = (int) get_post_meta( get_the_ID(), 'positive', true );
    $negative = (int) get_post_meta( get_the_ID(), 'negative', true );

    $positive_title = $positive ? sprintf( _n( '%d person found this useful', '%d persons found this useful', $positive, DOCSPRESS_DOMAIN ), number_format_i18n( $positive ) ) : __( 'No votes yet', DOCSPRESS_DOMAIN );
    $negative_title = $negative ? sprintf( _n( '%d person found this not useful', '%d persons found this not useful', $negative, DOCSPRESS_DOMAIN ), number_format_i18n( $negative ) ) : __( 'No votes yet', DOCSPRESS_DOMAIN );
    ?>

    <div>
        <?php _e( 'Was this helpful to you?', DOCSPRESS_DOMAIN ); ?>
    </div>

    <div class="docspress-single-feedback-vote">
        <a href="#" class="docspress-btn" data-id="<?php the_ID(); ?>" data-type="positive" title="<?php echo esc_attr( $positive_title ); ?>">
            <?php _e( 'Yes', DOCSPRESS_DOMAIN ); ?>

            <?php if ( $positive ) { ?>
                <span class="badge"><?php echo number_format_i18n( $positive ); ?></span>
            <?php } ?>
        </a>
        <a href="#" class="docspress-btn" data-id="<?php the_ID(); ?>" data-type="negative" title="<?php echo esc_attr( $negative_title ); ?>">
            <?php _e( 'No', DOCSPRESS_DOMAIN ); ?>

            <?php if ( $negative ) { ?>
                <span class="badge"><?php echo number_format_i18n( $negative ); ?></span>
            <?php } ?>
        </a>
    </div>
</div>