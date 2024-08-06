<?php
/**
 * Feedback class
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Initiate the class
 */
new DDTT_SETTINGS;


/**
 * Main plugin class.
 */
class DDTT_SETTINGS {

    /**
     * Nonce
     */
    private $nonce = DDTT_GO_PF.'settings';
    

    /**
	 * Constructor
	 */
	public function __construct() {

        // Hash the password field
        add_filter( 'pre_update_option_ddtt_pass', [ $this, 'hash_my_pass' ], 10, 3);

        // Check for access
        add_action( 'admin_init', [ $this, 'check_access' ] );

        // Ajax
        add_action( 'wp_ajax_'.DDTT_GO_PF.'verify_logs', [ $this, 'ajax' ] );
        add_action( 'wp_ajax_nopriv_'.DDTT_GO_PF.'verify_logs', [ $this, 'must_login' ] );

        // Enqueue scripts
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

	} // End __construct()


    /**
     * Hash the password field
     *
     * @param string $new_value
     * @param string $old_value
     * @param string $option
     * @return string
     */
    public function hash_my_pass( $new_value, $old_value, $option ) {
        if ( $option === 'ddtt_pass' ) {
            if ( !empty( $new_value ) ) {
                $new_value = wp_hash_password( $new_value );
            } else {
                // If the new value is empty, retain the old value
                $new_value = $old_value;
            }
        }
        return $new_value;
    } // End hash_my_pass()


    /**
     * Check access
     *
     * @return void
     */
    public function check_access() {
        // If password requirement is not enabled or is false, return true
        if ( !get_option( DDTT_GO_PF.'enable_pass' ) ) {
            return true;
        }
        
        // If password is not set, return true
        if ( !get_option( DDTT_GO_PF.'pass' ) ) {
            return true;
        }

        // Get the current url
        $current_url = home_url( add_query_arg( [], esc_url_raw( $_SERVER[ 'REQUEST_URI' ] ) ) );

        // Skip if password page
        $skip_pages = [
            ddtt_plugin_options_path( 'pw' ),
            ddtt_plugin_options_path( 'pw-reset' )
        ];
        foreach ( $skip_pages as $skip_page ) {
            if ( str_starts_with( $current_url, $skip_page ) ) {
                return true;
            }
        }

        // Get the urls we are checking
        $secure_pages = get_option( DDTT_GO_PF.'secure_pages' );

        // Check if the url is correct
        if ( ( !empty( $secure_pages ) && in_array( $current_url, $secure_pages ) ) || str_starts_with( $current_url, ddtt_plugin_options_path() ) ) {
            
            // Check if the transient exists and is not expired
            if ( false === get_transient( DDTT_GO_PF.'pass_active' ) ) {

                // Transient does not exist or is expired; redirect to the password page
                $redirect_to = add_query_arg( 'redirect_to', urlencode( $current_url ), ddtt_plugin_options_path( 'pw' ) );
                wp_safe_redirect( $redirect_to );
                exit;
            }
        }

        // Transient is valid; return true
        return true;
    } // End check_access()


    /**
     * Verify log filea
     *
     * @return void
     */
    public function ajax() {
        // First verify the nonce
        if ( !wp_verify_nonce( sanitize_text_field( wp_unslash ( $_REQUEST[ 'nonce' ] ) ), $this->nonce ) ) {
            exit( 'No naughty business please.' );
        }

        // Get the code
        $path = isset( $_REQUEST[ 'path' ] ) ? filter_var( $_REQUEST[ 'path' ], FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : false;
        if ( $path ) {

            $file = FALSE;
            if ( is_readable( ABSPATH.'/'.$path ) ) {
                $file = ABSPATH.''.$path;
            } elseif ( is_readable( dirname( ABSPATH ).'/'.$path ) ) {
                $file = dirname( ABSPATH ).'/'.$path;
            } elseif ( is_readable( $path ) ) {
                $file = $path;
            }
            if ( $file ) {
                $result[ 'type' ] = 'success';
            } else {
                $result[ 'type' ] = 'error';
            }
                        
        // Otherwise return error
        } else {
            $result[ 'type' ] = 'error';
        }

        // Pass to ajax
        if ( !empty( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) && strtolower( sanitize_key( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) ) == 'xmlhttprequest' ) {
            echo wp_json_encode( $result );
        } else {
            header( 'Location: '.filter_var( $_SERVER[ 'HTTP_REFERER' ], FILTER_SANITIZE_URL ) );
        }

        // Stop
        die();
    } // End ajax()


    /**
     * What to do if they are not logged in
     *
     * @return void
     */
    public function must_login() {
        die();
    } // End must_login()


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

        // Current tab
        $tab = ddtt_get( 'tab' );

        // Feedback form and error code checker
        if ( $tab == 'settings' ) {
            $handle = DDTT_GO_PF.'settings_script';
            wp_register_script( $handle, DDTT_PLUGIN_JS_PATH.'settings.js', [ 'jquery' ], DDTT_VERSION );
            wp_localize_script( $handle, 'settingsAjax', [
                'nonce'        => wp_create_nonce( $this->nonce ),
                'log_files'    => get_option( DDTT_GO_PF.'log_files' ),
                'secure_pages' => get_option( DDTT_GO_PF.'secure_pages' ),
                'ajaxurl'      => admin_url( 'admin-ajax.php' )
            ] );
            wp_enqueue_script( $handle );
            wp_enqueue_script( 'jquery' );
        }

        // Tabs with password view icon
        $tabs = [
            'settings',
            'pw',
            'pw-reset'
        ];

        // Feedback form and error code checker
        if ( in_array( $tab, $tabs ) ) {
            $pw_handle = DDTT_GO_PF.'pw_view_icon_script';
            wp_register_script( $pw_handle, DDTT_PLUGIN_JS_PATH.'pw-view-icon.js', [ 'jquery' ], DDTT_VERSION );
            wp_enqueue_script( $pw_handle );
            wp_enqueue_script( 'jquery' );
        }
    } // End enqueue_scripts()
}