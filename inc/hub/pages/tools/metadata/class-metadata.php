<?php
/**
 * Metadata
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Metadata {

    /**
     * Get the sections for the metadata page.
     *
     * Returns an array of sections with their titles.
     *
     * @return array
     */
    public static function sections() : array {
        return [
            'user'    => __( 'User', 'dev-debug-tools' ),
            'post'    => __( 'Post', 'dev-debug-tools' ),
            'term'    => __( 'Term', 'dev-debug-tools' ),
            'comment' => __( 'Comment', 'dev-debug-tools' ),
            'media'   => __( 'Media', 'dev-debug-tools' ),
        ];
    } // End sections()


    /**
     * Get the types for the metadata viewer.
     *
     * @return array
     */
    public static function types() : array {
        return [
            'general'      => [
                'label'    => __( 'General Info', 'dev-debug-tools' ),
                'sections' => 'all',
            ],
            'object'       => [
                'label'    => __( 'Object Meta', 'dev-debug-tools' ),
                'sections' => 'all',
            ],
            'custom'       => [
                'label'    => __( 'Custom Meta', 'dev-debug-tools' ),
                'sections' => 'all',
            ],
            'roles'      => [
                'label'    => __( 'Roles', 'dev-debug-tools' ),
                'sections' => [ 'user' ],
            ],
            'capabilities' => [
                'label'    => __( 'Capabilities', 'dev-debug-tools' ),
                'sections' => [ 'user' ],
            ],
            'taxonomies'   => [
                'label'    => __( 'Taxonomies', 'dev-debug-tools' ),
                'sections' => [ 'post' ],
            ],
            // 'comments'     => [
            //     'label'    => __( 'Comments', 'dev-debug-tools' ),
            //     'sections' => [ 'post' ],
            // ],
        ];
    } // End types()


    /**
     * Get the UI setting keys for metadata viewer.
     *
     * @return array
     */
    public static function ui_setting_keys() : array {
        $keys = [
            'closedpostboxes_*',
            'meta-box-order_*',
            '*columnshidden',
            'metaboxhidden_*',
            'screen_layout_*',
        ];


        /**
         * Filter the UI setting keys for metadata viewer.
         */
        $keys = apply_filters( 'ddtt_metadata_ui_setting_keys', $keys );

        return $keys;
    } // End ui_setting_keys()


    /**
     * Get the options for tool.
     *
     * @return array
     */
    public static function settings() : array {
        $sections = self::sections();
        $current_subsection = self::get_current_subsection();
        $current_subsection_label = isset( $sections[ $current_subsection ] ) ? $sections[ $current_subsection ] : '';
        
        $last_lookups = get_option( 'ddtt_metadata_last_lookups', [] );
        $last_lookups = is_array( $last_lookups ) ? array_map( 'absint', $last_lookups ) : [];

        $last_lookup = isset( $last_lookups[ $current_subsection ] ) ? $last_lookups[ $current_subsection ] : false;
        $default_lookup = $last_lookup ? $last_lookup : ( $current_subsection === 'user' ? get_current_user_id() : '' );

        return [
            'general' => [
                'label' => false,
                'fields' => [               
                    'lookup' => [
                        // Translators: %s: current subsection label (User, Post, etc.)
                        'title'     => sprintf( __( 'Look Up %s', 'dev-debug-tools' ), $current_subsection_label ),
                        'desc'      => $current_subsection === 'user' ? __( 'Enter a User ID, Username, or Email to look up user metadata.','dev-debug-tools' ) : __( 'Enter an ID to look up metadata.', 'dev-debug-tools' ),
                        'type'      => 'search',
                        'nonce'     => 'ddtt_metadata_lookup',
                        'scroll_to' => 'ddtt-metadata-general-section',
                        'default'   => $default_lookup,
                    ],
                ]
            ]
        ];
    } // End settings()


    /**
     * Get the protected meta keys that cannot be deleted.
     *
     * @return array
     */
    private static function protected_meta_keys() : array {
        $keys = [
            'ID',
            'user_registered',
            'user_activation_key',
            '_edit_last',
            '_edit_lock',
            '_wp_attached_file',
            '_wp_attachment_metadata'
        ];

        /**
         * Filter the protected meta keys.
         */
        $keys = apply_filters( 'ddtt_protected_meta_keys', $keys );

        return $keys;
    } // End protected_meta_keys()


    /**
     * Nonce for updating meta
     *
     * @var string
     */
    private $nonce = 'ddtt_metadata_nonce';


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?Metadata $instance = null;


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
        $this->handle_button_actions();
        // add_action( 'admin_init', [ $this, 'handle_button_actions' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_ddtt_get_metadata', [ $this, 'ajax_get_metadata' ] );
        add_action( 'wp_ajax_nopriv_ddtt_get_metadata', '__return_false' );
        add_action( 'wp_ajax_ddtt_metadata_table_actions', [ $this, 'ajax_table_actions' ] );
        add_action( 'wp_ajax_nopriv_ddtt_metadata_table_actions', '__return_false' );
        add_action( 'wp_ajax_ddtt_update_meta_value', [ $this, 'ajax_update_meta_value' ] );
        add_action( 'wp_ajax_nopriv_ddtt_update_meta_value', '__return_false' );
        add_action( 'wp_ajax_ddtt_delete_meta_key', [ $this, 'ajax_delete_meta_key' ] );
        add_action( 'wp_ajax_nopriv_ddtt_delete_meta_key', '__return_false' );
        add_action( 'wp_ajax_ddtt_update_user_role', [ $this, 'ajax_update_user_role' ] );
        add_action( 'wp_ajax_nopriv_ddtt_update_user_role', '__return_false' );
        add_action( 'wp_ajax_ddtt_update_user_capability', [ $this, 'ajax_update_user_capability' ] );
        add_action( 'wp_ajax_nopriv_ddtt_update_user_capability', '__return_false' );
        add_action( 'wp_ajax_ddtt_get_tax_terms_editor', [ $this, 'ajax_get_tax_terms_editor' ] );
        add_action( 'wp_ajax_nopriv_ddtt_get_tax_terms_editor', '__return_false' );
        add_action( 'wp_ajax_ddtt_update_tax_terms', [ $this, 'ajax_update_tax_terms' ] );
        add_action( 'wp_ajax_nopriv_ddtt_update_tax_terms', '__return_false' );
        add_action( 'wp_ajax_ddtt_metadata_import', [ $this, 'ajax_metadata_import' ] );
        add_action( 'wp_ajax_nopriv_ddtt_metadata_import', '__return_false' );
    } // End __construct()


    /**
     * Render header notices
     *
     * This method is called to render notices in the header.
     * It checks for deleted options and displays a notice if any were deleted.
     */
    public function render_header_notices() {
        if ( AdminMenu::get_current_page_slug() !== 'dev-debug-tools' || AdminMenu::current_tool_slug() !== 'metadata' ) {
            return;
        }

        if ( get_transient( 'ddtt_metadata_reset' ) ) {
            delete_transient( 'ddtt_metadata_reset' );
            Helpers::render_notice( __( 'Metadata reset successfully.', 'dev-debug-tools' ), 'success' );
        }

        // Check for auto-draft post
        $post_id = isset( $_GET[ 'lookup' ] ) ? absint( $_GET[ 'lookup' ] ) : 0; // phpcs:ignore
        if ( $post_id ) {
            $post = get_post( $post_id );
            if ( $post && $post->post_status === 'auto-draft' ) {
                Helpers::render_notice( __( 'This post is an auto-draft. WordPress does not allow editing auto-draft posts.', 'dev-debug-tools' ), 'notice' );
            }
        }
    } // End render_header_notices()


    /**
     * Handle metadata actions.
     */
    public function handle_button_actions() {
        if ( ! is_admin() || ! current_user_can( 'manage_options' ) || ! Helpers::is_dev() ) {
            return;
        }

        if ( ! isset( $_POST[ 'ddtt_metadata_action' ] ) ) {
            return;
        }

        check_admin_referer( 'ddtt_metadata_action', 'ddtt_metadata_nonce' );

        $action     = isset( $_POST[ 'ddtt_metadata_action' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'ddtt_metadata_action' ] ) ) : '';
        $subsection = isset( $_POST[ 'subsection' ] ) ? sanitize_key( wp_unslash( $_POST[ 'subsection' ] ) ) : '';
        $object_id  = isset( $_POST[ 'object_id' ] ) ? absint( $_POST[ 'object_id' ] ) : 0;

        if ( $action === 'download_meta' ) {
            $object = $this->get_object( $subsection, $object_id );
            if ( ! $object ) {
                wp_die( esc_html( __( 'Object not found.', 'dev-debug-tools' ) ) );
            }

            $export = [];

            // Main object data
            $export[ 'object' ] = ( $subsection === 'user' ) ? $object->data : (array) $object;

            // Custom meta
            switch ( $subsection ) {
                case 'user':
                    $export[ 'custom_meta' ] = get_user_meta( $object_id );
                    $export[ 'roles' ] = isset( $object->roles ) ? $object->roles : [];
                    $export[ 'capabilities' ] = isset( $object->allcaps ) ? $object->allcaps : [];
                    break;
                case 'post':
                case 'media':
                    $export[ 'custom_meta' ] = get_post_meta( $object_id );
                    $export[ 'taxonomies' ] = [];
                    $taxonomies = get_object_taxonomies( get_post_type( $object_id ), 'names' );
                    foreach ( $taxonomies as $taxonomy ) {
                        $terms = wp_get_object_terms( $object_id, $taxonomy, [ 'fields' => 'names' ] );
                        $export[ 'taxonomies' ][ $taxonomy ] = $terms;
                    }
                    break;
                case 'term':
                    $export[ 'custom_meta' ] = get_term_meta( $object_id );
                    break;
                case 'comment':
                    $export[ 'custom_meta' ] = get_comment_meta( $object_id );
                    break;
            }

            header( 'Content-Description: File Transfer' );
            header( 'Content-Type: application/json' );
            header( 'Content-Disposition: attachment; filename=metadata-' . $subsection . '-' . $object_id . '.json' );
            echo wp_json_encode( $export, JSON_PRETTY_PRINT );
            exit;
        }

        if ( $action === 'reset_meta' ) {
            $protected_keys = $this->protected_meta_keys();
            $all_meta = get_metadata( $subsection, $object_id );
            if ( ! empty( $all_meta ) ) {
                foreach ( $all_meta as $meta_key => $values ) {
                    if ( in_array( $meta_key, $protected_keys, true ) ) {
                        continue; // Skip protected keys
                    }
                    delete_metadata( $subsection, $object_id, $meta_key );
                }
            }
            set_transient( 'ddtt_metadata_reset', true, 30 );
            wp_safe_redirect( isset( $_SERVER[ 'REQUEST_URI' ] ) ? wp_unslash( $_SERVER[ 'REQUEST_URI' ] ) : Bootstrap::tool_url( 'metadata&s=' . $subsection ) ); // phpcs:ignore
            exit;
        }
    } // End handle_button_actions()


    /**
     * Get the object ID based on the subsection and object.
     *
     * @param string $subsection The current subsection.
     * @param object $object The object being inspected.
     * @return int|string
     */
    public static function user_lookup_url( $user_id ) : string {
        $nonce = wp_create_nonce( 'ddtt_metadata_lookup' );
        return Bootstrap::tool_url( 'metadata&s=user&lookup=' . $user_id . '&_wpnonce=' . $nonce );
    } // End user_lookup_url()


    /**
     * Get the post lookup URL.
     *
     * @param int $post_id The ID of the post.
     * @return string
     */
    public static function post_lookup_url( $post_id ) : string {
        $nonce = wp_create_nonce( 'ddtt_metadata_lookup' );
        return Bootstrap::tool_url( 'metadata&s=post&lookup=' . $post_id . '&_wpnonce=' . $nonce );
    } // End post_lookup_url()


    /**
     * Get the comment lookup URL.
     *
     * @param int $comment_id The ID of the comment.
     * @return string
     */
    public static function comment_lookup_url( $comment_id ) : string {
        $nonce = wp_create_nonce( 'ddtt_metadata_lookup' );
        return Bootstrap::tool_url( 'metadata&s=comment&lookup=' . $comment_id . '&_wpnonce=' . $nonce );
    } // End comment_lookup_url()


    /**
     * Get the current subsection from the URL
     *
     * @return string
     */
    public static function get_current_subsection() : string {
        return isset( $_GET[ 's' ] ) ? sanitize_key( wp_unslash( $_GET[ 's' ] ) ) : 'user'; // phpcs:ignore
    } // End get_current_subsection()


    /**
     * Render the type heading
     *
     * @param string $function The name of the function rendering the type.
     */
    private static function render_type_heading( $function ) : void {
        $types = self::types();
        $type_key = preg_replace( '/^render_(.+)_type$/', '$1', $function );
        echo '<h3>' . esc_html( $types[ $type_key ][ 'label' ] ) . '</h3>';
    } // End render_type_heading()


    /**
     * Filter and highlight metadata by search and filter rules.
     *
     * @param array $meta_data Associative array of meta key => meta value.
     * @param array $customizations
     * @return array
     */
    private static function filter_and_highlight_metadata( $meta_data, $customizations ) : array {
        $search_terms = [];
        $filter_terms = [];

        if ( ! empty( $customizations[ 'search' ] ) ) {
            $search_terms = array_filter( array_map( 'trim', explode( ',', $customizations[ 'search' ] ) ) );
        }
        if ( ! empty( $customizations[ 'filter' ] ) ) {
            $filter_terms = array_filter( array_map( 'trim', explode( ',', $customizations[ 'filter' ] ) ) );
        }

        $filtered_data = [];

        foreach ( $meta_data as $key => $value ) {
            $value_str = is_scalar( $value ) ? (string) $value : wp_json_encode( $value );

            // --- SEARCH FILTER ---
            if ( $search_terms ) {
                $match_found = false;
                foreach ( $search_terms as $term ) {
                    if ( stripos( $key, $term ) !== false || stripos( $value_str, $term ) !== false ) {
                        $match_found = true;
                        break;
                    }
                }
                if ( ! $match_found ) {
                    continue;
                }
            }

            // --- EXCLUDE FILTER ---
            if ( $filter_terms ) {
                $exclude = false;
                foreach ( $filter_terms as $term ) {
                    if ( stripos( $key, $term ) !== false || stripos( $value_str, $term ) !== false ) {
                        $exclude = true;
                        break;
                    }
                }
                if ( $exclude ) {
                    continue;
                }
            }

            // --- HIGHLIGHT SEARCH TERMS ---
            if ( $search_terms ) {
                foreach ( $search_terms as $term ) {
                    if ( $term === '' ) {
                        continue;
                    }
                    $highlight = '<i class="ddtt-highlight-search">$0</i>';
                    $pattern = '/' . preg_quote( $term, '/' ) . '/i';
                    $key = preg_replace( $pattern, $highlight, $key );
                    // $value_str = preg_replace( $pattern, $highlight, $value_str );
                }
            }

            $filtered_data[ $key ] = $value_str;
        }

        return $filtered_data;
    } // End filter_and_highlight_metadata()


    /**
     * Render a metadata value
     *
     * @param string $subsection The current subsection.
     * @param string $type The metadata type.
     * @param object $object The object being inspected.
     * @param string $key The metadata key.
     * @param mixed $value The metadata value.
     */
    private static function render_meta_value( $subsection, $type, $object, $key, $value ) {
        $display_value = '';

        // Object Meta
        if ( $type === 'object' ) {

            // User Meta
            if ( $subsection === 'user' ) {
                if ( $key === 'user_pass' ) {
                    $display_value = '********';

                } elseif ( $key === 'user_registered' ) {
                    $display_value = $value . ' <span class="ddtt-meta-value-note">(' . Helpers::convert_timezone( $value ) . ')</span>';
                }

            // Post Meta & Comment Meta
            } elseif ( $subsection === 'post' || $subsection === 'comment' || $subsection === 'media' ) {
                if ( $key === 'post_author' ) {
                    $user_id = $value;
                    $user_info = get_userdata( $user_id );

                    $display_value = $value . ' <span class="ddtt-meta-value-note">(<a href="' 
                        . esc_url( self::user_lookup_url( $user_id ) ) . '" target="_blank">'
                        . ( $user_info ? esc_html( $user_info->display_name ) : esc_html__( 'Unknown', 'dev-debug-tools' ) )
                        . '</a>)</span>';

                } elseif ( $key === 'post_date' || $key === 'post_modified' || $key === 'comment_date' || $key === 'comment_modified' ) {
                    $gmt_date = $object->post_date_gmt;
                    $gmt = $gmt_date === $value;

                    $display_value = $value . ' <span class="ddtt-meta-value-note">(' . Helpers::convert_date_format( $value, $gmt ) . ')</span>';

                } elseif ( $key === 'post_date_gmt' || $key === 'post_modified_gmt' || $key === 'comment_date_gmt' || $key === 'comment_modified_gmt' ) {
                    $display_value = $value . ' <span class="ddtt-meta-value-note">(' . Helpers::convert_date_format( $value, true ) . ')</span>';

                } elseif ( $key === 'post_parent' && $value ) {
                    $display_value = $value . ' <span class="ddtt-meta-value-note">(<a href="' . self::post_lookup_url( $value ) . '" target="_blank">' . get_the_title( $value ) . '</a> — ' . get_post_type( $value ) . ')</span>';
                }
            }

        // Custom Meta
        } elseif ( $type === 'custom' ) {

            // Post Meta & Media Meta
            if ( $subsection === 'post' || $subsection === 'media' ) {
                if ( $key === '_edit_last' ) {
                    $user_id = $value;
                    $user_info = get_userdata( $user_id );
                    $nonce = wp_create_nonce( 'ddtt_metadata_lookup' );

                    $display_value = $value . ' <span class="ddtt-meta-value-note">(<a href="' 
                        . esc_url( Bootstrap::tool_url( 'metadata&s=user&lookup=' . $user_id . '&_wpnonce=' . $nonce ) ) . '" target="_blank">'
                        . ( $user_info ? esc_html( $user_info->display_name ) : esc_html__( 'Unknown', 'dev-debug-tools' ) )
                        . '</a>)</span>';

                } elseif ( $key === '_edit_lock' ) {
                    list( $lock_timestamp, $lock_user_id ) = explode( ':', $value . ':' ); // add ':' to avoid missing user_id

                    $lock_timestamp = (int) $lock_timestamp;
                    $lock_user_id   = (int) $lock_user_id;

                    $author_name = '';
                    if ( $lock_user_id ) {
                        $user_info = get_userdata( $lock_user_id );
                        $author_name = $user_info ? esc_html( $user_info->display_name ) : esc_html__( 'Unknown', 'dev-debug-tools' );
                    }

                    $display_value = $value . ' <span class="ddtt-meta-value-note">('
                        . Helpers::convert_date_format( $lock_timestamp ) 
                        . ( $author_name ? ' — ' . $author_name : '' )
                        . ')</span>';
                }
            }
        }

        // General Meta
        if ( ! $display_value && $value ) {
            $display_value = Helpers::print_stored_value_to_table( $value, true );
            $display_value = Helpers::truncate_string( $display_value, true, 500, '…' );
        }
       
        return $display_value;
    } // End render_meta_value()


    /**
     * Render a general metadata type
     *
     * @param string $subsection The current subsection.
     * @param object $object The object to look up metadata for.
     * @param array
     */
    private static function render_general_type( $subsection, $object, $meta_viewer_customizations = [] ) {
        self::render_type_heading( __FUNCTION__ );

        $id = self::get_object_id( $subsection, $object );

        ?>
        <div class="ddtt-metadata-info-card">
            <div class="ddtt-metadata-image <?php echo esc_attr( $subsection ); ?>">
                <?php
                if ( $subsection === 'user' ) {
                    $avatar_url = get_avatar_url( $id );
                    echo '<img src="' . esc_url( $avatar_url ) . '" alt="' . esc_attr( $object->display_name ) . '">';
                } elseif ( $subsection === 'media' && wp_attachment_is_image( $id ) ) {
                    echo wp_get_attachment_image( $id, [ null, 300 ] );
                } elseif ( ( $subsection === 'post' || $subsection === 'term' ) && has_post_thumbnail( $id ) ) {
                    echo get_the_post_thumbnail( $id, [ null, 200 ] );
                } elseif ( $subsection === 'comment' && $object->user_id ) {
                    $avatar_url = get_avatar_url( $object->user_id );
                    echo '<img src="' . esc_url( $avatar_url ) . '" alt="' . esc_attr( $object->comment_author ) . '">';
                } else {
                    echo '<div class="ddtt-placeholder-image"><span>' . esc_html__( 'No Image', 'dev-debug-tools' ) . '</span></div>';
                }
                ?>
            </div>
            <div class="ddtt-metadata-details">
                <h4><?php
                if ( $subsection === 'user' ) {
                    echo esc_html( $object->display_name );
                } elseif ( $subsection === 'comment' ) {
                    echo esc_html( $object->comment_content );
                } else {
                    echo esc_html( $object->post_title );
                }
                ?></h4>

                <p><strong><?php echo esc_html__( 'ID', 'dev-debug-tools' ); ?></strong>: <?php echo esc_html( $id ); ?></p>

                <?php if ( $subsection === 'user' ) { ?>
                    <p><strong><?php echo esc_html__( 'Email', 'dev-debug-tools' ); ?></strong>: <?php echo esc_html( $object->user_email ); ?></p>
                <?php } elseif ( $subsection === 'post' ) {
                    $post_type_obj = get_post_type_object( $object->post_type );
                    $post_type_label = $post_type_obj ? $post_type_obj->labels->singular_name : $object->post_type;
                    ?>
                    <p><strong><?php echo esc_html__( 'Post Type', 'dev-debug-tools' ); ?></strong>: <?php echo esc_html( $post_type_label . ' (' . $object->post_type . ')' ); ?></p>
                <?php } elseif ( $subsection === 'term' ) {
                    $taxonomy_obj = get_taxonomy( $object->taxonomy );
                    $taxonomy_label = $taxonomy_obj ? $taxonomy_obj->labels->singular_name : $object->taxonomy;
                    ?>
                    <p><strong><?php echo esc_html__( 'Taxonomy', 'dev-debug-tools' ); ?></strong>: <?php echo esc_html( $taxonomy_label . ' (' . $object->taxonomy . ')' ); ?></p>
                <?php } elseif ( $subsection === 'comment' ) {
                    $comment_author_display = get_comment_author( $object->comment_ID );
                    ?>
                    <p><strong><?php echo esc_html__( 'Author', 'dev-debug-tools' ); ?></strong>: <?php echo esc_html( $comment_author_display ) . ' (ID: ' . intval( $object->user_id ) . ')'; ?></p>
                <?php } elseif ( $subsection === 'media' ) { ?>
                    <p><strong><?php echo esc_html__( 'Mime Type', 'dev-debug-tools' ); ?></strong>: <?php echo esc_html( $object->post_mime_type ); ?></p>
                <?php } ?>

                <?php
                // Map subsection to REST base
                switch ( $subsection ) {
                    case 'user':
                        $rest_base = 'users';
                        break;
                    case 'post':
                        $rest_base = 'posts';
                        break;
                    case 'comment':
                        $rest_base = 'comments';
                        break;
                    case 'media':
                        $rest_base = 'media';
                        break;
                    case 'term':
                        // For terms, use the taxonomy slug
                        $rest_base = $object->taxonomy;
                        break;
                    default:
                        $rest_base = $subsection . 's';
                }

                $rest_url = rest_url( 'wp/v2/' . $rest_base . '/' . $id );
                ?>

                <p><strong><?php echo esc_html__( 'API Rest URL', 'dev-debug-tools' ); ?></strong>:
                    <a href="<?php echo esc_url( $rest_url ); ?>" target="_blank"><?php echo esc_url( $rest_url ); ?></a>
                </p>


                <?php if ( $subsection === 'media' ) {
                    $alt = get_post_meta( $id, '_wp_attachment_image_alt', true );
                    $meta = wp_get_attachment_metadata( $id );
                    $file_path = get_attached_file( $id );
                    $file_size = file_exists( $file_path ) ? size_format( filesize( $file_path ) ) : '';
                    ?>
                    <?php if ( isset( $meta[ 'width' ], $meta[ 'height' ] ) ) { ?>
                        <p><strong><?php echo esc_html__( 'Dimensions', 'dev-debug-tools' ); ?></strong>: <?php echo intval( $meta[ 'width' ] ) . ' × ' . intval( $meta[ 'height' ] ) . ' px'; ?></p>
                    <?php } ?>
                    <?php if ( $file_size !== '' ) { ?>
                        <p><strong><?php echo esc_html__( 'File Size', 'dev-debug-tools' ); ?></strong>: <?php echo esc_html( $file_size ); ?></p>
                    <?php } ?>
                    <p><strong><?php echo esc_html__( 'Alt Text', 'dev-debug-tools' ); ?></strong>: <?php echo $alt !== '' ? esc_html( '"' . $alt . '"' ) : '<em>' . esc_html__( 'None', 'dev-debug-tools' ) . '</em>'; ?></p>
                <?php } ?>
            </div>
        </div>
        <?php
    } // End render_general_type()


    /**
     * Render an object type
     *
     * @param string $subsection The current subsection.
     * @param object $object The object to look up metadata for.
     * @param array
     */
    private static function render_object_type( $subsection, $object, $meta_viewer_customizations = [] ) {
        self::render_type_heading( __FUNCTION__ );

        $object_data = (array) ( $subsection === 'user' ? $object->data : $object );
        $object_data = self::filter_and_highlight_metadata( $object_data, $meta_viewer_customizations );

        // Check if the object is a post and is an auto-draft
        $is_auto_draft = false;
        if ( $subsection === 'post' && isset( $object->post_status ) && $object->post_status === 'auto-draft' ) {
            $is_auto_draft = true;
        }

        ?>
        <table class="ddtt-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Meta Key', 'dev-debug-tools' ); ?></th>
                    <th><?php esc_html_e( 'Meta Value', 'dev-debug-tools' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'dev-debug-tools' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ( $object_data as $key => $value ) {

                    $display_value = self::render_meta_value( $subsection, 'object', $object, $key, $value );

                    // Determine if button should be disabled
                    $disabled = '';
                    if ( in_array( $key, self::protected_meta_keys(), true ) || $is_auto_draft ) {
                        $disabled = ' disabled';
                    }
                    ?>
                    <tr class="ddtt-meta-key-row ddtt-meta-key-<?php echo esc_attr( $key ); ?>">
                        <td><span class="ddtt-highlight-variable"><?php echo wp_kses_post( $key ); ?></span></td>
                        <td><?php echo wp_kses_post( $display_value ); ?></td>
                        <td>
                            <button class="ddtt-button ddtt-meta-action-button" data-action="edit" data-key="<?php echo esc_attr( $key ); ?>"<?php echo esc_attr( $disabled ); ?>><?php esc_html_e( 'Edit', 'dev-debug-tools' ); ?></button>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
        <?php
    } // End render_object_type()


    /**
     * Render custom meta type
     *
     * @param string $subsection The current subsection.
     * @param object $object The object to look up metadata for.
     * @param array $meta_viewer_customizations Customizations for the meta viewer.
     */
    private static function render_custom_type( $subsection, $object, $meta_viewer_customizations = [] ) {
        $id = self::get_object_id( $subsection, $object );

        if ( $subsection === 'user' ) {
            $all_meta = get_user_meta( $id );
        } elseif ( $subsection === 'post' ) {
            $all_meta = get_post_meta( $id );
        } elseif ( $subsection === 'term' ) {
            $all_meta = get_term_meta( $id );
        } elseif ( $subsection === 'comment' ) {
            $all_meta = get_comment_meta( $id );
        } elseif ( $subsection === 'media' ) {
            $all_meta = get_post_meta( $id );
        } else {
            $all_meta = [];
        }

        $custom_meta = [];
        foreach ( $all_meta as $key => $values ) {
            $custom_meta[ $key ] = $values[0];
        }

        $custom_meta = self::filter_and_highlight_metadata( array_map( function( $v ) {
            return $v ?? '';
        }, $custom_meta ), $meta_viewer_customizations );

        ksort( $custom_meta );

        $table_classes = [ 'ddtt-table' ];

        if ( $subsection === 'user' || $subsection === 'post' ) {
            $hide_transients  = isset( $meta_viewer_customizations[ 'hide_transients' ] ) ? (bool) $meta_viewer_customizations[ 'hide_transients' ] : false;
            $hide_ui_settings = isset( $meta_viewer_customizations[ 'hide_ui_settings' ] ) ? (bool) $meta_viewer_customizations[ 'hide_ui_settings' ] : false;
            $ui_keys          = self::ui_setting_keys();

            if ( $hide_transients ) {
                $table_classes[] = 'ddtt-hide-transients';
            }
            if ( $hide_ui_settings ) {
                $table_classes[] = 'ddtt-hide-ui-settings';
            }
        }

        // Check if the object is a post and is an auto-draft
        $is_auto_draft = false;
        if ( $subsection === 'post' && isset( $object->post_status ) && $object->post_status === 'auto-draft' ) {
            $is_auto_draft = true;
        }

        ?>
        <div class="ddtt-header-actions-wrapper">
            <div class="ddtt-title-addnew">
                <?php self::render_type_heading( __FUNCTION__ ); ?>
                <button id="ddtt-add-new-meta" class="ddtt-button ddtt-add-new-button"<?php echo $is_auto_draft ? ' disabled' : ''; ?>><?php esc_html_e( '+ Add New', 'dev-debug-tools' ); ?></button>
            </div>

            <?php if ( $subsection === 'user' || $subsection === 'post' ) : ?>
                <div class="ddtt-table-actions">
                    <label id="ddtt-hide-transients-label" for="ddtt-hide-transients">
                        <?php esc_html_e( 'Hide Transients', 'dev-debug-tools' ); ?>
                        <input type="checkbox" id="ddtt-hide-transients" name="ddtt_hide_transients" value="1"<?php checked( $hide_transients ); echo $is_auto_draft ? ' disabled' : ''; ?>>
                    </label>
                    <label id="ddtt-hide-ui-settings-label" for="ddtt-hide-ui-settings">
                        <?php esc_html_e( 'Hide UI Settings', 'dev-debug-tools' ); ?>
                        <input type="checkbox" id="ddtt-hide-ui-settings" name="ddtt_hide_ui_settings" value="1"<?php checked( $hide_ui_settings ); echo $is_auto_draft ? ' disabled' : ''; ?>>
                    </label>
                </div>
            <?php endif; ?>
        </div>

        <table id="ddtt-custom-meta-table" class="<?php echo esc_attr( implode( ' ', $table_classes ) ); ?>">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Meta Key', 'dev-debug-tools' ); ?></th>
                    <th><?php esc_html_e( 'Meta Value', 'dev-debug-tools' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'dev-debug-tools' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ( $custom_meta as $key => $value ) {

                    $display_value = self::render_meta_value( $subsection, 'custom', $object, $key, $value );

                    $is_ui_setting = false;
                    foreach ( $ui_keys as $pattern ) {
                        if ( fnmatch( $pattern, $key, FNM_CASEFOLD ) ) {
                            $is_ui_setting = true;
                            break;
                        }
                    }

                    $table_row_classes = [ 
                        'ddtt-meta-key-row', 
                        'ddtt-meta-key-' . esc_attr( $key ) 
                    ];

                    if ( $subsection === 'user' || $subsection === 'post' ) {
                        $is_transient = ( 0 === strpos( $key, '_transient_' ) );
                        if ( $is_transient ) {
                            $table_row_classes[] = 'transient';
                        }
                        if ( $is_ui_setting ) {
                            $table_row_classes[] = 'ui-setting';
                        }
                    }

                    // Disable action buttons for protected keys or auto-draft posts
                    $disabled = '';
                    if ( in_array( $key, self::protected_meta_keys(), true ) || $is_auto_draft ) {
                        $disabled = ' disabled';
                    }
                    ?>
                    <tr class="<?php echo esc_attr( implode( ' ', $table_row_classes ) ); ?>">
                        <td><span class="ddtt-highlight-variable"><?php echo wp_kses_post( $key ); ?></span></td>
                        <td><?php echo wp_kses_post( $display_value ); ?></td>
                        <td>
                            <button class="ddtt-button ddtt-meta-action-button" data-action="edit" data-key="<?php echo esc_attr( $key ); ?>"<?php echo esc_attr( $disabled ); ?>><?php esc_html_e( 'Edit', 'dev-debug-tools' ); ?></button>
                            <button class="ddtt-button ddtt-meta-action-button" data-action="delete" data-key="<?php echo esc_attr( $key ); ?>"<?php echo esc_attr( $disabled ); ?>><?php esc_html_e( 'Delete', 'dev-debug-tools' ); ?></button>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
        <?php
    } // End render_custom_type()


    /**
     * Render a role type
     *
     * @param string $subsection The current subsection.
     * @param object $object The object to look up metadata for.
     * @param array
     */
    private static function render_roles_type( $subsection, $object, $meta_viewer_customizations = [] ) {
        self::render_type_heading( __FUNCTION__ );

        $all_roles = get_editable_roles();
        $user_roles = (array) $object->roles;

        $roles_data = [];
        foreach ( $all_roles as $role_slug => $role_info ) {
            $is_active = in_array( $role_slug, $user_roles, true );
            $roles_data[ $role_slug ] = [
                'label'  => $role_info[ 'name' ],
                'active' => $is_active,
            ];
        }

        // Sort roles: active first, then alphabetically by label
        uasort( $roles_data, function( $a, $b ) {
            if ( $a[ 'active' ] === $b[ 'active' ] ) {
                return strcasecmp( $a[ 'label' ], $b[ 'label' ] );
            }
            return $a[ 'active' ] ? -1 : 1;
        } );

        $filtered_roles = wp_list_pluck( $roles_data, 'label' );
        ?>
        <table class="ddtt-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Role', 'dev-debug-tools' ); ?></th>
                    <th><?php esc_html_e( 'Active', 'dev-debug-tools' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'dev-debug-tools' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ( $roles_data as $role_slug => $role_data ) {
                    $role_label_highlighted = isset( $filtered_roles[ $role_slug ] ) ? $filtered_roles[ $role_slug ] : $role_data[ 'label' ];
                    $active_text = $role_data[ 'active' ] ? __( 'Yes', 'dev-debug-tools' ) : __( 'No', 'dev-debug-tools' );
                    $row_classes = 'ddtt-meta-key-row' . ( $role_data[ 'active' ] ? ' active' : ' inactive' );
                    $btn_action = $role_data[ 'active' ] ? 'remove' : 'add';
                    $btn_label = $role_data[ 'active' ] ? __( 'Remove', 'dev-debug-tools' ) : __( 'Add', 'dev-debug-tools' );
                    $disable_remove = $role_data[ 'active' ] && count( $user_roles ) === 1 ? ' disabled' : '';
                    ?>
                    <tr class="<?php echo esc_attr( $row_classes ); ?>">
                        <td>
                            <span class="ddtt-highlight-variable">
                                <?php echo wp_kses_post( $role_label_highlighted . ' (' . esc_html( $role_slug ) . ')' ); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html( $active_text ); ?></td>
                        <td>
                            <button class="ddtt-button ddtt-meta-action-button"<?php echo esc_attr( $disable_remove ); ?> data-action="<?php echo esc_attr( $btn_action ); ?>" data-key="<?php echo esc_attr( $role_slug ); ?>"><?php echo esc_html( $btn_label ); ?></button>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
        <?php
    } // End render_roles_type()


    /**
     * Render a capabilities type
     *
     * @param string $subsection The current subsection.
     * @param object $object The object to look up metadata for.
     * @param array  $meta_viewer_customizations Customization options for filtering and highlighting.
     */
    private static function render_capabilities_type( $subsection, $object, $meta_viewer_customizations = [] ) {
        self::render_type_heading( __FUNCTION__ );

        global $wp_roles;

        $all_caps = [];
        if ( isset( $wp_roles->roles ) && is_array( $wp_roles->roles ) ) {
            foreach ( $wp_roles->roles as $role ) {
                if ( isset( $role[ 'capabilities' ] ) && is_array( $role[ 'capabilities' ] ) ) {
                    $all_caps = array_merge( $all_caps, array_keys( $role[ 'capabilities' ] ) );
                }
            }
        }

        $user_caps = ( isset( $object->allcaps ) && is_array( $object->allcaps ) ) ? $object->allcaps : [];
        $cap_keys  = array_unique( array_merge( $all_caps, array_keys( $user_caps ) ) );

        $capabilities = [];
        foreach ( $cap_keys as $cap ) {
            $capabilities[ $cap ] = isset( $user_caps[ $cap ] ) ? (bool) $user_caps[ $cap ] : false;
        }

        uksort( $capabilities, function( $cap_a, $cap_b ) use ( $capabilities ) {
            $active_a = $capabilities[ $cap_a ];
            $active_b = $capabilities[ $cap_b ];
            if ( $active_a && ! $active_b ) { return -1; }
            if ( ! $active_a && $active_b ) { return 1; }
            return strcasecmp( $cap_a, $cap_b );
        } );
        ?>
        <table class="ddtt-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Capability', 'dev-debug-tools' ); ?></th>
                    <th><?php esc_html_e( 'Active', 'dev-debug-tools' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'dev-debug-tools' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $capabilities as $cap_slug => $active ) : ?>
                    <?php $row_classes = 'ddtt-meta-key-row' . ( $active ? ' active' : ' inactive' ); ?>
                    <tr class="<?php echo esc_attr( $row_classes ); ?>">
                        <td>
                            <span class="ddtt-highlight-variable">
                                <?php echo esc_html( $cap_slug ); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html( $active ? __( 'Yes', 'dev-debug-tools' ) : __( 'No', 'dev-debug-tools' ) ); ?></td>
                        <td>
                            <?php
                            $btn_action = $active ? 'remove' : 'add';
                            $btn_label  = $active ? __( 'Remove', 'dev-debug-tools' ) : __( 'Add', 'dev-debug-tools' );
                            ?>
                            <button class="ddtt-button ddtt-meta-action-button"
                                    data-action="<?php echo esc_attr( $btn_action ); ?>"
                                    data-key="<?php echo esc_attr( $cap_slug ); ?>">
                                <?php echo esc_html( $btn_label ); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    } // End render_capabilities_type()


    /**
     * Render a taxonomies type
     *
     * @param string $subsection The current subsection.
     * @param object $object The object to look up metadata for (post object expected).
     * @param array  $meta_viewer_customizations Customization options for filtering and highlighting.
     */
    private static function render_taxonomies_type( $subsection, $object, $meta_viewer_customizations = [] ) {
        self::render_type_heading( __FUNCTION__ );

        $post_type = get_post_type( $object );
        $taxonomies = $post_type 
            ? get_object_taxonomies( $post_type, 'objects' ) 
            : [];

        $taxonomy_labels = [];
        foreach ( $taxonomies as $slug => $taxonomy_obj ) {
            $taxonomy_labels[ $slug ] = $taxonomy_obj->label . ' (' . $slug . ')';
        }

        $filtered_taxonomies = [];

        foreach ( $taxonomies as $taxonomy_slug => $taxonomy_obj ) {
            $terms = get_the_terms( $object->ID, $taxonomy_slug );

            if ( is_wp_error( $terms ) || empty( $terms ) ) {
                $filtered_taxonomies[ $taxonomy_slug ] = '';
                continue;
            }

            $term_strings = [];
            foreach ( $terms as $term ) {
                $term_strings[] = sprintf(
                    '%s (%s)',
                    esc_html( $term->name ),
                    esc_html( $term->slug )
                );
            }

            $filtered_taxonomies[ $taxonomy_slug ] = implode( '<br>', $term_strings );
        }

        // Sort filtered_taxonomies alphabetically by taxonomy slug
        ksort( $filtered_taxonomies );
        ?>
        <table class="ddtt-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Taxonomy', 'dev-debug-tools' ); ?></th>
                    <th><?php esc_html_e( 'Terms', 'dev-debug-tools' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'dev-debug-tools' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $filtered_taxonomies as $taxonomy_slug => $term_list ) : 
                    $taxonomy_label = isset( $taxonomy_labels[ $taxonomy_slug ] ) ? $taxonomy_labels[ $taxonomy_slug ] : $taxonomy_slug;
                    ?>
                    <tr class="ddtt-meta-key-row ddtt-meta-key-<?php echo esc_attr( $taxonomy_slug ); ?>">
                        <td><span class="ddtt-highlight-variable"><?php echo wp_kses_post( $taxonomy_label ); ?></span></td>
                        <td><?php echo wp_kses_post( $term_list ); ?></td>
                        <td>
                            <button class="ddtt-button ddtt-meta-action-button" 
                                    data-action="update" 
                                    data-key="<?php echo esc_attr( $taxonomy_slug ); ?>">
                                <?php esc_html_e( 'Update Terms', 'dev-debug-tools' ); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    } // End render_taxonomies_type()


    /**
     * Get the object based on type and ID.
     *
     * @param string $type The type of object (user, post, term, comment, media).
     * @param int $id The ID of the object.
     * @return object|false
     */
    private static function get_object( $type, $id ) {
        if ( $type === 'user' ) {
            return get_user_by( 'id', $id );
        } elseif ( $type === 'post' ) {
            return get_post( $id );
        } elseif ( $type === 'term' ) {
            return get_term( $id );
        } elseif ( $type === 'comment' ) {
            return get_comment( $id );
        } elseif ( $type === 'media' ) {
            $post = get_post( $id );
            if ( $post !== null && get_post_type( $id ) === 'attachment' ) {
                return $post;
            }
            return false;
        }
        return false;
    } // End get_object()


    /**
     * Get the object ID based on type and object.
     *
     * @param string $type The type of object (user, post, term, comment, media).
     * @param object $object The object to extract the ID from.
     * @return int|null
     */
    private static function get_object_id( $type, $object ) {
        if ( $type === 'user' ) {
            $id = $object->ID;
        } elseif ( $type === 'post' || $type === 'media' ) {
            $id = $object->ID;
        } elseif ( $type === 'term' ) {
            $id = $object->term_id;
        } elseif ( $type === 'comment' ) {
            $id = $object->comment_ID;
        } else {
            $id = null;
        }
        return $id;
    } // End get_object_id()


    /**
     * Render metadata for the specified subsection and ID.
     *
     * @param string $subsection The current subsection.
     * @param int|false $id The ID to look up metadata for.
     * @param array
     */
    public static function render_metadata( $subsection, $id, $meta_viewer_customizations = [] ) {
        if ( $id === false ) {
            if ( $subsection === 'user' ) {
                $id = get_current_user_id();
            } else {
                echo '<br><br><p><strong></strong>' . esc_html__( 'Please search for an ID.', 'dev-debug-tools' ) . '</strong></p>';
                return;
            }
        }

        // dpr( $meta_viewer_customizations ); // Debugging purpose, remove in production

        $object = self::get_object( $subsection, $id );
        if ( ! $object ) {
            echo '<br><br><p><strong>' . esc_html__( 'The object with the specified ID does not exist.', 'dev-debug-tools' ) . '</strong></p>';
            return;
        }

        $types = self::types();

        foreach ( $types as $type_key => $type_data ) {
            // Skip if subsection is not allowed for this type
            $sections = $type_data[ 'sections' ];
            if ( $sections !== 'all' && ! in_array( $subsection, $sections, true ) ) {
                continue;
            }

            // Skip if this type is disabled in customizations
            $enabled = ! isset( $meta_viewer_customizations[ 'types' ][ $type_key ] ) || filter_var( $meta_viewer_customizations[ 'types' ][ $type_key ], FILTER_VALIDATE_BOOLEAN );
            if ( ! $enabled ) {
                continue;
            }
            ?>
            <section id="ddtt-metadata-<?php echo esc_attr( $type_key ); ?>-section" data-subsection="<?php echo esc_attr( $subsection ); ?>" data-object-id="<?php echo esc_attr( $id ); ?>">
                <?php
                $method = 'render_' . $type_key . '_type';
                if ( method_exists( self::class, $method ) ) {
                    self::$method( $subsection, $object, $meta_viewer_customizations );
                } else {
                    printf(
                        /* translators: %s: meta type label */
                        esc_html__( '%s method not found.', 'dev-debug-tools' ),
                        esc_html( $type_data[ 'label' ] )
                    );
                }
                ?>
            </section>
            <?php
        }
    } // End render_metadata()


    /**
     * Enqueue assets
     *
     * @param string $hook The current admin page hook.
     * @return void
     */
    public function enqueue_assets( $hook ) : void {
        if ( ! AdminMenu::is_current_screen( $hook, 'tools', 'metadata' ) ) {
            return;
        }

        $subsection = $this->get_current_subsection();

        if ( $subsection === 'post' ) {
            wp_enqueue_script( 'tags-box' );
            wp_enqueue_script( 'post' );
            wp_enqueue_style( 'wp-admin' );
        }
        
        wp_localize_script( 'ddtt-tool-metadata', 'ddtt_metadata', [
            'subsection' => $subsection,
            'nonce'      => wp_create_nonce( $this->nonce ),            
            'i18n'       => [
                'loading'              => __( 'Please wait. Loading metadata', 'dev-debug-tools' ),
                'enterKey'             => __( 'Enter key', 'dev-debug-tools' ),
                'enterValue'           => __( 'Enter value', 'dev-debug-tools' ),
                'save'                 => __( 'Save', 'dev-debug-tools' ),
                'cancel'               => __( 'Cancel', 'dev-debug-tools' ),
                'edit'                 => __( 'Edit', 'dev-debug-tools' ),
                'delete'               => __( 'Delete', 'dev-debug-tools' ),
                'errorSaving'          => __( 'Error saving meta value.', 'dev-debug-tools' ),
                'errorDeleting'        => __( 'Error deleting meta key.', 'dev-debug-tools' ),
                'confirm'              => __( 'Are you sure you want to delete this meta key?', 'dev-debug-tools' ),
                'enterPassword'        => __( 'Enter a new password or leave blank to keep the current password', 'dev-debug-tools' ),
                'invalidEmail'         => __( 'Invalid email address.', 'dev-debug-tools' ),
                'cannotRemoveLastRole' => __( 'Cannot remove the last role.', 'dev-debug-tools' ),
                'yes'                  => __( 'Yes', 'dev-debug-tools' ),
                'no'                   => __( 'No', 'dev-debug-tools' ),
                'add'                  => __( 'Add', 'dev-debug-tools' ),
                'remove'               => __( 'Remove', 'dev-debug-tools' ),
                'updateTerms'          => __( 'Update Terms', 'dev-debug-tools' ),
                'confirmReset'         => __( 'Are you sure you want to reset all custom metadata for this object? This cannot be undone.', 'dev-debug-tools' ) 
            ],
        ] );
    } // End enqueue_assets()


    /**
     * Handle AJAX request to update the table actions.
     *
     * @return void
     */
    public function ajax_get_metadata() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        $stored_customizations = get_option( 'ddtt_metadata_viewer_customizations', [] );

        $id = isset( $_POST[ 'id' ] ) ? absint( wp_unslash( $_POST[ 'id' ] ) ) : false;
        if ( ! $id ) {
            wp_send_json_error( 'invalid_id' );
        }

        $types_raw = isset( $_POST[ 'types' ] ) && is_array( $_POST[ 'types' ] ) ? wp_unslash( $_POST[ 'types' ] ) : []; // phpcs:ignore 
        $types_sanitized = [];
        foreach ( $types_raw as $key => $value ) {
            $sanitized_key = sanitize_key( $key );
            $types_sanitized[ $sanitized_key ] = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
        }

        $customizations = [
            'types' => ! empty( $types_sanitized )
                ? $types_sanitized
                : ( isset( $stored_customizations[ 'types' ] ) ? $stored_customizations[ 'types' ] : [] ),
            'search'   => isset( $_POST[ 'search' ] )
                ? sanitize_text_field( wp_unslash( $_POST[ 'search' ] ) )
                : ( isset( $stored_customizations[ 'search' ] ) ? $stored_customizations[ 'search' ] : '' ),
            'filter'   => isset( $_POST[ 'filter' ] )
                ? sanitize_text_field( wp_unslash( $_POST[ 'filter' ] ) )
                : ( isset( $stored_customizations[ 'filter' ] ) ? $stored_customizations[ 'filter' ] : '' ),
            'hide_transients' => isset( $_POST[ 'hide_transients' ] )
                ? absint( wp_unslash( $_POST[ 'hide_transients' ] ) )
                : ( isset( $stored_customizations[ 'hide_transients' ] ) ? $stored_customizations[ 'hide_transients' ] : 0 ),
            'hide_ui_settings' => isset( $_POST[ 'hide_ui_settings' ] )
                ? absint( wp_unslash( $_POST[ 'hide_ui_settings' ] ) )
                : ( isset( $stored_customizations[ 'hide_ui_settings' ] ) ? $stored_customizations[ 'hide_ui_settings' ] : 0 ),
        ];

        update_option( 'ddtt_metadata_viewer_customizations', $customizations );

        $subsection = isset( $_POST[ 'subsection' ] )
            ? sanitize_key( wp_unslash( $_POST[ 'subsection' ] ) )
            : 'user';

        if ( method_exists( $this, 'render_metadata' ) ) {
            ob_start();
            self::render_metadata( $subsection, $id, $customizations );
            echo wp_kses_post( ob_get_clean() );
        } else {
            echo esc_html__( 'Metadata section not found.', 'dev-debug-tools' );
        }

        wp_die();
    } // End ajax_get_metadata()


    /**
     * Handle AJAX request to update the table actions.
     *
     * @return void
     */
    public function ajax_table_actions() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        $hide_transients = isset( $_POST[ 'hide_transients' ] ) ? absint( wp_unslash( $_POST[ 'hide_transients' ] ) ) : 0;
        $hide_ui_settings = isset( $_POST[ 'hide_ui_settings' ] ) ? absint( wp_unslash( $_POST[ 'hide_ui_settings' ] ) ) : 0;

        $customizations = get_option( 'ddtt_metadata_viewer_customizations', [] );
        $customizations[ 'hide_transients' ] = $hide_transients;
        $customizations[ 'hide_ui_settings' ] = $hide_ui_settings;
        update_option( 'ddtt_metadata_viewer_customizations', $customizations );

        wp_send_json_success();
    } // End ajax_table_actions()


    /**
     * Get a metadata value.
     *
     * @param string $subsection
     * @param int    $object_id
     * @param string $key
     * @return mixed
     */
    private function get_meta_value( $subsection, $object_id, $key ) {
        switch ( $subsection ) {
            case 'user':
                return get_user_meta( $object_id, $key, true );
            case 'post':
            case 'media':
                return get_post_meta( $object_id, $key, true );
            case 'term':
                return get_term_meta( $object_id, $key, true );
            case 'comment':
                return get_comment_meta( $object_id, $key, true );
            default:
                return null;
        }
    } // End get_meta_value()


    /**
     * Update a metadata value, preserving arrays/objects.
     *
     * @param string $subsection
     * @param int    $object_id
     * @param string $key
     * @param mixed  $value
     * @return bool
     */
    private function update_meta_value( $subsection, $object_id, $key, $value ) {
        // Handle password separately
        if ( $subsection === 'user' && $key === 'user_pass' ) {
            if ( $value ) {
                wp_set_password( $value, $object_id );
                return true;
            }
            return false;
        }

        // If value is a serialized string, try to unserialize it
        if ( is_string( $value ) ) {
            $maybe_unserialized = maybe_unserialize( $value );
            if ( is_array( $maybe_unserialized ) || is_object( $maybe_unserialized ) ) {
                $value_to_store = $maybe_unserialized;
            } else {
                $value_to_store = $value;
            }
        } else {
            $value_to_store = $value;
        }

        switch ( $subsection ) {
            case 'user':
                // Check if it's a core user field
                $core_user_fields = [
                    'user_login', 'user_email', 'user_nicename', 'display_name',
                    'user_url', 'user_registered', 'user_status', 'user_activation_key'
                ];
                if ( in_array( $key, $core_user_fields, true ) ) {
                    $userdata = [ 'ID' => $object_id, $key => $value_to_store ];
                    return wp_update_user( $userdata );
                }
                return update_user_meta( $object_id, $key, $value_to_store );

            case 'post':
            case 'media':
                $core_post_fields = [
                    'post_title','post_content','post_excerpt','post_status',
                    'post_type','post_name','post_parent','menu_order',
                    'post_password','post_date','post_modified',
                    'comment_status','ping_status','to_ping','pinged','guid'
                ];

                if ( in_array( $key, $core_post_fields, true ) ) {
                    $postarr = [ 'ID' => $object_id ];

                    if ( $key === 'post_date' ) {
                        $postarr[ 'post_date' ]     = $value_to_store;
                        $postarr[ 'post_date_gmt' ] = get_gmt_from_date( $value_to_store );
                    } elseif ( $key === 'post_modified' ) {
                        $postarr[ 'post_modified' ]     = $value_to_store;
                        $postarr[ 'post_modified_gmt' ] = get_gmt_from_date( $value_to_store );
                    } else {
                        $postarr[ $key ] = $value_to_store;
                    }

                    $updated_id = wp_update_post( $postarr, true );

                    // Check result
                    if ( is_wp_error( $updated_id ) || $updated_id === 0 ) {
                        return false;
                    }

                    return true;
                }

                return update_post_meta( $object_id, $key, $value_to_store );

            case 'term':
                // Core term fields
                $core_term_fields = [ 'name', 'slug', 'term_group' ];
                if ( in_array( $key, $core_term_fields, true ) ) {
                    $args = [ $key => $value_to_store ];
                    return wp_update_term( $object_id, '', $args );
                }
                return update_term_meta( $object_id, $key, $value_to_store );

            case 'comment':
                // Core comment fields
                $core_comment_fields = [
                    'comment_post_ID', 'comment_author', 'comment_author_email',
                    'comment_author_url', 'comment_author_IP', 'comment_date',
                    'comment_date_gmt', 'comment_content', 'comment_karma',
                    'comment_approved', 'comment_agent', 'comment_type',
                    'comment_parent', 'user_id'
                ];
                if ( in_array( $key, $core_comment_fields, true ) ) {
                    $commentarr = [ 'comment_ID' => $object_id, $key => $value_to_store ];
                    return wp_update_comment( $commentarr );
                }
                return update_comment_meta( $object_id, $key, $value_to_store );

            default:
                return false;
        }
    } // End update_meta_value()


    /**
     * Delete a metadata value.
     *
     * @param string $subsection
     * @param int    $object_id
     * @param string $key
     * @return bool True on success, false on failure.
     */
    private function delete_meta_value( $subsection, $object_id, $key ) {
        switch ( $subsection ) {
            case 'user':
                return delete_user_meta( $object_id, $key );
            case 'post':
            case 'media':
                return delete_post_meta( $object_id, $key );
            case 'term':
                return delete_term_meta( $object_id, $key );
            case 'comment':
                return delete_comment_meta( $object_id, $key );
            default:
                return false;
        }
    } // End delete_meta_value()


    /**
     * Handle AJAX request to update a metadata value.
     *
     * @return void
     */
    public function ajax_update_meta_value() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        $subsection = isset( $_POST[ 'subsection' ] ) ? sanitize_key( $_POST[ 'subsection' ] ) : '';
        $object_id  = isset( $_POST[ 'object_id' ] ) ? absint( $_POST[ 'object_id' ] ) : 0;
        $key        = isset( $_POST[ 'key' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'key' ] ) ) : '';
        $value      = isset( $_POST[ 'value' ] ) ? wp_unslash( $_POST[ 'value' ] ) : ''; // phpcs:ignore 

        if ( ! $subsection || ! $object_id || ! $key ) {
            wp_send_json_error();
        }

        $updated = $this->update_meta_value( $subsection, $object_id, $key, $value );

        if ( false === $updated && $this->get_meta_value( $subsection, $object_id, $key ) !== $value ) {
            wp_send_json_error();
        }

        // Return the rendered value
        $object = $this->get_object( $subsection, $object_id );
        $rendered_value = $this->render_meta_value( $subsection, 'custom', $object, $key, $value );

        wp_send_json_success( $rendered_value );
    } // End ajax_update_meta_value()


    /**
     * Handle AJAX request to delete a metadata key.
     *
     * @return void
     */
    public function ajax_delete_meta_key() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        $subsection = isset( $_POST[ 'subsection' ] ) ? sanitize_key( $_POST[ 'subsection' ] ) : '';
        $object_id  = isset( $_POST[ 'object_id' ] ) ? absint( $_POST[ 'object_id' ] ) : 0;
        $key        = isset( $_POST[ 'key' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'key' ] ) ) : '';

        if ( ! $subsection || ! $object_id || ! $key ) {
            wp_send_json_error();
        }

        $deleted = $this->delete_meta_value( $subsection, $object_id, $key );

        if ( false === $deleted ) {
            wp_send_json_error();
        }

        wp_send_json_success();
    } // End ajax_delete_meta_key()


    /**
     * AJAX handler to add or remove a user role.
     *
     * @return void
     */
    public function ajax_update_user_role() {
        check_ajax_referer( $this->nonce, '_wpnonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        $user_id = isset( $_POST[ 'object_id' ] ) ? absint( $_POST[ 'object_id' ] ) : 0;
        $role    = isset( $_POST[ 'role' ] ) ? sanitize_key( wp_unslash( $_POST[ 'role' ] ) ) : '';
        $toggle  = isset( $_POST[ 'toggle' ] ) ? sanitize_key( wp_unslash( $_POST[ 'toggle' ] ) ) : '';

        if ( ! $user_id || ! $role || ! in_array( $toggle, [ 'add', 'remove' ], true ) ) {
            wp_send_json_error();
        }

        $user = get_userdata( $user_id );
        if ( ! $user ) {
            wp_send_json_error();
        }

        $current_roles = (array) $user->roles;

        if ( $toggle === 'add' && ! in_array( $role, $current_roles, true ) ) {
            $user->add_role( $role );
        } elseif ( $toggle === 'remove' && in_array( $role, $current_roles, true ) ) {
            // Prevent removing last role
            if ( count( $current_roles ) <= 1 ) {
                wp_send_json_error( 'cannot_remove_last_role' );
            }
            $user->remove_role( $role );
        }

        // Refresh capabilities after role update
        $user = get_userdata( $user_id );
        $capabilities = isset( $user->allcaps ) ? $user->allcaps : [];

        wp_send_json_success( [ 'capabilities' => $capabilities ] );
    } // End ajax_update_user_role()


    /**
     * Update user capabilities via AJAX.
     */
    public function ajax_update_user_capability() {
        check_ajax_referer( $this->nonce, '_wpnonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        $object_id = isset( $_POST[ 'object_id' ] ) ? absint( $_POST[ 'object_id' ] ) : 0;
        $capability = isset( $_POST[ 'capability' ] ) ? sanitize_key( wp_unslash( $_POST[ 'capability' ] ) ) : '';
        $toggle = isset( $_POST[ 'toggle' ] ) ? sanitize_key( wp_unslash( $_POST[ 'toggle' ] ) ) : '';

        if ( ! $object_id || ! $capability || ! in_array( $toggle, [ 'add', 'remove' ], true ) ) {
            wp_send_json_error();
        }

        $user = get_userdata( $object_id );
        if ( ! $user ) {
            wp_send_json_error();
        }

        if ( $toggle === 'add' ) {
            $user->add_cap( $capability );
        } else {
            $user->remove_cap( $capability );
        }

        wp_send_json_success();
    } // End ajax_update_user_capability()


    /**
     * AJAX handler to get the taxonomy terms editor.
     *
     * @return void
     */
    public function ajax_get_tax_terms_editor() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        $taxonomy = isset( $_POST[ 'taxonomy' ] ) ? sanitize_key( $_POST[ 'taxonomy' ] ) : '';
        if ( ! $taxonomy ) {
            wp_send_json_error( 'invalid_taxonomy' );
        }

        $tax_obj  = get_taxonomy( $taxonomy );
        if ( ! $tax_obj ) {
            wp_send_json_error( 'invalid_taxonomy' );
        }

        $post_id  = isset( $_POST[ 'object_id' ] ) ? absint( $_POST[ 'object_id' ] ) : 0;
        if ( ! $post_id ) {
            wp_send_json_error( 'invalid_post_id' );
        }

        ob_start();
        
        if ( $tax_obj->hierarchical ) {
            wp_terms_checklist( $post_id, [
                'taxonomy'      => $taxonomy,
                'checked_ontop' => false
            ] );

        } else {
            $terms_list = wp_get_object_terms( $post_id, $taxonomy, [ 'fields' => 'names' ] );
            $terms_str  = is_wp_error( $terms_list ) ? '' : esc_attr( join( ', ', $terms_list ) );

            echo '<div class="ddtt-term-editor">
                <div class="tagsdiv" id="' . esc_attr( $taxonomy ) . '">
                    <div class="jaxtag">
                        <div class="nojs-tags hide-if-js">
                            <p>' . esc_html( $tax_obj->labels->add_or_remove_items ) . '</p>
                            <textarea name="tax_input[' . esc_attr( $taxonomy ) . ']" rows="3" cols="20" class="the-tags">' . esc_html( $terms_str ) . '</textarea>
                        </div>
                        <div class="ajaxtag hide-if-no-js">
                            <label class="screen-reader-text" for="new-tag-' . esc_attr( $taxonomy ) . '">' . esc_html( $tax_obj->labels->add_new_item ) . '</label>
                            <input type="text" id="new-tag-' . esc_attr( $taxonomy ) . '" name="newtag[' . esc_attr( $taxonomy ) . ']" class="newtag form-input-tip" size="16" autocomplete="off" />
                            <input type="button" class="button tagadd" value="' . esc_attr__( 'Add', 'dev-debug-tools' ) . '" />
                        </div>
                    </div>
                    <p class="howto">' . esc_html( $tax_obj->labels->separate_items_with_commas ) . '</p>
                    <ul class="tagchecklist"></ul>
                </div>
            </div>';
        }
        
        wp_die( wp_kses_post( ob_get_clean() ) );
    } // End ajax_get_tax_terms_editor()


    /**
     * AJAX handler to update taxonomy terms.
     *
     * @return void
     */
    public function ajax_update_tax_terms() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        $taxonomy  = isset( $_POST[ 'taxonomy' ] ) ? sanitize_key( $_POST[ 'taxonomy' ] ) : '';
        $post_id   = isset( $_POST[ 'object_id' ] ) ? absint( $_POST[ 'object_id' ] ) : 0;
        $terms     = isset( $_POST[ 'terms' ] ) ? $_POST[ 'terms' ] : []; // phpcs:ignore

        if ( ! $taxonomy || ! $post_id ) {
            wp_send_json_error( 'invalid_data' );
        }

        $tax_obj = get_taxonomy( $taxonomy );
        if ( ! $tax_obj ) {
            wp_send_json_error( 'invalid_taxonomy' );
        }

        if ( $tax_obj->hierarchical ) {
            // Hierarchical: expect array of term IDs
            $term_ids = array_map( 'absint', (array) $terms );
            $result = wp_set_post_terms( $post_id, $term_ids, $taxonomy, false );
        } else {
            // Non-hierarchical: comma-separated string of term names
            if ( is_string( $terms ) ) {
                $terms = explode( ',', $terms );
            }
            $terms = array_map( 'sanitize_text_field', array_map( 'trim', $terms ) );
            $result = wp_set_post_terms( $post_id, $terms, $taxonomy, false );
        }

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        // Return updated terms as display text
        $updated_terms = wp_get_post_terms( $post_id, $taxonomy );
        $term_strings  = [];

        if ( ! is_wp_error( $updated_terms ) && ! empty( $updated_terms ) ) {
            foreach ( $updated_terms as $term ) {
                $term_strings[] = sprintf(
                    '%s (%s)',
                    esc_html( $term->name ),
                    esc_html( $term->slug )
                );
            }
        }

        $display = implode( '<br>', $term_strings );

        wp_send_json_success( $display );
    } // End ajax_update_tax_terms()


    /**
     * Validate imported object data for metadata import.
     *
     * @param array $import The decoded import array.
     * @return array|WP_Error Returns sanitized import array or WP_Error on failure.
     */
    private function validate_import_data( $import ) {
        if ( ! is_array( $import ) ) {
            return new \WP_Error( 'invalid_format', __( 'Import data is not an array.', 'dev-debug-tools' ) );
        }

        if ( empty( $import[ 'object' ] ) || ! is_array( $import[ 'object' ] ) ) {
            return new \WP_Error( 'missing_object', __( 'Missing or invalid object data.', 'dev-debug-tools' ) );
        }

        $object = $import[ 'object' ];

        // Determine object type
        $object_type = isset( $object[ 'post_type' ] ) ? 'post' : ( isset( $object[ 'user_email' ] ) ? 'user' : '' );
        if ( ! $object_type ) {
            return new \WP_Error( 'unknown_type', __( 'Unknown object type.', 'dev-debug-tools' ) );
        }

        // Validate required fields for post
        if ( $object_type === 'post' ) {
            if ( empty( $object[ 'post_title' ] ) || empty( $object[ 'post_type' ] ) ) {
                return new \WP_Error( 'missing_post_fields', __( 'Post object is missing required fields.', 'dev-debug-tools' ) );
            }
        }

        // Validate required fields for user
        if ( $object_type === 'user' ) {
            if ( empty( $object[ 'user_email' ] ) ) {
                return new \WP_Error( 'missing_user_fields', __( 'User object is missing required fields.', 'dev-debug-tools' ) );
            }
        }

        return [
            'object_type' => $object_type,
            'object'      => $object,
            'meta'        => isset( $import[ 'custom_meta' ] ) ? $import[ 'custom_meta' ] : [],
            'taxonomies'  => isset( $import[ 'taxonomies' ] ) ? $import[ 'taxonomies' ] : [],
            'roles'       => isset( $import[ 'roles' ] ) ? $import[ 'roles' ] : [],
            'capabilities'=> isset( $import[ 'capabilities' ] ) ? $import[ 'capabilities' ] : [],
        ];
    } // End validate_import_data()


    /**
     * AJAX handler for metadata import.
     *
     * @return void
     */
    public function ajax_metadata_import() {
        check_ajax_referer( 'ddtt_save_settings_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        $file_data = isset( $_POST[ 'jsonData' ] ) ? wp_unslash( $_POST[ 'jsonData' ] ) : null; // phpcs:ignore

        if ( ! $file_data ) {
            wp_send_json_error( 'No file data.' );
        }

        $import = json_decode( $file_data, true );
        if ( ! $import || empty( $import[ 'object' ] ) ) {
            wp_send_json_error( 'Invalid JSON data.' );
        }

        // Validate import data
        $validated = $this->validate_import_data( $import );
        if ( is_wp_error( $validated ) ) {
            wp_send_json_error( $validated->get_error_message() );
        }

        $object_type = $validated[ 'object_type' ];
        $object      = $validated[ 'object' ];
        $meta        = $validated[ 'meta' ];
        $taxonomies  = $validated[ 'taxonomies' ];
        $roles       = $validated[ 'roles' ];
        $capabilities= $validated[ 'capabilities' ];
        $object_id   = isset( $object[ 'ID' ] ) ? absint( $object[ 'ID' ] ) : 0;

        // Post Object
        if ( $object_type === 'post' ) {
            $existing = get_post( $object_id );
            if ( $existing ) {
                wp_update_post( $object );
            } else {
                $object_id = wp_insert_post( $object );
            }

            // Update meta
            foreach ( $meta as $key => $values ) {
                foreach ( (array) $values as $value ) {
                    update_post_meta( $object_id, $key, $value );
                }
            }

            // Update taxonomies
            foreach ( $taxonomies as $taxonomy => $terms ) {
                wp_set_object_terms( $object_id, $terms, $taxonomy );
            }

        // User Object
        } elseif ( $object_type === 'user' ) {
            $existing = get_user_by( 'id', $object_id );
            if ( $existing ) {
                wp_update_user( $object );
            } else {
                $object_id = wp_insert_user( $object );
            }

            // Update meta
            foreach ( $meta as $key => $values ) {
                foreach ( (array) $values as $value ) {
                    update_user_meta( $object_id, $key, $value );
                }
            }

            // Update roles
            if ( ! empty( $roles ) ) {
                $user = get_userdata( $object_id );
                if ( $user ) {
                    foreach ( $user->roles as $role ) {
                        $user->remove_role( $role );
                    }
                    foreach ( $roles as $role ) {
                        $user->add_role( $role );
                    }
                }
            }

            // Update capabilities
            if ( ! empty( $capabilities ) ) {
                $user = get_userdata( $object_id );
                if ( $user ) {
                    foreach ( $capabilities as $cap => $active ) {
                        if ( $active ) {
                            $user->add_cap( $cap );
                        } else {
                            $user->remove_cap( $cap );
                        }
                    }
                }
            }
        }

        $url = $object_type === 'user' ? $this->user_lookup_url( $object_id ) : $this->post_lookup_url( $object_id );

        wp_send_json_success( [ 'redirect' => $url ] );
    } // End ajax_metadata_import()


    /**
     * Prevent cloning and unserializing
     */
    public function __clone() {}
    public function __wakeup() {}

}


Metadata::instance();