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
     * Our snippets
     * NOTE: IF REMOVING A SNIPPET, DO NOT DELETE FROM ARRAY, INSTEAD ADD 'remove' => TRUE (LIKE THE FORCE_SSL_LOGIN OPTION BELOW)
     *
     * @return array
     */
    public function snippets() {
        // Maintenance link
        $maint_link = home_url( DDTT_ADMIN_URL.'/maint/repair.php' );

        // Add the snippets
        $snippets = apply_filters( 'ddtt_wpconfig_snippets', [
            'debug_mode' => [
                'label' => 'Enable WP_DEBUG Mode',
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'WP_DEBUG',
                        'value'    => TRUE
                    ]
                ],
                'desc'  => 'Triggers the "debug" mode throughout WordPress, causing all PHP errors, notices, and warnings to be displayed. It is not recommended to use <code>WP_DEBUG</code> or the other debug tools on live sites; they are meant for local testing and staging installs. If you need to enable them on your live site, though, be sure to prevent direct outside access to your <code>debug.log</code> from your <code>.htaccess</code> file by enabling the "Prevent Debug.log from Being Public" option on the <a href="'.ddtt_plugin_options_path( 'htaccess' ).'">htaccess</a> tab.'
            ],
            'debug_log' => [
                'label' => 'Enable Debug Logging to the /'.DDTT_CONTENT_URL.'/debug.log File',
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'WP_DEBUG_LOG',
                        'value'    => TRUE
                    ]
                ],
                'desc'  => 'A companion to <code>WP_DEBUG</code> that causes all errors to also be saved to your <code>debug.log</code> file.'
            ],
            'debug_display' => [
                'label' => 'Disable Display of Errors and Warnings',
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'WP_DEBUG_DISPLAY',
                        'value'    => FALSE
                    ],
                    [
                        'prefix'   => '@ini_set',
                        'variable' => 'display_errors',
                        'value'    => 0
                    ],
                ],
                'desc'  => 'Another companion to <code>WP_DEBUG</code> that controls whether debug messages are shown inside the HTML of pages or not. This should be used with <code>WP_DEBUG_LOG</code> so that errors can be reviewed later.'
            ],
            'db_query_log' => [
                'label' => 'Enable Database Query Logging (Use Temporarily - Slows Performance)',
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'SAVEQUERIES',
                        'value'    => TRUE
                    ]
                ],
                'desc'  => 'Saves the database queries to an array, and that array can be displayed to help analyze those queries. The constant defined as true causes each query to be saved, how long that query took to execute, and what function called it. NOTE: This will have a performance impact on your site, so make sure to turn this off when you aren\'t debugging.'
            ],
            'disable_cache' => [
                'label' => 'Disable WordPress Caching',
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'WP_CACHE',
                        'value'    => FALSE
                    ]
                ],
                'desc'  => 'The <code>WP_CACHE</code> constant is used to activate caching for your site, which can significantly reduce server load and improve your site\'s speed. This results in a smoother experience for your users. Temporarily disabling it can be helpful when updating your site so you can see real changes, rather than old cached versions.'
            ],
            'fatal_error_emails' => [
                'old_label' => 'Disable Fatal Error Emails to Admin',
                'label' => 'Disable Fatal Error Recovery Feature',
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'WP_DISABLE_FATAL_ERROR_HANDLER',
                        'value'    => TRUE
                    ]
                ],
                'desc'  => 'Disables the fatal error recovery feature. This feature ensures that fatal errors caused by plugins don\'t lock you out of your site. Instead, front-end users receive a "technical difficulties" message rather than encountering a white screen. Disabling this gives you more control over how fatal errors are handled on your site. Disabling it also prevents fatal error emails being sent to the Admin email.'
            ],
            'set_time_limit' => [
                'label' => 'Increase PHP Time Limit',
                'lines' => [
                    [
                        'prefix' => 'set_time_limit',
                        'value'  => 300
                    ],
                ],
                'desc'  => 'Allows you to adjust the maximum execution time for a specific operation. By default, PHP imposes a time limit on how long a script can run before it times out. If an operation exceeds this limit, PHP terminates it and returns a fatal error message, such as "Maximum execution time of xx seconds exceeded." This feature is essential for preventing infinite loops or excessively long processes that could impact server performance, so it should be used temporarily during testing only.'
            ],
            'memory_limit' => [
                'label' => 'Increase Memory Limit',
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'WP_MEMORY_LIMIT',
                        'value'    => '512M'
                    ],
                    [
                        'prefix'   => 'define',
                        'variable' => 'WP_MAX_MEMORY_LIMIT',
                        'value'    => '1024M'
                    ]
                ],
                'desc'  => 'The <code>WP_MEMORY_LIMIT</code> constant defines the memory limit for WordPress. It specifies the maximum amount of memory that WordPress can allocate during its execution. Increasing the memory limit can be beneficial for performance, especially if your site uses resource-intensive plugins or themes. It allows WordPress to handle larger data sets and complex operations more efficiently. The <code>WP_MAX_MEMORY_LIMIT</code> constant allows you to change the maximum memory limit specifically for certain WordPress functions. These constants only affect the memory allocation within WordPress itself. The actual PHP memory limit for your server is set separately (usually in your hosting environment or server configuration).'
            ],
            'upload_size' => [
                'old_label' => 'Temporarily Increase Upload Size (Use Temporarily / Must also Increase Memory Limit!)',
                'label' => 'Increase Upload Size',
                'lines' => [
                    [
                        'prefix'   => '@ini_set',
                        'variable' => 'upload_max_size',
                        'value'    => '256M'
                    ],
                    [
                        'prefix'   => '@ini_set',
                        'variable' => 'post_max_size',
                        'value'    => '256M'
                    ]
                ],
                'desc'  => 'The <code>upload_max_size</code> setting defines the maximum size for individual file uploads via HTTP POST requests. It specifically controls the size of files uploaded through forms (e.g., media files, images, documents) to your site. The <code>post_max_size</code> setting determines the maximum size of data that can be sent in an HTTP POST request. It includes not only file uploads but also other form data (e.g., form fields, variables). Remember that these settings impact the overall performance and resource usage of your site, so it is recommended to use this temporarily.'
            ],
            'enable_dev_scripts' => [
                'label' => 'Enable Development Versions of Core CSS and JS Files Instead of Minified Versions',
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'SCRIPT_DEBUG',
                        'value'    => TRUE
                    ]
                ],
                'desc'  => 'Forces WordPress to use the "dev" versions of core CSS and JavaScript files rather than the minified versions that are normally loaded. This is useful when you are testing modifications to any built-in <code>.js</code> or <code>.css</code> files.'
            ],
            'concat_scripts' => [
                'label' => 'Turn Off Concatenating Scripts (Use if there are jQuery/JS Issues)',
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'CONCATENATE_SCRIPTS',
                        'value'    => FALSE
                    ]
                ],
                'desc'  => 'Controls the concatenation and minification of JavaScript files and stylesheets used by your website. When set to true, WordPress combines multiple JavaScript and stylesheet files into a single file. This process reduces the number of HTTP requests made to the server when a user visits your website, resulting in faster load times. Disabling this can be helpful if there are jQuery/JS Issues, but again should used temporarily until you figure out the real problem.'
            ],
            'unfiltered_uploads' => [
                'label' => 'Allow Uploads of All File Types',
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'ALLOW_UNFILTERED_UPLOADS',
                        'value'    => TRUE
                    ]
                ],
                'desc'  => 'Allows you to bypass certain security filters applied to uploaded files. By default, WordPress applies strict filtering to uploaded files to prevent potential security risks, such as executing malicious code or scripts. Some plugins or custom functionality may require uploading files with specific extensions or formats that would otherwise be blocked by default filters. Enabling this allows developers to handle such cases. Just be caustious! Allowing unfiltered HTML or JavaScript uploads can lead to cross-site scripting (XSS) attacks. Malicious code embedded in uploaded files could compromise user sessions and site security.'
            ],
            'force_ssl_login' => [
                'label' => 'Ensure Login Credentials are Encrypted when Transmitting to Server',
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'FORCE_SSL_LOGIN',
                        'value'    => TRUE
                    ]
                ],
                'desc'   => '',
                'remove' => TRUE
            ],
            'force_ssl_admin' => [
                'label' => 'Ensure Sensetive Admin-area Info is Encrypted when Transmitting to Server',
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'FORCE_SSL_ADMIN',
                        'value'    => TRUE
                    ]
                ],
                'desc'  => 'Allows you to enforce secure (SSL) connections for both logins and admin sessions in the WordPress dashboard. It ensures that all interactions with the admin area occur over HTTPS. By using SSL (Secure Sockets Layer), data transmitted between the user’s browser and the server is encrypted, enhancing security and privacy.'
            ],
            'max_input_vars' => [
                'old_label' => 'Increase Max Input Vars to 7000',
                'label' => 'Increase Max Input Vars',
                'lines' => [
                    [
                        'prefix'   => '@ini_set',
                        'variable' => 'max_input_vars',
                        'value'    => 7000
                    ]
                ],
                'desc'  => 'Sets the maximum number of variables that the server can utilize for a single function. When a user submits a form (such as saving settings, updating posts, or using widgets), the data is sent to the server via POST requests.
                Each form field, checkbox, or other input element corresponds to a variable. The max_input_vars setting limits the total number of input variables that PHP processes during a single request. If the number of input variables exceeds this limit, some data may be truncated or ignored, potentially affecting functionality.'
            ],
            'fs_method' => [
                'label' => 'Force Direct Filesystem Method',
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'FS_METHOD',
                        'value'    => 'direct'
                    ]
                ],
                'desc'  => 'Determines the filesystem method that WordPress should use for reading, writing, modifying, or deleting files. The "Direct" method directly reads and writes files within PHP, which is efficient and preferred when possible. On well-configured hosts, the "Direct" method provides faster file I/O; however, it can open security vulnerabilities on poorly configured servers, so be cautious! If you\'re building a plugin or theme that requires direct file manipulation, you might enable this during development.'
            ],
            'disallow_file_edit' => [
                'label' => 'Disable Plugin/Theme Editors',
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'DISALLOW_FILE_EDIT',
                        'value'    => TRUE
                    ]
                ],
                'desc'  => 'Serves an essential security purpose. Prevents users (even administrators) from editing theme and plugin files directly within the WordPress dashboard. Specifically, it disables the "Theme Editor" and "Plugin Editor" links under "Appearance" and "Plugins" respectively. Allowing direct file editing within WordPress poses security risks. If an unauthorized user gains access to your admin area, they could inject malicious code into your theme or plugin files.'
            ],
            'disallow_file_mods' => [
                'label' => 'Disable Plugin and Theme Update and Installation',
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'DISALLOW_FILE_MODS',
                        'value'    => TRUE
                    ]
                ],
                'desc'  => 'Blocks users being able to use the plugin and theme installation/update functionality from the WordPress admin area. Setting this constant also disables the Plugin and Theme File editor (i.e. you don\'t need to set <code>DISALLOW_FILE_MODS</code> and <code>DISALLOW_FILE_EDIT</code>, as on its own <code>DISALLOW_FILE_MODS</code> will have the same effect).'
            ],
            'disable_wp_cron' => [
                'label' => 'Disable WP Cron Scheduler',
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'DISABLE_WP_CRON',
                        'value'    => TRUE
                    ]
                ],
                'desc'  => 'By default, WordPress uses its built-in scheduling system called <code>wp-cron</code> to perform time-sensitive tasks such as checking for updates, publishing scheduled posts, creating backups, sending emails, and more. However, <code>wp-cron</code> relies on user visits to your website. When someone accesses your site, WordPress checks for scheduled tasks. This approach works well for most sites but can cause issues for low-traffic or high-traffic sites. If your site has low traffic, scheduled tasks (such as publishing posts) may not occur precisely on time. On high-traffic sites, frequent checks by wp-cron can affect performance. To address these issues, you can disable <code>wp-cron</code> and set up a real cron job (run by your server\'s operating system) to handle scheduled tasks more reliably.'
            ],
            'site_url' => [
                'label' => 'Set Home and Site URL',
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'WP_HOME',
                        'value'    => home_url()
                    ],
                    [
                        'prefix'   => 'define',
                        'variable' => 'WP_SITEURL',
                        'value'    => home_url()
                    ]
                ],
                'desc'  => 'The <code>WP_HOME</code> constant represents the address where your WordPress core files reside. It defines the base URL for your entire WordPress installation. The <code>WP_SITEURL</code> constant specifies the URL where your WordPress site is accessible. It determines the location where WordPress serves content (such as posts, pages, and media).
                Unlike <code>WP_HOME</code>, which points to the base address, <code>WP_SITEURL</code> specifically defines the URL for content retrieval. When a user logs in, WordPress sets cookies containing session information. These cookies are tied to the site\'s URL. If these URLs are not defined or are mismatched, users may experience unexpected redirects or being logged out. It could also lead to cross-site request forgery (CSRF) vulnerabilities.'
            ],
            'autosave_interval' => [
                'label' => 'Modify AutoSave Interval',
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'AUTOSAVE_INTERVAL',
                        'value'    => 160
                    ]
                ],
                'desc'  => 'When editing a post, WordPress uses Ajax to auto-save revisions to the post as you edit. You may want to increase this setting for longer delays in between auto-saves, or decrease the setting to make sure you never lose changes. The default is 60 seconds.'
            ],
            'post_revisions' => [
                'label' => 'Disable Post Revisions',
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'WP_POST_REVISIONS',
                        'value'    => FALSE
                    ]
                ],
                'desc'  => 'When editing a post, WordPress uses Ajax to auto-save revisions to the post as you edit every 60 seconds. You may want to disable this during testing so you don\'t have a ton of revisions saved to your database.'
            ],
            'empty_trash' => [
                'label' => 'Disable Trash',
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'EMPTY_TRASH_DAYS',
                        'value'    => 0
                    ]
                ],
                'desc'  => 'This constant controls the number of days before WordPress permanently deletes posts, pages, attachments, and comments, from the trash bin. We set it to zero to disable trash completely.'
            ],
            'block_external_http' => [
                'label' => 'Block External URL Requests',
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'WP_HTTP_BLOCK_EXTERNAL',
                        'value'    => TRUE
                    ],
                    [
                        'prefix'   => 'define',
                        'variable' => 'WP_ACCESSIBLE_HOSTS',
                        'value'    => 'api.wordpress.org,*.github.com'
                    ]
                ],
                'desc'  => 'Only allows localhost and your blog to make requests. The constant <code>WP_ACCESSIBLE_HOSTS</code> will allow additional hosts to go through for requests. The format of the <code>WP_ACCESSIBLE_HOSTS</code> constant is a comma separated list of hostnames to allow, wildcard domains are supported, eg *.wordpress.org will allow for all subdomains of wordpress.org to be contacted.'
            ],
            'auto-update' => [
                'label' => 'Disable WordPress Auto Updates',
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'AUTOMATIC_UPDATER_DISABLED',
                        'value'    => TRUE
                    ]
                ],
                'desc'  => 'There might be reason for a site to not auto-update, such as customizations or host supplied updates. It can also be done before a major release to allow time for testing on a development or staging environment before allowing the update on a production site.'
            ],
            'core-updates' => [
                'label' => 'Disable WordPress Core Updates',
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'WP_AUTO_UPDATE_CORE',
                        'value'    => FALSE
                    ]
                ],
                'desc'  => 'Disables core updates completely.'
            ],
            'cleanup_image_edits' => [
                'label' => 'Cleanup Image Edits',
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'IMAGE_EDIT_OVERWRITE',
                        'value'    => TRUE
                    ]
                ],
                'desc'  => 'By default, WordPress creates a new set of images every time you edit an image and when you restore the original, it leaves all the edits on the server. Defining <code>IMAGE_EDIT_OVERWRITE</code> as true changes this behaviour. Only one set of image edits are ever created and when you restore the original, the edits are removed from the server.'
            ],
            'auto_db_repair' => [
                'label' => 'Allow Automatic Database Repair',
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'WP_ALLOW_REPAIR',
                        'value'    => TRUE
                    ]
                ],
                'desc'  => 'WordPress comes with a built-in feature to automatically optimize and repair your WordPress database. To use it, enable this option then visit the following URL: <a href="'.$maint_link.'" target="_blank">'.$maint_link.'</a>. You will see a simple page with the options to "repair" or "repair and optimize" the database. You don\'t need to be logged in to access this page. <strong>Don\'t forget to disable this option immediately after repairing the database!</strong>'
            ],
        ] );
        return $snippets;
    } // End snippets()


    /**
     * Table row for WPCONFIG checkboxes
     *
     * @param string $name
     * @param string $label
     * @param boolean $snippet_exists
     * @param array $line_strings_1
     * @param array $line_strings_0
     * @param string $description
     * @return string
     */
    public function options_tr( $name, $label, $snippet_exists, $line_strings_1, $line_strings_0, $description ) {
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
            $lines[] = '<span class="line-exists true">'.$line_1.';</span>';
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
            <td><div class="snippet_container">'.implode( '<br>', $lines ).'</div>
                <div class="field-desc">'.$description.'</div></td>
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
                
            } else {
                
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
        } else {
            $snippet_exists = false;
        }

        // Return the results
        return [
            'exists'    => $snippet_exists,
            'partial'   => $partial,
            'lines'     => [
                'true'  => $lines_1,
                'false' => $lines_0
            ],
            'strings'   => [
                'true'  => $line_strings_1,
                'false' => $line_strings_0
            ],
            'found'     => $found
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
                $safe_file_lines[] =  htmlentities( $file_line, ENT_NOQUOTES );
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

                            // Sanitize the line
                            $safe_file_line = sanitize_text_field( $safe_file_line );
                            
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

            // dpr( $safe_file_lines );

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


    /**
     * Delete all backups added by our plugin except for most recent
     *
     * @return array|false
     */
    public function delete_backups() {
        // Get the backups
        $backups = ddtt_get_files( 'wp-config', 'wp-config.php' );

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
                $pattern = '/wp\-config\-[0-9]{4}\-[0-9]{2}\-[0-9]{2}\-[0-9]{2}\-[0-9]{2}\-[0-9]{2}.php/';
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
}