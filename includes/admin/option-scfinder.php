<style>
.admin-large-table th {
    text-align: center;
}
</style>

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
    if ( $attr != '' && $attr_is != '' ) {
        $param = $attr.'="'.$attr_is.'"';
    } else {
        $param = '';
    }

    // Store the results here
    $results = [];

    // Shortcode string
    $shortcode_string = '['.$shortcode.' '.$param.']';

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
        $full_shortcode_prefix = '['.$shortcode;

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
            // if ( strpos( $content, $full_shortcode_prefix ) !== false ) {
            $shortcode_regex = '/'.get_shortcode_regex( [ $shortcode ] ).'/';
            if ( preg_match_all( $shortcode_regex, $content, $matches ) ) {

                // Count
                $count = count( $matches[0] );

                // Filter if attribute doesn't match
                $stop = false;

                // Check if we are searching for attribute as well
                if ( $attr != '' && $attr_is != '' ) {

                    // Let's assume we don't find it
                    $stop = true;

                    // Verify we found matches
                    if ( isset( $matches[3] ) && !empty( $matches[3] ) ) {

                        // Iter the matches
                        foreach ( $matches[3] as $match ) {

                            // Check if it matches
                            if ( strpos( strtolower( $match ), strtolower( $param ) ) !== false ) {

                                // Yes? Then let's not stop after all
                                $stop = false;
                            }
                        }
                    }
                }

                // Do we need to stop?
                if ( !$stop ) {

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
                        'post_status' => $current_status,
                        'count' => $count
                    ];
                }
            }
        }

        // If none found
        if ( empty( $results ) ) {
                
            // Return no shortcode
            /* Translators: 1: shortcode */
            $notice = sprintf( __( 'Shortcode <code>%s</code> not found on the following post types:', 'dev-debug-tools' ), $shortcode_string ).'<br>'.implode( ', ', $post_types );
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
        <p><?php echo wp_kses( $notice, [ 'code' => [], 'br' => [] ] ); ?></p>
    </div>
    <?php
}

// Add a notice if we found something
if ( !empty( $results ) ) {
    $s = count( $results ) == 1 ? '' : 's';
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php 
        /* Translators: 1: count, 2: results */
        $notice = sprintf( __( 'Found %1$s %2$s. Scroll down to view.', 'dev-debug-tools' ), count( $results ), 'result'.$s );
        echo esc_html( $notice ); ?></p>
    </div>
    <?php
}

// Get available shortcodes
global $shortcode_tags;

// Sort the shortcodes with alphabetical order
ksort( $shortcode_tags );
?>

<p>Example: <code class="hl">[shortcode attribute="value"]</code></p>

<form method="get" action="<?php echo esc_url( $current_url ); ?>">
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><label for="find-shortcode-input">Shortcode</label></th>
            <td><select name="shortcode" id="find-shortcode-input" required>
                <option></option>
                <?php 
                foreach ( $shortcode_tags as $sc => $value ) {
                    ?>
                    <option value="<?php echo esc_attr( $sc ); ?>"<?php echo esc_attr( ddtt_is_qs_selected( $sc, $shortcode ) ); ?>>[<?php echo esc_attr( $sc ); ?>]</option>
                    <?php
                }
                ?>
            </select>
            </td>
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
                <th>Quantity</th>
            </tr>
            <?php
            foreach( $results as $result ) {
                ?>
                <tr>
                    <td><?php echo esc_attr( $result[ 'id' ] ); ?></td>
                    <td style="text-align: left;"><a href="<?php echo esc_url( $result[ 'url' ] ) ?>" target="_blank"><?php echo esc_html( $result[ 'title' ] ); ?></a></td>
                    <td><?php echo esc_html( $result[ 'post_type' ] ); ?></td>
                    <td><?php echo esc_html( $result[ 'post_status' ] ); ?></td>
                    <td><?php echo absint( $result[ 'count' ] ); ?></td>
                </tr>
                <?php
            }
            ?>
        </table>
    </div>
    <?php

