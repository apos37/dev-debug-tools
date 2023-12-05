<?php
/**
 * Plugin Name:         Developer Debug Tools
 * Plugin URI:          https://github.com/apos37/dev-debug-tools
 * Description:         WordPress debugging and testing tools for developers
 * Version:             1.5.6
 * Requires at least:   5.9.0
 * Tested up to:        6.4.1
 * Requires PHP:        7.4
 * Author:              Apos37
 * Author URI:          https://apos37.com/
 * Text Domain:         dev-debug-tools
 * License:             GPL v2 or later
 * License URI:         http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Defines
 */

// Prefixes
define( 'DDTT_PF', 'DDTT_' ); // Plugin prefix
define( 'DDTT_GO_PF', 'ddtt_' ); // Global options prefix

// Names
define( 'DDTT_NAME', 'Developer Debug Tools' );
define( 'DDTT_TEXTDOMAIN', 'dev-debug-tools' );
define( 'DDTT_AUTHOR', 'Apos37' );
define( 'DDTT_AUTHOR_EMAIL', 'apos37@pm.me' );
define( 'DDTT_AUTHOR_URL', 'https://apos37.com/' );
define( 'DDTT_DISCORD_SUPPORT_URL', 'https://discord.gg/3HnzNEJVnR' );

// Versions
define( 'DDTT_VERSION', '1.5.6' );
define( 'DDTT_MIN_PHP_VERSION', '7.4' );

// Prevent loading the plugin if PHP version is not minimum
if ( version_compare( PHP_VERSION, DDTT_MIN_PHP_VERSION, '<=' ) ) {
   add_action(
       'admin_init',
       static function() {
           deactivate_plugins( plugin_basename( __FILE__ ) );
       }
   );
   add_action(
       'admin_notices',
       static function() {
           echo wp_kses_post(
           sprintf(
               '<div class="notice notice-error"><p>%s</p></div>',
               __( '"'.DDTT_NAME.'" requires PHP '.DDTT_MIN_PHP_VERSION.' or newer.', 'dev-debug-tools' )
           )
           );
       }
   );
   return;
}

// Allow for Multisite dashboard
function ddtt_admin_url( $path = '', $scheme = 'admin' ) {
    if ( is_network_admin() ) {
        $admin_url = network_admin_url( $path, $scheme );
    } else {
        $admin_url = admin_url( $path, $scheme );
    }
    return $admin_url;
} // End ddtt_admin_url()

// Paths
$site_url = site_url( '/' );
define( 'DDTT_ADMIN_URL', str_replace( $site_url, '', rtrim( ddtt_admin_url(), '/' ) ) );           //: wp-admin || wp-admin/network
define( 'DDTT_CONTENT_URL', str_replace( $site_url, '', content_url() ) );                          //: wp-content
define( 'DDTT_INCLUDES_URL', str_replace( $site_url, '', rtrim( includes_url(), '/' ) ) );          //: wp-includes
define( 'DDTT_PLUGINS_URL', str_replace( $site_url, '', plugins_url() ) );                          //: wp-content/plugins
define( 'DDTT_PLUGIN_ABSOLUTE', __FILE__ );                                                         //: /home/.../public_html/wp-content/plugins/dev-debug-tools/dev-debug-tools.php)
define( 'DDTT_PLUGIN_ROOT', plugin_dir_path( __FILE__ ) );                                          //: /home/.../public_html/wp-content/plugins/dev-debug-tools/
define( 'DDTT_PLUGIN_DIR', plugins_url( '/'.DDTT_TEXTDOMAIN.'/' ) );                                //: https://domain.com/wp-content/plugins/dev-debug-tools/
define( 'DDTT_PLUGIN_SHORT_DIR', str_replace( site_url(), '', DDTT_PLUGIN_DIR ) );                  //: /wp-content/plugins/dev-debug-tools/
define( 'DDTT_PLUGIN_ASSETS_PATH', DDTT_PLUGIN_ROOT.'assets/' );                                    //: /home/.../public_html/wp-content/plugins/dev-debug-tools/assets/
define( 'DDTT_PLUGIN_IMG_PATH', DDTT_PLUGIN_DIR.'includes/admin/img/' );                            //: https://domain.com/wp-content/plugins/dev-debug-tools/includes/admin/img/
define( 'DDTT_PLUGIN_INCLUDES_PATH', DDTT_PLUGIN_ROOT.'includes/' );                                //: /home/.../public_html/wp-content/plugins/dev-debug-tools/includes/
define( 'DDTT_PLUGIN_ADMIN_PATH', DDTT_PLUGIN_INCLUDES_PATH.'admin/' );                             //: /home/.../public_html/wp-content/plugins/dev-debug-tools/includes/admin/
define( 'DDTT_PLUGIN_CLASSES_PATH', DDTT_PLUGIN_INCLUDES_PATH.'classes/' );                         //: /home/.../public_html/wp-content/plugins/dev-debug-tools/includes/classes/
define( 'DDTT_PLUGIN_CSS_PATH', DDTT_PLUGIN_SHORT_DIR.'includes/admin/css/' );                      //: /wp-content/plugins/dev-debug-tools/includes/admin/css/
define( 'DDTT_PLUGIN_JS_PATH', DDTT_PLUGIN_SHORT_DIR.'includes/admin/js/' );                        //: /wp-content/plugins/dev-debug-tools/includes/admin/js/
define( 'DDTT_PLUGIN_FILES_PATH', DDTT_PLUGIN_SHORT_DIR.'includes/files/' );                        //: /wp-content/plugins/dev-debug-tools/includes/files/

