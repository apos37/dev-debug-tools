<?php
/**
 * Option pages functions.
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Add selected to select field if option matches key
 *
 * @param string|int $option
 * @param string|int $the_key
 * @return string
 */
function ddtt_is_qs_selected( $option, $the_key ) {
    if ( esc_attr( $option ) == esc_attr( $the_key ) ) {
        $results = ' selected';
    } else {
        $results = '';
    }
    return $results;
} // End ddtt_is_qs_selected()


/**
 * Add checked to checkboxes and radio fields if option matches key
 *
 * @param string|int $option
 * @param string|int $the_key
 * @return string
 */
function ddtt_is_qs_checked( $option, $the_key ) {
    if ( esc_attr( $option ) == esc_attr( $the_key ) ) {
        $results = ' checked="checked"';
    } else {
        $results = '';
    }
    return $results;
} // End ddtt_is_qs_checked()


/**
 * Table row for form fields
 * 
 * $args = [ 'default' => 'Default Value', 'required' => true ]
 * 
 * Text $args = [ 'width' => '100%' 'pattern' => '^[a-zA-Z0-9_.-]*$' ]
 * 
 * Color $args = [ 'width' => '20rem' ]
 * 
 * Textarea $args = [ 'rows' => 6, 'cols' => 50 ]
 * 
 * Select $args = [ 
 *      'blank' => '-- Select One --',
 *      'options' => [
 *          [ 'value' => 'the_value', 'label' => 'Label Name' ], 
 *          [ 'value' => 'the_value', 'label' => 'Label Name' ]
 *      ]
 * ] 
 * OR if value and label are the same
 * $args = [
 *      'options' => [
 *          'Value/Label', 
 *          'Value/Label',
 *      ]
 * ]
 *
 * @param string $option_name
 * @param string $label
 * @param string $type
 * @param string $comments // Use 'get_option' for click to copy get_option()
 * @return string
 */
