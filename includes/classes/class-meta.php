<?php
/**
 * Meta tabs class
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Initiate the class
 */
new DDTT_META_TABS;


/**
 * Main plugin class.
 */
class DDTT_META_TABS {

    /**
     * Nonce
     */
    private $nonce = DDTT_GO_PF.'meta';
    

    /**
	 * Constructor
	 */
	public function __construct() {

        // Ajax
        add_action( 'wp_ajax_'.DDTT_GO_PF.'update_meta', [ $this, 'ajax' ] );
        add_action( 'wp_ajax_nopriv_'.DDTT_GO_PF.'update_meta', [ $this, 'must_login' ] );

        // Enqueue scripts
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

	} // End __construct()


    /**
     * Populate update meta field
     *
     * @return void
     */
    public function ajax() {
        // First verify the nonce
        if ( !wp_verify_nonce( sanitize_text_field( wp_unslash ( $_REQUEST[ 'nonce' ] ) ), $this->nonce ) ) {
            exit( 'No naughty business please.' );
        }

        // Get the code
        $tab = isset( $_REQUEST[ 'tab' ] ) ? sanitize_key( $_REQUEST[ 'tab' ] ) : false;
        $id = isset( $_REQUEST[ 'id' ] ) ? absint( $_REQUEST[ 'id' ] ) : false;
        $type = isset( $_REQUEST[ 'type' ] ) ? sanitize_key( $_REQUEST[ 'type' ] ) : false;
        $meta_key = isset( $_REQUEST[ 'metaKey' ] ) ? sanitize_key( $_REQUEST[ 'metaKey' ] ) : false;

        if ( $tab && $id && $type && $meta_key ) {

            // Get it
            if ( $tab == 'usermeta' ) {
                if ( $type == 'object' ) {
                    $user = get_userdata( $id );
                    $value = $user->$meta_key;
                } else {
                    $value = get_user_meta( $id, $meta_key, true );
                }
                if ( is_array( $value ) ) {
                    $format = 'array';
                } elseif ( is_object( $value ) ) {
                    $format = 'object';
                } else {
                    $format = 'string';
                }
                if ( $format == 'array' || $format == 'object' ) {
                    $value = wp_json_encode( $value, JSON_PRETTY_PRINT );
                } else {
                    $value = wp_kses_post( $value );
                }
            } else {
                if ( $type == 'object' ) {
                    $post = get_post( $id );
                    $value = $post->$meta_key;
                } else {
                    $value = get_post_meta( $id, $meta_key, true );
                }
                if ( is_array( $value ) ) {
                    $format = 'array';
                } elseif ( is_object( $value ) ) {
                    $format = 'object';
                } else {
                    $format = 'string';
                }
                if ( $format == 'array' || $format == 'object' ) {
                    $value = wp_json_encode( $value, JSON_PRETTY_PRINT );
                } else {
                    $value = wp_kses_post( $value );
                }
            }
            
            // Return it
            $result[ 'value' ] = $value;
            $result[ 'format' ] = $format;
            $result[ 'type' ] = 'success';

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
    } // End verify_log_files()


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

        // Tab
        $tab = ddtt_get( 'tab' );
        if ( $tab !== 'usermeta' && $tab !== 'postmeta' ) {
            return;
        }

        // ID
        $id = 0;
        if ( $tab == 'usermeta' ) {
            $s = false;
            if ( ddtt_get( 'user' ) ) {
                $s = ddtt_get( 'user' );
            } elseif ( isset( $_POST[ 'user' ] ) && $_POST[ 'user' ] != '' ) {
                $s = sanitize_text_field( $_POST[ 'user' ] );
            }
            if ( $s ) {

                // Get the user from the search
                if ( filter_var( $s, FILTER_VALIDATE_EMAIL ) ) {
                    $s = strtolower( $s );
                    if ( $user = get_user_by( 'email', $s ) ) {
                        $id = $user->ID;
                    }
                } elseif ( is_numeric( $s ) ) {
                    if ( $user = get_user_by( 'id', $s ) ) {
                        $id = $s;
                    }
                }
            } else {
                $id = get_current_user_id();
            }
        } else {
            if ( $post_id = ddtt_get( 'post_id' ) ) {
                $id = filter_var( $post_id, FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 1 ] ] );
            } elseif ( isset( $_POST[ 'post_id' ] ) && $_POST[ 'post_id' ] != '' ) {
                $id = filter_var( $_POST['post_id'], FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 1 ] ] );
            }
        }

        // Handle it
        if ( $id ) {
            $handle = DDTT_GO_PF.'meta_script';
            wp_register_script( $handle, DDTT_PLUGIN_JS_PATH.'meta.js', [ 'jquery' ], time() );
            wp_localize_script( $handle, 'metaAjax', [
                'nonce'     => wp_create_nonce( $this->nonce ),
                'tab'       => $tab,
                'id'        => $id,
                'ajaxurl'   => admin_url( 'admin-ajax.php' ) 
            ] );
            wp_enqueue_script( $handle );
            wp_enqueue_script( 'jquery' );
        }
    } // End enqueue_scripts()
}