//: https://domain.com/wp-admin/admin.php?page=dev-debug-tools
//: https://domain.com/wp-admin/admin.php?page=dev-debug-tools&tab=testing
function ddtt_plugin_options_path( $tab = null ) {
    $incl_tab = !is_null( $tab ) ? '&tab='.sanitize_html_class( $tab ) : '';
    return ddtt_admin_url( 'admin.php?page='.DDTT_TEXTDOMAIN.$incl_tab );
} // End ddtt_plugin_options_path()

//: dev-debug-tools
//: dev-debug-tools&tab=testing
function ddtt_plugin_options_short_path( $tab = null ) {
    $incl_tab = !is_null($tab) ? '&tab='.sanitize_html_class( $tab ) : '';
    return DDTT_TEXTDOMAIN.$incl_tab;
} // End ddtt_plugin_options_path()


/**
 * Multisite verbiage
 */
function ddtt_multisite_suffix() {
    if ( is_network_admin() ) {
        $sfx = __( ' <em>- Network</em>', 'dev-debug-tools' );
    } elseif ( is_multisite() && is_main_site() ) {
        $sfx = __( ' <em>- Primary</em>', 'dev-debug-tools' );
    } elseif ( is_multisite() && !is_main_site() ) {
        $sfx = __( ' <em>- Subsite</em>', 'dev-debug-tools' );
    } else {
        $sfx = '';
    }
    return $sfx;
} // End ddtt_multisite_suffix()


/**
 * Add user and url when an error occurs
 *
 * @param int $num
 * @param string $str
 * @param string $file
 * @param string $line
 * @param null $context
 * @return void
 */
function ddtt_log_error( $num, $str, $file, $line, $context = null ) {
    // Get user id
    $user_id = get_current_user_id();

    // Check for a user name
    if ( is_user_logged_in() ) {
        $user = get_userdata( $user_id );
        $display_name = sanitize_text_field( $user->display_name );
    } else {
        $display_name = 'Visitor';
    }

    // Log
    error_log( 'Error triggered by user '.$user_id.' ('.$display_name.') on '.$_SERVER[ 'REQUEST_URI' ] );
    
    // Restore the old handler
    restore_error_handler();
} // End ddtt_log_error()

// Option to set it
if ( get_option( DDTT_GO_PF.'log_user_url' ) && get_option( DDTT_GO_PF.'log_user_url' ) == 1 ) {
    set_error_handler( 'ddtt_log_error' );
}


/**
 * Checks for fatal errors and parse errors, work around for set_error_handler not working on them.
 *
 * @return void
 */
function ddtt_check_for_fatal() {
    $error = error_get_last();
    $additional_errors = [ E_ERROR, E_PARSE ];
    if ( isset( $error[ 'type' ] ) && in_array( $error[ 'type' ], $additional_errors ) ) {
        ddtt_log_error( $error[ 'type' ], $error[ 'message' ], $error[ 'file' ], $error[ 'line' ] );
    }
} // End ddtt_check_for_fatal()

// Option to set it
if ( get_option( DDTT_GO_PF.'log_user_url' ) && get_option( DDTT_GO_PF.'log_user_url' ) == 1 ) {
    register_shutdown_function( 'ddtt_check_for_fatal' );
}


/**
 * Activate
 */
register_activation_hook( __FILE__, 'ddtt_activate_plugin' );
function ddtt_activate_plugin() {
    // Log when this plugin was installed
    if ( !get_option( DDTT_GO_PF.'plugin_installed' ) ) {
        update_option( DDTT_GO_PF.'plugin_installed', gmdate( 'Y-m-d H:i:s' ) );
    }

	// Log when this plugin was last activated
    update_option( DDTT_GO_PF.'plugin_activated', gmdate( 'Y-m-d H:i:s' ) );

    // Log who activated this plugin
    update_option( DDTT_GO_PF.'plugin_activated_by', get_current_user_id() );

    // Uninstall
    register_uninstall_hook( __FILE__, DDTT_GO_PF.'uninstall_plugin' );
} // End ddtt_activate_plugin()


/**
 * Uninstall
 * Registered inside register_activation_hook above
 */
function ddtt_uninstall_plugin() {
    // Delete options
    delete_option( DDTT_GO_PF.'plugin_installed' ); // Date the plugin was installed
    delete_option( DDTT_GO_PF.'test_number' ); // Test number
    delete_option( DDTT_GO_PF.'centering_tool_cols' ); // Centering tool columns
    delete_option( DDTT_GO_PF.'dev_email' ); // Dev email
    delete_option( DDTT_GO_PF.'disable_fb_form' ); // Disable the deactivate feedback form
} // End ddtt_uninstall_plugin()


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require DDTT_PLUGIN_INCLUDES_PATH . 'class-'. DDTT_TEXTDOMAIN .'.php';


/**
 * Begin execution of the plugin
 */
new DDTT_DEBUG_TOOLS();