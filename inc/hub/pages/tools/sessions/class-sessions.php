<?php
/**
 * Sessions
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Sessions {


    /**
     * Available session flavors
     *
     * @return array
     */
    public function session_flavors() {
        return [
            'dance_session'        => __( 'Dance Session', 'dev-debug-tools' ),
            'jam_session'          => __( 'Jam Session', 'dev-debug-tools' ),
            'meditation_session'   => __( 'Meditation Session', 'dev-debug-tools' ),
            'yoga_session'         => __( 'Yoga Session', 'dev-debug-tools' ),
            'coding_session'       => __( 'Coding Session', 'dev-debug-tools' ),
            'reading_session'      => __( 'Reading Session', 'dev-debug-tools' ),
            'coffee_session'       => __( 'Coffee Session', 'dev-debug-tools' ),
            'gaming_session'       => __( 'Gaming Session', 'dev-debug-tools' ),
            'painting_session'     => __( 'Painting Session', 'dev-debug-tools' ),
            'singing_session'      => __( 'Singing Session', 'dev-debug-tools' ),
            'puzzle_session'       => __( 'Puzzle Session', 'dev-debug-tools' ),
            'hiking_session'       => __( 'Hiking Session', 'dev-debug-tools' ),
            'chat_session'         => __( 'Chat Session', 'dev-debug-tools' ),
            'writing_session'      => __( 'Writing Session', 'dev-debug-tools' ),
            'photography_session'  => __( 'Photography Session', 'dev-debug-tools' ),
            'drumming_session'     => __( 'Drumming Session', 'dev-debug-tools' ),
            'baking_session'       => __( 'Baking Session', 'dev-debug-tools' ),
            'stretching_session'   => __( 'Stretching Session', 'dev-debug-tools' ),
            'brainstorm_session'   => __( 'Brainstorm Session', 'dev-debug-tools' ),
            'storytelling_session' => __( 'Storytelling Session', 'dev-debug-tools' ),
            'sketching_session'    => __( 'Sketching Session', 'dev-debug-tools' ),
            'boardgame_session'    => __( 'Boardgame Session', 'dev-debug-tools' ),
            'movie_session'        => __( 'Movie Session', 'dev-debug-tools' ),
            'music_session'        => __( 'Music Session', 'dev-debug-tools' ),
            'coffee_break_session' => __( 'Coffee Break Session', 'dev-debug-tools' ),
            'networking_session'   => __( 'Networking Session', 'dev-debug-tools' ),
            'brain_training_session'=> __( 'Brain Training Session', 'dev-debug-tools' ),
        ];
    } // End session_flavors()


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
                    'browser_sessions' => [
                        'title' => __( "Clear All Browser Sessions", 'dev-debug-tools' ),
                        'desc'  => __( "This action will clear all browser sessions for the current site.", 'dev-debug-tools' ),
                        'type'  => 'button',
                        'label' => __( 'Clear Sessions', 'dev-debug-tools' ),
                    ],
                    'test_session' => [
                        'title' => __( "Give Me a Session", 'dev-debug-tools' ),
                        'desc'  => __( "This action will give you a test session variable.", 'dev-debug-tools' ),
                        'type'  => 'button',
                        'label' => __( 'New Session', 'dev-debug-tools' ),
                    ],
                    'auto_check' => [
                        'title'   => __( "Automatically Check for New Sessions", 'dev-debug-tools' ),
                        'desc'    => __( "Enable this option to have the sessions table update automatically every few seconds without reloading the page. It will be disabled when you leave the page.", 'dev-debug-tools' ),
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
    private $nonce = 'ddtt_sessions_nonce';


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?Sessions $instance = null;


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
        add_action( 'wp_ajax_ddtt_clear_session', [ $this, 'ajax_clear_session' ] );
        add_action( 'wp_ajax_nopriv_ddtt_clear_session', '__return_false' );
        add_action( 'wp_ajax_ddtt_clear_all_sessions', [ $this, 'ajax_clear_all_sessions' ] );
        add_action( 'wp_ajax_nopriv_ddtt_clear_all_sessions', '__return_false' );
        add_action( 'wp_ajax_ddtt_test_session', [ $this, 'ajax_test_session' ] );
        add_action( 'wp_ajax_nopriv_ddtt_test_session', '__return_false' );
        add_action( 'wp_ajax_ddtt_get_session_cookies', [ $this, 'ajax_get_cookies' ] );
        add_action( 'wp_ajax_nopriv_ddtt_get_session_cookies', '__return_false' );
    } // End __construct()


    /**
     * Enqueue assets
     *
     * @param string $hook The current admin page hook.
     * @return void
     */
    public function enqueue_assets( $hook ) : void {
        if ( ! AdminMenu::is_current_screen( $hook, 'tools', 'sessions' ) ) {
            return;
        }
        
        wp_localize_script( 'ddtt-tool-sessions', 'ddtt_sessions', [
            'nonce' => wp_create_nonce( $this->nonce ),
            'i18n'  => [
                'error'              => __( 'Yuck! No thanks.', 'dev-debug-tools' ),
                'btn_text_clear_all' => __( 'Clearing all active sessions...', 'dev-debug-tools' ),
                'btn_text_clear_all2'=> __( 'Hold tight, flushing sessions!', 'dev-debug-tools' ),
                'btn_text_clear_all3'=> __( 'All sessions cleared successfully!', 'dev-debug-tools' ),
                'btn_text_clear_one' => __( 'Clearing this session...', 'dev-debug-tools' ),
                'btn_text_add_test'  => __( 'New test session created!', 'dev-debug-tools' ),
            ],
        ] );
    } // End enqueue_assets()


    /**
     * AJAX: Clear a single session
     *
     * @return void
     */
    public function ajax_clear_session() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        if ( empty( $_POST[ 'session_name' ] ) ) {
            wp_send_json_error( [ 'message' => __( 'No session name provided.', 'dev-debug-tools' ) ] );
        }

        if ( ! session_id() ) {
            session_start();
        }

        $session_name = sanitize_text_field( wp_unslash( $_POST[ 'session_name' ] ) );

        if ( isset( $_SESSION[ $session_name ] ) ) {
            unset( $_SESSION[ $session_name ] );
        }

        wp_send_json_success( [ 'session' => $session_name ] );
    } // End ajax_clear_session()


    /**
     * AJAX: Clear all sessions
     *
     * @return void
     */
    public function ajax_clear_all_sessions() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        if ( ! session_id() ) {
            session_start();
        }

        $_SESSION = [];

        wp_send_json_success( [ 'message' => __( 'All sessions cleared.', 'dev-debug-tools' ) ] );
    } // End ajax_clear_all_sessions()


    /**
     * AJAX: Set a random test session
     *
     * @return void
     */
    public function ajax_test_session() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }
        
        if ( ! session_id() ) {
            session_start();
        }

        $session_flavors   = $this->session_flavors(); // Reuse same flavors array
        $flavor_keys       = array_keys( $session_flavors );
        $random_key        = $flavor_keys[ array_rand( $flavor_keys ) ];

        $session_name      = 'ddtt_' . $random_key;
        $session_value     = $session_flavors[ $random_key ];

        $_SESSION[ $session_name ] = $session_value;

        wp_send_json_success( [
            'session' => $session_name,
            'value'   => $session_value,
            'label'   => $session_value,
        ] );
    } // End ajax_test_session()


    /**
     * AJAX: Get current sessions
     *
     * @return void
     */
    public function ajax_get_sessions() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        wp_send_json_success( [ 'sessions' => $_SESSION ] );
    } // End ajax_get_sessions()


    /**
     * Prevent cloning and unserializing
     */
    public function __clone() {}
    public function __wakeup() {}
    
}


Sessions::instance();