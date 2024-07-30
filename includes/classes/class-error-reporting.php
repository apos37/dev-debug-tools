<?php
/**
 * Error Reporting class
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Initiate the class
 */
add_action( 'init', function() {
    (new DDTT_ERROR_REPORTING)->init();
} );


/**
 * Main plugin class.
 */
class DDTT_ERROR_REPORTING {

    /**
	 * Constructor
	 */
	public function init() {

        // Ajax
        add_action( 'wp_ajax_'.DDTT_GO_PF.'check_error_code', [ $this, 'ajax' ] );
        add_action( 'wp_ajax_nopriv_'.DDTT_GO_PF.'check_error_code', [ $this, 'must_login' ] );

        // Enqueue scripts
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

        // Are we sending fatal errors to Discord?
        if ( get_option( DDTT_GO_PF.'fatal_discord_enable' ) && get_option( DDTT_GO_PF.'fatal_discord_enable' ) == 1 && 
             get_option( DDTT_GO_PF.'fatal_discord_webhook' ) && get_option( DDTT_GO_PF.'fatal_discord_webhook' ) != '' ) {
            return register_shutdown_function( [ $this, 'send_fatal_errors_to_discord' ] );
        }

	} // End init()

    
    /**
     * Send fatal errors to Discord
     *
     * @return void
     */
    public function send_fatal_errors_to_discord() {
        // Get the last error
        $error = error_get_last();

        // Errors we are reporting
        $errors = [ E_ERROR, E_PARSE ];

        // Check if the last error was one of them
        if ( isset( $error[ 'type' ] ) && in_array( $error[ 'type' ], $errors ) ) {

            // Now get the webhook url
            $webhook = filter_var( get_option( DDTT_GO_PF.'fatal_discord_webhook' ), FILTER_SANITIZE_URL );
            if ( $webhook != '' ) {

                // The domain and website
                if ( !function_exists( 'ddtt_get_domain' ) ) {
                    return error_log( 'Could not find ddtt_get_domain() in send_fatal_errors_to_discord().' );
                }
                $domain = ddtt_get_domain();
                $website = get_bloginfo( 'name' );
                if ( !$website || $website == '' ) {
                    $website = $domain;
                }

                // The message
                $message = sanitize_textarea_field( $error[ 'message' ] );
                $message = str_replace( ABSPATH, '/', $message );
                if ( strpos( $message, 'Stack trace:' ) !== false ) {
                    $message = substr( $message, 0, strpos( $message, 'Stack trace:' ) );
                }
                $message = mb_strimwidth( $message, 0, 500, '...' );

                // The file
                $file = sanitize_text_field( $error[ 'file' ] );
                $file = str_replace( ABSPATH, '/', $file );

                // Discord args
                $args = [
                    'embed'          => true,
                    'title'          => 'New Error on '.$website,
                    'title_url'      => $domain,
                    'desc'           => $message,
                    'disable_footer' => false,
                    'fields' => [
                        [
                            'name'   => '--------------------',
                            'value'  => ' ',
                            'inline' => false
                        ],
                        [
                            'name'   => 'File Path',
                            'value'  => $file,
                            'inline' => false
                        ],
                        [
                            'name'   => 'Line',
                            'value'  => absint( $error[ 'line' ] ),
                            'inline' => false
                        ],
                        [
                            'name'   => 'Type',
                            'value'  => absint( $error[ 'type' ] ) === 4 ? 'PARSE ERROR' : 'FATAL ERROR',
                            'inline' => false
                        ]
                     ]
                ];

                // Send the message
                (new DDTT_DISCORD)->send( $webhook, $args );
            }
        }
    } // End send_fatal_errors_to_discord()


    /**
     * Add or remove the Must Use Plugin
     * USAGE: add_remove_mu_plugin( 'remove' )
     *
     * @param string $file_to_replace
     * @return boolean
     */
    public function add_remove_mu_plugin( $add_or_remove = 'add' ) {
        // Must-Use-Plugin filename
        $filename = '000-set-debug-level.php';

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
                ddtt_write_log( '"Debug Error Reporting Level" must-use-plugin has been added.' );
                return true;
            } else {
                ddtt_write_log( '"Debug Error Reporting Level" must-use-plugin could not be added.' );
            }

        // Remove
        } elseif ( $add_or_remove == 'remove' ) {
            
            // Already gone?
            if ( !$mu_plugin_exists ) {
                return false;
            }

            // Remove the file
            if ( $wp_filesystem->delete( $file_path ) ) {
                ddtt_write_log( '"Debug Error Reporting Level" must-use-plugin has been removed.' );
                return true;
            } else {
                ddtt_write_log( '"Debug Error Reporting Level" must-use-plugin could not be deleted. To remove it, please remove the "'.$filename.'" file from "'.DDTT_MU_PLUGINS_DIR.'" via FTP or File Manager.' );
            }
        }

        // Did not work out
        return false;
    } // End add_remove_mu_plugin()


    /**
     * Check the error code
     *
     * @return void
     */
    public function ajax() {
        // First verify the nonce
        if ( !wp_verify_nonce( sanitize_text_field( wp_unslash ( $_REQUEST[ 'nonce' ] ) ), DDTT_GO_PF.'check_error_code' ) ) {
            exit( 'No naughty business please.' );
        }

        // Get the code
        $code = isset( $_REQUEST[ 'code' ] ) ? absint( $_REQUEST[ 'code' ] ) : false;
        if ( $code ) {

            // Store the constants
            $constants = [];

            // Iter the codes
            $pot = 0;
            foreach ( array_reverse( str_split( decbin( $code ) ) ) as $bit ) {
                $constants[] = array_search( pow( 2, $pot ), get_defined_constants( true )[ 'Core' ] );
                $pot++;
            }

            if ( !empty( $constants ) ) {
                $result[ 'type' ] = 'success';
                $result[ 'constants' ] = $constants;
            } else {
                $result[ 'type' ] = 'error';
            }
                        
        // Otherwise return error
        } else {
            $result[ 'type' ] = 'error';
        }

        // Pass to ajax
        if ( !empty( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) && strtolower( sanitize_key( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) ) == 'xmlhttprequest' ) {
            echo wp_json_encode( $result );
        } else {
            header( 'Location: '.filter_var( $_SERVER[ 'HTTP_REFERER' ], FILTER_SANITIZE_URL ) );
        }

        // Stop
        die();
    } // End ajax()


    /**
     * What to do if they are not logged in
     *
     * @return void
     */
    public function must_login() {
        die();
    } // End must_login()


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
        $handle = DDTT_GO_PF.'error_script';

        // Feedback form and error code checker
        if ( ddtt_get( 'tab', '==', 'error' ) ) {
            wp_register_script( $handle, DDTT_PLUGIN_JS_PATH.'error-reporting.js', [ 'jquery' ], DDTT_VERSION );
            wp_localize_script( $handle, 'errorAjax', [ 
                'E_ALL'   => E_ALL,
                'ajaxurl' => admin_url( 'admin-ajax.php' ) 
            ] );
            wp_enqueue_script( $handle );
            wp_enqueue_script( 'jquery' );
        }
    } // End enqueue_scripts()
}