<?php
/**
 * Plugin Name:         Developer Debug Tools
 * Plugin URI:          https://github.com/apos37/dev-debug-tools
 * Description:         WordPress debugging and testing tools for developers
 * Version:             1.3.5
 * Requires at least:   5.9.0
 * Tested up to:        6.1
 * Requires PHP:        7.4
 * Author:              Apos37
 * Author URI:          https://github.com/apos37
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

// Versions
define( 'DDTT_VERSION', '1.3.5' );
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

// Paths
define( 'DDTT_ADMIN_URL', str_replace( site_url( '/' ), '', rtrim( admin_url(), '/' ) ) );          //: wp-admin
define( 'DDTT_CONTENT_URL', str_replace( site_url( '/' ), '', content_url() ) );                    //: wp-content
define( 'DDTT_INCLUDES_URL', str_replace( site_url( '/' ), '', rtrim( includes_url(), '/' ) ) );    //: wp-includes
define( 'DDTT_PLUGINS_URL', str_replace( site_url( '/' ), '', plugins_url() ) );                    //: wp-content/plugins
define( 'DDTT_PLUGIN_ABSOLUTE', __FILE__ );                                                         //: /home/.../public_html/wp-content/plugins/dev-debug-tools/dev-debug-tools.php)
define( 'DDTT_PLUGIN_ROOT', plugin_dir_path( __FILE__ ) );                                          //: /home/.../public_html/wp-content/plugins/dev-debug-tools/
define( 'DDTT_PLUGIN_DIR', plugins_url( '/'.DDTT_TEXTDOMAIN.'/' ) );                                //: https://domain.com/wp-content/plugins/dev-debug-tools/
define( 'DDTT_PLUGIN_SHORT_DIR', str_replace( site_url(), '', DDTT_PLUGIN_DIR ) );                  //: /wp-content/plugins/dev-debug-tools/
define( 'DDTT_PLUGIN_ASSETS_PATH', DDTT_PLUGIN_ROOT.'assets/' );                                    //: /home/.../public_html/wp-content/plugins/dev-debug-tools/assets/
define( 'DDTT_PLUGIN_IMG_PATH', DDTT_PLUGIN_DIR.'includes/admin/img/' );                            //: https://domain.com/wp-content/plugins/dev-debug-tools/includes/admin/img/
define( 'DDTT_PLUGIN_INCLUDES_PATH', DDTT_PLUGIN_ROOT.'includes/' );                                //: /home/.../public_html/wp-content/plugins/dev-debug-tools/includes/
define( 'DDTT_PLUGIN_ADMIN_PATH', DDTT_PLUGIN_INCLUDES_PATH.'admin/' );                             //: /home/.../public_html/wp-content/plugins/dev-debug-tools/includes/admin/
define( 'DDTT_PLUGIN_CLASSES_PATH', DDTT_PLUGIN_INCLUDES_PATH.'classes/' );                         //: /home/.../public_html/wp-content/plugins/dev-debug-tools/includes/classes/
define( 'DDTT_PLUGIN_FILES_PATH', DDTT_PLUGIN_SHORT_DIR.'includes/files/' );                        //: /wp-content/plugins/dev-debug-tools/includes/files/

//: https://domain.com/wp-admin/admin.php?page=dev-debug-tools%2Fincludes%2Fadmin%2Foptions.php
//: https://domain.com/wp-admin/admin.php?page=dev-debug-tools%2Fincludes%2Fadmin%2Foptions.php&tab=testing
function ddtt_plugin_options_path( $tab = null ) {
    $incl_tab = !is_null( $tab ) ? '&tab='.sanitize_html_class( $tab ) : '';
    return admin_url( 'admin.php?page='. DDTT_TEXTDOMAIN .'%2Fincludes%2Fadmin%2Foptions.php'.$incl_tab );
} // End ddtt_plugin_options_path()

//: dev-debug-tools/includes/admin/options.php
//: dev-debug-tools/includes/admin/options.php&tab=testing
function ddtt_plugin_options_short_path( $tab = null ) {
    $incl_tab = !is_null($tab) ? '&tab='.sanitize_html_class( $tab ) : '';
    return DDTT_TEXTDOMAIN .'/includes/admin/options.php'.$incl_tab;
} // End ddtt_plugin_options_path()


/**
 * Activate
 */
register_activation_hook( __FILE__, 'ddtt_activate_plugin' );
function ddtt_activate_plugin() {
    // Log when this plugin was installed
    update_option( DDTT_GO_PF.'plugin_installed', date( 'Y-m-d H:i:s' ) );

	// Log when this plugin was last activated
    update_option( DDTT_GO_PF.'plugin_activated', date( 'Y-m-d H:i:s' ) );

    // Uninstall
    register_uninstall_hook( __FILE__, DDTT_GO_PF.'uninstall_plugin' );
} // End ddtt_activate_plugin()


/**
 * Deactivate
 */
// register_deactivation_hook( __FILE__, 'ddtt_deactivate_plugin' );
// function ddtt_deactivate_plugin() {
// 	// Do something when plugin is deactivated
// }


/**
 * Uninstall
 * Registered inside register_activation_hook above
 */
function ddtt_uninstall_plugin() {
    // Delete options
    delete_option( DDTT_GO_PF.'plugin_installed' ); // Date the plugin was installed
    delete_option( DDTT_GO_PF.'test_number' ); // Test number
    delete_option( DDTT_GO_PF.'centering_tool_cols' ); // Test number
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