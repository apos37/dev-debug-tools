<?php
/**
 * The Gravity Form debug page
 */

// Include the header
include 'header.php';

// Debugs
$debugs = [ 'entry', 'feed', 'form', 'comment' ];
$debug_gf = [ 'entry', 'feed', 'form' ];

// Iter the debugs
foreach ( $debugs as $debug ) {

    // Check if the debug is in the query string
    if ( $debug_id = ddtt_get( 'debug_'.$debug ) ) {

        // Gravity forms
        if ( in_array( $debug, $debug_gf ) ) {
            
            // Make sure gravity forms is active
            if ( is_plugin_active( 'gravityforms/gravityforms.php' ) ) {

                // Add the title
                echo '<h3>'.esc_attr( ucwords( $debug ) ).' Meta Data:</h3>';

                // Get the array
                if ( $debug == 'form' ) {
                    $array = GFAPI::get_form( $debug_id );
                } elseif ( $debug == 'entry' ) {
                    $array = GFAPI::get_entry( $debug_id );
                } elseif ( $debug == 'feed' ) {
                    $array = GFAPI::get_feed( $debug_id );
                }

                // Print it
                ddtt_print_r( $array );

            // Gravity forms is inactive
            } else {

                // Display notice
                ddtt_print_r( 'Gravity Forms must be installed and activated.' );
            }

        // Comments
        } else {
            $array = get_comment( $debug_id );
            ddtt_print_r( $array );
        }

        // Add some space
        echo '<br><br>';
    }
}

// Space
echo '<br><br>';