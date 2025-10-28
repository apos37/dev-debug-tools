<?php
/**
 * Signups
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Signups {

    /**
     * Nonce
     *
     * @var string
     */
    private $nonce = 'ddtt_signups_nonce';


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?Signups $instance = null;


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
        add_action( 'wp_ajax_ddtt_clear_signup', [ $this, 'ajax_clear_signup' ] );
        add_action( 'wp_ajax_nopriv_ddtt_clear_signup', '__return_false' );
    } // End __construct()


    /**
     * Get signups
     *
     * @return array
     */
    public static function get_signups() : array {
        global $wpdb;

        $results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}signups", ARRAY_A );

        return $results ?: [];
    } // End get_signups()


    /**
     * Enqueue assets
     *
     * @param string $hook The current admin page hook.
     * @return void
     */
    public function enqueue_assets( $hook ) : void {
        if ( ! AdminMenu::is_current_screen( $hook, 'tools', 'signups' ) ) {
            return;
        }

        wp_localize_script( 'ddtt-tool-signups', 'ddtt_signups', [
            'nonce' => wp_create_nonce( $this->nonce ),
            'i18n'  => [
                'error'              => __( 'Oops! Something went wrong.', 'dev-debug-tools' ),
                'btn_text_clear_one' => __( 'Poof! Signup gone.', 'dev-debug-tools' ),
            ],
        ] );
    } // End enqueue_assets()


    /**
     * AJAX: Clear a single signup
     *
     * @return void
     */
    public function ajax_clear_signup() {
        check_ajax_referer( $this->nonce, 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized request.', 'dev-debug-tools' ) ] );
        }

        if ( empty( $_POST[ 'signup_id' ] ) ) {
            wp_send_json_error( [ 'message' => __( 'No signup ID provided.', 'dev-debug-tools' ) ] );
        }

        global $wpdb;

        $signup_id = absint( $_POST[ 'signup_id' ] );
        $table     = "{$wpdb->prefix}signups";

        // Verify that the signup exists before deletion
        $signup = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE signup_id = %d", $signup_id ), ARRAY_A );

        if ( ! $signup ) {
            wp_send_json_error( [ 'message' => __( 'Signup not found.', 'dev-debug-tools' ) ] );
        }

        // Perform deletion
        $deleted = $wpdb->delete( $table, [ 'signup_id' => $signup_id ], [ '%d' ] );

        if ( $deleted === false ) {
            wp_send_json_error( [ 'message' => __( 'Database error while deleting signup.', 'dev-debug-tools' ) ] );
        }

        wp_send_json_success( [
            'message' => __( 'Signup successfully deleted.', 'dev-debug-tools' ),
            'signup'  => $signup,
        ] );
    } // End ajax_clear_signup()


    /**
     * Prevent cloning and unserializing
     */
    public function __clone() {}
    public function __wakeup() {}
    
}


Signups::instance();