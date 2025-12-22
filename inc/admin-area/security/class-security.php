<?php
/**
 * Security
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Security {

    /**
     * Nonce action name
     *
     * @var string
     */
    private $nonce = 'ddtt_security_nonce';


    /**
     * Stored password hash
     *
     * @var string
     */
    private $stored_pw;


    /**
     * Constructor
     */
    public function __construct() {

        // Get stored password hash
        $this->stored_pw = sanitize_text_field( get_option( 'ddtt_pass', '' ) );

        // Check if feature is enabled
        if ( ! get_option( 'ddtt_enable_pass', false ) || empty( $this->stored_pw ) ) {
            return;
        }

        // Protect admin pages
        add_action( 'current_screen', [ $this, 'protect_admin_pages' ] );

        // Ajax heartbeat handler
        add_action( 'wp_ajax_ddtt_check_password', [ $this, 'ajax_check_password' ] );
        add_action( 'wp_ajax_nopriv_ddtt_check_password', '__return_false' );

    } // End __construct()


    /**
     * Render the password modal HTML
     */
    public function protect_admin_pages( $screen ) {
        ob_start();

        $protect_current = false;

        // Core plugin pages (by screen ID)
        $screens = [];
        $pages   = AdminMenu::pages();
        foreach ( $pages as $slug => $label ) {
            $screens[] = 'developer-debug-tools_page_dev-debug-' . $slug;
        }

        // Additional pages (full URLs)
        $addt_pages = Settings::sanitize_url_plus( get_option( 'ddtt_secure_pages', [] ) );
        $current_url = ( is_ssl() ? 'https://' : 'http://' ) .
            ( isset( $_SERVER[ 'HTTP_HOST' ] ) ? sanitize_text_field( wp_unslash( $_SERVER[ 'HTTP_HOST' ] ) ) : '' ) .
            ( isset( $_SERVER[ 'REQUEST_URI' ] ) ? sanitize_text_field( wp_unslash( $_SERVER[ 'REQUEST_URI' ] ) ) : '' );
        $current_url = esc_url_raw( $current_url );
        
        // Check core plugin pages by screen ID
        if ( in_array( $screen->id, $screens, true ) ) {
            $protect_current = true;
        }

        // Check additional pages by URL
        if ( ! $protect_current && ! empty( $addt_pages ) && is_array( $addt_pages ) ) {
            foreach ( $addt_pages as $url ) {
                if ( isset( $url ) && esc_url_raw( $url ) === $current_url ) {
                    $protect_current = true;
                    break;
                }
            }
        }

        if ( ! $protect_current ) {
            ob_end_clean();
            return;
        }

        // Check the transient token
        $token = isset( $_COOKIE['ddtt_pass_token'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['ddtt_pass_token'] ) ) : '';
        $token_key = 'ddtt_pass_' . $token;
        if ( ! $token || ! get_transient( $token_key ) ) {
            ob_end_clean();
            $this->render_standalone_password_page_and_exit();
        }

        ob_end_clean();
    } // End protect_admin_pages()

    
    /**
     * Render a standalone password entry page and exit
     */
    private function render_standalone_password_page_and_exit() {
        $version  = Bootstrap::script_version();
        $css_url  = Bootstrap::url( 'inc/admin-area/security/styles.css' );
        $font_url = Bootstrap::url( 'inc/hub/fonts/michroma.woff2' );
        $js_url   = Bootstrap::url( 'inc/admin-area/security/scripts.js' );

        $ip = isset( $_SERVER[ 'REMOTE_ADDR' ] ) ? sanitize_text_field( wp_unslash( $_SERVER[ 'REMOTE_ADDR' ] ) ) : '';
        if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
            $ip = 'unknown';
        }

        $lockout_key = 'ddtt_pass_lockout_' . md5( $ip );
        $locked_out_at = get_transient( $lockout_key ); // Timestamp in seconds
        $locked_out    = ( isset( $locked_out_at ) && $locked_out_at > 0 );

        $lockout_minutes = absint( get_option( 'ddtt_pass_lockout', 10 ) );
        $locked_out_seconds_remaining = 0;

        if ( $locked_out ) {
            $locked_out_seconds_remaining = ( $lockout_minutes * 60 ) - ( time() - $locked_out_at );
            if ( $locked_out_seconds_remaining < 0 ) {
                $locked_out_seconds_remaining = 0;
                delete_transient( $lockout_key ); // expired
                $locked_out = false;
            }
        }


        $payload = [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( $this->nonce ),
            'i18n'     => [
                'error_empty'             => __( 'Please enter a password.', 'dev-debug-tools' ),
                'error_invalid'           => __( 'The password you entered is incorrect. Please try again.', 'dev-debug-tools' ),
                'text_wait'               => __( 'Checking...', 'dev-debug-tools' ),
                'text_unlock'             => __( 'Unlock', 'dev-debug-tools' ),
                'locked_out_title'        => __( 'Locked Out', 'dev-debug-tools' ),
                'locked_out_message'      => __( 'Too many failed password attempts.', 'dev-debug-tools' ),
                'locked_out_countdown'    => __( 'Please try again in %s.', 'dev-debug-tools' ), // phpcs:ignore
                'try_again_button'        => __( 'Try Again', 'dev-debug-tools' ),
                'enter_password_title'    => __( 'Enter Password', 'dev-debug-tools' ),
                'protected_message_line1' => __( 'This area is password protected.', 'dev-debug-tools' ),
                'protected_message_line2' => __( 'Please enter the password to continue.', 'dev-debug-tools' ),
                'access_granted'          => __( 'Access granted!', 'dev-debug-tools' ),
            ],
        ];
        
        // Send an HTTP 403 status
        status_header( 403 );
        ?>
        <!doctype html>
        <html lang="<?php echo esc_attr( get_bloginfo( 'language' ) ); ?>">
            <head>
                <meta charset="<?php echo esc_attr( get_bloginfo( 'charset' ) ); ?>">
                <meta name="viewport" content="width=device-width,initial-scale=1">
                <title><?php esc_html_e( 'Password Required', 'dev-debug-tools' ); ?></title>

                <!-- Preload Font -->
                <link id="ddtt-font-michroma" rel="preload" href="<?php echo esc_url( $font_url ); ?>" as="font" type="font/woff2" crossorigin="anonymous">

                <!-- Font-face -->
                 <?php // phpcs:ignore ?>
                <style>
                    @font-face {
                        font-family: "Michroma";
                        src: url("<?php echo esc_url( $font_url ); ?>") format( "woff2" );
                        font-weight: normal;
                        font-style: normal;
                        font-display: swap;
                    }
                </style>

                <!-- CSS -->
                <?php // phpcs:ignore ?>
                <link rel="stylesheet" href="<?php echo esc_url( $css_url ); ?>?ver=<?php echo esc_attr( $version ); ?>">

                <!-- jQuery -->
                <?php // phpcs:ignore ?>
                <script src="<?php echo esc_url( includes_url( 'js/jquery/jquery.js' ) ); ?>"></script>

                <!-- Localized JS Payload -->
                <?php // phpcs:ignore ?>
                <script>
                    var ddtt_security = <?php echo wp_json_encode( $payload ); ?>;
                </script>

                <!-- Plugin JS -->
                <?php // phpcs:ignore ?>
                <script src="<?php echo esc_url( $js_url ); ?>?ver=<?php echo esc_attr( $version ); ?>"></script>
            </head>
            <body class="ddtt-password-body">
                <?php $mode_class = Helpers::is_dark_mode() ? 'ddtt-dark-mode' : 'ddtt-light-mode'; ?>

                <div id="ddtt-password-overlay" class="ddtt-password-overlay <?php echo esc_attr( $mode_class ); ?>" role="dialog" aria-modal="true" aria-labelledby="ddtt-password-title">
                    <div class="ddtt-password-modal" id="ddtt-password-modal">

                        <!-- Locked Out -->
                        <?php if ( $locked_out ) : ?>
                            <h1 id="ddtt-password-title"><?php esc_html_e( 'Locked Out', 'dev-debug-tools' ); ?></h1>
                            <p id="ddtt-lockout-message">
                                <?php esc_html_e( 'Too many failed password attempts.', 'dev-debug-tools' ); ?><br>
                                <span id="ddtt-countdown-wrapper" style="display:none;">
                                    <?php esc_html_e( 'Please try again in ', 'dev-debug-tools' ); ?>
                                    <span id="ddtt-countdown" data-seconds="<?php echo esc_attr( $locked_out_seconds_remaining ); ?>"></span>.
                                </span>
                            </p>
                            <div id="ddtt-try-again-container" style="display:none;">
                                <button id="ddtt-try-again" type="button"><?php esc_html_e( 'Try Again', 'dev-debug-tools' ); ?></button>
                            </div>

                        <!-- Enter Password -->
                        <?php else : ?>
                            <p>
                                <?php 
                                echo esc_html__( 'This area is password protected.', 'dev-debug-tools' ); 
                                ?><br><?php 
                                echo esc_html__( 'Please enter the password to continue.', 'dev-debug-tools' ); 
                                ?>
                            </p>

                            <h1 id="ddtt-password-title"><?php esc_html_e( 'Enter Password', 'dev-debug-tools' ); ?></h1>

                            <div class="ddtt-password-input-wrapper">
                                <input type="password" id="ddtt-password-input" aria-label="<?php esc_attr_e( 'Password', 'dev-debug-tools' ); ?>" autocomplete="off" />
                                <button type="button" id="ddtt-password-toggle" aria-label="<?php esc_attr_e( 'Show/Hide password', 'dev-debug-tools' ); ?>">👁</button>
                            </div>

                            <div>
                                <button id="ddtt-password-submit" type="button"><?php esc_html_e( 'Unlock', 'dev-debug-tools' ); ?></button>
                            </div>

                            <p id="ddtt-password-error" style="display:none;"></p>

                        <?php endif; ?>
                    </div>
                </div>
            </body>
        </html>
        <?php
        exit;
    } // End render_standalone_password_page_and_exit()


    /**
     * Handle AJAX password check
     */
    public function ajax_check_password() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! Helpers::is_dev() ) {
            wp_send_json_error( [ 'message' => __( 'You do not have permission.', 'dev-debug-tools' ) ] );
        }

        $input = sanitize_text_field( wp_unslash( $_POST[ 'password' ] ?? '' ) );

        // Sanitize and validate IP
        $ip = isset( $_SERVER[ 'REMOTE_ADDR' ] ) ? sanitize_text_field( wp_unslash( $_SERVER[ 'REMOTE_ADDR' ] ) ) : '';
        if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
            $ip = 'unknown';
        }

        $attempt_key      = 'ddtt_pass_attempts_' . md5( $ip );
        $lockout_key      = 'ddtt_pass_lockout_' . md5( $ip );
        $attempts_allowed = absint( get_option( 'ddtt_pass_attempts', 4 ) );
        $lockout_minutes  = absint( get_option( 'ddtt_pass_lockout', 10 ) );

        // Check if currently locked out
        if ( get_transient( $lockout_key ) ) {
            apply_filters( 'ddtt_log_error', 'ajax_check_password', new \Exception( 'Too many failed attempts. Locked out.' ), [ 'step' => 'lockout_check' ] );
            wp_send_json_error( [ 'message' => sprintf(
                // translators: %d: number of minutes until lockout expires
                __( 'Too many failed attempts. Try again in %d minutes.', 'dev-debug-tools' ),
                $lockout_minutes
            ) ] );
        }

        // Correct password
        if ( empty( $this->stored_pw ) || wp_check_password( $input, $this->stored_pw ) ) {

            // Reset attempts on success
            delete_transient( $attempt_key );
            delete_transient( $lockout_key );

            $minutes = absint( get_option( 'ddtt_pass_exp', 5 ) );
            $token   = wp_generate_password( 32, false );
            set_transient( 'ddtt_pass_' . $token, true, $minutes * 60 );

            // Set token cookie
            setcookie( 'ddtt_pass_token', $token, time() + $minutes * 60, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );

            wp_send_json_success( [ 'message' => __( 'Access granted!', 'dev-debug-tools' ) ] );
        }

        // Incorrect password
        $attempts = absint( get_transient( $attempt_key ) );
        $attempts++;
        set_transient( $attempt_key, $attempts, $lockout_minutes * 60 );

        $remaining = max( 0, $attempts_allowed - $attempts );

        // Lockout if exceeded
        if ( $attempts >= $attempts_allowed ) {
            $current_time = time();
            set_transient( $lockout_key, $current_time, $lockout_minutes * 60 );
            delete_transient( $attempt_key ); // reset attempts

            $seconds_remaining = $lockout_minutes * 60;

            apply_filters( 'ddtt_log_error', 'ajax_check_password', new \Exception( 'Too many failed attempts. Locked out.' ), [ 'step' => 'lockout_enforced' ] );
            wp_send_json_error( [
                'message'           => __( 'Too many failed attempts. Locked out.', 'dev-debug-tools' ),
                'lockout_seconds'   => $seconds_remaining,
            ] );
        }

        // Show attempts left in message
        $message = __( 'Incorrect password.', 'dev-debug-tools' ) . '<br>' .
            // translators: %d: number of attempts remaining
            sprintf( _n(
                'You have %d more attempt until locked out.',
                'You have %d more attempts until locked out.',
                $remaining,
                'dev-debug-tools'
            ), $remaining );

        apply_filters( 'ddtt_log_error', 'ajax_check_password', new \Exception( 'Incorrect password.' ), [ 'step' => 'invalid_password' ] );
        wp_send_json_error( [ 'message' => $message ] );
    } // End ajax_check_password()

}


new Security();