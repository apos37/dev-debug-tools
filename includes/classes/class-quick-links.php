<?php
/**
 * Add debug quick links to users, posts, pages, custom post types
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Initiate the class
 */
new DDTT_QUICK_LINKS;


/**
 * Main plugin class.
 */
class DDTT_QUICK_LINKS {

    /**
     * Quick link icon
     * 
     * @var string
     */
    public $quick_link_icon;


    /**
     * Gravity Forms tab
     *
     * @var string
     */
    public $gravity_forms_tab;


    /**
	 * Constructor
	 */
	public function __construct() {

        // $this->quick_link_icon = '<svg width="20" height="20" viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1696 960q0 26-19 45t-45 19h-224q0 171-67 290l208 209q19 19 19 45t-19 45q-18 19-45 19t-45-19l-198-197q-5 5-15 13t-42 28.5-65 36.5-82 29-97 13v-896h-128v896q-51 0-101.5-13.5t-87-33-66-39-43.5-32.5l-15-14-183 207q-20 21-48 21-24 0-43-16-19-18-20.5-44.5t15.5-46.5l202-227q-58-114-58-274h-224q-26 0-45-19t-19-45 19-45 45-19h224v-294l-173-173q-19-19-19-45t19-45 45-19 45 19l173 173h844l173-173q19-19 45-19t45 19 19 45-19 45l-173 173v294h224q26 0 45 19t19 45zm-480-576h-640q0-133 93.5-226.5t226.5-93.5 226.5 93.5 93.5 226.5z"/></svg>';
        $this->quick_link_icon = '&#9889';

        // Gravity Forms tab
        $this->gravity_forms_tab = 'gfdebug';

        // Add User ID column with a link to debug the user's meta
        if ( get_option( DDTT_GO_PF.'ql_user_id') == '1' ) {
            add_filter( 'manage_users_columns', [ $this, 'user_column' ] );
            add_action( 'admin_head-users.php',  [ $this, 'user_column_style' ] );
            add_action( 'manage_users_custom_column', [ $this, 'user_column_content' ], 999, 3 );
        }

        // Add a link to debug the post or page's meta next to the Post ID
        if ( get_option( DDTT_GO_PF.'ql_post_id') == '1' ) {
            
            // Available post types
            $post_types = get_post_types( 
                [ 
                   'public'   => true, 
                //    '_builtin' => false 
                ], 
                'names'
            );

            // Add to all post types
            foreach ( $post_types as $post_type ) {
                
                add_filter( 'manage_'.$post_type.'_posts_columns', [ $this, 'post_column' ] );
                add_action( 'manage_'.$post_type.'_posts_custom_column', [ $this, 'post_column_content' ], 10, 2 );
            }
            add_action( 'admin_head-edit.php',  [ $this, 'post_column_style' ] );
        }

        // Add a link to debug the forml, entry or feed's meta
        if ( get_option( DDTT_GO_PF.'ql_gravity_forms') == '1' && is_plugin_active( 'gravityforms/gravityforms.php' ) ) {

            // Add a link to debug the form's meta
            add_action( 'gform_form_actions', [ $this, 'gf_form_quick_link' ], 10, 4 );

            // Add a link to debug the entry's meta
            add_action( 'gform_entries_first_column_actions', [ $this, 'gf_entry_quick_link' ], 10, 4 );
            add_filter( 'gform_entry_detail_meta_boxes', [ $this, 'gf_entry_meta_box' ], 10, 3 );

            // Add feed actions after gform is loaded
            add_action( 'gform_loaded', [ $this, 'gf_load_feed_quick_link'], 5 );
        }
	} // End __construct()


    /**
     * Add ID column to user admin page
     *
     * @param array $columns
     * @return array
     */
    public function user_column( $columns ) {
        $columns[ strtolower( DDTT_PF ).'user_id' ] = __( 'ID', 'dev-debug-tools' );
        return $columns;
    } // End user_column()


