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
        // Add debug links
        add_action( 'post_submitbox_misc_actions', [ $this, 'post_submitbox_actions' ] );

        // Add plugins to featured plugins list
        add_filter( 'install_plugins_table_api_args_featured', [ $this, 'featured_plugins_tab' ] );

        // Add javascript to footer of plugin install page
        global $pagenow;
        if ( $pagenow == 'plugin-install.php' ) {
            add_action( 'admin_footer', [ $this, 'plugin_featured_tab' ] );
        }

        // Add columns to plugins page
        add_filter( 'manage_plugins_columns', [ $this, 'plugins_column' ] );

        // Plugins page column content
        add_action( 'manage_plugins_custom_column', [ $this, 'plugins_column_content' ], 10, 2 );
        
	} // End __construct()


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
                <a href="<?php echo esc_url( ddtt_plugin_options_path( 'post_meta' ) ); ?>&post_id=<?php echo absint( $post_id ); ?>" target="_blank">Post Meta</a>
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
            'asgaros-forum',
            'go-live-update-urls',
            'heartbeat-control', // WP Dashboard: 60, Frontend: Disable, Post Editor: 30
            'import-users-from-csv-with-meta',
            'post-type-switcher',
            'query-monitor',
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
            $res = self::add_plugin_favs( $wp_p, $res );
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
        add_filter( 'plugins_api_result', [ $this, 'plugins_api_result' ], 10, 3 );
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
            $plugin_info = plugins_api('plugin_information', array(
            'slug'   => $plugin_slug,
            'is_ssl' => is_ssl(),
            'fields' => array(
                'banners'           => true,
                'reviews'           => true,
                'downloaded'        => true,
                'active_installs'   => true,
                'icons'             => true,
                'short_description' => true,
            )
            ));
            if ( !is_wp_error($plugin_info) ) {
                $res->plugins[] = $plugin_info;
                set_transient( 'wf-plugin-info-' . $plugin_slug, $plugin_info, DAY_IN_SECONDS * 7 );
            }
        }

        return $res;
    } // End add_plugin_favs()


    /**
     * Plugin page > replace the "Featured" link with what we want
     *
     * @return void
     */
    public function plugin_featured_tab() {
        echo '<script>
        var featuredLink = document.querySelector(".plugin-install-featured a");
        featuredLink.innerHTML = "Recommended by '.esc_html( DDTT_NAME ).'";
        </script>';
    } // End featured_tab()


    /**
     * Add the "Main File" column to plugins page
     *
     * @param array $columns
     * @return array
     */
    function plugins_column( $columns ) {
        $columns['main_file'] = 'Main File';
        $columns['file_size'] = 'File Size';
        $columns['modified'] = 'Last Modified';
        return $columns;
    } // End plugins_column()


    /**
     * Plugins page column content
     *
     * @param string $column_name
     * @param [type] $plugin_file
     * @return void
     */
    function plugins_column_content( $column_name, $plugin_file ) {
        // Main File Path
        if ( 'main_file' === $column_name ) {
            echo esc_html( $plugin_file );
        }

        // File Size and Last Modified
        if ( 'file_size' === $column_name || 'modified' === $column_name ) {
            // Get the folder size
            if ( ! function_exists( 'get_dirsize' ) ) {
                require_once ABSPATH . WPINC . '/ms-functions.php';
            }

            // Strip the path to get the folder
            $p_parts = explode('/', $plugin_file);
            $folder = $p_parts[0];
             
            // Get the path of a directory.
            $directory = get_home_path().DDTT_PLUGINS_URL.'/'.$folder.'/';
        }

        // File Size
        if ( 'file_size' === $column_name ) {

            // Get the size of directory in bytes.
            $bytes = get_dirsize( $directory );
            
            // Get the MB
            $folder_size = ddtt_format_bytes( $bytes );
            echo esc_html( $folder_size );
        }

        // Last Modified
        if ( 'modified' === $column_name ) {
            // Get the last modified date
            $last_modified = date( 'F j, Y g:i A', filemtime( $directory ) );
            echo esc_html( $last_modified );
        }
    } // End plugins_column_content()
}