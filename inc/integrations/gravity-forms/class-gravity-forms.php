<?php
/**
 * Gravity Forms Integration
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Gravity_Forms {
    
    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?Gravity_Forms $instance = null;


    /**
     * Get the singleton instance
     *
     * @return self
     */
    public static function instance() : self {
        return self::$instance ??= new self();
    } // End instance()


    /**
     * Constructor
     */
    private function __construct() {
        add_filter( 'ddtt_log_viewer_regex', [ $this, 'regex' ], 10, 3 );
        add_filter( 'ddtt_easy_log_parse_error', [ $this, 'parse_error' ], 10, 3 );
    } // End __construct()


    /**
     * Add Gravity Forms specific regex to the log viewer
     *
     * @param null|string $regex The existing regex patterns.
     * @param string $abs_path The absolute path of the log file.
     * @param string $subsection The subsection of the log.
     * @return null|string
     */
    public function regex( $regex, $abs_path, $subsection ) : null|string {
        if ( strpos( $abs_path, '/uploads/gravity_forms/logs/' ) !== false ) {
            return '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d+ - DEBUG -->/';
        }
        return $regex;
    } // End regex()


    /**
     * Parse Gravity Forms log entries
     *
     * @param null|array $parsed_error The parsed error data.
     * @param string $first The first line of the log entry.
     * @param string $subsection The subsection of the log.
     * @return null|array
     */
    public function parse_error( $parsed_error, $line, $subsection ) : null|array {
        $core_subsections = [ 'debug', 'error', 'admin-error', 'activity' ];
        if ( in_array( $subsection, $core_subsections, true ) ) {
            return $parsed_error; // Only process custom subsections
        }

        if ( strpos( $line, 'DEBUG -->' ) !== false ) {
            // Match: datetime - DEBUG --> Type message... (possibly with body: ...)
            preg_match( '/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d+) - DEBUG --> ([^\s]+)?\s?(.*)$/', $line, $matches );

            $datetime = !empty( $matches[1] ) ? Helpers::convert_timezone( $matches[1], null, 'UTC' ) : ''; // Convert datetime to UTC because it's already stored in local time
            $message = $matches[3] ?? '';
            $stack   = '';

            // If there's a body: segment, separate it
            if ( preg_match( '/\bbody:\s*(.+)$/i', $message, $body_matches ) ) {
                $stack   = $body_matches[1];
                // Remove body: part from message
                $message = trim( preg_replace( '/\bbody:\s*.+$/i', '', $message ) );
            }

            $parsed_error = [
                'datetime'  => $datetime,
                'type'      => $matches[2] ?? '',
                'message'   => $message,
                'file'      => '',
                'line'      => '',
                'source'    => '',
                'stack'     => $stack
            ];

            return $parsed_error;
        }
        return null;
    } // End parse_error()


    /**
     * Prevent cloning and unserializing
     */
    public function __clone() {}
    public function __wakeup() {}

}


Gravity_Forms::instance();