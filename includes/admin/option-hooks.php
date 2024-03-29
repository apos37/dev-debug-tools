<!-- Add CSS to this table only -->
<style>
.admin-large-table td {
    vertical-align: top;
}
code {
    padding: 0;
    margin: 0;
}
</style>

<?php include 'header.php'; ?>

<?php 
$hooks = [
    [ 
        'type' => 'Filter',
        'hook' => 'ddtt_wpconfig_snippets',
        'desc' => 'Add, remove or modify a snippet from <a href="'.ddtt_plugin_options_path( 'wpcnfg' ).'">WP-CONFIG</a>.<br><em><a href="https://developer.wordpress.org/apis/wp-config-php/" target="_blank">Find more snippets here.</a></em>',
    ],
    [ 
        'type' => 'Filter',
        'hook' => 'ddtt_htaccess_snippets', 
        'desc' => 'Add, remove or modify a snippet from <a href="'.ddtt_plugin_options_path( 'htaccess' ).'">HTACCESS</a>.<br><em><a href="https://github.com/phanan/htaccess" target="_blank">Find more snippets here.</a></em>' 
    ],
    [ 
        'type' => 'Filter',
        'hook' => 'ddtt_highlight_debug_log', 
        'desc' => 'Change highlight colors on the <a href="'.ddtt_plugin_options_path( 'logs' ).'">debug log</a>.' 
    ],
    [ 
        'type' => 'Filter',
        'hook' => 'ddtt_debug_log_help_col', 
        'desc' => 'Add, remove or modify the <a href="'.ddtt_plugin_options_path( 'logs' ).'">debug log</a> search links in the Help column.' 
    ],
    [ 
        'type' => 'Filter',
        'hook' => 'ddtt_debug_log_max_filesize', 
        'desc' => 'Change the max file size for the <a href="'.ddtt_plugin_options_path( 'logs' ).'">debug log</a> viewer. Default is 2 MB.' 
    ],
    [ 
        'type' => 'Filter',
        'hook' => 'ddtt_resource_links', 
        'desc' => 'Add your own link to <a href="'.ddtt_plugin_options_path( 'resources' ).'">Resources</a>.' 
    ],
    [ 
        'type' => 'Filter',
        'hook' => 'ddtt_recommended_plugins', 
        'desc' => 'Add or remove a <a href="/'.DDTT_ADMIN_URL.'/plugin-install.php?tab=featured">recommended plugin</a>.<br><br><em>Note that you can only add plugins that are in the <a href="https://wordpress.org/plugins/" target="_blank">WordPress.org repository</a>.</em>' 
    ],
    [ 
        'type' => 'Filter',
        'hook' => 'ddtt_quick_link_icon', 
        'desc' => 'Change the Quick Debug Link icon when quick links are added to posts and users in admin lists.' 
    ],
    [ 
        'type' => 'Filter',
        'hook' => 'ddtt_quick_link_post_types', 
        'desc' => 'Add or remove post types that include quick links when enabled.' 
    ],
    [
        'type' => 'Filter',
        'hook' => 'ddtt_admin_bar_dropdown_links',
        'desc' => 'Add a link to the admin bar site name dropdown on the front-end.'
    ],
    [
        'type' => 'Filter',
        'hook' => 'ddtt_admin_bar_condensed_items',
        'desc' => 'Modify the admin bar icons that get condensed when the option is set.'
    ],
    [
        'type' => 'Filter',
        'hook' => 'ddtt_omit_shortcodes',
        'desc' => 'Omit shortcodes from the admin bar shortcode finder.'
    ],
    [ 
        'type' => 'Action',
        'hook' => 'ddtt_admin_list_update_each_user', 
        'desc' => 'Do something for each <strong>user</strong> when you load the <a href="/'.DDTT_ADMIN_URL.'/users.php"><em>user</em> admin list</a>. Must have Quick Debug Links enabled in <a href="'.ddtt_plugin_options_path( 'settings' ).'">Settings</a>.<br><br>Why? Because sometimes we have to update meta keys for all users (sometimes in the thousands), and running a function using pagination on a smaller amount of users at a time is better for testing, processing, preventing time-outs, etc. It\'s also easier to do this than to code a pagination script that you\'re only going to use once.'
    ],
    [ 
        'type' => 'Action',
        'hook' => 'ddtt_admin_list_update_each_post', 
        'desc' => 'Do something for each <strong>post</strong> or <strong>page</strong> when you load the <a href="/'.DDTT_ADMIN_URL.'/edit.php"><em>post</em> or <em>page</em> admin lists</a>. Must have Quick Debug Links enabled in <a href="'.ddtt_plugin_options_path( 'settings' ).'">Settings</a>.<br><br>Why? Because sometimes we have to update meta keys for all posts (sometimes in the thousands), and running a function using pagination on a smaller amount of posts at a time is better for testing, processing, preventing time-outs, etc. It\'s also easier to do this than to code a pagination script that you\'re only going to use once.'
    ],
    [
        'type' => 'Action',
        'hook' => 'ddtt_on_update_user_meta',
        'desc' => 'Do something when you <a href="'.ddtt_plugin_options_path( 'usermeta' ).'">update user meta</a>.'
    ],
    [
        'type' => 'Action',
        'hook' => 'ddtt_on_update_post_meta',
        'desc' => 'Do something when you <a href="'.ddtt_plugin_options_path( 'postmeta' ).'">update post meta</a>.'
    ],
    [ 
        'type' => 'Filter',
        'hook' => 'ddtt_ignore_pages_for_discord_notifications', 
        'desc' => 'Add, remove or modify pages that should be ignored when using Discord Notifications found in <a href="'.ddtt_plugin_options_path( 'settings' ).'">settings</a> under Show Online Users.' 
    ]
];
?>

<p><strong>Where do I add these hooks?</strong></p>
<p>You can place them in your <code>functions.php</code> file, or if you feel uncomfortable doing so you can use the <a href="https://wordpress.org/plugins/code-snippets/" target="_blank">Code Snippets</a> <em>by Code Snippets Pro</em> plugin to add code safely.</p>

<br><br>
<div class="full_width_container">
    <table class="admin-large-table">
        <tr>
            <th style="width: 300px;">Description</th>
            <th style="width: auto;">Hook</th>
            <th style="width: auto;">Example Usage</th>
        </tr>
        <?php
        // Add the hooks
        foreach ( $hooks as $hook ) {

            // The short path to the file
            $filepath = DDTT_PLUGIN_FILES_PATH . 'hooks/'.$hook[ 'hook' ].'.php';

            // Make sure it exists, and if so, get the full path
            if ( is_readable( rtrim( ABSPATH, '/' ) . $filepath ) ) {
                $file = rtrim( ABSPATH, '/' ) . $filepath;
            } elseif ( is_readable( dirname( ABSPATH ) . $filepath ) ) {
                $file = dirname( ABSPATH ) . $filepath;
            } else {
                $file = false;
            }
            // dpr( $file );

            // Add the snippet row
            if ( $file ) {
                ?>
                <tr id="<?php echo esc_attr( $hook[ 'hook' ] ); ?>">
                    <td><?php echo wp_kses_post( $hook[ 'desc' ] ); ?></td>
                    <td><code class="hl"><strong><?php echo esc_attr( $hook[ 'hook' ] ); ?></strong></code><br><br><strong>TYPE &#8674;</strong> <?php echo esc_attr( $hook[ 'type' ] ); ?></td>
                    <td class="usage"><?php ddtt_highlight_file2( $file, false ); ?><br><br></td>
                </tr>
                <?php
            }
        }
        ?>
    </table>
</div>