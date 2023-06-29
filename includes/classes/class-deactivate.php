<?php
/**
 * Deactivate class
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Initiate the class
 */
new DDTT_DEACTIVATE;


/**
 * Main plugin class.
 */
class DDTT_DEACTIVATE {

    /**
	 * Constructor
	 */
	public function __construct() {

        // Add javascript to footer of plugin page only
        // Note: this is not added to multisite network plugin page, and doesn't work with bulk deactivations
        global $pagenow;
        if ( $pagenow == 'plugins.php' ) {
            add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        }

        // Ajax
        add_action( 'wp_ajax_'.DDTT_GO_PF.'send_feedback_on_deactivate', [ $this, 'send' ] );
        add_action( 'wp_ajax_nopriv_'.DDTT_GO_PF.'send_feedback_on_deactivate', [ $this, 'send' ] );

	} // End __construct()


    /**
     * Send the feedback
     *
     * @return void
     */
    public function send() {
        // First verify the nonce
        if ( !wp_verify_nonce( $_REQUEST[ 'nonce' ], DDTT_GO_PF.'deactivate' ) ) {
            exit( 'No naughty business please' );
        }

        // Get the stuff
        $reason = isset( $_REQUEST[ 'reason' ] ) ? sanitize_key( $_REQUEST[ 'reason' ] ) : false;
        $message = isset( $_REQUEST[ 'comments' ] ) ? sanitize_textarea_field( $_REQUEST[ 'comments' ] ) : '';
        $anonymous = isset( $_REQUEST[ 'anonymous' ] ) ? filter_var( $_REQUEST[ 'anonymous' ], FILTER_VALIDATE_BOOLEAN ) : false;
        $contact = isset( $_REQUEST[ 'contact' ] ) ? filter_var( $_REQUEST[ 'contact' ], FILTER_VALIDATE_BOOLEAN ) : false;
       
        // Check for a message
        if ( $reason ) {

            // Domain
            if ( !$anonymous ) {
                $domain = ddtt_get_domain();
                $title_link = $domain;
            } else {
                $domain = 'Unknown';
                $title_link = DDTT_AUTHOR_URL;
            }

            // Subject
            $subject = DDTT_NAME.' Plugin Deactivated Feedback';

            // From
            if ( !$anonymous ) {
                $user = get_userdata( get_current_user_id() );
                $name = $user->display_name;
                if ( $contact ) {
                    $email = $user->user_email;
                } else {
                    $email = 'Wishes not to be contacted';
                }
            } else {
                $name = 'Anonymous';
                $email = 'Wishes not to be contacted';
            }

            // Reason
            $reasons = [
                'better'   => 'I found a better plugin',
                'short'    => 'I only needed the plugin for a short period',
                'noneed'   => 'I no longer need the plugin',
                'broke'    => 'The plugin broke my site',
                'errors'   => 'Found errors on the plugin',
                'conflict' => 'There is a conflict with another plugin',
                'temp'     => 'It\'s a temporary deactivation. I\'m just debugging an issue.',
                'other'    => 'Other',
            ];
            $reason = isset( $reasons[ $reason ] ) ? $reasons[ $reason ] : $reason;

            /**
             * Post to Discord Support Server
             */

            $args = [
                'embed'          => true,
                'title'          => $subject,
                'title_url'      => $title_link,
                'disable_footer' => true,
                'fields'         => [
                    [
                        'name'   => 'Name',
                        'value'  => $name,
                        'inline' => false
                    ],
                    [
                        'name'   => 'Email',
                        'value'  => $email,
                        'inline' => false
                    ],
                    [
                        'name'   => 'Website',
                        'value'  => $domain,
                        'inline' => false
                    ],
                    [
                        'name'   => 'Reason for Deactivating',
                        'value'  => $reason,
                        'inline' => false
                    ],
                    [
                        'name'   => 'Comments',
                        'value'  => $message,
                        'inline' => false
                    ]
                ]
            ];
            
            // First try sending to Discord
            if ( DDTT_DISCORD::send( $args ) ) {
                $result[ 'type' ] = 'success';

                // Method of sending
                $result[ 'method' ] = 'discord';

            // If it didn't work, send via email
            } else {

                /**
                 * Email as backup
                 */

                // To email
                $to = DDTT_AUTHOR_EMAIL;

                // From email must still come from website to avoid spam, so we'll use the admin email
                if ( $anonymous || ( !$anonymous && !$contact ) ) {
                    $email = get_bloginfo( 'admin_email' );
                    $message .= ' User wishes to remain anonymous.';
                }

                // Headers
                $headers[] = 'From: '.$name.' <'.$email.'>';
                $headers[] = 'Content-Type: text/html; charset=UTF-8';

                // Subject
                if ( !$anonymous ) {
                    $subject = DDTT_NAME.' Plugin Deactivated Feedback | '.$domain;
                }

                // Mail it
                $mail = wp_mail( $to, $subject, $message, $headers );
            
                // If mail was sent, return success
                if ( $mail ) {
                     $result[ 'type' ] = 'success';

                // Otherwise return error
                } else {
                    $result[ 'type' ] = 'error';
                }

                // Method of sending
                $result[ 'method' ] = 'email';
            }
                        
        // Otherwise return error
        } else {
            $result[ 'type' ] = 'error';
        }

        // Pass to ajax
        if( !empty( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) && strtolower( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) == 'xmlhttprequest' ) {
            echo json_encode( $result );
        } else {
            $referer = filter_input( INPUT_SERVER, 'HTTP_REFERER', FILTER_SANITIZE_URL );
            header( 'Location: '.$referer );
        }

        // Stop
        die();
    } // End send()


    /**
     * Enqueue scripts
     * Reminder to bump version number during testing to avoid caching
     *
     * @param string $screen
     * @return void
     */
    public function enqueue_scripts( $screen ) {
        // Handle
        $handle = DDTT_GO_PF.'deactivate_script';

        // Nonce
        $nonce = wp_create_nonce( DDTT_GO_PF.'deactivate' );

        // Register
        wp_register_script( $handle, DDTT_PLUGIN_JS_PATH.'deactivate.js', [ 'jquery' ] );
        wp_localize_script( $handle, DDTT_GO_PF.'deactivate', [ 
            'plugin_slug' => DDTT_TEXTDOMAIN,
            'support_url' => DDTT_DISCORD_SUPPORT_URL,
            'nonce'       => $nonce,
            'ajaxurl'     => admin_url( 'admin-ajax.php' ),
        ] );
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( $handle );
    } // End enqueue_scripts()
}