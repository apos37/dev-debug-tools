<?php

/**
 * Deprecated functions
 */

if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Increase Debug Test Number by 1
 *
 * @deprecated Option 'ddtt_test_number' removed; function kept for backward compatibility.
 * @return void
 */
function ddtt_increase_test_number() {
    _deprecated_function( __FUNCTION__, '3.0', 'Option "ddtt_test_number" removed; no longer functional.' );
    // Function intentionally left blank to avoid fatal errors in legacy code.
} // End ddtt_increase_test_number()


/**
 * Get current URL with query string
 *
 * @deprecated Use `home_url()` or `get_permalink()` instead.
 * @return false
 */
function ddtt_get_current_url() {
    _deprecated_function( __FUNCTION__, '3.0', 'home_url() or get_permalink()' );
    return false;
} // End ddtt_get_current_url()


/**
 * Get $_GET superglobal
 *
 * @deprecated Use `$_GET` directly instead.
 * @return false
 */
function ddtt_get() {
    _deprecated_function( __FUNCTION__, '3.0', 'Use $_GET instead.' );
    return false;
} // End ddtt_get()


