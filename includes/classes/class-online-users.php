<?php
/**
 * Online Users Class
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Initiate the class
 */
new DDTT_ONLINE_USERS;


/**
 * Main plugin class.
 */
class DDTT_ONLINE_USERS {

    /**
     * Number of seconds to check last online
     *
     * @var integer
     */
    public $seconds = 900;


    /**
     * The discord webhook
     *
     * @var string
     */
    public $discord_webhook;


    /**
	 * Constructor
	 */
	public function __construct() {

        // Update seconds based on setting
        $this->seconds = get_option( DDTT_GO_PF.'online_users_seconds', 900 );

        // Update user online status
        add_action( 'init', [ $this, 'users_status_init' ] );

        // Admin bar
        add_action( 'admin_bar_menu', [ $this, 'admin_bar' ], 999 );

        // User column
        add_filter( 'manage_users_columns', [ $this, 'user_column' ] );
        add_action( 'admin_head-users.php', [ $this, 'user_column_style' ] );
        add_action( 'manage_users_custom_column', [ $this, 'user_column_content' ], 999, 3 );

        // Shortcode
        add_shortcode( 'online_users_count', [ $this, 'shortcode' ] );

        // Get discord webhook
        $this->discord_webhook = get_option( DDTT_GO_PF.'discord_webhook' );
        $discord_page_loads = get_option( DDTT_GO_PF.'discord_page_loads' );
        $discord_login = get_option( DDTT_GO_PF.'discord_login' );
        
        // Notifify on page load
        if ( $this->discord_webhook && $this->discord_webhook != '' &&
             $discord_page_loads && $discord_page_loads == 1 ) {
            if ( is_admin() ) {
                $hook = 'admin_init';
            } else {
                $hook = 'template_redirect';
            }
            add_action( $hook, [ $this, 'page_load_discord_notification' ] );
        }

        // Discord notifications
        if ( $this->discord_webhook && $this->discord_webhook != '' &&
             $discord_login && $discord_login == 1 ) {
            add_action( 'wp_login', [ $this, 'login_discord_notification' ], 10, 2 );
        }

	} // End __construct()


    /**
     * Check if the current page is notification worthy
     *
     * @param string $current_url
     * @return boolean
     */
    public function is_notify_worthy_page( $current_url ) {
        // Pages to ignore
        $pages = apply_filters( 'ddtt_ignore_pages_for_discord_notifications', [
            [ 
                'url'    => get_rest_url(),
                'prefix' => true
            ],
            [ 
                'url'    => ddtt_admin_url( 'admin-ajax.php' ),
                'prefix' => true
            ],
            [ 
                'url'    => ddtt_admin_url( 'options.php' ),
                'prefix' => false
            ]
        ] );

        // Iter the pages
        foreach ( $pages as $page ) {     

            // Validate and sanitize
            if ( isset( $page[ 'prefix' ] ) && 
                 isset( $page[ 'url' ] ) && 
                 filter_var( $page[ 'url' ], FILTER_SANITIZE_URL ) != '' ) {
                $prefix = filter_var( $page[ 'prefix' ], FILTER_VALIDATE_BOOLEAN );
                $url = filter_var( $page[ 'url' ], FILTER_SANITIZE_URL );

                // Is this url just a prefix?
                if ( $prefix && str_starts_with( $current_url, $url ) ||
                     !$prefix && $current_url === $url ) {
                    return false;
                    break;
                }
            }
        }

        // Otherwise we're good
        return true;
    } // End is_notify_worthy_page()


