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
     * We will be ignoring nonce warnings here as nonce check is done in the download_root_file() and download_plugin_file() functions
     * 
     * @return string
     * @since   1.0.0
     */
    public function post() {
        // Admins only
        if ( current_user_can( 'administrator' ) ) {

            // ACTIVITY LOG
            if ( isset( $_POST[ 'ddtt_download_activity_log' ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $this->download_root_file( 'activity.log', DDTT_GO_PF.'activity_log_dl', null, (new DDTT_ACTIVITY)->log_directory_path );
            }

            // WP-CONFIG
            if ( isset( $_POST[ 'ddtt_download_wpconfig' ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $this->download_root_file( 'wp-config.php', DDTT_GO_PF.'wpconfig_dl' );
            }

            // HTACCESS
            if ( isset( $_POST[ 'ddtt_download_htaccess' ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $this->download_root_file( '.htaccess', DDTT_GO_PF.'htaccess_dl' );
            }
            
            // DEBUG.LOG
            if ( isset( $_POST[ 'ddtt_download_debug_log' ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $debug_log_path = get_option( DDTT_GO_PF.'debug_log_path' );
                if ( $debug_log_path && $debug_log_path != '' ) {
                    $debug_loc = sanitize_text_field( $debug_log_path );
                } elseif ( WP_DEBUG_LOG && WP_DEBUG_LOG !== true ) {
                    $debug_loc = WP_DEBUG_LOG;
                } else {
                    $debug_loc =  DDTT_CONTENT_URL.'/debug.log';
                }
                $this->download_root_file( $debug_loc, DDTT_GO_PF.'debug_log_dl',  );
            }
            
            // ADMIN ERROR_LOG
            if ( isset( $_POST[ 'ddtt_download_admin_error_log' ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $this->download_root_file( DDTT_ADMIN_URL.'/error_log', DDTT_GO_PF.'admin_error_log_dl' );
            }
            
            // ROOT ERROR_LOG
            if ( isset( $_POST[ 'ddtt_download_error_log' ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $this->download_root_file( 'error_log', DDTT_GO_PF.'error_log_dl' );
            }
            
            // FUNCTIONS.PHP
            if ( isset( $_POST[ 'ddtt_download_fx' ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $this->download_root_file( 'functions.php', DDTT_GO_PF.'fx_dl', null, get_stylesheet_directory() );
            }

            // TESTING PLAYGROUND
            if ( isset( $_POST[ 'ddtt_download_testing_pg' ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
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
        if ( !isset( $_REQUEST[ '_wpnonce' ] ) || !wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST[ '_wpnonce' ] ) ), $nonce_action ) ) {
            exit( 'No naughty business here please.' );
        }

        // Initialize WP_Filesystem
        if ( !function_exists( 'WP_Filesystem' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        global $wp_filesystem;
        if ( !WP_Filesystem() ) {
            exit( 'Failed to initialize WP_Filesystem' );
        }

        // File path
        if ( is_null( $path ) ) {
            $path = rtrim( ABSPATH, '/' );
        }

        // Read the file
        $file = false;
        if ( $wp_filesystem->is_readable( $path . '/' . $filename ) ) {
            $file = $path . '/' . $filename;
        } elseif ( $wp_filesystem->is_readable( dirname( $path ) . '/' . $filename ) ) {
            $file = dirname( $path ) . '/' . $filename;
        } elseif ( $wp_filesystem->is_readable( $filename ) ) {
            $file = $filename;
        }

        // No file?
        if ( !$file ) {
            die( 'Something went wrong. Path: ' . esc_html( $file ) );
        }

        // Get the mime type
        if ( is_null( $content_type ) ) {
            $content_type = mime_content_type( $file );
        }

        // Copy the file to a temp location
        if ( strpos( $filename, '/' ) !== false ) {
            $tmp_filename = basename( $filename );
        } else {
            $tmp_filename = $filename;
        }
        $tmp_file = DDTT_PLUGIN_INCLUDES_PATH.'files/tmp/'.$tmp_filename;

        // Copy the file using WP_Filesystem
        if ( !$wp_filesystem->copy( $file, $tmp_file, true ) ) {
            die( 'Failed to copy file to temporary location.' );
        }

        // Define header information
        header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
        header( 'Content-Description: File Transfer' );
        header( 'Content-Type: '.$content_type );
        header( 'Content-Disposition: attachment; filename="'.basename( $tmp_file ).'"' );
        header( 'Content-Length: '.filesize( $tmp_file ) );
        header( 'Expires: 0' );
        header( 'Pragma: public' );

        // Flush
        ob_clean();
        flush();
        
        // Read the file and write it to the output buffer
        $file_content = $wp_filesystem->get_contents( $tmp_file );
        if ( $file_content !== false ) {
            echo $file_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        // Remove the temp file
        $wp_filesystem->delete( $tmp_file );

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

        // Initialize WP_Filesystem
        if ( !function_exists( 'WP_Filesystem' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        global $wp_filesystem;
        if ( !WP_Filesystem() ) {
            exit( 'Failed to initialize WP_Filesystem' );
        }

        // The path
        $plugin_file_path = DDTT_PLUGIN_INCLUDES_PATH.$filename;
        
        // Check if it exists and is readable
        if ( $wp_filesystem->is_readable( $plugin_file_path ) ) {
            $file = $plugin_file_path;
        } else {
            $file = false;
        }
        
        // Validate
        if ( !$file ) {
            die( 'Something went wrong. Path: '.esc_html( $plugin_file_path ) );
        }

        // Copy the file a temp location
        $tmp_file = DDTT_PLUGIN_INCLUDES_PATH.'files/'.$filename;
        if ( !$wp_filesystem->copy( $file, $tmp_file, true ) ) {
            die( 'Failed to copy file to temporary location.' );
        }

        // Define header information
        header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
        header( 'Content-Description: File Transfer' );
        header( 'Content-Type: text/x-php' );
        header( 'Content-Disposition: attachment; filename="'.basename( $tmp_file ).'"' );
        header( 'Content-Length: ' . filesize( $tmp_file ) );
        header( 'Expires: 0' );
        header( 'Pragma: public' );

        // Flush
        ob_clean();
        flush();
        
        // Read the file and write it to the output buffer
        $file_content = $wp_filesystem->get_contents( $tmp_file );
        if ( $file_content !== false ) {
            echo $file_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        // Remove the temp file
        $wp_filesystem->delete( $tmp_file );

        // Terminate from the script
        die();
    } // End ddtt_download_plugin_file()
}