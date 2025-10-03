<?php
/**
 * Transients
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Transients {


    /**
     * Available transient flavors
     *
     * @return array
     */
    public function transient_flavors() {
        return [
            'sparkle_transient'      => __( 'Sparkle Time', 'dev-debug-tools' ),
            'popcorn_transient'      => __( 'Popcorn Moment', 'dev-debug-tools' ),
            'whistle_transient'      => __( 'Whistle Break', 'dev-debug-tools' ),
            'confetti_transient'     => __( 'Confetti Burst', 'dev-debug-tools' ),
            'giggle_transient'       => __( 'Giggle Fit', 'dev-debug-tools' ),
            'rainbow_transient'      => __( 'Rainbow Glow', 'dev-debug-tools' ),
            'bounce_transient'       => __( 'Bounce Around', 'dev-debug-tools' ),
            'pingpong_transient'     => __( 'Ping Pong Time', 'dev-debug-tools' ),
            'bubble_transient'       => __( 'Bubble Pop', 'dev-debug-tools' ),
            'juggle_transient'       => __( 'Juggle Fun', 'dev-debug-tools' ),
            'twinkle_transient'      => __( 'Twinkle Moment', 'dev-debug-tools' ),
            'fizz_transient'         => __( 'Fizz Pop', 'dev-debug-tools' ),
            'zoom_transient'         => __( 'Zoom Zoom', 'dev-debug-tools' ),
            'spark_transient'        => __( 'Spark of Joy', 'dev-debug-tools' ),
            'flip_transient'         => __( 'Flip It', 'dev-debug-tools' ),
            'swoosh_transient'       => __( 'Swoosh Moment', 'dev-debug-tools' ),
            'gigabyte_transient'     => __( 'Gigabyte Surprise', 'dev-debug-tools' ),
            'tickle_transient'       => __( 'Tickle Time', 'dev-debug-tools' ),
            'pop_transient'          => __( 'Pop Moment', 'dev-debug-tools' ),
            'zing_transient'         => __( 'Zing!', 'dev-debug-tools' ),
            'whirl_transient'        => __( 'Whirl Around', 'dev-debug-tools' ),
            'sparkplug_transient'    => __( 'Spark Plug', 'dev-debug-tools' ),
            'boing_transient'        => __( 'Boing!', 'dev-debug-tools' ),
            'flicker_transient'      => __( 'Flicker Flash', 'dev-debug-tools' ),
            'bounceback_transient'   => __( 'Bounce Back', 'dev-debug-tools' ),
        ];
    } // End transient_flavors()


    /**
     * Get the options for tool.
     *
     * @return array
     */
    public static function settings() : array {
        return [
            'general' => [
                'label' => __( 'Actions', 'dev-debug-tools' ),
                'fields' => [
                    'clear_transients' => [
                        'title' => __( "Clear All Transients", 'dev-debug-tools' ),
                        'desc'  => __( "This action will clear all transients for the current site.", 'dev-debug-tools' ),
                        'type'  => 'button',
                        'label' => __( 'Clear Transients', 'dev-debug-tools' ),
                    ],
                    'purge_expired' => [
                        'title' => __( "Purge Expired Transients", 'dev-debug-tools' ),
                        'desc'  => __( "This action will purge all expired transients for the current site.", 'dev-debug-tools' ),
                        'type'  => 'button',
                        'label' => __( 'Purge Expired', 'dev-debug-tools' ),
                    ],
                    'test_transient' => [
                        'title' => __( "Set Test Transient", 'dev-debug-tools' ),
                        'desc'  => __( "This action will set a random test transient.", 'dev-debug-tools' ),
                        'type'  => 'button',
                        'label' => __( 'Add Transient', 'dev-debug-tools' ),
                    ],
                    'auto_check' => [
                        'title'   => __( "Automatically Check for New Transients", 'dev-debug-tools' ),
                        'desc'    => __( "Enable this option to have the transients table update automatically every few seconds without reloading the page. It will be disabled when you leave the page.", 'dev-debug-tools' ),
                        'type'    => 'checkbox',
                        'default' => false,
                    ],
                ]
            ]
        ];
    } // End settings()


    /**
     * Nonce
     *
     * @var string
     */
    private $nonce = 'ddtt_transients_nonce';


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?Transients $instance = null;


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
        add_action( 'wp_ajax_ddtt_clear_transient', [ $this, 'ajax_clear_transient' ] );
        add_action( 'wp_ajax_nopriv_ddtt_clear_transient', '__return_false' );
        add_action( 'wp_ajax_ddtt_clear_all_transients', [ $this, 'ajax_clear_all_transients' ] );
        add_action( 'wp_ajax_nopriv_ddtt_clear_all_transients', '__return_false' );
        add_action( 'wp_ajax_ddtt_purge_expired_transients', [ $this, 'ajax_purge_expired_transients' ] );
        add_action( 'wp_ajax_nopriv_ddtt_purge_expired_transients', '__return_false' );
        add_action( 'wp_ajax_ddtt_test_transient', [ $this, 'ajax_test_transient' ] );
        add_action( 'wp_ajax_nopriv_ddtt_test_transient', '__return_false' );
        add_action( 'wp_ajax_ddtt_get_transients', [ $this, 'ajax_get_transients' ] );
        add_action( 'wp_ajax_nopriv_ddtt_get_transients', '__return_false' );
    } // End __construct()


    /**
     * Get current transients (for use outside AJAX)
     *
     * @return array
     */
    public static function get_transients() {
        global $wpdb;

        $transients = [];

        try {
            // Match the same query used on the page
            // phpcs:ignore
            $results = $wpdb->get_results( "
                SELECT option_name, option_value 
                FROM {$wpdb->options} 
                WHERE option_name LIKE '_transient_%' 
                AND option_name NOT LIKE '_transient_timeout_%'
            ", ARRAY_A );

            if ( ! is_array( $results ) ) {
                throw new \Exception( 'Invalid results from transients query.' );
            }

            foreach ( $results as $row ) {
                $name  = preg_replace( '/^_transient_/', '', $row['option_name'] );
                $value = maybe_unserialize( $row['option_value'] );

                // Get timeout
                // phpcs:ignore
                $timeout_option = $wpdb->get_var( $wpdb->prepare(
                    "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
                    '_transient_timeout_' . $name
                ) );

                $is_expired = false;
                if ( $timeout_option && $timeout_option < time() ) {
                    $is_expired = true;
                }

                $formatted_value = Helpers::print_stored_value_to_table( $value );
                $display_value = Helpers::truncate_string( $formatted_value, true );
                $date = $timeout_option ? Helpers::convert_timezone( date_i18n( 'Y-m-d H:i:s', $timeout_option ) ) : __( 'None', 'dev-debug-tools' );

                $transients[$name] = [
                    'value'      => $display_value,
                    'timeout'    => $date,
                    'is_expired' => $is_expired,
                ];
            }

            uksort( $transients, function( $a, $b ) {
                return strcasecmp( $a, $b );
            } );

        } catch ( \Exception $e ) {
            Helpers::write_log( __( 'Error fetching transients: ', 'dev-debug-tools' ) . $e->getMessage() );
            $transients = [];
        }

        return $transients;
    } // End get_transients()


    /**
     * Enqueue assets
     *
     * @param string $hook The current admin page hook.
     * @return void
     */
    public function enqueue_assets( $hook ) : void {
        if ( ! AdminMenu::is_current_screen( $hook, 'tools', 'transients' ) ) {
            return;
        }
        
        wp_localize_script( 'ddtt-tool-transients', 'ddtt_transients', [
            'nonce' => wp_create_nonce( $this->nonce ),
            'i18n'  => [
                'error'              => __( 'Yuck! No thanks.', 'dev-debug-tools' ),
                'btn_text_clear_all' => __( 'Clearing all active transients...', 'dev-debug-tools' ),
                'btn_text_clear_all2'=> __( 'Hold tight, flushing transients!', 'dev-debug-tools' ),
                'btn_text_clear_all3'=> __( 'All transients cleared successfully!', 'dev-debug-tools' ),
                'btn_text_clear_one' => __( 'Clearing this transient...', 'dev-debug-tools' ),
                'btn_text_add_test'  => __( 'New test transient created!', 'dev-debug-tools' ),
                'no_timeout'         => __( 'None', 'dev-debug-tools' ),
            ],
        ] );
    } // End enqueue_assets()


    /**
     * AJAX: Clear a single transient
     *
     * @return void
     */
    public function ajax_clear_transient() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        if ( empty( $_POST['transient_name'] ) ) {
            wp_send_json_error( [ 'message' => __( 'No transient name provided.', 'dev-debug-tools' ) ] );
        }

        $transient_name = sanitize_text_field( wp_unslash( $_POST['transient_name'] ) );
        delete_transient( $transient_name );

        wp_send_json_success( [ 'transient' => $transient_name ] );
    } // End ajax_clear_transient()


    /**
     * AJAX: Clear all transients
     *
     * @return void
     */
    public function ajax_clear_all_transients() {
        check_ajax_referer( $this->nonce, 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        global $wpdb;

        try {
            $prefix = $wpdb->esc_like( '_transient_' );
            // phpcs:ignore
            $deleted = $wpdb->query(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE '{$prefix}%'" // phpcs:ignore
            );

            if ( false === $deleted ) {
                throw new \Exception( 'Failed to delete transients from database.' );
            }

            wp_send_json_success( [
                'message' => __( 'All transients cleared.', 'dev-debug-tools' )
            ] );

        } catch ( \Exception $e ) {
            Helpers::write_log( __( 'Error clearing all transients: ', 'dev-debug-tools' ) . $e->getMessage() );
            wp_send_json_error( [
                'message' => __( 'Could not clear transients. Check logs.', 'dev-debug-tools' )
            ] );
        }
    } // End ajax_clear_all_transients()


    /**
     * AJAX: Purge expired transients
     *
     * @return void
     */
    public function ajax_purge_expired_transients() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        global $wpdb;
        $expired_names = [];

        try {
            $current_time = time();
            $prefix = $wpdb->esc_like( '_transient_timeout_' ) . '%';

            // Prepare the SQL safely
            $sql = $wpdb->prepare(
                "
                SELECT option_name
                FROM {$wpdb->options}
                WHERE option_name LIKE %s
                AND option_value < %d
                ",
                $prefix,
                $current_time
            );

            $expired_rows = $wpdb->get_results( $sql ); // phpcs:ignore

            if ( ! is_array( $expired_rows ) ) {
                throw new \Exception( 'Failed to fetch expired transient rows.' );
            }

            foreach ( $expired_rows as $row ) {
                $transient_name = preg_replace( '/^_transient_timeout_/', '', $row->option_name );
                delete_transient( $transient_name );
                $expired_names[] = $transient_name;
            }

            wp_send_json_success( [
                'message' => __( 'Expired transients purged.', 'dev-debug-tools' ),
                'expired' => $expired_names
            ] );

        } catch ( \Exception $e ) {
            Helpers::write_log( __( 'Error purging expired transients: ', 'dev-debug-tools' ) . $e->getMessage() );
            wp_send_json_error( [
                'message' => __( 'Could not purge expired transients. Check logs.', 'dev-debug-tools' ),
                'expired' => $expired_names
            ] );
        }
    } // End ajax_purge_expired_transients()


    /**
     * AJAX: Set a random test transient
     *
     * @return void
     */
    public function ajax_test_transient() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        $transient_flavors = $this->transient_flavors();
        $flavor_keys       = array_keys( $transient_flavors );
        $random_key        = $flavor_keys[ array_rand( $flavor_keys ) ];

        $transient_name  = 'ddtt_' . $random_key;
        $transient_value = $transient_flavors[ $random_key ];

        set_transient( $transient_name, $transient_value, HOUR_IN_SECONDS );

        wp_send_json_success( [
            'transient' => $transient_name,
            'value'     => $transient_value,
            'label'     => $transient_value,
        ] );
    } // End ajax_test_transient()
    

    /**
     * AJAX: Get current transients
     *
     * @return void
     */
    public function ajax_get_transients() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        wp_send_json_success( [ 'transients' => self::get_transients() ] );
    } // End ajax_get_transients()


    /**
     * Prevent cloning and unserializing
     */
    private function __clone() {}
    private function __wakeup() {}
    
}


Transients::instance();