<?php
/**
 * Security Checks Schedule
 *
 * Loaded on every request so WP-Cron can run the checks without a logged-in user.
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class SecurityChecksCron {

    /**
     * Recurring event that starts a scheduled run
     *
     * @var string
     */
    const HOOK = 'ddtt_security_checks_cron';


    /**
     * Single event that runs the next batch of checks
     *
     * @var string
     */
    const BATCH_HOOK = 'ddtt_security_checks_cron_batch';


    /**
     * Option holding the run in progress
     *
     * @var string
     */
    const STATE_KEY = 'ddtt_security_checks_cron_state';


    /**
     * Seconds of checks to start in one batch, kept well under common 30-second limits
     *
     * @var int
     */
    const BATCH_SECONDS = 15;


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?SecurityChecksCron $instance = null;


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
        add_action( self::HOOK, [ $this, 'start' ] );
        add_action( self::BATCH_HOOK, [ $this, 'run_batch' ] );
        add_action( 'add_option_ddtt_security_checks_schedule', [ $this, 'reschedule' ] );
        add_action( 'update_option_ddtt_security_checks_schedule', [ $this, 'reschedule' ] );
        register_deactivation_hook( Bootstrap::path( 'dev-debug-tools.php' ), [ $this, 'clear' ] );
    } // End __construct()


    /**
     * The saved schedule
     *
     * @return string off|daily|weekly
     */
    public static function schedule() : string {
        $schedule = (string) get_option( 'ddtt_security_checks_schedule', 'off' );
        return in_array( $schedule, [ 'daily', 'weekly' ], true ) ? $schedule : 'off';
    } // End schedule()


    /**
     * Why scheduled runs are paused
     *
     * Checks run during maintenance mode would test the maintenance redirect instead of the site
     * and send alerts that do not apply, so runs wait until maintenance mode is turned off.
     *
     * @return string Empty when scheduled runs are not paused.
     */
    public static function paused_reason() : string {
        $reason = '';
        if ( defined( 'SMREDIRECT_VERSION' ) && ! empty( sanitize_key( (string) get_option( 'smredirect_enabled', '' ) ) ) ) {
            $reason = __( 'Simple Maintenance Redirect has maintenance mode turned on.', 'dev-debug-tools' );
        }


        /**
         * Filter why scheduled security checks are paused. Return an empty string to let them run.
         */
        return (string) apply_filters( 'ddtt_security_checks_paused', $reason );
    } // End paused_reason()


    /**
     * Whether the Security Checks tool is turned on in Tools
     *
     * @return bool
     */
    private function tool_enabled() : bool {
        foreach ( (array) get_option( 'ddtt_tools', [] ) as $tool ) {
            if ( is_array( $tool ) && sanitize_key( $tool[ 'slug' ] ?? '' ) === 'security-checks' ) {
                return ! isset( $tool[ 'enabled' ] ) || filter_var( $tool[ 'enabled' ], FILTER_VALIDATE_BOOLEAN );
            }
        }

        return true;
    } // End tool_enabled()


    /**
     * Load the checks class
     *
     * @return bool
     */
    private function load() : bool {
        require_once Bootstrap::path( 'inc/hub/pages/tools/security-checks/class-security-checks.php' );
        return class_exists( SecurityChecks::class );
    } // End load()


    /**
     * Schedule or unschedule the recurring event after the setting changes
     *
     * @return void
     */
    public function reschedule() : void {
        wp_clear_scheduled_hook( self::HOOK );

        $schedule = self::schedule();
        if ( $schedule !== 'off' ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, $schedule, self::HOOK );
        }
    } // End reschedule()


    /**
     * Remove all scheduled events and any run in progress
     *
     * @return void
     */
    public function clear() : void {
        wp_clear_scheduled_hook( self::HOOK );
        wp_clear_scheduled_hook( self::BATCH_HOOK );
        delete_option( self::STATE_KEY );
    } // End clear()


    /**
     * Start a scheduled run
     *
     * @return void
     */
    public function start() : void {
        if ( self::schedule() === 'off' || ! $this->tool_enabled() ) {
            $this->clear();
            return;
        }

        if ( self::paused_reason() !== '' ) {
            return;
        }

        $state = get_option( self::STATE_KEY );
        if ( is_array( $state ) && ! empty( $state[ 'queue' ] ) && (int) ( $state[ 'started' ] ?? 0 ) > time() - DAY_IN_SECONDS ) {
            return;
        }

        if ( ! $this->load() ) {
            return;
        }

        update_option( self::STATE_KEY, [
            'queue'    => array_keys( SecurityChecks::enabled_checks() ),
            'previous' => SecurityChecks::get_results(),
            'score'    => SecurityChecks::score()[ 'value' ],
            'started'  => time(),
        ], false );

        $this->run_batch();
    } // End start()


    /**
     * Run checks until the batch time is used, then schedule the next batch
     *
     * @return void
     */
    public function run_batch() : void {
        $state = get_option( self::STATE_KEY );
        if ( ! is_array( $state ) || ! isset( $state[ 'queue' ] ) || self::paused_reason() !== '' || ! $this->load() ) {
            wp_clear_scheduled_hook( self::BATCH_HOOK );
            delete_option( self::STATE_KEY );
            return;
        }

        if ( function_exists( 'set_time_limit' ) ) {
            @set_time_limit( 120 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, Squiz.PHP.DiscouragedFunctions.Discouraged -- Long-running scan; the function may be disabled by the host.
        }

        // Safety net: continue later if this request is cut off mid-check.
        wp_clear_scheduled_hook( self::BATCH_HOOK );
        wp_schedule_single_event( time() + 5 * MINUTE_IN_SECONDS, self::BATCH_HOOK );

        $enabled = SecurityChecks::enabled_checks();
        $deadline = microtime( true ) + self::BATCH_SECONDS;

        while ( ! empty( $state[ 'queue' ] ) && microtime( true ) < $deadline ) {
            $key = array_shift( $state[ 'queue' ] );
            update_option( self::STATE_KEY, $state, false );

            if ( isset( $enabled[ $key ] ) ) {
                SecurityChecks::save_result( $key, SecurityChecks::run( $key, ! is_admin() ) );
            }
        }

        wp_clear_scheduled_hook( self::BATCH_HOOK );

        if ( ! empty( $state[ 'queue' ] ) ) {
            wp_schedule_single_event( time() + MINUTE_IN_SECONDS, self::BATCH_HOOK );
            return;
        }

        delete_option( self::STATE_KEY );
        $this->maybe_notify( (array) ( $state[ 'previous' ] ?? [] ), $state[ 'score' ] ?? null );
    } // End run_batch()


    /**
     * Send alerts when a check newly fails or the score drops
     *
     * @param array    $previous       Results before this run.
     * @param int|null $previous_score Score before this run.
     * @return void
     */
    private function maybe_notify( array $previous, ?int $previous_score ) : void {
        $results = SecurityChecks::get_results();
        $new_failures = [];

        foreach ( SecurityChecks::enabled_checks() as $key => $check ) {
            if ( ( $results[ $key ][ 'status' ] ?? '' ) === 'fail' && ( $previous[ $key ][ 'status' ] ?? '' ) !== 'fail' ) {
                $items = array_slice( (array) ( $results[ $key ][ 'items' ] ?? [] ), 0, 3 );
                $line = $check[ 'label' ] . ': ' . rtrim( (string) $results[ $key ][ 'message' ], ':' );
                $new_failures[] = empty( $items ) ? $line : $line . ' (' . implode( ', ', $items ) . ( count( (array) $results[ $key ][ 'items' ] ) > 3 ? ', …' : '' ) . ')';
            }
        }

        $score = SecurityChecks::score()[ 'value' ];
        $drop_limit = absint( get_option( 'ddtt_security_checks_score_drop', 10 ) );
        $dropped = $drop_limit && $previous_score !== null && $score !== null && $previous_score - $score >= $drop_limit;

        if ( empty( $new_failures ) && ! $dropped ) {
            return;
        }

        $site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
        $url = Bootstrap::tool_url( 'security-checks' );

        if ( $score === null ) {
            $score_line = __( 'Security score: not available', 'dev-debug-tools' );
        } elseif ( $previous_score === null ) {
            /* translators: %d: score */
            $score_line = sprintf( __( 'Security score: %d%%', 'dev-debug-tools' ), $score );
        } else {
            /* translators: 1: score, 2: previous score */
            $score_line = sprintf( __( 'Security score: %1$d%% (was %2$d%%)', 'dev-debug-tools' ), $score, $previous_score );
        }

        $failure_lines = array_map( function( $line ) {
            return '- ' . $line;
        }, $new_failures );

        if ( filter_var( get_option( 'ddtt_security_checks_email_enable', true ), FILTER_VALIDATE_BOOLEAN ) ) {
            $to = (string) get_option( 'ddtt_security_checks_email', '' );
            $to = is_email( $to ) ? $to : (string) get_option( 'admin_email' );

            $lines = [ $score_line, '' ];
            if ( ! empty( $failure_lines ) ) {
                $lines[] = __( 'New failures since the last run:', 'dev-debug-tools' );
                $lines = array_merge( $lines, $failure_lines, [ '' ] );
            }
            /* translators: %s: URL of the Security Checks tool */
            $lines[] = sprintf( __( 'View the results: %s', 'dev-debug-tools' ), $url );
            $lines[] = '';
            $lines[] = __( 'Sent by a scheduled run of the Security Checks tool in Developer Debug Tools. These checks cover common problems only and are not a guarantee of security.', 'dev-debug-tools' );

            /* translators: %s: site name */
            wp_mail( $to, sprintf( __( '[%s] Security checks found new problems', 'dev-debug-tools' ), $site ), implode( "\n", $lines ) );
        }

        $webhook = (string) get_option( 'ddtt_security_checks_discord_webhook', '' );
        if ( $webhook !== '' && filter_var( get_option( 'ddtt_security_checks_discord_enable', false ), FILTER_VALIDATE_BOOLEAN ) ) {
            $desc = empty( $failure_lines ) ? '' : __( 'New failures since the last run:', 'dev-debug-tools' ) . "\n" . implode( "\n", $failure_lines );

            DiscordWebhook::send( $webhook, [
                'embed'     => true,
                'color'     => 'e74c3c',
                /* translators: %s: site name */
                'title'     => sprintf( __( 'Security checks found new problems on %s', 'dev-debug-tools' ), $site ),
                'title_url' => $url,
                'desc'      => mb_strlen( $desc ) > 3500 ? mb_substr( $desc, 0, 3500 ) . '…' : $desc,
                'fields'    => [
                    [
                        'name'   => __( 'Score', 'dev-debug-tools' ),
                        'value'  => $score_line,
                        'inline' => false,
                    ],
                ],
            ] );
        }
    } // End maybe_notify()

}


SecurityChecksCron::instance();
