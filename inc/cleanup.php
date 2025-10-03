<?php
/**
 * Cleanup
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Cleanup {

    /**
     * Run the cleanup process
     */
    public static function run() {
        self::delete_general_options();
        self::delete_logging_options();
        self::delete_config_file_options();
        self::delete_metadata_options();
        self::delete_heartbeat_options();
        self::delete_online_user_options();
        self::delete_admin_bar_options();
        self::delete_admin_area_options();
        self::delete_security_options();
        self::delete_discord_options();
        self::delete_page_specific_options();
        self::delete_old_options();
        self::clear_all_user_meta();
    } // End run()


    /**
     * Delete general options
     */
    private static function delete_general_options() {
        $keys = [
            'dev_email',
            'dev_timezone',
            'dev_timeformat',
            'view_sensitive_info',
            'remove_data_on_uninstall',
        ];

        foreach ( $keys as $key ) {
            self::delete_option( $key );
        }
    } // End delete_general_options()


    /**
     * Delete logging options
     */
    private static function delete_logging_options() {
        $keys = [
            // Debug Log
            'disable_error_counts',
            'wp_mail_failure',
            'fatal_discord_enable',
            'fatal_discord_webhook',

            // Paths
            'debug_log_path',
            'error_log_path',
            'admin_error_log_path',
            'log_files',

            // Activity Log
            'activity',
            'activity_updating_usermeta_skip_keys',
            'activity_updating_postmeta_skip_keys',
            'activity_updating_setting_skip_keys',
        ];

        foreach ( $keys as $key ) {
            self::delete_option( $key );
        }
    } // End delete_logging_options()


    /**
     * Delete config file options
     */
    private static function delete_config_file_options() {
        $keys = [
            'wpconfig_move_old_ddtt',
            'wpconfig_simplify_mysql_settings',
            'wpconfig_minimize_auth_comments',
            'wpconfig_improve_abs_path',
            'wpconfig_remove_double_line_spaces',
            'wpconfig_add_spaces_inside_parenthesis_and_brackets',
            'wpconfig_convert_multi_line_to_single_line',

            'htaccess_move_old_ddtt',
            'htaccess_move_all_code_at_top',
            'htaccess_minimize_begin_end_comments',
            'htaccess_remove_double_comment_hashes',
            'htaccess_remove_double_line_spaces',
            'htaccess_remove_spaces_at_top_and_bottom',
            'htaccess_add_line_breaks_between_blocks',
        ];

        foreach ( $keys as $key ) {
            self::delete_option( $key );
        }
    } // End delete_config_file_options()


    /**
     * Delete metadata options
     */
    private static function delete_metadata_options() {
        $keys = [
            'protected_meta_keys',
        ];

        foreach ( $keys as $key ) {
            self::delete_option( $key );
        }
    } // End delete_metadata_options()


    /**
     * Delete Heartbeat options
     */
    private static function delete_heartbeat_options() {
        $keys = [
            'enable_heartbeat_monitor',
            'disable_everywhere',
            'disable_admin',
            'disable_frontend',
        ];

        foreach ( $keys as $key ) {
            self::delete_option( $key );
        }
    } // End delete_heartbeat_options()


    /**
     * Delete Online Users options
     */
    private static function delete_online_user_options() {
        $keys = [
            'online_users',
            'online_users_last_seen',
            'online_users_heartbeat',
            'online_users_heartbeat_interval',
            'online_users_link',
            'online_users_roles',
            'online_users_heartbeat_roles',
            'online_users_priority_roles',
            'online_users_discord_enable',
            'online_users_discord_webhook',
        ];

        foreach ( $keys as $key ) {
            self::delete_option( $key );
        }
    } // End delete_online_user_options()


    /**
     * Delete Admin Bar options
     */
    private static function delete_admin_bar_options() {
        $keys = [
            'admin_bar_wp_logo',
            'admin_bar_logs',
            'admin_bar_resources',
            'admin_bar_user_id',
            'admin_bar_page_loaded',
            'admin_bar_condense',
            'admin_bar_add_links',
            'admin_bar_post_id',
            'admin_bar_shortcodes',
            'admin_bar_centering_tool',
            'admin_bar_gravity_form_finder',
        ];

        foreach ( $keys as $key ) {
            self::delete_option( $key );
        }
    } // End delete_admin_bar_options()


    /**
     * Delete Admin Area options
     */
    private static function delete_admin_area_options() {
        $keys = [
            'ql_user_id',
            'ql_post_id',
            'ql_comment_id',
            'ids_in_search',
            'plugins_page_data',
            'plugins_page_size',
            'plugins_page_path',
            'plugins_page_last_modified',
            'plugins_page_installed_by',
            'plugins_page_notes',
        ];

        foreach ( $keys as $key ) {
            self::delete_option( $key );
        }
    } // End delete_admin_area_options()


    /**
     * Delete Security options
     */
    private static function delete_security_options() {
        $keys = [
            'hide_plugin',
            'plugin_alias',
            'plugin_desc',
            'plugin_author',
            'enable_pass',
            'pass',
            'pass_exp',
            'pass_attempts',
            'pass_lockout',
            'secure_pages',
        ];

        foreach ( $keys as $key ) {
            self::delete_option( $key );
        }
    } // End delete_security_options()


    /**
     * Delete Discord options
     */
    private static function delete_discord_options() {
        $keys = [
            'discord_webhook_url',
            'discord_embed_title',
            'discord_title_url',
            'discord_message_body',
            'discord_embed_color',
            'discord_bot_name',
            'discord_bot_avatar_url',
            'discord_image_url',
            'discord_thumbnail_url',
        ];

        foreach ( $keys as $key ) {
            self::delete_option( $key );
        }
    } // End delete_discord_options()


    /**
     * Delete page specific options
     */
    private static function delete_page_specific_options() {
        $keys = [
            'developers', // class-welcome.php > ajax_save_settings()
            'dev_access_only', // class-welcome.php > ajax_save_settings()
            'default_mode', // class-welcome.php > settings()
            'last_selected_table', // class-db-tables.php > ajax_get_db_table()
            'last_defined_constant', // class-defines.php > ajax_get_defined_constant()
            'last_global_variable', // class-globals.php > ajax_get_global_variable()
            'plugins', // class-activity.php > update_installed_plugins_option()
            'total_error_count', // class-logs.php > cache_total_error_count()
            'log_viewer_customizations', // class-logs.php > ajax_get_log(),
            'metadata_last_lookups', // class-metadata.php > settings(),
            'metadata_viewer_customizations', // class-metadata.php > ajax_get_metadata()
            'last_selected_post_type', // class-post-types.php > ajax_get_post_type()
            'deleted_site_options', // class-site-options.php > ajax_bulk_delete()
            'last_selected_taxonomy', // class-taxonomies.php > ajax_get_taxonomy()
            'tools', // class-tools.php > ajax_save_tools()
            'favorite_tools', // class-tools.php > ajax_favorite_tool()
            'resources', // class-resources.php > ajax_save_resources()
            'admin_menu_items', // class-admin-menu.php > maybe_store_admin_menu_options()
            'plugin_sizes', // class-plugins.php > get_plugin_size(),
            'plugin_installers', // class-plugins.php > maybe_record_installer()
            'plugin_notes', // class-plugins.php > ajax_save_plugin_note()
            'enable_curl_timeout', // class-helpers.php > start_timer()
            'last_viewed_version', // inc/hub/header.php > (whats new link)
            'test_mode', // menu.php > ajax_save_test_mode()
            'total_error_count', // class-logs.php > cache_total_error_count()
            'reset_plugin_data_now', // class-settings.php > ajax_reset_plugin_data()
        ];

        foreach ( $keys as $key ) {
            self::delete_option( $key );
        }
    } // End delete_page_specific_options()


    /**
     * Delete old options (no longer used)
     */
    private static function delete_old_options() {
        $keys = [
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
            'disable_activity_counts',
            'discord_ingore_devs',
            'discord_login',
            'discord_page_loads',
            'discord_transient',
            'error_constants',
            'error_enable',
            'error_uninstall',
            'htaccess_last_updated',
            'htaccess_og_replaced_date',
            'htaccess_snippets',
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
            'wpconfig_snippets',
        ];

        foreach ( $keys as $key ) {
            self::delete_option( $key );
        }
    } // End delete_old_options()


    /**
     * Clear all user meta related to the plugin
     */
    private static function clear_all_user_meta() {
        $offset = 0;
        $batch  = 500;

        $keys = [
            'last_online',
            'centering_tool',
            'mode',
        ];

        do {
            $users = get_users( [
                'fields' => 'ID',
                'number' => $batch,
                'offset' => $offset,
            ] );

            foreach ( $users as $user_id ) {
                foreach ( $keys as $key ) {
                    delete_user_meta( $user_id, 'ddtt_' . $key );
                }
            }

            $offset += $batch;
        } while ( !empty( $users ) );
    } // End clear_all_user_meta()


    /**
     * Helper function to delete an option if it exists.
     *
     * @param string $option_name The option name to delete.
     * @param string $prefix The prefix to use (default is 'ddtt_').
     */
    private static function delete_option( $option_name, $prefix = 'ddtt_' ) {
        if ( get_option( $prefix . $option_name ) !== false ) {
            delete_option( $prefix . $option_name );
        }
    } // End delete_option()

}