<?php
/**
 * Functions that can be used globally.
 */

if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Determine if current user is a developer or admin email.
 *
 * @param bool $email  Return developer email string instead of checking user.
 * @param bool $array  Return developer emails as array if $email is true.
 *
 * @return bool|string|array
 */
if ( ! function_exists( 'ddtt_is_dev' ) ) {
    function ddtt_is_dev( $id, $array = false ) : bool|string|array {
        if ( is_bool( $id ) ) {

            // Deprecated - use Helpers::get_devs( true ) instead
            // \Apos37\DevDebugTools\Helpers::write_log( 'ddtt_is_dev(): returning dev emails is deprecated from version 3.0 - please use ddtt_get_devs( true )', 'Developer Debug Tools: ', true );
            _doing_it_wrong(
                __FUNCTION__,
                'Passing $email or $array is no longer supported. Use ddtt_get_devs( true ) instead, which returns an array of developer emails. You may also use ddtt_get_devs( false ) to get an array of developer IDs.',
                '3.0'
            );

            $devs = \Apos37\DevDebugTools\Helpers::get_devs( true );
            if ( $array ) {
                return $devs;
            } else {
                return ! empty( $devs ) ? implode( ', ', $devs ) : '';
            }
        }

        return \Apos37\DevDebugTools\Helpers::is_dev( $id );
    }
} // End ddtt_is_dev()


/**
 * Get developer user IDs or emails.
 *
 * @param bool $return_emails  Return emails instead of user IDs.
 *
 * @return array
 */
if ( ! function_exists( 'ddtt_get_devs' ) ) {
    function ddtt_get_devs( $return_emails = false ) : array {
        return \Apos37\DevDebugTools\Helpers::get_devs( $return_emails );
    }
} // End ddtt_get_devs()


/**
 * Safe print_r with <pre> tags.
 * Displays output only for developer or specified user IDs.
 *
 * @param mixed $var                         Data to print.
 * @param string|int|bool|null $left_margin  Left margin (px, string value, or true for 200px).
 * @param int|array|null $user_id            Single user ID or array of IDs allowed to see the output.
 * @param bool $write_bool                   Convert boolean to "TRUE"/"FALSE".
 *
 * @return void
 */
if ( ! function_exists( 'ddtt_print_r' ) ) {
    function ddtt_print_r( $var, $left_margin = null, $user_id = null, $write_bool = true ) {
        return \Apos37\DevDebugTools\Helpers::print_r( $var, $left_margin, $user_id, $write_bool );
    }
} // End ddtt_print_r()


/**
 * Short alias for ddtt_print_r()
 */
if ( ! function_exists( 'dpr' ) ) {
    function dpr( $var, $left_margin = null, $user_id = null, $write_bool = true ) {
        return \Apos37\DevDebugTools\Helpers::print_r( $var, $left_margin, $user_id, $write_bool );
    }
} // End dpr()


/**
 * Log a message or variable to debug.log.
 *
 * @param mixed        $log             Data to log.
 * @param bool|string  $prefix          Prefix text or true for default.
 * @param bool         $backtrace       Include file/line backtrace.
 * @param bool         $full_stacktrace Include full stack trace.
 *
 * @return void
 */
if ( ! function_exists( 'ddtt_write_log' ) ) {
    function ddtt_write_log( $log, $prefix = true, $backtrace = false, $full_stacktrace = false ) {
        return \Apos37\DevDebugTools\Helpers::write_log( $log, $prefix, $backtrace, $full_stacktrace );
    }
} // End ddtt_write_log()


/**
 * Short alias for ddtt_write_log()
 */
if ( ! function_exists( 'dwl' ) ) {
    function dwl( $log, $prefix = true, $backtrace = false, $full_stacktrace = false ) {
        return \Apos37\DevDebugTools\Helpers::write_log( $log, $prefix, $backtrace, $full_stacktrace );
    }
} // End dwl()


