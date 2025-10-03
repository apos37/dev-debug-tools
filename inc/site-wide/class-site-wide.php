<?php
/**
 * Site Wide
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class SiteWide {

    /**
     * Constructor
     */
    public function __construct() {

        // User logins to Discord
        if ( get_option( 'ddtt_online_users_discord_enable', false ) ) {
            add_action( 'wp_login', [ $this, 'send_login_to_discord' ], 10, 2 );
        }
        
        // WP Mail failure logging
        if ( get_option( 'ddtt_wp_mail_failure', false ) ) {
            add_action( 'wp_mail_failed', [ $this, 'mail_failure' ], 10, 1 );
        }

        // Fatal errors to Discord
        if ( get_option( 'ddtt_fatal_discord_enable', false ) ) {
            register_shutdown_function( [ $this, 'send_fatal_errors_to_discord' ] );
        }

        // Enqueue assets
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );

    } // End __construct()


    /**
     * Send user login details to Discord via webhook
     *
     * @param string  $user_login Username.
     * @param WP_User $user       WP_User object of the logged-in user.
     */
    public function send_login_to_discord( $user_login, $user ) {
        $webhook = filter_var( get_option( 'ddtt_online_users_discord_webhook' ), FILTER_SANITIZE_URL );
        if ( $webhook != '' ) {

            $site_url = home_url();
            $domain = wp_parse_url( $site_url, PHP_URL_HOST );
            if ( ! $domain ) {
                $domain = $site_url; // fallback
            }

            $website = get_bloginfo( 'name' );
            if ( ! $website || $website == '' ) {
                $website = $domain;
            }

            // Discord args
            $args = [
                'embed'          => true,
                'title'          => __( 'User Login on', 'dev-debug-tools' ) . ' ' . $website,
                'title_url'      => $site_url,
                'color'          => '258F9B',
                'disable_footer' => false,
                'fields' => [
                    [
                        'name'   => '--------------------',
                        'value'  => ' ',
                        'inline' => false
                    ],
                    [
                        'name'   => __( 'Display Name', 'dev-debug-tools' ),
                        'value'  => sanitize_user( $user->display_name ),
                        'inline' => false
                    ],
                    [
                        'name'   => __( 'Email', 'dev-debug-tools' ),
                        'value'  => sanitize_email( $user->user_email ),
                        'inline' => false
                    ],
                    [
                        'name'   => __( 'User ID', 'dev-debug-tools' ),
                        'value'  => absint( $user->ID ),
                        'inline' => false
                    ]
                ]
            ];

            /**
             * Filter the Discord webhook message arguments before sending.
             */
            $args = apply_filters( 'ddtt_online_users_discord_message_args', $args, $user_login, $user );

            // Send the message
            DiscordWebhook::send( $webhook, $args );
        }
    } // End send_login_to_discord()


    /**
     * Log mail failures to the debug log
     *
     * @param WP_Error $wp_error The WP_Error object containing mail error details.
     */
    public function mail_failure( $wp_error ) {
        error_log( esc_html( __( 'Mail sending failed with the following error(s):', 'dev-debug-tools' ) ) ); // phpcs:ignore
        error_log( print_r( $wp_error, true) ); // phpcs:ignore
    } // End mail_failure()


    /**
     * Send fatal errors to Discord via webhook
     *
     * This method checks for the last error and if it's a fatal error,
     * it sends the details to a configured Discord webhook.
     */
    public function send_fatal_errors_to_discord() {
        $error = error_get_last();
        $errors = [ E_ERROR, E_PARSE ];

        if ( isset( $error[ 'type' ] ) && in_array( $error[ 'type' ], $errors ) ) {

            $webhook = filter_var( get_option( 'ddtt_fatal_discord_webhook' ), FILTER_SANITIZE_URL );
            if ( $webhook != '' ) {

                $site_url = home_url();
                $domain = wp_parse_url( $site_url, PHP_URL_HOST );
                if ( ! $domain ) {
                    $domain = $site_url; // fallback
                }
                
                $website = get_bloginfo( 'name' );
                if ( ! $website || $website == '' ) {
                    $website = $domain;
                }

                $message = sanitize_textarea_field( $error[ 'message' ] );
                $message = str_replace( ABSPATH, '/', $message );
                $stack_trace_marker = __( 'Stack trace:', 'dev-debug-tools' );
                if ( strpos( $message, $stack_trace_marker ) !== false ) {
                    $message = substr( $message, 0, strpos( $message, $stack_trace_marker ) );
                }
                $message = mb_strimwidth( $message, 0, 500, '...' );

                $file = sanitize_text_field( $error[ 'file' ] );
                $file = str_replace( ABSPATH, '/', $file );

                // Discord args
                $args = [
                    'embed'          => true,
                    'title'          => __( 'New Error on', 'dev-debug-tools' ) . ' ' . $website,
                    'title_url'      => Bootstrap::tool_url( 'logs' ),
                    'color'          => 'FF0000',
                    'desc'           => $message,
                    'disable_footer' => false,
                    'fields' => [
                        [
                            'name'   => '--------------------',
                            'value'  => ' ',
                            'inline' => false
                        ],
                        [
                            'name'   => __( 'File Path', 'dev-debug-tools' ),
                            'value'  => $file,
                            'inline' => false
                        ],
                        [
                            'name'   => __( 'Line', 'dev-debug-tools' ),
                            'value'  => absint( $error[ 'line' ] ),
                            'inline' => false
                        ],
                        [
                            'name'   => __( 'Type', 'dev-debug-tools' ),
                            'value'  => absint( $error[ 'type' ] ) === 4 ? 'PARSE ERROR' : 'FATAL ERROR',
                            'inline' => false
                        ]
                    ]
                ];


                /**
                 * Filter the Discord webhook message arguments before sending.
                 */
                $args = apply_filters( 'ddtt_fatal_discord_message_args', $args, $error );

                // Send the message
                DiscordWebhook::send( $webhook, $args );
            }
        }
    } // End send_fatal_errors_to_discord()


    /**
     * Enqueue assets
     */
    public function enqueue_assets() : void {
        $version = Bootstrap::script_version();

        wp_enqueue_style(
            'ddtt-site-wide',
            Bootstrap::url( 'inc/site-wide/styles.css' ),
            [],
            $version
        );
    } // End enqueue_assets()

}


new SiteWide();