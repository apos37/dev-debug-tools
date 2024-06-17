<?php
/**
 * Functions that can be used globally.
 * If you are using these functions outside of the plugin, 
 * please wrap with `if ( is_plugin_active( 'dev-debug-tools/dev-debug-tools.php' ) ) { ... }`
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Add a JS alert for debugging
 *
 * @param string $msg
 * @param int $user_id
 * @return bool
 */
function ddtt_alert( $msg, $user_id = null ) {
    if ( ddtt_is_dev() || !is_null($user_id) && get_current_user_id() == $user_id ) {
        echo '<script type="text/javascript">alert("'.esc_html( $msg ).'");</script>';
        return true;
    } else {
        return false;
    }
} // End ddtt_alert()


/**
 * Console log with PHP
 *
 * @param string|array|object $msg
 * @param int $user_id
 * @return void
 */
function ddtt_console( $msg ) {
    if ( is_array( $msg ) ) {
        $msg = json_encode( $msg );
    } elseif ( is_object( $msg ) ) {
        $msg = json_encode( $msg );
    }
    echo '<script type="text/javascript">console.log("'.wp_kses_post( str_replace( '"', '\"', $msg ) ).'");</script>';
} // End ddtt_console()


/**
 * Log a message or variable to the debug.log
 *
 * @param mixed $log
 * @param boolean|string $prefix
 * @param boolean $backtrace
 * @param boolean $full_stacktrace
 * @return void
 */
function ddtt_write_log( $log, $prefix = true, $backtrace = false, $full_stacktrace = false ) {
    // Make sure debugging is enabled
    if ( true === WP_DEBUG ) {

        // Options not allowed for arrays/objects
        if ( is_array( $log ) || is_object( $log ) ) {
            error_log( print_r( $log, true ) );

        // Options for non-arrays/non-objects
        } else {

            // Are we including the prefix?
            if ( is_bool( $prefix ) && $prefix == true ) {
                $pf = 'DDTT LOG: ';
            } elseif ( $prefix != '' ) {
                $pf = $prefix;
            } else {
                $pf = '';
            }

            // Are we including backtrace?
            if ( $backtrace ) {

                $backtrace = debug_backtrace();
                $log = $log.' in '.$backtrace[0][ 'file' ].' on line '.$backtrace[0][ 'line' ];
                
                // Stacktrace
                if ( $full_stacktrace && count( $backtrace ) > 1 ) {

                    // The stack
                    $stack = [];
                    $stack[] = 'Stack trace:';
                    foreach( $backtrace as $key => $bt ) {
                        $stack[] = '#'.$key.' '.$bt[ 'file' ].'('.$bt[ 'line' ].'): '.$bt[ 'function' ].'()';
                    }
                    
                    // Add it
                    $log .= "\n".implode( "\n", $stack );
                }
            }

            // Log it
            error_log( esc_html( $pf.$log ) );
        }
    }
} // End ddtt_write_log()


/**
 * Display an error message for admins only
 *
 * @param string $msg
 * @param boolean $include_pre
 * @param boolean $br
 * @param boolean $hide_error
 * @return string
 */
function ddtt_admin_error( $msg, $include_pre = true, $br = true, $hide_error = false ) {
    // Errors should only be seen by admins, and may be hidden by another function
    if ( ddtt_has_role( 'administrator' ) && !$hide_error ) {

        // Should we include line breaks?
        $display_br = $br ? '<br>' : '';

        // Should we add ADMIN ERROR: before the error?
        $display_pre = $include_pre ? 'ADMIN ERROR: ' : '';

        // Return the error
        return $display_br.'<span class="notice error">'.$display_pre.$msg.'</span>';

    // Otherwise return nothing to everyone else
    } else {
        return '';
    }
} // End ddtt_admin_error()


/**
 * Check if a user has a role
 *
 * @param string $role
 * @param int $user_id
 * @return void
 */
function ddtt_has_role( $role, $user_id = null ) {
    // First verify that $role is not null
    if ( $role == false || is_null( $role ) ){
        return false;
    }

    // Get the user id
    if ( is_null( $user_id ) ) {
        $user_id = get_current_user_id();
    }

    // Get the user
    if ( $user = get_user_by( 'id', $user_id ) ) {

        // Get their roles
        $roles = $user->roles;

        // Check if role exists
        if ( is_array( $roles ) && in_array( $role, $roles ) ) {
            return true;

        } else {
            return false;
        }
    } else {
        return false;
    }
} // End ddtt_has_role()


/**
 * Get current URL with query string
 *
 * @param boolean $params
 * @param boolean $domain
 * @return string
 */
function ddtt_get_current_url( $params = true, $domain = true ) {
    // Are we including the domain?
    if ( $domain == true ) {

        // Get the protocol
        $protocol = isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] !== 'off' ? 'https' : 'http';

        // Get the domain
        $domain_without_protocol = sanitize_text_field( $_SERVER[ 'HTTP_HOST' ] );

        // Domain with protocol
        $domain = $protocol.'://'.$domain_without_protocol;

    } elseif ( $domain == 'only' ) {

        // Get the domain
        $domain = sanitize_text_field( $_SERVER[ 'HTTP_HOST' ] );
        return $domain;

    } else {
        $domain = '';
    }

    // Get the URI
    $uri = filter_input( INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_URL );

    // Put it together
    $full_url = $domain.$uri;

    // Are we including query string params?
    if ( !$params ) {
        return strtok( $full_url, '?' );
        
    } else {
        return $full_url;
    }
} // End ddtt_get_current_url()


