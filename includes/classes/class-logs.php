<?php
/**
 * Logs class
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Main plugin class.
 */
class DDTT_LOGS {

    /**
     * Set the highlight args to be used by stylesheet, settings and error_logs
     *
     * @return array
     */
    public function highlight_args() {
        // Get the active theme folder
        $active_theme = str_replace( '%2F', '/', rawurlencode( get_stylesheet() ) );

        // Set the args
        $args = apply_filters( 'ddtt_highlight_debug_log', [
            'php-fatal' => [
                'name'          => 'Fatal Error',
                'keyword'       => 'Fatal',
                'bg_color'      => '#FF0000',
                'font_color'    => '#FFFFFF',
                'priority'      => true,
                'column'        => 'type'
            ],
            'php-parse' => [
                'name'          => 'Parse Error',
                'keyword'       => 'Parse',
                'bg_color'      => '#FF0000',
                'font_color'    => '#FFFFFF',
                'priority'      => true,
                'column'        => 'type'
            ],
            'ddtt-plugin' => [
                'name'          => 'Dev Debug Tools Plugin',
                'keyword'       => DDTT_TEXTDOMAIN,
                'bg_color'      => '#26BECF',
                'font_color'    => '#1E1E1E',
                'priority'      => true,
                'column'        => 'path'
            ],
            'plugin' => [
                'name'          => 'Plugin',
                'keyword'       => DDTT_PLUGINS_URL,
                'bg_color'      => '#0073AA',
                'font_color'    => '#FFFFFF',
                'priority'      => false,
                'column'        => 'path'
            ],
            'theme' => [
                'name'          => 'Theme',
                'keyword'       => $active_theme,
                'bg_color'      => '#006400',
                'font_color'    => '#FFFFFF',
                'priority'      => false,
                'column'        => 'path'
            ]
        ] );
        
        // Return them
        return $args;
    } // End highlight_args()


    /**
     * Replace a file with another one on the server
     * USAGE: replace_file( 'debug.log', true )
     *
     * @param string $file_to_replace
     * @param string $file_to_copy
     * @param boolean $plugin_assets
     * @return void
     */
    public function replace_file( $file_to_replace, $file_to_copy, $plugin_assets = false ) {
        
        // First check if we are copying a file from the plugin assets folder
        if ( $plugin_assets ) {
            $file_to_copy =  get_home_path().DDTT_PLUGIN_FILES_PATH.$file_to_copy;
        } else {
            $file_to_copy =  get_home_path().$file_to_copy;
        }

        // Extract the filename from the $file_to_replace path
        $path_parts = pathinfo( $file_to_replace );
        $replacement_filename = $path_parts[ 'basename' ];
        $replacement_dir = $path_parts[ 'dirname' ];

        // Define the temporary path for the copied file
        $temp_file_path = $replacement_dir . '/' . $replacement_filename;

        // Copy the file to the new spot
        $result = copy( $file_to_copy, $temp_file_path );

        if (!$result) {
            $error = error_get_last();
            ddtt_admin_notice( 'error', 'Uh oh! Your file could not be copied to ' . $temp_file_path . '! ' . $error[ 'message' ] );
            return false;
        }

        // Rename the copied file to the final destination if necessary
        $result = rename( $temp_file_path, $file_to_replace );

        if (!$result) {
            $error = error_get_last();
            ddtt_admin_notice( 'error', 'Uh oh! Your file could not be renamed to ' . $file_to_replace . '! ' . $error[ 'message' ] );
        }

        return $result;
    } // End replace_file()


