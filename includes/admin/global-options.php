<?php
/**
 * Global options class file.
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Initiate the class
 */
new DDTT_GLOBAL_OPTIONS;


/**
 * Main plugin class.
 */
class DDTT_GLOBAL_OPTIONS {

    /**
	 * Constructor
	 */
	public function __construct() {
        // Call register settings function
        add_action( 'admin_init', [ $this, 'register_settings' ] );
	} // End __construct()


    /**
     * Register settings
     * Do not need to include the prefix
     *
     * @return void
     */
    public function register_settings() {
        // General Settings
        $this->register_group_settings( 'settings', [
            'dev_email',
            'dev_timezone',
            'view_sensitive_info',
            'test_number',
            'centering_tool_cols',
            'stop_heartbeat',
            'enable_curl_timeout',
            'change_curl_timeout',
            'ql_user_id',
            'ql_post_id',
            'ql_comment_id',
            'ql_gravity_forms',
            'disable_error_counts',
            'log_viewer',
            'log_user_url',
            'wp_mail_failure',
            'error_log_path',
            'admin_error_log_path',
            'log_files',
            'fatal_discord_webhook',
            'fatal_discord_enable',
            'online_users',
            'online_users_seconds',
            'online_users_show_last',
            'online_users_link',
            'online_users_priority_roles',
            'discord_webhook',
            'discord_login',
            'discord_transient',
            'discord_page_loads',
            'discord_ingore_devs',
            'admin_bar_wp_logo',
            'admin_bar_resources',
            'admin_bar_gf',
            'admin_bar_shortcodes',
            'admin_bar_centering_tool',
            'admin_bar_post_info',
            'admin_bar_add_links',
            'admin_bar_condense',
            'color_comments',
            'color_fx_vars',
            'color_syntax',
            'color_text_quotes',
        ] );

        // Error Reporting Settings
        $this->register_group_settings( 'error', [
            'error_enable',
            'error_uninstall',
            'error_constants',
        ] );

        // Regex Settings
        $this->register_group_settings( 'regex', [
            'regex_string',
            'regex_pattern'
        ] );
    } // End register_settings()


    /**
     * Register group settings
     * 
     * @return void
     */
    public function register_group_settings( $group_name = 'options', $options = [] ) {   
        if ( !empty( $options ) ) {
            foreach ( $options as $option ) {
                register_setting( DDTT_PF.'group_'.$group_name, DDTT_GO_PF.$option );
            }
        }
    } // End register_group_settings
}