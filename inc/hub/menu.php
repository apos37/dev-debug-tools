<?php
/**
 * Admin Menu and Dynamic Pages Loader
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class AdminMenu {

    /**
     * Page slugs and labels
     */
    public static function pages() : array {
        return [
            'welcome'   => __( 'Welcome', 'dev-debug-tools' ),
            'dashboard' => __( 'Dashboard', 'dev-debug-tools' ),
            'tools'     => __( 'Tools', 'dev-debug-tools' ),
            'resources' => __( 'Resources', 'dev-debug-tools' ),
            'settings'  => __( 'Settings', 'dev-debug-tools' ),
            'changelog' => __( 'Changelog', 'dev-debug-tools' ),
        ];
    } // End pages()


    /**
     * Tool slugs and labels
     */
    private function tools() : array {
        return [
            'logs' => [
                'name'        => __( "Logs", 'dev-debug-tools' ),
                'description' => __( "Debug log, error logs, activity logs, and any custom logs you added in Settings.", 'dev-debug-tools' ),
                'include_menu' => true,
            ],
            'metadata' => [
                'name'         => __( "Metadata", 'dev-debug-tools' ),
                'description'  => __( "View and manage user meta, post meta, and other metadata.", 'dev-debug-tools' ),
                'include_menu' => true,
            ],
            'db-tables' => [
                'name'        => __( "Database Tables", 'dev-debug-tools' ),
                'description' => __( "View your database tables and their records.", 'dev-debug-tools' ),
                'include_menu' => true,
            ],
            'wpconfig' => [
                'name'        => __( "WP-CONFIG", 'dev-debug-tools' ),
                'description' => __( "View and edit your <code>wp-config.php</code> file.", 'dev-debug-tools' ),
                'include_menu' => true,
            ],
            'htaccess' => [
                'name'        => __( "HTACCESS", 'dev-debug-tools' ),
                'description' => __( "View and edit your <code>.htaccess</code> file.", 'dev-debug-tools' ),
                'include_menu' => true,
            ],
            'site-options' => [
                'name'        => __( "Site Options", 'dev-debug-tools' ),
                'description' => __( "View the site's options and delete any that are not needed.", 'dev-debug-tools' ),
                'include_menu' => true,
            ],
            'globals' => [
                'name'        => __( "Globals", 'dev-debug-tools' ),
                'description' => __( "A list of available global variables.", 'dev-debug-tools' ),
                'include_menu' => true,
            ],
            'defines' => [
                'name'        => __( "Defined Constants", 'dev-debug-tools' ),
                'description' => __( "A list of all defined constants.", 'dev-debug-tools' ),
                'include_menu' => true,
            ],
            'transients' => [
                'name'        => __( "Transients", 'dev-debug-tools' ),
                'description' => __( "View and delete transients set by your site.", 'dev-debug-tools' ),
                'include_menu' => true,
            ],
            'cookies' => [
                'name'        => __( "Cookies", 'dev-debug-tools' ),
                'description' => __( "View and delete cookies set by your site.", 'dev-debug-tools' ),
                'include_menu' => true,
            ],
            'sessions' => [
                'name'        => __( "Sessions", 'dev-debug-tools' ),
                'description' => __( "View and delete sessions set by your site.", 'dev-debug-tools' ),
                'include_menu' => true,
            ],
            'post-types' => [
                'name'        => __( "Post Types", 'dev-debug-tools' ),
                'description' => __( "View your post type settings and associated taxonomies from one place.", 'dev-debug-tools' ),
                'include_menu' => true,
            ],
            'taxonomies' => [
                'name'        => __( "Taxonomies", 'dev-debug-tools' ),
                'description' => __( "View your taxonomy settings and associated post types from one place.", 'dev-debug-tools' ),
                'include_menu' => true,
            ],
            'auto-drafts' => [
                'name'        => __( "Auto Drafts", 'dev-debug-tools' ),
                'description' => __( "View and delete auto drafts from your posts.", 'dev-debug-tools' ),
                'include_menu' => true,
            ],
            'signups' => [
                'name'        => __( "Signups", 'dev-debug-tools' ),
                'description' => __( "View and manage user signups.", 'dev-debug-tools' ),
                'include_menu' => true,
            ],
            'server' => [
                'name'        => __( "Server Info", 'dev-debug-tools' ),
                'description' => __( "Available server metrics.", 'dev-debug-tools' ),
                'include_menu' => true,
            ],
            'shortcodes' => [
                'name'        => __( "Shortcodes", 'dev-debug-tools' ),
                'description' => __( "View all registered shortcodes and find what pages they are used on.", 'dev-debug-tools' ),
                'include_menu' => true,
            ],
            'cron-jobs' => [
                'name'        => __( "Cron Jobs", 'dev-debug-tools' ),
                'description' => __( "View your scheduled WordPress cron jobs.", 'dev-debug-tools' ),
                'include_menu' => true,
            ],
            'apis' => [
                'name'        => __( "REST APIs", 'dev-debug-tools' ),
                'description' => __( "View your site's REST APIs and quickly check their status.", 'dev-debug-tools' ),
                'include_menu' => true,
            ],
            'php-ini' => [
                'name'        => __( "PHP.INI", 'dev-debug-tools' ),
                'description' => __( "All registered configuration options from your <code>php.ini</code>.", 'dev-debug-tools' ),
                'include_menu' => true,
            ],
            'php-info' => [
                'name'        => __( "PHP Info", 'dev-debug-tools' ),
                'description' => __( "Information about your PHP's configuration.", 'dev-debug-tools' ),
                'include_menu' => true,
            ],
            'discord' => [
                'name'        => __( "Discord Messenger", 'dev-debug-tools' ),
                'description' => __( "Send test messages to your Discord channel using a webhook.", 'dev-debug-tools' ),
                'include_menu' => true,
            ],
            'testing' => [
                'name'        => __( "Testing", 'dev-debug-tools' ),
                'description' => __( "Test PHP code straight from the page.", 'dev-debug-tools' ),
                'include_menu' => true,
            ],
        ];
    } // End tools()


    /**
     * Tools array indexed by slug
     *
     * @var array[]
     */
    private static array $tools = [];


    /**
     * The paths to the file editor assets
     */
    private function file_editor_assets() : array {
        return [
            'wpconfig' => ABSPATH . 'wp-config.php',
            'htaccess' => ABSPATH . '.htaccess'
        ];
    } // End file_editor_assets()


    /**
     * Integrations array indexed by slug
     *
     * @var array[]
     */
    private array $integrations = [];


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?AdminMenu $instance = null;


    /**
     * Get the singleton instance
     *
     * @return self
     */
    public static function instance() : self {
        return self::$instance ??= new self();
    } // End instance()


    /**
     * Constructor
     */
    private function __construct() {
        add_action( 'admin_init', [ $this, 'discover_tools' ] );
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_init', [ $this, 'require_page_classes' ] );
        add_action( 'admin_init', [ $this, 'require_tool_classes' ] );
        add_action( 'admin_init', [ $this, 'instantiate_file_editor_assets' ] );
        add_filter( 'admin_title', [ $this, 'admin_title' ], 10, 2 );
        add_action( 'admin_body_class', [ $this, 'add_admin_body_class' ] );
        add_action( 'current_screen', [ $this, 'maybe_remove_screen_options' ] );
        add_action( 'admin_head' , [ $this, 'preload_fonts' ] );
        add_action( 'admin_init', [ $this, 'discover_integrations' ] );
        add_action( 'admin_init', [ $this, 'require_integration_classes' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        add_action( 'wp_ajax_ddtt_save_mode', [ $this, 'ajax_save_mode' ] );
        add_action( 'wp_ajax_nopriv_ddtt_save_mode', [ $this, 'ajax_save_mode' ] );
        add_action( 'wp_ajax_ddtt_dismiss_whats_new', [ $this, 'ajax_dismiss_whats_new' ] );
        add_action( 'wp_ajax_nopriv_ddtt_dismiss_whats_new', [ $this, 'ajax_dismiss_whats_new' ] );
        add_action( 'wp_ajax_ddtt_save_test_mode', [ $this, 'ajax_save_test_mode' ] );
        add_action( 'wp_ajax_nopriv_ddtt_save_test_mode', [ $this, 'ajax_save_test_mode' ] );
    } // End __construct()


    /**
     * Register admin menu (single page; pages rendered within)
     *
     * @return void
     */
    public function register_menu() : void {
        // Die if not admin
        if ( ! current_user_can( 'administrator' ) ) {
            return;
        }

        if ( ! get_option( 'ddtt_disable_error_counts', true ) ) {
            $total_lines = get_option( 'ddtt_total_error_count', 0 );
        } else {
            $total_lines = 0;
        }

        $incl_count = $total_lines > 0 ? '<span class="ddtt-error-count update-plugins count-' . $total_lines . '"><span class="update-count">' . $total_lines . '</span></span>' : '';

        add_menu_page(
            Bootstrap::name(),
            Bootstrap::name() . Helpers::multisite_suffix() . $incl_count,
            'manage_options',
            'dev-debug-dashboard',
            [ $this, 'render_page' ],
            Helpers::icon(),
            2
        );

        // Hidden pages
        $hidden_pages = [ 'welcome' ];

        $is_dev = Helpers::is_dev();

        foreach ( self::pages() as $slug => $label ) {
            $parent_slug = in_array( $slug, $hidden_pages, true ) ? '' : 'dev-debug-dashboard';

            add_submenu_page(
                $parent_slug,
                $label,
                $label,
                'manage_options',
                'dev-debug-' . $slug,
                [ $this, 'render_page' ]
            );

            // If we're on the tools page, add any favorited tools as submenus
            if ( $slug === 'tools' && $is_dev ) {
                $favorited_tools = filter_var_array( get_option( 'ddtt_favorite_tools', [] ), FILTER_SANITIZE_SPECIAL_CHARS );
                if ( ! empty( $favorited_tools ) && is_array( $favorited_tools ) ) {

                    $saved_tools = filter_var_array( get_option( 'ddtt_tools', [] ), FILTER_SANITIZE_SPECIAL_CHARS );
                    $enabled_saved_tools = [];
                    if ( is_array( $saved_tools ) ) {
                        foreach ( $saved_tools as $tool_info ) {
                            if ( $tool_info[ 'enabled' ] ) {
                                $enabled_saved_tools[] = $tool_info[ 'slug' ];
                            }
                        }
                    }

                    $all_tools = array_keys( $this->tools() );

                    $favorited_in_order = [];
                    foreach ( $enabled_saved_tools as $tool_slug ) {
                        if ( in_array( $tool_slug, $favorited_tools, true ) && in_array( $tool_slug, $all_tools, true ) ) {
                            $favorited_in_order[] = $tool_slug;
                        }
                    }

                    // Check if there are any favorited tools not already in $favorited_in_order
                    foreach ( $all_tools as $tool_slug ) {
                        if ( in_array( $tool_slug, $favorited_tools, true ) && ! in_array( $tool_slug, $favorited_in_order, true ) ) {
                            $favorited_in_order[] = $tool_slug;
                        }
                    }

                    foreach ( $favorited_in_order as $fav_slug ) {
                        $label = $this->tools()[ $fav_slug ][ 'name' ];
                        $url = Bootstrap::tool_url( $fav_slug );

                        add_submenu_page(
                            'dev-debug-dashboard',
                            $label,
                            '⇢ ' . $label,
                            'manage_options',
                            'dev-debug-tools&tool=' . $fav_slug,
                            $url
                        );
                    }
                }
            }
        }
    } // End register_menu()


    /**
     * Check if current page is a DDTT page
     *
     * @return bool
     */
    public function is_ddtt_page() : bool {
        $our_pages = array_keys( self::pages() );
        $current_slug = self::get_current_page_slug();
        foreach ( $our_pages as $slug ) {
            if ( $current_slug == 'dev-debug-' . $slug ) {
                return true;
            }
        }
        return false;
    } // End is_ddtt_page()


    /**
     * Instantiate page classes
     *
     * This is called to ensure all page classes are loaded and available.
     * It should be called after the menu is registered.
     *
     * @return void
     */
    public function require_page_classes() : void {
        foreach ( self::pages() as $slug => $label ) {
            $class_file = Bootstrap::path( 'inc/hub/pages/' . $slug . '/class-' . $slug . '.php' );
            if ( file_exists( $class_file ) ) {
                require_once $class_file;
            }
        }

        // Add additional page classes here if they exist
        require_once Bootstrap::path( 'inc/hub/pages/dashboard/class-issues.php' );
    } // End require_page_classes()


    /**
     * Get the current page slug
     *
     * @return string
     */
    public static function get_current_page_slug() : string {
        return isset( $_GET[ 'page' ] ) ? sanitize_key( wp_unslash( $_GET[ 'page' ] ) ) : ''; // phpcs:ignore
    } // End get_current_page_slug()


    /**
     * Render tool page
     *
     * @return void
     */
    public function render_page() : void {
        $screen = self::get_current_page_slug();
        $slug = str_replace( 'dev-debug-', '', $screen );
        
        if ( $slug !== 'welcome' && ! get_option( 'ddtt_developers', false ) ) {
            $page_file = Bootstrap::path( 'inc/hub/pages/welcome/page-welcome.php' );
            if ( file_exists( $page_file ) ) {
                $page_url = Bootstrap::page_url( 'welcome' );
                wp_safe_redirect( $page_url );
            }
        }

        if ( $slug === 'tools' && ! Helpers::is_dev() ) {
            $tool_slug = self::current_tool_slug();
            if ( $tool_slug !== '' ) {
                $page_url = Bootstrap::page_url( 'tools' );
                wp_safe_redirect( $page_url );
            }
        }

        $page_file = '';
        $tool = false;
        
        require Bootstrap::path( 'inc/hub/header.php' );

            // If we're on a tool page, load the tool class
            if ( $slug === 'tools' ) {
                $tool = $this->current_tool_data();
                if ( ! empty( $tool ) ) {
                    $enabled = $tool[ 'enabled' ] ?? true;
                    if ( ! $enabled ) {
                        echo '<h2>' . esc_html__( 'This tool is currently disabled.', 'dev-debug-tools' ) . '</h2>
                        <br>
                        <p><a class="ddtt-button" href="' . esc_url( Bootstrap::page_url( 'tools' ) ) . '">' . esc_html__( 'Return to Tools Dashboard', 'dev-debug-tools' ) . '</a></p>';
                    } else {
                        $page_file = $tool[ 'page' ];
                    }
                } else {
                    $tool_slug = self::current_tool_slug();
                    if ( ! empty( $tool_slug ) && ! isset( self::$tools[ $tool_slug ] ) ) {
                        wp_die(
                            '<div class="ddtt-page-not-found-message">' . esc_html( ErrorMessages::page_not_found() ) . '</div>',
                            '',
                            [ 'response' => 404 ]
                        );
                    }
                }
            }

            // Otherwise, load the normal page if it exists
            if ( ! $tool && empty( $page_file ) ) {
                $page_file = Bootstrap::path( 'inc/hub/pages/' . $slug . '/page-' . $slug . '.php' );
            }

            // If the page file exists, include it
            if ( file_exists( $page_file ) ) {
                include $page_file;
            } elseif ( ! $tool ) {
                // Translators: %s is the slug of the missing page.
                echo sprintf( esc_html__( 'Silly wabbit! There is no page file for %s.', 'dev-debug-tools' ), esc_html( $slug ) );
            }

        require Bootstrap::path( 'inc/hub/footer.php' );
    } // End render_page()


    /**
     * Discover pages by scanning page page.php and reading headers
     *
     * @return void
     */
    public function discover_tools() : void {
        $root_path = Bootstrap::path( 'inc/hub/pages/tools/' );
        $root_url  = Bootstrap::url( 'inc/hub/pages/tools/' );

        if ( ! is_dir( $root_path ) ) return;

        $dirs = glob( $root_path . '*/', GLOB_ONLYDIR );
        if ( ! is_array( $dirs ) ) return;

        $tools_enabled = get_option( 'ddtt_tools', [] );
        $tool_labels = $this->tools();

        $enabled_map = [];
        $order_map = [];

        foreach ( $tools_enabled as $order => $tool_info ) {
            $slug = sanitize_key( $tool_info[ 'slug' ] ?? '' );
            if ( $slug ) {
                $enabled_map[ $slug ] = isset( $tool_info[ 'enabled' ] )
                    ? filter_var( $tool_info[ 'enabled' ], FILTER_VALIDATE_BOOLEAN )
                    : true;
                $order_map[ $slug ] = $order;
            }
        }

        $discovered_tools = [];

        foreach ( $dirs as $page_dir ) {
            $rel_dir = str_replace( $root_path, '', $page_dir );
            $slug    = sanitize_key( trim( $rel_dir, '/' ) );

            if ( $slug === '' ) continue;

            $page_file  = $page_dir . "page-{$slug}.php";

            if ( ! file_exists( $page_file ) ) continue;

            $class_file = $page_dir . "class-{$slug}.php";

            $meta = $tool_labels && isset( $tool_labels[ $slug ] ) ? $tool_labels[ $slug ] : [];
            if ( empty( $meta ) ) {
                continue;
            }

            // Skip if include_menu is explicitly set to 'no'
            if ( isset( $meta[ 'include_menu' ] ) && strtolower( $meta[ 'include_menu' ] ) === 'no' ) {
                continue;
            }

            $name = isset( $meta[ 'name' ] ) && $meta[ 'name' ] !== '' ? $meta[ 'name' ] : ucwords( str_replace( [ '-', '_' ], ' ', $slug ) );
            $description = isset( $meta[ 'description' ] ) ? $meta[ 'description' ] : '';

            $order = $order_map[ $slug ] ?? false;

            $discovered_tools[ $slug ] = [
                'name'        => $name,
                'slug'        => $slug,
                'description' => $description,
                'dir_path'    => $page_dir,
                'dir_url'     => $root_url . $rel_dir,
                'page'        => $page_file,
                'class'       => $class_file,
                'style'       => $page_dir . 'styles.css',
                'script'      => $page_dir . 'scripts.js',
                'order'       => $order,
                'enabled'     => $enabled_map[ $slug ] ?? true,
            ];
        }

        if ( empty( $tools_enabled ) ) {
            uasort( $discovered_tools, function( $a, $b ) {
                return strcasecmp( $a[ 'name' ], $b[ 'name' ] );
            } );
        } else {
            uasort( $discovered_tools, function( $a, $b ) {
                return $a[ 'order' ] <=> $b[ 'order' ];
            } );
        }

        self::$tools = $discovered_tools;
    } // End discover_tools()


    /**
     * Get all tools in correct order
     *
     * @return array
     */
    public static function get_ordered_tool_data() : array {
        return self::$tools;
    } // End get_ordered_tool_data()


    /**
     * Check if we are logging activity
     *
     * @return boolean
     */
    public function is_logging_activity() : bool {
        $activities = get_option( 'ddtt_activity' );
        return ! empty( $activities ) && is_array( $activities );
    } // End is_logging_activity()


    /**
     * Instantiate tool classes
     *
     * This is called to ensure all tool classes are loaded and available.
     * It should be called after the menu is registered.
     *
     * @return void
     */
    public function require_tool_classes() : void {
        foreach ( self::$tools as $tool ) {
            if ( empty( $tool[ 'enabled' ] ) || ! $tool[ 'enabled' ] ) {
                continue;
            }

            $class_file = $tool[ 'class' ];
            if ( file_exists( $class_file ) ) {
                require_once $class_file;
            }
        }

        if ( $this->is_logging_activity() && ! class_exists( 'Activity_Log' ) ) {
            require_once Bootstrap::path( 'inc/hub/pages/tools/logs/class-activity.php' );
        }

        require_once Bootstrap::path( 'inc/helpers/file-editor/class-file-editor.php' );
    } // End require_tool_classes()


    /**
     * Instantiate file editor assets
     *
     * @return void
     */
    public function instantiate_file_editor_assets() : void {
        foreach ( $this->file_editor_assets() as $path ) {
            if ( file_exists( $path ) ) {
                FileEditor::instance( $path );
            }
        }
    } // End instantiate_file_editor_assets()


    /**
     * Get current page slug
     *
     * @return string
     */
    public static function current_tool_slug() : string {
        return isset( $_GET[ 'tool' ] ) ? sanitize_key( wp_unslash( $_GET[ 'tool' ] ) ) : ''; // phpcs:ignore
    } // End current_tool_slug()


    /**
     * Get current page data
     *
     * @return array
     */
    private function current_tool_data() : array {
        $slug = self::current_tool_slug();
        return $slug && isset( self::$tools[ $slug ] ) ? self::$tools[ $slug ] : [];
    } // End current_tool_data()


    /**
     * Output tool navigation (call from tools-header.php)
     *
     * @return void
     */
    public static function render_tool_navigation() : void {
        $base = admin_url( 'admin.php?page=dev-debug-tools' );

        // Define navigation sections
        $sections = [
            'Quick Links' => [
                'dashboard' => [ 'name' => __( 'Dashboard', 'dev-debug-tools' ) ],
                'tools'     => [ 'name' => __( 'Tools', 'dev-debug-tools' ) ],
                'resources' => [ 'name' => __( 'Resources', 'dev-debug-tools' ) ],
                'settings'  => [ 'name' => __( 'Settings', 'dev-debug-tools' ) ],
                'changelog' => [ 'name' => __( 'Changelog', 'dev-debug-tools' ) ],
            ]
        ];
        if ( Helpers::is_dev() ) { 
            $sections[ 'Tools' ] = self::$tools; 
        } 

        echo '<select id="ddtt-nav-dropdown">';

            echo '<option value="">' . esc_html__( 'Navigate to...', 'dev-debug-tools' ) . '</option>';

            foreach ( $sections as $section_label => $items ) {
                echo '<optgroup label="' . esc_attr( $section_label ) . '">';

                if ( $section_label == 'Quick Links' ) {
                    foreach ( $items as $slug => $data ) {
                        $url = admin_url( "admin.php?page=dev-debug-{$slug}" );
                        echo '<option value="' . esc_url( $url ) . '">' . esc_html( $data[ 'name' ] ) . '</option>';
                    }
                    continue;
                }

                foreach ( $items as $slug => $data ) {
                    $url = esc_url( $base . ( $slug ? '&tool=' . $slug : '' ) );
                    echo '<option value="' . esc_url( $url ) . '">' . esc_html( $data[ 'name' ] ) . '</option>';
                }

                echo '</optgroup>';
            }

        echo '</select>';
    } // End render_tool_navigation()


    /**
     * Admin title filter
     *
     * @param string $title Current admin title.
     * @param string $page Current page slug.
     * 
     * @return string
     */
    public function admin_title( $title, $page ) {
        if ( ! $this->is_ddtt_page() ) {
            return $title;
        }

        if ( self::current_tool_slug() ) {
            $tool = $this->current_tool_data();
            if ( ! empty( $tool ) && isset( $tool[ 'name' ] ) ) {
                $title = $tool[ 'name' ] . ' - ' . Bootstrap::name();
            } else {
                $title = __( "Developer Debug Tools", 'dev-debug-tools' );
            }
        }
        return $title;
    } // End admin_title()


    /**
     * Add custom body class for dark mode
     *
     * @param string $classes Existing body classes.
     * 
     * @return string
     */
    public function add_admin_body_class( $classes ) : string {
        if ( $this->is_ddtt_page()) {
            $classes .= ' ' . Bootstrap::textdomain();
        }
        if ( Helpers::is_dark_mode() ) {
            $classes .= ' ddtt-dark-mode';
        }
        if ( Bootstrap::is_test_mode() ) {
            $classes .= ' ddtt-test-mode';
        }
        return $classes;
    } // End add_admin_body_class()


    /**
     * Maybe remove screen options tab
     *
     * @param \WP_Screen $screen Current screen object.
     * 
     * @return void
     */
    public function maybe_remove_screen_options( $screen ) {
        if ( isset( $screen->id ) && strpos( $screen->id, 'dev-debug-' ) !== false ) {
            remove_all_actions( 'screen_options' );
            add_filter( 'screen_options_show_screen', '__return_false' );
        }
    } // End maybe_remove_screen_options()


    /**
     * Pre-load fonts for the admin area
     *
     * @return void
     */
    public function preload_fonts() : void {
        if ( ! $this->is_ddtt_page() ) {
            return;
        }

        $fonts = [
            'exo2',
            'michroma',
            'poppins',
            'poppins-bold'
        ];

        foreach ( $fonts as $font ) {
            echo '<link id="ddtt-font-' . esc_attr( $font ) . '" rel="preload" href="' . esc_url( Bootstrap::url( "inc/hub/fonts/{$font}.woff2" ) ) . '" as="font" type="font/woff2" crossorigin="anonymous">';
        }
    } // End preload_fonts()


    /**
     * Discover pages by scanning page page.php and reading headers
     *
     * @return void
     */
    public function discover_integrations() : void {
        $root_path = Bootstrap::path( 'inc/integrations/' );
        $root_url  = Bootstrap::url( 'inc/integrations/' );

        if ( ! is_dir( $root_path ) ) return;

        $dirs = glob( $root_path . '*/', GLOB_ONLYDIR );
        if ( ! is_array( $dirs ) ) return;

        $discovered_integrations = [];

        foreach ( $dirs as $page_dir ) {
            $rel_dir = str_replace( $root_path, '', $page_dir );
            $slug    = sanitize_key( trim( $rel_dir, '/' ) );

            if ( $slug === '' ) continue;

            $page_file  = $page_dir . "page-{$slug}.php";
            $class_file = $page_dir . "class-{$slug}.php";

            $discovered_integrations[ $slug ] = [
                'slug'        => $slug,
                'dir_path'    => $page_dir,
                'dir_url'     => $root_url . $rel_dir,
                'page'        => $page_file,
                'class'       => $class_file,
                'style'       => $page_dir . 'styles.css',
                'script'      => $page_dir . 'scripts.js'
            ];
        }

        $this->integrations = $discovered_integrations;
    } // End discover_integrations()


    /**
     * Instantiate integration classes
     *
     * This is called to ensure all integration classes are loaded and available.
     * It should be called after the menu is registered.
     *
     * @return void
     */
    public function require_integration_classes() : void {
        foreach ( $this->integrations as $integration ) {
            $class_file = $integration[ 'class' ];
            if ( file_exists( $class_file ) ) {
                require_once $class_file;
            }
        }
    } // End require_integration_classes()


    /**
     * Check if the current screen matches the given hook
     *
     * @return bool
     */
    public static function is_current_screen( $hook, $page_slug, $tool_slug = null ) : bool {
        $expected_start = 'developer-debug-tools';
        $expected_end = '_page_dev-debug-' . $page_slug;

        if ( strpos( $hook, $expected_start ) !== 0 || substr( $hook, -strlen( $expected_end ) ) !== $expected_end ) {
            return false;
        }

        if ( ! is_null( $tool_slug ) && self::current_tool_slug() !== $tool_slug ) {
            return false;
        }

        return true;
    } // End is_current_screen()


    /**
     * Enqueue assets for current page only
     * 
     * @param string $hook Current admin page hook.
     *
     * @return void
     */
    public function enqueue_admin_assets( $hook ) : void {
        $version = Bootstrap::script_version();

        
        /**
         * Only load on our pages
         */
        if ( $hook !== 'toplevel_page_dev-debug-tools' && strpos( $hook, 'dev-debug-' ) === false ) {
            return;
        }
        
        $page = self::get_current_page_slug();
        $slug = str_replace( 'dev-debug-', '', $page );
        $tool = $this->current_tool_data();

        // All Hub
        $hub_style = Bootstrap::path( 'inc/hub/styles.css' );
        if ( file_exists( $hub_style ) ) {
            wp_enqueue_style(
                'ddtt-hub',
                Bootstrap::url( 'inc/hub/styles.css' ),
                [],
                $version
            );
        }

        $hub_dark_style = Bootstrap::path( 'inc/hub/styles-dark.css' );
        if ( file_exists( $hub_dark_style ) ) {
            wp_enqueue_style(
                'ddtt-hub-dark',
                Bootstrap::url( 'inc/hub/styles-dark.css' ),
                [],
                $version
            );
        }

        $hub_script = Bootstrap::path( 'inc/hub/scripts.js' );
        if ( file_exists( $hub_script ) ) {
            wp_enqueue_script(
                'ddtt-hub',
                Bootstrap::url( 'inc/hub/scripts.js' ),
                [ 'jquery' ],
                $version,
                true
            );

            wp_localize_script( 'ddtt-hub', 'ddtt_header', [
                'open_nav_new_tab'        => get_option( 'ddtt_open_nav_new_tab', false ),
                'nonce_save_mode'         => wp_create_nonce( 'ddtt_save_mode' ),
                'nonce_dismiss_whats_new' => wp_create_nonce( 'ddtt_dismiss_whats_new' ),
            ] );
        }

        // Pages
        // styles.css
        $page_style = Bootstrap::path( "inc/hub/pages/{$slug}/styles.css" );
        if ( file_exists( $page_style ) ) {
            wp_enqueue_style(
                "ddtt-page-{$slug}",
                Bootstrap::url( "inc/hub/pages/{$slug}/styles.css" ),
                [],
                $version
            );
        }

        // styles-dark.css
        $page_style_dark = Bootstrap::path( "inc/hub/pages/{$slug}/styles-dark.css" );
        if ( file_exists( $page_style_dark ) ) {
            wp_enqueue_style(
                "ddtt-page-{$slug}-dark",
                Bootstrap::url( "inc/hub/pages/{$slug}/styles-dark.css" ),
                [],
                $version
            );
        }

        // scripts.js
        $pages_with_sortable = [ 'resources', 'tools' ];
        if ( in_array( $slug, $pages_with_sortable, true ) && Helpers::is_dev() ) {
            $page_deps = [ 'jquery', 'jquery-ui-sortable' ];
        } else {
            $page_deps = [ 'jquery' ];
        }

        $page_script = Bootstrap::path( "inc/hub/pages/{$slug}/scripts.js" );
        if ( file_exists( $page_script ) ) {
            wp_enqueue_script(
                "ddtt-page-{$slug}",
                Bootstrap::url( "inc/hub/pages/{$slug}/scripts.js" ),
                $page_deps,
                $version,
                true
            );
            if ( in_array( $slug, $pages_with_sortable, true ) ) {
                wp_enqueue_script( 'jquery-ui-sortable' );
            }
        }

        // Tools
        if ( $tool ) {
            if ( $tool[ 'enabled' ] ) {
                if ( file_exists( $tool[ 'style' ] ) ) {
                    wp_enqueue_style(
                        'ddtt-tool-' . $tool[ 'slug' ],
                        $tool[ 'dir_url' ] . 'styles.css',
                        [],
                        $version
                    );
                }

                if ( file_exists( str_replace( 'styles.css', 'styles-dark.css', $tool[ 'style' ] ) ) ) {
                    wp_enqueue_style(
                        'ddtt-tool-' . $tool[ 'slug' ] . '-dark',
                        $tool[ 'dir_url' ] . 'styles-dark.css',
                        [],
                        $version
                    );
                }

                if ( file_exists( $tool[ 'script' ] ) ) {
                    wp_enqueue_script(
                        'ddtt-tool-' . $tool[ 'slug' ],
                        $tool[ 'dir_url' ] . 'scripts.js',
                        [ 'jquery' ],
                        $version,
                        true
                    );
                }
            }     
            
            if ( $tool[ 'slug' ] === 'wpconfig' || $tool[ 'slug' ] === 'htaccess' ) {
                wp_enqueue_style(
                    'ddtt-file-editor',
                    Bootstrap::url( 'inc/helpers/file-editor/styles.css' ),
                    [],
                    $version
                );

                wp_enqueue_script(
                    'ddtt-file-editor',
                    Bootstrap::url( 'inc/helpers/file-editor/scripts.js' ),
                    [ 'jquery' ],
                    $version,
                    true
                );
            }
        }

        // Integrations
        if ( !empty( $this->integrations ) ) {
            foreach ( $this->integrations as $integration ) {
                if ( file_exists( $integration[ 'style' ] ) ) {
                    wp_enqueue_style(
                        'ddtt-integration-' . $integration[ 'slug' ],
                        $integration[ 'dir_url' ] . 'styles.css',
                        [],
                        $version
                    );
                }

                if ( file_exists( str_replace( 'styles.css', 'styles-dark.css', $integration[ 'style' ] ) ) ) {
                    wp_enqueue_style(
                        'ddtt-integration-' . $integration[ 'slug' ] . '-dark',
                        $integration[ 'dir_url' ] . 'styles-dark.css',
                        [],
                        $version
                    );
                }

                if ( file_exists( $integration[ 'script' ] ) ) {
                    wp_enqueue_script(
                        'ddtt-integration-' . $integration[ 'slug' ],
                        $integration[ 'dir_url' ] . 'scripts.js',
                        [ 'jquery' ],
                        $version,
                        true
                    );
                }
            }
        }
    } // End enqueue_admin_assets()


    /**
     * AJAX handler to save dark mode preference
     *
     * @return void
     */
    public function ajax_save_mode() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( 'unauthorized' );
        }

        if ( ! check_ajax_referer( 'ddtt_save_mode', 'nonce', false ) ) {
            wp_send_json_error( 'invalid_nonce' );
        }

        $mode = ( isset( $_POST[ 'mode' ] ) && $_POST[ 'mode' ] === 'dark' ) ? 'dark' : 'light';
        update_user_meta( get_current_user_id(), 'ddtt_mode', $mode );

        wp_send_json_success();
    } // End ajax_save_mode()


    /**
     * AJAX handler to dismiss the "What's New" message
     *
     * @return void
     */
    public function ajax_dismiss_whats_new() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( 'unauthorized' );
        }

        if ( ! check_ajax_referer( 'ddtt_dismiss_whats_new', 'nonce', false ) ) {
            wp_send_json_error( 'invalid_nonce' );
        }

        update_option( 'ddtt_last_viewed_version', Bootstrap::version() );

        wp_send_json_success();
    } // End ajax_dismiss_whats_new()


    /**
     * AJAX handler to save test mode preference
     *
     * @return void
     */
    public function ajax_save_test_mode() {
        check_ajax_referer( 'ddtt_save_mode', 'nonce' );

        $mode = isset( $_POST[ 'mode' ] ) ? (int) $_POST[ 'mode' ] : 0;
        update_option( 'ddtt_test_mode', $mode );

        wp_send_json_success();
    } // End ajax_save_test_mode()

    
    /**
     * Prevent cloning and unserializing
     */
    public function __clone() {}
    public function __wakeup() {}

}


AdminMenu::instance();