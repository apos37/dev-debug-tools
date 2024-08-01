<style>
.full_width_container {
    overflow-x: auto;
}

.table-container {
    overflow-x: auto;
    white-space: nowrap;
}

.db-table th {
    min-width: 100px;
}

.db-table th.id { min-width: 50px; }
.db-table th.post_content { min-width: 500px; }

.full-value {
    display: none;
}
.view-more {
    display: block;
    margin-top: 1rem;
    width: fit-content;
}

.pagination {
    margin-top: 1rem;
}
.page-num.previous, .page-num.number {
    margin-right: 10px;
}
</style>

<?php include 'header.php'; 

// Build the current URL
$page = ddtt_plugin_options_short_path();
$tab = 'db';
$current_url = ddtt_plugin_options_path( $tab );

// Define the character limit
$char_limit = 200;

// Hidden inputs
$hidden_allowed_html = [
    'input' => [
        'type'      => [],
        'name'      => [],
        'value'     => []
    ],
];
$hidden_path = '<input type="hidden" name="page" value="'.$page.'">
<input type="hidden" name="tab" value="'.$tab.'">';

// Get wpdb
global $wpdb;

// Are we viewing a single table
$table_to_view = ddtt_get( 'table', '!=', '', 'view_table_entries' ) ?? '';
if ( $table_to_view ) {
    
    // Limit
    $limit = 10; // Number of entries per page
    $page_number = isset( $_GET[ 'page_num' ] ) ? intval( $_GET[ 'page_num' ] ) : 1;
    $offset = ( $page_number - 1 ) * $limit;

    // Get the columns and rows
    $columns = $wpdb->get_results( "SHOW COLUMNS FROM `{$table_to_view}`", ARRAY_A );
    $total_rows = $wpdb->get_var( "SELECT COUNT(*) FROM `{$table_to_view}`" );
    $rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table_to_view}` LIMIT %d OFFSET %d", $limit, $offset ), ARRAY_A );
}

// Table info
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
?>

<div class="full_width_container">
<table class="admin-large-table">
    <tr>
        <th width="300px">Database Info</th>
        <th>Value</th>
    </tr>
    <?php
    foreach ( $db_info as $info ) {
        ?>
        <tr>
            <td><span class="highlight-variable"><?php echo esc_attr( $info[ 'label' ] ); ?></span></td>
            <td><?php echo wp_kses( $info[ 'value' ], [ 'span' => [ 'class' => [] ] ] ); ?></td>
        </tr>
        <?php
    } ?>
    </table>
</div>
<br><br>

<?php
// Get table names using $wpdb
$tables = $wpdb->get_col( "SHOW TABLES LIKE '{$wpdb->prefix}%'" );

// If we found any tables
if ( !empty( $tables ) ) {
    if ( $table_to_view != '' ) {
        $incl_all_link = '<br><em><a href="'.esc_url( $current_url ).'">Or View All Tables</a></em>';
    } else {
        $incl_all_link = '';
    }
    ?>
    <form id="view-table-form" method="get" action="<?php echo esc_url( $current_url ); ?>">
        <?php echo wp_kses( $hidden_path, $hidden_allowed_html ); ?>
        <?php wp_nonce_field( 'view_table_entries' ); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="select-table">View a Single Table</label><?php echo wp_kses( $incl_all_link, [ 'a' => [ 'href' => [] ], 'br' => [], 'em' => [] ] ); ?></th>
                <td><select id="select-table" name="table" required>
                    <option value="">-- Select One --</option>
                    <?php
                    foreach ( $tables as $table_name ) {
                        ?>
                        <option value="<?php echo esc_attr( $table_name ); ?>"<?php echo selected( $table_to_view, $table_name, false ); ?>><?php echo esc_html( $table_name ); ?></option>
                        <?php
                    }
                    ?>
                </select> <input type="submit" value="View Table Entries" class="button button-primary"/></td>
            </tr>
        </table>
    </form>
    <br><br>

    <?php
    // Update the current url with the nonce
    $nonce = wp_create_nonce( 'view_table_entries' );
    $current_url = add_query_arg( '_wpnonce', $nonce, $current_url );

    // Table names and columns
    if ( $table_to_view == '' ) {
        ?>
        <div class="full_width_container">
            <table class="admin-large-table">
                <tr>
                    <th style="width: 300px;">Table Name</th>
                    <th>Columns</th>
                </tr>
                <?php
                // Loop through each table
                foreach ( $tables as $table_name ) {

                    // Query to get columns of the current table
                    $columns = $wpdb->get_results( "SHOW COLUMNS FROM `{$table_name}`", ARRAY_A );

                    // Validate
                    if ( !empty( $columns ) ) {

                        // Fetch each column name
                        $table_columns = [];
                        foreach ( $columns as $col ) {
                            $table_columns[] = $col[ 'Field' ];
                        }

                        // Output table name and columns
                        ?>
                        <tr>
                            <td><a href="<?php echo esc_url( add_query_arg( 'table', $table_name, $current_url ) ); ?>"><?php echo esc_attr( $table_name ); ?></a></td>
                            <td><?php echo wp_kses( implode( '<br>', $table_columns ), [ 'br' => [] ] ); ?></td>
                        </tr>
                        <?php
                    }
                }
                ?>
                

            </table>
        </div>

        <?php

    // Single table
    } else {

        // Verify rows
        if ( isset( $rows ) ) {
            ?>
            <div class="full_width_container">
                <h3>Entries for Table: <?php echo esc_html( $table_to_view ); ?></h3>
                <div class="table-container">
                    <table class="admin-large-table db-table">
                        <thead>
                            <tr>
                                <?php
                                foreach ( $columns as $col ) {
                                    $field_name = sanitize_text_field( $col[ 'Field' ] );
                                    ?>
                                    <th class="<?php echo esc_html( strtolower( str_replace( ' ', '_', $field_name ) ) ); ?>"><?php echo esc_html( $field_name ); ?></th>
                                    <?php 
                                }
                                ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ( $rows as $row ) {
                                ?>
                                <tr>
                                    <?php
                                    foreach ( $columns as $col ) {
                                        $field_name = $col[ 'Field' ];
                                        $value = $row[ $field_name ];
                                        $value = esc_html( $value );
                                        $value = stripslashes( $value );

                                        // Check if the value exceeds the character limit
                                        if ( strlen( $value ) > $char_limit ) {
                                            $short_value = substr( $value, 0, $char_limit ) . '... ';
                                            $view_more_link = '<a href="#" class="view-more">View More</a>';
                                            $full_value = '<span class="full-value">'.$value.'</span>';
                                            $value = $short_value.$full_value.$view_more_link;
                                        }
                                        ?>
                                        <td><?php echo wp_kses_post( $value ); ?></td>
                                        <?php
                                    }
                                    ?>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <?php
                // Pagination controls
                $total_pages = ceil( $total_rows / $limit );
                if ( $total_pages > 1 ) {
                    // Update the current url to include the table row
                    $current_url = add_query_arg( [
                        'table'    => $table_to_view,
                    ], $current_url );
                    ?>
                    <div class="pagination">
                        <?php if ( $page_number > 1 ) : ?>
                            <a class="page-num previous" href="<?php echo esc_url( add_query_arg( 'page_num', $page_number - 1, $current_url ) ); ?>">« Previous</a>
                        <?php endif; ?>

                        <?php if ( $page_number < $total_pages ) : ?>
                            <a class="page-num next" href="<?php echo esc_url( add_query_arg( 'page_num', $page_number + 1, $current_url ) ); ?>">Next »</a>
                        <?php endif; ?>
                    </div>
                    <?php
                }

                // No table rows
                if ( count( $rows ) == 0 ) {
                    echo '<br><br><h3>No table rows found for table "'.esc_html( $table_to_view ).'"</h3>';
                }
                ?>
            </div>
            <?php

        }
    }
} else {
    echo '<h3>No tables found. :(</h3>';
}