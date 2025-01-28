<?php include 'header.php'; 

// Build the current url
$page = ddtt_plugin_options_short_path();
$tab = 'globalvars';
$current_url = ddtt_plugin_options_path( $tab );

// Check if we are viewing one
if ( $gv = ddtt_get( 'gv' ) ) {

    // Attempt to get it
    global ${$gv};

    // Attempt to print it
    if ( ${ddtt_get( 'gv' )} ) {
        echo '<br><h3><code class="hl" style="font-size: inherit;">$'.esc_attr( $gv ).'</code> returnss:</h3><br>';
        echo '<div class="full_width_container">';
        echo ddtt_print_r( ${$gv} );
        echo '</div>';

    } else {
        echo '<br><h3><code class="hl" style="font-size: inherit;">$'.esc_attr( $gv ).'</code> is not available on this page or does not exist.</h3>';
    }

    // Add some space
    echo '<br><br><hr><br><br>';
}

// Return the table
?>
<div class="full_width_container">
    <table class="admin-large-table">
        <tr>
            <th>Key</th>
            <th>Type</th>
            <th>Inspect</th>
        </tr>
        <?php
        $global_keys = array_keys( $GLOBALS );
        // Custom comparison function to sort keys
        usort( $global_keys, function ( $a, $b ) {
            // If both start with special characters, sort case-insensitively
            if ( preg_match( '/^[^a-zA-Z0-9]/', $a ) && preg_match( '/^[^a-zA-Z0-9]/', $b ) ) {
                return strcasecmp( $a, $b );
            }
            // If only one starts with a special character, it goes first
            if ( preg_match( '/^[^a-zA-Z0-9]/', $a ) ) {
                return -1;
            }
            if ( preg_match( '/^[^a-zA-Z0-9]/', $b ) ) {
                return 1;
            }
            // Otherwise, sort case-insensitively
            return strcasecmp( $a, $b );
        } );        

        // Cycle through the options
        foreach( $global_keys as $key ) {
            $value = $GLOBALS[ $key ];
            $escaped_key = urlencode( $key );
            $type = gettype( $value );
            $inspect_url = $current_url . '&gv=' . $escaped_key;
            ?>
            <tr>
                <td><span class="highlight-variable">$<?php echo esc_attr( $key ); ?></span></td>
                <td><code>(<?php echo esc_attr( $type ); ?>)</code></td>
                <td><a class="button button-primary api-check" href="<?php echo esc_url( $inspect_url ); ?>">Fetch Value</a></td>
            </tr>
            <?php
        }
        ?>
    </table>
</div>