    /**
     * Ignore devs
     *
     * @return boolean
     */
    public function maybe_ignore_devs() {
        if ( get_option( DDTT_GO_PF.'discord_ingore_devs' ) && ddtt_is_dev() ) {
            return true;
        }
        return false;
    } // End maybe_ignore_devs()

    
    /**
     * Get the online users
     *
     * @param string $return
     * @return int|array
     */
    public function online_users( $return = 'count' ) {
        // Get the user status transient
        $logged_in_users = get_transient( 'users_status' );
        
        // If no users are online
        if ( empty( $logged_in_users ) ) {
            // If requesting a count return 0, if requesting user data return false.
            return ( $return == 'count' ) ? 0 : false; 
        }
        
        // Set the count to zero
        $user_online_count = 0;

        // Store the users here
        $online_users = [];

        // Iter the users
        foreach ( $logged_in_users as $user ) {

            // If the user has been online in the last # of seconds, add them to the array and increase the online count.
            if ( !empty( $user[ 'username' ] ) && isset( $user[ 'last' ] ) && $user[ 'last' ] > time() - $this->seconds ) { 
                $online_users[] = $user;
                $user_online_count++;
            }
        }

        // Return either an integer count, or an array of all online user data.
        return ( $return == 'count' ) ? $user_online_count : $online_users; 
    } // End online_users()

    
    /**
     * Update user online status
     *
     * @return void
     */
    public function users_status_init() {
        // Ignore visitors
        if ( !is_user_logged_in() ) {
            return;
        }

        // The current url
        if ( !function_exists( 'ddtt_get_current_url' ) ) {
            return;
        }
        $current_url = ddtt_get_current_url();

        // Skip if just loading ajax, otherwise it loads twice
        if ( !$this->is_notify_worthy_page( $current_url ) ) {
            return;
        }

        // Get the active users from the transient
        $logged_in_users = get_transient( 'users_status' );

        // Get the current user
        $user = wp_get_current_user();

        // Update the user if they are not on the list, or if they have not been online in the last # of seconds
        if ( !isset( $logged_in_users[ $user->ID ] ) || !isset( $logged_in_users[ $user->ID ][ 'last' ] ) || $logged_in_users[ $user->ID ][ 'last' ] <= time() - $this->seconds ) {

            // Check for discord notifications
            $discord_transient = get_option( DDTT_GO_PF.'discord_transient' );
            if ( $discord_transient && $discord_transient == 1 && !$this->maybe_ignore_devs() ) {

                // Notify
                $this->validate_and_send_discord_notification( $user, 'Intermittent Logged-In User', $current_url );
            }

            // The user array to set in the transient
            $user_array = [
                'id'       => $user->ID,
                'username' => $user->user_login,
                'last'     => time(),
            ];

            // Check if user exists
            if ( isset( $logged_in_users[ $user->ID ] ) ) {
                $logged_in_users[ $user->ID ] = $user_array;

            // Otherwise merge
            } else {
                $this_user = [ $user->ID => $user_array ];
                if ( $logged_in_users && is_array( $logged_in_users ) ) {
                    $logged_in_users += $this_user;
                } else {
                    $logged_in_users = $this_user;
                }
            }

            // Set this transient to expire 15 minutes after it is created
            set_transient( 'users_status', $logged_in_users, $this->seconds );
            update_user_meta( $user->ID, 'ddtt_last_online', time() );
        }
    } // End users_status_init()


    /**
     * Check if a user has been online in the last 15 minutes
     *
     * @param int $id
     * @return boolean
     */
    public function is_user_online( $id ) {	
        // Get the active users from the transient
        $logged_in_users = get_transient( 'users_status' ); 
        
        // Return boolean if the user has been online in the last # of seconds
        return isset( $logged_in_users[ $id ][ 'last' ] ) && $logged_in_users[ $id ][ 'last' ] > time() - $this->seconds; 
    } // End is_user_online()


    /**
     * Check when a user was last online
     *
     * @param int $id
     * @return int|false
     */
    public function user_last_online( $id, $also_check_user_meta = false ) {
        // Get the active users from the transient
        $logged_in_users = get_transient( 'users_status' ); 
        
        // Determine if the user has ever been logged in (and return their last active date if so)
        if ( isset( $logged_in_users[ $id ][ 'last' ] ) ) {
            return $logged_in_users[ $id ][ 'last' ];
        } elseif ( $also_check_user_meta ) {
            return get_user_meta( $id, 'ddtt_last_online', true );
        } else {
            return false;
        }
    } // End user_last_online()


