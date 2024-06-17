<?php
/**
 * Admin area class file.
 * All functions that modify the admin area.
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Initiate the class
 */
new DDTT_ADMIN_AREA;


/**
 * Main plugin class.
 */
class DDTT_ADMIN_AREA {

    /**
	 * Constructor
	 */
	public function __construct() {
        
        // Add a settings link to plugins list page
        add_filter( 'plugin_action_links_'.DDTT_TEXTDOMAIN.'/'.DDTT_TEXTDOMAIN.'.php', [ $this, 'settings_link' ],  );

        // Add links to the website and discord
        add_filter( 'plugin_row_meta', [ $this, 'plugin_row_meta' ], 10, 2 );

        // Add debug links
        add_action( 'post_submitbox_misc_actions', [ $this, 'post_submitbox_actions' ] );

        // Add plugins to featured plugins list
        add_filter( 'install_plugins_table_api_args_featured', [ $this, 'featured_plugins_tab' ] );

        // Add columns to plugins page
        if ( !get_option( DDTT_GO_PF.'plugins_page_data' ) || get_option( DDTT_GO_PF.'plugins_page_data' ) != 1 ) {
            add_filter( 'manage_plugins_columns', [ $this, 'plugins_column' ] );
            add_action( 'manage_plugins_custom_column', [ $this, 'plugins_column_content' ], 10, 2 );
        }

        // Allow searching posts/pages by id in admin area
        add_action( 'pre_get_posts', [ $this, 'admin_search_include_ids' ] );

        // Change browser tabs for plugin
        add_filter( 'admin_title', [ $this, 'browser_tabs' ], 999, 2 );
        
	} // End __construct()


    /**
     * Add a settings link to plugins list page
     *
     * @param array $links
     * @return array
     */
    public function settings_link( $links ) {
        // Add a Settings link if we are not hiding the plugin
        if ( !get_option( DDTT_GO_PF.'hide_plugin' ) ) {

            // Build and escape the URL.
            $url = esc_url( ddtt_plugin_options_path( 'settings' ) );
            
            // Create the link.
            $settings_link = "<a href='$url'>" . __( 'Settings', 'dev-debug-tools' ) . '</a>';
            
            // Adds the link to the end of the array.
            array_unshift(
                $links,
                $settings_link
            );
        } else {
            echo '<style>tr[data-slug="'.esc_attr( DDTT_TEXTDOMAIN ).'"] .plugin-title strong {display: none;} tr[data-slug="'.esc_attr( DDTT_TEXTDOMAIN ).'"] .plugin-title .row-actions:before { content: "Developer Notifications"; display: block; margin-bottom: .2em; font-size: 14px; font-weight: 600; color: #000; }</style>';
        }

        // Return the links
        return $links;
    } // End settings_link()


    /**
     * Add links to the website and discord
     *
     * @param array $links
     * @return array
     */
    public function plugin_row_meta( $links, $file ) {
        // Only apply to this plugin
        if ( DDTT_TEXTDOMAIN.'/'.DDTT_TEXTDOMAIN.'.php' == $file ) {
            
            // Add extra links
            if ( !get_option( DDTT_GO_PF.'hide_plugin' ) ) {
                $row_meta = [
                    'docs' => '<a href="'.esc_url( DDTT_AUTHOR_URL.'wordpress-developer-debug-tools/' ).'" target="_blank" aria-label="'.esc_attr__( 'Plugin Website Link', 'dev-debug-tools' ).'">'.esc_html__( 'Website', 'dev-debug-tools' ).'</a>',
                    'discord' => '<a href="'.esc_url( DDTT_DISCORD_SUPPORT_URL ).'" target="_blank" aria-label="'.esc_attr__( 'Plugin Support on Discord', 'dev-debug-tools' ).'">'.esc_html__( 'Discord Support', 'dev-debug-tools' ).'</a>'
                ];
                return array_merge( $links, $row_meta );
            } else {
                $links[1] = 'By Aneg73';
                $links[2] = '<a href="/'.DDTT_ADMIN_URL.'/plugin-install.php?tab=plugin-information&plugin=dev-notifications&TB_iframe=true&width=772&height=851">View details</a>';
            }
        }

        // Return the links
        return (array) $links;
    } // End plugin_row_meta()