    /**
     * Check if file exists and is not empty; return notice
     *
     * @param string $path
     * @return string|bool
     */
    public function file_exists_with_content( $path ) {
        // Initialize the WP_Filesystem
        if ( !function_exists( 'WP_Filesystem' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        global $wp_filesystem;
        if ( !WP_Filesystem() ) {
            ddtt_write_log( 'Failed to initialize WP_Filesystem' );
            return false;
        }

        // Construct possible file paths
        $file_paths = [
            ABSPATH . $path,
            dirname( ABSPATH ) . '/' . $path,
            $path
        ];

        // Check if any of the paths exist and are readable
        $file = false;
        foreach ( $file_paths as $file_path ) {
            if ( $wp_filesystem->exists( $file_path ) ) {
                $file = $file_path;
                break;
            }
        }

        // Check file size if a file is found
        if ( $file && $wp_filesystem->size( $file ) > 0 ) {
            $result = $file;
        } elseif ( $file && $wp_filesystem->size( $file ) == 0 ) {
            $result = false;
        } else {
            $result = null;
        }

        // Return the result
        return $result;
    } // End file_exists_with_content()


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
    public function file_contents_with_clear_button( $query_string_param, $button_label, $path, $filesize, $log = false, $highlight_args = [], $allow_repeats = true ) {
        // The clear url
        $clear_url = esc_url( add_query_arg( $query_string_param, 'true', ddtt_plugin_options_path( 'logs' ) ) );

        // Button for clearing log
        if ( ( is_multisite() && !is_network_admin() && is_main_site() ) || !is_multisite() ) {
            $clear_button = '<div><a id="clear-log-button-'.$query_string_param.'" class="button button-warning" href="'.$clear_url.'" style="font-weight: normal;">Clear '.esc_html( $button_label ).'</a></div>';
        } else {
            $clear_button = '';
        }

        // Button for downloading
        if ( $button_label == 'Debug Log' ) {
            $dl = 'debug_log';
        } elseif ( $button_label == 'Admin Error Log' ) {
            $dl = 'admin_error_log';
        } elseif ( $button_label == 'Error Log' ) {
            $dl = 'error_log';
        } else {
            $dl = 'null';
        }
        $download_button = '<div><form method="post">
            '.wp_nonce_field( DDTT_GO_PF.$dl.'_dl', '_wpnonce' ).'
            <input type="submit" value="Download '.esc_html( $button_label ).'" name="ddtt_download_'.$dl.'" class="button button-primary"/>
        </form></div>';

        // Set the viewer var
        if ( ddtt_get( 'viewer', '==', 'classic' ) || ddtt_get( 'viewer', '==', 'easy' ) ) {
            $dl_viewer = ddtt_get( 'viewer' );

        } elseif ( get_option( DDTT_GO_PF.'log_viewer' ) == 'Classic' ) {
            $dl_viewer = 'classic';

        } elseif ( get_option( DDTT_GO_PF.'log_viewer' ) == 'Easy Reader' ) {
            $dl_viewer = 'easy';

        } else {
            $dl_viewer = 'easy';
        }

        // If debug log
        if ( $dl == 'debug_log' ) {

            // Set the max filesize
            $dl_max_filesize = ddtt_get_max_log_filesize();

            // Include issues notice
            if ( $filesize > 10485760 ) {
                ddtt_admin_notice( 'warning', 'Uh oh! Your debug log is '.ddtt_format_bytes( $filesize ).'! That is far too big, and may cause issues for your site. It is recommended that you download your log to see what\'s going on, and then clear it. If your log does not download from the button below, try logging into your File Manager on your host or downloading via FTP.' );
            }

            // Check if we are under
            if ( $filesize >= absint( $dl_max_filesize ) ) {
                ddtt_admin_notice( 'warning', 'Your debug log is larger than the max viewable log size (currently '.esc_html( ddtt_format_bytes( $filesize ) ).', max is '.esc_html( ddtt_format_bytes( absint( $dl_max_filesize ) ) ).'), and can cause issues if we try to load the whole thing on this page. Therefore, we have only included the last portion of your log. If you want to increase the max size regardless, you can do so in Settings.' );
            }

            // Get the contents
            if ( $dl_viewer == 'easy' ) {
                $contents = ddtt_view_file_contents_easy_reader( $path, $log, $highlight_args, $allow_repeats);
            } else {
                $contents = ddtt_view_file_contents( $path, $log );
            }
        }

        // Get the contents
        if ( $dl != 'debug_log' ) {
            $contents = ddtt_view_file_contents( $path, $log );
        }

        // The current url
        $current_url = ddtt_plugin_options_path( 'logs' );

        // Add viewer links
        $switch_to = '';
        if ( $dl == 'debug_log' ) {
            if ( $dl_viewer == 'easy' ) {
                $switch_to = '<a href="'.$current_url.'&viewer=classic">Switch to Classic View</a>';
            } else {
                $switch_to = '<a href="'.$current_url.'&viewer=easy">Switch to Easy Reader</a>';
            }
        }

        // Add recent links
        if ( $dl == 'debug_log' && $dl_viewer == 'easy' ) {
            $recent = '<br><br>View Recent: <a href="'.$current_url.'&r=1">1</a> | <a href="'.$current_url.'&r=5">5</a> | <a href="'.$current_url.'&r=10">10</a>';
        } else {
            $recent = '';
        }

        // Add color panel
        if ( $dl == 'debug_log' && $dl_viewer == 'easy' && !empty( $highlight_args ) ) {

            // Outer container
            $highlights = '<br><br><div id="color-identifiers">';

            // Iter the colors
            foreach ( $highlight_args as $hl_key => $hl ) {

                // Make sure the args are correct
                if ( isset( $hl[ 'name' ] ) ) {

                    // Check if it's a priority
                    if ( isset( $hl[ 'priority' ] ) && $hl[ 'priority' ] == true ) {
                        $priority = '!';
                    } else {
                        $priority = '';
                    }

                    // Add links if easy reader
                    if ( $dl_viewer == 'easy' ) {

                        // Set the link col
                        if ( $hl[ 'column' ] == 'type' ) {
                            $col = '&c=t';
                        } elseif ( $hl[ 'column' ] == 'path' ) {
                            $col = '&c=p';
                        } else {
                            $col = '';
                        }

                        // Add the link
                        $text = '<a href="'.$current_url.'&s='.$hl[ 'keyword' ].$col.'">'.$hl[ 'name' ].'</a>';
                    } else {
                        $text = $hl[ 'name' ];
                    }
                    
                    // Add the color
                    $highlights .= '<div class="color-cont">
                        <div class="color-box '.$hl_key.'">'.$priority.'</div>
                        <div class="hl-name">'.$text.'</div>
                    </div>';
                }
            }

            // End the outer container
            $highlights .= '</div>';
        } else {
            $highlights = '';
        }

        // Search bar
        if ( $dl == 'debug_log' && $dl_viewer == 'easy' ) {

            // Put the search bar together
            $search_bar = '<tr valign="top">
                <th scope="row"><label for="ddtt-dl-search">Search/Filter</label></th>
                <td><form id="ddtt-dl-search-form" method="get" action="'.$current_url.'">';

                    // Get the current search string
                    if ( ddtt_get( 's' ) ) {
                        $search = ddtt_get( 's' );
                    } else {
                        $search = '';
                    }
                    
                    // Get the column
                    if ( ddtt_get( 'c', '==', 't' ) ) {
                        $check_type = ' checked="checked"';
                        $check_err = '';
                        $check_path = '';
                    } elseif ( ddtt_get( 'c', '==', 'p' ) ) {
                        $check_type = '';
                        $check_err = '';
                        $check_path = ' checked="checked"';
                    } else {
                        $check_type = '';
                        $check_err = ' checked="checked"';
                        $check_path = '';
                    }

                    // Get all of the query strings
                    $qs_array = filter_input_array( INPUT_GET, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                    
                    // Iter the params
                    $hidden_inputs = [];
                    foreach ( $qs_array as $k => $v ) {

                        // Add hidden inputs
                        $hidden_inputs[] = '<input type="hidden" id="ddtt-dl-search-'.$k.'" name="'.$k.'" value="'.$v.'"/>';
                    }

                    // Add the search field and button
                    $search_bar .= implode( '', $hidden_inputs ).'
                    <div id="ddtt-dl-search-bar">
                        <input type="text" name="s" id="ddtt-dl-search" value="'.$search.'" style="width: 43.75rem"/>
                        <br>// You may remove an item from the list by adding a minus (-) symbol before a keyword (ie. -keyword)
                    </div>                    
                    <div id="ddtt-dl-search-options">
                        <input class="update_choice_input" type="radio" name="c" id="ddtt-dl-search-col-t" value="t"'.$check_type.'/> <label for="ddtt-dl-search-col-t">Type</label>
                        <input class="update_choice_input" type="radio" name="c" id="ddtt-dl-search-col-e" value="e"'.$check_err.'/> <label for="ddtt-dl-search-col-e">Error</label>
                        <input class="update_choice_input" type="radio" name="c" id="ddtt-dl-search-col-p" value="p"'.$check_path.'/> <label for="ddtt-dl-search-col-p">File Path</label>
                        <input type="submit" value="Search" id="ddtt-dl-search-btn"/>
                        <a href="'.$current_url.'" id="ddtt-dl-reset-btn" class="button button-primary">Reset</a>
                    </div>
                </form></td>
            </tr>';
        } else {
            $search_bar = '';
        }

        // Format filename
        $filename = str_replace( [ '.', '_' ], ' ', $dl );
        $filename = ucwords( $filename );

        // Return the row
        $results = '<table class="form-table">
            '.$search_bar.'
            <tr valign="top">
                <th scope="row">Current '.$filename.' (View Only)<br><br><em>'.$switch_to.'</em>'.$recent.$highlights.'<br><br>'.$download_button.'<br>'.$clear_button.'</th>
                <td class="log-cell"><div class="full_width_container"> '.$contents.' </div></td>
            </tr>
        </table>';
        return $results;
    } // End file_contents_with_clear_button()
}