<?php
/**
 * DB Tables class
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Initiate the class
 */
add_action( 'init', function() {
    (new DDTT_DB_TABLES)->init();
} );


/**
 * Main plugin class.
 */
class DDTT_DB_TABLES {

    /**
	 * Constructor
	 */
	public function init() {

        // Enqueue scripts
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

	} // End init()


    /**
     * Enqueue scripts
     * Reminder to bump version number during testing to avoid caching
     *
     * @param string $screen
     * @return void
     */
    public function enqueue_scripts( $screen ) {
        // Get the options page slug
        $options_page = 'toplevel_page_'.DDTT_TEXTDOMAIN;

        // Allow for multisite
        if ( is_network_admin() ) {
            $options_page .= '-network';
        }

        // Are we on the options page?
        if ( $screen != $options_page ) {
            return;
        }

        // Handle
        $handle = DDTT_GO_PF.'s_error_script';

        // Feedback form and error code checker
        if ( ddtt_get( 'tab', '==', 'db' ) ) {
            wp_register_script( $handle, DDTT_PLUGIN_JS_PATH.'db.js', [ 'jquery' ], time() );
            wp_enqueue_script( $handle );
            wp_enqueue_script( 'jquery' );
        }
    } // End enqueue_scripts()
}