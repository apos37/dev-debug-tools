<?php include 'header.php'; 

// Build the current url
$page = ddtt_plugin_options_short_path();
$tab = 'defines';
$current_url = ddtt_plugin_options_path( $tab );

// Get the defined constants
$categories = @get_defined_constants( true );

// Return the table
echo '<div class="full_width_container">
    <table class="admin-large-table">
        <tr>
            <th>Category</th>
            <th>Constant</th>
            <th>Value</th>
        </tr>';

        // Cycle through the cateogories
        foreach ( $categories as $category => $constants ) {

            // Cycle through the constants
            foreach ( $constants as $constant => $value ) {
                if ( $constant == 'PHP_EOL' ) {
                    $value = ddtt_convert_php_eol_to_string();
                }
    
                // Add it
                echo '<tr>
                    <td><span class="highlight-variable">'.esc_attr( $category ).'</span></td>
                    <td><span class="highlight-variable">'.esc_attr( $constant ).'</span></td>
                    <td>'.esc_html( $value ).'</td>
                </tr>';
            }

        }

echo '</table>
</div>';