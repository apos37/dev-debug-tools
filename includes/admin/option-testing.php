<?php 
// Page header
include 'header.php';

// Include helpers
ddtt_testing_playground_helpers();

// Filename of the testing playground
$filename = 'TESTING_PLAYGROUND.php';

// File paths
$local_file_path = ABSPATH.str_replace( site_url( '/' ), '', content_url() ).'/'. $filename;
$plugin_file_path = DDTT_PLUGIN_ROOT.$filename;

// Check if there is a local playground
if ( is_readable( $local_file_path ) ) {
    $file = $local_file_path;

// Otherwise make sure the plugin's playground is available
} elseif ( is_readable( $plugin_file_path ) ) {
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
    $wpconfig = file_get_contents( $file );

    // Separate each line into an array item
    $file_lines = explode( PHP_EOL, $wpconfig );

    // Test line
    $test_line = 'TEST YOUR PHP BELOW';

    // Search for test material
    $last_key = 0;
    $found_test_material = false;
    foreach( $file_lines as $key => $file_line ) {
        $line = htmlentities( $file_line );

        // Search for the testing comments
        if ( strpos( $line, $test_line ) !== false ) {
            $last_key = $key;
        }

        // If the last key has been identified
        if ( $last_key > 0 && $key > $last_key ) {

            // Check if there are any lines that do not start with comments
            if ( ( str_starts_with( $line, '//' ) === false ) && 
                 ( str_starts_with( $line, ' //' ) === false ) && 
                 strlen( $line ) > 0 &&
                 !ctype_space( $line ) ) {
                    
                // Stop here because we found some test material
                $found_test_material = true;
                break;
            }
        }
    }

    // If we did not find test material
    if ( !$found_test_material ) {

        // Add instructions
        echo '<div class="snippet_container">
            <h3>How to use this page as a PHP playground:</h3>
            <br>
            <h4>Method 1</h4>
            <br>1. Go to the <a href="/'.esc_attr( DDTT_ADMIN_URL ).'/plugin-editor.php?file='.esc_attr( DDTT_TEXTDOMAIN ).'%2FTESTING_PLAYGROUND.php&plugin='.esc_attr( DDTT_TEXTDOMAIN ).'%2F'.esc_attr( DDTT_TEXTDOMAIN ).'.php" target="_blank">Plugin File Editor</a>, or use FTP to access the plugin root folder:
            <br><strong><code>/'.esc_attr( DDTT_PLUGINS_URL ).'/'. esc_attr( DDTT_TEXTDOMAIN ) .'/</code></strong>
            <br><br>2. Open the <strong><code>"'.esc_attr( $filename ).'"</code></strong> file
            <br><br>3. Edit the file by adding your test code <strong>AFTER</strong> where it says:
            <span class="comment-out">
            <br>//////////////  '.esc_html( $test_line ).'  //////////////
            <br>//////////////  '.esc_html( $test_line ).'  //////////////
            <br>//////////////  '.esc_html( $test_line ).'  //////////////
            <br>//////////////  '.esc_html( $test_line ).'  //////////////
            </span>
            <br><br><em>Note: Do NOT delete these comment lines, or else these instructions will continue to show during testing.</em>
            <br><br>4. Save the file and refresh this page.

            <br><br><hr>
            <h4>Method 2</h4>
            Do the same thing as Method 1, but download the file below and upload it to your <strong><code>/'.esc_attr( DDTT_CONTENT_URL ).'/</code></strong> folder instead.<br>The benefit of doing it this way is that it won\'t reset when the plugin is updated.
            <br><br>
            <form method="post">
                <input type="submit" value="Download '.esc_attr( $filename ).'" name="ddtt_download_testing_pg" class="button button-primary"/>
            </form>
        </div>';
    }

// Echo and error
} else {
    echo 'Uh oh! The '.esc_attr( $filename ).' file is missing.';
}
?>