<?php
/**
 * HTACCESS class file.
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


// Instantiate
add_action( 'init', function() {
    (new DDTT_HTACCESS)->init();
} );


/**
 * Main plugin class.
 */
class DDTT_HTACCESS {

    /**
     * Run on init
     */
    public function init() {

        // Enqueue scripts
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

    } // End init()


    /**
     * Our snippets
     * NOTE: IF REMOVING A SNIPPET, DO NOT DELETE FROM ARRAY, INSTEAD ADD 'remove' => TRUE (LIKE THE force_ssl OPTION BELOW)
     *
     * @return array
     */
    public function snippets() {
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
            'restrict_direct_access' => [
                'old_label' => [
                    'Prevent Cross Site Request Forgery (CSRF)',
                    'Restrict Unuathorized Direct Access to Login and Admin'
                ],
                'label' => 'Restrict Unauthorized Direct Access to Login and Admin',
                'lines' => [
                    '<IfModule mod_rewrite.c>',
                    'RewriteEngine on',
                    'RewriteCond %{REQUEST_METHOD} POST',
                    'RewriteCond %{HTTP_REFERER} !^https://(.*)?'.$domain.' [NC]',
                    'RewriteCond %{REQUEST_URI} ^(.*)?wp-login\.php(.*)$ [OR]',
                    'RewriteCond %{REQUEST_URI} ^(.*)?'.$admin_path.'$',
                    'RewriteRule ^(.*)$ - [F]',
                    '</IfModule>'
                ],
                'desc' => 'Restricts direct access to the WordPress login page (wp-login.php) and the admin area (wp-admin) unless the request is a valid POST request coming from your domain. It enhances security by preventing unauthorized access to sensitive parts of your WordPress site.'
            ],
            'samesite' => [
                'label' => 'Set SameSite Attribute for Session Cookies',
                'lines' => [
                    'Header always edit Set-Cookie (.*) "$1; SameSite=Lax"'
                ],
                'desc' => 'Helps prevent Cross Site Request Forgery (CSRF). A CSRF attack occurs when a malicious website tricks an authenticated user\'s browser into performing unwanted actions on a trusted site. The attacker exploits the user\'s existing session to execute actions without their knowledge. CSRF attacks can lead to unauthorized actions, such as changing passwords, transferring funds, or making purchases.'
            ],
            'protect_imp_files' => [
                'label' => 'Protect Important WP and Server Files',
                'lines' => [
                    '<FilesMatch "^.*(error_log|wp-config\.php|php.ini|\.[hH][tT][aApP].*)$">',
                    'Order deny,allow',
                    'Deny from all',
                    '</FilesMatch>',
                    'RedirectMatch 403 \.(htaccess|htpasswd|errordocs|logs)$',
                ],
                'desc' => 'Restricts access to sensitive files and directories within your WordPress installation. Denies access to the server error log file, critical WordPress configuration file, PHP configuration file, and any file starting with <code>.ht</code> or <code>.htaccess</code>. Any request for these files will result in a 403 Forbidden error. By denying access to critical files and directories, you enhance the security of your WordPress installation. Unauthorized users won\'t be able to view sensitive information or manipulate essential configuration files.'
            ],
            'debuglog_private' => [
                'label' => 'Prevent Debug.log from Being Public',
                'lines' => [
                    '<Files "debug.log">',
                    'Require all denied',
                    'Require ip 127.0.0.1',
                    'Require ip '.$ip_server,
                    '</Files>',
                ],
                'desc' => 'Restricts access to the <code>debug.log</code> file within your WordPress installation, enhancing security. This is highly recommended, especially if you have enabled debugging on your <code>wp-config.php</code> file. Only authorized IP addresses (localhost and hosting server) can view the log file. Unauthorized users won\'t be able to access sensitive debugging information.'
            ],
            'redirect_https' => [
                'label' => 'Redirect http:// to https://',
                'lines' => [
                    '<IfModule mod_rewrite.c>',
                    'RewriteEngine On',
                    'RewriteCond %{SERVER_PORT} 80',
                    'RewriteRule ^(.*)$ https://'.$domain.'/$1 [R=301,L]',
                    '</IfModule>',
                ],
                'desc' => 'Performs a 301 (permanent) redirect from HTTP to HTTPS for your WordPress site. By doing so, you ensure that all traffic to your site is encrypted. The 301 redirect tells search engines that the change is permanent, preserving SEO rankings and avoiding duplicate content issues.'
            ],
            'force_ssl' => [
                'label' => 'Force Require SSL to View Site',
                'lines' => [
                    '# May cause issues with GoDaddy Security Backups',
                    'SSLOptions +StrictRequire',
                    'SSLRequireSSL',
                    'SSLRequire %{HTTP_HOST} eq "'.$domain.'"',
                    'ErrorDocument 403 https://'.$domain,
                ],
                'remove' => TRUE
                // 'desc' => 'Slated for removal'
            ],
            'disable_server_sig' => [
                'label' => 'Turn Off Server Signature',
                'lines' => [
                    '# Suppresses the footer line server version number and ServerName of the serving virtual host',
                    'ServerSignature Off',
                ],
                'desc' => 'Disables the server signature (also known as the server banner or server version) for your WordPress site. The server signature is a piece of information about your web server (e.g., Apache, Nginx) and its version. By default, when an error occurs (such as a 404 page), the server includes this signature in the response headers. The server signature can reveal sensitive information about the software versions running on the web server. Hiding the server signature enhances security by reducing the exposure of server details. It prevents potential attackers from knowing specific server software versions. Disabling the server signature is a recommended security practice.'
            ],
            'disable_index_browsing' => [
                'label' => 'Disable Index Browsing',
                'lines' => [
                    '# Options All -Indexes may cause Internal Server Error',
                    'Options -Indexes',
                ],
                'desc' => 'Prevents the web server from automatically generating a list of files and directories when no specific file (such as an index file) is found in a directory. Without this directive, if someone accesses a directory without an index file (e.g., https://yourdomain.com/some-directory/), the server might display a list of all files and subdirectories within that directory. Disable this behavior makes it more difficult for potential attackers to explore your directory structure.'
            ],
            'dir_force_index' => [
                'label' => 'Directory Index Force Index.php',
                'lines' => [
                    'DirectoryIndex index.php index.html /index.php',
                ],
                'desc' => 'Modifies the default index page behavior for your site. By configuring the order of index files, you control which file is loaded first when someone accesses a directory. In this example, if a user visits a directory without specifying a specific file (e.g., https://yourdomain.com/some-directory/), the server will first look for index.php, then index.html, and finally /index.php. This ensures that the appropriate default page is displayed when accessing directories within your WordPress site.'
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
                ],
                'desc' => 'Prevent potential attacks that exploit query strings containing suspicious patterns related to scripts or global variables. It enhances security by blocking access to URLs with harmful query strings.'
            ],
            'plugin_theme_access' => [
                'label' => 'Restrict Direct Access to Plugin and Theme PHP files',
                'lines' => [
                    'RewriteCond %{REQUEST_URI} !^'.$plugin_path,
                    'RewriteRule '.$plugins_path.'(.*\.php)$ - [R=404,L]',
                    'RewriteCond %{REQUEST_URI} !^'.$parent_theme_path,
                    'RewriteCond %{REQUEST_URI} !^'.$active_theme_path,
                    'RewriteRule '.$themes_root_uri.'(.*\.php)$ - [R=404,L]',
                ],
                'remove' => TRUE
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
                ],
                'desc' => 'Prevents unauthorized direct access to sensitive PHP files within the "include" directories.'
            ],
            'username_enumeration' => [
                'label' => 'Prevent Username Enumeration',
                'lines' => [
                    'RewriteEngine On',
                    'RewriteCond %{REQUEST_URI} !^/='.$admin_path.' [NC]',
                    'RewriteCond %{QUERY_STRING} author=\d',
                    'RewriteRule ^ /? [L,R=301]',
                ],
                'desc' => 'Redirects URLs with an author parameter (e.g., ?author=123) away from the site. This can be useful for security reasons or to prevent exposing user information, including their username.'
            ],
            'redirect_bots' => [
                'label' => 'Block Bots from WP Admin',
                'lines' => [
                    'ErrorDocument 401 /404.shtml',
                    'ErrorDocument 403 /404.shtml',
                    'Redirect 301 /author/admin/ /404.shtml',
                ],
                'desc' => 'Tells the server to display the <code>/404.shtml</code> page whenever a 401 Unauthorized error occurs. This typically happens when a user tries to access a restricted area of the site without the correct credentials. Same with when a 403 Forbidden error occurs, which is usually triggered when a user tries to access a directory or file for which they do not have permission. Furthermore, this snippet blocks access to the <code>/author/admin/</code> page.'
            ],
            'upload_size' => [
                'label' => 'Increase Upload Size',
                'lines' => [
                    'php_value upload_max_filesize 256M',
                    'php_value post_max_size 256M',
                    'php_value max_execution_time 300',
                    'php_value max_input_time 300',
                ],
                'desc' => 'Sets the maximum upload file size, which means that users will be able to upload files up to 256MB in size. Sets the maximum size of POST data that PHP will accept to 256MB. Sets the maximum time in seconds a script is allowed to run before it is terminated by the parser. This helps prevent poorly written scripts from tying up the server. Allows a script to run for up to 300 seconds (or 5 minutes). Sets the maximum time in seconds (also 300) that a script is allowed to parse input data, like POST, GET and file uploads.'
            ],
            'max_input_vars' => [
                'label' => 'Increase Max Vars Limit',
                'lines' => [
                    'php_value max_input_vars 3000',
                ],
                'desc' => 'Increases the maximum number of input variables that PHP will accept. By default, PHP has a limit on the number of input variables it can handle. This limit is set to prevent attacks such as hash collisions. However, in some cases, you might need to increase this limit. For example, if you have a form with a large number of fields or if you\'re using a WordPress theme that requires a higher limit.'
            ],
            'allow_backups' => [
                'label' => 'Allow Sucuri GoDaddy Backups',
                'lines' => [
                    '<ifmodule mod_rewrite.c="">',
                    'RewriteRule ^sucuri-(.*).php$ - [L]',
                    '</ifmodule>',
                ],
                'desc' => 'Only useful if you are hosting with GoDaddy and have Website Security feature with backups enabled. This is a recommended snippet by Sucuri that may help resolve issues with redirecting during backups.'
            ],
        ] );
        return $snippets;
    } // End snippets()


    /**
     * Table row for snippet checkboxes
     *
     * @param string $name
     * @param string $label
     * @param boolean $snippet_exists
     * @param array $line_strings_1
     * @param array $line_strings_0
     * @param string $description
     * @return string
     */
    public function options_tr( $name, $snippet, $is_detected ) {
        // Description
        if ( isset( $snippet[ 'desc' ] ) ) {
            $description = $snippet[ 'desc' ];
        } else {
            $description = '';
        }
        
        // Get the lines
        $lines = $this->snippet_to_string( $snippet, '<br>' );

        // Check if we are redacting
        if ( !get_option( DDTT_GO_PF.'view_sensitive_info' ) || get_option( DDTT_GO_PF.'view_sensitive_info' ) != 1 ) {

            // Redact sensitive info
            $substrings = [
                'Require ip ',
            ];

            // Iter the globals
            foreach ( $substrings as $substring ) {

                // Attempt to find it
                if ( preg_match_all( '/'.$substring.'(\s?)(.+?)\<br\>/', $lines, $matches ) ) {

                    // Iter each match
                    foreach ( $matches[2] as $match ) {
                        
                        // Add redact div
                        $lines = str_replace( $match, '<div class="redact">'.$match.'</div>', $lines );
                    }
                }
            }
        }

        // Check the box if the snippet exists
        if ( $is_detected ) {
            $detected = 'Yes';
            $detected_class = ' yes';
            $disable_add = ' disabled="disabled"';
            $disable_remove = '';
            $current_tab = '<a href="#" class="snippet-tab current active">Current</a>';
            $current_cont = '<div class="snippet_container current active">'.$lines.'</div>';
            $proposed_tab = '';
            $proposed_cont = '';
        } else {
            $detected = 'No';
            $detected_class = '';
            $disable_add = '';
            $disable_remove = ' disabled="disabled"';
            $current_tab = '';
            $current_cont = '';
            $proposed_tab = '<a href="#" class="snippet-tab proposed active">Proposed</a>';
            $proposed_cont = '<div class="snippet_container proposed active">'.$lines.'</div>';
        }

        // Different value
        if ( $is_detected ) {
            $title = 'Snippet found.';
        } else {
            $title = 'Snippet not found.';
        }

        // Build the row
        $row = '<tr valign="top">
            <td scope="row" class="option-cell"><div>'.$snippet[ 'label' ].' <a href="#" class="learn-more" data-name="'.$name.'">?</a></div> 
            <div id="desc-'.$name.'" class="field-desc">'.$description.'</div></td>
            <td class="checkbox-cell"><div class="detected-indicator'.$detected_class.'" title="'.$title.'">'.$detected.'</div></td>
            <td class="checkbox-cell"><input type="checkbox" name="a[]" value="'.$name.'"'.$disable_add.'/></td>
            <td class="checkbox-cell"><input type="checkbox" name="r[]" value="'.$name.'"'.$disable_remove.'/></td>
            <td class="snippet-cell" data-name="'.$name.'">
                '.$proposed_tab.'
                '.$current_tab.'
                '.$proposed_cont.'
                '.$current_cont.'
            </td>
        </tr>';
        
        // Return the row
        return $row;
    } // End options_tr()


    /**
     * Check if a snippet exists
     *
     * @param string $htaccess
     * @param array $snippet
     * @return boolean
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
            $lines[] = trim( htmlspecialchars( $line ) );
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
            $htaccess = file_get_contents( $file );

            // Display html tags
            $file_contents = htmlspecialchars( $htaccess );

            // Replace line breaks
            $file_contents = strtr( $file_contents, chr(10), chr(32) );

            // Separate each line into an array item
            $file_lines = explode( PHP_EOL, $htaccess );

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

            // Let's split up what we need to change
            $snippets_to_remove = isset( $enabled[ 'remove' ] ) ? $enabled[ 'remove' ] : [];
            $all_snippets_to_change = [];
            foreach ( $enabled as $snippet_keys ) {
                foreach ( $snippet_keys as $snippet_key ) {
                    $all_snippets_to_change[] = $snippet_key;
                }
            }

            // Cycle each snippet
            foreach ( $snippets as $snippet_key => $snippet ) {
            
                // Check if the snippet exists in the file
                $exists = $this->snippet_exists( $file_contents, $snippet );
                // if ( $exists ) {
                //     ddtt_print_r( $this->snippet_to_string( $snippet, '<br>' ) );
                // }

                // Enabled
                $changing = in_array( $snippet_key, $all_snippets_to_change ) ? true : false;
                // ddtt_print_r( $snippet_key.': '.$changing );

                // Does NOT exist
                // NOT enabled
                // SKIP, because we're not going to add it anyway
                if ( !$exists && !$changing ) {
                    continue;

                // Exists
                // REMOVE SNIPPETS REGARDLESS, because we're going to rewrite it all
                } elseif ( $exists ) {

                    // Get a string version
                    $line_string = $this->snippet_to_string( $snippet );

                    // Search the file lines
                    foreach( $safe_file_lines as $file_key => $safe_file_line ) {

                        // Does the snippet have an old label?
                        if ( isset( $snippet[ 'old_label' ] ) ) {
                            foreach ( $snippet[ 'old_label' ] as $old_label ) {
                                
                                // Check the file for the old comment line
                                if ( strpos( $safe_file_line, $old_label ) !== false ) {

                                    // Check for each line in the snippet
                                    for ( $sl = 1; $sl <= count( $snippet[ 'lines' ] ); $sl++ ) {
                                        
                                        // If the line below it is in the snippet, remove it
                                        if ( isset( $safe_file_lines[ $file_key + $sl ] ) && strpos( $line_string, $safe_file_lines[ $file_key + $sl ] ) !== false ) {
                                            unset( $safe_file_lines[ $file_key + $sl ] );
                                        }
                                    }

                                    // If there is a space directly below it, remove that too
                                    $end_of_snippet = $file_key + count( $snippet[ 'lines' ] );
                                    if ( isset( $safe_file_lines[ $end_of_snippet + 1 ] ) && strlen( $safe_file_lines[ $end_of_snippet + 1 ] ) >= 0 && empty( trim( $safe_file_lines[ $end_of_snippet + 1 ] ) ) ) {
                                        unset( $safe_file_lines[ $end_of_snippet + 1 ] );
                                    }

                                    // Lastly, remove the comment line
                                    unset( $safe_file_lines[ $file_key ] );
                                }
                            }

                        }

                        // Check the file for the current comment line
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
                            if ( isset( $safe_file_lines[ $end_of_snippet + 1 ] ) && strlen( $safe_file_lines[ $end_of_snippet + 1 ] ) >= 0 && empty( trim( $safe_file_lines[ $end_of_snippet + 1 ] ) ) ) {
                                unset( $safe_file_lines[ $end_of_snippet + 1 ] );
                            }

                            // Lastly, remove the comment line
                            unset( $safe_file_lines[ $file_key ] );
                        }
                    }

                    // Add the snippet if we are keeping it the way it is
                    if ( $exists && !in_array( $snippet_key, $all_snippets_to_change ) ) {
                        $add[ $snippet_key ] = $snippet;
                    }

                    // Add it if it is enabled
                    if ( $changing ) {
                        if ( !in_array( $snippet_key, $snippets_to_remove ) ) {
                            $add[ $snippet_key ] = $snippet;
                        }
                    }

                    // If it's not supposed to be there, just count this as an edit
                    if ( !$changing ) {
                        $edits++;
                    }

                // Does NOT exist
                // IS enabled
                // ADD SNIPPET
                } elseif ( !$exists && $changing ) {
                    // ddtt_print_r( $snippet );

                    // Add the snippet to the add bucket
                    if ( !in_array( $snippet_key, $snippets_to_remove ) ) {
                        $add[ $snippet_key ] = $snippet;
                    }

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

                // Count how many we are adding
                $adding = count( $add );

                // Check for previously added section
                $section_already_added = false;
                if ( ( false !== $added_by_key = array_search( $added_by_id, $safe_file_lines ) ) ) {
                    $section_already_added = $added_by_key;
    
                    // Remove the added_by comments only if we have nothing to add
                    if ( $adding == 0 ) {
                        
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
                }

                // Info at bottom
                $end = [];
                $end_id = '################# END OF '.strtoupper(DDTT_NAME).' ##################';
                $end[] = htmlentities( $end_id );

                // Remove the end comment
                if ( ( false !== $end_key = array_search( $end_id, $safe_file_lines ) ) ) {
                    // ddtt_print_r( $end_key );

                    // Remove the end comments only if we have nothing to add
                    if ( $adding == 0 ) {

                        // First remove the id key
                        unset( $safe_file_lines[ $end_key ] );
                    }
                }

                // Store converted snippets here
                $add_converted = [];

                // Cycle through the snippets we need to add
                if ( !empty( $add ) ) {
                    foreach ( $add as $a ) {

                        // Get the label
                        $add_converted[] = htmlentities( '# '.$a[ 'label' ] );

                        // Convert the snippet
                        foreach ( $a[ 'lines' ] as $aline ) {
                            $add_converted[] = htmlentities( $aline );
                        }
                        $add_converted[] = '';
                    }
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
                    if ( $section_already_added ) {
                        $index_to_add = $section_already_added + 3;
                        $safe_file_lines = array_merge(
                            array_slice( $safe_file_lines, 0, $index_to_add ),
                            $add_converted,
                            array_slice( $safe_file_lines, $index_to_add )
                        );
                    } else {
                        $safe_file_lines = array_merge( $safe_file_lines, $added_by, $add_converted, $end );
                    }
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

                    // File names
                    $old_file = str_replace( '.htaccess', '.htaccess-'.date( 'Y-m-d-H-i-s' ), $file );
                    $temp_file = str_replace( '.htaccess', '.htaccess-'.DDTT_GO_PF.'temp', $file );

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
            }

            // Stop the script
            return;
            
        // Otherwise say the file wasn't found
        } else {

            echo esc_html( $file ) . ' not found';
            return;
        }
    } // End rewrite()


    /**
     * Delete all backups added by our plugin except for most recent
     *
     * @return array|false
     */
    public function delete_backups() {
        // Get the backups
        $backups = ddtt_get_files( 'htaccess', '.htaccess' );

        // Make sure there are backups
        if ( !empty( $backups ) ) {

            // Reverse sort them
            rsort( $backups );

            // Store the filenames that were deleted
            $deleted = [];

            // Number them so we can skip the first one that is ours
            $delete = 0;

            // Iter the backups
            foreach ( $backups as $backup ) {

                // Remove the filepath
                $exp = explode( '/', $backup );
                $short = trim( array_pop( $exp ) );
                
                // Check if it's ours
                $pattern = '/.htaccess\-[0-9]{4}\-[0-9]{2}\-[0-9]{2}\-[0-9]{2}\-[0-9]{2}\-[0-9]{2}/';
                if ( preg_match( $pattern, $short ) ) {

                    // Get the date from the filename
                    // $date_string = str_replace( [ 'wp-config-', '.php' ], '', $short );
                    // $d = explode( '-', $date_string );
                    // $date = date( 'Y-m-d H:i:s', strtotime( $d[0].'-'.$d[1].'-'.$d[2].' '.$d[3].':'.$d[4].':'.$d[5] ) );
                    
                    // Skip the first one as it will always be the most recent
                    $delete++;
                    if ( $delete == 1 ) {
                        continue;
                    }

                    // Otherwise delete it
                    if ( file_exists( $backup ) && unlink( $backup ) ) {
                        $deleted[] = $short;
                    }
                }
            }

            // If we deleted some, let's return them
            if ( !empty( $deleted ) ) {
                return $deleted;
            }
        }

        // Else return false
        return false;
    } // delete_backups()


    /**
     * Enqueue scripts
     * Reminder to bump version number during testing to avoid caching
     *
     * @param string $screen
     * @return void
     */
    public function enqueue_scripts( $screen ) {
        // Get the options page slug
        $options_page = 'toplevel_page_'.DDTT_TEXTDOMAIN;

        // Allow for multisite
        if ( is_network_admin() ) {
            $options_page .= '-network';
        }

        // Are we on the options page?
        if ( $screen != $options_page ) {
            return;
        }

        // Handle
        $handle = DDTT_GO_PF.'htaccess_script';

        // Feedback form and error code checker
        if ( ddtt_get( 'tab', '==', 'htaccess' ) ) {
            wp_register_script( $handle, DDTT_PLUGIN_JS_PATH.'htaccess.js', [ 'jquery' ], time() );
            wp_enqueue_script( $handle );
            wp_enqueue_script( 'jquery' );
        }
    } // End enqueue_scripts()
}