<?php
/**
 * Plugin Name:         Debug Error Reporting Level
 * Plugin URI:          https://github.com/apos37/dev-debug-tools
 * Description:         Sets the error reporting level for debugging. Added via Developer Debug Tools plugin under Error Types tab.
 * Version:             1.0.0
 * Author:              Apos37
 * Author URI:          https://apos37.com/
 * Text Domain:         dev-debug-tools-err
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

// Is enabled?
if ( get_option( 'ddtt_error_enable' ) ) {
    
    // Check for settings made from Developer Debug Tools
    if ( $constants = get_option( 'ddtt_error_constants' ) ) {
        $constants = filter_var_array( $constants, FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        // Store error code here
        $errors = 0;

        // First check for E_ALL
        if ( array_key_exists( 'E_ALL', $constants ) ) {
            $errors = E_ALL;

        // Others
        } else {

            // Verify some
            if ( !empty( $constants ) ) {

                // Convert to string
                $constants = array_keys( $constants );

                // Convert to constants
                foreach ( $constants as $constant ) {
                    try {
                        $errors += constant( $constant );
                    } catch ( Exception $e ) {
                        error_log( $e->getMessage() );
                    }
                }
            }
        }

        // Set them
        if ( $errors ) {
            error_reporting( $errors );
        }
    }
}