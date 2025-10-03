<?php
/**
 * WP Config
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Htaccess {


    /**
     * Our snippets
     *
     * @return array
     */
    public static function snippets() : array {
        // Vars
        $domain     = wp_parse_url( home_url(), PHP_URL_HOST );
        $server_ip = Helpers::get_server_ip() ?: '127.0.0.1';
        $admin_path = trim( wp_parse_url( admin_url(), PHP_URL_PATH ), '/' );
        $includes_path = basename( includes_url() ) . '/';

        // Add the snippets
        $snippets = [
            'restrict_direct_access' => [
                'label' => __( 'Restrict Unauthorized Direct Access to Login and Admin', 'dev-debug-tools' ),
                'lines' => [
                    '<IfModule mod_rewrite.c>',
                    'RewriteEngine on',
                    'RewriteCond %{REQUEST_METHOD} POST',
                    'RewriteCond %{HTTP_REFERER} !^https?://(.*)?' . $domain . ' [NC]',
                    'RewriteCond %{REQUEST_URI} ^(.*)?wp-login\.php(.*)$ [OR]',
                    'RewriteCond %{REQUEST_URI} ^(.*)?' . $admin_path . '$',
                    'RewriteRule ^.*$ - [F]',
                    '</IfModule>'
                ],
                'desc' => __( 'Restricts direct access to the WordPress login page (wp-login.php) and the admin area (wp-admin) unless the request is a valid POST request coming from your domain. It enhances security by preventing unauthorized access to sensitive parts of your WordPress site.', 'dev-debug-tools' )
            ],
            'samesite' => [
                'label' => __( 'Set SameSite Attribute for Session Cookies', 'dev-debug-tools' ),
                'lines' => [
                    'Header always edit Set-Cookie (.*) "$1; SameSite=Lax"'
                ],
                'desc' => __( 'Helps prevent Cross Site Request Forgery (CSRF). A CSRF attack occurs when a malicious website tricks an authenticated user\'s browser into performing unwanted actions on a trusted site. The attacker exploits the user\'s existing session to execute actions without their knowledge. CSRF attacks can lead to unauthorized actions, such as changing passwords, transferring funds, or making purchases.', 'dev-debug-tools' )
            ],
            'protect_imp_files' => [
                'label' => __( 'Protect Important WP and Server Files', 'dev-debug-tools' ),
                'lines' => [
                    '<FilesMatch "^.*(error_log|wp-config\.php|php.ini|\.[hH][tT][aApP].*)$">',
                    'Order deny,allow',
                    'Deny from all',
                    '</FilesMatch>',
                    'RedirectMatch 403 \.(htaccess|htpasswd|errordocs|logs)$',
                ],
                'desc' => __( 'Restricts access to sensitive files and directories within your WordPress installation. Denies access to the server error log file, critical WordPress configuration file, PHP configuration file, and any file starting with <code>.ht</code> or <code>.htaccess</code>. Any request for these files will result in a 403 Forbidden error. By denying access to critical files and directories, you enhance the security of your WordPress installation. Unauthorized users won\'t be able to view sensitive information or manipulate essential configuration files.', 'dev-debug-tools' )
            ],
            'debuglog_private' => [
                'label' => __( 'Prevent Debug.log from Being Public', 'dev-debug-tools' ),
                'lines' => [
                    '<Files "debug.log">',
                    'Require all denied',
                    'Require ip 127.0.0.1',
                    'Require ip ' . $server_ip,
                    '</Files>',
                ],
                'desc' => __( 'Restricts access to the <code>debug.log</code> file within your WordPress installation, enhancing security. This is highly recommended, especially if you have enabled debugging on your <code>wp-config.php</code> file. Only authorized IP addresses (localhost and hosting server) can view the log file. Unauthorized users won\'t be able to access sensitive debugging information.', 'dev-debug-tools' )
            ],
            'redirect_https' => [
                'label' => __( 'Redirect http:// to https://', 'dev-debug-tools' ),
                'lines' => [
                    '<IfModule mod_rewrite.c>',
                    'RewriteEngine On',
                    'RewriteCond %{SERVER_PORT} 80',
                    'RewriteRule ^(.*)$ https://' . $domain . '/$1 [R=301,L]',
                    '</IfModule>',
                ],
                'desc' => __( 'Performs a 301 (permanent) redirect from HTTP to HTTPS for your WordPress site. By doing so, you ensure that all traffic to your site is encrypted. The 301 redirect tells search engines that the change is permanent, preserving SEO rankings and avoiding duplicate content issues.', 'dev-debug-tools' )
            ],
            'disable_server_sig' => [
                'label' => __( 'Turn Off Server Signature', 'dev-debug-tools' ),
                'lines' => [
                    'ServerSignature Off',
                ],
                'desc' => __( 'Disables the server signature (also known as the server banner or server version) for your WordPress site. The server signature is a piece of information about your web server (e.g., Apache, Nginx) and its version. By default, when an error occurs (such as a 404 page), the server includes this signature in the response headers. The server signature can reveal sensitive information about the software versions running on the web server. Hiding the server signature enhances security by reducing the exposure of server details. It prevents potential attackers from knowing specific server software versions. Disabling the server signature is a recommended security practice.', 'dev-debug-tools' )
            ],
            'disable_index_browsing' => [
                'label' => __( 'Disable Index Browsing', 'dev-debug-tools' ),
                'lines' => [
                    'Options -Indexes',
                ],
                'desc' => sprintf(
                    /* translators: %s: Example directory URL */
                    __( 'Prevents the web server from automatically generating a list of files and directories when no specific file (such as an index file) is found in a directory. Without this directive, if someone accesses a directory without an index file (e.g., %s), the server might display a list of all files and subdirectories within that directory. Disabling this behavior makes it more difficult for potential attackers to explore your directory structure.', 'dev-debug-tools' ),
                    esc_url( home_url( '/some-directory/' ) )
                ),
            ],
            'dir_force_index' => [
                'label' => __( 'Directory Index Force Index.php', 'dev-debug-tools' ),
                'lines' => [
                    'DirectoryIndex index.php index.html /index.php',
                ],
                'desc' => sprintf(
                    /* translators: %s: Example directory URL */
                    __( 'Modifies the default index page behavior for your site. By configuring the order of index files, you control which file is loaded first when someone accesses a directory. In this example, if a user visits a directory without specifying a specific file (e.g., %s), the server will first look for index.php, then index.html, and finally /index.php. This ensures that the appropriate default page is displayed when accessing directories within your WordPress site.', 'dev-debug-tools' ),
                    esc_url( home_url( '/some-directory/' ) )
                ),
            ],
            'script_injections' => [
                'label' => __( 'Prevent Script Injections', 'dev-debug-tools' ),
                'lines' => [
                    'Options +FollowSymLinks',
                    'RewriteEngine On',
                    'RewriteCond %{QUERY_STRING} (<|%3C).*script.*(>|%3E) [NC,OR]',
                    'RewriteCond %{QUERY_STRING} GLOBALS(=|[|%[0-9A-Z]{0,2}) [OR]',
                    'RewriteCond %{QUERY_STRING} _REQUEST(=|[|%[0-9A-Z]{0,2})',
                    'RewriteRule ^(.*)$ index.php [F,L]',
                ],
                'desc' => __( 'Prevent potential attacks that exploit query strings containing suspicious patterns related to scripts or global variables. It enhances security by blocking access to URLs with harmful query strings.', 'dev-debug-tools' )
            ],
            'protect_includes' => [
                'label' => __( 'Protect WP Includes Directory', 'dev-debug-tools' ),
                'lines' => [
                    '<IfModule mod_rewrite.c>',
                    'RewriteEngine On',
                    'RewriteBase /',
                    'RewriteRule ^' . $admin_path . '/includes/ - [F,L]',
                    'RewriteRule !^' . $includes_path . ' - [S=3]',
                    'RewriteRule ^' . $includes_path . '[^/]+\.php$ - [F,L]',
                    'RewriteRule ^' . $includes_path . 'js/tinymce/langs/.+\.php - [F,L]',
                    'RewriteRule ^' . $includes_path . 'theme-compat/ - [F,L]',
                    '</IfModule>',
                ],
                'desc' => __( 'Prevents unauthorized direct access to sensitive PHP files within the "include" directories.', 'dev-debug-tools' )
            ],
            'username_enumeration' => [
                'label' => __( 'Prevent Username Enumeration', 'dev-debug-tools' ),
                'lines' => [
                    'RewriteEngine On',
                    'RewriteCond %{REQUEST_URI} !^/=' . $admin_path . ' [NC]',
                    'RewriteCond %{QUERY_STRING} author=\d',
                    'RewriteRule ^ /? [L,R=301]',
                ],
                'desc' => __( 'Redirects URLs with an author parameter (e.g., ?author=123) away from the site. This can be useful for security reasons or to prevent exposing user information, including their username.', 'dev-debug-tools' )
            ],
            'redirect_bots' => [
                'label' => __( 'Block Bots from WP Admin', 'dev-debug-tools' ),
                'lines' => [
                    'ErrorDocument 401 /404.shtml',
                    'ErrorDocument 403 /404.shtml',
                    'Redirect 301 /author/admin/ /404.shtml',
                ],
                'desc' => __( 'Tells the server to display the <code>/404.shtml</code> page whenever a 401 Unauthorized error occurs. This typically happens when a user tries to access a restricted area of the site without the correct credentials. Same with when a 403 Forbidden error occurs, which is usually triggered when a user tries to access a directory or file for which they do not have permission. Furthermore, this snippet blocks access to the <code>/author/admin/</code> page.', 'dev-debug-tools' )
            ],
            'upload_size' => [
                'label' => __( 'Increase Upload Size', 'dev-debug-tools' ),
                'lines' => [
                    'php_value upload_max_filesize 256M',
                    'php_value post_max_size 256M',
                    'php_value max_execution_time 300',
                    'php_value max_input_time 300',
                ],
                'desc' => __( 'Sets the maximum upload file size, which means that users will be able to upload files up to 256MB in size. Sets the maximum size of POST data that PHP will accept to 256MB. Sets the maximum time in seconds a script is allowed to run before it is terminated by the parser. This helps prevent poorly written scripts from tying up the server. Allows a script to run for up to 300 seconds (or 5 minutes). Sets the maximum time in seconds (also 300) that a script is allowed to parse input data, like POST, GET and file uploads.', 'dev-debug-tools' )
            ],
            'max_input_vars' => [
                'label' => __( 'Increase Max Vars Limit', 'dev-debug-tools' ),
                'lines' => [
                    'php_value max_input_vars 3000',
                ],
                'desc' => __( 'Increases the maximum number of input variables that PHP will accept. By default, PHP has a limit on the number of input variables it can handle. This limit is set to prevent attacks such as hash collisions. However, in some cases, you might need to increase this limit. For example, if you have a form with a large number of fields or if you\'re using a WordPress theme that requires a higher limit.', 'dev-debug-tools' )
            ],
            'allow_backups' => [
                'label' => __( 'Allow Sucuri GoDaddy Backups', 'dev-debug-tools' ),
                'lines' => [
                    '<ifmodule mod_rewrite.c="">',
                    'RewriteRule ^sucuri-(.*).php$ - [L]',
                    '</ifmodule>',
                ],
                'desc' => __( 'Only useful if you are hosting with GoDaddy and have Website Security feature with backups enabled. This is a recommended snippet by Sucuri that may help resolve issues with redirecting during backups.', 'dev-debug-tools' )
            ],
        ];


        /**
         * Apply filters to the snippets.
         */
        $snippets = apply_filters( 'ddtt_htaccess_snippets', $snippets );

        return $snippets;
    } // End snippets()


    /**
     * Sanitize a single snippet array.
     *
     * @param array $snippet Snippet data to sanitize.
     * @return array
     */
    public static function sanitize_existing_snippet( $snippet ) : array {
        if ( !is_array( $snippet ) ) {
            return [
                'label' => '',
                'desc'  => '',
                'lines' => [],
                'added' => false,
            ];
        }

        // Sanitize lines (all plain strings)
        $sanitized_lines = [];
        if ( isset( $snippet[ 'lines' ] ) && is_array( $snippet[ 'lines' ] ) ) {
            $sanitized_lines = array_map( fn( $line ) => is_string( $line ) ? trim( $line ) : '', $snippet[ 'lines' ] );
        }

        // Sanitize added metadata
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
            'added' => $added,
        ];
    } // End sanitize_existing_snippet()


    /**
     * Sanitize new snippet lines.
     *
     * @param array $lines The snippet lines
     * @return array
     */
    public static function sanitize_new_snippet_lines( $lines ) : array {
        return array_map( 'sanitize_text_field', $lines );
    } // End sanitize_new_snippet_lines()


    /**
     * Create a unique key from the snippet
     *
     * @param array $snippet The snippet data.
     * @return string
     */
    public static function create_key_from_snippet( $snippet ) : string {
        $key = strtolower( $snippet[ 'label' ] );
        $key = preg_replace( '/[^a-z0-9_]+/', '_', $key );
        $key = trim( $key, '_' );
        return $key;
    } // End create_key_from_snippet()


    /**
     * Check if a snippet already exists.
     *
     * @param array $snippets The existing snippets.
     * @param array $snippet  The snippet to check.
     * @return string|false
     */
    public static function does_snippet_key_exist( $snippets, $snippet ) {
        $key = self::create_key_from_snippet( $snippet );
        if ( $key ) {
            // Check if the key already exists
            foreach ( $snippets as $existing_key => $existing ) {
                if ( $existing_key === $key ) {
                    return $key;
                }
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
        $lines = preg_split( '/\r\n|\r|\n/', $raw_contents );

        $snippets = [];
        $current_block = [];

        foreach ( $lines as $line ) {
            $trimmed = trim( $line );

            if ( $trimmed === '' ) {
                if ( ! empty( $current_block ) ) {
                    $snippets[] = $current_block;
                    $current_block = [];
                }
                continue;
            }

            $current_block[] = $trimmed;
        }

        if ( ! empty( $current_block ) ) {
            $snippets[] = $current_block;
        }

        return $snippets;
    } // End extract_snippets_from_content()


    /**
     * Get the current code lines for a specific line.
     *
     * @param array $lines The line data.
     * @return array
     */
    public static function get_current_code_lines( $lines, $current_blocks ) : array {
        $detected = false;
        $current_code = [];

        $normalize_lines = function( $lines ) {
            $lines = array_map( 'trim', $lines );
            $lines = array_filter( $lines, fn( $line ) => $line !== '' && strpos( $line, '#' ) !== 0 );
            $lines = array_values( $lines );

            $lines = array_map( function( $line ) {
                $line = str_replace( ['\\-', '\\/'], ['-', '/'], $line );
                $line = preg_replace('/https\?:\/\//', 'https://', $line);

                return $line;
            }, $lines );

            return $lines;
        };

        $lines_cleaned = $normalize_lines( $lines );

        foreach ( $current_blocks as $block ) {
            $block_normalized = array_map( 'trim', $block );
            $block_to_compare = $normalize_lines( $block_normalized );

            if ( $block_to_compare === $lines_cleaned ) {
                $detected = true;
                $current_code = array_values(
                    array_filter( $block_normalized, fn( $line ) => trim( $line ) !== '' && strpos( ltrim( $line ), '#' ) !== 0 )
                );
                break;
            }
        }

        if ( ! $detected ) {
            $current_code = $lines_cleaned;
        }

        return [
            'detected' => $detected,
            'code'     => $current_code
        ];
    } // End get_current_code_lines()


    /**
     * Redact sensitive content in the raw .htaccess file contents.
     *
     * @param string $raw_contents The raw file contents.
     * @return array
     */
    public static function redact_content( $raw_contents ) {
        $domain = wp_parse_url( home_url(), PHP_URL_HOST );
        $sensitive_values = [];

        // Redact IP addresses (IPv4 and IPv6)
        $raw_contents = preg_replace_callback(
            '/\b((?:\d{1,3}\.){3}\d{1,3})\b|([a-f0-9:]+:+[a-f0-9]+)/i',
            function( $matches ) use ( &$sensitive_values ) {
                $ip = $matches[1] ?: $matches[2];
                $sensitive_values[ $ip ] = $ip;
                return '__DDTT_REDACT_IP__';
            },
            $raw_contents
        );

        // Redact current domain (not including protocol)
        if ( $domain ) {
            $raw_contents = preg_replace_callback(
                '/\b' . preg_quote( $domain, '/' ) . '\b/i',
                function( $matches ) use ( &$sensitive_values, $domain ) {
                    $sensitive_values[ $domain ] = $domain;
                    return '__DDTT_REDACT_DOMAIN__';
                },
                $raw_contents
            );
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
        // Unredact IP addresses
        if ( ! empty( $sensitive_values ) ) {
            foreach ( $sensitive_values as $key => $value ) {
                if ( filter_var( $key, FILTER_VALIDATE_IP ) ) {
                    $highlighted_content = str_replace(
                        '__DDTT_REDACT_IP__',
                        '<i class="ddtt-redact">' . esc_html( $value ) . '</i>',
                        $highlighted_content
                    );
                }
            }
            // Unredact domain
            if ( isset( $sensitive_values[ wp_parse_url( home_url(), PHP_URL_HOST ) ] ) ) {
                $highlighted_content = str_replace(
                    '__DDTT_REDACT_DOMAIN__',
                    '<i class="ddtt-redact">' . esc_html( $sensitive_values[ wp_parse_url( home_url(), PHP_URL_HOST ) ] ) . '</i>',
                    $highlighted_content
                );
            }
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
        // Check for at least one RewriteEngine or IfModule block
        if (
            stripos( $content, 'RewriteEngine' ) === false &&
            stripos( $content, '<IfModule' ) === false
        ) {
            $errors[] = __( 'No RewriteEngine or IfModule block detected. Most .htaccess files require these.', 'dev-debug-tools' );
        }

        // Check for # BEGIN WordPress and # END WordPress comments
        if (
            stripos( $content, '# BEGIN WordPress' ) === false ||
            stripos( $content, '# END WordPress' ) === false
        ) {
            $errors[] = __( 'Missing required # BEGIN WordPress or # END WordPress comment block.', 'dev-debug-tools' );
        }

        // --- 2. Check for invalid characters ---
        if ( preg_match( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $content ) ) {
            $errors[] = __( 'Invalid control characters detected in .htaccess file.', 'dev-debug-tools' );
        }

        // --- 3. Check for duplicate blocks ---
        $snippets = self::extract_snippets_from_content( $content );
        $seen_blocks = [];
        foreach ( $snippets as $block ) {
            $block_str = implode( "\n", $block );
            if ( in_array( $block_str, $seen_blocks, true ) ) {
                $errors[] = __( 'Duplicate snippet block detected.', 'dev-debug-tools' );
            } else {
                $seen_blocks[] = $block_str;
            }
        }

        // --- 4. Check for syntax errors using Apache's configtest if available ---
        // This is optional and only works if shell_exec and apache2ctl are available
        if ( function_exists( 'shell_exec' ) && trim( shell_exec( 'which apache2ctl' ) ) ) {
            $tmp_htaccess = tempnam( sys_get_temp_dir(), 'htaccess_' );
            file_put_contents( $tmp_htaccess, $content );
            $output = shell_exec( 'apache2ctl -t -f ' . escapeshellarg( $tmp_htaccess ) . ' 2>&1' );
            if ( $output && stripos( $output, 'Syntax OK' ) === false ) {
                $errors[] = __( 'Apache configtest failed: ', 'dev-debug-tools' ) . esc_html( $output );
            }
            wp_delete_file( $tmp_htaccess );
        }

        // --- 5. Additional checks ---
        $forbidden = [ 'php_flag', 'Options +Indexes', 'AddType', 'AddHandler', 'SetHandler' ];
        foreach ( $forbidden as $directive ) {
            if ( stripos( $content, $directive ) !== false ) {
                // Translators: %s: Forbidden directive name.
                $errors[] = sprintf( __( 'Forbidden directive detected: %s', 'dev-debug-tools' ), esc_html( $directive ) );
            }
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
        $add    = isset( $changes[ 'add' ] ) && is_array( $changes[ 'add' ] ) ? $changes[ 'add' ] : [];
        $remove = isset( $changes[ 'remove' ] ) && is_array( $changes[ 'remove' ] ) ? $changes[ 'remove' ] : [];
        $update = isset( $changes[ 'update' ] ) && is_array( $changes[ 'update' ] ) ? $changes[ 'update' ] : [];

        // 1. Remove blocks
        $to_remove = [];
        $total = count( $lines );
        foreach ( $remove as $key ) {
            if ( ! isset( $all_snippets[ $key ] ) ) {
                continue;
            }
            $snippet = $all_snippets[ $key ];
            if ( empty( $snippet[ 'lines' ] ) ) {
                continue;
            }
            // Find matching block in lines
            for ( $i = 0; $i < $total; $i++ ) {
                // Try to match the block starting at $i
                $match = true;
                foreach ( $snippet[ 'lines' ] as $j => $snippet_line ) {
                    if (
                        ! isset( $lines[ $i + $j ] ) ||
                        trim( $lines[ $i + $j ] ) !== trim( $snippet[ 'lines' ][ $j ] )
                    ) {
                        $match = false;
                        break;
                    }
                }
                if ( $match ) {
                    // Remove contiguous comment block above, stopping at blank line or # END
                    $k = $i - 1;
                    while ( $k >= 0 ) {
                        $line = ltrim( $lines[ $k ] );
                        if ( $line === '' || stripos( $line, '# END' ) === 0 ) {
                            break;
                        }
                        if ( strpos( $line, '#' ) === 0 ) {
                            $to_remove[ $k ] = true;
                            $k--;
                            continue;
                        }
                        break;
                    }

                    // Mark all lines in the block for removal
                    for ( $j = 0; $j < count( $snippet[ 'lines' ] ); $j++ ) {
                        $to_remove[ $i + $j ] = true;
                    }

                    // Remove a blank line after the block if present
                    if ( isset( $lines[ $i + count( $snippet[ 'lines' ] ) ] ) && trim( $lines[ $i + count( $snippet[ 'lines' ] ) ] ) === '' ) {
                        $to_remove[ $i + count( $snippet[ 'lines' ] ) ] = true;
                    }
                }
            }
        }

        // 2. Apply updates (replace matching blocks)
        foreach ( $update as $snippet ) {
            // Find the label comment for this snippet
            $label = '';
            if ( isset( $all_snippets[ $snippet[ 'key' ] ] ) ) {
                $label = wp_strip_all_tags( $all_snippets[ $snippet[ 'key' ] ][ 'label' ] );
            } elseif ( isset( $snippet[ 'label' ] ) ) {
                $label = wp_strip_all_tags( $snippet[ 'label' ] );
            }
            $comment = '# ' . $label;

            $total = count( $lines );
            for ( $i = 0; $i < $total; $i++ ) {
                if ( trim( $lines[ $i ] ) === $comment ) {
                    // Found the start of the block, now find where it ends (next blank line or next comment)
                    $block_start = $i;
                    $block_end = $i + 1;
                    while (
                        $block_end < $total &&
                        trim( $lines[ $block_end ] ) !== '' &&
                        strpos( ltrim( $lines[ $block_end ] ), '#' ) !== 0
                    ) {
                        $block_end++;
                    }
                    // Support both 'lines' (array) and 'code' (string)
                    if ( isset( $snippet[ 'lines' ] ) && is_array( $snippet[ 'lines' ] ) ) {
                        $lines_array = $snippet[ 'lines' ];
                    } elseif ( isset( $snippet[ 'code' ] ) && is_string( $snippet[ 'code' ] ) ) {
                        $code = preg_replace( '/<br\s*\/?>/i', "\n", $snippet[ 'code' ] );
                        $code = html_entity_decode( $code );
                        $lines_array = preg_split( '/\r\n|\r|\n/', $code );
                    } else {
                        $lines_array = array();
                    }
                    $new_block = array_merge( array( $comment ), $lines_array, array( '' ) );
                    array_splice( $lines, $block_start, $block_end - $block_start, $new_block );
                    // Adjust total and index after splice
                    $total = count( $lines );
                    $i = $block_start + count( $new_block ) - 1;
                }
            }
        }

        // 3. Build filtered lines
        $filtered = [];
        for ( $i = 0; $i < count( $lines ); $i++ ) {
            if ( ! isset( $to_remove[ $i ] ) ) {
                $filtered[] = $lines[ $i ];
            }
        }
        $lines = $filtered;

        // 4. Add new blocks at the end, separated by blank lines
        $add_lines = [];
        foreach ( $add as $snippet ) {
            if ( isset( $all_snippets[ $snippet[ 'key' ] ] ) ) {
                $label = wp_strip_all_tags( $all_snippets[ $snippet[ 'key' ] ][ 'label' ] );
                $add_lines[] = '# ' . $label;
            }
            // Support both 'lines' (array) and 'code' (string)
            if ( isset( $snippet[ 'lines' ] ) && is_array( $snippet[ 'lines' ] ) ) {
                foreach ( $snippet[ 'lines' ] as $code_line ) {
                    $add_lines[] = $code_line;
                }
            } elseif ( isset( $snippet[ 'code' ] ) && is_string( $snippet[ 'code' ] ) ) {
                // Convert <br> tags to newlines before splitting
                $code = preg_replace( '/<br\s*\/?>/i', "\n", $snippet[ 'code' ] );
                // Decode HTML entities (e.g. &lt; to <)
                $code = html_entity_decode( $code );
                foreach ( preg_split( '/\r\n|\r|\n/', $code ) as $code_line ) {
                    $add_lines[] = $code_line;
                }
            }
            $add_lines[] = ''; // Blank line after each snippet block
        }
        if ( ! empty( $add_lines ) ) {
            // Remove trailing blank lines from $lines
            while ( ! empty( $lines ) && trim( end( $lines ) ) === '' ) {
                array_pop( $lines );
            }
            // Add a blank line before new blocks if needed
            if ( ! empty( $lines ) && trim( end( $lines ) ) !== '' ) {
                $lines[] = '';
            }
            $lines = array_merge( $lines, $add_lines );
        }

        return $lines;
    } // End update_content_with_snippets()

}