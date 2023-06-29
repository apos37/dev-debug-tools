<?php include 'header.php'; ?>
<?php $allowed_html = ddtt_wp_kses_allowed_html(); ?>

<?php if ( is_network_admin() ) { ?>
    <p>You are currently on the multisite network. Settings are only available on a per-site bases. Please navigate to the appropriate site to update settings.</p>

    <?php 
    // Build the current url
    $admin = str_replace( site_url( '/' ), '', rtrim( admin_url(), '/' ) );

    // Get all of the subsites
    global $wpdb;
    $subsites = $wpdb->get_results( "SELECT blog_id, domain, path FROM $wpdb->blogs WHERE archived = '0' AND deleted = '0' AND spam = '0' ORDER BY blog_id" );

    // Iter the subsites
    if ( $subsites && !empty( $subsites ) ) {
        ?><ul><?php
        foreach( $subsites as $subsite ) {
            $subsite_id = $subsite->blog_id;
            $subsite_name = get_blog_details( $subsite_id )->blogname;
            $link = get_site_url( $subsite_id ).'/'.$admin.'/admin.php?page='.DDTT_TEXTDOMAIN.'&tab=settings';
            if ( is_main_site( $subsite_id ) ) {
                $is_main_site = ' — <em>Primary</em>';
            } else {
                $is_main_site = '';
            }
            ?>
            <li><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_attr( $subsite_name ); ?></a> (ID: <?php echo absint( $subsite_id ); ?>)<?php echo wp_kses_post( $is_main_site ); ?></li>
            <?php
        }
        ?></ul><?php
    }
    ?>

<?php } else { ?>

    <?php
    // Check if the developer is already added as a developer
    $is_dev = false;
    $user = get_userdata( get_current_user_id() );
    if ( get_option( 'admin_email' ) == $user->user_email || ( get_option( DDTT_GO_PF.'dev_email' ) && get_option( DDTT_GO_PF.'dev_email' ) != '' ) ) {
        $get_dev_emails = get_option( DDTT_GO_PF.'dev_email' );
        $exp_dev_emails = explode( ',', $get_dev_emails );
        foreach ( $exp_dev_emails as $dev_email ) {
            if ( strtolower( $user->user_email ) == trim( strtolower( $dev_email ) ) ) {
                $is_dev = true;
            }
        }
    }

    // Get the activated email
    if ( get_option( DDTT_GO_PF.'plugin_activated_by' ) && absint( get_option( DDTT_GO_PF.'plugin_activated_by' ) ) > 0 ) {
        $activated_email = absint( get_option( DDTT_GO_PF.'plugin_activated_by' ) );
    } else {
        $activated_email = get_bloginfo( 'admin_email' );
    }

    // If they are not a developer
    if ( !$is_dev ) {

        // Add extra instructions
        $instructions = '<br>// This will give you access to additional settings and all of the debugging and testing tabs available for developers.';

        // Activated by the current user?
        if ( get_option( DDTT_GO_PF.'plugin_activated_by' ) && get_option( DDTT_GO_PF.'plugin_activated_by' ) == get_current_user_id() ) {
            ddtt_admin_notice( 'success', 'If you are a developer using this plugin for debugging and testing, please enter your email address in the "Developer Account Email Addresses" field below. This must be the email address of the account you will be logged in as.' );
        }

    } else {

        // Activated by the current user?
        if ( get_option( DDTT_GO_PF.'plugin_activated_by' ) && get_option( DDTT_GO_PF.'plugin_activated_by' ) == get_current_user_id() ) {
            $instructions = '<br>// If you would like to give access to additional developers, just add their account email addresses above.';

        } else {

            // No extra instructions
            $instructions = '';
        }
    }
    ?>

    <form method="post" action="options.php">
        <?php settings_fields( DDTT_PF.'group_settings' ); ?>
        <?php do_settings_sections( DDTT_PF.'group_settings' ); ?>
        <table class="form-table">
            <?php echo wp_kses( ddtt_options_tr( 'dev_email', 'Developer Account Email Addresses', 'text', $instructions.'<br>// Default is the email of the user that activated the plugin.
            <br>// You may use multiple email addresses separated by commas', [ 'default' => $activated_email, 'pattern' => '^^([\w+-.%]+@[\w.-]+\.[A-Za-z]{2,4})(\s*,\s*[\w+-.%]+@[\w.-]+\.[A-Za-z]{2,4})*$' ] ), $allowed_html ); ?>

            <?php $timezone_args = [ 
                'default' => wp_timezone_string(),
                'blank' => '-- Select One --',
                'options' => DateTimeZone::listIdentifiers()
            ]; ?>
            <?php echo wp_kses( ddtt_options_tr( 'dev_timezone', 'Developer Timezone', 'select', '<br>// Default is what the site uses', $timezone_args ), $allowed_html ); ?>
            
        </table>

        <?php if ( ddtt_is_dev() ) { ?>
            
            <br><hr><br></br>
            <h2>Testing Options</h2>
            <table class="form-table">
                <?php echo wp_kses( ddtt_options_tr( 'view_sensitive_info', 'View Sensitive Info', 'checkbox', '// Displays redacted database login info, IP addresses, etc.' ), $allowed_html ); ?>

                <?php $log_viewers = [
                    'options' => [
                        'Easy Reader',
                        'Classic'
                    ]
                ]; ?>
                <?php echo wp_kses( ddtt_options_tr( 'log_viewer', 'Log Viewer', 'select', '<br>// Change how the <a href="'.ddtt_plugin_options_path( 'logs' ).'">debug log</a> is displayed', $log_viewers ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'log_user_url', 'Also Log User and URL With Errors', 'checkbox', '// Adds an additional line to debug.log errors with the user ID, user display name, and url with query strings when an error is triggered.' ), $allowed_html ); ?>
                
                <?php echo wp_kses( ddtt_options_tr( 'test_number', 'Debugging Test Number', 'number', null, [ 'width' => '10rem' ] ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'centering_tool_cols', 'Centering Tool Columns (Found on Admin Bar in Front-End)', 'number', null, [ 'width' => '10rem', 'default' => 16 ] ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'stop_heartbeat', 'Stop Heartbeat', 'checkbox', '// 503 INTERNAL ERRORS' ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'enable_curl_timeout', 'Extend cURL Timeout', 'checkbox', '// HTTP cURL Timeout Errors' ), $allowed_html ); ?>

                <?php if ( get_option( DDTT_GO_PF.'enable_curl_timeout' ) == '1' ) { ?>
                    <?php echo wp_kses( ddtt_options_tr( 'change_curl_timeout', 'cURL Timeout Seconds', 'text', '// Default is 5 seconds; change # of seconds here to 30 or 120 for testing' ), $allowed_html ); ?>
                <?php } ?>

                <?php echo wp_kses( ddtt_options_tr( 'ql_user_id', 'Add User IDs with Quick Debug Links to User Admin List Page', 'checkbox', '// Adds User ID column with a link to debug the user\'s meta.' ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'ql_post_id', 'Add Post/Page IDs with Quick Debug Links to Admin List Pages', 'checkbox', '// Adds a link to debug the post or page\'s meta.' ), $allowed_html ); ?>

                <?php if ( is_plugin_active( 'gravityforms/gravityforms.php' ) ) { 
                    echo wp_kses( ddtt_options_tr( 'ql_gravity_forms', 'Add Quick Debug Links to Gravity Forms', 'checkbox', '// Adds a link to debug forms and entries.' ), $allowed_html ); 
                } ?>

                <?php echo wp_kses( ddtt_options_tr( 'wp_mail_failure', 'Capture WP_Mail Failure Details in Debug.log', 'checkbox', '// Must have debug log enabled.' ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'online_users', 'Show Online Users', 'checkbox', '// Adds indicator to admin bar, a dashboard widget, and users admin list column.' ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'online_users_seconds', 'Online Users # of Seconds', 'number', '<br>// Checks if users were logged in this amount of time ago. Recommended 900 seconds (15 minutes).<br>// Note that logged-in time is stored on page load, so if a user is on a page for longer than the amount of time you specify here, it may show them as offline when they are not.', [ 'width' => '10rem', 'default' => 900 ] ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'online_users_show_last', 'Online Users Show Last in Admin Bar', 'checkbox', '// Show the last online time in the admin bar. Note that the logged-in status only updates if they are not already stored.' ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'online_users_link', 'Online Users Link', 'text', '<br>// Link online users in the admin bar<br>// Merge tags available: {user_id}, {user_email} ie. '.DDTT_ADMIN_URL( 'user-edit.php?user_id={user_id}' ).'<br>// Leave blank to remove link' ), $allowed_html ); ?>

            </table>

            <br><hr><br></br>
            <h2>Remove Items from Admin Bar</h2>
            <table class="form-table">

                <?php echo wp_kses( ddtt_options_tr( 'admin_bar_wp_logo', '(—) WordPress Logo', 'checkbox' ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'admin_bar_resources', '(—) Resources', 'checkbox' ), $allowed_html ); ?>

                <?php if ( is_plugin_active( 'gravityforms/gravityforms.php' ) ) {
                    echo wp_kses( ddtt_options_tr( 'admin_bar_gf', '(—) Gravity Form Finder', 'checkbox' ), $allowed_html );
                } ?>

                <?php echo wp_kses( ddtt_options_tr( 'admin_bar_shortcodes', '(—) Shortcode Finder', 'checkbox' ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'admin_bar_centering_tool', '(—) Centering Tool', 'checkbox' ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'admin_bar_post_info', '(—) Post Information', 'checkbox' ), $allowed_html ); ?>
                
            </table>

            <br></br>
            <table class="form-table">

                <?php $condense_options = [
                    'options' => [
                        'No',
                        'Everyone',
                        'Developer Only',
                        'Everyone Excluding Developer'
                    ]
                ]; ?>
                <?php echo wp_kses( ddtt_options_tr( 'admin_bar_condense', 'Consense Admin Bar Items', 'select', '<br>// You can also use the <code>ddtt_admin_bar_condensed_items</code> <a href="'.ddtt_plugin_options_path( 'hooks' ).'">hook</a> to customize items', $condense_options ), $allowed_html ); ?>
                
            </table>

            <br><hr><br></br>
            <h2>Colors</h2>
            <p>Accepts any CSS color code. Defaults can be reset by simply leaving the fields blank and saving.</p>
            <table class="form-table">
                <?php echo wp_kses( ddtt_options_tr( 'color_comments', 'Comments', 'color', null, [ 'default' => '#5E9955' ] ), $allowed_html ); ?>
                
                <?php echo wp_kses( ddtt_options_tr( 'color_fx_vars', 'Functions and Variables', 'color', null, [ 'default' => '#DCDCAA' ] ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'color_syntax', 'Syntax', 'color', null, [ 'default' => '#569CD6' ] ), $allowed_html ); ?>
                
                <?php echo wp_kses( ddtt_options_tr( 'color_text_quotes', 'Text with Quotes', 'color', null, [ 'default' => '#ACCCCC' ] ), $allowed_html ); ?>
                
            </table>
        <?php } ?>
        
        <?php submit_button(); ?>
    </form>
<?php } ?>

<script>
// Display cURL change field
ddtt_curl_timeout_seconds();
function ddtt_curl_timeout_seconds() {
    var enableCURL = document.getElementById( "<?php echo esc_attr( DDTT_GO_PF ); ?>enable_curl_timeout" );
    enableCURL.addEventListener( "change", function() {
        var changeCURL = document.getElementById( "row_<?php echo esc_attr( DDTT_GO_PF ); ?>change_curl_timeout" );
        if ( this.checked ) {
            changeCURL.style.display = 'table-row';
        } else {
            changeCURL.style.display = 'none';
        }
    } );
}
</script>