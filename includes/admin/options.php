<?php
/**
 * Admin options page.
 */

// Current WordPress Version
global $wp_version;

// Get the active tab
$tab = ddtt_get( 'tab' ) ?? 'debug';

// Include the admin page CSS
include DDTT_PLUGIN_ADMIN_PATH.'css/style.php';

// Get the tabs
$menu_items = ddtt_plugin_menu_items();
?>
<div class="wrap" style="padding: 20px; background: #f6f9fc;">
    <img class="admin_helpbox_title" src="<?php echo esc_url( DDTT_PLUGIN_IMG_PATH ); ?>logo.png" width="64" height="64">
    <h1><?php echo esc_attr( DDTT_NAME ); ?></h1>
    <span style="margin-top: -10px;">PLUGIN VERSION <?php echo esc_attr( DDTT_VERSION ); ?></span><br>
    <span>WordPress Version <?php echo esc_attr( $wp_version ); ?></span><br>
    <span>PHP Version <?php echo esc_attr( phpversion() ); ?></span><br>
    <span id="jquery_ver">jQuery Version </span><br>
    <span id="jquery_mver">jQUery Migrate Version </span>

    <?php if ( ddtt_get( 'settings-updated' ) ) { ?>
        <div id="message" class="updated">
            <p><strong><?php _e( 'Settings saved.', 'dev_debug_tools' ) ?></strong></p>
        </div>
    <?php } ?>

    <br><br>
    <div class="tabs-wrapper">
        <nav class="nav-tab-wrapper">
            <?php
            foreach ( $menu_items as $key => $menu_item ) { 
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
                if ($slug == 'changelog') {
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