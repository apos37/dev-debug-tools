<?php
/**
 * WP Config
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class WpConfig {


    /**
     * Our snippets
     *
     * @return array
     */
    public static function snippets() : array {
        // Maintenance link
        $maintenance_link = admin_url( 'maint/repair.php' );

        // Add the snippets
        $snippets = [
            'debug_mode' => [
                'label' => __( 'Enable WP_DEBUG Mode', 'dev-debug-tools' ),
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'WP_DEBUG',
                        'value'    => TRUE
                    ]
                ],
                'desc'  => sprintf(
                    // translators: %1$s: .htaccess tab URL
                    __( 'Triggers the "debug" mode throughout WordPress, causing all PHP errors, notices, and warnings to be displayed. If you need to enable debugging on a live site, be sure to prevent direct outside access to your <code>debug.log</code> from your <code>.htaccess</code> file by enabling the "Prevent Debug.log from Being Public" option on the <a href="%1$s">htaccess</a> tab.', 'dev-debug-tools' ),
                    Bootstrap::tool_url( 'htaccess' )
                ),
            ],
            'debug_log' => [
                'label' => __( 'Enable Debug Logging to the debug.log File', 'dev-debug-tools' ),
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'WP_DEBUG_LOG',
                        'value'    => TRUE
                    ]
                ],
                'desc'  => __( 'A companion to <code>WP_DEBUG</code> that causes all errors to also be saved to your <code>debug.log</code> file.', 'dev-debug-tools' )
            ],
            'debug_display' => [
                'label' => __( 'Disable Display of Errors and Warnings', 'dev-debug-tools' ),
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
                'desc'  => __( 'Another companion to <code>WP_DEBUG</code> that controls whether debug messages are shown inside the HTML of pages or not. This should be used with <code>WP_DEBUG_LOG</code> so that errors can be reviewed later.', 'dev-debug-tools' )
            ],
            'db_query_log' => [
                'label' => __( 'Enable Database Query Logging (Use Temporarily - Slows Performance)', 'dev-debug-tools' ),
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'SAVEQUERIES',
                        'value'    => TRUE
                    ]
                ],
                'desc'  => __( 'Saves the database queries to an array, and that array can be displayed to help analyze those queries. The constant defined as true causes each query to be saved, how long that query took to execute, and what function called it. NOTE: This will have a performance impact on your site, so make sure to turn this off when you aren\'t debugging.', 'dev-debug-tools' )
            ],
            'disable_cache' => [
                'label' => __( 'Disable WordPress Caching', 'dev-debug-tools' ),
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'WP_CACHE',
                        'value'    => FALSE
                    ]
                ],
                'desc'  => __( 'The <code>WP_CACHE</code> constant is used to activate caching for your site, which can significantly reduce server load and improve your site\'s speed. This results in a smoother experience for your users. Temporarily disabling it can be helpful when updating your site so you can see real changes, rather than old cached versions.', 'dev-debug-tools' )
            ],
            'fatal_error_emails' => [
                'label' => __( 'Disable Fatal Error Recovery Feature', 'dev-debug-tools' ),
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'WP_DISABLE_FATAL_ERROR_HANDLER',
                        'value'    => TRUE
                    ]
                ],
                'desc'  => __( 'Disables the fatal error recovery feature. This feature ensures that fatal errors caused by plugins don\'t lock you out of your site. Instead, front-end users receive a "technical difficulties" message rather than encountering a white screen. Disabling this gives you more control over how fatal errors are handled on your site. Disabling it also prevents fatal error emails being sent to the Admin email.', 'dev-debug-tools' )
            ],
            'set_time_limit' => [
                'label' => __( 'Increase PHP Time Limit', 'dev-debug-tools' ),
                'lines' => [
                    [
                        'prefix' => 'set_time_limit',
                        'value'  => 300
                    ],
                ],
                'desc'  => __( 'Allows you to adjust the maximum execution time for a specific operation. By default, PHP imposes a time limit on how long a script can run before it times out. If an operation exceeds this limit, PHP terminates it and returns a fatal error message, such as "Maximum execution time of xx seconds exceeded." This feature is essential for preventing infinite loops or excessively long processes that could impact server performance, so it should be used temporarily during testing only.', 'dev-debug-tools' )
            ],
            'memory_limit' => [
                'label' => __( 'Increase Memory Limit', 'dev-debug-tools' ),
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
                'desc'  => __( 'The <code>WP_MEMORY_LIMIT</code> constant defines the memory limit for WordPress. It specifies the maximum amount of memory that WordPress can allocate during its execution. Increasing the memory limit can be beneficial for performance, especially if your site uses resource-intensive plugins or themes. It allows WordPress to handle larger data sets and complex operations more efficiently. The <code>WP_MAX_MEMORY_LIMIT</code> constant allows you to change the maximum memory limit specifically for certain WordPress functions. These constants only affect the memory allocation within WordPress itself. The actual PHP memory limit for your server is set separately (usually in your hosting environment or server configuration).', 'dev-debug-tools' )
            ],
            'upload_size' => [
                'label' => __( 'Increase Upload Size', 'dev-debug-tools' ),
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
                'desc'  => __( 'The <code>upload_max_size</code> setting defines the maximum size for individual file uploads via HTTP POST requests. It specifically controls the size of files uploaded through forms (e.g., media files, images, documents) to your site. The <code>post_max_size</code> setting determines the maximum size of data that can be sent in an HTTP POST request. It includes not only file uploads but also other form data (e.g., form fields, variables). Remember that these settings impact the overall performance and resource usage of your site, so it is recommended to use this temporarily.', 'dev-debug-tools' )
            ],
            'enable_dev_scripts' => [
                'label' => __( 'Enable Development Versions of Core CSS and JS Files Instead of Minified Versions', 'dev-debug-tools' ),
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'SCRIPT_DEBUG',
                        'value'    => TRUE
                    ]
                ],
                'desc'  => __( 'Forces WordPress to use the "dev" versions of core CSS and JavaScript files rather than the minified versions that are normally loaded. This is useful when you are testing modifications to any built-in <code>.js</code> or <code>.css</code> files.', 'dev-debug-tools' )
            ],
            'concat_scripts' => [
                'label' => __( 'Turn Off Concatenating Scripts (Use if there are jQuery/JS Issues)', 'dev-debug-tools' ),
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'CONCATENATE_SCRIPTS',
                        'value'    => FALSE
                    ]
                ],
                'desc'  => __( 'Controls the concatenation and minification of JavaScript files and stylesheets used by your website. When set to true, WordPress combines multiple JavaScript and stylesheet files into a single file. This process reduces the number of HTTP requests made to the server when a user visits your website, resulting in faster load times. Disabling this can be helpful if there are jQuery/JS Issues, but again should used temporarily until you figure out the real problem.', 'dev-debug-tools' )
            ],
            'unfiltered_uploads' => [
                'label' => __( 'Allow Uploads of All File Types', 'dev-debug-tools' ),
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'ALLOW_UNFILTERED_UPLOADS', // Cannot suppress warning
                        'value'    => TRUE
                    ]
                ],
                'desc'  => __( 'Allows you to bypass certain security filters applied to uploaded files. By default, WordPress applies strict filtering to uploaded files to prevent potential security risks, such as executing malicious code or scripts. Some plugins or custom functionality may require uploading files with specific extensions or formats that would otherwise be blocked by default filters. Enabling this allows developers to handle such cases. Just be caustious! Allowing unfiltered HTML or JavaScript uploads can lead to cross-site scripting (XSS) attacks. Malicious code embedded in uploaded files could compromise user sessions and site security.', 'dev-debug-tools' )
            ],
            'force_ssl_login' => [
                'label' => __( 'Ensure Login Credentials are Encrypted when Transmitting to Server', 'dev-debug-tools' ),
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'FORCE_SSL_LOGIN',
                        'value'    => TRUE
                    ]
                ],
                'desc'   => __( 'Ensures that the login credentials are transmitted securely over HTTPS. This is particularly important for protecting sensitive information, such as usernames and passwords, during the login process. When this constant is set to true, WordPress forces the use of SSL (Secure Sockets Layer) for the login page and any subsequent requests made during the login session. It helps prevent eavesdropping and man-in-the-middle attacks.', 'dev-debug-tools' ),
                'remove' => TRUE
            ],
            'force_ssl_admin' => [
                'label' => __( 'Ensure Sensitive Admin-area Info is Encrypted when Transmitting to Server', 'dev-debug-tools' ),
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'FORCE_SSL_ADMIN',
                        'value'    => TRUE
                    ]
                ],
                'desc'  => __( 'Allows you to enforce secure (SSL) connections for both logins and admin sessions in the WordPress dashboard. It ensures that all interactions with the admin area occur over HTTPS. By using SSL (Secure Sockets Layer), data transmitted between the user’s browser and the server is encrypted, enhancing security and privacy.', 'dev-debug-tools' )
            ],
            'max_input_vars' => [
                'old_label' => 'Increase Max Input Vars to 7000',
                'label' => __( 'Increase Max Input Vars', 'dev-debug-tools' ),
                'lines' => [
                    [
                        'prefix'   => '@ini_set',
                        'variable' => 'max_input_vars',
                        'value'    => 7000
                    ]
                ],
                'desc'  => __( 'Sets the maximum number of variables that the server can utilize for a single function. When a user submits a form (such as saving settings, updating posts, or using widgets), the data is sent to the server via POST requests. Each form field, checkbox, or other input element corresponds to a variable. The max_input_vars setting limits the total number of input variables that PHP processes during a single request. If the number of input variables exceeds this limit, some data may be truncated or ignored, potentially affecting functionality.', 'dev-debug-tools' )
            ],
            'fs_method' => [
                'label' => __( 'Force Direct Filesystem Method', 'dev-debug-tools' ),
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'FS_METHOD',
                        'value'    => 'direct'
                    ]
                ],
                'desc'  => __( 'Determines the filesystem method that WordPress should use for reading, writing, modifying, or deleting files. The "Direct" method directly reads and writes files within PHP, which is efficient and preferred when possible. On well-configured hosts, the "Direct" method provides faster file I/O; however, it can open security vulnerabilities on poorly configured servers, so be cautious! If you\'re building a plugin or theme that requires direct file manipulation, you might enable this during development.', 'dev-debug-tools' )
            ],
            'disallow_file_edit' => [
                'label' => __( 'Disable Plugin/Theme Editors', 'dev-debug-tools' ),
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'DISALLOW_FILE_EDIT',
                        'value'    => TRUE
                    ]
                ],
                'desc'  => __( 'Serves an essential security purpose. Prevents users (even administrators) from editing theme and plugin files directly within the WordPress dashboard. Specifically, it disables the "Theme Editor" and "Plugin Editor" links under "Appearance" and "Plugins" respectively. Allowing direct file editing within WordPress poses security risks. If an unauthorized user gains access to your admin area, they could inject malicious code into your theme or plugin files.', 'dev-debug-tools' )
            ],
            'disallow_file_mods' => [
                'label' => __( 'Disable Plugin and Theme Update and Installation', 'dev-debug-tools' ),
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'DISALLOW_FILE_MODS',
                        'value'    => TRUE
                    ]
                ],
                'desc'  => __( 'Blocks users being able to use the plugin and theme installation/update functionality from the WordPress admin area. Setting this constant also disables the Plugin and Theme File editor (i.e. you don\'t need to set <code>DISALLOW_FILE_MODS</code> and <code>DISALLOW_FILE_EDIT</code>, as on its own <code>DISALLOW_FILE_MODS</code> will have the same effect).', 'dev-debug-tools' )
            ],
            'disable_wp_cron' => [
                'label' => __( 'Disable WP Cron Scheduler', 'dev-debug-tools' ),
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'DISABLE_WP_CRON',
                        'value'    => TRUE
                    ]
                ],
                'desc'  => __( 'By default, WordPress uses its built-in scheduling system called <code>wp-cron</code> to perform time-sensitive tasks such as checking for updates, publishing scheduled posts, creating backups, sending emails, and more. However, <code>wp-cron</code> relies on user visits to your website. When someone accesses your site, WordPress checks for scheduled tasks. This approach works well for most sites but can cause issues for low-traffic or high-traffic sites. If your site has low traffic, scheduled tasks (such as publishing posts) may not occur precisely on time. On high-traffic sites, frequent checks by wp-cron can affect performance. To address these issues, you can disable <code>wp-cron</code> and set up a real cron job (run by your server\'s operating system) to handle scheduled tasks more reliably.', 'dev-debug-tools' )
            ],
            'site_url' => [
                'label' => __( 'Set Home and Site URL', 'dev-debug-tools' ),
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
                'desc'  => __( 'The <code>WP_HOME</code> constant represents the address where your WordPress core files reside. It defines the base URL for your entire WordPress installation. The <code>WP_SITEURL</code> constant specifies the URL where your WordPress site is accessible. It determines the location where WordPress serves content (such as posts, pages, and media). Unlike <code>WP_HOME</code>, which points to the base address, <code>WP_SITEURL</code> specifically defines the URL for content retrieval. When a user logs in, WordPress sets cookies containing session information. These cookies are tied to the site\'s URL. If these URLs are not defined or are mismatched, users may experience unexpected redirects or being logged out. It could also lead to cross-site request forgery (CSRF) vulnerabilities.', 'dev-debug-tools' )
            ],
            'autosave_interval' => [
                'label' => __( 'Modify AutoSave Interval', 'dev-debug-tools' ),
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'AUTOSAVE_INTERVAL',
                        'value'    => 160
                    ]
                ],
                'desc'  => __( 'When editing a post, WordPress uses Ajax to auto-save revisions to the post as you edit. You may want to increase this setting for longer delays in between auto-saves, or decrease the setting to make sure you never lose changes. The default is 60 seconds.', 'dev-debug-tools' )
            ],
            'post_revisions' => [
                'label' => __( 'Disable Post Revisions', 'dev-debug-tools' ),
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'WP_POST_REVISIONS',
                        'value'    => FALSE
                    ]
                ],
                'desc'  => __( 'When editing a post, WordPress uses Ajax to auto-save revisions to the post as you edit every 60 seconds. You may want to disable this during testing so you don\'t have a ton of revisions saved to your database.', 'dev-debug-tools' )
            ],
            'empty_trash' => [
                'label' => __( 'Disable Trash', 'dev-debug-tools' ),
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'EMPTY_TRASH_DAYS',
                        'value'    => 0
                    ]
                ],
                'desc'  => __( 'This constant controls the number of days before WordPress permanently deletes posts, pages, attachments, and comments, from the trash bin. We set it to zero to disable trash completely.', 'dev-debug-tools' )
            ],
            'block_external_http' => [
                'label' => __( 'Block External URL Requests', 'dev-debug-tools' ),
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
                'desc'  => __( 'Only allows localhost and your blog to make requests. The constant <code>WP_ACCESSIBLE_HOSTS</code> will allow additional hosts to go through for requests. The format of the <code>WP_ACCESSIBLE_HOSTS</code> constant is a comma separated list of hostnames to allow, wildcard domains are supported, eg *.wordpress.org will allow for all subdomains of wordpress.org to be contacted.', 'dev-debug-tools' )
            ],
            'auto-update' => [
                'label' => __( 'Disable WordPress Auto Updates', 'dev-debug-tools' ),
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'AUTOMATIC_UPDATER_DISABLED',
                        'value'    => TRUE
                    ]
                ],
                'desc'  => __( 'There might be reason for a site to not auto-update, such as customizations or host supplied updates. It can also be done before a major release to allow time for testing on a development or staging environment before allowing the update on a production site.', 'dev-debug-tools' )
            ],
            'core-updates' => [
                'label' => __( 'Disable WordPress Core Updates', 'dev-debug-tools' ),
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'WP_AUTO_UPDATE_CORE',
                        'value'    => FALSE
                    ]
                ],
                'desc'  => __( 'Disables core updates completely.', 'dev-debug-tools' )
            ],
            'cleanup_image_edits' => [
                'label' => __( 'Cleanup Image Edits', 'dev-debug-tools' ),
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'IMAGE_EDIT_OVERWRITE',
                        'value'    => TRUE
                    ]
                ],
                'desc'  => __( 'By default, WordPress creates a new set of images every time you edit an image and when you restore the original, it leaves all the edits on the server. Defining <code>IMAGE_EDIT_OVERWRITE</code> as true changes this behaviour. Only one set of image edits are ever created and when you restore the original, the edits are removed from the server.', 'dev-debug-tools' )
            ],
            'auto_db_repair' => [
                'label' => __( 'Allow Automatic Database Repair', 'dev-debug-tools' ),
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'WP_ALLOW_REPAIR',
                        'value'    => TRUE
                    ]
                ],
                'desc'  => sprintf(
                    // translators: %1$s: database repair URL
                    __( 'WordPress comes with a built-in feature to automatically optimize and repair your WordPress database. To use it, enable this option then visit the following URL: <a href="%1$s" target="_blank">%1$s</a>. You will see a simple page with the options to "repair" or "repair and optimize" the database. You don\'t need to be logged in to access this page. <strong>Don\'t forget to disable this option immediately after repairing the database!</strong>', 'dev-debug-tools' ),
                    $maintenance_link
                ),
            ],
            'environment' => [
                'label' => __( 'Set Environment Type', 'dev-debug-tools' ),
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'WP_ENVIRONMENT_TYPE',
                        'value'    => 'production'
                    ]
                ],
                'desc'  => __( 'This constant allows you to specify whether your site is running in a <code class="hl">development</code>, <code class="hl">staging</code>, or <code class="hl">production</code> environment. Some developers and setups might also use <code class="hl">local</code> as an alternative value to <code class="hl">development</code>. This helps plugins, themes, and developers to adjust their behavior or settings based on the environment.', 'dev-debug-tools' )
            ],
            'woocommerce' => [
                'label' => __( 'Remove WooCommerce Data (Add Before Deactivating)', 'dev-debug-tools' ),
                'lines' => [
                    [
                        'prefix'   => 'define',
                        'variable' => 'WC_REMOVE_ALL_DATA',
                        'value'    => TRUE
                    ]
                ],
                'desc'  => __( 'WooCommerce leaves database tables after deactivation and uninstallation, so if you want to completely remove everything, add this to your config file, then deactivate and uninstall. Then you can remove this from your config file as it will no longer be needed. If you already uninstalled, you will have to reinstall and activate and then make sure this is added prior to deactivating and uninstalling again.', 'dev-debug-tools' )
            ],
        ];


        /**
         * Apply filters to the snippets.
         */
        $snippets = apply_filters( 'ddtt_wpconfig_snippets', $snippets );

        return $snippets;
    } // End snippets()


    /**
     * Sanitize a single snippet array.
     *
     * @param array $snippet Snippet data to sanitize.
     * @return array
     */
    public static function sanitize_existing_snippet( $snippet ) : array {
        if ( ! isset( $snippet[ 'lines' ] ) || ! is_array( $snippet[ 'lines' ] ) ) {
            return [
                'label' => '',
                'desc'  => '',
                'lines' => [],
                'added' => false
            ];
        }

        $sanitized_lines = array_map( function( $line ) {
            $prefix   = isset( $line[ 'prefix' ] ) ? sanitize_text_field( $line[ 'prefix' ] ) : '';
            $variable = isset( $line[ 'variable' ] ) ? sanitize_text_field( $line[ 'variable' ] ) : '';
            $value    = $line[ 'value' ] ?? null;

            // Normalize booleans
            if ( is_string( $value ) && preg_match( '/^(true|false)$/i', $value ) ) {
                $value = strtolower( $value ) === 'true';
            }
            // Normalize numbers
            elseif ( is_numeric( $value ) ) {
                $value = $value + 0;
            }

            return [
                'prefix'   => $prefix,
                'variable' => $variable,
                'value'    => $value,
            ];
        }, $snippet[ 'lines' ] );

        $added = false;
        if ( isset( $snippet[ 'added' ] ) && is_array( $snippet[ 'added' ] ) ) {
            $author = isset( $snippet[ 'added' ][ 'author' ] ) ? intval( $snippet[ 'added' ][ 'author' ] ) : 0;
            $date   = isset( $snippet[ 'added' ][ 'date' ] ) ? sanitize_text_field( $snippet[ 'added' ][ 'date' ] ) : '';
            $added  = [
                'author' => $author,
                'date'   => $date,
            ];
        }

        return [
            'label' => isset( $snippet[ 'label' ] ) ? sanitize_text_field( $snippet[ 'label' ] ) : '',
            'desc'  => isset( $snippet[ 'desc' ] ) ? wp_kses_post( $snippet[ 'desc' ] ) : '',
            'lines' => $sanitized_lines,
            'added' => $added
        ];
    } // End sanitize_existing_snippet()


    /**
     * Sanitize new snippet lines.
     *
     * @param array $lines The snippet lines
     * @return array
     */
    public static function sanitize_new_snippet_lines( $lines ) : array {
        $lines = array_map( function( $line ) {
            $prefix   = sanitize_text_field( $line[ 'prefix' ] );
            $variable = sanitize_text_field( $line[ 'variable' ] );
            $value    = trim( $line[ 'value' ] );

            // Normalize booleans
            if ( preg_match( '/^(true|false)$/i', $value ) ) {
                $value = strtolower( $value ) === 'true';
            }
            // Normalize numbers
            elseif ( is_numeric( $value ) ) {
                $value = $value + 0; // cast to int or float automatically
            }
            // Otherwise treat as string
            else {
                $value = sanitize_text_field( $value );
            }

            return [
                'prefix'   => $prefix,
                'variable' => $variable,
                'value'    => $value,
            ];
        }, $lines );

        return $lines;
    } // End sanitize_new_snippet_lines()


    /**
     * Create a unique key from the snippet
     *
     * @param array $snippet The snippet data.
     * @return string
     */
    public static function create_key_from_snippet( $snippet ) : string {
        return $snippet[ 'lines' ][ 0 ][ 'variable' ];
    } // End create_key_from_snippet()


    /**
     * Check if a snippet already exists.
     *
     * @param array $snippets The existing snippets.
     * @param array $lines    The lines to check.
     * @return string|false
     */
    public static function does_snippet_key_exist( $snippets, $snippet ) {
        $keys = [];
        foreach ( $snippets as $existing ) {
            if ( ! empty( $existing[ 'lines' ] ) && is_array( $existing[ 'lines' ] ) ) {
                foreach ( $existing[ 'lines' ] as $line ) {
                    if ( ! empty( $line[ 'variable' ] ) ) {
                        $keys[] = $line[ 'variable' ];
                    }
                }
            }
        }

        // Check if it exists
        foreach ( $snippet[ 'lines' ] as $line ) {
            if ( in_array( $line[ 'variable' ], $keys, true ) ) {
                return $line[ 'variable' ];
            }
        }
        return false;
    } // End does_snippet_key_exist()


    /**
     * Check if a snippet exists in the content.
     *
     * @param string $content The content to check.
     * @param array  $snippet The snippet.
     * @return bool
     */
    public static function does_snippet_exist_in_content( $content, $snippet ) {
        $key = self::create_key_from_snippet( $snippet );
        return strpos( $content, $key ) !== false;
    } // End does_snippet_exist_in_content()


    /**
     * Extract snippets from the raw contents.
     *
     * @param string $raw_contents The raw contents to extract snippets from.
     * @return array
     */
    public static function extract_snippets_from_content( $raw_contents ) {
        // Extract current defines and ini_set calls
        preg_match_all(
            '/(define\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*(.+?)\s*\)|@ini_set\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*(.+?)\s*\))\s*;/',
            $raw_contents,
            $matches,
            PREG_SET_ORDER
        );

        $current_variables = [];
        foreach ( $matches as $match ) {
            if ( ! empty( $match[2] ) ) {
                // define()
                $current_variables[ $match[2] ] = [
                    'code'  => trim( $match[1] ),
                    'value' => trim( $match[3] ),
                    'type'  => 'define',
                ];
            } elseif ( ! empty( $match[4] ) ) {
                // @ini_set()
                $current_variables[ $match[4] ] = [
                    'code'  => trim( $match[1] ),
                    'value' => trim( $match[5] ),
                    'type'  => '@ini_set',
                ];
            }
        }

        return $current_variables;
    } // End extract_snippets_from_content()


    /**
     * Get the current code lines for a specific line.
     *
     * @param array $lines The line data.
     * @return array
     */
    public static function get_current_code_lines( $lines, $current_blocks ) : array {
        $detected = true;
        $current_code = [];

        foreach ( $lines as $line ) {
            $prefix   = $line[ 'prefix' ] ?? '';
            $variable = $line[ 'variable' ] ?? '';
            $value    = $line[ 'value' ] ?? null;

            if ( is_string( $value ) ) {
                $lower = strtolower( trim( $value ) );
                if ( $lower === 'true' ) {
                    $display_value = 'true';   // literal boolean string
                } elseif ( $lower === 'false' ) {
                    $display_value = 'false';  // literal boolean string
                } else {
                    $display_value = "'" . $value . "'"; // wrap other strings in quotes
                }
            } elseif ( is_bool( $value ) ) {
                $display_value = $value ? 'true' : 'false';
            } else {
                $display_value = $value; // numeric values
            }

            if ( isset( $current_blocks[ $variable ] ) ) {
                $current_code[] = $current_blocks[ $variable ][ 'code' ] . ';';
            } else {
                $detected = false;
                if ( in_array( $prefix, [ 'define', '@ini_set' ], true ) && $variable !== '' ) {
                    $current_code[] = $prefix . "( '" . $variable . "', " . $display_value . " );";
                } else {
                    $current_code[] = $prefix . "( " . $display_value . " );";
                }
            }
        }

        return [
            'detected' => $detected,
            'code'     => $current_code
        ];
    } // End get_current_code_lines()


    /**
     * Get the core constants.
     *
     * @return array
     */
    public static function core_constants( $incl_abspath = false ) : array {
        $core_constants = [
            'DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_HOST', 'DB_CHARSET', 'DB_COLLATE', 
            'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 
            'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT'
        ];

        if ( $incl_abspath ) {
            $core_constants[] = 'ABSPATH';
        }

        return $core_constants;
    } // End core_constants()


    /**
     * Redact sensitive content in the raw file contents.
     *
     * @param string $raw_contents The raw file contents.
     * @return string
     */
    public static function redact_content( $raw_contents ) {
        $globals = self::core_constants();
        $globals = array_merge( $globals, [ 'DB_HOST_SLAVE', 'WP_CACHE_KEY_SALT', 'WPE_APIKEY' ] );

        $sensitive_values = [];

        foreach ( $globals as $global ) {
            $pattern = '/(define\s*\(\s*[\'"]' . preg_quote( $global, '/' ) . '[\'"]\s*,\s*)(\'(.*?)\')(\s*\))/s';
            $raw_contents = preg_replace_callback( $pattern, function( $matches ) use ( &$sensitive_values, $global ) {
                $sensitive_values[ $global ] = $matches[3];
                return $matches[1] . '__DDTT_REDACT__' . $global . '__' . $matches[4];
            }, $raw_contents );
        }

        return [
            'raw_contents'     => $raw_contents,
            'sensitive_values' => $sensitive_values
        ];
    } // End redact_content()


    /**
     * Unredact sensitive content in the raw file contents.
     *
     * @param string $highlighted_content The highlighted content.
     * @param array $sensitive_values The sensitive values to unredact.
     * @return string
     */
    public static function unredact_content( $highlighted_content, $sensitive_values ) {
        foreach ( $sensitive_values as $constant => $value ) {
            $highlighted_content = str_replace(
                "__DDTT_REDACT__" . $constant . "__",
                '<i class="ddtt-redact">' . esc_html( $value ) . '</i>',
                $highlighted_content
            );
        }
        return $highlighted_content;
    } // End unredact_content()


    /**
     * Validate the content string and return an array of errors.
     *
     * @param string $content The file content.
     * @param string $temp_file The temporary file path.
     *
     * @return array
     */
    public static function validate_file( $content, $temp_file ) : array {
        $errors = [];

        // --- 1. Minimum required check ---
        $required_constants = self::core_constants();
        $missing = [];
        foreach ( $required_constants as $constant ) {
            if ( stripos( $content, $constant ) === false ) {
                $missing[] = $constant;
            }
        }
        if ( ! empty( $missing ) ) {
            $errors[] = __( 'Missing required items: ', 'dev-debug-tools' ) . ' <code>' . implode( '</code>, <code>', $missing ) . '</code>';
        }

        // --- 2. Required ABSPATH check ---
        if ( ! preg_match( '/define\(\s*[\'"]ABSPATH[\'"]\s*,/', $content ) ) {
            $errors[] = __( 'Missing ABSPATH definition.', 'dev-debug-tools' );
        }

        // --- 3. Required table prefix check ---
        if ( ! preg_match( '/\$table_prefix\s*=\s*[\'"][^\'"]+[\'"]\s*;/', $content ) ) {
            $errors[] = __( 'Missing or invalid $table_prefix definition.', 'dev-debug-tools' );
        }

        // --- 4. Requiring once wp-settings.php ---
        if ( ! preg_match( '/require_once\s*\(\s*ABSPATH\s*\.\s*[\'"]wp-settings\.php[\'"]\s*\)\s*;/', $content ) ) {
            $errors[] = __( 'Missing require_once for wp-settings.php.', 'dev-debug-tools' );
        }

        // --- 5. Direct execution check ---
        $output     = null;
        $return_var = null;
        exec( "php " . escapeshellarg( $temp_file ) . " 2>&1", $output, $return_var );
        if ( $return_var !== 0 ) {
            $error_msg = implode( "\n", array_filter( $output ) );
            $errors[] = [
                'message' => $error_msg,
                'line'    => preg_match( '/on line (\d+)/i', $error_msg, $matches ) ? intval( $matches[1] ) : null
            ];
        }

        // --- 6. Database connection check ---
        $db_name = $db_user = $db_password = $db_host = $table_prefix = null;
        foreach ( [ 'DB_NAME','DB_USER','DB_PASSWORD','DB_HOST' ] as $constant ) {
            if ( preg_match( "/define\(\s*['\"]{$constant}['\"]\s*,\s*['\"](.+?)['\"]\s*\)/i", $content, $matches ) ) {
                ${strtolower($constant)} = $matches[1];
            }
        }
        if ( preg_match( '/\$table_prefix\s*=\s*[\'"]([^\'"]+)[\'"]\s*;/', $content, $matches ) ) {
            $table_prefix = $matches[1];
        }

        if ( $db_name && $db_user && $db_password && $db_host ) {
            global $wpdb;

            if ( $db_name && $db_user && $db_password && $db_host ) {

                // Test connection by trying to query a known table
                $table_to_check = $table_prefix . 'options';
                $table_exists   = $wpdb->get_var( $wpdb->prepare( // phpcs:ignore
                    "SHOW TABLES LIKE %s",
                    $table_to_check
                ) );

                if ( ! $table_exists ) {
                    $errors[] = __( 'The table prefix does not match any WordPress tables in the database.', 'dev-debug-tools' );
                }

            } else {
                $errors[] = __( 'Cannot test database connection; one or more DB constants are missing or not in the expected format.', 'dev-debug-tools' );
            }
        } else {
            $errors[] = __( 'Cannot test database connection; one or more DB constants are missing or not in the expected format.', 'dev-debug-tools' );
        }

        // --- 7. Additional checks ---
        if ( strpos( ltrim( $content ), '<?php' ) !== 0 ) {
            $errors[] = __( 'wp-config.php must start with &lt;?php.', 'dev-debug-tools' );
        }
        if ( preg_match( '/\?>\s*$/', $content ) ) {
            $errors[] = __( 'wp-config.php should not have a closing ?> tag.', 'dev-debug-tools' );
        }
        if ( preg_match( '/^\s*(echo|print|var_dump|dpr|ddtt_print_r)\s*\(/m', $content ) ) {
            $errors[] = __( 'wp-config.php should not output content directly (echo, print, var_dump, etc).', 'dev-debug-tools' );
        }
        if ( preg_match( '/\b(eval|exec|shell_exec|system|passthru|popen|proc_open|curl_exec|curl_multi_exec|file_get_contents|file_put_contents)\b/i', $content ) ) {
            $errors[] = __( 'wp-config.php should not use potentially dangerous functions.', 'dev-debug-tools' );
        }
        if ( preg_match( '/^\s*(ob_start|ob_get_clean|ob_get_contents|ob_end_clean)\s*\(/m', $content ) ) {
            $errors[] = __( 'wp-config.php should not use output buffering functions.', 'dev-debug-tools' );
        }
        if ( preg_match( '/<html|<body|<div|<span|<script/i', $content ) ) {
            $errors[] = __( 'HTML output detected in wp-config.php.', 'dev-debug-tools' );
        }

        return $errors;
    } // End validate_file()


    /**
     * Update the file lines with the specified changes.
     *
     * @param array $all_snippets All available snippets.
     * @param array $lines The current file lines.
     * @param array $changes The changes to apply (add, remove, update).
     * @return array
     */
    public static function update_content_with_snippets( $all_snippets, $lines, $changes ) {
        $add = isset( $changes[ 'add' ] ) && is_array( $changes[ 'add' ] ) ? $changes[ 'add' ] : [];
        $remove = isset( $changes[ 'remove' ] ) && is_array( $changes[ 'remove' ] ) ? $changes[ 'remove' ] : [];
        $update = isset( $changes[ 'update' ] ) && is_array( $changes[ 'update' ] ) ? $changes[ 'update' ] : [];

        // 3a. Apply updates (replace matching lines)
        foreach ( $update as $snippet ) {
            foreach ( $lines as $i => $line ) {
                if ( stripos( $line, $snippet[ 'key' ] ) !== false ) {
                    $code = rtrim( $snippet[ 'code' ], ';' );
                    $lines[ $i ] = $code . ';';
                }
            }
        }

        // 3b. Remove snippets: mark indices to remove (snippet line, preceding comment block directly above it, and a single empty line after)
        $to_remove = [];
        $total     = count( $lines );
        foreach ( $remove as $key ) {
            if ( ! isset( $all_snippets[ $key ] ) ) {
                continue;
            }
            $snippet = $all_snippets[ $key ];
            if ( empty( $snippet[ 'lines' ] ) ) {
                continue;
            }

            foreach ( $snippet[ 'lines' ] as $line_def ) {
                $prefix   = isset( $line_def[ 'prefix' ] ) ? $line_def[ 'prefix' ] : '';
                $variable = isset( $line_def[ 'variable' ] ) ? $line_def[ 'variable' ] : '';

                // Build a regex pattern for this line
                if ( in_array( $prefix, [ 'define', '@ini_set' ], true ) && $variable !== '' ) {
                    $pattern = '/^\s*' . preg_quote( $prefix, '/' ) . '\s*\(\s*[\'"]' . preg_quote( $variable, '/' ) . '[\'"]/i';
                } elseif ( $variable !== '' ) {
                    $pattern = '/^\s*' . preg_quote( $prefix, '/' ) . '\s*\(\s*' . preg_quote( $variable, '/' ) . '/i';
                } else {
                    $pattern = '/^\s*' . preg_quote( $prefix, '/' ) . '\s*\(/i';
                }

                for ( $i = 0; $i < $total; $i++ ) {
                    if ( preg_match( $pattern, $lines[ $i ] ) ) {
                        $to_remove[ $i ] = true;

                        // Remove preceding comment block directly above
                        $j = $i - 1;
                        while ( $j >= 0 ) {
                            $prev_line_trim = trim( $lines[ $j ] );
                            if ( $prev_line_trim === '' ) {
                                break;
                            }
                            if ( preg_match( '/^[\/\*]/', $prev_line_trim ) ) {
                                $to_remove[ $j ] = true;
                                $j--;
                                continue;
                            }
                            break;
                        }

                        // Remove one blank line immediately after
                        if ( isset( $lines[ $i + 1 ] ) && trim( $lines[ $i + 1 ] ) === '' ) {
                            $to_remove[ $i + 1 ] = true;
                        }
                    }
                }
            }
        }

        // Build filtered lines
        $filtered = [];
        for ( $i = 0; $i < count( $lines ); $i++ ) {
            if ( ! isset( $to_remove[ $i ] ) ) {
                $filtered[] = $lines[ $i ];
            }
        }
        $lines = $filtered;

        // --- re-find the "stop editing" line after removals/updates ---
        $stop_index = false;
        foreach ( $lines as $i => $line ) {
            if ( strpos( $line, "That's all, stop editing!" ) !== false ) {
                $stop_index = $i;
                break;
            }
        }
        if ( $stop_index === false ) {
            $errors[] = __( 'Could not find the "stop editing" line in wp-config.php.', 'dev-debug-tools' );
            wp_send_json_error( $errors );
        }

        // 3c. Add snippets above "stop editing" with comment and controlled blank lines
        $add_lines    = [];
        foreach ( $add as $snippet ) {
            if ( isset( $all_snippets[ $snippet[ 'key' ] ] ) ) {
                // Use stripped label to avoid injecting HTML into code comments
                $label = wp_strip_all_tags( $all_snippets[ $snippet[ 'key' ] ][ 'label' ] );
                $add_lines[] = '/** ' . $label . ' */';
            }
            $code = rtrim( $snippet[ 'code' ], ';' );
            $add_lines[] = $code . ';';
            $add_lines[] = ''; // Blank line after each snippet block
        }

        if ( ! empty( $add_lines ) ) {
            // Collapse any contiguous blank lines directly above the stop line so we can ensure exactly one
            $k = $stop_index - 1;
            while ( $k >= 0 && trim( $lines[ $k ] ) === '' ) {
                array_splice( $lines, $k, 1 );
                $k--;
                $stop_index--;
            }

            // Insert a single blank line, then the add_lines
            array_splice( $lines, $stop_index, 0, array_merge( [ '' ], $add_lines ) );
        }

        return $lines;
    } // End update_content_with_snippets()

}