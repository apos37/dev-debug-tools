<?php 
if ( is_plugin_active( 'dev-debug-tools/dev-debug-tools.php' ) ) {
    add_filter( 'ddtt_debug_log_max_filesize', 'ddtt_debug_log_max_filesize_filter' );
    function ddtt_debug_log_max_filesize_filter( $bytes ) {
        // Value is in bytes
        // 1 MB = 1048576 bytes, 2 MB = 2097152 bytes
        // You can use http://byteconvert.org/ for converting to bytes
        return 1048576;
    }
}