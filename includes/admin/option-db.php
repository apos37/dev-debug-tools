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
$sql = "SHOW TABLES LIKE '{$wpdb->prefix}%'";
$result = $conn->query( $sql );

// If we found any
if ( $result->num_rows > 0 ) {

    // Start the table
    echo '<div class="full_width_container">
        <table class="admin-large-table">
            <tr>
                <th style="width: 300px;">Table Name</th>
                <th>Columns</th>
            </tr>';

            // Loop through each table
            while( $row = $result->fetch_row() ) {
                $table_name = $row[0];
        
                // Query to get columns of the current table
                $sql_columns = "SHOW COLUMNS FROM `$table_name`";
                $result_columns = $conn->query( $sql_columns );

                // Validate
                if ( $result_columns->num_rows > 0 ) {
                    
                    // Fetch each column name
                    $table_columns = [];
                    while ( $col_row = $result_columns->fetch_assoc() ) {
                        $table_columns[] = $col_row[ 'Field' ];
                    }

                    // Output table name and columns
                    echo '<tr>
                        <td><span class="highlight-variable">'.esc_attr( $table_name ).'</span></td>
                        <td>'.wp_kses( implode( '<br>', $table_columns ), [ 'br' => [] ] ).'</td>
                    </tr>';
                }
            }

    echo '</table>
    </div>';
}

// Close the connection
$conn->close();