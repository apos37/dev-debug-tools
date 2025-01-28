<?php include 'header.php'; ?>

<?php 
// Current url
$page = ddtt_plugin_options_short_path();
$tab = 'plugins';
$current_url = ddtt_plugin_options_path( $tab );
$featured_plugins_url = home_url( DDTT_ADMIN_URL.'/plugin-install.php?tab=featured' );

// Cached data
$plugins_data = get_transient( DDTT_GO_PF.'plugins_data' );
$force_get = ddtt_get( 'force_get', '==', 'true' );
if ( $force_get ) {
    ddtt_remove_qs_without_refresh( 'force_get' );
}
$recache = ( $plugins_data === false || $force_get );
if ( $recache ) {
    $plugins_data = ddtt_get_plugins_data();
}

// Last cached date
$last_cached = ( $plugins_data && isset( $plugins_data[ 'last_cached' ] ) && !$force_get ) ? $plugins_data[ 'last_cached' ] : gmdate( 'Y-m-d H:i:s' );
$display_last_cached = ddtt_convert_timezone( $last_cached );
?>

<br><br>
<div class="full_width_container">
    <?php if ( ddtt_get( 'simple_plugin_list', '==', 'true' ) ) { ?>
        <a class="button button-secondary" href="<?php echo esc_url( $current_url ); ?>">View Table</a>
    <?php } else { ?>
        <a class="button button-secondary" href="<?php echo esc_url( $current_url ); ?>&simple_plugin_list=true">View Simple List</a>
    <?php } ?>
    <a class="button button-secondary" href="<?php echo esc_url( $featured_plugins_url ); ?>">Recommended Plugins</a>
    <span style="float: right;"><strong>Last Cached:</strong> <?php echo esc_html( $display_last_cached ); ?> [<a href="<?php echo esc_url( add_query_arg( 'force_get', 'true', $current_url ) ); ?>">UPDATE NOW</a>]</span>
    <br><br><br>
    
    <?php 
    // Defaults
    $link = true;
    $table = true;
    
    // Convert to simple list if in query string
    if ( ddtt_get( 'simple_plugin_list', '==', 'true' ) ) {
        $table = false;
    }

    // Validation
    if ( !empty( $plugins_data ) ) {

        // Let's remove the last updated date so we can sort properly
        unset( $plugins_data[ 'last_cached' ] );

        // Sort by name
        uasort( $plugins_data, function( $a, $b ) {
            return strcasecmp( $a[ 'name' ], $b[ 'name' ] );
        } );
        
        /**
         * Display the data
         */
        if ( $table ) {

            // If on multisite, we need a site column
            if ( is_network_admin() ) {
                $site_col = '<th class="col-site">Sites</th>';
            } else {
                $site_col = '';
            }

            // The table
            $results = '<p><em>Note: some plugins may be missing last updated and WP compatibility if they were not downloaded from WP.org. These are usually premium/paid plugins.</em></p>
            <p><em>Items showing <span class="red-example">red</span> may be outdated and should be used with caution.</em></p>
            <table id="active-plugin-list" class="admin-large-table alternate-row">
                <tr>
                    <th class="col-active">Active</th>
                    <th class="col-plugin">Plugin</th>
                    '.$site_col.'
                    <th class="col-version">Version</th>
                    <th class="col-updated">Last Updated by Author</th>
                    <th class="col-compatible">WP Compatibility</th>
                    <th class="col-size">Folder Size</th>
                    <th class="col-path">Path to Main File</th>
                    <th class="col-date">Last Modified on Server</th>
                </tr>';

                // Iter the plugins
                foreach ( $plugins_data as $key => $plugin_data ) {

                    // Name
                    $name = $plugin_data[ 'name' ];
                    $url = $plugin_data[ 'url' ];
                    if ( $link && $url ) {
                        $name = '<a href="'.$url.'" target="_blank">'.$name.'</a>';
                    }

                    // Only display active plugins
                    $active_class = ( $plugin_data[ 'is_active' ] == 'No' ) ? 'inactive' : 'active';

                    // Description
                    $incl_desc = ( $plugin_data[ 'description' ] != '' ) ? '<br>'.$plugin_data[ 'description' ] : '';

                    // If on multisite network
                    if ( is_network_admin() && $plugin_data[ 'site_names' ] != '' ) {
                        $site_row = '<td>'.$plugin_data[ 'site_names' ].'</td>';
                    } else {
                        $site_row = '';
                    }

                    // Folder size
                    $bytes = $plugin_data[ 'folder_size' ];

                    // Define the size threshold in bytes (2MB)
                    $threshold = 2 * 1024 * 1024; // 2MB in bytes

                    // Check if the size exceeds the threshold
                    if ( $bytes == 'Unknown' ) {
                        $size_class = 'unknown';
                    } elseif ( $bytes > $threshold ) {
                        $size_class = 'size-large';
                    } else {
                        $size_class = 'size-small';
                    }
                    
                    // Get the MB
                    $folder_size = $bytes == 'Unknown' ? 'Unknown' : ddtt_format_bytes( $bytes );

                    // The table row
                    $results .= '<tr class="'.$active_class.'">
                        <td>'.$plugin_data[ 'is_active' ].'</td>
                        <td>'.$name.' <em>by '.$plugin_data[ 'author' ].'</em>'.$incl_desc.'</td>
                        '.$site_row.'
                        <td>'.$plugin_data[ 'version' ].'</td>
                        <td class="'.$plugin_data[ 'old_class' ].'">'.$plugin_data[ 'last_updated' ].'</td>
                        <td class="'.$plugin_data[ 'incompatible_class' ].'">'.$plugin_data[ 'compatibility' ].'</td>
                        <td class="'.$size_class.'">'.$folder_size.'</td>
                        <td>'.$key.'</td>
                        <td>'.$plugin_data[ 'last_modified' ].'</td>
                    </tr>';
                }

            // End the table
            $results .= '</table>';

        } else {

            // Display key
            $incl_key = false;
            $display_key = $incl_key ? ' ('.$key.')' : '';

            // Or else list
            $results = '<div id="active-plugin-list">';

            // Iter the plugins
            foreach ( $plugins_data as $key => $plugin_data ) {

                // Only display active plugins
                if ( $plugin_data[ 'is_active' ] == 'No' ) {
                    continue;
                }

                // The row
                $results .= '<div>'.$plugin_data[ 'name' ].' - Version '.$plugin_data[ 'version' ].$display_key.'</div>';
            }

            // End container
            $results .= '</div>';
        }

        // Return how we want to
        echo wp_kses_post( $results );

    // No plugins
    } else {

        wp_safe_redirect( $current_url );
        exit;
    }
    ?>
</div>