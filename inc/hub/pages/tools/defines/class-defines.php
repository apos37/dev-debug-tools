<?php
/**
 * Defines
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Defines {

    /**
     * Nonce
     *
     * @var string
     */
    private $nonce = 'ddtt_defines_nonce';


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?Defines $instance = null;


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
        add_action( 'wp_ajax_ddtt_get_defined_constant', [ $this, 'ajax_get_defined_constant' ] );
        add_action( 'wp_ajax_nopriv_ddtt_get_defined_constant', '__return_false' );
    } // End __construct()


    /**
     * Enqueue assets
     *
     * @param string $hook The current admin page hook.
     * @return void
     */
    public function enqueue_assets( $hook ) : void {
        if ( ! AdminMenu::is_current_screen( $hook, 'tools', 'defines' ) ) {
            return;
        }

        $categories = @get_defined_constants( true );
        foreach ( $categories as $cat => &$consts ) {
            foreach ( $consts as $name => $value ) {
                // Only keep scalar values (string, int, float, bool, null)
                if ( ! is_scalar( $value ) && $value !== null ) {
                    unset( $consts[ $name ] );
                    continue;
                }
                // Remove values that cannot be JSON encoded
                if ( json_encode( $value ) === false ) {
                    unset( $consts[ $name ] );
                }
            }
        }
        unset( $consts );

        wp_localize_script( 'ddtt-tool-defines', 'ddtt_defines', [
            'nonce'      => wp_create_nonce( $this->nonce ),
            'categories' => $categories,
            'i18n'       => [
                'loading'      => __( 'Loading', 'dev-debug-tools' ),
                'showing'      => __( 'Showing value for', 'dev-debug-tools' ),
                'not_selected' => __( 'The selected defined constant value will be displayed here.', 'dev-debug-tools' ),
                'category'     => __( 'Category', 'dev-debug-tools' ),
                'property'     => __( 'Property', 'dev-debug-tools' ),
                'value'        => __( 'Value', 'dev-debug-tools' ),
            ],
        ] );
    } // End enqueue_assets()


    /**
     * AJAX handler to get the value of a defined constant
     *
     * @return void
     */
    public function ajax_get_defined_constant() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        $key = isset( $_POST[ 'key' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'key' ] ) ) : '';
        $rows = '';
        $has_data = false;

        // Get all constants grouped by category
        $all_constants = get_defined_constants( true );

        // Build a lookup: constant => category
        $constant_categories = [];
        foreach ( $all_constants as $cat => $category_constants ) {
            foreach ( $category_constants as $name => $value ) {
                $constant_categories[ $name ] = $cat;
            }
        }

        // Flatten to a single array: key => value
        $constants = [];
        foreach ( $all_constants as $category_constants ) {
            $constants = array_merge( $constants, $category_constants );
        }

        $keys = is_array( $key ) ? $key : ( $key ? [ $key ] : [] );
        if ( ! empty( $keys ) ) {
            foreach ( $keys as $single_key ) {
                $single_key = sanitize_text_field( wp_unslash( $single_key ) );
                $category = isset( $constant_categories[ $single_key ] ) ? $constant_categories[ $single_key ] : 'Unknown';
                if ( $single_key && isset( $constants[ $single_key ] ) ) {
                    $var = $constants[ $single_key ];
                    if ( is_array( $var ) || is_object( $var ) ) {
                        if ( ! empty( (array) $var ) ) {
                            $has_data = true;
                            foreach ( (array) $var as $prop => $val ) {
                                $formatted_value = Helpers::print_stored_value_to_table( $val );
                                $display_value   = Helpers::truncate_string( $formatted_value, true );
                                $rows .= '<tr>';
                                $rows .= '<td>' . esc_html( $category ) . '</td>';
                                $rows .= '<td><span class="ddtt-highlight-variable">' . esc_html( $prop ) . '</span></td>';
                                $rows .= '<td>' . $display_value . '</td>';
                                $rows .= '</tr>';
                            }
                        } else {
                            $rows .= '<tr><td colspan="3"><em>' . esc_html__( 'Empty array/object', 'dev-debug-tools' ) . '</em></td></tr>';
                        }
                    } else {
                        $formatted_value = Helpers::print_stored_value_to_table( $var );
                        $display_value   = Helpers::truncate_string( $formatted_value, true );
                        $rows .= '<tr>';
                        $rows .= '<td>' . esc_html( $category ) . '</td>';
                        $rows .= '<td><span class="ddtt-highlight-variable">' . esc_html( $single_key ) . '</span></td>';
                        $rows .= '<td>' . $display_value . '</td></tr>';
                    }
                } else {
                    $rows .= '<tr>';
                    $rows .= '<td>' . esc_html( $category ) . '</td>';
                    $rows .= '<td><span class="ddtt-highlight-variable">' . esc_html( $single_key ) . '</span></td>';
                    $rows .= '<td><em>' . esc_html__( 'Sorry, but this constant is not accessible.', 'dev-debug-tools' ) . '</em></td>';
                    $rows .= '</tr>';
                }
            }
        } else {
            $rows = '<tr><td colspan="3"><em>' . esc_html__( 'No constants found.', 'dev-debug-tools' ) . '</em></td></tr>';
        }

        // Save last selected constant
        update_option( 'ddtt_last_defined_constant', is_array( $key ) ? ( $key[0] ?? '' ) : $key );

        wp_send_json_success([
            'rows'     => $rows,
            'has_data' => $has_data,
        ]);
    }  // End ajax_get_defined_constant()


    /**
     * Prevent cloning and unserializing
     */
    public function __clone() {}
    public function __wakeup() {}
    
}


Defines::instance();