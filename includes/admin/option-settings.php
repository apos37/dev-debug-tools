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
        $activated_user_id = absint( get_option( DDTT_GO_PF.'plugin_activated_by' ) );
        $user = get_userdata( $activated_user_id );
        $activated_email = $user->user_email;
    } else {
        $activated_email = get_bloginfo( 'admin_email' );
    }

    // If they are not a developer
    if ( !$is_dev ) {

        // Add extra instructions
        $instructions = '<br>This will give you access to additional settings and all of the debugging and testing tabs available for developers.';

        // Activated by the current user?
        if ( get_option( DDTT_GO_PF.'plugin_activated_by' ) && get_option( DDTT_GO_PF.'plugin_activated_by' ) == get_current_user_id() ) {
            ddtt_admin_notice( 'success', 'If you are a developer using this plugin for debugging and testing, please enter your email address in the "Developer Account Email Addresses" field below. This must be the email address of the account you will be logged in as.' );
        }

    } else {

        // Activated by the current user?
        if ( get_option( DDTT_GO_PF.'plugin_activated_by' ) && get_option( DDTT_GO_PF.'plugin_activated_by' ) == get_current_user_id() ) {
            $instructions = '<br>If you would like to give access to additional developers, just add their account email addresses above.';

        } else {

            // No extra instructions
            $instructions = '';
        }
    }

    // Check if cURL timeout is enabled
    if ( get_option( DDTT_GO_PF.'enable_curl_timeout' ) ) {
        $display_curl_row = 'table-row';
    } else {
        $display_curl_row = 'none';
    }

    // Update admin menu links
    if ( ddtt_get( 'settings-updated' ) && get_option( DDTT_GO_PF.'admin_bar_add_links' ) ) {
        global $menu, $submenu;

        // Store the links here
        $admin_menu_links = [];

        // Iter
        foreach ( $menu as $item ) {
            
            // Skip separators
            if ( !$item[0] || $item[0] == '' ) {
                continue;
            }

            // Get name of menu item
            $admin_menu_label = $item[0];

            // Skip dashboard
            if ( $admin_menu_label == 'Dashboard' ) {
                continue;
            }

            // Get dashboard item file
            $admin_menu_file = $item[2];

            // Get URL for item
            $admin_menu_url = ddtt_get_admin_menu_item_url( $admin_menu_file, $menu, $submenu );

            // Store it
            $admin_menu_links[] = [
                'label' => ddtt_strip_tags_content( $admin_menu_label ),
                'url'   => $admin_menu_url,
                'perm'  => $item[1]
            ];
        }

        // Update option
        update_option( DDTT_GO_PF.'admin_menu_links', $admin_menu_links );

    // If saving without adding links
    } elseif ( ddtt_get( 'settings-updated' ) && !get_option( DDTT_GO_PF.'admin_bar_add_links' ) ) {

        // Delete option
        delete_option( DDTT_GO_PF.'admin_menu_links' );
    }
    ?>

    <style>
    #row_ddtt_change_curl_timeout {
        display: <?php echo esc_html( $display_curl_row ); ?>;
    }
    </style>

    <form method="post" action="options.php">
        <?php settings_fields( DDTT_PF.'group_settings' ); ?>
        <?php do_settings_sections( DDTT_PF.'group_settings' ); ?>
        <table class="form-table">
            <?php echo wp_kses( ddtt_options_tr( 'dev_email', 'Developer Account Email Addresses', 'text', $instructions.'<br>Default is the email of the user that activated the plugin. You may use multiple email addresses separated by commas.', [ 'default' => $activated_email, 'pattern' => '([a-zA-Z0-9+_.\-]+@[a-zA-Z0-9.\-]+.[a-zA-Z0-9]+)(\s*,\s*([a-zA-Z0-9+_.\-]+@[a-zA-Z0-9.\-]+.[a-zA-Z0-9]+))*' ] ), $allowed_html ); ?>

            <?php $timezone_args = [ 
                'default' => wp_timezone_string(),
                'blank' => '-- Select One --',
                'options' => DateTimeZone::listIdentifiers()
            ]; ?>
            <?php echo wp_kses( ddtt_options_tr( 'dev_timezone', 'Developer Timezone', 'select', '<br>Changes the timezone on Debug Log viewer and other areas in the plugin. Default is what the site uses.', $timezone_args ), $allowed_html ); ?>
            
        </table>

        <p class="submit"><input type="submit" class="button button-primary" value="Save Changes"></p>

        <?php if ( !ddtt_is_dev() ) {
            $readonly = ' style="display: none;"';
        } else {
            $readonly = '';
        } ?>

        <div id="testing-options"<?php echo wp_kses( $readonly, $allowed_html ); ?>>
            
            <br><hr><br></br>
            <h2>Testing Options</h2>
            <table class="form-table">
                
                <?php echo wp_kses( ddtt_options_tr( 'view_sensitive_info', 'View Sensitive Info', 'checkbox', 'Displays redacted database login info, authentication keys and salts, IP addresses, etc.' ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'test_number', 'Debugging Test Number', 'number', null, [ 'width' => '10rem' ] ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'centering_tool_cols', 'Centering Tool Columns (Found on Admin Bar in Front-End)', 'number', null, [ 'width' => '10rem', 'default' => 16 ] ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'stop_heartbeat', 'Stop Heartbeat', 'checkbox', 'Helpful to resolve 503 INTERNAL ERRORS.' ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'enable_curl_timeout', 'Extend cURL Timeout', 'checkbox', 'Helpful to resolve HTTP cURL Timeout Errors.' ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'change_curl_timeout', 'cURL Timeout Seconds', 'text', '<br>Change # of seconds here to 30 or 120 for testing. Default is 5 seconds.', [ 'width' => '10rem', 'default' => 5 ] ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'ql_user_id', 'Add User IDs with Quick Debug Links to User Admin List Page', 'checkbox' ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'ql_post_id', 'Add Post/Page IDs with Quick Debug Links to Admin List Pages', 'checkbox', 'Use the <code>ddtt_quick_link_post_types</code> filter from <a href="'.ddtt_plugin_options_path( 'hooks' ).'#ddtt_quick_link_post_types">Hooks</a> tab to customize which post types are included.' ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'ql_comment_id', 'Add Debug Columns (Comment ID, Comment Type, and Karma) and Quick Debug Links to Comment Admin List Page', 'checkbox' ), $allowed_html ); ?>

                <?php if ( is_plugin_active( 'gravityforms/gravityforms.php' ) ) { 
                    echo wp_kses( ddtt_options_tr( 'ql_gravity_forms', 'Add Quick Debug Links to Gravity Forms & Entries', 'checkbox' ), $allowed_html ); 
                } ?>

                <?php echo wp_kses( ddtt_options_tr( 'plugins_page_data', 'Disable Extra Data on Plugins Page (Main File Path, File Size, and Last Modified Date)', 'checkbox', 'You can still see all the extra data on our <a href="'.ddtt_plugin_options_path( 'plugins' ).'">Plugins</a> tab.' ), $allowed_html ); ?>

            </table>

            <p class="submit"><input type="submit" class="button button-primary" value="Save Changes"></p>

            <br><hr><br></br>
            <h2>Logging</h2>
            <table class="form-table">
                <?php echo wp_kses( ddtt_options_tr( 'disable_error_counts', 'Disable Error Counts', 'checkbox', 'Disabling this will prevent counting and improve page load time. Good to use when you have a lot of errors in your logs.' ), $allowed_html ); ?>

                <?php $log_viewers = [
                    'options' => [
                        'Easy Reader',
                        'Classic'
                    ]
                ]; ?>
                <?php echo wp_kses( ddtt_options_tr( 'log_viewer', 'Log Viewer', 'select', '<br>Change how the <a href="'.ddtt_plugin_options_path( 'logs' ).'">debug log</a> is displayed.', $log_viewers ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'max_log_size', 'Maximum Debug Log Size for Viewer', 'number', '<br>The maximum file size in MB alloted for viewing the debug log before giving a warning. This is set to prevent the page from crashing if it is too big and not allowing you to clear it.', [ 'width' => '10rem', 'default' => 3 ] ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'log_user_url', 'Also Log User and URL With Errors', 'checkbox', 'Adds an additional line to debug.log errors with the user ID, user display name, and url with query strings when a user error is triggered.' ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'wp_mail_failure', 'Capture WP_Mail Failure Details in Debug.log', 'checkbox', 'Must have debug log enabled.' ), $allowed_html ); ?>

                <?php
                // Paths
                $themes_root_uri = str_replace( site_url( '/' ), '', get_theme_root_uri() ).'/';
                $active_theme = str_replace( '%2F', '/', rawurlencode( get_stylesheet() ) );
                $active_theme_path = '/'.$themes_root_uri.$active_theme.'/';
                ?>

                <?php echo wp_kses( ddtt_options_tr( 'error_log_path', 'Path to error_log', 'text', '', [ 'default' => 'error_log', 'log_files' => 'yes' ] ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'admin_error_log_path', 'Path to admin error_log', 'text', '', [ 'default' => DDTT_ADMIN_URL.'/error_log', 'log_files' => 'yes' ] ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'log_files', 'Log Files to Check<br>.TXT Files Only', 'text+', 'We automatically check your debug.log, error_log, and admin error_log. If you have additional <strong>.TXT</strong> logs you would like to view on the Logs tab, enter the root paths to the files here (not including the domain).', [ 'placeholder' => $active_theme_path.'your_log_file.txt', 'log_files' => 'yes', 'pattern' => '.*\.txt$' ] ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'fatal_discord_webhook', 'Discord Webhook URL<br>** BETA **', 'text', '<br>Send notifications to a <a href="https://support.discord.com/hc/en-us/articles/228383668-Intro-to-Webhooks" target="_blank">Discord Webhook</a> when a fatal error occurs.<br>Webhook URL should look like this: https://discord.com/api/webhooks/xxx/xxx...', [ 'pattern' => "(https:\/\/discord\.com\/api\/webhooks\/([A-Za-z0-9\-\._~:\/\?#\[\]@!$&'\(\)\*\+,;\=]*)?)" ] ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'fatal_discord_enable', 'Send Fatal Errors to Discord Channel (Must have WP_DEBUG enabled)', 'checkbox' ), $allowed_html ); ?>

            </table>

            <p class="submit"><input type="submit" class="button button-primary" value="Save Changes"></p>

            <br><hr><br></br>
            <h2>Show Online Users</h2>
            <table class="form-table">

                <?php echo wp_kses( ddtt_options_tr( 'online_users', 'Show Online Users', 'checkbox', 'Adds indicator to admin bar and users admin list column.' ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'online_users_seconds', '# of Seconds', 'number', '<br>Checks if users were logged in this amount of time ago. Recommended 900 seconds (15 minutes).<br>Note that logged-in time is stored on page load, so if a user is on a page for longer than the amount of time you specify here, it may show them as offline when they are not.', [ 'width' => '10rem', 'default' => 900 ] ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'online_users_show_last', 'Show Last Time in Admin Bar', 'checkbox', 'Show the last online time in the admin bar. Note that the logged-in status only updates if they are not already stored.' ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'online_users_link', 'User Link URL', 'text', '<br>Link online users in the admin bar<br>Merge tags available: {user_id}, {user_email} ie. '.DDTT_ADMIN_URL( 'user-edit.php?user_id={user_id}' ).'<br>Leave blank for no link.' ), $allowed_html ); ?>

                <?php
                // Get the role details
                $roles = get_editable_roles();

                // Store the roles here
                $role_options = [];

                // Iter the roles
                foreach ( $roles as $key => $role ) {

                    // Pre check it
                    if ( $key == 'administrator' ) {
                        $pre_check_role = true;
                    } else {
                        $pre_check_role = false;
                    }

                    // Add the option's label and value
                    $role_options[] = [
                        'label'   => $role[ 'name' ],
                        'value'   => $key,
                        'checked' => $pre_check_role
                    ];
                }

                // Set the args
                $prioritize_roles_args = [
                    'options' => $role_options,
                    'class'   => DDTT_GO_PF.'role_checkbox'
                ]; ?>
                <?php echo wp_kses( ddtt_options_tr( 'online_users_priority_roles', 'Roles to Prioritize on Top', 'checkboxes', '', $prioritize_roles_args ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'discord_webhook', 'Discord Webhook URL<br>** BETA **', 'text', '<br>Send notifications to a <a href="https://support.discord.com/hc/en-us/articles/228383668-Intro-to-Webhooks" target="_blank">Discord Webhook</a> when users do different things (enable notification types below).<br>Useful if you need to stop debugging when there is activity.<br>Webhook URL should look like this: https://discord.com/api/webhooks/xxx/xxx...', [ 'pattern' => "(https:\/\/discord\.com\/api\/webhooks\/([A-Za-z0-9\-\._~:\/\?#\[\]@!$&'\(\)\*\+,;\=]*)?)" ] ), $allowed_html ); ?>

                <?php 
                $seconds = get_option( DDTT_GO_PF.'online_users_seconds', 900 ); 
                if ( $seconds && $seconds > 0 ) {
                    $minutes = $seconds / 60;
                } else {
                    $minutes = 15;
                }
                ?>
                <?php echo wp_kses( ddtt_options_tr( 'discord_login', 'Login Notifications', 'checkbox', 'Notifies you if a user with a priority role (selected above) has logged in.' ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'discord_transient', 'Intermittent Notifications', 'checkbox', 'Notifies you if a user with a priority role is still logged in. Updates every '.$minutes.' minutes; you may reset timer by clearing transients on the <a href="'.ddtt_plugin_options_path( 'siteoptions' ).'">Site Options</a> tab.' ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'discord_page_loads', 'Page Load Notifications', 'checkbox', 'Notifies you every time a user with a priority role loads a page (warning: this may cause rate limits on Discord if too many pages are loaded at once).' ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'discord_ingore_devs', 'Ignore Developer Notifications', 'checkbox', 'Ignore developers for intermittent notifications and page load notifications.' ), $allowed_html ); ?>

            </table>

            <p class="submit"><input type="submit" class="button button-primary" value="Save Changes"></p>

            <br><hr><br></br>
            <h2>Admin Bar</h2>
            <table class="form-table">

                <?php echo wp_kses( ddtt_options_tr( 'admin_bar_wp_logo', 'Remove WordPress Logo', 'checkbox' ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'admin_bar_resources', 'Remove Resources', 'checkbox' ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'admin_bar_centering_tool', 'Remove Centering Tool', 'checkbox' ), $allowed_html ); ?>

                <?php if ( is_plugin_active( 'gravityforms/gravityforms.php' ) ) {
                    echo wp_kses( ddtt_options_tr( 'admin_bar_gf', 'Remove Gravity Form Finder', 'checkbox' ), $allowed_html );
                } ?>

                <?php echo wp_kses( ddtt_options_tr( 'admin_bar_shortcodes', 'Remove Shortcode Finder', 'checkbox' ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'admin_bar_post_info', 'Remove Post Information', 'checkbox' ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'admin_bar_my_account', 'Disable "My Account" Enhancements', 'checkbox' ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'admin_bar_add_links', 'Add All Admin Menu Links to Front End', 'checkbox', 'If enabled, resave this page at any time to update links.' ), $allowed_html ); ?>
                
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
                <?php echo wp_kses( ddtt_options_tr( 'admin_bar_condense', 'Condense Admin Bar Items', 'select', '<br>You can also use the <code>ddtt_admin_bar_condensed_items</code> <a href="'.ddtt_plugin_options_path( 'hooks' ).'">hook</a> to customize items.', $condense_options ), $allowed_html ); ?>
                
            </table>

            <p class="submit"><input type="submit" class="button button-primary" value="Save Changes"></p>

            <br><hr><br></br>
            <h2>Colors</h2>
            <p>Accepts any CSS color code. Defaults can be reset by simply leaving the fields blank and saving.</p>
            <table class="form-table">
                <?php echo wp_kses( ddtt_options_tr( 'color_comments', 'Comments', 'color', null, [ 'default' => '#5E9955' ] ), $allowed_html ); ?>
                
                <?php echo wp_kses( ddtt_options_tr( 'color_fx_vars', 'Functions and Variables', 'color', null, [ 'default' => '#DCDCAA' ] ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'color_syntax', 'Syntax', 'color', null, [ 'default' => '#569CD6' ] ), $allowed_html ); ?>
                
                <?php echo wp_kses( ddtt_options_tr( 'color_text_quotes', 'Text with Quotes', 'color', null, [ 'default' => '#ACCCCC' ] ), $allowed_html ); ?>
                
            </table>

            <p class="submit"><input type="submit" class="button button-primary" value="Save Changes"></p>

            <br><hr><br></br>
            <h2>Security</h2>
            <table class="form-table">

                <?php echo wp_kses( ddtt_options_tr( 'hide_plugin', 'Hide Plugin', 'checkbox', 'Hides the plugin from the left admin menu and disguises it on the plugins page as "Developer Notifications." Requires you to access this area directly (I recommend bookmarking this page first).' ), $allowed_html ); ?>
                
                <?php $pass = get_option( 'ddtt_pass' ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'enable_pass', 'Require Passwords', 'checkbox', 'Require a password to be entered to access tabs and other pages set below.', [ 'require' => [ 'pass' => [ 'label' => 'Password', 'check' => $pass ] ] ] ), $allowed_html ); ?>

                <?php echo wp_kses( ddtt_options_tr( 'pass', ( $pass ? 'New ' : '' ).'Password', 'password', '<br>'.( $pass ? 'Password has been set. Enter a new one to change it, or leave it blank to keep using the one you set previously.' : 'Set a password of your choosing. Please review your password prior to saving it.' ) ), $allowed_html ); ?>
                
                <?php echo wp_kses( ddtt_options_tr( 'pass_exp', 'Keep Logged In For', 'number', '<br>Enter the number of minutes until you have to re-enter your password.', [ 'width' => '10rem', 'default' => 5 ] ), $allowed_html ); ?>
                
                <?php echo wp_kses( ddtt_options_tr( 'secure_pages', 'Admin Pages to Secure with Password', 'text+', 'All DDT pages are added to this list automatically. If you have additional admin pages you would like users to require a password for, enter the url paths here.', [ 'placeholder' => home_url( DDTT_ADMIN_URL.'/plugins.php' ), 'pattern' => 'https?://.+' ] ), $allowed_html ); ?>
                
            </table>

            <p class="submit"><input type="submit" class="button button-primary" value="Save Changes"></p>
        </div>
    </form>
<?php } ?>