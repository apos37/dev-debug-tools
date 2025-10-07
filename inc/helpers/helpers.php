<?php
/**
 * Helpers
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Helpers {

    /**
     * Get the developer emails or IDs
     *
     * @return array
     */
    public static function get_devs( $return_emails = false ) : array {
        // delete_option( 'ddtt_developers' );
        $developer_ids = filter_var_array( get_option( 'ddtt_developers', [] ), FILTER_VALIDATE_INT );
        if ( empty( $developer_ids ) ) {
            
            // If we have old comma-separated emails, convert to user IDs and replace option
            $email_string = trim( sanitize_text_field( get_option( 'ddtt_dev_email' ) ) );
            if ( ! empty( $email_string ) ) {

                $email_array = array_filter( array_map( 'sanitize_email', explode( ',', str_replace( ' ', '', $email_string ) ) ) );
                foreach ( $email_array as $email ) {
                    if ( $user = get_user_by( 'email', $email ) ) {
                        $developer_ids[] = absint( $user->ID );
                    }
                }

                // if ( ! empty( $developer_ids ) ) {
                //     update_option( 'ddtt_developers', array_values( array_unique( $developer_ids ) ) );
                //     delete_option( 'ddtt_dev_email' );
                // }

                if ( $return_emails ) {
                    return $email_array;
                }
            }
        }

        if ( empty( $developer_ids ) ) {
            $admins = get_users( [ 'role' => 'administrator', 'fields' => [ 'ID', 'display_name', 'user_email' ] ] );
            $developer_emails = [];
            foreach ( $admins as $admin ) {
                if ( $return_emails ) {
                    $developer_emails[] = $admin->user_email;
                } else {
                    $developer_ids[] = $admin->ID;
                }
            }
            if ( $return_emails ) {
                return $developer_emails;
            }
        }

        if ( ! empty( $developer_ids ) && $return_emails ) {
            $emails = [];
            foreach ( $developer_ids as $id ) {
                if ( $user = get_user_by( 'id', $id ) ) {
                    $emails[] = $user->user_email;
                }
            }
            return $emails;
        }

        return $developer_ids;
    } // End get_devs()


    /**
     * Determine if current user is a developer.
     *
     * @return bool
     */
    public static function is_dev( $user_id = null ) : bool {
        $developer_ids = self::get_devs();
        $current_user_id = is_null( $user_id ) ? get_current_user_id() : $user_id;
        return in_array( $current_user_id, $developer_ids );
    } // End is_dev()


    /**
     * Check if current user is admin only (not a developer)
     *
     * @param int|null $user_id Optional user ID, defaults to current user.
     * @return bool
     */
    public static function is_admin_only( $user_id = null ) : bool {
        if ( is_null( $user_id ) ) {
            $user_id = get_current_user_id();
        }
        return ( current_user_can( 'administrator' ) && ! self::is_dev( $user_id ) );
    } // End is_admin_only()


    /**
     * Check if current user has access (developer or admin)
     *
     * @return bool
     */
    public static function has_access() : bool {
        if ( self::is_dev() ) {
            return true;
        }

        $dev_access_only = get_option( 'ddtt_dev_access_only', false );
        if ( ! $dev_access_only && current_user_can( 'administrator' ) ) {
            return true;
        }

        return false;
    } // End has_access()


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
    public static function print_r( $var, $left_margin = null, $user_id = null, $write_bool = true ) {
        $current_user_id = get_current_user_id();

        // Permission check
        if ( $user_id !== null ) {
            if ( is_array( $user_id ) ) {
                if ( ! in_array( $current_user_id, array_map( 'intval', $user_id ), true ) ) {
                    return;
                }
            } elseif ( intval( $user_id ) !== $current_user_id ) {
                return;
            }
        } elseif ( ! Helpers::is_dev() ) {
            return;
        }

        // Margin calculation
        if ( is_numeric( $left_margin ) ) {
            $margin = intval( $left_margin ) . 'px';
        } elseif ( is_string( $left_margin ) ) {
            $margin = sanitize_text_field( $left_margin );
        } elseif ( $left_margin === true ) {
            $margin = '180px';
        } else {
            $margin = '0';
        }

        // Boolean conversion
        if ( $write_bool && is_bool( $var ) ) {
            $var = $var ? 'TRUE' : 'FALSE';
        }

        // Output
        echo '<pre class="ddtt_print_r" style="margin-left:' . esc_attr( $margin ) . ';overflow-x:unset;">';
        wp_kses_post( print_r( $var ) ); // phpcs:ignore
        echo '</pre>';
    } // End print_r()


    /**
     * Log a message or variable to debug.log
     *
     * @param mixed        $log             Data to log.
     * @param bool|string  $prefix          Prefix text or true for default.
     * @param bool         $backtrace       Include file/line backtrace.
     * @param bool         $full_stacktrace Include full stack trace.
     *
     * @return void
     */
    public static function write_log( $log, $prefix = true, $backtrace = false, $full_stacktrace = false ) {
        if ( ! defined( 'WP_DEBUG' ) || WP_DEBUG !== true ) {
            return;
        }

        // Determine prefix
        if ( is_bool( $prefix ) && $prefix ) {
            $pf = 'DDTT LOG: ';
        } elseif ( is_string( $prefix ) && $prefix !== '' ) {
            $pf = sanitize_text_field( $prefix );
        } else {
            $pf = '';
        }

        // Prepare log content
        if ( is_array( $log ) || is_object( $log ) ) {
            $log_output = print_r( $log, true ); // phpcs:ignore
        } else {
            $log_output = (string) $log;
        }

        // Add backtrace info
        if ( $backtrace ) {
            $trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, $full_stacktrace ? 0 : 1 ); // phpcs:ignore
            if ( ! empty( $trace[ 0 ][ 'file' ] ) && ! empty( $trace[ 0 ][ 'line' ] ) ) {
                $log_output .= ' in ' . $trace[ 0 ][ 'file' ] . ' on line ' . $trace[ 0 ][ 'line' ];
            }

            // Full stack trace
            if ( $full_stacktrace && count( $trace ) > 1 ) {
                $stack = [ 'Stack trace:' ];
                foreach ( $trace as $key => $bt ) {
                    $file = isset( $bt[ 'file' ] ) ? $bt[ 'file' ] : '[internal function]';
                    $line = isset( $bt[ 'line' ] ) ? $bt[ 'line' ] : '?';
                    $func = isset( $bt[ 'function' ] ) ? $bt[ 'function' ] : '?';
                    $stack[] = '#' . $key . ' ' . $file . '(' . $line . '): ' . $func . '()';
                }
                $log_output .= "\n" . implode( "\n", $stack );
            }
        }

        error_log( $pf . $log_output ); // phpcs:ignore
    } // End write_log()


    /**
     * Logs a comma-separated string or array of functions that have been called to get to the current point in code.
     *
     * @return void
     */
    public static function backtrace( $ignore_class = null, $skip_frames = 0, $pretty = true ) {
        self::write_log( wp_debug_backtrace_summary( $ignore_class, $skip_frames, $pretty ) ); // phpcs:ignore
    } // End backtrace()


    /**
     * Convert var_dump to string.
     * Useful for printing errors in CSV exports.
     *
     * @param mixed $var  Variable to be dumped and converted to string.
     * 
     * @return string
     */
    public static function var_dump_to_string( $var ) : string {
        ob_start();
        var_dump( $var ); // phpcs:ignore
        return (string) ob_get_clean();
    } // End var_dump_to_string()


    /**
     * Add a JS alert for debugging.
     *
     * @param string   $msg      Message to alert.
     * @param int|null $user_id  Optional user ID restriction.
     * @param bool     $echo     Whether to echo (true) or return (false) the script.
     * 
     * @return void|string
     */
    public static function alert( $msg, $user_id = null, $echo = true ) {
        if ( self::is_dev() || ( ! is_null( $user_id ) && get_current_user_id() == $user_id ) ) {
            $script = '<script type="text/javascript">alert("' . esc_html( $msg ) . '");</script>';
            if ( $echo ) {
                echo $script; // phpcs:ignore
                return;
            }
            return $script;
        }
        return;
    } // End alert()


    /**
     * Console log with PHP.
     *
     * @param string|array|object $msg      Message, array, or object to log.
     * @param int|null            $user_id  Optional user ID restriction.
     * @param bool                $echo     Whether to echo (true) or return (false) the script.
     * 
     * @return void|string
     */
    public static function console( $msg, $user_id = null, $echo = true ) {
        if ( ! is_null( $user_id ) && get_current_user_id() != $user_id && ! self::is_dev() ) {
            return;
        }

        if ( is_array( $msg ) || is_object( $msg ) ) {
            $msg = wp_json_encode( $msg );
        }

        $script = '<script type="text/javascript">console.log("' . wp_kses_post( str_replace( '"', '\"', $msg ) ) . '");</script>';

        if ( $echo ) {
            echo $script; // phpcs:ignore
            return;
        }

        return $script;
    } // End console()


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
    public static function debug_form_post( $email, $test_number = 1, $subject = 'Test Form ' ) {
        // phpcs:ignore
        if ( empty( $_POST ) ) {
            self::print_r( '$_POST not found!' );
            self::console( '$_POST not found!' );
            return false;
        }

        $_POST = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        self::print_r( '$_POST found!' );
        self::console( '$_POST found!' );

        $message = '';

        // phpcs:ignore
        foreach ( $_POST as $key => $value ) {
            if ( is_array( $value ) ) {
                $value = 'ARRAY: ' . implode( ', ', $value );
            } elseif ( is_object( $value ) ) {
                $value = 'OBJECT: ' . print_r( $value, true ); // phpcs:ignore
            }

            $safe_key   = htmlspecialchars( $key );
            $safe_value = htmlspecialchars( $value );

            if ( $safe_key !== 'content' && $safe_key !== 'post_content' ) {
                $message .= "Field: {$safe_key} is {$safe_value}\r\n";
            }
        }

        $headers = [];
        $headers[] = 'From: ' . sanitize_option( 'blogname', get_option( 'blogname' ) ) . ' <' . sanitize_email( get_option( 'admin_email' ) ) . '>';

        if ( wp_mail( $email, $subject . $test_number, $message, $headers ) ) {
            self::print_r( 'Email was sent to ' . $email );
        }
    } // End debug_form_post()


    /**
     * Check if a user has a role
     *
     * @param string $role    Role to check for.
     * @param int    $user_id Optional user ID, defaults to current user.
     *
     * @return bool
     */
    public static function has_role( $role, $user_id = null ) : bool {
        if ( ! $role || is_null( $role ) ) {
            return false;
        }

        if ( is_null( $user_id ) ) {
            $user_id = get_current_user_id();
        }

        if ( $user = get_user_by( 'id', $user_id ) ) {
            $roles = $user->roles;
            return is_array( $roles ) && in_array( $role, $roles );
        }

        return false;
    } // End has_role()


    /**
     * Get a user's highest-level role.
     *
     * @param \WP_User $user User object.
     * @return string Role label.
     */
    public static function get_highest_role( $user ) {
        if ( empty( $user->roles ) ) {
            return __( 'No role', 'dev-debug-tools' );
        }

        $roles_obj   = wp_roles();
        $role_labels = $roles_obj->get_names();
        $all_roles   = $roles_obj->roles;

        $highest_role  = '';
        $highest_level = -1;

        foreach ( $user->roles as $role ) {
            if ( isset( $all_roles[ $role ][ 'capabilities' ] ) ) {
                $caps = $all_roles[ $role ][ 'capabilities' ];

                // Check for level caps
                $level = -1;
                foreach ( $caps as $cap => $grant ) {
                    if ( $grant && preg_match( '/^level_(\d+)$/', $cap, $m ) ) {
                        $level = max( $level, (int) $m[1] );
                    }
                }

                if ( $level > $highest_level ) {
                    $highest_level = $level;
                    $highest_role  = $role;
                }
            }
        }

        // Fallback if no level caps found: just take the first role
        if ( empty( $highest_role ) ) {
            $highest_role = reset( $user->roles );
        }

        return isset( $role_labels[ $highest_role ] )
            ? $role_labels[ $highest_role ]
            : ucfirst( $highest_role );
    } // End get_highest_role()


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
    public static function admin_error( $msg, $include_pre = true, $br = true, $hide_error = false ) : string {
        if ( self::has_role( 'administrator' ) && ! $hide_error ) {
            $display_br  = $br ? '<br>' : '';
            $display_pre = $include_pre ? 'ADMIN ERROR: ' : '';
            return $display_br . '<span class="notice error">' . $display_pre . esc_html( $msg ) . '</span>';
        } else {
            return '';
        }
    } // End admin_error()


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
    public static function start_timer( $timeout_seconds = '300' ) : float|bool {
        if ( ! is_null( $timeout_seconds ) && $timeout_seconds ) {
            update_option( 'ddtt_enable_curl_timeout', 1 );
            update_option( 'ddtt_change_curl_timeout', $timeout_seconds );
        }

        $time = microtime( true );
        return $time !== false ? $time : false;
    } // End start_timer()


    /**
     * Stop timing - Use with start_timer() above
     *
     * @param float   $start        Start time from start_timer().
     * @param boolean $timeout      Whether to restore cURL timeout, default true.
     * @param boolean $milliseconds Whether to return result in milliseconds, default false.
     * @return float                Elapsed time in seconds or milliseconds (rounded).
     */
    public static function stop_timer( $start, $timeout = true, $milliseconds = false ) : float {
        $finish = microtime( true );

        if ( $milliseconds ) {
            $total_time = round( ( $finish - (float) $start ) * 1000, 2 );
        } else {
            $total_time = round( $finish - (float) $start, 2 );
        }

        if ( ! is_null( $timeout ) && $timeout ) {
            update_option( 'ddtt_enable_curl_timeout', 0 );
            update_option( 'ddtt_change_curl_timeout', '' );
        }

        return $total_time;
    } // End stop_timer()


    /**
     * Get just the domain without the https://
     * Option to capitalize the first part, remove extension, and include protocol
     *
     * @param bool $capitalize Capitalize the first part of the domain.
     * @param bool $remove_ext Remove the domain extension.
     * @param bool $incl_protocol Include the protocol (http:// or https://).
     * @return string
     */
    public static function get_domain( $capitalize = false, $remove_ext = false, $incl_protocol = false ) : string {
        $url = home_url();

        $parts = wp_parse_url( $url );

        $domain = $parts[ 'host' ] ?? '';

        if ( $capitalize || $remove_ext ) {
            $pos = strrpos( $domain, '.' );

            if ( $pos !== false ) {
                $prefix = $capitalize ? strtoupper( substr( $domain, 0, $pos ) ) : substr( $domain, 0, $pos );
                $suffix = substr( $domain, $pos + 1 );

                $domain = $remove_ext ? $prefix : $prefix . '.' . $suffix;
            }
        }

        if ( $incl_protocol ) {
            $protocol = $parts[ 'scheme' ] ?? 'http';
            $domain = $protocol . '://' . $domain;
        }

        return $domain;
    } // End get_domain()


    /**
     * Check if we are on a specific website
     * May use partial words, such as "example" for example.com
     *
     * @param string $site_to_check The site substring to check for in the current domain.
     * @return bool
     */
    public static function is_site( $site_to_check ) : bool {
        $site_to_check = strtolower( trim( $site_to_check ) );
        $current_domain = strtolower( self::get_domain() );
        return ( strpos( $current_domain, $site_to_check ) !== false );
    } // End is_site()


    /**
     * Convert time to elapsed string
     *
     * @param string|DateTime $datetime Date/time to compare from.
     * @param boolean         $full     Whether to show full time difference or just the largest unit.
     * @return string
     */
    public static function time_elapsed_string( $datetime, $full = false ) : string {
        $now = new \DateTime;
        $ago = new \DateTime( $datetime );
        $diff = $now->diff( $ago );

        $days = $diff->days;
        $weeks = floor( $days / 7 );
        $remainingDays = $days % 7;

        $string = [
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        ];

        if ( $weeks > 0 ) {
            $string[ 'w' ] = $weeks . ' ' . ( $weeks > 1 ? 'weeks' : 'week' );
            $diff->d = $remainingDays;
        }

        foreach ( $string as $k => &$v ) {
            if ( isset( $diff->$k ) && $diff->$k ) {
                $v = $diff->$k . ' ' . $v . ( $diff->$k > 1 ? 's' : '' );
            } else {
                unset( $string[ $k ] );
            }
        }

        if ( ! $full ) {
            $string = array_slice( $string, 0, 1 );
        }

        return $string ? implode( ', ', $string ) . ' ago' : 'just now';
    } // End time_elapsed_string()


    /**
     * Get plugins data fresh and recache
     *
     * @return array
     */
    public static function get_plugins_data() : array {
        $plugins_data = [ 'last_cached' => time() ];
        $plugins = [];

        if ( is_multisite() ) {
            $network_active = get_site_option( 'active_sitewide_plugins', [] );
            foreach ( $network_active as $plugin_path => $value ) {
                $plugins[ $plugin_path ][] = 'network';
            }

            $subsites = get_sites( [
                'archived' => 0,
                'deleted'  => 0,
                'spam'     => 0,
                'orderby'  => 'id',
                'fields'   => 'ids',
            ] );

            if ( $subsites ) {
                foreach ( $subsites as $blog_id ) {
                    $site_active = get_blog_option( $blog_id, 'active_plugins', [] );
                    foreach ( $site_active as $plugin_path ) {
                        $plugins[ $plugin_path ][] = $blog_id;
                    }
                }
            }
        } else {
            $site_active = get_option( 'active_plugins', [] );
            foreach ( $site_active as $plugin_path ) {
                $plugins[ $plugin_path ] = 'local';
            }
        }

        $all = get_plugins();
        $added_by = get_option( 'ddtt_plugin_installers', [] );

        foreach ( $all as $plugin_path => $plugin_data ) {
            if ( ! array_key_exists( $plugin_path, $plugins ) ) {
                $plugins[ $plugin_path ] = false;
            }
        }

        $sorted_plugins = [];
        foreach ( $plugins as $plugin_path => $active_sites ) {
            if ( isset( $all[ $plugin_path ] ) ) {
                $name = $all[ $plugin_path ][ 'Name' ];
                $sorted_plugins[ $name ] = [
                    'path' => $plugin_path,
                    'sites' => ! $active_sites ? [] : ( is_array( $active_sites ) ? $active_sites : [ $active_sites ] ),
                ];
            }
        }

        foreach ( $sorted_plugins as $name => $data ) {
            $plugin_path = $data[ 'path' ];
            $sites = $data[ 'sites' ];

            if ( ! isset( $all[ $plugin_path ] ) ) {
                continue;
            }

            $plugin_info = $all[ $plugin_path ];

            $url = ! empty( $plugin_info[ 'PluginURI' ] ) ? $plugin_info[ 'PluginURI' ] :
                ( ! empty( $plugin_info[ 'AuthorURI' ] ) ? $plugin_info[ 'AuthorURI' ] : false );

            $author_name = ! empty( $plugin_info[ 'Author' ] ) ? $plugin_info[ 'Author' ] :
                        ( ! empty( $plugin_info[ 'AuthorName' ] ) ? $plugin_info[ 'AuthorName' ] : '' );

            $api = plugins_api(
                'plugin_information',
                [
                    'slug'   => $plugin_info[ 'TextDomain' ],
                    'fields' => [
                        'last_updated' => true,
                        'tested'       => true,
                    ],
                ]
            );

            $last_updated = '';
            $old_class = '';
            $compatibility = '';
            $incompatible_class = '';

            if ( ! is_wp_error( $api ) && $api ) {
                if ( $name !== 'Hello Dolly' && ! empty( $api->last_updated ) ) {
                    $last_updated = self::time_elapsed_string( $api->last_updated );

                    $earlier = new \DateTime( $api->last_updated );
                    $today = new \DateTime( gmdate( 'Y-m-d' ) );
                    $diff = $today->diff( $earlier )->format( '%a' );
                    if ( $diff >= 335 ) {
                        $old_class = ' warning';
                    }

                    $compatibility = $api->tested ?? '';

                    global $wp_version;
                    if ( version_compare( $compatibility, $wp_version, '<' ) ) {
                        $incompatible_class = ' warning';
                    }
                } else {
                    $last_updated = 'just now';
                }
            }

            if ( ! function_exists( 'get_dirsize' ) ) {
                require_once ABSPATH . WPINC . '/ms-functions.php';
            }

            $folder = explode( '/', $plugin_path )[0];
            $directory = get_home_path() . 'wp-content/plugins/' . $folder . '/';

            if ( ! is_dir( $directory ) ) {
                $bytes = 'Unknown';
                $last_modified = 'Directory does not exist or is not accessible';
            } else {
                $bytes = get_dirsize( $directory );

                if ( $name !== 'Hello Dolly' ) {
                    $utc_time = gmdate( 'Y-m-d H:i:s', filemtime( $directory ) );
                    $dt = new \DateTime( $utc_time, new \DateTimeZone( 'UTC' ) );
                    $dt->setTimezone( new \DateTimeZone( get_option( 'ddtt_dev_timezone', wp_timezone_string() ) ) );
                    $last_modified = $dt->format( 'F j, Y g:i A T' );
                } else {
                    $last_modified = '';
                }
            }

            if ( ! empty( $sites ) ) {
                if ( is_multisite() ) {
                    if ( in_array( 'network', $sites, true ) ) {
                        $is_active = 'Network';
                    } elseif ( ! is_network_admin() && in_array( get_current_blog_id(), $sites, true ) || is_network_admin() ) {
                        $is_active = 'Local Only';
                    } else {
                        $is_active = 'No';
                    }
                } else {
                    $is_active = 'Yes';
                }
            } else {
                $is_active = 'No';
            }

            if ( is_network_admin() ) {
                if ( ! empty( $sites ) ) {
                    $site_names = [];
                    if ( in_array( 'network', $sites, true ) ) {
                        $site_names[] = 'Network Active';
                    } else {
                        foreach ( $sites as $site_id ) {
                            $details = get_blog_details( $site_id );
                            $site_names[] = 'ID:' . $site_id . ' - ' . ( $details ? $details->blogname : '' );
                        }
                    }
                    $site_names = implode( '<br>', $site_names );
                } else {
                    $site_names = 'None';
                }
            } else {
                $site_names = '';
            }

            $plugin_added_by = isset( $added_by[ $plugin_path ] ) && ! empty( $added_by[ $plugin_path ] ) ? absint( $added_by[ $plugin_path ] ) : '';

            $plugins_data[ $plugin_path ] = [
                'is_active'          => $is_active,
                'name'               => $name,
                'author'             => $author_name,
                'url'                => $url,
                'description'        => $plugin_info[ 'Description' ],
                'site_names'         => $site_names,
                'version'            => $plugin_info[ 'Version' ],
                'old_class'          => $old_class,
                'last_updated'       => $last_updated,
                'incompatible_class' => $incompatible_class,
                'compatibility'      => $compatibility,
                'folder_size'        => $bytes,
                'last_modified'      => $last_modified,
                'added_by'           => $plugin_added_by,
            ];
        }

        set_transient( 'ddtt_plugins_data', $plugins_data, DAY_IN_SECONDS );

        return $plugins_data;
    } // End get_plugins_data()


    /**
     * Get the plugin icon as a base64 encoded SVG.
     *
     * @param int $width Optional width of the icon.
     * @param int $height Optional height of the icon.
     * @return string
     */
    public static function icon( $width = 20, $height = 20 ) : string {
        $icon_base64 = base64_encode('<svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 504.24 489.11"><path fill="#9CA2A7" d="M322.19,150.95c28.62,0,57.25-.31,85.87,.22,7.07,.13,10.98-3.1,14.92-7.72,8.94-10.47,18.45-20.47,29.34-28.87,4.83-3.73,11.11-3.23,16.66-.21,5.1,2.78,8,6.88,8.22,13.01,.2,5.55-1.66,9.76-5.56,13.62-12.14,12.06-24.3,24.09-36.81,35.78-2.32,2.17-4.73,3.22-7.94,3.19-12.66-.12-25.32-.05-38.82-.05,3.88,5.78,7.57,11.04,11,16.47,14.82,23.41,27.25,47.89,32.95,75.27,.63,3.04,2.4,2.19,3.96,2.19,16.83,.06,33.65,.03,50.48,.04,10.07,0,17.19,6.48,17.72,15.17,.86,14-7.7,19.55-17.77,19.74-15.32,.29-30.65,.13-45.98,.03-2.86-.02-4.19,.23-4.46,3.85-1.29,17.44-3.65,34.75-9.85,51.25-2.68,7.14-5.79,14.12-9.02,21.91,3.08,0,5.97,.16,8.84-.04,5.14-.35,9.26,1.68,12.76,5.16,12.16,12.1,24.33,24.2,36.39,36.41,5.86,5.93,9.94,12.56,6.55,21.39-4.37,11.36-18.84,14-28.06,4.97-10.23-10.03-20.31-20.21-30.38-30.4-1.74-1.76-3.46-2.94-6.06-2.55-1.3,.2-2.66-.01-3.99,.04-6.29,.27-13.19-1.82-18.7,.79-5.27,2.5-9.04,8.06-13.6,12.15-23.92,21.45-50.66,37.96-81.49,47.35-18.63,5.67-37.8,9.32-57.26,7.48-23.09-2.18-45.6-7.21-66.88-17.18-21.8-10.2-42.35-22.71-57.33-41.31-10.02-12.44-21.57-8.25-33.09-9.24-1.91-.16-2.73,1.7-3.87,2.83-10.05,9.98-19.98,20.07-30.09,29.98-8.95,8.78-23.12,6.4-27.77-4.52-2.91-6.83-1.58-14.05,3.66-19.31,12.23-12.27,24.62-24.39,36.69-36.81,4.9-5.04,10.26-8.04,17.44-7.24,1.81,.2,3.66,.03,6.16,.03-3.43-7.47-6.93-14.53-9.37-22.04-4.2-12.92-7.63-25.97-8.39-39.59-.11-1.99-1.08-3.72-1.1-5.79-.08-9.55-.18-9.55-9.53-9.55-13.49,0-26.99,.14-40.48-.04-16.98-.23-21.48-15.5-15.92-26.49,2.75-5.44,7.64-8.44,13.95-8.45,16.49-.04,32.99-.09,49.48,.05,3.78,.03,5.08-.74,5.81-5.06,2.5-14.77,7.21-28.99,13.12-42.8,6.95-16.24,15.87-31.32,27.42-46.17-9.08,0-17.25-.86-25.16,.21-10.56,1.43-17.94-3.02-24.88-9.92-9.9-9.82-20.08-19.36-30.03-29.13-6.82-6.7-6.39-16.26-.11-23.42,5.47-6.23,18.14-6.07,23.55-.29,9.78,10.46,20.22,20.29,30.24,30.53,2.14,2.18,4.24,3.11,7.35,3.1,27.49-.13,54.97-.08,82.46-.08h5.32c.23,.34,.45,.68,.68,1.01-42.16,20.66-71.38,53.03-86.98,96.92-15.76,44.32-12.39,87.92,9.09,129.64,37.85,73.53,109.81,95.36,158.67,91.8,43.67-3.18,81.92-20.78,112.02-53.29,23.44-25.32,38.54-55.24,43.55-89.63,10.88-74.71-30.09-145.43-95.6-176.46Z M126.48,220.75c46.03-75.47,158.52-93.39,227.1-29.04-4.94,1.43-10.13,1.55-14.48,4.59-10.77,7.52-14.78,20.07-10.39,32.96,3.08,9.05,8.59,16.76,13.44,24.85,6.1,10.19,10.12,20.91,11.31,33.01,1.7,17.33-2.92,33.33-7.91,49.43-5.48,17.67-10.83,35.39-16.26,53.08-.43,1.39-1,2.74-1.64,4.5-3.09-3.31-3.59-7.39-4.7-11.05-11.17-36.74-24.87-72.63-36.61-109.16-4.1-12.74-8.27-25.45-12.54-38.13-.96-2.84-.47-3.92,2.69-3.94,3.96-.02,7.93-.41,11.89-.78,4.84-.45,7.28-2.97,6.79-6.83-.48-3.76-3.48-5.06-8.22-4.87-18.05,.73-36.07,2.69-54.2,.92-7.55-.74-15.2-.32-22.8-1.24-3.18-.39-5.75,1.59-6.12,5.2-.34,3.37,.59,5.97,4.55,6.37,3.94,.4,7.89,1.26,11.82,1.16,3.33-.09,4.84,1.37,5.85,4.09,7.25,19.54,14.5,39.09,21.76,58.63,.75,2,.38,3.85-.29,5.82-9.5,27.89-18.9,55.81-28.44,83.69-1.25,3.65-1.25,7.73-4.17,11.77-2.19-6.25-4.2-11.9-6.14-17.57-7.36-21.44-15.2-42.74-21.9-64.38-8.13-26.24-17.73-51.96-26.37-78.02-.89-2.69-.65-3.97,2.64-3.97,4.12,0,8.24-.43,12.35-.75,4.99-.39,7.66-3.09,7.11-7.08-.5-3.63-3.44-4.71-8.37-4.55-15.86,.52-31.71,2.19-47.73,1.29Z M356.89,135.01H147.77c.56-9.46,.7-18.75,3.4-27.78,3.92-13.13,9.84-25.11,18.37-35.91,6.72-8.51,14.73-15.49,23.69-21.39,2.51-1.65,1.91-2.38,.3-3.88-6.19-5.75-12.13-11.77-18.26-17.59-7.06-6.71-5.82-17.58-.28-23.4,6.48-6.81,17.65-6.66,24.68,.24,9.16,8.98,18.16,18.12,27.24,27.18,1.35,1.35,2.37,2.35,4.94,1.98,6.63-.96,13.25-2.36,20.04-2.23,6.26,.12,12.43,.75,18.56,1.99,2.94,.59,5.31,.27,7.74-2.29,8.36-8.83,17.24-17.16,25.66-25.93,5.41-5.64,12.04-7.03,18.97-5.26,6.87,1.75,10.25,7.68,11.05,14.3,.57,4.7-1.3,9.31-4.86,12.88-6.01,6.01-11.96,12.07-18.05,17.99-1.88,1.83-2.09,2.58,.42,4.22,16.65,10.84,29.04,25.35,36.95,43.65,4.78,11.07,7.92,22.46,7.83,34.62-.01,2.13-.46,4.34,.71,6.63Z M254.77,315.44c5.7,15.24,11.08,29.63,16.46,44.01,4.4,11.77,9.39,23.37,13.08,35.36,4.97,16.12,11.45,31.66,17.16,47.5,1.09,3.02,.08,3.72-2.42,4.48-28.19,8.58-56.53,9.7-85.17,2.29-3.94-1.02-4.11-2.67-2.96-5.97,6.87-19.86,13.51-39.79,20.39-59.64,5.79-16.71,11.7-33.38,17.75-49.99,2.04-5.59,2.54-11.64,5.71-18.03Z M187.4,439.22c-76.25-36.61-105.45-126.98-72.37-197.71,24.08,65.8,47.97,131.06,72.37,197.71Z M403.03,293.89c0,6.34,0,12.68,0,19.02-.25,.5-.5,1-.75,1.5-.24,18.21-5.95,35-12.89,51.6-8.65,20.71-22.79,37.19-39.01,52.07-6.52,5.98-13.84,11.09-22.54,15.33,1.45-7.51,4.42-13.77,6.62-20.25,5.43-15.98,11.7-31.7,16.65-47.82,9.22-30.03,21.84-58.9,30.32-89.15,3.31-11.81,4.83-23.94,3.67-36.29-.22-2.31,.03-4.65,.07-6.98l-.1,.07c7.37,11.51,11.34,24.34,14.56,37.47,1.82,7.39,2.29,14.97,3.34,22.48-.11,.1-.33,.24-.31,.29,.09,.24,.24,.45,.38,.67Z M385.16,232.91c-.2-.2-.4-.41-.6-.61,.73-.19,.82,.09,.5,.68,0,0,.1-.07,.1-.07Z M403.03,293.89c-.13-.22-.29-.43-.38-.67-.02-.05,.2-.19,.31-.29,.02,.32,.04,.64,.07,.96Z M402.28,314.41c.25-.5,.5-1,.75-1.5,.12,.69-.15,1.18-.75,1.5Z"/></svg>');
        return 'data:image/svg+xml;base64,' . $icon_base64;
    } // End icon()


    /**
     * Check if the current user has dark mode enabled.
     *
     * @return bool
     */
    public static function is_dark_mode() : bool {
        $value = get_user_meta( get_current_user_id(), 'ddtt_mode', true );
        if ( $value === '' || $value === false ) {
            $value = sanitize_key( get_option( 'ddtt_default_mode', 'dark' ) );
        }
        return $value === 'dark';
    } // End is_dark_mode()


    /**
     * Redact sensitive information based on settings.
     *
     * @param string $string The string to potentially redact.
     * @return string The original or redacted string.
     */
    public static function maybe_redact( $string, $abspath_only = false, $remove_redacted = false ) : string {
        // If sensitive info viewing is allowed, return as-is
        if ( filter_var( get_option( 'ddtt_view_sensitive_info' ), FILTER_VALIDATE_BOOLEAN ) ) {
            return $string;
        }

        // If we only want to redact the absolute path
        if ( $abspath_only ) {
            $abspath = untrailingslashit( ABSPATH );

            if ( strpos( $string, $abspath ) === 0 ) {
                if ( $remove_redacted ) {
                    return substr( $string, strlen( $abspath ) );
                }
                return '<i class="ddtt-redact">' . $abspath . '</i>' . substr( $string, strlen( $abspath ) );
            }

            // If not an abspath, return string as-is
            return $string;
        }

        // Default: redact the whole string
        return '<i class="ddtt-redact">' . $string . '</i>';
    } // End maybe_redact()

    
    /**
     * Get the plugin version.
     *
     * @return string
     */
    public static function multisite_suffix() : string {
        if ( is_network_admin() ) {
            $sfx = ' <em>' . __( '- Network', 'dev-debug-tools' ) . '</em>';
        } elseif ( is_multisite() && is_main_site() ) {
            $sfx = ' <em>' . __( '- Primary', 'dev-debug-tools' ) . '</em>';
        } elseif ( is_multisite() && !is_main_site() ) {
            $sfx = ' <em>' . __( '- Subsite', 'dev-debug-tools' ) . '</em>';
        } else {
            $sfx = '';
        }
        return $sfx;
    } // End multisite_suffix()


    /**
     * Format seconds into a human-readable uptime string.
     *
     * @param int $seconds Number of seconds to format.
     * @return string
     */
    public static function format_bytes( $bytes, $precision = 2 ) : string {
        $units = [ 'B', 'KB', 'MB', 'GB', 'TB' ];
        $bytes = max( $bytes, 0 );
        $power = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
        return round( $bytes / ( 1024 ** $power ), $precision ) . ' ' . $units[$power];
    } // End format_bytes()


    /**
     * Get the last modified time of all files in a folder.
     *
     * @param string $path The folder path.
     * @return string
     */
    public static function folder_last_modified( $path ) : string {
        if ( ! is_dir( $path ) ) {
            return 'Directory does not exist or is not accessible';
        }

        $last_modified = 0;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator( $path, \FilesystemIterator::SKIP_DOTS )
        );

        foreach ( $iterator as $fileinfo ) {
            $file_mtime = $fileinfo->getMTime();
            if ( $file_mtime > $last_modified ) {
                $last_modified = $file_mtime;
            }
        }

        if ( $last_modified === 0 ) {
            return 'No files found';
        }

        // Get the timezone from WP
        $tz_string = get_option( 'ddtt_dev_timezone', wp_timezone_string() );
        $tz = new \DateTimeZone( $tz_string );

        // Create DateTime directly from timestamp in local timezone
        $dt = new \DateTime( '@' . $last_modified ); // '@' means timestamp
        $dt->setTimezone( $tz );

        $format = sanitize_text_field( get_option( 'ddtt_dev_timeformat', 'F j, Y g:i A T' ) );

        return $dt->format( $format );
    } // End folder_last_modified()


    /**
     * Get the total size of all files in a directory.
     *
     * @param string $dir The directory path.
     * @return int The total size in bytes.
     */
    public static function get_directory_size( $dir ) {
        $size = 0;

        foreach ( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) ) as $file ) {
            if ( $file->isFile() ) {
                $size += $file->getSize();
            }
        }

        return $size;
    } // End get_directory_size()


    /**
     * Check if settings were saved.
     *
     * @return bool
     */
    public static function is_settings_saved() : bool {
        return isset( $_GET[ 'settings-updated' ] ) && sanitize_key( wp_unslash( $_GET[ 'settings-updated' ] ) ) === 'true'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    } // End is_settings_saved()


    /**
     * Remove query strings from url without refresh
     *
     * @param null|string|array $qs Query strings to remove; can be a string or array of keys.
     * @param boolean $is_admin Whether the current context is admin (true) or front-end (false).
     * 
     * @return void
     */
    public static function remove_qs_without_refresh( $qs = null, $is_admin = true ) {
        if ( ! is_null( $qs ) ) {
            if ( ! is_array( $qs ) ) {
                $qs = [ $qs ];
            }
            $new_url = remove_query_arg( $qs );
        } elseif ( ! $is_admin ) {
            $new_url = strtok( esc_url_raw( add_query_arg( null, null ) ), '?' );
        } else {
            return;
        }
        
        $args = [
            'title' => is_admin() ? get_admin_page_title() : get_the_title(),
            'url'   => $new_url,
        ];

        $handle = 'ddtt-remove-qs';

        wp_enqueue_script(
            $handle,
            Bootstrap::url( 'inc/helpers/remove-qs.js' ),
            [ 'jquery' ],
            Bootstrap::script_version(),
            true
        );

        wp_localize_script( $handle, 'ddtt_remove_qs', $args );
    } // End remove_qs_without_refresh()


    /**
     * Reformat a datetime string or timestamp using dev timezone or UTC.
     *
     * @param string|int $date Date string or timestamp.
     * @param bool       $utc  Whether to treat the date as UTC instead of dev timezone.
     *
     * @return string
     */
    public static function convert_date_format( $date, $utc = false ) : string {
        if ( empty( $date ) || $date === '0000-00-00 00:00:00' || $date === 0 || $date === '0' ) {
            return __( 'Undefined', 'dev-debug-tools' );
        }
        
        $format = sanitize_text_field( get_option( 'ddtt_dev_timeformat', 'n/j/Y g:i a T' ) );

        // Determine timezone object
        if ( $utc ) {
            $tz = new \DateTimeZone( 'UTC' );
        } else {
            $timezone_string = sanitize_text_field( get_option( 'ddtt_dev_timezone' ) );
            $tz = $timezone_string ? new \DateTimeZone( $timezone_string ) : wp_timezone();
        }

        if ( is_numeric( $date ) ) {
            $dt = new \DateTimeImmutable( '@' . (int) $date );
            $dt = $dt->setTimezone( $tz );
        } else {
            $dt = new \DateTimeImmutable( $date, $tz );
        }

        return $dt->format( $format );
    } // End convert_date_format()


    /**
     * Convert a date/time to a specific timezone.
     *
     * @param string|int $date       Date string or timestamp; null for current time.
     * @param string|null $format    Optional date format; defaults to dev time format.
     * @param string|null $timezone  Optional timezone identifier; uses dev timezone if not provided.
     *
     * @return string
     */
    public static function convert_timezone( $date, $format = null, $timezone = null ) : string {
        if ( empty( $date ) || $date === '0000-00-00 00:00:00' || $date === 0 || $date === '0' ) {
            return __( 'Undefined', 'dev-debug-tools' );
        }

        $timestamp = is_numeric( $date ) ? (int) $date : strtotime( $date );
        $format    = $format ?: sanitize_text_field( get_option( 'ddtt_dev_timeformat', 'n/j/Y g:i a T' ) );

        // Use provided timezone, then dev timezone, then WP timezone
        $timezone_string = $timezone ?: sanitize_text_field( get_option( 'ddtt_dev_timezone' ) );
        $tz = $timezone_string ? new \DateTimeZone( $timezone_string ) : wp_timezone();

        return wp_date( $format, $timestamp, $tz );
    } // End convert_timezone()


    /**
     * Convert timestamp to human-readable elapsed string
     *
     * @param mixed $ts Timestamp or date string
     * @param bool $short Whether to use short format
     * @return string
     */
    public static function convert_timestamp_to_string( $ts, $short = false ) : string {
        if ( ! is_numeric( $ts ) ) {
            $ts = strtotime( $ts );
        }

        $diff = time() - $ts;

        if ( $short ) {
            $minute = 'm';
            $minutes = 'm';
            $hour = 'h';
            $hours = 'h';
            $days = 'd';
            $weeks = 'w';
        } else {
            $minute = ' minute ago';
            $minutes = ' minutes ago';
            $hour = ' hour ago';
            $hours = ' hours ago';
            $days = ' days ago';
            $weeks = ' weeks ago';
        }

        if ( $diff == 0 ) {
            return 'Now';
        } elseif ( $diff > 0 ) {
            $day_diff = floor( $diff / 86400 );
            if ( $day_diff == 0 ) {
                if ( $diff < 60 ) return 'Just now';
                if ( $diff < 120 ) return '1' . $minute;
                if ( $diff < 3600 ) return floor( $diff / 60 ) . $minutes;
                if ( $diff < 7200 ) return '1' . $hour;
                if ( $diff < 86400 ) return floor( $diff / 3600 ) . $hours;
            }
            if ( $day_diff == 1 ) return 'Yesterday';
            if ( $day_diff < 7 ) return $day_diff . $days;
            if ( $day_diff < 31 ) return ceil( $day_diff / 7 ) . $weeks;
            if ( $day_diff < 60 ) return 'Last month';
            return gmdate( 'F Y', $ts );
        } else {
            $diff = abs( $diff );
            $day_diff = floor( $diff / 86400 );
            if ( $day_diff == 0 ) {
                if ( $diff < 120 ) return 'In a minute';
                if ( $diff < 3600 ) return 'In ' . floor( $diff / 60 ) . ' minutes';
                if ( $diff < 7200 ) return 'In an hour';
                if ( $diff < 86400 ) return 'In ' . floor( $diff / 3600 ) . ' hours';
            }
            if ( $day_diff == 1 ) return 'Tomorrow';
            if ( $day_diff < 4 ) return gmdate( 'l', $ts );
            if ( $day_diff < 7 + ( 7 - gmdate( 'w' ) ) ) return 'Next week';
            if ( ceil( $day_diff / 7 ) < 4 ) return 'In ' . ceil( $day_diff / 7 ) . ' weeks';
            if ( gmdate( 'n', $ts ) == gmdate( 'n' ) + 1 ) return 'Next month';
            return gmdate( 'F Y', $ts );
        }
    } // End convert_timestamp_to_string()


    /**
     * Get contrast color for a given hex color
     *
     * @param string $hexColor Hex color code (with or without #).
     * 
     * @return string
     */
    public static function get_contrast_color( $hexColor ) : string {
        // Normalize hex color (remove # if present)
        $hexColor = ltrim( $hexColor, '#' );

        // Parse RGB components (supports 3 or 6 chars)
        if ( strlen( $hexColor ) === 3 ) {
            $r = hexdec( str_repeat( $hexColor[0], 2 ) );
            $g = hexdec( str_repeat( $hexColor[1], 2 ) );
            $b = hexdec( str_repeat( $hexColor[2], 2 ) );
        } else {
            $r = hexdec( substr( $hexColor, 0, 2 ) );
            $g = hexdec( substr( $hexColor, 2, 2 ) );
            $b = hexdec( substr( $hexColor, 4, 2 ) );
        }

        // Calculate relative luminance
        $luminance = function( $channel ) {
            $c = $channel / 255;
            return ( $c <= 0.03928 ) ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
        };

        $L = 0.2126 * $luminance( $r ) + 0.7152 * $luminance( $g ) + 0.0722 * $luminance( $b );

        // Contrast ratios with black and white
        $contrastBlack = ( $L + 0.05 ) / 0.05;
        $contrastWhite = ( 1.05 ) / ( $L + 0.05 );

        // Return color with better contrast
        return ( $contrastBlack > $contrastWhite ) ? '#000000' : '#FFFFFF';
    } // End get_contrast_color()


    /**
     * Convert hex color to rgba format
     *
     * @param string $hex     Hex color code (with or without #).
     * @param float  $opacity Opacity value between 0 and 1.
     * 
     * @return string
     */
    public static function hex_to_rgba( $hex, $opacity = 1 ) {
        $hex = str_replace( '#', '', $hex );
        if ( strlen( $hex ) === 3 ) {
            $r = hexdec( str_repeat( $hex[0], 2 ) );
            $g = hexdec( str_repeat( $hex[1], 2 ) );
            $b = hexdec( str_repeat( $hex[2], 2 ) );
        } else {
            $r = hexdec( substr( $hex, 0, 2 ) );
            $g = hexdec( substr( $hex, 2, 2 ) );
            $b = hexdec( substr( $hex, 4, 2 ) );
        }
        return "rgba($r, $g, $b, $opacity)";
    } // End hex_to_rgba()


    /**
     * Check if something is truely true
     * Returns true if variable is true, True, TRUE, 'true', 1
     * Returns false if variable is false, 0, or any other string
     *
     * @param mixed $variable Variable to check
     *
     * @return bool|null True, false, or null if not set
     */
    public static function is_enabled( $variable ) : bool|null {
        if ( ! isset( $variable ) ) {
            return null;
        }
        return filter_var( $variable, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
    } // End is_enabled()

    
    /**
     * Get the server IP address.
     *
     * @return string|null
     */
    public static function get_server_ip() : ?string {
        if ( isset( $_SERVER[ 'SERVER_ADDR' ] ) ) {
            return filter_var( wp_unslash( $_SERVER[ 'SERVER_ADDR' ] ), FILTER_VALIDATE_IP );
        }

        if ( isset( $_SERVER[ 'HTTP_HOST' ] ) ) {
            $hostname = filter_var( wp_unslash( $_SERVER[ 'HTTP_HOST' ] ), FILTER_SANITIZE_SPECIAL_CHARS );
            $ip = gethostbyname( $hostname );
            if ( $validated_ip = filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                return $validated_ip;
            }
        }

        $ip = trim( shell_exec( 'hostname -I' ) );
        if ( $validated_ip = filter_var( $ip, FILTER_VALIDATE_IP ) ) {
            return $validated_ip;
        }

        return null;
    } // End get_server_ip()


    /**
     * Convert backtick-wrapped text to <code> tags, escaping HTML inside.
     *
     * @param string $text Input text possibly containing backticks.
     * @return string Text with backticks replaced by <code> tags.
     */
    public static function backticks_to_code( $string ) : string {
        return preg_replace_callback(
            '/`([^`]+)`/',
            function( $matches ) {
                return '<code>' . esc_html( $matches[1] ) . '</code>';
            },
            $string
        );
    } // End backticks_to_code()


    /**
     * Check if a string is a serialized object.
     *
     * @param string $string The string to check.
     * @return bool
     */
    public static function is_serialized_object( $string ) : bool {
        return ( is_string( $string ) && preg_match( '/^O:\d+:"[^"]+":\d+:{.*}$/s', $string) );
    } // End is_serialized_object()


    /**
     * Check if a string is a serialized array.
     *
     * @param string $string The string to check.
     * @return bool
     */
    public static function is_serialized_array( $string ) : bool {
        // Check if the string is empty or not a string
        if ( ! is_string( $string ) || empty( $string ) ) {
            return false;
        }

        // Check if the string is a serialized format
        $trimmed = trim( $string );
        if ( $trimmed === 'b:0;' ) {
            return true;
        }

        if ( preg_match( '/^(a|O|s|i|d|b|C):/i', $trimmed ) && preg_match( '/[;}]/', substr( $trimmed, -1 ) ) ) {
            try {
                $result = unserialize( $trimmed );
                return ( $result !== false || $string === 'b:0;' );
            } catch ( \Exception $e ) {
                return false;
            }
        }

        return false;
    } // End is_serialized_array()


    /**
     * Render a notice with a message and type.
     *
     * @param string $message The message to display.
     * @param string $type The type of notice (success, error, warning, info).
     */
    public static function render_notice( $message, $type = 'success' ) : void {
        $allowed_types = [ 'success', 'error', 'warning', 'info' ];
        if ( ! in_array( $type, $allowed_types, true ) ) {
            $type = 'success';
        }
        ?>
        <div class="ddtt-notice ddtt-notice-<?php echo esc_attr( $type ); ?> is-dismissible">
            <p><?php echo wp_kses_post( $message ); ?></p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'dev-debug-tools' ); ?></span>
            </button>
        </div>
        <?php
    } // End render_notice()


    /**
     * Get the bots we will use in the activity log
     *
     * @return array
     */
    public static function get_bots() : array {
        return filter_var_array( apply_filters( 'ddtt_bots_to_log', [
            'Googlebot' => [
                'name' => 'Google Bot',
                'url' => 'http://www.google.com/bot.html'
            ],
            'Googlebot-Mobile' => [
                'name' => 'Google Mobile Bot',
                'url' => 'http://www.google.com/bot.html'
            ],
            'Googlebot-Image' => [
                'name' => 'Google Image Bot',
                'url' => 'http://www.google.com/bot.html'
            ],
            'Googlebot-News' => [
                'name' => 'Google News Bot',
                'url' => 'http://www.google.com/bot.html'
            ],
            'Googlebot-Video' => [
                'name' => 'Google Video Bot',
                'url' => 'http://www.google.com/bot.html'
            ],
            'AdsBot-Google' => [
                'name' => 'Google Ads Bot',
                'url' => 'http://www.google.com/adsbot.html'
            ],
            'AdsBot-Google-Mobile' => [
                'name' => 'Google Ads Bot Mobile"',
                'url' => 'http://www.google.com/mobile/adsbot.html'
            ],
            'Feedfetcher-Google' => [
                'name' => 'Google Feedfetcher',
                'url' => 'http://www.google.com/feedfetcher.html'
            ],
            'Mediapartners-Google' => [
                'name' => 'Google Media Partners',
                'url' => 'http://www.google.com/bot.html'
            ],
            'APIs-Google' => [
                'name' => 'Google APIs',
                'url' => 'https://developers.google.com/webmasters/APIs-Google.html'
            ],
            'Google-InspectionTool' => [
                'name' => 'Google Inspection Tool',
                'url' => 'https://developers.google.com/search/docs/crawling-indexing/overview-google-crawlers'
            ],
            'Storebot-Google' => [
                'name' => 'Google Store Bot',
                'url' => 'hhttps://developers.google.com/search/docs/crawling-indexing/overview-google-crawlers'
            ],
            'Google-Site-Verification' => [
                'name' => 'Google Site Verifier',
                'url' => 'https://support.google.com/webmasters/answer/9008080'
            ],
            'Google-Safety' => [
                'name' => 'Google Safety',
                'url' => 'https://developers.google.com/search/docs/crawling-indexing/overview-google-crawlers'
            ],
            'Google-Read-Aloud' => [
                'name' => 'Google Read Aloud',
                'url' => 'https://developers.google.com/search/docs/crawling-indexing/overview-google-crawlers'
            ],
            'GoogleOther' => [
                'name' => 'Google Other',
                'url' => 'https://developers.google.com/search/docs/crawling-indexing/overview-google-crawlers'
            ],
            'bingbot' => [
                'name' => 'Bing Bot',
                'url' => 'http://www.bing.com/bingbot.htm'
            ],
            'Yahoo! Slurp' => [
                'name' => 'Yahoo! Slurp',
                'url' => 'http://help.yahoo.com/help/us/ysearch/slurp'
            ],
            'Wget' => [
                'name' => 'Wget',
                'url' => 'http://wget.alanreed.org'
            ],
            'LinkedInBot' => [
                'name' => 'LinkedIn Bot',
                'url' => 'http://www.google.com/bot.html'
            ],
            'Python-urllib' => [
                'name' => 'Python Urllib',
                'url' => 'https://docs.python.org/3/library/urllib.html'
            ],
            'python-requests' => [
                'name' => 'Python Requests',
                'url' => 'https://www.geeksforgeeks.org/user-agent-in-python-request/'
            ],
            'aiohttp' => [
                'name' => 'AIOHTTP',
                'url' => 'https://docs.aiohttp.org/en/stable/'
            ],
            'python-httpx' => [
                'name' => 'Python HTTPX',
                'url' => 'https://www.python-httpx.org'
            ],
            'libwww-perl' => [
                'name' => 'libwww-perl',
                'url' => 'https://github.com/libwww-perl/libwww-perl'
            ],
            'httpunit' => [
                'name' => 'HttpUnit',
                'url' => 'https://httpunit.sourceforge.net/'
            ],
            'nutch' => [
                'name' => 'Apache Nutch',
                'url' => 'https://nutch.apache.org/'
            ],
            'Go-http-client' => [
                'name' => 'Go HTTP client',
                'url' => 'https://golang.org/pkg/net/http/'
            ],
            'phpcrawl' => [
                'name' => 'PHPCrawl',
                'url' => 'http://phpcrawl.cuab.de/'
            ],
            'msnbot' => [
                'name' => 'MSN Bot',
                'url' => 'http://search.msn.com/msnbot.htm'
            ],
            'FAST-WebCrawler' => [
                'name' => 'FAST-WebCrawler',
                'url' => 'http://fast.no/support/crawler.asp'
            ],
            'FAST Enterprise Crawler' => [
                'name' => 'FAST Enterprise Crawler',
                'url' => 'http://www.scirus.com/srsapp/contactus/'
            ],
            'ia_archiver' => [
                'name' => 'Internet Archive',
                'url' => 'https://web.archive.org/'
            ],
            'HTTrack' => [
                'name' => 'HTTrack Website Copier',
                'url' => 'https://www.httrack.com/'
            ],
            'yacybot' => [
                'name' => 'YaCy',
                'url' => 'http://yacy.net/bot.html'
            ],
            'MJ12bot' => [
                'name' => 'Majestic (MJ12Bot)',
                'url' => 'http://majestic12.co.uk/bot.php'
            ],
            'Buzzbot' => [
                'name' => 'Buzz Stream',
                'url' => 'http://www.buzzstream.com'
            ],
            'Yandex' => [
                'name' => 'Yandex',
                'url' => 'http://yandex.com/bots'
            ],
            'Linguee Bot' => [
                'name' => 'Linguee Bot',
                'url' => 'http://www.linguee.com/bot'
            ],
            'Baiduspider' => [
                'name' => 'Baidu Spider',
                'url' => 'http://www.baidu.com/search/spider.html'
            ],
            'Turnitin' => [
                'name' => 'Turnitin',
                'url' => 'https://turnitin.com/robot/crawlerinfo.html'
            ],
            'Page2RSS' => [
                'name' => 'Page2RSS',
                'url' => 'http://page2rss.com/'
            ],
            'CCBot' => [
                'name' => 'CCBot (Common Crawl)',
                'url' => 'http://www.commoncrawl.org/bot.html'
            ],
            'facebookexternalhit' => [
                'name' => 'Facebook External Hit',
                'url' => 'http://www.facebook.com/externalhit_uatext.php'
            ],
            'DuckDuckBot' => [
                'name' => 'DuckDuckBot',
                'url' => 'http://duckduckgo.com/duckduckbot.html'
            ],
            'proximic' => [
                'name' => 'Proximic',
                'url' => 'http://www.proximic.com/info/spider.php'
            ],
            'Apache-HttpClient' => [
                'name' => 'Apache HTTP Client',
                'url' => 'https://hc.apache.org/httpcomponents-client-4.5.x/index.html'
            ],
            'Feedly' => [
                'name' => 'Feedly',
                'url' => 'https://www.feedly.com/fetcher.html'
            ],
            'zgrab' => [
                'name' => 'ZGrab',
                'url' => 'https://zmap.io/'
            ],
            'axios' => [
                'name' => 'Axios',
                'url' => 'https://github.com/axios/axios'
            ],
            'HubSpot' => [
                'name' => 'HubSpot',
                'url' => 'http://dev.hubspot.com/'
            ],
            'Chrome-Lighthouse' => [
                'name' => 'Chrome Lighthouse',
                'url' => 'https://developers.google.com/speed/pagespeed/insights'
            ],
            'HeadlessChrome' => [
                'name' => 'Headless Chrome',
                'url' => 'https://developers.google.com/web/updates/2017/04/headless-chrome'
            ],
            'Uptimebot' => [
                'name' => 'Uptimebot',
                'url' => 'http://www.uptime.com/uptimebot'
            ],
            'curl' => [
                'name' => 'Curl',
                'url' => 'https://curl.haxx.se/'
            ],
            'Bytespider' => [
                'name' => 'Bytespider (ByteDance)',
                'url' => 'https://bytedance.com'
            ],
            'PetalBot' => [
                'name' => 'PetalBot',
                'url' => 'https://webmaster.petalsearch.com/site/petalbot'
            ],
            'virustotalcloud' => [
                'name' => 'Virustotal',
                'url' => 'https://www.virustotal.com/gui/home/url'
            ],
            'SpeedCurve' => [
                'name' => 'WebPageTest',
                'url' => 'https://www.webpagetest.org'
            ],
            'Feedbin' => [
                'name' => 'Feedbin',
                'url' => 'https://feedbin.com/'
            ],
            'CriteoBot' => [
                'name' => 'Criteo Crawler',
                'url' => 'https://www.criteo.com/criteo-crawler/'
            ],
            'RuxitSynthetic' => [
                'name' => 'Dynatrace',
                'url' => 'https://www.dynatrace.com/support/help/platform-modules/digital-experience/synthetic-monitoring/browser-monitors/configure-browser-monitors#expand--default-user-agent'
            ],
            'GPTBot' => [
                'name' => 'GPTBot (ChatGPT)',
                'url' => 'https://openai.com/gptbot'
            ],
            'AwarioBot' => [
                'name' => 'AwarioSmartBot',
                'url' => 'https://awario.com/bots.html'
            ],
            'DataForSeoBot' => [
                'name' => 'DataForSeoBot',
                'url' => 'https://dataforseo.com/dataforseo-bot'
            ],
            'Linespider' => [
                'name' => 'Linespider',
                'url' => 'https://lin.ee/4dwXkTH'
            ],
            'BrightEdge' => [
                'name' => 'BrightEdge Crawler',
                'url' => 'https://www.brightedge.com/'
            ],
            'Iframely' => [
                'name' => 'Iframely',
                'url' => 'https://iframely.com/docs/about'
            ],
            'MetaInspector' => [
                'name' => 'MetaInspector',
                'url' => 'https://github.com/jaimeiniesta/metainspector'
            ],
            'node-fetch' => [
                'name' => 'Node Fetch',
                'url' => 'https://github.com/bitinn/node-fetch'
            ],
            'python-opengraph' => [
                'name' => 'Python OpenGraph',
                'url' => 'https://github.com/jaywink/python-opengraph'
            ],
            'Palo Alto Networks' => [
                'name' => 'Cortex Xpanse Bot (Palo Alto Networks)',
                'url' => 'https://www.paloaltonetworks.com/cortex/cortex-xpanse'
            ],
            'BW/' => [
                'name' => 'BuiltWith',
                'url' => 'https://builtwith.com/biup'
            ],
            'GeedoBot' => [
                'name' => 'GeedoBot',
                'url' => 'http://www.geedo.com/bot.html'
            ],
            'Audisto Crawler' => [
                'name' => 'Audisto Crawler',
                'url' => 'https://audisto.com/help/crawler/bot/'
            ],
            'PerplexityBot' => [
                'name' => 'Perplexity AI',
                'url' => 'https://perplexity.ai/perplexitybot'
            ],
        ] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS );
    } // End get_bots()


    /**
     * Check if a visitor is a bot
     *
     * @return boolean|array
     */
    public static function is_bot() : bool|array {
        $user_agent = isset( $_SERVER[ 'HTTP_USER_AGENT' ] ) ? sanitize_text_field( wp_unslash( $_SERVER[ 'HTTP_USER_AGENT' ]) ) : '';

        $bots = self::get_bots();

        foreach ( $bots as $ua => $props ) {
            if ( strpos( strtolower( $user_agent ), strtolower( $ua ) ) !== false ) {
                $bot_name = sanitize_text_field( $props[ 'name' ] );
                $bot_url = sanitize_url( $props[ 'url' ] );
                if ( stripos( $bot_name, 'Bot' ) === false && stripos( $bot_name, 'Robot' ) === false ) {
                    $bot_name .= ' Bot';
                }
                return [
                    'name' => $bot_name,
                    'url'  => $bot_url,
                    'keyword' => $ua,
                    'user_agent' => $user_agent
                ];
            }
        }

        return false;
    } // End is_bot()


    /**
     * Print a stored value to a table cell, handling arrays, serialized data, and JSON.
     *
     * @param mixed $value The value to print.
     * @return string
     */
    public static function print_stored_value_to_table( $value ) : string {
        if ( is_object( $value ) || is_array( $value ) ) {
            $formatted_value = '<code class="ddtt-table-code"><pre>' . esc_html( print_r( $value, true ) ) . '</pre></code>'; // phpcs:ignore

        } elseif ( self::is_serialized_array( $value ) || self::is_serialized_object( $value ) ) {
            $unserialized_value = @unserialize( $value );
            if ( is_string( $unserialized_value ) && ( self::is_serialized_array( $unserialized_value ) || self::is_serialized_object( $unserialized_value ) ) ) {
                $unserialized_value = @unserialize( $unserialized_value );
            }
            $formatted_value = esc_html( $value ) . '<br><code class="ddtt-table-code"><pre>' . esc_html( print_r( $unserialized_value, true ) ) . '</pre></code>'; // phpcs:ignore 

        } elseif ( is_string( $value ) ) {
            $json_value = json_decode( $value, true );
            if ( json_last_error() === JSON_ERROR_NONE && ( is_array( $json_value ) || is_object( $json_value ) ) ) {
                $formatted_value = esc_html( $value ) . '<br><code class="ddtt-table-code"><pre>' . esc_html( print_r( $json_value, true ) ) . '</pre></code>'; // phpcs:ignore
            } else {
                $formatted_value = esc_html( $value );
            }

        } else {
            $formatted_value = esc_html( $value );
        }

        return $formatted_value;
    } // End print_stored_value_to_table()


    /**
     * Truncate a string to a specified length, optionally stripping tags and adding view more/less links.
     *
     * @param string $string The string to truncate.
     * @param bool $strip_tags Whether to strip HTML tags from the string.
     * @param int $length The maximum length of the truncated string.
     * @param string $suffix The suffix to append if truncation occurs.
     * @return string The truncated string with view more/less links if applicable.
     */
    public static function truncate_string( $string, $strip_tags = false, $length = 500, $suffix = '...' ) : string {
        $original_string = $string;
        $stripped_string = $strip_tags ? wp_strip_all_tags( $string ) : $string;

        if ( strlen( $stripped_string ) > $length ) {
            $short_text = esc_html( substr( $stripped_string, 0, $length ) ) . $suffix;

            $display_value = '<div class="ddtt-value-wrapper"><div class="ddtt-value-preview">' . $short_text . ' <a href="#" class="view-more">' . __( 'View More', 'dev-debug-tools' ) . '</a></div><div class="ddtt-value-full">' . wp_kses_post( $original_string ) . ' <a href="#" class="view-less">' . __( 'View Less', 'dev-debug-tools' ) . '</a></div></div>';
        } else {
            $display_value = wp_kses_post( $original_string );
        }

        return $display_value;
    } // End truncate_string()


    /**
     * Get the php_eol type we are using for the file
     *
     * @param string $file
     * @return string
     */
    public static function get_eol_char( $eol ) : string {
        $eol_types = [
            '\r\n' => "\r\n",
            '\n\r' => "\n\r",
            '\n'   => "\n",
            '\r'   => "\r"
        ];
        if ( isset( $eol_types[ $eol ] ) ) {
            return $eol_types[ $eol ];
        }
        return PHP_EOL;
    } // End get_eol_char()


    /**
     * Get the eol type(s) being used by a file
     *
     * @param string $file
     * @return array
     */
    public static function get_file_eol( $file_contents, $incl_code = false ) : string {
        $types = [
            '\r\n' => "/(?<!\n)\r\n(?!\r)/",
            '\n\r' => "/(?<!\r)\n\r(?!\n)/",
            '\n'   => "/(?<!\r)\n/",
            '\r'   => "/\r(?!\n)/"
        ];

        $counts = [];
        foreach ( $types as $type => $regex ) {
            preg_match_all( $regex, $file_contents, $matches );
            $counts[ $type ] = count( $matches[0] );
        }

        // Determine the dominant type
        arsort( $counts ); // Sort descending by count
        $dominant_type = key( $counts );

        // If no EOL found, default to \n
        if ( $counts[ $dominant_type ] === 0 ) {
            $dominant_type = '\n';
        }

        return ( $incl_code ) ? '<code class="hl">' . $dominant_type . '</code>' : $dominant_type;
    } // End get_file_eol()


    /**
     * Get the contents of a file.
     *
     * @param string $relative_path The relative path to the file.
     * @return string|WP_Error
     */
    public static function get_file_contents( $relative_path ) {
        global $wp_filesystem;

        if ( ! function_exists( 'request_filesystem_credentials' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        if ( ! WP_Filesystem() ) {
            return new \WP_Error( 'filesystem_init_failed', __( 'Could not initialize WordPress filesystem.', 'dev-debug-tools' ) );
        }

        if ( ! is_object( $wp_filesystem ) ) {
            return new \WP_Error( 'filesystem_missing', __( 'Filesystem object is not available.', 'dev-debug-tools' ) );
        }

        $abs_path = ABSPATH . ltrim( $relative_path, '/' );

        if ( ! $wp_filesystem->exists( $abs_path ) ) {
            return new \WP_Error( 'file_not_found', __( 'File not found.', 'dev-debug-tools' ) );
        }

        $contents = $wp_filesystem->get_contents( $abs_path );

        if ( $contents === false ) {
            return new \WP_Error( 'read_failed', __( 'Failed to read file contents.', 'dev-debug-tools' ) );
        }

        return $contents;
    } // End get_file_contents()


    /**
     * Get the mime type of a file.
     *
     * @param string $filename The name of the file.
     * @return string The mime type of the file.
     */
    public static function get_mime_type( $filename ) {
        // Try WordPress' built-in
        $filetype = wp_check_filetype( $filename );

        if ( ! empty( $filetype[ 'type' ] ) ) {
            return $filetype[ 'type' ];
        }

        // Custom fallbacks for extensions WordPress does not map
        $custom_mime_map = [
            'htaccess'     => 'text/plain',
            'htpasswd'     => 'text/plain',
            'gitignore'    => 'text/plain',
            'editorconfig' => 'text/plain',
            'log'          => 'text/plain',
            'sql'          => 'application/sql',
            'sh'           => 'application/x-sh',
            'env'          => 'text/plain',
            'conf'         => 'text/plain',
            'ini'          => 'text/plain',
            'json'         => 'application/json',
            'xml'          => 'application/xml',
            'yaml'         => 'application/x-yaml',
            'yml'          => 'application/x-yaml',
            'php'          => 'application/x-php',
            'md'           => 'text/markdown',
        ];

        $ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

        if ( isset( $custom_mime_map[ $ext ] ) ) {
            return $custom_mime_map[ $ext ];
        }

        return 'application/octet-stream';
    } // End get_mime_type()


    /**
     * Detect the user's operating system based on the HTTP_USER_AGENT.
     *
     * @return string Operating system name (windows, mac, linux, unknown).
     */
    public static function get_os() {
        $user_agent = isset( $_SERVER[ 'HTTP_USER_AGENT' ] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER[ 'HTTP_USER_AGENT' ] ) ) ) : '';

        if ( strpos( $user_agent, 'windows' ) !== false ) {
            return 'windows';
        } elseif ( strpos( $user_agent, 'macintosh' ) !== false || strpos( $user_agent, 'mac os' ) !== false ) {
            return 'mac';
        } elseif ( strpos( $user_agent, 'linux' ) !== false ) {
            return 'linux';
        }

        return 'unknown';
    } // End get_os()


    /**
     * Check a URL to see if it exists
     *
     * @param string $url
     * @param integer|null $timeout
     * @return array
     */
    public static function check_url_status_code( $url ) : array {
        // Add the home url
        if ( str_starts_with( $url, '/' ) ) {
            $link = home_url() . $url;
        } else {
            $link = $url;
        }

        // The request args
        // See https://developer.wordpress.org/reference/classes/WP_Http/request/
        $http_request_args = [
            'method'      => 'GET',
            'timeout'     => 5,        // How long the connection should stay open in seconds. Default 5.
            'redirection' => 0,        // Number of allowed redirects. Not supported by all transports. Default 5.
            'httpversion' => '1.1',    // Version of the HTTP protocol to use. Accepts '1.0' and '1.1'. Default '1.0'.
            'sslverify'   => false
        ];

        // Store the message text
        $text = '';

        // Check the link
        $response = wp_safe_remote_get( $link, $http_request_args );
        if ( ! is_wp_error( $response ) ) {
            $code = wp_remote_retrieve_response_code( $response );
            if ( $code !== 200 ) {
                $body = wp_remote_retrieve_body( $response );
                if ( ! is_wp_error( $body ) ) {
                    $decoded = json_decode( $body, true );
                    if ( isset( $decoded[ 'data' ][ 'status' ] ) && $decoded[ 'message' ] ) {
                        $code = $decoded[ 'data' ][ 'status' ];
                        $text = '. ' . $decoded[ 'message' ];
                    }
                }
                $error = $text;
            }
            $error = 'Unknown';
        } else {
            $code = 0;
            $error = $response->get_error_message();
        }

        // Possible Codes
        $codes = [
            0   => $error,
            100 => 'Continue',
            101 => 'Switching Protocols',
            102 => 'Processing', // WebDAV; RFC 2518
            103 => 'Early Hints', // RFC 8297
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information', // since HTTP/1.1
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content', // RFC 7233
            207 => 'Multi-Status', // WebDAV; RFC 4918
            208 => 'Already Reported', // WebDAV; RFC 5842
            226 => 'IM Used', // RFC 3229
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found', // Previously "Moved temporarily"
            303 => 'See Other', // since HTTP/1.1
            304 => 'Not Modified', // RFC 7232
            305 => 'Use Proxy', // since HTTP/1.1
            306 => 'Switch Proxy',
            307 => 'Temporary Redirect', // since HTTP/1.1
            308 => 'Permanent Redirect', // RFC 7538
            400 => 'Bad Request',
            401 => 'Unauthorized', // RFC 7235
            402 => 'Payment Required',
            403 => 'Forbidden or Unsecure',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required', // RFC 7235
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed', // RFC 7232
            413 => 'Payload Too Large', // RFC 7231
            414 => 'URI Too Long', // RFC 7231
            415 => 'Unsupported Media Type', // RFC 7231
            416 => 'Range Not Satisfiable', // RFC 7233
            417 => 'Expectation Failed',
            418 => 'I\'m a teapot', // RFC 2324, RFC 7168
            421 => 'Misdirected Request', // RFC 7540
            422 => 'Unprocessable Entity', // WebDAV; RFC 4918
            423 => 'Locked', // WebDAV; RFC 4918
            424 => 'Failed Dependency', // WebDAV; RFC 4918
            425 => 'Too Early', // RFC 8470
            426 => 'Upgrade Required',
            428 => 'Precondition Required', // RFC 6585
            429 => 'Too Many Requests', // RFC 6585
            431 => 'Request Header Fields Too Large', // RFC 6585
            451 => 'Unavailable For Legal Reasons', // RFC 7725
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            506 => 'Variant Also Negotiates', // RFC 2295
            507 => 'Insufficient Storage', // WebDAV; RFC 4918
            508 => 'Loop Detected', // WebDAV; RFC 5842
            510 => 'Not Extended', // RFC 2774
            511 => 'Network Authentication Required', // RFC 6585

            // Unofficial codes
            103 => 'Checkpoint',
            218 => 'This is fine', // Apache Web Server
            419 => 'Page Expired', // Laravel Framework
            420 => 'Method Failure', // Spring Framework
            420 => 'Enhance Your Calm', // Twitter
            430 => 'Request Header Fields Too Large', // Shopify
            450 => 'Blocked by Windows Parental Controls', // Microsoft
            498 => 'Invalid Token', // Esri
            499 => 'Token Required', // Esri
            509 => 'Bandwidth Limit Exceeded', // Apache Web Server/cPanel
            526 => 'Invalid SSL Certificate', // Cloudflare and Cloud Foundry's gorouter
            529 => 'Site is overloaded', // Qualys in the SSLLabs
            530 => 'Site is frozen', // Pantheon web platform
            598 => 'Network read timeout error', // Informal convention
            440 => 'Login Time-out', // IIS
            449 => 'Retry With', // IIS
            451 => 'Redirect', // IIS
            444 => 'No Response', // nginx
            494 => 'Request header too large', // nginx
            495 => 'SSL Certificate Error', // nginx
            496 => 'SSL Certificate Required', // nginx
            497 => 'HTTP Request Sent to HTTPS Port', // nginx
            499 => 'Client Closed Request', // nginx
            520 => 'Web Server Returned an Unknown Error', // Cloudflare
            521 => 'Web Server Is Down', // Cloudflare
            522 => 'Connection Timed Out', // Cloudflare
            523 => 'Origin Is Unreachable', // Cloudflare
            524 => 'A Timeout Occurred', // Cloudflare
            525 => 'SSL Handshake Failed', // Cloudflare
            526 => 'Invalid SSL Certificate', // Cloudflare
            527 => 'Railgun Error', // Cloudflare
            666 => $error, // Our own error converted from 0
            999 => 'Scanning Not Permitted' // Non-standard code
        ];

        // Filter status
        $status = [
            'code' => $code,
            'text' => isset( $codes[ $code ] ) ? $codes[ $code ] . $text : $error . $text,
        ];

        // Return the array
        return $status;
    } // End check_url_status_code()


    /**
     * Canonical pathname
     *
     * @param string $pathname
     * @return string
     */
    public static function canonical_pathname( $pathname ) {
        return str_replace( DIRECTORY_SEPARATOR, '/', $pathname );
    } // End canonical_pathname()


    /**
     * Relative pathname
     *
     * @param string $pathname
     * @return string
     */
    public static function relative_pathname( $pathname ) {
        $pathname = self::canonical_pathname( $pathname );
        $abspath = self::canonical_pathname( ABSPATH );
        if ( !str_starts_with( $pathname, $abspath ) ) {
            return $pathname;
        } else {
            return substr( $pathname, strlen( $abspath ) );
        }
    } // End relative_pathname()


    /**
     * Get the default debug log path
     *
     * @return string
     */
    public static function get_default_debug_log_path( $relative = false ) : string {
        if ( defined( 'WP_DEBUG_LOG' ) && is_string( WP_DEBUG_LOG ) && WP_DEBUG_LOG !== '' ) {
            $log_path = wp_normalize_path( WP_DEBUG_LOG );
        } else {
            $log_path = wp_normalize_path( WP_CONTENT_DIR . '/debug.log' );
        }
        if ( ! $relative ) {
            return $log_path;
        }
        return str_replace( untrailingslashit( ABSPATH ) . '/', '', $log_path );
    } // End get_default_debug_log_path()


    /**
     * Check if a filesystem path exists and is readable
     *
     * @param string $value The filesystem path to check.
     * @return bool True if the path exists and is readable, false otherwise.
     */
    public static function path_exists( $path ) : bool {
        global $wp_filesystem;
        if ( ! function_exists( 'request_filesystem_credentials' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        if ( ! WP_Filesystem() || ! is_object( $wp_filesystem ) ) {
            return false;
        }

        // Normalize path — if it's not absolute, prepend ABSPATH.
        if ( strpos( $path, ABSPATH ) !== 0 ) {
            $path = wp_normalize_path( ABSPATH . ltrim( $path, '/' ) );
        } else {
            $path = wp_normalize_path( $path );
        }

        return $wp_filesystem->exists( $path ) && $wp_filesystem->is_readable( $path );
    } // End path_exists()


    /**
     * Get the latest login timestamp from a user's session tokens
     *
     * @param int $user_id User ID to check.
     *
     * @return int|null Unix timestamp of latest login, or null if none found.
     */
    public static function get_session_token_login( $user_id ) {
        $sessions = get_user_meta( $user_id, 'session_tokens', true );
        if ( empty( $sessions ) || ! is_array( $sessions ) ) {
            return null;
        }

        // Extract all login timestamps
        $timestamps = wp_list_pluck( $sessions, 'login' );
        if ( empty( $timestamps ) ) {
            return null;
        }

        // Return the latest login timestamp
        return max( $timestamps );
    } // End get_session_token_login()


    /**
     * Get the time format choices with current time examples
     *
     * @return array
     */
    public static function get_time_format_choices() {
        $current_time = time();
        $dev_timezone_string = sanitize_text_field( get_option( 'ddtt_dev_timezone', get_option( 'timezone_string', 'UTC' ) ) );

        try {
            $dev_timezone = new \DateTimeZone( $dev_timezone_string );
        } catch ( \Exception $e) {
            $dev_timezone = new \DateTimeZone( 'UTC' );
        }

        $time_format_choices = [
            'n/j/Y g:i a T'  => wp_date( 'n/j/Y g:i a T', $current_time, $dev_timezone ) . ' ( n/j/Y g:i a T )',
            'n/j/Y H:i T'    => wp_date( 'n/j/Y H:i T', $current_time, $dev_timezone ) . ' ( n/j/Y H:i T )',
            'F j, Y g:i a T' => wp_date( 'F j, Y g:i a T', $current_time, $dev_timezone ) . ' ( F j, Y g:i a T )',
            'F j, Y G:i T'   => wp_date( 'F j, Y G:i T', $current_time, $dev_timezone ) . ' ( F j, Y G:i T )',
            'Y-m-d H:i:s'    => wp_date( 'Y-m-d H:i:s', $current_time, $dev_timezone ) . ' ( Y-m-d H:i:s )',
            'm/d/Y g:i a'    => wp_date( 'm/d/Y g:i a', $current_time, $dev_timezone ) . ' ( m/d/Y g:i a )',
            'm/d/Y H:i'      => wp_date( 'm/d/Y H:i', $current_time, $dev_timezone ) . ' ( m/d/Y H:i )',
            'D, M j, Y g:i a'=> wp_date( 'D, M j, Y g:i a', $current_time, $dev_timezone ) . ' ( D, M j, Y g:i a )',
            'D, M j, Y H:i'  => wp_date( 'D, M j, Y H:i', $current_time, $dev_timezone ) . ' ( D, M j, Y H:i )',
        ];


        /**
         * Filter the date format choices used in Dev Debug Tools.
         */
        $time_format_choices = apply_filters( 'ddtt_time_format_choices', $time_format_choices );

        return $time_format_choices;
    } // End get_time_format_choices()


    /**
     * Get the Must-Use Plugins directory path
     *
     * @return string
     */
    public static function get_mu_plugins_dir() : string {
        return wp_normalize_path( WP_CONTENT_DIR . '/mu-plugins/' );
    } // End get_mu_plugins_dir()


    /**
     * Remove the old MU plugins
     * 
     * @return bool True if the action was successful, false otherwise.
     */
    public static function remove_mu_plugins() : bool {
        if ( ! function_exists( 'WP_Filesystem' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        global $wp_filesystem;
        if ( ! WP_Filesystem() ) {
            Helpers::write_log( __( 'Failed to initialize WP_Filesystem.', 'dev-debug-tools' ) );
            return false;
        }

        $filenames = [
            '000-set-debug-level.php',
            '000-suppress-errors.php',
        ];

        $dir = self::get_mu_plugins_dir();
        $success = true;

        foreach ( $filenames as $filename ) {
            $file_path = wp_normalize_path( trailingslashit( $dir ) . $filename );

            if ( $wp_filesystem->exists( $file_path ) ) {
                if ( $wp_filesystem->delete( $file_path ) ) {
                    Helpers::write_log(
                        /* translators: %s: MU plugin name */
                        sprintf( __( '"%s" must-use plugin has been removed.', 'dev-debug-tools' ), $filename )
                    );
                } else {
                    Helpers::write_log(
                        /* translators: 1: MU plugin name, 2: directory path */
                        sprintf( __( '"%1$s" must-use plugin could not be deleted. Please remove the "%1$s" file from "%2$s" via FTP or File Manager.', 'dev-debug-tools' ),
                            $filename,
                            $dir
                        )
                    );
                    $success = false;
                }
            }
        }

        return $success;
    } // End remove_mu_plugins()

}