<?php include 'header.php'; 

// Build the current url
$page = ddtt_plugin_options_short_path();
$tab = 'db';
$current_url = ddtt_plugin_options_path( $tab );

// Table info
global $wpdb;
$db_info = [
    [
        'label' => 'Database Name',
        'value' => '<span class="redact">'.DB_NAME.'</span>',
    ],
    [
        'label' => 'Database Version',
        'value' => get_option( 'db_version' ),
    ],
    [
        'label' => 'Table Prefix',
        'value' => $wpdb->prefix,
    ],
];

// Return the table
echo '<div class="full_width_container">
<table class="admin-large-table">
    <tr>
        <th width="300px">Database Info</th>
        <th>Value</th>
    </tr>';

    // Cycle through the constants
    foreach ( $db_info as $info ) {
    
        // Add it
        echo '<tr>
            <td><span class="highlight-variable">'.esc_attr( $info[ 'label' ] ).'</span></td>
            <td>'.wp_kses( $info[ 'value' ], [ 'span' => [ 'class' => [] ] ] ).'</td>
        </tr>';
    }

echo '</table>
</div><br><br>';

// Create connection
$conn = new mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );

// Check connection
if ( $conn->connect_error ) {
  die( "Connection failed: " . $conn->connect_error );
}

// Get table names
$sql = "SHOW TABLES";
$result = $conn->query( $sql );

// If we found any
if ( $result->num_rows > 0 ) {

    // Return the table
    echo '<div class="full_width_container">
        <table class="admin-large-table">
            <tr>
                <th>Table Name</th>
            </tr>';

            // Loop
            while( $row = $result->fetch_assoc() ) {

                // Add it
                echo '<tr>
                    <td><span class="highlight-variable">'.esc_attr( $row[ 'Tables_in_'.DB_NAME ] ).'</span></td>
                </tr>';

            }

    echo '</table>
    </div>';
}

// Close the connection
$conn->close();