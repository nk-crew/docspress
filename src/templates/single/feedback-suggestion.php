<?php
/**
 * Single docs feedback suggestion template
 *
 * This template can be overridden by copying it to yourtheme/docspress/single/feedback-suggestion.php.
 *
 * @author  nK
 * @package @@plugin_name/Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$admin_email = docspress()->get_option( 'show_feedback_suggestion_email', 'docspress_single', '' ) ? : get_option( 'admin_email' );

if (
    ! docspress()->get_option( 'show_feedback_buttons', 'docspress_single', true ) ||
    ! docspress()->get_option( 'show_feedback_suggestion', 'docspress_single', false ) ||
    ! $admin_email
) {
    return;
}

$from = '';

if ( is_user_logged_in() ) {
    $user = wp_get_current_user();

    if ( $user->display_name ) {
        $from = $user->display_name;
    }

    if ( $user->user_email ) {
        $from .= ( $from ? ' <' : '' ) . $user->user_email . ( $from ? '>' : '' );
    }
}

?>

<form class="docspress-single-feedback-suggestion" action="" method="post" style="display: none;">
    <h3><?php echo esc_html__( 'How can we improve this documentation?', '@@text_domain' ); ?></h3>

    <div>
        <textarea name="suggestion" placeholder="<?php echo esc_attr__( 'Your suggestions', '@@text_domain' ); ?>" required></textarea>

        <input name="from" type="text" value="<?php echo esc_attr( $from ); ?>" placeholder="<?php echo esc_attr__( 'Your Name or Email (Optional)', '@@text_domain' ); ?>">

        <button class="docspress-btn docspress-btn-md"><?php echo esc_attr__( 'Send', '@@text_domain' ); ?></button>

        <input type="hidden" name="id" value="<?php echo esc_attr( get_the_ID() ); ?>">
        <input type="hidden" name="action" value="docspress_suggestion">
    </div>
</form>
