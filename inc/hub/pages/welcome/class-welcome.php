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
                        'choices' => Helpers::get_time_format_choices(),
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
                'setupComplete2' => __( "I'll take you to the security settings so you can lock down your important info.", 'dev-debug-tools' ),
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
                        if ( is_array( $decoded ) ) {
                            // Extract the 'id' from each object and cast to int
                            $value = array_map( function( $item ) {
                                return isset( $item[ 'id' ] ) ? intval( $item[ 'id' ] ) : 0;
                            }, $decoded );
                        } else {
                            $value = [];
                        }
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

        // Attempt to remove the MU plugins if they exist
        Helpers::remove_mu_plugins();

        // Cleanup old options no longer needed
        $this->cleanup_options();

        // Disable the what's new notice since we just set up
        // update_option( 'ddtt_last_viewed_version', Bootstrap::version() );

        wp_send_json_success( [ 'updated' => $updated ] );
    } // End ajax_save_settings()


    /**
     * Cleanup old options no longer needed and reset ones that have changed.
     *
     * @return void
     */
    private function cleanup_options() : void {
        /**
         * Update options
         */

        // Activity option format changed from associative array to indexed array
        $old_options = get_option( 'ddtt_activity', [] );
        $new_options = [];
        if ( is_array( $old_options ) ) {
            foreach ( $old_options as $option_key => $option_value ) {
                if ( intval( $option_value ) === 1 ) {
                    $new_options[] = $option_key;
                }
            }
        }
        update_option( 'ddtt_activity', $new_options );

        $keys_to_change = [
            [ 'admin_bar_gf',        'admin_bar_gravity_form_finder' ],
            [ 'admin_bar_post_info', 'admin_bar_post_id' ],
            [ 'discord_webhook',     'discord_webhook_url' ],
            [ 'discord_webhook',     'online_users_discord_webhook' ],
        ];

        foreach ( $keys_to_change as $pair ) {
            $old_key = $pair[ 0 ];
            $new_key = $pair[ 1 ];

            $old_value = get_option( 'ddtt_' . $old_key, null );

            if ( $old_value !== null ) {
                update_option( 'ddtt_' . $new_key, $old_value );
            }
        }


        /**
         * Delete options
         */
        $old_options = [
            'admin_bar_gf',
            'admin_bar_my_account',
            'admin_bar_post_info',
            'admin_menu_links',
            'centering_tool_cols',
            'centering_tool_height',
            'centering_tool_width',
            'change_curl_timeout',
            'color_comments',
            'color_fx_vars',
            'color_syntax',
            'color_text_quotes',
            'dev_email',
            'disable_activity_counts',
            'discord_ingore_devs',
            'discord_login',
            'discord_page_loads',
            'discord_transient',
            'eol_htaccess',
            'eol_wpcnfg',
            'error_constants',
            'error_enable',
            'error_uninstall',
            'htaccess_last_updated',
            'htaccess_og_replaced_date',
            'log_user_url',
            'log_viewer',
            'max_log_size',
            'media_per_page',
            'menu_items',
            'menu_type',
            'online_users_seconds',
            'online_users_show_last',
            'php_eol',
            'plugin_activated',
            'plugin_activated_by',
            'plugin_installed',
            'post_meta_hide_pf',
            'ql_gravity_forms',
            'snippets',
            'stop_heartbeat',
            'suppress_errors_enable',
            'suppressed_errors',
            'test_number',
            'user_meta_hide_pf',
            'wpconfig_last_updated',
            'wpconfig_og_replaced_date',
        ];

        foreach ( $old_options as $option ) {
            delete_option( 'ddtt_' . $option );
        }
    } // End cleanup_old_options()


    /**
     * Prevent cloning and unserializing
     */
    public function __clone() {}
    public function __wakeup() {}

}


Welcome::instance();