/**
 * Remove query strings from url without refresh
 */
function ddtt_remove_qs_without_refresh( $qs = null, $is_admin = true ) {
    // Get the current title
    $page_title = get_the_title();

    // Get the current url without the query string
    if ( !is_null( $qs ) ) {

        // Check if $qs is an array
        if ( !is_array( $qs ) ) {
            $qs = [ $qs ];
        }
        $new_url = remove_query_arg( $qs, ddtt_get_current_url() );

    } else {
        $new_url = ddtt_get_current_url( false );
    }

    // Write the script
    $args = [ 
        'title' => $page_title,
        'url' => $new_url
    ];

    // Admin or not
    if ( $is_admin ) {
        $hook = 'admin_footer';
    } else {
        $hook = 'wp_footer';
    }

    // Add the script to the admin footer
    add_action( $hook, function() use ( $args ) {
        echo '<script id="ddtt_remove_qs_without_refresh">
        if ( history.pushState ) { 
            var url = window.location.href; 
            var obj = { Title: "'.esc_html( $args[ 'title' ] ).'", Url: "'.esc_url_raw( $args[ 'url' ] ).'"}; 
            window.history.pushState( obj, obj.Title, obj.Url ); 
        }
        </script>';
    } );

    // Return
    return;
} // End ddtt_remove_qs_without_refresh()


/**
 * Convert timezone
 * 
 * @param string $date
 * @param string $format
 * @param string $timezone
 * @return string
 */
function ddtt_convert_timezone( $date = null, $format = 'F j, Y g:i A', $timezone = null ) {
    // Get today as default
    if ( is_null( $date ) || !$date ) {
        $date = date( 'Y-m-d H:i:s' );

    // Or else format it properly for converting purposes
    } else {

        // Check if the date is a timestamp
        if ( is_numeric( $date ) && (int)$date == $date ) {
            $date = date( 'Y-m-d H:i:s', $date );
        } else {
            $date = date( 'Y-m-d H:i:s', strtotime( $date ) );
        }
    }

    // Get the date in UTC time
    $date = new DateTime( $date, new DateTimeZone( 'UTC' ) );

    // Get the timezone string
    if ( !is_null( $timezone ) ) {
        $timezone_string = $timezone;
    } else {
        $timezone_string = wp_timezone_string();
    }

    // Set the timezone to the new one
    $date->setTimezone( new DateTimeZone( $timezone_string ) );

    // Format it the way we way
    $new_date = $date->format( $format );

    // Return it
    return $new_date;
} // End ddtt_convert_timezone()


/**
 * Simplified/sanitized version of $_GET
 *
 * @param string $qs_param
 * @param string $comparison
 * @param string $equal_to
 * @return string|false
 */
