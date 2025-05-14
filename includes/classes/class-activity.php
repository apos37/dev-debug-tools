<?php
/**
 * Activity logging
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Initiate the class
 */
add_action( 'init', function() {
    (new DDTT_ACTIVITY)->init();
} );



/**
 * Main plugin class.
 */
class DDTT_ACTIVITY {


    /**
     * Log file path
     *
     * @var string
     */
    public $log_directory_path = DDTT_UPLOADS_DIR . '/' . DDTT_TEXTDOMAIN . '/';
    public $log_filename = 'activity.log';
    public $log_file_path;


    /**
     * Activities to log
     *
     * @var array
     */
    public $activities = [
        // User-related activities
        'users' => [
            'logging_in' => [
                'settings' => 'Users Logging In',
                'action'   => 'User Logged In',
            ],
            'updating_usermeta' => [
                'settings' => 'Updating User Meta',
                'action'   => 'User Meta Updated',
            ],
            'creating_account' => [
                'settings' => 'Creating Accounts',
                'action'   => 'Account Created',
            ],
            'deleting_account' => [
                'settings' => 'Deleting Accounts',
                'action'   => 'Account Deleted',
            ],
            'updating_roles' => [
                'settings' => 'Updating User Roles',
                'action'   => 'User Roles Updated',
            ],
        ],
    
        // Post-related activities
        'posts' => [
            'creating_post' => [
                'settings' => 'Creating Posts & Pages',
                'action'   => 'Post Created',
            ],
            'updating_post' => [
                'settings' => 'Updating Posts & Pages',
                'action'   => 'Post Updated',
            ],
            'deleting_post' => [
                'settings' => 'Deleting Posts & Pages',
                'action'   => 'Post Deleted',
            ],
            'status_post' => [
                'settings' => 'Changing Post Statuses',
                'action'   => 'Post Status Changed',
            ],
            'visiting_post' => [
                'settings' => 'Visiting Posts & Pages',
                'action'   => 'Post/Page Visited',
            ],
            'bots_crawling' => [
                'settings' => 'Bots Crawling Posts & Pages',
                'action'   => 'Bot Crawled'
            ]
        ],
    
        // Plugin-related activities
        'plugins' => [
            'activating_plugin' => [
                'settings' => 'Activating Plugins',
                'action'   => 'Plugin Activated',
            ],
            'updating_plugin' => [
                'settings' => 'Updating Plugins',
                'action'   => 'Plugin Updated',
            ],
            'deactivating_plugin' => [
                'settings' => 'Deactivating Plugins',
                'action'   => 'Plugin Deactivated',
            ],
            'deleting_plugin' => [
                'settings' => 'Deleting Plugins',
                'action'   => 'Plugin Deleted',
            ],
        ],
    
        // Theme-related activities
        'themes' => [
            'switching_theme' => [
                'settings' => 'Switching Themes',
                'action'   => 'Theme Switched',
            ],
            'updating_theme' => [
                'settings' => 'Updating Themes',
                'action'   => 'Theme Updated',
            ],
        ],
    
        // Settings-related activities
        'settings' => [
            'updating_settings' => [
                'settings' => 'Updating Site Settings',
                'action'   => 'Site Settings Updated',
            ],
        ],
    
        // Security-related activities
        'security' => [
            'failed_login_attempt' => [
                'settings' => 'Failed Login Attempts',
                'action'   => 'Failed Login Attempted',
            ],
            'resetting_password' => [
                'settings' => 'Resetting Passwords',
                'action'   => 'Reset Password Requested',
            ],
        ],
    ];      


    /**
	 * Constructor
	 */
	public function __construct() {

        // The log file path
        $this->log_file_path = $this->log_directory_path.$this->log_filename;

	} // End __construct()


