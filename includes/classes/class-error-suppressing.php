<?php
/**
 * Error Suppressing class
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Initiate the class
 */
add_action( 'init', function() {
    (new DDTT_ERROR_SUPPRESSING)->init();
} );


/**
 * Main plugin class.
 */
class DDTT_ERROR_SUPPRESSING {

    /**
     * The MU-Plugin Name
     *
     * @var string
     */
    public $mu_plugin_name = 'Suppress Debug Log Errors';


    /**
	 * Constructor
	 */
	public function init() {

        // Enqueue scripts
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

	} // End init()


    /**
     * Add or remove the Must Use Plugin
     * USAGE: add_remove_mu_plugin( 'remove' )
     *
     * @param string $file_to_replace
     * @return boolean
     */
    public function add_remove_mu_plugin( $add_or_remove = 'add' ) {
        // Must-Use-Plugin filename
        $filename = '000-suppress-errors.php';

        // Get the file
        if ( !function_exists( 'WP_Filesystem' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        global $wp_filesystem;
        if ( !WP_Filesystem() ) {
            ddtt_write_log( 'Failed to initialize WP_Filesystem' );
            return false;
        }

        // Check if the file exists
        $file_path = DDTT_MU_PLUGINS_DIR.$filename;
        $mu_plugin_exists = $wp_filesystem->exists( $file_path );

        // Add
        if ( $add_or_remove == 'add' ) {

            // Already exists?
            if ( $mu_plugin_exists ) {
                return false;
            }

            // Create the directory if it doesn't exist
            if ( !$wp_filesystem->is_dir( DDTT_MU_PLUGINS_DIR ) ) {
                $wp_filesystem->mkdir( DDTT_MU_PLUGINS_DIR );
            }

            // Path to Must-Use-Plugin file
            $mu_plugin_file = ABSPATH.DDTT_PLUGIN_FILES_PATH.$filename;

            // Add the file
            if ( $wp_filesystem->copy( $mu_plugin_file, $file_path, true ) ) {
                ddtt_write_log( '"'.$this->mu_plugin_name.'" must-use-plugin has been added.' );
                return true;
            } else {
                ddtt_write_log( '"'.$this->mu_plugin_name.'" must-use-plugin could not be added.' );
            }

        // Remove
        } elseif ( $add_or_remove == 'remove' ) {
            
            // Already gone?
            if ( !$mu_plugin_exists ) {
                return false;
            }

            // Remove the file
            if ( $wp_filesystem->delete( $file_path ) ) {
                ddtt_write_log( '"'.$this->mu_plugin_name.'" must-use-plugin has been removed.' );
                return true;
            } else {
                ddtt_write_log( '"'.$this->mu_plugin_name.'" must-use-plugin could not be deleted. To remove it, please remove the "'.$filename.'" file from "'.DDTT_MU_PLUGINS_DIR.'" via FTP or File Manager.' );
            }
        }

        // Did not work out
        return false;
    } // End add_remove_mu_plugin()


    /**
     * Enqueue scripts
     * Reminder to bump version number during testing to avoid caching
     *
     * @param string $screen
     * @return void
     */
    public function enqueue_scripts( $screen ) {
        // Get the options page slug
        $options_page = 'toplevel_page_'.DDTT_TEXTDOMAIN;

        // Allow for multisite
        if ( is_network_admin() ) {
            $options_page .= '-network';
        }

        // Are we on the options page?
        if ( $screen != $options_page ) {
            return;
        }

        // Handle
        $handle = DDTT_GO_PF.'s_error_script';

        // Feedback form and error code checker
        if ( ddtt_get( 'tab', '==', 'error-suppressing' ) ) {
            wp_register_script( $handle, DDTT_PLUGIN_JS_PATH.'error-suppressing.js', [ 'jquery' ], DDTT_VERSION );
            wp_enqueue_script( $handle );
            wp_enqueue_script( 'jquery' );
        }
    } // End enqueue_scripts()
}