function ddtt_get( $qs_param, $comparison = '!=', $equal_to = '' ) {
    // Get if the query string exists at all
    if ( isset( $_GET[ $qs_param ] ) ) {
        $_GET = filter_input_array( INPUT_GET, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $get = $_GET[ $qs_param ];

        // How are we comparing?
        if ( $comparison == '!=' ) {
            if ( $get != $equal_to ) {
                return $get;
            } else {
                return false;
            }
        } elseif ( $comparison == '==' ) {
            if ( $get == $equal_to ) {
                return $get;
            } else {
                return false;
            }
        } else {
            return false;
        }
    } else {
        return false;
    }
} // End ddtt_get()


/**
 * Define user email and check for it
 * 
 * @param string $email
 * @return bool
 */
function ddtt_is_dev( $email = false, $array = false ){
    // Option
    $option = DDTT_GO_PF.'dev_email';

    // Get the developer email address
    if ( get_option( $option ) && get_option( $option ) != '' ) {
        $dev_string = get_option( $option );

    // Or revert to admin email address if one is not set
    } else {
        $dev_string = get_option( 'admin_email' );
    }

    // Turn into an array
    $dev_array = explode( ',', str_replace( ' ', '', $dev_string ) );

    // Check if we are simply returning the developer email
    if ( $email ) {
        if ( $array ) {
            return $dev_array;
        } else {
            return $dev_string;
        }
    }

    // Otherwise get the current user
    $user_id = get_current_user_id();
    $user_info = get_userdata( $user_id );
    $email_address = isset( $user_info->user_email ) ? $user_info->user_email : '';

    // Check if the current user is the developer or admin
    $me = false;
    if ( in_array( $email_address, $dev_array ) ) {
        $me = true;
    }

    // Return true or false
    return $me;
} // End ddtt_is_dev()


/**
 * Wrap array print_r in <pre> tags </pre>
 * Only display on front end if current user is dev
 *
 * @param array|string|bool $array
 * @param int|array $user_id
 * @param bool|int $left_margin
 * @return void
 */

function ddtt_print_r( $var, $user_id = null, $left_margin = null, $write_bool = true ) {
    // Current user
    $current_user_id = get_current_user_id();

    // Single id provided
    if ( !is_null( $user_id ) && !is_array( $user_id ) && $user_id != $current_user_id ) {
        return;

    // Multiple ids provided
    } elseif ( !is_null( $user_id ) && is_array( $user_id ) && !in_array( $current_user_id, $user_id ) ) {
        return;
    
    // No id provided
    } elseif ( is_null( $user_id ) && !ddtt_is_dev() ) {
        return;
    }

    // Add a margin
    if ( is_numeric( $left_margin ) ) {
        $margin = $left_margin;
    } elseif ( $left_margin == true ) {
        $margin = 200;
    } else {
        $margin = 0;
    }

    // Convert bool
    if ( $write_bool && is_bool( $var ) ) {
        if ( ddtt_is_enabled( $var ) ) {
            $var = 'TRUE';
        } else {
            $var = 'FALSE';
        }
    }

    // Return the array
    echo '<pre style="margin-left: '.esc_attr( $margin ).'px; overflow-x: unset;">'; wp_kses_post( print_r( $var ) ); echo '</pre>';
} // End ddtt_print_r()

// Allow shortcut
if ( !function_exists( 'dpr' ) ) {
    function dpr( $var, $user_id = NULL, $left_margin = null, $write_bool = true ) {
        ddtt_print_r( $var, $user_id, $left_margin, $write_bool );
    } 
} // End wpr()

// Allow old name of function for ERI
if ( !function_exists( 'eri_print_r' ) ) {
    function eri_print_r( $array, $user_id = NULL, $left_margin = null ) {
        ddtt_print_r( $array, $user_id, $left_margin );
    } 
} // End ddtt_print_r()


/**
 * Convert var_dump to string
 * Useful for printing errors in CSV exports
 *
 * @param [type] $var
 * @return string
 */
function ddtt_var_dump_to_string( $var ) {
    ob_start();
    var_dump( $var );
    return ob_get_clean();
} // End ddtt_var_dump_to_string()


/**
 * Check if we are on a specific website
 * May use partial words, such as "eri-wi" for eri-wi.org
 *
 * @param string $site_to_check
 * @return bool
 */
function ddtt_is_site( $site_to_check ) {
    // Get the current domain
    $current_domain = ddtt_get_domain();

    // Lowercase
    $site_to_check = strtolower( $site_to_check );
    $current_domain = strtolower( $current_domain );

    // Check if what we're searching is in the domain
    if ( strpos($current_domain, $site_to_check) !== false ) {
        $result = true;
    } else {
        $result = false;
    }

    // Return the result
    return $result;
} // End ddtt_is_site()


/**
 * Get just the domain without the https://
 * Option to capitalize the first part
 *
 * @param boolean $capitalize
 * @return string
 */
function ddtt_get_domain( $capitalize = false, $remove_ext = false, $incl_protocol = false ) {
    // Get the domain
    $domain = sanitize_text_field( $_SERVER[ 'SERVER_NAME' ] );

    // Are we capitalizing
    if ( $capitalize || $remove_ext ) {

        // Get the position of the ext
        $pos = strrpos( $domain, '.' );

        // Make the first part uppercase
        if ( $capitalize ) {
            $prefix = strtoupper( substr( $domain, 0, $pos ) );
        } else {
            $prefix = substr( $domain, 0, $pos );
        }
        
        // Get the extension
        $suffix = substr( $domain, $pos + 1 );

        // Put it back together
        if ( !$remove_ext ) {
            $domain = $prefix.'.'.$suffix;
        } else {
            $domain = $prefix;
        }
    }

    // Include the protocol
    if ( $incl_protocol ) {
        if ( isset( $_SERVER[ 'HTTPS' ] ) && ( sanitize_text_field( $_SERVER[ 'HTTPS' ] ) == 'on' || sanitize_text_field( $_SERVER[ 'HTTPS' ] ) == 1 ) ||
            isset( $_SERVER[ 'HTTP_X_FORWARDED_PROTO' ] ) && sanitize_text_field( $_SERVER[ 'HTTP_X_FORWARDED_PROTO' ] ) == 'https' ) {
            $protocol = 'https://';
        } else {
            $protocol = 'http://';
        }
        $domain = $protocol.$domain;
    }

    // Return it
    return $domain;
} // End ddtt_get_domain()


/**
 * Increase Debug Test Number by 1
 *
 * @param boolean $reset
 * @return void
 */
function ddtt_increase_test_number( $reset = false ) {
    // Reset the number
    if ( $reset ) {
        update_option( 'ddtt_test_number', 0 );

    // Or increase it
    } else {
        $count = get_option( 'ddtt_test_number', 0 );
        $count++;
        update_option( 'ddtt_test_number', $count );
    }
} // End ddtt_increase_test_number()


/**
 * Get user meta
 */
function ddtt_user_meta( $meta_key, $id = null, $default = false ) {
    // Get the user info
    $user_id = get_current_user_id();
    if ( !is_null( $id ) ) {
        $user_id = $id;
    }

    if ( get_userdata( $user_id ) ) {
        $user_info = get_userdata( $user_id );

        // Get meta value
        if ( isset( $user_info->$meta_key ) && $user_info->$meta_key != '' ) {
            $meta_value = $user_info->$meta_key;

        // Or show error message
        } else {
            return $default;
        }
        
        // Check if array and convert to string if it is
        if ( is_array( $meta_value ) && !empty( $meta_value ) ) {
            $results = implode( ', ', $meta_value );
        } else {
            $results = $meta_value;
        }
    
        // Otherwise return not found
    } else {
        return $default;
    }
    
    // Return the value
    return $results;
} // End ddtt_user_meta()


/**
 * Return true if current user is an admin with email from this domain
 *
 * @return boolean
 */
function is_ddtt_admin(){
    $user_info = get_userdata( get_current_user_id() );
    $user_email = $user_info->user_email;
    $explode = explode( '@', $user_email );
    $domain = array_pop( $explode );

    // output domain
    if ( $domain == ddtt_get_domain() && current_user_can( 'administrator' ) ) {
        return true;
    } else {
        return false;
    }
} // End is_ddtt_admin()


/**
 * Click to Copy
 *
 * @param string $unique_link_id
 * @param string $link_text
 * @param string $unique_copy_id
 * @param string $copy_text
 * @param boolean $include_copied_span
 * @return string
 */
function ddtt_click_to_copy($unique_link_id, $link_text, $unique_copy_id = null, $copy_text = null, $include_copied_span = false) {
    // First check if we are copying text
    if ($copy_text != null) {
        $content = 'let content = "'.$copy_text.'";';

    // If not, let's see if we're copying another div
    } elseif ($unique_copy_id != null) {
        $content = 'var e = document.getElementById("'.$unique_copy_id.'");
        let content = e;
        if (e instanceof HTMLElement) {
        	content = e.innerHTML;
        }';

    // Finally we will just copy the current text
    } else {
        $content = 'var e = document.getElementById("'.$unique_link_id.'");
        let content = e;
        if (e instanceof HTMLElement) {
        	content = e.innerHTML;
        }';
    }
    
    // Are we including the "- Copied" span?
    if ($include_copied_span) {
        $incl_copied_span = ' <span id="copied_'.$unique_link_id.'" class="click-to-copy"><strong>- Copied!</strong></span>';
    } else {
        $incl_copied_span = '';
    }

    // Create the link
    $results = '<a href="#" id="'.$unique_link_id.'" style="cursor: pointer;">'.$link_text.'</a>'.$incl_copied_span;

    // The script
    $results .= '<script>
    document.getElementById("'.$unique_link_id.'").onclick = function(e) {
        e.preventDefault();
        var tempItem = document.createElement("input");
        tempItem.setAttribute("type","text");
        tempItem.setAttribute("display","none");
        '.$content.'
        tempItem.setAttribute("value",htmlEntities(content));
        document.body.appendChild(tempItem);
        tempItem.select();
        document.execCommand("Copy");
        tempItem.parentElement.removeChild(tempItem);
        var c = document.getElementById("copied_'.$unique_link_id.'");
        c.style.display="inline-block";
        setTimeout(function () {
            c.style.display="none"
        }, 3000);
        console.log("Copied: " + htmlEntities(content));
    }
    function htmlEntities(str) {
        return String(str).replace("<!--?php", "<?php").replace("?-->", "?>").replaceAll("&amp;", "&");
    }
    </script>';
    
    return $results;
} // End ddtt_click_to_copy()


/**
 * Get contrast color (black or white) from hex color
 */
function ddtt_get_contrast_color( $hexColor ){
    // hexColor RGB
    $R1 = hexdec(substr($hexColor, 1, 2));
    $G1 = hexdec(substr($hexColor, 3, 2));
    $B1 = hexdec(substr($hexColor, 5, 2));

    // Black RGB
    $blackColor = "#000000";
    $R2BlackColor = hexdec(substr($blackColor, 1, 2));
    $G2BlackColor = hexdec(substr($blackColor, 3, 2));
    $B2BlackColor = hexdec(substr($blackColor, 5, 2));

     // Calc contrast ratio
     $L1 = 0.2126 * pow($R1 / 255, 2.2) +
           0.7152 * pow($G1 / 255, 2.2) +
           0.0722 * pow($B1 / 255, 2.2);

    $L2 = 0.2126 * pow($R2BlackColor / 255, 2.2) +
          0.7152 * pow($G2BlackColor / 255, 2.2) +
          0.0722 * pow($B2BlackColor / 255, 2.2);

    $contrastRatio = 0;
    if ($L1 > $L2) {
        $contrastRatio = (int)(($L1 + 0.05) / ($L2 + 0.05));
    } else {
        $contrastRatio = (int)(($L2 + 0.05) / ($L1 + 0.05));
    }

    // If contrast is more than 5, return black color
    if ($contrastRatio > 5) {
        return '#000000';
    } else { 
        // if not, return white color.
        return '#FFFFFF';
    }
} // End ddtt_get_contrast_color()


/**
 * Get the path to our front-end stylesheet
 *
 * @param string $type // Accepts 'css', 'js'
 * @param bool $path
 * @return array|false
 */
function ddtt_get_styles( $path = true ) {
    // Full directory path
    $dir = DDTT_PLUGIN_ADMIN_PATH.'css/';

    // Scan the css folder
    $scan = scandir( $dir );

    // Files to exclude
    $exclude = ['.', '..', 'index.php'];

    // Store the files here
    $files = [];

    // Cycle through each filename
    foreach( $scan as $file ) {

        // Check if the directory exists, the file is not excluded
        if ( !is_dir( $dir.$file ) && !in_array( $file, $exclude ) ) {
            
            // Are we returning the full path or just the filename?
            if ( $path ) {
                $files[] = $dir.$file;
            } else {
                $files[] = $file;
            }
        }
    }

    // Return the array
    if ( !empty( $files ) ) {
        return $files;
    } else {
        return false;
    }
} // End ddtt_get_styles()


/**
 * Get the path to files that include a keyword
 *
 * @param string $keyword
 * @param string|array $exclude
 * @param string|false $path
 * @return array
 */
function ddtt_get_files( $keyword, $exclude = [], $path = false ) {
    // Full directory path
    if ( $path ) {
        $dir = ABSPATH.$path;
    } else {
        $dir = ABSPATH;
    }

    // Scan the css folder
    $scan = scandir( $dir );

    // Files to exclude
    $exc = ['.', '..', 'index.php'];
    if ( is_array( $exclude ) && !empty( $exclude ) ) {
        $exc = array_merge( $exc, $exclude );
    } elseif ( !is_array( $exclude ) && $exclude != '' ) {
        $exc[] = $exclude;
    }

    // Store the files here
    $files = [];

    // Cycle through each filename
    foreach( $scan as $file ) {

        // Check if the directory exists, the file is not excluded
        if ( !is_dir( $dir.$file ) && !in_array( $file, $exc ) ) {

            // Check if it contains the keyword
            if ( strpos( $file, $keyword ) !== false ) {

                // Return the file
                $files[] = $dir.$file;
            }
        }
    }

    // Return the array
    return $files;
} // End ddtt_get_files()


/**
 * Debug $_POST via Email
 * USAGE: ddtt_debug_form_post( 'yourname@youremail.com', 2 );
 *
 * @param string $email
 * @param integer $test_number
 * @param string $subject
 * @return void
 */
function ddtt_debug_form_post( $email, $test_number = 1, $subject = 'Test Form $_POST ' ){
    // First check if $_POST exists
    if ( $_POST ) {
        $_POST = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        ddtt_print_r( '$_POST found!' );
        ddtt_console( '$_POST found!' );
    } else {
        ddtt_print_r( '$_POST not found!' );
        ddtt_console( '$_POST not found!' );
        return false;
    }

    // Store the message here
    $message = '';

    // Cycle through each $_POST and format them
    foreach ( $_POST as $key => $value ) {
        if ( is_array( $value ) ) {
            $value = 'ARRAY: '.implode( ', ', $value );
        } elseif ( is_object( $value ) ) {
            $value = 'OBJECT: '.$value;
        }
        if ( htmlspecialchars( $key ) != 'content' && htmlspecialchars( $key ) != 'post_content' ) {
            $message .= "Field: ".htmlspecialchars( $key )." is ".htmlspecialchars( $value )."\r\n";
        }
    }
    
    // Add the from header
    $headers[] = 'From: '.sanitize_option( 'blogname', get_option( 'blogname' ) ).' '.sanitize_option( 'admin_email', get_option( 'admin_email' ) );
    
    // Send the email
    if ( wp_mail( $email, $subject.$test_number, $message, $headers ) ) {
        ddtt_print_r( 'Email was sent to '.$email );
    }
} // End ddtt_debug_form_post()


/**
 * Return a user id if the user id or user email is in query string
 *
 * @param string $qs
 * @return int|false
 */
function ddtt_get_user_from_query_string( $qs = 'user' ) {
    if ( $s = ddtt_get( $qs ) ) {

        // Get the user from the search
        if ( filter_var( $s, FILTER_VALIDATE_EMAIL ) ) {
            $s = strtolower( $s );
            if ( $user = get_user_by( 'email', $s ) ) {
                $user_id = $user->ID;
            } else {
                return false;
            }
        } elseif ( is_numeric( $s ) ) {
            if ( get_user_by( 'id', $s ) ) {
                $user_id = $s;
            } else {
                return false;
            }
        } else {
            return false;
        }
    } else {
        $user_id = get_current_user_id();
    }
    return $user_id;
} // End ddtt_get_user_from_query_string()


/**
 * Check if something is truely true
 * Returns true if variable is true, True, TRUE, 'true', 1
 * Returns false if variable is false, 0, or any other string
 *
 * @param [mixed] $variable
 * @return boolean
 */
function ddtt_is_enabled( $variable ) {
    if ( !isset( $variable ) ) return null;
    return filter_var( $variable, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
} // End ddtt_is_enabled()


/**
 * Add string comparison functions to earlier versions of PHP
 *
 * @param string $haystack
 * @param string $needle
 * @return bool
 */
if ( version_compare( PHP_VERSION, 8.0, '<=' ) && !function_exists( 'str_starts_with' ) ) {
    function str_starts_with( $haystack, $needle ) {
        return strpos( $haystack , $needle ) === 0;
    }
}
if ( version_compare( PHP_VERSION, 8.0, '<=' ) && !function_exists( 'str_ends_with' ) ) {
    function str_ends_with( $haystack, $needle ) {
        return $needle !== '' && substr( $haystack, -strlen( $needle ) ) === (string)$needle;
    }
}
if ( version_compare( PHP_VERSION, 8.0, '<=' ) && !function_exists( 'str_contains' ) ) {
    function str_contains( $haystack, $needle ) {
        return $needle !== '' && mb_strpos( $haystack, $needle ) !== false;
    }
}


/**
 * Remap user meta keys
 * Run on each user
 * Please test on a single user before using it on all users
 *
 * @param int $user_id                  // The user id
 * @param array $keys                   // REQUIRED: 'old' => The old meta key where the value you want it stored
 *                                      // REQUIRED: 'new' => The new meta key where you want to move the value (may be same as old if just changing the values)
 *                                      // REQUIRED: 'not_listed' => The meta key where you want to move the value if the value does not exist in the $args
 *                                      // OPTIONAL: 'not_listed_other' => [ 'your_key' => 'your value' ] // Set other key/value pairs when the old meta key value is not listed in the $map
 * @param array $map                    // An array of old values and new values
 * @param boolean $delete_old_key       // Delete the old key if different than the new key; will not delete if same key
 * @param boolean $testing              // True if testing (will print what will happen on page); false to update user
 * @return bool
 */
function ddtt_remap_user_meta_keys( $user_id, $keys, $map, $delete_old_key = true, $testing = true ) {
    // Verify and sanitize
    if ( !isset( $keys[ 'old' ] ) || $keys[ 'old' ] == '' ) {
        return false;
    } else {
        $old_key = sanitize_text_field( $keys[ 'old' ] );
    }
    if ( !isset( $keys[ 'new' ] ) || $keys[ 'new' ] == '' ) {
        return false;
    } else {
        $new_key = sanitize_text_field( $keys[ 'new' ] );
    }
    if ( !isset( $keys[ 'not_listed' ] ) || $keys[ 'not_listed' ] == '' ) {
        return false;
    } else {
        $not_listed_key = sanitize_text_field( $keys[ 'not_listed' ] );
    }
    if ( !is_numeric( $user_id ) ) {
        return false;
    } else {
        $user_id = absint( $user_id );
    }

    // Verify that the user exists
    if ( $user = get_userdata( $user_id ) ) {

        /**
         * Map the values
         */
        
        // Make sure the $map array is set up correctly
        if ( isset( $map[0][0] ) && isset( $map[0][1] ) ) {

            // Iterate through each key we need to remap
            foreach ( $map as $m ) {

                // Vars, skip if old or new is blank
                if ( isset( $m[0] ) && sanitize_text_field( $m[0] ) != '' ) {
                    $old_value = sanitize_text_field( $m[0] );
                } else {
                    continue;
                }
                if ( isset( $m[1] ) && sanitize_text_field( $m[1] ) != '' ) {
                    $new_value = sanitize_text_field( $m[1] );
                } else {
                    continue;
                }

                // Conditions
                if ( get_user_meta( $user_id, $old_key, true ) && get_user_meta( $user_id, $old_key, true ) == $old_value ) {

                    // Are we deleting old key?
                    if ( $delete_old_key && $old_key != $new_key ) {
                        $delete = true;
                        $delete_verb = 'Then delete';
                    } else {
                        $delete = false;
                        $delete_verb = 'Preserve the';
                    }

                    // Testing?
                    if ( $testing ) {

                        // Print what will happen
                        ddtt_print_r( 'TEST: update user #'.$user_id.' ('.$user->display_name.') old meta key "<code class="hl">'.$old_key.'</code>" ( '.$old_value.' ) with new meta key "<code class="hl">'.$new_key.'</code>" ('.$new_value.'). '.$delete_verb.' old meta key.' );

                        // Success
                        return true;

                    } else {

                        // Update the new meta key
                        update_user_meta( $user_id, $new_key, $new_value );

                        // Remove the old meta key
                        if ( $delete ) {
                            delete_user_meta( $user_id, $old_key );
                        }

                        // Acknowledge
                        ddtt_console( 'COMPLETED: updated user #'.$user_id.' ('.$user->display_name.') old meta key "'.$old_key.'" ( '.$old_value.' ) with new meta key "'.$new_key.'" ('.$new_value.'). '.$delete_verb.' old meta key.' );

                        // Success
                        return true;
                    }
                }
            }

            

        }

        /**
         * Handle values that are not in $map
         */
        // Await success
        $success = false;

        // Make sure there is a key/value
        if ( get_user_meta( $user_id, $old_key, true ) && get_user_meta( $user_id, $old_key, true ) != '' ) {

            // Get the old value
            $old_value = get_user_meta( $user_id, $old_key, true );

            // Are we deleting old key?
            if ( $delete_old_key && $old_key != $new_key ) {
                $delete = true;
                $delete_verb = 'Then delete';
            } else {
                $delete = false;
                $delete_verb = 'Preserve the';
            }

            // Testing?
            if ( $testing ) {

                // Print what will happen
                ddtt_print_r( 'TEST: move user #'.$user_id.' ('.$user->display_name.') old meta key "<code class="hl">'.$old_key.'</code>" value ( '.$old_value.' ) to not listed meta key "<code class="hl">'.$not_listed_key.'</code>". '.$delete_verb.' old meta key.' );

                // Success
                $success = true;

            } else {

                // Update the new meta key
                update_user_meta( $user_id, $not_listed_key, $old_value );

                // Remove the old meta key
                if ( $delete ) {
                    delete_user_meta( $user_id, $old_key );
                }

                // Acknowledge
                ddtt_console( 'COMPLETED: moved user #'.$user_id.' ('.$user->display_name.') old meta key "'.$old_key.'" value ( '.$old_value.' ) to not listed meta key "'.$not_listed_key.'". '.$delete_verb.' old meta key.' );

                // Success
                $success = true;
            }
        }

        /**
         * Handle other key/value pairs that are not in $map
         */
        // Verify and sanitize
        if ( !isset( $keys[ 'not_listed_other' ] ) || empty( $keys[ 'not_listed_other' ] ) ) {

            // Were we successful in handling values not listed?
            if ( $success ) {
                return true;
            } else {
                return false;
            }
            
        } else {

            // Iterate through other key/value pairs
            foreach ( $keys[ 'not_listed_other' ] as $key => $nlo ) {

                // Sanitize
                $nlo_key = sanitize_text_field( $key );
                $nlo_value = sanitize_text_field( $nlo );

                // Expect to update
                $nlo_update = true;

                // Make sure the $map array is set up correctly
                if ( isset( $map[0][0] ) && isset( $map[0][1] ) ) {

                    // Check if it's in the map list
                    foreach( $map as $m ) {

                        // Vars, skip if old or new is blank
                        if ( !isset( $m[0] ) || sanitize_text_field( $m[0] ) == '' ) {
                            continue;
                        }
                        if ( isset( $m[1] ) && sanitize_text_field( $m[1] ) != '' ) {
                            $new_value = sanitize_text_field( $m[1] );
                        } else {
                            continue;
                        }


                        $nlo_key_value = get_user_meta( $user_id, $nlo_key, true );
                        // dpr( 'if $nlo_key ('.$nlo_key.') == $new_key ('.$new_key.') && $nlo_key_value ('.$nlo_key_value.') && $nlo_key_value ('.$nlo_key_value.') == $new_value ('.$new_value.') && $nlo_key_value ('.$nlo_key_value.') == $nlo_value ('.$nlo_value.') ');
                        if ( $nlo_key == $new_key && $nlo_key_value && $nlo_key_value == $new_value && $nlo_key_value != $nlo_value ) {
                            $nlo_update = false;
                            break;
                        }
                    }
                }

                // Are we moving on?
                if ( $nlo_update ) {

                    // Testing?
                    if ( $testing ) {

                        // Print what will happen
                        ddtt_print_r( 'TEST: update user #'.$user_id.' ('.$user->display_name.') meta key "<code class="hl">'.$nlo_key.'</code>" with value ( '.$nlo_value.' ).' );

                        // Success
                        return true;

                    } else {

                        // Add the key/value pair
                        update_user_meta( $user_id, $nlo_key, $nlo_value );

                        // Acknowledge
                        ddtt_console( 'COMPLETED: updated user #'.$user_id.' ('.$user->display_name.') meta key "'.$nlo_key.'" with value ( '.$nlo_value.' ).' );

                    }
                }

                // Success
                return true;
            }
        }
    }
        
    // Otherwise return false
    return false;
} // End ddtt_remap_user_meta_keys()


/**
 * Find shortcodes on the page without attributes
 *
 * @param int $post_id
 * @return array
 */
function ddtt_get_shortcodes_on_page( $post_id = null ) {
    // Get the post id
    if ( $post_id == null ) {
        $post_id = get_the_ID();
    } 

    // Let's get the post content
    $content = get_the_content( null, false, $post_id );

    // Shortcode array
    $shortcodes = [];

    if ( preg_match_all( '@\[([^<>&/\[\]\x00-\x20=]++)@', $content, $matches ) ) {

        // Cycle through them and filter out Cornerstone
        foreach ( $matches[1] as $match ) {

            // Omit these
            $omits = apply_filters( 'ddtt_omit_shortcodes', [
                'cs_content',
                'cs_element'
            ] );

            // Iter omits
            $omit_this = false;
            foreach ( $omits as $omit ) {
                if ( str_starts_with( $match, $omit ) ) {
                    $omit_this = true;
                }
            }

            // Omit shortcodes starting with cs_content or cs_element
            if ( !$omit_this ) {

                // Add to array
                $shortcodes[] = $match;
            }
        }
    }

    // Return the array
    return $shortcodes;
} // End ddtt_get_shortcodes_on_page()


/**
 * Get all the Gravity Form form ids on the page
 *
 * @param int $post_id
 * @return array
 */
function ddtt_get_form_ids_on_page( $post_id = null ) {

    // Get the post id
    if ( $post_id == null ) {
        $post_id = get_the_ID();
    } 

    // Let's get the post content
    $content = get_the_content( null, false, $post_id );

    // Findings array
    $findings = [];

    // Find the full shortcode
    $regex = '/\[gravityform\s.+?\]/';
    if ( preg_match_all( $regex, $content, $matches ) ) {

        // Cycle through the shortcodes and find the id
        foreach ( $matches[0] as $match ) {

            // Check if the id attribute exists
            if ( strpos( $match, 'id=' ) !== false ) {

                // Return id="#" in an array
                if ( preg_match('/id="\d+"/', $match, $attr ) ) {

                    // Rename the var
                    $attribute = $attr[0];

                    // If so, let's get the value
                    if ( preg_match( '/\d{1,}+/', $attribute, $form_id_array ) ) {
                        $form_id = $form_id_array[0];

                        // Add to array
                        $findings[] = $form_id;
                    }
                }
            }
        }
    }

    // Method 2
    if ( empty( $findings ) ) {

        // Try to get the content another way
        $content = apply_filters( 'the_content', $content );
        $content = htmlspecialchars( $content );

        // Regex for the html
        $regex = '/id=("|\'|"([^"]*)\s)gform_wrapper_[0-9]*("|\'|\s([^"]*)"|\')/';
        if ( preg_match_all( $regex, $content, $matches ) ) {

            // Cycle through the shortcodes and find the id
            foreach ( $matches[0] as $match ) {

                // Check if the id attribute exists
                if ( strpos( $match, 'id=' ) !== false ) {

                    // Return id="#" in an array
                    if ( preg_match('/".*?"|\'.*?\'/', $match, $attr ) ) {

                        // Rename the var
                        $attribute = trim( $attr[0], '\'"');

                        // If so, let's get the value
                        if ( preg_match( '/\d{1,}+/', $attribute, $form_id_array ) ) {
                            $form_id = $form_id_array[0];

                            // Add to array
                            $findings[] = $form_id;
                        }
                    }
                }
            }
        }
    }

    // Return the array
    return $findings;
} // End ddtt_get_form_ids_on_page()


/**
 * Time how long it takes to process code (in seconds)
 * $start = ddtt_start_timer();
 * run functions
 * $total_time = ddtt_stop_timer( $start );
 * $sec_per_link = round( ( $total_time / $count_links ), 2 );
 *
 * @param string $timeout_seconds
 * @return int|bool
 */
function ddtt_start_timer( $timeout_seconds = '300' ) {
    // Temporarily extend cURL timeout
    if ( !is_null( $timeout_seconds ) && $timeout_seconds ) {
        update_option( DDTT_GO_PF.'enable_curl_timeout', 1 );
        update_option( DDTT_GO_PF.'change_curl_timeout', $timeout_seconds );
    }

    // Start timing
    $time = microtime();
    $time = explode( ' ', $time );
    $time = $time[1] + $time[0];
    return $time;
} // End ddtt_start_timer()


/**
 * Stop timing - Use with ddtt_start_timer() above
 *
 * @param int $start
 * @param boolean $timeout
 * @return int
 */
function ddtt_stop_timer( $start, $timeout = true, $milliseconds = false ) {
    // Finish timing
    $time = microtime();
    $time = explode( ' ', $time );
    $time = $time[1] + $time[0];
    $finish = $time;
    

    // Are we converting to milliseconds
    if ( $milliseconds ) {
        $total_time = round( ( $finish - absint( $start ) ) * 1000, 2 );
    } else {
        $total_time = round( ( $finish - absint( $start ) ), 2 );
    }

    // Now restore cURL timeout
    if ( !is_null( $timeout ) && $timeout ) {
        update_option( DDTT_GO_PF.'enable_curl_timeout', 0 );
        update_option( DDTT_GO_PF.'change_curl_timeout', '' );
    }

    // Return the total time in seconds
    return $total_time;
} // End ddtt_stop_timer()


/**
 * Convert timestamp to relevant string
 *
 * @param int $ts
 * @return string
 */
function ddtt_convert_timestamp_to_string( $ts, $short = false ) {
    // Make sure the format is correct
    if( !is_numeric( $ts ) ) {
        $ts = strtotime( $ts );
    }
    
    // Get the difference in time
    $diff = time() - $ts;

    // Short?
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

    // If no difference, return now
    if ( $diff == 0 ) {
        return 'Now';

    // Or if it's in the past
    } elseif( $diff > 0 ) {
        $day_diff = floor( $diff / 86400 );
        if ( $day_diff == 0 ) {
            if ( $diff < 60 ) return 'Just now';
            if ( $diff < 120 ) return '1'.$minute;
            if ( $diff < 3600 ) return floor( $diff / 60 ).$minutes;
            if ( $diff < 7200 ) return '1'.$hour;
            if ( $diff < 86400 ) return floor( $diff / 3600 ).$hours;
        }
        if ( $day_diff == 1 ) return 'Yesterday';
        if ( $day_diff < 7 ) return $day_diff.$days;
        if ( $day_diff < 31 ) return ceil( $day_diff / 7 ).$weeks;
        if ( $day_diff < 60 ) return 'Last month';
        return date( 'F Y', $ts );

    // Or if it's the future
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
        if ( $day_diff < 4 ) return date( 'l', $ts );
        if ( $day_diff < 7 + ( 7 - date( 'w' ) ) ) return 'Next week';
        if ( ceil( $day_diff / 7 ) < 4 ) return 'In ' . ceil( $day_diff / 7 ) . ' weeks';
        if ( date( 'n', $ts ) == date( 'n' ) + 1 ) return 'Next month';
        return date( 'F Y', $ts );
    }
} // End ddtt_convert_timestamp_to_string()


/**
 * Display Example Script
 * USAGE: [example to="John"]
 * 
 * @param array $atts
 * @return string
 */
add_shortcode( 'example', 'ddtt_example_shortcode' );
function ddtt_example_shortcode( $atts ) {
    $atts = shortcode_atts( [ 'to' => 'Aristocles' ], $atts );
    return 'Hello, '.$atts[ 'to' ];
} // End ddtt_example_shortcode()


/**
 * Logs a comma-separated string or array of functions that have been called to get to the current point in code.
 *
 * @return void
 */
function ddtt_backtrace( $ignore_class = null, $skip_frames = 0, $pretty = true ) {
    ddtt_write_log( wp_debug_backtrace_summary( $ignore_class, $skip_frames, $pretty ) );
} // End ddtt_backtrace()


/**
 * THE END
 */