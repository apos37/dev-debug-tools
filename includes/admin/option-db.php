<style>
.full_width_container {
    overflow-x: auto;
}

.table-container {
    /* overflow-x: auto; */
    white-space: nowrap;
    overflow: auto;
    /* height: 80vh; */
    max-height: 100vh;
}

.db-table {
    border-collapse: separate;
    border-spacing: 0;
}

.db-table thead {
    background: #2D2D2D;
    position: sticky;
    top: 0;
    z-index: 1;
}

.db-table th {
    min-width: 100px;
    border: 1px solid white;
    background: #222222;
    position: sticky;
    top: 0;
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

#records-per-page-form {
    float: left;
    margin-top: 10px;
}

#records-per-page {
    margin-left: 10px;
    text-align: center;
    width: 70px;
    padding: 0 !important;
    height: 30px !important;
    min-height: 30px !important;
}

.pagination {
    text-align: center;
    margin-top: 1rem;
}

.page-num, .page-info {
    display: inline-block;
    margin: 0 20px;
}
</style>

<?php include 'header.php'; 

// Build the current URL
$page = ddtt_plugin_options_short_path();
$tab = 'db';
$current_url = ddtt_plugin_options_path( $tab );
$nonce = wp_create_nonce( 'view_table_records' );
$current_url_with_nonce = add_query_arg( '_wpnonce', $nonce, $current_url );

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
$table_to_view = ddtt_get( 'table', '!=', '', 'view_table_records' ) ?? '';
if ( $table_to_view ) {
    
    // Limit
    if ( $per_page = ddtt_get( 'per_page' ) ) {
        $limit = $per_page;
        update_option( DDTT_GO_PF.'db_per_page', $limit );
    } elseif ( $db_per_page = get_option( DDTT_GO_PF.'db_per_page' ) ) {
        $limit = $db_per_page;
    } else {
        $limit = 10;
    }
    $page_number = isset( $_GET[ 'page_num' ] ) ? intval( $_GET[ 'page_num' ] ) : 1;
    $offset = ( $page_number - 1 ) * $limit;

    // Get the columns
    $columns = $wpdb->get_results( "SHOW COLUMNS FROM `{$table_to_view}`", ARRAY_A );

    // Are we searching records for keywords?
    $search_keywords = ddtt_get( 'search' ) ?? '';
    if ( $search_keywords ) {
        
        // Sanitize the search keywords
        $search_keywords = esc_sql( $search_keywords );
        
        // Construct the WHERE clause for searching in all columns
        $search_columns = array_column( $columns, 'Field' );
        $search_clauses = [];
        foreach ( $search_columns as $column ) {
            $search_clauses[] = "`$column` LIKE '%$search_keywords%'";
        }
        
        // Combine all search clauses with OR
        $where_clause = implode( ' OR ', $search_clauses );
        $sql_query = "SELECT * FROM `{$table_to_view}` WHERE $where_clause";
        $count_query = "SELECT COUNT(*) FROM `{$table_to_view}` WHERE $where_clause";
    } else {
        $sql_query = "SELECT * FROM `{$table_to_view}`";
        $count_query = "SELECT COUNT(*) FROM `{$table_to_view}`";
    }

    // Add LIMIT and OFFSET
    $sql_query .= $wpdb->prepare( " LIMIT %d OFFSET %d", $limit, $offset );

    // Fetch the results
    $rows = $wpdb->get_results( $sql_query, ARRAY_A );

    // Count total rows for pagination
    $total_rows = $wpdb->get_var( $count_query );

    // Some more links
    $current_url_with_table_nonce = add_query_arg( 'table', $table_to_view, $current_url_with_nonce );

    if ( $search_keywords ) {
        $current_url_with_table_search_nonce = add_query_arg( 'search', $search_keywords, $current_url_with_table_nonce );
    } else {
        $current_url_with_table_search_nonce = $current_url_with_table_nonce;
    }
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
    $all_tables_link = ( $table_to_view != '' ) ? '<br><em><a href="'.esc_url( $current_url ).'">Or View All Tables</a></em>' : '';
    $all_records_link = ( $table_to_view != '' && $search_keywords != '' ) ? '<br><em><a href="'.esc_url( $current_url_with_table_nonce ).'">Or View All records</a></em>' : '';
    ?>
    <form id="view-table-form" method="get" action="<?php echo esc_url( $current_url ); ?>">
        <?php echo wp_kses( $hidden_path, $hidden_allowed_html ); ?>
        <?php wp_nonce_field( 'view_table_records' ); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="select-table">View a Single Table</label><?php echo wp_kses( $all_tables_link, [ 'a' => [ 'href' => [] ], 'br' => [], 'em' => [] ] ); ?></th>
                <td><select id="select-table" name="table" required>
                    <option value="">-- Select One --</option>
                    <?php
                    foreach ( $tables as $table_name ) {
                        ?>
                        <option value="<?php echo esc_attr( $table_name ); ?>"<?php echo selected( $table_to_view, $table_name, false ); ?>><?php echo esc_html( $table_name ); ?></option>
                        <?php
                    }
                    ?>
                </select> <input type="submit" value="View Table records" class="button button-primary"/></td>
            </tr>
            <?php if ( $table_to_view != '' ) { ?>
                <tr valign="top">
                    <th scope="row"><label for="select-table">Search Records by Keyword</label><?php echo wp_kses( $all_records_link, [ 'a' => [ 'href' => [] ], 'br' => [], 'em' => [] ] ); ?></th>
                    <td><input type="text" name="search" value="<?php echo esc_html( $search_keywords ); ?>"> <input type="submit" value="Search Records" class="button button-primary"/></td>
                </tr>
            <?php } ?>
        </table>
    </form>
    <br><br>

    <?php
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
                            <td><a href="<?php echo esc_url( add_query_arg( 'table', $table_name, $current_url_with_nonce ) ); ?>"><?php echo esc_attr( $table_name ); ?></a></td>
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
            $incl_keywords = ( $search_keywords != '' ) ? ' for <code class="hl" style="font-size: 1.3rem">'.$search_keywords.'</code>' : ''; 
            ?>
            <div class="full_width_container">
                <h3><?php echo esc_attr( $total_rows ); ?> record<?php echo esc_attr( ( $total_rows == 1 ) ? '' : 's' ); ?> found in <code class="hl" style="font-size: 1.3rem"><?php echo esc_html( $table_to_view ); ?></code><?php echo wp_kses( $incl_keywords, [ 'code' => [ 'class' => [], 'style'=> [] ] ] ); ?></h3>
                <div class="table-container db-table-container">
                    <table class="admin-large-table db-table">
                        <thead>
                            <tr class="header-row">
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
                
                <?php if ( $total_rows > 10 ) { ?>
                    <form id="records-per-page-form">
                        <label for="records-per-page">Records per page:</label>
                        <select id="records-per-page" name="per_page" onchange="window.location.href = '<?php echo esc_url( $current_url_with_table_search_nonce ); ?>&per_page=' + this.value;">
                            <option value="10" <?php echo ( $limit == '10' ) ? 'selected' : ''; ?>>10</option>
                            <option value="25" <?php echo ( $limit == '25' ) ? 'selected' : ''; ?>>25</option>
                            <option value="50" <?php echo ( $limit == '50' ) ? 'selected' : ''; ?>>50</option>
                        </select>
                    </form>
                <?php } ?>

                <?php
                // Pagination controls
                $total_pages = ceil( $total_rows / $limit );
                if ( $total_pages > 1 ) {
                    ?>
                    <div class="pagination">
                        <?php if ( $page_number > 1 ) : ?>
                            <a class="page-num first" href="<?php echo esc_url( add_query_arg( 'page_num', 1, $current_url_with_table_search_nonce ) ); ?>">« First Page</a>
                            <a class="page-num previous" href="<?php echo esc_url( add_query_arg( 'page_num', $page_number - 1, $current_url_with_table_search_nonce ) ); ?>">‹ Previous</a>
                        <?php else : ?>
                            <span class="page-num first disabled">« First Page</span>
                            <span class="page-num previous disabled">‹ Previous</span>
                        <?php endif; ?>

                        <span class="page-info"><?php echo esc_html( sprintf( 'Page %d of %d', $page_number, $total_pages ) ); ?></span>

                        <?php if ( $page_number < $total_pages ) : ?>
                            <a class="page-num next" href="<?php echo esc_url( add_query_arg( 'page_num', $page_number + 1, $current_url_with_table_search_nonce ) ); ?>">Next ›</a>
                            <a class="page-num last" href="<?php echo esc_url( add_query_arg( 'page_num', $total_pages, $current_url_with_table_search_nonce ) ); ?>">Last Page »</a>
                        <?php else : ?>
                            <span class="page-num next disabled">Next ›</span>
                            <span class="page-num last disabled">Last Page »</span>
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