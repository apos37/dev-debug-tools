<?php 
/**
 * Change the max file size for the debug log viewer. Default is 2 MB.
 *
 * @param int $bytes
 * @return int
 */
function ddtt_debug_log_max_filesize_filter( $bytes ) {
    // Value is in bytes
    // 1 MB = 1048576 bytes, 2 MB = 2097152 bytes
    // You can use http://byteconvert.org/ for converting to bytes
    return 1048576;
} // End ddtt_debug_log_max_filesize_filter()

add_filter( 'ddtt_debug_log_max_filesize', 'ddtt_debug_log_max_filesize_filter' );