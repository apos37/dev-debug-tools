<?php
/**
 * Admin menu class file.
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Initiate the class
 */
new DDTT_MENU;


/**
 * Main plugin class.
 */
class DDTT_MENU {

    /**
     * The menu slug
     *
     * @var string
     */
    public $slug;
    

    /**
	 * Constructor
	 */
	public function __construct() {
        // Define the menu slug
        $this->slug = DDTT_TEXTDOMAIN;

        // Add the menu
        $hook = is_network_admin() ? 'network_' : '';
        add_action( $hook.'admin_menu', [ $this, 'admin_menu' ] );

        // Show active menu
        add_filter( 'parent_file', [ $this, 'submenus' ] );
	} // End __construct()


    /**
     * Add options menu to the Admin Control Panel
     * 
     * @return void
     * @since   1.0.0
     */
    public function admin_menu() {
        // Die if not admin
        if ( !ddtt_has_role( 'administrator' ) ) {
            return;
        }

        // The icon
        $icon_base64 = base64_encode('<svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 504.24 489.11"><path fill="#9CA2A7" d="M322.19,150.95c28.62,0,57.25-.31,85.87,.22,7.07,.13,10.98-3.1,14.92-7.72,8.94-10.47,18.45-20.47,29.34-28.87,4.83-3.73,11.11-3.23,16.66-.21,5.1,2.78,8,6.88,8.22,13.01,.2,5.55-1.66,9.76-5.56,13.62-12.14,12.06-24.3,24.09-36.81,35.78-2.32,2.17-4.73,3.22-7.94,3.19-12.66-.12-25.32-.05-38.82-.05,3.88,5.78,7.57,11.04,11,16.47,14.82,23.41,27.25,47.89,32.95,75.27,.63,3.04,2.4,2.19,3.96,2.19,16.83,.06,33.65,.03,50.48,.04,10.07,0,17.19,6.48,17.72,15.17,.86,14-7.7,19.55-17.77,19.74-15.32,.29-30.65,.13-45.98,.03-2.86-.02-4.19,.23-4.46,3.85-1.29,17.44-3.65,34.75-9.85,51.25-2.68,7.14-5.79,14.12-9.02,21.91,3.08,0,5.97,.16,8.84-.04,5.14-.35,9.26,1.68,12.76,5.16,12.16,12.1,24.33,24.2,36.39,36.41,5.86,5.93,9.94,12.56,6.55,21.39-4.37,11.36-18.84,14-28.06,4.97-10.23-10.03-20.31-20.21-30.38-30.4-1.74-1.76-3.46-2.94-6.06-2.55-1.3,.2-2.66-.01-3.99,.04-6.29,.27-13.19-1.82-18.7,.79-5.27,2.5-9.04,8.06-13.6,12.15-23.92,21.45-50.66,37.96-81.49,47.35-18.63,5.67-37.8,9.32-57.26,7.48-23.09-2.18-45.6-7.21-66.88-17.18-21.8-10.2-42.35-22.71-57.33-41.31-10.02-12.44-21.57-8.25-33.09-9.24-1.91-.16-2.73,1.7-3.87,2.83-10.05,9.98-19.98,20.07-30.09,29.98-8.95,8.78-23.12,6.4-27.77-4.52-2.91-6.83-1.58-14.05,3.66-19.31,12.23-12.27,24.62-24.39,36.69-36.81,4.9-5.04,10.26-8.04,17.44-7.24,1.81,.2,3.66,.03,6.16,.03-3.43-7.47-6.93-14.53-9.37-22.04-4.2-12.92-7.63-25.97-8.39-39.59-.11-1.99-1.08-3.72-1.1-5.79-.08-9.55-.18-9.55-9.53-9.55-13.49,0-26.99,.14-40.48-.04-16.98-.23-21.48-15.5-15.92-26.49,2.75-5.44,7.64-8.44,13.95-8.45,16.49-.04,32.99-.09,49.48,.05,3.78,.03,5.08-.74,5.81-5.06,2.5-14.77,7.21-28.99,13.12-42.8,6.95-16.24,15.87-31.32,27.42-46.17-9.08,0-17.25-.86-25.16,.21-10.56,1.43-17.94-3.02-24.88-9.92-9.9-9.82-20.08-19.36-30.03-29.13-6.82-6.7-6.39-16.26-.11-23.42,5.47-6.23,18.14-6.07,23.55-.29,9.78,10.46,20.22,20.29,30.24,30.53,2.14,2.18,4.24,3.11,7.35,3.1,27.49-.13,54.97-.08,82.46-.08h5.32c.23,.34,.45,.68,.68,1.01-42.16,20.66-71.38,53.03-86.98,96.92-15.76,44.32-12.39,87.92,9.09,129.64,37.85,73.53,109.81,95.36,158.67,91.8,43.67-3.18,81.92-20.78,112.02-53.29,23.44-25.32,38.54-55.24,43.55-89.63,10.88-74.71-30.09-145.43-95.6-176.46Z M126.48,220.75c46.03-75.47,158.52-93.39,227.1-29.04-4.94,1.43-10.13,1.55-14.48,4.59-10.77,7.52-14.78,20.07-10.39,32.96,3.08,9.05,8.59,16.76,13.44,24.85,6.1,10.19,10.12,20.91,11.31,33.01,1.7,17.33-2.92,33.33-7.91,49.43-5.48,17.67-10.83,35.39-16.26,53.08-.43,1.39-1,2.74-1.64,4.5-3.09-3.31-3.59-7.39-4.7-11.05-11.17-36.74-24.87-72.63-36.61-109.16-4.1-12.74-8.27-25.45-12.54-38.13-.96-2.84-.47-3.92,2.69-3.94,3.96-.02,7.93-.41,11.89-.78,4.84-.45,7.28-2.97,6.79-6.83-.48-3.76-3.48-5.06-8.22-4.87-18.05,.73-36.07,2.69-54.2,.92-7.55-.74-15.2-.32-22.8-1.24-3.18-.39-5.75,1.59-6.12,5.2-.34,3.37,.59,5.97,4.55,6.37,3.94,.4,7.89,1.26,11.82,1.16,3.33-.09,4.84,1.37,5.85,4.09,7.25,19.54,14.5,39.09,21.76,58.63,.75,2,.38,3.85-.29,5.82-9.5,27.89-18.9,55.81-28.44,83.69-1.25,3.65-1.25,7.73-4.17,11.77-2.19-6.25-4.2-11.9-6.14-17.57-7.36-21.44-15.2-42.74-21.9-64.38-8.13-26.24-17.73-51.96-26.37-78.02-.89-2.69-.65-3.97,2.64-3.97,4.12,0,8.24-.43,12.35-.75,4.99-.39,7.66-3.09,7.11-7.08-.5-3.63-3.44-4.71-8.37-4.55-15.86,.52-31.71,2.19-47.73,1.29Z M356.89,135.01H147.77c.56-9.46,.7-18.75,3.4-27.78,3.92-13.13,9.84-25.11,18.37-35.91,6.72-8.51,14.73-15.49,23.69-21.39,2.51-1.65,1.91-2.38,.3-3.88-6.19-5.75-12.13-11.77-18.26-17.59-7.06-6.71-5.82-17.58-.28-23.4,6.48-6.81,17.65-6.66,24.68,.24,9.16,8.98,18.16,18.12,27.24,27.18,1.35,1.35,2.37,2.35,4.94,1.98,6.63-.96,13.25-2.36,20.04-2.23,6.26,.12,12.43,.75,18.56,1.99,2.94,.59,5.31,.27,7.74-2.29,8.36-8.83,17.24-17.16,25.66-25.93,5.41-5.64,12.04-7.03,18.97-5.26,6.87,1.75,10.25,7.68,11.05,14.3,.57,4.7-1.3,9.31-4.86,12.88-6.01,6.01-11.96,12.07-18.05,17.99-1.88,1.83-2.09,2.58,.42,4.22,16.65,10.84,29.04,25.35,36.95,43.65,4.78,11.07,7.92,22.46,7.83,34.62-.01,2.13-.46,4.34,.71,6.63Z M254.77,315.44c5.7,15.24,11.08,29.63,16.46,44.01,4.4,11.77,9.39,23.37,13.08,35.36,4.97,16.12,11.45,31.66,17.16,47.5,1.09,3.02,.08,3.72-2.42,4.48-28.19,8.58-56.53,9.7-85.17,2.29-3.94-1.02-4.11-2.67-2.96-5.97,6.87-19.86,13.51-39.79,20.39-59.64,5.79-16.71,11.7-33.38,17.75-49.99,2.04-5.59,2.54-11.64,5.71-18.03Z M187.4,439.22c-76.25-36.61-105.45-126.98-72.37-197.71,24.08,65.8,47.97,131.06,72.37,197.71Z M403.03,293.89c0,6.34,0,12.68,0,19.02-.25,.5-.5,1-.75,1.5-.24,18.21-5.95,35-12.89,51.6-8.65,20.71-22.79,37.19-39.01,52.07-6.52,5.98-13.84,11.09-22.54,15.33,1.45-7.51,4.42-13.77,6.62-20.25,5.43-15.98,11.7-31.7,16.65-47.82,9.22-30.03,21.84-58.9,30.32-89.15,3.31-11.81,4.83-23.94,3.67-36.29-.22-2.31,.03-4.65,.07-6.98l-.1,.07c7.37,11.51,11.34,24.34,14.56,37.47,1.82,7.39,2.29,14.97,3.34,22.48-.11,.1-.33,.24-.31,.29,.09,.24,.24,.45,.38,.67Z M385.16,232.91c-.2-.2-.4-.41-.6-.61,.73-.19,.82,.09,.5,.68,0,0,.1-.07,.1-.07Z M403.03,293.89c-.13-.22-.29-.43-.38-.67-.02-.05,.2-.19,.31-.29,.02,.32,.04,.64,.07,.96Z M402.28,314.41c.25-.5,.5-1,.75-1.5,.12,.69-.15,1.18-.75,1.5Z"/></svg>');
        $icon = 'data:image/svg+xml;base64,' . $icon_base64;
        
        // Add verbiage if multisite
        $sfx = ddtt_multisite_suffix();

        // Add a new top level menu link to the ACP
        add_menu_page(
            DDTT_NAME,                  // Title of the page
            DDTT_NAME.$sfx,             // Text to show on the menu link
            'manage_options',           // Capability requirement to see the link
            $this->slug,                // The 'slug' (file to display when clicking the link)
            [ $this, 'options_page' ],  // Function to call
            $icon,                      // The admin menu icon
            2                           // Position on the menu
        );

        // Hide plugin if option is selected
        if ( get_option( DDTT_GO_PF . 'hide_plugin' ) ) {
            remove_menu_page( $this->slug );

        // Otherwise, show submenu
        } else {

            // Fetch the global submenu
            global $submenu;

            // Get the menu items
            $menu_items = ddtt_plugin_menu_items();

            // Skip if multisite
            $multisite_skip = [
                'siteoptions',
                'usermeta',
                'postmeta',
                'scfinder',
                'regex',
                'siteoptions',
                'testing'
            ];

            // Add them
            foreach ( $menu_items as $key => $menu_item ) {
                
                // Skip if multisite
                if ( is_network_admin() && in_array( $key, $multisite_skip ) ) {
                    continue;
                }
                
                // Skip hidden subpages
                if ( isset( $menu_item[3] ) && $menu_item[3] == true ) {
                    continue;
                }

                // Skip subpages if not dev
                if ( isset( $menu_item[2] ) && $menu_item[2] == true && !ddtt_is_dev() ) {
                    continue;
                }

                // Add the menu item
                $submenu[ $this->slug ][] = array( $menu_item[0], 'manage_options', 'admin.php?page='.DDTT_TEXTDOMAIN.'&tab='.$key );
            }
        }
    } // End admin_menu()


