<?php
/**
 * Tools
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Tools {
    
    /**
     * Get a list of the tools.
     *
     * This method returns an array of tool links.
     *
     * @return array
     */
    public static function get_tool_links() : array {
        $tool_data = AdminMenu::get_ordered_tool_data();
        if ( empty( $tool_data ) || ! is_array( $tool_data ) ) {
            return [];
        }

        $ordered   = [];
        $unordered = [];

        foreach ( $tool_data as $slug => $data ) {
            $entry = [
                'title'   => $data[ 'name' ],
                'url'     => admin_url( 'admin.php?page=dev-debug-tools&tool=' . $slug ),
                'desc'    => $data[ 'description' ],
                'slug'    => $slug,
                'enabled' => $data[ 'enabled' ] ?? true,
            ];

            if ( isset( $data[ 'order' ] ) && $data[ 'order' ] !== '' && is_numeric( $data[ 'order' ] ) ) {
                $ordered[ (int) $data[ 'order' ] ] = $entry;
            } else {
                $unordered[] = $entry;
            }
        }

        ksort( $ordered, SORT_NUMERIC );
        $tool_links = array_values( $ordered ); // reindex to keep order clean
        $tool_links = array_merge( $tool_links, $unordered );

        /**
         * Allow filtering of the resource links
         */
        return apply_filters( 'ddtt_tool_links', $tool_links );
    } // End get_tool_links()
    

    /**
     * Nonce for saving tool links
     *
     * @var string
     */
    private $nonce = 'ddtt_save_tools';


    /**
     * Meta key for storing resources
     *
     * @var string
     */
    public $option_key = 'ddtt_tools';


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?Tools $instance = null;


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
        add_action( 'wp_ajax_ddtt_save_tools', [ $this, 'ajax_save_tools' ] );
        add_action( 'wp_ajax_ddtt_favorite_tool', [ $this, 'ajax_favorite_tool' ] );
        add_action( 'wp_ajax_ddtt_toggle_tool', [ $this, 'ajax_toggle_tool' ] );
    } // End __construct()


    /**
     * Enqueue assets for the resources page
     *
     * @param string $hook The current admin page hook.
     * @return void
     */
    public function enqueue_assets( $hook ) : void {
        if ( ! AdminMenu::is_current_screen( $hook, 'tools' ) ) {
            return;
        }

        wp_localize_script( "ddtt-page-tools", 'ddtt_tools', [
            'nonce' => wp_create_nonce( $this->nonce ),
            'i18n' => [
                'disableTool' => __( 'Disable Tool', 'dev-debug-tools' ),
                'enableTool'  => __( 'Enable Tool', 'dev-debug-tools' ),
            ]
        ] );
    } // End enqueue_assets()


    /**
     * AJAX handler to save tools
     *
     * @return void
     */
    public function ajax_save_tools() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error();
        }
        check_ajax_referer( $this->nonce, 'nonce' );

        $order_map = isset( $_POST[ 'tools' ] ) && is_array( $_POST[ 'tools' ] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST[ 'tools' ] ) ) : []; 

        $saved_tools = get_option( 'ddtt_tools', [] );

        $enabled_map = [];
        if ( is_array( $saved_tools ) ) {
            foreach ( $saved_tools as $tool_info ) {
                $slug = sanitize_key( $tool_info[ 'slug' ] ?? '' );
                if ( $slug ) {
                    $enabled_map[ $slug ] = filter_var( $tool_info[ 'enabled' ] ?? true, FILTER_VALIDATE_BOOLEAN );
                }
            }
        }

        $new_saved = [];

        // Rebuild array ordered by $order_map keys and index values
        asort( $order_map );
        foreach ( $order_map as $slug => $index ) {
            $slug = sanitize_key( $slug );
            if ( ! $slug ) {
                continue;
            }
            $new_saved[ $index ] = [
                'slug'    => $slug,
                'enabled' => $enabled_map[ $slug ] ?? true,
            ];
        }

        // Fill in any tools not in $order_map, appended after
        if ( is_array( $saved_tools ) ) {
            foreach ( $saved_tools as $tool_info ) {
                $slug = sanitize_key( $tool_info[ 'slug' ] ?? '' );
                if ( ! $slug || isset( $order_map[ $slug ] ) ) {
                    continue;
                }
                $new_saved[] = [
                    'slug'    => $slug,
                    'enabled' => $enabled_map[ $slug ] ?? ( $tool_info[ 'enabled' ] ?? true ),
                ];
            }
        }

        // Reindex numerically
        $new_saved = array_values( $new_saved );

        update_option( 'ddtt_tools', $new_saved );

        wp_send_json_success( [ 'saved' => true, 'tools' => $new_saved ] );
    } // End ajax_save_tools()


    /**
     * AJAX handler to favorite a tool
     *
     * @return void
     */
    public function ajax_favorite_tool() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error();
        }
        check_ajax_referer( $this->nonce, 'nonce' );

        $slug = sanitize_text_field( wp_unslash( $_POST[ 'slug' ] ?? '' ) );
        $favorited = filter_var( wp_unslash( $_POST[ 'favorited' ] ?? '' ), FILTER_VALIDATE_BOOLEAN );
        if ( ! $slug ) {
            wp_send_json_error();
        }

        $favorites = get_option( 'ddtt_favorite_tools', [] );
        if ( $favorited ) {
            $favorites[] = $slug;
        } else {
            $favorites = array_diff( $favorites, [ $slug ] );
        }
        $favorites = array_unique( $favorites );
        update_option( 'ddtt_favorite_tools', $favorites );

        wp_send_json_success( [ 'message' => __( 'Tool favorited successfully.', 'dev-debug-tools' ) ] );
    } // End ajax_favorite_tool()

    
    /**
     * AJAX handler to enable a tool
     *
     * @return void
     */
    public function ajax_toggle_tool() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error();
        }
        check_ajax_referer( $this->nonce, 'nonce' );

        $post_data = wp_unslash( $_POST );

        $slug = isset( $post_data[ 'slug' ] ) ? sanitize_key( $post_data[ 'slug' ] ) : '';
        $enabled = isset( $post_data[ 'enabled' ] ) ? filter_var( $post_data[ 'enabled' ], FILTER_VALIDATE_BOOLEAN ) : false;

        if ( ! $slug ) {
            wp_send_json_error( 'Invalid slug' );
        }

        $saved_tools = get_option( 'ddtt_tools', [] );
        if ( ! is_array( $saved_tools ) ) {
            $saved_tools = [];
        }

        $exists = false;
        foreach ( $saved_tools as & $tool ) {
            if ( isset( $tool[ 'slug' ] ) && $tool[ 'slug' ] === $slug ) {
                $tool[ 'enabled' ] = $enabled;
                $exists = true;
                break;
            }
        }
        unset( $tool );

        if ( ! $exists ) {
            $all_tools = AdminMenu::get_ordered_tool_data();

            $new_tool = [
                'slug'    => $slug,
                'enabled' => $enabled,
            ];

            foreach ( $all_tools as $i => $tool_info ) {
                if ( isset( $tool_info[ 'slug' ] ) && $tool_info[ 'slug' ] === $slug ) {
                    $new_tool[ 'order' ] = $i;
                    break;
                }
            }

            $saved_tools[] = $new_tool;
        }

        usort( $saved_tools, function( $a, $b ) {
            $order_a = $a[ 'order' ] ?? PHP_INT_MAX;
            $order_b = $b[ 'order' ] ?? PHP_INT_MAX;
            return $order_a <=> $order_b;
        } );

        update_option( 'ddtt_tools', $saved_tools );

        wp_send_json_success();
    } // End ajax_toggle_tool()


    /**
     * Prevent cloning and unserializing
     */
    private function __clone() {}
    private function __wakeup() {}

}


Tools::instance();