    /**
     * Change width of user column
     *
     * @return void
     */
    public function user_column_style(){
        echo '<style>.column-'.esc_attr( strtolower( DDTT_PF ) ).'user_id{width: 5%}</style>';
    } // user_column_style()


    /**
     * Add the user column content
     *
     * @param mixed $value
     * @param string $column_name
     * @param int $user_id
     * @return string
     */
    public function user_column_content( $value, $column_name, $user_id ) {
        // Make sure we are only modifying this column
        if ( $column_name == strtolower( DDTT_PF ).'user_id' ) {

            // Add an action to perform on each user when the page loads
            do_action( 'ddtt_admin_list_update_each_user', $user_id );

            // Allow icon to be filtered
            $quick_link_icon = apply_filters( 'ddtt_quick_link_icon', $this->quick_link_icon );

            // The content
            if ( ddtt_is_dev() ){
                return $user_id.' <a href="'.ddtt_plugin_options_path( 'usermeta' ).'&user='.$user_id.'" target="_blank">'.$quick_link_icon.'</a>';
            } else {
                return $user_id;
            }
        }
        return $value;
    } // End user_column_content()


    /**
     * Add ID column to post/page admin pages
     *
     * @param array $columns
     * @return array
     */
    public function post_column( $columns ) {
        $columns[ strtolower( DDTT_PF ).'post_id' ] = __( 'ID', 'dev-debug-tools' );
        return $columns;
    } // End user_column()


    /**
     * Change width of post/page ID column
     *
     * @return void
     */
    public function post_column_style(){
        echo '<style>.column-'.esc_attr( strtolower( DDTT_PF ) ).'post_id{width: 5%}</style>';
    } // user_column_style()


    /**
     * Add the post/page ID column content
     *
     * @param mixed $value
     * @param string $column_name
     * @param int $user_id
     * @return string
     */
    public function post_column_content( $column_name, $post_id ) {
        // Make sure we are only modifying this column
        if ( $column_name == strtolower( DDTT_PF ).'post_id' ) {

            // Add an action to perform on each user when the page loads
            do_action( 'ddtt_admin_list_update_each_post', $post_id );

            // Allow icon to be filtered
            $quick_link_icon = apply_filters( 'ddtt_quick_link_icon', $this->quick_link_icon );

            // The content
            if ( ddtt_is_dev() ){
                echo absint( $post_id ).' <a href="'.ddtt_plugin_options_path( 'postmeta' ).'&post_id='.$post_id.'" target="_blank">'.$quick_link_icon.'</a>';
            } else {
                echo absint( $post_id );
            }
        }
    } // End post_column_content()


    /**
     * Add quick links to Gravity Forms form list
     *
     * @param array $actions
     * @param int $form_id
     * @return array
     */
    public function gf_form_quick_link( $actions, $form_id ) {
        // Only allow devs
        if ( !ddtt_is_dev() ) {
            return $actions;
        }

        // The quick link icon
        $quick_link_icon = apply_filters( 'ddtt_quick_link_icon', $this->quick_link_icon );

        // Add action
        $link = ddtt_plugin_options_path( $this->gravity_forms_tab ).'&debug_form='.$form_id;
        $actions = array_merge( $actions, [
            'edit_post_cs' => sprintf( '<a href="%1$s" target="_blank">%2$s</a>',
                esc_url( $link ),
                'Debug Form '.$quick_link_icon
            )
        ] );
        return $actions;
    } // End gf_form_quick_link()


    /**
     * Add quick links to Gravity Forms entry list
     *
     * @param int $form_id
     * @param int $field_id
     * @param [type] $value
     * @param array $entry
     * @return void
     */
    public function gf_entry_quick_link( $form_id, $field_id, $value, $entry ) {
        // Only allow devs
        if ( !ddtt_is_dev() ) {
            return false;
        }

        // The quick link icon
        $quick_link_icon = apply_filters( 'ddtt_quick_link_icon', $this->quick_link_icon );

        // Add the link
        $link = ddtt_plugin_options_path( $this->gravity_forms_tab ).'&debug_entry='.$entry[ 'id' ];
        echo '| <span>'.sprintf( '<a href="%1$s" target="_blank">%2$s</a>',
            esc_url( $link ), 
            'Debug Entry '.$quick_link_icon
        ).'</span>';
    } // End gf_entry_quick_link()