/**
 * Logs a comma-separated string or array of functions that have been called to get to the current point in code.
 *
 * @param string|null $ignore_class Optional class to ignore.
 * @param int         $skip_frames  Number of frames to skip.
 * @param bool        $pretty       Pretty-print the backtrace.
 *
 * @return void
 */
if ( ! function_exists( 'ddtt_backtrace' ) ) {
    function ddtt_backtrace( $ignore_class = null, $skip_frames = 0, $pretty = true ) {
        return \Apos37\DevDebugTools\Helpers::backtrace( $ignore_class, $skip_frames, $pretty );
    }
} // End ddtt_backtrace()


/**
 * Convert var_dump to string.
 * Useful for printing errors in CSV exports.
 *
 * @param mixed $var  Variable to be dumped and converted to string.
 * 
 * @return string
 */
if ( ! function_exists( 'ddtt_var_dump_to_string' ) ) {
    function ddtt_var_dump_to_string( $var ) : string {
        return \Apos37\DevDebugTools\Helpers::var_dump_to_string( $var );
    }
} // End ddtt_var_dump_to_string()


/**
 * Add a JS alert for debugging.
 *
 * @param string   $msg      Message to alert.
 * @param int|null $user_id  Optional user ID restriction.
 * @param bool     $echo     Whether to echo (true) or return (false) the script.
 * 
 * @return void|string
 */
if ( ! function_exists( 'ddtt_alert' ) ) {
    function ddtt_alert( $msg, $user_id = null, $echo = true ) {
        return \Apos37\DevDebugTools\Helpers::alert( $msg, $user_id, $echo );
    }
} // End ddtt_alert()


/**
 * Console log with PHP.
 *
 * @param string|array|object $msg      Message, array, or object to log.
 * @param int|null            $user_id  Optional user ID restriction.
 * @param bool                $echo     Whether to echo (true) or return (false) the script.
 * 
 * @return void|string
 */
if ( ! function_exists( 'ddtt_console_log' ) ) {
    function ddtt_console_log( $msg, $user_id = null, $echo = true ) {
        return \Apos37\DevDebugTools\Helpers::console( $msg, $user_id, $echo );
    }
} // End ddtt_console_log()


/**
 * Short alias for ddtt_console_log()
 */
if ( ! function_exists( 'ddtt_console' ) ) {
    function ddtt_console( $msg, $user_id = null, $echo = true ) {
        return \Apos37\DevDebugTools\Helpers::console( $msg, $user_id, $echo );
    }
} // End ddtt_console()


/**
 * Debug $_POST via Email.
 * USAGE: ddtt_debug_form_post( 'yourname@youremail.com', 2 );
 *
 * @param string  $email       Recipient email address.
 * @param int     $test_number Optional test number appended to subject.
 * @param string  $subject     Email subject prefix.
 * 
 * @return void|false
 */
if ( ! function_exists( 'ddtt_debug_form_post' ) ) {
    function ddtt_debug_form_post( $email, $test_number = 1, $subject = 'Test Form ' ){
        return \Apos37\DevDebugTools\Helpers::debug_form_post( $email, $test_number, $subject );
    }
} // End ddtt_debug_form_post()


/**
 * Check if a user has a role
 *
 * @param string $role    Role to check for.
 * @param int    $user_id Optional user ID, defaults to current user.
 *
 * @return bool
 */
if ( ! function_exists( 'ddtt_has_role' ) ) {
    function ddtt_has_role( $role, $user_id = null ) : bool {
        return \Apos37\DevDebugTools\Helpers::has_role( $role, $user_id );
    }
} // End ddtt_has_role()


/**
 * Display an error message for admins only
 *
 * @param string  $msg          Error message to display.
 * @param bool    $include_pre  Whether to prepend 'ADMIN ERROR:' prefix.
 * @param bool    $br           Whether to include a line break before the message.
 * @param bool    $hide_error   Whether to hide the error message.
 *
 * @return string
 */
