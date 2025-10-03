<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;

try {
    // phpcs:ignore
    $tables = $wpdb->get_col( 'SHOW TABLES' );

    if ( is_wp_error( $tables ) || ! is_array( $tables ) ) {
        throw new \Exception( 'Error retrieving tables.' );
    }

    sort( $tables, SORT_NATURAL | SORT_FLAG_CASE );

} catch ( \Exception $e ) {
    Helpers::write_log( __( 'Error fetching DB tables: ', 'dev-debug-tools' ) . $e->getMessage() );
    $tables = [];
}

// Last selected table
$selected_table = get_option( 'ddtt_last_selected_table', '' );
$selected_table_name = is_array( $selected_table ) && isset( $selected_table[ 'table' ] ) ? $selected_table[ 'table' ] : '';
$selected_table_search = is_array( $selected_table ) && isset( $selected_table[ 'search' ] ) ? sanitize_text_field( $selected_table[ 'search' ] ) : '';
$selected_table_per_page = is_array( $selected_table ) && isset( $selected_table[ 'per_page' ] ) ? absint( $selected_table[ 'per_page' ] ) : DbTables::RECORDS_PER_PAGE_DEFAULT;
?>

<div id="ddtt-page-title-section">
    <div id="ddtt-page-title-left">
        <h2><?php esc_html_e( 'Database Tables', 'dev-debug-tools' ); ?></h2>
    </div>
</div>

<section id="ddtt-tool-section" class="ddtt-db-tables ddtt-section-content">
    <div class="ddtt-info-box">
        <div class="ddtt-info-box-content">
            <span class="ddtt-info-box-label"><?php esc_html_e( 'Database Name:', 'dev-debug-tools' ); ?></span>
            <span class="ddtt-info-box-value"><?php echo esc_html( DB_NAME ); ?></span>
        </div>
        <span class="ddtt-info-box-separator">|</span>
        <div class="ddtt-info-box-content">
            <span class="ddtt-info-box-label"><?php esc_html_e( 'Database Version:', 'dev-debug-tools' ); ?></span>
            <span class="ddtt-info-box-value"><?php echo esc_html( get_option( 'db_version' ) ); ?></span>
        </div>
        <span class="ddtt-info-box-separator">|</span>
        <div class="ddtt-info-box-content">
            <span class="ddtt-info-box-label"><?php esc_html_e( 'Table Prefix:', 'dev-debug-tools' ); ?></span>
            <span class="ddtt-info-box-value"><?php echo esc_html( $wpdb->prefix ); ?></span>
        </div>
    </div>

    <h3><?php esc_html_e( 'Select a Database Table:', 'dev-debug-tools' ); ?></h3>

    <div class="ddtt-filter-section">
        <div class="ddtt-filters">
            <select id="ddtt-table-list">
                <option value=""><?php esc_html_e( '-- Select a Table --', 'dev-debug-tools' ); ?></option>
                <?php foreach ( $tables as $table ) : ?>
                    <option value="<?php echo esc_attr( $table ); ?>"<?php echo ( $selected_table_name === $table ) ? ' selected' : ''; ?>><?php echo esc_html( $table ); ?></option>
                <?php endforeach; ?>
            </select>

            <select id="ddtt-records-per-page">
                <option value="10"<?php echo ( $selected_table_per_page == 10 ) ? ' selected' : ''; ?>><?php esc_html_e( '10 per page', 'dev-debug-tools' ); ?></option>
                <option value="25"<?php echo ( $selected_table_per_page == 25 ) ? ' selected' : ''; ?>><?php esc_html_e( '25 per page', 'dev-debug-tools' ); ?></option>
                <option value="50"<?php echo ( $selected_table_per_page == 50 ) ? ' selected' : ''; ?>><?php esc_html_e( '50 per page', 'dev-debug-tools' ); ?></option>
                <option value="100"<?php echo ( $selected_table_per_page == 100 ) ? ' selected' : ''; ?>><?php esc_html_e( '100 per page', 'dev-debug-tools' ); ?></option>
            </select>
        </div>
        <div class="ddtt-search-box">
            <form id="ddtt-record-search-form" style="display: none;">
                <input type="text" id="ddtt-record-search" placeholder="<?php echo esc_attr__( 'Search Records...', 'dev-debug-tools' ); ?>" value="<?php echo esc_attr( $selected_table_search ); ?>">
                <button type="submit" id="ddtt-record-search-btn" class="ddtt-button"><?php esc_html_e( 'Search', 'dev-debug-tools' ); ?></button>
            </form>
        </div>
    </div>

    <div id="ddtt-record-value-table-cont">
        <table class="ddtt-table" id="ddtt-record-value-table">
            <tr><th></th></tr>
            <tr><td><?php esc_html_e( 'The selected table records will be displayed here.', 'dev-debug-tools' ); ?></td></tr>
        </table>
    </div>

    <div id="ddtt-records-pagination"></div>

</section>