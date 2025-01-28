<?php
/**
 * APIs class
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Initiate the class
 */
add_action( 'init', function() {
    (new DDTT_APIS)->init();
} );


/**
 * Main plugin class.
 */
class DDTT_APIS {

    /**
     * Name of nonce used for ajax call
     *
     * @var string
     */
    private $nonce = 'ddtt_check_api';


    /**
	 * Constructor
	 */
	public function init() {

        // Ajax
        add_action( 'wp_ajax_'.DDTT_GO_PF.'check_api', [ $this, 'check_api' ] );
        add_action( 'wp_ajax_nopriv_'.DDTT_GO_PF.'check_api', [ $this, 'must_login' ] );

        // Enqueue scripts
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

	} // End init()


    /**
     * Check the api availbility
     *
     * @return void
     */
    public function check_api() {
        // First verify the nonce
        if ( !wp_verify_nonce( sanitize_text_field( wp_unslash ( $_REQUEST[ 'nonce' ] ) ), DDTT_GO_PF.'check_api' ) ) {
            exit( 'No naughty business please.' );
        }

        // Get the code
        $route = sanitize_text_field( $_REQUEST[ 'route' ] );
        if ( $route ) {

            // Get the endpoint url
            $url = rest_url( $route );

            // Check it
            $status = ddtt_check_url_status_code( $url );
            $result[ 'type' ] = $status[ 'code' ];
            $result[ 'text' ] = $status[ 'text' ];
                        
        // Otherwise return error
        } else {
            $result[ 'type' ] = 'ERROR';
            $result[ 'text' ] = 'No Route Found.';
        }

        // Pass to ajax
        if ( !empty( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) && strtolower( sanitize_key( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) ) == 'xmlhttprequest' ) {
            echo wp_json_encode( $result );
        } else {
            header( 'Location: '.filter_var( $_SERVER[ 'HTTP_REFERER' ], FILTER_SANITIZE_URL ) );
        }

        // Stop
        die();
    } // End check_error_code()


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

        // Nonce
        $nonce = wp_create_nonce( $this->nonce );

        // Handle
        $handle = DDTT_GO_PF.'error_script';

        // Feedback form and error code checker
        if ( ddtt_get( 'tab', '==', 'api' ) ) {
            wp_register_script( $handle, DDTT_PLUGIN_JS_PATH.'apis.js', [ 'jquery' ], time() );
            wp_localize_script( $handle, 'apiAjax', [
                'nonce'   => $nonce,
                'ajaxurl' => admin_url( 'admin-ajax.php' ) 
            ] );
            wp_enqueue_script( $handle );
            wp_enqueue_script( 'jquery' );
        }
    } // End enqueue_scripts()
}