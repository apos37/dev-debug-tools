<?php
/**
 * Inactive Users
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class InactiveUsers {

    /**
     * Default users per page
     */
    const USERS_PER_PAGE_DEFAULT = 50;


    /**
     * Limit for chunking when syncing, marking or deleting users
     */
    private $chunking_limit = 500;


    /**
     * Get the options for tool.
     *
     * @return array
     */
    public static function settings() : array {
        $roles = get_editable_roles();
        $role_options = [];
        foreach ( $roles as $key => $role ) {
            if ( $key === 'administrator' ) {
                continue;
            }
            $role_options[ $key ] = $role[ 'name' ];
        }

        $last_lookup = get_option( 'ddtt_inactive_users_last_lookups', [] );

        $last_synced = get_option( 'ddtt_sync_users_last_online', 0 );

        return [
            'general' => [
                'label' => __( 'Options', 'dev-debug-tools' ),
                'fields' => [
                    'threshold' => [
                        'title'     => __( "Threshold", 'dev-debug-tools' ),
                        'desc'      => __( 'Set the threshold for inactive users. Users inactive for this period will be considered inactive.', 'dev-debug-tools' ),
                        'type'      => 'inactive_users_threshold',
                        'defaults'  => [
                            'val'  => absint( $last_lookup[ 'threshold_val' ] ?? 2 ),
                            'unit' => sanitize_text_field( $last_lookup[ 'threshold_unit' ] ?? 'years' ),
                        ],
                    ],
                    'deletion_status' => [
                        'title'   => __( 'Lifecycle Status', 'dev-debug-tools' ),
                        'desc'    => __( 'Filter users by their position in the deletion pipeline.', 'dev-debug-tools' ),
                        'type'    => 'select',
                        'choices' => [
                            'all'             => __( 'All Inactive Users', 'dev-debug-tools' ),
                            'unmarked_only'   => __( 'Unmarked Only (Not Pending Deletion)', 'dev-debug-tools' ),
                            'pending_only'    => __( 'Pending Delete Only', 'dev-debug-tools' ),
                            'ready_only'      => __( 'Ready for Delete Only (Grace Period Expired)', 'dev-debug-tools' ),
                            'pending_ready'   => __( 'Pending & Ready for Delete', 'dev-debug-tools' ),
                            'test_users_only' => __( 'Test Users Only (For Testing Purposes)', 'dev-debug-tools' ),
                        ],
                        'default' => sanitize_text_field( $last_lookup[ 'deletion_status' ] ?? 'all' ),
                    ],
                    'grace_period' => [
                        'title'   => __( 'Grace Period (Days)', 'dev-debug-tools' ),
                        'desc'    => __( 'Number of days a user remains "Pending" before becoming "Ready for Delete".', 'dev-debug-tools' ),
                        'type'    => 'number',
                        'default' => absint( $last_lookup[ 'grace_period' ] ?? 90 ),
                    ],
                    'exclude_roles' => [
                        'title'     => __( "Exclude Roles", 'dev-debug-tools' ),
                        'desc'      => __( "Select the user roles to exclude from the inactive users scan.", 'dev-debug-tools' ),
                        'type'      => 'checkboxes',
                        'choices'   => $role_options,
                        'default'   => array_map( 'sanitize_text_field', $last_lookup[ 'exclude_roles' ] ?? [] ),
                    ],
                    'keywords' => [
                        'title'     => __( 'Keywords Filter', 'dev-debug-tools' ),
                        'desc'      => __( 'Optionally enter keywords to filter users by, separated by commas. This will search the user\'s login, display name, and email for matches.', 'dev-debug-tools' ),
                        'type'      => 'text',
                        'default'   => sanitize_text_field( $last_lookup[ 'keywords' ] ?? '' ),
                    ],
                    'table_cols' => [
                        'title'     => __( 'Table Columns', 'dev-debug-tools' ),
                        'desc'      => __( 'Enter the user meta keys to display as columns in the inactive users table, separated by commas with the labels in parenthesis.', 'dev-debug-tools' ),
                        'type'      => 'text',
                        'default'   => sanitize_text_field( $last_lookup[ 'table_cols' ] ?? 'user_login (Username), display_name (Display Name), user_email (Email)' ),
                    ],
                    'fetch_users' => [
                        'title'     => __( 'Identify Inactive Accounts', 'dev-debug-tools' ),
                        'desc'      => __( 'Scan the database for users who haven\'t logged in or shown activity within your chosen timeframe.', 'dev-debug-tools' ),
                        'type'      => 'button',
                        'label'     => __( 'Find Inactive Users', 'dev-debug-tools' ),
                    ],
                    'sync_users_last_online' => [
                        'title'     => __( 'Legacy Data Migration', 'dev-debug-tools' ),
                        'desc'      => __( 'Search existing WordPress session tokens to "backfill" activity dates for users who haven\'t been tracked by this plugin yet. Recommended for first-time setup. Adds a "ddtt_last_online" meta key to users.', 'dev-debug-tools' ) . '<br><br><span class="ddtt-last-synced">' . sprintf( __( 'Last synced: %s', 'dev-debug-tools' ), $last_synced > 0 ? human_time_diff( $last_synced, time() ) . ' ago' : 'Never' ) . '</span>',
                        'type'      => 'button',
                        'label'     => __( 'Sync Activity from Sessions', 'dev-debug-tools' ),
                    ],
                    'mark_inactive_users' => [
                        'title'     => __( 'Bulk Protection Phase', 'dev-debug-tools' ),
                        'desc'      => __( 'Tag the currently filtered list of inactive users. This starts their "Grace Period" timer and prevents accidental immediate deletion.You must run the scan first for this to be available.', 'dev-debug-tools' ),
                        'type'      => 'button',
                        'label'     => __( 'Queue All for Deletion', 'dev-debug-tools' ),
                        'disabled'  => true
                    ],
                    'delete_eligible_users' => [
                        'title'     => __( 'Permanent Cleanup', 'dev-debug-tools' ),
                        'desc'      => __( 'Permanently remove users from the database who have completed their full grace period. <strong>This action is irreversible.</strong> You must run the scan first for this to be available.', 'dev-debug-tools' ),
                        'type'      => 'button',
                        'label'     => __( 'Purge Expired Users', 'dev-debug-tools' ),
                        'disabled'  => true
                    ],
                    'remove_pending_delete_keys' => [
                        'title'     => __( 'Pipeline Reset', 'dev-debug-tools' ),
                        'desc'      => __( 'Clear the "Pending Deletion" status from all accounts. This moves everyone back into the "Safe" zone and stops all countdowns.', 'dev-debug-tools' ),
                        'type'      => 'button',
                        'label'     => __( 'Cancel All Pending Deletions', 'dev-debug-tools' ),
                    ],
                    'remove_tracking_keys' => [
                        'title'     => __( 'Database Scrub', 'dev-debug-tools' ),
                        'desc'      => __( 'Wipe all "Last Online" timestamps recorded by this plugin. Use this if you wish to reset tracking history or are preparing to uninstall.', 'dev-debug-tools' ),
                        'type'      => 'button',
                        'label'     => __( 'Wipe All Tracking History', 'dev-debug-tools' ),
                    ],
                    'add_fake_accounts' => [
                        'title'     => __( 'Developer Sandbox', 'dev-debug-tools' ),
                        'desc'      => __( 'Quickly populate your site with 20 dummy subscribers featuring "aged" registration dates and sessions to verify your filters and grace period logic.', 'dev-debug-tools' ),
                        'type'      => 'button',
                        'label'     => __( 'Populate Test Accounts', 'dev-debug-tools' ),
                    ]
                ]
            ]
        ];
    } // End settings()


    /**
     * Nonces
     *
     * @var string
     */
    private $nonce = 'ddtt_inactive_users_nonce';
    private $sync_users_nonce = 'ddtt_sync_users_last_online_nonce';
    private $mark_inactive_nonce = 'ddtt_mark_inactive_users_nonce';
    private $reset_tracking_nonce = 'ddtt_reset_tracking_nonce';
    private $delete_users_nonce = 'ddtt_delete_inactive_users_nonce';
    private $add_fake_users_nonce = 'ddtt_add_fake_users_nonce';


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?InactiveUsers $instance = null;


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
        $this->chunking_limit = apply_filters( 'ddtt_inactive_users_chunking_limit', $this->chunking_limit );

        add_action( 'ddtt_header_notices', [ $this, 'render_header_notices' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_ddtt_get_inactive_users', [ $this, 'ajax_get_inactive_users' ] );
        add_action( 'wp_ajax_ddtt_sync_users_last_online', [ $this, 'ajax_sync_users_last_online' ] );
        add_action( 'wp_ajax_ddtt_mark_all_inactive_users', [ $this, 'ajax_mark_all_inactive' ] );
        add_action( 'wp_ajax_ddtt_mark_selected_inactive_users', [ $this, 'ajax_mark_selected_inactive_users' ] );
        add_action( 'wp_ajax_ddtt_remove_all_pending_keys', [ $this, 'ajax_remove_all_pending_keys' ] );
        add_action( 'wp_ajax_ddtt_remove_selected_pending_users', [ $this, 'ajax_remove_selected_pending_users' ] );
        add_action( 'wp_ajax_ddtt_remove_all_tracking_keys', [ $this, 'ajax_remove_all_tracking_keys' ] );
        add_action( 'wp_ajax_ddtt_delete_all_eligible_users', [ $this, 'ajax_delete_all_eligible_users' ] );
        add_action( 'wp_ajax_ddtt_delete_selected_users', [ $this, 'ajax_delete_selected_users' ] );
        add_action( 'wp_ajax_ddtt_generate_test_users', [ $this, 'ajax_generate_test_users' ] );
    } // End __construct()


    /**
     * Render header notices
     */
    public function render_header_notices() {
        if ( AdminMenu::get_current_page_slug() !== 'dev-debug-tools' || AdminMenu::current_tool_slug() !== 'inactive-users' ) {
            return;
        }

        $track_last_online = get_option( 'ddtt_track_last_online', false );
        $online_users_enabled = get_option( 'ddtt_online_users', true );
        $last_synced = get_option( 'ddtt_sync_users_last_online' );

        if ( ! $track_last_online && ! $online_users_enabled ) {
            Helpers::render_notice( sprintf( __( 'You must enable <a href="1%s">tracking of last online users</a> or <a href="2%s">online users feature</a>.', 'dev-debug-tools' ), esc_html( Bootstrap::page_url( 'settings&s=admin_areas' ) ), esc_html( Bootstrap::page_url( 'settings&s=online_users' ) ) ), 'error' );
        } elseif ( ! $last_synced ) {
            Helpers::render_notice( __( 'You have not optimized user tracking yet. Please click the <strong>"Sync Activity from Sessions"</strong> button below to backfill existing users so that they can be properly tracked and appear in the inactive users tool.', 'dev-debug-tools' ), 'warning' );
        }
    } // End render_header_notices()


    /**
     * Enqueue assets
     *
     * @param string $hook The current admin page hook.
     * @return void
     */
    public function enqueue_assets( $hook ) : void {
        if ( ! AdminMenu::is_current_screen( $hook, 'tools', 'inactive-users' ) ) {
            return;
        }

        wp_localize_script( 'ddtt-tool-inactive-users', 'ddtt_inactive_users', [
            'nonce'              => wp_create_nonce( $this->nonce ),
            'syncUsersNonce'     => wp_create_nonce( $this->sync_users_nonce ),
            'markInactiveNonce'  => wp_create_nonce( $this->mark_inactive_nonce ),
            'resetTrackingNonce' => wp_create_nonce( $this->reset_tracking_nonce ),
            'deleteUsersNonce'   => wp_create_nonce( $this->delete_users_nonce ),
            'addFakeUsersNonce'  => wp_create_nonce( $this->add_fake_users_nonce ),
            'i18n'               => [
                'scanning'             => __( 'Scanning users', 'dev-debug-tools' ),
                'scanLink'             => __( 'VIEW SCAN RESULTS', 'dev-debug-tools' ),
                'scanSuccess'          => __( 'Users fetched successfully!', 'dev-debug-tools' ),
                'scanError'            => __( 'Error fetching users. Refresh and try again.', 'dev-debug-tools' ),
                'syncConfirm'          => __( 'This will add the tracking key to all users that have not been tracked yet based on their last online session. Do you want to proceed?', 'dev-debug-tools' ),
                'syncingUsers'         => __( 'Syncing users', 'dev-debug-tools' ),
                'syncSuccess'          => __( 'Users synced successfully!', 'dev-debug-tools' ),
                'syncError'            => __( 'Error syncing users. Refresh and try again.', 'dev-debug-tools' ),
                'marking'              => __( 'Marking users as pending deletion', 'dev-debug-tools' ),
                'markingCond'          => __( 'To mark users as inactive, please set the Lifecycle Status to "All Inactive Users" or "Unmarked Only" and scan again.', 'dev-debug-tools' ),
                'markingConfirm'       => __( 'This will mark the users as pending deletion. They will not be deleted yet, but will be in the deletion pipeline. Do you want to proceed?', 'dev-debug-tools' ),
                'markedSuccess'        => __( 'Selected users marked as pending deletion successfully!', 'dev-debug-tools' ),
                'markedError'          => __( 'Error marking users as pending deletion.', 'dev-debug-tools' ),
                'removing'             => __( 'Removing users from pending deletion', 'dev-debug-tools' ),
                'removingCond'         => __( 'To remove users from pending status, the Lifecycle Status cannot be set to "Unmarked Only". Please adjust and scan again.', 'dev-debug-tools' ),
                'removeSuccess'        => __( 'Users removed from pending deletion successfully!', 'dev-debug-tools' ),
                'removeError'          => __( 'Error removing users from pending deletion.', 'dev-debug-tools' ),
                'removeConfirm'        => __( 'Are you sure you want to remove the users from the deletion pipeline?', 'dev-debug-tools' ),
                'resetTrackingConfirm' => __( 'This will remove the tracking keys from all users. This cannot be undone and will reset the tracking for all users. Do you want to proceed?', 'dev-debug-tools' ),
                'resetTracking'        => __( 'Removing tracking keys', 'dev-debug-tools' ),
                'resetTrackingSuccess' => __( 'Tracking keys removed successfully!', 'dev-debug-tools' ),
                'resetTrackingError'   => __( 'Error removing tracking keys.', 'dev-debug-tools' ),
                'deleteConfirm'        => __( 'Are you sure you want to permanently delete the inactive users?', 'dev-debug-tools' ),
                'deleting'             => __( 'Deleting users', 'dev-debug-tools' ),
                'deleteSuccess'        => __( 'users deleted successfully!', 'dev-debug-tools' ),
                'deleteError'          => __( 'Error deleting users.', 'dev-debug-tools' ),
                'generateConfirm'      => __( 'This will generate 20 fake users (subscriber role) with session tokens and backdated registration for testing purposes. Only use this on a development or staging site as it will create real user accounts. Do you want to proceed?', 'dev-debug-tools' ),
                'generating'           => __( 'Generating test users', 'dev-debug-tools' ),
                'generateSuccess'      => __( 'Test users generated successfully!', 'dev-debug-tools' ),
                'generateError'        => __( 'Error generating test users.', 'dev-debug-tools' ),
            ],
        ] );
    } // End enqueue_assets()


    /**
     * AJAX handler to get inactive users based on the defined threshold and excluded roles
     *
     * @return void
     */
    public function ajax_get_inactive_users() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) && ! Helpers::is_dev() ) {
            wp_send_json_error( 'unauthorized' );
        }

        // Get options
        $val = isset( $_POST[ 'threshold_val' ] ) ? intval( $_POST[ 'threshold_val' ] ) : 2;
        $unit = isset( $_POST[ 'threshold_unit' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'threshold_unit' ] ) ) : 'years';
        $deletion_status = isset( $_POST[ 'deletion_status' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'deletion_status' ] ) ) : 'all';
        $grace_period = isset( $_POST[ 'grace_period' ] ) ? absint( $_POST[ 'grace_period' ] ) : 90;
        $exclude = isset( $_POST[ 'exclude_roles' ] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST[ 'exclude_roles' ] ) ) : [];
        $keywords = isset( $_POST[ 'keywords' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'keywords' ] ) ) : '';
        $table_cols = isset( $_POST[ 'table_cols' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'table_cols' ] ) ) : '';
        $page = isset( $_POST[ 'page' ] ) ? absint( $_POST[ 'page' ] ) : 1;
        $per_page = isset( $_POST[ 'per_page' ] ) ? absint( $_POST[ 'per_page' ] ) : self::USERS_PER_PAGE_DEFAULT;

        // Save state
        $state = [
            'threshold_val'   => $val,
            'threshold_unit'  => $unit,
            'deletion_status' => $deletion_status,
            'grace_period'    => $grace_period,
            'exclude_roles'   => $exclude,
            'keywords'        => $keywords,
            'table_cols'      => $table_cols,
            'page'            => $page,
            'per_page'        => $per_page,
        ];
        update_option( 'ddtt_inactive_users_last_lookups', $state );

        $grace_period_seconds = $grace_period * DAY_IN_SECONDS;
        $ready_timestamp = time() - $grace_period_seconds;
        $threshold_timestamp = strtotime( "-{$val} {$unit}" );
        $threshold_date_string = gmdate( 'Y-m-d H:i:s', $threshold_timestamp );

        $meta_query = [ 'relation' => 'AND' ];

        if ( $deletion_status === 'test_users_only' ) {
            // If we only want test users, ignore inactivity thresholds and just look for the tag
            $meta_query[] = [
                'key'     => 'is_dummy_test_account',
                'value'   => '1',
                'compare' => '='
            ];
        } else {
            // Standard Inactivity Logic
            $meta_query[] = [
                'relation' => 'OR',
                [ 'key' => 'ddtt_last_online', 'value' => $threshold_timestamp, 'compare' => '<', 'type' => 'NUMERIC' ],
                [ 'key' => 'ddtt_last_online', 'compare' => 'NOT EXISTS' ]
            ];

            // Apply Lifecycle Filters
            if ( $deletion_status === 'unmarked_only' ) {
                $meta_query[] = [ 'key' => 'ddtt_pending_delete', 'compare' => 'NOT EXISTS' ];
            } elseif ( $deletion_status === 'pending_only' ) {
                $meta_query[] = [ 'key' => 'ddtt_pending_delete', 'value' => $ready_timestamp, 'compare' => '>', 'type' => 'NUMERIC' ];
            } elseif ( $deletion_status === 'ready_only' ) {
                $meta_query[] = [ 'key' => 'ddtt_pending_delete', 'value' => $ready_timestamp, 'compare' => '<=', 'type' => 'NUMERIC' ];
            } elseif ( $deletion_status === 'pending_ready' ) {
                $meta_query[] = [ 'key' => 'ddtt_pending_delete', 'compare' => 'EXISTS' ];
            }
        }

        $user_args = [
            'role__not_in' => array_unique( array_merge( [ 'administrator' ], $exclude ) ),
            'meta_query'   => $meta_query,
            'date_query'   => [
                [
                    'before'    => $threshold_date_string,
                    'inclusive' => true,
                ],
            ],
            'number'       => $per_page,
            'paged'        => $page,
            'count_total'  => true,
            'search'       => $keywords ? '*' . $keywords . '*' : '',
            'search_columns' => [ 'user_login', 'user_nicename', 'user_email', 'display_name' ],
        ];

        $query = new \WP_User_Query( $user_args );
        $users = $query->get_results();
        $total = $query->get_total();

        $table_cols = array_filter( array_map( 'trim', explode( ',', $table_cols ) ) );
        $extra_keys = [];

        foreach ( $table_cols as $col ) {
            $key   = $col;
            $label = $col;

            if ( preg_match( '/^(.+?)\s*\((.+)\)\s*$/', $col, $matches ) ) {
                $key   = trim( $matches[1] );
                $label = trim( $matches[2] );
            }

            if ( $key !== '' ) {
                $extra_keys[ $key ] = $label;
            }
        }

        // Build the Header/Footer Row variable
        ob_start();
        $render_header_row = function( $suffix ) use ( $extra_keys ) {
            ?>
            <tr>
                <th><input id="ddtt-select-all-<?php echo esc_attr( $suffix ); ?>" class="ddtt-select-all-toggle" type="checkbox"></th>
                <th><?php esc_html_e( 'ID', 'dev-debug-tools' ); ?></th>
                <?php foreach ( $extra_keys as $key => $label ) : ?>
                    <th><?php echo esc_html( $label ); ?></th>
                <?php endforeach; ?>
                <th><?php esc_html_e( 'Roles', 'dev-debug-tools' ); ?></th>
                <th><?php esc_html_e( 'Registered', 'dev-debug-tools' ); ?></th>
                <th><?php esc_html_e( 'Last Online', 'dev-debug-tools' ); ?></th>
                <th><?php esc_html_e( 'Deletion Status', 'dev-debug-tools' ); ?></th>
            </tr>
            <?php
        };

        // Define allowed HTML (added 'class' to input)
        $header_allowed_html = [
            'tr'    => [],
            'th'    => [ 'scope' => true ],
            'input' => [ 
                'type'  => true, 
                'id'    => true, 
                'class' => true 
            ],
        ];

        // Build THEAD
        ob_start();
        $render_header_row( 'top' );
        $thead_html = ob_get_clean();

        // Build TFOOT
        ob_start();
        $render_header_row( 'bottom' );
        $tfoot_html = ob_get_clean();
        ?>

        <thead>
            <?php echo wp_kses( $thead_html, $header_allowed_html ); ?>
        </thead>
        <tfoot>
            <?php echo wp_kses( $tfoot_html, $header_allowed_html ); ?>
        </tfoot>
        <tbody>
            <?php if ( $users ) : ?>
                <?php foreach ( $users as $user ) : 
                    $last_online = get_user_meta( $user->ID, 'ddtt_last_online', true );
                    $last_session = false;
                
                    $last_online_display = $last_online ? Helpers::convert_date_format( $last_online ) : '<em>' . esc_html__( 'Not Tracked', 'dev-debug-tools' ) . '</em>';

                    if ( ! $last_online ) {
                        $session_tokens = get_user_meta( $user->ID, 'session_tokens', true );
                        if ( is_array( $session_tokens ) && ! empty( $session_tokens ) ) {
                            $logins = wp_list_pluck( $session_tokens, 'login' );
                            $last_session = max( $logins );
                        }

                        $last_online_display .= '<br><small>— ' . ( $last_session ? __( 'Last Session: ', 'dev-debug-tools' ) . Helpers::convert_date_format( $last_session ) : '<em>' . esc_html__( 'No Sessions', 'dev-debug-tools' ) . '</em>' ) . '</small>';
                    }

                    $pending_delete = get_user_meta( $user->ID, 'ddtt_pending_delete', true );
                    $is_pending = $pending_delete && ( time() - $pending_delete < ( $grace_period_seconds ) );
                    $is_ready = $pending_delete && ( time() - $pending_delete >= ( $grace_period_seconds ) );

                    // Build the dynamic row classes
                    $row_classes = [];
                    if ( $is_ready ) {
                        $row_classes[] = 'ddtt-ready-for-deletion';
                    } elseif ( $is_pending ) {
                        $row_classes[] = 'ddtt-pending-deletion';
                    }
                ?>
                    <tr id="ddtt-user-row-<?php echo esc_attr( $user->ID ); ?>" class="<?php echo esc_attr( implode( ' ', $row_classes ) ); ?>">
                        <td><input class="ddtt-user-checkbox" type="checkbox" value="<?php echo esc_attr( $user->ID ); ?>"></td>
                        <td><span class="ddtt-highlight-variable"><a href="<?php echo MetaData::user_lookup_url( $user->ID ); ?>" target="_blank"><?php echo esc_attr( $user->ID ); ?></a></span></td>
                        <?php foreach ( $extra_keys as $key => $label ) : ?>
                            <td><?php echo esc_html( isset( $user->$key ) ? $user->$key : get_user_meta( $user->ID, $key, true ) ); ?></td>
                        <?php endforeach; ?>
                        <td><?php echo esc_html( implode( ', ', $user->roles ) ); ?></td>
                        <td><?php echo esc_html( Helpers::convert_date_format( $user->user_registered ) ); ?></td>
                        <td><?php echo wp_kses( $last_online_display, [ 'em' => [], 'br' => [], 'small' => [] ] ); ?></td>
                        <td class="ddtt-deletion-status"><?php echo $is_pending ? esc_html__( 'Pending since ', 'dev-debug-tools' ) . esc_html( Helpers::convert_date_format( $pending_delete ) ) : ( $is_ready ? esc_html__( 'Ready for Deletion', 'dev-debug-tools' ) : esc_html__( 'Active', 'dev-debug-tools' ) ); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="100%"><?php esc_html_e( 'No inactive users found.', 'dev-debug-tools' ); ?></td></tr>
            <?php endif; ?>
        </tbody>
        <?php
        $html = ob_get_clean();

        // Pagination Build
        $pagination_html = '';
        $total_pages = ceil( $total / $per_page );
        if ( $total_pages > 1 ) {
            $pagination_html .= '<div class="ddtt-pagination">';

            // First page
            if ( $page > 1 ) {
                $pagination_html .= '<span class="ddtt-pagination-first"><a href="#" data-page="1">' . esc_html__( '&laquo; First Page', 'dev-debug-tools' ) . '</a></span> ';
            } else {
                $pagination_html .= '<span class="ddtt-pagination-first ddtt-disabled">' . esc_html__( '&laquo; First Page', 'dev-debug-tools' ) . '</span> ';
            }

            // Previous page
            if ( $page > 1 ) {
                $pagination_html .= '<span class="ddtt-pagination-prev"><a href="#" data-page="' . ( $page - 1 ) . '">' . esc_html__( '&lsaquo; Previous', 'dev-debug-tools' ) . '</a></span> ';
            } else {
                $pagination_html .= '<span class="ddtt-pagination-prev ddtt-disabled">' . esc_html__( '&lsaquo; Previous', 'dev-debug-tools' ) . '</span> ';
            }

            // Page info
            /* Translators: %1$d is the current page, %2$d is the total number of pages. */
            $pagination_html .= '<span class="ddtt-pagination-info">' . sprintf( esc_html__( 'Page %1$d of %2$d', 'dev-debug-tools' ), $page, $total_pages ) . '</span> ';

            // Next page
            if ( $page < $total_pages ) {
                $pagination_html .= '<span class="ddtt-pagination-next"><a href="#" data-page="' . ( $page + 1 ) . '">' . esc_html__( 'Next &rsaquo;', 'dev-debug-tools' ) . '</a></span> ';
            } else {
                $pagination_html .= '<span class="ddtt-pagination-next ddtt-disabled">' . esc_html__( 'Next &rsaquo;', 'dev-debug-tools' ) . '</span> ';
            }

            // Last page
            if ( $page < $total_pages ) {
                $pagination_html .= '<span class="ddtt-pagination-last"><a href="#" data-page="' . $total_pages . '">' . esc_html__( 'Last Page &raquo;', 'dev-debug-tools' ) . '</a></span>';
            } else {
                $pagination_html .= '<span class="ddtt-pagination-last ddtt-disabled">' . esc_html__( 'Last Page &raquo;', 'dev-debug-tools' ) . '</span>';
            }

            $pagination_html .= '</div>';
        }

        wp_send_json_success( [
            'html'           => $html,
            'pagination'     => $pagination_html,
            'total_records'  => $total
        ] );
    } // End ajax_get_inactive_users()


    /**
     * AJAX handler for syncing users' last online timestamp.
     *
     * @return void
     */
    public function ajax_sync_users_last_online() {
        check_ajax_referer( $this->sync_users_nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) && ! Helpers::is_dev() ) {
            wp_send_json_error( 'unauthorized' );
        }

        $user_args = [
            'number'      => $this->chunking_limit,
            'fields'      => 'ID',
            'meta_query'  => [
                [
                    'key'     => 'ddtt_last_online',
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ];

        $query = new \WP_User_Query( $user_args );
        $user_ids = $query->get_results();
        $total_remaining = $query->get_total();

        if ( ! empty( $user_ids ) ) {
            foreach ( $user_ids as $user_id ) {
                $last_online = 0; // Default to 0 if no session found
                $sessions = get_user_meta( $user_id, 'session_tokens', true );
                
                if ( is_array( $sessions ) && ! empty( $sessions ) ) {
                    $logins = wp_list_pluck( $sessions, 'login' );
                    $last_online = max( $logins );
                }

                update_user_meta( $user_id, 'ddtt_last_online', $last_online );
            }
        }
        
        update_option( 'ddtt_sync_users_last_online', time() );

        wp_send_json_success( [
            'processed' => count( $user_ids ),
            'remaining' => max( 0, $total_remaining - count( $user_ids ) ),
            'done'      => ( empty( $user_ids ) || $total_remaining <= count( $user_ids ) )
        ] );
    } // End ajax_sync_users_last_online()


    /**
     * AJAX handler to mark all inactive users as pending deletion in chunks.
     */
    public function ajax_mark_all_inactive() {
        check_ajax_referer( $this->mark_inactive_nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) && ! Helpers::is_dev() ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized', 'dev-debug-tools' ) ] );
        }

        // Get live values from the request
        $val      = isset( $_POST[ 'threshold_val' ] ) ? absint( $_POST[ 'threshold_val' ] ) : 2;
        $unit     = isset( $_POST[ 'threshold_unit' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'threshold_unit' ] ) ) : 'years';
        $exclude  = isset( $_POST[ 'exclude_roles' ] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST[ 'exclude_roles' ] ) ) : [];
        $keywords = isset( $_POST[ 'keywords' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'keywords' ] ) ) : '';
        
        $threshold_timestamp = strtotime( "-{$val} {$unit}" );
        $threshold_date_string = gmdate( 'Y-m-d H:i:s', $threshold_timestamp );

        $user_args = [
            'role__not_in'   => array_unique( array_merge( [ 'administrator' ], $exclude ) ),
            'number'         => $this->chunking_limit,
            'fields'         => 'ID',
            'search'         => $keywords ? '*' . $keywords . '*' : '',
            'search_columns' => [ 'user_login', 'user_nicename', 'user_email', 'display_name' ],
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'relation' => 'OR',
                    [ 'key' => 'ddtt_last_online', 'value' => $threshold_timestamp, 'compare' => '<' ],
                    [ 'key' => 'ddtt_last_online', 'compare' => 'NOT EXISTS' ]
                ],
                [
                    'key'     => 'ddtt_pending_delete',
                    'compare' => 'NOT EXISTS'
                ]
            ],
            'date_query'   => [
                [
                    'before'    => $threshold_date_string,
                    'inclusive' => true,
                ],
            ],
        ];

        $query = new \WP_User_Query( $user_args );
        $user_ids = $query->get_results();
        $total_remaining = $query->get_total();

        if ( ! empty( $user_ids ) ) {
            foreach ( $user_ids as $user_id ) {
                update_user_meta( $user_id, 'ddtt_pending_delete', time() );
            }
        }

        wp_send_json_success( [
            'processed' => count( $user_ids ),
            'remaining' => max( 0, $total_remaining - count( $user_ids ) ),
            'done'      => ( empty( $user_ids ) || $total_remaining <= count( $user_ids ) )
        ] );
    } // End ajax_mark_all_inactive()


    /**
     * AJAX handler to mark users as pending deletion based on the defined threshold and excluded roles
     *
     * @return void
     */
    public function ajax_mark_selected_inactive_users() {
        check_ajax_referer( $this->mark_inactive_nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) && ! Helpers::is_dev() ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized', 'dev-debug-tools' ) ] );
        }

        if ( empty( $_POST[ 'user_ids' ] ) || ! is_array( $_POST[ 'user_ids' ] ) ) {
            wp_send_json_error( [ 'message' => __( 'No users selected.', 'dev-debug-tools' ) ] );
        }

        $user_ids = array_map( 'absint', $_POST[ 'user_ids' ] );
        
        foreach ( $user_ids as $user_id ) {
            // Safety: Check admins/self
            if ( get_current_user_id() === $user_id || user_can( $user_id, 'manage_options' ) ) {
                wp_send_json_error( [ 'message' => __( 'One or more selected users are administrators and cannot be marked.', 'dev-debug-tools' ) ] );
            }
        }

        $marked_users = [];
        foreach ( $user_ids as $user_id ) {
            if ( update_user_meta( $user_id, 'ddtt_pending_delete', time() ) ) {
                $marked_users[] = $user_id;
            }
        }
        
        wp_send_json_success( [
            'marked_users' => $marked_users,
            'total_marked' => count( $marked_users ),
        ] );
    } // End ajax_mark_selected_inactive_users()


    /**
     * AJAX handler to remove the pending deletion status from all users.
     */
    public function ajax_remove_all_pending_keys() {
        check_ajax_referer( $this->mark_inactive_nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) && ! Helpers::is_dev() ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized', 'dev-debug-tools' ) ] );
        }

        $user_args = [
            'number'     => $this->chunking_limit,
            'fields'     => 'ID',
            'meta_query' => [
                [
                    'key'     => 'ddtt_pending_delete',
                    'compare' => 'EXISTS',
                ],
            ],
        ];

        $query = new \WP_User_Query( $user_args );
        $user_ids = $query->get_results();
        $total_remaining = $query->get_total();

        if ( ! empty( $user_ids ) ) {
            foreach ( $user_ids as $user_id ) {
                delete_user_meta( $user_id, 'ddtt_pending_delete' );
            }
        }

        wp_send_json_success( [
            'processed' => count( $user_ids ),
            'remaining' => max( 0, $total_remaining - count( $user_ids ) ),
            'done'      => ( empty( $user_ids ) || $total_remaining <= count( $user_ids ) ),
        ] );
    } // End ajax_remove_all_pending_keys()


    /**
     * AJAX handler to remove the pending deletion status from selected users.
     */
    public function ajax_remove_selected_pending_users() {
        check_ajax_referer( $this->mark_inactive_nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) && ! Helpers::is_dev() ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized', 'dev-debug-tools' ) ] );
        }

        if ( empty( $_POST[ 'user_ids' ] ) || ! is_array( $_POST[ 'user_ids' ] ) ) {
            wp_send_json_error( [ 'message' => __( 'No users selected.', 'dev-debug-tools' ) ] );
        }

        $user_ids = array_map( 'absint', $_POST[ 'user_ids' ] );
        $removed_count = 0;

        foreach ( $user_ids as $user_id ) {
            // We use delete_user_meta to completely remove the flag
            if ( delete_user_meta( $user_id, 'ddtt_pending_delete' ) ) {
                $removed_count++;
            }
        }

        if ( $removed_count > 0 ) {
            wp_send_json_success( [
                'total_removed' => $removed_count,
                'message'       => sprintf( __( 'Successfully removed %d users from pending deletion.', 'dev-debug-tools' ), $removed_count )
            ] );
        } else {
            wp_send_json_error( [ 'message' => __( 'No changes were made. Selected users might not have been pending deletion.', 'dev-debug-tools' ) ] );
        }
    } // End ajax_remove_selected_pending_users()


    /**
     * AJAX handler to remove all last online tracking meta keys in chunks.
     */
    public function ajax_remove_all_tracking_keys() {
        check_ajax_referer( $this->reset_tracking_nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) && ! Helpers::is_dev() ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized', 'dev-debug-tools' ) ] );
        }

        $user_args = [
            'number'     => $this->chunking_limit,
            'fields'     => 'ID',
            'meta_query' => [
                [
                    'key'     => 'ddtt_last_online',
                    'compare' => 'EXISTS',
                ],
            ],
        ];

        $query = new \WP_User_Query( $user_args );
        $user_ids = (array) $query->get_results();
        $total_remaining = (int) $query->get_total();

        if ( ! empty( $user_ids ) ) {
            foreach ( $user_ids as $user_id ) {
                delete_user_meta( $user_id, 'ddtt_last_online' );
            }
        }

        // Also remove the last synced timestamp to allow for a fresh sync
        delete_option( 'ddtt_sync_users_last_online' );

        wp_send_json_success( [
            'processed' => count( $user_ids ),
            'remaining' => max( 0, $total_remaining - count( $user_ids ) ),
            'done'      => ( empty( $user_ids ) || $total_remaining <= count( $user_ids ) ),
        ] );
    } // End ajax_remove_all_tracking_keys()


    /**
     * AJAX handler to delete users who have passed the grace period.
     */
    public function ajax_delete_all_eligible_users() {
        check_ajax_referer( $this->delete_users_nonce, 'nonce' );

        if ( ! current_user_can( 'manage_options' ) && ! Helpers::is_dev() ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized', 'dev-debug-tools' ) ] );
        }

        $grace_period = isset( $_POST[ 'grace_period' ] ) ? absint( $_POST[ 'grace_period' ] ) : 90;
        $ready_timestamp = time() - ( $grace_period * DAY_IN_SECONDS );

        $user_args = [
            'number'     => $this->chunking_limit,
            'fields'     => 'ID',
            'meta_query' => [
                [
                    'key'     => 'ddtt_pending_delete',
                    'value'   => $ready_timestamp,
                    'compare' => '<=',
                    'type'    => 'NUMERIC'
                ],
            ],
        ];

        $query = new \WP_User_Query( $user_args );
        $user_ids = $query->get_results();
        $total_remaining = $query->get_total();

        $deleted_count = 0;

        if ( ! empty( $user_ids ) ) {
            require_once( ABSPATH . 'wp-admin/includes/user.php' );
            
            $current_admin = get_current_user_id();

            foreach ( $user_ids as $user_id ) {
                // Final Safety: Never delete self or administrators
                if ( $user_id === $current_admin || user_can( $user_id, 'manage_options' ) ) {
                    // Remove the key so they don't show up in the "Ready" list again
                    delete_user_meta( $user_id, 'ddtt_pending_delete' );
                    continue;
                }

                if ( wp_delete_user( $user_id ) ) {
                    $deleted_count++;
                }
            }
        }

        wp_send_json_success( [
            'total_deleted' => $deleted_count,
            'remaining'     => max( 0, $total_remaining - count( $user_ids ) ),
            'done'          => ( empty( $user_ids ) || $total_remaining <= count( $user_ids ) )
        ] );
    } // End ajax_delete_all_eligible_users()


    /**
     * AJAX handler to delete selected users
     *
     * @return void
     */
    public function ajax_delete_selected_users() {
        check_ajax_referer( $this->delete_users_nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) && ! Helpers::is_dev() ) {
            wp_send_json_error( __( 'Unauthorized', 'dev-debug-tools' ) );
        }

        if ( empty( $_POST[ 'user_ids' ] ) || ! is_array( $_POST[ 'user_ids' ] ) ) {
            wp_send_json_error( [ 'message' => __( 'No users selected.', 'dev-debug-tools' ) ] );
        }
        
        $user_ids = array_map( 'absint', $_POST[ 'user_ids' ] );
        
        foreach ( $user_ids as $user_id ) {
            // Safety: Check admins/self
            if ( get_current_user_id() === $user_id || user_can( $user_id, 'manage_options' ) ) {
                wp_send_json_error( [ 'message' => __( 'One or more selected users are administrators and cannot be marked.', 'dev-debug-tools' ) ] );
            }
        }

        // Ensure WordPress user deletion functions are available
        require_once( ABSPATH . 'wp-admin/includes/user.php' );

        // Delete the users and all their content
        $deleted_users = [];
        foreach ( $user_ids as $user_id ) {
            $deleted = wp_delete_user( $user_id );
            if ( $deleted ) {
                $deleted_users[] = $user_id;
            }
        }
        
        if ( ! empty( $deleted_users ) ) {
            wp_send_json_success( [ 
                'deleted_users' => $deleted_users,
                'total_deleted' => count( $deleted_users ),
            ] );
        } else {
            wp_send_json_error( [ 'message' => __( 'Failed to delete users.', 'dev-debug-tools' ) ] );
        }
    } // End ajax_delete_selected_users()


    /**
     * AJAX handler to generate 20 backdated test users with unique identifiers.
     */
    public function ajax_generate_test_users() {
        check_ajax_referer( $this->add_fake_users_nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) && ! Helpers::is_dev() ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized', 'dev-debug-tools' ) ] );
        }

        // Generate a short unique ID for this batch (e.g., 'a7b2')
        $batch_id = substr( md5( microtime() ), 0, 4 );

        $number_words = [
            1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
            6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
            11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
            16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty'
        ];

        $fake_time_string = date( 'Y-m-d H:i:s', strtotime( '-3 years' ) );
        $fake_timestamp   = strtotime( $fake_time_string );
        $count            = 0;

        global $wpdb;

        for ( $i = 1; $i <= 20; $i++ ) {
            // Append batch ID to ensure uniqueness: test01_a7b2
            $username = 'test' . sprintf( '%02d', $i ) . '_' . $batch_id;
            $email    = $username . '@example.com';

            $last_name = isset( $number_words[ $i ] ) ? $number_words[ $i ] : $i;
            $full_name = "Test {$last_name} ({$batch_id})";

            $user_id = wp_insert_user( [
                'user_login'   => $username,
                'user_email'   => $email,
                'user_pass'    => 'password123',
                'first_name'   => 'Test',
                'last_name'    => $last_name . ' ' . $batch_id,
                'display_name' => $full_name,
                'role'         => 'subscriber',
            ] );

            if ( ! is_wp_error( $user_id ) ) {

                // Backdate and Session logic remains the same...
                $wpdb->update( $wpdb->users, [ 'user_registered' => $fake_time_string ], [ 'ID' => $user_id ] );

                $verifier = wp_generate_password( 43, false );
                $hash     = wp_hash( $verifier );
                $sessions = [
                    $hash => [
                        'expiration' => $fake_timestamp + DAY_IN_SECONDS,
                        'login'      => $fake_timestamp,
                        'ua'         => 'Mozilla/5.0 (TestBot)',
                        'ip'         => '127.0.0.1',
                    ],
                ];
                update_user_meta( $user_id, 'session_tokens', $sessions );
                update_user_meta( $user_id, 'is_dummy_test_account', '1' );
                
                $count++;
            }
        }

        wp_send_json_success( [ 'message' => sprintf( __( 'Batch %s: %d test users generated.', 'dev-debug-tools' ), $batch_id, $count ) ] );
    } // End ajax_generate_test_users()


    /**
     * Prevent cloning and unserializing
     */
    public function __clone() {}
    public function __wakeup() {}
    
}


InactiveUsers::instance();