    /**
     * Add an online user count to the admin bar
     *
     * @param [type] $wp_admin_bar
     * @return void
     */
    public function admin_bar( $wp_admin_bar ) {
        // Get the online user count
        $active_users_count = $this->online_users( 'count' );

        // Plural or singular
        $s = ( $active_users_count == 1 ) ? '' : 's';

        // Add the node
        $wp_admin_bar->add_node( [
            'id'    => DDTT_GO_PF.'online-users',
            'title' => '<span class = "ab-icon"></span>'.$active_users_count.'<span class = "full-width-only hide-condensed"> User'.$s.' Online</span>',
            'href'  => '/'.DDTT_ADMIN_URL.'/users.php',
            'meta'  => [
                'class' => DDTT_GO_PF.'online-user-count',
            ],
        ] );

        // Store the users here
        $users = [];

        // We are not linking by default
        $link = false;

        // Make sure we found active users
        if ( $active_users_count > 0 ) {

            // Get the active users
            $active_users = $this->online_users( 'get_users' );

            // Are we linking?
            if ( get_option( DDTT_GO_PF.'online_users_link' ) && get_option( DDTT_GO_PF.'online_users_link' ) != '' ) {
                $link = get_option( DDTT_GO_PF.'online_users_link' );
            }

            // Get the role details
            if ( !function_exists( 'get_editable_roles' ) ) {
                require_once ABSPATH.DDTT_ADMIN_URL.'/includes/user.php';
            }
            $roles = get_editable_roles();

            // Fetch priority roles only once
            $priority_roles = get_option( DDTT_GO_PF.'online_users_priority_roles' );
            if ( $priority_roles ) {
                $priority_roles = array_keys( $priority_roles );
            } else {
                $priority_roles = [];
            }

            // Iter the active users
            foreach ( $active_users as $active_user ) {

                // Get the user
                $user_id = $active_user[ 'id' ];
                $user = get_userdata( $user_id );

                // Make sure user exists
                if ( $user ) {
                
                    // First and last name
                    if ( $user->first_name && $user->last_name ) {
                        $display_name = $user->first_name.' '.$user->last_name;
                    } else {
                        $display_name = $user->display_name;
                    }

                    // Are we showing last online?
                    if ( get_option( DDTT_GO_PF.'online_users_show_last' ) && get_option( DDTT_GO_PF.'online_users_show_last' ) == 1 ) {
                        $last_date = ddtt_convert_timestamp_to_string( $active_user[ 'last' ], true );
                        $show_last = ' ('.$last_date.')';
                    } else {
                        $show_last = '';
                    }

                    // The user roles
                    $user_roles = $user->roles;

                    // Priority roles
                    $intersect = false;
                    if ( $user_roles && $priority_roles && !empty( $priority_roles ) ) {
                        $intersect = array_intersect( $user_roles, $priority_roles );
                    }
                    if ( $priority_roles && !empty( $intersect ) ) {

                        // Store the role names here
                        $intersect_names = [];

                        // Iter the roles
                        foreach ( $roles as $key => $role ) {

                            // Iter the user roles that match
                            foreach ( $intersect as $i ) {

                                // Get the name
                                if ( $i == $key ) {
                                    if ( $key == 'administrator' || $key == 'super_admin' ) {
                                        $intersect_names[] = 'Admin';
                                    } else {
                                        $intersect_names[] = $role[ 'name' ];
                                    }
                                }
                            }
                        }

                        // Sort the slugs
                        sort( $user_roles );

                        // Add them
                        $this_user[ 'name' ] = '<span class="'.implode( ' ', $user_roles ).'">'.$display_name.' <em>- '.implode( ', ', $intersect_names ).'</em>'.$show_last.'</span>'; 

                    // Otherwise add admin anyway
                    } elseif ( $priority_roles === false && ( in_array( 'administrator', (array) $user_roles ) || in_array( 'super_admin', (array) $user_roles ) ) ) {
                        $this_user[ 'name' ] = '<span class="admin">'.$display_name.' <em>- Admin</em>'.$show_last.'</span>';

                    // Other users
                    } else {

                        // Check for staff (matching email domains to website domain)
                        $email_parts = explode( '@', strtolower( $user->user_email ) );
                        $email_domain = $email_parts[1];
                        $urlparts = wp_parse_url( home_url() );
                        $domain = isset( $urlparts[ 'host' ] ) ? $urlparts[ 'host' ] : '';
                        if ( preg_match( '/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs ) ) {
                            $website_domain = $regs[ 'domain' ];
                        } else {
                            $website_domain = $domain;
                        }
                        if ( $email_domain == $website_domain ) {
                            $this_user[ 'name' ] = '<span class="staff">'.$display_name.' <em>- &#x2B50;</em>'.$show_last.'</span>';
                        } else {
                            $this_user[ 'name' ] = $display_name.$show_last;
                        }
                    }

                    // Are we linking?
                    if ( $link ) {
                        if ( strpos( $link, '{user_id}' ) !== false ) {
                            $this_user[ 'link' ] = str_replace( '{user_id}', $user_id, $link );
                        }
                        if ( strpos( $link, '{user_email}' ) !== false ) {
                            $this_user[ 'link' ] = str_replace( '{user_email}', $user->user_email, $link );
                        }
                    }

                    // Add the user
                    $users[] = $this_user;
                }
            }
        }

        // Verify there are users
        if ( !empty( $users ) ) {
            
            // Sort them alphabetically
            sort( $users );

            // Add them to the submenu
            foreach ( $users as $key => $user ) {
                $node_args = [
                    'id'     => DDTT_GO_PF.'online-users-li-'.$key,
                    'parent' => DDTT_GO_PF.'online-users',
                    'title'  => $user[ 'name' ],
                    'meta'   => [
                        'class' => DDTT_GO_PF.'online-user-li',
                    ],
                ];
                if ( $link ) {
                    $node_args[ 'href' ] = isset( $user[ 'link' ] ) ? $user[ 'link' ] : '#';
                }
                $wp_admin_bar->add_node( $node_args );
            }
        }

        // Add a blank row
        $wp_admin_bar->add_node( [
            'id'     => DDTT_GO_PF.'online-users-li-blank',
            'parent' => DDTT_GO_PF.'online-users',
            'title'  => '',
            'meta'   => [
                'class' => DDTT_GO_PF.'online-user-li',
            ],
        ] );

        // Count the total number of users
        $user_count = count_users();

        // Add total users
        $wp_admin_bar->add_node( [
            'id'     => DDTT_GO_PF.'online-users-li-total',
            'parent' => DDTT_GO_PF.'online-users',
            'title'  => 'Total Users: '.number_format( $user_count[ 'total_users' ] ),
            'meta'   => [
                'class' => DDTT_GO_PF.'online-user-li',
            ],
            'href'   => '/'.DDTT_ADMIN_URL.'/users.php'
        ] );

        // Add some CSS
        echo '<style>
        #wp-admin-bar-'.esc_attr( DDTT_GO_PF ).'online-users .ab-icon {
            height: 5px;
            width: 13px;
            margin-top: 9px;
            margin-right: 6px;
            background-color: green;
            border-radius: 50%;
        }
        @media (max-width: 1200px) { 
            #wp-admin-bar-'.esc_attr( DDTT_GO_PF ).'online-users .full-width-only {
                display: none !important;
            }
        }
        #wp-admin-bar-'.esc_attr( DDTT_GO_PF ).'online-users-li-blank {
            height: 10px;
        }
        #wp-admin-bar-'.esc_attr( DDTT_GO_PF ).'online-users-li-total {
            border-top: 1px solid #A7AAAD;
        }
        #wp-admin-bar-'.esc_attr( DDTT_GO_PF ).'online-users .ab-sub-wrapper .ab-item:has(span) {
            display: flex;
            align-items: center;
        }
        </style>';
    } // End admin_bar()


    /**
     * Add the user column
     *
     * @param array $columns
     * @return array
     */
    public function user_column( $columns ) {
        $columns[ 'online_status' ] = 'Online Status';
        return $columns;
    } // End user_column()


    /**
     * Column width
     *
     * @return void
     */
    public function user_column_style() {
        echo '<style>.column-online_status{width: 10%}</style>';
    } // End users_column_style()


    /**
     * Column content
     *
     * @param string $value
     * @param string $column_name
     * @param int $user_id
     * @return string
     */
    public function user_column_content( $value, $column_name, $user_id ) {
        if ( $column_name == 'online_status' ) {
            if ( $this->is_user_online( $user_id ) ) {
                return '<strong style="color: green;">Online Now</strong>';
            } else {
                $last_seen = ddtt_convert_timezone( $this->user_last_online( $user_id ), 'M j, Y @ g:ia' );
                return ( $this->user_last_online( $user_id ) ) ? '<small>Last Online: <br /><em>'.$last_seen.'</em></small>' : '';
            }
        }
        return $value;
    } // End column_content()
    

    /**
     * Shortcode for adding online count
     * USAGE: [online_users_count indicator="false" text="false"]
     *
     * @param [type] $atts
     * @return void
     */
    public function shortcode( $atts ) {
        $atts = shortcode_atts(array(
            'indicator' => 'true',
            'text' => 'true'
        ), $atts);

        // Get the user count
        $active_users_count = $this->online_users( 'count' );

        // Status?
        if ( strtolower( $atts[ 'indicator' ] ) == 'true' ) {
            $status = '<div style="height: 10px; width: 10px; background-color: green; border-radius: 50%; margin-right: 5px; display: inline-block;"></div>';
        } else {
            $status = '';
        }

        // Text?
        if ( strtolower( $atts[ 'text' ] ) == 'true' ) {
            $s = ( $active_users_count == 1 ) ? '' : 's';
            $text = ' User'.$s.' Online';
        } else {
            $text = '';
        }
        
        // Return it
        return $status.$active_users_count.$text;
    } // End shortcode()


    /**
     * Send Discord notification
     * 
     * @return boolean
     */
    public function page_load_discord_notification() {
        // Ignore visitors
        if ( !is_user_logged_in() ) {
            return;
        }

        // The current url
        if ( is_singular() ) {
            $id = get_the_ID();
            $url = get_the_permalink( $id );
            $post_type_object = get_post_type_object( get_post_type( $id ) );
            if ( $post_type_object ) {
                $post_type = sanitize_text_field( $post_type_object->labels->singular_name );
            } else {
                $post_type = null;
            }
        } else {
            $url = ddtt_get_current_url();
            $id = url_to_postid( $url );
            $post_type = null;
        }
        
        // Skip pages
        if ( !$this->is_notify_worthy_page( $url ) ) {
            return;
        }

        // Ignore devs
        if ( $this->maybe_ignore_devs() ) {
            return false;
        }

        // Get the current user
        $user = wp_get_current_user();

        // Send the notification
        return $this->validate_and_send_discord_notification( $user, 'New Page Load', $url , $id, $post_type );
    } // End login_discord_notification()


    /**
     * Send Discord notification
     * 
     * @return boolean
     */
    public function login_discord_notification( $user_login, WP_User $user ) {
        $this->validate_and_send_discord_notification( $user, 'New Login' );
    } // End login_discord_notification()


    /**
     * Send Discord notification
     * 
     * @return boolean
     */
    public function validate_and_send_discord_notification( $user, $title, $url = null, $id = null, $post_type = null ) {
        // Check for a webhook url
        if ( !$this->discord_webhook || $this->discord_webhook == '' ) {
            return false;
        }

        // Found
        $found = false;

        // Get the roles
        $roles = get_option( DDTT_GO_PF.'online_users_priority_roles' );
        if ( !$roles || $roles == '' || empty( $roles ) ) {
            return false;
        }
        foreach ( $user->roles as $role ) {
            if ( array_key_exists( $role, $roles ) ) {
                $found = true;
                break;
            }
        }
        
        // Check that this user has the role
        if ( !$found ) {
            return false;
        }

        // Args
        $domain = ddtt_get_domain();
        $website = get_bloginfo( 'name' );
        if ( !$website || $website == '' ) {
            $website = $domain;
        }
        $args = [
            'embed'          => true,
            'title'          => $title.' on '.$website,
            'title_url'      => $domain,
            'disable_footer' => false,
            'fields'         => [
                [
                    'name'   => 'User ID',
                    'value'  => $user->ID,
                    'inline' => false
                ],
                [
                    'name'   => 'Name',
                    'value'  => $user->display_name,
                    'inline' => false
                ],
                [
                    'name'   => 'Email',
                    'value'  => $user->user_email,
                    'inline' => false
                ],
                [
                    'name'   => 'Website',
                    'value'  => $domain,
                    'inline' => false
                ]
            ]
        ];

        // Include current page if we have a url
        if ( !is_null( $url ) && $url != '' ) {

            // Start with no title
            $title = '';

            // If post id is found
            if ( !is_null( $id ) && $id > 0 ) {

                // Get the title
                $title = sanitize_text_field( get_the_title( $id ) );

            // No id found from url
            } else {

                // Check for post id in query string
                $qs = wp_parse_url( $url );
                if ( isset( $qs[ 'query' ] ) ) {
                    parse_str( $qs[ 'query' ], $params );

                    // Check if we're editing a post or page
                    if ( isset( $params[ 'post' ] ) && isset( $params[ 'action' ] ) && sanitize_key( $params[ 'action' ] ) == 'edit' ) {
                        $title = 'EDITING: '.sanitize_text_field( get_the_title( absint( $params[ 'post' ] ) ) );
                    }

                // Check if we're editing with cornerstone
                } elseif ( strpos( $url, '/cornerstone/edit/' ) !== false ) {
                    $ex = explode( '/', trim( $url,'/' ) );
                    $id = end( $ex );
                    $title = 'EDITING via CORNERSTONE: '.sanitize_text_field( get_the_title( absint( $id ) ) );
                }
            }

            // Format the title if we found one
            if ( $title != '' ) {
                $title = '
Title: '.$title;

                // Add post type if available
                if ( !is_null( $id ) && $id > 0 ) {
                    $title .= '
ID: '.$id;
                }

                // Add post type if available
                if ( !is_null( $post_type ) && $post_type != '' ) {
                    $title .= '
Post Type: '.$post_type;
                }
            }

            // Add the page field
            $args[ 'fields' ][] = [
                'name'   => 'Page',
                'value'  => $url.$title,
                'inline' => false
            ];
        }

        // Add a thumbnail if site icon exists
        if ( $avatar_url = get_avatar_url( $user->ID ) ) {
            $args[ 'thumbnail_url' ] = $avatar_url;
        } else {
            $icon = get_site_icon_url();
            if ( $icon && $icon != '' ) {
                $args[ 'thumbnail_url' ] = $icon;
            }
        }
        
        // First try sending to Discord
        if ( (new DDTT_DISCORD)->send( $this->discord_webhook, $args ) ) {
            return true;
        } else {
            return false;
        }
    } // End validate_and_send_discord_notification()
}