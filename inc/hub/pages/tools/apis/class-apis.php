<?php
/**
 * APIs
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class APIs {


    /**
     * Nonce
     *
     * @var string
     */
    private $nonce = 'ddtt_apis_nonce';


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?APIs $instance = null;


    /**
     * Get the singleton instance
     *
     * @return self
     */
    public static function instance() : self {
        return self::$instance ??= new self();
    } // End instance()


    /**
     * Constructor
     */
    private function __construct() {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_ddtt_check_api', [ $this, 'ajax_check_api' ] );
        add_action( 'wp_ajax_nopriv_ddtt_check_api', '__return_false' );
    } // End __construct()


    /**
     * Enqueue assets
     *
     * @param string $hook The current admin page hook.
     * @return void
     */
    public function enqueue_assets( $hook ) : void {
        if ( ! AdminMenu::is_current_screen( $hook, 'tools', 'apis' ) ) {
            return;
        }

        wp_localize_script( 'ddtt-tool-apis', 'ddtt_apis', [
            'nonce' => wp_create_nonce( $this->nonce ),
            'i18n'  => [
                'error'    => __( 'Error :(', 'dev-debug-tools' ),
                'checking' => __( 'Checking', 'dev-debug-tools' )
            ],
        ] );
    } // End enqueue_assets()


    /**
     * AJAX: Check a given API route
     *
     * @return void
     */
    public function ajax_check_api() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        if ( ! isset( $_POST[ 'route' ] ) || empty( $_POST[ 'route' ] ) ) {
            wp_send_json_error( [ 'message' => __( 'No route provided.', 'dev-debug-tools' ) ] );
        }

        // Get the code
        $route = sanitize_text_field( wp_unslash( $_POST[ 'route' ] ) );
        if ( $route ) {

            // Get the endpoint url
            $url = rest_url( $route );

            // Check it
            $status = Helpers::check_url_status_code( $url );
            $type = $status[ 'code' ];
            $text = $status[ 'text' ];
                        
        // Otherwise return error
        } else {
            $type = 'ERROR';
            $text = 'No Route Found.';
        }

        wp_send_json_success( [ 'type' => $type, 'text' => $text ] );
    } // End ajax_check_api()


    /**
     * Prevent cloning and unserializing
     */
    public function __clone() {}
    public function __wakeup() {}
    
}


APIs::instance();