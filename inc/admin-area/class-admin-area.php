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

        // Display post/page slugs in admin list tables
        if ( get_option( 'ddtt_page_slugs', true ) ) {
            add_action( 'admin_init', function() {
                $post_types = $this->post_types();
                foreach ( $post_types as $post_type ) {
                    add_filter( "manage_{$post_type}_posts_columns", [ $this, 'add_path_column' ] );
                    add_action( "manage_{$post_type}_posts_custom_column", [ $this, 'render_path_column' ], 10, 2 );
                }
            } );
        }

        if ( get_option( 'ddtt_force_updates_check', true ) ) {
            add_action( 'wp_ajax_ddtt_force_check_single_plugin', [ $this, 'ajax_force_check_single_plugin' ] );
            add_action( 'wp_ajax_ddtt_force_check_single_theme', [ $this, 'ajax_force_check_single_theme' ] );
            add_action( 'wp_ajax_ddtt_commit_update_results', [ $this, 'ajax_commit_update_results' ] );
            add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'protect_plugin_update_transient' ] );
            add_filter( 'pre_set_site_transient_update_themes', [ $this, 'protect_theme_update_transient' ] );
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
        
        $exclude = [
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
            'wp_font_family',
            'wp_font_face',
            'e-floating-buttons',
            'elementor_library',
            'elementor-hf',
            'elementor_page',
            'elementor_global',
            'elementor_theme',
            'elementor_icons',
            'blnotifier-results',
        ];
        foreach( $exclude as $post_type ) {
            if ( ( $key = array_search( $post_type, $post_types ) ) !== false ) {
                unset( $post_types[ $key ] );
            }
        }

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
     * Add Path column to post/page admin pages
     *
     * @param array $columns
     * @return array
     */
    public function add_path_column( $columns ) {
        $columns[ 'ddtt_post_path' ] = 'Path';
        return $columns;
    } // End add_path_column()


    /**
     * Add the post/page Path column content
     *
     * @param string $column
     * @param int $post_id
     */
    public function render_path_column( $column, $post_id ) {
        if ( $column !== 'ddtt_post_path' ) {
            return;
        }

        $post = get_post( $post_id );
        $status = $post->post_status;

        if ( $status === 'publish' || $status === 'private' ) {
            $permalink = get_permalink( $post );
        } else {
            $permalink = get_preview_post_link( $post );
        }

        // Strip the domain to get the path + query
        $parsed = wp_parse_url( $permalink );
        $path = '';
        if ( isset( $parsed[ 'path' ] ) ) {
            $path .= $parsed[ 'path' ];
        }
        if ( isset( $parsed[ 'query' ] ) ) {
            $path .= '?' . $parsed[ 'query' ];
        }

        echo '<code style="color:#555;">' . esc_html( $path ) . '</code>';
    } // End render_path_column()


    /**
     * AJAX: Check a single plugin against the WP.org or external API and inject into transient.
     */
    public function ajax_force_check_single_plugin() {
        $nonce = isset( $_POST[ 'nonce' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'nonce' ] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'force_update_check' ) || ! current_user_can( 'update_plugins' ) ) {
            wp_send_json_error();
        }

        $plugin = isset( $_POST[ 'plugin' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'plugin' ] ) ) : '';
        $slug   = isset( $_POST[ 'slug' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'slug' ] ) ) : '';

        if ( ! $plugin || ! $slug ) {
            wp_send_json_error();
        }

        $plugin_data   = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
        $local_version = $plugin_data[ 'Version' ] ?? '0';

        $api_url  = 'https://api.wordpress.org/plugins/info/1.0/' . $slug . '.json';
        $request  = wp_remote_get( $api_url );

        if ( is_wp_error( $request ) ) {
            wp_send_json_success( [
                'plugin'     => $plugin,
                'has_update' => false,
                'error'      => 'WP_Error: ' . $request->get_error_message(),
            ] );
        }

        $body     = wp_remote_retrieve_body( $request );
        $response = json_decode( $body );

        if ( empty( $response ) || ! empty( $response->error ) ) {
            wp_send_json_success( [
                'plugin'     => $plugin,
                'has_update' => false,
                'error'      => 'Not found on WordPress.org',
            ] );
        }

        $remote_version = $response->version ?? '0';

        if ( version_compare( $remote_version, $local_version, '>' ) ) {
            wp_send_json_success( [
                'has_update'    => true,
                'plugin'        => $plugin,
                'slug'          => $slug,
                'local_version' => $local_version,
                'new_version'   => $remote_version,
                'download_link' => $response->download_link ?? '',
            ] );
        }

        wp_send_json_success( [
            'plugin'     => $plugin,
            'has_update' => false,
        ] );
    } // End ajax_force_check_single_plugin()


    /**
     * AJAX: Check a single theme against the WP.org API and inject into transient.
     */
    public function ajax_force_check_single_theme() {
        $nonce = isset( $_POST[ 'nonce' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'nonce' ] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'force_update_check' ) || ! current_user_can( 'update_themes' ) ) {
            wp_send_json_error();
        }

        $stylesheet = isset( $_POST[ 'stylesheet' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'stylesheet' ] ) ) : '';

        if ( ! $stylesheet ) {
            wp_send_json_error();
        }

        $theme         = wp_get_theme( $stylesheet );
        $local_version = $theme->get( 'Version' ) ?? '0';

        $api_url  = 'https://api.wordpress.org/themes/info/1.1/?action=theme_information&request[slug]=' . rawurlencode( $stylesheet ) . '&request[fields][version]=1';
        $request  = wp_remote_get( $api_url );

        if ( is_wp_error( $request ) ) {
            wp_send_json_success( [
                'theme'      => $stylesheet,
                'has_update' => false,
                'error'      => 'WP_Error: ' . $request->get_error_message(),
            ] );
        }

        $body     = wp_remote_retrieve_body( $request );
        $response = json_decode( $body );

        if ( empty( $response ) || ! empty( $response->error ) ) {
            wp_send_json_success( [
                'theme'      => $stylesheet,
                'has_update' => false,
                'error'      => 'Not found on WordPress.org',
            ] );
        }

        $remote_version = $response->version ?? '0';

        if ( version_compare( $remote_version, $local_version, '>' ) ) {
            wp_send_json_success( [
                'has_update'    => true,
                'theme'         => $stylesheet,
                'local_version' => $local_version,
                'new_version'   => $remote_version,
                'download_link' => $response->download_link ?? '',
            ] );
        }

        wp_send_json_success( [
            'theme'      => $stylesheet,
            'has_update' => false,
        ] );
    } // End ajax_force_check_single_theme()


    /**
     * AJAX: Commit update results to prevent transient from being overwritten.
     */
    public function ajax_commit_update_results() {
        $nonce = isset( $_POST[ 'nonce' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'nonce' ] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'force_update_check' ) || ! current_user_can( 'update_plugins' ) ) {
            wp_send_json_error();
        }

        $plugin_updates = isset( $_POST[ 'plugin_updates' ] ) ? json_decode( stripslashes( $_POST[ 'plugin_updates' ] ), true ) : [];
        $theme_updates  = isset( $_POST[ 'theme_updates' ] ) ? json_decode( stripslashes( $_POST[ 'theme_updates' ] ), true ) : [];

        if ( ! empty( $plugin_updates ) ) {
            $transient = get_site_transient( 'update_plugins' );

            if ( ! is_object( $transient ) ) {
                $transient = new \stdClass();
            }

            if ( ! isset( $transient->response ) ) {
                $transient->response = [];
            }

            if ( ! isset( $transient->checked ) ) {
                $transient->checked = [];
            }

            $transient->last_checked = time();

            foreach ( $plugin_updates as $update ) {
                $plugin = $update[ 'plugin' ];
                $slug   = $update[ 'slug' ];

                $transient->checked[ $plugin ]  = $update[ 'local_version' ];
                $transient->response[ $plugin ] = (object) [
                    'id'          => 'w.org/plugins/' . $slug,
                    'slug'        => $slug,
                    'plugin'      => $plugin,
                    'new_version' => $update[ 'new_version' ],
                    'url'         => 'https://wordpress.org/plugins/' . $slug . '/',
                    'package'     => $update[ 'download_link' ],
                    'icons'       => [
                        '1x' => 'https://ps.w.org/' . $slug . '/assets/icon-128x128.png',
                        '2x' => 'https://ps.w.org/' . $slug . '/assets/icon-256x256.png',
                    ],
                ];
            }

            foreach ( $plugin_updates as $update ) {
                unset( $transient->no_update[ $update[ 'plugin' ] ] );
            }

            set_site_transient( 'update_plugins', $transient );
            wp_clear_scheduled_hook( 'wp_update_plugins' );
            set_site_transient( 'ddtt_protected_plugin_updates', $transient->response, 12 * HOUR_IN_SECONDS );
        }

        if ( ! empty( $theme_updates ) ) {
            $transient = get_site_transient( 'update_themes' );

            if ( ! is_object( $transient ) ) {
                $transient = new \stdClass();
            }

            if ( ! isset( $transient->response ) ) {
                $transient->response = [];
            }

            if ( ! isset( $transient->checked ) ) {
                $transient->checked = [];
            }

            $transient->last_checked = time();

            foreach ( $theme_updates as $update ) {
                $stylesheet = $update[ 'theme' ];

                $transient->checked[ $stylesheet ]  = $update[ 'local_version' ];
                $transient->response[ $stylesheet ] = [
                    'theme'       => $stylesheet,
                    'new_version' => $update[ 'new_version' ],
                    'url'         => 'https://wordpress.org/themes/' . $stylesheet . '/',
                    'package'     => $update[ 'download_link' ],
                    'icons'       => [
                        '1x' => 'https://ts.w.org/' . $stylesheet . '/screenshot.png?ver=' . $update[ 'new_version' ],
                    ],
                ];
            }

            foreach ( $theme_updates as $update ) {
                unset( $transient->no_update[ $update[ 'theme' ] ] );
            }

            set_site_transient( 'update_themes', $transient );
            wp_clear_scheduled_hook( 'wp_update_themes' );
            set_site_transient( 'ddtt_protected_theme_updates', $transient->response, 12 * HOUR_IN_SECONDS );
        }

        wp_send_json_success();
    } // End ajax_commit_update_results()


    /**
     * Protect injected plugin update data from being overwritten.
     *
     * @param mixed $value
     * @return mixed
     */
    public function protect_plugin_update_transient( $value ) {
        $protected = get_site_transient( 'ddtt_protected_plugin_updates' );
        if ( empty( $protected ) ) {
            return $value;
        }

        if ( ! is_object( $value ) ) {
            $value = new \stdClass();
        }

        if ( ! isset( $value->response ) ) {
            $value->response = [];
        }

        foreach ( $protected as $plugin => $data ) {
            $value->response[ $plugin ] = $data;
        }

        return $value;
    } // End protect_plugin_update_transient()


    /**
     * Protect injected theme update data from being overwritten.
     *
     * @param mixed $value
     * @return mixed
     */
    public function protect_theme_update_transient( $value ) {
        $protected = get_site_transient( 'ddtt_protected_theme_updates' );
        if ( empty( $protected ) ) {
            return $value;
        }

        if ( ! is_object( $value ) ) {
            $value = new \stdClass();
        }

        if ( ! isset( $value->response ) ) {
            $value->response = [];
        }

        foreach ( $protected as $stylesheet => $data ) {
            $value->response[ $stylesheet ] = $data;
        }

        return $value;
    } // End protect_theme_update_transient()


    /**
     * Enqueue admin area assets.
     * 
     * @param string $hook The current admin page hook suffix.
     */
    public function enqueue_assets( $hook ) : void {
        $version = Bootstrap::script_version();
        $is_test_mode = Bootstrap::is_test_mode();

        
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
            'test_mode'   => $is_test_mode,
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


        /**
         * Updates Page (single site or multisite)
         */
        if ( in_array( $hook, [ 'update-core.php', 'update-core-network.php' ], true ) && ! isset( $_GET[ 'action' ] ) ) {
            wp_enqueue_script(
                'ddtt-updates-page',
                Bootstrap::url( 'inc/admin-area/updates.js' ),
                [ 'jquery' ],
                $version,
                true
            );

            if ( ! function_exists( 'get_plugins' ) ) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            $all_plugins    = get_plugins();
            $plugin_list    = [];
            foreach ( $all_plugins as $file => $data ) {
                $slug = $data[ 'TextDomain' ] ?? '';
                if ( ! $slug ) {
                    continue;
                }
                $plugin_list[] = [
                    'file' => $file,
                    'slug' => $slug,
                    'name' => $data[ 'Name' ] ?? $slug,
                ];
            }

            usort( $plugin_list, function( $a, $b ) {
                return strcmp( $a[ 'name' ], $b[ 'name' ] );
            } );

            $all_themes  = wp_get_themes();
            $theme_list  = [];
            foreach ( $all_themes as $stylesheet => $theme ) {
                $theme_list[] = [
                    'stylesheet' => $stylesheet,
                    'name'       => $theme->get( 'Name' ) ?? $stylesheet,
                ];
            }
            usort( $theme_list, function( $a, $b ) {
                return strcmp( $a[ 'name' ], $b[ 'name' ] );
            } );

            wp_localize_script( 'ddtt-updates-page', 'ddtt_updates', [
                'nonce'     => wp_create_nonce( 'force_update_check' ),
                'plugins'   => $plugin_list,
                'themes'    => $theme_list,
                'btn_label' => __( 'Force Update Check', 'dev-debug-tools' ),
                'test_mode' => $is_test_mode,
                'i18n'      => [
                    'checking'       => __( 'Checking', 'dev-debug-tools' ),
                    'plugin_updates' => __( 'plugin update(s) found', 'dev-debug-tools' ),
                    'theme_updates'  => __( 'theme update(s) found', 'dev-debug-tools' ),
                    'all_up_to_date' => __( 'Everything is up to date.', 'dev-debug-tools' ),
                ],
            ] );
        }
    } // End enqueue_assets()

}


new AdminArea();