<?php
/**
 * WPCONFIG class file.
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Main plugin class.
 */
class DDTT_WPCONFIG {

    /**
	 * Constructor
	 */
	public function __construct() {
        
        
	} // End __construct()


    /**
     * Our snippets
     *
     * @return array
     */
    public static function snippets() {
        // Add the snippets
        $snippets = apply_filters( 'ddtt_wpconfig_snippets', [
            'debug_mode' => [
                'label' => 'Enable WP_DEBUG Mode',
                'lines' => [
                    [
                        'prefix' => 'define',
                        'variable' => 'WP_DEBUG',
                        'value' => TRUE
                    ]
                ]
            ],
            'debug_log' => [
                'label' => 'Enable Debug Logging to the /'.DDTT_CONTENT_URL.'/debug.log File',
                'lines' => [
                    [
                        'prefix' => 'define',
                        'variable' => 'WP_DEBUG_LOG',
                        'value' => TRUE
                    ]
                ]
            ],
            'debug_display' => [
                'label' => 'Disable Display of Errors and Warnings',
                'lines' => [
                    [
                        'prefix' => 'define',
                        'variable' => 'WP_DEBUG_DISPLAY',
                        'value' => FALSE
                    ],
                    [
                        'prefix' => '@ini_set',
                        'variable' => 'display_errors',
                        'value' => 0
                    ],
                    // [
                    //     'prefix' => '@ini_set',
                    //     'variable' => 'error_reporting',
                    //     'value' => E_ALL
                    // ]
                ]
            ],
            'enable_dev_scripts' => [
                'label' => 'Enable Development Versions of Core CSS and JS Files Instead of Minified Versions',
                'lines' => [
                    [
                        'prefix' => 'define',
                        'variable' => 'SCRIPT_DEBUG',
                        'value' => TRUE
                    ]
                ]
            ],
            'db_query_log' => [
                'label' => 'Enable Database Query Logging (Use Temporarily - Slows Performance)',
                'lines' => [
                    [
                        'prefix' => 'define',
                        'variable' => 'SAVEQUERIES',
                        'value' => TRUE
                    ]
                ]
            ],
            'disable_cache' => [
                'label' => 'Disable WordPress Caching',
                'lines' => [
                    [
                        'prefix' => 'define',
                        'variable' => 'WP_CACHE',
                        'value' => FALSE
                    ]
                ]
            ],
            'fatal_error_emails' => [
                'label' => 'Disable Fatal Error Emails to Admin',
                'lines' => [
                    [
                        'prefix' => 'define',
                        'variable' => 'WP_DISABLE_FATAL_ERROR_HANDLER',
                        'value' => TRUE
                    ]
                ]
            ],
            'memory_limit' => [
                'label' => 'Increase Memory Limit',
                'lines' => [
                    [
                        'prefix' => '@ini_set',
                        'variable' => 'upload_max_size',
                        'value' => '256M'
                    ],
                    [
                        'prefix' => '@ini_set',
                        'variable' => 'post_max_size',
                        'value' => '256M'
                    ]
                ]
            ],
            'upload_size' => [
                'label' => 'Temporarily Increase Upload Size (Use Temporarily / Must also Increase Memory Limit!)',
                'lines' => [
                    [
                        'prefix' => 'set_time_limit',
                        'value' => 300
                    ],
                    [
                        'prefix' => 'define',
                        'variable' => 'WP_MEMORY_LIMIT',
                        'value' => '512M'
                    ],
                    [
                        'prefix' => 'define',
                        'variable' => 'WP_MAX_MEMORY_LIMIT',
                        'value' => '1024M'
                    ]
                ]
            ],
            'concat_scripts' => [
                'label' => 'Turn Off Concatenating Scripts (Use if there are jQuery/JS Issues)',
                'lines' => [
                    [
                        'prefix' => 'define',
                        'variable' => 'CONCATENATE_SCRIPTS',
                        'value' => FALSE
                    ]
                ]
            ],
            'unfiltered_uploads' => [
                'label' => 'Allow Uploads of All File Types',
                'lines' => [
                    [
                        'prefix' => 'define',
                        'variable' => 'ALLOW_UNFILTERED_UPLOADS',
                        'value' => TRUE
                    ]
                ]
            ],
            'force_ssl_login' => [
                'label' => 'Ensure Login Credentials are Encrypted when Transmitting to Server',
                'lines' => [
                    [
                        'prefix' => 'define',
                        'variable' => 'FORCE_SSL_LOGIN',
                        'value' => TRUE
                    ]
                ]
            ],
            'force_ssl_admin' => [
                'label' => 'Ensure Sensetive Admin-area Info is Encrypted when Transmitting to Server',
                'lines' => [
                    [
                        'prefix' => 'define',
                        'variable' => 'FORCE_SSL_ADMIN',
                        'value' => TRUE
                    ]
                ]
            ],
            'max_input_vars' => [
                'label' => 'Increase Max Input Vars to 7000',
                'lines' => [
                    [
                        'prefix' => '@ini_set',
                        'variable' => 'max_input_vars',
                        'value' => 7000
                    ]
                ]
            ],
            'fs_method' => [
                'label' => 'Force Direct Filesystem Method',
                'lines' => [
                    [
                        'prefix' => 'define',
                        'variable' => 'FS_METHOD',
                        'value' => 'direct'
                    ]
                ]
            ],
        ] );
        return $snippets;
    } // End snippets()


