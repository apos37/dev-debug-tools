<?php
/**
 * Plugin Name:         Suppress Debug Log Errors
 * Plugin URI:          https://github.com/apos37/dev-debug-tools
 * Description:         Suppresses errors that are reported to the debug log. Added via Developer Debug Tools plugin under Error Reporting tab.
 * Version:             1.0.0
 * Author:              Apos37
 * Author URI:          https://apos37.com/
 * Text Domain:         dev-debug-tools-suppress
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

// Is enabled?
if ( get_option( 'ddtt_suppress_errors_enable' ) ) {
    
    
    /**
     * Check if we have suppressed errors
     */
    if ( ddtt_get_suppressed_errors() ) {
        
        
        /**
         * Add our own error handler
         */
        add_action( 'muplugins_loaded', 'ddtt_error_suppressor', 1 );


        /**
         * Fire the handler
         *
         * @return void
         */
        function ddtt_error_suppressor() {
            set_error_handler( 'ddtt_errors_to_suppress' );
            register_shutdown_function( 'ddtt_restore_error_handler' );
        } // End ddtt_error_suppressor()

        
        /**
         * Supress
         *
         * @param [type] $errno
         * @param string $errstr
         * @param [type] $errfile
         * @param [type] $errline
         * @return boolean
         */
        function ddtt_errors_to_suppress( $errno, $errstr, $errfile, $errline ) {
            // Handle our suppressed errors
            $errors = ddtt_get_suppressed_errors();
            foreach ( $errors as $err ) {
                if ( strpos( $errstr, $err[ 'string' ] ) !== false && $err[ 'status' ] == 'active' ) {
                    // error_log( "Suppressing warning: $errstr" );
                    return true;
                }
            }

            // For all other errors, fall back to the default error handler
            return false;
        } // End ddtt_errors_to_suppress()

        
        /**
         * Restore the error handler
         *
         * @return void
         */
        function ddtt_restore_error_handler() {
            restore_error_handler();
        } // End ddtt_restore_error_handler()
    }
}


/**
 * Get suppressed errors
 *
 * @return array|false
 */
function ddtt_get_suppressed_errors() {
    return get_option( 'ddtt_suppressed_errors' );
} // End ddtt_get_suppressed_errors()