function ddtt_options_tr( $option_name, $label, $type, $comments = null, $args = null ) {
    // Add the prefix to the option name
    $option_name = DDTT_GO_PF.$option_name;

    // Get default
    if ( get_option( $option_name ) ) {
        $value = get_option( $option_name );
    } elseif ( !is_null( $args ) && isset( $args[ 'default' ]) && $args[ 'default' ] != '' ) {
        $value = $args[ 'default' ];
    } else {
        $value = '';
    }

    // Mark required?
    if ( !is_null( $args ) && isset( $args[ 'required' ] ) && $args[ 'required' ] == true ) {
        $required = ' required';
    } else {
        $required = '';
    }

    // Checkbox
    if ($type == 'checkbox') {
        $input = '<input type="checkbox" name="'.esc_attr( $option_name ).'" value="1" '.checked( 1, $value, false ).''.$required.'/>';

    // Text Field
    } elseif ( $type == 'text' ) {
        if ( !is_null( $args ) && isset( $args[ 'width' ] ) ) {
            $width = $args[ 'width' ];
        } else {
            $width = '43.75rem';
        }
        if ( !is_null( $args ) && isset( $args[ 'pattern' ] ) ) {
            $pattern = ' pattern="'.$args[ 'pattern' ].'"';
        } else {
            $pattern = '';
        }
        
        $input = '<input type="text" id="'.esc_attr( $option_name ).'" name="'.esc_attr( $option_name ).'" value="'.esc_attr( $value ).'" style="width: '.esc_attr( $width ).'"'.$pattern.$required.'/>';

    // Number Field
    } elseif ( $type == 'number' ) {
        if ( !is_null( $args ) && isset( $args[ 'width' ] ) ) {
            $width = $args[ 'width' ];
        } else {
            $width = '43.75rem';
        }
        if ( !is_null( $args ) && isset( $args[ 'pattern' ] ) ) {
            $pattern = ' pattern="'.$args[ 'pattern' ].'"';
        } else {
            $pattern = '';
        }
        
        $input = '<input type="number" id="'.esc_attr( $option_name ).'" name="'.esc_attr( $option_name ).'" value="'.esc_attr( $value ).'" style="width: '.esc_attr( $width ).'"'.$pattern.$required.'/>';


    // Text with Color Field
    } elseif ( $type == 'color' ) {
        if ( !is_null( $args ) && isset( $args[ 'width' ] ) ) {
            $width = $args[ 'width' ];
        } else {
            $width = '10rem';
        }
        $input = '<input type="color" id="'.esc_attr( $option_name ).'" name="'.esc_attr( $option_name ).'" value="'.esc_html( $value ).'" style="width: '.esc_attr( $width ).'"/>';

    // Textarea    
    } elseif ( $type == 'textarea' ) {
        if ( !is_null( $args ) && isset( $args[ 'rows' ] ) && isset( $args[ 'cols' ] ) ) {
            $rows = $args[ 'rows' ];
            $cols = $args[ 'cols' ];
        } else {
            $rows = 6;
            $cols = 50;
        }
        $input = '<textarea type="text" id="'.esc_attr( $option_name ).'" name="'.esc_attr( $option_name ).'" rows="'.esc_attr( $rows ).'" cols="'.esc_attr( $cols ).'"'.$required.'>'.esc_html( $value ).'</textarea>';

    // Select    
    } elseif ( $type == 'select' ) {
        if ( !is_null( $args ) ) {
            $options = $args[ 'options' ];
            
            if ( isset( $args[ 'blank' ] ) ) {
                $blank = '<option value="">'.esc_html( $args[ 'blank' ] ).'</option>';
            } else {
                $blank = '';
            }
        } else {
            return false;
        }
        $input = '<select id="'.esc_attr( $option_name ).'" name="'.esc_attr( $option_name ).'"'.$required.'>'.$blank;

        foreach ( $options as $option ) {
            if ( isset( $option[ 'value' ] ) && isset( $option[ 'label' ] ) ) {
                $option_value = $option[ 'value' ];
                $option_label = $option[ 'label' ];
            } elseif ( !is_array( $option ) ) {
                $option_value = $option;
                $option_label = $option;
            }
            $input .= '<option value="'.esc_attr( $option_value ).'"'.ddtt_is_qs_selected( $option_value, $value ).'>'.$option_label.'</option>';
        }

        $input .= '</select>';

    // Text+ Field
    } elseif ( $type == 'text+' ) {
        if ( !is_null( $args ) && isset( $args[ 'width' ] ) ) {
            $width = $args[ 'width' ];
        } else {
            $width = '43.75rem';
        }
        if ( !is_null( $args ) && isset( $args[ 'pattern' ] ) ) {
            $pattern = ' pattern="'.$args[ 'pattern' ].'"';
        } else {
            $pattern = '';
        }

        if ( !is_array( $value ) ) {
            $value = [ $value ];
        }
        
        $input = '<div id="text_plus_'.esc_attr( $option_name ).'">
            <a href="#" class="add_form_field">Add New Field +</a>
            <div><input type="text" id="'.esc_attr( $option_name ).'" name="'.esc_attr( $option_name ).'[]" value="'.esc_attr( $value[0] ).'" style="width: '.esc_attr( $width ).'"'.$pattern.$required.'/></div>
        </div>';

        // Add jQuery
        $js_value = json_encode( $value );
        $input .= '<script>
        jQuery( document ).ready( function( $ ) {
            var max_fields = 20;
            var wrapper = $( "#text_plus_'.esc_attr( $option_name ).'" );
            var add_link = $( "#text_plus_'.esc_attr( $option_name ).' .add_form_field" );
            var load_count = parseInt( "'.count( $value ).'" );
            var value = '.$js_value.';
            console.log( value );

            if ( load_count > 1 ) {
                value.slice( 1 ).forEach( function( v ) {
                    console.log( v );
                    $( wrapper ).append( &quot;<div><input type=\"text\" id=\"'.esc_attr( $option_name ).'\" name=\"'.esc_attr( $option_name ).'[]\" value=\"&quot; + v + &quot;\"/> <a href=\"#\" class=\"delete\">Delete</a></div>&quot; );
                } );
            }
        
            var x = 1;
            $( add_link ).click( function( e ) {
                e.preventDefault();
                if ( x < max_fields ) {
                    x++;
                    $( wrapper ).append( &quot;<div><input type=\"text\" id=\"'.esc_attr( $option_name ).'\" name=\"'.esc_attr( $option_name ).'[]\" value=\"\"/> <a href=\"#\" class=\"delete\">Delete</a></div>&quot; );
                } else {
                    alert( "You reached the limit." )
                }
            });
        
            $( wrapper ).on( "click", ".delete", function( e ) {
                e.preventDefault();
                $( this ).parent( "div" ).remove();
                x--;
            })
        });
        </script>';

    // Otherwise return false
    } else {
        return false;
    }

    // If comments
    $incl_comments = '';
    if ( !is_null( $comments ) ) {
        if ( $comments == 'get_option' ) {
            $incl_comments = 'get_option( '.$option_name.' )';
        } else {
            $incl_comments = $comments;
        }
    }
    // Build the row
    $row = '<tr valign="top">
        <th scope="row">'.$label.'</th>
        <td>'.$input.' '.$incl_comments.'</td>
    </tr>';
    
    // Return the row
    return $row;
} // End ddtt_options_tr()


/**
 * Allowed html for ddtt_options_tr() sanitation
 *
 * @return array
 */
function ddtt_wp_kses_allowed_html() {
    $allowed_html = [
        'div' => [
            'class' => []
        ],
        'pre' => [
            'class' => []
        ],
        'span' => [
            'class' => [],
            'style' => []
        ],
        'a' => [
            'href' => [],
            'id' => [],
            'class' => [],
            'style' => []
        ],
        'tr' => [
            'valign' => [],
            'class' => []
        ],
        'th' => [
            'scope' => [],
            'class' => []
        ],
        'td' => [
            'class' => []
        ],
        'br' => [],
        'form' => [
            'method' => []
        ],
        'input' => [
            'type' => [],
            'id' => [],
            'class' => [],
            'name' => [],
            'value' => [],
            'checked' => [],
            'required' => [],
            'style' => [],
            'pattern' => [],
            'disabled' => [],
            'size' => []
        ],
        'textarea' => [
            'type' => [],
            'id' => [],
            'class' => [],
            'name' => [],
            'rows' => [],
            'cols' => [],
            'required' => [],
        ],
        'select' => [
            'id' => [],
            'class' => [],
            'name' => [],
            'required' => [],
        ],
        'option' => [
            'value' => [],
            'selected' => [],
        ],
        'button' => [
            'class' => [],
            'selected' => [],
        ],
        'script' => [
            'id' => []
        ]
    ];
    return $allowed_html;
} // End ddtt_options_tr_allowed_html()


/**
 * Return color from options or default
 *
 * @param string $key
 * @return string
 */
function ddtt_get_syntax_color( $key, $default ) {
    if ( get_option( DDTT_GO_PF.$key ) && get_option( DDTT_GO_PF.$key ) != '' ) {
        $color = get_option( DDTT_GO_PF.$key );
    } else {
        $color = $default;
    }
    return $color;
} // End ddtt_get_syntax_color()


/**
 * Get all defined functions in a php file
 *
 * @param string $file
 * @return void
 */
function ddtt_get_defined_functions_in_file( $file ) {
    // Get our file
    $source = file_get_contents( $file );
    $tokens = token_get_all( $source );

    $functions = array();
    $nextStringIsFunc = false;
    $inClass = false;
    $bracesCount = 0;

    foreach($tokens as $token) {
        switch($token[0]) {
            case T_CLASS:
                $inClass = true;
                break;
            case T_FUNCTION:
                if(!$inClass) $nextStringIsFunc = true;
                break;

            case T_STRING:
                if($nextStringIsFunc) {
                    $nextStringIsFunc = false;
                    $functions[] = $token[1];
                }
                break;

            // Anonymous functions
            case '(':
            case ';':
                $nextStringIsFunc = false;
                break;

            // Exclude Classes
            case '{':
                if($inClass) $bracesCount++;
                break;

            case '}':
                if($inClass) {
                    $bracesCount--;
                    if($bracesCount === 0) $inClass = false;
                }
                break;
        }
    }

    return $functions;
} // End ddtt_get_defined_functions_in_file()


/**
 * Get a function with parameters by function name
 *
 * @param string $function_name
 * @return string
 */
function ddtt_get_function_example( $function_name ){
    // Check if the function exists
    if( function_exists( $function_name ) ){

        // Store the attributes here
        $attribute_names = [];

        // Get the function
        $fx = new ReflectionFunction( $function_name );

        // Get the params
        foreach ( $fx->getParameters() as $param ){

            // Check for optional params
            if ( $param->isOptional() ){

                // Get the default
                if ( is_null( $param->getDefaultValue() ) ) {
                    $default_value = 'null';
                } elseif ( $param->getDefaultValue() === false ) {
                    $default_value = 'false';
                } elseif ( ddtt_is_enabled( $param->getDefaultValue() ) ) {
                    $default_value = 'true';
                } elseif ( is_array( $param->getDefaultValue() ) ) {
                    $default_value = 'array()';
                } elseif ( is_numeric( $param->getDefaultValue() ) ) {
                    $default_value = $param->getDefaultValue();
                } else {
                    $default_value = '"'.$param->getDefaultValue().'"';
                }

                // Add the default to the name
                $attribute_names[] = '$'.$param->name.' = '.$default_value;
                
            // Otherwise just add the name
            } else {
                $attribute_names[] = '$'.$param->name;
            }
        }           

        // Put the function together
        if ( !empty( $attribute_names ) ) {
            $attributes = ' '.implode( ', ', $attribute_names ).' ';
        } else {
            $attributes = '';
        }
        return $function_name.'('.$attributes.')';

    } else {
        return ddtt_admin_error( 'FUNCTION DOES NOT EXIST' );
    }
} // End ddtt_get_function_example()


/**
 * Get a dropdown field with all forms, and return form id as value
 *
 * @param int $id
 * @param int $selected
 * @param boolean $include_inactive
 * @return string
 */
function ddtt_get_form_selections( $id, $selected, $include_inactive = false ) {
    // Get active forms
    $forms = GFAPI::get_forms( true, false, 'title' );

    // Check if there are any pages
    if (!empty($forms)) {

        // Let's start the selection
        $results = '<select id="'.$id.'" name="'.$id.'">
            <option value="">-- Select a Form --</option>
            <option disabled>Active Forms</option>';

        // For each page
        foreach ($forms as $form) {

            // Get the page name, page id, and status
            $name = $form['title'];
            $page_id = $form['id'];

            // Return the option
            $results.= '<option value="'.$page_id.'"'.ddtt_is_qs_selected($page_id, $selected).'>'.$name.'</option>';
        }

        // Get inactive forms
        if ($include_inactive) {
            $inactive_forms = GFAPI::get_forms( false, false, 'title' );

            $results .= '<option disabled>Inactive Forms</option>';

            // For each page
            foreach ($inactive_forms as $inactive_form) {

                // Get the page name, page id, and status
                $name = $inactive_form['title'];
                $page_id = $inactive_form['id'];

                // Return the option
                $results.= '<option value="'.$page_id.'"'.ddtt_is_qs_selected($page_id, $selected).'>'.$name.'</option>';
            }
        }
        
        // End the selection
        $results.= '</select>';
    }
    return $results;
} // End ddtt_get_form_selections()


/**
 * Return error counts
 *
 * @return int
 */
function ddtt_error_count(){
    // Check for error_log
    $error_log = FALSE;
    if ( is_readable( ABSPATH.'error_log' ) ) {
        $error_log = ABSPATH.'error_log';
    } elseif ( is_readable( dirname( ABSPATH ).'/error_log' ) ) {
        $error_log = dirname( ABSPATH ).'/error_log';
    }
    
    // Check for debug.log
    $debug_log = FALSE;
    if ( is_readable( ABSPATH.DDTT_CONTENT_URL.'/debug.log' ) ) {
        $debug_log = ABSPATH.DDTT_CONTENT_URL.'/debug.log';
    } elseif ( is_readable( dirname( ABSPATH ).'/'.DDTT_CONTENT_URL.'/debug.log' ) ) {
        $debug_log = dirname( ABSPATH ).'/'.DDTT_CONTENT_URL.'/debug.log';
    }

    // Count debug log lines
    $line_count = 0;
    if ( $debug_log ) {
        $string = file_get_contents( $debug_log );
        $lines = explode( PHP_EOL, $string );
        
        foreach( $lines as $line ){
            if ( $line != '' ){
                $line_count ++; 
            }
        }
    }

    // Check for wp-admin error_log
    $admin_error_log = FALSE;
    if ( is_readable( ABSPATH.DDTT_ADMIN_URL.'/error_log' ) ) {
        $admin_error_log = ABSPATH.DDTT_ADMIN_URL.'/error_log';
    } elseif ( is_readable( dirname( ABSPATH ).'/'.DDTT_ADMIN_URL.'/error_log' ) ) {
        $admin_error_log = dirname( ABSPATH ).'/'.DDTT_ADMIN_URL.'/error_log';
    }
    
    // Return count
    if ( $debug_log && filesize($debug_log) > 0 ) {
        return $line_count;
    } elseif ( ( $error_log && filesize( $error_log ) > 0 ) || 
              ( $admin_error_log && filesize( $admin_error_log ) > 0 ) ) {
        return 1;
    } else {
        return 0;
    }
} // End ddtt_error_count()


/**
 * Return error logs from this server
 *
 * @param string $plugin_folder
 * @param string $plugin_admin_page
 * @return void
 */
function ddtt_error_logs(){
    // Replace the files if query string exists

    if ( ddtt_get( 'clear_error_log', '==', 'true' ) ) {
        ddtt_replace_file( 'error_log', 'error_log', true );
    }
    if ( ddtt_get( 'clear_debug_log', '==', 'true' ) ) {
        ddtt_replace_file( DDTT_CONTENT_URL.'/debug.log', 'debug.log', true );
    }
    if ( ddtt_get( 'clear_admin_error_log', '==', 'true' ) ) {
        ddtt_replace_file( DDTT_ADMIN_URL.'/error_log', 'error_log', true );
    }

    // Get the different error logs
    $error_log = ddtt_file_exists_with_content( 'error_log' );
    $error_log_notice = ddtt_check_file_notice( 'error_log' );

    $debug_log = ddtt_file_exists_with_content( DDTT_CONTENT_URL.'/debug.log' );
    $debug_log_notice = ddtt_check_file_notice( DDTT_CONTENT_URL.'/debug.log' );
    
    $admin_error_log = ddtt_file_exists_with_content( DDTT_ADMIN_URL.'/error_log' );
    $admin_error_log_notice = ddtt_check_file_notice( DDTT_ADMIN_URL.'/error_log' );

    // Echo the table row if any of them exists
    if ( $error_log || $debug_log || $admin_error_log ) {
        $logs = [];
        if ( $error_log ) {
            $logs[] = $error_log_notice;
        }
        if ( $debug_log ) {
            $logs[] = $debug_log_notice;
        }
        if ( $admin_error_log ) {
            $logs[] = $admin_error_log_notice;
        }
        echo '<tr valign="top">
            <th scope="row">Logs Available</th>
            <td>'.wp_kses_post( implode('<br>', $logs) ).'</td>
        </tr>';

        // Allowed HTML
        $allowed_html = ddtt_wp_kses_allowed_html();

        // Display the error_log with clear button
        if ( $error_log ) {
            echo wp_kses( ddtt_file_contents_with_clear_button( 'clear_error_log', 'Error Log', 'error_log', true, array(), false ), $allowed_html );
        }

        // Display the debug_log with clear button
        if ( $debug_log ) {
            $active_theme = str_replace( '%2F', '/', rawurlencode( get_stylesheet() ) );
            echo wp_kses( ddtt_file_contents_with_clear_button( 'clear_debug_log', 'Debug Log', DDTT_CONTENT_URL.'/debug.log', true, array(
                ['keyword' => DDTT_INCLUDES_URL, 'class' => 'theme-functions'],
                ['keyword' => $active_theme, 'class' => 'my-functions'],
                ['keyword' => DDTT_TEXTDOMAIN, 'class' => 'my-plugin'],
                ['keyword' => 'Fatal', 'class' => 'fatal'],
            ), true ), $allowed_html );
        }

        // Display the admin error_log with clear button
        if ( $admin_error_log ) {
            echo wp_kses( ddtt_file_contents_with_clear_button( 'clear_admin_error_log', 'Admin Error Log', DDTT_ADMIN_URL.'/error_log', true, array(), false ), $allowed_html );
        }

    // If none found
    } else {
        if ( WP_DEBUG ) {
            echo 'Yay! No errors found!';
        } else {
            echo 'Debug mode is disabled...';
        }
    }
} // End ddtt_error_logs()


/**
 * Replace a file with another one on the server
 * USAGE: ddtt_replace_file( 'debug.log', true )
 *
 * @param string $file_to_replace
 * @param string $file_to_copy
 * @param boolean $plugin_assets
 * @return void
 */
function ddtt_replace_file( $file_to_replace, $file_to_copy, $plugin_assets = false ){
    
    // First check if we are copying a file from the plugin assets folder
    if ( $plugin_assets ) {
        $file_to_copy = get_home_path() . DDTT_PLUGIN_FILES_PATH . $file_to_copy;
    } else {
        $file_to_copy = get_home_path() . $file_to_copy;
    }
    
    // Get the full path of the file to replace
    $file_to_replace = get_home_path() . $file_to_replace;

    // Copy the file to new spot
    copy( $file_to_copy, $file_to_replace );
} // End ddtt_replace_file()


/**
 * Check if file exists and is not empty; return notice
 *
 * @param string $path
 * @return string|bool
 */
function ddtt_file_exists_with_content( $path ) {
    $file = FALSE;
    if ( is_readable( ABSPATH.'/'.$path ) ) {
        $file = ABSPATH.'/'.$path;
    } elseif ( is_readable( dirname( ABSPATH ).'/'.$path ) ) {
        $file = dirname( ABSPATH ).'/'.$path;
    }

    if ( $file && filesize($file) > 0 ) {
        $result = $file;
    } else {
        $result = false;
    }

    return $result;
} // End ddtt_file_exists_with_content()


/**
 * Check if file exists and is not empty; return notice
 *
 * @param string $path
 * @return string
 */
function ddtt_check_file_notice( $path ) {
    $file = ddtt_file_exists_with_content( $path );
    
    // Include the notice
    if ( $file ) {
        $file_link = '<span>&#9888;</span> <strong>Notice! <u><a href="'.get_home_url().'/'.$path.'" target="_blank">'.$path.'</a></u> is available at:</strong> '.$file.' ('.number_format( filesize( $file ) ).' bytes. Last Modified: '.date( "F d, Y H:i", filemtime( $file ) ).')';

        // Direct access
        if ( strpos( $path, 'debug.log' ) !== false ) {
            $file_link .= '<br>// If you have blocked access to the debug.log from your .htaccess, this link will show as not found.';
        }

    } else {
        $file_link = '';
    }

    return $file_link;
} // End ddtt_check_file_notice()


/**
 * Display the file contents with a clear button
 *
 * @param string $plugin_folder
 * @param string $plugin_admin_page
 * @param string $query_string_param
 * @param string $button_label
 * @param string $path
 * @param boolean $log
 * @param array $highlight_args
 * @param boolean $allow_repeats
 * @return void
 */
function ddtt_file_contents_with_clear_button( $query_string_param, $button_label, $path, $log = false, $highlight_args = array(), $allow_repeats = true ) {
    // The clear url
    $clear_url = esc_url( add_query_arg( $query_string_param, 'true', ddtt_plugin_options_path( 'debug' ) ) );

    // Button for clearing log
    $clear_button = '<div><a id="clear-log-button-'.$query_string_param.'" class="button button-warning" href="'.$clear_url.'" style="font-weight: normal;">Clear '.esc_html( $button_label ).'</a></div>';

    // Button for downloading
    if ( strpos( $path, 'debug.log' ) !== false ) {
        $dl = 'debug_log';
    } elseif ( strpos( $path, DDTT_ADMIN_URL.'/error_log' ) !== false ) {
        $dl = 'admin_error_log';
    } elseif ( strpos( $path, 'error_log' ) !== false ) {
        $dl = 'error_log';
    } else {
        $dl = 'null';
    }
    $download_button = '<div><form method="post">
        <input type="submit" value="Download '.esc_html( $button_label ).'" name="ddtt_download_'.$dl.'" class="button button-primary"/>
    </form></div>';

    // Get the contents
    $contents = ddtt_view_file_contents( $path, $log, $highlight_args, $allow_repeats);

    // Return the row
    return '<tr valign="top">
        <th scope="row">Current '.$path.' file (View Only)<br><br>'.$clear_button.'<br>'.$download_button.'</th>
        <td><div class="full_width_container"> '.$contents.' </div></td>
    </tr>';
} // End ddtt_file_contents_with_clear_button()


/**
 * Return a log file from this server line by line, numbered, with colors
 * Home path is public_html/
 * Include filename in path
 * USAGE: ddtt_view_file_contents( 'wp-config.php' );
 * If log file, include highlight args as follows:
 * ddtt_view_file_contents( $path, true, array(
 *  ['keyword' => 'wp-includes', 'class' => 'theme-functions'],
 *  ['keyword' => 'x-child', 'class' => 'my-functions'],
 *  ['keyword' => 'wp-debug-tools', 'class' => 'my-plugin']
 * ));
 *
 * @param string $path
 * @param boolean $log
 * @param array $highlight_args
 * @param boolean $allow_repeats
 * @return string
 */
function ddtt_view_file_contents( $path, $log = false, $highlight_args = array(), $allow_repeats = true ){
    // Define the file
    $file = FALSE;
    if ( is_readable( ABSPATH . $path ) )
        $file = ABSPATH . $path;
    elseif ( is_readable( dirname( ABSPATH ) . '/' . $path ) )
        $file = dirname( ABSPATH ) . '/' . $path;

    // Check if the file exists
    if ( $file ) {
        // If so, get it
        $string = file_get_contents( $file );

        // Separate each line into an array item
        $lines = explode(PHP_EOL, $string);

        // Empty array
        $modified_lines = [];

        // Start the line count
        $line_count = $log ? 0 : 1;

        // Default CSS
        $results = '';
        
        // For each line...
        foreach( $lines as $key => $line ){

            // Check if we're viewing a log
            if ( $log ) {

                // If so, we're going to filter out blank lines
                if ( $line != '' ) {
                    // Increase the line count
                    $line_count ++; 
    
                    // Convert UTC times to local
                    $dev_timezone = get_option( DDTT_GO_PF.'dev_timezone', wp_timezone_string() );

                    $get_date_section = substr( $line, 0, 26 );
                    $get_rest_section = substr( $line, 26 );
                    $new_line = '';
                    if ( preg_match( '/\bUTC\b/', $get_date_section ) ) {
                        $chars = [ '[', ']', 'UTC' ];
                        $remove   = [ '', '', '' ];
                        $stripped_date = str_replace( $chars, $remove, $get_date_section );
                        $date = new DateTime( $stripped_date, new DateTimeZone( 'UTC' ) );
                        $date->setTimezone( new DateTimeZone( $dev_timezone ) );
                        $time = $date->format('d-M-Y H:i:s');
                        $new_date = '['.$time.' '.$dev_timezone.']';
                        $new_line = $new_date.''.$get_rest_section;
                    } else {
                        $new_line = $line;
                    }
                    
                    // Add classes to lines based on keywords found
                    $class = '';
                    if (!empty($highlight_args)) {
                        for ($h = 0; $h < count($highlight_args); $h++) {
                            $keyword = $highlight_args[$h]['keyword'];

                            if (preg_match('/\b'.$keyword.'\b/', $new_line)) {
                                $class .= ' '.$highlight_args[$h]['class'];
                            }
                        }
                    }
    
                    // Prevent repeats
                    $og_key = null;
                    if ( $allow_repeats ) {
                        $on_line = strval(strstr($line, 'on line'));
                        foreach ($modified_lines as $key => $modified_line){
                            if ($on_line != '') {
                                if (strpos($modified_line, $on_line) && strpos($modified_line, $on_line) !== false){
                                    $og_key = $key;
                                }
                            }
                        }
                    }

                    // Separate line from path
                    $esc_line = esc_html( $new_line );
                    if ( strpos( $esc_line, 'in /' ) !== false ) {
                        $line_parts = explode( ' in /', $esc_line );
                        $warning_with_date = $line_parts[0];
                        
                        if ( strpos( $warning_with_date, '] PHP ' ) !== false ) {
                            $warning_parts = explode( '] PHP ', $warning_with_date );
                            $warning_date = $warning_parts[0].']';
                            $warning = ' PHP '.$warning_parts[1];

                            $warning_path = ' in /'.$line_parts[1];
                            $esc_line = $warning_date.' <a href="https://www.google.com/search?q='.$warning.'" target="_blank">'.$warning.'</a>'.$warning_path;
                        }
                    }

                    // If the line number already exists
                    if ( $og_key ) {
                        $modified_lines[ $og_key ] = '<div class="debug-li'.$class.'"><span class="debug-ln unselectable">'.$line_count.'</span><span class="ln-content"><span class="repeat">REPEAT</span> '.$esc_line.' </span></div>';

                    } else {
                        $modified_lines[] = '<div class="debug-li'.$class.'"><span class="debug-ln unselectable">'.$line_count.'</span><span class="ln-content">'.$esc_line.'</span></div>';
                    }
                }

            } else {

                // If not, check for comment marks; add a class
                if (substr( $line, 0, 3 ) === '// ' || 
                    substr( $line, 0, 3 ) === '/**' || 
                    substr( $line, 0, 2 ) === ' *' || 
                    substr( $line, 0, 1 ) === '*' || 
                    substr( $line, 0, 2 ) === '*/' || 
                    substr( $line, 0, 2 ) === '/*' || 
                    substr( $line, 0, 1 ) === '#') {
                        $comment_out = ' comment-out';
                } else {
                    $comment_out = '';
                }

                // Escape the html early
                $line = esc_html( $line );

                // Add a new, modified line to the array
                $modified_lines[] = '<div class="debug-li 2"><span class="debug-ln unselectable">'.$line_count.'</span><span class="ln-content'.$comment_out.' selectable">'.$line.'</span></div>';

                // Increase Line Count
                $line_count ++; 
            }
        }
        
        // Turn the new lines into a string
        $code = implode('', $modified_lines);
        
    } else {
        // Otherwise say the file wasn't found
        $code = $path . ' not found';
    }
    
    // Return the code with the defined path at top
    $results .= '<pre class="code"
            >Installation path: ' . ABSPATH
          . "\n\n"
          . $code
          . '</pre>';

    return $results;
} // End ddtt_view_file_contents()


/**
 * Delete ALL transients from the wpdb
 *
 * @return void
 */
function ddtt_delete_all_transients() {
    global $wpdb;
 
    $sql = 'DELETE FROM ' . $wpdb->options . ' WHERE option_name LIKE "_transient_%"';
    $wpdb->query($sql);
} // End ddtt_delete_all_transients()


/**
 * Deletes all transients that have expired
 *
 * @param string $older_than
 * @param boolean $safemode
 * @return void
 */
function ddtt_purge_expired_transients($older_than = '1 day', $safemode = true) {
 
    global $wpdb;
    $older_than_time = strtotime('-' . $older_than);
 
    // Only check if the transients are older than the specified time
    if ( $older_than_time > time() || $older_than_time < 1 ) {
        return false;
    }
 
    // Get all the expired transients
    $transients = $wpdb->get_col(
    $wpdb->prepare( "
    SELECT REPLACE(option_name, '_transient_timeout_', '') AS transient_name
    FROM {$wpdb->options}
    WHERE option_name LIKE '\_transient\_timeout\__%%'
    AND option_value < %s
    ", $older_than_time)
    );
 
    // If safemode is ON just use the default WordPress get_transient() function to delete the expired transients
    if ( $safemode ) {
        foreach( $transients as $transient ) {
            get_transient($transient);
        }
    }
 
    // If safemode is OFF the just manually delete all the transient rows in the database
    else {
        $options_names = array();
        foreach($transients as $transient) {
            $options_names[] = '_transient_' . $transient;
            $options_names[] = '_transient_timeout_' . $transient;
        }
        if ($options_names) {
            $options_names = array_map(array($wpdb, 'escape'), $options_names);
            $options_names = "'". implode("','", $options_names) ."'";
 
            $result = $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name IN ({$options_names})" );
            if (!$result) {
                return false;
            }
        }
    }
 
    return $transients;
} // End ddtt_purge_expired_transients()


/**
 * Delete unused meta keys
 *
 * @param string $post_type
 * @param string $keyword
 * @param string $dumk
 * @return string|bool
 */
function ddtt_delete_unused_mk_tab( $post_type, $keyword, $dumk ) {
    // Let's get the published posts
    $args = array( 
        'post_type' => $post_type,
        'post_status' => 'publish',
        'posts_per_page' => -1,
    );

    // Run the query
    $the_query = new WP_Query( $args );

    // Continue if there are posts found
    if ( $the_query->have_posts() ) {

        // Temporarily extend cURL timeout
        update_option( 'ddtt_enable_curl_timeout', 1 );
        update_option( 'ddtt_change_curl_timeout', 300 );

        // Start timing
        $time = microtime();
        $time = explode(' ', $time);
        $time = $time[1] + $time[0];
        $start = $time;

        // Echo the title of the post
        $post_meta_tab = 'postmeta';
        $post_meta_url = ddtt_plugin_options_path( $post_meta_tab );

        // For each list item...
        while ( $the_query->have_posts() ) {

            // Get the post
            $the_query->the_post();

            // Get the post ID
            $post_id = get_the_ID();

            // Add the title
            echo '<br><br><strong>Checking... '.esc_html( get_the_title() ).' (Post ID: <a href="'.esc_url( $post_meta_url ).'&post_id='.absint( $post_id ).'" target="_blank">'.absint( $post_id ).'</a>)</strong><br><br>';

            // Are we testing or doing this fo real?
            if ( $dumk == 'Test' ) {
                $delete_all = false;
            } elseif ( $dumk == 'Remove' ) {
                $delete_all = true;
            }

            // Run the function
            delete_unused_post_meta( $post_id, $keyword, $delete_all );
        }

        // Finish timing
        $time = microtime();
        $time = explode(' ', $time);
        $time = $time[1] + $time[0];
        $finish = $time;
        $total_time = round(($finish - $start), 2);

        // Now restore cURL timeout
        update_option( 'ddtt_enable_curl_timeout', 0 );
        update_option( 'ddtt_change_curl_timeout', '' );

        $results = '<span class="time-loaded">Results generated in <strong>'.$total_time.' seconds</strong></span><br>';

        // Restore original Post Data
        wp_reset_postdata();

        return $results;
    }
    return false;
} // End ddtt_delete_unused_mk_tab()


/**
 * Highlighting syntax
 * 
 * @param string $fl
 * @param bool $ret
 * @return void|bool
 */
function ddtt_highlight_file2( $filename, $return = false ) {
    $str = highlight_file( $filename, true );
    preg_match_all("/\<span style=\"color: #([\d|A|B|C|D|E|F]{6})\"\>.*?\<\/span\>/", $str, $mtch);
    $m = array_unique( $mtch[1] );

    $cls = '<style type="text/css">'."\n";
    $rpl = array("</a>");
    $mtc = array("</span>");
    $i = 0;
    foreach($m as $clr) {
        $cls .= "a.c".$i."{color: #".$clr.";}\n";
        $rpl[] = "<a class=\"c".$i++."\">";
        $mtc[] = "<span style=\"color: #".$clr."\">";
    }
    $cls .= "</style>";
    $str2 = str_replace($mtc,$rpl,$str);
    if ( $return ) return $str2;
    else echo wp_kses_post( $str2 );
} // End ddtt_highlight_file2()


/**
 * Get active plugin list
 *
 * @param boolean $link
 * @param boolean $path
 * @param boolean $table
 * @return string
 */
function ddtt_get_active_plugins( $link = false, $path = false, $table = false ){
    // Convert to simple list if in query string
    if ( ddtt_get( 'simple_plugin_list', '==', 'true' ) ) {
        $path = false;
        $table = false;
    }
    
    // Get the active plugins list
    $active = get_option( 'active_plugins' );

    // Get all the plugins full info
    $all = get_plugins();

    // Start the table if we're building one
    if ( $table ) {
        $results = '<p><em>Note: some plugins may be missing last updated and WP compatibility if they were not downloaded from WP.org. These are usually premium/paid plugins.</em></p>
        <p><em>Items showing <span class="red-example">red</span> may be outdated and should be used with caution.</em></p>
        <table id="active-plugin-list" class="admin-large-table alternate-row">
            <tr>
                <th class="col-plugin">Plugin</th>
                <th class="col-version">Version</th>
                <th class="col-updated">Last Updated</th>
                <th class="col-compatible">WP Compatibility</th>
                <th class="col-size">Folder Size</th>';

        if ( $path ) {
            $results .= '<th class="col-path">Path to Main File</th>';
        }

        $results .= '<th class="col-date">Last Modified</th>
            </tr>';
    } else {

        // Set an empty array
        $activated_plugins = array();
    }

    // Only get the full info for the active plugins
    foreach ( $active as $p ){           
        if( isset( $all[$p] ) ){
            
            // Check if the plugin has a Plugin URL
            $name = '';
            if ( $link ) {
                if ( $all[$p]['PluginURI'] && $all[$p]['PluginURI'] != '' ) {
                    $name = '<a href="'.$all[$p]['PluginURI'].'" target="_blank">'.$all[$p]['Name'].'</a>';
                } elseif ( $all[$p]['AuthorURI'] && $all[$p]['AuthorURI'] != '' ) {
                    $name = '<a href="'.$all[$p]['AuthorURI'].'" target="_blank">'.$all[$p]['Name'].'</a>';
                } else {
                    $name = $all[$p]['Name'];
                }
            } else {
                $name = $all[$p]['Name'];
            }

            // Add author to name
            if ( $all[$p]['Author'] && $all[$p]['Author'] != '' ) {
                $name = $name.' <em>by '.$all[$p]['Author'].'</em>';
            } elseif ( $all[$p]['AuthorName'] && $all[$p]['AuthorName'] != '' ) {
                $name = $name.' <em>by '.$all[$p]['AuthorName'].'</em>';
            }

            // Add description
            if ( $table && $all[$p]['Description'] && $all[$p]['Description'] != '' ) {
                $name = $name.'<br>'.$all[$p]['Description'];
            }

            // Get the last updated date and tested up to version
            $last_updated = '';
            $old_class = '';
            $compatibility = '';
            $incompatible_class = '';
            $args = [ 
                'slug' => $all[$p]['TextDomain'], 
                'fields' => [
                    'last_updated' => true,
                    'tested' => true
                ]
            ];
            $response = wp_remote_post(
                'http://api.wordpress.org/plugins/info/1.0/',
                [
                    'body' => [
                        'action' => 'plugin_information',
                        'request' => serialize( (object)$args )
                    ]
                ]
            );
            if ( !is_wp_error( $response ) ) {
                $returned_object = unserialize( wp_remote_retrieve_body( $response ) );   
                if ( $returned_object ) {
                    
                    // Last Updated
                    $last_updated = $returned_object->last_updated;
                    $last_updated = ddtt_time_elapsed_string( $last_updated );
                    
                    // Add old class if more than 11 months old
                    $earlier = new DateTime( $last_updated );
                    $today = new DateTime( date( 'Y-m-d' ) );
                    $diff = $today->diff( $earlier )->format("%a");
                    if ( $diff >= 335 ) {
                        $old_class = ' warning';
                    }

                    // Compatibility
                    $compatibility = $returned_object->tested;

                    // Add incompatibility class
                    global $wp_version;
                    if ( $compatibility < $wp_version ) {
                        $incompatible_class = ' warning';
                    }
                }
            }

            // Displaying path?
            if ( $path && $table ) {
                $display_path = '<td>'.$p.'</td>';
            } elseif ( $path && !$table ) {
                $display_path = ' ('.$p.')';
            } else {
                $display_path = '';
            }

            // Get the folder size
            if ( !function_exists( 'get_dirsize' ) ) {
                require_once ABSPATH . WPINC . '/ms-functions.php';
            }

            // Strip the path to get the folder
            $p_parts = explode('/', $p);
            $folder = $p_parts[0];
             
            // Get the path of a directory.
            $directory = get_home_path().DDTT_PLUGINS_URL.'/'.$folder.'/';
             
            // Get the size of directory in bytes.
            $bytes = get_dirsize( $directory );
            
            // Get the MB
            // $folder_size = number_format( $bytes / ( 1024 * 1024 ), 1 ) . ' MB';
            $folder_size = ddtt_format_bytes( $bytes );

            // Get the last modified date
            $last_modified = date( 'F j, Y g:i A', filemtime( $directory ) );

            // Are we putting it in a table or no?
            if ( $table ) {
                $results .= '<tr>
                    <td>'.$name.'</td>
                    <td>Version '.$all[$p]['Version'].'</td>
                    <td class="'.$old_class.'">'.$last_updated.'</td>
                    <td class="'.$incompatible_class.'">'.$compatibility.'</td>
                    <td>'.$folder_size.'</td>
                    '.$display_path.'
                    <td>'.$last_modified.'</td>
                </tr>';
            } else {

                // Otherwise we are displaying in a single line
                $activated_plugins[] = $name.' - Version '.$all[$p]['Version'].$display_path;
            }
        }           
    }

    // End the table if we're building one
    if ($table) {
        $results .= '</table>';

    } else {

        // Or else implode each line as a string
        $results = '<div id="active-plugin-list">'.implode('<br>', $activated_plugins).'</div>';
    }

    // Return how we want to
    return $results;
} // End ddtt_get_active_plugins()


/**
 * Format bytes to b, KB, MB, GB
 *
 * @param int $bytes
 * @return string
 */
function ddtt_format_bytes( $bytes ) { 
    $bytes = floatval($bytes);
    if ($bytes >= 1073741824){
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
        $bytes = '0 bytes';
    }

    return $bytes;
} // End ddtt_format_bytes()


/**
 * Convert time to elapsed string
 *
 * @param [type] $datetime
 * @param boolean $full
 * @return string
 */
function ddtt_time_elapsed_string( $datetime, $full = false ) {
    $now = new DateTime;
    $ago = new DateTime( $datetime );
    $diff = $now->diff( $ago );

    $diff->w = floor( $diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ( $string as $k => &$v ) {
        if ( $diff->$k ) {
            $v = $diff->$k . ' ' . $v . ( $diff->$k > 1 ? 's' : '' );
        } else {
            unset( $string[$k] );
        }
    }

    if ( !$full ) $string = array_slice( $string, 0, 1 );
    return $string ? implode( ', ', $string ) . ' ago' : 'just now';
} // End ddtt_time_elapsed_string()


/**
 * Simplify admin notice that allows passing arguments
 *
 * @param string $type // Accepts 'success' or 'error'
 * @param string $msg
 * @return void
 */
function ddtt_admin_notice( $type, $msg ) {
    // Add the params to an array
    $args = [ 
        'type' => $type, 
        'msg' => $msg
    ];

    // Set the class
    $class = 'notice notice-'.$args[ 'type' ];

    // Set the message
    $message = __( $args[ 'msg' ], 'dev-debug-tools' );

    printf( '<div id="message" class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
} // End ddtt_admin_notice()


/**
 * Do some stuff on the testing tab
 *
 * @return void
 */
function ddtt_testing_playground_helpers() {
    // Increase the test number on every page load
    ddtt_increase_test_number();

    // Debug form and entry from query string
    $gf_not_active = 'Gravity Forms must be installed and activated.';
    if ( $form_id = ddtt_get( 'debug_form' ) ) {
        if ( is_plugin_active( 'gravityforms/gravityforms.php' ) ) {
            $form = GFAPI::get_form( $form_id );
            ddtt_print_r( $form );
        } else {
            ddtt_print_r( $gf_not_active );
        }
        echo '<br><br>';
    }
    if ( $entry_id = ddtt_get( 'debug_entry' ) ) {
        if ( is_plugin_active( 'gravityforms/gravityforms.php' ) ) {
            $entry = GFAPI::get_entry( $entry_id );
            ddtt_print_r( $entry );
        } else {
            ddtt_print_r( $gf_not_active );
        }
        echo '<br><br>';
    }
    echo '<br><br>';
} // End ddtt_testing_playground_helpers()


/**
 * Download a root file
 *
 * @param string $filename
 * @return void
 */
// WP-CONFIG
if ( isset( $_POST[ 'ddtt_download_wpconfig' ] ) ) {
    add_action( 'init', 'ddtt_download_wpconfig' );
}
function ddtt_download_wpconfig() {
    ddtt_download_root_file( 'wp-config.php' );
}

// HTACCESS
if ( isset( $_POST[ 'ddtt_download_htaccess' ] ) ) {
    add_action( 'init', 'ddtt_download_htaccess' );
}
function ddtt_download_htaccess() {
    ddtt_download_root_file( '.htaccess' );
}

// DEBUG.LOG
if ( isset( $_POST[ 'ddtt_download_debug_log' ] ) ) {
    add_action( 'init', 'ddtt_download_debug_log' );
}
function ddtt_download_debug_log() {
    ddtt_download_root_file( DDTT_CONTENT_URL.'/debug.log' );
}

// ADMIN ERROR_LOG
if ( isset( $_POST[ 'ddtt_download_admin_error_log' ] ) ) {
    add_action( 'init', 'ddtt_download_admin_error_log' );
}
function ddtt_download_admin_error_log() {
    ddtt_download_root_file( DDTT_ADMIN_URL.'/error_log' );
}

// ROOT ERROR_LOG
if ( isset( $_POST[ 'ddtt_download_error_log' ] ) ) {
    add_action( 'init', 'ddtt_download_error_log' );
}
function ddtt_download_error_log() {
    ddtt_download_root_file( 'error_log' );
}

// The function
function ddtt_download_root_file( $filename, $content_type = null ) {
    // Read the WPCONFIG
    if ( is_readable( ABSPATH.$filename ) ) {
        $file = ABSPATH.$filename;
    } elseif ( is_readable( dirname( ABSPATH ).'/'.$filename ) ) {
        $file = dirname( ABSPATH ).'/'.$filename;
    } else {
        $file = false;
    }

    // Get the mime type
    if ( is_null( $content_type ) ) {
        $content_type = mime_content_type( $file );
    }

    // No file?
    if ( !$file ) {
        die( 'Something went wrong. Path: '.ABSPATH . $filename );
    }

    // Copy the file a temp location
    if ( strpos( $filename, '/' ) !== false) {
        $tmp_filename = strstr( $filename, '/' );
    } else {
        $tmp_filename = $filename;
    }
    $tmp_file = DDTT_PLUGIN_INCLUDES_PATH.'files/tmp/'.$tmp_filename;
    copy( $file, $tmp_file );

    // Define header information
    header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
    header( 'Content-Description: File Transfer' );
    header( 'Content-Type: '.$content_type );
    header( 'Content-Disposition: attachment; filename="'.basename( $tmp_file ).'"' );
    header( 'Content-Length: ' . filesize( $tmp_file ) );
    header( 'Expires: 0' );
    header( 'Pragma: public' );

    ob_clean();
    flush();
    
    // Read the file and write it to the output buffer
    readfile( $tmp_file, true );

    // Remove the temp file
    @unlink( $tmp_file );

    // Terminate from the script
    die();
} // End ddtt_download_root_file()


/**
 * Download a plugin file
 *
 * @param string $filename
 * @return void
 */
// TESTING PLAYGROUND
if ( isset( $_POST[ 'ddtt_download_testing_pg' ]) ) {
    add_action( 'init', 'ddtt_download_testing_pg' );
}
function ddtt_download_testing_pg() {
    ddtt_download_plugin_file( 'TESTING_PLAYGROUND.php' );
}

// The function
function ddtt_download_plugin_file( $filename ) {
    // The path
    $plugin_file_path = DDTT_PLUGIN_ROOT . $filename;
    
    // Check if it exists and is readable
    if ( is_readable( $plugin_file_path ) ) {
        $file = $plugin_file_path;

    // Else we failed
    } else {
        $file = false;
    }

    if ( !$file ) {
        die( 'Something went wrong. Path: '.$plugin_file_path );
    }

    // Copy the file a temp location
    $tmp_file = DDTT_PLUGIN_INCLUDES_PATH.'files/'.$filename;
    copy( $file, $tmp_file );

    // Define header information
    header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
    header( 'Content-Description: File Transfer' );
    header( 'Content-Type: text/x-php' );
    header( 'Content-Disposition: attachment; filename="'.basename( $tmp_file ).'"' );
    header( 'Content-Length: ' . filesize( $tmp_file ) );
    header( 'Expires: 0' );
    header( 'Pragma: public' );

    ob_clean();
    flush();
    
    // Read the file and write it to the output buffer
    readfile( $tmp_file, true );

    // Remove the temp file
    @unlink( $tmp_file );

    // Terminate from the script
    die();
} // End ddtt_download_plugin_file()


/**
 * THE END
 */