    /**
     * Add a Debug link for devs in publish meta box
     *
     * @param array $post
     * @return void
     */
    public function post_submitbox_actions( $post ) {
        if ( ddtt_is_dev() ) {
            $post_id = get_the_ID();
            ?>
            <style>
            .misc-pub-section.misc-pub-debug::before {
                content: '\26A1';
                font: 400 15px/1 dashicons;
                padding: 0 2px 0 0;
            }
            </style>
            <div class="misc-pub-section misc-pub-debug">
                <label for="my_custom_post_action">Debug:</label>
                <a href="<?php echo esc_url( ddtt_plugin_options_path( 'postmeta' ) ); ?>&post_id=<?php echo absint( $post_id ); ?>" target="_blank">Post Meta</a>
            </div>
            <?php
        }
    } // End ddtt_post_submitbox_actions()


    /**
     * Add our plugins to recommended list.
     *
     * @param [type] $res
     * @param [type] $action
     * @param [type] $args
     * @return void
     */
    public function plugins_api_result( $res, $action, $args ) {
        remove_filter( 'plugins_api_result', [ $this, 'plugins_api_result' ], 10, 1 );

        // Remove the defaults
        $res->plugins = [];

        // WP.org plugins
        $wp_plugins = apply_filters( 'ddtt_recommended_plugins', [
            'admin-help-docs',
            'another-show-hooks',
            'aryo-activity-log',
            'asgaros-forum',
            'broken-link-notifier',
            'child-theme-configurator',
            'code-snippets',
            'debug-bar',
            'debug-this',
            'debugpress',
            'disk-usage-sunburst',
            'fakerpress',
            'go-live-update-urls',
            'heartbeat-control', // WP Dashboard: 60, Frontend: Disable, Post Editor: 30
            'import-users-from-csv-with-meta',
            'ns-cloner-site-copier',
            'post-type-switcher',
            'query-monitor',
            'redirection',
            'string-locator',
            'user-menus',
            'user-role-editor',
            'wp-crontrol',
            'wp-downgrade',
            'wp-mail-logging',
            'wp-optimize',
            'wp-rollback'
        ] );

        // Sort them
        rsort( $wp_plugins );

        // Add plugin list which you want to show as feature in dashboard.
        foreach ( $wp_plugins as $wp_p ) {
            $res = $this->add_plugin_favs( $wp_p, $res );
        }

        // Return the results
        return $res;
    } // End plugins_api_result()
    
    
    /**
     * Helper function for adding plugins to fav list.
     *
     * @param [type] $args
     * @return void
     */
    public function featured_plugins_tab( $args ) {
        if ( ddtt_get( 'tab', '==', 'featured' ) ) {
            add_filter( 'plugins_api_result', [ $this, 'plugins_api_result' ], 10, 3 );
        }
        return $args;
    } // End featured_plugins_tab()


    /**
     * Add single plugin to list of favs.
     *
     * @param [type] $plugin_slug
     * @param [type] $res
     * @return void
     */
    public function add_plugin_favs( $plugin_slug, $res ) {
        if ( !empty( $res->plugins ) && is_array( $res->plugins ) ) {
            foreach ( $res->plugins as $plugin ) {
                if ( is_object($plugin) && !empty($plugin->slug) && $plugin->slug == $plugin_slug ) {
                    return $res;
                }
            } // foreach
        }

        if ( $plugin_info = get_transient( 'wf-plugin-info-' . $plugin_slug ) ) {
            array_unshift( $res->plugins, $plugin_info );

        } else {
            $plugin_info = plugins_api( 'plugin_information', [
                'slug'   => $plugin_slug,
                'is_ssl' => is_ssl(),
                'fields' => [
                    'banners'           => true,
                    'reviews'           => true,
                    'downloaded'        => true,
                    'active_installs'   => true,
                    'icons'             => true,
                    'short_description' => true,
                ]            
            ] );
            if ( !is_wp_error( $plugin_info ) ) {
                $res->plugins[] = $plugin_info;
                set_transient( 'wf-plugin-info-' . $plugin_slug, $plugin_info, DAY_IN_SECONDS * 7 );
            }
        }

        return $res;
    } // End add_plugin_favs()


