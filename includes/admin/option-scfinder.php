<?php include 'header.php'; ?>

<?php 
// Build the current url
$page = ddtt_plugin_options_short_path();
$tab = 'scfinder';
$current_url = ddtt_plugin_options_path( $tab );

// Hidden inputs
$hidden_allowed_html = [
    'input' => [
        'type'      => [],
        'name'      => [],
        'value'     => []
    ],
];
$hidden_path = '<input type="hidden" name="page" value="'.$page.'">
<input type="hidden" name="tab" value="'.$tab.'">';

// Get the shortcode we are searching for
if ( ddtt_get( 'shortcode' ) ) {
    $shortcode = ddtt_get( 'shortcode' );
} else {
    $shortcode = '';
}

// Get the attribute info
if ( ddtt_get( 'attr' ) ) {
    $attr = esc_attr( ddtt_get( 'attr' ) );
} else {
    $attr = '';
}
if ( ddtt_get( 'attr_is' ) ) {
    $attr_is = esc_attr( ddtt_get( 'attr_is' ) );
} else {
    $attr_is = '';
}

// If we have a search keyword
$notice = false;

// Check if we are searching
if ( $shortcode != '' ) {

    // Set the param
    if ( $attr != '' && $attr_is != '') {
        $param = ' '.$attr.'="'.$attr_is.'"';
    } else {
        $param = '';
    }

    // Store the results here
    $results = [];

    // Shortcode string
    $shortcode_string = '['.$shortcode.$param.']';

    // Get the post types
    $post_types = get_post_types();

    // Exclude post types
    $exclude = apply_filters( 'ddtt_shortcode_finder_exclude_post_types', [
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
        'x-portfolio'
    ] );

    // Iter the post types
    foreach ( $post_types as $key => $post_type ) {
        if ( in_array( $post_type, $exclude ) ) {
            unset( $post_types[ $key ] );
        }
    }

    // Let's get the posts
    $the_query = new WP_Query( [ 
        'post_type' => $post_types,
        'posts_per_page' => -1,  
    ] );
    if ( $the_query->have_posts() ) {

        // Let's build the full shortcode we are looking for
        $full_shortcode_prefix = '['.$shortcode.$param;

        // For each list item...
        while ( $the_query->have_posts() ) {

            // Get the post
            $the_query->the_post();

            // Get the post content once
            $content = get_the_content();
            // $content = apply_filters( 'the_content', get_the_content() );

            // Allow filtering of the content
            $content = apply_filters( 'ddtt_shortcode_finder_exclude_post_types', $content );

            // Check if the content has this shortcode
            if ( strpos( $content, $full_shortcode_prefix ) !== false ) {

                // Post type and status
                $post_type = get_post_type();
                $post_type_obj = get_post_type_object( $post_type );
                if ( $post_type_obj ) {
                    $pt_name = esc_html( $post_type_obj->labels->singular_name ).' ';
                } else {
                    $pt_name = '';
                }

                // Post status
                $post_status = get_post_status();
                if ( $post_status == 'publish' ) {
                    $current_status = 'Published';
                } elseif ( $post_status == 'draft' ) {
                    $current_status = 'Draft';
                } elseif ( $post_status == 'private' ) {
                    $current_status = 'Private';
                } elseif ( $post_status == 'archive' ) {
                    $current_status = 'Archived';
                } else {
                    $current_status = 'Unknown';
                }

                // Add the result
                $results[] = [
                    'title' => get_the_title(),
                    'id' => get_the_ID(),
                    'url' => get_the_permalink(),
                    'post_type' => $pt_name,
                    'post_status' => $current_status
                ];
            }
        }

        // If none found
        if ( empty( $results ) ) {
                
            // Return no shortcode
            $notice = 'Shortcode <code>'.$shortcode_string.'</code> not found on the following post types:<br>'.implode( ', ', $post_types );
        }

    } else {
        
        // No posts found
        $notice = 'No posts found';
    }

    // Restore original post data
    wp_reset_postdata();
}

// Are we returning a notice?
if ( $notice ) {
    ?>
    <div class="notice notice-error is-dismissible">
    <p><?php _e( $notice, 'dev-debug-tools' ); ?></p>
    </div>
    <?php
}

// Add a notice if we found something
if ( !empty( $results ) ) {
    $s = count( $results ) == 1 ? '' : 's';
    ?>
    <div class="notice notice-success is-dismissible">
    <p><?php _e( 'Found '.count( $results ).' result'.$s.'. Scroll down to view.', 'dev-debug-tools' ); ?></p>
    </div>
    <?php
}

?>

<p>Example: <code class="hl">[shortcode_name attribute="attribute value"]</code></p>

<form method="get" action="<?php echo esc_url( $current_url ); ?>">
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><label for="find-shortcode-input">Shortcode Name<br><em>Do not include brackets</em></label></th>
            <td><input type="text" name="shortcode" id="find-shortcode-input" value="<?php echo esc_attr( $shortcode ); ?>" required></td>
        </tr>
        <tr valign="top">
            <th scope="row"><label for="find-shortcode-attr-input">Attribute</label></th>
            <td><input type="text" name="attr" id="find-shortcode-attr-input" value="<?php echo esc_attr( $attr ); ?>"></td>
        </tr>
        <tr valign="top">
            <th scope="row"><label for="find-shortcode-attr-value-input">Attribute Value</label></th>
            <td><input type="text" name="attr_is" id="find-shortcode-attr-value-input" value="<?php echo esc_attr( $attr_is ); ?>"></td>
        </tr>
    </table>
    <?php echo wp_kses( $hidden_path, $hidden_allowed_html ); ?>
    <br><br><input type="submit" value="Search" id="find-shortcode-button" class="button button-primary"/>
</form>
<br><br>

<?php
// Continue if shortcode is found
if ( $shortcode != '' && !empty( $results ) ) {

    // Sort them
    sort( $results );
    ?>

    <!-- The table -->
    <br><br><hr><br></br>
    <h2><?php echo absint( count( $results ) ); ?> Result<?php echo esc_attr( $s ); ?> Found:</h2>
    <div class="full_width_container">
        <table class="admin-large-table">
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Post Type</th>
                <th>Post Status</th>
            </tr>
            <?php
            foreach( $results as $result ) {
                ?>
                <tr>
                    <td><?php echo esc_attr( $result[ 'id' ] ); ?></td>
                    <td><a href="<?php echo esc_url( $result[ 'url' ] ) ?>" target="_blank"><?php echo esc_html( $result[ 'title' ] ); ?></a></td>
                    <td><?php echo esc_html( $result[ 'post_type' ] ); ?></td>
                    <td><?php echo esc_html( $result[ 'post_status' ] ); ?></td>
                </tr>
                <?php
            }
            ?>
        </table>
    </div>
    <?php

// Otherwise we are going to list all available shortcodes
} else {

    // Get the shortcodes
    global $shortcode_tags;

    // Sort the shortcodes with alphabetical order
    ksort( $shortcode_tags );
    ?>

    <!-- The table -->
    <br><br><hr><br></br>
    <h2>Available Shortcodes</h2>
    <div class="full_width_container">
        <table class="admin-large-table">
            <tr>
                <th>Shortcode</th>
            </tr>
            <?php
            foreach( $shortcode_tags as $sc => $value ) {
                ?>
                <tr>
                    <td><a href="<?php echo esc_url( $current_url ) ?>&shortcode=<?php echo esc_attr( $sc ); ?>">[<?php echo esc_attr( $sc ); ?>]</a></td>
                </tr>
                <?php
            }
            ?>
        </table>
    </div>
    <?php
}