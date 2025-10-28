<?php
/**
 * Online Users
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class OnlineUsers {

    /**
     * Nonce action name
     *
     * @var string
     */
    private $nonce = 'ddtt_online_users';


    /**
     * User meta key for last activity timestamp
     *
     * @var string
     */
    public $meta_key = 'ddtt_last_online';


    /**
     * Capability required to view online users in admin bar
     *
     * @var string
     */
    public $capability_to_view = 'administrator';


    /**
     * Constructor
     */
    public function __construct() {

        // Check if feature is enabled
        if ( ! get_option( 'ddtt_online_users', true ) ) {
            return;
        }

        // Track user activity
        add_action( 'init', [ $this, 'track_activity' ] );

        // Admin bar menu
        add_action( 'admin_bar_menu', [ $this, 'admin_bar' ], 9999999 );

        // User column
        add_filter( 'manage_users_columns', [ $this, 'user_column' ] );
        add_action( 'manage_users_custom_column', [ $this, 'user_column_content' ], 999, 3 );

        // Enqueue assets
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );

        // Ajax heartbeat handler
        add_action( 'wp_ajax_ddtt_online_users_heartbeat', [ $this, 'ajax_heartbeat' ] );
        add_action( 'wp_ajax_nopriv_ddtt_online_users_heartbeat', '__return_false' );

    } // End __construct()


    /**
     * Determine if current user should be tracked based on role settings
     *
     * @return bool
     */
    private function should_track_user() {
        if ( ! is_user_logged_in() ) {
            return false;
        }

        $registered_roles = array_keys( wp_roles()->roles );
        $roles = array_intersect(
            array_map( 'sanitize_key', (array) get_option( 'ddtt_online_users_roles', [ 'administrator' ] ) ),
            $registered_roles
        );

        if ( empty( $roles ) ) {
            return false;
        }

        $user = wp_get_current_user();
        if ( array_intersect( $user->roles, $roles ) ) {
            return true;
        }
        return false;
    } // End should_track_user()


    /**
     * Check if current user can view online users
     *
     * @return bool
     */
    private function can_view() {
        $cap = apply_filters( 'ddtt_online_users_capability', $this->capability_to_view );
        return current_user_can( $cap );
    } // End can_view()


    /**
     * Track current user's activity
     */
    public function track_activity() {
        if ( $this->should_track_user() ) {
            $user_id = get_current_user_id();
            update_user_meta( $user_id, $this->meta_key, time() );
            return true;
        }
        return false;
    } // End track_activity()


    /**
     * Get online users within timeframe (cached), filtered & sorted by role.
     *
     * @param int $minutes Active within last X minutes.
     * @return array Array of WP_User objects.
     */
    public function get_online_users( $minutes = 5 ) {
        $cache_key = 'online_users_' . $minutes;
        $users     = get_transient( $cache_key );

        $registered_roles = array_keys( wp_roles()->roles );
        $roles = array_intersect(
            array_map( 'sanitize_key', (array) get_option( 'ddtt_online_users_roles', [ 'administrator' ] ) ),
            $registered_roles
        );
        $priority_roles = array_intersect(
            array_map( 'sanitize_key', (array) get_option( 'ddtt_online_users_priority_roles', [ 'administrator' ] ) ),
            $registered_roles
        );

        if ( false === $users ) {
            $cutoff = time() - ( $minutes * 60 );

            $args = [
                'meta_key'     => $this->meta_key, // phpcs:ignore
                'meta_value'   => $cutoff, // phpcs:ignore
                'meta_compare' => '>=',
                'fields'       => 'all',
            ];

            $user_query = new \WP_User_Query( $args );
            $users = $user_query->get_results();

            if ( ! empty( $roles ) ) {
                $users = array_filter( $users, function( $user ) use ( $roles ) {
                    return array_intersect( $user->roles, $roles );
                } );
            }

            // 1. Priority roles first
            // 2. Then alphabetically by display_name
            usort( $users, function( $a, $b ) use ( $priority_roles ) {
                $a_priority = array_intersect( $a->roles, $priority_roles );
                $b_priority = array_intersect( $b->roles, $priority_roles );

                // If both have priority roles, or neither, sort alphabetically
                if ( empty( $a_priority ) && ! empty( $b_priority ) ) {
                    return 1; // b first
                } elseif ( ! empty( $a_priority ) && empty( $b_priority ) ) {
                    return -1; // a first
                } else {
                    return strcasecmp( $a->display_name, $b->display_name );
                }
            } );

            // Cache for 60 seconds
            set_transient( $cache_key, $users, MINUTE_IN_SECONDS );
        }

        return $users;
    } // End get_online_users()


    /**
     * Add admin bar node with online users
     *
     * @param \WP_Admin_Bar $wp_admin_bar
     */
    public function admin_bar( $wp_admin_bar ) { 
        if ( ! $this->can_view() ) {
            return;
        }

        $minutes       = absint( get_option( 'ddtt_online_users_last_seen', 5 ) );
        $online_users  = $this->get_online_users( $minutes );
        $count         = count( $online_users );
        $label         = _n( 'User', 'Users', $count, 'dev-debug-tools' );

        // Parent node
        $wp_admin_bar->add_node( [
            'id'    => 'ddtt-online-users',
            'title' => '<span class="ab-icon" style="height: 13px !important; width: 13px !important; padding: 0 !important;"></span><span class="ab-count">' . $count . '</span><span class="ab-label">' . $label . '</span>',
            'href'  => admin_url( 'users.php' ),
        ] );

        $link_template = sanitize_text_field( get_option( 'ddtt_online_users_link', '' ) );

        // Child nodes
        foreach ( $online_users as $user ) {
            $role_display = Helpers::get_highest_role( $user );
            $title        = $user->display_name . ' — ' . $role_display;

            // Add dev icon if applicable
            $class = 'ddtt-online-user';
            if ( Helpers::is_dev( $user->ID ) ) {
                $class .= ' dev';
                $title .= ' <img src="' . esc_attr( Helpers::icon( 14, 14 ) ) . '" alt="Dev" style="vertical-align:middle; margin-left:7px; margin-top:0px; height: 16px; width: 16px; display: inline-block;" />';
            }

            if ( empty( $link_template ) ) {
                $link = get_edit_profile_url( $user->ID );
            } else {
                $link = str_replace( '{user_id}', $user->ID, $link_template );
            }

            $wp_admin_bar->add_node( [
                'id'     => 'online-user-' . esc_attr( $user->ID ),
                'parent' => 'ddtt-online-users',
                'title'  => wp_kses_post( $title ),
                'href'   => esc_url( $link ),
                'meta'   => [
                    'class' => $class,
                ],
            ] );
        }

        // After looping through all online users
        $all_users = count_users();

        $wp_admin_bar->add_node( [
            'id'     => 'ddtt-online-users-total',
            'parent' => 'ddtt-online-users',
            'title'  => __( 'Total Registered Users:', 'dev-debug-tools' ) . ' ' . esc_attr( $all_users[ 'total_users' ] ),
            'meta'   => [
                'class' => 'ab-sub-secondary',
            ],
        ] );
    } // End admin_bar()


    /**
     * Add the user column
     *
     * @param array $columns
     * @return array
     */
    public function user_column( $columns ) {
        $columns[ 'ddtt_online_status' ] = __( 'Online Status', 'dev-debug-tools' );
        return $columns;
    } // End user_column()


    /**
     * Column content
     *
     * @param string $value
     * @param string $column_name
     * @param int $user_id
     * @return string
     */
    public function user_column_content( $value, $column_name, $user_id ) {
        if ( 'ddtt_online_status' !== $column_name ) {
            return $value;
        }

        $source = '';
        $last_activity = get_user_meta( $user_id, $this->meta_key, true );

        if ( ! empty( $last_activity ) ) {
            $source = 'ddtt';
        } else {
            $last_activity = Helpers::get_session_token_login( $user_id );
            if ( ! empty( $last_activity ) ) {
                $source = 'session';
            }
        }

        if ( empty( $last_activity ) ) {
            return '';
        }

        $minutes = absint( get_option( 'online_users_last_seen', 5 ) );
        $cutoff  = time() - ( $minutes * 60 );

        if ( $source === 'ddtt' && $last_activity >= $cutoff ) {
            return '<span style="color: green; font-weight: bold; font-style: italic;">' . __( 'Currently Online', 'dev-debug-tools' ) . '</span>';
        }

        $last_seen = Helpers::convert_timezone( $last_activity );

        if ( $source === 'ddtt' ) {
            // translators: %s is the formatted date/time of last online activity
            return sprintf( __( 'Last Online: %s', 'dev-debug-tools' ), $last_seen );
        } else { // session fallback
            // translators: %s is the formatted date/time of last session activity
            return sprintf( __( 'Last Session: %s', 'dev-debug-tools' ), $last_seen );
        }
    } // End user_column_content()


    /**
     * Enqueue the centering tool script on the front end if the user has it enabled
     */
    public function enqueue_assets() {
        $version = Bootstrap::script_version();
        $handle = 'ddtt-online-users';
        
        if ( $this->should_track_user() && get_option( 'ddtt_online_users_heartbeat', true ) ) {

            wp_enqueue_script(
                $handle,
                Bootstrap::url( 'inc/admin-area/online-users/scripts.js' ),
                [ 'jquery' ],
                $version,
                true
            );

            $minutes = absint( get_option( 'online_users_heartbeat_interval', 1 ) );
            $interval = $minutes * 60;

            wp_localize_script( $handle, 'ddtt_online_users', [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( $this->nonce ),
                'interval' => $interval,
                'i18n'     => [
                    'being_tracked' => __( 'You are being tracked for online status.', 'dev-debug-tools' ),
                ],
            ] );
        }

        if ( $this->can_view() ) {
            wp_enqueue_style(
                $handle,
                Bootstrap::url( 'inc/admin-area/online-users/styles.css' ),
                [],
                $version
            );
        }
    } // End enqueue_assets()


    /**
     * Handle AJAX heartbeat to update user activity
     */
    public function ajax_heartbeat() {
        check_ajax_referer( $this->nonce, 'nonce' );

        if ( ! get_option( 'ddtt_online_users_heartbeat', true ) ) {
            wp_send_json_error( [ 'message' => __( 'Heartbeat tracking has been disabled.', 'dev-debug-tools' ) ] );
        }
        
        $tracked = $this->track_activity();
        if ( ! $tracked ) {
            wp_send_json_error( [ 'message' => __( 'Failed to track activity.', 'dev-debug-tools' ) ] );
        }

        wp_send_json_success( [ 'message' => __( 'Heartbeat recorded.', 'dev-debug-tools' ) ] );
    } // End ajax_heartbeat()

}


new OnlineUsers();