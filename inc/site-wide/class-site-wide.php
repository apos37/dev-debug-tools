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

        // Add body class
        add_action( 'admin_body_class', [ $this, 'body_class' ] );

        // User logins to Discord
        if ( get_option( 'ddtt_online_users_discord_enable', false ) ) {
            add_action( 'wp_login', [ $this, 'send_login_to_discord' ], 10, 2 );
        }

        // Backtrace logging
        if ( get_option( 'ddtt_backtrace_deprecations', false ) ) {
            add_action( 'deprecated_function_run', [ $this, 'log_deprecated_backtrace' ], 10, 1 );
            add_action( 'deprecated_argument_run', [ $this, 'log_deprecated_backtrace' ], 10, 1 );
        }
        if ( get_option( 'ddtt_backtrace_notices', false ) ||
             get_option( 'ddtt_backtrace_warnings', false ) ||
             get_option( 'ddtt_backtrace_errors', false ) ) {

            set_error_handler( [ $this, 'log_php_backtrace' ] );
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
     * Add custom body class for devs
     *
     * @param string $classes Existing body classes.
     * 
     * @return string
     */
    public function body_class( $classes ) : string {
        if ( Helpers::is_dev() ) {
            $classes .= ' ddtt-is-dev';
        }
        return $classes;
    } // End body_class()


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
     * Format backtrace for logging
     *
     * @param array $backtrace The backtrace array.
     * 
     * @return string
     */
    public static function log_deprecated_backtrace( $function ) {
        $backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
        $formatted = Helpers::format_backtrace( $backtrace );
        error_log( "{$backtrace} for deprecated function '{$function}':\n{$formatted}. You may disable backtraces for this type of error in the Developer Debug Log Settings." );
    } // End log_deprecated_backtrace()


    /**
     * Log PHP backtrace for errors, warnings, and notices
     *
     * @param int    $errno   The level of the error raised.
     * @param string $errstr  The error message.
     * @param string $errfile The filename that the error was raised in.
     * @param int    $errline The line number the error was raised at.
     * 
     * @return bool
     */
    public function log_php_backtrace( $errno, $errstr, $errfile, $errline ) {
        $backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
        array_shift( $backtrace );
        $formatted = Helpers::format_backtrace( $backtrace );

        switch ( $errno ) {

            case E_NOTICE:
            case E_USER_NOTICE:
                if ( ! get_option( 'ddtt_backtrace_notices', false ) ) {
                    return false;
                }
                $type = 'Notice';
                break;

            case E_WARNING:
            case E_USER_WARNING:
                if ( ! get_option( 'ddtt_backtrace_warnings', false ) ) {
                    return false;
                }
                $type = 'Warning';
                break;

            case E_ERROR:
            case E_USER_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
                if ( ! get_option( 'ddtt_backtrace_errors', false ) ) {
                    return false;
                }
                $type = 'Error';
                break;

            default:
                return false;
        }

        // Append backtrace to the original error message
        $backtrace = __( "Backtrace", 'dev-debug-tools' );
        $errstr .= "\n" . $backtrace . ":\n" . $formatted;

        // Log the message as a single line
        error_log( "{$type} {$backtrace} for: '{$errstr}' in {$errfile}( {$errline} ).\nYou may disable backtraces for this type of error in the Developer Debug Log Settings." );

        return false;
    } // End log_php_backtrace()


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