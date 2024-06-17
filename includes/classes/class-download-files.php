<?php
/**
 * Download files class
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Initiate the class
 */
new DDTT_DOWNLOAD_FILES;


/**
 * Main plugin class.
 */
class DDTT_DOWNLOAD_FILES {

    /**
	 * Constructor
	 */
	public function __construct() {

        // Admin only
        add_action( 'init', [ $this, 'post' ] );

	} // End __construct()


    /**
     * Check for $_POST
     * 
     * @return string
     * @since   1.0.0
     */
    public function post() {

        // Admins only
        if ( current_user_can( 'administrator' ) ) {

            // WP-CONFIG
            if ( isset( $_POST[ 'ddtt_download_wpconfig' ] ) ) {
                $this->download_root_file( 'wp-config.php', DDTT_GO_PF.'wpconfig_dl' );
            }

            // HTACCESS
            if ( isset( $_POST[ 'ddtt_download_htaccess' ] ) ) {
                $this->download_root_file( '.htaccess', DDTT_GO_PF.'htaccess_dl' );
            }
            
            // DEBUG.LOG
            if ( isset( $_POST[ 'ddtt_download_debug_log' ] ) ) {
                if ( WP_DEBUG_LOG && WP_DEBUG_LOG !== true ) {
                    $debug_loc = WP_DEBUG_LOG;
                } else {
                    $debug_loc =  DDTT_CONTENT_URL.'/debug.log';
                }
                $this->download_root_file( $debug_loc, DDTT_GO_PF.'debug_log_dl' );
            }
            
            // ADMIN ERROR_LOG
            if ( isset( $_POST[ 'ddtt_download_admin_error_log' ] ) ) {
                $this->download_root_file( DDTT_ADMIN_URL.'/error_log', DDTT_GO_PF.'admin_error_log_dl' );
            }
            
            // ROOT ERROR_LOG
            if ( isset( $_POST[ 'ddtt_download_error_log' ] ) ) {
                $this->download_root_file( 'error_log', DDTT_GO_PF.'error_log_dl' );
            }
            
            // FUNCTIONS.PHP
            if ( isset( $_POST[ 'ddtt_download_fx' ] ) ) {
                $this->download_root_file( 'functions.php', DDTT_GO_PF.'fx_dl', null, get_stylesheet_directory() );
            }

            // TESTING PLAYGROUND
            if ( isset( $_POST[ 'ddtt_download_testing_pg' ] ) ) {
                $this->download_plugin_file( 'TESTING_PLAYGROUND.php' );
            }
        }

    } // End post()


    /**
     * Download a root file
     *
     * @param string $filename
     * @param string $content_type
     * @param string $path
     * @return void
     */
    public function download_root_file( $filename, $nonce_action, $content_type = null, $path = null ) {
        // First verify the nonce
        if ( !isset( $_POST[ '_wpnonce' ] ) || !wp_verify_nonce( sanitize_text_field( wp_unslash ( $_REQUEST[ '_wpnonce' ] ) ), $nonce_action ) ) {
            exit( 'No naughty business please.' );
        }

        // File path
        if ( is_null( $path ) ) {
            $path = rtrim( ABSPATH, '/' );
        }

        // Read the WPCONFIG
        if ( is_readable( $path.'/'.$filename ) ) {
            $file = $path.'/'.$filename;
        } elseif ( is_readable( dirname( $path ).'/'.$filename ) ) {
            $file = dirname( $path ).'/'.$filename;
        } elseif ( is_readable( $filename ) ) {
            $file = $filename;
        } else {
            $file = false;
        }

        // Get the mime type
        if ( is_null( $content_type ) ) {
            $content_type = mime_content_type( $file );
        }

        // No file?
        if ( !$file ) {
            die( 'Something went wrong. Path: '.$file );
        }

        // Copy the file a temp location
        if ( strpos( $filename, '/' ) !== false) {
            $tmp_filename = strstr( $filename, '/' );
        } else {
            $tmp_filename = $filename;
        }
        $tmp_file = DDTT_PLUGIN_INCLUDES_PATH.'files/tmp/'.$tmp_filename;
        copy( $file, $tmp_file );

        // Define header information
        header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
        header( 'Content-Description: File Transfer' );
        header( 'Content-Type: '.$content_type );
        header( 'Content-Disposition: attachment; filename="'.basename( $tmp_file ).'"' );
        header( 'Content-Length: ' . filesize( $tmp_file ) );
        header( 'Expires: 0' );
        header( 'Pragma: public' );

        ob_clean();
        flush();
        
        // Read the file and write it to the output buffer
        readfile( $tmp_file, true );

        // Remove the temp file
        @unlink( $tmp_file );

        // Terminate from the script
        die();
    } // End download_root_file()


    /**
     * Download a testing playground
     *
     * @param string $filename
     * @return void
     */
    public function download_plugin_file( $filename ) {
        // First verify the nonce
        if ( !isset( $_POST[ '_wpnonce' ] ) || !wp_verify_nonce( sanitize_text_field( wp_unslash ( $_REQUEST[ '_wpnonce' ] ) ), DDTT_GO_PF.'testing_playground_dl' ) ) {
            exit( 'No naughty business please.' );
        }

        // The path
        $plugin_file_path = DDTT_PLUGIN_INCLUDES_PATH.$filename;
        
        // Check if it exists and is readable
        if ( is_readable( $plugin_file_path ) ) {
            $file = $plugin_file_path;

        // Else we failed
        } else {
            $file = false;
        }

        if ( !$file ) {
            die( 'Something went wrong. Path: '.$plugin_file_path );
        }

        // Copy the file a temp location
        $tmp_file = DDTT_PLUGIN_INCLUDES_PATH.'files/'.$filename;
        copy( $file, $tmp_file );

        // Define header information
        header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
        header( 'Content-Description: File Transfer' );
        header( 'Content-Type: text/x-php' );
        header( 'Content-Disposition: attachment; filename="'.basename( $tmp_file ).'"' );
        header( 'Content-Length: ' . filesize( $tmp_file ) );
        header( 'Expires: 0' );
        header( 'Pragma: public' );

        ob_clean();
        flush();
        
        // Read the file and write it to the output buffer
        readfile( $tmp_file, true );

        // Remove the temp file
        @unlink( $tmp_file );

        // Terminate from the script
        die();
    } // End ddtt_download_plugin_file()
}