    /**
     * Table row for WPCONFIG checkboxes
     *
     * @param array $snippet
     * @return string
     */
    public function options_tr( $name, $label, $snippet_exists, $line_strings_1, $line_strings_0 ) {
        // Check the box if the snippet exists
        if ( $snippet_exists ) {
            $checkbox_value = true;
        } else {
            $checkbox_value = false;
        }

        // Stores the snippet lines here
        $lines = [];

        // Cycle through all of the lines
        foreach ( $line_strings_1 as $line_1 ) {

            // Put the snippet together
            $lines[] = '<span class="line-exists true">'.$line_1.'</span>';
        }
        foreach ( $line_strings_0 as $line_0 ) {

            // Put the snippet together
            $lines[] = '<span class="line-exists false">'.$line_0.'</span>';
        }

        // The Checkbox
        $input = '<input type="checkbox" name="l[]" value="'.$name.' " '.checked( 1, $checkbox_value, false ).'/>';

        // Build the row
        $row = '<tr valign="top">
            <th scope="row">'.$label.'</th>
            <td class="checkbox-cell">'.$input.'</td>
            <td><div class="snippet_container">'.implode( '<br>', $lines ).'</div></td>
        </tr>';
        
        // Return the row
        return $row;
    } // End options_tr()


    /**
     * Check if a snippet exists
     *
     * @param string $wpconfig
     * @param array $snippet
     * @return array
     */
    public function snippet_exists( $wpconfig, $snippet ) {
        // Count the number of lines in the snippet
        $count = count( $snippet[ 'lines' ] );

        // Count number of items that exist
        $lines_exist = 0;

        // Line strings
        $line_strings_1 = [];
        $line_strings_0 = [];

        // Line keys
        $lines_1 = [];
        $lines_0 = [];

        // Found
        $found = [];

        // Partial in-line
        $partial = false;

        // Cycle each line
        foreach ( $snippet[ 'lines' ] as $key => $line ) {
            // ddtt_print_r($line);

            // Create a line string
            $line_string = $this->snippet_line_to_string( $line );

            // Create the regex search pattern from the line
            $regex = $this->snippet_regex( $line );
            // ddtt_print_r($regex);

            // Check the file for the line
            if ( preg_match_all( $regex, $wpconfig, $matches ) ) {

                // Display an error if there are any duplicates found
                if ( count( $matches[0] ) > 1 ) {                
                    ddtt_admin_notice( 'error', 'Duplicate snippet found: '.$matches[0][0] );
                }

                // Count this as exists
                $lines_exist++;

                // Add line string to the true bucket
                foreach ( $matches[0] as $match ) {
                    $line_strings_1[] = $match;
                    $lines_1[] = $line;

                    // Add what we found in snippet form
                    if ( $converted = $this->string_to_snippet_line( $match ) ) {
                        $found[] = $converted;

                        // Check if the converted matches the line
                        if ( $line != $converted ) {
                            $partial = true;
                        }
                    }
                }
                
            }else {
                
                // Add line string to the false bucket
                $line_strings_0[] = $line_string;
                $lines_0[] = $line;
            }
        }

        // Check if all lines exist
        if ( !$partial ) {
            if ( $count == $lines_exist ) {
                $snippet_exists = true;
                $partial = false;
    
            // If no lines exist
            } elseif ( $lines_exist == 0 ) {
                $snippet_exists = false;
                $partial = false;
    
            // If only some of the lines exist
            } else {
                $snippet_exists = false;
                $partial = true;
            }
        }

        // Return the results
        return [
            'exists' => $snippet_exists,
            'partial' => $partial,
            'lines' => [
                'true' => $lines_1,
                'false' => $lines_0
            ],
            'strings' => [
                'true' => $line_strings_1,
                'false' => $line_strings_0
            ],
            'found' => $found
        ];
    } // End snippet_exists()


