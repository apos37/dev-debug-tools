<?php
/**
 * Plugin Name:         Developer Debug Tools
 * Plugin URI:          https://pluginrx.com/plugin/dev-debug-tools/
 * Description:         WordPress debugging and testing tools for developers
 * Version:             3.0.0.1
 * Requires at least:   5.9
 * Tested up to:        6.8
 * Requires PHP:        8.0
 * Author:              PluginRx
 * Author URI:          https://pluginrx.com/
 * Discord URI:         https://discord.gg/3HnzNEJVnR
 * Text Domain:         dev-debug-tools
 * License:             GPLv2 or later
 * License URI:         http://www.gnu.org/licenses/gpl-2.0.txt
 * Created on:          May 13, 2022
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * BOOTSTRAP
 *
 * Loads plugin metadata, performs environment checks, and initializes the plugin.
 */
final class Bootstrap {

    /**
     * Plugin files to load.
     *
     * This array contains the paths to all plugin files that need to be included.
     */
    public const FILES = [
        'hub/pages/resources/links.php'                  => 'dev',
        'admin-area/class-admin-area.php'                => 'admin',
        'hub/menu.php'                                   => 'dev',
        'functions.php'                                  => 'dev',
        'helpers/help-map.php'                           => 'dev',
        'helpers/error-messages.php'                     => 'all',
        'helpers/jokes.php'                              => 'dev',
        'helpers/discord.php'                            => 'all',
        'admin-area/security/class-security.php'         => 'all',
        'shortcodes.php'                                 => 'dev',
        'site-wide/heartbeat/class-heartbeat.php'        => 'dev',
        'admin-area/admin-bar/class-admin-bar.php'       => 'admin',
        'admin-area/online-users/class-online-users.php' => 'all',
        'admin-area/plugins/class-plugins.php'           => 'admin',
        'site-wide/class-site-wide.php'                  => 'all',
        'cleanup.php'                                    => 'dev',
        'deprecated.php'                                 => 'dev',
    ];


    /**
     * Plugin header keys for get_file_data()
     */
    public const HEADER_KEYS = [
        'name'         => 'Plugin Name',
        'description'  => 'Description',
        'version'      => 'Version',
        'plugin_uri'   => 'Plugin URI',
        'requires_php' => 'Requires PHP',
        'textdomain'   => 'Text Domain',
        'author'       => 'Author',
        'author_uri'   => 'Author URI',
        'discord_uri'  => 'Discord URI'
    ];


    /**
     * @var array Plugin metadata from file header
     */
    private array $meta;


    /**
     * @var Bootstrap|null Singleton instance
     */
    private static ?Bootstrap $instance = null;


    /**
     * Get instance
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
        $this->meta = $this->load_meta();
        $this->check_environment();
        add_action( 'plugins_loaded', [ $this, 'load_files' ] );
    } // End __construct()


    /**
     * Check if test mode is enabled
     *
     * @return bool
     */
    public static function is_test_mode() : bool {
        return filter_var( get_option( 'ddtt_test_mode' ), FILTER_VALIDATE_BOOLEAN );
    } // End is_test_mode()


    /**
     * Load plugin metadata
     *
     * @return array
     */
    private function load_meta() : array {
        return get_file_data( __FILE__, self::HEADER_KEYS );
    } // End load_meta()


    /**
     * Check environment requirements
     *
     * @return void
     */
    private function check_environment() : void {
        if ( version_compare( PHP_VERSION, $this->meta[ 'requires_php' ], '<' ) ) {
            deactivate_plugins( plugin_basename( __FILE__ ) );
            wp_die( sprintf(
                /* translators: %1$s is plugin name, %2$s is required PHP version */
                esc_html( __( '%1$s requires PHP %2$s or higher.', 'dev-debug-tools' ) ),
                esc_html( $this->meta['name'] ),
                esc_html( $this->meta['requires_php'] )
            ) );
        }
    } // End check_environment()


