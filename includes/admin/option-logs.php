<?php 
// Include the header
include 'header.php';

// Remove the clear query string if avail
if ( ddtt_get( 'clear_debug_log', '==', 'true' ) ) {
    ddtt_remove_qs_without_refresh( [ 'clear_debug_log' ] );
}

// Allowed HTML
$allowed_html = ddtt_wp_kses_allowed_html();

// New instance of logs class
$DDTT_LOGS = new DDTT_LOGS();

// Get the debug log location
if ( WP_DEBUG_LOG && WP_DEBUG_LOG !== true ) {
    $debug_loc = WP_DEBUG_LOG;
} else {
    $debug_loc =  DDTT_CONTENT_URL.'/debug.log';
}

// Replace the files if query string exists
if ( ddtt_get( 'clear_error_log', '==', 'true' ) ) {
    $DDTT_LOGS->replace_file( 'error_log', 'error_log', true );
}
if ( ddtt_get( 'clear_debug_log', '==', 'true' ) ) {
    $DDTT_LOGS->replace_file( $debug_loc, 'debug.log', true );
}
if ( ddtt_get( 'clear_admin_error_log', '==', 'true' ) ) {
    $DDTT_LOGS->replace_file( DDTT_ADMIN_URL.'/error_log', 'error_log', true );
}


/**
 * debug.log
 */
echo '<h2>/'.esc_attr( DDTT_CONTENT_URL ).'/debug.log</h2>';

// Contents?
if ( $debug_log = $DDTT_LOGS->file_exists_with_content( $debug_loc ) ) {

    // Filesize
    $debug_log_filesize = filesize( $debug_log );

    // Highlight args
    $highlight_args = $DDTT_LOGS->highlight_args();

    // Show the log
    echo wp_kses( $DDTT_LOGS->file_contents_with_clear_button( 'clear_debug_log', 'Debug Log', $debug_loc, $debug_log_filesize, true, $highlight_args, true ), $allowed_html );

// If none found
} else {
    if ( WP_DEBUG ) {
        echo '<em>Yay! No errors found on your debug.log!</em>';
    } else {
        echo '<em>Debug mode is disabled...</em>';
    }
}


/**
 * error_log
 */
echo '<br><br><br><br><h2>error_log</h2>';

// Contents?
if ( $error_log = $DDTT_LOGS->file_exists_with_content( 'error_log' ) ) {

    // Filesize
    $error_log_filesize = filesize( $error_log );

    // Show the log
    echo wp_kses( $DDTT_LOGS->file_contents_with_clear_button( 'clear_error_log', 'Error Log', 'error_log', $error_log_filesize , true, [], false ), $allowed_html );

// If none found
} else {
    echo '<em>No errors found!</em>';
}


/**
 * admin error_log
 */
echo '<br><br><br><br><h2>/'.esc_html( DDTT_ADMIN_URL ).'/error_log</h2>';

// Contents?

if ( $admin_error_log = $DDTT_LOGS->file_exists_with_content( DDTT_ADMIN_URL.'/error_log' ) ) {

    // Filesize
    $admin_error_log_filesize = filesize( $admin_error_log );
    
    // Show the log
    echo wp_kses( $DDTT_LOGS->file_contents_with_clear_button( 'clear_admin_error_log', 'Admin Error Log', DDTT_ADMIN_URL.'/error_log', $admin_error_log_filesize, true, [], false ), $allowed_html );

// If none found
} else {
    echo '<em>No errors found!</em>';
}


/**
 * User defined logs
 */
$user_defined_logs = get_option( DDTT_GO_PF.'log_files' );
if ( $user_defined_logs && !empty( $user_defined_logs ) ) {

    // Iter the logs
    foreach ( $user_defined_logs as $user_defined_log ) {

        // Add a header regardless
        echo '<br><br><br><br><h2>'.esc_html( $user_defined_log ).'</h2>';
        
        // Remove starting slash
        if ( str_starts_with( $user_defined_log, '/' ) ) {
            $user_defined_log = ltrim( $user_defined_log, '/' );
        }

        // Verify existence
        if ( $verified_log = $DDTT_LOGS->file_exists_with_content( $user_defined_log ) ) {
            
            // Filesize
            $verified_log_filesize = filesize( $verified_log );
            
            // Show the log
            echo wp_kses( ddtt_view_file_contents( $user_defined_log ), $allowed_html );

        // No content
        } else {
            echo '<em>No log content found!</em>';
        }
    }
}
?>