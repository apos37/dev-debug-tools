<?php
/**
 * Resources
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Resources {
    
    /**
     * Get a list of useful resources.
     *
     * This method returns an array of resource links that can help users with WordPress development, debugging, and general web development.
     *
     * @return array
     */
    public static function get_links() : array {
        return ResourceLinks::defaults();
    } // End get_links()


    /**
     * Nonce for saving resources
     *
     * @var string
     */
    private $nonce = 'ddtt_save_resources';


    /**
     * Meta key for storing resources
     *
     * @var string
     */
    public static string $option_key = 'ddtt_resources';


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?Resources $instance = null;


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
        add_action( 'wp_ajax_ddtt_save_resources', [ $this, 'ajax_save_resources' ] );
        add_action( 'wp_ajax_ddtt_add_resource', [ $this, 'ajax_add_resource' ] );
        add_action( 'wp_ajax_ddtt_delete_resource', [ $this, 'ajax_delete_resource' ] );
    } // End __construct()


    /**
     * Enqueue assets for the resources page
     *
     * @param string $hook The current admin page hook.
     * @return void
     */
    public function enqueue_assets( $hook ) : void {
        if ( ! AdminMenu::is_current_screen( $hook, 'resources' ) ) {
            return;
        }

        wp_localize_script( 'ddtt-page-resources', 'ddtt_resources', [
            'nonce' => wp_create_nonce( $this->nonce ),
            'i18n' => [
                'removeResource'     => __( 'Remove Resource', 'dev-debug-tools' ),
                'titlePlaceholder'   => __( 'Title', 'dev-debug-tools' ),
                'linkPlaceholder'    => __( 'Link', 'dev-debug-tools' ),
                'descPlaceholder'    => __( 'Description', 'dev-debug-tools' ),
                'save'               => __( 'Add New Resource', 'dev-debug-tools' ),
                'cancel'             => __( 'Cancel', 'dev-debug-tools' ),
                'failedToSave'       => __( 'Failed to save resource. Please try again.', 'dev-debug-tools' ),
                'deleteConfirm'      => __( 'Are you sure you want to delete this resource?', 'dev-debug-tools' ),
                'alertTitleRequired' => __( 'Title is required.', 'dev-debug-tools' ),
                'alertLinkRequired'  => __( 'Link is required.', 'dev-debug-tools' ),
                'alertLinkInvalid'   => __( 'Please enter a valid URL.', 'dev-debug-tools' ),
                'alertDescRequired'  => __( 'Description is required.', 'dev-debug-tools' ),
                'reset_confirm'      => __( 'Are you sure you want to reset the resources? Any default resources that you have removed will be restored, and any custom resources that you have added will be lost.', 'dev-debug-tools' )
            ]
        ] );
    } // End enqueue_assets()


    /**
     * AJAX handler to save resources
     *
     * @return void
     */
    public function ajax_save_resources() {
        if ( ! current_user_can( 'manage_options' ) || ! check_ajax_referer( $this->nonce, '', false ) ) {
            wp_send_json_error();
        }

        $order = isset( $_POST[ 'resources' ] ) && is_array( $_POST[ 'resources' ] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST[ 'resources' ] ) ) : [];

        $saved = get_option( self::$option_key, [] );
        $custom = isset( $saved[ 'custom' ] ) ? $saved[ 'custom' ] : [];

        update_option( self::$option_key, [
            'order'  => $order,
            'custom' => $custom
        ] );

        wp_send_json_success();
    } // End ajax_save_resources()

    
    /**
     * AJAX handler to add a new resource
     *
     * @return void
     */
    public function ajax_add_resource() {
        check_ajax_referer( $this->nonce, '_ajax_nonce' );

        $title = isset( $_POST[ 'title' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'title' ] ) ) : '';
        $url   = isset( $_POST[ 'url' ] ) ? esc_url_raw( wp_unslash( $_POST[ 'url' ] ) ) : '';
        $desc  = isset( $_POST[ 'desc' ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ 'desc' ] ) ) : '';
        $key   = isset( $_POST[ 'key' ] ) ? sanitize_key( wp_unslash( $_POST[ 'key' ] ) ) : '';

        if ( ! $title || ! $url || ! $key ) {
            wp_send_json_error();
        }

        $option = get_option( self::$option_key, [ 'order' => [], 'custom' => [] ] );

        if ( in_array( $key, $option[ 'order' ], true ) ) {
            wp_send_json_error();
        }

        $option[ 'custom' ][ $key ] = [
            'title' => $title,
            'url' => $url,
            'desc' => $desc,
        ];
        $option[ 'order' ][] = $key;

        update_option( self::$option_key, $option );

        wp_send_json_success( [
            'title' => $title,
            'url' => $url,
            'desc' => $desc
        ] );
    } // End ajax_add_resource()


    /**
     * AJAX handler to delete a resource
     *
     * @return void
     */
    public function ajax_delete_resource() {
        if ( ! current_user_can( 'manage_options' ) || ! check_ajax_referer( $this->nonce, '', false ) ) {
            wp_send_json_error();
        }

        $key = sanitize_text_field( wp_unslash( $_POST[ 'key' ] ?? '' ) );
        if ( ! $key ) wp_send_json_error();

        $saved  = get_option( self::$option_key, [] );
        $order  = isset( $saved[ 'order' ] ) && is_array( $saved[ 'order' ] ) ? $saved[ 'order' ] : [];
        $custom = isset( $saved[ 'custom' ] ) && is_array( $saved[ 'custom' ] ) ? $saved[ 'custom' ] : [];

        if ( empty( $order ) ) {
            $defaults = array_keys( $this->get_links() );
            $custom_keys = array_keys( $custom );
            $order = array_merge( $defaults, $custom_keys );
            $order = array_unique( $order );
        }

        $order = array_values( array_diff( $order, [ $key ] ) );
        unset( $custom[ $key ] );

        update_option( self::$option_key, [
            'order'  => $order,
            'custom' => $custom
        ] );

        wp_send_json_success();
    } // End ajax_delete_resource()


    /**
     * Prevent cloning and unserializing
     */
    private function __clone() {}
    private function __wakeup() {}

}


Resources::instance();