    /**
     * Load all required plugin files
     *
     * @return void
     */
    public function load_files() : void {
        require_once __DIR__ . '/inc/helpers/helpers.php';
        $is_dev = Helpers::has_access();
        $is_administrator = current_user_can( 'administrator' );

        foreach ( self::FILES as $file => $env ) {

            if (
                ( $env === 'dev' && $is_dev ) ||
                ( $env === 'admin' && ( $is_administrator || $is_dev ) ) ||
                ( $env === 'all' )
            ) {
                $file_path = __DIR__ . '/inc/' . $file;
                if ( file_exists( $file_path ) ) {
                    require_once $file_path;
                } else {
                    _doing_it_wrong(
                        __METHOD__,
                        sprintf( 'File not found: %s', esc_html( $file_path ) ),
                        esc_html( $this->version() )
                    );
                }
            }
        }
    } // End load_files()


    /**
     * Get admin URL
     *
     * @param string $path
     * @param string $scheme
     * @return string
     */
    public static function admin_url( $path = '', $scheme = 'admin' ) {
         return is_network_admin() ? network_admin_url( $path, $scheme ) : admin_url( $path, $scheme );
    } // End admin_url()


    /**
     * Get metadata value
     *
     * @param string $key
     * @return string
     */
    public static function meta( string $key ) : string {
        return self::$instance->meta[ $key ] ?? '';
    } // End meta()


    /**
     * Get plugin URL
     *
     * @param string $append
     * @return string
     */
    public static function url( string $append = '' ) : string {
        return plugin_dir_url( __FILE__ ) . ltrim( $append, '/' );
    } // End url()


    /**
     * Get plugin path
     *
     * @param string $append
     * @return string
     */
    public static function path( string $append = '' ) : string {
        return plugin_dir_path( __FILE__ ) . ltrim( $append, '/' );
    } // End path()


    /**
     * Get a page URL
     *
     * @param string $append
     * @return string
     */
    public static function page_url( $slug ) : string {
        return self::admin_url( 'admin.php?page=dev-debug-' . $slug );
    } // End page_url()


    /**
     * Get a tool URL
     *
     * @param string $append
     * @return string
     */
    public static function tool_url( $slug ) : string {
        return self::admin_url( 'admin.php?page=dev-debug-tools&tool=' . $slug );
    } // End tool_url()


    /**
     * Get plugin name
     *
     * @return string
     */
    public static function name() : string {
        return self::meta( 'name' );
    } // End name()


    /**
     * Get plugin version
     *
     * @return string
     */
    public static function version() : string {
        return self::meta( 'version' );
    } // End version()


    /**
     * Get script/style version for cache busting.
     * Returns timestamp if TEST_MODE is enabled, otherwise plugin version.
     *
     * @return string
     */
    public static function script_version() : string {
        return self::is_test_mode() ? (string) time() : self::version();
    } // End script_version()


    /**
     * Get plugin text domain
     *
     * @return string
     */
    public static function textdomain() : string {
        return self::meta( 'textdomain' );
    } // End textdomain()


    /**
     * Get plugin author
     *
     * @return string
     */
    public static function author() : string {
        return self::meta( 'author' );
    } // End author()


    /**
     * Get plugin URI
     *
     * @return string
     */
    public static function plugin_uri() : string {
        return self::meta( 'plugin_uri' );
    } // End plugin_uri()


    /**
     * Get author URI
     *
     * @return string
     */
    public static function author_uri() : string {
        return self::meta( 'author_uri' );
    } // End author_uri()


    /**
     * Get Discord URI
     *
     * @return string
     */
    public static function discord_uri() : string {
        return self::meta( 'discord_uri' );
    } // End discord_uri()


    /**
     * Prevent cloning and unserializing
     */
    public function __clone() {}
    public function __wakeup() {}

} // End Bootstrap


Bootstrap::instance();