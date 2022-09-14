<?php include 'header.php'; ?>
<?php $allowed_html = ddtt_wp_kses_allowed_html(); ?>

<form method="post" action="options.php">
    <?php settings_fields( DDTT_PF.'group_settings' ); ?>
    <?php do_settings_sections( DDTT_PF.'group_settings' ); ?>
    <table class="form-table">
        <?php echo wp_kses( ddtt_options_tr( 'dev_email', 'Developer Email Addresses', 'text', '<br>// Default is admin email
        <br>// Used for testing code using the <strong>ddtt_is_dev()</strong> function globally
        <br>// Displays debug notifications, additional debug tabs, and other helpful things for developers', [ 'default' => get_bloginfo( 'admin_email' ), 'pattern' => '^^([\w+-.%]+@[\w.-]+\.[A-Za-z]{2,4})(\s*,\s*[\w+-.%]+@[\w.-]+\.[A-Za-z]{2,4})*$' ] ), $allowed_html ); ?>

        <?php $timezone_args = [ 
            'default' => wp_timezone_string(),
            'blank' => '-- Select One --',
            'options' => DateTimeZone::listIdentifiers()
        ]; ?>
        <?php echo wp_kses( ddtt_options_tr( 'dev_timezone', 'Developer Timezone', 'select', '<br>// Default is what the site uses', $timezone_args ), $allowed_html ); ?>

        
    </table>

    <?php if ( ddtt_is_dev() ) { ?>
        <br><hr><br></br>
        <h2>Colors</h2>
        <p>Accepts any CSS color code. Defaults can be reset by simply leaving the fields blank and saving.</p>
        <table class="form-table">
            <?php echo wp_kses( ddtt_options_tr( 'color_comments', 'Comments', 'color', null, [ 'default' => '#5E9955' ] ), $allowed_html ); ?>
            
            <?php echo wp_kses( ddtt_options_tr( 'color_fx_vars', 'Functions and Variables', 'color', null, [ 'default' => '#DCDCAA' ] ), $allowed_html ); ?>

            <?php echo wp_kses( ddtt_options_tr( 'color_syntax', 'Syntax', 'color', null, [ 'default' => '#569CD6' ] ), $allowed_html ); ?>
            
            <?php echo wp_kses( ddtt_options_tr( 'color_text_quotes', 'Text with Quotes', 'color', null, [ 'default' => '#ACCCCC' ] ), $allowed_html ); ?>
            
        </table>

        <br><hr><br></br>
        <h2>Testing Options</h2>
        <table class="form-table">
            <?php echo wp_kses( ddtt_options_tr( 'test_number', 'Debugging Test Number', 'number', null, [ 'width' => '10rem' ] ), $allowed_html ); ?>

            <?php echo wp_kses( ddtt_options_tr( 'centering_tool_cols', 'Centering Tool Columns (Found on Admin Bar in Front-End)', 'number', null, [ 'width' => '10rem', 'default' => 16 ] ), $allowed_html ); ?>

            <?php echo wp_kses( ddtt_options_tr( 'stop_heartbeat', 'Stop Heartbeat', 'checkbox', '// 503 INTERNAL ERRORS' ), $allowed_html ); ?>

            <?php echo wp_kses( ddtt_options_tr( 'enable_curl_timeout', 'Extend cURL Timeout', 'checkbox', '// HTTP cURL Timeout Errors' ), $allowed_html ); ?>

            <?php if ( get_option( DDTT_GO_PF.'enable_curl_timeout') == '1' ) { ?>
                <?php echo wp_kses( ddtt_options_tr( 'change_curl_timeout', 'cURL Timeout Seconds', 'text', '// Default is 5 seconds; change # of seconds here to 30 or 120 for testing' ), $allowed_html ); ?>
            <?php } ?>

            <?php echo wp_kses( ddtt_options_tr( 'ql_user_id', 'Add User IDs with Quick Debug Links to User Admin List Page', 'checkbox', '// Adds User ID column with a link to debug the user\'s meta.' ), $allowed_html ); ?>

            <?php echo wp_kses( ddtt_options_tr( 'ql_post_id', 'Add Post/Page IDs with Quick Debug Links to Admin List Pages', 'checkbox', '// Adds a link to debug the post or page\'s meta.' ), $allowed_html ); ?>

            <?php echo wp_kses( ddtt_options_tr( 'wp_mail_failure', 'Capture WP_Mail Failure Details in Debug.log', 'checkbox', '// Must have debug log enabled.' ), $allowed_html ); ?>

            <!-- echo wp_kses( ddtt_options_tr( 'online_users', 'Enable Online Users', 'checkbox', '' ), $allowed_html ); -->

            <?php 
            ///TODO: Add quick debug links to Gravity Form forms and entries
            // if ( is_plugin_active( 'gravityforms/gravityforms.php' ) ) { 
                // echo wp_kses( ddtt_options_tr( 'ql_gravity_forms', 'Add Quick Debug Links to Gravity Forms forms and entries', 'checkbox', '// Adds a link to debug the form or entry\'s meta.' ), $allowed_html ); 
            // } 
            ?>
        </table>
    <?php } ?>
       
    <?php submit_button(); ?>
</form>