if ( ! function_exists( 'ddtt_admin_error' ) ) {
    function ddtt_admin_error( $msg, $include_pre = true, $br = true, $hide_error = false ) : string {
        return \Apos37\DevDebugTools\Helpers::admin_error( $msg, $include_pre, $br, $hide_error );
    }
} // End ddtt_admin_error()


/**
 * Time how long it takes to process code (in seconds)
 * $start = ddtt_start_timer();
 * run functions
 * $total_time = ddtt_stop_timer( $start );
 * $sec_per_link = round( ( $total_time / $count_links ), 2 );
 *
 * @param string $timeout_seconds Optional cURL timeout in seconds, defaults to '300'.
 * @return float|bool             Start time in seconds as float, or false on failure.
 */
if ( ! function_exists( 'ddtt_start_timer' ) ) {
    function ddtt_start_timer( $timeout_seconds = '300' ) : float|bool {
        return \Apos37\DevDebugTools\Helpers::start_timer( $timeout_seconds );
    }
} // End ddtt_start_timer()


/**
 * Stop timing - Use with ddtt_start_timer() above
 *
 * @param float   $start        Start time from ddtt_start_timer().
 * @param boolean $timeout      Whether to restore cURL timeout, default true.
 * @param boolean $milliseconds Whether to return result in milliseconds, default false.
 * @return float                Elapsed time in seconds or milliseconds (rounded).
 */
if ( ! function_exists( 'ddtt_stop_timer' ) ) {
    function ddtt_stop_timer( $start, $timeout = true, $milliseconds = false ) : float {
        return \Apos37\DevDebugTools\Helpers::stop_timer( $start, $timeout, $milliseconds );
    }
} // End ddtt_stop_timer()


/**
 * Get just the domain without the https://
 * Option to capitalize the first part, remove extension, and include protocol
 *
 * @param bool $capitalize Capitalize the first part of the domain.
 * @param bool $remove_ext Remove the domain extension.
 * @param bool $incl_protocol Include the protocol (http:// or https://).
 * @return string
 */
if ( ! function_exists( 'ddtt_get_domain' ) ) {
    function ddtt_get_domain( $capitalize = false, $remove_ext = false, $incl_protocol = false ) : string {
        return \Apos37\DevDebugTools\Helpers::get_domain( $capitalize, $remove_ext, $incl_protocol );
    }
} // End ddtt_get_domain()


/**
 * Check if we are on a specific website
 * May use partial words, such as "example" for example.com
 *
 * @param string $site_to_check The site substring to check for in the current domain.
 * @return bool
 */
if ( ! function_exists( 'ddtt_is_site' ) ) {
    function ddtt_is_site( $site_to_check ) : bool {
        return \Apos37\DevDebugTools\Helpers::is_site( $site_to_check );
    }
} // End ddtt_is_site()


/**
 * Convert time to elapsed string
 *
 * @param string|DateTime $datetime Date/time to compare from.
 * @param boolean         $full     Whether to show full time difference or just the largest unit.
 * @return string
 */
if ( ! function_exists( 'ddtt_time_elapsed_string' ) ) {
    function ddtt_time_elapsed_string( $datetime, $full = false ) : string {
        return \Apos37\DevDebugTools\Helpers::time_elapsed_string( $datetime, $full );
    }
} // End ddtt_time_elapsed_string()


/**
 * Get plugins data fresh and recache
 *
 * @return array
 */
if ( ! function_exists( 'ddtt_get_plugins_data' ) ) {
    function ddtt_get_plugins_data() : array {
        return \Apos37\DevDebugTools\Helpers::get_plugins_data();
    }
} // End ddtt_get_plugins_data()


/**
 * Redact sensitive info "View Sensitive Info" option is off
 * 
 * @param string $string   String to maybe redact.
 * @param bool   $abspath  Whether to redact absolute paths.
 *
 * @return string
 */
if ( ! function_exists( 'ddtt_maybe_redact' ) ) {
    function ddtt_maybe_redact( $string, $abspath = false ) : string {
        return \Apos37\DevDebugTools\Helpers::maybe_redact( $string, $abspath );
    }
} // End ddtt_maybe_redact()