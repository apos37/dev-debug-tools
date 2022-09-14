<?php 
// Start example timer
$start = ddtt_start_timer();

// Get user data
$user = get_userdata( get_current_user_id() );
$display_name = $user->display_name;
$email = $user->user_email;
?>
<!-- Add CSS to this table only -->
<style>
.admin-large-table td {
    vertical-align: top;
}
</style>

<?php include 'header.php'; ?>

<div class="full_width_container">
    <table class="admin-large-table">
        <tr>
            <th style="width: 300px;">Item</th>
            <th style="width: auto;">Function</th>
            <th style="width: auto;">Usage</th>
        </tr>
        <tr>
            <td>Only do something if the current user is a developer</td>
            <td><strong><?php echo wp_kses_post( ddtt_get_function_example( "ddtt_is_dev" ) ); ?></strong></td>
            <td><strong><?php echo htmlentities( 'if ( ddtt_is_dev() ) { ... }'); ?></strong>
            <br><br>// $email: returns the developer emails
            <br>// $array: only works if $email is true, returns the emails as an array or string; default is string</td>
        </tr>
        <tr>
            <td>Wrap <code>print_r</code> in &#60;pre&#62; tags &#60;/pre&#62;</td>
            <td><strong><?php echo wp_kses_post( ddtt_get_function_example( "ddtt_print_r" ) ); ?><br><?php echo wp_kses_post( ddtt_get_function_example( "dpr" ) ); ?></strong></td>
            <td><strong><?php echo htmlentities( 'dpr( $array, 3 );' ); ?></strong>
            <br><br>// $user_id: display only to that user; default is developer (you)
            <br>// $left_margin: will add # of pixels to left margin or 200px if true; useful if debugging in admin to avoid left admin menu</td>
        </tr>
        <tr>
            <td>Convert <code>var_dump()</code> to string</td>
            <td><strong><?php echo wp_kses_post( ddtt_get_function_example( "ddtt_var_dump_to_string" ) ); ?></strong></td>
            <td><strong><?php echo htmlentities( 'ddtt_var_dump_to_string( $var );' ); ?></strong></td>
        </tr>
        <tr>
            <td>Add JavaScript alert in PHP</td>
            <td><strong><?php echo wp_kses_post( ddtt_get_function_example( "ddtt_alert" ) ); ?></strong></td>
            <td><strong><?php echo htmlentities( 'ddtt_alert( \'Test 1\' );' ); ?></strong>
            <br><br>// $user_id: only shows the alert to this user; default is developer (you)</td>
        </tr>
        <tr>
            <td>Add JavaScript console.log in PHP</td>
            <td><strong><?php echo wp_kses_post( ddtt_get_function_example( "ddtt_console" ) ); ?></strong></td>
            <td><strong><?php echo htmlentities( 'ddtt_console( \'Test 1\' );' ); ?></strong></td>
        </tr>
        <tr>
            <td>Add text, arrays, or objects to debug.log</td>
            <td><strong><?php echo wp_kses_post( ddtt_get_function_example( "ddtt_write_log" ) ); ?></strong></td>
            <td><strong><?php echo htmlentities( 'ddtt_write_log( \'Test 1\' );' ); ?></strong>
            <br><br>// $prefix: if true, adds "DDTT_LOG: " to beginning of line; can use your own string as a prefix or false to remove
            <br>// $backtrace: include file and line number the function is called on
            <br>// $full_stacktrace: include the full stack trace of where the function is called</td>
        </tr>
        <tr>
            <td>Debug <code>$_POST</code> via email</td>
            <td><strong>ddtt_debug_form_post( $email, $test_number = 1, $subject = "Test Form $_POST " )</strong></td>
            <td><strong><?php echo htmlentities( 'ddtt_debug_form_post( \''.sanitize_email( get_userdata( get_current_user_id() )->user_email ).'\', 1 );' ); ?></strong>
            <br><br>// $email: the email you want to send debug email to
            <br>// $test_number: just adds a test number to the subject so you know which test you are attempting
            <br>// $subject: change the subject if you want</td>
        </tr>
        <tr>
            <td>Global admin error notice on front-end</td>
            <td><strong><?php echo wp_kses_post( ddtt_get_function_example( "ddtt_admin_error" ) ); ?></strong></td>
            <td><strong><?php echo htmlentities( 'echo ddtt_admin_error( \'Please do this to fix the error...\' );' ); ?></strong>
            <br><br>// $include_pre: adds "ADMIN ERROR: " to the beginning of the message
            <br>// $br: adds a <?php echo htmlentities('<br>'); ?> tag before the message
            <br>// $hide_error: will force the error to be hidden during testing</td>
        </tr>
        <tr>
            <td><?php $total_time = ddtt_stop_timer( $start, true, true ); ?>Add a timer to your processes to check processing speed. Returns number of seconds or milliseconds (Example: this page loaded in <strong><?php echo absint( $total_time ); ?></strong> milliseconds up to this point)</td>
            <td><strong><?php echo wp_kses_post( ddtt_get_function_example( "ddtt_start_timer" ) ); ?></strong>
            <br><strong><?php echo wp_kses_post( ddtt_get_function_example( "ddtt_stop_timer" ) ); ?></strong></td>
            <td><strong><?php echo htmlentities( '$start = ddtt_start_timer();' ); ?><br><em>run your functions</em><br>
            <?php echo htmlentities( '$total_time = ddtt_stop_timer( $start );' ); ?></strong>
            <br><br>// Useful to count seconds per item you are processing, such as: <?php echo htmlentities( '$sec_per_link = round( ( $total_time / $count_links ), 2 );' ); ?></td>
        </tr>
        <tr>
            <td>Increase global test number</td>
            <td><strong><?php echo wp_kses_post( ddtt_get_function_example( "ddtt_increase_test_number" ) ); ?></strong></td>
            <td><strong><?php echo htmlentities( 'ddtt_increase_test_number();' ); ?></strong>
            <br><br>// This just increases a number on every page load. Helpful during testing to make sure your page is reloading properly.</td>
        </tr>
        <tr>
            <td>Check if the current domain contains</td>
            <td><strong><?php echo wp_kses_post( ddtt_get_function_example( "ddtt_is_site" ) ); ?></strong></td>
            <td><strong><?php echo htmlentities( 'if ( ddtt_is_site( \''.esc_html( ddtt_get_domain( false, true ) ).'\' ) ) { ... }' ); ?></strong>
            <br><br>// Accepts partial keywords, so if domain is https://long-domain.com, you can use ddtt_is_site( "long" ) and it will return true
            <br>// This is really only useful if you are maintaining multiple sites on different domains</td>
        </tr>
    </table>
</div>