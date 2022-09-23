<?php
/**
 * HTACCESS class file.
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Main plugin class.
 */
class DDTT_HTACCESS {

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
        // Domain & IP
        $domain = ddtt_get_domain();
        $ip_server = sanitize_text_field( $_SERVER['SERVER_ADDR'] );

        // Plugin path
        $plugin_path = DDTT_PLUGIN_SHORT_DIR;
        $plugins_path = DDTT_PLUGINS_URL.'/';

        // Theme paths
        $themes_root_uri = str_replace( site_url( '/' ), '', get_theme_root_uri() ).'/';
        
        $parent_theme = str_replace( '%2F', '/', rawurlencode( get_template() ) );
        $parent_theme_path = '/'.$themes_root_uri.$parent_theme.'/';

        $active_theme = str_replace( '%2F', '/', rawurlencode( get_stylesheet() ) );
        $active_theme_path = '/'.$themes_root_uri.$active_theme.'/';

        // Other paths
        $admin_path = DDTT_ADMIN_URL;
        $includes_path = DDTT_INCLUDES_URL.'/';

        // Add the snippets
        $snippets = apply_filters( 'ddtt_htaccess_snippets', [
            'prevent_csrf' => [
                'label' => 'Prevent Cross Site Request Forgery (CSRF)',
                'lines' => [
                    '<IfModule mod_rewrite.c>',
                    'RewriteEngine on',
                    'RewriteCond %{REQUEST_METHOD} POST',
                    'RewriteCond %{HTTP_REFERER} !^https://(.*)?'.$domain.' [NC]',
                    'RewriteCond %{REQUEST_URI} ^(.*)?wp-login\.php(.*)$ [OR]',
                    'RewriteCond %{REQUEST_URI} ^(.*)?'.$admin_path.'$',
                    'RewriteRule ^(.*)$ - [F]',
                    '</IfModule>'
                ]
            ],
            'debuglog_private' => [
                'label' => 'Prevent Debug.log from Being Public',
                'lines' => [
                    '<Files "debug.log">',
                    'Require all denied',
                    'Require ip 127.0.0.1',
                    'Require ip '.$ip_server,
                    '</Files>',
                ]
            ],
            'redirect_https' => [
                'label' => 'Redirect http:// to https://',
                'lines' => [
                    '<IfModule mod_rewrite.c>',
                    'RewriteEngine On',
                    'RewriteCond %{SERVER_PORT} 80 ',
                    'RewriteRule ^(.*)$ https://'.$domain.'/$1 [R=301,L]',
                    '</IfModule>',
                ]
            ],
            'force_ssl' => [
                'label' => 'Force Require SSL to View Site',
                'lines' => [
                    '# May cause issues with GoDaddy Security Backups',
                    'SSLOptions +StrictRequire',
                    'SSLRequireSSL',
                    'SSLRequire %{HTTP_HOST} eq "'.$domain.'"',
                    'ErrorDocument 403 https://'.$domain,
                ]
            ],
            'protect_imp_files' => [
                'label' => 'Protect Important WP and Server Files',
                'lines' => [
                    '<FilesMatch "^.*(error_log|wp-config\.php|php.ini|\.[hH][tT][aApP].*)$">',
                    'Order deny,allow',
                    'Deny from all',
                    '</FilesMatch>',
                    'RedirectMatch 403 \.(htaccess|htpasswd|errordocs|logs)$',
                ]
            ],
            'disable_server_sig' => [
                'label' => 'Turn Off Server Signature',
                'lines' => [
                    '# Suppresses the footer line server version number and ServerName of the serving virtual host',
                    'ServerSignature Off',
                ]
            ],
            'disable_index_browsing' => [
                'label' => 'Disable Index Browsing',
                'lines' => [
                    '# Options All -Indexes may cause Internal Server Error',
                    'Options -Indexes',
                ]
            ],
            'dir_force_index' => [
                'label' => 'Directory Index Force Index.php',
                'lines' => [
                    'DirectoryIndex index.php index.html /index.php',
                ]
            ],
            'script_injections' => [
                'label' => 'Prevent Script Injections',
                'lines' => [
                    'Options +FollowSymLinks',
                    'RewriteEngine On',
                    'RewriteCond %{QUERY_STRING} (<|%3C).*script.*(>|%3E) [NC,OR]',
                    'RewriteCond %{QUERY_STRING} GLOBALS(=|[|%[0-9A-Z]{0,2}) [OR]',
                    'RewriteCond %{QUERY_STRING} _REQUEST(=|[|%[0-9A-Z]{0,2})',
                    'RewriteRule ^(.*)$ index.php [F,L]',
                ]
            ],
            'plugin_theme_access' => [
                'label' => 'Restrict Direct Access to Plugin and Theme PHP files',
                'lines' => [
                    'RewriteCond %{REQUEST_URI} !^'.$plugin_path,
                    'RewriteRule '.$plugins_path.'(.*\.php)$ - [R=404,L]',
                    'RewriteCond %{REQUEST_URI} !^'.$parent_theme_path,
                    'RewriteCond %{REQUEST_URI} !^'.$active_theme_path,
                    'RewriteRule '.$themes_root_uri.'(.*\.php)$ - [R=404,L]',
                ]
            ],
            'protect_includes' => [
                'label' => 'Protect WP Includes Directory',
                'lines' => [
                    '<IfModule mod_rewrite.c>',
                    'RewriteEngine On',
                    'RewriteBase /',
                    'RewriteRule ^'.$admin_path.'/includes/ - [F,L]',
                    'RewriteRule !^'.$includes_path.' - [S=3]',
                    'RewriteRule ^'.$includes_path.'[^/]+\.php$ - [F,L]',
                    'RewriteRule ^'.$includes_path.'js/tinymce/langs/.+\.php - [F,L]',
                    'RewriteRule ^'.$includes_path.'theme-compat/ - [F,L]',
                    '</IfModule>',
                ]
            ],
            'username_enumeration' => [
                'label' => 'Prevent Username Enumeration',
                'lines' => [
                    'RewriteEngine On',
                    'RewriteCond %{REQUEST_URI} !^/='.$admin_path.' [NC]',
                    'RewriteCond %{QUERY_STRING} author=\d',
                    'RewriteRule ^ /? [L,R=301]',
                ]
            ],
            'redirect_bots' => [
                'label' => 'Block Bots from WP Admin',
                'lines' => [
                    'ErrorDocument 401 /404.shtml',
                    'ErrorDocument 403 /404.shtml',
                    'Redirect 301 /author/admin/ /404.shtml',
                ]
            ],
            'upload_size' => [
                'label' => 'Increase Upload Size',
                'lines' => [
                    'php_value upload_max_filesize 256M',
                    'php_value post_max_size 256M',
                    'php_value max_execution_time 300',
                    'php_value max_input_time 300',
                ]
            ],
            'allow_backups' => [
                'label' => 'Allow Sucuri GoDaddy Backups',
                'lines' => [
                    '<ifmodule mod_rewrite.c="">',
                    'RewriteRule ^sucuri-(.*).php$ - [L]',
                    '</ifmodule>',
                ]
            ],
        ] );
        return $snippets;
    } // End snippets()


    /**
     * Table row for HTACCESS checkboxes
     *
     * @param array $snippet
     * @return string
     */
    public function options_tr( $name, $snippet, $exists ) {
        // Check the box if the snippet exists
        if ( $exists ) {
            $checkbox_value = true;
            $class = 'true';
        } else {
            $checkbox_value = false;
            $class = 'false';
        }

        // Convert the snippet to a string
        $lines = $this->snippet_to_string( $snippet, '<br>' );

        // The Checkbox
        $input = '<input type="checkbox" name="l[]" value="'.$name.' " '.checked( 1, $checkbox_value, false ).'/>';

        // Build the row
        $row = '<tr valign="top">
            <th scope="row">'.$snippet[ 'label' ].'</th>
            <td class="checkbox-cell">'.$input.'</td>
            <td><div class="snippet_container '.$name.'"> <span class="snippet-exists '.$class.'">'.$lines.'</div></td>
        </tr>';
        
        // Return the row
        return $row;
    } // End options_tr()


    /**
     * Check if a snippet exists
     *
     * @param string $htaccess
     * @param array $snippet
     * @return array
     */
    public function snippet_exists( $htaccess, $snippet ) {
        // Add the lines together
        $lines = $this->snippet_to_string( $snippet, ' ' );

        // Check the file for the line
        if ( strpos( $htaccess, $lines ) !== false ) {

            // Count this as exists
            $snippet_exists = true;
        
        // Otherwise
        } else {
            $snippet_exists = false;
        }

        // Return the results
        return $snippet_exists;
    } // End snippet_exists()


    /**
     * Create the regex for finding a snippet line
     *
     * @param array $line
     * @return string
     */
    public function snippet_regex( $line ) {
        // Replace spaces
        $line = str_replace( ' ', '\s*', $line );

        // Put the regex together
        $regex = '/\s*'.$line.'\s*/i';
        // ddtt_print_r($regex);

        return $regex;
    } // End snippet_regex()


    /**
     * Convert a snippet to a string
     *
     * @param array $snippet
     * @return string
     */
    public function snippet_to_string( $snippet, $br = ' ' ) {
        // Store the new array here
        $lines = [];

        // Add htmlspecialchars
        foreach ( $snippet[ 'lines' ] as $line ) {

            // Put the snippet together
            $lines[] = htmlspecialchars( $line );
        }

        // Return it as one string
        return implode( $br, $lines );
    } // End snippet_line_to_string()


    /**
     * Rewrite the WP-CONFIG file based on options
     *
     * @param string $file
     * @param array $snippets
     * @param array $enabled
     * @return void
     */
    public function rewrite( $filename, $snippets, $enabled, $testing = false ) {
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
            $htaccess = file_get_contents( $file );

            // Display html tags
            $file_contents = htmlspecialchars( $htaccess );

            // Replace line breaks
            $file_contents = strtr( $file_contents, chr(10), chr(32) );

            // Separate each line into an array item
            $file_lines = explode(PHP_EOL, $htaccess );

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
                dpr( '$enabled: ' );
                dpr( $enabled );
                // dpr( '<br><br><hr><br><br>' );
                // dpr( 'BEFORE:<br>' );
                // dpr( $safe_file_lines );
            }

            // Cycle each snippet
            foreach ( $snippets as $snippet_key => $snippet ) {
            
                // Check if the snippet exists in the file
                $exists = $this->snippet_exists( $file_contents, $snippet );

                // if ( $exists ) {
                //     dpr( $this->snippet_to_string( $snippet, '<br>' ) );
                // }

                // Enabled
                if ( strpos( json_encode( $enabled ), $snippet_key ) !== false ) {
                    $is_enabled = true;
                } else {
                    $is_enabled = false;
                }
                // $is_enabled = in_array( $snippet_key, $enabled ) ? true : false;
                // dpr( $snippet_key.': '.$is_enabled );

                // Does NOT exist
                // NOT enabled
                // SKIP, because we're not going to add it anyway
                if ( !$exists && !$is_enabled ) {
                    continue;

                // Exists
                // REMOVE SNIPPETS REGARDLESS, because we're going to rewrite it all
                } elseif ( $exists ) {

                    // Get a string version
                    $line_string = $this->snippet_to_string( $snippet );

                    // Search the file lines
                    foreach( $safe_file_lines as $file_key => $safe_file_line ) {
                        
                        // Check the file for the comment line
                        if ( strpos( $safe_file_line, $snippet[ 'label' ] ) !== false ) {

                            // Check for each line in the snippet
                            for ( $sl = 1; $sl <= count( $snippet[ 'lines' ] ); $sl++ ) {
                                
                                // If the line below it is in the snippet, remove it
                                if ( isset( $safe_file_lines[ $file_key + $sl ] ) && strpos( $line_string, $safe_file_lines[ $file_key + $sl ] ) !== false ) {
                                    unset( $safe_file_lines[ $file_key + $sl ] );
                                }
                            }

                            // If there is a space directly below it, remove that too
                            $end_of_snippet = $file_key + count( $snippet[ 'lines' ] );
                            if ( isset( $safe_file_lines[ $end_of_snippet + 1 ] ) && strlen( $safe_file_lines[ $end_of_snippet + 1 ] ) >= 0 && empty( trim( $safe_file_lines[ $file_key + 1 ] ) ) ) {
                                unset( $safe_file_lines[ $end_of_snippet + 1 ] );
                            }

                            // Lastly, remove the comment line
                            unset( $safe_file_lines[ $file_key ] );
                        }
                    }

                    // Add it if it is enabled
                    if ( $is_enabled ) {
                        $add[] = $snippet;
                    }

                    // If it's not supposed to be there, just count this as an edit
                    if ( !$is_enabled ) {
                        $edits++;
                    }

                // Does NOT exist
                // IS enabled
                // ADD SNIPPET
                } elseif ( !$exists && $is_enabled ) {
                    // ddtt_print_r( $snippet );

                    // Add the snippet to the add bucket
                    $add[] = $snippet;

                    // Count this as an edit
                    $edits++;
                }
            }

            // Check if we need to add anything and make edits
            if ( $edits > 0 ) {

                // Info at top
                $added_by = [];
                $added_by_id = '################ ADDED VIA '.strtoupper(DDTT_NAME).' ################';
                $added_by_lines = [
                    '',
                    '',
                    $added_by_id,
                    '# Last updated: '.date( 'F j, Y g:i A'),
                    '',
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
                    // ddtt_print_r( $added_by_key );
                        
                    // Count available rows
                    $add_by_count = count( $added_by );

                    // Check the lines above the id for space
                    for ( $la = 1; $la <= 20; $la++ ) {

                        // If there is space directly above it, remove them
                        if( isset( $safe_file_lines[ $added_by_key - $la ] ) && strlen( $safe_file_lines[ $added_by_key - $la ] ) >= 0 && empty( trim( $safe_file_lines[ $added_by_key - $la ] ) ) ) {
                            unset( $safe_file_lines[ $added_by_key - $la ] );

                        } else {
                            break;
                        }
                    }

                    // Check the lines below the id
                    for ( $lb = 1; $lb <= $add_by_count; $lb++ ) {

                        // If the key below it exists
                        if ( isset( $safe_file_lines[ $added_by_key + $lb ] ) ) {
                            
                            // Remove it
                            unset( $safe_file_lines[ $added_by_key + $lb ] );
                        }
                    }

                    // Remove the id key
                    unset( $safe_file_lines[ $added_by_key ] );
                }

                // Info at bottom
                $end = [];
                $end_id = '################# END OF '.strtoupper(DDTT_NAME).' ##################';
                $end[] = htmlentities( $end_id );

                // Remove the end comment
                if ( ( false !== $end_key = array_search( $end_id, $safe_file_lines ) ) ) {
                    // ddtt_print_r( $end_key );

                    // First remove the id key
                    unset( $safe_file_lines[ $end_key ] );
                }

                // Store converted snippets here
                $add_converted = [];

                // Cycle through the snippets we need to add
                foreach ( $add as $a ) {

                    // Convert the snippet
                    $add_converted[] = htmlentities( '# '.$a[ 'label' ] );
                    foreach ( $a[ 'lines' ] as $aline ) {
                        $add_converted[] = htmlentities( $aline );
                    }
                    $add_converted[] = '';
                }
                // ddtt_print_r( $add_converted );

                // Let's remove any trailing line breaks from the bottom of the safe lines
                $sfl_count = count( $safe_file_lines );
                for ( $sfl = 0; $sfl < $sfl_count; $sfl++ ) {

                    if ( isset( $safe_file_lines[ $sfl_count - $sfl ] ) && strlen( $safe_file_lines[ $sfl_count - $sfl ] ) >= 0 && empty( trim( $safe_file_lines[ $sfl_count - $sfl ] ) ) ) {
                        unset( $safe_file_lines[ $sfl_count - $sfl ] );

                    } else {
                        break;
                    }
                }

                // Add everything together
                if ( !empty( $add ) ) {
                    $safe_file_lines = array_merge( $safe_file_lines, $added_by, $add_converted, $end );
                }

                // Are we testing?
                if ( $testing ) {
                    dpr( '<br><br><hr><br><br>' );
                    dpr( 'AFTER:<br>' );
                    dpr( $safe_file_lines );

                // Otherwise continue with production
                } else {

                    // Separate into lines and make html characters work again
                    $separate_safe_lines = [];
                    foreach($safe_file_lines as $k => $sfl) {
                        if ($k === array_key_last($safe_file_lines)) {
                            $separate_safe_lines[] = html_entity_decode($sfl);
                        } else {
                            $separate_safe_lines[] = html_entity_decode($sfl).PHP_EOL;
                        }
                    }

                    // Copy old file if we are making edits
                    $old_file = str_replace( '.htaccess', '.htaccess-'.date( 'Y-m-d-H-i-s' ), $file );
                    if ( copy( $file, $old_file ) ) {
                        ddtt_admin_notice( 'success', 'The previous '.$filename.' file has been copied to '.$old_file );
                    } else {
                        ddtt_admin_notice( 'error', 'There was a problem making a back-up of the original '.$filename.' file.' );
                    }

                    // Back up the original
                    if ( !get_option( 'ddtt_htaccess_og' ) ) {
                        update_option( 'ddtt_htaccess_og', $htaccess );
                        update_option( 'ddtt_htaccess_og_replaced_date', date( 'Y-m-d-H-i-s' ) );
                    }

                    // Back up the previous file string to site option
                    update_option( 'ddtt_htaccess_last', $htaccess );
                    update_option( 'ddtt_htaccess_last_updated', date( 'Y-m-d-H-i-s' ) );

                    // Turn the new lines into a string
                    if ( file_put_contents( $file, $separate_safe_lines ) ) {
                        ddtt_admin_notice( 'success', 'Your '.$filename.' file has been updated successfully!' );
                    } else {
                        ddtt_admin_notice( 'error', 'There was a problem updating your '.$filename.' file.' );
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