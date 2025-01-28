<?php 
// Include the header
include 'header.php';

// Allowed HTML
$allowed_html = ddtt_wp_kses_allowed_html();

// New instance of logs class
$DDTT_ACTIVITY = new DDTT_ACTIVITY();
$DDTT_LOGS = new DDTT_LOGS();

// Get the activity log location
$activity_log_path = $DDTT_ACTIVITY->log_file_path;

// Replace the files if query string exists
if ( ddtt_get( 'clear_activity_log', '==', 'true' ) ) {
    ddtt_remove_qs_without_refresh( [ 'clear_activity_log' ] );
    $DDTT_LOGS->replace_file( $activity_log_path, 'activity.log', true );
}


/**
 * activity.log
 */
echo '<h2>'.esc_attr( str_replace( ABSPATH, '', $activity_log_path ) ).'</h2>';

// Contents?
if ( $activity_log = $DDTT_LOGS->file_exists_with_content( $activity_log_path ) ) {

    // Filesize
    $activity_log_filesize = filesize( $activity_log );

    // Highlight args
    $highlight_args = $DDTT_ACTIVITY->highlight_args();

    // Show the log
    echo wp_kses( $DDTT_LOGS->file_contents_with_clear_button( 'clear_activity_log', 'Activity Log', $activity_log_path, $activity_log_filesize, true, $highlight_args, true ), $allowed_html );

// If none found
} else {
    if ( $DDTT_ACTIVITY->is_logging_activity() ) {
        echo '<em>No activity has been logged to your activity.log!</em>';
    } else {
        echo '<em>Activity logging is disabled...</em>';
    }
}