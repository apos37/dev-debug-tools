<?php
/**
 * Cron Jobs
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class CronJobs {

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
     * Prevent cloning and unserializing
     */
    private function __clone() {}
    private function __wakeup() {}
    
}


CronJobs::instance();