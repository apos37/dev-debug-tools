<?php
/**
 * Cookies
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Cookies {


    /**
     * Available cookie flavors
     *
     * @return array
     */
    public function cookie_flavors() {
        return [
            'chocolate_chip_cookie'     => __( 'Chocolate Chip Cookie', 'dev-debug-tools' ),
            'oatmeal_raisin_cookie'     => __( 'Oatmeal Raisin Cookie', 'dev-debug-tools' ),
            'peanut_butter_cookie'      => __( 'Peanut Butter Cookie', 'dev-debug-tools' ),
            'sugar_cookie'              => __( 'Sugar Cookie', 'dev-debug-tools' ),
            'snickerdoodle_cookie'      => __( 'Snickerdoodle Cookie', 'dev-debug-tools' ),
            'gingerbread_cookie'        => __( 'Gingerbread Cookie', 'dev-debug-tools' ),
            'shortbread_cookie'         => __( 'Shortbread Cookie', 'dev-debug-tools' ),
            'macadamia_cookie'          => __( 'White Chocolate Macadamia Nut Cookie', 'dev-debug-tools' ),
            'fortune_cookie'            => __( 'Fortune Cookie', 'dev-debug-tools' ),
            'biscotti_cookie'           => __( 'Biscotti Cookie', 'dev-debug-tools' ),
            'thumbprint_cookie'         => __( 'Thumbprint Cookie', 'dev-debug-tools' ),
            'spritz_cookie'             => __( 'Spritz Cookie', 'dev-debug-tools' ),
            'linzer_cookie'             => __( 'Linzer Cookie', 'dev-debug-tools' ),
            'molasses_cookie'           => __( 'Molasses Cookie', 'dev-debug-tools' ),
            'pepparkakor_cookie'        => __( 'Pepparkakor Cookie', 'dev-debug-tools' ),
            'anise_cookie'              => __( 'Anise Cookie', 'dev-debug-tools' ),
            'butter_cookie'             => __( 'Butter Cookie', 'dev-debug-tools' ),
            'almond_cookie'             => __( 'Almond Cookie', 'dev-debug-tools' ),
            'coconut_cookie'            => __( 'Coconut Cookie', 'dev-debug-tools' ),
            'lavender_cookie'           => __( 'Lavender Cookie', 'dev-debug-tools' ),
            'lemon_cookie'              => __( 'Lemon Cookie', 'dev-debug-tools' ),
            'matcha_cookie'             => __( 'Matcha Cookie', 'dev-debug-tools' ),
            'red_velvet_cookie'         => __( 'Red Velvet Cookie', 'dev-debug-tools' ),
            'salted_caramel_cookie'     => __( 'Salted Caramel Cookie', 'dev-debug-tools' ),
            'toffee_cookie'             => __( 'Toffee Cookie', 'dev-debug-tools' ),
        ];
    } // End cookie_flavors()


    /**
     * Get the options for tool.
     *
     * @return array
     */
    public static function settings() : array {
        return [
            'general' => [
                'label' => __( 'Actions', 'dev-debug-tools' ),
                'fields' => [
                    'browser_cookies' => [
                        'title' => __( "Clear All Browser Cookies", 'dev-debug-tools' ),
                        'desc'  => __( "This action will clear all browser cookies for the current site, but will skip WordPress login cookies (wordpress_logged_in_*, wordpress_sec_*, and wordpress_test_cookie) to prevent logging you out.", 'dev-debug-tools' ),
                        'type'  => 'button',
                        'label' => __( 'Clear Cookies', 'dev-debug-tools' ),
                    ],
                    'test_cookie' => [
                        'title' => __( "Give Me a Cookie", 'dev-debug-tools' ),
                        'desc'  => __( "This action will give you a test cookie.", 'dev-debug-tools' ),
                        'type'  => 'button',
                        'label' => __( 'Yum Yum', 'dev-debug-tools' ),
                    ],
                    'auto_check' => [
                        'title'   => __( "Automatically Check for New Cookies", 'dev-debug-tools' ),
                        'desc'    => __( "Enable this option to have the cookies table update automatically every few seconds without reloading the page. It will be disabled when you leave the page.", 'dev-debug-tools' ),
                        'type'    => 'checkbox',
                        'default' => false,
                    ],
                ]
            ]
        ];
    } // End settings()


    /**
     * Nonce
     *
     * @var string
     */
    private $nonce = 'ddtt_cookies_nonce';


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?Cookies $instance = null;


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
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_ddtt_clear_cookie', [ $this, 'ajax_clear_cookie' ] );
        add_action( 'wp_ajax_nopriv_ddtt_clear_cookie', '__return_false' );
        add_action( 'wp_ajax_ddtt_clear_all_cookies', [ $this, 'ajax_clear_all_cookies' ] );
        add_action( 'wp_ajax_nopriv_ddtt_clear_all_cookies', '__return_false' );
        add_action( 'wp_ajax_ddtt_test_cookie', [ $this, 'ajax_test_cookie' ] );
        add_action( 'wp_ajax_nopriv_ddtt_test_cookie', '__return_false' );
        add_action( 'wp_ajax_ddtt_get_cookies', [ $this, 'ajax_get_cookies' ] );
        add_action( 'wp_ajax_nopriv_ddtt_get_cookies', '__return_false' );
    } // End __construct()


    /**
     * Enqueue assets
     *
     * @param string $hook The current admin page hook.
     * @return void
     */
    public function enqueue_assets( $hook ) : void {
        if ( ! AdminMenu::is_current_screen( $hook, 'tools', 'cookies' ) ) {
            return;
        }
        
        wp_localize_script( 'ddtt-tool-cookies', 'ddtt_cookies', [
            'nonce' => wp_create_nonce( $this->nonce ),
            'i18n'  => [
                'error'              => __( 'Yuck! No thanks.', 'dev-debug-tools' ),
                'btn_text_clear_all' => __( 'I\'m eating all your cookies...', 'dev-debug-tools' ),
                'btn_text_clear_all2' => __( 'Are you feeling okay about this?', 'dev-debug-tools' ),
                'btn_text_clear_all3' => __( 'Mmmmm... delicious! Thank you!', 'dev-debug-tools' ),
                'btn_text_clear_one' => __( 'Eating your cookie...', 'dev-debug-tools' ),
                'btn_text_add_test'  => __( 'You\'ve been a good cookie monster!', 'dev-debug-tools' ),
                'skip_wp_login'      => __( 'This is a WordPress login or authentication cookie. Deleting it will log you out and may break your session. Are you sure you want to delete it?', 'dev-debug-tools' )
            ],
        ] );
    } // End enqueue_assets()


    /**
     * AJAX: Clear a single cookie
     *
     * @return void
     */
    public function ajax_clear_cookie() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        if ( empty( $_POST[ 'cookie_name' ] ) ) {
            wp_send_json_error( [ 'message' => __( 'No cookie name provided.', 'dev-debug-tools' ) ] );
        }

        $cookie_name = sanitize_text_field( wp_unslash( $_POST[ 'cookie_name' ] ) );

        // Expire cookie
        setcookie( $cookie_name, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN );
        if ( SITECOOKIEPATH != COOKIEPATH ) {
            setcookie( $cookie_name, '', time() - 3600, SITECOOKIEPATH, COOKIE_DOMAIN );
        }

        wp_send_json_success( [ 'cookie' => $cookie_name ] );
    } // End ajax_clear_cookie()


    /**
     * AJAX: Clear all cookies
     *
     * @return void
     */
    public function ajax_clear_all_cookies() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        foreach ( $_COOKIE as $name => $value ) {
            if ( str_starts_with( $name, 'wordpress_logged_in_' ) ||
                str_starts_with( $name, 'wordpress_sec_' ) ||
                $name === 'wordpress_test_cookie' ) {
                continue;
            }

            setcookie( $name, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN );
            if ( SITECOOKIEPATH != COOKIEPATH ) {
                setcookie( $name, '', time() - 3600, SITECOOKIEPATH, COOKIE_DOMAIN );
            }
        }

        wp_send_json_success( [ 'message' => __( 'All cookies cleared.', 'dev-debug-tools' ) ] );
    } // End ajax_clear_all_cookies()


    /**
     * AJAX: Set a random test cookie
     *
     * @return void
     */
    public function ajax_test_cookie() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        $cookie_flavors   = $this->cookie_flavors();
        $flavor_keys      = array_keys( $cookie_flavors );
        $random_key       = $flavor_keys[ array_rand( $flavor_keys ) ];

        $cookie_name      = 'ddtt_' . $random_key;
        $cookie_value     = $cookie_flavors[ $random_key ];

        setcookie( $cookie_name, $cookie_value, time() + 3600, COOKIEPATH, COOKIE_DOMAIN );

        wp_send_json_success( [
            'cookie' => $cookie_name,
            'value'  => $cookie_value,
            'label'  => $cookie_value,
        ] );
    } // End ajax_test_cookie()


    /**
     * AJAX: Get current cookies
     *
     * @return void
     */
    public function ajax_get_cookies() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        $cookies = $_COOKIE;
        wp_send_json_success( [ 'cookies' => $cookies ] );
    } // End ajax_get_cookies()


    /**
     * Prevent cloning and unserializing
     */
    private function __clone() {}
    private function __wakeup() {}
    
}


Cookies::instance();