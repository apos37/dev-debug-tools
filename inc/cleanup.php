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
        // Delete all options
        $options = self::get_all_options();
        foreach ( $options as $option_list ) {
            foreach ( $option_list as $option ) {
                self::delete_option( $option );
            }
        }

        // Clear all user meta
        self::clear_all_user_meta();

        // Delete any transients related to the plugin
        self::delete_transients();

        // Delete any files created by the plugin
        self::delete_files();
    } // End run()


    /**
     * Get all option keys used by the plugin
     *
     * @return array
     */
    public static function get_all_options( $incl_old = true ) {
        $all_options = [];

        $groups = [
            'general',
            'logging',
            'config_files',
            'metadata',
            'heartbeat',
            'online_users',
            'admin_bar',
            'admin_areas',
            'security',
            'discord',
            'page_specific',
        ];

        if ( $incl_old ) {
            $groups[] = 'old';
        }

        foreach ( $groups as $group ) {
            $method = 'get_' . $group . '_options';
            if ( method_exists( __CLASS__, $method ) ) {
                $all_options[ $group ] = self::$method();
            }
        }

        return $all_options;
    } // End get_all_options()


    /**
     * Delete general options
     */
    private static function get_general_options() {
        return [
            'developers',
            'dev_timezone',
            'dev_timeformat',
            'open_nav_new_tab'
        ];
    } // End delete_general_options()


    /**
     * Get logging options
     */
    private static function get_logging_options() {
        return [
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
    } // End get_logging_options()


    /**
     * Get config file options
     */
    private static function get_config_files_options() {
        return [
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
    } // End get_config_files_options()


    /**
     * Get metadata options
     */
    private static function get_metadata_options() {
        return [
            'protected_meta_keys',
        ];
    } // End get_metadata_options()


    /**
     * Get Heartbeat options
     */
    private static function get_heartbeat_options() {
        return [
            'enable_heartbeat_monitor',
            'disable_everywhere',
            'disable_admin',
            'disable_frontend',
        ];
    } // End get_heartbeat_options()


    /**
     * Get Online Users options
     */
    private static function get_online_users_options() {
        return [
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
    } // End get_online_users_options()


    /**
     * Get Admin Bar options
     */
    private static function get_admin_bar_options() {
        return [
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
    } // End get_admin_bar_options()


    /**
     * Get Admin Area options
     */
    private static function get_admin_areas_options() {
        return [
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
    } // End get_admin_areas_options()


    /**
     * Get Security options
     */
    private static function get_security_options() {
        return [
            'dev_access_only',
            'hide_plugin',
            'plugin_alias',
            'plugin_desc',
            'plugin_author',
            'view_sensitive_info',
            'enable_pass',
            'pass',
            'pass_exp',
            'pass_attempts',
            'pass_lockout',
            'secure_pages',
            'remove_data_on_uninstall',
        ];
    } // End get_security_options()


    /**
     * Get Discord options
     */
    private static function get_discord_options() {
        return [
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
    } // End get_discord_options()


    /**
     * Get page specific options
     */
    private static function get_page_specific_options() {
        return [
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
            'htaccess_viewer_customizations', // class-file-editor.php > ajax_update_colors()
            'wpconfig_viewer_customizations', // class-file-editor.php > ajax_update_colors()
            'htaccess_last_modified', // class-file-editor.php > ajax_save_edits()
            'wpconfig_last_modified', // class-file-editor.php > ajax_save_edits()
            'htaccess_snippets', // class-file-editor.php > ajax_add_snippet()
            'wpconfig_snippets', // class-file-editor.php > ajax_add_snippet()
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
    } // End get_page_specific_options()


    /**
     * Get old options (no longer used)
     */
    private static function get_old_options() {
        return [
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
    } // End get_old_options()


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


    /**
     * Delete any transients related to the plugin
     */
    private static function delete_transients() {
        global $wpdb;

        // Prefix used by your plugin
        $prefix = 'ddtt_';

        // Delete all transients with your prefix
        $transients = $wpdb->get_col( $wpdb->prepare(
            "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s",
            $wpdb->esc_like( '_transient_' . $prefix ) . '%'
        ) );

        foreach ( $transients as $transient ) {
            // Strip the _transient_ prefix to get the transient name
            $name = preg_replace( '/^_transient_/', '', $transient );
            delete_transient( $name );
        }

        // Also delete expired transient timeouts just in case
        $timeouts = $wpdb->get_col( $wpdb->prepare(
            "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s",
            $wpdb->esc_like( '_transient_timeout_' . $prefix ) . '%'
        ) );

        foreach ( $timeouts as $timeout ) {
            $name = preg_replace( '/^_transient_timeout_/', '', $timeout );
            delete_transient( $name );
        }
    } // End delete_transients()


    /**
     * Delete any files created by the plugin
     */
    private static function delete_files() {
        if ( ! function_exists( 'WP_Filesystem' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        global $wp_filesystem;

        if ( ! WP_Filesystem() ) {
            return;
        }

        $upload_dir = wp_upload_dir();
        $ddtt_dir   = trailingslashit( $upload_dir[ 'basedir' ] ) . 'dev-debug-tools/';

        // Delete the directory and everything inside it
        if ( $wp_filesystem->is_dir( $ddtt_dir ) ) {
            $wp_filesystem->rmdir( $ddtt_dir, true );
        }
    } // End delete_files()

}