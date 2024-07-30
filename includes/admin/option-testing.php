<?php 
// Page header
include 'header.php';

// Get the file
if ( !function_exists( 'WP_Filesystem' ) ) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
}
global $wp_filesystem;
if ( !WP_Filesystem() ) {
    ddtt_write_log( 'Failed to initialize WP_Filesystem' );
    return false;
}

// Filename of the testing playground
$filename = 'TESTING_PLAYGROUND.php';

// File paths
$local_file_path = get_stylesheet_directory().'/'. $filename;
$local_file_path2 = ABSPATH.str_replace( site_url( '/' ), '', content_url() ).'/'. $filename;
$plugin_file_path = DDTT_PLUGIN_INCLUDES_PATH.$filename;

// Check if there is a local playground in theme folder
if ( $wp_filesystem->exists( $local_file_path ) ) {
    $file = $local_file_path;

// Check if there is a local playground in /wp-content/
} elseif ( $wp_filesystem->exists( $local_file_path2 ) ) {
    $file = $local_file_path2;

// Otherwise make sure the plugin's playground is available
} elseif ( $wp_filesystem->exists( $plugin_file_path ) ) {
    $file = $plugin_file_path;

// Else we failed
} else {
    $file = false;
}

// Check if the file exists
if ( $file ) {

    // Include the file regardless
    include $file;

    // Get the file
    $contents = $wp_filesystem->get_contents( $file );

    // Separate each line into an array item
    $file_lines = explode( PHP_EOL, $contents );

    // Test line
    $test_line = 'TEST YOUR PHP BELOW';

    // Search for test material
    $last_key = 0;
    $found_test_line = false;
    $found_test_material = false;
    foreach( $file_lines as $key => $file_line ) {
        $line = htmlentities( $file_line );

        // Search for the testing comments
        if ( strpos( $line, $test_line ) !== false ) {
            $last_key = $key;
        }

        // If the last key has been identified
        if ( $last_key > 0 && $key > $last_key ) {

            // Found test line
            $found_test_line = true;

            

            // Check if there are any lines that do not start with comments
            if ( ( str_starts_with( $line, '//' ) === false ) && 
                 ( str_starts_with( $line, ' //' ) === false ) && 
                 ( str_starts_with( $line, '/**' ) === false ) && 
                 ( str_starts_with( $line, ' * ' ) === false ) && 
                 ( str_starts_with( $line, ' */' ) === false ) && 
                 strlen( $line ) > 0 &&
                 !ctype_space( $line ) ) {
                      
                // Stop here because we found some test material
                $found_test_material = true;
                break;
            }
        }
    }

    // If no test line, still check if there are any lines that do not start with comments
    if ( !$found_test_material && !$found_test_line &&
         ( str_starts_with( $line, '//' ) === false ) && 
         ( str_starts_with( $line, ' //' ) === false ) && 
         strlen( $line ) > 0 &&
         !ctype_space( $line ) ) {
            
        // Stop here because we found some test material
        $found_test_material = true;
    }

    // If we did not find test material, or if we're using the wordpress playground for live preview
    if ( ddtt_get_domain() == 'playground.wordpress.net' || !$found_test_material ) {

        // Theme path
        $themes_root_uri = str_replace( site_url( '/' ), '', get_theme_root_uri() ).'/';
        $active_theme = str_replace( '%2F', '/', rawurlencode( get_stylesheet() ) );
        $active_theme_path = '/'.$themes_root_uri.$active_theme.'/';

        // Url
        $plugin_editor_path = add_query_arg( 'file', DDTT_TEXTDOMAIN.'%2Fincludes%2FTESTING_PLAYGROUND.php&plugin='.DDTT_TEXTDOMAIN.'%2F'.DDTT_TEXTDOMAIN.'.php', '/'.DDTT_ADMIN_URL.'/plugin-editor.php' );

        // Add instructions
        echo '<div class="snippet_container">
            <h3>How to use this page as a PHP playground:</h3>
            <br>
            <h4>Method 1</h4>
            
            <p>1. Use FTP or your host\'s File Manager to access the plugin root folder:<br>
            <strong><code class="hl">/'.esc_attr( DDTT_PLUGINS_URL ).'/'. esc_attr( DDTT_TEXTDOMAIN ) .'/</code></strong></p>
            
            <p>2. Open the <strong><code class="hl">"'.esc_attr( $filename ).'"</code></strong></p>
            
            <p>3. Edit the file by adding your test code <strong>AFTER</strong> where it says:
            <span class="comment-out">
            <br>//////////////  '.esc_html( $test_line ).'  //////////////
            </span></p>

            <p><em>Note: These instructions will disappear if you have any non-commented out code below this line.</em></p>
            
            <p>4. Save the file and refresh this page.</p><br><br>

            <p><strong>Here is what the file looks like (not recommended to edit directly, though): <a href="'.esc_url( $plugin_editor_path ).'" target="_blank">Plugin File Editor</a></strong></p>

            <br><br><hr>
            <h4>Method 2</h4>
            
            <p>Do the same thing as Method 1, but download the file below and upload it to your current theme\'s root folder (<strong><code class="hl">'.esc_attr( $active_theme_path ).'</code></strong>) instead.<br>The benefit of doing it this way is that it won\'t reset when the plugin is updated.</p><br>
            
            <form method="post">';
                wp_nonce_field( DDTT_GO_PF.'testing_playground_dl' );
                echo '<input type="submit" value="Download '.esc_attr( $filename ).'" name="ddtt_download_testing_pg" class="button button-primary"/>
            </form>
        </div>';
    }

// Echo and error
} else {
    echo 'Uh oh! The '.esc_attr( $filename ).' file is missing.';
}