    /**
     * Call the options page
     *
     * @return void
     */
    public function options_page() {
        include DDTT_PLUGIN_ADMIN_PATH.'options.php';
    } // End options_page()


    /**
     * Show the active submenu
     *
     * @param string $parent_file
     * @return string
     */
    public function submenus( $parent_file ) {
        // Hide plugin if option is selected
        if ( get_option( DDTT_GO_PF.'hide_plugin' ) ) {
            return;
        }
        
        // Get the global vars
        global $submenu_file, $current_screen;

        // Get the options page
        $options_page = 'toplevel_page_'.DDTT_TEXTDOMAIN;

        // Allow for multisite
        if ( is_network_admin() ) {
            $options_page .= '-network';
        }

        // Help Docs
        if ( $current_screen->id == $options_page ) {
            $tab = ddtt_get( 'tab' ) ?? '';
            $submenu_file = 'admin.php?page='.DDTT_TEXTDOMAIN.'&tab='.$tab;
        }
        return $parent_file;
    } // End submenus()
}


/**
 * Plugin menu items / tabs
 * [ Menu item name, item slug ]
 *
 * @param string $slug
 * @return string|array
 * @since   1.0.0
 */
function ddtt_plugin_menu_items( $slug = null, $desc = false ) {
    // Get notification count
    $notif = '';
    if ( ddtt_is_dev() ) {
        $warning = 0;
        if ( !ddtt_get( 'clear_debug_log' ) || ddtt_get( 'clear_debug_log', '!=', 'true' ) ) {
            $warning = ddtt_error_count();
        }
        if ( $warning > 0 ) {
            $notif = ' <span class="awaiting-mod">'.$warning.'</span>';
        }
    }

    // Testing #
    if ( get_option( DDTT_GO_PF.'test_number' ) && get_option( DDTT_GO_PF.'test_number' ) > 0 ) {
        $test_num = ' #'.get_option( DDTT_GO_PF.'test_number' );
    } else {
        $test_num = '';
    }

    // Multisite wp-config/htaccess
    $multisite = '';
    if ( is_network_admin() || is_multisite() && !is_main_site() ) {
        $multisite = ' <strong><em>';
        if ( is_network_admin() ) {
            $multisite .= 'Note that you are currently on the multisite network.';
        } else {
            $multisite .= 'Note that this site is on a multisite network.';
        }
        $admin = str_replace( site_url( '/' ), '', rtrim( admin_url(), '/' ) );
        $main_site_url = get_site_url( get_main_site_id() ).'/'.$admin.'/admin.php?page='.DDTT_TEXTDOMAIN.'&tab=logs';
        $multisite .= ' All sites share the same file located on the primary site. Please go to the <a href="'.$main_site_url.'" target="_blank">primary site</a> if you need to update it.</em></strong>';
    }

    // Are we currently debugging?
    if ( WP_DEBUG ) {
        $debugging = 'ENABLED';
    } else {
        $debugging = 'DISABLED';
    }

    // Rest API root
    $rest_api_root = rest_url();

    // The menu items
    // Set 3rd param to true if the item should only be visible to devs
    // Set 4th param to true if the item should not be added to the menu or tabs, but is a hidden subpage
    $items = [
        'settings'          => [ __( 'Settings', 'dev-debug-tools' ), 'This area is for developers only.' ],
        'plugins'           => [ __( 'Plugins', 'dev-debug-tools' ), 'A more in-depth breakdown of all the plugins installed on the site.' ],
        'logs'              => [ __( 'Logs', 'dev-debug-tools' ).$notif, 'All of your log files in one place. You can add more log files in <a href="'.ddtt_plugin_options_path( 'settings' ).'">Settings</a>.'.$multisite, true ],
        'error'             => [ __( 'Error Reporting', 'dev-debug-tools' ), 'Choose which errors are reported to your <code class="hl">debug.log</code> file. Note that <code class="hl">WP_DEBUG</code> is currently <code class="'.strtolower( $debugging ).'">'.$debugging.'</code> on your <code class="hl">wp-config.php</code> file. It must be enabled for any of the reporting to work. Please note that not all hosts allow these settings to be changed, but hey it\'s worth a try.'.$multisite, true ],
        'wpcnfg'            => [ 'WP-CONFIG', 'View and update your wp-config.php. Please backup the original before updating.'.$multisite, true ],
        'htaccess'          => [ 'HTACCESS', 'View and update your .htaccess from here. Please backup the original before updating.'.$multisite, true ],
        'fx'                => [ 'Functions.php', 'A simple functions.php viewer.', true ],
        'phpini'            => [ 'PHP.INI', 'All registered configuration options from your php.ini', true ],
        'phpinfo'           => [ 'PHP Info', ' Information about your PHP\'s configuration', true ],
        'cookies'           => [ __( 'Cookies', 'dev-debug-tools' ), 'Your browser cookies currently on this site.', true ],
        'crons'             => [ __( 'Cron Jobs', 'dev-debug-tools' ), 'A list of all scheduled cron jobs.', true ],
        'siteoptions'       => [ __( 'Site Options', 'dev-debug-tools' ), 'A quick view of all the site\'s options for reference.', true ],
        'globalvars'        => [ __( 'Globals', 'dev-debug-tools' ), 'A list of available global variables that can be called with <code class="hl">global $variable;</code>', true ],
        'defines'           => [ __( 'Defines', 'dev-debug-tools' ), 'A full list of all the defined constants and their values. Constants are defined using <code class="hl">define( "CONSTANT", "VALUE" )</code>.', true ],
        'db'                => [ __( 'DB Tables', 'dev-debug-tools' ), 'A quick reference of the database table and column structure.', true ],
        'usermeta'          => [ __( 'User Meta', 'dev-debug-tools' ), 'A quick view of all the user meta so you don\'t have to log into phpMyAdmin.', true ],
        'postmeta'          => [ __( 'Post Meta', 'dev-debug-tools' ), 'A quick view of all the post meta so you don\'t have to log into phpMyAdmin.', true ],
        'autodrafts'        => [ __( 'Auto-Drafts', 'dev-debug-tools' ), 'View current auto-drafts. Auto-drafts are temporary drafts that are typically created when you start a new post and then leave the page without saving it. They can be hidden in the database and not show up in your admin list table with the rest of your drafts. Since these are unnecessary, this page allows you to clear them easily.', true ],
        'api'               => [ __( 'APIs', 'dev-debug-tools' ), 'A list of the site\'s registered REST APIs. Your REST API root is: <a href="'.$rest_api_root.'" target="_blank">'.$rest_api_root.'</a>', true ],
        'scfinder'          => [ __( 'Shortcode Finder', 'dev-debug-tools' ), 'Search through posts and pages for a shortcode.' ],
        'regex'             => [ 'Regex', 'Learn and test regex patterns.', true ],
        'testing'           => [ __( 'Testing', 'dev-debug-tools' ), '<h3>Test Number: '.$test_num.'</h3><br>Use this page as a testing ground for PHP - Only developer accounts can see this.', true ],
        'debug'             => [ __( 'Debug Stuff', 'dev-debug-tools' ), '', false, true ],
        'functions'         => [ __( 'Available Functions', 'dev-debug-tools' ), 'The following functions are available to use for making debugging easier. Note that if you continue to use these functions after deactivating or uninstalling this plugin, it will result in a fatal error.', true ],
        'hooks'             => [ __( 'Available Hooks', 'dev-debug-tools' ), 'A list of the action and filter hooks available for developers. As of version 1.7.4, you can now look up hooks found on other plugins, but please note that we can only look for hooks that are <em>not</em> set up dynamically, so it would be wise to actually look at the plugin\'s documentation if they have any, or search for hooks (<code class="hl">do_action</code> and <code class="hl">apply_filters</code>) in your favorite IDE. This is just a good starting point to find things you may not know it exist. <em>You might also want to see <a href="https://developer.wordpress.org/apis/hooks/action-reference/" target="_blank">WP Core Actions</a> and <a href="https://developer.wordpress.org/apis/hooks/filter-reference/" target="_blank">WP Core Filters</a>.</em>', true ],
        'resources'         => [ __( 'Resources', 'dev-debug-tools' ), 'Helpful resources for WP developers.', true ],
        'about'             => [ __( 'About', 'dev-debug-tools' ), '<a href="'.ddtt_plugin_options_path( 'changelog' ).'">View the Changelog</a>', false ],
        'changelog'         => [ __( 'Changelog', 'dev-debug-tools' ), 'Updates to this plugin.', false, true ],
    ];

    if ( !is_null( $slug ) ) {
        if ( $desc ) {
            return $items[$slug][1];
        } else {
            return $items[$slug][0];
        }
    } else {
        return $items;
    }
} // End menu_items()