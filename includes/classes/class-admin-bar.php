<?php
/**
 * Admin bar class
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Initiate the class
 */
new DDTT_ADMIN_BAR;


/**
 * Main plugin class.
 */
class DDTT_ADMIN_BAR {

    /**
	 * Constructor
	 */
	public function __construct() {

        // Customize the admin bar menu
        add_action( 'admin_bar_menu', [ $this, 'admin_bar' ], 99999 );

	} // End __construct()


    /**
     * Customize Admin Bar Items
     *
     * @param object $wp_admin_bar
     * @return void
     */
    public function admin_bar( $wp_admin_bar ) {
        // Get the current URL
        $current_url = ddtt_get_current_url();

        // Get the user ID
        $user_id = get_current_user_id();


        /**
         * Remove Items
         */

        // Items on both front-end and admin area
        if ( get_option( DDTT_GO_PF.'admin_bar_wp_logo' ) && get_option( DDTT_GO_PF.'admin_bar_wp_logo' ) == 1 ) {
            $wp_admin_bar->remove_node( 'wp-logo' ); // The WordPress Logo
        }


        /**
         * Add the user's name and ID in a better way with page loaded info
         */        
        // Replace Howdy with User ID
        if ( 0 != $user_id ) {
            $my_account = $wp_admin_bar->get_node( 'my-account' );
            $greeting = str_replace( 'Howdy,', '(User ID: '.$user_id.')', $my_account->title );
            $wp_admin_bar->add_node( [
                'id' => 'my-account',
                'title' => $greeting,
            ] );

            // Dev only
            if ( ddtt_is_dev() ) {

                // Get the dev's timezone
                if ( get_option( 'ddtt_dev_timezone' ) && get_option( 'ddtt_dev_timezone' ) != ''){
                    $tz = get_option( 'ddtt_dev_timezone' );
                } else {
                    $tz = wp_timezone_string();
                }
                $currentTime = ddtt_convert_timezone( null, 'g:i A', $tz );
                $currentDate = ddtt_convert_timezone( null, 'n/j/y', $tz );

                // Add the page loaded information
                $loaded_text = sprintf( __( 'Page loaded at %1$s on %2$s', 'dev-debug-tools' ), $currentTime, $currentDate );       
                $wp_admin_bar->add_node( [
                    'id' => DDTT_GO_PF.'page-loaded',
                    'parent' => 'user-actions',
                    'title' => $loaded_text
                ] );
            }
        }


        /**
         * Add the post ID and status
         */
        if ( !is_admin() && ( !get_option( DDTT_GO_PF.'admin_bar_post_info' ) || get_option( DDTT_GO_PF.'admin_bar_post_info' ) == 0 ) ) {
            if ( is_singular() ) {
                $post_id = get_the_ID();
                
            } else {
    
                // Cornerstone content editor
                $theme = wp_get_theme(); // gets the current theme
                if ( 'X – Child Theme' == $theme->name || 'X' == $theme->parent_theme ) {
                    $cornerstone = get_site_url().'/cornerstone/content/';
                    if ( strpos( $current_url, $cornerstone ) !== false ) {
                        $parsed_url = parse_url( $current_url );
                        $path = $parsed_url[ 'path' ];
                        $explode = explode( '/', $path );
                        $post_id = $explode[3];
                    } else {
                        $post_id = false;
                    }
                } else {
                    $post_id = false;
                }
            }
    
            // If post id exists, continue
            if ( $post_id ) {
                
                // Add Page/Post ID
                $post_type = get_post_type( $post_id );
                $post_type_obj = get_post_type_object( $post_type );
                if ( $post_type_obj ) {
                    $pt_name = esc_html( $post_type_obj->labels->singular_name ).' ID';
                } else {
                    $pt_name = '';
                }
    
                // Add Page/Post Status 
                $get_post_status = get_post_status( $post_id );
                if ( $get_post_status == 'publish' ) {
                    $post_status = 'Published';
                } elseif ( $get_post_status == 'auto-draft' ) {
                    $post_status = 'Auto Draft';
                } elseif ( $get_post_status == 'draft' ) {
                    $post_status = 'Draft';
                } elseif ( $get_post_status == 'private' ) {
                    $post_status = 'Private';
                } elseif ( $get_post_status == 'archive' ) {
                    $post_status = 'Archived';
                } else {
                    $post_status = $get_post_status;
                }

                // What to add to the bar
                $post_info_title = sprintf( __( '%1$s %2$s (%3$s)', 'dev-debug-tools' ), $pt_name, $post_id, $post_status );    
            } else {
                $post_info_title = sprintf( __( '%1$s', 'dev-debug-tools' ), 'Not Singular' );
            }

            // Add to bar
            $wp_admin_bar->add_node( [
                'id'     => DDTT_GO_PF.'admin-post-id',
                'parent' => 'top-secondary',
                'title'  => $post_info_title,
            ] );
        }

        
        /**
         * Add resource links
         */
        if ( !get_option( DDTT_GO_PF.'admin_bar_resources' ) || get_option( DDTT_GO_PF.'admin_bar_resources' ) == 0 ) {
            if ( ddtt_is_dev() ) {
                $DDTT_RESOURCES = new DDTT_RESOURCES();
                $links = $DDTT_RESOURCES->get_resources();
                if ( !empty( $links ) ) {
                    $resources_icon = '&#128214;';       
                    $wp_admin_bar->add_node( [
                        'id' => DDTT_GO_PF.'resources',
                        'title' => $resources_icon
                    ] );

                    // Add each link
                    foreach ( $links as $key => $link ) {
                        $wp_admin_bar->add_node( [
                            'id' => DDTT_GO_PF.'resource-'.$key,
                            'parent' => DDTT_GO_PF.'resources',
                            'title' => $link[ 'title' ],
                            'href' => $link[ 'url' ],
                            'meta' => [
                                'class' => DDTT_GO_PF.'resource',
                                'target' => '_blank'
                            ],
                        ] );
                    }
                }
            }
        }
        

        /**
         * Add additional links to {Site Name} dropdown on front end
         */
        if ( !is_admin() ) {

            // Store the custom links here
            $site_name_links = [];

            // Are we including all menu items?
            if ( get_option( DDTT_GO_PF.'admin_bar_add_links' ) == 1 && ddtt_is_dev() ) {

                // Get them
                $admin_menu_links = get_option( DDTT_GO_PF.'admin_menu_links' );
                if ( !empty( $admin_menu_links ) ) {

                    // Iter them
                    foreach ( $admin_menu_links as $admin_menu_link ) {
                        
                        // Check permissions
                        if ( !current_user_can( $admin_menu_link[ 'perm' ] ) ) {
                            continue;
                        }

                        // Add it
                        $site_name_links[] = [ 
                            $admin_menu_link[ 'label' ],
                            home_url( '/'.DDTT_ADMIN_URL.'/'.$admin_menu_link[ 'url' ] )
                        ];
                    }
                }

            // Otherwise add a few of our own
            } elseif ( ddtt_is_dev() ) {

                // Dev Debug Tools
                $site_name_links[] = [ 'Dev Debug Tools', ddtt_plugin_options_path() ];

                // Admin Help Docs
                if ( is_plugin_active( 'admin-help-docs/admin-help-docs.php' ) ) {
                    $help_docs_menu_title = get_option( 'helpdocs_menu_title' );
                    if ( !$help_docs_menu_title ) {
                        $help_docs_menu_title = 'Help Docs';
                    }
                    $site_name_links[] = [ $help_docs_menu_title, helpdocs_plugin_options_path() ];
                }

                // Merge
                $site_name_links = array_merge( $site_name_links, [
                    [ 'Users', '/'.DDTT_ADMIN_URL.'/users.php' ],
                    [ 'Posts', '/'.DDTT_ADMIN_URL.'/edit.php' ],
                    [ 'Pages', '/'.DDTT_ADMIN_URL.'/edit.php?post_type=page' ],
                ] );

                // Gravity Forms
                if ( is_plugin_active( 'gravityforms/gravityforms.php' ) ){
                    $site_name_links[] = [ 'Forms', '/'.DDTT_ADMIN_URL.'/admin.php?page=gf_edit_forms' ];
                }

                // Plugins
                $site_name_links[] = [ 'Plugins', '/'.DDTT_ADMIN_URL.'/plugins.php' ];
            }

            // Support filtering all links
            $site_name_links = apply_filters( 'ddtt_admin_bar_dropdown_links', $site_name_links );

            // Add them to the admin bar
            foreach( $site_name_links as $snl ) {
                $wp_admin_bar->add_node( [
                    'id' => DDTT_GO_PF.strtolower( str_replace( ' ', '_', $snl[0] ) ),
                    'parent' => 'site-name',
                    'title' => $snl[0],
                    'href' => $snl[1]
                ] );
            }
        }


        /**
         * Add centering tool
         */
        $ct_is_enabled = false;
        if ( !get_option( DDTT_GO_PF.'admin_bar_centering_tool' ) || get_option( DDTT_GO_PF.'admin_bar_centering_tool' ) == 0 ) {
            if ( !is_admin() ) {

                // Get column count
                if ( get_option( DDTT_GO_PF.'centering_tool') && get_option( DDTT_GO_PF.'centering_tool') != '' ) {
                    $ct_count = get_option( DDTT_GO_PF.'centering_tool');
                } else {
                    $ct_count = 16;
                }

                // Update
                if ( ddtt_get( 'ct' ) && ddtt_get( 'ct' ) == 'true' ) {
                    update_user_meta( $user_id, DDTT_GO_PF.'centering_tool', $ct_count );
                    ddtt_remove_qs_without_refresh( 'ct', false );
                    
                } elseif ( ddtt_get( 'ct' ) && ddtt_get( 'ct' ) == 'false' ) {
                    update_user_meta( $user_id, DDTT_GO_PF.'centering_tool', false );
                    ddtt_remove_qs_without_refresh( 'ct', false );
                }

                // Display
                $url_to_parse = parse_url( htmlspecialchars( $_SERVER[ 'REQUEST_URI' ] ) );
                $qsi = isset( $url_to_parse['query'] ) ? '&' : '?';
                if ( get_user_meta( $user_id, DDTT_GO_PF.'centering_tool', true ) && get_user_meta( $user_id, DDTT_GO_PF.'centering_tool', true ) != '' ) {
                    $ct_is_enabled = true;

                    // Container
                    $centering_tool = '<div id="ct-top" class="centering-tool" expanded="false">';
                    for ( $i = 0; $i < $ct_count; $i++ ) {
                        if ( $ct_count % 2 == 0 && $i == round( ( $ct_count / 2 ) - 1, 0 ) || 
                            $ct_count % 2 == 1 && $i == round( ( $ct_count / 2 ) - 1, 0 ) || 
                            $ct_count % 2 == 1 && $i == round( ( $ct_count / 2 ) - 2, 0 ) ) {

                            $center = ' center';
                        } else {
                            $center = '';
                        }
                        $centering_tool .= '<div class="ct-q'.$center.'"></div>';
                    }
                    $centering_tool .= '</div>';

                    // Add to page 
                    echo wp_kses_post( $centering_tool );

                    // Text and link
                    $ct_text = 'On';
                    $ct_link = $current_url.$qsi.'ct=false';
                    
                } else {
                    $ct_is_enabled = false;
                    $ct_text = 'Off';
                    $ct_link = $current_url.$qsi.'ct=true';
                }
                $wp_admin_bar->add_node( [
                    'id' => DDTT_GO_PF.'ct-admin-bar',
                    'parent' => 'top-secondary',
                    'title' => '&#x271B; '.$ct_text,
                    'href' => $ct_link
                ] );
            }
        }

        
        /**
         * Dev only
         */
        if ( ddtt_is_dev() && !is_admin() ){

            /**
             * Add shortcodes count
             */
            if ( !get_option( DDTT_GO_PF.'admin_bar_shortcodes' ) || get_option( DDTT_GO_PF.'admin_bar_shortcodes' ) == 0 ) {
                $shortcodes = ddtt_get_shortcodes_on_page();
                $sc_bar = sprintf( __( '<span class="full-width-only">[%1$s]</span>', 'dev-debug-tools' ), count( $shortcodes ) ); 
                $wp_admin_bar->add_node( [
                    'id' => DDTT_GO_PF.'shortcodes-found',
                    'parent' => 'top-secondary',
                    'title' => $sc_bar
                ] );

                // Add the list of shortcodes
                if ( !empty( $shortcodes ) ) {
                    $sc_desc_text = __( 'Shortcodes Found:', 'dev-debug-tools' );   
                    $wp_admin_bar->add_node( [
                        'id' => DDTT_GO_PF.'shortcode-desc',
                        'parent' => DDTT_GO_PF.'shortcodes-found',
                        'title' => $sc_desc_text
                    ] );

                    $sc_num = 0;
                    $shortcode_counts = array_count_values( $shortcodes );
                    foreach ( $shortcode_counts as $sc => $shortcode_count ) {
                        if ( $shortcode_count > 1 ) {
                            $incl_count = ' <span class="'.DDTT_GO_PF.'shortcode-count">x'.$shortcode_count.'</span>';
                        } else {
                            $incl_count = '';
                        }
                        $loaded_text = sprintf( __( '[%1$s]%2$s', 'dev-debug-tools' ), $sc, $incl_count );       
                        $wp_admin_bar->add_node( [
                            'id' => DDTT_GO_PF.'shortcode-'.$sc_num,
                            'parent' => DDTT_GO_PF.'shortcodes-found',
                            'title' => $loaded_text
                        ] );
                        $sc_num++;
                    }
                }
            }


            /**
             * Get the Gravity Forms IDs from the page
             */
            if ( !get_option( DDTT_GO_PF.'admin_bar_gf' ) || get_option( DDTT_GO_PF.'admin_bar_gf' ) == 0 ) {
                if ( is_plugin_active( 'gravityforms/gravityforms.php' ) ) {
                    $form_ids = ddtt_get_form_ids_on_page();
                    $gf_icon = '<div class="wp-menu-image svg" style="width: 19px; height: 23px; display: inline-block; margin: 0 2px 0 -6px; vertical-align: middle; background-image: url(&quot;data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz48c3ZnIHdpZHRoPSIyMSIgaGVpZ2h0PSIyMSIgdmlld0JveD0iMCAwIDIxIDIxIiBmaWxsPSIjYTdhYWFkIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxtYXNrIGlkPSJtYXNrMCIgbWFzay10eXBlPSJhbHBoYSIgbWFza1VuaXRzPSJ1c2VyU3BhY2VPblVzZSIgeD0iMiIgeT0iMSIgd2lkdGg9IjE3IiBoZWlnaHQ9IjIwIj48cGF0aCBmaWxsLXJ1bGU9ImV2ZW5vZGQiIGNsaXAtcnVsZT0iZXZlbm9kZCIgZD0iTTExLjU5MDYgMi4wMzcwM0wxNy4xNzkzIDUuNDQ4MjRDMTcuODk0IDUuODg0IDE4LjQ3NjcgNi45NTI5OCAxOC40NzY3IDcuODI0NTFWMTQuNjUwM0MxOC40NzY3IDE1LjUxODUgMTcuODk0IDE2LjU4NzQgMTcuMTc5MyAxNy4wMjMyTDExLjU5MDYgMjAuNDMxQzEwLjg3OTIgMjAuODY2OCA5LjcxMDU1IDIwLjg2NjggOC45OTkwOSAyMC40MzFMMy40MTA0MSAxNy4wMTk4QzIuNjk1NzMgMTYuNTg0IDIuMTEzMDQgMTUuNTE4NSAyLjExMzA0IDE0LjY0NjlWNy44MjExQzIuMTEzMDQgNi45NTI5OCAyLjY5ODk1IDUuODg0IDMuNDEwNDEgNS40NDgyNEw4Ljk5OTA5IDIuMDM3MDNDOS43MTA1NSAxLjYwMTI2IDEwLjg3OTIgMS42MDEyNiAxMS41OTA2IDIuMDM3MDNaTTE1Ljc0OTQgOS4zNzUwM0g4LjgxMDQ5QzguMzgyOTkgOS4zNzUwMyA4LjA2MjM3IDkuNTAxNjQgNy44MDkwNCA5Ljc3MDY4QzcuMjU0ODggMTAuMzYwMiA2Ljk2MTk2IDExLjUwMzYgNi45MTg0MiAxMi4xNDA2SDEzLjc1MDVWMTAuNDI3NUgxNS43MDE5VjE0LjA5MTJINC44NDAzMUM0Ljg0MDMxIDE0LjA5MTIgNC44Nzk4OSAxMC4wMzk3IDYuMzkxOTcgOC40MzMzOUM3LjAxNzM4IDcuNzY0NzUgNy44NDA3IDcuNDI0NDkgOC44MzAyOCA3LjQyNDQ5SDE1Ljc0OTRWOS4zNzUwM1oiIGZpbGw9IiNhN2FhYWQiLz48L21hc2s+PGcgbWFzaz0idXJsKCNtYXNrMCkiPjxyZWN0IHg9IjAuMjk0OTIyIiB5PSIwLjc1NzgxMiIgd2lkdGg9IjIwIiBoZWlnaHQ9IjIwIiBmaWxsPSIjYTdhYWFkIi8+PC9nPjwvc3ZnPg==&quot;) !important;" aria-hidden="true"><br></div>';
                    if ( empty( $form_ids ) ) {
                        $gf_var = $gf_icon.' No Forms';

                    } elseif ( count( $form_ids ) > 1 ) {
                        $gf_var = $gf_icon.' '.count( $form_ids ).' Forms';
                        
                    } else {
                        $form_id_display = '<a href="/'.DDTT_ADMIN_URL.'/admin.php?page=gf_edit_forms&view=settings&subview=settings&id='.$form_ids[0].'" target="_blank" style="display: inline-block; color: white;">'.$form_ids[0].'</a>';
                        $gf_var = $gf_icon.' Form ID: '.$form_id_display;
                    }

                    $gf_bar = '<span class="full-width-only">'.$gf_var.'</span>';
                    $wp_admin_bar->add_node( [
                        'id' => DDTT_GO_PF.'gf-found',
                        'parent' => 'top-secondary',
                        'title' => $gf_bar
                    ] );

                    // Iter the form links
                    foreach( $form_ids as $form_id ) {
                        $link = '<a href="/'.DDTT_ADMIN_URL.'/admin.php?page=gf_edit_forms&view=settings&subview=settings&id='.$form_id.'" target="_blank" style="display: inline-block; color: white;">'.$form_id.'</a>';
                        $wp_admin_bar->add_node( [
                            'id' => DDTT_GO_PF.'gf-found-multiple'.'-'.$form_id,
                            'parent' => DDTT_GO_PF.'gf-found',
                            'title' => 'ID: '.$link,
                            'meta' => [
                                'class' => DDTT_GO_PF.'gf-found',
                            ],
                        ] );
                    }
                }
            }
        }


        // Are we condensing the menu items?
        $condense_option = get_option( DDTT_GO_PF.'admin_bar_condense' );
        if ( $condense_option == 'Everyone' || 
             ( $condense_option == 'Developer Only' && ddtt_is_dev() ) || 
             ( $condense_option == 'Everyone Excluding Developer' && !ddtt_is_dev() ) ) {
            $condense_items = true;
        } else {
            $condense_items = false;
        }

        // Check for condensing
        if ( $condense_items ) {

            // Get the nodes only
            $nodes = $wp_admin_bar->get_nodes();

            // Do not condense
            $condensed = apply_filters( 'ddtt_admin_bar_condensed_items', [
                'site-name' => '',
                'customize' => '',
                'edit' => '',
                'wpengine_adminbar' => 'WPE',
                'tco-main' => '<span class="tco-admin-bar-logo ab-item" style="background-image: url(data:image/svg+xml;base64,CiAgICA8c3ZnIGZpbGw9IiNhN2FhYWQiIHZpZXdCb3g9IjAgMCA3OTIgNzgwIiB2ZXJzaW9uPSIxLjEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiPgogICAgICA8ZyBzdHJva2U9Im5vbmUiIHN0cm9rZS13aWR0aD0iMSIgZmlsbC1ydWxlPSJldmVub2RkIj4KICAgICAgICA8cGF0aCBkPSJNNDMuMzYzNjA5NSw4Ni45NjQxMjgzIEw3MzYuMzYzNjA5LDAuMzg2ODI3NTk5IEM3NjMuNDkwODI2LC0zLjAwMjIwNzMyIDc4OC4yMjkxMzYsMTYuMjQxMzkxMiA3OTEuNjE4MTcxLDQzLjM2ODYwNzkgQzc5MS44NzI0NzgsNDUuNDA0MTgzNCA3OTIsNDcuNDUzNTk5MSA3OTIsNDkuNTA0OTk4NSBMNzkyLDcyOS42MjU5NDEgQzc5Miw3NTYuOTY0MDM2IDc2OS44MzgwOTUsNzc5LjEyNTk0MSA3NDIuNSw3NzkuMTI1OTQxIEM3NDAuNjUzMDk4LDc3OS4xMjU5NDEgNzM4LjgwNzY0Myw3NzkuMDIyNTc2IDczNi45NzIyOTIsNzc4LjgxNjMzMSBMNDMuOTcyMjkyMSw3MDAuOTQxMzMxIEMxOC45MzA3OTg3LDY5OC4xMjczMjUgLTEuMDM0OTU0MWUtMTMsNjc2Ljk1MDA0OSAtMS4wNjU4MTQxZS0xMyw2NTEuNzUwOTQxIEwtMS4xMzY4NjgzOGUtMTMsMTM2LjA4MjI5OSBDLTEuMTY3NDQyMDNlLTEzLDExMS4xMTcwMTkgMTguNTkwOTA0Niw5MC4wNTkwMTEzIDQzLjM2MzYwOTUsODYuOTY0MTI4MyBaIE0zNzMuNTk5NDc1LDQ2My4zNDI4MDggQzM1NS4zODM0NzUsNDgxLjU0ODc3NyAzMjguMDU5NDc1LDQ5MS40NDMzMjYgMzAzLjkwMzQ3NSw0OTEuNDQzMzI2IEMyMzUuMzk1NDc1LDQ5MS40NDMzMjYgMjA4Ljg2MzQ3NSw0NDMuNTUzNzExIDIwOC40Njc0NzUsMzk3LjY0MzAwNSBDMjA4LjA3MTQ3NSwzNTEuMzM2NTE3IDIzNi45Nzk0NzUsMzAxLjQ2Nzk5MiAzMDMuOTAzNDc1LDMwMS40Njc5OTIgQzMyOC4wNTk0NzUsMzAxLjQ2Nzk5MiAzNTIuNjExNDc1LDMwOS43Nzk0MTMgMzcwLjgyNzQ3NSwzMjcuNTg5NiBMNDA1LjY3NTQ3NSwyOTMuOTQ4MTM1IEMzNzcuMTYzNDc1LDI2NS44NDc2MTcgMzQxLjUyMzQ3NSwyNTEuNTk5NDY3IDMwMy45MDM0NzUsMjUxLjU5OTQ2NyBDMjAzLjcxNTQ3NSwyNTEuNTk5NDY3IDE1Ni41OTE0NzUsMzI1LjIxNDkwOSAxNTYuOTg3NDc1LDM5Ny42NDMwMDUgQzE1Ny4zODM0NzUsNDY5LjY3NTMxOSAyMDAuOTQzNDc1LDU0MC41MjAyODcgMzAzLjkwMzQ3NSw1NDAuNTIwMjg3IEMzNDMuODk5NDc1LDU0MC41MjAyODcgMzgwLjcyNzQ3NSw1MjcuNDU5NDgzIDQwOS4yMzk0NzUsNDk5LjM1ODk2NSBMMzczLjU5OTQ3NSw0NjMuMzQyODA4IFogTTYzOC45MTk0NzUsMzAyLjY1NTMzOCBDNjE3LjkzMTQ3NSwyNTkuOTEwODg4IDU3My4xODM0NzUsMjQ3LjY0MTY0NyA1MzAuMDE5NDc1LDI0Ny42NDE2NDcgQzQ3OC45MzU0NzUsMjQ4LjAzNzQyOSA0MjIuNzAzNDc1LDI3MS4zODg1NjQgNDIyLjcwMzQ3NSwzMjguMzgxMTY0IEM0MjIuNzAzNDc1LDM5MC41MTg5MyA0NzQuOTc1NDc1LDQwNS41NTg2NDQgNTMxLjYwMzQ3NSw0MTIuMjg2OTM3IEM1NjguNDMxNDc1LDQxNi4yNDQ3NTYgNTk1Ljc1NTQ3NSw0MjYuOTMwODY5IDU5NS43NTU0NzUsNDUzLjA1MjQ3NyBDNTk1Ljc1NTQ3NSw0ODMuMTMxOTA1IDU2NC44Njc0NzUsNDk0LjYwOTU4MiA1MzEuOTk5NDc1LDQ5NC42MDk1ODIgQzQ5OC4zMzk0NzUsNDk0LjYwOTU4MiA0NjYuMjYzNDc1LDQ4MS4xNTI5OTUgNDUzLjk4NzQ3NSw0NTAuNjc3Nzg2IEw0MTAuNDI3NDc1LDQ3My4yMzczNTcgQzQzMS4wMTk0NzUsNTIzLjg5NzQ0NiA0NzQuNTc5NDc1LDU0MS4zMTE4NTEgNTMxLjIwNzQ3NSw1NDEuMzExODUxIEM1OTIuOTgzNDc1LDU0MS4zMTE4NTEgNjQ3LjYzMTQ3NSw1MTQuNzk0NDYxIDY0Ny42MzE0NzUsNDUzLjA1MjQ3NyBDNjQ3LjYzMTQ3NSwzODYuOTU2ODkyIDU5My43NzU0NzUsMzcxLjkxNzE3OCA1MzUuOTU5NDc1LDM2NC43OTMxMDMgQzUwMi42OTU0NzUsMzYwLjgzNTI4NCA0NzQuMTgzNDc1LDM1NC4xMDY5OTEgNDc0LjE4MzQ3NSwzMjkuOTY0MjkyIEM0NzQuMTgzNDc1LDMwOS4zODM2MzEgNDkyLjc5NTQ3NSwyOTMuMTU2NTcxIDUzMS42MDM0NzUsMjkzLjE1NjU3MSBDNTYxLjY5OTQ3NSwyOTMuMTU2NTcxIDU4Ny44MzU0NzUsMzA4LjE5NjI4NSA1OTcuMzM5NDc1LDMyNC4wMjc1NjMgTDYzOC45MTk0NzUsMzAyLjY1NTMzOCBaIj48L3BhdGg+CiAgICAgIDwvZz4KICAgIDwvc3ZnPgoKICAgIA==)"></span>',
            ] );

            // Iter the nodes
            foreach ( $nodes as $node ) {

                // Remove the node
                $wp_admin_bar->remove_node( $node->id );

                // Are we condensing it?
                foreach ( $condensed as $k => $c ) {

                    // Check the condensed array
                    if ( $k == $node->id ) {

                        // Are we replacing the title with an icon or short text?
                        if ( $c && $c != '' ) {

                            // Replace the title property
                            $node->title = $c;

                        } else {
                            
                            // Just remove the title property
                            $node->title = '';
                        }
                    }
                }

                // Add the node back
                $wp_admin_bar->add_node( $node );
            }
        }

        
        /**
         * ADD CSS
         */
        echo '<style>
        .'.esc_attr( DDTT_GO_PF ).'shortcode-count {
            background-color: #26BECF;
            border-radius: 25px !important;
            display: inline-block;
            padding: 0 5px !important;
            color: black;
            line-height: 1.5 !important;
        }
        @media (max-width: 1200px) { 
            li#wp-admin-bar-'.esc_attr( DDTT_GO_PF ).'my-account .full-width-only {
                display: none !important;
            }
        }
        ul li .ab-item, #wpadminbar .quicklinks .menupop ul li a strong, #wpadminbar .quicklinks .menupop.hover ul li .ab-item, #wpadminbar .shortlink-input, #wpadminbar.nojs .quicklinks .menupop:hover ul li .ab-item {
            line-height: 2.5 !important;
        }
        div#ct-top {
            height: 25px;
            transition: height 300ms;
            width: 100%;
            background: rgba(0, 0, 0, 0.15);
            display: flex;
            position: fixed;
            top: 30px;
            left: 0;
            right: 0%;
            z-index: 9999;
            cursor: pointer;
        }
        .ct-q {
            width: 25%;
            border-right: 1px solid #000;
        }
        .ct-q.center {
            border-right: 2px solid blue;
        }
        .ct-q:last-child {
            width: 25%;
            border-right: none;
        }
        @media only screen and (max-width: 641px) {
            div#ct-top {
                top: 0 !important;
            }
        }';

        // Make admin menu links scrollable if too long
        if ( !is_admin() ) {
            echo '
            #wpadminbar #wp-admin-bar-site-name .ab-sub-wrapper {
                max-height: 400px;
                overflow-y: auto !important;
            }
            #wpadminbar #wp-admin-bar-site-name .ab-sub-wrapper ul:last-of-type {
                padding-bottom: 10px;
            }';
        }

        // Change css for the condensed items
        if ( $condense_items ) {
            foreach ( $condensed as $k => $c ) {
                
                // Remove the right margin from the icon
                echo '
                #wp-admin-bar-'.esc_attr( $k ).' .ab-item:first-child:before,
                #wp-admin-bar-'.esc_attr( $k ).' .ab-item span:first-child {
                    margin-right: 0 !important;
                }';
            }

            // Remove online users text
            echo '
            #wp-admin-bar-'.esc_attr( DDTT_GO_PF ).'online-users .hide-condensed {
                display: none;
            }';
        }
        echo '</style>';


        /**
         * Add JS
         */
        if ( $ct_is_enabled ) {
            echo '<script>
            document.getElementById( "ct-top" ).addEventListener( "click", function() {
                var ct = document.getElementById( "ct-top" );
                var exp = ct.getAttribute( "expanded" );
                if ( exp == "false" ) {
                    ct.style.height = "100%";
                    ct.setAttribute( "expanded", "true" );
                } else {
                    ct.style.height = "25px";
                    ct.setAttribute( "expanded", "false" );
                }
            });
            </script>';
        }
    } // End admin_bar()

}