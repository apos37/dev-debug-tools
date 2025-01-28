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

// Gather server metrics
global $wpdb;

// Server load
$load = function_exists( 'sys_getloadavg' ) ? sys_getloadavg()[0] : 'N/A';

$num_processors = false;

// Try Linux's /proc/cpuinfo
if ( is_readable( '/proc/cpuinfo' ) ) {
    $cpuinfo = file_get_contents( '/proc/cpuinfo' );
    $num_processors = substr_count( $cpuinfo, 'processor' );

// Try shell
} elseif ( function_exists( 'shell_exec' ) ) {
    
    // Try nproc
    $output = shell_exec( 'nproc' );
    if ( is_numeric( trim( $output ) ) ) {
        $num_processors = (int) trim( $output );

    // Try getconf
    } else {
        $output = shell_exec( 'getconf _NPROCESSORS_ONLN' );
        if ( is_numeric( trim( $output ) ) ) {
            $num_processors = (int) trim( $output );
        }
    }
}

$load_percentage = ( $load !== 'N/A' && $num_processors ) ? round( ( $load / $num_processors ) * 100, 2 ) . '%' : 'N/A';

$load_class = 'good'; // Default class
if ( $load_percentage !== 'N/A' ) {
    $load_value = (float) rtrim( $load_percentage, '%' );

    if ( $load_value < 10 ) {
        $load_class = 'optimal'; // Load below 10% is optimal
    } elseif ( $load_value < 50 ) {
        $load_class = 'excellent'; // Load between 10-50% is excellent
    } elseif ( $load_value < 70 ) {
        $load_class = 'good'; // Load between 50-70% is good
    } elseif ( $load_value < 80 ) {
        $load_class = 'moderate'; // Load between 70-80% is moderate
    } elseif ( $load_value < 90 ) {
        $load_class = 'high'; // Load between 80-90% is high
    } elseif ( $load_value <= 100 ) {
        $load_class = 'critical'; // Load exactly at 100% is critical
    } else {
        $load_class = 'overload'; // Load over 100% is overload
    }
}

// Gather memory info
$memory_usage_percentage = 'N/A';
$memory_class = 'good';

if ( is_readable( '/proc/meminfo' ) ) {
    $meminfo = file( '/proc/meminfo' );
    $memory_data = [];

    foreach ( $meminfo as $line ) {
        list( $key, $value ) = explode( ':', $line, 2 ) + [ null, null ];
        $key = trim( $key );
        $value = trim( $value );

        if ( $key && $value ) {
            $memory_data[ $key ] = $value;
        }
    }

    // Extract memory values
    $memory_total = isset( $memory_data[ 'MemTotal' ] ) ? (int) str_replace( ' kB', '', $memory_data[ 'MemTotal' ] ) : 0;
    $memory_free = isset( $memory_data[ 'MemFree' ] ) ? (int) str_replace( ' kB', '', $memory_data[ 'MemFree' ] ) : 0;
    $memory_buffers = isset( $memory_data[ 'Buffers' ] ) ? (int) str_replace( ' kB', '', $memory_data[ 'Buffers' ] ) : 0;
    $memory_cached = isset( $memory_data[ 'Cached' ] ) ? (int) str_replace( ' kB', '', $memory_data[ 'Cached' ] ) : 0;

    // Calculate memory used and available
    $memory_used = $memory_total - $memory_free - $memory_buffers - $memory_cached;

    // Calculate the memory usage percentage
    if ( $memory_total > 0 ) {
        $memory_usage_percentage = round( ( $memory_used / $memory_total ) * 100, 2 );
    }
    
    // Determine the memory usage class based on the percentage
    if ( $memory_usage_percentage < 10 ) {
        $memory_class = 'optimal'; // Memory usage below 10% is optimal
    } elseif ( $memory_usage_percentage < 50 ) {
        $memory_class = 'excellent'; // Memory usage between 10-50% is excellent
    } elseif ( $memory_usage_percentage < 70 ) {
        $memory_class = 'good'; // Memory usage between 50-70% is good
    } elseif ( $memory_usage_percentage < 80 ) {
        $memory_class = 'moderate'; // Memory usage between 70-80% is moderate
    } elseif ( $memory_usage_percentage < 90 ) {
        $memory_class = 'high'; // Memory usage between 80-90% is high
    } else {
        $memory_class = 'critical'; // Memory usage over 90% is critical
    }
}

// Get the menu type setting (Tabs or Dropdown)
$menu_type = sanitize_text_field( get_option( DDTT_GO_PF . 'menu_type', 'Tabs' ) );

// What's new button
$incl_changelog = '';
if ( $tab !== 'changelog' ) {
    $last_viewed_version = sanitize_text_field( get_option( DDTT_GO_PF . 'last_viewed_version' ) );
    if ( !$last_viewed_version || version_compare( DDTT_VERSION, $last_viewed_version, '>' ) ) {
        $incl_changelog = '<a class="see-whats-new" href="' . ddtt_plugin_options_path( 'changelog' ) . '">See what\'s new in version ' . DDTT_VERSION . '! ✨</a>';
    }
}
?>
<style>
.ddtt-header-cont {
    display: flex;
    align-items: center;
}
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

