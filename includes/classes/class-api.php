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
     * Check a URL to see if it Exists
     *
     * @param string $url
     * @param integer|null $timeout
     * @return array
     */
    public function check_url_status_code( $url ) {
        // Add the home url
        if ( str_starts_with( $url, '/' ) ) {
            $link = home_url().$url;
        } else {
            $link = $url;
        }

        // The request args
        // See https://developer.wordpress.org/reference/classes/WP_Http/request/
        $http_request_args = [
            'method'      => 'GET',
            'timeout'     => 5,        // How long the connection should stay open in seconds. Default 5.
            'redirection' => 0,        // Number of allowed redirects. Not supported by all transports. Default 5.
            'httpversion' => '1.1',    // Version of the HTTP protocol to use. Accepts '1.0' and '1.1'. Default '1.0'.
            'sslverify'   => false
        ];

        // Store the message text
        $text = '';

        // Check the link
        $response = wp_safe_remote_get( $link, $http_request_args );
        if ( !is_wp_error( $response ) ) {
            $code = wp_remote_retrieve_response_code( $response );
            if ( $code !== 200 ) {
                $body = wp_remote_retrieve_body( $response );
                if ( !is_wp_error( $body ) ) {
                    $decoded = json_decode( $body, true );
                    if ( isset( $decoded[ 'data' ][ 'status' ] ) && $decoded[ 'message' ] ) {
                        $code = $decoded[ 'data' ][ 'status' ];
                        $text = '. '.$decoded[ 'message' ];
                    }
                }
                $error = $text;
            }
            $error = 'Unknown';
        } else {
            $code = 0;
            $error = $response->get_error_message();
        }

        // Possible Codes
        $codes = [
            0 => $error,
            100 => 'Continue',
            101 => 'Switching Protocols',
            102 => 'Processing', // WebDAV; RFC 2518
            103 => 'Early Hints', // RFC 8297
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information', // since HTTP/1.1
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content', // RFC 7233
            207 => 'Multi-Status', // WebDAV; RFC 4918
            208 => 'Already Reported', // WebDAV; RFC 5842
            226 => 'IM Used', // RFC 3229
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found', // Previously "Moved temporarily"
            303 => 'See Other', // since HTTP/1.1
            304 => 'Not Modified', // RFC 7232
            305 => 'Use Proxy', // since HTTP/1.1
            306 => 'Switch Proxy',
            307 => 'Temporary Redirect', // since HTTP/1.1
            308 => 'Permanent Redirect', // RFC 7538
            400 => 'Bad Request',
            401 => 'Unauthorized', // RFC 7235
            402 => 'Payment Required',
            403 => 'Forbidden or Unsecure',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required', // RFC 7235
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed', // RFC 7232
            413 => 'Payload Too Large', // RFC 7231
            414 => 'URI Too Long', // RFC 7231
            415 => 'Unsupported Media Type', // RFC 7231
            416 => 'Range Not Satisfiable', // RFC 7233
            417 => 'Expectation Failed',
            418 => 'I\'m a teapot', // RFC 2324, RFC 7168
            421 => 'Misdirected Request', // RFC 7540
            422 => 'Unprocessable Entity', // WebDAV; RFC 4918
            423 => 'Locked', // WebDAV; RFC 4918
            424 => 'Failed Dependency', // WebDAV; RFC 4918
            425 => 'Too Early', // RFC 8470
            426 => 'Upgrade Required',
            428 => 'Precondition Required', // RFC 6585
            429 => 'Too Many Requests', // RFC 6585
            431 => 'Request Header Fields Too Large', // RFC 6585
            451 => 'Unavailable For Legal Reasons', // RFC 7725
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            506 => 'Variant Also Negotiates', // RFC 2295
            507 => 'Insufficient Storage', // WebDAV; RFC 4918
            508 => 'Loop Detected', // WebDAV; RFC 5842
            510 => 'Not Extended', // RFC 2774
            511 => 'Network Authentication Required', // RFC 6585
            
            // Unofficial codes
            103 => 'Checkpoint',
            218 => 'This is fine', // Apache Web Server
            419 => 'Page Expired', // Laravel Framework
            420 => 'Method Failure', // Spring Framework
            420 => 'Enhance Your Calm', // Twitter
            430 => 'Request Header Fields Too Large', // Shopify
            450 => 'Blocked by Windows Parental Controls', // Microsoft
            498 => 'Invalid Token', // Esri
            499 => 'Token Required', // Esri
            509 => 'Bandwidth Limit Exceeded', // Apache Web Server/cPanel
            526 => 'Invalid SSL Certificate', // Cloudflare and Cloud Foundry's gorouter
            529 => 'Site is overloaded', // Qualys in the SSLLabs
            530 => 'Site is frozen', // Pantheon web platform
            598 => 'Network read timeout error', // Informal convention
            440 => 'Login Time-out', // IIS
            449 => 'Retry With', // IIS
            451 => 'Redirect', // IIS
            444 => 'No Response', // nginx
            494 => 'Request header too large', // nginx
            495 => 'SSL Certificate Error', // nginx
            496 => 'SSL Certificate Required', // nginx
            497 => 'HTTP Request Sent to HTTPS Port', // nginx
            499 => 'Client Closed Request', // nginx
            520 => 'Web Server Returned an Unknown Error', // Cloudflare
            521 => 'Web Server Is Down', // Cloudflare
            522 => 'Connection Timed Out', // Cloudflare
            523 => 'Origin Is Unreachable', // Cloudflare
            524 => 'A Timeout Occurred', // Cloudflare
            525 => 'SSL Handshake Failed', // Cloudflare
            526 => 'Invalid SSL Certificate', // Cloudflare
            527 => 'Railgun Error', // Cloudflare
            666 => $error, // Our own error converted from 0
            999 => 'Scanning Not Permitted' // Non-standard code
        ];

        // Filter status
        $status = [
            'code' => $code,
            'text' => isset( $codes[ $code ] ) ? $codes[ $code ].$text : $error.$text,
        ];

        // Return the array
        return $status;
    } // End check_url_status_code


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
            $status = $this->check_url_status_code( $url );
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