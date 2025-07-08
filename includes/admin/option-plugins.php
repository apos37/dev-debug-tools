<?php include 'header.php'; ?>

<?php 
// Current url
$page = ddtt_plugin_options_short_path();
$tab = 'plugins';
$current_url = ddtt_plugin_options_path( $tab );
$featured_plugins_url = home_url( DDTT_ADMIN_URL.'/plugin-install.php?tab=dev_debug_tools' );

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

// Get who added the plugins
$added_by = get_option( DDTT_GO_PF . 'plugins_added_by', [] );
$unassigned_plugins = array_filter( $plugins_data, function( $plugin_data, $plugin_path ) use ( $added_by ) {
    if ( $plugin_path === 'last_cached' ) {
        return false;
    }
    return empty( $added_by[ 'plugins' ][ $plugin_path ] );
}, ARRAY_FILTER_USE_BOTH );
$show_apply_all_checkbox = ( count( $unassigned_plugins ) > 0 );
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
                    <th class="col-added">Added By</th>
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

                    // Plugin added by
                    $plugin_user_id = $added_by[ 'plugins' ][ $key ] ?? 0;
                    $display_name = '';
                    if ( $plugin_user_id > 0 ) {
                        $user = get_user_by( 'ID', $plugin_user_id );
                        if ( $user ) {
                            $display_name = $user->display_name;
                        } else {
                            $display_name = $added_by[ 'user_ids' ][ $plugin_user_id ] ?? '';
                        }
                    }
                    $added_by_html = $display_name !== ''
                        ? '<span>' . esc_html( $display_name ) . '</span>'
                        : '<em>Unknown</em>';

                    // The table row
                    $results .= '<tr class="' . $active_class . '" data-plugin="' . $key . '">
                        <td>'.$plugin_data[ 'is_active' ].'</td>
                        <td>'.$name.' <em>by '.$plugin_data[ 'author' ].'</em>'.$incl_desc.'</td>
                        '.$site_row.'
                        <td>'.$plugin_data[ 'version' ].'</td>
                        <td class="'.$plugin_data[ 'old_class' ].'">'.$plugin_data[ 'last_updated' ].'</td>
                        <td class="'.$plugin_data[ 'incompatible_class' ].'">'.$plugin_data[ 'compatibility' ].'</td>
                        <td class="'.$size_class.'">'.$folder_size.'</td>
                        <td>'.$key.'</td>
                        <td>'.$plugin_data[ 'last_modified' ].'</td>
                        <td class="added-by-cell">
                            <div class="added-by-display">' . $added_by_html . ' <a href="#" class="edit-added-by" style="display:none;">[Edit]</a></div>
                            <div class="added-by-edit" style="display:none;">
                                <div><em>Update User ID:</em></div>
                                <input type="number" class="added-by-input" value="' . $plugin_user_id . '" />';
                                
                                if ( $show_apply_all_checkbox ) {
                                    $results .= '<label style="display:block;margin-top:4px;">
                                        <input type="checkbox" class="apply-to-all" />
                                        Apply to all unassigned plugins
                                    </label>';
                                }

                                $results .= '<a href="#" class="save-added-by">[Save]</a>
                            </div>
                        </td>
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
        $allowed = wp_kses_allowed_html( 'post' );
        $allowed['input'] = [
            'type'    => true,
            'class'   => true,
            'value'   => true,
            'name'    => true,
            'id'      => true,
            'style'   => true,
            'data-*'  => true, // optional: wildcard for data attributes
        ];
        $allowed['a']['href'] = true;
        $allowed['a']['target'] = true;
        $allowed['a']['class'] = true;

        echo wp_kses( $results, $allowed );

    // No plugins
    } else {

        wp_safe_redirect( $current_url );
        exit;
    }
    ?>
</div>