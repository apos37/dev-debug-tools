<?php
/**
 * Admin options page.
 */


// Get the active tab
$tab = ddtt_get( 'tab' ) ?? 'debug';

// Include the admin page CSS
include DDTT_PLUGIN_ADMIN_PATH.'css/style.php';

// Get the tabs
$menu_items = ddtt_plugin_menu_items();

// Header separator
$sep = '|';

// Multisite header
$sfx = ddtt_multisite_suffix();

// Updates url
$updates_url = ddtt_admin_url( 'update-core.php' );

// Beta
if ( defined( 'DDTT_BETA' ) && DDTT_BETA ) {
    $is_beta = ' (BETA)';
} else {
    $is_beta = '';
}

// Check if we have the latest plugin version
$plugin_warning = '';
$latest_plugin = ddtt_get_latest_plugin_version();
if ( version_compare( DDTT_VERSION, $latest_plugin, '<' ) ) {
    
    // Add the warning
    $plugin_warning = '<div class="tooltip"><a href="'.$updates_url.'"><span class="warning-symbol"></span></a>
        <span class="tooltiptext">A newer version of this plugin is available ('.$latest_plugin.')</span>
    </div>';
}

// Current WordPress Version
global $wp_version;

// Check if we have the latest version
$wp_warning = '';
$latest_wp = get_site_transient( 'update_core' );
if ( is_object( $latest_wp ) && isset( $latest_wp->updates[0]->version ) && $wp_version !== $latest_wp->updates[0]->version ) {

    // Add the warning
    $wp_warning = '<div class="tooltip"><a href="'.$updates_url.'"><span class="warning-symbol"></span></a>
        <span class="tooltiptext">A newer version of WordPress is available ('.$latest_wp->updates[0]->version.')</span>
    </div>';
}

// Get the current php version
$php_version = phpversion();

// Get the latest version of PHP
$php_warning = '';
$latest_php = ddtt_get_latest_php_version( true );
if ( floatval( $php_version ) < floatval( $latest_php ) ) {
    
    // Add the warning
    $php_warning = '<div class="tooltip"><span class="warning-symbol"></span>
        <span class="tooltiptext">A new major version of PHP is available ('.$latest_php.'.x)</span>
    </div>';
}
?>
<style>
.admin-title-cont {
    vertical-align: middle;
}
.admin-title-cont img {
    margin-right: 10px;
}
.admin-title-cont h1 {
    font-size: 1.73rem; 
    display: inline-block;
}
.sep {
    color: #37373D;
    margin: 0 5px;
}
</style>
<div class="wrap" style="padding: 20px; background: #f6f9fc;">
    <div class="admin-title-cont">
        <img src="<?php echo esc_url( DDTT_PLUGIN_IMG_PATH ); ?>logo.png" width="32" height="32" alt="Developer Debug Tools Logo">
        <h1><?php echo esc_attr( DDTT_NAME ); ?><?php echo wp_kses_post( $sfx ); ?></h1>
    </div>
    <div>Plugin <?php echo esc_attr( DDTT_VERSION ).$is_beta.' '.wp_kses_post( $plugin_warning ); ?> <span class="sep"><?php echo esc_attr( $sep ); ?></span> WP <?php echo esc_attr( $wp_version ).' '.wp_kses_post( $wp_warning ); ?> <span class="sep"><?php echo esc_attr( $sep ); ?></span> PHP <?php echo esc_attr( $php_version ).' '.wp_kses_post( $php_warning ); ?> <span class="sep"><?php echo esc_attr( $sep ); ?></span> <span id="jquery_ver">jQuery </span> <span class="sep"><?php echo esc_attr( $sep ); ?></span> <span id="jquery_mver">jQuery Migrate </span></div>

    <?php if ( ddtt_get( 'settings-updated' ) ) { ?>
        <div id="message" class="updated">
            <p><strong><?php _e( 'Settings saved.', 'dev_debug_tools' ) ?></strong></p>
        </div>
    <?php } ?>

    <br><br>
    <div class="tabs-wrapper">
        <nav class="nav-tab-wrapper">
            <?php
            // Skip if multisite
            $multisite_skip = [
                'siteoptions',
                'usermeta',
                'postmeta',
                'scfinder',
                'regex',
                'siteoptions',
                'testing'
            ];

            // Iter the menu items
            foreach ( $menu_items as $key => $menu_item ) { 
                // Skip if multisite
                if ( is_network_admin() && in_array( $key, $multisite_skip ) ) {
                    continue;
                }

                // Skip if no access
                if ( !ddtt_is_dev() && isset( $menu_item[2] ) && $menu_item[2] == true ) {
                    continue;
                }

                // Skip if hidden subpage
                if ( isset( $menu_item[3] ) && $menu_item[3] == true ) {
                    continue;
                }

                // Set the vars
                $slug = $key;
                $name = $menu_item[0];

                // Skip Changelog
                if ( $slug == 'changelog' ) {
                    continue;
                }

                // Sanitize name
                $allowed_html = [
                    'span' => [
                        'class' => []
                    ]
                ];
                ?>
                <a href="<?php echo esc_url( ddtt_plugin_options_path( $slug ) ); ?>" class="nav-tab <?php if ( $tab === $slug || $tab === null ) : ?>nav-tab-active<?php endif; ?>"><?php echo wp_kses( $name, $allowed_html ); ?></a>
            <?php } ?>
        </nav>
    </div>

    <div class="tab-content">
        <?php
        foreach ( $menu_items as $key => $menu_item ) {
            if ( $tab === $key ) { 
                include 'option-'.$key.'.php';
            }
        }

        // What to do if there is no tab?
        if ( !ddtt_get( 'tab' ) || !array_key_exists( ddtt_get( 'tab' ), $menu_items ) ) {
            ?>
            <br><br>
            <?php
            wp_safe_redirect( ddtt_plugin_options_path( 'settings' ) );
        }
        ?>
    </div>
</div>

<!-- Get the jQuery versions -->
<script>
var jqversion = jQuery.fn.jquery
var jqmversion = jQuery.migrateVersion;
document.getElementById( "jquery_ver" ).innerHTML += jqversion;
document.getElementById( "jquery_mver" ).innerHTML += jqmversion;
</script>