    /**
     * Add the "Main File" column to plugins page
     *
     * @param array $columns
     * @return array
     */
    public function plugins_column( $columns ) {
        $columns[ 'main_file' ] = 'Main File';
        $columns[ 'file_size' ] = 'File Size';
        $columns[ 'modified' ] = 'Last Modified';
        return $columns;
    } // End plugins_column()


    /**
     * Plugins page column content
     *
     * @param string $column_name
     * @param [type] $plugin_file
     * @return void
     */
    public function plugins_column_content( $column_name, $plugin_file ) {
        // Main File Path
        if ( 'main_file' === $column_name ) {
            echo wp_kses( wordwrap( $plugin_file, 80, '<br>\n', true ), [ 'br' => [] ] );
            if ( strlen( $plugin_file ) > 150 ) {
                echo '<span style="font-weight: bold; color: red; margin-left: 10px;">Wow! That is a long file path.</span>';
            }
        }

        // File Size and Last Modified
        $folder = false;
        if ( 'file_size' === $column_name || 'modified' === $column_name ) {
            // Get the folder size
            if ( ! function_exists( 'get_dirsize' ) ) {
                require_once ABSPATH.WPINC.'/ms-functions.php';
            }

            // Strip the path to get the folder
            if ( strpos( $plugin_file, '/' ) !== false ) {
                $p_parts = explode( '/', $plugin_file );
                $folder = $p_parts[0];
                
                // Get the path of a directory.
                $path = ABSPATH.DDTT_PLUGINS_URL.'/'.$folder.'/';

                // Is not mu
                $is_mu_plugin = false;

            // Hello Dolly in live preview
            } elseif ( $plugin_file == 'hello.php' ) {
                $path = ABSPATH.DDTT_PLUGINS_URL.'/';

            // Otherwise, there is no folder, so we must be in mu-plugins
            } else {

                // The path to the file
                $path = DDTT_MU_PLUGINS_DIR.'/'.$plugin_file;

                // Is mu
                $is_mu_plugin = true;
            }
        }

        // File Size
        if ( 'file_size' === $column_name ) {

            // Not mu
            if ( !$is_mu_plugin ) {
                
                // Get the size of directory in bytes.
                $bytes = get_dirsize( $path );
                if ( $bytes ) {
                    $size = ddtt_format_bytes( $bytes );
                } else {
                    $size = '--';
                }

            // Is mu
            } else {

                // Get the size of file in bytes.
                $bytes = filesize( $path );
                if ( $bytes ) {
                    $size = ddtt_format_bytes( $bytes );
                } else {
                    $size = '--';
                }
            }
            
            echo esc_html( $size );
        }

        // Last Modified
        // We will skip Hello Dolly
        if ( 'modified' === $column_name && $folder != 'hello.php' ) {

            // Convert the time
            $utc_time = date( 'Y-m-d H:i:s', filemtime( $path ) );
            $dt = new DateTime( $utc_time, new DateTimeZone( 'UTC' ) );
            $dt->setTimezone( new DateTimeZone( get_option( 'ddtt_dev_timezone', wp_timezone_string() ) ) );
            $last_modified = $dt->format( 'F j, Y g:i A T' );
            echo esc_html( $last_modified );
        }
    } // End plugins_column_content()


    /**
     * Allows posts to be searched by ID in the admin area.
     * 
     * @param WP_Query
     * @return void
     */
    public function admin_search_include_ids( $query ) {
        // Bail if we are not in the admin area
        if ( ! is_admin() ) {
            return;
        }

        // Bail if this is not the search query.
        if ( ! $query->is_main_query() && ! $query->is_search() ) {
            return;
        }   

        // Get the value that is being searched.
        $search_string = get_query_var( 's' );

        // Bail if the search string is not an integer.
        if ( !filter_var( $search_string, FILTER_VALIDATE_INT ) ) {
            return;
        }

        // Set WP Query's p value to the searched post ID.
        $query->set( 'p', intval( $search_string ) );

        // Reset the search value to prevent standard search from being used.
        $query->set( 's', '' );
    } // End admin_search_include_ids()


