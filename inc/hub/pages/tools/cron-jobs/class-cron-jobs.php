<?php
/**
 * Cron Jobs
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class CronJobs {

    /**
     * Nonce
     *
     * @var string
     */
    private $nonce = 'ddtt_cron_jobs_nonce';


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?CronJobs $instance = null;


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
        add_action( 'wp_ajax_ddtt_run_cron_event', [ $this, 'ajax_run_event' ] );
        add_action( 'wp_ajax_nopriv_ddtt_run_cron_event', '__return_false' );
    } // End __construct()


    /**
     * Render header notices
     *
     * This method is called to render notices in the header.
     * It checks for deleted options and displays a notice if any were deleted.
     */
    public function render_header_notices() {
        if ( AdminMenu::get_current_page_slug() !== 'dev-debug-tools' || AdminMenu::current_tool_slug() !== 'cron-jobs' ) {
            return;
        }

        if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
            Helpers::render_notice( 'WP Cron is disabled on your <code>wp-config.php</code> file, but it does not prevent the schedules from firing if called directly.', 'warning' );
        }
    } // End render_header_notices()


    /**
     * Format an event's run time for display
     *
     * @param int $timestamp
     * @return array [ timestamp, next_run (relative), time (date in the developer's timezone) ]
     */
    public static function format_time( int $timestamp ) : array {
        $dt = new \DateTime( gmdate( 'Y-m-d H:i:s', $timestamp ), new \DateTimeZone( 'UTC' ) );
        $dt->setTimezone( new \DateTimeZone( get_option( 'ddtt_dev_timezone', wp_timezone_string() ) ) );

        return [
            'timestamp' => $timestamp,
            'next_run'  => Helpers::convert_timestamp_to_string( $timestamp ),
            'time'      => $dt->format( 'F j, Y g:i A T' ),
        ];
    } // End format_time()


    /**
     * Keys of every scheduled event, as "timestamp|hook|signature"
     *
     * @return array
     */
    private function event_keys() : array {
        $keys = [];
        foreach ( (array) _get_cron_array() as $timestamp => $hooks ) {
            foreach ( (array) $hooks as $hook => $events ) {
                foreach ( array_keys( (array) $events ) as $sig ) {
                    $keys[] = $timestamp . '|' . $hook . '|' . $sig;
                }
            }
        }

        return $keys;
    } // End event_keys()


    /**
     * Enqueue assets
     *
     * @param string $hook
     * @return void
     */
    public function enqueue_assets( $hook ) : void {
        if ( ! AdminMenu::is_current_screen( $hook, 'tools', 'cron-jobs' ) ) {
            return;
        }

        wp_localize_script( 'ddtt-tool-cron-jobs', 'ddtt_cron_jobs', [
            'nonce' => wp_create_nonce( $this->nonce ),
            'i18n'  => [
                'running'      => __( 'Running', 'dev-debug-tools' ),
                'error'        => __( 'The event could not be run. Reload the page and try again.', 'dev-debug-tools' ),
                'notScheduled' => __( 'Not scheduled again', 'dev-debug-tools' ),
            ],
        ] );
    } // End enqueue_assets()


    /**
     * AJAX: Run a scheduled event now
     *
     * Runs the event the same way wp-cron.php does: a recurring event is rescheduled from now,
     * a single event is removed, and then the hook runs.
     *
     * @return void
     */
    public function ajax_run_event() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) || ! Helpers::is_dev() ) {
            wp_send_json_error( __( 'You do not have permission to run cron events.', 'dev-debug-tools' ) );
        }

        $hook = isset( $_POST[ 'hook' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'hook' ] ) ) : '';
        $timestamp = isset( $_POST[ 'timestamp' ] ) ? absint( $_POST[ 'timestamp' ] ) : 0;
        $sig = isset( $_POST[ 'sig' ] ) ? sanitize_key( wp_unslash( $_POST[ 'sig' ] ) ) : '';

        $crons = _get_cron_array();
        if ( ! isset( $crons[ $timestamp ][ $hook ][ $sig ] ) ) {
            wp_send_json_error( __( 'This event is no longer scheduled. It may have already run. Reload the page.', 'dev-debug-tools' ) );
        }

        $event = $crons[ $timestamp ][ $hook ][ $sig ];
        $args = (array) ( $event[ 'args' ] ?? [] );
        $before = $this->event_keys();

        if ( ! empty( $event[ 'schedule' ] ) ) {
            wp_reschedule_event( $timestamp, $event[ 'schedule' ], $hook, $args );
        }
        wp_unschedule_event( $timestamp, $hook, $args );

        $start = microtime( true );
        ob_start();
        try {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- Manually runs an existing cron event by its registered hook name.
            do_action_ref_array( $hook, $args );
        } catch ( \Throwable $e ) {
            ob_end_clean();
            /* translators: %s: error message */
            wp_send_json_error( sprintf( __( 'The event ran but stopped with an error: %s', 'dev-debug-tools' ), $e->getMessage() ) );
        }
        ob_end_clean();
        $seconds = round( microtime( true ) - $start, 1 );

        $next = wp_next_scheduled( $hook, $args );
        $new_events = count( array_diff( $this->event_keys(), $before ) ) - ( $next ? 1 : 0 );

        /* translators: %s: number of seconds */
        $message = sprintf( __( 'Ran in %s seconds.', 'dev-debug-tools' ), number_format_i18n( $seconds, 1 ) );
        if ( $new_events > 0 ) {
            /* translators: %d: number of events */
            $message .= ' ' . sprintf( _n( 'It scheduled %d new event. Reload the page to see it.', 'It scheduled %d new events. Reload the page to see them.', $new_events, 'dev-debug-tools' ), $new_events );
        }

        wp_send_json_success( [
            'next'    => $next ? self::format_time( (int) $next ) : null,
            'message' => $message,
        ] );
    } // End ajax_run_event()


    /**
     * Prevent cloning and unserializing
     */
    public function __clone() {}
    public function __wakeup() {}

}


CronJobs::instance();
