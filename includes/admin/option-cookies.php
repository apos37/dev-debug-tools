<?php include 'header.php'; ?>

<?php 
// The current url
$current_url = ddtt_plugin_options_path( 'cookies' );
?>

<p><strong>What are cookies?</strong> Cookies are small pieces of text sent to your browser by a website you visit. They help that website remember information about your visit, which can both make it easier to visit the site again and make the site more useful to you.</p>

<a href="<?php echo esc_url( $current_url ); ?>&clear=true" class="button secondary-button" onclick="document.cookie.split(';').forEach(function(c) { document.cookie = c.replace(/^ +/, '').replace(/=.*/, '=;expires=' + new Date().toUTCString() + ';path=/'); });">Clear all my browser cookies!</a>

<a class="button secondary-button" onclick="event.preventDefault(); console.log( localStorage ); localStorage.clear(); alert('Local storage cleared!');">Clear all my browser local storage!</a>

<br><br>
<?php
// Get the cookies and sort them
$cookies = $_COOKIE;
ksort( $cookies );

// Are we clearing cookies
if ( ddtt_get( 'clear', '==', 'true' ) ) {
    
    // Remove the param
    ddtt_remove_qs_without_refresh( 'clear' );

    // Iter the cookies
    foreach ( $cookies as $key => $cookie ) {

        // Unset the cookie
        setcookie( $key, '', time() - 3600);
    }
    ?>
    <div class="notice notice-success is-dismissible">
    <p><?php _e( 'Cookies have been cleared. Note that some cookies are renewed upon page load, so it might seem like they were not cleared if only those ones were set to begin with.', 'dev-debug-tools' ); ?></p>
    </div>
    <?php
}

// Return the table
echo '<div class="full_width_container">
    <table class="admin-large-table">
        <tr>
            <th>Cookie Name</th>
            <th>Value</th>
        </tr>';

        // Cycle through the options
        foreach( $cookies as $key => $cookie ) {
            echo '<tr>
                <td>'.esc_attr( $key ).'</td>
                <td>'.esc_html( $cookie ).'</td>
            </tr>';
        }

echo '</table>
</div>';
?>
<br>
<p>Note: Dots (.) and spaces ( ) in cookie names are being replaced with underscores (_).</p>