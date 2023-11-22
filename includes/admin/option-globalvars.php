<?php include 'header.php'; 

// Build the current url
$page = ddtt_plugin_options_short_path();
$tab = 'globalvars';
$current_url = ddtt_plugin_options_path( $tab );

// Global variables
$globals = [
    [ 
        'section' => 'loop', 
        'var'     => 'post', 
        'returns' => 'WP_Post',
        'desc'    => 'The post object for the current post.',
        'avail'   => false
    ],
    [ 
        'section' => 'loop', 
        'var'     => 'authordata', 
        'returns' => 'WP_User',
        'desc'    => 'The author object for the current post.',
        'avail'   => false
    ],
    [ 
        'section' => 'loop', 
        'var'     => 'currentday', 
        'returns' => 'string',
        'desc'    => 'Day that the current post was published.',
        'avail'   => false
    ],
    [ 
        'section' => 'loop', 
        'var'     => 'currentmonth', 
        'returns' => 'string',
        'desc'    => 'Month that the curent post was published.',
        'avail'   => false
    ],
    [ 
        'section' => 'loop', 
        'var'     => 'page', 
        'returns' => 'int',
        'desc'    => 'The page of the current post being viewed. Specified by the query var page.',
        'avail'   => false
    ],
    [ 
        'section' => 'loop', 
        'var'     => 'pages', 
        'returns' => 'array',
        'desc'    => 'The content of the pages of the current post. Each page elements contains part of the content separated by the \<!--nextpage--\> tag.',
        'avail'   => false
    ],
    [ 
        'section' => 'loop', 
        'var'     => 'multipage', 
        'returns' => 'boolean',
        'desc'    => 'Flag to know if the current post has multiple pages or not. Returns true if the post has multiple pages, related to $pages.',
        'avail'   => false
    ],
    [ 
        'section' => 'loop', 
        'var'     => 'more', 
        'returns' => 'boolean',
        'desc'    => 'Flag to know if WordPress should enforce the \<!--more--\> tag for the current post. WordPress will not enforce the more tag if true.',
        'avail'   => false
    ],
    [ 
        'section' => 'loop', 
        'var'     => 'numpages', 
        'returns' => 'int',
        'desc'    => 'Returns the number of pages in the post, related to $pages.',
        'avail'   => false
    ],
    [ 
        'section' => 'browser', 
        'var'     => 'is_iphone', 
        'returns' => 'boolean',
        'desc'    => 'iPhone Safari',
        'avail'   => true
    ],
    [ 
        'section' => 'browser', 
        'var'     => 'is_chrome', 
        'returns' => 'boolean',
        'desc'    => 'Google Chrome',
        'avail'   => true
    ],
    [ 
        'section' => 'browser', 
        'var'     => 'is_safari', 
        'returns' => 'boolean',
        'desc'    => 'Safari',
        'avail'   => true
    ],
    [ 
        'section' => 'browser', 
        'var'     => 'is_NS4', 
        'returns' => 'boolean',
        'desc'    => 'Netscape 4',
        'avail'   => true
    ],
    [ 
        'section' => 'browser', 
        'var'     => 'is_opera', 
        'returns' => 'boolean',
        'desc'    => 'Opera',
        'avail'   => true
    ],
    [ 
        'section' => 'browser', 
        'var'     => 'is_macIE', 
        'returns' => 'boolean',
        'desc'    => 'Mac Internet Explorer',
        'avail'   => true
    ],
    [ 
        'section' => 'browser', 
        'var'     => 'is_winIE', 
        'returns' => 'boolean',
        'desc'    => 'Windows Internet Explorer',
        'avail'   => true
    ],
    [ 
        'section' => 'browser', 
        'var'     => 'is_gecko', 
        'returns' => 'boolean',
        'desc'    => 'FireFox',
        'avail'   => true
    ],
    [ 
        'section' => 'browser', 
        'var'     => 'is_lynx', 
        'returns' => 'boolean',
        'desc'    => '',
        'avail'   => true
    ],
    [ 
        'section' => 'browser', 
        'var'     => 'is_IE', 
        'returns' => 'boolean',
        'desc'    => 'Internet Explorer',
        'avail'   => true
    ],
    [ 
        'section' => 'browser', 
        'var'     => 'is_edge', 
        'returns' => 'boolean',
        'desc'    => 'Microsoft Edge',
        'avail'   => true
    ],
    [ 
        'section' => 'server', 
        'var'     => 'is_apache', 
        'returns' => 'boolean',
        'desc'    => 'Apache HTTP Server',
        'avail'   => true
    ],
    [ 
        'section' => 'server', 
        'var'     => 'is_IIS', 
        'returns' => 'boolean',
        'desc'    => 'Microsoft Internet Information Services (IIS)',
        'avail'   => true
    ],
    [ 
        'section' => 'server', 
        'var'     => 'is_iis7', 
        'returns' => 'boolean',
        'desc'    => 'Microsoft Internet Information Services (IIS) v7.x',
        'avail'   => true
    ],
    [ 
        'section' => 'server', 
        'var'     => 'is_nginx', 
        'returns' => 'boolean',
        'desc'    => 'Nginx web server',
        'avail'   => true
    ],
    [ 
        'section' => 'versions', 
        'var'     => 'wp_version', 
        'returns' => 'string',
        'desc'    => 'The installed version of WordPress',
        'avail'   => true
    ],
    [ 
        'section' => 'versions', 
        'var'     => 'wp_db_version', 
        'returns' => 'int',
        'desc'    => 'The version number of the database',
        'avail'   => true
    ],
    [ 
        'section' => 'versions', 
        'var'     => 'tinymce_version', 
        'returns' => 'string',
        'desc'    => 'The installed version of TinyMCE',
        'avail'   => true
    ],
    [ 
        'section' => 'versions', 
        'var'     => 'manifest_version', 
        'returns' => 'string',
        'desc'    => 'The cache manifest version',
        'avail'   => true
    ],
    [ 
        'section' => 'versions', 
        'var'     => 'required_php_version', 
        'returns' => 'string',
        'desc'    => 'The version of PHP this install of WordPress requires',
        'avail'   => true
    ],
    [ 
        'section' => 'versions', 
        'var'     => 'required_mysql_version', 
        'returns' => 'string',
        'desc'    => 'The version of MySQL this install of WordPress requires',
        'avail'   => true
    ],
    [ 
        'section' => 'misc', 
        'var'     => 'super_admins', 
        'returns' => 'array',
        'desc'    => 'An array of user IDs that should be granted super admin privileges (multisite). This global is only set by the site owner (e.g., in wp-config.php), and contains an array of IDs of users who should have super admin privileges. If set it will override the list of super admins in the database.',
        'avail'   => true
    ],
    [ 
        'section' => 'misc', 
        'var'     => 'wp_query', 
        'returns' => 'object',
        'desc'    => 'The global instance of the Class_Reference/WP_Query class.',
        'avail'   => true
    ],
    [ 
        'section' => 'misc', 
        'var'     => 'wp_rewrite', 
        'returns' => 'object',
        'desc'    => 'The global instance of the Class_Reference/WP_Rewrite class.',
        'avail'   => true
    ],
    [ 
        'section' => 'misc', 
        'var'     => 'wp', 
        'returns' => 'object',
        'desc'    => 'The global instance of the Class_Reference/WP class.',
        'avail'   => true
    ],
    [ 
        'section' => 'misc', 
        'var'     => 'wpdb', 
        'returns' => 'object',
        'desc'    => 'The global instance of the Class_Reference/wpdb class.',
        'avail'   => true
    ],
    [ 
        'section' => 'misc', 
        'var'     => 'wp_locale', 
        'returns' => 'object',
        'desc'    => '',
        'avail'   => true
    ],
    [ 
        'section' => 'misc', 
        'var'     => 'wp_admin_bar', 
        'returns' => 'WP_Admin_Bar',
        'desc'    => '',
        'avail'   => true
    ],
    [ 
        'section' => 'misc', 
        'var'     => 'wp_roles', 
        'returns' => 'WP_Roles',
        'desc'    => '',
        'avail'   => true
    ],
    [ 
        'section' => 'misc', 
        'var'     => 'wp_meta_boxes', 
        'returns' => 'array',
        'desc'    => 'Object containing all registered metaboxes, including their id\'s, args, callback functions and title for all post types including custom.',
        'avail'   => true
    ],
    [ 
        'section' => 'misc', 
        'var'     => 'wp_registered_sidebars', 
        'returns' => 'array',
        'desc'    => '',
        'avail'   => true
    ],
    [ 
        'section' => 'misc', 
        'var'     => 'wp_registered_widgets', 
        'returns' => 'array',
        'desc'    => '',
        'avail'   => true
    ],
    [ 
        'section' => 'misc', 
        'var'     => 'wp_registered_widget_controls', 
        'returns' => 'array',
        'desc'    => '',
        'avail'   => true
    ],
    [ 
        'section' => 'misc', 
        'var'     => 'wp_registered_widget_updates', 
        'returns' => 'array',
        'desc'    => '',
        'avail'   => true
    ],
    [ 
        'section' => 'admin', 
        'var'     => 'pagenow', 
        'returns' => 'string',
        'desc'    => 'Used in wp-admin to return the current .php page',
        'avail'   => true,
    ],
    [ 
        'section' => 'admin', 
        'var'     => 'post_type', 
        'returns' => 'string',
        'desc'    => 'Used in wp-admin on post pages to return the post type.',
        'avail'   => false
    ],
    [ 
        'section' => 'admin', 
        'var'     => 'allowedposttags', 
        'returns' => 'array',
        'desc'    => 'Used by the wp_kses library to sanitize posts. A white list of html tags and attributes that WordPress allows in posts.',
        'avail'   => true
    ],
    [ 
        'section' => 'admin', 
        'var'     => 'allowedtags', 
        'returns' => 'array',
        'desc'    => 'Used by the wp_kses library to sanitize post comments. A white list of html tags and attributes that WordPress allows in comments.',
        'avail'   => true
    ],
    [ 
        'section' => 'admin', 
        'var'     => 'menu', 
        'returns' => 'array',
        'desc'    => 'The admin menu.',
        'avail'   => true
    ],
    [ 
        'section' => 'admin', 
        'var'     => 'submenu', 
        'returns' => 'array',
        'desc'    => 'The admin submenus.',
        'avail'   => true
    ],
];

