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
    public $quick_link_icon = '&#9889';


    /**
     * Debug tab
     *
     * @var string
     */
    public $debug_tab = 'debug';


    /**
	 * Constructor
	 */
	public function __construct() {

        // Add User ID column with a link to debug the user's meta
        if ( get_option( DDTT_GO_PF.'ql_user_id' ) == '1' ) {
            add_filter( 'manage_users_columns', [ $this, 'user_column' ] );
            add_action( 'admin_head-users.php',  [ $this, 'user_column_style' ] );
            add_action( 'manage_users_custom_column', [ $this, 'user_column_content' ], 999, 3 );
        }

        // Add a link to debug the post or page's meta next to the Post ID
        if ( get_option( DDTT_GO_PF.'ql_post_id' ) == '1' ) {
            add_action( 'init', [ $this, 'admin_columns' ] );
        }

        // Add a link to comments
        if ( get_option( DDTT_GO_PF.'ql_comment_id' ) == '1' ) {
            add_filter( 'manage_edit-comments_columns', [ $this, 'comments_column' ] );
            add_action( 'admin_head-edit-comments.php',  [ $this, 'comments_column_style' ] );
            add_action( 'manage_comments_custom_column', [ $this, 'comments_column_content' ], 999, 2 );
        }

        // Add a link to debug the forml, entry or feed's meta
        if ( get_option( DDTT_GO_PF.'ql_gravity_forms') == '1' ) {

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
    public function user_column_style() {
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
     * Add to post columns
     *
     * @return void
     */
    public function admin_columns() {
        // Available post types
        $post_types = get_post_types( 
            [ 
               'public'   => true, 
            //    '_builtin' => false 
            ], 
            'names'
        );
        $post_types = apply_filters( 'ddtt_quick_link_post_types', $post_types );

        // Add to all post types
        foreach ( $post_types as $post_type ) {
            
            add_filter( 'manage_'.$post_type.'_posts_columns', [ $this, 'post_column' ] );
            add_action( 'manage_'.$post_type.'_posts_custom_column', [ $this, 'post_column_content' ], 10, 2 );
        }
        add_action( 'admin_head-edit.php',  [ $this, 'post_column_style' ] );
    } // End admin_columns()


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
    public function post_column_style() {
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
     * Add ID column to user admin page
     *
     * @param array $columns
     * @return array
     */
    public function comments_column( $columns ) {
        $columns[ strtolower( DDTT_PF ).'comment_type' ] = __( 'Type', 'dev-debug-tools' );
        $columns[ strtolower( DDTT_PF ).'comment_karma' ] = __( 'Karma', 'dev-debug-tools' );
        $columns[ strtolower( DDTT_PF ).'comment_id' ] = __( 'ID', 'dev-debug-tools' );
        return $columns;
    } // End comments_column()


    /**
     * Change width of user column
     *
     * @return void
     */
    public function comments_column_style() {
        echo '<style>
        .column-'.esc_attr( strtolower( DDTT_PF ) ).'comment_type { width: 7% }
        .column-'.esc_attr( strtolower( DDTT_PF ) ).'comment_karma { width: 5% }
        .column-'.esc_attr( strtolower( DDTT_PF ) ).'comment_id { width: 5% }
        </style>';
    } // comments_column_style()


    /**
     * Add the user column content
     *
     * @param string $column_name
     * @param int $comment_id
     * @return string
     */
    public function comments_column_content( $column_name, $comment_id ) {
        // Type
        if ( $column_name == strtolower( DDTT_PF ).'comment_type' ) {

            // The content
            echo sanitize_key( get_comment_type( $comment_id ) );

        // Karma
        } elseif ( $column_name == strtolower( DDTT_PF ).'comment_karma' ) {

            // The content
            $comment = get_comment( $comment_id );
            echo esc_attr( $comment->comment_karma );

        // ID
        } elseif ( $column_name == strtolower( DDTT_PF ).'comment_id' ) {

            // Allow icon to be filtered
            $quick_link_icon = apply_filters( 'ddtt_quick_link_icon', $this->quick_link_icon );

            // The content
            if ( ddtt_is_dev() ) {
                $link = ddtt_plugin_options_path( $this->debug_tab ).'&debug_comment='.$comment_id;
                echo absint( $comment_id ).' <a href="'.esc_url( $link ).'" target="_blank">'.$quick_link_icon.'</a>';
            } else {
                echo absint( $comment_id );
            }
        }
    } // End comments_column_content()


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
        $link = ddtt_plugin_options_path( $this->debug_tab ).'&debug_form='.$form_id;
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
        $link = ddtt_plugin_options_path( $this->debug_tab ).'&debug_entry='.$entry[ 'id' ];
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
        $url = ddtt_plugin_options_path( 'debug' );
        
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
        $link = ddtt_plugin_options_path( $this->debug_tab ).'&debug_feed='.$feed[ 'id' ];
        $actions = array_merge( $actions, [
            'edit_post_cs' => sprintf( '<a href="%1$s" target="_blank">%2$s</a>',
                esc_url( $link ), 
                'Debug Feed '.$quick_link_icon
            ) 
        ] );
        return $actions;
    } // End gf_entry_quick_link()
}