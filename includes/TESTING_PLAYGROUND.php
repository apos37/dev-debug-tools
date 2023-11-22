<?php 
////////  HELPERS  ////////

// Current URL
// $current_url = ddtt_plugin_options_path( 'testing' );

// Get the user id from the query string (?user=1) or default to current user
// $user_id = ddtt_get_user_from_query_string();


////////  TEST YOUR PHP BELOW  ////////


/**
 * Uncomment the following code to test how it will show up on the Testing tab.
 */

// $current_user = wp_get_current_user();

// echo '<br><br>Yay! Great job, <strong>'.esc_html( $current_user->first_name ).'</strong>! Now you know how to use this PHP playground. :)';

// echo '<br><br>You can use a very helpful function from this plugin called <code class="hl">ddtt_print_r()</code>, or <code class="hl">dpr()</code> for short. The <code class="hl">dpr()</code> function may or may not be available depending on whether or not your theme or other plugins are using it. As a backup, the <code class="hl">ddtt_print_r()</code> function can be used.';

// echo '<br><br><br><br><h3>What does it do?</h3>
// It\'s basically <code class="hl">print_r()</code> wrapped in <code class="hl">&#60;pre&#62;</code> tags <code class="hl">&#60;/pre&#62;</code> which helps view <code class="hl">Objects</code> and <code class="hl">Arrays</code> much easier/cleaner. There are some other helpful additions as well:
// <br><br>
// - By default, it will only display to devs, but you can change the user by entering their user id as the second argument.<br>
// - You can also add left margin in the third argument, or simply set it to <code class="hl">true</code> to add <code class="hl">200px</code>. This is useful when debugging in the admin area and need to move it from behind the left admin menu.<br>
// - Also by default, if you return a boolean, it will return "TRUE" or "FALSE" rather than "1" or "". You can turn this off by add <code class="hl">false</code> to the fourth argument.';

// echo '<br><br><br><strong><em>Okay, let\s try it out!</em></strong>';

// echo '<br><br><br><br><h3>Here we can test a variable that returns your website\'s URL:</h3>';
// $test = home_url();
// dpr( $test );

// echo '<br><br><h3>Here we can debug a <code class="hl" style="font-size: 1rem;">WP_User Object</code>:</h3>';
// dpr( $current_user );

// echo '<br><br><strong><em>Cool, huh?! Now you can delete all this jargon and use it to test code on your site. Have fun!</em></strong>';