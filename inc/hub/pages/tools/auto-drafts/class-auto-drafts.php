<?php
/**
 * Auto Drafts
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class AutoDrafts {

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
                    'auto_drafts_all' => [
                        'title' => __( "Clear All Auto-Drafts", 'dev-debug-tools' ),
                        'desc'  => __( "This action will clear all auto-drafts for the current site.", 'dev-debug-tools' ),
                        'type'  => 'button',
                        'label' => __( 'Clear All', 'dev-debug-tools' ),
                    ],
                    'auto_drafts_old' => [
                        'title' => __( "Clear Older Auto-Drafts", 'dev-debug-tools' ),
                        'desc'  => __( "This action will clear all auto-drafts older than 7 days for the current site.", 'dev-debug-tools' ),
                        'type'  => 'button',
                        'label' => __( 'Clear Over 7 Days Old', 'dev-debug-tools' ),
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
    private $nonce = 'ddtt_auto_drafts_nonce';


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?AutoDrafts $instance = null;


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
        add_action( 'wp_ajax_ddtt_clear_auto_draft', [ $this, 'ajax_clear_auto_draft' ] );
        add_action( 'wp_ajax_nopriv_ddtt_clear_auto_draft', '__return_false' );
        add_action( 'wp_ajax_ddtt_clear_all_auto_drafts', [ $this, 'ajax_clear_all_auto_drafts' ] );
        add_action( 'wp_ajax_nopriv_ddtt_clear_all_auto_drafts', '__return_false' );
        add_action( 'wp_ajax_ddtt_clear_old_auto_drafts', [ $this, 'ajax_clear_old_auto_drafts' ] );
        add_action( 'wp_ajax_nopriv_ddtt_clear_old_auto_drafts', '__return_false' );
    } // End __construct()


    /**
     * Enqueue assets
     *
     * @param string $hook The current admin page hook.
     * @return void
     */
    public function enqueue_assets( $hook ) : void {
        if ( ! AdminMenu::is_current_screen( $hook, 'tools', 'auto-drafts' ) ) {
            return;
        }

        wp_localize_script( 'ddtt-tool-auto-drafts', 'ddtt_auto_drafts', [
            'nonce' => wp_create_nonce( $this->nonce ),
            'i18n'  => [
                'error'              => __( 'Error :(', 'dev-debug-tools' ),
                'btn_text_clear_all' => __( 'I\'m deleting your auto-drafts...', 'dev-debug-tools' ),
                'btn_text_clear_all2' => __( 'How cool is that?!', 'dev-debug-tools' ),
                'btn_text_clear_all3' => __( 'Nice. We\'re all done.', 'dev-debug-tools' ),
                'btn_text_clear_one' => __( 'Clearing your auto-draft...', 'dev-debug-tools' ),
            ],
        ] );
    } // End enqueue_assets()


    /**
     * AJAX: Clear a single auto-draft
     *
     * @return void
     */
    public function ajax_clear_auto_draft() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        if ( ! isset( $_POST[ 'post_id' ] ) || empty( $_POST[ 'post_id' ] ) ) {
            wp_send_json_error( [ 'message' => __( 'No post ID provided.', 'dev-debug-tools' ) ] );
        }

        $post_id = intval( $_POST[ 'post_id' ] );
        wp_delete_post( $post_id, true );

        wp_send_json_success( [ 'post_id' => $post_id ] );
    } // End ajax_clear_auto_draft()


    /**
     * AJAX: Clear all auto drafts (ignores age)
     *
     * @return void
     */
    public function ajax_clear_all_auto_drafts() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        $auto_drafts = get_posts( [
            'post_status'    => 'auto-draft',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ] );

        $deleted_count = 0;
        foreach ( (array) $auto_drafts as $post_id ) {
            if ( wp_delete_post( $post_id, true ) ) {
                $deleted_count++;
            }
        }

        wp_send_json_success( [
            // Translators: %d is the number of auto-drafts that were cleared.
            'message'       => sprintf( _n( '%d auto-draft cleared.', '%d auto-drafts cleared.', $deleted_count, 'dev-debug-tools' ), $deleted_count ),
            'deleted_count' => $deleted_count,
        ] );
    } // End ajax_clear_all_auto_drafts()


    /**
     * AJAX: Clear old auto drafts (7 days or older)
     *
     * @return void
     */
    public function ajax_clear_old_auto_drafts() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        // 7 days ago
        $date_threshold = gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) );

        // Get auto-drafts older than 7 days
        $old_posts = get_posts( [
            'post_status'    => 'auto-draft',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'date_query'     => [
                [
                    'column' => 'post_date',
                    'before' => $date_threshold,
                    'inclusive' => true,
                ],
            ],
        ] );

        $deleted_ids = [];
        foreach ( $old_posts as $post_id ) {
            if ( wp_delete_post( $post_id, true ) ) {
                $deleted_ids[] = $post_id;
            }
        }

        if ( empty( $deleted_ids ) ) {
            wp_send_json_success( [
                'deleted' => [],
                'message' => __( 'No auto-drafts older than 7 days to clear.', 'dev-debug-tools' ),
            ] );
        } else {
            wp_send_json_success( [
                'deleted' => $deleted_ids,
                'message' => sprintf(
                    /* translators: %d is the number of old auto-drafts that were cleared. */
                    _n( '%d old auto-draft cleared.', '%d old auto-drafts cleared.', count( $deleted_ids ), 'dev-debug-tools' ),
                    count( $deleted_ids )
                ),
            ] );
        }
    } // End ajax_clear_old_auto_drafts()


    /**
     * Prevent cloning and unserializing
     */
    public function __clone() {}
    public function __wakeup() {}
    
}


AutoDrafts::instance();