// Sections
$sections = [
    'admin'    => 'Admin Globals',
    'versions' => 'Version Variables',
    'server'   => 'Web Server Detection Booleans',
    'browser'  => 'Browser Detection Booleans',
    'misc'     => 'Miscellaneous',
    'loop'     => 'Inside the Loop variables'
];

// Check if we are viewing one
if ( $gv = ddtt_get( 'gv' ) ) {

    // Attempt to get it
    global ${$gv};

    // Attempt to print it
    if ( ${ddtt_get( 'gv' )} ) {
        echo '<br><h3>$'.esc_attr( $gv ).' returns:</h3><br>';
        ddtt_print_r( ${$gv} );

    } else {
        echo '$'.esc_attr( $gv ).' is not available on this page or does not exist.';
    }

    // Add some space
    echo '<br><br><hr><br><br>';
}

// Return the table
echo '<div class="full_width_container">
    <table class="admin-large-table">
        <tr>
            <th>Global Variable</th>
            <th>Returns (click to print available arrays)</th>
            <th>Type</th>
            <th width="50%">Description</th>
        </tr>';

        // Cycle through the options
        foreach( $globals as $global ) {

            // Not available
            $na = '';

            // Available for print
            $print_returns = [
                'array',
                'object',
                'WP_Admin_Bar',
                'WP_Roles'
            ];

            // If available for print
            if ( isset( $global[ 'avail' ] ) && $global[ 'avail' ] == true && in_array( $global[ 'returns' ], $print_returns ) ) {
                $returns = '<code>(<a href="'.$current_url.'&gv='.$global[ 'var' ].'">Array</a>)</code>';

            // If an available non-array
            } elseif ( isset( $global[ 'avail' ] ) && $global[ 'avail' ] == true && !in_array( $global[ 'returns' ], $print_returns ) ) {

                // Attempt to get it
                global ${$global[ 'var' ]};

                // Attempt to print it
                if ( ${$global[ 'var' ]} ) {
                    $returns = '<code>('.ucwords( $global[ 'returns' ] ).')</code> => '.${$global[ 'var' ]};
                } else {
                    $returns = '<code>('.ucwords( $global[ 'returns' ] ).')</code> '.$na;
                }

            // Otherwise
            } else {
                $returns = '<code>('.ucwords( $global[ 'returns' ] ).')</code> '.$na;
            }

            echo '<tr>
                <td><span class="highlight-variable">$'.esc_attr( $global[ 'var' ] ).'</span></td>
                <td>'.wp_kses_post( $returns ).'</td>
                <td>'.esc_html( isset( $sections[ $global[ 'section' ] ] ) ? $sections[ $global[ 'section' ] ] : $global[ 'section' ] ).'</td>
                <td>'.esc_html( $global[ 'desc' ] ).'</td>
            </tr>';
        }

echo '</table>
</div>';