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
            <td><strong><?php echo wp_kses_post( ddtt_highlight_string( htmlentities( 'if ( ddtt_is_dev() ) { ... }') ) ); ?></strong>
            <p class="field-desc break"><?php echo wp_kses_post( ddtt_highlight_string( '$email:' ) ); ?> returns the developer emails
            <br><?php echo wp_kses_post( ddtt_highlight_string( '$array:' ) ); ?> only works if <code>$email</code> is <code>true</code>, returns the emails as an <code>Array</code> or <code>String</code>. Default is <code>String</code>.</p></td>
        </tr>
        <tr>
            <td>Wrap <code class="hl">print_r</code> in &#60;pre&#62; tags &#60;/pre&#62;,<br>Displays only to devs (or specified user),<br>Display TRUE OR FALSE instead of 1 and <em>nothing</em>,<br>Easily add left margin</td>
            <td><strong><?php echo wp_kses_post( ddtt_get_function_example( "ddtt_print_r" ) ); ?><br><?php echo wp_kses_post( ddtt_get_function_example( "dpr" ) ); ?></strong></td>
            <td><strong><?php echo wp_kses_post( ddtt_highlight_string( htmlentities( 'dpr( $array, 3 );' ) ) ); ?></strong>
            <p class="field-desc break"><span class="highlight-variable">$user_id</span>: display only to that user. Default is developer (you).
            <br><span class="highlight-variable">$left_margin</span>: will add # of pixels to left margin or <code>200px</code> if <code>true</code>. Useful if debugging in admin to avoid left admin menu.</p></td>
        </tr>
        <tr>
            <td>Convert <code class="hl">var_dump()</code> to string</td>
            <td><strong><?php echo wp_kses_post( ddtt_get_function_example( "ddtt_var_dump_to_string" ) ); ?></strong></td>
            <td><strong><?php echo wp_kses_post( ddtt_highlight_string( htmlentities( 'ddtt_var_dump_to_string( $var );' ) ) ); ?></strong></td>
        </tr>
        <tr>
            <td>Add JavaScript alert in PHP</td>
            <td><strong><?php echo wp_kses_post( ddtt_get_function_example( "ddtt_alert" ) ); ?></strong></td>
            <td><strong><?php echo wp_kses_post( ddtt_highlight_string( htmlentities( 'ddtt_alert( \'Test 1\' );' ) ) ); ?></strong>
            <p class="field-desc break"><span class="highlight-variable">$user_id</span>: only shows the alert to this user. Default is developer (you).</p></td>
        </tr>
        <tr>
            <td>Add JavaScript <code class="hl">console.log</code> in PHP</td>
            <td><strong><?php echo wp_kses_post( ddtt_get_function_example( "ddtt_console" ) ); ?></strong></td>
            <td><strong><?php echo wp_kses_post( ddtt_highlight_string( htmlentities( 'ddtt_console( \'Test 1\' );' ) ) ); ?></strong></td>
        </tr>
        <tr>
            <td>Add text, arrays, or objects to debug.log</td>
            <td><strong><?php echo wp_kses_post( ddtt_get_function_example( "ddtt_write_log" ) ); ?></strong></td>
            <td><strong><?php echo wp_kses_post( ddtt_highlight_string( htmlentities( 'ddtt_write_log( \'Test 1\' );' ) ) ); ?></strong>
            <p class="field-desc break"><span class="highlight-variable">$prefix</span>: if <code>true</code>, adds "DDTT_LOG: " to beginning of line. Yan use your own <code>String</code> as a prefix or <code>false</code> to remove.
            <br><span class="highlight-variable">$backtrace</span>: include file and line number the function is called on.
            <br><span class="highlight-variable">$full_stacktrace</span>: include the full stack trace of where the function is called.</p></td>
        </tr>
        <tr>
            <td>Debug <code class="hl">$_POST</code> via email</td>
            <td><strong><?php echo wp_kses_post( ddtt_highlight_string( 'ddtt_debug_form_post( $email, $test_number = 1, $subject = "Test Form $_POST " )' ) ); ?></strong></td>
            <td><strong><?php echo wp_kses_post( ddtt_highlight_string( htmlentities( 'ddtt_debug_form_post( \''.sanitize_email( get_userdata( get_current_user_id() )->user_email ).'\', 1 );' ) ) ); ?></strong>
            <p class="field-desc break"><span class="highlight-variable">$email</span>: the email you want to send debug email to.
            <br><span class="highlight-variable">$test_number</span>: just adds a test number to the subject so you know which test you are attempting.
            <br><span class="highlight-variable">$subject</span>: change the subject if you want.</p></td>
        </tr>
        <tr>
            <td>Global admin error notice on front-end</td>
            <td><strong><?php echo wp_kses_post( ddtt_get_function_example( "ddtt_admin_error" ) ); ?></strong></td>
            <td><strong><?php echo wp_kses_post( ddtt_highlight_string( htmlentities( 'echo ddtt_admin_error( \'Please do this to fix the error...\' );' ) ) ); ?></strong>
            <p class="field-desc break"><span class="highlight-variable">$include_pre</span>: adds "ADMIN ERROR: " to the beginning of the message.
            <br><span class="highlight-variable">$br</span>: adds a <code><?php echo htmlentities('<br>'); ?></code> tag before the message.
            <br><span class="highlight-variable">$hide_error</span>: will force the error to be hidden during testing.</p></td>
        </tr>
        <tr>
            <td><?php $total_time = ddtt_stop_timer( $start, true, true ); ?>Add a timer to your processes to check processing speed. Returns number of seconds or milliseconds (Example: this page loaded in <strong><?php echo absint( $total_time ); ?></strong> milliseconds up to this point)</td>
            <td><strong><?php echo wp_kses_post( ddtt_get_function_example( "ddtt_start_timer" ) ); ?></strong>
            <br><strong><?php echo wp_kses_post( ddtt_get_function_example( "ddtt_stop_timer" ) ); ?></strong></td>
            <td><strong><?php echo wp_kses_post( ddtt_highlight_string( htmlentities( '$start = ddtt_start_timer();' ) ) ); ?><br><?php echo wp_kses_post( ddtt_highlight_string( '// run your functions' ) ); ?><br>
            <?php echo wp_kses_post( ddtt_highlight_string( htmlentities( '$total_time = ddtt_stop_timer( $start );' ) ) ); ?></strong>
            <p class="field-desc break">Useful to count seconds per item you are processing, such as: <code><?php echo htmlentities( '$sec_per_link = round( ( $total_time / $count_links ), 2 );' ); ?></code></p></td>
        </tr>
        <tr>
            <td>Increase global test number</td>
            <td><strong><?php echo wp_kses_post( ddtt_get_function_example( "ddtt_increase_test_number" ) ); ?></strong></td>
            <td><strong><?php echo wp_kses_post( ddtt_highlight_string( htmlentities( 'ddtt_increase_test_number();' ) ) ); ?></strong>
            <p class="field-desc break">This just increases a number on every page load. Helpful during testing to make sure your page is reloading properly.</p></td>
        </tr>
        <tr>
            <td>Check if the current domain contains</td>
            <td><strong><?php echo wp_kses_post( ddtt_get_function_example( "ddtt_is_site" ) ); ?></strong></td>
            <td><strong><?php echo wp_kses_post( ddtt_highlight_string( htmlentities( 'if ( ddtt_is_site( \''.esc_html( ddtt_get_domain( false, true ) ).'\' ) ) { ... }' ) ) ); ?></strong>
            <p class="field-desc break">Accepts partial keywords, so if domain is <code>https://long-domain.com</code>, you can use <code>ddtt_is_site( "long" )</code> and it will return <code>true</code>.
            <br>This is really only useful if you are maintaining multiple sites on different domains.</p></td>
        </tr>
    </table>
</div>