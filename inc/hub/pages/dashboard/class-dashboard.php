<?php
/**
 * Dashboard
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Dashboard {

    /**
     * Nonce for dashboard
     *
     * @var string
     */
    private $nonce_action = 'ddtt_dashboard_nonce_action';
    private $nonce_field = 'ddtt_dashboard_nonce_field';


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?Dashboard $instance = null;


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
        if ( isset( $_POST[ 'ddtt-download-important-files' ] ) ) { // phpcs:ignore
            $this->download_important_files();
        }

        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_ddtt_check_issue', [ $this, 'ajax_check_issue' ] );
    } // End __construct()


    /**
     * Handle the download important files request.
     */
    public function download_important_files() {
        if ( !isset( $_POST[ 'ddtt-download-important-files' ] ) || ! isset( $_POST[ $this->nonce_field ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $this->nonce_field ] ) ), $this->nonce_action ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized access.' );
        }

        $files_to_backup = [
            ABSPATH . 'wp-config.php',
            ABSPATH . '.htaccess',
            get_theme_file_path( 'functions.php' ),
            // Add other files here
        ];

        $zip = new \ZipArchive();
        $zip_name = 'wp-important-backup-' . Helpers::convert_timezone( time(), 'Y-m-d-H-i-s' ) . '.zip';
        $zip_path = wp_upload_dir()[ 'basedir' ] . '/' . $zip_name;

        if ( $zip->open( $zip_path, \ZipArchive::CREATE ) === TRUE ) {
            foreach ( $files_to_backup as $file ) {
                if ( file_exists( $file ) ) {
                    $zip->addFile( $file, basename( $file ) );
                }
            }
            $zip->close();

            header( 'Content-Type: application/zip' );
            header( 'Content-Disposition: attachment; filename="' . $zip_name . '"' );
            header( 'Content-Length: ' . filesize( $zip_path ) );

            global $wp_filesystem;
            if ( ! $wp_filesystem ) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                WP_Filesystem();
            }
            echo $wp_filesystem->get_contents( $zip_path ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

            wp_delete_file( $zip_path );
            exit;
        } else {
            wp_die( esc_html( __( 'Could not create backup zip.', 'dev-debug-tools' ) ) );
        }
    } // End download_important_files()


    /**
     * Render the dashboard page.
     */
    public static function get_versions() : array {
        $plugin_version = Bootstrap::version();
        $updates_url = Bootstrap::admin_url( 'update-core.php' );

        // Plugin Version
        $plugin_warning = '';
        $latest_plugin = self::get_latest_plugin_version();
        if ( version_compare( $plugin_version, $latest_plugin, '<' ) ) {
            $plugin_warning = '<div class="tooltip"><a href="' . $updates_url . '"><span class="warning-symbol"></span></a>
                <span class="tooltiptext">' . __( 'A newer version of this plugin is available', 'dev-debug-tools' ) . ' (' . $latest_plugin . ')</span>
            </div>';
        }

        // WordPress Version
        $wp_warning = '';
        global $wp_version;
        $latest_wp = get_site_transient( 'update_core' );
        if ( is_object( $latest_wp ) && isset( $latest_wp->updates[0]->version ) && $wp_version !== $latest_wp->updates[0]->version ) {
            $wp_warning = '<div class="tooltip"><a href="' . $updates_url . '"><span class="warning-symbol"></span></a>
                <span class="tooltiptext">' . __( 'A newer version of WordPress is available', 'dev-debug-tools' ) . ' (' . $latest_wp->updates[0]->version . ')</span>
            </div>';
        }

        // PHP Version
        $php_warning = '';
        $php_version = phpversion();
        $latest_php = self::get_latest_php_version( true );
        if ( floatval( $php_version ) < floatval( $latest_php ) ) {
            $php_warning = '<div class="tooltip"><span class="warning-symbol"></span>
                <span class="tooltiptext">' . __( 'A new major version of PHP is available', 'dev-debug-tools' ) . ' (' . $latest_php . '.x)</span>
            </div>';
        }

        // MySQL Version
        global $wpdb;
        $mysql_version = $wpdb->db_version();

        // jQuery Version
        $jquery_version = false;
        if ( isset( $GLOBALS[ 'wp_scripts' ] ) && $GLOBALS[ 'wp_scripts' ] instanceof \WP_Scripts ) {
            $jquery_version = $GLOBALS[ 'wp_scripts' ]->registered[ 'jquery' ]->ver ?? false;
            if ( $jquery_version ) {
                $jquery_version = sanitize_text_field( wp_unslash( $jquery_version ) );
            }
        }

        // cURL Version
        $curl_version = false;
        if ( function_exists( 'curl_version' ) ) {
            $curl_data = curl_version();
            if ( ! empty( $curl_data['version'] ) ) {
                $curl_version = sanitize_text_field( wp_unslash( $curl_data['version'] ) );
            }
        }        

        // GD Library Version
        $gd_version = false;
        if ( defined( 'GD_VERSION' ) ) {
            $gd_version = GD_VERSION;
        } elseif ( function_exists( 'gd_info' ) ) {
            $gd_info = gd_info();
            $gd_version = $gd_info['GD Version'] ?? false;
        }

        // Return all versions and warnings
        return [
            'plugin'         => $plugin_version,
            'plugin_warning' => $plugin_warning,
            'wp'             => $wp_version,
            'wp_warning'     => $wp_warning,
            'php'            => $php_version,
            'php_warning'    => $php_warning,
            'mysql'          => $mysql_version,
            'curl'           => $curl_version,
            'jquery'         => $jquery_version,
            'gd'             => $gd_version,
        ];
    } // End get_versions()


    /**
     * Get the latest plugin version from the WordPress.org API.
     *
     * @return string
     */
    public static function get_latest_plugin_version() : string {
        if ( ! function_exists( 'plugins_api' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        }

        $api = plugins_api( 'plugin_information', [
            'slug'   => Bootstrap::textdomain(),
            'fields' => [
                'versions'          => false,
                'downloaded'        => false,
                'active_installs'   => false,
                'ratings'           => false,
                'reviews'           => false,
                'short_description' => false,
                'sections'          => false,
                'screenshots'       => false,
                'tags'              => false,
                'last_updated'      => false,
            ],
        ] );

        if ( is_wp_error( $api ) || empty( $api->version ) ) {
            return Bootstrap::version();
        }

        return $api->version;
    } // End get_latest_plugin_version()


    /**
     * Get the latest PHP version from the official PHP releases API.
     *
     * @param bool $major_only Whether to return only the major version.
     * 
     * @return string|int
     */
    public static function get_latest_php_version( $major_only = false ) : string|int {
        $response = wp_remote_get( 'https://www.php.net/releases/?json' );
        if ( is_wp_error( $response ) || empty( $response[ 'body' ] ) ) {
            return 0;
        }

        $releases = json_decode( $response[ 'body' ] );
        if ( ! is_object( $releases ) || empty( $releases ) ) {
            return 0;
        }

        $latest_major = max( array_map( 'intval', array_keys( (array) $releases ) ) );

        return $major_only ? (int) $latest_major : sanitize_text_field( $releases->$latest_major->version ?? '0.0.0' );
    } // End get_latest_php_version()


    /**
     * Get server metrics like load and memory usage.
     *
     * @return array
     */
    public static function get_server_metrics() : array {
        $load = function_exists( 'sys_getloadavg' ) ? sys_getloadavg()[0] : 'N/A';
        $num_processors = false;

        if ( is_readable( '/proc/cpuinfo' ) ) {
            $cpuinfo = @file_get_contents( '/proc/cpuinfo' );
            $num_processors = $cpuinfo !== false ? substr_count( $cpuinfo, 'processor' ) : false;
        }

        $load_percentage = ( $load !== 'N/A' && $num_processors )
            ? round( ( $load / $num_processors ) * 100, 2 )
            : 'N/A';

        $load_class = 'good';
        if ( $load_percentage !== 'N/A' ) {
            $load_value = (float) $load_percentage;
            $load_class = match ( true ) {
                $load_value < 10 => __( 'optimal', 'dev-debug-tools' ),
                $load_value < 50 => __( 'excellent', 'dev-debug-tools' ),
                $load_value < 70 => __( 'good', 'dev-debug-tools' ),
                $load_value < 80 => __( 'moderate', 'dev-debug-tools' ),
                $load_value < 90 => __( 'high', 'dev-debug-tools' ),
                $load_value <= 100 => __( 'critical', 'dev-debug-tools' ),
                default => __( 'overload', 'dev-debug-tools' ),
            };
        }

        $memory_usage_percentage = 'N/A';
        $memory_class = 'good';

        if ( is_readable( '/proc/meminfo' ) ) {
            $meminfo = @file( '/proc/meminfo' );
            if ( $meminfo !== false ) {
                $memory_data = [];
                foreach ( $meminfo as $line ) {
                    [ $key, $value ] = explode( ':', $line, 2 ) + [ null, null ];
                    if ( $key && $value ) {
                        $memory_data[ trim( $key ) ] = trim( $value );
                    }
                }

                $total  = (int) str_replace( ' kB', '', $memory_data['MemTotal'] ?? 0 );
                $free   = (int) str_replace( ' kB', '', $memory_data['MemFree'] ?? 0 );
                $buff   = (int) str_replace( ' kB', '', $memory_data['Buffers'] ?? 0 );
                $cached = (int) str_replace( ' kB', '', $memory_data['Cached'] ?? 0 );

                $used = $total - $free - $buff - $cached;

                if ( $total > 0 ) {
                    $memory_usage_percentage = round( ( $used / $total ) * 100, 2 );
                    $memory_class = match ( true ) {
                        $memory_usage_percentage < 10 => __( 'optimal', 'dev-debug-tools' ),
                        $memory_usage_percentage < 50 => __( 'excellent', 'dev-debug-tools' ),
                        $memory_usage_percentage < 70 => __( 'good', 'dev-debug-tools' ),
                        $memory_usage_percentage < 80 => __( 'moderate', 'dev-debug-tools' ),
                        $memory_usage_percentage < 90 => __( 'high', 'dev-debug-tools' ),
                        default => __( 'critical', 'dev-debug-tools' ),
                    };
                }
            }
        }

        return [
            'load'                    => $load,
            'num_processors'          => $num_processors,
            'load_percentage'         => $load_percentage !== 'N/A' ? $load_percentage . '%' : 'N/A',
            'load_class'              => $load_class,
            'memory_usage_percentage' => $memory_usage_percentage,
            'memory_class'            => $memory_class,
        ];
    } // End get_server_metrics()


    /**
     * Format seconds into a human-readable uptime string.
     *
     * @param int $seconds Number of seconds to format.
     * @return string
     */
    public static function format_uptime( $seconds ) : string {
        $days = floor( $seconds / 86400 );
        $hours = floor( ( $seconds % 86400 ) / 3600 );
        $minutes = floor( ( $seconds % 3600 ) / 60 );

        $parts = [];
        if ( $days > 0 ) {
            $parts[] = $days . ' day' . ( $days > 1 ? 's' : '' );
        }
        if ( $hours > 0 ) {
            $parts[] = $hours . ' hour' . ( $hours > 1 ? 's' : '' );
        }
        if ( $minutes > 0 || empty( $parts ) ) {
            $parts[] = $minutes . ' minute' . ( $minutes > 1 ? 's' : '' );
        }

        return implode( ', ', $parts );
    } // End format_uptime()


    /**
     * Format bytes into a human-readable string.
     *
     * @param int $bytes Number of bytes to format.
     * @return string
     */
    public static function get_server_uptime() {
        $uptime = false;

        if ( is_readable( '/proc/uptime' ) ) {
            $contents = file_get_contents( '/proc/uptime' );
            if ( $contents !== false ) {
                $parts = explode( ' ', trim( $contents ) );
                $uptime = (int) $parts[0];
            }
        }

        if ( $uptime === false && function_exists( 'shell_exec' ) && !in_array( 'shell_exec', array_map( 'trim', explode( ',', ini_get( 'disable_functions' ) ) ) ) ) {
            $raw = shell_exec( 'uptime -p' );
            return [ 'raw' => trim( $raw ) ];
        }

        if ( $uptime !== false ) {
            return [
                'seconds'  => $uptime,
                'readable' => self::format_uptime( $uptime ),
            ];
        }

        return false;
    } // End get_server_uptime()


    /**
     * Get environment information.
     *
     * @return array
     */
    public static function get_environment_info() : array {
        $is_multisite = is_multisite();
        $blog_id = get_current_blog_id();
        
        $theme = wp_get_theme();
        $theme_name = $theme->get( 'Name' );
        $theme_version = $theme->get( 'Version' );

        $all_plugins = get_plugins();
        $active_plugins = get_option( 'active_plugins', [] );
        $mu_plugins = get_mu_plugins();

        $active_count = count( $active_plugins );
        $mu_count = count( $mu_plugins );
        $inactive_count = count( $all_plugins ) - count( $active_plugins );

        return [
            'is_multisite'     => $is_multisite,
            'blog_id'          => $blog_id,
            'theme'            => $theme_name,
            'theme_version'    => $theme_version,
            'active_plugins'   => $active_count,
            'mu_plugins'       => $mu_count,
            'inactive_plugins' => $inactive_count,
        ];
    } // End get_environment_info()


    /**
     * Enqueue assets
     *
     * @param string $hook The current admin page hook.
     * @return void
     */
    public function enqueue_assets( $hook ) : void {
        if ( $hook != 'toplevel_page_dev-debug-dashboard' ) {
            return;
        }

        wp_localize_script( 'ddtt-page-dashboard', 'ddtt_dashboard', [
            'nonce' => wp_create_nonce( $this->nonce_action ),
            'i18n' => [
                'checking'    => __( 'Checking', 'dev-debug-tools' ),
                'good'        => __( 'Good', 'dev-debug-tools' ),
                'issue_found' => __( 'Issue found', 'dev-debug-tools' ),
            ]
        ] );
    } // End enqueue_assets()


    /**
     * AJAX handler to check for issues.
     *
     * @return void
     */
    public function ajax_check_issue() {
        check_ajax_referer( $this->nonce_action, 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized', 'dev-debug-tools' ) ] );
        }

        $issue_key = isset( $_POST[ 'issue_key' ] ) ? sanitize_key( wp_unslash( $_POST[ 'issue_key' ]) ) : '';

        if ( empty( $issue_key ) ) {
            wp_send_json_error( [ 'message' => __( 'No issue key provided', 'dev-debug-tools' ) ] );
        }

        $issues_instance = new \Apos37\DevDebugTools\Issues();
        $issues_list = $issues_instance->get();

        if ( ! isset( $issues_list[ $issue_key ] ) || ! is_callable( $issues_list[ $issue_key ][ 'callback' ] ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid issue key', 'dev-debug-tools' ) ] );
        }

        $found = call_user_func( $issues_list[ $issue_key ][ 'callback' ] );

        wp_send_json_success( [
            'found'    => $found,
            'key'      => $issue_key,
            'actions'  => $found ? $issues_list[ $issue_key ][ 'actions' ] : [ ],
            'severity' => $issues_list[ $issue_key ][ 'severity' ],
        ] );
    } // End ajax_check_issue()


    /**
     * Prevent cloning and unserializing
     */
    public function __clone() {}
    public function __wakeup() {}

}


Dashboard::instance();