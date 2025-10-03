<?php
/**
 * Admin Area
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class AdminArea {

    /**
     * The quick link icon
     *
     * @var string
     */
    public function quick_link_icon() {
        return apply_filters( 'ddtt_quick_link_icon', '&#9889;' );
    } // End quick_link_icon()


    /**
     * Constructor
     */
    public function __construct() {

        // User ID quick links
        if ( get_option( 'ddtt_ql_user_id', true ) ) {
            add_filter( 'manage_users_columns', [ $this, 'user_column' ] );
            add_action( 'manage_users_custom_column', [ $this, 'user_column_content' ], 999, 3 );
            if ( Helpers::is_dev() ) {
                add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_user_profile_edit' ] );
            }
        }

        // Post ID quick links
        if ( get_option( 'ddtt_ql_post_id', true ) ) {
            add_action( 'admin_init', function() {
                $post_types = $this->post_types();
                foreach ( $post_types as $post_type ) {
                    add_filter( "manage_{$post_type}_posts_columns", [ $this, 'post_column' ] );
                    add_action( "manage_{$post_type}_posts_custom_column", [ $this, 'post_column_content' ], 10, 2 );
                }
            } );
            if ( Helpers::is_dev() ) {
                add_action( 'post_submitbox_misc_actions', [ $this, 'post_submitbox_actions' ] ); // Classic Editor
                add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );  // Block Editor
            }
        }

        // Comment ID quick links
        if ( get_option( 'ddtt_ql_comment_id', true ) ) {
            add_filter( 'manage_edit-comments_columns', [ $this, 'comments_column' ] );
            add_action( 'manage_comments_custom_column', [ $this, 'comments_column_content' ], 999, 2 );
        }
        
        // Allow searching posts/pages by id in admin area
        if ( get_option( 'ddtt_ids_in_search', true ) ) {
            add_action( 'pre_get_posts', [ $this, 'admin_search_include_ids' ] );
        }

        // Enqueue admin area assets
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

    } // End __construct()


    /**
     * Add ID column to user admin page
     *
     * @param array $columns
     * @return array
     */
    public function user_column( $columns ) {
        $columns[ 'ddtt_user_id' ] = __( 'ID', 'dev-debug-tools' );
        $columns[ 'ddtt_user_registered' ] = __( 'Registered', 'dev-debug-tools' );
        return $columns;
    } // End user_column()


    /**
     * Add the user column content
     *
     * @param mixed $value
     * @param string $column_name
     * @param int $user_id
     * @return string
     */
    public function user_column_content( $value, $column_name, $user_id ) {
        // User ID column
        if ( $column_name == 'ddtt_user_id' ) {

            do_action( 'ddtt_admin_list_update_each_user', $user_id );

            if ( Helpers::is_dev() ) {
                return $user_id.' <a href=" ' . Metadata::user_lookup_url( $user_id ) . ' " target="_blank">' . $this->quick_link_icon() . '</a>';
            } else {
                return $user_id;
            }

        // User Registered column
        } elseif ( $column_name == 'ddtt_user_registered' ) {
            
            $user = get_userdata( $user_id );
            if ( $user && !empty( $user->user_registered ) && $user->user_registered !== '0000-00-00 00:00:00' ) {
                return esc_html( Helpers::convert_date_format( $user->user_registered ) );
            }
            return __( 'Unknown', 'dev-debug-tools' );
        }
        
        return $value;
    } // End user_column_content()


    /**
     * Enqueue assets for User Profile Edit page
     */
    public function enqueue_user_profile_edit( $hook ) {
        if ( $hook !== 'user-edit.php' ) {
            return;
        }

        $version = Bootstrap::script_version();
        $handle = 'ddtt-user-profile-edit';

        wp_enqueue_script(
            $handle,
            Bootstrap::url( 'inc/admin-area/user-profile-edit.js' ),
            [ 'jquery' ],
            $version,
            true
        );

        wp_localize_script( $handle, 'ddtt_user_profile_edit', [
            'quick_link_icon' => $this->quick_link_icon(),
            'quick_link_url'  => Metadata::user_lookup_url( isset( $_GET[ 'user_id' ] ) ? intval( $_GET[ 'user_id' ] ) : 0 ), // phpcs:ignore
            'i18n'            => [
                'debug_user' => __( 'Debug User', 'dev-debug-tools' ),
            ]
        ] );
    } // End enqueue_user_profile_edit()


    /**
     * Get the post types to add quick links to
     *
     * @return array
     */
    public function post_types() {
        $post_types = get_post_types( [], 'names' );
        $post_types = apply_filters( 'ddtt_quick_link_post_types', $post_types );
        return $post_types;
    } // End post_types()


    /**
     * Add ID column to post/page admin pages
     *
     * @param array $columns
     * @return array
     */
    public function post_column( $columns ) {
        $columns[ 'ddtt_post_id' ] = __( 'ID', 'dev-debug-tools' );
        return $columns;
    } // End user_column()


    /**
     * Add the post/page ID column content
     *
     * @param mixed $value
     * @param string $column_name
     * @param int $user_id
     * @return string
     */
    public function post_column_content( $column_name, $post_id ) {
        // Post ID column
        if ( $column_name == 'ddtt_post_id' ) {

            do_action( 'ddtt_admin_list_update_each_post', $post_id );

            if ( Helpers::is_dev() ) {
                echo esc_attr( $post_id ).' <a href="' . esc_url( Metadata::post_lookup_url( $post_id ) ) . '" target="_blank">' . wp_kses_post( $this->quick_link_icon() ) . '</a>';
            } else {
                echo esc_attr( $post_id );
            }
        }
    } // End post_column_content()


    /**
     * Add links to post submit box
     *
     * @param WP_Post $post
     */
    public function post_submitbox_actions( $post ) {
        if ( Helpers::is_dev() ) {
            ?>
            <div class="misc-pub-section misc-pub-debug">
                <label for="my_custom_post_action"><?php echo wp_kses_post( $this->quick_link_icon() ); ?> <?php esc_html_e( 'Debug:', 'dev-debug-tools' ); ?></label>
                <a href="<?php echo esc_url( Metadata::post_lookup_url( $post->ID ) ); ?>" target="_blank"><?php esc_html_e( 'Post Meta', 'dev-debug-tools' ); ?></a>
            </div>
            <?php
        }
    } // End ddtt_post_submitbox_actions()


    /**
     * Enqueue block editor assets for Gutenberg sidebar/status link.
     */
    public function enqueue_editor_assets() {
        if ( ! Helpers::is_dev() ) {
            return;
        }

        if ( ! in_array( get_post_type(), $this->post_types() ) ) {
            return;
        }

        $version = Bootstrap::script_version();
        $handle = 'ddtt-gutenberg-debug-link';

        wp_enqueue_script(
            $handle,
            Bootstrap::url( 'inc/admin-area/post-edit-box.js' ),
            [ 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-core-data', 'wp-i18n' ],
            $version,
            true
        );

        wp_localize_script( $handle, 'ddtt_post_edit_box', [
            'quick_link_icon' => $this->quick_link_icon(),
            'quick_link_url'  => Metadata::post_lookup_url( '%d' ),
            'i18n'            => [
                'debug_post_meta' => __( 'Debug Post Meta', 'dev-debug-tools' ),
            ]
        ] );
    } // End enqueue_editor_assets()


    /**
     * Add ID column to user admin page
     *
     * @param array $columns
     * @return array
     */
    public function comments_column( $columns ) {
        $columns[ 'ddtt_comment_type' ] = __( 'Type', 'dev-debug-tools' );
        $columns[ 'ddtt_comment_karma' ] = __( 'Karma', 'dev-debug-tools' );
        $columns[ 'ddtt_comment_id' ] = __( 'ID', 'dev-debug-tools' );
        return $columns;
    } // End comments_column()


    /**
     * Add the user column content
     *
     * @param string $column_name
     * @param int $comment_id
     * @return string
     */
    public function comments_column_content( $column_name, $comment_id ) {
        // Type
        if ( $column_name == 'ddtt_comment_type' ) {
            echo sanitize_key( get_comment_type( $comment_id ) );

        // Karma
        } elseif ( $column_name == 'ddtt_comment_karma' ) {
            $comment = get_comment( $comment_id );
            echo esc_attr( $comment->comment_karma );

        // ID
        } elseif ( $column_name == 'ddtt_comment_id' ) {

            do_action( 'ddtt_admin_list_update_each_comment', $comment_id );

            if ( Helpers::is_dev() ) {
                echo esc_attr( $comment_id ).' <a href="' . esc_url( Metadata::comment_lookup_url( $comment_id ) ) . '" target="_blank">' . esc_html( $this->quick_link_icon() ) . '</a>';
            } else {
                echo esc_attr( $comment_id );
            }
        }
    } // End comments_column_content()


    /**
     * Allow searching posts/pages by id in admin area
     *
     * @param WP_Query $query The WP_Query instance (passed by reference).
     */
    public function admin_search_include_ids( $query ) {
        if ( ! is_admin() && ! $query->is_main_query() && ! $query->is_search() ) {
            return;
        }

        $search_string = get_query_var( 's' );
        if ( ! filter_var( $search_string, FILTER_VALIDATE_INT ) ) {
            return;
        }

        $query->set( 'p', intval( $search_string ) );
        $query->set( 's', '' );
    } // End admin_search_include_ids()


    /**
     * Enqueue admin area assets
     */
    public function enqueue_assets( $hook ) : void {
        $version = Bootstrap::script_version();

        
        /**
         * All Admin Area
         */
        wp_enqueue_style(
            'ddtt-admin-area',
            Bootstrap::url( 'inc/admin-area/styles.css' ),
            [],
            $version
        );


        /**
         * Helpers
         */
        wp_enqueue_script(
            'ddtt-helpers',
            Bootstrap::url( 'inc/helpers/helpers.js' ),
            [ 'jquery' ],
            $version,
            true
        );

        wp_localize_script( 'ddtt-helpers', 'ddtt_helpers', [
            'test_mode'   => Bootstrap::is_test_mode(),
            'plugin_root' => Bootstrap::url(),
        ] );

        
        /**
         * Hide Plugin in Menu CSS
         */
        $hide_plugin = get_option( 'ddtt_hide_plugin', false );
        if ( $hide_plugin && Helpers::is_dev() ) {
            wp_enqueue_style(
                'ddtt-hide-plugin',
                Bootstrap::url( 'inc/admin-area/hide-plugin.css' ),
                [],
                $version
            );
        }
        

        /**
         * Plugins Page
         */
        if ( $hook === 'plugins.php' ) {
            wp_enqueue_style(
                'ddtt-plugins-page',
                Bootstrap::url( 'inc/admin-area/plugins/styles.css' ),
                [],
                $version
            );

            wp_enqueue_script(
                'ddtt-plugins-page',
                Bootstrap::url( 'inc/admin-area/plugins/scripts.js' ),
                [ 'jquery' ],
                $version,
                true
            );
        }
    } // End enqueue_assets()

}


new AdminArea();