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
 * $args = [ 'default' => 'Default Value', 'required' => true, 'submit_button' => true ]
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
            $autocomplete = ' autocomplete="off"';
        } else {
            $pattern = '';
            $autocomplete = '';
        }
        
        $input = '<input type="text" id="'.esc_attr( $option_name ).'" name="'.esc_attr( $option_name ).'" value="'.esc_attr( $value ).'" style="width: '.esc_attr( $width ).'"'.$pattern.$autocomplete.$required.'/>';

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
            $autocomplete = ' autocomplete="off"';
        } else {
            $pattern = '';
            $autocomplete = '';
        }

        if ( !is_array( $value ) ) {
            $value = [ $value ];
        }
        
        $input = '<div id="text_plus_'.esc_attr( $option_name ).'">
            <a href="#" class="add_form_field">Add New Field +</a>
            <div><input type="text" id="'.esc_attr( $option_name ).'" name="'.esc_attr( $option_name ).'[]" value="'.esc_attr( $value[0] ).'" style="width: '.esc_attr( $width ).'"'.$pattern.$autocomplete.$required.'/></div>
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

    // Submit button
    if ( !is_null( $args ) && isset( $args[ 'submit_button' ] ) && $args[ 'submit_button' ] == true ) {
        $submit_button = get_submit_button( 'Search', 'button button-primary button-large '.$option_name );
    } else {
        $submit_button = '';
    }

    // Build the row
    $row = '<tr valign="top">
        <th scope="row">'.$label.'</th>
        <td>'.$input.$submit_button.' '.$incl_comments.'</td>
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
            'id' => [],
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
            'style' => [],
            'target' => [],
            'rel' => []
        ],
        'img' => [
            'border' => [],
            'id' => [],
            'class' => [],
            'style' => [],
            'src' => [],
            'alt' => []
        ],
        'table' => [
            'class' => []
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
            'method' => [],
            'id' => [],
            'action' => [],
        ],
        'label' => [
            'for' => [],
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
            'size' => [],
            'autocomplete' => [],
        ],
        'textarea' => [
            'type' => [],
            'id' => [],
            'class' => [],
            'name' => [],
            'rows' => [],
            'cols' => [],
            'required' => [],
            'autocomplete' => [],
        ],
        'select' => [
            'id' => [],
            'class' => [],
            'name' => [],
            'required' => [],
            'autocomplete' => [],
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
        ],
        'em' => [],
        'strong' => []
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
 * ///TODO: Add actual error.log counts to total errors
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
    if ( is_readable( ABSPATH.'/'.$path ) ) {
        $file = ABSPATH.$path;
    } elseif ( is_readable( dirname( ABSPATH ).'/'.$path ) ) {
        $file = dirname( ABSPATH ).'/'.$path;
    }

    // Check if the file exists
    if ( $file ) {
        // If so, get it
        $string = file_get_contents( $file );

        // Separate each line into an array item
        $lines = explode( PHP_EOL, $string );

        // Empty array
        $modified_lines = [];

        // Default CSS
        $results = '';

        // Count the total number of lines
        $total_count = count( $lines );
        
        // How many lines are we allowing?
        $allowed_qty = 100;

        // Offset
        $allowed_qty_with_offset = $allowed_qty + 1;

        // Get the difference
        if ( $total_count > $allowed_qty_with_offset ) {
            $start_count = $total_count - $allowed_qty_with_offset;
        } else {
            $start_count = 0;
        }

        // Are we displaying the debug.log?
        if ( $log ) {

            // Iter
            for ( $i = $start_count; $i < $total_count; $i++ ){

                // Line var
                $line = $lines[ $i ];
            
                // If so, we're going to filter out blank lines
                if ( $line != '' ) {

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
    
                    // Escape any html
                    $esc_line = esc_html( $new_line );

                    // Add the line
                    $modified_lines[] = '<div class="debug-li"><span class="debug-ln unselectable">'.round( $i + 1 ).'</span><span class="ln-content">'.$esc_line.'</span></div>';
                }
            }

        // Otherwise, no log
        } else {

            // Start the line count
            $line_count = 1;

            // For each line...
            foreach( $lines as $key => $line ) {

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
        $code = implode( '', $modified_lines );
        
    } else {
        // Otherwise say the file wasn't found
        $code = $path . ' not found';
    }

    // Check if we have lines
    if ( !empty( $lines ) ) {

        // Get the converted time
        $utc_time = date( 'Y-m-d H:i:s', filemtime( $file ) );
        $dt = new DateTime( $utc_time, new DateTimeZone( 'UTC' ) );
        $dt->setTimezone( new DateTimeZone( get_option( 'ddtt_dev_timezone', wp_timezone_string() ) ) );
        $last_modified = $dt->format( 'F j, Y g:i A T' );

        // Include last number of lines for log
        if ( $log && $total_count > $allowed_qty ) {
            $incl_showing = ' (Showing last '.$allowed_qty.')';
        } else {
            $incl_showing = '';
        }
            
        // Display the error count
        $results .= 'Lines: <strong>'.$total_count.'</strong>'.$incl_showing.' <span class="sep">|</span> Filesize: <strong>'.ddtt_format_bytes( filesize( $file ) ).'</strong> <span class="sep">|</span> Last Modified: <strong>'.$last_modified.'</strong><br><br>';
    }
    
    // Return the code with the defined path at top
    $results .= '<pre class="code">Installation path: '.ABSPATH.$path.'<br><br>'.$code.'</pre>';

    return $results;
} // End ddtt_view_file_contents()


/**
 * Return a log file in an Easy-to-Read format
 * Home path is public_html/
 * Include filename in path
 * USAGE: ddtt_view_file_contents_easy_reader( 'wp-config.php' );
 * If log file, include highlight args as follows:
 * ddtt_view_file_contents_easy_reader( $path, true, array(
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
function ddtt_view_file_contents_easy_reader( $path, $log = false, $highlight_args = [], $allow_repeats = true ){
    // Define the file
    $file = FALSE;
    if ( is_readable( ABSPATH.'/'.$path ) ) {
        $file = ABSPATH.$path;
    } elseif ( is_readable( dirname( ABSPATH ).'/'.$path ) ) {
        $file = dirname( ABSPATH ).'/'.$path;
    }

    // dpr( $highlight_args );

    // Start results
    $results = '';

    // Store the actual lines we are displaying
    $actual_lines = [];

    // Check if the file exists
    if ( $file ) {
        // If so, get it
        $string = file_get_contents( $file );

        // Separate each line in the file into an array item
        $lines = explode( PHP_EOL, $string );

        // Store the rests here for checking repeats
        $rests = [];

        // Start the line count
        $line_count = $log ? 0 : 1;

        // Default CSS
        $results = '';

        // Check if we have lines
        if ( !empty( $lines ) ) {

            // Get the dev's timezone
            $dev_timezone = get_option( DDTT_GO_PF.'dev_timezone', wp_timezone_string() );

            // For each file line...
            foreach( $lines as $line ) {

                // Check if we're viewing a log
                if ( $log ) {

                    // If so, we're going to filter out blank lines
                    if ( $line != '' ) {

                        // By default, this should be a new actual line
                        $new_actual_line = true;

                        // Increase the line count
                        $line_count ++;

                        // Stack trace bool
                        $is_stack = false;

                        // Starting qty
                        $qty = 1;

                        // Check for a date section
                        $date_section = false;
                        if ( preg_match( '/\[(.*?)\]/s', $line, $get_date_section ) ) {
                            if ( ddtt_is_date( $get_date_section[1] ) ) {
                                $date_section = $get_date_section;
                            }
                        }
        
                        // Check for a date section
                        if ( $date_section ) {

                            // Strip the brackets and timezone
                            $date_parts = explode( ' ', $date_section[1] );
                            $stripped_date = $date_parts[0].' '.$date_parts[1];

                            // Convert timezone
                            $datetime = new DateTime( $stripped_date, new DateTimeZone( 'UTC' ) );
                            $datetime->setTimezone( new DateTimeZone( $dev_timezone ) );

                            // Get the date, time and shortened timezone
                            $date = $datetime->format('F j, Y');
                            $time = $datetime->format('g:i A');
                            $tz = $datetime->format('T');
                            $display_date = $date.'<br>'.$time.' '.$tz;

                            // Get the rest of the line
                            $rest = substr( $line, strlen( $date_section[0] ) );

                            // Add classes to the line based on keywords found
                            $class = '';
                            if ( !empty( $highlight_args ) ) {

                                // Iter the args
                                foreach ( $highlight_args as $hl_key => $hl ) {

                                    // Make sure we have a keyword/class and the column is err
                                    if ( isset( $hl[ 'keyword' ] ) && 
                                         isset( $hl[ 'column' ] ) && 
                                         ( $hl[ 'column' ] == 'err' || $hl[ 'column' ] == 'path' ) ) {

                                        // Get the keyword
                                        $keyword = sanitize_text_field( $hl[ 'keyword' ] );

                                        // Allow slashes
                                        $keyword = str_replace( '/', '\/', $keyword );

                                        // Search the line for the keyword
                                        if ( preg_match( '/'.$keyword.'/', $rest ) ) {
                                            $class .= ' '.esc_attr( $hl_key );
                                        }
                                    }
                                }
                            }

                            // Separate warning from path
                            // Remove html from the rest
                            $esc_line = esc_html( $rest );

                            // Does the path exist?
                            if ( strpos( $esc_line, 'in /' ) !== false ) {

                                // Let's split it up
                                $line_parts = explode( ' in /', $esc_line );

                                // The warning and error
                                $warning_and_error = $line_parts[0];

                                // Split the warning and error
                                if ( preg_match( '/PHP(.*?)\:/s', $warning_and_error, $wae ) ) {
                                    $warning = rtrim( $wae[0], ':' );
                                    $err = trim( str_replace( $warning.':', '', $warning_and_error ) );

                                // Otherwise it's unknown
                                } else {
                                    $warning = 'Unknown';
                                    $err = $warning_and_error;
                                }

                                // The path with the line number
                                $full_path = '/'.$line_parts[1];
                            
                            // Otherwise the whole thing is the error
                            } else {
                                $warning = 'Unknown';
                                $err = $esc_line;
                                $full_path = '';
                            }

                            // Prevent repeats
                            $path_only = '';
                            $on_line_num = 0;
                            if ( $allow_repeats ) {

                                // Iter the rests
                                $repeat = false;
                                $repeat_key = false;
                                foreach ( $rests as $rest_key => $r ) {

                                    // Have we already added this rest?
                                    if ( in_array( $rest, $r ) ) {
                                        
                                        // Found
                                        $repeat = true;
                                        $repeat_key = $rest_key;

                                        // Stop looking
                                        break;
                                    }
                                }

                                // Have we already added this rest?
                                if ( $repeat ) {

                                    // Don't add this line
                                    $new_actual_line = false;

                                    // Count this as a repeat
                                    $qty = $rests[ $repeat_key ][ 'qty' ] + 1;
                                    $rests[ $repeat_key ][ 'qty' ] = $qty;
                                        
                                } else {

                                    // Add the rest
                                    $rests[ $line_count ] = [
                                        'rest' => $rest,
                                        'qty' => $qty
                                    ];
                                }

                                // Check for a line number
                                if ( strval( strstr( $full_path, 'on line' ) ) ) {
                                    $path_parts = explode( ' ', $full_path );
                                    $path_only = $path_parts[0];
                                } elseif ( strpos( $full_path, ':' ) !== false ) {
                                    $path_parts = explode( ':', $full_path );
                                    $path_only = $path_parts[0];
                                }

                                // Get the line number by itself
                                if( preg_match_all( '/\d+/', $rest, $on_line_numbers ) ) {
                                    $on_line_num = end( $on_line_numbers[0] );
                                }
                            }

                        // Or if there is no date
                        } else {

                            // Check if it is a stack trace
                            if ( str_starts_with( $line, 'Stack trace' ) || str_starts_with( $line, '#' ) || str_starts_with( ltrim( $line ), 'thrown' ) ) {
                                $is_stack = true;
                                $new_actual_line = false;

                            // Otherwise something is fishy
                            } else {
                                $display_date = '--';
                                $warning = 'Unknown';
                                $err = $line;
                                $path_only = '';
                                $on_line_num = '';
                                $class = '';
                            }
                        }
                            
                        // Are we creating a new line?
                        if ( $new_actual_line ) {
                            
                            // Check for a search filter
                            if ( $search = ddtt_get( 's' ) ) {

                                // Sanitize the text
                                $search = sanitize_text_field( $search );
                                // dpr( $search );

                                // Convert to lowercase
                                $search_lc = strtolower( $search );

                                // Which column?
                                if ( ddtt_get( 'c', '==', 't' ) ) {
                                    $col = $warning;
                                } else if ( ddtt_get( 'c', '==', 'p' ) ) {
                                    $col = $path_only;
                                } else {
                                    $col = $err;
                                }

                                // Continue var
                                $continue = false;

                                // Separate the words by spaces
                                $words = explode( ' ', $search_lc );

                                // Store the words to search for here
                                $add = [];

                                // Store the words to remove here
                                $remove = [];

                                // Iter the words
                                foreach ( $words as $w ) {

                                    // Check the word for subtractions
                                    if ( str_starts_with( $w, '-' ) !== false ) {

                                        // Add the word to the remove array
                                        $remove[] = ltrim( $w, '\-' );

                                    } else {

                                        // Add the word to the add array
                                        $add[] = $w;
                                    }
                                }

                                // Now search the column for the adds
                                if ( !empty( $add ) ) {

                                    // Iter the adds
                                    foreach ( $add as $a ) {

                                        // If the line does not contain the add, then skip it
                                        if ( strpos( strtolower( $col ), $a ) === false ) {
                                            $continue = true;
                                        }
                                    }
                                }
                                
                                // Now search the column for the removes
                                if ( !empty( $remove ) ) {

                                    // Iter the removes
                                    foreach ( $remove as $r ) {

                                        // If the line contains the remove, then skip it
                                        if ( strpos( strtolower( $col ), $r ) !== false ) {
                                            $continue = true;
                                        }
                                    }
                                }

                                // Continue now?
                                if ( $continue ) {
                                    continue;
                                }
                            }

                            // Store the new actual line
                            $actual_lines[] = [
                                'line'  => $line_count,
                                'date'  => $display_date,
                                'type'  => $warning,
                                'err'   => $err,
                                'path'  => $path_only,
                                'lnum'  => $on_line_num,
                                'class' => $class
                            ];

                        // Or add the stack
                        } elseif ( $is_stack ) {

                            // Get the current stack lines
                            if ( isset( $actual_lines[ count( $actual_lines ) - 1 ][ 'stack' ] ) ) {
                                $stack_lines = $actual_lines[ count( $actual_lines ) - 1 ][ 'stack' ];
                            } else {
                                $stack_lines = [];
                            }

                            // If the line has not been added
                            if ( !in_array( $line, $stack_lines ) ) {

                                // Then add the line
                                $actual_lines[ count( $actual_lines ) - 1 ][ 'stack' ][] = $line;
                            }
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

            // Now that we have actual lines, let's add them
            // dpr( $actual_lines );
            if ( !empty( $actual_lines ) ) {

                // Start the table
                $code = '<table class="log-table easy-reader">
                <tr>
                    <th class="line">Line #</th>
                    <th class="date">Date/Time</th>
                    <th class="type">Type</th>
                    <th class="err">Error</th>
                    <th class="qty">Qty</th>
                    <th class="help">Help</th>
                </th>';

                // Get help links
                $search_engines = apply_filters( 'ddtt_debug_log_help_col', [
                    'google' => [
                        'name'   => 'Google',
                        'url'    => 'https://www.google.com/search?q=',
                        'format' => '{type}: {err}',
                        'filter' => false
                    ],
                    'google_past_year' => [
                        'name'   => 'Google Past Year',
                        'url'    => 'https://www.google.com/search?as_qdr=y&q=',
                        'format' => '{type}: {err}',
                        'filter' => false
                    ],
                    'google_with_path' => [
                        'name'   => 'Google With Path',
                        'url'    => 'https://www.google.com/search?q=',
                        'format' => '{type}: {err} in {path}',
                        'filter' => 'path'
                    ],
                    'google_plugin' => [
                        'name'   => 'Google Plugin',
                        'url'    => 'https://www.google.com/search?q=',
                        'format' => '{type}: {err} {plugin}',
                        'filter' => 'plugin'
                    ],
                    'google_theme' => [
                        'name'   => 'Google Theme',
                        'url'    => 'https://www.google.com/search?q=',
                        'format' => '{type}: {err} {theme}',
                        'filter' => 'theme'
                    ],
                    'wp_plugin_support' => [
                        'name'   => 'Plugin Support',
                        'url'    => 'https://wordpress.org/search/',
                        'format' => '{type}: {err} intext:"Plugin: {plugin}"',
                        'filter' => 'plugin'
                    ],
                    'google_stackoverflow' => [
                        'name'   => 'Google:stackoverflow',
                        'url'    => 'https://www.google.com/search?as_sitesearch=stackoverflow.com&q=',
                        'format' => '{err}',
                        'filter' => false
                    ],
                    'stack_exchange' => [
                        'name'   => 'WP Stack Exchange',
                        'url'    => 'https://wordpress.stackexchange.com/search?q=',
                        'format' => '{err}',
                        'filter' => false
                    ]
                ] );

                // Get all plugins
                $plugins = get_plugins();

                // Get all themes
                $themes = wp_get_themes();

                // Are we only displaying the most recent error?
                if ( $most_recent = absint( ddtt_get( 'r' ) ) ) {
                    
                    // Get the last line key
                    $last_key = array_key_last( $actual_lines );
                    
                    // Iter the most recent
                    $recent_keys = [];
                    for ( $r = 0; $r < $most_recent; $r++ ) {

                        // Get the keys
                        $recent_keys[] = $last_key - $r;
                    }

                    // Unset the others
                    foreach ( $actual_lines as $al_key => $actual_line ) {
                        if ( !in_array( $al_key, $recent_keys ) ) {
                            unset( $actual_lines[ $al_key ] );
                        }
                    }
                }

                // Iter
                foreach ( $actual_lines as $actual_line ) {

                    // Set the error type class
                    $error_class = '';
                    foreach ( $highlight_args as $hl_key => $hl ) {

                        // Make sure we have a keyword/class and the column is err
                        if ( isset( $hl[ 'keyword' ] ) && 
                             isset( $hl[ 'column' ] ) && 
                             $hl[ 'column' ] == 'type' ) {

                            // Get the keyword
                            $error_type = sanitize_text_field( $hl[ 'keyword' ] );

                            // Search the line for the keyword
                            if ( preg_match( '/'.$error_type.'/', $actual_line[ 'type' ] ) ) {
                                $error_class = ' '.esc_attr( $hl_key );
                            }
                        }
                    }

                    // Is there a stack trace?
                    if ( isset( $actual_line[ 'stack' ] ) ) {
                        $stack = $actual_line[ 'stack' ];

                        // Iter the stack
                        $stack_array = [];
                        foreach ( $stack as $s ) {

                            // Shorten the paths
                            $s = str_replace( ABSPATH, '/', $s );
                            
                            // Add a class to the first line
                            if ( str_starts_with( $s, 'Stack trace' ) ) {
                                $stack_array[] = '<span class="stack-trace">'.$s.'</span>';

                            // Add spaces to thrown
                            } elseif ( str_starts_with( trim( $s ), 'thrown' ) ) {
                                $stack_array[] = '<span class="stack-thrown">'.$s.'</span>';

                            // Otherwise do nothing
                            } else {
                                $stack_array[] = $s;
                            }
                        }
                        $display_stack = '<br><br>'.implode( '<br>', $stack_array );
                    } else {
                        $display_stack = '';
                    }
                    
                    // Shorten the path
                    $short_path = str_replace( ABSPATH, '/', $actual_line[ 'path' ] );

                    // Get the admin url
                    if ( is_multisite() ) {
                        $admin_url = str_replace( site_url( '/' ), '', rtrim( network_admin_url(), '/' ) );
                    } else {
                        $admin_url = DDTT_ADMIN_URL;
                    }

                    // Check if it's a plugin
                    $plugin_name = '';
                    $theme_name = '';
                    $plugin_or_theme = '';
                    $plugin_requires = false;
                    if ( strpos( $short_path, DDTT_PLUGINS_URL ) !== false ) {

                        // If so, get the plugin slug
                        $plugin_path_and_filename = str_replace( DDTT_PLUGINS_URL, '', ltrim( $short_path, '\/' ) );
                        $plugin_path_parts = explode( '/', $plugin_path_and_filename );
                        $plugin_slug = $plugin_path_parts[1];
                        $plugin_filename = substr( $plugin_path_and_filename, strpos( $plugin_path_and_filename, '/' ) + 1);
                    
                        // Now check the active plugins for the file
                        $plugin_folder_and_file = false;
                        foreach( $plugins as $key => $ap ) {                            
                            if ( str_starts_with( $key, $plugin_slug ) ) {
                                $plugin_folder_and_file = $key;
                            }
                        }

                        // Make sure we found the file
                        if ( $plugin_folder_and_file ) {

                            // Require the get_plugin_data function
                            if( !function_exists( 'get_plugin_data' ) ){
                                require_once( ABSPATH.DDTT_ADMIN_URL.'/includes/plugin.php' );
                            }

                            // Get the file
                            $plugin_file = ABSPATH.DDTT_PLUGINS_URL.'/'.$plugin_folder_and_file;

                            // Get the plugin data
                            $plugin_data = get_plugin_data( $plugin_file );
                            
                            // Check if requires exists
                            if ( $plugin_data[ 'RequiresWP' ] && $plugin_data[ 'RequiresWP' ] != '' ) {
                                $plugin_requires = true;
                            }

                            // Store for search filter merge tags
                            $plugin_name = $plugin_data[ 'Name' ];

                            // This is what we will display
                            $plugin_or_theme = 'Plugin: '.$plugin_name.'<br>';

                            // Update short file path link
                            $short_path = '<a href="/'.esc_attr( $admin_url ).'/plugin-editor.php?file='.esc_attr( urlencode( $plugin_filename ) ).'&plugin='.esc_attr( $plugin_slug ).'%2F'.esc_attr( $plugin_slug ).'.php" target="_blank">'.esc_attr( $short_path ).'</a>';
                            
                        }

                    // Check if it's a theme file
                    } elseif ( strpos( $short_path, DDTT_CONTENT_URL.'/themes/' ) !== false ) {

                        // Theme parts
                        $theme_parts = explode( '/', ltrim( $short_path, '\/' ) );
                        $theme_filename = $theme_parts[3];
                        $theme_slug = $theme_parts[2];

                        // Check if the themes exists in the array
                        $theme_name = 'Unknown';
                        foreach ( $themes as $k => $t ) {
                            if ( $k == $theme_slug ) {
                                $theme_name = $t->get( 'Name' );
                            }
                        }

                        // This is what we will display
                        $plugin_or_theme = 'Theme: '.$theme_name.'<br>';

                        // Update short file path link
                        $short_path = '<a href="/'.esc_attr( $admin_url ).'/theme-editor.php?file='.esc_attr( urlencode( $theme_filename ) ).'&theme='.esc_attr( $theme_slug ).'" target="_blank">'.esc_attr( $short_path ).'</a>';
                    }

                    // Check for a qty
                    if ( isset( $rests[ $actual_line[ 'line' ] ] ) ) {
                        $final_qty = $rests[ $actual_line[ 'line' ] ][ 'qty' ];
                    } else {
                        $final_qty = 1;
                    }
                    
                    // Iter the search engines
                    $help_links = [];
                    foreach ( $search_engines as $se ) {

                        // Get the format
                        $format = $se[ 'format' ];

                        // Only include "plugin or theme or path" if they exist on the line
                        if ( $se[ 'filter' ] == 'plugin' && $plugin_name == '' ) {
                            continue;
                        } elseif ( $se[ 'filter' ] == 'theme' && $theme_name == '' ) {
                            continue;
                        } elseif ( $se[ 'filter' ] == 'path' && $short_path == '' ) {
                            continue;
                        }

                        // Now if plugin, check if it's on WP.org, skip if not
                        if ( $se[ 'filter' ] == 'plugin' && strpos( $se[ 'url' ], 'wordpress.org' ) !== false && !$plugin_requires ) {
                            continue;
                        }

                        // Replace merge tags in format
                        $merge_tags = [
                            '{type}'            => $actual_line[ 'type' ],
                            '{err}'             => $actual_line[ 'err' ],
                            '{path}'            => str_replace( ABSPATH, '/', $actual_line[ 'path' ] ),
                            '{plugin}'          => $plugin_name,
                            '{theme}'           => $theme_name
                        ];
                        foreach ( $merge_tags as $merge_tag => $search_value ) {
                            $format = str_replace( $merge_tag, $search_value, $format );
                        }

                        // Get the name
                        $name = $se[ 'name' ];
                        
                        // Add the link
                        $help_links[] = '<a class="help-links" href=\''.$se[ 'url' ].$format.'\' target="_blank" rel="noopener noreferrer">'.$name.'</a>';
                    }

                    // Add file and line number
                    if ( $actual_line[ 'type' ] != 'Unknown' ) {
                        $file_and_line = 'File: '.$short_path.'<br>Line: '.$actual_line[ 'lnum' ];
                    } else {
                        $file_and_line = '';
                    }
                    
                    // Create the row
                    $code .= '<tr class="debug-li'.$error_class.$actual_line[ 'class' ].'">
                        <td class="line"><span class="unselectable">'.$actual_line[ 'line' ].'</span></td>
                        <td class="date">'.$actual_line[ 'date' ].'</td>
                        <td class="type">'.$actual_line[ 'type' ].'</td>
                        <td class="err"><span class="the-error">'.$actual_line[ 'err' ].'</span>'.$plugin_or_theme.$file_and_line.$display_stack.'</td>
                        <td class="qty">x '.$final_qty.'</td>
                        <td class="help">'.implode( '<br>', $help_links ).'</td>
                    </tr>';
                }

                // End the table
                $code .= '</table>';

            // Else no lines
            } else {

                // Are we searching?
                if ( ddtt_get( 's' ) ) {
                    $code = 'No lines found when searching "'.ddtt_get( 's' ).'"';

                // No? Okay, then just say it isn't so (but this should never happen)
                } else {
                    $code = 'No lines found.';
                }
            }
            // dpr( $actual_lines );
            
        } else {
            $code = 'No errors.';
        }
        
    } else {
        // Otherwise say the file wasn't found
        $code = $path . ' not found';
    }

    // Check if we have lines
    if ( !empty( $lines ) ) {

        // Get the converted time
        $utc_time = date( 'Y-m-d H:i:s', filemtime( $file ) );
        $dt = new DateTime( $utc_time, new DateTimeZone( 'UTC' ) );
        $dt->setTimezone( new DateTimeZone( get_option( 'ddtt_dev_timezone', wp_timezone_string() ) ) );
        $last_modified = $dt->format( 'F j, Y g:i A T' );
            
        // Display the error count
        $results .= 'Lines: <strong>'.$line_count.'</strong> <span class="sep">|</span> Unique Errors: <strong>'.count( $actual_lines ).'</strong> <span class="sep">|</span> Filesize: <strong>'.ddtt_format_bytes( filesize( $file ) ).'</strong> <span class="sep">|</span> Last Modified: <strong>'.$last_modified.'</strong><br><br>';
    }

    // Return the code with the defined path at top
    $results .= 'Installation path: '.ABSPATH.$path.'<br><br>'.$code;

    return $results;
} // End ddtt_view_file_contents_easy_reader()


/**
 * Validate that a date is an actual date
 *
 * @param [type] $date
 * @return bool
 */
function ddtt_is_date( $date ) {
    return (bool)strtotime( $date );
} // End ddtt_validate_date()


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
        $options_names = [];
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
    $args = [ 
        'post_type' => $post_type,
        'post_status' => 'publish',
        'posts_per_page' => -1,
    ];

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

    // Store the plugins for all sites here
    $plugins = [];

    // If on the network, let's get all the sites plugins, not just the local
    if ( is_multisite() ) {

        // Get the network active plugins
        $network_active = get_site_option( 'active_sitewide_plugins' );

        // Add them to the active array
        foreach ( $network_active as $na_key => $na ) {
            $plugins[ $na_key ] = 'network';
        }

        // Get all the sites
        global $wpdb;
        $subsites = $wpdb->get_results( "SELECT blog_id, domain, path FROM $wpdb->blogs WHERE archived = '0' AND deleted = '0' AND spam = '0' ORDER BY blog_id" );
        
        // Iter the sites
        if ( $subsites && !empty( $subsites ) ) {
            foreach( $subsites as $subsite ) {

                // Get the plugins
                $site_active = get_blog_option( $subsite->blog_id, 'active_plugins' );

                // Iter each plugin
                foreach ( $site_active as $site ) {
                    
                    // Only continue if the plugin hasn't already been added by the network
                    if ( !isset( $plugins[ $site ] ) ) {
                        
                        // Add the site
                        $plugins[ $site ][] = $subsite->blog_id;
                    }
                }
            }
        }

    // If not on multisite network
    } else {

        // Get the active plugins
        $site_active = get_option( 'active_plugins' );

        // Iter each plugin
        foreach ( $site_active as $site ) {
            $plugins[ $site ] = 'local';
        }
    }

    // Get all the plugins full info
    $all = get_plugins();

    // Iter each
    foreach ( $all as $k => $a ) {

        // Add the non-active plugins
        if ( !array_key_exists( $k, $plugins ) ) {
            $plugins[ $k ] = false;
        }
    }

    // Start the table if we're building one
    if ( $table ) {

        // If on multisite, we need a site column
        if ( is_network_admin() ) {
            $site_col = '<th class="col-site">Sites</th>';
        } else {
            $site_col = '';
        }

        // The table
        $results = '<p><em>Note: some plugins may be missing last updated and WP compatibility if they were not downloaded from WP.org. These are usually premium/paid plugins.</em></p>
        <p><em>Items showing <span class="red-example">red</span> may be outdated and should be used with caution.</em></p>
        <table id="active-plugin-list" class="admin-large-table alternate-row">
            <tr>
                <th class="col-active">Active</th>
                <th class="col-plugin">Plugin</th>
                '.$site_col.'
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

    // Get the full info for the plugins
    foreach ( $plugins as $key => $p ){      
        
        // Make sure the plugin exists
        if( isset( $all[ $key ] ) ){
            
            // Check if the plugin has a Plugin URL
            $name = '';
            if ( $link ) {
                if ( $all[ $key ][ 'PluginURI' ] && $all[ $key ][ 'PluginURI' ] != '' ) {
                    $name = '<a href="'.$all[ $key ][ 'PluginURI' ].'" target="_blank">'.$all[ $key ][ 'Name' ].'</a>';
                } elseif ( $all[ $key ][ 'AuthorURI' ] && $all[ $key ][ 'AuthorURI' ] != '' ) {
                    $name = '<a href="'.$all[ $key ][ 'AuthorURI' ].'" target="_blank">'.$all[ $key ][ 'Name' ].'</a>';
                } else {
                    $name = $all[ $key ][ 'Name' ];
                }
            } else {
                $name = $all[ $key ][ 'Name' ];
            }

            // Add author to name
            if ( $all[ $key ][ 'Author' ] && $all[ $key ][ 'Author' ] != '' ) {
                $name = $name.' <em>by '.$all[ $key ][ 'Author' ].'</em>';
            } elseif ( $all[ $key ][ 'AuthorName' ] && $all[ $key ][ 'AuthorName' ] != '' ) {
                $name = $name.' <em>by '.$all[ $key ][ 'AuthorName' ].'</em>';
            }

            // Add description
            if ( $table && $all[ $key ][ 'Description' ] && $all[ $key ][ 'Description' ] != '' ) {
                $name = $name.'<br>'.$all[ $key ][ 'Description' ];
            }

            // Get the last updated date and tested up to version
            $last_updated = '';
            $old_class = '';
            $compatibility = '';
            $incompatible_class = '';
            $args = [ 
                'slug' => $all[ $key ][ 'TextDomain' ], 
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
                $display_path = '<td>'.$key.'</td>';
            } elseif ( $path && !$table ) {
                $display_path = ' ('.$key.')';
            } else {
                $display_path = '';
            }

            // Get the folder size
            if ( !function_exists( 'get_dirsize' ) ) {
                require_once ABSPATH . WPINC . '/ms-functions.php';
            }

            // Strip the path to get the folder
            $p_parts = explode('/', $key);
            $folder = $p_parts[0];
             
            // Get the path of a directory.
            $directory = get_home_path().DDTT_PLUGINS_URL.'/'.$folder.'/';
             
            // Get the size of directory in bytes.
            $bytes = get_dirsize( $directory );
            
            // Get the MB
            // $folder_size = number_format( $bytes / ( 1024 * 1024 ), 1 ) . ' MB';
            $folder_size = ddtt_format_bytes( $bytes );

            // Get the last modified date and convert to developer's timezone
            $utc_time = date( 'Y-m-d H:i:s', filemtime( $directory ) );
            $dt = new DateTime( $utc_time, new DateTimeZone( 'UTC' ) );
            $dt->setTimezone( new DateTimeZone( get_option( 'ddtt_dev_timezone', wp_timezone_string() ) ) );
            $last_modified = $dt->format( 'F j, Y g:i A T' );

            // Are we putting it in a table or no?
            if ( $table ) {

                // If plugin is active or on multisite
                if ( $p !== false ) {

                    // If on multisite
                    if ( is_multisite() ) {

                        // If network activated
                        if ( $p == 'network' ) {
                            $is_active = 'Network';
                            $active_class = 'active';

                        // If on this site
                        } elseif ( ( !is_network_admin() && in_array( get_current_blog_id(), $p ) ) || is_network_admin() ) {
                            $is_active = 'Local Only';
                            $active_class = 'active';

                        // If not on this site
                        } else {
                            $is_active = 'No';
                            $active_class = 'inactive';
                        }
                    } else {
                        $is_active = 'Yes';
                        $active_class = 'active';
                    }

                // If inactive and not on network
                } else {
                    $is_active = 'No';
                    $active_class = 'inactive';
                }

                // If on multisite network
                if ( is_network_admin() ) {
                    $site_names = [];
                    if ( $p == 'network' ) {
                        $site_names[] = 'Network Active';
                    } elseif ( is_array( $p ) ) {
                        foreach ( $p as $site_id ) {
                            $site_names[] = get_blog_details( $site_id )->blogname;
                        }
                    }
                    $site_row = '<td>'.implode( ', ', $site_names ).'</td>';
                } else {
                    $site_row = '';
                }

                // The table row
                $results .= '<tr class="'.$active_class.'">
                    <td>'.$is_active.'</td>
                    <td>'.$name.'</td>
                    '.$site_row.'
                    <td>Version '.$all[ $key ]['Version'].'</td>
                    <td class="'.$old_class.'">'.$last_updated.'</td>
                    <td class="'.$incompatible_class.'">'.$compatibility.'</td>
                    <td>'.$folder_size.'</td>
                    '.$display_path.'
                    <td>'.$last_modified.'</td>
                </tr>';
            } else {

                // Otherwise we are displaying in a single line
                $activated_plugins[] = $name.' - Version '.$all[ $key ]['Version'].$display_path;
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

    printf( '<div id="message" class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), wp_kses_post( $message ) );
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