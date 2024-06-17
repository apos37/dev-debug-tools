<?php
/**
 * Validate php code class
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Initiate the class
 */
add_action( 'init', function() {
    (new DDTT_VALIDATE_PHP_CODE)->init();
} );



/**
 * Main plugin class.
 */
class DDTT_VALIDATE_PHP_CODE {

    /**
     * Nonce
     */
    private $nonce = DDTT_GO_PF.'validate_code';


    /**
     * Load on init, but not every time the class is called
     *
     * @return void
     */
    public function init() {
        
        // Ajax
        add_action( 'wp_ajax_'.DDTT_GO_PF.'validate_code', [ $this, 'validate_code' ] );
        add_action( 'wp_ajax_nopriv_'.DDTT_GO_PF.'validate_code', [ $this, 'must_login' ] );

    } // End init()


    /**
     * Check if the code is invalid
     *
     * @param string $code
     * @return string|false
     */
    public function is_invalid_php_code( $code ) {
        try {
            $code = "<?php\n".trim( $code );
            token_get_all( $code, TOKEN_PARSE );
          } catch ( Throwable $ex ) {
            $error = $ex->getMessage();
            $line = $ex->getLine() - 1;
            return "PARSE ERROR on line $line:\n\n$error";
          }
          return false;
    } // End is_invalid_php_code()


    /**
     * Verify log filea
     *
     * @return void
     */
    public function validate_code() {
        // First verify the nonce
        if ( !wp_verify_nonce( sanitize_text_field( wp_unslash ( $_REQUEST[ 'nonce' ] ) ), $this->nonce ) ) {
            exit( 'No naughty business please.' );
        }

        // Get the code
        $code = isset( $_REQUEST[ 'code' ] ) ? sanitize_textarea_field( $_REQUEST[ 'code' ] ) : false;
        if ( $code ) {
            $code = wp_unslash( $code );

            // Check if it's invalid
            if ( $invalid = $this->is_invalid_php_code( $code ) ) {
                $result = [
                    'type' => 'error',
                    'msg'  => $invalid,
                ];
            } else {
                $result = [
                    'type' => 'success',
                ];
            }
                        
        // Otherwise return error
        } else {
            $result = [
                'type' => 'error',
                'msg'  => 'No code found',
            ];
        }

        // Pass to ajax
        if ( !empty( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) && strtolower( sanitize_key( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) ) == 'xmlhttprequest' ) {
            echo wp_json_encode( $result );
        } else {
            header( 'Location: '.filter_var( $_SERVER[ 'HTTP_REFERER' ], FILTER_SANITIZE_URL ) );
        }

        // Stop
        die();
    } // End validate_code()


    /**
     * What to do if they are not logged in
     *
     * @return void
     */
    public function must_login() {
        die();
    } // End must_login()
}