    /**
     * Create the regex for finding a snippet line with ANY value
     *
     * @param array $line
     * @return string
     */
    public function snippet_regex( $line ) {
        // Include variable?
        if ( isset( $line[ 'variable' ] ) ) {
            $incl_var = '\s*([\'"])'.$line[ 'variable' ].'\1\s*,';
        } else {
            $incl_var = '';
        }
        
        // Convert value
        $value = '.*?';
        // if ( !is_bool( $line[ 'value' ] ) && $line[ 'value' ] != '' ) {
        //     $value = '.*?';
        // } elseif ( ddtt_is_enabled( $line[ 'value' ] ) ) {
        //     $value = '(true|1)';
        // } elseif ( !$line[ 'value' ] ) {
        //     $value = '(false|0)';
        // } else {
        //     $value = $line[ 'value' ];
        // }

        // Adding quotes around value
        if ( is_numeric( $line[ 'value' ] ) || is_bool( $line[ 'value' ] ) ) {
            // ddtt_print_r($line_string);
            $value_quotes_str = '';
            $value_quotes_end = '';
        } else {
            $value_quotes_str = '([\'"])';
            $value_quotes_end = '\1';
        }

        // Put the regex together
        $regex = '/'.$line[ 'prefix' ].'\s*\('.$incl_var.'\s*'.$value_quotes_str.$value.$value_quotes_end.'\s*\)/i';
        // ddtt_print_r($regex);

        return $regex;
    } // End snippet_regex()


    /**
     * Convert a snippet string to a snippet line
     *
     * @param string $string
     * @return array
     */
    public function string_to_snippet_line( $string ) {
        // Get the variable and value
        if ( strstr( $string, '(', true ) && ( preg_match('/\((.*?)\)/', $string, $match ) == 1 ) ) {
            
            // Get the prefix
            $prefix = strstr( $string, '(', true );

            // Get the insides
            $insides = str_replace( [ '(', ')' ], '', $match[0] );

            // Split the insides
            if ( strpos( $insides, ',' ) !== false ) {
                $inside = explode( ',', trim( $insides ) );
                $variable = str_replace( [ '"',"'" ], '', trim( $inside[0] ) );
                $value = str_replace( [ '"',"'" ], '', trim( $inside[1] ) );
            } else {
                $variable = null;
                $value = str_replace( [ '"',"'" ], '', trim( $insides ) );
            }

            // Convert the value
            if ( ddtt_is_enabled( $value ) || strtolower( $value ) == 'true' ) {
                $value = TRUE;
            } elseif ( !$value || strtolower( $value ) == 'false' ) {
                $value = FALSE;
            } elseif ( is_numeric( $value ) ) {
                $value = absint( $value );
            } else {
                $value = $value;
            }

            // Build the array
            $array[ 'prefix' ] = $prefix;
            if ( !is_null( $variable ) ) {
                $array[ 'variable' ] = $variable;
            }
            $array[ 'value' ] = $value;

            // Return the array
            return $array;

        // Otherwise we couldn't parse it
        } else {
            return false;
        }
    } // End string_to_snippet_line()


    /**
     * Convert snippet line to string
     *
     * @param array $snippet_line
     * @return string
     */
    public function snippet_line_to_string( $snippet_line ) {
        // Check if there is a value
        if ( isset( $snippet_line[ 'variable' ] ) ) {
            $var_param = "'".$snippet_line[ 'variable' ]."', ";
        } else {
            $var_param = '';
        }

        // Set the value
        if ( $snippet_line[ 'value' ] === TRUE ) {
            $value = 'true';
        } else if ( $snippet_line[ 'value' ] == E_ALL ) {
            $value = 'E_ALL';
        } else if ( is_numeric( $snippet_line[ 'value' ] ) ) {
            $value = $snippet_line[ 'value' ];
        } else if ( $snippet_line[ 'value' ] != '' ) {
            $value = "'".$snippet_line[ 'value' ]."'";
        } else {
            $value = 'false';
        }

        // Put the snippet together
        return $snippet_line[ 'prefix' ]."( ".$var_param.$value." );";
    } // End snippet_line_to_string() 


