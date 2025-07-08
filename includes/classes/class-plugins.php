<?php
/**
 * Plugins class
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Initiate the class
 */
new DDTT_PLUGINS;


/**
 * Main plugin class.
 */
class DDTT_PLUGINS {

    /**
     * Nonce
     */
    private $nonce = DDTT_GO_PF . 'plugins';
    

    /**
	 * Constructor
	 */
	public function __construct() {

        // Store the plugins added by users
        add_action( 'activated_plugin', [ $this, 'add_plugin_user' ] );

        // Delete the plugins added by users
        add_action( 'deactivated_plugin', [ $this, 'remove_plugin_user' ] );

        // delete_option( DDTT_GO_PF . 'plugins_added_by' ); // For testing purposes

        // Ajax
        add_action( 'wp_ajax_' . DDTT_GO_PF . 'update_plugin_user', [ $this, 'ajax' ] );
        add_action( 'wp_ajax_nopriv_' . DDTT_GO_PF . 'update_plugin_user', [ $this, 'must_login' ] );

        // Enqueue scripts
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

	} // End __construct()


    /**
     * Update the user who added a plugin
     *
     * @param string $plugin
     * @return void
     */
    public function add_plugin_user( $plugin ) {
        $user_id = get_current_user_id();
        $user = get_user_by( 'ID', $user_id );
        if ( !$user ) {
            return;
        }

        $added_by = get_option( DDTT_GO_PF . 'plugins_added_by', [
            'user_ids' => [],
            'plugins'  => []
        ] );

        if ( isset( $added_by[ 'plugins' ][ $plugin ] ) ) {
            return;
        }

        $added_by[ 'plugins' ][ $plugin ] = $user_id;
        $added_by[ 'user_ids' ][ $user_id ] = $user->display_name;

        update_option( DDTT_GO_PF . 'plugins_added_by', $added_by );
    } // End add_plugin_user()


    /**
     * Remove the user who added a plugin
     *
     * @param string $plugin
     * @return void
     */
    public function remove_plugin_user( $plugin ) {
        $added_by = get_option( DDTT_GO_PF . 'plugins_added_by', [
            'user_ids' => [],
            'plugins'  => []
        ] );

        if ( isset( $added_by[ 'plugins' ][ $plugin ] ) ) {
            unset( $added_by[ 'plugins' ][ $plugin ] );
            update_option( DDTT_GO_PF . 'plugins_added_by', $added_by );
        }
    } // End remove_plugin_user()


    /**
     * Update the user who added a plugin via AJAX
     *
     * @return void
     */
    public function ajax() {
        if ( !ddtt_is_dev() || !current_user_can( 'activate_plugins' ) || !isset( $_POST[ 'plugin' ], $_POST[ 'user_id' ] ) ) {
            wp_send_json_error();
        }

        $plugin  = sanitize_text_field( wp_unslash( $_POST[ 'plugin' ] ) );
        $user_id = absint( wp_unslash( $_POST[ 'user_id' ] ) );
        $apply_all = isset( $_POST[ 'apply_all' ] ) && absint( $_POST[ 'apply_all' ] ) === 1;

        $added_by = get_option( DDTT_GO_PF . 'plugins_added_by', [
            'user_ids' => [],
            'plugins'  => []
        ] );

        if ( $user_id === 0 ) {
            unset( $added_by[ 'plugins' ][ $plugin ] );
            $display = '<em>Unknown</em>';
        } else {
            $user = get_user_by( 'ID', $user_id );
            if ( ! $user ) {
                wp_send_json_error( [ 'message' => 'User ID not found.' ] );
            }

            $display_name = $user->display_name;
            $added_by[ 'user_ids' ][ $user_id ] = $display_name;

            // Apply to this plugin
            $added_by[ 'plugins' ][ $plugin ] = $user_id;

            if ( $apply_all ) {
                $all_plugins = array_keys( get_plugins() );
                foreach ( $all_plugins as $slug ) {
                    if ( ! isset( $added_by[ 'plugins' ][ $slug ] ) ) {
                        $added_by[ 'plugins' ][ $slug ] = $user_id;
                    }
                }
            }

            $display = '<span>' . esc_html( $display_name ) . '</span>';
        }

        update_option( DDTT_GO_PF . 'plugins_added_by', $added_by );

        wp_send_json_success( [
            'html'     => $display,
            'applyAll' => $apply_all,
            'user_id'  => $user_id
        ] );
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
     *
     * @param string $screen
     * @return void
     */
    public function enqueue_scripts( $screen ) {
        // Get the plugins tab slug
        $plugins_page = 'toplevel_page_'.DDTT_TEXTDOMAIN;

        // Allow for multisite
        if ( is_network_admin() ) {
            $plugins_page .= '-network';
        }

        // Are we on the plugins page?
        if ( $screen != $plugins_page ) {
            return;
        }

        // Current tab
        $tab = ddtt_get( 'tab' );

        // Feedback form and error code checker
        if ( $tab == 'plugins' ) {
            $handle = DDTT_GO_PF.'plugins_script';
            wp_register_script( $handle, DDTT_PLUGIN_JS_PATH.'plugins.js', [ 'jquery' ], time(), true );
            wp_localize_script( $handle, 'pluginsAjax', [
                'nonce'        => wp_create_nonce( $this->nonce ),
                'ajaxurl'      => admin_url( 'admin-ajax.php' )
            ] );
            wp_enqueue_script( $handle );
            wp_enqueue_script( 'jquery' );
        }
    } // End enqueue_scripts()
}