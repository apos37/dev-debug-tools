<?php
/**
 * Globals
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Globals {

    /**
     * Nonce
     *
     * @var string
     */
    private $nonce = 'ddtt_globals_nonce';


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?Globals $instance = null;


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
        add_action( 'wp_ajax_ddtt_get_global_variable', [ $this, 'ajax_get_global_variable' ] );
        add_action( 'wp_ajax_nopriv_ddtt_get_global_variable', '__return_false' );
    } // End __construct()


    /**
     * Enqueue assets
     *
     * @param string $hook The current admin page hook.
     * @return void
     */
    public function enqueue_assets( $hook ) : void {
        if ( ! AdminMenu::is_current_screen( $hook, 'tools', 'globals' ) ) {
            return;
        }

        global $menu, $submenu;
        $menu_data    = is_array( $menu ) ? $menu : [];
        $submenu_data = is_array( $submenu ) ? $submenu : [];

        wp_localize_script( 'ddtt-tool-globals', 'ddtt_globals', [
            'nonce'   => wp_create_nonce( $this->nonce ),
            'menu'    => $menu_data,
            'submenu' => $submenu_data,
            'i18n'    => [
                'loading'      => __( 'Loading', 'dev-debug-tools' ),
                'showing'      => __( 'Showing value for', 'dev-debug-tools' ),
                'not_selected' => __( 'The selected global variable value will be displayed here.', 'dev-debug-tools' ),
                'property'     => __( 'Property', 'dev-debug-tools' ),
                'value'        => __( 'Value', 'dev-debug-tools' ),
            ],
        ] );
    } // End enqueue_assets()


    /**
     * AJAX handler to get the value of a global variable
     *
     * @return void
     */
    public function ajax_get_global_variable() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        $key = isset( $_POST[ 'key' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'key' ] ) ) : '';
        $rows = '';
        $has_data = false;
        $var = null;

        // Handle menu/submenu safely
        $doing_blank = false;
        if ( $key === 'menu' || $key === 'submenu' ) {
            // Expect JS has passed these into a global variable
            $js_var_name = $key;
            $var = isset( $_POST[ $js_var_name ] ) ? filter_var_array( wp_unslash( $_POST[ $js_var_name ] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : [];
        }
        // Otherwise, try $GLOBALS first
        elseif ( $key && isset( $GLOBALS[ $key ] ) ) {
            $var = $GLOBALS[ $key ];
        }
        // Fallback: dynamic global
        elseif ( $key ) {
            global ${$key};
            if ( isset( ${$key} ) ) {
                $var = ${$key};
            }
        } else {
            $doing_blank = true;
        }

        if ( ! $doing_blank ) {
            if ( isset( $var ) ) {
                if ( is_array( $var ) || is_object( $var ) ) {
                    if ( ! empty( (array) $var ) ) {
                        $has_data = true;
                        foreach ( (array) $var as $prop => $val ) {
                            $formatted_value = Helpers::print_stored_value_to_table( $val );
                            $display_value   = Helpers::truncate_string( $formatted_value, true );

                            $rows .= '<tr>';
                            $rows .= '<td><span class="ddtt-highlight-variable">' . esc_html( $prop ) . '</span></td>';
                            $rows .= '<td>' . $display_value . '</td>';
                            $rows .= '</tr>';
                        }
                    } else {
                        $rows = '<tr><td colspan="2"><em>' . esc_html__( 'Empty array/object', 'dev-debug-tools' ) . '</em></td></tr>';
                    }
                } else {
                    $formatted_value = Helpers::print_stored_value_to_table( $var );
                    $display_value   = Helpers::truncate_string( $formatted_value, true );

                    $rows = '<tr><td><span class="ddtt-highlight-variable">' . esc_html( $key ) . '</span></td>';
                    $rows .= '<td>' . $display_value . '</td></tr>';
                }
            } else {
                $rows = '<tr><td colspan="2"><em>' . esc_html__( 'Sorry, but this global variable is not accessible.', 'dev-debug-tools' ) . '</em></td></tr>';
            }
        }

        // Save the last variable
        update_option( 'ddtt_last_global_variable', $key );

        wp_send_json_success( [
            'rows' => $rows,
            'has_data' => $has_data,
        ] );
    } // End ajax_get_global_variable()


    /**
     * Prevent cloning and unserializing
     */
    public function __clone() {}
    public function __wakeup() {}
    
}


Globals::instance();