    /**
     * Load on init, but not every time the class is called
     *
     * @return void
     */
    public function init() {

        // Make sure it's enabled
        if ( !$this->is_logging_activity() ) {
            return;
        } else {

            // If we haven't stored the plugin names yet, let's do so
            if ( !get_option( 'ddtt_plugins' ) ) {
                $this->update_installed_plugins_option();
            }
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

    } // End init()


    /**
     * Set the highlight args to be used by activity log viewer
     *
     * @return array
     */
    public function highlight_args() {
        // Set the args
        $args = apply_filters( 'ddtt_highlight_activity_log', [
            'users' => [
                'name'          => 'User-related',
                'bg_color'      => '#FF6F61',
                'font_color'    => '#FFFFFF',
            ],
            'posts' => [
                'name'          => 'Post-related',
                'bg_color'      => '#00A2E8',
                'font_color'    => '#000000',
            ],
            'plugins' => [
                'name'          => 'Plugin-related',
                'bg_color'      => '#0073AA',
                'font_color'    => '#FFFFFF',
            ],
            'themes' => [
                'name'          => 'Theme-related',
                'bg_color'      => '#006400',
                'font_color'    => '#FFFFFF',
            ],
            'settings' => [
                'name'          => 'Settings-related',
                'bg_color'      => '#FFA500',
                'font_color'    => '#000000',
            ],
            // 'roles' => [
            //     'name'          => 'User Role-related',
            //     'bg_color'      => '#8A2BE2',
            //     'font_color'    => '#FFFFFF',
            // ],
            'security' => [
                'name'          => 'Security-related',
                'bg_color'      => '#DC143C',
                'font_color'    => '#FFFFFF',
            ],
        ] );        
    
        // Return them
        return $args;
    } // End highlight_args()    


    /**
     * Check if we are logging activity
     *
     * @return boolean
     */
    public function is_logging_activity( $activity_key = '' ) {
        $activities = get_option( DDTT_GO_PF . 'activity' );
        if ( !empty( $activities ) && is_array( $activities ) ) {
            if ( $activity_key ) {
                return !empty( $activities[ $activity_key ] );
            }
            return in_array( 1, $activities );
        }
        return false;
    } // End is_logging_activity()


    /**
     * Remove the log file if it exists.
     *
     * @return bool True if the log file was successfully deleted, false if it doesn't exist or could not be deleted.
     */
    public function remove_log_file() {
        if ( !function_exists( 'WP_Filesystem' ) ) {
            require_once DDTT_ADMIN_INCLUDES_URL . 'file.php';
        }

        global $wp_filesystem;
        if ( !WP_Filesystem() ) {
            error_log( 'DDTT LOG: Cannot access WP Filesystem to delete log file and directory during uninstall.' );
            return false;
        }

        $log_directory_path = $this->log_directory_path;
        $log_file_path = $this->log_file_path;

        if ( $wp_filesystem->exists( $log_file_path ) ) {
            if ( !$wp_filesystem->delete( $log_file_path ) ) {
                error_log( 'DDTT LOG: Failed to delete log file during uninstall.' );
                return false;
            }
        }
    
        // Remove the directory if it exists and is empty
        if ( $wp_filesystem->exists( $log_directory_path ) ) {
            $files = $wp_filesystem->dirlist( $log_directory_path );
            if ( empty( $files ) ) {
                if ( !$wp_filesystem->rmdir( $log_directory_path, false ) ) {
                    error_log( 'DDTT LOG: Failed to remove log directory during uninstall.' );
                    return false;
                }
            } else {
                error_log( 'DDTT LOG: Log directory is not empty, could not delete at uninstall.' );
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

        // Initiate the filesystem
        if ( !function_exists( 'WP_Filesystem' ) ) {
            if ( strpos( DDTT_ADMIN_INCLUDES_URL, 'http' ) !== false ) {
                ddtt_write_log( 'DDTT_ADMIN_INCLUDES_URL: ' . DDTT_ADMIN_INCLUDES_URL );
                return false;
            }
            require_once DDTT_ADMIN_INCLUDES_URL . 'file.php';
        }
        if ( !WP_Filesystem() ) {
            ddtt_write_log( 'Cannot access WP Filesystem' );
            return false;
        }
        global $wp_filesystem;

        // Create log directory if it doesn't exist
        $log_directory_path = $this->log_directory_path;
        if ( !$wp_filesystem->is_dir( $log_directory_path ) ) {
            if ( !$wp_filesystem->mkdir( $log_directory_path, FS_CHMOD_DIR ) ) {
                ddtt_write_log( 'Could not create log directory: ' . $log_directory_path );
                return false;
            }
        }

        // Create log file if it doesn't exist
        if ( !$wp_filesystem->exists( $log_file_path ) ) {
            if ( !$wp_filesystem->put_contents( $log_file_path, '', FS_CHMOD_FILE ) ) {
                ddtt_write_log( 'Could not create log file: ' . $log_file_path );
                return false;
            }
        }

        $existing_log = $wp_filesystem->get_contents( $log_file_path );
        if ( $existing_log === false ) {
            ddtt_write_log( 'Could not find activity log to write to.' );
            return false;
        }
    
        if ( !$wp_filesystem->put_contents( $log_file_path, $existing_log . $log_entry, FS_CHMOD_FILE ) ) {
            ddtt_write_log( 'Could not write to activity log.' );
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
        foreach ( $this->activities as $activity ) {
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
        if ( !$current_user ) {
            return 'Current user not found.';
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
        if ( !$this->is_logging_activity( __FUNCTION__ ) ) {
            return;
        }
        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {
            if ( !$this->write_to_log( $this->current_user_log_message( $action_label, $user ) ) ) {
                ddtt_write_log( 'Failed to write to activity log file during login.' );
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
        if ( !$this->is_logging_activity( __FUNCTION__ ) ) {
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

        if ( !$old_value ) {
            $old_value = '""';
        }
        if ( !$new_value ) {
            $new_value = '""';
        }

        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {
            
            $log_message = sprintf(
                'User being updated: <code>%s (%s - ID: %d)</code> | Meta Key: <code>%s</code> (Old Value: <code>%s</code> => New Value: <code>%s</code>)',
                $user->display_name,
                $user->user_email,
                $user->ID,
                $meta_key,
                $this->maybe_handle_array_value( $old_value ),
                $this->maybe_handle_array_value( $new_value )
            );
    
            if ( !$this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                ddtt_write_log( 'Failed to write to activity log file during logout.' );
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
        if ( !$this->is_logging_activity( $activity_key ) ) {
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
                        'User object being updated: <code>%s (%s - ID: %d)</code> | Meta Key: <code>%s</code> (Old Value: <code>%s</code> => New Value: <code>%s</code>)',
                        $user[ 'display_name' ],
                        $user[ 'user_email' ],
                        $user_id,
                        $meta_key,
                        $old_value,
                        $new_value
                    );
            
                    if ( !$this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                        ddtt_write_log( 'Failed to write to activity log file during logout.' );
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
        if ( !$this->is_logging_activity( __FUNCTION__ ) ) {
            return;
        }
    
        $user = get_userdata( $user_id );

        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {
            
            $log_message = sprintf(
                'User being updated: <code>%s (%s - ID: %d)</code> | Role %s: <code>%s</code>',
                $user->display_name,
                $user->user_email,
                $user->ID,
                $action,
                sanitize_text_field( $role )
            );
        
            if ( ! $this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                ddtt_write_log( 'Failed to write to activity log file for user role change.' );
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

        if ( !$this->is_logging_activity( __FUNCTION__ ) ) {
            return;
        }

        $new_user = get_user_by( 'ID', $user_id );
        $roles = !empty( $new_user->roles ) ? implode( ', ', $new_user->roles ) : 'No role assigned';

        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {
            
            $log_message = sprintf(
                'New user: <code>%s (%s - ID: %d)</code> | Role(s): <code>%s</code>',
                $new_user->display_name,
                $new_user->user_email,
                $new_user->ID,
                $roles
            );

            if ( !$this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                ddtt_write_log( 'Failed to write to activity log file during new user creation.' );
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
    
        if ( !$this->is_logging_activity( __FUNCTION__ ) ) {
            return;
        }

        $deleted_user = get_user_by( 'ID', $user_id );
        if ( !$deleted_user ) {
            ddtt_write_log( sprintf( 'User with ID %d could not be found during deletion logging.', $user_id ) );
            return;
        }

        $roles = !empty( $deleted_user->roles ) ? implode( ', ', $deleted_user->roles ) : 'No role assigned';
    
        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {
           
            $log_message = sprintf(
                'Deleted user: <code>%s (%s - ID: %d)</code> | Role(s): <code>%s</code>',
                $deleted_user->display_name,
                $deleted_user->user_email,
                $deleted_user->ID,
                $roles
            );

            if ( !$this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                ddtt_write_log( 'Failed to write to activity log file during user deletion.' );
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
    
        if ( !$this->is_logging_activity( __FUNCTION__ ) ) {
            return;
        }
    
        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {

            $log_message = sprintf(
                'Post ID: <code>%d</code> | Post Title: <code>%s</code> | Post Type: <code>%s</code> | Post Status: <code>%s</code>',
                $post_id,
                $post->post_title,
                $post->post_type,
                $post->post_status
            );
    
            if ( !$this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                ddtt_write_log( 'Failed to write to activity log during post creation.' );
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
        if ( !$this->is_logging_activity( __FUNCTION__ ) ) {
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

        if ( !$old_value ) {
            $old_value = '""';
        }
        if ( !$new_value ) {
            $new_value = '""';
        }

        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {
        
            $log_message = sprintf(
                'Post meta being updated: <code>%s (%s ID: %d)</code> | Meta Key: <code>%s</code> (Old Value: <code>%s</code> => New Value: <code>%s</code>)',
                $post->post_title,
                $post_type_name,
                $post->ID,
                $meta_key,
                $this->maybe_handle_array_value( $old_value ),
                $this->maybe_handle_array_value( $new_value )
            );

            if ( !$this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                ddtt_write_log( 'Failed to write to activity log file during post meta update.' );
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
        if ( !$this->is_logging_activity( $activity_key ) ) {
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
        if ( !$post ) {
            return;
        }

        if ( $post->post_type == 'revision' ) {
            return;
        }

        $post_type_object = get_post_type_object( $post->post_type );
        $post_type_name = $post_type_object ? $post_type_object->labels->singular_name : ucfirst( $post->post_type );

        if ( $action_label = $this->get_action_label( $activity_key ) ) {

            $log_message = sprintf(
                'New meta key added for: <code>%s (%s ID: %d)</code> | Meta Key: <code>%s</code> (Value: <code>%s</code>)',
                $post->post_title,
                $post_type_name,
                $object_id,
                $meta_key,
                $this->maybe_handle_array_value( $new_value )
            );

            if ( !$this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                ddtt_write_log( 'Failed to write to activity log file for adding post meta.' );
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
        if ( !$this->is_logging_activity( $activity_key ) ) {
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
        if ( !$post ) {
            return;
        }

        if ( $post->post_type == 'revision' ) {
            return;
        }

        $post_type_object = get_post_type_object( $post->post_type );
        $post_type_name = $post_type_object ? $post_type_object->labels->singular_name : ucfirst( $post->post_type );

        if ( $action_label = $this->get_action_label( $activity_key ) ) {

            $log_message = sprintf(
                'Meta key deleted for: <code>%s (%s ID: %d)</code> | Meta Key: <code>%s</code>',
                $post->post_title,
                $post_type_name,
                $object_id,
                $meta_key
            );

            if ( !$this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                ddtt_write_log( 'Failed to write to activity log file for deleting post meta.' );
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
        if ( !$this->is_logging_activity( $activity_key ) ) {
            return;
        }

        $original_post = get_post( $post_id );
        if ( !$original_post ) {
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
                
                $old_value = $field === 'post_content' ? '<em>See revisions...</em>' : $all_fields[ $field ];
                $new_value = $field === 'post_content' ? '<em>See revisions...</em>' : $new_post[ $field ];
    
                $changes[] = sprintf(
                    'Meta Key <code>%s</code>: (Old Value: <code>%s</code> => New Value: <code>%s</code>)',
                    $field,
                    $old_value,
                    $new_value
                );
            }
        }

        if ( !empty( $changes ) ) {
            
            $post_type_object = get_post_type_object( $original_post->post_type );
            $post_type_name = $post_type_object ? $post_type_object->labels->singular_name : ucfirst( $original_post->post_type );

            if ( $action_label = $this->get_action_label( $activity_key ) ) {
                $log_message = sprintf(
                    'Post object changes for: <code>%s (%s ID: %d)</code> | %s',
                    $original_post->post_title,
                    $post_type_name,
                    $post_id,
                    implode( ' | ', $changes )
                );

                if ( !$this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                    ddtt_write_log( 'Failed to write to activity log file for post object meta changes.' );
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
        if ( !$this->is_logging_activity( __FUNCTION__ ) ) {
            return;
        }

        $post = get_post( $post_id );
        if ( $post->post_status == 'auto-draft' || $post->post_type == 'revision' || $post->post_type == 'customize_changeset' ) {
            return;
        }
    
        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {
    
            $log_message = sprintf(
                'ID: <code>%d</code> | Title: <code>%s</code> | Post Type: <code>%s</code> | Status: <code>%s</code>',
                $post_id,
                $post->post_title,
                $post->post_type,
                $post->post_status
            );
    
            if ( !$this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                ddtt_write_log( 'Failed to write to activity log during post deletion.' );
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
        if ( !$this->is_logging_activity( $activity_key ) ) {
            return;
        }

        $post = get_post( $post_id );
        if ( !$post ) {
            return;
        }
        if ( $post->post_type == 'revision' || $post->post_type == 'customize_changeset' ) {
            return;
        }

        $post_type_object = get_post_type_object( $post->post_type );
        $post_type_name = $post_type_object ? $post_type_object->labels->singular_name : ucfirst( $post->post_type );
        
        if ( $action_label = $this->get_action_label( $activity_key ) ) {

            $log_message = sprintf(
                'Post Title: <code>%s</code> | %s ID: <code>%d</code> | Post Type: <code>%s</code>',
                $post->post_title,
                $post_type_name,
                $post_id,
                $post->post_type
            );

            if ( !$this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                ddtt_write_log( 'Failed to write to activity log file for trashed post.' );
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
        
        if ( !$this->is_logging_activity( __FUNCTION__ ) ) {
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
                'Post status being updated: <code>%s (%s ID: %d)</code> | Old Status: <code>%s</code> => New Status: <code>%s</code>',
                $post->post_title,
                $post_type_name,
                $post->ID,
                $old_status,
                $new_status
            );

            if ( !$this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                ddtt_write_log( 'Failed to write to activity log file for post status change.' );
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
        if ( !$this->is_logging_activity( __FUNCTION__ ) ) {
            return;
        }

        // Skip bot views
        if ( ddtt_is_bot() ) {
            return;
        }

        // Get the current post object
        global $post;

        // Ensure it's a valid post object and is singular (post or page)
        if ( !isset( $post->ID ) || !is_singular() ) {
            return;
        }

        $post_type_object = get_post_type_object( $post->post_type );
        $post_type_name = $post_type_object ? $post_type_object->labels->singular_name : ucfirst( $post->post_type );

        // Get action label
        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {

            $ip = get_current_user_id() ? sanitize_text_field( $_SERVER[ 'REMOTE_ADDR' ] ) : '';

            // Prepare log message
            $log_message = sprintf(
                'Title: <code>%s</code> | Link: <code><a href="%s" target="_blank">%s</a></code> | %s ID: <code>%d</code>',
                $post->post_title,
                get_permalink( $post->ID ),
                get_permalink( $post->ID ),
                $post_type_name,
                $post->ID,
            );

            // Write to the log
            if ( !$this->write_to_log( $this->current_user_log_message( $action_label, null, $ip ) . ' | ' . $log_message ) ) {
                ddtt_write_log( 'Failed to write to activity log during post visit.' );
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
        if ( !is_main_query() || is_admin() ) {
            return;
        }

        // Check if logging is enabled for this action
        if ( !$this->is_logging_activity( __FUNCTION__ ) ) {
            return;
        }

        // Check if the user is a bot
        $bot = ddtt_is_bot();
        if ( !$bot ) {
            return;
        }

        // Get the current post object
        global $post;

        // Ensure it's a valid post object and is singular (post or page)
        if ( !isset( $post->ID ) || !is_singular() ) {
            return;
        }

        $post_type_object = get_post_type_object( $post->post_type );
        $post_type_name = $post_type_object ? $post_type_object->labels->singular_name : ucfirst( $post->post_type );

        // Get action label
        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {

            $ip = sanitize_text_field( $_SERVER[ 'REMOTE_ADDR' ] );

            // Prepare log message
            $log_message = sprintf(
                'Title: <code>%s</code> | Link: <code><a href="%s" target="_blank">%s</a></code> | %s ID: <code>%d</code> | Bot: <code><a href="%s" target="_blank">%s</a></code> | User Agent: <code>%s</code>',
                $post->post_title,
                get_permalink( $post->ID ),
                get_permalink( $post->ID ),
                $post_type_name,
                $post->ID,
                $bot[ 'url' ],
                $bot[ 'name' ],
                $bot[ 'user_agent' ],
            );

            // Write to the log
            if ( !$this->write_to_log( $this->current_user_log_message( $action_label, null, $ip ) . ' | ' . $log_message ) ) {
                ddtt_write_log( 'Failed to write to activity log during post visit.' );
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
            if ( !$name ) {
                $name = sanitize_text_field( $new_plugin_data[ 'Name' ] );
            }
            $new_version = sanitize_text_field( $new_plugin_data[ 'Version' ] );
        }

        return [
            'name'        => $name ?? 'Unknown',
            'old_version' => $old_version ?? 'Unknown',
            'new_version' => $new_version ?? 'Unknown',
        ];
    } // End get_plugin_name_and_versions()
    

    /**
     * Log when a plugin is activated.
     *
     * @param string $plugin The path to the plugin file relative to the plugins directory.
     */
    public function activating_plugin( $plugin ) {
        if ( !$this->is_logging_activity( __FUNCTION__ ) ) {
            return;
        }

        $plugin_data = $this->get_plugin_name_and_versions( $plugin );
        $plugin_name = $plugin_data[ 'name' ];
        $plugin_version = $plugin_data[ 'new_version' ];

        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {
            $log_message = sprintf(
                'Name: <code>%s</code> | Path: <code>%s</code> | Version: <code>%s</code>',
                $plugin_name,
                $plugin,
                $plugin_version
            );

            if ( !$this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                ddtt_write_log( 'Failed to write to activity log file for plugin activation.' );
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
        if ( !$this->is_logging_activity( __FUNCTION__ ) ) {
            return;
        }

        $plugin_data = $this->get_plugin_name_and_versions( $plugin );
        $plugin_name = $plugin_data[ 'name' ];
        $plugin_version = $plugin_data[ 'new_version' ];

        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {
            $log_message = sprintf(
                'Name: <code>%s</code> | Path: <code>%s</code> | Version: <code>%s</code>',
                $plugin_name,
                $plugin,
                $plugin_version
            );

            if ( !$this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                ddtt_write_log( 'Failed to write to activity log file for plugin deactivation.' );
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
        if ( !$this->is_logging_activity( __FUNCTION__ ) ) {
            return;
        }

        if ( empty( $hook_extra[ 'action' ] ) || $hook_extra[ 'action' ] !== 'update' || empty( $hook_extra[ 'type' ] ) || $hook_extra[ 'type' ] !== 'plugin' ) {
            return;
        }

        if ( !empty( $hook_extra[ 'plugins' ] ) && is_array( $hook_extra[ 'plugins' ] ) ) {
            foreach ( $hook_extra[ 'plugins' ] as $plugin ) {
                $plugin_data = $this->get_plugin_name_and_versions( $plugin );
                $plugin_name = $plugin_data[ 'name' ];
                $old_version = $plugin_data[ 'old_version' ];
                $new_version = $plugin_data[ 'new_version' ];

                if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {
                    $log_message = sprintf(
                        'Name: <code>%s</code> | Path: <code>%s</code> | Old Version: <code>%s</code> | New Version: <code>%s</code>',
                        $plugin_name,
                        $plugin,
                        $old_version,
                        $new_version
                    );

                    if ( ! $this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                        ddtt_write_log( 'Failed to write to activity log file for plugin update.' );
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
        if ( !$this->is_logging_activity( __FUNCTION__ ) ) {
            return;
        }

        $plugin_data = $this->get_plugin_name_and_versions( $plugin );
        $plugin_name = $plugin_data[ 'name' ];
        $plugin_version = $plugin_data[ 'old_version' ];

        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {
            $log_message = sprintf(
                'Name: <code>%s</code> | Path: <code>%s</code> | Version: <code>%s</code>',
                $plugin_name,
                $plugin,
                $plugin_version
            );

            if ( !$this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                ddtt_write_log( 'Failed to write to activity log file for plugin deletion.' );
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
        if ( !$this->is_logging_activity( __FUNCTION__ ) ) {
            return;
        }

        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {
            $log_message = sprintf(
                'Old Theme: <code>%s</code> | New Theme: <code>%s</code>',
                $old_theme->get( 'Name' ),
                $new_name
            );

            if ( !$this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                ddtt_write_log( 'Failed to write to activity log file for theme switching.' );
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
        if ( !$this->is_logging_activity( __FUNCTION__ ) ) {
            return;
        }

        if ( isset( $hook_extra[ 'type' ] ) && $hook_extra[ 'type' ] === 'theme' ) {

            $theme_name = 'Unknown';
            $old_version = 'Unknown';
            $new_version = 'Unknown';
                
            if ( isset( $upgrader->new_theme_data ) && !empty( $upgrader->new_theme_data ) ) {
                $new_theme_data = $upgrader->new_theme_data;

                if ( isset( $new_theme_data[ 'Name' ] ) ) {
                    $theme_name = $new_theme_data[ 'Name' ];
                    $new_version = $new_theme_data[ 'Version' ];
                }
                
            }  elseif ( isset( $upgrader->skin->options[ 'title' ] ) ) {
                $theme_name = $upgrader->skin->options[ 'title' ];
                $new_version = $upgrader->skin->options[ 'version' ];

            } elseif ( isset( $hook_extra[ 'themes' ] ) ) {
                $theme_name = $hook_extra[ 'themes' ][0];

            } elseif ( isset( $hook_extra[ 'theme' ] ) ) {
                $theme_name = $hook_extra[ 'theme' ];
            }

            if ( isset( $upgrader->skin->theme_info ) ) {
                $old_version = $upgrader->skin->theme_info->get( 'Version' );
            }

            if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {
                $log_message = sprintf(
                    'Theme: <code>%s</code> | Old Version: <code>%s</code> | New Version: <code>%s</code>',
                    $theme_name,
                    $old_version,
                    $new_version
                );

                if ( !$this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                    ddtt_write_log( 'Failed to write to activity log file for theme update.' );
                }
            }
        }
    } // End updating_theme()'


    /**
     * Log site setting updates.
     *
     * @param string $option_name Option name being updated.
     * @param mixed  $old_value   Old value of the option.
     * @param mixed  $new_value   New value of the option.
     */
    public function updating_settings( $option_name, $old_value, $new_value ) {
        if ( ! $this->is_logging_activity( __FUNCTION__ ) ) {
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
            $old_value = '""';
        }
        if ( ! $new_value ) {
            $new_value = '""';
        }

        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {
            
            $log_message = sprintf(
                'Site setting updated: <code>%s</code> (Old Value: <code>%s</code> => New Value: <code>%s</code>)',
                $option_name,
                $this->maybe_handle_array_value( $old_value ),
                $this->maybe_handle_array_value( $new_value )
            );

            if ( ! $this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                ddtt_write_log( 'Failed to write to activity log file during site setting update.' );
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
        if ( !$this->is_logging_activity( __FUNCTION__ ) ) {
            return;
        }

        $ip_address = sanitize_text_field( $_SERVER[ 'REMOTE_ADDR' ] ) ?? 'Unknown IP';

        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {

            $error_message = !is_wp_error( $error ) ? 'No error information' : sanitize_text_field( $error->get_error_message() );
            $error_message = str_starts_with( $error_message, 'Error:' ) ? substr( $error_message, 7 ) : $error_message;

            $log_message = sprintf(
                'Username: <code>%s</code> | IP Address: <code>%s</code> | Error: <code>%s</code>',
                sanitize_text_field( $username ),
                sanitize_text_field( $ip_address ),
                $error_message
            );

            if ( !$this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                ddtt_write_log( 'Failed to write to activity log file for failed login attempt.' );
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
        if ( !$this->is_logging_activity( __FUNCTION__ ) ) {
            return;
        }

        if ( $action_label = $this->get_action_label( __FUNCTION__ ) ) {
            $user = get_user_by( 'login', $user_login );
            if ( !$user ) {
                $user = get_user_by( 'email', $user_login );
            }
            if ( $user ) {

                $log_message = sprintf(
                    'For user: <code>%s (%s - ID: %d)</code>',
                    $user->display_name,
                    $user->user_email,
                    $user->ID,
                );

                if ( !$this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                    ddtt_write_log( 'Failed to write to activity log file during password reset initialization.' );
                }

            } else {

                $log_message = sprintf(
                    'Unknown user attempted password reset with identifier: %s',
                    $user_login
                );

                if ( !$this->write_to_log( $this->current_user_log_message( $action_label ) . ' | ' . $log_message ) ) {
                    ddtt_write_log( 'Failed to write to activity log file during password reset initialization for unknown user.' );
                }
            }
        }
    } // End resetting_password()

}