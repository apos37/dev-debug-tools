<?php
/**
 * Heartbeat
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Heartbeat {

    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?Heartbeat $instance = null;


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
        add_action( 'init', [ $this, 'maybe_disable_heartbeat' ], 1 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    } // End __construct()


    /**
     * Disable heartbeat according to settings
     *
     * @return void
     */
    public function maybe_disable_heartbeat() : void {

        // TODO: TEST THESE OPTIONS
        // TODO: ADD FLOATING COUNTER CSS TO THE STYLESHEET INSTEAD OF INLINE


        $disable_everywhere = get_option( 'ddtt_disable_everywhere' );
        $disable_admin      = get_option( 'ddtt_disable_admin' );
        $disable_frontend   = get_option( 'ddtt_disable_frontend' );

        if ( $disable_everywhere ) {
            wp_deregister_script( 'heartbeat' );
        } elseif ( is_admin() && $disable_admin ) {
            wp_deregister_script( 'heartbeat' );
        } elseif ( ! is_admin() && $disable_frontend ) {
            wp_deregister_script( 'heartbeat' );
        }
    } // End maybe_disable_heartbeat()


    /**
     * Enqueue the centering tool script on the front end if the user has it enabled
     */
    public function enqueue_assets() {
        $version = Bootstrap::script_version();
        $handle = 'ddtt-heartbeat-monitor';
        
        if ( get_option( 'ddtt_enable_heartbeat_monitor', false ) ) {

            wp_enqueue_script(
                $handle,
                Bootstrap::url( 'inc/site-wide/heartbeat/scripts.js' ),
                [ 'jquery' ],
                $version,
                true
            );

            wp_localize_script( $handle, 'ddtt_heartbeat_monitor', [
                'i18n'     => [
                    'heartbeat'       => __( 'HEARTBEAT', 'dev-debug-tools' ),
                    'loaded'          => __( 'Heartbeat monitoring script loaded.', 'dev-debug-tools' ),
                    'since_page_load' => __( 'seconds since page load', 'dev-debug-tools' ),
                    'since_last_beat' => __( 'seconds since last heartbeat', 'dev-debug-tools' ),
                ],
            ] );
        }

        wp_enqueue_style(
            $handle,
            Bootstrap::url( 'inc/site-wide/heartbeat/styles.css' ),
            [],
            $version
        );
    } // End enqueue_assets()


    /**
     * Prevent cloning and unserializing
     */
    private function __clone() {}
    private function __wakeup() {}
    
}


Heartbeat::instance();