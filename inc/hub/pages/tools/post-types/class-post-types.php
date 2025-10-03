<?php
/**
 * Post Types
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class PostTypes {

    /**
     * Nonce
     *
     * @var string
     */
    private $nonce = 'ddtt_post_types_nonce';


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?PostTypes $instance = null;


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
        add_action( 'wp_ajax_ddtt_get_post_type', [ $this, 'ajax_get_post_type' ] );
        add_action( 'wp_ajax_nopriv_ddtt_get_post_type', '__return_false' );
    } // End __construct()


    /**
     * Enqueue assets
     *
     * @param string $hook The current admin page hook.
     * @return void
     */
    public function enqueue_assets( $hook ) : void {
        if ( ! AdminMenu::is_current_screen( $hook, 'tools', 'post-types' ) ) {
            return;
        }

        $selected_post_type = get_option( 'ddtt_last_selected_post_type', '' );

        wp_localize_script( 'ddtt-tool-post-types', 'ddtt_post_types', [
            'nonce'      => wp_create_nonce( $this->nonce ),
            'last'       => $selected_post_type,
            'i18n'       => [
                'loading'      => __( 'Loading', 'dev-debug-tools' ),
                'showing'      => __( 'Showing value for', 'dev-debug-tools' ),
                'not_selected' => __( 'The selected post type value will be displayed here.', 'dev-debug-tools' ),
                'post_type'    => __( 'Post Type', 'dev-debug-tools' ),
                'public'       => __( 'Public', 'dev-debug-tools' ),
                'settings'     => __( 'Settings', 'dev-debug-tools' ),
                'labels'       => __( 'Labels', 'dev-debug-tools' ),
                'taxonomies'   => __( 'Taxonomies', 'dev-debug-tools' ),
            ],
        ] );
    } // End enqueue_assets()


    /**
     * AJAX handler to get the post type data
     *
     * @return void
     */
    public function ajax_get_post_type() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        $post_type = isset( $_POST[ 'post_type' ] ) ? sanitize_key( $_POST[ 'post_type' ] ) : '';

        if ( empty( $post_type ) || ! post_type_exists( $post_type ) ) {
            wp_send_json_error( 'invalid_post_type' );
        }

        // Save last selected
        update_option( 'ddtt_last_selected_post_type', $post_type );

        $pt_object   = get_post_type_object( $post_type );
        global $_wp_post_type_features;

        // Add supports if available
        if ( isset( $_wp_post_type_features[ $pt_object->name ] ) ) {
            $supports = array_keys( $_wp_post_type_features[ $pt_object->name ] );
            $pt_object->supports = $supports;
        }

        $settings = (array) $pt_object;
        // Remove 'labels' and 'taxonomies' keys from settings
        unset( $settings[ 'labels' ], $settings[ 'taxonomies' ] );
        ksort( $settings );

            // Format arrays/objects for readability
            foreach ( $settings as $key => $value ) {
                if ( is_array( $value ) || is_object( $value ) ) {
                    $settings[ $key ] = esc_html( print_r( $value, true ) ); // phpcs:ignore
                }
            }

            $labels      = (array) $pt_object->labels;
            $taxonomies  = get_object_taxonomies( $post_type, 'objects' );

            $tax_data = [];
            foreach ( $taxonomies as $tax ) {
                $tax_data[] = [
                    'slug'  => $tax->name,
                    'label' => $tax->labels->name,
                ];
            }

            wp_send_json_success( [
                'settings'   => $settings,
                'labels'     => $labels,
                'taxonomies' => $tax_data,
            ] );
    }  // End ajax_get_post_type()


    /**
     * Prevent cloning and unserializing
     */
    private function __clone() {}
    private function __wakeup() {}
    
}


PostTypes::instance();