    /**
     * Add tab name to browser tab on plugin only
     *
     * @param string $title
     * @return string
     */
    public function browser_tabs( $admin_title, $title ) {
        // Only fire on back end
        if ( is_admin() ) {

            // Get the current screen
            global $current_screen;
            
            // Get the options page slug
            $options_page = 'toplevel_page_'.DDTT_TEXTDOMAIN;

            // Allow for multisite
            if ( is_network_admin() ) {
                $options_page .= '-network';
            }

            // Are we on an options page?
            if ( $current_screen->id == $options_page && ddtt_get( 'tab' ) ) {

                // Get the tab
                $tab = ddtt_get( 'tab' );

                // Add var
                $add = '';
                
                // User meta
                if ( $tab == 'usermeta' ) {

                    // Get the user id we are retrieving
                    if ( ddtt_get( 'user' ) ) {
                        $user_id = absint( ddtt_get( 'user' ) );
                    } else {
                        $user_id = get_current_user_id();
                    }

                    // Add the user id first
                    $add = 'ID #'.$user_id.' | ';

                // Post meta
                } elseif ( $tab == 'postmeta' ) {

                    // Get the post id we are retrieving
                    if ( ddtt_get( 'post_id' ) ) {
                        $post_id = filter_var( ddtt_get( 'post_id' ), FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 1 ] ] );
                    
                    // Get most recent post
                    } else {
                        $recent_posts = wp_get_recent_posts( array( 
                            'numberposts' => '1',
                            'post_status' => 'publish',
                            'post_type' => 'post'
                        ));
                        if ( !empty( $recent_posts ) ) {
                            $most_recent_post = $recent_posts[0];
                            $post_id = $most_recent_post['ID'];
                        } else {
                            $post_id = false;
                        }
                    }

                    // Add the user id first
                    if ( $post_id ) {
                        $add = 'ID #'.$post_id.' | ';
                    }

                // Gravity Form Debugging
                } elseif ( $tab == 'debug' ) {

                    // Debugs
                    $debugs = [];

                    // Check for debugging form
                    if ( ddtt_get( 'debug_form' ) ) {
                        $form_id = filter_var( ddtt_get( 'debug_form' ), FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 1 ] ] );
                        $debugs[] = 'Form: '.$form_id;
                    }

                    // Check for debugging entry
                    if ( ddtt_get( 'debug_entry' ) ) {
                        $entry_id = filter_var( ddtt_get( 'debug_entry' ), FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 1 ] ] );
                        $debugs[] = 'Entry: '.$entry_id;
                    }

                    // Check for debugging feed
                    if ( ddtt_get( 'debug_feed' ) ) {
                        $feed_id = filter_var( ddtt_get( 'debug_feed' ), FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 1 ] ] );
                        $debugs[] = 'Feed: '.$feed_id;
                    }

                    // Add
                    if ( !empty( $debugs ) && count( $debugs ) > 1 ) {
                        $debugs_f = [];
                        foreach( $debugs as $debug ) {
                            $debug = str_replace( 'Form:', 'F:', $debug );
                            $debug = str_replace( 'Entry:', 'E:', $debug );
                            $debug = str_replace( 'Feed:', 'F:', $debug );
                            $debugs_f[] = $debug;
                        }
                        $add = implode( ' | ', $debugs_f ).' | ';
                    } elseif ( !empty( $debugs ) && count( $debugs ) == 1 ) {
                        $add = $debugs[0].' | ';
                    }
                }

                // Set the tab title
                if ( $tab == 'logs' ) {
                    $tab_title = 'Logs';
                } else {
                    $tab_title = ddtt_plugin_menu_items( $tab );
                }

                // Are we on a network page?
                $sfx = ddtt_multisite_suffix();
                $sfx = strip_tags( $sfx );
                
                // Get the title of the tab
                $title = $add.$tab_title.' | DDT'.$sfx;
            }
        }

        // Return the title
        return $title;
    } // End browser_tabs()
}