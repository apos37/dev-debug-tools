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
new DDTT_SETTINGS;


/**
 * Main plugin class.
 */
class DDTT_SETTINGS {

    /**
     * Nonce
     */
    private $nonce = DDTT_GO_PF.'settings';
    

    /**
	 * Constructor
	 */
	public function __construct() {

        // Ajax
        add_action( 'wp_ajax_'.DDTT_GO_PF.'verify_logs', [ $this, 'verify_log_files' ] );
        add_action( 'wp_ajax_nopriv_'.DDTT_GO_PF.'verify_logs', [ $this, 'must_login' ] );

        // Enqueue scripts
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

	} // End __construct()


    /**
     * Verify log filea
     *
     * @return void
     */
    public function verify_log_files() {
        // First verify the nonce
        if ( !wp_verify_nonce( sanitize_text_field( wp_unslash ( $_REQUEST[ 'nonce' ] ) ), $this->nonce ) ) {
            exit( 'No naughty business please.' );
        }

        // Get the code
        $path = isset( $_REQUEST[ 'path' ] ) ? filter_var( $_REQUEST[ 'path' ], FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : false;
        if ( $path ) {

            $file = FALSE;
            if ( is_readable( ABSPATH.'/'.$path ) ) {
                $file = ABSPATH.''.$path;
            } elseif ( is_readable( dirname( ABSPATH ).'/'.$path ) ) {
                $file = dirname( ABSPATH ).'/'.$path;
            } elseif ( is_readable( $path ) ) {
                $file = $path;
            }
            if ( $file ) {
                $result[ 'type' ] = 'success';
            } else {
                $result[ 'type' ] = 'error';
            }
                        
        // Otherwise return error
        } else {
            $result[ 'type' ] = 'error';
        }

        // Pass to ajax
        if ( !empty( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) && strtolower( sanitize_key( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) ) == 'xmlhttprequest' ) {
            echo wp_json_encode( $result );
        } else {
            header( 'Location: '.filter_var( $_SERVER[ 'HTTP_REFERER' ], FILTER_SANITIZE_URL ) );
        }

        // Stop
        die();
    } // End verify_log_files()


    /**
     * What to do if they are not logged in
     *
     * @return void
     */
    public function must_login() {
        die();
    } // End must_login()


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
        $handle = DDTT_GO_PF.'settings_script';

        // Feedback form and error code checker
        if ( ddtt_get( 'tab', '==', 'settings' ) ) {
            wp_register_script( $handle, DDTT_PLUGIN_JS_PATH.'settings.js', [ 'jquery' ], time() );
            wp_localize_script( $handle, 'settingsAjax', [
                'nonce'     => wp_create_nonce( $this->nonce ),
                'log_files' => get_option( DDTT_GO_PF.'log_files' ),
                'ajaxurl'   => admin_url( 'admin-ajax.php' ) 
            ] );
            wp_enqueue_script( $handle );
            wp_enqueue_script( 'jquery' );
        }
    } // End enqueue_scripts()
}