<?php 
// Include the header
include 'header.php';

// Remove the clear query string if avail
if ( ddtt_get( 'clear_debug_log', '==', 'true' ) ) {
    ddtt_remove_qs_without_refresh( [ 'clear_debug_log' ] );
}

// New instance of logs class
$DDTT_LOGS = new DDTT_LOGS(); ?>

<table class="form-table">
    <?php $DDTT_LOGS->error_logs( 'options.php' ); ?>
</table>