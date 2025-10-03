<?php
/**
 * Shortcodes
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Shortcodes {

    /**
     * Excluded Search Post Types
     *
     * @return array An array of post types to exclude from shortcode search.
     */
    public function excluded_search_post_types() : array {
        return apply_filters( 'ddtt_shortcode_finder_exclude_post_types', [
            'attachment',
            'revision',
            'nav_menu_item',
            'custom_css',
            'customize_changeset',
            'oembed_cache',
            'user_request',
            'wp_block',
            'wp_template',
            'wp_template_part',
            'wp_global_styles',
            'wp_navigation',
            'cs_template',
            'cs_user_templates',
            'um_form',
            'um_directory',
            'cs_global_block',
            'x-portfolio',
        ] );
    } // End excluded_search_post_types()


    /**
     * Nonce
     *
     * @var string
     */
    private $nonce = 'ddtt_shortcodes_nonce';


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?Shortcodes $instance = null;


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
        add_action( 'wp_ajax_ddtt_find_shortcode', [ $this, 'ajax_find_shortcode' ] );
        add_action( 'wp_ajax_nopriv_ddtt_find_shortcode', '__return_false' );
    } // End __construct()


    /**
     * Get All Shortcodes
     *
     * @return array An array of all registered shortcodes.
     */
    public static function get_all_shortcodes( $keys_only = false ) : array {
        global $shortcode_tags;
        ksort( $shortcode_tags );
        $shortcode_keys = array_keys( $shortcode_tags );
        return $keys_only ? $shortcode_keys : $shortcode_tags;
    } // End get_all_shortcodes()


    /**
     * Group Shortcodes by Source
     *
     * @param array $shortcode_tags The array of shortcode tags.
     * @return array An associative array grouping shortcodes by their source (plugin or theme).
     */
    public static function group_shortcodes_by_source( $shortcode_tags ) {
        $grouped_shortcodes = [];

        foreach ( $shortcode_tags as $sc => $callback ) {
            $source_name = 'Unknown';
            $source_type = 'Other';
            $file_path   = '';
            $line        = '';
            $errors      = [];

            try {
                $fx = null;

                if ( is_array( $callback ) ) {
                    if ( count( $callback ) >= 2 ) {
                        $class_or_object = $callback[0];
                        $method_name     = $callback[1];
                        if (
                            ( is_string( $class_or_object ) && class_exists( $class_or_object ) ) ||
                            ( is_object( $class_or_object ) && is_callable( [ $class_or_object, $method_name ] ) )
                        ) {
                            if ( method_exists( $class_or_object, $method_name ) ) {
                                $fx = new \ReflectionMethod( $class_or_object, $method_name );
                            }
                        }
                    }
                } elseif ( is_string( $callback ) && function_exists( $callback ) ) {
                    $fx = new \ReflectionFunction( $callback );
                }

                if ( $fx ) {
                    $abs_path  = $fx->getFileName();
                    $file_path = Helpers::relative_pathname( $abs_path );
                    $line      = $fx->getStartLine();

                    // --- WordPress Core check ---
                    if ( strpos( $abs_path, ABSPATH . 'wp-includes/' ) === 0 ) {
                        $source_name = 'Core';
                        $source_type = 'WordPress';

                    // --- Plugin check ---
                    } elseif ( strpos( $abs_path, WP_PLUGIN_DIR ) === 0 ) {
                        $plugin_slug = explode( '/', str_replace( WP_PLUGIN_DIR . '/', '', $abs_path ) )[0];
                        $plugin_data = false;

                        if ( ! function_exists( 'get_plugin_data' ) ) {
                            require_once ABSPATH . 'wp-admin/includes/plugin.php';
                        }

                        foreach ( get_option( 'active_plugins', [] ) as $ap ) {
                            if ( strpos( $ap, $plugin_slug . '/' ) === 0 ) {
                                $plugin_file = WP_PLUGIN_DIR . '/' . $ap;
                                $plugin_data = get_plugin_data( $plugin_file );
                                break;
                            }
                        }

                        if ( $plugin_data ) {
                            $source_name = $plugin_data['Name'];
                        } else {
                            $source_name = ucfirst( $plugin_slug );
                        }
                        $source_type = 'Plugin';

                    // --- Theme check ---
                    } elseif ( strpos( $abs_path, get_theme_root() ) === 0 ) {
                        $theme_slug = explode( '/', str_replace( get_theme_root() . '/', '', $abs_path ) )[0];
                        $themes     = wp_get_themes();

                        if ( isset( $themes[ $theme_slug ] ) ) {
                            $source_name = $themes[ $theme_slug ]->get( 'Name' );
                            $source_type = 'Theme';
                        }
                    }
                }
            } catch ( \Exception $e ) {
                $errors[] = $e->getMessage();
            }

            $key = $source_type . ': ' . $source_name;
            if ( ! isset( $grouped_shortcodes[ $key ] ) ) {
                $grouped_shortcodes[ $key ] = [];
            }

            $grouped_shortcodes[ $key ][] = [
                'shortcode' => $sc,
                'source'    => $source_name,
                'type'      => $source_type,
                'file'      => $file_path,
                'line'      => $line,
                'errors'    => $errors,
            ];
        }

        // --- Sort groups: Core → Theme → Plugin → Other ---
        $sorted_groups = [];
        foreach ( [ 'WordPress', 'Theme', 'Plugin', 'Other' ] as $type ) {
            foreach ( $grouped_shortcodes as $key => $shortcodes ) {
                if ( str_starts_with( $key, $type . ':' ) ) {
                    $sorted_groups[ $key ] = $shortcodes;
                }
            }
        }

        return $sorted_groups;
    } // End group_shortcodes_by_source()


    /**
     * Locate Shortcode Usage
     *
     * @param string $shortcode Shortcode to search for.
     * @param array  $attr      Optional attributes to filter by (key => value).
     * @return array            Array of post results containing the shortcode.
     */
    public function locate_shortcode( $shortcode, $attr = [] ) : array {
        $results = [];

        // Get all post types
        $post_types = get_post_types();

        // Exclude specific post types
        $exclude = $this->excluded_search_post_types();
        foreach ( $post_types as $key => $post_type ) {
            if ( in_array( $post_type, $exclude, true ) ) {
                unset( $post_types[ $key ] );
            }
        }

        // Query posts
        $the_query = new \WP_Query( [
            'post_type'      => $post_types,
            'posts_per_page' => -1,
        ] );

        if ( $the_query->have_posts() ) {

            while ( $the_query->have_posts() ) {

                $the_query->the_post();

                // Get post content and apply filter
                $content = get_the_content();
                $content = apply_filters( 'ddtt_shortcode_finder_post_content', $content, get_the_ID() );

                // Build shortcode regex
                $shortcode_regex = '/'. get_shortcode_regex( [ $shortcode ] ) .'/';

                if ( preg_match_all( $shortcode_regex, $content, $matches ) ) {

                    $matched_count = 0;

                    if ( ! empty( $attr ) ) {
                        // Only count instances where all attributes match
                        if ( isset( $matches[3] ) && ! empty( $matches[3] ) ) {
                            foreach ( $matches[3] as $match ) {
                                $found_attrs = shortcode_parse_atts( $match );
                                $all_match = true;
                                foreach ( $attr as $key => $value ) {
                                    if ( ! isset( $found_attrs[ $key ] ) || $found_attrs[ $key ] !== $value ) {
                                        $all_match = false;
                                        break;
                                    }
                                }
                                if ( $all_match ) {
                                    $matched_count++;
                                }
                            }
                        }
                    } else {
                        // No attributes provided, count all matches
                        $matched_count = count( $matches[0] );
                    }

                    if ( $matched_count > 0 ) {
                        // Post type label
                        $post_type_obj = get_post_type_object( get_post_type() );
                        $pt_name       = $post_type_obj ? esc_html( $post_type_obj->labels->singular_name ) : '';

                        // Post status label
                        $status_map = [
                            'publish'  => 'Published',
                            'draft'    => 'Draft',
                            'private'  => 'Private',
                            'archive'  => 'Archived',
                        ];
                        $current_status = $status_map[ get_post_status() ] ?? 'Unknown';

                        // Add result
                        $results[] = [
                            'title'       => get_the_title(),
                            'id'          => get_the_ID(),
                            'url'         => get_the_permalink(),
                            'post_type'   => $pt_name,
                            'post_status' => $current_status,
                            'count'       => $matched_count,
                        ];
                    }
                }
            }

            wp_reset_postdata();
        }

        return $results;
    } // End locate_shortcode()


    /**
     * Enqueue assets
     *
     * @param string $hook The current admin page hook.
     * @return void
     */
    public function enqueue_assets( $hook ) : void {
        if ( ! AdminMenu::is_current_screen( $hook, 'tools', 'shortcodes' ) ) {
            return;
        }

        wp_localize_script( 'ddtt-tool-shortcodes', 'ddtt_shortcodes', [
            'nonce' => wp_create_nonce( $this->nonce ),
            'i18n'  => [
                'error'      => __( 'Error', 'dev-debug-tools' ),
                'locating'   => __( 'Locating', 'dev-debug-tools' ),
                'no_results' => __( 'No results found', 'dev-debug-tools' )
            ],
        ] );
    } // End enqueue_assets()


    /**
     * AJAX: Find Shortcode
     *
     * @return void
     */
    public function ajax_find_shortcode() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        if ( ! isset( $_POST[ 'shortcode' ] ) || empty( $_POST[ 'shortcode' ] ) ) {
            wp_send_json_error( [ 'message' => __( 'No shortcode provided.', 'dev-debug-tools' ) ] );
        }

        // Get the shortcode
        $shortcode = sanitize_text_field( wp_unslash( $_POST[ 'shortcode' ] ) );

        // Get attributes as array
        $attr = isset( $_POST[ 'attr' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'attr' ] ) ) : '';
        $attr_array = [];

        // Convert string to array for locate_shortcode()
        if ( $attr ) {
            preg_match_all( '/(\w+)="(.*?)"/', $attr, $matches, PREG_SET_ORDER );
            foreach ( $matches as $m ) {
                $attr_array[ $m[1] ] = $m[2];
            }
        }

        $locate = $this->locate_shortcode( $shortcode, $attr_array );

        // Search for the shortcode
        if ( $shortcode ) {
            $type   = 'SUCCESS';
            $text   = '';
            $data   = $locate;

        } else {
            $type   = 'ERROR';
            $text   = 'No Shortcode Found.';
            $data   = [];
        }

        // Build the shortcode string for display
        $attr = $attr ? ' ' . $attr : '';
        $shortcode_string = '[' . $shortcode . $attr . ']';

        wp_send_json_success( [ 
            'type'      => $type, 
            'text'      => $text, 
            'data'      => $data, 
            'shortcode' => $shortcode_string 
        ] );
    } // End ajax_find_shortcode()


    /**
     * Prevent cloning and unserializing
     */
    private function __clone() {}
    private function __wakeup() {}
    
}


Shortcodes::instance();