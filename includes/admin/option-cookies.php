<?php include 'header.php'; ?>

<?php 
// The current url
$current_url = ddtt_plugin_options_path( 'cookies' );
?>

<p><strong>What are cookies?</strong> Cookies are small pieces of text sent to your browser by a website you visit. They help that website remember information about your visit, which can both make it easier to visit the site again and make the site more useful to you. You can also see your cookies in your developer console under Application > Store > Cookies > <?php echo esc_url( home_url() ); ?>.</p>
<br>

<a href="<?php echo esc_url( add_query_arg( [ 'give' => 'cookie' ], $current_url ) ); ?>" class="button secondary-button" onclick="ddttAddTestCookie();">Give me a test cookie!</a>

<a href="<?php echo esc_url( add_query_arg( [ 'clear' => 'true' ], $current_url ) ); ?>" class="button secondary-button" onclick="ddttClearAllCookies();">Clear all my browser cookies!</a>

<a class="button secondary-button" onclick="ddttClearBrowserStorage(); return false;">Clear all my browser local storage!</a>

<br><br>
<?php


// Are we clearing cookies
if ( $clear_all = ddtt_get( 'clear', '==', 'true' ) ) {
    ddtt_remove_qs_without_refresh( 'clear' );
    ?>
    <div class="notice notice-success is-dismissible">
    <p><?php _e( 'Cookies have been cleared from your browser. Note that some cookies are renewed upon page load, so it might seem like they were not cleared if only those ones were set to begin with.', 'dev-debug-tools' ); ?></p>
    </div>
    <?php
} elseif ( $clear_single = ddtt_get( 'clear_single' ) ) {
    ddtt_remove_qs_without_refresh( 'clear_single' );
    ?>
    <div class="notice notice-success is-dismissible">
    <p><?php _e( 'The cookie "'.esc_attr( $clear_single ).'" has been cleared from your browser. Note that some cookies are renewed upon page load, so it might seem like it was not cleared.', 'dev-debug-tools' ); ?></p>
    </div>
    <?php
} elseif ( ddtt_get( 'give', '==', 'cookie' ) ) {
    ?>
    <div class="notice notice-success is-dismissible">
    <p><?php _e( 'You have been given a Chocolate Chip cookie. Yumm!!', 'dev-debug-tools' ); ?></p>
    </div>
    <?php
}

// Get the cookies and sort them
$cookies = $_COOKIE;
ksort( $cookies );
?>

<div class="full_width_container">
    <table class="admin-large-table">
        <tr>
            <th>Cookie Name</th>
            <th>Value</th>
            <th>Clear</th>
        </tr>
        <?php
        // Cycle through the options
        foreach( $cookies as $key => $cookie ) {
            ?>
            <tr>
                <td><span class="highlight-variable"><?php echo esc_attr( $key ); ?></span></td>
                <td><?php echo esc_html( $cookie ); ?></td>
                <td><a href="<?php echo esc_url( add_query_arg( [ 'clear_single' => $key ], $current_url ) ); ?>" class="button button-secondary" onclick="ddttClearSingleCookie( '<?php echo esc_attr( $key ); ?>' );">Clear</a></td>
            </tr>
            <?php
        }
        ?>
    </table>
</div>

<br>
<p>Note: Dots (.) and spaces ( ) in cookie names are being replaced with underscores (_).</p>

<script>
    // Function to clear all cookies
    function ddttClearAllCookies() {
        document.cookie.split( ';' ).forEach( function( c ) {
            document.cookie = c.replace( /^ +/, '' ).replace(/=.*/, '=;expires=' + new Date().toUTCString() + ';path=/');
        } );
        console.log( 'Whoops! We "broke" the cookie jar. Throwing them all away now...' );
    }

    // Clear Browser Storage
    function ddttClearBrowserStorage() {
        event.preventDefault(); console.log( localStorage ); localStorage.clear(); alert( 'Local storage cleared!' );
    }

    // Function to add a test cookie
    function ddttAddTestCookie() {
        document.cookie = 'test_cookie=Chocolate Chip;expires=' + new Date( Date.now() + 86400000 ).toUTCString() + ';path=/';
        console.log( 'Tried to give a cookie.' );
    }

    // Function to delete a single test
    function ddttClearSingleCookie( cookie_name ) {
        document.cookie = encodeURIComponent( cookie_name ) + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
        console.log( 'Tossing the nasty "' + cookie_name + '" cookie in the trash.' );
    }
</script>