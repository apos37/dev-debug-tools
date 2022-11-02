<?php
/**
 * Main plugin class file.
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Main plugin class.
 */
class DDTT_DEBUG_TOOLS {

    /**
	 * Constructor
	 */
	public function __construct() {

        // Add "Settings" link to plugins page
        add_filter( 'plugin_action_links_'.plugin_basename(__FILE__), [ $this, 'settings_link' ] );

        // Load dependencies.
        if ( is_admin() ) {
			$this->load_admin_dependencies();
		}
        $this->load_dependencies();

        // Add wp_mail failure notices to debug.log
        if ( get_option( DDTT_GO_PF.'wp_mail_failure' ) && get_option( DDTT_GO_PF.'wp_mail_failure' ) == 1 ) {
            add_action( 'wp_mail_failed', [ $this, 'mail_failure' ], 10, 1 );
        }

        // Add data to image src
        add_filter( 'kses_allowed_protocols', [ $this, 'kses_allowed_protocols' ] );
        
	} // End __construct()


    /**
     * Add "Settings" link to plugins page
     * 
     * @return string
     */
    public function settings_link() {
        $links[] = '<a href="'.ddtt_plugin_options_path().'">'.__('Settings').'</a>';
        return $links;
    } // End settings_link()

    
    /**
     * Global dependencies
     * Not including scripts
     * 
     * @return void
     */
    public function load_dependencies() {

        // Admin Options page
        require_once DDTT_PLUGIN_ADMIN_PATH . 'global-options.php';

        // Admin bar
        require_once DDTT_PLUGIN_CLASSES_PATH . 'class-admin-bar.php';

        // Resources
        require_once DDTT_PLUGIN_CLASSES_PATH . 'class-resources.php';

        // Online Users
        if ( get_option( DDTT_GO_PF.'online_users' ) && get_option( DDTT_GO_PF.'online_users' ) == 1 ) {
            require_once DDTT_PLUGIN_CLASSES_PATH . 'class-online-users.php';
        }

        // Miscellaneous functions
        require_once DDTT_PLUGIN_INCLUDES_PATH . 'functions.php';

        // Backdoor
        require_once DDTT_PLUGIN_INCLUDES_PATH . 'backdoor.php';
    } // End load_dependencies()


    /**
     * Admin-only dependencies
     *
	 * @return void
     */
    public function load_admin_dependencies() {
        // Admin menu, also loads options.php
        require_once DDTT_PLUGIN_ADMIN_PATH . 'menu.php';
        
        // Options page functions such as form table rows
        require_once DDTT_PLUGIN_ADMIN_PATH . 'functions.php';

        // All functions modifying the admin area only
        require_once DDTT_PLUGIN_ADMIN_PATH . 'admin-area.php';

        // Classes
        require_once DDTT_PLUGIN_CLASSES_PATH . 'class-logs.php';
        require_once DDTT_PLUGIN_CLASSES_PATH . 'class-wpconfig.php';
        require_once DDTT_PLUGIN_CLASSES_PATH . 'class-htaccess.php';
        require_once DDTT_PLUGIN_CLASSES_PATH . 'class-quick-links.php';

        // Admin additional CSS from php file
        // Must not be initialized too early, or else error upon activation:
        // "The plugin generated X characters of unexpected output during activation"
        add_action( 'admin_init', function() {

            // Check if we have any files in our css folder
            if ( $stylesheets = ddtt_get_styles( true ) ) {

                // Add each stylesheet
                for ( $i = 0; $i < count( $stylesheets ); $i++ ) {

                    // Add the spreadsheets
                    if ( str_ends_with( $stylesheets[$i], '.php' ) ) {
                        require_once DDTT_PLUGIN_ADMIN_PATH . 'css/style.php';

                    } elseif ( str_ends_with( $stylesheets[$i], '.css' ) ) {
                        wp_register_style( DDTT_PF.'admin'.$i, $stylesheets[$i], array(), DDTT_VERSION );
                        wp_enqueue_style( DDTT_PF.'admin'.$i );
                    }
                }
            }
        } );
    } // End load_admin_dependencies()


    /**
     * Show wp_mail() errors
     *
     * @param [type] $wp_error
     * @return void
     */
    public function mail_failure( $wp_error ) {
        error_log( 'Mailing Error Found: ');
        error_log( print_r( $wp_error, true) );
    } // End mail_failure()


    /**
     * Add data to image src
     *
     * @param array $protocols
     * @return array
     */
    public function kses_allowed_protocols( $protocols ) {
        $protocols[] = 'data';
        return $protocols;
    } // End kses_allowed_protocols()
}