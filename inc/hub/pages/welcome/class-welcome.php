<?php
/**
 * Welcome
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Welcome {
    
    /**
     * Get the options for tool.
     *
     * @return array
     */
    public static function settings() : array {
        $current_time = time();
        $time_format_choices = [
            'n/j/Y g:i a T' => wp_date( 'n/j/Y g:i a T', $current_time ) . ' (n/j/Y g:i a T)',
            'n/j/Y H:i T' => wp_date( 'n/j/Y H:i T', $current_time ) . ' (n/j/Y H:i T)',
            'F j, Y g:i a T'  => wp_date( 'F j, Y g:i a T', $current_time ) . ' (F j, Y g:i a T)',
            'F j, Y G:i T'    => wp_date( 'F j, Y G:i T', $current_time ) . ' (F j, Y G:i T)',
            'Y-m-d H:i:s'     => wp_date( 'Y-m-d H:i:s', $current_time ) . ' (Y-m-d H:i:s)',
            'm/d/Y g:i a'     => wp_date( 'm/d/Y g:i a', $current_time ) . ' (m/d/Y g:i a)',
            'm/d/Y H:i'       => wp_date( 'm/d/Y H:i', $current_time ) . ' (m/d/Y H:i)',
            'D, M j, Y g:i a' => wp_date( 'D, M j, Y g:i a', $current_time ) . ' (D, M j, Y g:i a)',
            'D, M j, Y H:i'   => wp_date( 'D, M j, Y H:i', $current_time ) . ' (D, M j, Y H:i)',
        ];

        return [
            'general' => [
                'label' => __( 'Set-Up', 'dev-debug-tools' ),
                'fields' => [
                    'developers' => [
                        'title'     => __( 'Developer Accounts', 'dev-debug-tools' ),
                        'desc'      => __( 'Add user accounts that should see errors, receive fatal error notifications, and have access to special features.', 'dev-debug-tools' ),
                        'type'      => 'devs',
                        'default'   => get_option( 'admin_email' ),
                    ],
                    'dev_timezone' => [
                        'title'     => __( 'Developer Timezone', 'dev-debug-tools' ),
                        'desc'      => __( 'Changes the timezone on Debug Log viewer and other areas in the plugin. Default is what the site uses.', 'dev-debug-tools' ),
                        'type'      => 'select',
                        'choices'   => timezone_identifiers_list(),
                        'default'   => get_option( 'timezone_string', 'UTC' ),
                    ],
                    'dev_timeformat' => [
                        'title'   => __( 'Developer Time Format', 'dev-debug-tools' ),
                        'desc'    => __( 'Changes the time format on Debug Log viewer and other areas in the plugin.', 'dev-debug-tools' ),
                        'type'    => 'select',
                        'choices' => $time_format_choices,
                        'default' => get_option( 'time_format', 'F j, Y g:i a T' ),
                    ],
                    'dev_access_only' => [
                        'title'     => __( 'Developer Access Only', 'dev-debug-tools' ),
                        'desc'      => __( 'Restrict access to the plugin for developers only. (RECOMMENDED)', 'dev-debug-tools' ),
                        'type'      => 'checkbox',
                        'default'   => true,
                    ],
                    'default_mode' => [
                        'title'     => __( 'Default Mode', 'dev-debug-tools' ),
                        'desc'      => __( 'Set the default mode for the plugin interface.', 'dev-debug-tools' ),
                        'type'      => 'select',
                        'choices'   => [
                            'dark'  => __( 'Dark Mode', 'dev-debug-tools' ),
                            'light' => __( 'Light Mode', 'dev-debug-tools' ),
                        ],
                        'default'   => 'dark',
                    ],
                ]
            ]
        ];
    } // End settings()


    /**
     * Nonce for AJAX requests
     *
     * @var string
     */
    private $welcome_nonce = 'ddtt_welcome_nonce';
    private $settings_nonce = 'ddtt_save_settings_nonce';


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?Welcome $instance = null;


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
        add_action( 'wp_ajax_ddtt_save_welcome_settings', [ $this, 'ajax_save_settings' ] );
        add_action( 'wp_ajax_nopriv_ddtt_save_welcome_settings', '__return_false' );
    } // End __construct()


    /**
     * Enqueue assets for the resources page
     *
     * @param string $hook The current admin page hook.
     * @return void
     */
    public function enqueue_assets( $hook ) : void {
        $first_name = get_user_meta( get_current_user_id(), 'first_name', true );
        wp_localize_script( 'ddtt-page-welcome', 'ddtt_welcome', [
            'welcome_nonce'  => wp_create_nonce( $this->welcome_nonce ),
            'settings_nonce' => wp_create_nonce( $this->settings_nonce ),
            'redirect_url'   => Bootstrap::page_url( 'settings&s=security' ),
            'bug_icon'       => Bootstrap::url( 'inc/hub/img/logo.png' ),
            'i18n'           => [
                'saving'         => __( 'Saving', 'dev-debug-tools' ),
                'loading'        => __( 'Loading', 'dev-debug-tools' ),
                'saveError'      => __( 'Error saving settings.', 'dev-debug-tools' ),
                'setupComplete'  => sprintf(
                    /* translators: %s is the user's first name */
                    __( 'Thank you, %s! Initial setup is complete.', 'dev-debug-tools' ),
                    '<span class="ddtt-first-name">' . ( $first_name ? esc_html( $first_name ) : __( 'Developer', 'dev-debug-tools' ) ) . '</span>'
                ),
                'setupComplete2' => __( 'I will take you to the security settings so you can lock down your important info.', 'dev-debug-tools' ),
            ]
        ] );
    } // End enqueue_assets()


    /**
     * AJAX handler to save settings.
     */
    public function ajax_save_settings() {
        check_ajax_referer( $this->welcome_nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'dev-debug-tools' ) ], 403 );
        }

        $options = $_POST[ 'options' ] ?? []; // phpcs:ignore

        if ( ! is_array( $options ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid data.', 'dev-debug-tools' ) ], 400 );
        }

        // Unslash each option value before processing
        foreach ( $options as $key => &$val ) {
            if ( is_string( $val ) ) {
                $val = wp_unslash( $val );
            } elseif ( is_array( $val ) ) {
                // Recursively unslash array values
                array_walk_recursive( $val, function( &$item ) {
                    if ( is_string( $item ) ) {
                        $item = wp_unslash( $item );
                    }
                } );
            }
        }
        unset( $val );

        $settings = $this->settings();
        $fields = [];
        foreach ( $settings as $section ) {
            if ( isset( $section[ 'fields' ] ) && is_array( $section[ 'fields' ] ) ) {
                foreach ( $section[ 'fields' ] as $key => $args ) {
                    $fields[ $key ] = $args[ 'type' ];
                }
            }
        }

        $updated = [];
        foreach ( $fields as $key => $type ) {
            $option_key = 'ddtt_' . $key; // Prefix the option key

            // Get the value from the POST data, default to null if it doesn't exist
            $value = $options[ $option_key ] ?? null;

            // The switch statement now handles all cases, including null (not set) values
            switch ( $type ) {
                case 'checkbox':
                    $value = Settings::sanitize_checkbox( $value );
                    break;

                case 'devs':
                    if ( is_string( $value ) ) {
                        $decoded = json_decode( $value, true );
                        $value   = is_array( $decoded ) ? array_map( 'intval', $decoded ) : [];
                    } elseif ( is_array( $value ) ) {
                        $value = array_map( 'intval', $value );
                    } else {
                        $value = [];
                    }
                    break;

                default:
                    $value = sanitize_text_field( $value );
                    break;
            }

            // Add or update the option
            if ( get_option( $option_key, '__notset' ) === '__notset' ) {
                add_option( $option_key, $value );
            } else {
                update_option( $option_key, $value );
            }
            $updated[] = $option_key;

            // Also update the mode option on the user
            if ( $key === 'default_mode' && in_array( $value, [ 'dark', 'light' ], true ) ) {
                update_user_meta( get_current_user_id(), 'ddtt_mode', $value );
            }
        }

        // Disable the what's new notice since we just set up
        update_option( 'ddtt_last_viewed_version', Bootstrap::version() );

        wp_send_json_success( [ 'updated' => $updated ] );
    } // End ajax_save_settings()


    /**
     * Prevent cloning and unserializing
     */
    private function __clone() {}
    private function __wakeup() {}

}


Welcome::instance();