    /**
     * Rewrite the WP-CONFIG file based on options
     *
     * @param string $file
     * @param array $snippets
     * @param array $enabled
     * @return void
     */
    public function rewrite( $filename, $snippets, $enabled, $testing = false, $confirm = false ) {
        // Get the file path
        if ( is_readable( ABSPATH . $filename ) ) {
            $file = ABSPATH . $filename;
        } elseif ( is_readable( dirname( ABSPATH ) . '/' . $filename ) ) {
            $file = dirname( ABSPATH ) . '/' . $filename;
        } else {
            $file = false;
        }
        
        // Check if the file exists
        if ( $file ) {

            // Get the file
            $wpconfig = file_get_contents( $file );

            // Separate each line into an array item
            $file_lines = explode( PHP_EOL, $wpconfig );

            // Make it html safe
            $safe_file_lines = [];
            foreach( $file_lines as $file_line ) {
                $safe_file_lines[] =  htmlentities( $file_line );
            }

            // Count edits
            $edits = 0;

            // Store what we need to add here
            $add = [];

            // Are we testing?                
            if ( $testing ) {
                ddtt_print_r( '$enabled: ' );
                ddtt_print_r( $enabled );
                ddtt_print_r( '<br><br><hr><br><br>' );
                ddtt_print_r( 'BEFORE:<br>' );
                ddtt_print_r( $safe_file_lines );
            }

            // Cycle each snippet
            foreach ( $snippets as $snippet_key => $snippet ) {
            
                // Check if the snippet exists in the file
                $e = $this->snippet_exists( $wpconfig, $snippet );
                $exists = $e[ 'exists' ];
                $partial = $e[ 'partial' ];
                // ddtt_print_r( $e );
                $snippet_lines_1 = $e[ 'lines' ][ 'true' ];

                // Enabled
                if ( strpos( json_encode( $enabled ), $snippet_key ) !== false ) {
                    $is_enabled = true;
                } else {
                    $is_enabled = false;
                }
                // $is_enabled = in_array( $snippet_key, $enabled, true ) ? true : false;
                // ddtt_print_r( $snippet_key.': '.$is_enabled );

                // Does NOT exist
                // NOT partial
                // NOT enabled
                // SKIP, because we're not going to add it anyway
                if ( !$exists && !$partial && !$is_enabled ) {
                    continue;

                // Exists, at least partially
                // REMOVE SNIPPETS REGARDLESS, because we're going to rewrite it all
                } elseif ( $exists || ( !$exists && $partial ) ) {

                    // For each snippet line
                    foreach( $snippet_lines_1 as $snippet_line_1 ) {
                        // ddtt_print_r( $snippet_line_1 );

                        // Regex
                        $regex = $this->snippet_regex( $snippet_line_1 );

                        // Search the file lines
                        foreach( $safe_file_lines as $file_key => $safe_file_line ) {
                            
                            // Check the file for the line
                            if ( preg_match_all( $regex, $safe_file_line, $matches ) ) {

                                // Remove all instances of the line from the list
                                foreach ( $matches[0] as $match ) {
                            
                                    // If there is a comment directly above it, remove that too
                                    if ( isset( $safe_file_lines[ $file_key - 1 ] ) && str_starts_with( $safe_file_lines[ $file_key - 1 ], '//' ) !== false ) {
                                        unset( $safe_file_lines[ $file_key - 1 ] );
                                    }

                                    // If it is not commented
                                    // If there is a space directly below it, remove that too
                                    if ( ( str_starts_with( $safe_file_lines[ $file_key ], '//' ) === false ) && strlen( $safe_file_lines[ $file_key + 1 ] ) >= 0 && empty( trim( $safe_file_lines[ $file_key + 1 ] ) ) ) {
                                        unset( $safe_file_lines[ $file_key + 1 ] );
                                    }

                                    // Lastly, remove the line
                                    unset( $safe_file_lines[ $file_key ] );
                                }
                            }
                        }
                    }

                    // If it at least partially exists, then add the snippet to the add bucket 
                    if ( ( $exists && $is_enabled ) || ( !$exists && $partial && $is_enabled) ) {
                        $add[] = $snippet;
                    }

                    // If it's not supposed to be there, just count this as an edit
                    if ( !$is_enabled || ( !$exists && $partial ) ) {
                        $edits++;
                    }

                // Does NOT exist
                // NOT partial
                // IS enabled
                // ADD SNIPPET
                } elseif ( !$exists && !$partial && $is_enabled ) {
                    // ddtt_print_r( $snippet );

                    // Add the snippet to the add bucket
                    $add[] = $snippet;

                    // Count this as an edit
                    $edits++;
                }
            }

            // Check if we need to add anything
            if ( $edits > 0 ) {

                // Remove the <?php
                if ( strpos( $safe_file_lines[0], htmlentities( '<?php' ) ) !== false ) {
                    unset( $safe_file_lines[0] );
                }

                // Domain & IP
                $blogname = get_option( 'blogname' );

                // Info at top
                $added_by = [];
                $added_by_id = ' * Added via '.DDTT_NAME;
                $added_by_lines = [
                    '<?php',
                    '/**',
                    ' * '.$blogname,
                    ' * '.home_url(),
                    $added_by_id,
                    ' * Last updated: '.date( 'F j, Y g:i A'),
                    ' */',
                    ''
                ];
                foreach ( $added_by_lines as $abl ) {
                    if ( $abl != '' ) {
                        $abl = htmlentities( $abl );
                    }
                    $added_by[] = $abl;
                }
                
                // Remove the added_by comments
                if ( ( false !== $added_by_key = array_search( $added_by_id, $safe_file_lines ) ) ) {

                    // Stopped at
                    $stopped_at = 0;
                        
                    // Count available rows
                    $add_by_count = count( $added_by );

                    // First remove the id key
                    unset( $safe_file_lines[ $added_by_key ] );

                    // Check the lines above the id
                    for ( $la = 1; $la <= $add_by_count; $la++ ) {

                        // Does the line above start mid comment?
                        if ( isset( $safe_file_lines[ $added_by_key - $la ] ) && str_starts_with( $safe_file_lines[ $added_by_key - $la ], ' * ' ) !== false ) {

                            // Remove it
                            unset( $safe_file_lines[ $added_by_key - $la ] );
                        }

                        // Does the line above start the comment?
                        if ( isset( $safe_file_lines[ $added_by_key - $la ] ) && str_starts_with( $safe_file_lines[ $added_by_key - $la ], '/**' ) !== false ) {

                            // Remove it
                            unset( $safe_file_lines[ $added_by_key - $la ] );
                            
                            // Stop here
                            break;
                        }
                    }

                    // Check the lines below the id
                    for ( $lb = 1; $lb <= $add_by_count; $lb++ ) {

                        // Does the line below start mid comment?
                        if ( isset( $safe_file_lines[ $added_by_key + $lb ] ) && str_starts_with( $safe_file_lines[ $added_by_key + $lb ], ' * ' ) !== false ) {

                            // Remove it
                            unset( $safe_file_lines[ $added_by_key + $lb ] );
                        }

                        // Does the line below end the comment?
                        if ( isset( $safe_file_lines[ $added_by_key + $lb ] ) && str_starts_with( $safe_file_lines[ $added_by_key + $lb ], ' */' ) !== false ) {

                            // Remove it
                            unset( $safe_file_lines[ $added_by_key + $lb ] );

                            // Save which row we stopped on
                            $stopped_at = $added_by_key + $lb;

                            // Stop here
                            break;
                        }
                    }

                    // If there is a space directly below it, remove that too
                    if( strlen( $safe_file_lines[ $stopped_at + 1 ] ) >= 0 && empty( trim( $safe_file_lines[ $stopped_at + 1 ] ) ) ) {
                        unset( $safe_file_lines[ $stopped_at + 1 ] );
                    }
                }

                // Info at bottom
                $end = [];
                $end_id = '/* End of snippets added via '.DDTT_NAME.' */';
                $end[] = htmlentities( $end_id );
                $end[] = '';
                $end[] = '';

                // Remove the end comment
                if ( ( false !== $end_key = array_search( $end_id, $safe_file_lines ) ) ) {
                    // ddtt_print_r( $end_key );

                    // First remove the id key
                    unset( $safe_file_lines[ $end_key ] );

                    // If there are spaces directly below it, remove them too
                    if( strlen( $safe_file_lines[ $end_key + 1 ] ) >= 0 && empty( trim( $safe_file_lines[ $end_key + 1 ] ) ) ) {
                        unset( $safe_file_lines[ $end_key + 1 ] );
                    }
                    if( strlen( $safe_file_lines[ $end_key + 2 ] ) >= 0 && empty( trim( $safe_file_lines[ $end_key + 2 ] ) ) ) {
                        unset( $safe_file_lines[ $end_key + 2 ] );
                    }
                }

                // Store converted snippets here
                $add_converted = [];

                // Cycle through the snippets we need to add
                foreach ( $add as $a ) {

                    // Convert the snippet
                    $add_converted[] = htmlentities( '// '.$a[ 'label' ] );
                    foreach ( $a[ 'lines' ] as $aline ) {
                        $add_converted[] = htmlentities( $this->snippet_line_to_string( $aline ) );
                    }
                    $add_converted[] = '';
                }

                // Are we testing?  
                // if ( $testing ) {
                //     ddtt_print_r( $add );
                // }

                // Add them to the safe file lines
                if ( !empty( $add ) ) {
                    $safe_file_lines = array_merge( $added_by, $add_converted, $end, $safe_file_lines );
                } else {
                    $safe_file_lines = array_merge( ['<?php'], $safe_file_lines );
                }

                // Are we testing?                
                if ( $testing ) {
                    ddtt_print_r( '<br><br><hr><br><br>' );
                    ddtt_print_r( 'AFTER:<br>' );
                    ddtt_print_r( $safe_file_lines );

                // Otherwise continue with production
                } else {

                    // Separate into lines and make html characters work again
                    $separate_safe_lines = [];
                    foreach( $safe_file_lines as $k => $sfl ) {
                        if ( $k === array_key_last( $safe_file_lines ) ) {
                            $separate_safe_lines[] = html_entity_decode( $sfl );
                        } else {
                            $separate_safe_lines[] = html_entity_decode( $sfl ).PHP_EOL;
                        }
                    }

                    // Filenames
                    $now = ddtt_convert_timezone( date( 'Y-m-d H:i:s' ), 'Y-m-d-H-i-s', get_option( 'ddtt_dev_timezone', wp_timezone_string() ) );
                    $old_file = str_replace( '.php', '-'.$now.'.php', $file );
                    $temp_file = str_replace( '.php', '-'.DDTT_GO_PF.'temp.php', $file );

                    // Are we confirming?
                    if ( $confirm ) {

                        // Make human readable
                        if ( file_put_contents( $temp_file, $separate_safe_lines ) ) {
                            ddtt_admin_notice( 'error', '&#9888; CAUTION! You are about to replace your '.$filename.' file, which may result in your site breaking. Please confirm below that the new file looks as you expect it to. Once confirmed, a copy of your old '.$filename.' file will be copied here:<br>"'.$old_file.'"<br>Please make note of this location so you can restore it if needed. To restore this file you will need to access your file manager from your host or through FTP, then simply delete the current '.$filename.' file and rename the copied version as '.$filename.'.' );
                        }

                    // We have confirmed
                    } else {

                        // Delete temp file
                        wp_delete_file( $temp_file );

                        // Copy old file if we are making edits
                        if ( copy( $file, $old_file ) ) {
                            ddtt_admin_notice( 'success', 'The previous '.$filename.' file has been copied to '.$old_file );
                        } else {
                            ddtt_admin_notice( 'error', 'There was a problem making a back-up of the original '.$filename.' file.' );
                        }

                        // Back up the original
                        if ( !get_option( 'ddtt_wpconfig_og' ) ) {
                            update_option( 'ddtt_wpconfig_og', $wpconfig );
                            update_option( 'ddtt_wpconfig_og_replaced_date', $now );
                        }

                        // Back up the previous file string to site option
                        update_option( 'ddtt_wpconfig_last', $wpconfig );
                        update_option( 'ddtt_wpconfig_last_updated', $now );

                        // Update the file
                        if ( file_put_contents( $file, $separate_safe_lines ) ) {
                            ddtt_admin_notice( 'success', 'Your '.$filename.' file has been updated successfully!' );
                        } else {
                            ddtt_admin_notice( 'error', 'There was a problem updating your '.$filename.' file.' );
                        }
                    }
                }
            }

            // Stop the script
            return;
            
        // Otherwise say the file wasn't found
        } else {

            echo esc_html( $file ) . ' not found';
            return;
        }
    } // End rewrite()
}