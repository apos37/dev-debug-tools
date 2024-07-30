<?php include 'header.php'; 

// Build the current URL
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

// Output the table info
echo '<div class="full_width_container">
<table class="admin-large-table">
    <tr>
        <th width="300px">Database Info</th>
        <th>Value</th>
    </tr>';

foreach ( $db_info as $info ) {
    echo '<tr>
        <td><span class="highlight-variable">'.esc_attr( $info[ 'label' ] ).'</span></td>
        <td>'.wp_kses( $info[ 'value' ], [ 'span' => [ 'class' => [] ] ] ).'</td>
    </tr>';
}

echo '</table>
</div><br><br>';

// Get table names using $wpdb
$tables = $wpdb->get_col( "SHOW TABLES LIKE '{$wpdb->prefix}%'" );

// If we found any tables
if ( ! empty( $tables ) ) {
    echo '<div class="full_width_container">
        <table class="admin-large-table">
            <tr>
                <th style="width: 300px;">Table Name</th>
                <th>Columns</th>
            </tr>';

    // Loop through each table
    foreach ( $tables as $table_name ) {
        // Query to get columns of the current table
        $columns = $wpdb->get_results( "SHOW COLUMNS FROM `{$table_name}`", ARRAY_A );

        // Validate
        if ( ! empty( $columns ) ) {
            // Fetch each column name
            $table_columns = [];
            foreach ( $columns as $col ) {
                $table_columns[] = $col[ 'Field' ];
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