.see-whats-new {
    display: block;
    background-color: #2b6cb0;  /* Darker blue */
    color: #ffffff;  /* White text */
    padding: 42px;
    text-decoration: none;
    font-weight: bold;
    font-size: 1.5rem;
    margin: -30px -40px 30px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    transition: background-color 0.3s ease;
}
.see-whats-new:hover,
.see-whats-new:focus {
    background-color: #1e4e8c;
}
</style>
<div class="wrap" style="padding: 20px; background: #f6f9fc;">

    <?php echo wp_kses_post( $incl_changelog ); ?>

    <div class="admin-title-cont">
        <img src="<?php echo esc_url( DDTT_PLUGIN_IMG_PATH ); ?>logo.png" width="32" height="32" alt="Developer Debug Tools Logo">
        <h1><?php echo esc_attr( DDTT_NAME ); ?><?php echo wp_kses_post( $sfx ); ?></h1>

        <?php if ( ddtt_get( 'settings-updated' ) ) { ?>
            <div id="message" class="updated">
                <p><strong><?php esc_html_e( 'Settings saved.', 'dev_debug_tools' ) ?></strong></p>
            </div>
        <?php } ?>
    </div>

    <div class="info-menu-cont <?php echo esc_attr( str_replace( ' ', '-', strtolower( $menu_type ) ) ); ?>">

        <div>
            <div>
                Plugin <?php echo esc_attr( DDTT_VERSION ) . esc_html( $is_beta ) . ' ' . wp_kses_post( $plugin_warning ); ?> 
                <span class="sep"><?php echo esc_attr( $sep ); ?></span> 
                WP <?php echo esc_attr( $wp_version ) . ' ' . wp_kses_post( $wp_warning ); ?> 
                <span class="sep"><?php echo esc_attr( $sep ); ?></span> 
                PHP <?php echo esc_attr( $php_version ) . ' ' . wp_kses_post( $php_warning ); ?> 
                <span class="sep"><?php echo esc_attr( $sep ); ?></span> 
                <span id="jquery_ver">jQuery </span> 
                <span class="sep"><?php echo esc_attr( $sep ); ?></span> 
                <span id="jquery_mver">jQuery Migrate </span> 
                <span class="sep"><?php echo esc_attr( $sep ); ?></span>
                <span id="abspath">ABSPATH: <?php echo esc_html( ABSPATH ); ?></span>
            </div>
            <div>
                <?php if ( $load !== 'N/A' && $num_processors ) : ?>
                    CPU Load: <span class="cpu-load <?php echo esc_attr( $load_class ); ?>"><?php echo esc_html( round( $load, 2 ) ); ?> (<?php echo esc_html( $num_processors ); ?> processors, <?php echo esc_html( $load_percentage ); ?>) — <?php echo esc_attr( ucwords( $load_class ) ); ?></span>
                <?php endif; ?>

                <?php if ( ( $load !== 'N/A' && $num_processors ) && ( $memory_class !== 'N/A' ) ) : ?>
                    <span class="sep"><?php echo esc_attr( $sep ); ?></span>
                <?php endif; ?>

                <?php if ( $memory_class !== 'N/A' ) : ?>
                    Memory Usage: <span class="memory-usage <?php echo esc_attr( $memory_class ); ?>"><?php echo esc_html( $memory_usage_percentage ); ?>% — <?php echo esc_attr( ucwords( $memory_class ) ); ?></span>
                <?php endif; ?>
            </div>
        </div>

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
        ?>
        <?php if ( $menu_type != 'Side Menu Only' ) { ?>
            <div class="menu-wrapper <?php echo esc_attr( str_replace( ' ', '-', strtolower( $menu_type ) ) ); ?>">
                <?php if ( $menu_type == 'Tabs' ) { ?><nav class="ddtt nav-tab-wrapper"><?php } else { ?><select id="menu-dropdown" onchange="location = this.value;"><?php } ?>

                    <?php
                    foreach ( $menu_items as $key => $menu_item ) { 
                        if ( is_network_admin() && in_array( $key, $multisite_skip ) ) {
                            continue;
                        }
                        if ( !ddtt_is_dev() && isset( $menu_item[2] ) && $menu_item[2] == true ) {
                            continue;
                        }
                        if ( isset( $menu_item[3] ) && $menu_item[3] == true ) {
                            continue;
                        }
                        $slug = $key;
                        $name = $menu_item[0];
                        if ( $slug == 'changelog' ) {
                            continue;
                        }
                        $allowed_html = [ 'span' => [ 'class' => [] ] ];

                        if ( $menu_type == 'Tabs' ) { 
                            ?>
                            <a href="<?php echo esc_url( ddtt_plugin_options_path( $slug ) ); ?>" class="nav-tab <?php if ( $tab === $slug || $tab === null ) : ?>nav-tab-active<?php endif; ?>"><?php echo wp_kses( $name, $allowed_html ); ?></a>
                            <?php
                        } else {
                            ?>
                            <option value="<?php echo esc_url( ddtt_plugin_options_path( $slug ) ); ?>" <?php selected( $tab, $slug ); ?>><?php echo esc_html( strip_tags( $name ) ); ?></option>
                            <?php
                        }
                        ?>    
                    <?php } ?>

                <?php if ( $menu_type == 'Tabs' ) { ?></nav><?php } else { ?></select><?php } ?>
            </div>
        <?php } ?>

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