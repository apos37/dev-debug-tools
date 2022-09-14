<?php include 'header.php'; ?>

<?php
// Get the php.inis
$all_options = ini_get_all( null, false );

// Return the table
echo '<div class="full_width_container">
    <table class="admin-large-table">
        <tr>
            <th>Registered Configuration Option</th>
            <th>Value</th>
        </tr>';

        // Cycle through the options
        foreach( $all_options as $option => $value ) {
            echo '<tr>
                <td>'.esc_attr( $option ).'</td>
                <td>'.esc_html( $value ).'</td>
            </tr>';
        }

echo '</table>
</div>';