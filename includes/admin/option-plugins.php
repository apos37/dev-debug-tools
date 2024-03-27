<?php include 'header.php'; ?>

<?php 
$page = ddtt_plugin_options_short_path();
$tab = 'plugins';
$current_url = ddtt_plugin_options_path( $tab );
?>

<br><br>
<div class="full_width_container">
    <?php if ( ddtt_get( 'simple_plugin_list', '==', 'true' ) ) { ?>
        <a href="<?php echo esc_url( $current_url ); ?>">View Table</a>
    <?php } else { ?>
        <a href="<?php echo esc_url( $current_url ); ?>&simple_plugin_list=true">View Simple List</a>
    <?php } ?>
    <br><br>
    <?php 
    // Defaults
    $link = true;
    $path = true;
    $table = true;
    
    // Convert to simple list if in query string
    if ( ddtt_get( 'simple_plugin_list', '==', 'true' ) ) {
        $path = false;
        $table = false;
    }

    // Store the plugins for all sites here
    $plugins = [];

    // If on the network, let's get all the sites plugins, not just the local
    if ( is_multisite() ) {

        // Get the network active plugins
        $network_active = get_site_option( 'active_sitewide_plugins' );

        // Add them to the active array
        foreach ( $network_active as $na_key => $na ) {
            $plugins[ $na_key ][] = 'network';
        }

        // Get all the sites
        global $wpdb;
        $subsites = $wpdb->get_results( "SELECT blog_id, domain, path FROM $wpdb->blogs WHERE archived = '0' AND deleted = '0' AND spam = '0' ORDER BY blog_id" );

        // Iter the sites
        if ( $subsites && !empty( $subsites ) ) {
            foreach( $subsites as $subsite ) {

                // Get the plugins
                $site_active = get_blog_option( $subsite->blog_id, 'active_plugins' );

                // Iter each plugin
                foreach ( $site_active as $p_path ) {
                    
                    // Add the site
                    $plugins[ $p_path ][] = $subsite->blog_id;
                }
            }
        }

    // If not on multisite network
    } else {

        // Get the active plugins
        $site_active = get_option( 'active_plugins' );

        // Iter each plugin
        foreach ( $site_active as $site ) {
            $plugins[ $site ] = 'local';
        }
    }

    // Get all the plugins full info
    $all = get_plugins();

    // Iter each
    foreach ( $all as $k => $a ) {

        // Add the non-active plugins
        if ( !array_key_exists( $k, $plugins ) ) {
            $plugins[ $k ] = false;
        }
    }

    // Start the table if we're building one
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
                <th class="col-updated">Last Updated</th>
                <th class="col-compatible">WP Compatibility</th>
                <th class="col-size">Folder Size</th>';

        if ( $path ) {
            $results .= '<th class="col-path">Path to Main File</th>';
        }

        $results .= '<th class="col-date">Last Modified</th>
            </tr>';
    } else {

        // Set an empty array
        $activated_plugins = [];
    }

    // Store the new array here so we can sort them by name
    $sorted_plugins = [];

    // Get the full info for the plugins
    foreach ( $plugins as $key => $p ) {      
        
        // Make sure the plugin exists
        if ( isset( $all[ $key ] ) ) {

            // Get the plugin name
            $name = $all[ $key ][ 'Name' ];

            // Add to sorted array
            $sorted_plugins[ $name ] = [
                'path' => $key,
                'p'    => !$p ? [] : ( !is_array( $p ) ? [ $p ] : $p )
            ];
        }
    }

    // Sort them
    if ( $table ) {
        uksort( $sorted_plugins, 'strcasecmp' );
    }

    // Get the full info for the plugins
    foreach ( $sorted_plugins as $name => $args ) {      

        // Set the key/path
        $key = $args[ 'path' ];
        $p = $args[ 'p' ];
        
        // Make sure the plugin exists
        if ( isset( $all[ $key ] ) ) {
            
            // Check if the plugin has a Plugin URL
            if ( $link ) {
                if ( $all[ $key ][ 'PluginURI' ] && $all[ $key ][ 'PluginURI' ] != '' ) {
                    $display_name = '<a href="'.$all[ $key ][ 'PluginURI' ].'" target="_blank">'.$name.'</a>';
                } elseif ( $all[ $key ][ 'AuthorURI' ] && $all[ $key ][ 'AuthorURI' ] != '' ) {
                    $display_name = '<a href="'.$all[ $key ][ 'AuthorURI' ].'" target="_blank">'.$name.'</a>';
                } else {
                    $display_name = $name;
                }
            } else {
                $display_name = $name;
            }

            // Add author to name
            if ( $all[ $key ][ 'Author' ] && $all[ $key ][ 'Author' ] != '' ) {
                $display_name = $display_name.' <em>by '.$all[ $key ][ 'Author' ].'</em>';
            } elseif ( $all[ $key ][ 'AuthorName' ] && $all[ $key ][ 'AuthorName' ] != '' ) {
                $display_name = $display_name.' <em>by '.$all[ $key ][ 'AuthorName' ].'</em>';
            }

            // Add description
            if ( $table && $all[ $key ][ 'Description' ] && $all[ $key ][ 'Description' ] != '' ) {
                $display_name = $display_name.'<br>'.$all[ $key ][ 'Description' ];
            }

            // Get the last updated date and tested up to version
            $last_updated = '';
            $old_class = '';
            $compatibility = '';
            $incompatible_class = '';
            $args = [ 
                'slug' => $all[ $key ][ 'TextDomain' ], 
                'fields' => [
                    'last_updated' => true,
                    'tested' => true
                ]
            ];
            $response = wp_remote_post(
                'http://api.wordpress.org/plugins/info/1.0/',
                [
                    'body' => [
                        'action' => 'plugin_information',
                        'request' => serialize( (object)$args )
                    ]
                ]
            );
            if ( !is_wp_error( $response ) ) {
                $returned_object = unserialize( wp_remote_retrieve_body( $response ) );   
                if ( $returned_object ) {
                    
                    // Last Updated
                    if ( $name != 'Hello Dolly' ) {
                        $last_updated = $returned_object->last_updated;
                        $last_updated = ddtt_time_elapsed_string( $last_updated );
                        
                        // Add old class if more than 11 months old
                        $earlier = new DateTime( $last_updated );
                        $today = new DateTime( date( 'Y-m-d' ) );
                        $diff = $today->diff( $earlier )->format("%a");
                        if ( $diff >= 335 ) {
                            $old_class = ' warning';
                        }

                        // Compatibility
                        $compatibility = $returned_object->tested;

                        // Add incompatibility class
                        global $wp_version;
                        if ( $compatibility < $wp_version ) {
                            $incompatible_class = ' warning';
                        }
                    } else {
                        $last_updated = 'just now';
                        $compatibility = '';
                    }
                }
            }

            // Displaying path?
            if ( $path && $table ) {
                $display_path = '<td>'.$key.'</td>';
            } elseif ( $path && !$table ) {
                $display_path = ' ('.$key.')';
            } else {
                $display_path = '';
            }

            // Get the folder size
            if ( !function_exists( 'get_dirsize' ) ) {
                require_once ABSPATH.WPINC.'/ms-functions.php';
            }

            // Strip the path to get the folder
            $p_parts = explode( '/', $key );
            $folder = $p_parts[0];
             
            // Get the path of a directory.
            $directory = get_home_path().DDTT_PLUGINS_URL.'/'.$folder.'/';
             
            // Get the size of directory in bytes.
            $bytes = get_dirsize( $directory );
            
            // Get the MB
            // $folder_size = number_format( $bytes / ( 1024 * 1024 ), 1 ) . ' MB';
            $folder_size = ddtt_format_bytes( $bytes );

            // Get the last modified date and convert to developer's timezone
            if ( $name != 'Hello Dolly' ) {
                $utc_time = date( 'Y-m-d H:i:s', filemtime( $directory ) );
                $dt = new DateTime( $utc_time, new DateTimeZone( 'UTC' ) );
                $dt->setTimezone( new DateTimeZone( get_option( 'ddtt_dev_timezone', wp_timezone_string() ) ) );
                $last_modified = $dt->format( 'F j, Y g:i A T' );
            } else {
                $last_modified = '';
            }

            // Are we putting it in a table or no?
            if ( $table ) {

                // If plugin is active or on multisite
                if ( !empty( $p ) ) {
                    

                    // If on multisite
                    if ( is_multisite() ) {

                        // If network activated
                        if ( in_array( 'network', $p ) ) {
                            $is_active = 'Network';
                            $active_class = 'active';

                        // If on this site
                        } elseif ( ( !is_network_admin() && in_array( get_current_blog_id(), $p ) ) || is_network_admin() ) {
                            $is_active = 'Local Only';
                            $active_class = 'active';

                        // If not on this site
                        } else {
                            $is_active = 'No';
                            $active_class = 'inactive';
                        }
                    } else {
                        $is_active = 'Yes';
                        $active_class = 'active';
                    }

                // If inactive and not on network
                } else {
                    $is_active = 'No';
                    $active_class = 'inactive';
                }

                // If on multisite network
                if ( is_network_admin() ) {
                    if ( !empty( $p ) ) {
                        $site_names = [];
                        if ( in_array( 'network', $p ) ) {
                            $site_names[] = 'Network Active';
                        } else {
                            foreach ( $p as $site_id ) {
                                $site_names[] = 'ID:'.$site_id.' - '.get_blog_details( $site_id )->blogname;
                            }
                        }
                        $site_names = implode( '<br>', $site_names );
                    } else {
                        $site_names = 'None';
                    }
                    $site_row = '<td>'.$site_names.'</td>';
                } else {
                    $site_row = '';
                }

                // The table row
                $results .= '<tr class="'.$active_class.'">
                    <td>'.$is_active.'</td>
                    <td>'.$display_name.'</td>
                    '.$site_row.'
                    <td>Version '.$all[ $key ]['Version'].'</td>
                    <td class="'.$old_class.'">'.$last_updated.'</td>
                    <td class="'.$incompatible_class.'">'.$compatibility.'</td>
                    <td>'.$folder_size.'</td>
                    '.$display_path.'
                    <td>'.$last_modified.'</td>
                </tr>';
            } else {

                // Otherwise we are displaying in a single line
                $activated_plugins[ $name ] = $display_name.' - Version '.$all[ $key ]['Version'].$display_path;
            }
        }           
    }

    // End the table if we're building one
    if ( $table ) {
        $results .= '</table>';

    } else {

        // Sort
        ksort( $activated_plugins );

        // Or else implode each line as a string
        $results = '<div id="active-plugin-list">'.implode( '<br>', $activated_plugins ).'</div>';
    }

    // Return how we want to
    echo wp_kses_post( $results );
    ?>
</div>