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
	 * Constructor
	 */
	public function __construct() {

        // Update user online status
        add_action('init', [ $this, 'users_status_init' ] );
        add_action('admin_init', [ $this, 'users_status_init' ] );

        // Admin bar
        add_action('admin_bar_menu', [ $this, 'admin_bar' ], 999);

        // Dashboard widget
        add_action( 'wp_dashboard_setup', [ $this, 'dashboard_widget_metabox'] );

        // User column
        add_filter( 'manage_users_columns', [ $this, 'user_column' ] );
        add_action( 'admin_head-users.php', [ $this, 'user_column_style' ] );
        add_action( 'manage_users_custom_column', [ $this, 'user_column_content' ], 10, 3 );

        // Shortcode
        add_shortcode( 'online_users_count', [ $this, 'shortcode' ] );

	} // End __construct()

    
    /**
     * Get the online users
     *
     * @param string $return
     * @return int|array
     */
    public function online_users( $return = 'count' ){
        // Get the user status transient
        $logged_in_users = get_transient('users_status');
        
        // If no users are online
        if ( empty( $logged_in_users ) ){
            // If requesting a count return 0, if requesting user data return false.
            return ( $return == 'count' ) ? 0 : false; 
        }
        
        // Set the count to zero
        $user_online_count = 0;

        // Store the users here
        $online_users = [];

        // Iter the users
        foreach ( $logged_in_users as $user ){

            // If the user has been online in the last 900 seconds, add them to the array and increase the online count.
            if ( !empty( $user[ 'username' ] ) && isset( $user[ 'last' ] ) && $user[ 'last' ] > time()-900 ){ 
                $online_users[] = $user;
                $user_online_count++;
            }
        }

        // Return either an integer count, or an array of all online user data.
        return ( $return == 'count' )? $user_online_count : $online_users; 
    } // End online_users()

    
    /**
     * Update user online status
     *
     * @return void
     */
    public function users_status_init() {
        // Get the active users from the transient
        $logged_in_users = get_transient('users_status'); 

        // Get the current user's data
        $user = wp_get_current_user(); 

        // Update the user if they are not on the list, or if they have not been online in the last 900 seconds (15 minutes)
        if ( !isset( $logged_in_users[ $user->ID ][ 'last' ] ) || $logged_in_users[ $user->ID ][ 'last' ] <= time()-900 ) {
            $timezone = get_option( 'timezone_string' );
            date_default_timezone_set( $timezone );
            $logged_in_users[ $user->ID ] = [
                'id' => $user->ID,
                'username' => $user->user_login,
                'last' => time(),
            ];

            // Set this transient to expire 15 minutes after it is created
            set_transient( 'users_status', $logged_in_users, 900 ); 
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
        
        // Return boolean if the user has been online in the last 900 seconds (15 minutes)
        return isset( $logged_in_users[ $id ][ 'last' ] ) && $logged_in_users[ $id ][ 'last' ] > time()-900; 
    } // End is_user_online()


    /**
     * Check when a user was last online
     *
     * @param int $id
     * @return int|false
     */
    public function user_last_online( $id ) {
        // Get the active users from the transient
        $logged_in_users = get_transient('users_status'); 
        
        // Determine if the user has ever been logged in (and return their last active date if so)
        if ( isset( $logged_in_users[ $id ][ 'last' ] ) ) {
            return $logged_in_users[ $id ][ 'last' ];
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
            'id' => DDTT_GO_PF.'online-users',
            'title' => '<span class="ab-icon"></span>'.$active_users_count.'<span class="full-width-only hide-condensed"> User'.$s.' Online</span>',
            'href' => '/'.DDTT_ADMIN_URL.'/users.php',
            'meta' => [
                'class' => DDTT_GO_PF.'online-user-count',
            ],
        ] );

        // Store the users here
        $users = [];

        // Make sure we found active users
        if ( $active_users_count > 0 ) {

            // Get the active users
            $active_users = $this->online_users( 'get_users' );

            // Iter the active users
            foreach( $active_users as $active_user ) {

                // Get the user
                $user_id = $active_user[ 'id' ];
                $user = get_userdata( $user_id );

                // Admins
                if (in_array( 'administrator', (array) $user->roles) || in_array( 'super_admin', (array) $user->roles)) {               
                    $users[] = '<span>'.$user->first_name.' '.$user->last_name.' <em>- Admin</em></span>';

                // Other users
                } else {
                    $users[] = $user->first_name.' '.$user->last_name;
                }
            }
        }

        // Verify there are users
        if ( !empty( $users ) ) {
            
            // Sort them alphabetically
            sort($users);

            // Add them to the submenu
            foreach( $users as $key => $value ) {
                $wp_admin_bar->add_node( array(
                    'id' => DDTT_GO_PF.'online-users-li-'.$key,
                    'parent' => DDTT_GO_PF.'online-users',
                    'title' => $value,
                    'meta' => array(
                        'class' => DDTT_GO_PF.'online-user-li',
                    ),
                ));
            }
        }

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
        </style>';
    } // End admin_bar()


    /**
     * Dasboard widget metabox
     *
     * @return void
     */
    public function dashboard_widget_metabox(){
        if ( current_user_can( 'administrator' ) ) {
            wp_add_dashboard_widget(
                DDTT_GO_PF.'active_users',
                'Active Users', 
                [ $this, 'dashboard_active_users' ],
                null,
                null,
                'normal',
                'high' 
            );
        }
    } // End dashboard_widget_metabox()


    /**
     * Dashboard widget content
     *
     * @return void
     */
    public function dashboard_active_users(){
        // Count the number of users
        $user_count = count_users();

        // User or Users
        $users_plural = ( $user_count[ 'total_users' ] == 1 ) ? 'User' : 'Users';

        // Count the active users
        $active_users_count = $this->online_users( 'count' );
        
        // Add the totals
        echo '<div>
            <a href="users.php">' . absint( $user_count[ 'total_users' ] ) . ' ' . esc_html( $users_plural ) . '</a> 
            <span style="color: green;">( <strong>' . absint( $active_users_count ) . '</strong> currently active)</span>
        </div><br>';

        // Store the users here
        $users = [];

        // Check if there are any online (there should be at all times)
        if ( $active_users_count > 0 ) {

            // Get the active users
            $active_users = $this->online_users( 'get_users' );

            // Iter each active user
            foreach( $active_users as $active_user ) {

                // Get the user
                $user_id = $active_user['id'];
                $user = get_userdata( $user_id );

                // If they are an admin
                if ( in_array( 'administrator', (array) $user->roles) || in_array( 'super_admin', (array) $user->roles ) ) {               
                    // Add admin to the end
                    $users[] = '<strong>'.$user->first_name.' '.$user->last_name.'</strong> <em>- Administrator</em>';

                // Other users
                } else {

                    // Just add their name
                    $users[] = $user->first_name.' '.$user->last_name;
                }
            }
        }

        // Did we find users?
        if ( !empty( $users ) ) {

            // Sort them alphabetically
            sort( $users );

            // Add them to the dashboard widget
            echo '<div><ul style="list-style: square; margin-left: 20px;">';
            foreach( $users as $listuser ) {
                echo '<li>'.wp_kses_post( $listuser ).'</li>';
            }
            echo '</ul></div>';
        }
    
        // While we're here, let's hide those annoying empty containers
        echo '<style>
        #dashboard-widgets .postbox-container .empty-container {
            outline: 0;
        }
        #dashboard-widgets .postbox-container .empty-container:after {
            content: "";
        }
        </style>';
    } // End dashboard_active_users()


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
    public function user_column_style(){
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
            // Start the var
            $output = '';
        
            if ( $this->is_user_online( $user_id ) ){
                $output .= '<strong style="color: green;">Online Now</strong>';
            } else {
                $output .= ( $this->user_last_online( $user_id ) ) ? '<small>Last Seen: <br /><em>' . date('M j, Y @ g:ia', $this->user_last_online( $user_id ) ) . '</em></small>' : '';
            }
        
            // Return it
            return $output;
        }
    } // End column_content()
    

    /**
     * Shortcode for adding online count
     * USAGE: [online_users_count indicator="false" text="false"]
     *
     * @param [type] $atts
     * @return void
     */
    public function shortcode( $atts ){
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
}