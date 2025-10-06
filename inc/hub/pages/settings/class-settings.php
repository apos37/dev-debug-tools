<?php
/**
 * Settings
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Settings {

    /**
     * Get the sections for the settings page.
     *
     * Returns an array of sections with their titles.
     *
     * @return array
     */
    public static function sections() : array {
        $sections = [
            'general' => __( 'General', 'dev-debug-tools' ),
        ];

        if ( Helpers::is_dev() ) {
            $dev_only = [
                'logging'      => __( 'Logging', 'dev-debug-tools' ),
                'config_files' => __( 'Config Files', 'dev-debug-tools' ),
                'metadata'     => __( 'Metadata', 'dev-debug-tools' ),
                'heartbeat'    => __( 'Heartbeat', 'dev-debug-tools' ),
                'online_users' => __( 'Online Users', 'dev-debug-tools' ),
                'admin_bar'    => __( 'Admin Bar', 'dev-debug-tools' ),
                'admin_areas'  => __( 'Admin Area', 'dev-debug-tools' ),
                'security'     => __( 'Security', 'dev-debug-tools' ),
            ];
            $sections = array_merge( $sections, $dev_only );
        }

        return $sections;
    } // End sections()
    
    
    /**
     * Get the general settings options.
     *
     * Returns an array of settings options for the plugin.
     *
     * @return array
     */
    public static function general_options() : array {
        $fields = [ 
            'developers' => [ 
                'title'   => __( 'Developer Accounts', 'dev-debug-tools' ), 
                'desc'    => __( 'Add user accounts that should see errors, receive fatal error notifications, and have access to special features.', 'dev-debug-tools' ), 
                'type'    => 'devs', 
                'default' => get_option( 'admin_email' ), 
            ], 
        ]; 

        if ( Helpers::is_dev() ) { 
            $current_time = time(); 

            $time_format_choices = [ 
                'n/j/Y g:i a T'  => wp_date( 'n/j/Y g:i a T', $current_time ) . ' ( n/j/Y g:i a T )', 
                'n/j/Y H:i T'    => wp_date( 'n/j/Y H:i T', $current_time ) . ' ( n/j/Y H:i T )', 
                'F j, Y g:i a T' => wp_date( 'F j, Y g:i a T', $current_time ) . ' ( F j, Y g:i a T )', 
                'F j, Y G:i T'   => wp_date( 'F j, Y G:i T', $current_time ) . ' ( F j, Y G:i T )', 
                'Y-m-d H:i:s'    => wp_date( 'Y-m-d H:i:s', $current_time ) . ' ( Y-m-d H:i:s )', 
                'm/d/Y g:i a'    => wp_date( 'm/d/Y g:i a', $current_time ) . ' ( m/d/Y g:i a )', 
                'm/d/Y H:i'      => wp_date( 'm/d/Y H:i', $current_time ) . ' ( m/d/Y H:i )', 
                'D, M j, Y g:i a'=> wp_date( 'D, M j, Y g:i a', $current_time ) . ' ( D, M j, Y g:i a )', 
                'D, M j, Y H:i'  => wp_date( 'D, M j, Y H:i', $current_time ) . ' ( D, M j, Y H:i )', 
            ]; 

            $fields[ 'dev_timezone' ] = [ 
                'title'   => __( 'Developer Timezone', 'dev-debug-tools' ), 
                'desc'    => __( 'Changes the timezone on Debug Log viewer and other areas in the plugin. Default is what the site uses.', 'dev-debug-tools' ), 
                'type'    => 'select', 
                'choices' => timezone_identifiers_list(), 
                'default' => get_option( 'timezone_string', 'UTC' ), 
            ]; 

            $fields[ 'dev_timeformat' ] = [ 
                'title'   => __( 'Developer Time Format', 'dev-debug-tools' ), 
                'desc'    => __( 'Changes the time format on Debug Log viewer and other areas in the plugin.', 'dev-debug-tools' ), 
                'type'    => 'select', 
                'choices' => $time_format_choices, 
                'default' => get_option( 'time_format', 'F j, Y g:i a T' ), 
            ]; 

            $fields[ 'open_nav_new_tab' ] = [ 
                'title'   => __( 'Open Nav Links in New Tab', 'dev-debug-tools' ), 
                'desc'    => __( 'When navigating to a different page in the plugin from the dropdown navigation at the top right of the page, open the link in a new tab.', 'dev-debug-tools' ), 
                'type'    => 'checkbox', 
                'default' => false, 
            ]; 
        } 

        return [ 
            'general' => [ 
                'label'  => false, 
                'fields' => $fields, 
            ], 
        ]; 
    } // End general_options()


    /**
     * Get the logging settings options.
     *
     * Returns an array of logging settings options for the plugin.
     *
     * @return array
     */
    public static function logging_options() : array {
        // Activities
        if ( ! class_exists( 'Activity_Log' ) ) {
            require_once Bootstrap::path( 'inc/hub/pages/tools/logs/class-activity.php' );
        }
        $activities = Activity_Log::activities();
        $activity_options = [];
        foreach ( $activities as $activity ) {
            foreach ( $activity as $key => $a ) {
                $activity_options[ $key ] = $a[ 'settings' ];
            }
        }
        
        return [
            'debug_log' => [
                'label' => __( 'Debug Log', 'dev-debug-tools' ),
                'fields' => [
                    'disable_error_counts' => [
                        'title'      => __( 'Disable Error Counts', 'dev-debug-tools' ),
                        'desc'       => __( 'Disabling this will prevent repeated counting and improve page load time for developers (developers are the only ones that see the count).', 'dev-debug-tools' ),
                        'type'       => 'checkbox',
                        'default'    => true,
                    ],
                    // 'log_user_url' => [
                    //     'title'      => __( 'Also Log User and URL With Errors', 'dev-debug-tools' ),
                    //     'desc'       => __( 'Adds an additional line to debug.log errors with the user ID, user display name, and url with query strings when a user error is triggered. This can add up quick, so use with caution and only temporarily.', 'dev-debug-tools' ),
                    //     'type'       => 'checkbox',
                    //     'default'    => false,
                    // ],
                    'wp_mail_failure' => [
                        'title'      => __( 'Log WP Mail Failures', 'dev-debug-tools' ),
                        'desc'       => __( 'Log the details of any failed WP Mail attempts.', 'dev-debug-tools' ),
                        'type'       => 'checkbox',
                        'default'    => false,
                    ],
                    'fatal_discord_enable' => [
                        'title'     => __( 'Enable Discord Webhook for Fatal Errors', 'dev-debug-tools' ),
                        'desc'      => __( 'If you want to receive notifications of fatal errors in Discord, enable this option.', 'dev-debug-tools' ),
                        'type'      => 'checkbox',
                        'default'   => false,
                    ],
                    'fatal_discord_webhook' => [
                        'title'     => __( 'Discord Webhook for Fatal Errors', 'dev-debug-tools' ),
                        /* translators: %s is a link to Discord webhook documentation */
                        'desc'      => sprintf( __( 'If you want to receive notifications of fatal errors in %s, enter your webhook URL here. Webhook URL should look like: https://discord.com/api/webhooks/xxx/xxx...', 'dev-debug-tools' ), '<a href="https://support.discord.com/hc/en-us/articles/228383668-Intro-to-Webhooks" target="_blank">Discord</a>' ),
                        'type'      => 'text',
                        'pattern'   => '^https://discord\.com/api/webhooks/\d+/\w+$',
                        'default'   => '',
                        'condition' => [
                            'field' => 'fatal_discord_enable',
                            'value' => true,
                        ],
                    ],
                ]
            ],
            'paths' => [
                'label' => __( 'Paths', 'dev-debug-tools' ),
                'desc'  => __( 'Configure the paths for various log files. These paths should be absolute paths on the server. If you start the path with a forward slash (<code>/</code>), it will be treated as the beginning of the absolute path, otherwise it will be relative to the WordPress root directory.', 'dev-debug-tools' ),
                'fields' => [
                    'debug_log_path' => [
                        'title'      => __( 'Debug Log Path', 'dev-debug-tools' ),
                        'desc'       => __( 'The path to the debug.log file. This is usually in the wp-content directory.', 'dev-debug-tools' ),
                        'type'       => 'path',
                        'default'    => Helpers::get_default_debug_log_path( true ),
                    ],
                    'error_log_path' => [
                        'title'      => __( 'Error Log Path', 'dev-debug-tools' ),
                        'desc'       => __( 'The path to the error.log file. This is usually in the root directory.', 'dev-debug-tools' ),
                        'type'       => 'path',
                        'default'    => 'error_log',
                    ],
                    'admin_error_log_path' => [
                        'title'      => __( 'Admin Error Log Path', 'dev-debug-tools' ),
                        'desc'       => __( 'The path to the admin error.log file. This is usually in the wp-admin directory.', 'dev-debug-tools' ),
                        'type'       => 'path',
                        'default'    => 'wp-admin/error_log',
                    ],
                    'log_files' => [
                        'title'      => __( 'Additional Log Files', 'dev-debug-tools' ),
                        'desc'       => __( 'We automatically check your debug.log, error_log, and admin error_log. If you have additional logs you would like to view on the Logs page, enter the root paths to the files here (not including the domain).', 'dev-debug-tools' ),
                        'type'       => 'path_plus',
                        'default'    => '',
                    ],
                ]
            ],
            'activity_log' => [
                'label' => __( 'Activity Log', 'dev-debug-tools' ),
                'fields' => [
                    'activity' => [
                        'title'     => __( 'Enable Activity Logs', 'dev-debug-tools' ),
                        'desc'      => __( 'Enable logging of user activity.', 'dev-debug-tools' ),
                        'type'      => 'checkboxes',
                        'choices'   => $activity_options,
                    ],
                    'activity_updating_usermeta_skip_keys' => [
                        'title'     => __( 'Skip User Meta Keys', 'dev-debug-tools' ),
                        'desc'      => __( 'Enter user meta keys (comma-separated) to exclude from the activity log. Use asterisks (*) as wildcards for prefixes or suffixes. For example, "wp_*" excludes all keys starting with "wp_".', 'dev-debug-tools' ),
                        'type'      => 'textarea',
                        'default'   => 'session_tokens, user_activation_key, wpfv_user_level, _transient_*',
                        'html'      => '<div class="ddtt-html">' . __( 'Suggested', 'dev-debug-tools' ) . ': `session_tokens`, `user_activation_key`, `wpfv_user_level`, `_transient_*`</div>'
                    ],
                    'activity_updating_postmeta_skip_keys' => [
                        'title'     => __( 'Skip Post Meta Keys', 'dev-debug-tools' ),
                        'desc'      => __( 'Enter post meta keys (comma-separated) to exclude from the activity log. Use asterisks (*) as wildcards for prefixes or suffixes. For example, "wp_*" excludes all keys starting with "wp_".', 'dev-debug-tools' ),
                        'type'      => 'textarea',
                        'default'   => 'ID, post_modified, post_modified_gmt, filter, _edit_lock, _edit_last, _wp_desired_post_slug, _encloseme, _wp_trash_, _transient_*',
                        'html'      => '<div class="ddtt-html">' . __( 'Suggested', 'dev-debug-tools' ) . ': `ID`, `post_modified`, `post_modified_gmt`, `filter`, `_edit_lock`, `_edit_last`, `_wp_desired_post_slug`, `_encloseme`, `_wp_trash_`, `_transient_*`</div>'
                    ],
                    'activity_updating_setting_skip_keys' => [
                        'title'     => __( 'Skip Setting/Option Keys', 'dev-debug-tools' ),
                        'desc'      => __( 'Enter options (comma-separated) to exclude from the activity log. Use asterisks (*) as wildcards for prefixes or suffixes. For example, "wp_*" excludes all options starting with "wp_".', 'dev-debug-tools' ),
                        'type'      => 'textarea',
                        'default'   => 'cron, rewrite_rules, recently_edited, _site_transient_*, _transient_*',
                        'html'      => '<div class="ddtt-html">' . __( 'Suggested', 'dev-debug-tools' ) . ': `cron`, `rewrite_rules`, `recently_edited`, `_site_transient_*`, `_transient_*`</div>'
                    ],
                ]
            ]
        ];
    } // End logging_options()


    /**
     * Get the config files settings options.
     *
     * Returns an array of config files settings options for the plugin.
     *
     * @return array
     */
    public static function config_files_options() : array {
        return [
            'wpconfig_cleaner' => [
                'label' => __( 'WP-Config Cleaner (✨)', 'dev-debug-tools' ),
                'desc'  => __( 'This tool located in the Raw Editor lets you tidy up the wp-config.php file in one click. The options below control what gets cleaned. After running the cleaner, you can cancel editing if needed, so test freely and turn off any options you don’t want.', 'dev-debug-tools' ),
                'fields' => [
                    'wpconfig_move_old_ddtt' => [
                        'title'      => __( 'Update DDT Section', 'dev-debug-tools' ),
                        'desc'       => __( 'For users upgrading from a version prior to 3.0, this option will relocate the old snippet section into its new standard position above "That\'s all, stop editing!", remove the legacy wrapper comments, and unify the comment format. After the initial cleanup, you can disable this option if desired, though leaving it on will not cause issues.', 'dev-debug-tools' ),
                        'type'       => 'checkbox',
                        'default'    => true,
                    ],
                    'wpconfig_simplify_mysql_settings' => [
                        'title'      => __( 'Simplify Database Settings', 'dev-debug-tools' ),
                        'desc'       => __( 'Removes unnecessary MySQL configuration comments and formatting for a cleaner wp-config.php layout, while keeping the functional settings intact.', 'dev-debug-tools' ),
                        'type'       => 'checkbox',
                        'default'    => true,
                    ],
                    'wpconfig_minimize_auth_comments' => [
                        'title'      => __( 'Minimize Auth Key Comments', 'dev-debug-tools' ),
                        'desc'       => __( 'Condenses the default comment blocks that surround the authentication keys and salts section.', 'dev-debug-tools' ),
                        'type'       => 'checkbox',
                        'default'    => true,
                    ],
                    'wpconfig_improve_abs_path' => [
                        'title'      => __( 'Improve ABSPATH Definition', 'dev-debug-tools' ),
                        'desc'       => __( 'Reformats the ABSPATH setting at the end of wp-config.php for clarity and consistency, ensuring the conditional check is optimized by always using braces.', 'dev-debug-tools' ),
                        'type'       => 'checkbox',
                        'default'    => true,
                    ],
                    'wpconfig_remove_double_line_spaces' => [
                        'title'      => __( 'Remove Double Blank Lines', 'dev-debug-tools' ),
                        'desc'       => __( 'Collapses any unnecessary double line breaks in wp-config.php so that spacing remains compact but still readable.', 'dev-debug-tools' ),
                        'type'       => 'checkbox',
                        'default'    => true,
                    ],
                    'wpconfig_add_spaces_inside_parenthesis_and_brackets' => [
                        'title'      => __( 'Format Parentheses and Brackets', 'dev-debug-tools' ),
                        'desc'       => __( 'Adjusts the formatting of wp-config.php to add consistent spacing inside parentheses and brackets for better readability.', 'dev-debug-tools' ),
                        'type'       => 'checkbox',
                        'default'    => true,
                    ],
                    'wpconfig_convert_multi_line_to_single_line' => [
                        'title'      => __( 'Convert Multi-line Comments', 'dev-debug-tools' ),
                        'desc'       => __( 'Changes any multi-line style comments into a standardized /** ... */ single-line format to unify the code style throughout wp-config.php.', 'dev-debug-tools' ),
                        'type'       => 'checkbox',
                        'default'    => true,
                    ],
                ]
            ],
            'htaccess_cleaner' => [
                'label' => __( 'HTACCESS Cleaner (✨)', 'dev-debug-tools' ),
                'desc'  => __( 'This tool located in the Raw Editor lets you tidy up the .htaccess file in one click. The options below control what gets cleaned. After running the cleaner, you can cancel editing if needed, so test freely and turn off any options you don’t want.', 'dev-debug-tools' ),
                'fields' => [
                    'htaccess_move_old_ddtt' => [
                        'title'      => __( 'Update DDT Section', 'dev-debug-tools' ),
                        'desc'       => __( 'For users upgrading from a version prior to 3.0, this option will remove the legacy wrapper comments. After the initial cleanup, you can disable this option if desired, though leaving it on will not cause issues.', 'dev-debug-tools' ),
                        'type'       => 'checkbox',
                        'default'    => true,
                    ],
                    'htaccess_move_all_code_at_top' => [
                        'title'      => __( 'Move All Code from Top', 'dev-debug-tools' ),
                        'desc'       => __( 'Relocates all code blocks at the top of the .htaccess file down below the # BEGIN WordPress - END WordPress section.', 'dev-debug-tools' ),
                        'type'       => 'checkbox',
                        'default'    => true,
                    ],
                    'htaccess_minimize_begin_end_comments' => [
                        'title'      => __( 'Minimize Begin End Comments', 'dev-debug-tools' ),
                        'desc'       => __( 'Removes the unnecessary comments explaining that the lines between "BEGIN WordPress" and "END WordPress" are for the WordPress core and should not be modified. Also removes any spaces between the anchors comments.', 'dev-debug-tools' ),
                        'type'       => 'checkbox',
                        'default'    => true,
                    ],
                    'htaccess_remove_double_comment_hashes' => [
                        'title'      => __( 'Remove Double Comment Hashes', 'dev-debug-tools' ),
                        'desc'       => __( 'Collapses any unnecessary double hash marks (##) to a single hash mark (#) for cleaner comments.', 'dev-debug-tools' ),
                        'type'       => 'checkbox',
                        'default'    => true,
                    ],
                    'htaccess_remove_double_line_spaces' => [
                        'title'      => __( 'Remove Double Blank Lines', 'dev-debug-tools' ),
                        'desc'       => __( 'Collapses any unnecessary double line breaks so that spacing remains compact but still readable.', 'dev-debug-tools' ),
                        'type'       => 'checkbox',
                        'default'    => true,
                    ],
                    'htaccess_remove_spaces_at_top_and_bottom' => [
                        'title'      => __( 'Remove Spaces at Top and Bottom', 'dev-debug-tools' ),
                        'desc'       => __( 'Removes any extra spaces at the beginning and end of the file for a cleaner look.', 'dev-debug-tools' ),
                        'type'       => 'checkbox',
                        'default'    => true,
                    ],
                    'htaccess_add_line_breaks_between_blocks' => [
                        'title'      => __( 'Add Line Breaks Between Blocks', 'dev-debug-tools' ),
                        'desc'       => __( 'Adds a single line break between code blocks for improved readability.', 'dev-debug-tools' ),
                        'type'       => 'checkbox',
                        'default'    => true,
                    ]
                ]
            ]
        ];
    } // End config_files_options()


    /**
     * Get the logging settings options.
     *
     * Returns an array of logging settings options for the plugin.
     *
     * @return array
     */
    public static function metadata_options() : array {
        return [
            'general' => [
                'label' => false,
                'fields' => [
                    'protected_meta_keys' => [
                        'title'      => __( 'Protected Meta Keys', 'dev-debug-tools' ),
                        'desc'       => __( 'Enter meta keys (comma-separated) to protect from being deleted. Use asterisks (*) as wildcards for prefixes or suffixes. For example, "wp_*" protects all keys starting with "wp_".', 'dev-debug-tools' ),
                        'type'       => 'textarea',
                        'default'    => 'user_registered, user_activation_key, _edit_last, _edit_lock, _wp_attached_file, _wp_attachment_metadata',
                        'html'      => '<div class="ddtt-html">' . __( 'Suggested', 'dev-debug-tools' ) . ': `user_registered`, `user_activation_key`, `_edit_last`, `_edit_lock`, `_wp_attached_file`, `_wp_attachment_metadata`</div>'
                    ],
                ]
            ],
        ];
    } // End metadata_options()


    /**
     * Get the heartbeat settings options.
     *
     * Returns an array of heartbeat settings options for the plugin.
     *
     * @return array
     */
    public static function heartbeat_options() : array {
        return [
            'general' => [
                'label'  => __( 'Options', 'dev-debug-tools' ),
                'fields' => [
                    'enable_heartbeat_monitor' => [
                        'title' => __( 'Enable Heartbeat Monitoring', 'dev-debug-tools' ),
                        'desc'  => __( 'Monitor the WordPress Heartbeat API activity and performance in your console across all pages.', 'dev-debug-tools' ),
                        'type'  => 'checkbox',
                        'default' => false,
                    ],
                    'disable_everywhere' => [
                        'title' => __( 'Disable Heartbeat Everywhere', 'dev-debug-tools' ),
                        'desc'  => __( 'Completely disable the WordPress Heartbeat API on both frontend and admin. Useful if you suspect Heartbeat is causing server load or 503 errors.', 'dev-debug-tools' ),
                        'type'  => 'checkbox',
                        'default' => false,
                    ],
                    'disable_admin' => [
                        'title' => __( 'Disable Heartbeat in Admin', 'dev-debug-tools' ),
                        'desc'  => __( 'Stop Heartbeat API requests in the WordPress admin area only. Auto-saves and post lock features will not work.', 'dev-debug-tools' ),
                        'type'  => 'checkbox',
                        'default' => false,
                    ],
                    'disable_frontend' => [
                        'title' => __( 'Disable Heartbeat on Frontend', 'dev-debug-tools' ),
                        'desc'  => __( 'Stop Heartbeat API requests on the frontend for logged-in users.', 'dev-debug-tools' ),
                        'type'  => 'checkbox',
                        'default' => false,
                    ],
                ],
            ],
        ];
    } // End heartbeat_options()


    /**
     * Get the upload options.
     *
     * Returns an array of upload options for the plugin.
     *
     * @return array
     */
    public static function upload_options() : array {
        return [
            'upload' => [
                'label' => __( 'Import an Object', 'dev-debug-tools' ),
                'fields' => [
                    'import_object' => [
                        'title'      => __( 'Add or Override an Object', 'dev-debug-tools' ),
                        'desc'       => __( 'Use this form to import a single object using a JSON file. You can download an object from the relative object managers to get a sense of what the structure should look like.', 'dev-debug-tools' ),
                        'type'       => 'upload',
                        'filetypes'  => [ 'json' ],
                        'ignore'     => true
                    ],
                ]
            ]
        ];
    } // End metadata_options()


    /**
     * Get the online users settings options.
     *
     * Returns an array of online users settings options for the plugin.
     *
     * @return array
     */
    public static function online_users_options() : array {
        // Online Users - prioritize roles
        $roles = get_editable_roles();
        $role_options = [];
        foreach ( $roles as $key => $role ) {
            $role_options[ $key ] = $role[ 'name' ];
        }

        // Online Users settings
        return [
            'general' => [
                'label' => false,
                'desc'  => __( 'These settings control the Online Users feature, which adds an indicator to the admin bar and a column to the users admin list showing who is currently online. The admin bar indicator is only shown to administrators and only checks for users when you load the page. It also caches the results for 60 seconds to improve performance, so a new online user may not shown online immediately. If you are seeing a lag from high traffic, try increasing the time frame, minimizing the roles tracked, disabling heartbeat if enabled, or disabling the feature entirely.', 'dev-debug-tools' ),
                'fields' => [
                    'online_users' => [
                        'title'      => __( 'Enable Online Users', 'dev-debug-tools' ),
                        'desc'       => __( 'Adds an indicator to admin bar and users admin list column.', 'dev-debug-tools' ),
                        'type'       => 'checkbox',
                        'default'    => true,
                    ],
                    'online_users_last_seen' => [
                        'title'      => __( 'Last Seen Window (Minutes)', 'dev-debug-tools' ),
                        'desc'       => __( 'Consider users "online" if they’ve been active within this time window.', 'dev-debug-tools' ),
                        'type'       => 'number',
                        'default'    => 5,
                    ],
                    'online_users_heartbeat' => [
                        'title'      => __( 'Enable Heartbeat', 'dev-debug-tools' ),
                        'desc'       => __( 'Keep track of online users in real-time.', 'dev-debug-tools' ),
                        'type'       => 'checkbox',
                        'default'    => true,
                    ],
                    'online_users_heartbeat_interval' => [
                        'title'      => __( 'Heartbeat Interval (Minutes)', 'dev-debug-tools' ),
                        'desc'       => __( 'Frequency for AJAX heartbeats to confirm users are still online.', 'dev-debug-tools' ),
                        'type'       => 'number',
                        'default'    => 1,
                    ],
                    'online_users_link' => [
                        'title'      => __( 'User Link URL', 'dev-debug-tools' ),
                        'desc'       => __( 'Link online users in the admin bar. Merge tags available: {user_id}, {user_email} ie. https://yourdomain.com/another-page/?user={user_id}. Defaults to user profile edit page.', 'dev-debug-tools' ),
                        'type'       => 'text',
                        'default'    => admin_url( 'user-edit.php?user_id={user_id}' ),
                    ],
                    'online_users_roles' => [
                        'title'      => __( 'Roles to Track on Page Load', 'dev-debug-tools' ),
                        'desc'       => __( 'Select the roles you want to track on page load.', 'dev-debug-tools' ),
                        'type'       => 'checkboxes',
                        'choices'    => $role_options,
                        'default'    => [ 'administrator' ],
                    ],
                    'online_users_heartbeat_roles' => [
                        'title'      => __( 'Roles to Monitor via Heartbeat', 'dev-debug-tools' ),
                        'desc'       => __( 'Logs these roles still on a page via AJAX every X minutes (defined above). Recommended for admins only or low-traffic sites.', 'dev-debug-tools' ),
                        'type'       => 'checkboxes',
                        'choices'    => $role_options,
                        'default'    => [ 'administrator' ],
                    ],
                    'online_users_priority_roles' => [
                        'title'      => __( 'Roles to Prioritize on Top', 'dev-debug-tools' ),
                        'desc'       => __( 'Select the roles to prioritize on top of the online users list.', 'dev-debug-tools' ),
                        'type'       => 'checkboxes',
                        'choices'    => $role_options,
                        'default'    => [ 'administrator' ],
                    ],
                    'online_users_discord_enable' => [
                        'title'     => __( 'Enable Discord Webhook for Logins', 'dev-debug-tools' ),
                        'desc'      => __( 'If you want to receive notifications of logins in Discord, enable this option.', 'dev-debug-tools' ),
                        'type'      => 'checkbox',
                        'default'   => false,
                    ],
                    'online_users_discord_webhook' => [
                        'title'     => __( 'Discord Webhook for Logins', 'dev-debug-tools' ),
                        /* translators: %s is a link to Discord webhook documentation */
                        'desc'      => sprintf( __( 'If you want to receive notifications of logins in %s, enter your webhook URL here. Webhook URL should look like: https://discord.com/api/webhooks/xxx/xxx...', 'dev-debug-tools' ), '<a href="https://support.discord.com/hc/en-us/articles/228383668-Intro-to-Webhooks" target="_blank">Discord</a>' ),
                        'type'      => 'text',
                        'pattern'   => '^https://discord\.com/api/webhooks/\d+/\w+$',
                        'default'   => '',
                        'condition' => [
                            'field' => 'online_users_discord_enable',
                            'value' => true,
                        ],
                    ],
                ]
            ],
        ];
    } // End online_users_options()


    /**
     * Get the admin bar settings options.
     *
     * Returns an array of admin bar settings options for the plugin.
     *
     * @return array
     */
    public static function admin_bar_options() : array {
        $options = [
            'general' => [
                'label' => false,
                'fields' => [
                    'admin_bar_wp_logo' => [
                        'title'     => __( 'Remove WordPress Logo', 'dev-debug-tools' ),
                        'desc'      => __( 'Remove the WordPress logo from the admin bar.', 'dev-debug-tools' ),
                        'type'      => 'checkbox',
                        'default'   => false,
                    ],
                    'admin_bar_logs' => [
                        'title'     => __( 'Add Log Error Count', 'dev-debug-tools' ),
                        'desc'      => __( 'Add the total number of errors in your logs to the admin bar.', 'dev-debug-tools' ),
                        'type'      => 'checkbox',
                        'default'   => true,
                    ],
                    'admin_bar_resources' => [
                        'title'     => __( 'Add Resources Menu', 'dev-debug-tools' ),
                        'desc'      => __( 'Add a Resources menu to the admin bar with links to useful resources.', 'dev-debug-tools' ),
                        'type'      => 'checkbox',
                        'default'   => true,
                    ],
                    'admin_bar_user_id' => [
                        'title'     => __( 'Add My User ID', 'dev-debug-tools' ),
                        'desc'      => __( 'Display your User ID in the admin bar instead of "Howdy," for quick reference.', 'dev-debug-tools' ),
                        'type'      => 'checkbox',
                        'default'   => true,
                    ],
                    'admin_bar_page_loaded' => [
                        'title'     => __( 'Add Page Loaded Time', 'dev-debug-tools' ),
                        'desc'      => __( 'Display the time it took to load the current page as well as loaded date and time in the admin bar.', 'dev-debug-tools' ),
                        'type'      => 'checkbox',
                        'default'   => false,
                    ],
                    'admin_bar_condense' => [
                        'title'     => __( 'Condense Admin Bar Items', 'dev-debug-tools' ),
                        'desc'      => __( 'Condense the admin bar to save space. Note that not all items may be visible or affected when condensed.', 'dev-debug-tools' ),
                        'type'      => 'select',
                        'choices' => [
                            'No'                           => __( 'No', 'dev-debug-tools' ),
                            'Everyone'                     => __( 'Everyone', 'dev-debug-tools' ),
                            'Developer Only'               => __( 'Developer Only', 'dev-debug-tools' ),
                            'Everyone Excluding Developer' => __( 'Everyone Excluding Developer', 'dev-debug-tools' ),
                        ],
                        'default'   => 'No',
                    ]
                ]
            ],
            'front-end' => [
                'label' => __( 'Front End Only', 'dev-debug-tools' ),
                'fields' => [
                    'admin_bar_add_links' => [
                        'title'     => __( 'Add Links to Admin Bar', 'dev-debug-tools' ),
                        'desc'      => __( 'Add All Admin Menu Links to Front End', 'dev-debug-tools' ) . '<br><a class="ddtt-button" href="#" id="ddtt-refresh-admin-bar-menu-links" style="font-style: normal; margin-top: 10px; display: inline-block;">' . __( 'Refresh Links', 'dev-debug-tools' ) . '</a>',
                        'type'      => 'checkbox',
                        'default'   => true,
                    ],
                    'admin_bar_post_id' => [
                        'title'     => __( 'Add Post ID, Post Type, and Post Status', 'dev-debug-tools' ),
                        'desc'      => __( 'Display the Post ID, Post Type, and Post Status in the admin bar for quick reference.', 'dev-debug-tools' ),
                        'type'      => 'checkbox',
                        'default'   => true,
                    ],
                    'admin_bar_shortcodes' => [
                        'title'     => __( 'Add Shortcodes Finder', 'dev-debug-tools' ),
                        'desc'      => __( 'Add a Shortcodes finder to the admin bar for quick reference to shortcodes used on the page.', 'dev-debug-tools' ),
                        'type'      => 'checkbox',
                        'default'   => true,
                    ],
                    'admin_bar_centering_tool' => [
                        'title'     => __( 'Add Centering Tool', 'dev-debug-tools' ),
                        'desc'      => __( 'Add a centering tool to the admin bar for easier alignment of elements on the page.', 'dev-debug-tools' ),
                        'type'      => 'checkbox',
                        'default'   => true,
                    ],
                ]
            ],
        ];

        // Append Gravity Forms finder at the bottom if active
        if ( is_plugin_active( 'gravityforms/gravityforms.php' ) ) {
            $gravityFormsFinder = [
                'admin_bar_gravity_form_finder' => [
                    'title'   => __( 'Gravity Forms Finder', 'dev-debug-tools' ),
                    'desc'    => __( 'Add a Gravity Forms finder to the admin bar for quick access to forms.', 'dev-debug-tools' ),
                    'type'    => 'checkbox',
                    'default' => true,
                ]
            ];
            // Move to second-to-last position
            $fields = $options[ 'front-end' ][ 'fields' ];
            $fieldsBefore = array_slice( $fields, 0, count( $fields ) - 1, true );
            $fieldsAfter = array_slice( $fields, count( $fields ) - 1, 1, true );
            $options[ 'front-end' ][ 'fields' ] = $fieldsBefore + $gravityFormsFinder + $fieldsAfter;
        }

        return $options;
    } // End admin_bar_options()


    /**
     * Get the admin areas settings options.
     *
     * Returns an array of admin areas settings options for the plugin.
     *
     * @return array
     */
    public static function admin_areas_options() : array {
        return [
            'general' => [
                'label' => false,
                'fields' => [
                    'ql_user_id' => [
                        'title'     => __( 'User ID Quick Links', 'dev-debug-tools' ),
                        'desc'      => __( 'Adds a User ID column to the Users admin list page with quick debug links for developers to debug user meta.', 'dev-debug-tools' ),
                        'type'      => 'checkbox',
                        'default'   => true,
                    ],
                    'ql_post_id' => [
                        'title'     => __( 'Post ID Quick Links', 'dev-debug-tools' ),
                        'desc'      => __( 'Adds a Post ID column to the Posts and Pages admin list page with quick debug links for developers to debug post meta.', 'dev-debug-tools' ),
                        'type'      => 'checkbox',
                        'default'   => true,
                    ],
                    'ql_comment_id' => [
                        'title'     => __( 'Comment ID Quick Links', 'dev-debug-tools' ),
                        'desc'      => __( 'Adds columns for Comment ID, Comment Type and Karma to the Comments admin list page with quick debug links for developers to debug comment meta.', 'dev-debug-tools' ),
                        'type'      => 'checkbox',
                        'default'   => true,
                    ],
                    'ids_in_search' => [
                        'title'     => __( 'Include IDs in Admin List Table Searches', 'dev-debug-tools' ),
                        'desc'      => __( 'Allows searching by ID in the admin list tables.', 'dev-debug-tools' ),
                        'type'      => 'checkbox',
                        'default'   => true,
                    ],
                    'plugins_page_data' => [
                        'title'     => __( 'Plugins Page Enhancements', 'dev-debug-tools' ),
                        'desc'      => __( 'Adds the additional tools set below to your Plugins Page.', 'dev-debug-tools' ),
                        'type'      => 'checkbox',
                        'default'   => true,
                    ],
                ]
            ],
            'plugins_page' => [
                'label' => __( 'Plugins Page — Must Enable Enhancements Above', 'dev-debug-tools' ),
                'fields' => [
                    'plugins_page_size' => [
                        'title'     => __( 'Size Column', 'dev-debug-tools' ),
                        'desc'      => __( 'Show the total folder size of each plugin.', 'dev-debug-tools' ),
                        'type'      => 'checkbox',
                        'default'   => true,
                    ],
                    'plugins_page_path' => [
                        'title'     => __( 'Path Column', 'dev-debug-tools' ),
                        'desc'      => __( 'Show the main plugin file path (e.g. dev-debug-tools/dev-debug-tools.php).', 'dev-debug-tools' ),
                        'type'      => 'checkbox',
                        'default'   => true,
                    ],
                    'plugins_page_last_modified' => [
                        'title'     => __( 'Last Updated Column & Warning', 'dev-debug-tools' ),
                        'desc'      => __( 'Show the last modified date and time of the plugin files, with a warning if older than one year.', 'dev-debug-tools' ),
                        'type'      => 'checkbox',
                        'default'   => true,
                    ],
                    'plugins_page_installed_by' => [
                        'title'     => __( 'Installed By Column', 'dev-debug-tools' ),
                        'desc'      => __( 'Show and edit who installed the plugin.', 'dev-debug-tools' ),
                        'type'      => 'checkbox',
                        'default'   => true,
                    ],
                    'plugins_page_notes' => [
                        'title'     => __( 'Plugin Notes', 'dev-debug-tools' ),
                        'desc'      => __( 'Add an “Edit Notes” action link to include custom notes for each plugin.', 'dev-debug-tools' ),
                        'type'      => 'checkbox',
                        'default'   => true,
                    ],
                ]
            ]
        ];
    } // End admin_areas_options()
    

    /**
     * Get the security settings options.
     *
     * Returns an array of security settings options for the plugin.
     *
     * @return array
     */
    public static function security_options() : array {
        return [
            'general' => [
                'label' => false,
                'fields' => [
                    'dev_access_only' => [
                        'title'     => __( 'Developer Access Only', 'dev-debug-tools' ),
                        'desc'      => __( 'Restrict access to the plugin for developers only.', 'dev-debug-tools' ),
                        'type'      => 'checkbox',
                        'default'   => false,
                    ],
                    'hide_plugin' => [
                        'title'     => __( 'Hide Plugin', 'dev-debug-tools' ),
                        'desc'      => __( 'Hides the plugin from the left admin menu entirely and disguises it on the plugins page with the alias you set below. Requires you to access plugin area directly (I recommend bookmarking the dashboard first).', 'dev-debug-tools' ),
                        'type'      => 'checkbox',
                        'default'   => false,
                    ],
                    'plugin_alias' => [
                        'title'     => __( 'Plugin Alias', 'dev-debug-tools' ),
                        'desc'      => __( 'Set an alias for the plugin to disguise it on the plugins page. Defaults to "Developer Notifications" if not set.', 'dev-debug-tools' ),
                        'type'      => 'text',
                        'default'   => __( 'Developer Notifications', 'dev-debug-tools' ),
                        'condition' => [
                            'field' => 'hide_plugin',
                            'value' => true,
                        ],
                    ],
                    'plugin_desc' => [
                        'title'     => __( 'Plugin Description', 'dev-debug-tools' ),
                        'desc'      => __( 'Set a description for the plugin to disguise it on the plugins page. Defaults to "Provides developer-focused system notifications." if not set.', 'dev-debug-tools' ),
                        'type'      => 'text',
                        'default'   => __( 'Provides developer-focused system notifications.', 'dev-debug-tools' ),
                        'condition' => [
                            'field' => 'hide_plugin',
                            'value' => true,
                        ],
                    ],
                    'plugin_author' => [
                        'title'     => __( 'Plugin Author', 'dev-debug-tools' ),
                        'desc'      => __( 'Set an author for the plugin to disguise it on the plugins page. Defaults to "WordPress Core Team" if not set.', 'dev-debug-tools' ),
                        'type'      => 'text',
                        'default'   => __( 'WordPress Core Team', 'dev-debug-tools' ),
                        'condition' => [
                            'field' => 'hide_plugin',
                            'value' => true,
                        ],
                    ],
                    'view_sensitive_info' => [
                        'title'     => __( 'View Sensitive Information', 'dev-debug-tools' ),
                        'desc'      => __( 'Displays redacted database login info, authentication keys and salts, IP addresses, etc.', 'dev-debug-tools' ),
                        'type'      => 'checkbox',
                        'default'   => false,
                    ],
                ]
            ],
            'pass_protect' => [
                'label' => __( 'Password Protection', 'dev-debug-tools' ),
                'fields' => [
                    'enable_pass' => [
                        'title'     => __( 'Enable Password Protection', 'dev-debug-tools' ),
                        'desc'      => __( 'Enable password protection for the Developer Debug Tools area and other specified admin pages set below.', 'dev-debug-tools' ),
                        'type'      => 'checkbox',
                        'default'   => false,
                    ],
                    'pass' => [
                        'title'     => __( 'Password', 'dev-debug-tools' ),
                        'desc'      => __( 'Set your password here.', 'dev-debug-tools' ),
                        'type'      => 'password',
                        'default'   => '',
                        'condition' => [
                            'field' => 'enable_pass',
                            'value' => true,
                        ],
                    ],
                    'pass_exp' => [
                        'title'     => __( 'Keep Logged In For X Minutes', 'dev-debug-tools' ),
                        'desc'      => __( 'Enter the number of minutes until you have to re-enter your password.', 'dev-debug-tools' ),
                        'type'      => 'number',
                        'default'   => 5,
                        'condition' => [
                            'field' => 'enable_pass',
                            'value' => true,
                        ],
                    ],
                    'pass_attempts' => [
                        'title'     => __( 'Attempts', 'dev-debug-tools' ),
                        'desc'      => __( 'Enter the number of attempts allowed before the user is locked out.', 'dev-debug-tools' ),
                        'type'      => 'number',
                        'default'   => 4,
                        'condition' => [
                            'field' => 'enable_pass',
                            'value' => true,
                        ],
                    ],
                    'pass_lockout' => [
                        'title'     => __( 'Lockout Duration (Minutes)', 'dev-debug-tools' ),
                        'desc'      => __( 'Enter the number of minutes the user will be locked out after exceeding the maximum attempts.', 'dev-debug-tools' ),
                        'type'      => 'number',
                        'default'   => 10,
                        'condition' => [
                            'field' => 'enable_pass',
                            'value' => true,
                        ],
                    ],
                    'secure_pages' => [
                        'title'     => __( 'Admin Pages to Secure with Password', 'dev-debug-tools' ),
                        'desc'      => __( 'Add additional admin pages not affiliated with Developer Debug Tools to secure.', 'dev-debug-tools' ),
                        'type'      => 'url_plus',
                        'default'   => [ admin_url( 'options.php' ) ],
                        'condition' => [
                            'field' => 'enable_pass',
                            'value' => true,
                        ],
                    ],
                ]
            ],
            'reset' => [
                'label' => __( 'Plugin Data', 'dev-debug-tools' ),
                'fields' => [
                    'export_settings' => [
                        'title'  => __( "Export All Plugin Data", 'dev-debug-tools' ),
                        'desc'   => __( "This action will download the current plugin settings as a JSON file to keep as a backup or use on a different site.", 'dev-debug-tools' ),
                        'type'   => 'download',
                        'label'  => __( 'Download JSON File', 'dev-debug-tools' ),
                        'ignore' => true
                    ],
                    'import_settings' => [
                        'title'     => __( 'Import Plugin Data', 'dev-debug-tools' ),
                        'desc'      => __( 'Use this form to import plugin settings using a JSON file. Use the JSON schema provided in the download above. You may modify the JSON file to only import specific settings. Note that existing settings will be overwritten.', 'dev-debug-tools' ),
                        'type'      => 'upload',
                        'filetypes' => [ 'json' ],
                        'ignore'    => true
                    ],
                    'remove_data_on_uninstall' => [
                        'title'   => __( 'Remove All Plugin Data on Uninstall', 'dev-debug-tools' ),
                        'desc'    => __( 'When you uninstall the plugin, all settings and data will be permanently deleted. This action cannot be undone.', 'dev-debug-tools' ),
                        'type'    => 'checkbox',
                        'default' => false,
                    ],
                    'reset_plugin_data_now' => [
                        'title'  => __( "Reset All Plugin Data Now", 'dev-debug-tools' ),
                        'desc'   => __( "This action will clear all plugin data for the current site to start fresh with default settings. It will also remove old option keys no longer in use from previous versions. This action cannot be undone.", 'dev-debug-tools' ),
                        'type'   => 'button',
                        'label'  => __( 'Reset All Plugin Data', 'dev-debug-tools' ),
                        'ignore' => true
                    ],
                ]
            ],
        ];
    } // End security_options()


    /**
     * Nonce for saving settings
     *
     * @var string
     */
    private $nonce = 'ddtt_save_settings_nonce';
    private static $download_nonce = 'ddtt_download_settings_nonce';
    private $reset_nonce = 'ddtt_reset_all_plugin_data_nonce';


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?Settings $instance = null;


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
        add_action( 'ddtt_header_notices', [ $this, 'render_header_notices' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_ddtt_user_select', [ $this, 'ajax_user_select' ] );
        add_action( 'wp_ajax_nopriv_ddtt_user_select', '__return_false' );
        add_action( 'wp_ajax_ddtt_verify_settings_path', [ $this, 'ajax_verify_settings_path' ] );
        add_action( 'wp_ajax_nopriv_ddtt_verify_settings_path', '__return_false' );
        add_action( 'wp_ajax_ddtt_save_settings', [ $this, 'ajax_save_settings' ] );
        add_action( 'wp_ajax_nopriv_ddtt_save_settings', '__return_false' );
        add_action( 'wp_ajax_ddtt_reset_all_plugin_data', [ $this, 'ajax_reset_all_plugin_data' ] );
        add_action( 'wp_ajax_nopriv_ddtt_reset_all_plugin_data', '__return_false' );
        $this->handle_downloads();
        add_action( 'wp_ajax_ddtt_settings_import', [ $this, 'ajax_settings_import' ] );
        add_action( 'wp_ajax_nopriv_ddtt_settings_import', '__return_false' );
    } // End __construct()
    

    /**
     * Render header notices
     *
     * This method is called to render notices in the header.
     * It checks for deleted options and displays a notice if any were deleted.
     */
    public function render_header_notices() {
        if ( AdminMenu::get_current_page_slug() !== 'dev-debug-settings' ) {
            return;
        }

        if ( $import_data = get_transient( 'ddtt_settings_imported_successfully' ) ) {
            Helpers::render_notice( sprintf( __( '%1$s settings imported successfully.', 'dev-debug-tools' ), esc_attr( $import_data[ 'count' ] ) ), 'success' );
        }
    } // End render_header_notices()


    /**
     * Render a developers field.
     *
     * @param string $key The key of the option.
     * @param array $args The arguments for the field.
     */
    public static function render_field_devs( $key, $args ) {
        $value = get_option( $key, $args[ 'default' ] );

        // If old format (comma-separated emails), convert to array of user IDs
        if ( $dev_emails = sanitize_text_field( get_option( 'ddtt_dev_email' ) ) ) {
            $values = [];
            $emails = array_filter( array_map( 'trim', explode( ',', $dev_emails ) ) );
            foreach ( $emails as $email ) {
                if ( is_email( $email ) ) {
                    $user = get_user_by( 'email', $email );
                    if ( $user ) {
                        $values[] = [
                            'id'   => $user->ID,
                            'text' => $user->display_name . ' (' . $user->user_email . ')'
                        ];
                    }
                }
            }

        // Get the user details for the selected IDs
        } elseif ( ! empty( $value ) && is_array( $value ) ) {
            $values = [];
            foreach ( $value as $id ) {
                $user = get_user_by( 'ID', $id );
                if ( $user ) {
                    $values[] = [
                        'id'   => $user->ID,
                        'text' => $user->display_name . ' (' . $user->user_email . ')'
                    ];
                }
            }
        }
        
        // If no values, then the default admin_email isn't a valid user, so let's add all admins
        if ( empty( $values ) ) {
            $values = [];
            $admins = get_users( [ 'role' => 'administrator', 'fields' => [ 'ID', 'display_name', 'user_email' ] ] );
            foreach ( $admins as $admin ) {
                $values[] = [
                    'id'   => $admin->ID,
                    'text' => $admin->display_name . ' (' . $admin->user_email . ')'
                ];
            }
        }

        $html = isset( $args[ 'html' ] ) ? Helpers::backticks_to_code( $args[ 'html' ] ) : '';

        $chips_html = '';
        foreach ( $values as $user ) {
            $chips_html .= sprintf(
                '<div class="ddtt-user-chip"><span class="ddtt-user-name">%1$s</span><span class="ddtt-remove-user">&times;</span></div>',
                esc_html( $user[ 'text' ] )
            );
        }

        printf(
            '<input type="hidden" id="%1$s" name="%1$s" value=\'%2$s\' /> 
            <div class="ddtt-users-field" data-field="%1$s">
                <div class="ddtt-users-selected">%3$s</div>
                <input type="text" class="ddtt-users-input" placeholder="%4$s" />
            </div> %5$s',
            esc_attr( $key ),
            esc_attr( wp_json_encode( $values ) ),
            wp_kses_post( $chips_html ),
            esc_attr( $args[ 'placeholder' ] ?? __( 'Start typing a user\'s name...', 'dev-debug-tools' ) ),
            wp_kses_post( $html )
        );
    } // End render_field_devs()


    /**
     * Render a text field.
     *
     * @param string $key The key of the option.
     * @param array $args The arguments for the field.
     */
    public static function render_field_text( $key, $args ) {
        $value = sanitize_text_field( get_option( $key, $args[ 'default' ] ) );
        $pattern = isset( $args[ 'pattern' ] ) ? ' pattern="' . esc_attr( $args[ 'pattern' ] ) . '"' : '';
        $placeholder = isset( $args[ 'placeholder' ] ) ? ' placeholder="' . esc_attr( $args[ 'placeholder' ] ) . '"' : '';
        printf(
            '<input type="text" id="%1$s" name="%1$s" value="%2$s" class="regular-text"%3$s%4$s/> %5$s',
            esc_attr( $key ),
            esc_attr( $value ),
            esc_attr( $pattern ),
            esc_attr( $placeholder ),
            wp_kses_post( isset( $args[ 'html' ] ) ? $args[ 'html' ] : '' )
        );
    } // End render_field_text()


    /**
     * Render a number field.
     *
     * @param string $key The key of the option.
     * @param array $args The arguments for the field.
     */
    public static function render_field_number( $key, $args ) {
        $value = intval( get_option( $key, $args[ 'default' ] ) );
        $placeholder = isset( $args[ 'placeholder' ] ) ? ' placeholder="' . esc_attr( $args[ 'placeholder' ] ) . '"' : '';
        printf(
            '<input type="number" id="%1$s" name="%1$s" value="%2$s" class="small-text"%3$s /> %4$s',
            esc_attr( $key ),
            esc_attr( $value ),
            esc_attr( $placeholder ),
            wp_kses_post( isset( $args[ 'html' ] ) ? $args[ 'html' ] : '' )
        );
    } // End render_field_number()


    /**
     * Render a password field.
     *
     * @param string $key The key of the option.
     * @param array $args The arguments for the field.
     */
    public static function render_field_password( $key, $args ) {
        printf(
            '<div class="ddtt-password-field-wrap">
                <input type="password" id="%1$s" name="%1$s" value="" class="regular-text ddtt-password-input" />
                <button type="button" class="ddtt-button ddtt-toggle-password" aria-label="Toggle Password Visibility">&#128065;</button>
                %2$s
            </div>',
            esc_attr( $key ),
            wp_kses_post( isset( $args[ 'html' ] ) ? $args[ 'html' ] : '' )
        );
    } // End render_field_password()


    /**
     * Render a URL field.
     *
     * @param string $key The key of the option.
     * @param array $args The arguments for the field.
     */
    public static function render_field_url( $key, $args ) {
        $value = esc_url_raw( get_option( $key, $args[ 'default' ] ) );
        $placeholder = isset( $args[ 'placeholder' ] ) ? ' placeholder=' . esc_attr( $args[ 'placeholder' ] ) . '' : '';
        printf(
            '<input type="url" id="%1$s" name="%1$s" value="%2$s" class="regular-text"%3$s /> %4$s',
            esc_attr( $key ),
            esc_url( $value ),
            esc_attr( $placeholder ),
            wp_kses_post( isset( $args[ 'html' ] ) ? $args[ 'html' ] : '' )
        );
    } // End render_field_url()


    /**
     * Render a url field with ability to add extra fields.
     *
     * @param string $key The key of the option.
     * @param array $args The arguments for the field.
     */
    public static function render_field_url_plus( $key, $args ) {
        $values  = get_option( $key, $args[ 'default' ] );
        if ( ! is_array( $values ) ) {
            $values = $values ? [ $values ] : [ '' ];
        }

        echo '<div class="ddtt-url-plus-wrap">';

        foreach ( $values as $index => $url ) {
            $url = sanitize_text_field( $url );
            echo '<div class="ddtt-text-field-wrap">';
                printf(
                    '<input type="url" name="%1$s[]" value="%2$s" class="regular-url" />',
                    esc_attr( $key ),
                    esc_attr( $url )
                );
                echo ' <button type="button" class="ddtt-button ddtt-remove-url">–</button>';
            echo '</div>';
        }

        echo '<button type="button" class="ddtt-button ddtt-add-url" data-key="' . esc_attr( $key ) . '">' . esc_html__( 'Add Another', 'dev-debug-tools' ) . '</button>';
        echo '</div>';
    } // End render_field_url_plus()


    /**
     * Sanitize a URL plus field value.
     *
     * @param string|array $value The value to sanitize.
     * @return string|array Sanitized value.
     */
    public static function sanitize_url_plus( $value ) {
        if ( ! is_array( $value ) ) {
            $value = [ $value ];
        }
        return array_filter( array_map( 'sanitize_url', $value ) );
    } // End sanitize_url_plus()


    /**
     * Render a textarea field.
     *
     * @param string $key The key of the option.
     * @param array $args The arguments for the field.
     */
    public static function render_field_textarea( $key, $args ) {
        $value = sanitize_textarea_field( get_option( $key, $args[ 'default' ] ) );
        $html = isset( $args[ 'html' ] ) ? Helpers::backticks_to_code( $args[ 'html' ] ) : '';
        printf(
            '<textarea id="%1$s" name="%1$s" rows="5" class="large-text">%2$s</textarea> %3$s',
            esc_attr( $key ),
            esc_textarea( $value ),
            wp_kses_post( $html )
        );
    } // End render_field_textarea()


    /**
     * Render a checkbox field.
     *
     * @param string $key The key of the option.
     * @param mixed $value The value of the option.
     */
    public static function render_field_checkbox( $key, $args ) {
        $value = self::sanitize_checkbox( get_option( $key, $args[ 'default' ] ) );
		printf(
			'<div class="ddtt-toggle-wrapper">
                <input type="checkbox" id="%1$s" name="%1$s"%2$s value="1" />
                <label class="ddtt-toggle-label" for="%1$s" aria-label="Toggle"></label>
            </div>',
			esc_attr( $key ),
			checked( $value, 1, false ),
			wp_kses_post( isset( $args[ 'html' ] ) ? $args[ 'html' ] : '' )
		);
    } // End render_field_checkbox()


    /**
     * Sanitize checkbox
     *
     * @param int $value
     * @return boolean
     */
    public static function sanitize_checkbox( $value ) {
        return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
    } // End sanitize_checkbox()


    /**
     * Render a checkboxes field as toggle switches.
     *
     * @param string $key The key of the option.
     * @param array  $args The arguments for the field.
     */
    public static function render_field_checkboxes( $key, $args ) {
        $value = get_option( $key, [] );
        $value = self::sanitize_checkboxes( $value );
        $defaults = $args[ 'default' ] ?? [];

        echo '<div class="ddtt-toggle-group">';

            foreach ( $args[ 'choices' ] as $choice_key => $choice_label ) {
                if ( is_int( $choice_key ) ) {
                    $choice_key = $choice_label;
                }

                $is_checked = in_array( $choice_key, $value ) || ( empty( $value ) && in_array( $choice_key, $defaults ) );

                printf(
                    '<div class="ddtt-toggle-wrapper">
                        <label class="ddtt-toggle-combined" for="%1$s_%2$s">
                            <div class="ddtt-toggle-input-label">
                                <input type="checkbox" id="%1$s_%2$s" name="%1$s[]" value="%2$s"%3$s />
                                <span class="ddtt-toggle-label" aria-hidden="true"></span>
                            </div>
                            <span class="ddtt-toggle-description">%4$s</span>
                        </label>
                    </div>',
                    esc_attr( $key ),
                    esc_attr( $choice_key ),
                    $is_checked ? ' checked' : '',
                    esc_html( $choice_label )
                );
            }

        echo '</div>';
    } // End render_field_checkboxes()


    /**
     * Sanitize multiple checkboxes
     *
     * @param mixed $values
     * @return array
     */
    public static function sanitize_checkboxes( $values ) {
        if ( ! is_array( $values ) ) {
            return [];
        }

        $sanitized = [];

        foreach ( $values as $value ) {
            $sanitized[] = sanitize_text_field( $value );
        }

        return $sanitized;
    } // End sanitize_checkboxes()


    /**
     * Render a number field.
     *
     * @param string $key The key of the option.
     * @param array $args The arguments for the field.
     */
    public static function render_field_select( $key, $args ) {
        $value = get_option( $key, $args[ 'default' ] );
        printf(
            '<select id="%1$s" name="%1$s"%2$s>',
            esc_attr( $key ),
            isset( $args[ 'html' ] ) && $args[ 'html' ] ? ' ' . esc_attr( $args[ 'html' ] ) : ''
        );

        foreach ( $args[ 'choices' ] as $choice_key => $choice_label ) {
            if ( is_int( $choice_key ) ) {
                $choice_key = $choice_label;
            }
            printf(
                '<option value="%1$s"%2$s>%3$s</option>',
                esc_attr( $choice_key ),
                selected( $value, $choice_key, false ),
                esc_html( $choice_label )
            );
        }
        echo '</select>';
    } // End render_field_select()


    /**
     * Render a path field with verification button.
     *
     * @param string $key The key of the option.
     * @param array $args The arguments for the field.
     */
    public static function render_field_path( $key, $args ) {
        $value   = sanitize_text_field( get_option( $key, $args[ 'default' ] ) );
        $pattern = isset( $args[ 'pattern' ] ) ? ' pattern="' . esc_attr( $args[ 'pattern' ] ) . '"' : '';

        // Check if the path exists
        if ( $value ) {
            $path_exists = Helpers::path_exists( $value );
            $class = $path_exists ? ' ddtt-status-verified' : ' ddtt-status-failed';
            $label = $path_exists ? __( 'Verified', 'dev-debug-tools' ) : __( 'Failed', 'dev-debug-tools' );
        } else {
            $class = '';
            $label = __( 'Verify', 'dev-debug-tools' );
        }

        echo '<div class="ddtt-text-field-wrap has-verify">';

            printf(
                '<input type="text" id="%1$s" name="%1$s" value="%2$s" class="regular-text"%3$s />',
                esc_attr( $key ),
                esc_attr( $value ),
                esc_attr( $pattern )
            );

            printf(
                ' <button type="button" class="ddtt-button ddtt-verify-path%1$s" data-key="%2$s">%3$s</button>',
                esc_attr( $class ),
                esc_attr( $key ),
                esc_html( $label )
            );

            if ( ! empty( $args[ 'html' ] ) ) {
                echo ' ' . wp_kses_post( $args[ 'html' ] );
            }

        echo '</div>';
    } // End render_field_path()


    /**
     * Render a path field with verification button.
     *
     * @param string $key The key of the option.
     * @param array $args The arguments for the field.
     */
    public static function render_field_path_plus( $key, $args ) {
        $values  = get_option( $key, $args[ 'default' ] );
        if ( ! is_array( $values ) ) {
            $values = $values ? [ $values ] : [ '' ];
        }

        echo '<div class="ddtt-path-plus-wrap">';

        foreach ( $values as $index => $path ) {
            $value = sanitize_text_field( $path );

            // Check if the path exists
            if ( $value ) {
                if ( strpos( $value, ABSPATH ) !== 0 ) {
                    $path = wp_normalize_path( ABSPATH . ltrim( $value, '/' ) );
                } else {
                    $path = wp_normalize_path( $value );
                }
                $path_exists = file_exists( $path ) && is_readable( $path );
                $class = $path_exists ? ' ddtt-status-verified' : ' ddtt-status-failed';
                $label = $path_exists ? __( 'Verified', 'dev-debug-tools' ) : __( 'Failed', 'dev-debug-tools' );
            } else {
                $class = '';
                $label = __( 'Verify', 'dev-debug-tools' );
            }
            
            echo '<div class="ddtt-text-field-wrap has-verify">';
                printf(
                    '<input type="text" name="%1$s[]" value="%2$s" class="regular-text" />',
                    esc_attr( $key ),
                    esc_attr( $value )
                );
                printf(
                    ' <button type="button" class="ddtt-button ddtt-verify-path%1$s" data-key="%2$s">%3$s</button>',
                    esc_attr( $class ),
                    esc_attr( $key ),
                    esc_html( $label )
                );
                echo ' <button type="button" class="ddtt-button ddtt-remove-path">–</button>';
            echo '</div>';
        }

        echo '<button type="button" class="ddtt-button ddtt-add-path" data-key="' . esc_attr( $key ) . '">' . esc_html__( 'Add Another', 'dev-debug-tools' ) . '</button>';
        echo '</div>';
    } // End render_field_path_plus()


    /**
     * Sanitize a path plus field value.
     *
     * @param string|array $value The value to sanitize.
     * @return string|array Sanitized value.
     */
    public static function sanitize_path_plus( $value ) {
        if ( ! is_array( $value ) ) {
            $value = [ $value ];
        }
        return array_filter( array_map( 'sanitize_text_field', $value ) );
    } // End sanitize_path_plus()


    /**
     * Enqueue admin assets for settings page.
     *
     * @param string $hook The current admin page.
     */
    public static function render_field_html( $key, $args ) {
        echo isset( $args[ 'html' ] ) ? '<div class="ddtt-html-field">' . wp_kses_post( $args[ 'html' ] ) . '</div>' : '';
    } // End render_field_html()


    /**
     * Render a button field.
     *
     * @param string $key The key of the option.
     * @param array $args The arguments for the field.
     */
    public static function render_field_button( $key, $args ) {
        $onclick = isset( $args[ 'onclick' ] ) ? ' onclick="' . esc_attr( $args[ 'onclick' ] ) . '"' : '';
        printf(
            '<button id="%1$s" class="ddtt-button"%2$s>%3$s</button> %4$s',
            esc_attr( $key ),
            esc_attr( $onclick ),
            esc_html( $args[ 'label' ] ),
            wp_kses_post( isset( $args[ 'html' ] ) ? $args[ 'html' ] : '' )
        );
    } // End render_field_button()


    /**
     * Render a search field for options pages.
     *
     * @param string $key The key of the option.
     * @param array $args The arguments for the field.
     */
    public static function render_field_search( $key, $args ) {
        $name = str_replace( 'ddtt_', '', $key );

        $value = isset( $_GET[ $name ] ) && $_GET[ $name ] !== '' ? sanitize_text_field( wp_unslash( $_GET[ $name ] ) ) : ( $args[ 'default' ] ?? '' ); // phpcs:ignore 

        $scroll_to = isset( $args[ 'scroll_to' ] ) ? '#' . sanitize_key( $args[ 'scroll_to' ] ) : '';        
        ?>
        <form method="get" action="<?php echo esc_url( $scroll_to ); ?>">
            <input type="hidden" name="page" value="<?php echo esc_attr( AdminMenu::get_current_page_slug() ); ?>">
            <input type="hidden" name="tool" value="<?php echo esc_attr( AdminMenu::current_tool_slug() ); ?>">
            <?php if ( isset( $_GET[ 's' ] ) ) : // phpcs:ignore
                // phpcs:ignore ?>
                <input type="hidden" name="s" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET[ 's' ] ) ) ); ?>">
            <?php endif; ?>
            <input type="text" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" required>
            <?php wp_nonce_field( $args[ 'nonce' ], '_wpnonce', false ); ?>
            <input type="submit" value="<?php esc_attr_e( 'Search', 'dev-debug-tools' ); ?>" id="search-button" class="ddtt-button" />
            <?php if ( ! empty( $value ) ) : ?>
            <?php
            // Add reset=true and nonce to the reset button URL
            $reset_url = add_query_arg(
                [
                    'reset'   => 'true',
                    '_wpnonce'=> wp_create_nonce( $args[ 'nonce' ] )
                ],
                remove_query_arg( [ $name, '_wpnonce' ] )
            );
            ?>
            <a href="<?php echo esc_url( $reset_url ); ?>" class="ddtt-button ddtt-reset-button"><?php esc_html_e( 'Reset', 'dev-debug-tools' ); ?></a>
        <?php endif; ?>
        </form>
        <?php
    } // End render_field_search()


    /**
     * Render a download field.
     *
     * @param string $key The key of the option.
     * @param array $args The arguments for the field.
     */
    public static function render_field_download( $key, $args ) {
        // Start form.
        printf(
            '<form id="%1$s_form" method="post">',
            esc_attr( $key )
        );

        // Add nonce field.
        wp_nonce_field( 'ddtt_setting_download_' . $key, self::$download_nonce );

        // Hidden fields.
        printf(
            '<input type="hidden" name="ddtt_download_setting" value="%s">',
            esc_attr( $key )
        );

        // Submit button.
        printf(
            '<button id="%1$s" type="submit" class="ddtt-button">%2$s</button> %3$s',
            esc_attr( $key ),
            esc_html( $args[ 'label' ] ),
            wp_kses_post( isset( $args[ 'html' ] ) ? $args[ 'html' ] : '' )
        );

        // End form.
        echo '</form>';
    } // End render_field_download()
    

    /**
     * Render an upload field with custom label/button styling.
     *
     * @param string $key The key of the option.
     * @param array $args The arguments for the field.
     */
    public static function render_field_upload( $key, $args ) {
        $filetypes = isset( $args[ 'filetypes' ] ) && is_array( $args[ 'filetypes' ] )
            ? implode( ',', array_map( 'strtolower', $args[ 'filetypes' ] ) )
            : 'json';

        $input_id = esc_attr( $key );
        $label_text = ! empty( $args[ 'label' ] ) ? esc_html( $args[ 'label' ] ) : __( 'Choose File', 'dev-debug-tools' );

        echo '<div class="ddtt-upload-wrap">
            <div class="ddtt-upload-buttons-wrap">';
                printf(
                    '<label for="%1$s" class="ddtt-upload-label">
                        <span class="ddtt-upload-btn ddtt-button">%2$s</span>
                    </label>
                    <input type="file" id="%1$s" name="%1$s" accept=".%3$s" class="ddtt-upload-input" style="display:none;"%4$s />',
                    esc_attr( $input_id ),
                    esc_html( $label_text ),
                    esc_attr( $filetypes ),
                    esc_attr( isset( $args[ 'ignore' ] ) && $args[ 'ignore' ] ? ' data-ignore=yes' : '' )
                );
                
                // Upload button (disabled by default)
                echo '<button type="button" id="' . esc_attr( $input_id ) . '_upload" class="ddtt-button ddtt-upload-btn-action" disabled>' . esc_html__( 'Upload', 'dev-debug-tools' ) . '</button>
            </div>';

            // Filename display
            echo '<div id="' . esc_attr( $input_id ) . '_filename" class="ddtt-upload-filename"></div>';

            if ( ! empty( $args[ 'desc' ] ) ) {
                printf(
                    '<p class="description">%s</p>',
                    wp_kses_post( $args[ 'desc' ] )
                );
            }

        echo '</div>';
    } // End render_field_upload()


    /**
     * Render settings section with subsections and fields.
     *
     * @param array $options_subsections The subsections and their fields to render.
     */
    public static function render_settings_section( $option_subsections, $full_section = true ) {
        ?>
        <section id="ddtt-settings-section" class="<?php echo esc_attr( $full_section ? 'ddtt-section-content' : '' ); ?>">
            <div class="ddtt-settings-content">
                <?php foreach ( $option_subsections as $subsection ) { 
                    if ( $subsection[ 'label' ] ) {
                        ?><h3 class="ddtt-settings-subsection-title"><?php echo esc_html( $subsection[ 'label' ] ); ?></h3><?php
                    }
                    if ( isset( $subsection[ 'desc' ] ) && ! empty( $subsection[ 'desc' ] ) ) {
                        ?><p class="ddtt-settings-subsection-desc"><?php echo wp_kses_post( $subsection[ 'desc' ] ); ?></p><?php
                    }
                    foreach ( $subsection[ 'fields' ] as $key => $option ) {
                        $callback = 'render_field_' . $option[ 'type' ];
                        if ( ! is_callable( [ self::class, $callback ] ) ) {
                            continue;
                        }

                        $hidden = '';
                        $condition_data = '';
                        if ( isset( $option[ 'condition' ] ) && $option[ 'condition' ] ) {
                            $condition = $option[ 'condition' ];
                            $condition_field = 'ddtt_' . $condition[ 'field' ];
                            $condition_default = isset( $subsection[ 'fields' ][ $condition[ 'field' ] ][ 'default' ] ) ? $subsection[ 'fields' ][ $condition[ 'field' ] ][ 'default' ] : '';
                            $condition_value = get_option( $condition_field, $condition_default ) ?? '';

                            if ( (bool) $condition[ 'value' ] !== (bool) $condition_value ) {
                                $hidden = ' ddtt-hidden';
                            }
                            $condition_data = ' data-condition="' . esc_attr( $condition[ 'field' ] ) . '" data-value="' . esc_attr( $condition[ 'value' ] ) . '"';
                        }
                        ?>
                        <div id="<?php echo esc_attr( $key ); ?>" class="ddtt-settings-row type-<?php echo esc_attr( $option[ 'type' ] ); ?><?php echo esc_attr( $hidden ); ?>"<?php echo esc_attr( $condition_data ); ?>>
                            <div class="ddtt-settings-label">
                                <label class="ddtt-label" for="<?php echo esc_attr( $key ); ?>"><?php echo wp_kses_post( $option[ 'title' ] ); ?></label>
                                <?php if ( ! empty( $option[ 'desc' ] ) ) { ?>
                                    <p class="ddtt-desc"><?php echo wp_kses_post( $option[ 'desc' ] ); ?></p>
                                <?php } ?>
                            </div>
                            <div class="ddtt-settings-field">
                                <?php
                                $args = [];
                                foreach ( $option as $arg_key => $arg_value ) {
                                    if ( in_array( $arg_key, [ 'type', 'title', 'desc' ] ) ) {
                                        continue;
                                    }
                                    $args[ $arg_key ] = $arg_value;
                                }
                                self::$callback( 'ddtt_' . $key, $args );
                                ?>
                            </div>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>
        </section>
        <?php
    } // End render_settings_section()


    /**
     * Enqueue assets for the resources page
     *
     * @param string $hook The current admin page hook.
     * @return void
     */
    public function enqueue_assets( $hook ) : void {
        if ( ! AdminMenu::is_current_screen( $hook, 'settings' ) ) {
            return;
        }

        wp_localize_script( 'ddtt-page-settings', 'ddtt_settings', [
            'nonce'      => wp_create_nonce( $this->nonce ),
            'resetNonce' => wp_create_nonce( $this->reset_nonce ),
            'i18n'       => [
                'verifyButton'  => __( 'Verify', 'dev-debug-tools' ),
                'verifying'     => __( 'Verifying...', 'dev-debug-tools' ),
                'verified'      => __( 'Verified', 'dev-debug-tools' ),
                'failed'        => __( 'Failed', 'dev-debug-tools' ),
                'verifySuccess' => __( 'Good news! File exists at this path.', 'dev-debug-tools' ),
                'verifyFail'    => __( 'Uh oh! File does not exist at this path.', 'dev-debug-tools' ),
                'verifyError'   => __( 'Error verifying path.', 'dev-debug-tools' ),
                'saving'        => __( 'Saving...', 'dev-debug-tools' ),
                'saved'         => __( 'Saved', 'dev-debug-tools' ),
                'saveSuccess'   => __( 'Saved successfully!', 'dev-debug-tools' ),
                'saveError'     => __( 'Error saving settings.', 'dev-debug-tools' ),
                'saveButton'    => __( 'Save Settings', 'dev-debug-tools' ),
                'resetConfirm'  => __( 'Are you sure you want to reset all plugin settings and saved data? This action cannot be undone.', 'dev-debug-tools' ),
                'resetting'     => __( 'Removing all plugin data now', 'dev-debug-tools' ),
                'resetSuccess'  => __( 'All done! Reloading', 'dev-debug-tools' ),
                'startTyping'   => __( 'Start typing a user\'s name.', 'dev-debug-tools' ),
                'importing'     => __( 'Importing', 'dev-debug-tools' ),
            ]
        ] );
    } // End enqueue_assets()


    /**
     * AJAX callback for Select2 user search
     */
    public function ajax_user_select() {
        check_ajax_referer( $this->nonce, 'nonce' );

        $term    = isset( $_GET[ 'q' ] ) ? sanitize_text_field( wp_unslash( $_GET[ 'q' ] ) ) : '';
        $exclude = isset( $_GET[ 'exclude' ] ) ? array_map( 'intval', (array) $_GET[ 'exclude' ] ) : [];

        $args = [
            'role'    => 'administrator',
            'number'  => 10,
            'orderby' => 'display_name',
            'order'   => 'ASC',
            'search_columns' => [ 'user_login', 'user_email', 'display_name' ],
        ];

        if ( $term !== '' ) {
            if ( is_numeric( $term ) ) {
                $args[ 'include' ] = [ intval( $term ) ];
            } else {
                $args[ 'search' ] = '*' . esc_attr( $term ) . '*';
            }
        }

        $users   = get_users( $args );
        $results = [];

        foreach ( $users as $user ) {
            if ( in_array( $user->ID, $exclude, true ) ) {
                continue;
            }

            $results[] = [
                'id'   => $user->ID,
                'text' => $user->display_name . ' (' . $user->user_email . ')',
            ];
        }

        wp_send_json( $results );
    } // End ajax_user_select()


    /**
     * AJAX handler to verify settings path.
     *
     * Checks if the provided path exists and returns a JSON response.
     */
    public function ajax_verify_settings_path() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized', 'dev-debug-tools' ) ] );
        }

        $path = isset( $_POST[ 'path' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'path' ] ) ) : '';

        if ( ! $path ) {
            wp_send_json_error( [ 'message' => __( 'No path provided', 'dev-debug-tools' ) ] );
        }

        // Convert relative path to absolute if needed
        if ( strpos( $path, ABSPATH ) !== 0 ) {
            $path = wp_normalize_path( ABSPATH . ltrim( $path, '/' ) );
        } else {
            $path = wp_normalize_path( $path );
        }

        $exists = file_exists( $path );

        wp_send_json_success( [
            'exists' => $exists,
            'path'   => $path,
        ] );
    } // End ajax_verify_settings_path()


    /**
     * AJAX handler to save settings.
     */
    public function ajax_save_settings() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'dev-debug-tools' ) ], 403 );
        }

        $subsection = sanitize_text_field( wp_unslash( $_POST[ 'subsection' ] ?? '' ) );
        $options    = $_POST[ 'options' ] ?? []; // phpcs:ignore

        if ( ! $subsection || ! is_array( $options ) ) {
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

        $method = $subsection . '_options';
        if ( ! method_exists( $this, $method ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid subsection.', 'dev-debug-tools' ) ], 400 );
        }

        $settings = $this->$method();
        $fields = [];
        foreach ( $settings as $section ) {
            if ( isset( $section[ 'fields' ] ) && is_array( $section[ 'fields' ] ) ) {
                foreach ( $section[ 'fields' ] as $key => $args ) {
                    $fields[ $key ] = $args[ 'type' ];
                }
            }
        }

        // Include additional fields for specific subsections
        // $addt_settings = [];
        // if ( $subsection == 'admin_areas' ) {
        //     $addt_settings = self::plugin_page_options();
        // }

        // if ( ! empty( $addt_settings ) ) {
        //     foreach ( $addt_settings as $section ) {
        //         if ( isset( $section[ 'fields' ] ) && is_array( $section[ 'fields' ] ) ) {
        //             foreach ( $section[ 'fields' ] as $key => $args ) {
        //                 $fields[ $key ] = $args[ 'type' ];
        //             }
        //         }
        //     }
        // }

        $updated = [];
        foreach ( $fields as $key => $type ) {
            $option_key = 'ddtt_' . $key; // Prefix the option key

            // Get the value from the POST data, default to null if it doesn't exist
            $value = $options[ $option_key ] ?? null;

            // The switch statement now handles all cases, including null (not set) values
            switch ( $type ) {
                case 'checkbox':
                    $value = self::sanitize_checkbox( $value );
                    break;

                case 'checkboxes':
                    $value = self::sanitize_checkboxes( $value );
                    break;

                case 'number':
                    $value = intval( $value );
                    break;

                case 'url':
                    $value = esc_url_raw( $value );
                    break;

                case 'url_plus':
                    $value = self::sanitize_url_plus( $value );
                    break;

                case 'path_plus':
                    $value = self::sanitize_path_plus( $value );
                    break;

                case 'password':
                    $value = sanitize_text_field( $value );
                    // Only hash if not empty
                    if ( ! empty( $value ) ) {
                        $value = wp_hash_password( $value );
                    } else {
                        // Preserve the existing password if left blank
                        $existing = get_option( $option_key );
                        if ( $existing && $existing !== '__notset' ) {
                            $value = $existing;
                        }
                    }
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

                case 'path':
                case 'text':
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
        }

        wp_send_json_success( [ 'updated' => $updated ] );
    } // End ajax_save_settings()


    /**
     * AJAX handler to reset all plugin data.
     */
    public function ajax_reset_all_plugin_data() {
        check_ajax_referer( $this->reset_nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) && ! Helpers::is_dev() ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'dev-debug-tools' ) ], 403 );
        }

        Cleanup::run();
        wp_send_json_success( [ 'message' => __( 'All plugin data has been reset.', 'dev-debug-tools' ) ] );
    } // End ajax_reset_all_plugin_data()


    /**
     * Handle download buttons
     */
    public function handle_downloads() {
        if ( ! is_admin() || ! current_user_can( 'manage_options' ) || ! Helpers::is_dev() ) {
            return;
        }

        if ( ! isset( $_POST[ 'ddtt_download_setting' ] ) ) {
            return;
        }

        $action = sanitize_text_field( wp_unslash( $_POST[ 'ddtt_download_setting' ] ) );

        if ( $action === 'ddtt_export_settings' ) {

            check_admin_referer( 'ddtt_setting_download_' . $action, self::$download_nonce );

            // Get all plugin settings
            $all_options = Cleanup::get_all_options( false );

            // Keys we do NOT want to export
            $exclude_keys = [
                'developers',
                'dev_access_only',
                'hide_plugin',
                'enable_pass',
                'debug_log_path',
                'error_log_path',
                'admin_error_log_path',
                'plugins',
                'plugin_sizes',
                'plugin_installers',
                'last_selected_table',
                'last_defined_constant',
                'last_global_variable',
                'last_selected_post_type',
                'last_selected_taxonomy',
                'last_viewed_version',
                'metadata_last_lookups',
                'htaccess_last_modified',
                'wpconfig_last_modified',
                'total_error_count',
                'admin_menu_items',
                'deleted_site_options',
                'test_mode',
                'reset_plugin_data_now',
                'enable_curl_timeout'
            ];

            $data = [
                'site_url' => get_site_url(),
            ];

            foreach ( $all_options as $group => $options ) {
                if ( ! is_array( $options ) ) {
                    continue;
                }

                foreach ( $options as $option ) {

                    if ( in_array( $option, $exclude_keys, true ) ) {
                        continue;
                    }

                    $option_name = 'ddtt_' . $option;

                    // Use a sentinel to detect truly missing options
                    $value = get_option( $option_name, '__missing__' );

                    // Only include if the option actually exists in the DB
                    if ( $value !== '__missing__' ) {
                        $data[ $group ][ $option ] = $value;
                    }
                }
            }

            // Encode and send
            $export_data = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
            $filename    = 'ddtt-settings-export-' . gmdate( 'Y-m-d' ) . '.json';

            // Force download headers
            header( 'Content-Description: File Transfer' );
            header( 'Content-Type: application/json; charset=utf-8' );
            header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
            header( 'Expires: 0' );
            header( 'Cache-Control: must-revalidate' );
            header( 'Pragma: public' );
            header( 'Content-Length: ' . strlen( $export_data ) );

            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- direct JSON output
            echo $export_data;
            exit;
        }
    } // End handle_downloads()
    

    /**
     * AJAX handler for settings import.
     *
     * @return void
     */
    public function ajax_settings_import() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) && ! Helpers::is_dev() ) {
            wp_send_json_error( 'unauthorized' );
        }

        $file_data = isset( $_POST[ 'jsonData' ] ) ? wp_unslash( $_POST[ 'jsonData' ] ) : null; // phpcs:ignore

        if ( ! $file_data ) {
            wp_send_json_error( 'No file data.' );
        }

        $import = json_decode( $file_data, true );
        if ( ! is_array( $import ) || empty( $import ) ) {
            wp_send_json_error( 'Invalid JSON data.' );
        }

        // Current site URL
        $current_site_url = get_site_url();

        // If export contains a 'site_url', use it to replace old URLs
        $old_site_url = isset( $import[ 'site_url' ] ) ? $import[ 'site_url' ] : $current_site_url;


        // Update the settings
        $successful_updates = 0;
        $options_updated = [];
        foreach ( $import as $group => $options ) {
            if ( ! is_array( $options ) ) {
                continue;
            }

            foreach ( $options as $option => $value ) {
                $option_key = 'ddtt_' . $option;

                // Replace old site URL with current site URL in string values
                if ( is_string( $value ) ) {
                    $value = str_replace( $old_site_url, $current_site_url, $value );
                }
                // Optionally, recursively replace in arrays
                elseif ( is_array( $value ) ) {
                    array_walk_recursive( $value, function( &$item ) use ( $old_site_url, $current_site_url ) {
                        if ( is_string( $item ) ) {
                            $item = str_replace( $old_site_url, $current_site_url, $item );
                        }
                    });
                }

                // Add or update the option
                if ( get_option( $option_key, '__notset' ) === '__notset' ) {
                    add_option( $option_key, $value );
                } else {
                    update_option( $option_key, $value );
                }

                $successful_updates++;
                $options_updated[] = $option_key;
            }
        }

        // Set transient to show admin notice
        set_transient( 'ddtt_settings_imported_successfully', [ 'count' => $successful_updates ], 30 );
        set_transient( 'ddtt_settings_imported', [ 'count' => $successful_updates, 'updated' => $options_updated ], 12 * HOUR_IN_SECONDS );

        wp_send_json_success();
    } // End ajax_settings_import()


    /**
     * Prevent cloning and unserializing
     */
    public function __clone() {}
    public function __wakeup() {}

}


Settings::instance();