// Otherwise we are going to list all available shortcodes
} else {

    // Plural or singular
    $as = count( $shortcode_tags ) == 1 ? '' : 's';

    // Get all active plugins
    $active_plugins = get_option( 'active_plugins' );

    // Get all themes
    $themes = wp_get_themes();
    ?>

    <!-- The table -->
    <br><br><hr><br></br>
    <h2><?php echo absint( count( $shortcode_tags ) ); ?> Available Shortcode<?php echo esc_attr( $as ); ?></h2>
    <div class="full_width_container">
        <table class="admin-large-table">
            <tr>
                <th>Shortcode</th>
                <th>Source</th>
            </tr>
            <?php
            // Get the admin url
            if ( is_multisite() ) {
                $admin_url = str_replace( site_url( '/' ), '', rtrim( network_admin_url(), '/' ) );
            } else {
                $admin_url = DDTT_ADMIN_URL;
            }

            // Iter the tags
            foreach ( $shortcode_tags as $sc => $callback ) {

                // Log errors
                $errors = [];
                $source = '';

                // Attempt to process each shortcode callback
                try {
                    // Initialize $fx variable
                    $fx = null;
            
                    // Avoid callbacks that are arrays
                    if ( is_array( $callback ) ) {
                        if ( count( $callback ) >= 2 ) {
                            $class_or_object = $callback[0];
                            $method_name = $callback[1];
            
                            // Check if $class_or_object is a valid class name or object instance
                            if ( is_string( $class_or_object ) && class_exists( $class_or_object ) ||
                                 ( is_object( $class_or_object ) && is_callable( [$class_or_object, $method_name] ) ) ) {
            
                                // Check if method exists in class or object
                                if ( method_exists( $class_or_object, $method_name ) ) {
                                    $fx = new ReflectionMethod( $class_or_object, $method_name );
                                }
                            }
                        }
            
                    } elseif ( is_string( $callback ) && function_exists( $callback ) ) {

                        // If $callback is a string and represents a function
                        $fx = new ReflectionFunction( $callback );
                    }
            
                    // Proceed if $fx is successfully initialized
                    if ( $fx ) {

                        // Get the file path of the callback function/method
                        $file_path = ddtt_relative_pathname( $fx->getFileName() );

                        // Line number
                        $line = $fx->getStartLine();

                        // Check if it's a plugin
                        if ( strpos( $file_path, DDTT_PLUGINS_URL ) !== false ) {

                            // If so, get the plugin slug
                            $plugin_path_and_filename = str_replace( DDTT_PLUGINS_URL, '', $file_path );
                            $plugin_path_parts = explode( '/', $plugin_path_and_filename );
                            $plugin_slug = $plugin_path_parts[1];
                            $plugin_filename = substr( $plugin_path_and_filename, strpos( $plugin_path_and_filename, '/' ) + 1 );

                            // Now check the active plugins for the file
                            $plugin_folder_and_file = false;
                            foreach( $active_plugins as $ap ) {
                                if ( str_starts_with( $ap, $plugin_slug ) ) {
                                    $plugin_folder_and_file = $ap;
                                }
                            }

                            // Make sure we found the file
                            if ( $plugin_folder_and_file ) {

                                // Require the get_plugin_data function
                                if ( !function_exists( 'get_plugin_data' ) ) {
                                    require_once( ABSPATH.DDTT_ADMIN_URL.'/includes/plugin.php' );
                                }

                                // Get the file
                                $plugin_file = ABSPATH.DDTT_PLUGINS_URL.'/'.$plugin_folder_and_file;

                                // Get the plugin data
                                $plugin_data = get_plugin_data( $plugin_file );

                                // This is what we will display
                                $include = '<strong>Plugin:</strong> '.$plugin_data[ 'Name' ].'<br>';

                                // Make sure editors are not disabled
                                if ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT ) {
                                        
                                    // Update short file path link
                                    $file_path = esc_attr( $file_path );

                                } else {
                                    
                                    // Update short file path link
                                    $file_path = '<a href="/'.esc_attr( $admin_url ).'/plugin-editor.php?file='.esc_attr( urlencode( $plugin_filename ) ).'&plugin='.esc_attr( $plugin_slug ).'%2F'.esc_attr( $plugin_slug ).'.php&line='.esc_attr( $line ).'" target="_blank">'.esc_attr( $file_path ).'</a>';
                                }
                            }

                        // Check if it's a theme file
                        } elseif ( strpos( $file_path, DDTT_CONTENT_URL.'/themes/' ) !== false ) {

                            // Theme parts
                            $theme_parts = explode( '/', $file_path );
                            $theme_filename = $theme_parts[3];
                            $theme_slug = $theme_parts[2];

                            // Check if the themes exists in the array
                            $theme_name = 'Unknown';
                            foreach ( $themes as $k => $t ) {
                                if ( $k == $theme_slug ) {
                                    $theme_name = $t->get( 'Name' );
                                }
                            }

                            // This is what we will display
                            $include = '<strong>Theme:</strong> '.$theme_name.'<br>';

                            // Make sure editors are not disabled
                            if ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT ) {
                                    
                                // Update short file path link
                                $file_path = esc_attr( $file_path );

                            } else {
                                
                                // Update short file path link
                                $file_path = '<a href="/'.esc_attr( $admin_url ).'/theme-editor.php?file='.esc_attr( urlencode( $theme_filename ) ).'&theme='.esc_attr( $theme_slug ).'&line='.esc_attr( $line ).'" target="_blank">'.esc_attr( $file_path ).'</a>';
                            }
                        }

                        // Add the line number
                        $short_file_path = $file_path.'<br><strong>Line:</strong> '.$line;

                        // Source
                        $source = $include.'<strong>Path: </strong>/'.$short_file_path;

                    } else {
                        // Handle cases where $fx couldn't be initialized
                        $errors[] = "Invalid format or non-existent function/method.";
                    }
            
                } catch ( Exception $e ) {
                    // Log or handle any exceptions (if necessary)
                    $errors[] = "Error processing shortcode callback: " . $e->getMessage();
                }

                // Errors
                if ( !empty( $errors ) ) {
                    $source = implode( '<br>', $errors );
                }

                // Return the row
                ?>
                <tr>
                    <td><a href="<?php echo esc_url( $current_url ) ?>&shortcode=<?php echo esc_attr( $sc ); ?>">[<?php echo esc_attr( $sc ); ?>]</a></td>
                    <td><?php echo wp_kses_post( $source ); ?><br></td>
                </tr>
                <?php
            }
            ?>
        </table>
    </div>
    <?php
}
