<?php
/**
 * Activity Log
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Activity_Log {

    /**
     * Get the activities to log
     *
     * @return array
     */
    public static function activities() : array {
        return [
            // User-related activities
            'users' => [
                'logging_in' => [
                    'settings' => __( 'Users Logging In', 'dev-debug-tools' ),
                    'action'   => __( 'User Logged In', 'dev-debug-tools' ),
                ],
                'updating_usermeta' => [
                    'settings' => __( 'Updating User Meta', 'dev-debug-tools' ),
                    'action'   => __( 'User Meta Updated', 'dev-debug-tools' ),
                ],
                'creating_account' => [
                    'settings' => __( 'Creating Accounts', 'dev-debug-tools' ),
                    'action'   => __( 'Account Created', 'dev-debug-tools' ),
                ],
                'deleting_account' => [
                    'settings' => __( 'Deleting Accounts', 'dev-debug-tools' ),
                    'action'   => __( 'Account Deleted', 'dev-debug-tools' ),
                ],
                'updating_roles' => [
                    'settings' => __( 'Updating User Roles', 'dev-debug-tools' ),
                    'action'   => __( 'User Roles Updated', 'dev-debug-tools' ),
                ],
            ],

            // Post-related activities
            'posts' => [
                'creating_post' => [
                    'settings' => __( 'Creating Posts & Pages', 'dev-debug-tools' ),
                    'action'   => __( 'Post Created', 'dev-debug-tools' ),
                ],
                'updating_post' => [
                    'settings' => __( 'Updating Posts & Pages', 'dev-debug-tools' ),
                    'action'   => __( 'Post Updated', 'dev-debug-tools' ),
                ],
                'deleting_post' => [
                    'settings' => __( 'Deleting Posts & Pages', 'dev-debug-tools' ),
                    'action'   => __( 'Post Deleted', 'dev-debug-tools' ),
                ],
                'status_post' => [
                    'settings' => __( 'Changing Post Statuses', 'dev-debug-tools' ),
                    'action'   => __( 'Post Status Changed', 'dev-debug-tools' ),
                ],
                'visiting_post' => [
                    'settings' => __( 'Visiting Posts & Pages', 'dev-debug-tools' ),
                    'action'   => __( 'Post/Page Visited', 'dev-debug-tools' ),
                ],
                'bots_crawling' => [
                    'settings' => __( 'Bots Crawling Posts & Pages', 'dev-debug-tools' ),
                    'action'   => __( 'Bot Crawled', 'dev-debug-tools' ),
                ],
            ],

            // Plugin-related activities
            'plugins' => [
                'activating_plugin' => [
                    'settings' => __( 'Activating Plugins', 'dev-debug-tools' ),
                    'action'   => __( 'Plugin Activated', 'dev-debug-tools' ),
                ],
                'updating_plugin' => [
                    'settings' => __( 'Updating Plugins', 'dev-debug-tools' ),
                    'action'   => __( 'Plugin Updated', 'dev-debug-tools' ),
                ],
                'deactivating_plugin' => [
                    'settings' => __( 'Deactivating Plugins', 'dev-debug-tools' ),
                    'action'   => __( 'Plugin Deactivated', 'dev-debug-tools' ),
                ],
                'deleting_plugin' => [
                    'settings' => __( 'Deleting Plugins', 'dev-debug-tools' ),
                    'action'   => __( 'Plugin Deleted', 'dev-debug-tools' ),
                ],
            ],

            // Theme-related activities
            'themes' => [
                'switching_theme' => [
                    'settings' => __( 'Switching Themes', 'dev-debug-tools' ),
                    'action'   => __( 'Theme Switched', 'dev-debug-tools' ),
                ],
                'updating_theme' => [
                    'settings' => __( 'Updating Themes', 'dev-debug-tools' ),
                    'action'   => __( 'Theme Updated', 'dev-debug-tools' ),
                ],
            ],

            // Settings-related activities
            'settings' => [
                'updating_settings' => [
                    'settings' => __( 'Updating Site Settings', 'dev-debug-tools' ),
                    'action'   => __( 'Site Settings Updated', 'dev-debug-tools' ),
                ],
            ],

            // Security-related activities
            'security' => [
                'failed_login_attempt' => [
                    'settings' => __( 'Failed Login Attempts', 'dev-debug-tools' ),
                    'action'   => __( 'Failed Login Attempted', 'dev-debug-tools' ),
                ],
                'resetting_password' => [
                    'settings' => __( 'Resetting Passwords', 'dev-debug-tools' ),
                    'action'   => __( 'Reset Password Requested', 'dev-debug-tools' ),
                ],
            ],
        ];
    } // End activities()


    /**
     * Set the highlight args to be used by activity log viewer
     *
     * @return array
     */
    public static function highlight_args() {
        // Set the args
        $args = apply_filters( 'ddtt_highlight_activity_log', [
            'users' => [
                'name'          => __( 'User-related', 'dev-debug-tools' ),
                'bg_color'      => '#FF6F61',
                'font_color'    => '#FFFFFF',
            ],
            'posts' => [
                'name'          => __( 'Post-related', 'dev-debug-tools' ),
                'bg_color'      => '#00A2E8',
                'font_color'    => '#000000',
            ],
            'plugins' => [
                'name'          => __( 'Plugin-related', 'dev-debug-tools' ),
                'bg_color'      => '#0073AA',
                'font_color'    => '#FFFFFF',
            ],
            'themes' => [
                'name'          => __( 'Theme-related', 'dev-debug-tools' ),
                'bg_color'      => '#006400',
                'font_color'    => '#FFFFFF',
            ],
            'settings' => [
                'name'          => __( 'Settings-related', 'dev-debug-tools' ),
                'bg_color'      => '#FFA500',
                'font_color'    => '#000000',
            ],
            'security' => [
                'name'          => __( 'Security-related', 'dev-debug-tools' ),
                'bg_color'      => '#DC143C',
                'font_color'    => '#FFFFFF',
            ],
        ] );        
    
        // Return them
        return $args;
    } // End highlight_args()


    /**
     * Log file path
     *
     * @var string
     */
    public $log_directory_path;
    public $log_filename = 'activity.log';
    public $log_file_path;


    /**
     * Filesystem instance
     *
     * @var \WP_Filesystem_Base|null
     */
    protected $filesystem;


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?Activity_Log $instance = null;


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
	public function __construct() {

        // Check if logging is enabled
        if ( ! Logs::is_logging_activity() ) {
            return;
        }

        // Paths
        $upload_dir = wp_upload_dir();
        $this->log_directory_path = trailingslashit( $upload_dir[ 'basedir' ] ) . Bootstrap::textdomain() . '/';
        $this->log_file_path = $this->log_directory_path . $this->log_filename;

        // Initialize the filesystem
        $this->init_filesystem();

        // If we haven't stored the plugin names yet, let's do so
        if ( !get_option( 'ddtt_plugins' ) ) {
            $this->update_installed_plugins_option();
        }

        // Logging in
        add_action( 'wp_login', [ $this, 'logging_in' ], 10, 2 );

        // Updating user meta
        add_filter( 'update_user_metadata', [ $this, 'updating_usermeta' ], 10, 4 );
        add_action( 'profile_update', [ $this, 'updating_userobject' ], 10, 3 );

        // Updating roles
        add_action( 'add_user_role', function ( $user_id, $role ) {
            $this->updating_roles( $user_id, $role, 'added' );
        }, 10, 2 );

        add_action( 'remove_user_role', function ( $user_id, $role ) {
            $this->updating_roles( $user_id, $role, 'removed' );
        }, 10, 2 );

        // Creating an account (does not include front-end registration, only admins creating accounts on back-end)
        add_action( 'user_register', [ $this, 'creating_account' ] );

        // Deleting an account
        add_action( 'delete_user', [ $this, 'deleting_account' ] );

        // Creating a post
        add_action( 'save_post', [ $this, 'creating_post' ], 10, 3 );

        // Updating a post
        add_filter( 'update_post_metadata', [ $this, 'updating_post' ], 10, 4 );
        add_action( 'added_post_meta', [ $this, 'adding_postmeta' ], 10, 4 );
        add_action( 'deleted_post_meta', [ $this, 'deleting_postmeta' ], 10, 4 );
        add_action( 'pre_post_update', [ $this, 'updating_postobject' ], 10, 2 );

        // Deleting a post
        add_action( 'before_delete_post', [ $this, 'deleting_post' ] );
        add_action( 'trashed_post', [ $this, 'trashing_post' ], 10, 1 );

        // Change post status
        add_action( 'transition_post_status', [ $this, 'status_post' ], 10, 3 );

        // Visiting posts and pages
        add_action( 'template_redirect', [ $this, 'visiting_post' ] );

        // Bot crawling
        add_action( 'template_redirect', [ $this, 'bots_crawling' ] );

        // Activating a plugin
        add_action( 'activated_plugin', [ $this, 'activating_plugin' ] );

        // Updating a plugin
        add_action( 'upgrader_process_complete', [ $this, 'updating_plugin' ], 10, 2 );
        
        // Deactivating a plugin
        add_action( 'deactivated_plugin', [ $this, 'deactivating_plugin' ] );

        // Deleting a plugin
        add_action( 'deleted_plugin', [ $this, 'deleting_plugin' ] );

        // Switching themes
        add_action( 'switch_theme', [ $this, 'switching_theme' ], 10, 3 );

        // Updating a theme
        add_action( 'upgrader_process_complete', [ $this, 'updating_theme' ], 10, 2 );

        // Updating settings
        add_action( 'update_option', [ $this, 'updating_settings' ], 10, 3 );

        // Failed login attempts
        add_action( 'wp_login_failed', [ $this, 'failed_login_attempt' ], 10, 2 );

        // Resetting a password
        add_action( 'retrieve_password', [ $this, 'resetting_password' ] );        

	} // End __construct()


    /**
     * Initialize the filesystem
     *
     * This method initializes the filesystem for reading and writing log files.
     * It is called in the constructor to ensure the filesystem is ready for use.
     */
    protected function init_filesystem() {
        global $wp_filesystem;

        if ( ! function_exists( 'request_filesystem_credentials' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        WP_Filesystem();

        if ( is_object( $wp_filesystem ) ) {
            $this->filesystem = $wp_filesystem;
        }
    } // End init_filesystem()


    /**
     * Remove the log file if it exists.
     *
     * @return bool True if the log file was successfully deleted, false if it doesn't exist or could not be deleted.
     */
    public function remove_log_file() {
        if ( ! $this->filesystem ) {
            echo '<p>' . esc_html__( 'Filesystem not initialized.', 'dev-debug-tools' ) . '</p>';
            return;
        }

        $log_directory_path = $this->log_directory_path;
        $log_file_path = $this->log_file_path;

        if ( $this->filesystem->exists( $log_file_path ) ) {
            if ( ! $this->filesystem->delete( $log_file_path ) ) {
                Helpers::write_log( __( 'Failed to delete log file during uninstall.', 'dev-debug-tools' ) );
                return false;
            }
        }
    
        // Remove the directory if it exists and is empty
        if ( $this->filesystem->exists( $log_directory_path ) ) {
            $files = $this->filesystem->dirlist( $log_directory_path );
            if ( empty( $files ) ) {
                if ( ! $this->filesystem->rmdir( $log_directory_path, false ) ) {
                    Helpers::write_log( __( 'Failed to remove log directory during uninstall.', 'dev-debug-tools' ) );
                    return false;
                }
            } else {
                Helpers::write_log( __( 'Log directory is not empty, could not delete at uninstall.', 'dev-debug-tools' ) );
            }
        }

        return true;
    } // End remove_log_file()


    /**
     * Write a message to the log file.
     *
     * @param string $message The message to log.
     * @return boolean
     */
    public function write_to_log( $message ) {
        $message = wp_kses_post( $message );
        $log_file_path = $this->log_file_path;
        $log_entry = sprintf( "[%s] %s\n", gmdate( 'd-M-Y H:i:s e' ), $message );

        if ( ! $this->filesystem ) {
            echo '<p>' . esc_html__( 'Filesystem not initialized.', 'dev-debug-tools' ) . '</p>';
            return;
        }

        // Create log directory if it doesn't exist
        $log_directory_path = $this->log_directory_path;
        if ( ! $this->filesystem->is_dir( $log_directory_path ) ) {
            if ( ! $this->filesystem->mkdir( $log_directory_path, FS_CHMOD_DIR ) ) {
                Helpers::write_log( __( 'Could not create log directory: ', 'dev-debug-tools' ) . $log_directory_path );
                return false;
            }
        }

        // Create log file if it doesn't exist
        if ( ! $this->filesystem->exists( $log_file_path ) ) {
            if ( ! $this->filesystem->put_contents( $log_file_path, '', FS_CHMOD_FILE ) ) {
                Helpers::write_log( __( 'Could not create log file: ', 'dev-debug-tools' ) . $log_file_path );
                return false;
            }
        }

        $existing_log = $this->filesystem->get_contents( $log_file_path );
        if ( $existing_log === false ) {
            Helpers::write_log( __( 'Could not find activity log to write to.', 'dev-debug-tools' ) );
            return false;
        }

        if ( ! $this->filesystem->put_contents( $log_file_path, $existing_log . $log_entry, FS_CHMOD_FILE ) ) {
            Helpers::write_log( __( 'Could not write to activity log.', 'dev-debug-tools' ) );
            return false;
        }
    
        return true;
    } // End write_to_log()


    /**
     * Get the action label
     *
     * @param string $action
     * @return string|false
     */
    public function get_action_label( $__FUNCTION__ ) {
        $action_label = false;
        foreach ( self::activities() as $activity ) {
            if ( isset( $activity[ $__FUNCTION__ ] ) ) {
                $action_label = $activity[ $__FUNCTION__ ][ 'action' ];
                break;
            }
        }
        return $action_label;
    } // End get_action_label()


    /**
     * Get the current user log message that will start at the beginning of most activity log messages
     *
     * @param string $action_label
     * @param WP_User|null $current_user
     * @return string
     */
    public function current_user_log_message( $action_label, $current_user = null, $append = '' ) {
        if ( $current_user == null ) {
            $current_user = wp_get_current_user();
        }
        if ( ! $current_user ) {
            return __( 'Current user not found.', 'dev-debug-tools' );
        }

        $append = $append ? ' ' . $append : '';

        return sprintf(
            '%s: %s (%s - ID: %d)%s',
            $action_label,
            $current_user->display_name,
            $current_user->user_email,
            $current_user->ID,
            $append
        );
    } // End current_user_log_message()


    /**
     * One way to handle all arrays
     *
     * @param string $meta_value
     * @return string
     */
    public function maybe_handle_array_value( $meta_value ) {
        if ( is_object( $meta_value ) ) {
            $meta_value = (array) $meta_value;
        }
        return is_array( $meta_value ) ? wp_unslash( wp_json_encode( $meta_value ) ) : $meta_value;
    } // End maybe_handle_array_value()
    

    /**
     * Handle user login.
     *
     * @param string $user_login Username of the user logging in.
     * @param WP_User $user WP_User object of the user logging in.
     * @return void
     */
    public function logging_in( $user_login, $user ) {
        if ( ! Logs::is_logging_activity( __FUNCTION__ ) ) {
            return;
        }
        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {
            if ( ! $this->write_to_log( $this->current_user_log_message( $action_label, $user ) ) ) {
                Helpers::write_log( __( 'Failed to write to activity log file during login.', 'dev-debug-tools' ) );
            }
        }
    } // End logging_in()


    /**
     * Handle user updating a profile
     *
     * @param null|bool $check
     * @param int $object_id
     * @param string $meta_key
     * @param mixed $meta_value
     * @return null|bool
     */
    public function updating_usermeta( $check, $object_id, $meta_key, $new_value ) {
        if ( ! Logs::is_logging_activity( __FUNCTION__ ) ) {
            return $check;
        }

        $raw_skip_keys = sanitize_text_field( get_option( 'ddtt_activity_updating_usermeta_skip_keys' ) );
        $skip_keys = array_filter( array_map( 'trim', explode( ',', $raw_skip_keys ) ) );

        $skip = false;

        foreach ( $skip_keys as $pattern ) {
            $regex_pattern = '/^' . str_replace( '\*', '.*', preg_quote( $pattern, '/' ) ) . '$/';

            if ( preg_match( $regex_pattern, $meta_key ) ) {
                $skip = true;
                break;
            }
        }

        $old_value = get_user_meta( $object_id, $meta_key, true );
        if ( $skip || $new_value == $old_value ) {
            return $check;
        }

        $user = get_userdata( $object_id );

        if ( ! $old_value ) {
            $old_value = '""';
        }
        if ( ! $new_value ) {
            $new_value = '""';
        }

        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {
            
            $log_message = sprintf(
                /* Translators: 1: User display name, 2: User email, 3: User ID, 4: Meta key, 5: Old value, 6: New value */
                __( 'User being updated: <code>%1$s (%2$s - ID: %3$d)</code> | Meta Key: <code>%4$s</code> (Old Value: <code>%5$s</code> => New Value: <code>%6$s</code>)', 'dev-debug-tools' ),
                $user->display_name,
                $user->user_email,
                $user->ID,
                $meta_key,
                $this->maybe_handle_array_value( $old_value ),
                $this->maybe_handle_array_value( $new_value )
            );
    
            if ( ! $this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                Helpers::write_log( __( 'Failed to write to activity log file during logout.', 'dev-debug-tools' ) );
            }
        }
        return $check;
    } // End updating_profile()


    /**
     * Handle user updating a profile
     *
     * @param int $user_id
     * @param WP_User $old_user_data
     * @param array $userdata
     * @return void
     */
    public function updating_userobject( $user_id, $old_user_data, $user ) {
        $activity_key = 'updating_usermeta';
        if ( ! Logs::is_logging_activity( $activity_key ) ) {
            return;
        }

        $raw_skip_keys = sanitize_text_field( get_option( 'ddtt_activity_updating_usermeta_skip_keys' ) );
        $skip_keys = array_filter( array_map( 'trim', explode( ',', $raw_skip_keys ) ) );

        if ( $action_label = $this->get_action_label( $activity_key ) ) {
        
            foreach ( $old_user_data->data as $meta_key => $old_value ) {
                if ( $meta_key == 'ID' ) {
                    continue;
                }

                $skip = false;
                foreach ( $skip_keys as $pattern ) {
                    $regex_pattern = '/^' . str_replace( '\*', '.*', preg_quote( $pattern, '/' ) ) . '$/';
                    if ( preg_match( $regex_pattern, $meta_key ) ) {
                        $skip = true;
                        break;
                    }
                }
                if ( $skip ) {
                    continue;
                }

                $new_value = isset( $user[ $meta_key ] ) ? $user[ $meta_key ] : null;
                if ( $new_value !== null && $old_value !== $new_value ) {

                    if ( !$old_value ) {
                        $old_value = '""';
                    }
                    if ( !$new_value ) {
                        $new_value = '""';
                    }

                    $log_message = sprintf(
                        /* Translators: Log message for user object update, including user display name, email, ID, meta key, old value, and new value */
                        __( 'User object being updated: <code>%1$s (%2$s - ID: %3$d)</code> | Meta Key: <code>%4$s</code> (Old Value: <code>%5$s</code> => New Value: <code>%6$s</code>)', 'dev-debug-tools' ),
                        $user[ 'display_name' ],
                        $user[ 'user_email' ],
                        $user_id,
                        $meta_key,
                        $old_value,
                        $new_value
                    );
            
                    if ( !$this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                        Helpers::write_log( __( 'Failed to write to activity log file during logout.', 'dev-debug-tools' ) );
                    }
                }
            }
        }
        return;
    } // End updating_userobject()


    /**
     * Log when a user's roles have changed.
     *
     * @param int $user_id
     * @param string $role
     * @param array $old_roles
     * @param string $action
     * @return void
     */
    public function updating_roles( $user_id, $role, $action = '' ) {
        if ( ! Logs::is_logging_activity( __FUNCTION__ ) ) {
            return;
        }
    
        $user = get_userdata( $user_id );

        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {
            
            $log_message = sprintf(
                /* Translators: 1: User display name, 2: User email, 3: User ID, 4: Action (added/removed), 5: Role */
                __( 'User being updated: <code>%1$s (%2$s - ID: %3$d)</code> | Role %4$s: <code>%5$s</code>', 'dev-debug-tools' ),
                $user->display_name,
                $user->user_email,
                $user->ID,
                $action,
                sanitize_text_field( $role )
            );
        
            if ( ! $this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                Helpers::write_log( __( 'Failed to write to activity log file for user role change.', 'dev-debug-tools' ) );
            }
        }
    } // End updating_roles()

    
    /**
     * Log when an admin creates a new user account in the back-end.
     *
     * @param int $user_id ID of the newly created user.
     * @return void
     */
    public function creating_account( $user_id ) {
        if ( !is_admin() || wp_doing_ajax() || !current_user_can( 'create_users' ) ) {
            return;
        }

        if ( ! Logs::is_logging_activity( __FUNCTION__ ) ) {
            return;
        }

        $new_user = get_user_by( 'ID', $user_id );
        $roles = ! empty( $new_user->roles ) ? implode( ', ', $new_user->roles ) : __( 'No role assigned', 'dev-debug-tools' );

        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {
            
            $log_message = sprintf(
                /* Translators: 1: User display name, 2: User email, 3: User ID, 4: Roles */
                __( 'New user: <code>%1$s (%2$s - ID: %3$d)</code> | Role(s): <code>%4$s</code>', 'dev-debug-tools' ),
                $new_user->display_name,
                $new_user->user_email,
                $new_user->ID,
                $roles
            );

            if ( ! $this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                Helpers::write_log( __( 'Failed to write to activity log file during new user creation.', 'dev-debug-tools' ) );
            }
        }
    } // End creating_account()


    /**
     * Deleting an account
     *
     * @param int $user_id
     * @return void
     */
    public function deleting_account( $user_id ) {
        if ( !is_admin() || wp_doing_ajax() || !current_user_can( 'delete_users' ) ) {
            return;
        }

        if ( ! Logs::is_logging_activity( __FUNCTION__ ) ) {
            return;
        }

        $deleted_user = get_user_by( 'ID', $user_id );
        if ( ! $deleted_user ) {
            // Translators: %d is the user ID that could not be found during deletion logging.
            ddtt_write_log( sprintf( __( 'User with ID %d could not be found during deletion logging.', 'dev-debug-tools' ), $user_id ) );
            return;
        }

        $roles = ! empty( $deleted_user->roles ) ? implode( ', ', $deleted_user->roles ) : 'No role assigned';
    
        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {
           
            $log_message = sprintf(
                /* Translators: 1: User display name, 2: User email, 3: User ID, 4: Roles */
                __( 'Deleted user: <code>%1$s (%2$s - ID: %3$d)</code> | Role(s): <code>%4$s</code>', 'dev-debug-tools' ),
                $deleted_user->display_name,
                $deleted_user->user_email,
                $deleted_user->ID,
                $roles
            );

            if ( ! $this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                Helpers::write_log( __( 'Failed to write to activity log file during user deletion.', 'dev-debug-tools' ) );
            }
        }
    } // End deleting_account()
    

    /**
     * Creating a post
     *
     * @param int $post_id
     * @param object $post
     * @param boolean $update
     * @return void
     */
    public function creating_post( $post_id, $post, $update ) {
        if ( $update || $post->post_status == 'auto-draft' || $post->post_type == 'revision' ) {
            return;
        }

        if ( ! Logs::is_logging_activity( __FUNCTION__ ) ) {
            return;
        }
    
        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {

            $log_message = sprintf(
                /* Translators: 1: Post ID, 2: Post Title, 3: Post Type, 4: Post Status */
                __( 'Post ID: <code>%1$d</code> | Post Title: <code>%2$s</code> | Post Type: <code>%3$s</code> | Post Status: <code>%4$s</code>', 'dev-debug-tools' ),
                $post_id,
                $post->post_title,
                $post->post_type,
                $post->post_status
            );
    
            if ( ! $this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                Helpers::write_log( __( 'Failed to write to activity log during post creation.', 'dev-debug-tools' ) );
            }
        }
    } // End creating_post()


    /**
     * Log post meta updates.
     *
     * @param mixed $check Default value for whether to allow the update.
     * @param int $object_id Post ID.
     * @param string $meta_key Meta key being updated.
     * @param mixed $new_value New meta value.
     * @return mixed
     */
    public function updating_post( $check, $object_id, $meta_key, $new_value ) {
        if ( ! Logs::is_logging_activity( __FUNCTION__ ) ) {
            return $check;
        }

        $raw_skip_keys = sanitize_text_field( get_option( 'ddtt_activity_updating_postmeta_skip_keys' ) );
        $skip_keys = array_filter( array_map( 'trim', explode( ',', $raw_skip_keys ) ) );

        $skip = false;

        foreach ( $skip_keys as $pattern ) {
            $regex_pattern = '/^' . str_replace( '\*', '.*', preg_quote( $pattern, '/' ) ) . '$/';

            if ( preg_match( $regex_pattern, $meta_key ) ) {
                $skip = true;
                break;
            }
        }

        $old_value = get_post_meta( $object_id, $meta_key, true );
        if ( $skip || $new_value == $old_value ) {
            return $check;
        }

        $post = get_post( $object_id );

        if ( $post->post_type == 'revision' ) {
            return $check;
        }

        $post_type_object = get_post_type_object( $post->post_type );
        $post_type_name = $post_type_object ? $post_type_object->labels->singular_name : ucfirst( $post->post_type );

        if ( ! $old_value ) {
            $old_value = '""';
        }
        if ( ! $new_value ) {
            $new_value = '""';
        }

        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {
        
            $log_message = sprintf(
                /* Translators: 1: Post title, 2: Post type name, 3: Post ID, 4: Meta key, 5: Old value, 6: New value */
                __( 'Post meta being updated: <code>%1$s (%2$s ID: %3$d)</code> | Meta Key: <code>%4$s</code> (Old Value: <code>%5$s</code> => New Value: <code>%6$s</code>)', 'dev-debug-tools' ),
                $post->post_title,
                $post_type_name,
                $post->ID,
                $meta_key,
                $this->maybe_handle_array_value( $old_value ),
                $this->maybe_handle_array_value( $new_value )
            );

            if ( ! $this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                Helpers::write_log( __( 'Failed to write to activity log file during post meta update.', 'dev-debug-tools' ) );
            }
        }

        return $check;
    } // End updating_postmeta()


    /**
     * Log when post meta is added.
     *
     * @param int    $meta_id     The meta ID.
     * @param int    $object_id   The post ID.
     * @param string $meta_key    The meta key.
     * @param mixed  $meta_value  The meta value.
     */
    public function adding_postmeta( $meta_id, $object_id, $meta_key, $new_value ) {
        $activity_key = 'updating_post';
        if ( ! Logs::is_logging_activity( $activity_key ) ) {
            return;
        }

        $raw_skip_keys = sanitize_text_field( get_option( 'ddtt_activity_updating_postmeta_skip_keys' ) );
        $skip_keys = array_filter( array_map( 'trim', explode( ',', $raw_skip_keys ) ) );

        $skip = false;

        foreach ( $skip_keys as $pattern ) {
            $regex_pattern = '/^' . str_replace( '\*', '.*', preg_quote( $pattern, '/' ) ) . '$/';

            if ( preg_match( $regex_pattern, $meta_key ) ) {
                $skip = true;
                break;
            }
        }

        if ( $skip ) {
            return;
        }

        $post = get_post( $object_id );
        if ( ! $post ) {
            return;
        }

        if ( $post->post_type == 'revision' ) {
            return;
        }

        $post_type_object = get_post_type_object( $post->post_type );
        $post_type_name = $post_type_object ? $post_type_object->labels->singular_name : ucfirst( $post->post_type );

        if ( $action_label = $this->get_action_label( $activity_key ) ) {

            $log_message = sprintf(
                /* Translators: 1: Post title, 2: Post type name, 3: Post ID, 4: Meta key, 5: Value */
                __( 'New meta key added for: <code>%1$s (%2$s ID: %3$d)</code> | Meta Key: <code>%4$s</code> (Value: <code>%5$s</code>)', 'dev-debug-tools' ),
                $post->post_title,
                $post_type_name,
                $object_id,
                $meta_key,
                $this->maybe_handle_array_value( $new_value )
            );

            if ( ! $this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                Helpers::write_log( __( 'Failed to write to activity log file for adding post meta.', 'dev-debug-tools' ) );
            }
        }
    } // End adding_postmeta()


    /**
     * Log when post meta is deleted.
     *
     * @param int    $meta_id    The meta ID.
     * @param int    $object_id  The post ID.
     * @param string $meta_key   The meta key.
     * @param mixed  $meta_value The meta value.
     */
    public function deleting_postmeta( $meta_ids, $object_id, $meta_key, $meta_value ) {
        $activity_key = 'updating_post';
        if ( ! Logs::is_logging_activity( $activity_key ) ) {
            return;
        }

        $raw_skip_keys = sanitize_text_field( get_option( 'ddtt_activity_updating_postmeta_skip_keys' ) );
        $skip_keys = array_filter( array_map( 'trim', explode( ',', $raw_skip_keys ) ) );

        $skip = false;

        foreach ( $skip_keys as $pattern ) {
            $regex_pattern = '/^' . str_replace( '\*', '.*', preg_quote( $pattern, '/' ) ) . '$/';

            if ( preg_match( $regex_pattern, $meta_key ) ) {
                $skip = true;
                break;
            }
        }

        if ( $skip ) {
            return;
        }

        $post = get_post( $object_id );
        if ( ! $post ) {
            return;
        }

        if ( $post->post_type == 'revision' ) {
            return;
        }

        $post_type_object = get_post_type_object( $post->post_type );
        $post_type_name = $post_type_object ? $post_type_object->labels->singular_name : ucfirst( $post->post_type );

        if ( $action_label = $this->get_action_label( $activity_key ) ) {

            $log_message = sprintf(
                /* Translators: 1: Post title, 2: Post type name, 3: Post ID, 4: Meta key */
                __( 'Meta key deleted for: <code>%1$s (%2$s ID: %3$d)</code> | Meta Key: <code>%4$s</code>', 'dev-debug-tools' ),
                $post->post_title,
                $post_type_name,
                $object_id,
                $meta_key
            );

            if ( ! $this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                Helpers::write_log( 'Failed to write to activity log file for deleting post meta.' );
            }
        }
    } // End deleting_postmeta()


    /**
     * Updating a post
     *
     * @param int $post_id
     * @param array $data
     * @return void
     */
    public function updating_postobject( $post_id, $new_post ) {
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return;
        }

        $activity_key = 'updating_post';
        if ( ! Logs::is_logging_activity( $activity_key ) ) {
            return;
        }

        $original_post = get_post( $post_id );
        if ( ! $original_post ) {
            return;
        }

        $raw_skip_keys = sanitize_text_field( get_option( 'ddtt_activity_updating_postmeta_skip_keys' ) );
        $skip_keys = array_filter( array_map( 'trim', explode( ',', $raw_skip_keys ) ) );

        $all_fields = get_object_vars( $original_post );
        $fields_to_check = array_diff( array_keys( $all_fields ), $skip_keys );

        $changes = [];

        foreach ( $fields_to_check as $field ) {
            if ( isset( $all_fields[ $field ] ) && isset( $new_post[ $field ] ) && $all_fields[ $field ] != $new_post[ $field ] ) {
                if ( $field === 'comment_count' && $all_fields[ $field ] == 0 && $new_post[ $field ] == '' ) {
                    continue;
                }
                
                $skip = false;
                foreach ( $skip_keys as $pattern ) {
                    $regex_pattern = '/^' . str_replace( '\*', '.*', preg_quote( $pattern, '/' ) ) . '$/';
                    if ( preg_match( $regex_pattern, $field ) ) {
                        $skip = true;
                        break;
                    }
                }

                if ( $skip ) {
                    continue;
                }
                
                $see_revisions = __( 'See revisions...', 'dev-debug-tools' );
                $old_value = $field === 'post_content' ? '<em>' . $see_revisions . '</em>' : $all_fields[ $field ];
                $new_value = $field === 'post_content' ? '<em>' . $see_revisions . '</em>' : $new_post[ $field ];

                $changes[] = sprintf(
                    /* Translators: 1: Meta key, 2: Old value, 3: New value */
                    __( 'Meta Key <code>%1$s</code>: (Old Value: <code>%2$s</code> => New Value: <code>%3$s</code>)', 'dev-debug-tools' ),
                    $field,
                    $old_value,
                    $new_value
                );
            }
        }

        if ( ! empty( $changes ) ) {
            
            $post_type_object = get_post_type_object( $original_post->post_type );
            $post_type_name = $post_type_object ? $post_type_object->labels->singular_name : ucfirst( $original_post->post_type );

            if ( $action_label = $this->get_action_label( $activity_key ) ) {
                $log_message = sprintf(
                    /* Translators: 1: Post title, 2: Post type name, 3: Post ID, 4: Summary of changes */
                    __( 'Post object changes for: <code>%1$s (%2$s ID: %3$d)</code> | %4$s', 'dev-debug-tools' ),
                    $original_post->post_title,
                    $post_type_name,
                    $post_id,
                    implode( ' | ', $changes )
                );

                if ( ! $this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                    Helpers::write_log( 'Failed to write to activity log file for post object meta changes.' );
                }
            }
        }
    } // End updating_post()


    /**
     * Deleting a post
     *
     * @param int $post_id
     * @return void
     */
    public function deleting_post( $post_id ) {
        if ( ! Logs::is_logging_activity( __FUNCTION__ ) ) {
            return;
        }

        $post = get_post( $post_id );
        if ( $post->post_status == 'auto-draft' || $post->post_type == 'revision' || $post->post_type == 'customize_changeset' ) {
            return;
        }
    
        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {
    
            $log_message = sprintf(
                /* Translators: 1: Post ID, 2: Post title, 3: Post type, 4: Post status */
                __( 'ID: <code>%1$d</code> | Title: <code>%2$s</code> | Post Type: <code>%3$s</code> | Status: <code>%4$s</code>', 'dev-debug-tools' ),
                $post_id,
                $post->post_title,
                $post->post_type,
                $post->post_status
            );

            if ( ! $this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                Helpers::write_log( __( 'Failed to write to activity log during post deletion.', 'dev-debug-tools' ) );
            }
        }
    } // End deleting_post()


    /**
     * Log when a post is trashed.
     *
     * @param int $post_id The ID of the trashed post.
     */
    public function trashing_post( $post_id ) {
        $activity_key = 'deleting_post';
        if ( ! Logs::is_logging_activity( $activity_key ) ) {
            return;
        }

        $post = get_post( $post_id );
        if ( ! $post ) {
            return;
        }
        if ( $post->post_type == 'revision' || $post->post_type == 'customize_changeset' ) {
            return;
        }

        $post_type_object = get_post_type_object( $post->post_type );
        $post_type_name = $post_type_object ? $post_type_object->labels->singular_name : ucfirst( $post->post_type );
        
        if ( $action_label = $this->get_action_label( $activity_key ) ) {

            $log_message = sprintf(
                /* Translators: 1: Post title, 2: Post type name, 3: Post ID, 4: Post type */
                __( 'Post Title: <code>%1$s</code> | %2$s ID: <code>%3$d</code> | Post Type: <code>%4$s</code>', 'dev-debug-tools' ),
                $post->post_title,
                $post_type_name,
                $post_id,
                $post->post_type
            );

            if ( ! $this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                Helpers::write_log( __( 'Failed to write to activity log file for trashed post.', 'dev-debug-tools' ) );
            }
        }
    } // End trashing_post()


    /**
     * Log when a post's status changes.
     *
     * @param string $new_status The new status of the post.
     * @param string $old_status The old status of the post.
     * @param WP_Post $post The post object.
     */
    public function status_post( $new_status, $old_status, $post ) {
        if ( $post->post_type == 'revision' ) {
            return;
        }
        
        if ( ! Logs::is_logging_activity( __FUNCTION__ ) ) {
            return;
        }

        if ( $old_status == 'new' || $new_status == 'auto-draft' ) {
            return;
        }

        if ( $new_status === $old_status ) {
            return;
        }

        $post_type_object = get_post_type_object( $post->post_type );
        $post_type_name = $post_type_object ? $post_type_object->labels->singular_name : ucfirst( $post->post_type );

        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {

            $log_message = sprintf(
                /* Translators: 1: Post title, 2: Post type name, 3: Post ID, 4: Old status, 5: New status */
                __( 'Post status being updated: <code>%1$s (%2$s ID: %3$d)</code> | Old Status: <code>%4$s</code> => New Status: <code>%5$s</code>', 'dev-debug-tools' ),
                $post->post_title,
                $post_type_name,
                $post->ID,
                $old_status,
                $new_status
            );

            if ( ! $this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                Helpers::write_log( __( 'Failed to write to activity log file for post status change.', 'dev-debug-tools' ) );
            }
        }
    } // End status_post()


    /**
     * Logging post/page visits
     *
     * @return void
     */
    public function visiting_post() {
        // Ensure we are in the main query and not in admin or other irrelevant areas
        if ( !is_main_query() || is_admin() ) {
            return;
        }

        // Check if logging is enabled for this action
        if ( ! Logs::is_logging_activity( __FUNCTION__ ) ) {
            return;
        }

        // Skip bot views
        if ( Helpers::is_bot() ) {
            return;
        }

        // Get the current post object
        global $post;

        // Ensure it's a valid post object and is singular (post or page)
        if ( ! isset( $post->ID ) || ! is_singular() ) {
            return;
        }

        $post_type_object = get_post_type_object( $post->post_type );
        $post_type_name = $post_type_object ? $post_type_object->labels->singular_name : ucfirst( $post->post_type );

        // Get action label
        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {

            $ip = get_current_user_id() && isset( $_SERVER[ 'REMOTE_ADDR' ] ) ? sanitize_text_field( wp_unslash( $_SERVER[ 'REMOTE_ADDR' ] ) ) : '';

           // Prepare log message
            $log_message = sprintf(
                /* Translators: 1: Post title, 2: Link URL, 3: Link URL, 4: Post type name, 5: Post ID */
                __( 'Title: <code>%1$s</code> | Link: <code><a href="%2$s" target="_blank">%3$s</a></code> | %4$s ID: <code>%5$d</code>', 'dev-debug-tools' ),
                $post->post_title,
                get_permalink( $post->ID ),
                get_permalink( $post->ID ),
                $post_type_name,
                $post->ID
            );

            // Write to the log
            if ( ! $this->write_to_log( $this->current_user_log_message( $action_label, null, $ip ) . ' | ' . $log_message ) ) {
                Helpers::write_log( __( 'Failed to write to activity log during post visit.', 'dev-debug-tools' ) );
            }
        }
    } // End visiting_post()


    /**
     * Logging bots crawling
     *
     * @return void
     */
    public function bots_crawling() {
        // Ensure we are in the main query and not in admin or other irrelevant areas
        if ( ! is_main_query() || is_admin() ) {
            return;
        }

        // Check if logging is enabled for this action
        if ( ! Logs::is_logging_activity( __FUNCTION__ ) ) {
            return;
        }

        // Check if the user is a bot
        $bot = Helpers::is_bot();
        if ( !$bot ) {
            return;
        }

        // Get the current post object
        global $post;

        // Ensure it's a valid post object and is singular (post or page)
        if ( ! isset( $post->ID ) || ! is_singular() ) {
            return;
        }

        $post_type_object = get_post_type_object( $post->post_type );
        $post_type_name = $post_type_object ? $post_type_object->labels->singular_name : ucfirst( $post->post_type );

        // Get action label
        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {

            $ip = isset( $_SERVER[ 'REMOTE_ADDR' ] ) ? sanitize_text_field( wp_unslash( $_SERVER[ 'REMOTE_ADDR' ] ) ) : '';

            // Prepare log message
            $log_message = sprintf(
                /* Translators: Log message for bot crawling posts: 1: Title, 2: Link URL, 3: Link URL, 4: Post type name, 5: Post ID, 6: Bot URL, 7: Bot name, 8: User agent */
                __( 'Title: <code>%1$s</code> | Link: <code><a href="%2$s" target="_blank">%3$s</a></code> | %4$s ID: <code>%5$d</code> | Bot: <code><a href="%6$s" target="_blank">%7$s</a></code> | User Agent: <code>%8$s</code>', 'dev-debug-tools' ),
                $post->post_title,
                get_permalink( $post->ID ),
                get_permalink( $post->ID ),
                $post_type_name,
                $post->ID,
                $bot[ 'url' ],
                $bot[ 'name' ],
                $bot[ 'user_agent' ]
            );

            // Write to the log
            if ( ! $this->write_to_log( $this->current_user_log_message( $action_label, null, $ip ) . ' | ' . $log_message ) ) {
                Helpers::write_log( __( 'Failed to write to activity log during post visit.', 'dev-debug-tools' ) );
            }
        }
    } // End bots_crawling()


    /**
     * Store all installed plugins in an option.
     */
    public function update_installed_plugins_option() {
        $all_plugins = get_plugins();

        $plugin_data = [];
        foreach ( $all_plugins as $plugin_path => $plugin_details ) {
            $sanitized_name = sanitize_text_field( $plugin_details[ 'Name' ] );
            $sanitized_version = sanitize_text_field( $plugin_details[ 'Version' ] );
            $sanitized_path = sanitize_text_field( $plugin_path );
            
            $plugin_data[ $sanitized_path ] = [
                'name'    => $sanitized_name,
                'version' => $sanitized_version,
            ];
        }

        ksort( $plugin_data );

        update_option( 'ddtt_plugins', $plugin_data );
    } // End update_installed_plugins_option()
    

    /**
     * Get a plugin's name and version that we stored
     *
     * @param string $path
     * @return string
     */
    public function get_plugin_name_and_versions( $path ) {
        $name = false;
        $old_version = false;
        $new_version = false;

        $old_plugin_data = get_option( 'ddtt_plugins' );
        if ( $old_plugin_data ) {
            $old_plugin_data = filter_var_array( $old_plugin_data, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            
            if ( isset( $old_plugin_data[ $path ] ) ) {
                $name = sanitize_text_field( $old_plugin_data[ $path ][ 'name' ] );
                $old_version = sanitize_text_field( $old_plugin_data[ $path ][ 'version' ] );
            }
        }

        $plugin_path = WP_PLUGIN_DIR . '/' . $path;
        $new_plugin_data = is_readable( $plugin_path ) ? get_plugin_data( $plugin_path ) : null;

        if ( $new_plugin_data ) {
            if ( ! $name ) {
                $name = sanitize_text_field( $new_plugin_data[ 'Name' ] );
            }
            $new_version = sanitize_text_field( $new_plugin_data[ 'Version' ] );
        }

        $unknown = __( 'Unknown', 'dev-debug-tools' );

        return [
            'name'        => $name ?? $unknown,
            'old_version' => $old_version ?? $unknown,
            'new_version' => $new_version ?? $unknown,
        ];
    } // End get_plugin_name_and_versions()
    

    /**
     * Log when a plugin is activated.
     *
     * @param string $plugin The path to the plugin file relative to the plugins directory.
     */
    public function activating_plugin( $plugin ) {
        if ( ! Logs::is_logging_activity( __FUNCTION__ ) ) {
            return;
        }

        $plugin_data = $this->get_plugin_name_and_versions( $plugin );
        $plugin_name = $plugin_data[ 'name' ];
        $plugin_version = $plugin_data[ 'new_version' ];

        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {
            $log_message = sprintf(
                /* translators: 1: Plugin name, 2: Plugin path, 3: Plugin version */
                __( 'Name: <code>%1$s</code> | Path: <code>%2$s</code> | Version: <code>%3$s</code>', 'dev-debug-tools' ),
                $plugin_name,
                $plugin,
                $plugin_version
            );

            if ( ! $this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                Helpers::write_log( __( 'Failed to write to activity log file for plugin activation.', 'dev-debug-tools' ) );
            }
        }

        $this->update_installed_plugins_option();
    } // End activating_plugin()


    /**
     * Log when a plugin is deactivated.
     *
     * @param string $plugin The path to the plugin file relative to the plugins directory.
     */
    public function deactivating_plugin( $plugin ) {
        if ( ! Logs::is_logging_activity( __FUNCTION__ ) ) {
            return;
        }

        $plugin_data = $this->get_plugin_name_and_versions( $plugin );
        $plugin_name = $plugin_data[ 'name' ];
        $plugin_version = $plugin_data[ 'new_version' ];

        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {
            $log_message = sprintf(
                /* translators: 1: Plugin name, 2: Plugin path, 3: Plugin version */
                __( 'Name: <code>%1$s</code> | Path: <code>%2$s</code> | Version: <code>%3$s</code>', 'dev-debug-tools' ),
                $plugin_name,
                $plugin,
                $plugin_version
            );

            if ( ! $this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                Helpers::write_log( __( 'Failed to write to activity log file for plugin deactivation.', 'dev-debug-tools' ) );
            }
        }
    } // End deactivating_plugin()


    /**
     * Log when a plugin is updated.
     *
     * @param \WP_Upgrader $upgrader The upgrader instance.
     * @param array        $hook_extra Extra arguments passed to the hook.
     */
    public function updating_plugin( $upgrader, $hook_extra ) {
        if ( ! Logs::is_logging_activity( __FUNCTION__ ) ) {
            return;
        }

        if ( empty( $hook_extra[ 'action' ] ) || $hook_extra[ 'action' ] !== 'update' || empty( $hook_extra[ 'type' ] ) || $hook_extra[ 'type' ] !== 'plugin' ) {
            return;
        }

        if ( ! empty( $hook_extra[ 'plugins' ] ) && is_array( $hook_extra[ 'plugins' ] ) ) {
            foreach ( $hook_extra[ 'plugins' ] as $plugin ) {
                $plugin_data = $this->get_plugin_name_and_versions( $plugin );
                $plugin_name = $plugin_data[ 'name' ];
                $old_version = $plugin_data[ 'old_version' ];
                $new_version = $plugin_data[ 'new_version' ];

                if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {
                    $log_message = sprintf(
                        /* translators: 1: Plugin name, 2: Plugin path, 3: Old version, 4: New version */
                        __( 'Name: <code>%1$s</code> | Path: <code>%2$s</code> | Old Version: <code>%3$s</code> | New Version: <code>%4$s</code>', 'dev-debug-tools' ),
                        $plugin_name,
                        $plugin,
                        $old_version,
                        $new_version
                    );

                    if ( ! $this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                        Helpers::write_log( __( 'Failed to write to activity log file for plugin update.', 'dev-debug-tools' ) );
                    }
                }
            }
        }

        $this->update_installed_plugins_option();
    } // End updating_plugin()


    /**
     * Log when a plugin is deleted.
     *
     * @param string $plugin The path to the plugin file relative to the plugins directory.
     */
    public function deleting_plugin( $plugin ) {
        if ( ! Logs::is_logging_activity( __FUNCTION__ ) ) {
            return;
        }

        $plugin_data = $this->get_plugin_name_and_versions( $plugin );
        $plugin_name = $plugin_data[ 'name' ];
        $plugin_version = $plugin_data[ 'old_version' ];

        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {
            $log_message = sprintf(
                /* translators: 1: Plugin name, 2: Plugin path, 3: Plugin version */
                __( 'Name: <code>%1$s</code> | Path: <code>%2$s</code> | Version: <code>%3$s</code>', 'dev-debug-tools' ),
                $plugin_name,
                $plugin,
                $plugin_version
            );

            if ( ! $this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                Helpers::write_log( __( 'Failed to write to activity log file for plugin deletion.', 'dev-debug-tools' ) );
            }
        }

        $this->update_installed_plugins_option();
    } // End deleting_plugin()


    /**
     * Log when a theme is switched.
     *
     * @param string $new_name Name of the new theme.
     * @param WP_Theme $new_theme The new theme object.
     * @param WP_Theme $old_theme The old theme object.
     */
    public function switching_theme( $new_name, $new_theme, $old_theme ) {
        if ( ! Logs::is_logging_activity( __FUNCTION__ ) ) {
            return;
        }

        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {            
            $log_message = sprintf(
                /* translators: 1: Old theme name, 2: New theme name */
                __( 'Old Theme: <code>%1$s</code> | New Theme: <code>%2$s</code>', 'dev-debug-tools' ),
                $old_theme->get( 'Name' ),
                $new_name
            );

            if ( ! $this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                Helpers::write_log( __( 'Failed to write to activity log file for theme switching.', 'dev-debug-tools' ) );
            }
        }
    } // End switching_theme()


    /**
     * Log when a theme is updated.
     *
     * @param WP_Upgrader $upgrader Upgrader instance.
     * @param array       $hook_extra Contains additional information like 'type' and 'skin'.
     */
    public function updating_theme( $upgrader, $hook_extra ) {
        if ( ! Logs::is_logging_activity( __FUNCTION__ ) ) {
            return;
        }

        if ( isset( $hook_extra[ 'type' ] ) && $hook_extra[ 'type' ] === 'theme' ) {

            $unknown = __( 'Unknown', 'dev-debug-tools' );
            $theme_name  = $unknown;
            $old_version = $unknown;
            $new_version = $unknown;

            if ( isset( $upgrader->new_theme_data ) && ! empty( $upgrader->new_theme_data ) ) {
                $new_theme_data = $upgrader->new_theme_data;

                if ( isset( $new_theme_data[ 'Name' ] ) ) {
                    $theme_name  = $new_theme_data[ 'Name' ];
                    $new_version = $new_theme_data[ 'Version' ];
                }
                
            } elseif ( isset( $upgrader->skin->options[ 'title' ] ) ) {
                $theme_name  = $upgrader->skin->options[ 'title' ];
                $new_version = $upgrader->skin->options[ 'version' ];

            } elseif ( isset( $hook_extra[ 'themes' ] ) ) {
                $theme_name = $hook_extra[ 'themes' ][ 0 ];

            } elseif ( isset( $hook_extra[ 'theme' ] ) ) {
                $theme_name = $hook_extra[ 'theme' ];
            }

            if ( isset( $upgrader->skin->theme_info ) ) {
                $old_version = $upgrader->skin->theme_info->get( 'Version' );
            }

            if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {                
                $log_message = sprintf(
                    /* translators: 1: Theme name, 2: Old version number, 3: New version number */
                    __( 'Theme: <code>%1$s</code> | Old Version: <code>%2$s</code> | New Version: <code>%3$s</code>', 'dev-debug-tools' ),
                    $theme_name,
                    $old_version,
                    $new_version
                );

                if ( ! $this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                    Helpers::write_log( __( 'Failed to write to activity log file for theme update.', 'dev-debug-tools' ) );
                }
            }
        }
    } // End updating_theme()


    /**
     * Log site setting updates.
     *
     * @param string $option_name Option name being updated.
     * @param mixed  $old_value   Old value of the option.
     * @param mixed  $new_value   New value of the option.
     */
    public function updating_settings( $option_name, $old_value, $new_value ) {
        if ( ! Logs::is_logging_activity( __FUNCTION__ ) ) {
            return;
        }

        $raw_skip_keys = sanitize_text_field( get_option( 'ddtt_activity_updating_setting_skip_keys' ) );
        $skip_keys = array_filter( array_map( 'trim', explode( ',', $raw_skip_keys ) ) );

        $skip = false;

        foreach ( $skip_keys as $pattern ) {
            $regex_pattern = '/^' . str_replace( '\*', '.*', preg_quote( $pattern, '/' ) ) . '$/';

            if ( preg_match( $regex_pattern, $option_name ) ) {
                $skip = true;
                break;
            }
        }

        if ( $skip || $new_value == $old_value ) {
            return;
        }

        if ( ! $old_value ) {
            $old_value = '';
        }
        if ( ! $new_value ) {
            $new_value = '';
        }

        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {
            $log_message = sprintf(
                /* translators: 1: Setting name, 2: Old value, 3: New value */
                __( 'Site setting updated: <code>%1$s</code> (Old Value: <code>%2$s</code> => New Value: <code>%3$s</code>)', 'dev-debug-tools' ),
                $option_name,
                $this->maybe_handle_array_value( $old_value ),
                $this->maybe_handle_array_value( $new_value )
            );

            if ( ! $this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                Helpers::write_log( __( 'Failed to write to activity log file during site setting update.', 'dev-debug-tools' ) );
            }
        }
    } // End updating_settings()


    /**
     * Handle failed login attempts.
     *
     * @param string $username The username used in the failed login attempt.
     * @param WP_Error $error
     * @return void
     */
    public function failed_login_attempt( $username, $error ) {
        if ( ! Logs::is_logging_activity( __FUNCTION__ ) ) {
            return;
        }

        $ip_address = isset( $_SERVER[ 'REMOTE_ADDR' ] ) ? sanitize_text_field( wp_unslash( $_SERVER[ 'REMOTE_ADDR' ] ) ) : __( 'Unknown IP', 'dev-debug-tools' );

        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {

            $error_message = ! is_wp_error( $error ) ? __( 'No error information', 'dev-debug-tools' ) : sanitize_text_field( $error->get_error_message() );
            $error_message = str_starts_with( $error_message, 'Error:' ) ? substr( $error_message, 7 ) : $error_message;

            $log_message = sprintf(
                /* translators: 1: Username, 2: IP address, 3: Error message */
                __( 'Username: <code>%1$s</code> | IP Address: <code>%2$s</code> | Error: <code>%3$s</code>', 'dev-debug-tools' ),
                sanitize_text_field( $username ),
                sanitize_text_field( $ip_address ),
                $error_message
            );

            if ( ! $this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                Helpers::write_log( __( 'Failed to write to activity log file for failed login attempt.', 'dev-debug-tools' ) );
            }
        }
    } // End failed_login_attempt()


    /**
     * Log when a password reset is initialized.
     *
     * @param string $user_login Username or email address used for the password reset.
     * @return void
     */
    public function resetting_password( $user_login ) {
        if ( ! Logs::is_logging_activity( __FUNCTION__ ) ) {
            return;
        }

        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {
            $user = get_user_by( 'login', $user_login );
            if ( ! $user ) {
                $user = get_user_by( 'email', $user_login );
            }

            if ( $user ) {
                $log_message = sprintf(
                    /* translators: 1: User display name, 2: User email, 3: User ID */
                    __( 'For user: <code>%1$s (%2$s - ID: %3$d)</code>', 'dev-debug-tools' ),
                    $user->display_name,
                    $user->user_email,
                    $user->ID
                );

                if ( ! $this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                    /* translators: Log message for failed activity log write during password reset for known user */
                    Helpers::write_log( __( 'Failed to write to activity log file during password reset initialization.', 'dev-debug-tools' ) );
                }

            } else {
                $log_message = sprintf(
                    /* translators: %s: User login or email used for password reset */
                    __( 'Unknown user attempted password reset with identifier: %s', 'dev-debug-tools' ),
                    $user_login
                );

                if ( ! $this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                    Helpers::write_log( __( 'Failed to write to activity log file during password reset initialization for unknown user.', 'dev-debug-tools' ) );
                }
            }
        }
    } // End resetting_password()


    /**
     * Prevent cloning and unserializing
     */
    public function __clone() {}
    public function __wakeup() {}

}


Activity_Log::instance();