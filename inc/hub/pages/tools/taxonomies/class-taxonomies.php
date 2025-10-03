<?php
/**
 * Taxonomies
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Taxonomies {

    /**
     * Nonce
     *
     * @var string
     */
    private $nonce = 'ddtt_taxonomies_nonce';


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?Taxonomies $instance = null;


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
        add_action( 'wp_ajax_ddtt_get_taxonomy', [ $this, 'ajax_get_taxonomy' ] );
        add_action( 'wp_ajax_nopriv_ddtt_get_taxonomy', '__return_false' );
    } // End __construct()


    /**
     * Enqueue assets
     *
     * @param string $hook The current admin page hook.
     * @return void
     */
    public function enqueue_assets( $hook ) : void {
        if ( ! AdminMenu::is_current_screen( $hook, 'tools', 'taxonomies' ) ) {
            return;
        }

        $selected_taxonomy = get_option( 'ddtt_last_selected_taxonomy', '' );

        wp_localize_script( 'ddtt-tool-taxonomies', 'ddtt_taxonomies', [
            'nonce'      => wp_create_nonce( $this->nonce ),
            'last'       => $selected_taxonomy,
            'i18n'       => [
                'loading'      => __( 'Loading', 'dev-debug-tools' ),
                'showing'      => __( 'Showing value for', 'dev-debug-tools' ),
                'not_selected' => __( 'The selected taxonomy value will be displayed here.', 'dev-debug-tools' ),
                'taxonomy'     => __( 'Taxonomy', 'dev-debug-tools' ),
                'public'       => __( 'Public', 'dev-debug-tools' ),
                'settings'     => __( 'Settings', 'dev-debug-tools' ),
                'labels'       => __( 'Labels', 'dev-debug-tools' ),
                'post_types'   => __( 'Post Types', 'dev-debug-tools' ),
            ],
        ] );
    } // End enqueue_assets()


    /**
     * AJAX handler to get taxonomy details
     *
     * @return void
     */
    public function ajax_get_taxonomy() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        $taxonomy = isset( $_POST[ 'taxonomy' ] ) ? sanitize_key( $_POST[ 'taxonomy' ] ) : '';

        if ( empty( $taxonomy ) || ! taxonomy_exists( $taxonomy ) ) {
            wp_send_json_error( 'invalid_taxonomy' );
        }

        // Save last selected
        update_option( 'ddtt_last_selected_taxonomy', $taxonomy );

        $tax_object = get_taxonomy( $taxonomy );
        if ( ! $tax_object ) {
            wp_send_json_error( 'taxonomy_not_found' );
        }

        // Settings: all properties except 'labels' and 'object_type'
        $settings = (array) $tax_object;
        unset( $settings[ 'labels' ], $settings[ 'object_type' ] );
        ksort( $settings );

        // Format arrays/objects for readability
        foreach ( $settings as $key => $value ) {
            if ( is_array( $value ) || is_object( $value ) ) {
                $settings[ $key ] = esc_html( print_r( $value, true ) ); // phpcs:ignore
            }
        }

        // Labels
        $labels = isset( $tax_object->labels ) ? (array) $tax_object->labels : [];

        // Associated post types
        $post_types = [];
        if ( ! empty( $tax_object->object_type ) && is_array( $tax_object->object_type ) ) {
            foreach ( $tax_object->object_type as $pt_slug ) {
                $pt_obj = get_post_type_object( $pt_slug );
                $post_types[] = [
                    'slug'  => $pt_slug,
                    'label' => $pt_obj && isset( $pt_obj->labels->name ) ? $pt_obj->labels->name : $pt_slug,
                ];
            }
        }

        wp_send_json_success( [
            'settings'   => $settings,
            'labels'     => $labels,
            'post_types' => $post_types,
        ] );
    } // End ajax_get_taxonomy()


    /**
     * Prevent cloning and unserializing
     */
    private function __clone() {}
    private function __wakeup() {}
    
}


Taxonomies::instance();