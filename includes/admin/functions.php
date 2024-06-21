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
    } elseif ( !is_null( $args ) && isset( $args[ 'default' ] ) && $args[ 'default' ] != '' ) {
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

    // Autocomplete?
    $autocomplete = ' autocomplete="off"';

    // Checkbox
    if ( $type == 'checkbox' ) {
        $input = '<input type="checkbox" id="'.esc_attr( $option_name ).'" name="'.esc_attr( $option_name ).'" value="1" '.checked( 1, $value, false ).''.$required.'/>';

    // Checkboxes
    } elseif ( $type == 'checkboxes' ) {
        if ( !is_null( $args ) ) {
            $options = $args[ 'options' ];
            $class = isset( $args[ 'class' ] ) ? ' class="'.esc_attr( $args[ 'class' ] ).'"' : '';
        } else {
            return false;
        }

        // Sort by label
        usort( $options, function ( $item1, $item2 ) {
            return strtolower( $item1[ 'label' ] ) <=> strtolower( $item2[ 'label' ] );
        });

        // Iter the options
        $input = '';
        foreach ( $options as $option ) {
            if ( isset( $option[ 'value' ] ) && isset( $option[ 'label' ] ) ) {
                $option_value = $option[ 'value' ];
                $option_label = $option[ 'label' ];
            } elseif ( !is_array( $option ) ) {
                $option_value = $option;
                $option_label = $option;
            }
            if ( ( !empty( $value ) && array_key_exists( $option_value, $value ) ) || ( get_option( $option_name ) === false && isset( $option[ 'checked' ] ) && $option[ 'checked' ] == true ) ) {
                $checked = ' checked="checked"';
            } else {
                $checked = '';
            }

            $input .= '<div class="checkbox_cont"><input type="checkbox" id="'.esc_attr( $option_name.'_'.$option_value ).'"'.$class.' name="'.esc_attr( $option_name ).'['.$option_value.']" value="1"'.$checked.'/> <label for="'.esc_attr( $option_name.'_'.$option_value ).'">'.$option_label.'</label></div>';
        }

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
        if ( !is_null( $args ) && isset( $args[ 'log_files' ] ) && $args[ 'log_files' ] == 'yes' ) {
            $file = false;
            if ( $value != '' ) {
                if ( is_readable( ABSPATH.'/'.$value ) ) {
                    $file = ABSPATH.''.$value;
                } elseif ( is_readable( dirname( ABSPATH ).'/'.$value ) ) {
                    $file = dirname( ABSPATH ).'/'.$value;
                } elseif ( is_readable( $value ) ) {
                    $file = $value;
                }
            }
            if ( $file ) {
                $verified = 'VERIFIED';
                $verified_class= 'enabled';
            } else {
                $verified = 'FILE NOT FOUND';
                $verified_class= 'disabled';
            }
            $log_file_verified = ' <code class="verification '.$verified_class.'">'.$verified.'</code> <button type="button" class="button check hide">CHECK</button>';
        } else {
            $log_file_verified = '';
        }
        
        $input = '<input type="text" id="'.esc_attr( $option_name ).'" name="'.esc_attr( $option_name ).'" value="'.esc_attr( $value ).'" style="width: '.esc_attr( $width ).'"'.$pattern.$autocomplete.$required.'/>'.$log_file_verified;

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
        
        $input = '<input type="number" id="'.esc_attr( $option_name ).'" name="'.esc_attr( $option_name ).'" value="'.esc_attr( $value ).'" style="width: '.esc_attr( $width ).'"'.$pattern.$autocomplete.$required.'/>';


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
        if ( !is_null( $args ) && isset( $args[ 'placeholder' ] ) ) {
            $placeholder = sanitize_textarea_field( $args[ 'placeholder' ] );
        } else {
            $placeholder = '';
        }
        $input = '<textarea type="text" id="'.esc_attr( $option_name ).'" name="'.esc_attr( $option_name ).'" rows="'.esc_attr( $rows ).'" cols="'.esc_attr( $cols ).'" placeholder="'.esc_html( $placeholder ).'" '.$autocomplete.$required.'>'.esc_html( $value ).'</textarea>';

    // Select    
    } elseif ( $type == 'select' ) {
        if ( !is_null( $args ) ) {
            $options = $args[ 'options' ];
            
            if ( isset( $args[ 'blank' ] ) ) {
                $blank = '<option value="">'.esc_html( $args[ 'blank' ] ).'</option>';
            } else {
                $blank = '';
            }

            if ( !is_null( $args ) && isset( $args[ 'width' ] ) ) {
                $width = $args[ 'width' ];
            } else {
                $width = '43.75rem';
            }
        } else {
            return false;
        }
        $input = '<select id="'.esc_attr( $option_name ).'" name="'.esc_attr( $option_name ).'"'.$required.' style="width: '.esc_attr( $width ).'">'.$blank;

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
        if ( !is_null( $args ) && isset( $args[ 'placeholder' ] ) ) {
            $placeholder = sanitize_textarea_field( $args[ 'placeholder' ] );
        } else {
            $placeholder = '';
        }
        if ( !is_null( $args ) && isset( $args[ 'log_files' ] ) && $args[ 'log_files' ] == 'yes' ) {
            $file = false;
            if ( $value[0] != '' ) {
                if ( is_readable( ABSPATH.'/'.$value[0] ) ) {
                    $file = ABSPATH.''.$value[0];
                } elseif ( is_readable( dirname( ABSPATH ).'/'.$value[0] ) ) {
                    $file = dirname( ABSPATH ).'/'.$value[0];
                } elseif ( is_readable( $value[0] ) ) {
                    $file = $value[0];
                }
            }
            if ( $file ) {
                $verified = 'VERIFIED';
                $verified_class= 'enabled';
            } else {
                $verified = 'FILE NOT FOUND';
                $verified_class= 'disabled';
            }
            $log_file_verified = ' <code class="verification '.$verified_class.'">'.$verified.'</code> <button type="button" class="button check hide">CHECK</button>';
        } else {
            $log_file_verified = '';
        }
        
        $input = '<div id="text_plus_'.esc_attr( $option_name ).'">
            <a href="#" class="add_form_field">Add New Field +</a>
            <div><input type="text" id="'.esc_attr( $option_name ).'" name="'.esc_attr( $option_name ).'[]" value="'.esc_attr( $value[0] ).'" style="width: '.esc_attr( $width ).'" placeholder="'.esc_html( $placeholder ).'" '.$pattern.$autocomplete.$required.'/>'.$log_file_verified.'</div>
        </div>';

    // Otherwise return false
    } else {
        return false;
    }

    // If comments
    $incl_comments = '';
    if ( !is_null( $comments ) ) {
        if ( $comments == '' ) {
            $incl_comments = '';
        } elseif ( $comments == 'get_option' ) {
            $incl_comments = 'get_option( '.$option_name.' )';
        } elseif ( str_starts_with( $comments, '<br>' ) ) {
            $comments = ltrim( $comments, '<br>' );
            $incl_comments = '<p class="field-desc break">'.$comments.'</p>';
        } else {
            $incl_comments = '<p class="field-desc">'.$comments.'</p>';
        }
    }

    // Submit button
    if ( !is_null( $args ) && isset( $args[ 'submit_button' ] ) && $args[ 'submit_button' ] == true ) {
        $submit_button = get_submit_button( 'Search', 'button button-primary button-large '.$option_name );
    } else {
        $submit_button = '';
    }

    // Build the row
    $row = '<tr valign="top" id="row_'.esc_attr( $option_name ).'">
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
            'class' => [],
            'title' => [],
        ],
        'p' => [
            'id' => [],
            'class' => []
        ],
        'pre' => [
            'class' => []
        ],
        'code' => [
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
            'rel' => [],
            'data-name' => []
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
            'class' => [],
            'id' => [],
        ],
        'th' => [
            'scope' => [],
            'class' => []
        ],
        'td' => [
            'colspan' => [],
            'class' => [],
            'data-name' => []
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
            'placeholder' => [],
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
            'placeholder' => [],
        ],
        'select' => [
            'id' => [],
            'class' => [],
            'name' => [],
            'required' => [],
            'autocomplete' => [],
            'style' => []
        ],
        'option' => [
            'value' => [],
            'selected' => [],
        ],
        'button' => [
            'type' => [],
            'class' => [],
            'selected' => [],
            'style' => [],
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

    $functions = [];
    $nextStringIsFunc = false;
    $inClass = false;
    $bracesCount = 0;

    foreach( $tokens as $token ) {
        switch( $token[0] ) {
            case T_CLASS:
                $inClass = true;
                break;
            case T_FUNCTION:
                if ( !$inClass ) $nextStringIsFunc = true;
                break;

            case T_STRING:
                if ( $nextStringIsFunc ) {
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
                if ( $inClass ) $bracesCount++;
                break;

            case '}':
                if ( $inClass ) {
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
function ddtt_get_function_example( $function_name ) {
    // Check if the function exists
    if( function_exists( $function_name ) ){

        // Store the attributes here
        $attribute_names = [];

        // Get the function
        $fx = new ReflectionFunction( $function_name );

        // Get the params
        foreach ( $fx->getParameters() as $param ) {

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
        $display_fx = $function_name.'('.$attributes.')';
        return ddtt_highlight_string( $display_fx );

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
    if ( !empty( $forms ) ) {

        // Let's start the selection
        $results = '<select id="'.$id.'" name="'.$id.'">
            <option value="">-- Select a Form --</option>
            <option disabled>Active Forms</option>';

        // For each page
        foreach ( $forms as $form ) {

            // Get the page name, page id, and status
            $name = $form['title'];
            $page_id = $form['id'];

            // Return the option
            $results.= '<option value="'.$page_id.'"'.ddtt_is_qs_selected($page_id, $selected).'>'.$name.'</option>';
        }

        // Get inactive forms
        if ( $include_inactive ) {
            $inactive_forms = GFAPI::get_forms( false, false, 'title' );

            $results .= '<option disabled>Inactive Forms</option>';

            // For each page
            foreach ( $inactive_forms as $inactive_form ) {

                // Get the page name, page id, and status
                $name = $inactive_form['title'];
                $page_id = $inactive_form['id'];

                // Return the option
                $results.= '<option value="'.$page_id.'"'.ddtt_is_qs_selected( $page_id, $selected ).'>'.$name.'</option>';
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
function ddtt_error_count() {
    // Check if disabled
    if ( get_option( DDTT_GO_PF.'disable_error_counts' ) ) {
        return false;
    }

    // Check for error_log
    $error_log = FALSE;
    if ( is_readable( ABSPATH.'error_log' ) ) {
        $error_log = ABSPATH.'error_log';
    } elseif ( is_readable( dirname( ABSPATH ).'/error_log' ) ) {
        $error_log = dirname( ABSPATH ).'/error_log';
    }
    
    // Check for debug.log
    if ( WP_DEBUG_LOG && WP_DEBUG_LOG !== true ) {
        $debug_loc = WP_DEBUG_LOG;
    } else {
        $debug_loc =  DDTT_CONTENT_URL.'/debug.log';
    }
    $debug_log = FALSE;
    if ( is_readable( ABSPATH.$debug_loc ) ) {
        $debug_log = ABSPATH.$debug_loc;
    } elseif ( is_readable( dirname( ABSPATH ).'/'.$debug_loc ) ) {
        $debug_log = dirname( ABSPATH ).'/'.$debug_loc;
    } elseif ( is_readable( $debug_loc ) ) {
        $debug_log = $debug_loc;
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
 * Get current error reporting constants
 *
 * @return array
 */
function ddtt_get_error_reporting_constants( $return_e_all = false ) {
    // Store constants
    $constants = [];

    // Get the code
    $err_code = error_reporting();

    // If E_ALL
    if ( $return_e_all && $err_code == E_ALL ) {
        $constants[] = 'E_ALL';

    // Otherwise break it down
    } else {

        // Iter the codes
        $pot = 0;
        foreach ( array_reverse( str_split( decbin( $err_code ) ) ) as $bit ) {
            $constants[] = array_search( pow( 2, $pot ), get_defined_constants( true )[ 'Core' ] );
            $pot++;
        }
    }

    // Return them
    return $constants;
} // End ddtt_get_error_reporting_constants()


/**
 * Get the max log filesize
 *
 * @return int|float
 */
function ddtt_get_max_log_filesize() {
    $megabytes = get_option( DDTT_GO_PF.'max_log_size', 2 );
    $bytes = $megabytes * 1024 * 1024;
    return apply_filters( 'ddtt_debug_log_max_filesize', $bytes );
} // End ddtt_get_max_log_filesize()


/**
 * Return a log file from this server line by line, numbered, with colors
 * Home path is public_html/
 * Include filename in path
 * USAGE: ddtt_view_file_contents( 'wp-config.php' );
 *
 * @param string $path
 * @param boolean $log
 * @return string
 */
function ddtt_view_file_contents( $path, $log = false ) {
    // Define the file
    $file = FALSE;
    if ( is_readable( ABSPATH.'/'.$path ) ) {
        $file = ABSPATH.$path;
    } elseif ( is_readable( dirname( ABSPATH ).'/'.$path ) ) {
        $file = dirname( ABSPATH ).'/'.$path;
    } elseif ( is_readable( $path ) ) {
        $file = $path;
    }

    // Abspath
    if ( !get_option( DDTT_GO_PF.'view_sensitive_info' ) || get_option( DDTT_GO_PF.'view_sensitive_info' ) != 1 ) {

        // Add redacted div to ABSPATH
        $public_html = strstr( $file, '/public_html', true );
        $redacted_path = str_replace( $public_html, '<span class="redact">'.$public_html.'</span>', $file );
    } else {
        $redacted_path = $file;
    }

    // Check if the file exists
    if ( $file ) {

        // Get the file size
        $file_size = filesize( $file );

        // Max file size
        $max_filesize = ddtt_get_max_log_filesize();
        $offset = $file_size <= $max_filesize ? 0 : $max_filesize;

        // Get the file
        $string = file_get_contents( $file, false, null, $offset );

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
            foreach ( $lines as $key => $line ) {

                // If not, check for comment marks; add a class
                if ( substr( $line, 0, 3 ) === '// ' || 
                    substr( $line, 0, 3 ) === '/**' || 
                    substr( $line, 0, 2 ) === ' *' || 
                    substr( $line, 0, 1 ) === '*' || 
                    substr( $line, 0, 2 ) === '*/' || 
                    substr( $line, 0, 2 ) === '/*' || 
                    substr( $line, 0, 1 ) === '#' ) {
                    $comment_out = ' comment-out';
                } else {
                    $comment_out = '';
                }

                // Escape the html early
                $line = esc_html( $line );

                // Check if we are redacting
                if ( !get_option( DDTT_GO_PF.'view_sensitive_info' ) || get_option( DDTT_GO_PF.'view_sensitive_info' ) != 1 ) {

                    // Redact sensitive info
                    $substrings = [
                        'Require ip ',
                    ];

                    // Iter the globals
                    foreach ( $substrings as $substring ) {

                        // Attempt to find it
                        if ( strpos( $line, $substring ) !== false ) {
                            
                            // Get the remaining piece of text
                            $redact = trim( str_replace( $substring, '', $line ) );

                            // Add redact div
                            $line = str_replace( $redact, '<div class="redact">'.$redact.'</div>', $line );
                        }
                    }
                }

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
    
    // Path
    $results .= '<pre class="code">Installation path: '.$redacted_path.'<br><br>'.$code.'</pre>';

    // Return
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
function ddtt_view_file_contents_easy_reader( $path, $log = false, $highlight_args = [], $allow_repeats = true ) {
    // Define the file
    $file = FALSE;
    if ( is_readable( ABSPATH.'/'.$path ) ) {
        $file = ABSPATH.$path;
    } elseif ( is_readable( dirname( ABSPATH ).'/'.$path ) ) {
        $file = dirname( ABSPATH ).'/'.$path;
    }

    // Start results
    $results = '';

    // Store the actual lines we are displaying
    $actual_lines = [];

    // Check if the file exists
    if ( $file ) {

        // Get the file size
        $file_size = filesize( $file );

        // Max file size
        $max_filesize = ddtt_get_max_log_filesize();
        $offset = $file_size <= $max_filesize ? 0 : $max_filesize;

        // Get the file
        $string = file_get_contents( $file, false, null, $offset );

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
            if ( get_option( DDTT_GO_PF.'dev_timezone' ) && get_option( DDTT_GO_PF.'dev_timezone' ) != '' ) {
                $dev_timezone = sanitize_text_field( get_option( DDTT_GO_PF.'dev_timezone' ) );
            } else {
                $dev_timezone = wp_timezone_string();
            }

            // For each file line...
            foreach ( $lines as $line ) {

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

                        // Array bool
                        $is_array = false;
                        $start_collecting_array = true;

                        // Starting qty
                        $qty = 1;

                        // Check for a date section
                        $date_section = false;
                        if ( preg_match( '/\[(.*?)\]/s', $line, $get_date_section ) ) {
                            if ( strpos( $get_date_section[1], 'UTC' ) !== false && ddtt_is_date( $get_date_section[1] ) ) {
                                $date_section = $get_date_section;
                            }
                        }
        
                        // Check for a date section
                        // dpr( $get_date_section[1] );
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
                                if ( $repeat && ( !$is_array || ( $is_array && !$start_collecting_array ) ) ) {

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
                                if ( preg_match_all( '/\d+/', $rest, $on_line_numbers ) ) {
                                    $on_line_num = end( $on_line_numbers[0] );
                                }
                            }

                            // Check if array
                            if ( str_starts_with( $rest, ' Array' ) ) {
                                $err = 'Array';
                                $warning = 'Array';
                                $new_actual_line = true;
                                $start_collecting_array = true;
                            }

                        // Or if there is no date
                        } else {

                            // Check if it is a stack trace
                            if ( str_starts_with( $line, 'Stack trace' ) || str_starts_with( $line, '#' ) || str_starts_with( ltrim( $line ), 'thrown' ) ) {
                                $is_stack = true;
                                $new_actual_line = false;

                            // Check if we are still looking for the rest of the array
                            } elseif ( $start_collecting_array && 
                                      ( iconv_strlen( $line, 'UTF-8' ) == 1 && str_starts_with( $line, '(' ) ) || 
                                      ( iconv_strlen( $line, 'UTF-8' ) > 1 && !str_starts_with( $line, ')' ) ) ) {
                                $is_array = true;
                                $new_actual_line = false;
                            
                            // Stop looking for the rest of the array
                            } elseif ( $start_collecting_array && iconv_strlen( $line, 'UTF-8' ) == 1 && str_starts_with( $line, ')' ) ) {
                                $is_array = true;
                                $new_actual_line = false;
                                $start_collecting_array = false;

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

                        // Count actual lines
                        $actual_line_count = count( $actual_lines );
                        $actual_line_count = $actual_line_count > 0 ? $actual_line_count - 1 : 0;
                            
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
                                } elseif ( ddtt_get( 'c', '==', 'p' ) ) {
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
                            if ( isset( $actual_lines[ $actual_line_count ][ 'stack' ] ) ) {
                                $stack_lines = $actual_lines[ $actual_line_count ][ 'stack' ];
                            } else {
                                $stack_lines = [];
                            }

                            // If the line has not been added
                            if ( !in_array( $line, $stack_lines ) ) {

                                // Then add the line
                                $actual_lines[ $actual_line_count ][ 'stack' ][] = $line;
                            }

                        // Or add the array
                        } elseif ( $is_array ) {

                            // Check for a search filter
                            if ( ddtt_get( 's' ) ) {
                                continue;
                            }

                            // Check if the line # matches
                            if ( isset( $actual_lines[ $actual_line_count ][ 'err' ] ) && $actual_lines[ $actual_line_count ][ 'err' ] === 'Array' ) {

                                // Then add the line
                                $actual_lines[ $actual_line_count ][ 'array' ][] = $line;
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

                    // Is there an array?
                    if ( isset( $actual_line[ 'array' ] ) ) {
                        $array = $actual_line[ 'array' ];

                        // // Iter the array
                        // $array_array = [];
                        // foreach ( $array as $a ) {
                            
                        //     // Remove the brackets
                        //     if ( $a == '(' || $a == ')' ) {
                        //         continue;
                        //     }

                        //     // Explode the line to remove the array items
                        //     $split = explode( ' => ', $a );
                        //     $a = isset( $split[1] ) ? $split[1] : '';

                        //     // Shorten the paths
                        //     $a = str_replace( ABSPATH, '/', $a );
                            
                            
                        //     // Add a class to the first line
                        //     if ( str_starts_with( $a, 'Array' ) ) {
                        //         $array_array[] = '<span class="array">'.$a.'</span>';

                        //     // Otherwise do nothing
                        //     } else {
                        //         $array_array[] = $a;
                        //     }
                        // }
                        $display_array = '<pre>'.print_r( $array, true ).'</pre>';
                        // $display_array = '<pre>'.print_r( $array_array, true ).'</pre>';
                    } else {
                        $display_array = '';
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
                        $plugin_filename = substr( $plugin_path_and_filename, strpos( $plugin_path_and_filename, '/' ) + 1 );
                    
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

                            // Make sure editors are not disabled
                            if ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT ) {
                                
                                // Update short file path link
                                $short_path = esc_attr( $short_path );

                            } else {
                                
                                // Update short file path link
                                $short_path = '<a href="/'.esc_attr( $admin_url ).'/plugin-editor.php?file='.esc_attr( urlencode( $plugin_filename ) ).'&plugin='.esc_attr( $plugin_slug ).'%2F'.esc_attr( $plugin_slug ).'.php" target="_blank">'.esc_attr( $short_path ).'</a>';
                            }
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

                        // Make sure editors are not disabled
                        if ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT ) {
                            
                            // Update short file path link
                            $short_path = esc_attr( $short_path );

                        } else {
                            
                            // Update short file path link
                            $short_path = '<a href="/'.esc_attr( $admin_url ).'/theme-editor.php?file='.esc_attr( urlencode( $theme_filename ) ).'&theme='.esc_attr( $theme_slug ).'" target="_blank">'.esc_attr( $short_path ).'</a>';
                        }
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
                    if ( $actual_line[ 'type' ] != 'Unknown' && $actual_line[ 'type' ] != 'Array' ) {
                        $file_and_line = 'File: '.$short_path.'<br>Line: '.$actual_line[ 'lnum' ];
                    } else {
                        $file_and_line = '';
                    }

                    // Create the row
                    $code .= '<tr class="debug-li'.$error_class.$actual_line[ 'class' ].'">
                        <td class="line"><span class="unselectable">'.$actual_line[ 'line' ].'</span></td>
                        <td class="date">'.$actual_line[ 'date' ].'</td>
                        <td class="type">'.$actual_line[ 'type' ].'</td>
                        <td class="err"><span class="the-error">'.$actual_line[ 'err' ].'</span>'.$plugin_or_theme.$file_and_line.$display_stack.$display_array.'</td>
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

        // Get the dev's timezone
        if ( get_option( DDTT_GO_PF.'dev_timezone' ) && get_option( DDTT_GO_PF.'dev_timezone' ) != '' ) {
            $dev_timezone = sanitize_text_field( get_option( DDTT_GO_PF.'dev_timezone' ) );
        } else {
            $dev_timezone = wp_timezone_string();
        }

        // Get the converted time
        $utc_time = date( 'Y-m-d H:i:s', filemtime( $file ) );
        $dt = new DateTime( $utc_time, new DateTimeZone( 'UTC' ) );
        $dt->setTimezone( new DateTimeZone( $dev_timezone ) );
        $last_modified = $dt->format( 'F j, Y g:i A T' );
            
        // Display the error count
        $results .= 'Lines: <strong>'.$line_count.'</strong> <span class="sep">|</span> Unique Errors: <strong>'.count( $actual_lines ).'</strong> <span class="sep">|</span> Filesize: <strong>'.ddtt_format_bytes( filesize( $file ) ).'</strong> <span class="sep">|</span> Last Modified: <strong>'.$last_modified.'</strong><br><br>';
    }

    // Check if we are redacting
    if ( !get_option( DDTT_GO_PF.'view_sensitive_info' ) || get_option( DDTT_GO_PF.'view_sensitive_info' ) != 1 ) {

        // Add redacted div to ABSPATH
        $public_html = strstr( ABSPATH, '/public_html', true );
        $abspath = str_replace( $public_html, '<span class="redact">'.$public_html.'</span>', ABSPATH );
    } else {
        $abspath = ABSPATH;
    }

    // Return the code with the defined path at top
    $results .= 'Installation path: '.$abspath.$path.'<br><br>'.$code;

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
    // Change the colors
    $comments = ddtt_get_syntax_color( 'color_comments', '#5E9955' );
    $fx_vars = ddtt_get_syntax_color( 'color_fx_vars', '#DCDCAA' );
    $text_quotes = ddtt_get_syntax_color( 'color_text_quotes', '#ACCCCC' );
    $syntax = ddtt_get_syntax_color( 'color_syntax', '#569CD6' );
    ini_set( 'highlight.comment', $comments );
    ini_set( 'highlight.default', $fx_vars );
    ini_set( 'highlight.html', $text_quotes );
    ini_set( 'highlight.keyword', $syntax );
    ini_set( 'highlight.string', $text_quotes );

    // Stock highlight
    $string2 = highlight_file( $filename, true );

    // Check if we are redacting
    if ( !get_option( DDTT_GO_PF.'view_sensitive_info' ) || get_option( DDTT_GO_PF.'view_sensitive_info' ) != 1 ) {

        // Redact sensitive info
        $globals = [
            'DB_USER',
            'DB_NAME',
            'DB_PASSWORD',
            'AUTH_KEY',
            'SECURE_AUTH_KEY',
            'LOGGED_IN_KEY',
            'NONCE_KEY',
            'AUTH_SALT',
            'SECURE_AUTH_SALT',
            'LOGGED_IN_SALT',
            'NONCE_SALT',
            'WP_CACHE_KEY_SALT'
        ];

        // Iter the globals
        foreach ( $globals as $global ) {

            // The pattern we're searching for
            $pattern = '/define\<\/span\>\<span style=\"color: '.$syntax.'\"\>\((&nbsp;)*\<\/span\>(\'|\")'.$global.'(\'|\")\<span style=\"color: '.$syntax.'\"\>,(&nbsp;)*\<\/span\>(\'|\")(.+?)(\'|\")/i';

            // Attempt to find it
            if ( preg_match( $pattern, $string2, $define_pw ) ) {
                
                // Strip the tags
                $stripped = strip_tags( $define_pw[0] );

                // Remove the beginning
                $pw = substr( $stripped, strpos( $stripped, ',') + 1 );

                // Remove any spaces from beginning
                $despace = ltrim( $pw, ' &nbsp;', );

                // Remove quotes
                $unquoted = str_replace( [ '"', "'" ], '', $despace );

                // Add redact div
                $string2 = str_replace( $unquoted, '<div class="redact">'.$unquoted.'</div>', $string2 );
            }
        }
    }

    // Return it
    if ( $return ) return $string2;
    else echo wp_kses_post( $string2 );
} // End ddtt_highlight_file2()


/**
 * Highlight syntax in a string
 *
 * @param string $text
 * @param boolean $return
 * @return string
 */
function ddtt_highlight_string( $text ) {
    // Change the colors
    ini_set( 'highlight.comment', ddtt_get_syntax_color( 'color_text_quotes', '#ACCCCC' ) );
    ini_set( 'highlight.default', ddtt_get_syntax_color( 'color_fx_vars', '#DCDCAA' ) );
    ini_set( 'highlight.html', ddtt_get_syntax_color( 'color_text_quotes', '#ACCCCC' ) );
    ini_set( 'highlight.keyword', ddtt_get_syntax_color( 'color_comments', '#5E9955' )."; font-weight: bold" );
    ini_set( 'highlight.string', ddtt_get_syntax_color( 'color_syntax', '#569CD6' ) );

    // Work some magic
    $text = trim( $text );
    $text = highlight_string( "<?php " . $text, true );  // highlight_string() requires opening PHP tag or otherwise it will not colorize the text
    $text = trim( $text );
    $text = preg_replace( "|^\\<code\\>\\<span style\\=\"color\\: #[a-fA-F0-9]{0,6}\"\\>|", "", $text, 1);
    $text = preg_replace( "|\\</code\\>\$|", "", $text, 1 );
    $text = trim( $text );
    $text = preg_replace( "|\\</span\\>\$|", "", $text, 1 );
    $text = trim( $text );
    $text = preg_replace( "|^(\\<span style\\=\"color\\: #[a-fA-F0-9]{0,6}\"\\>)(&lt;\\?php&nbsp;)(.*?)(\\</span\\>)|", "\$1\$3\$4", $text);  // remove custom added "<?php "

    // Return it
    return $text;
} // End ddtt_highlight_string()


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
 * USAGE: ddtt_admin_notice( 'warning', 'Your message!' ); // <-- No need to echo
 *
 * @param string $type // Accepts 'success', 'error', or 'warning'
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
 * Add a WP Plugin Info Card
 *
 * @param string $slug
 * @return string
 */
function ddtt_plugin_card( $slug ) {
    // Set the args
    $args = [ 
        'slug' => $slug, 
        'fields' => [
            'last_updated' => true,
            'tested' => true,
            'active_installs' => true
        ]
    ];
    
    // Fetch the plugin info from the wp repository
    $response = wp_remote_post(
        'http://api.wordpress.org/plugins/info/1.0/',
        [
            'body' => [
                'action' => 'plugin_information',
                'request' => serialize( (object)$args )
            ]
        ]
    );

    // If there is no error, continue
    if ( !is_wp_error( $response ) ) {

        // Unserialize
        $returned_object = unserialize( wp_remote_retrieve_body( $response ) );   
        if ( $returned_object ) {
            
            // Last Updated
            $last_updated = $returned_object->last_updated;
            $last_updated = ddtt_time_elapsed_string( $last_updated );

            // Compatibility
            $compatibility = $returned_object->tested;

            // Add incompatibility class
            global $wp_version;
            if ( $compatibility == $wp_version ) {
                $is_compatible = '<span class="compatibility-compatible"><strong>Compatible</strong> with your version of WordPress</span>';
            } else {
                $is_compatible = '<span class="compatibility-untested">Untested with your version of WordPress</span>';
            }

            // Get all the installed plugins
            $plugins = get_plugins();

            // Check if this plugin is installed
            $is_installed = false;
            foreach ( $plugins as $key => $plugin ) {
                if ( $plugin[ 'TextDomain' ] == $slug ) {
                    $is_installed = $key;
                }
            }

            // Check if it is also active
            $is_active = false;
            if ( $is_installed && is_plugin_active( $is_installed ) ) {
                $is_active = true;
            }

            // Check if the plugin is already active
            if ( $is_active ) {
                $install_link = 'role="link" aria-disabled="true"';
                $php_notice = '';
                $install_text = 'Active';

            // Check if the plugin is installed but not active
            } elseif ( $is_installed ) {
                $install_link = 'href="'.admin_url( 'plugins.php' ).'"';
                $php_notice = '';
                $install_text = 'Go to Activate';

            // Check for php requirement
            } elseif ( phpversion() < $returned_object->requires_php ) {
                $install_link = 'role="link" aria-disabled="true"';
                $php_notice = '<div class="php-incompatible"><em><strong>Requires PHP Version '.$returned_object->requires_php.'</strong> — You are currently on Version '.phpversion().'</em></div>';
                $install_text = 'Incompatible';

            // If we're good to go, add the link
            } else {

                // Get the admin url for the plugin install page
                if ( is_multisite() ) {
                    $admin_url = network_admin_url( 'plugin-install.php' );
                } else {
                    $admin_url = admin_url( 'plugin-install.php' );
                }

                // Vars
                $install_link = 'href="'.$admin_url.'?s='.esc_attr( $returned_object->name ).'&tab=search&type=term"';
                $php_notice = '';
                $install_text = 'Get Now';
            }
            
            // Short Description
            $pos = strpos( $returned_object->sections[ 'description' ], '.');
            $desc = substr( $returned_object->sections[ 'description' ], 0, $pos + 1 );

            // Rating
            $rating = ddtt_get_five_point_rating( 
                $returned_object->ratings[1], 
                $returned_object->ratings[2], 
                $returned_object->ratings[3], 
                $returned_object->ratings[4], 
                $returned_object->ratings[5] 
            );

            // Link guts
            $link_guts = 'href="https://wordpress.org/plugins/'.esc_attr( $slug ).'/" target="_blank" aria-label="More information about '.$returned_object->name.' '.$returned_object->version.'" data-title="'.$returned_object->name.' '.$returned_object->version.'"';
            ?>
            <style>
            .plugin-card {
                float: none !important;
                margin-left: 0 !important;
            }
            .plugin-card .ws_stars {
                display: inline-block;
            }
            .php-incompatible {
                padding: 12px 20px;
                background-color: #D1231B;
                color: #FFFFFF;
                border-top: 1px solid #dcdcde;
                overflow: hidden;
            }
            #wpbody-content .plugin-card .plugin-action-buttons a.install-now[aria-disabled="true"] {
                color: #CBB8AD !important;
                border-color: #CBB8AD !important;
            }
            .plugin-action-buttons {
                list-style: none !important;   
            }
            </style>
            <div class="plugin-card plugin-card-<?php echo esc_attr( $slug ); ?>">
                <div class="plugin-card-top">
                    <div class="name column-name">
                        <h3>
                            <a <?php echo wp_kses_post( $link_guts ); ?>>
                                <?php echo esc_html( $returned_object->name ); ?> 
                                <img src="<?php echo esc_url( DDTT_PLUGIN_IMG_PATH ).esc_attr( $slug  ); ?>.png" class="plugin-icon" alt="<?php echo esc_html( $returned_object->name ); ?> Thumbnail">
                            </a>
                        </h3>
                    </div>
                    <div class="action-links">
                        <ul class="plugin-action-buttons">
                            <li><a class="install-now button" data-slug="<?php echo esc_attr( $slug ); ?>" <?php echo wp_kses_post( $install_link ); ?> aria-label="<?php echo esc_attr( $install_text );?>" data-name="<?php echo esc_html( $returned_object->name ); ?> <?php echo esc_html( $returned_object->version ); ?>"><?php echo esc_attr( $install_text );?></a></li>
                            <li><a <?php echo wp_kses_post( $link_guts ); ?>>More Details</a></li>
                        </ul>
                    </div>
                    <div class="desc column-description">
                        <p><?php echo wp_kses_post( $desc ); ?></p>
                        <p class="authors"> <cite>By <?php echo wp_kses_post( $returned_object->author ); ?></cite></p>
                    </div>
                </div>
                <div class="plugin-card-bottom">
                    <div class="vers column-rating">
                        <div class="star-rating"><span class="screen-reader-text"><?php echo abs( $rating ); ?> rating based on <?php echo absint( $returned_object->num_ratings ); ?> ratings</span>
                            <?php echo wp_kses_post( ddtt_convert_to_stars( abs( $rating ) ) ); ?>
                        </div>					
                        <span class="num-ratings" aria-hidden="true">(<?php echo absint( $returned_object->num_ratings ); ?>)</span>
                    </div>
                    <div class="column-updated">
                        <strong>Last Updated:</strong> <?php echo esc_html( $last_updated ); ?>
                    </div>
                    <div class="column-downloaded" data-downloads="<?php echo esc_html( number_format( $returned_object->downloaded ) ); ?>">
                        <?php echo esc_html( number_format( $returned_object->active_installs ) ); ?>+ Active Installs
                    </div>
                    <div class="column-compatibility">
                        <?php echo wp_kses_post( $is_compatible ); ?>				
                    </div>
                </div>
                <?php echo wp_kses_post( $php_notice ); ?>
            </div>
            <?php
        }
    }
} // End ddtt_plugin_card()


/**
 * Convert 5-point rating to plugin card stars
 *
 * @param int|float $r
 * @return string
 */
function ddtt_convert_to_stars( $r ) {
    $f = '<div class="star star-full" aria-hidden="true"></div>';
    $h = '<div class="star star-half" aria-hidden="true"></div>';
    $e = '<div class="star star-empty" aria-hidden="true"></div>';
    
    $stars = $e.$e.$e.$e.$e;
    if ( $r > 4.74 ) {
        $stars = $f.$f.$f.$f.$f;
    } elseif ( $r > 4.24 && $r < 4.75 ) {
        $stars = $f.$f.$f.$f.$h;
    } elseif ( $r > 3.74 && $r < 4.25 ) {
        $stars = $f.$f.$f.$f.$e;
    } elseif ( $r > 3.24 && $r < 3.75 ) {
        $stars = $f.$f.$f.$h.$e;
    } elseif ( $r > 2.74 && $r < 3.25 ) {
        $stars = $f.$f.$f.$e.$e;
    } elseif ( $r > 2.24 && $r < 2.75 ) {
        $stars = $f.$f.$h.$e.$e;
    } elseif ( $r > 1.74 && $r < 2.25 ) {
        $stars = $f.$f.$e.$e.$e;
    } elseif ( $r > 1.24 && $r < 1.75 ) {
        $stars = $f.$h.$e.$e.$e;
    } elseif ( $r > 0.74 && $r < 1.25 ) {
        $stars = $f.$e.$e.$e.$e;
    } elseif ( $r > 0.24 && $r < 0.75 ) {
        $stars = $h.$e.$e.$e.$e;
    } else {
        $stars = $stars;
    }

    return '<div class="ws_stars">'.$stars.'</div>';
} // End ddtt_convert_to_stars()


/**
 * Get 5-point rating from 5 values
 *
 * @param int|float $r1
 * @param int|float $r2
 * @param int|float $r3
 * @param int|float $r4
 * @param int|float $r5
 * @return float
 */
function ddtt_get_five_point_rating ( $r1, $r2, $r3, $r4, $r5 ) {
    // Calculate them on a 5-point rating system
    $r5b = round( $r5 * 5, 0 );
    $r4b = round( $r4 * 4, 0 );
    $r3b = round( $r3 * 3, 0 );
    $r2b = round( $r2 * 2, 0 );
    $r1b = $r1;
    
    $total = round( $r1 + $r2 + $r3 + $r4 + $r5, 0 );
    if ( $total == 0 ) {
        $r = 0;
    } else {
        $r = round( ( $r1b + $r2b + $r3b + $r4b + $r5b ) / $total, 2 );
    }

    return $r;
} // End ddtt_get_five_point_rating()


/**
 * Get the latest version of this plugin
 *
 * @return string
 */
function ddtt_get_latest_plugin_version() {
    // Set the args
    $args = [ 'slug' => DDTT_TEXTDOMAIN ];

    // Fetch the plugin info from the wp repository
    $response = wp_remote_post(
        'http://api.wordpress.org/plugins/info/1.0/',
        [
            'body' => [
                'action' => 'plugin_information',
                'request' => serialize( (object)$args )
            ]
        ]
    );

    // If there is no error, continue
    if ( !is_wp_error( $response ) ) {

        // Unserialize
        $returned_object = unserialize( wp_remote_retrieve_body( $response ) );   
        if ( $returned_object ) {
            return $returned_object->version;
        }
    }
} // End ddtt_get_latest_plugin_version()


/**
 * Get the latest PHP version
 *
 * @return array
 */
function ddtt_get_latest_php_version( $major_only = false ) {
    // Get the latest releases from php.net
    $response = wp_remote_get( 'https://www.php.net/releases/?json' );

    // Make sure we got a response
    if ( is_array( $response ) && !is_wp_error( $response ) ) {

        // Grab the json content
        $json = $response[ 'body' ];

        // Decode it
        $php_releases = json_decode( $json );

        // Store the latest major release (ie 8.x)
        $latest_major = 0;

        // Iter the releases
        foreach ( $php_releases as $major_version => $release ) {

            // Only retrieve if the version is later than the stored version
            if ( $major_version > $latest_major ) {
                $latest_major = $major_version;
            } else {
                continue;
            }
        }

        // Get what we need
        if ( $major_only ) {
            return $latest_major;
        } else {
            return $php_releases->$latest_major->version;
        }
    }
    return 0;
} // End ddtt_get_latest_php_version()


/**
 * Check if a string is json
 *
 * @param string $string
 * @return boolean
 */
function ddtt_is_serialized_array( $string ) {
    if ( preg_match( '/a:\d+:\{/', $string ) ) {
        return true;
    }
    return false;
} // End ddtt_is_json()


/**
 * Get the URL of an admin menu item
 *
 * @param   string $menu_item_file
 * @param   boolean $submenu_as_parent
 * @return  string|null
 */
function ddtt_get_admin_menu_item_url( $menu_item_file, $menu = null, $submenu = null, $submenu_as_parent = true ) {
	if ( is_null( $menu ) && is_null( $submenu ) ) {
        global $menu, $submenu;
    }

	$admin_is_parent = false;
	$item = '';
	$submenu_item = '';
	$url = '';

	// Check if top-level menu item
	foreach( $menu as $key => $menu_item ) {
		if ( array_keys( $menu_item, $menu_item_file, true ) ) {
			$item = $menu[ $key ];
		}

		if ( $submenu_as_parent && ! empty( $submenu_item ) ) {
			$menu_hook = get_plugin_page_hook( $submenu_item[2], $item[2] );
			$menu_file = $submenu_item[2];

			if ( false !== ( $pos = strpos( $menu_file, '?' ) ) )
				$menu_file = substr( $menu_file, 0, $pos );
			if ( ! empty( $menu_hook ) || ( ( 'index.php' != $submenu_item[2] ) && file_exists( WP_PLUGIN_DIR.'/'.$menu_file ) && ! file_exists( ABSPATH.'/'.DDTT_ADMIN_URL.'/'.$menu_file ) ) ) {
				$admin_is_parent = true;
				$url = 'admin.php?page=' . $submenu_item[2];
			} else {
				$url = $submenu_item[2];
			}
		}

		elseif ( ! empty( $item[2] ) && current_user_can( $item[1] ) ) {
			$menu_hook = get_plugin_page_hook( $item[2], 'admin.php' );
			$menu_file = $item[2];

			if ( false !== ( $pos = strpos( $menu_file, '?' ) ) )
				$menu_file = substr( $menu_file, 0, $pos );
			if ( ! empty( $menu_hook ) || ( ( 'index.php' != $item[2] ) && file_exists( WP_PLUGIN_DIR.'/'.$menu_file ) && ! file_exists( ABSPATH.'/'.DDTT_ADMIN_URL.'/'.$menu_file ) ) ) {
				$admin_is_parent = true;
				$url = 'admin.php?page=' . $item[2];
			} else {
				$url = $item[2];
			}
		}
	}

	// Check if sub-level menu item
	if ( !$item ) {
		$sub_item = '';
		foreach( $submenu as $top_file => $submenu_items ) {

			// Reindex $submenu_items
			$submenu_items = array_values( $submenu_items );

			foreach( $submenu_items as $key => $submenu_item ) {
				if ( array_keys( $submenu_item, $menu_item_file ) ) {
					$sub_item = $submenu_items[ $key ];
					break;
				}
			}					

			if ( ! empty( $sub_item ) )
				break;
		}

		// Get top-level parent item
		foreach( $menu as $key => $menu_item ) {
			if ( array_keys( $menu_item, $top_file, true ) ) {
				$item = $menu[ $key ];
				break;
			}
		}

		// If the $menu_item_file parameter doesn't match any menu item, return false
		if ( ! $sub_item )
			return false;

		// Get URL
		$menu_file = $item[2];

		if ( false !== ( $pos = strpos( $menu_file, '?' ) ) )
			$menu_file = substr( $menu_file, 0, $pos );

		// Handle current for post_type=post|page|foo pages, which won't match $self.
		$menu_hook = get_plugin_page_hook( $sub_item[2], $item[2] );

		$sub_file = $sub_item[2];
		if ( false !== ( $pos = strpos( $sub_file, '?' ) ) )
			$sub_file = substr($sub_file, 0, $pos);

		if ( ! empty( $menu_hook ) || ( ( 'index.php' != $sub_item[2] ) && file_exists( WP_PLUGIN_DIR."/$sub_file" ) && ! file_exists( ABSPATH.'/'.DDTT_ADMIN_URL.'/'.$sub_file ) ) ) {
			// If admin.php is the current page or if the parent exists as a file in the plugins or admin dir
			if ( ( ! $admin_is_parent && file_exists( WP_PLUGIN_DIR."/$menu_file" ) && ! is_dir( WP_PLUGIN_DIR."/{$item[2]}" ) ) || file_exists( $menu_file ) )
				$url = add_query_arg( array( 'page' => $sub_item[2] ), $item[2] );
			else
				$url = add_query_arg( array( 'page' => $sub_item[2] ), 'admin.php' );
		} else {
			$url = $sub_item[2];
		}
	}

    // Return the url
	return esc_url( $url );
} // End ddtt_get_admin_menu_item_url()


/**
 * Strip html and content from string
 *
 * @param string $text
 * @param string $tags
 * @param boolean $invert
 * @return string
 */
function ddtt_strip_tags_content( $text, $tags = '', $invert = false ) {
    // Search
    preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim( $tags ), $matches );
    $tags = array_unique( $matches[1] );

    if ( is_array( $tags ) && count( $tags ) > 0 ) {

        if ( $invert == false ) {
            return preg_replace( '@<(?!(?:'. implode( '|', $tags ) .')\b)(\w+)\b.*?>.*?</\1>@si', '', $text );
        } else {
            return preg_replace( '@<('. implode( '|', $tags ) .')\b.*?>.*?</\1>@si', '', $text );
        }

    } elseif ( $invert == false ) {
        return preg_replace( '@<(\w+)\b.*?>.*?</\1>@si', '', $text );
    }

    return $text;
} // End ddtt_strip_tags_content()


/**
 * Delete auto-drafts
 *
 * @param boolean $delete_all
 * @return void
 */
function ddtt_delete_autodrafts( $delete_all = false ) {
    // Just older than 7 days
    if ( !$delete_all ) {
        wp_delete_auto_drafts();
        
    // Delete all
    } else {
        global $wpdb;
        $old_posts = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_status = 'auto-draft'" );
        foreach ( (array) $old_posts as $delete ) {
            wp_delete_post( $delete, true );
        }
    }

    // Fail
    return false;
} // End ddtt_delete_autodrafts()


/**
 * Get all php filepaths in a directory
 *
 * @param string $dir
 * @return array
 */
function ddtt_get_all_php_filepaths( $dir ) {
    $paths = [];
    $fileinfos = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator( $dir )
    );
    foreach( $fileinfos as $pathname => $fileinfo ) {
        if ( !$fileinfo->isFile() ) continue;
        if ( !str_ends_with( $pathname, '.php' ) ) continue;
        $paths[] = $pathname;
    }
    return $paths;
} // End ddtt_get_all_php_filepaths()


/**
 * Scan a plugin for hooks
 *
 * @param string $plugin_dir
 * @return array
 */
function ddtt_scan_plugin_for_hooks( $plugin_dir ) {
    // Store the hooks here
    $hooks = [];
  
    // Get the files in an array (your own function that works fine)
    $files = ddtt_get_all_php_filepaths( $plugin_dir );
    if ( !empty( $files ) ) {

        // Loop through all PHP files in the plugin directory
        foreach ( $files as $file ) {

            // Get the content of the file as a single string
            $content = file_get_contents( $file );

            // Patterns
            $patterns = [
                'normal' => '/\b(apply_filters|do_action)\s*\(\s*([\'"])([^\'"]+?)\2\s*(?:,|\))/i',
                'gforms' => '/\bgf_apply_filters\s*\(\s*array\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*(\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*\)\s*,\s*\$form\s*,\s*\$ajax\s*,\s*\$field_values\s*\)/i'
            ];

            // Iter each pattern
            foreach ( $patterns as $source => $pattern ) {

                // Search
                preg_match_all( $pattern, $content, $matches, PREG_SET_ORDER );

                // Iter the matches
                foreach ( $matches as $match ) {

                    // Gravity Forms
                    if ( $source == 'gforms' ) {
                        $hook_type = 'gf_apply_filters';
                        $hook_name = $match[1].'_{'.$match[2].'}';
                        // dpr( $match );

                    // Normal
                    } else {
                        $hook_type = $match[1];
                        $hook_name = $match[3];
                    }
                    
                    // Find line number of match
                    $lineNumber = substr_count( substr( $content, 0, strpos( $content, $match[0] ) ), "\n" ) + 1;

                    // Check if the hook is already in the list
                    $hookExists = false;
                    foreach ( $hooks as $existingHook ) {
                        if ( $existingHook['name'] === $hook_name && $existingHook['file'] === $file && $existingHook['line'] === $lineNumber ) {
                            $hookExists = true;
                            break;
                        }
                    }

                    // Collect the data
                    if ( !$hookExists ) {
                        $hooks[] = [
                            'type' => $hook_type,
                            'name' => $hook_name,
                            'file' => $file,
                            'line' => $lineNumber,
                        ];
                    }
                }
            }
        }
    }
  
    // Return the hooks
    return $hooks;
} // End ddtt_scan_plugin_for_hooks()


/**
 * Convert PHP_EOL type to string
 *
 * @param string $value
 * @return string
 */
function ddtt_convert_php_eol_to_string( $value = PHP_EOL ) {
    $eol = [
        '0a'   => '\n',
        '0d'   => '\r',
        '0d0a' => '\r\n'
    ];
    $hex = bin2hex( $value );
    return isset( $eol[ $hex ] ) ? $eol[ $hex ] : $value;
} // End ddtt_convert_php_eol_to_string()


/**
 * Get the php_eol type we are using for the file
 *
 * @param string $file
 * @return string
 */
function ddtt_get_eol( $tab ) {
    $eol_types = [
        '\n'   => "\n",
        '\r'   => "\r",
        '\r\n' => "\r\n"
    ];
    if ( $eol = get_option( DDTT_GO_PF.'eol_'.$tab ) ) {
        if ( isset( $eol_types[ $eol ] ) ) {
            return $eol_types[ $eol ];
        }
    }
    return PHP_EOL;
} // End ddtt_get_php_eol()


/**
 * Get the eol type(s) being used by a file
 *
 * @param string $file
 * @return array
 */
function ddtt_get_file_eol( $file_contents ) {
    $types = [
        "\n"   => '\n',
        "\r"   => '\r',
        "\r\n" => '\r\n'
    ];
    $found = [];
    foreach ( $types as $char => $type ) {
        $pattern = preg_quote( $char, '/' );
        if ( preg_match( '/'.$pattern.'/', $file_contents ) ) {
            $found[] = '<code class="hl">'.$type.'</code>';
        }
    }
    return $found;
} // End ddtt_get_php_eol()


/**
 * THE END
 */