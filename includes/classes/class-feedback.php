<?php
/**
 * Feedback class
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Initiate the class
 */
new DDTT_FEEDBACK;


/**
 * Main plugin class.
 */
class DDTT_FEEDBACK {

    /**
	 * Constructor
	 */
	public function __construct() {

        // Ajax
        add_action( 'wp_ajax_'.DDTT_GO_PF.'send_feedback', [ $this, 'send' ] );
        add_action( 'wp_ajax_nopriv_'.DDTT_GO_PF.'send_feedback', [ $this, 'send' ] );

        // Enqueue scripts
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

        // On failure
        add_action( 'wp_mail_failed', [ $this, 'mail_error' ], 10, 1 );

	} // End __construct()


    /**
     * Send the message
     *
     * @return void
     */
    public static function send() {
        // First verify the nonce
        if ( !wp_verify_nonce( $_REQUEST[ 'nonce' ], DDTT_GO_PF.'feedback' ) ) {
            exit( 'No naughty business please' );
        }

        // Get the stuff
        $name = isset( $_REQUEST[ 'name' ] ) ? sanitize_text_field( $_REQUEST[ 'name' ] ) : false;
        $email = isset( $_REQUEST[ 'email' ] ) ? sanitize_email( $_REQUEST[ 'email' ] ) : false;
        $msg = isset( $_REQUEST[ 'msg' ] ) ? sanitize_text_field( $_REQUEST[ 'msg' ] ) : false;
       
        // Check for a message
        if ( $name && $email && $msg ) {
           
            // To email
            $to = DDTT_AUTHOR_EMAIL;

            // Subject
            $subject = DDTT_NAME.' Feedback | '.ddtt_get_domain();
                    
            // The message
            $message = $msg;

            // Headers
            $headers[] = 'From: '.$name.' <'.$email.'>';
            $headers[] = 'Content-Type: text/html; charset=UTF-8';

            // Mail it
            $mail = wp_mail( $to, $subject, $message, $headers );
           
            // If mail was sent, return success
            if ( $mail ) {
                 $result[ 'type' ] = 'success';

            // Otherwise return error
            } else {
                $result[ 'type' ] = 'error';
                $error = get_option( DDTT_GO_PF.'feedback_error' );
                $result[ 'err' ] = $error->errors;
                $result[ 'what' ] = 'Error: Mail Failure ['.base64_encode( $error->errors[ 'wp_mail_failed' ][0] ).']. ';
            }
                        
        // Otherwise return error
        } else {
            $result[ 'type' ] = 'error';

            // Catch the errors
            $errors = [];
            if ( !$name ) {
                $errors[] = base64_encode( 'name' );
            } elseif ( !$email ) {
                $errors[] = base64_encode( 'email' );
            } elseif ( !$msg ) {
                $errors[] = base64_encode( 'message' );
            }
            $result[ 'what' ] = 'Errors: '.implode( ', ', $errors );
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
        // Get the options page slug
        $options_page = 'toplevel_page_'.DDTT_TEXTDOMAIN;

        // Allow for multisite
        if ( is_network_admin() ) {
            $options_page .= '-network';
        }

        // Are we on the options page?
        if ( $screen != $options_page ) {
            return;
        }

        // Handle
        $handle = DDTT_GO_PF.'feedback_script';

        // Feedback form and error code checker
        if ( ddtt_get( 'tab', '==', 'about' ) ) {
            wp_register_script( $handle, DDTT_PLUGIN_JS_PATH.'feedback.js', [ 'jquery' ], time() );
            wp_localize_script( $handle, 'feedbackAjax', [ 'ajaxurl' => admin_url( 'admin-ajax.php' ) ] );
            wp_enqueue_script( $handle );
            wp_enqueue_script( 'jquery' );
        }
    } // End enqueue_scripts()


    /**
     * Do something if there is an error sending feedback
     *
     * @param [type] $wp_error
     * @return void
     */
    public function mail_error( $wp_error ) {
        update_option( DDTT_GO_PF.'feedback_error', $wp_error );
    } // End mail_error()
}