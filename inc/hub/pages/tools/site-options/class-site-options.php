<?php
/**
 * Site Options
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class SiteOptions {

    /**
     * Whether to cache the option sources detection
     *
     * @var bool
     */
    public const CACHE_SITE_OPTION_SOURCES = false;


    /**
     * Get a list of core options
     *
     * @return array
     */
    private static function get_core_option_names() : array {
        $core_options = [
            // General
            'admin_email',
            'new_admin_email',
            'adminhash',
            'blogdescription',
            'blogname',
            'date_format',
            'gmt_offset',
            'site_icon',
            'home',
            'siteurl',
            'start_of_week',
            'time_format',
            'timezone_string',
            'users_can_register',
            'default_role',
            'auto_update_core_major',
            'auto_update_core_minor',
            'auto_update_core_dev',
            'admin_color',
            'show_admin_bar_front',
            'enable_xmlrpc',
            'enable_term_meta_cache',

            // Media
            'upload_path',
            'upload_url_path',
            'uploads_use_yearmonth_folders',
            'thumbnail_size_w',
            'thumbnail_size_h',
            'thumbnail_crop',
            'medium_size_w',
            'medium_size_h',
            'large_size_w',
            'large_size_h',
            'medium_large_size_w',
            'medium_large_size_h',
            'embed_size_w',
            'embed_size_h',
            'upload_filetypes',

            // Comments
            'default_pingback_flag',
            'default_comment_status',
            'default_ping_status',
            'require_name_email',
            'comment_registration',
            'close_comments_for_old_posts',
            'close_comments_days_old',
            'show_comments_cookies_opt_in',
            'thread_comments',
            'thread_comments_depth',
            'page_comments',
            'comments_per_page',
            'default_comments_page',
            'comment_order',
            'comments_notify',
            'moderation_notify',
            'comment_moderation',
            'comment_whitelist',
            'comment_max_links',
            'moderation_keys',
            'blacklist_keys',

            // Avatars
            'show_avatars',
            'avatar_rating',
            'avatar_default',

            // Permanent Links
            'permalink_structure',
            'category_base',
            'tag_base',

            // Writing
            'default_category',
            'default_post_format',
            'use_smilies',
            'use_balanceTags',
            'use_trackback',
            'mailserver_url',
            'mailserver_login',
            'mailserver_pass',
            'mailserver_port',
            'default_email_category',
            'ping_sites',

            // Reading
            'blog_public',
            'blog_charset',
            'page_on_front',
            'page_for_posts',
            'show_on_front',
            'posts_per_page',
            'posts_per_rss',
            'rss_use_excerpt',

            // Theme
            'template',
            'stylesheet',

            // Others
            'active_plugins',
            'recently_edited',
            'image_default_link_type',
            'image_default_size',
            'image_default_align',
            'sidebars_widgets',
            'sticky_posts',
            'widget_categories',
            'widget_text',
            'widget_rss',
            'html_type',
            'wp_page_for_privacy_policy',
            'wp_user_roles',
            'rewrite_rules',
            'https_detection_errors',
            'links_updated_date_format',
            'initial_db_version',
            'db_version',
            'page_on_privacy_policy',
            'salt',
            'secret_key',
            'logged_in_key',
            'auth_key',

            // Multisite
            'fileupload_maxk',
            'site_admins',
            'upload_space_check_disabled',
            'upload_space_check_disabled_for_network',
            'allowedthemes',
            'network_site_terms_agreed',
            'active_sitewide_plugins',
            'default_site',
            'network_admin_email',
            'network_name',
            'network_public',
            'network_site_name',
            'new_site_notification',
            'new_user_notification',
        ];

        /**
         * Filter the list of core option names.
         */
        $core_options = apply_filters( 'ddtt_core_option_names', $core_options );

        return $core_options;        
    } // End get_core_option_names()


    /**
     * Get the options for tool.
     *
     * @return array
     */
    public static function settings() : array {
        $deleted_options_link = get_option( 'ddtt_deleted_site_options' ) ? sprintf(
            ' <a id="scroll-to-deleted-options" href="#">%s</a>',
            __( 'View Deleted Options', 'dev-debug-tools' )
        ) : '';

        return [
            'general' => [
                'label' => __( 'Options', 'dev-debug-tools' ),
                'fields' => [
                    'options.php' => [
                        'title'     => __( "All Site Options", 'dev-debug-tools' ),
                        'desc'      => __( "&#9888; Warning: This page allows direct access to your site settings. You can break things here. Please be cautious!", 'dev-debug-tools' ),
                        'type'      => 'button',
                        'label'    => __( 'Go to Options Page', 'dev-debug-tools' ),
                        'onclick'   => 'window.open("' . esc_url( Bootstrap::admin_url( 'options.php' ) ) . '", "_blank")',
                    ],
                    'bulk_delete' => [
                        'title'     => __( "Enable Bulk Delete", 'dev-debug-tools' ),
                        'desc'      => __( "&#9888; Warning: This allows you to delete your site settings in bulk, which can cause your site to break if you accidentally delete ones you shouldn't. Please be careful! It is highly recommended to back-up your site first.", 'dev-debug-tools' ) . $deleted_options_link,
                        'type'      => 'checkbox',
                        'default'   => false,
                    ],                    
                    'lookup' => [
                        'title'     => __( 'Look Up Option', 'dev-debug-tools' ),
                        'desc'      => __( 'Enter the option name to retrieve the value.', 'dev-debug-tools' ),
                        'type'      => 'search',
                        'nonce'     => 'ddtt_site_option_lookup',
                        'scroll_to' => 'ddtt-tool-section',
                    ],
                ]
            ]
        ];
    } // End settings()


    /**
     * Nonce for saving settings
     *
     * @var string
     */
    private $nonce = 'ddtt_bulk_delete_nonce';


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?SiteOptions $instance = null;


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
        add_action( 'ddtt_header_notices', [ $this, 'render_header_notices' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_ddtt_bulk_delete', [ $this, 'ajax_bulk_delete' ] );
        add_action( 'wp_ajax_nopriv_ddtt_bulk_delete', '__return_false' );
    } // End __construct()


    /**
     * Render header notices
     *
     * This method is called to render notices in the header.
     * It checks for deleted options and displays a notice if any were deleted.
     */
    public function render_header_notices() {
        if ( AdminMenu::get_current_page_slug() !== 'dev-debug-tools' || AdminMenu::current_tool_slug() !== 'site-options' ) {
            return;
        }

        $deleted = get_transient( 'ddtt_deleted_these_site_options' );
        if ( ! empty( $deleted ) && is_array( $deleted ) ) {
            delete_transient( 'ddtt_deleted_these_site_options' );
            $deleted = array_map( 'sanitize_text_field', $deleted );
            $list = implode( ', ', array_map( fn( $opt ) => '<code>' . esc_html( $opt ) . '</code>', $deleted ) );
            Helpers::render_notice( '<strong>Deleted option(s):</strong> ' . $list, 'success' );
        }
    } // End render_header_notices()

    
    /**
     * Get a list of the tools.
     *
     * This method returns an array of tool links.
     *
     * @return array
     */
    public static function get_site_options() : array {
        global $wpdb;

        try {
            // phpcs:ignore
            $db_options = $wpdb->get_results(
                "SELECT option_name, option_value, autoload FROM {$wpdb->options}",
                ARRAY_A
            );

            if ( is_wp_error( $db_options ) || ! is_array( $db_options ) ) {
                throw new \Exception( 'Error fetching site options from DB.' );
            }
        } catch ( \Exception $e ) {
            Helpers::write_log( __( 'Error fetching site options: ', 'dev-debug-tools' ) . $e->getMessage() );
            $db_options = [];
        }

        $raw_options = [];
        $option_autoload_status = [];

        foreach ( $db_options as $option ) {
            $name = $option[ 'option_name' ];
            $value = $option[ 'option_value' ];
            $autoload = $option[ 'autoload' ];

            $raw_options[ $name ] = $value;
            $option_autoload_status[ $name ] = $autoload;
        }

        $reg_settings = get_registered_settings();
        $_option_groups = [];

        foreach ( $reg_settings as $name => $setting_args ) {
            if ( ! isset( $raw_options[ $name ] ) ) {
                $raw_options[ $name ] = get_option( $name );
                if ( ! isset( $option_autoload_status[ $name ] ) ) {
                    $option_autoload_status[ $name ] = 'unknown';
                }
            }
            if ( isset( $setting_args[ 'option_group' ] ) ) {
                $_option_groups[ $name ] = $setting_args[ 'option_group' ];
            }
        }

        $sources = get_transient( 'ddtt_option_sources' );
        if ( ! self::CACHE_SITE_OPTION_SOURCES || ! $sources ) {
            $sources = self::detect_option_sources(); 
            set_transient( 'ddtt_option_sources', $sources, HOUR_IN_SECONDS );
        }

        $all_options = [];
        foreach ( $raw_options as $name => $value ) {
            $all_options[ $name ] = [
                'value'    => $value,
                'source'   => $sources[ $name ] ?? [
                    'type' => 'unknown',
                    'name' => 'Unknown'
                ],
                'autoload' => $option_autoload_status[ $name ] ?? 'unknown',
                'group'    => $_option_groups[ $name ] ?? 'general',
                'size'     => strlen( maybe_serialize( $value ) ),
            ];
        }

        uksort( $all_options, function( $a, $b ) {
            return strcasecmp( $a, $b );
        } );

        return $all_options;
    } // End get_site_options()


    /**
     * Test the autoload status of a given option.
     *
     * @param string $option_name The name of the option to test.
     */
    public static function test_option_autoload_status( $option_name ) {
        global $wpdb;

        try {
            // phpcs:ignore
            $result = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT option_name, autoload FROM {$wpdb->options} WHERE option_name = %s",
                    $option_name
                ),
                ARRAY_A
            );

            if ( $result ) {
                Helpers::print_r( sprintf(
                    /* Translators: %1$s is the option name, %2$s is the autoload value in DB. */
                    __( 'Option: %1$s, Autoload value in DB: %2$s', 'dev-debug-tools' ),
                    $result['option_name'],
                    $result['autoload']
                ) );
            } else {
                Helpers::print_r( sprintf(
                    /* Translators: %s is the option name that was not found in the options table. */
                    __( 'Option %s not found in options table.', 'dev-debug-tools' ),
                    $option_name
                ) );
            }

        } catch ( \Exception $e ) {
            Helpers::write_log( __( 'Error fetching option autoload status: ', 'dev-debug-tools' ) . $e->getMessage() );
        }
    } // End test_option_autoload_status()


    /**
     * Check if a given option is autoloaded.
     *
     * @param string $option_name The name of the option.
     * @return string|null 'yes', 'no', or null if not found.
     */
    public static function get_option_autoload_status( $option_name ) {
        global $wpdb;

        try {
            // phpcs:ignore
            $autoload = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT autoload FROM {$wpdb->options} WHERE option_name = %s",
                    $option_name
                )
            );

            // Ensure we always return a string if DB returns null
            return $autoload !== null ? (string) $autoload : 'unknown';

        } catch ( \Exception $e ) {
            Helpers::write_log(
                __( 'Error fetching autoload status for option: ', 'dev-debug-tools' ) . $option_name
                . ' - ' . $e->getMessage()
            );
            return 'unknown';
        }
    } // End get_option_autoload_status()


    /**
     * Detect the sources of site options
     * 
     * @param string|null $option_name Option name to check (optional)
     *
     * @return array
     */
    public static function detect_option_sources( $option_name = null ) : array {
        $sources = [];

        // Otherwise find them
        $core_options = self::get_core_option_names();
        foreach ( $core_options as $core_option ) {
            $sources[ $core_option ] = [
                'type' => 'core',
                'name' => 'Core (WordPress)',
            ];
        }

        if ( $option_name !== null && isset( $sources[ $option_name ] ) ) {
            return [ $option_name => $sources[ $option_name ] ];
        }

        $paths = [
            'theme'     => get_theme_root(),
            'plugin'    => WP_PLUGIN_DIR,
            'mu-plugin' => WPMU_PLUGIN_DIR
        ];

        foreach ( $paths as $type => $base_path ) {
            if ( !is_dir( $base_path ) ) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator( $base_path, \RecursiveDirectoryIterator::SKIP_DOTS )
            );

            foreach ( $iterator as $file ) {
                if ( $file->getExtension() !== 'php' ) {
                    continue;
                }

                $file_path = $file->getPathname();

                $contents = file_get_contents( $file_path );
                if ( !$contents ) {
                    continue;
                }

                if ( preg_match_all( '/(?:get|add|update|delete|register)_option\s*\(\s*[\'"]([^\'"]+)[\'"]/', $contents, $matches ) ) {
                    foreach ( $matches[1] as $option ) {
                        if ( $option_name !== null && $option !== $option_name ) {
                            continue;
                        }

                        if ( !isset( $sources[ $option ] ) ) {
                            $rel_path = str_replace( $base_path . '/', '', $file_path );
                            $rel_path_parts = explode( '/', $rel_path );
                            $slug = $rel_path_parts[0];
                            $full_name = self::get_plugin_or_theme_name( $type, $slug, $base_path );

                            $sources[ $option ] = [
                                'type' => $type,
                                'name' => ucfirst( $type ) . ': ' . $full_name,
                            ];

                            if ( $option_name !== null ) {
                                return [ $option_name => $sources[ $option_name ] ];
                            }
                        }
                    }
                }
            }
        }

        return $sources;
    } // End ddtt_detect_option_sources()


    /**
     * Get the plugin or theme name from its slug
     *
     * @param string $type 'plugin', 'mu-plugin', 'theme', or 'core'
     * @param string $slug The slug of the plugin or theme
     * @param string $base_path The base path to search in
     * @return string
     */
    private static function get_plugin_or_theme_name( string $type, string $slug, string $base_path ): string {
        if ( $type === 'plugin' ) {
            $plugin_dir = trailingslashit( $base_path ) . $slug;   
            if ( is_dir( $plugin_dir ) ) {
                $plugin_files = glob( $plugin_dir . '/*.php' );
                if ( $plugin_files ) {
                    foreach ( $plugin_files as $file ) {
                        $data = get_plugin_data( $file );
                        if ( ! empty( $data[ 'Name' ] ) ) {
                            return $data[ 'Name' ];
                        }
                    }
                }
            } elseif ( is_file( $plugin_dir . '.php' ) ) {
                $data = get_plugin_data( $plugin_dir . '.php' );
                if ( ! empty( $data[ 'Name' ] ) ) {
                    return $data[ 'Name' ];
                }
            }
            return $slug;
        }

        if ( $type === 'mu-plugin' ) {
            $file = trailingslashit( $base_path ) . $slug . '.php';
            if ( file_exists( $file ) ) {
                $data = get_plugin_data( $file );
                if ( ! empty( $data[ 'Name' ] ) ) {
                    return $data[ 'Name' ];
                }
            }

            $mu_dir = trailingslashit( $base_path ) . $slug;
            if ( is_dir( $mu_dir ) ) {
                $php_files = glob( $mu_dir . '/*.php' );
                foreach ( $php_files as $file ) {
                    $data = get_plugin_data( $file );
                    if ( ! empty( $data[ 'Name' ] ) ) {
                        return $data[ 'Name' ];
                    }
                }
            }

            return $slug;
        }

        if ( $type === 'theme' ) {
            $themes = wp_get_themes();
            foreach ( $themes as $key => $theme ) {
                if ( $key === $slug || $theme->get_stylesheet() === $slug || $theme->get_template() === $slug ) {
                    return $theme->get( 'Name' );
                }
            }
            return $slug;
        }

        return 'Core (WordPress)';
    } // End ddtt_get_plugin_or_theme_name()


    /**
     * Enqueue assets
     *
     * @param string $hook The current admin page hook.
     * @return void
     */
    public function enqueue_assets( $hook ) : void {
        if ( ! AdminMenu::is_current_screen( $hook, 'tools', 'site-options' ) ) {
            return;
        }
        
        wp_localize_script( 'ddtt-tool-site-options', 'ddtt_site_options', [
            'nonce' => wp_create_nonce( $this->nonce ),
            'i18n'  => [
                'confirmationNotice' => __( 'Select options to delete and click the button above. This action cannot be undone!', 'dev-debug-tools' ),
                'confirmDelete'      => __( 'Are you sure you want to delete the selected options? This action cannot be undone!', 'dev-debug-tools' ),
                'noneSelected'       => __( 'No options selected.', 'dev-debug-tools' ),
                'error'              => __( 'Error deleting options.', 'dev-debug-tools' ),
                'deleting'           => __( 'Deleting options...', 'dev-debug-tools' ),
            ],
        ] );
    } // End enqueue_assets()


    /**
     * Handle AJAX request to bulk delete options
     *
     * @return void
     */
    public function ajax_bulk_delete() {
        check_ajax_referer( $this->nonce, 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        $options = isset( $_POST[ 'options' ] ) ? wp_unslash( $_POST[ 'options' ] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

        if ( empty( $options ) || ! is_array( $options ) ) {
            wp_send_json_error( 'no_options' );
        }

        $deleted = [];
        foreach ( $options as $option ) {
            $option = sanitize_text_field( $option );

            if ( $option === 'ddtt_deleted_site_options' ) {
                continue;
            }

            if ( in_array( $option, self::get_core_option_names(), true ) ) {
                continue;
            }

            delete_option( $option );
            $deleted[] = $option;
        }

        // Log it
        if ( $deleted ) {
            $user = wp_get_current_user();
            $user_id = $user->exists() ? $user->ID : 0;

            Helpers::write_log( sprintf(
                'Deleted options by %s: %s',
                $user->exists() ? $user->user_login . ' (ID ' . $user_id . ')' : 'Unknown user',
                implode( ', ', $deleted )
            ) );

            set_transient( 'ddtt_deleted_these_site_options', $deleted, 60 * 5 ); // 5 minutes

            if ( ! in_array( 'ddtt_deleted_site_options', $deleted, true ) ) {
                $all_deletions = get_option( 'ddtt_deleted_site_options', [] );
                $timestamp = time();
                $all_deletions[ $timestamp ] = [
                    'user'    => $user_id,
                    'options' => $deleted,
                ];
                update_option( 'ddtt_deleted_site_options', $all_deletions );
            }
        }

        wp_send_json_success( [ 'deleted' => $deleted ] );
    } // End ajax_bulk_delete()


    /**
     * Prevent cloning and unserializing
     */
    public function __clone() {}
    public function __wakeup() {}
    
}


SiteOptions::instance();