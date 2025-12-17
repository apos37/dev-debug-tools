<?php
/**
 * Emergency Admin Login Recovery
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/************************* USE AT YOUR OWN RISK!! *************************/
/************************* USE AT YOUR OWN RISK!! *************************/
/************************* USE AT YOUR OWN RISK!! *************************/
/************************* USE AT YOUR OWN RISK!! *************************/
/************************* USE AT YOUR OWN RISK!! *************************/


/**
 * EMERGENCY ADMIN LOGIN BACKDOOR
 *
 * This file provides a TEMPORARY way to regain access to a WordPress
 * administrator account if you are completely locked out.
 *
 * THIS IS A BREAK-GLASS TOOL.
 * DO NOT LEAVE ENABLED.
 */


/**
 * STEP-BY-STEP INSTRUCTIONS
 *
 * Step 1: Update the tokens array
 * --------------------------------
 * Add your WordPress admin user ID and a secure token to the $tokens array.
 * You can generate a secure token here ( 32+ characters recommended ):
 * https://it-tools.tech/token-generator?length=32
 *
 *
 * Step 2: Enable the backdoor
 * --------------------------
 * Set $backdoor_enabled to TRUE.
 *
 *
 * Step 3: Upload this file
 * -----------------------
 * Upload this file as:
 * wp-content/plugins/dev-debug-tools/inc/backdoor.php
 *
 *
 * Step 4: Visit the backdoor URL
 * ------------------------------
 * Use the following URL format in your browser:
 * https://yourdomain.com/?uid=123&token=YOUR_TOKEN_HERE
 * e.g. https://example.com/?uid=1&token=AfLbVk8wYXoT12zVdV6HQbSh0OBbAAyU
 *
 * - uid   = WordPress user ID
 * - token = Token defined in the $tokens array
 *
 *
 * Step 5: Log in
 * --------------
 * If the token is valid and the user is an administrator,
 * the user will be logged in immediately.
 *
 *
 * Step 6: DISABLE THE BACKDOOR
 * ---------------------------
 * After logging in:
 * - Set $backdoor_enabled back to FALSE
 * - Upload the updated backdoor.php file again
 *
 *
 * Step 7: Verify it is disabled
 * -----------------------------
 * Revisit the same backdoor URL via a private/incognito browser window.
 * You should NOT be logged in.
 *
 * NEVER LEAVE THIS FILE ENABLED.
 */


/**
 * ENABLE / DISABLE SWITCH
 *
 * Set to "TRUE" ONLY while actively using this file.
 * Set back to "FALSE" immediately after login.
 */
$backdoor_enabled = FALSE;


class BackDoor {

    /**
     * User ID => Token map
     */
    private array $tokens = [
        1 => 'AfLbVk8wYXoT12zVdV6HQbSh0OBbAAyU',
    ];


    private bool $enabled;


    public function __construct( bool $enabled ) {
        $this->enabled = $enabled;
    } // End __construct()


    public function init() : void {
        if ( ! $this->enabled ) {
            return;
        }

        add_action( 'init', [ $this, 'force_login' ] );
    } // End init()


    /**
     * Force Login
     * Logs in the specified user if the correct token is provided.
     */
    public function force_login() : void {
        if ( ! isset( $_GET[ 'uid' ], $_GET[ 'token' ] ) ) {
            return;
        }

        $user_id = absint( wp_unslash( $_GET[ 'uid' ] ) );
        $token   = sanitize_text_field( wp_unslash( $_GET[ 'token' ] ) );

        if ( ! isset( $this->tokens[ $user_id ] ) ) {
            return;
        }

        if ( ! hash_equals( $this->tokens[ $user_id ], $token ) ) {
            return;
        }

        $user = get_user_by( 'id', $user_id );

        if ( ! $user || ! user_can( $user, 'manage_options' ) ) {
            wp_die(
                'Invalid or unauthorized user.',
                'BackDoor',
                [ 'response' => 403 ]
            );
        }

        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id, true );
        $this->log_and_notify_devs( $user );

        wp_die(
            'Login successful. TELL THE DEVELOPER TO DISABLE THE BACKDOOR IMMEDIATELY.',
            'BackDoor',
            [ 'response' => 200 ]
        );
    } // End force_login()


    /**
     * Notify developers that the backdoor was used
     */
    private function log_and_notify_devs( \WP_User $user ) : void {
        $dev_emails = Helpers::get_devs( true );

        if ( empty( $dev_emails ) || ! is_array( $dev_emails ) ) {
            return;
        }

        $subject = 'Emergency Backdoor Login Used';
        $message = sprintf(
            "The emergency admin login backdoor was used.\n\nUser ID: %d\nUsername: %s\nTime: %s\n\nDISABLE THE BACKDOOR IMMEDIATELY.",
            $user->ID,
            $user->user_login,
            gmdate( 'Y-m-d H:i:s' )
        );

        ddtt_write_log( $message );

        wp_mail( $dev_emails, $subject, $message );
    } // End log_and_notify_devs()
}


( new BackDoor( $backdoor_enabled ) )->init();