    /**
     * Add a meta box on the entry
     *
     * @param array $meta_boxes
     * @param array $entry
     * @param array $form
     * @return array
     */
    public function gf_entry_meta_box( $meta_boxes, $entry, $form ) {
        // Only allow devs
        if ( !ddtt_is_dev() ) {
            return $meta_boxes;
        }

        // Link to Debug Form and Entry
        if ( !isset( $meta_boxes[ 'debug_entry' ] ) ) {
            $meta_boxes['debug_entry'] = [
                'title'         => esc_html__( 'Debug Entry', 'dev-debug-tools' ),
                'callback'      => [ $this, 'gf_entry_meta_box_content' ],
                'context'       => 'side',
                'callback_args' => [ $entry, $form ],
            ];
        }
     
        // Return the meta boxes
        return $meta_boxes;
    } // End gf_entry_meta_box()
    
    
    /**
     * Entry meta box content
     *
     * @param array $args
     * @return void
     */
    public function gf_entry_meta_box_content( $args ) {
        // Get the form and entry
        $form  = $args[ 'form' ];
        $entry = $args[ 'entry' ];
    
        // Get the testing url
        $url = ddtt_plugin_options_path( 'gfdebug' );
        
        // Start the container
        $results = '<div>';
    
        // Add the links
        $results .= '<a href="'.$url.'&debug_entry='.$entry[ 'id' ].'" target="_blank">Debug Entry</a>
        <br>
        <a href="'.$url.'&debug_form='.$form[ 'id' ].'" target="_blank">Debug Form</a>
        <br>
        <a href="'.$url.'&debug_entry='.$entry[ 'id' ].'&debug_form='.$form[ 'id' ].'" target="_blank">Debug Both</a>';
    
        // Start the container
        $results .= '</div>';
    
        // Return everything
        echo $results;
    } // End gf_entry_meta_box_content()

    
    /**
     * Load the filters for the feed quick link
     *
     * @return void
     */
    public function gf_load_feed_quick_link() {
        // Make ure the class exists
        if ( class_exists( 'GFAPI' ) ) {
            $feed_slugs = [];

            // Get the feeds
            $feeds = GFAPI::get_feeds();

            // Iter the feeds
            foreach ( $feeds as $feed ) {
                
                // Skip if no addon
                if ( !isset( $feed[ 'addon_slug' ] ) ) {
                    continue;
                }

                // Check if it's the array
                $feed_slug = $feed[ 'addon_slug' ];
                if ( !in_array( $feed_slug, $feed_slugs ) ) {
                    
                    // Add a link to debug the feed's meta
                    add_filter( $feed_slug.'_feed_actions', [ $this, 'gf_feed_quick_link' ], 10, 3 );
                }
            }
        }
    } // End gf_load_feed_quick_link()


    /**
     * Add quick links to Gravity Forms feeds
     *
     * @param int $form_id
     * @param int $field_id
     * @param [type] $value
     * @param array $entry
     * @return void
     */
    public function gf_feed_quick_link( $actions, $feed, $column ) {
        // Only allow devs
        if ( !ddtt_is_dev() ) {
            return $actions;
        }

        // The quick link icon
        $quick_link_icon = apply_filters( 'ddtt_quick_link_icon', $this->quick_link_icon );

        // Add the link
        $link = ddtt_plugin_options_path( $this->gravity_forms_tab ).'&debug_feed='.$feed[ 'id' ];
        $actions = array_merge( $actions, [
            'edit_post_cs' => sprintf( '<a href="%1$s" target="_blank">%2$s</a>',
                esc_url( $link ), 
                'Debug Feed '.$quick_link_icon
            ) 
        ] );
        return $actions;
    } // End gf_entry_quick_link()
}