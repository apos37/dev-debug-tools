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
     * Recommended plugins
     *
     * @var array
     */
    private $recommended_plugins = [
        'dev-debug-tools',
        'another-show-hooks',
        'aryo-activity-log',
        'asgaros-forum',
        'broken-link-notifier',
        'child-theme-configurator',
        'clear-cache-everywhere',
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
        'simple-maintenance-redirect',
        'string-locator',
        'user-menus',
        'user-role-editor',
        'wp-crontrol',
        'wp-downgrade',
        'wp-mail-logging',
        'wp-optimize',
        'wp-rollback'
    ];


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
        add_filter( 'install_plugins_tabs', [ $this, 'add_plugins_tab' ] );
        add_action( 'install_plugins_dev_debug_tools', [ $this, 'render_add_plugins_tab' ] );        

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
        $text_domain = DDTT_TEXTDOMAIN;
        if ( $text_domain . '/' . $text_domain . '.php' == $file ) {
            
            // Add extra links
            if ( !get_option( DDTT_GO_PF.'hide_plugin' ) ) {
                $guide_url = DDTT_GUIDE_URL;
                $docs_url = DDTT_DOCS_URL;
                $support_url = DDTT_SUPPORT_URL;
                $plugin_name = DDTT_NAME;

                $our_links = [
                    'guide' => [
                        // translators: Link label for the plugin's user-facing guide.
                        'label' => __( 'How-To Guide', 'dev-debug-tools' ),
                        'url'   => $guide_url
                    ],
                    'docs' => [
                        // translators: Link label for the plugin's developer documentation.
                        'label' => __( 'Developer Docs', 'dev-debug-tools' ),
                        'url'   => $docs_url
                    ],
                    'support' => [
                        // translators: Link label for the plugin's support page.
                        'label' => __( 'Support', 'dev-debug-tools' ),
                        'url'   => $support_url
                    ],
                ];

                $row_meta = [];
                foreach ( $our_links as $key => $link ) {
                    // translators: %1$s is the link label, %2$s is the plugin name.
                    $aria_label = sprintf( __( '%1$s for %2$s', 'dev-debug-tools' ), $link[ 'label' ], $plugin_name );
                    $row_meta[ $key ] = '<a href="' . esc_url( $link[ 'url' ] ) . '" target="_blank" aria-label="' . esc_attr( $aria_label ) . '">' . esc_html( $link[ 'label' ] ) . '</a>';
                }

                // Add the links
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
     * Add a new tab for Dev Debug Tools.
     *
     * @param array $tabs Existing plugin install tabs.
     * @return array Modified tabs with custom "Dev Debug Tools" tab.
     */
    public function add_plugins_tab( $tabs ) {
        $tabs[ 'dev_debug_tools' ] = __( 'Dev Debug Tools', 'dev-debug-tools' );
        return $tabs;
    } // End add_plugins_tab()


    /**
     * Render the custom "Dev Debug Tools" tab content
     *
     * @return void
     */
    public function render_add_plugins_tab() {
        echo '<div class="wrap">';

        require_once ABSPATH . 'wp-admin/includes/class-wp-plugin-install-list-table.php';

        $table = new WP_Plugin_Install_List_Table();

        // List of plugin slugs for "Dev Debug Tools" tab
        $plugin_slugs = $this->recommended_plugins;

        // Store the plugins here
        $plugins = [];

        // Loop through the slugs and get plugin information
        foreach ( $plugin_slugs as $slug ) {
            $response = plugins_api( 'plugin_information', [
                'slug'   => $slug,
                'is_ssl' => is_ssl(),
                'fields' => [
                    'banners'           => true,
                    'reviews'           => true,
                    'downloaded'        => true,
                    'active_installs'   => true,
                    'icons'             => true,
                    'short_description' => true,
                ],
            ]);

            if ( is_wp_error( $response ) ) {
                echo '<p>' . __( 'Error fetching plugin details. ' . $response->get_error_message() . '. Please try again later.', 'apos37' ) . '</p>';
                return;
            }

            if ( $response ) {
                $plugins[] = $response;
            }
        }

        // Inject the plugins data (we assume plugins exist)
        $table->items = $plugins;

        // Since we are only showing a fixed set of plugins, no pagination needed
        $table->set_pagination_args([
            'total_items' => count( $plugins ),
            'per_page'    => count( $plugins ), // Display all plugins
            'total_pages' => 1,
        ]);

        $table->display();

        echo '</div>';
    } // End render_add_plugins_tab()


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
        $columns[ 'added_by' ] = 'Added By';
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

        // Is not mu by default
        $is_mu_plugin = false;

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
            $utc_time = gmdate( 'Y-m-d H:i:s', filemtime( $path ) );
            $dt = new DateTime( $utc_time, new DateTimeZone( 'UTC' ) );
            $dt->setTimezone( new DateTimeZone( get_option( 'ddtt_dev_timezone', wp_timezone_string() ) ) );
            $last_modified = $dt->format( 'F j, Y g:i A T' );
            echo esc_html( $last_modified );
        }

        // Added By
        if ( 'added_by' === $column_name ) {
            $added_by = get_option( 'ddtt_plugins_added_by', [ ] );

            if ( ! empty( $added_by )
                && is_array( $added_by )
                && isset( $added_by[ 'plugins' ][ $plugin_file ] )
            ) {
                $user_id = absint( $added_by[ 'plugins' ][ $plugin_file ] );
                $display_name = '';

                if ( $user_id > 0 ) {
                    $user = get_user_by( 'ID', $user_id );
                    if ( $user ) {
                        $display_name = $user->display_name;
                    } elseif ( isset( $added_by[ 'user_ids' ][ $user_id ] ) ) {
                        $display_name = $added_by[ 'user_ids' ][ $user_id ];
                    }
                }

                if ( $display_name !== '' ) {
                    echo '<span>' . esc_html( $display_name ) . '</span>';
                } else {
                    echo '<em>' . __( 'Unknown', 'dev-debug-tools' ) . '</em>';
                }
            } else {
                echo '<em>' . __( 'Unknown', 'dev-debug-tools' ) . '</em>';
            }
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
                if ( $tab == 'activity' ) {
                    $tab_title = 'Activity Logs';
                } elseif ( $tab == 'logs' ) {
                    $tab_title = 'Error Logs';
                } else {
                    $tab_title = ddtt_plugin_menu_items( $tab );
                }

                // Are we on a network page?
                $sfx = ddtt_multisite_suffix();
                $sfx = wp_strip_all_tags( $sfx );
                
                // Get the title of the tab
                $title = $add.$tab_title.' | DDT'.$sfx;
            }
        }

        // Return the title
        return $title;
    } // End browser_tabs()
}