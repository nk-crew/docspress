<?php
/**
 * Suggestion save and send email.
 *
 * @package @@plugin_name
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Suggestion
 *
 * @class       DocsPress_Suggestion
 * @package     docspress
 */
class DocsPress_Suggestion {
    /**
     * Send suggestion.
     *
     * @param array $data - suggestion data.
     *
     * @return boolean
     */
    public static function send( $data ) {
        self::mail_before_send();

        $success = self::process_mail( $data );

        self::mail_after_send();

        return $success;
    }

    /**
     * Process email using wp_mail function.
     *
     * @param array $data - Form block attributes.
     *
     * @return boolean
     */
    public static function process_mail( $data ) {
        // phpcs:ignore
        $wp_email           = 'wordpress@' . preg_replace( '#^www\.#', '', strtolower( $_SERVER['SERVER_NAME'] ) );
        $add_reply_to_email = true;
        $initial_from       = $wp_email;

        if ( isset( $data['from'] ) && ! empty( $data['from'] ) ) {
            $from         = $data['from'];
            $initial_from = $from;

            if ( filter_var( $from, FILTER_VALIDATE_EMAIL ) ) {
                $from               = filter_var( $from, FILTER_VALIDATE_EMAIL );
                $add_reply_to_email = false;
            }
        } elseif ( is_user_logged_in() ) {
            $from = '';

            $user = wp_get_current_user();

            if ( $user->display_name ) {
                $from = $user->display_name;
            }

            if ( $user->user_email ) {
                if ( $from ) {
                    $from = '"' . esc_attr( $from ) . '" <' . $user->user_email . '>';
                } else {
                    $from = $user->user_email;
                }

                $add_reply_to_email = false;
            }
        } else {
            $from = esc_html__( 'Anonymous', '@@text_domain' );
        }

        $reply_to = $from;
        if ( $add_reply_to_email ) {
            $reply_to = '"' . esc_attr( $reply_to ) . '" <' . $wp_email . '>';
        }

        $data['from']       = $from;
        $data['ip_address'] = self::get_ip_address();
        $data['blogname']   = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

        $admin_email = docspress()->get_option( 'show_feedback_suggestion_email', 'docspress_single', '' ) ? docspress()->get_option( 'show_feedback_suggestion_email', 'docspress_single', '' ) : get_option( 'admin_email' );

        // translators: %s - user name.
        $subject = sprintf( __( 'New Doc Suggestion by "%s"', '@@text_domain' ), $initial_from );

        // Prepare headers.
        $headers = array(
            'Content-Type: text/html; charset="' . get_option( 'blog_charset' ) . '"',
            'From: "' . $data['blogname'] . '" <' . $admin_email . '>',
            'Return-Path: ' . $admin_email,
            'Reply-To: ' . $reply_to,
        );

        // Prepare message.
        $message = self::get_mail_html( $data );

        return wp_mail( $admin_email, wp_specialchars_decode( $subject ), $message, $headers );
    }

    /**
     * Get mail HTML template.
     *
     * @param array $attributes - From block attributes.
     *
     * @return string
     */
    public static function get_mail_html( $attributes ) {
        ob_start();

        docspress()->get_template_part(
            'feedback-mail',
            array(
                'data' => $attributes,
            )
        );

        return ob_get_clean();
    }

    /**
     * Get a clients IP address
     *
     * @return string
     */
    public static function get_ip_address() {
        $ipaddress = '';

        // phpcs:disable
        if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif ( isset( $_SERVER['HTTP_X_FORWARDED'] ) ) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        } elseif ( isset( $_SERVER['HTTP_FORWARDED_FOR'] ) ) {
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif ( isset( $_SERVER['HTTP_FORWARDED'] ) ) {
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        } elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $ipaddress = 'UNKNOWN';
        }
        // phpcs:enable

        return $ipaddress;
    }

    /**
     * Mail before send.
     */
    public static function mail_before_send() {
        add_filter( 'wp_mail_content_type', array( __CLASS__, 'get_content_type' ) );
    }

    /**
     * Mail after send.
     */
    public static function mail_after_send() {
        remove_filter( 'wp_mail_content_type', array( __CLASS__, 'get_content_type' ) );
    }

    /**
     * Change wp_mail content type to HTML.
     *
     * @return string
     */
    public static function get_content_type() {
        return 'text/html';
    }
}
