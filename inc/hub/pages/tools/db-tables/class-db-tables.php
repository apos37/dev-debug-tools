<?php
/**
 * Database Tables
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class DbTables {

    /**
     * Character limit for displaying cell values before truncation
     *
     * @var int
     */
    const CHARACTER_LIMIT = 150;


    /**
     * Default records per page
     */
    const RECORDS_PER_PAGE_DEFAULT = 25;


    /**
     * Nonce
     *
     * @var string
     */
    private $nonce = 'ddtt_db_tables_nonce';


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?DbTables $instance = null;


    /**
     * Get the singleton instance
     *
     * @return self
     */
    public static function instance() : self {
        return self::$instance ??= new self();
    } // End instance()


    /**
     * Constructor
     */
    private function __construct() {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_ddtt_get_table_records', [ $this, 'ajax_get_db_table' ] );
        add_action( 'wp_ajax_nopriv_ddtt_get_table_records', '__return_false' );
    } // End __construct()


    /**
     * Enqueue assets
     *
     * @param string $hook The current admin page hook.
     * @return void
     */
    public function enqueue_assets( $hook ) : void {
        if ( ! AdminMenu::is_current_screen( $hook, 'tools', 'db-tables' ) ) {
            return;
        }

        $last_table = get_option( 'ddtt_last_selected_table', [
            'table'  => '',
            'page'   => 1,
            'search' => '',
        ] );

        wp_localize_script( 'ddtt-tool-db-tables', 'ddtt_db_tables', [
            'nonce'      => wp_create_nonce( $this->nonce ),
            'last_table' => $last_table,
            'i18n'       => [
                'loading'      => __( 'Loading', 'dev-debug-tools' ),
                'not_selected' => __( 'The selected table records will be displayed here.', 'dev-debug-tools' ),
            ],
        ] );
    } // End enqueue_assets()


    /**
     * AJAX handler to get the value of a defined constant
     *
     * @return void
     */
    public function ajax_get_db_table() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        global $wpdb;

        $table    = isset( $_POST[ 'table' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'table' ] ) ) : '';
        $page     = isset( $_POST[ 'page' ] ) ? absint( $_POST[ 'page' ] ) : 1;
        $search   = isset( $_POST[ 'search' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'search' ] ) ) : '';
        $per_page = isset( $_POST[ 'per_page' ] ) ? absint( $_POST[ 'per_page' ] ) : self::RECORDS_PER_PAGE_DEFAULT;

        update_option( 'ddtt_last_selected_table', [
            'table'    => $table,
            'page'     => $page,
            'search'   => $search,
            'per_page' => $per_page,
        ] );

        if ( empty( $table ) ) {
            wp_send_json_error( [ 'message' => __( 'No table selected.', 'dev-debug-tools' ) ] );
        }

        // Validate table exists to avoid SQL injection
        // phpcs:ignore
        $all_tables = $wpdb->get_col( 'SHOW TABLES' );
        if ( ! in_array( $table, $all_tables, true ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid table selected.', 'dev-debug-tools' ) ] );
        }

        $offset  = ( $page - 1 ) * $per_page;
        $columns = $wpdb->get_col( "SHOW COLUMNS FROM `" . esc_sql( $table ) . "`" ); // phpcs:ignore
        if ( ! $columns ) {
            wp_send_json_error( [ 'message' => __( 'Could not fetch table columns.', 'dev-debug-tools' ) ] );
        }

        // Build WHERE clause and prepare placeholders
        $where_sql = '';
        $where_values = [];
        if ( $search ) {
            $like_clauses = [];
            foreach ( $columns as $col ) {
                $like_clauses[]   = "`" . esc_sql( $col ) . "` LIKE %s";
                $where_values[]   = '%' . $wpdb->esc_like( $search ) . '%';
            }
            $where_sql = 'WHERE ' . implode( ' OR ', $like_clauses );
        }

        // Total rows count
        if ( $search ) {
            // phpcs:ignore
            $total = (int) $wpdb->get_var(
                $wpdb->prepare( "SELECT COUNT(*) FROM `" . esc_sql( $table ) . "` $where_sql", ...$where_values ) // phpcs:ignore 
            );
        } else {
            $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `" . esc_sql( $table ) . "`" ); // phpcs:ignore
        }

        // Fetch rows
        if ( $search ) {
            // phpcs:ignore
            $rows = $wpdb->get_results(
                $wpdb->prepare( // phpcs:ignore
                    "SELECT * FROM `" . esc_sql( $table ) . "` $where_sql LIMIT %d OFFSET %d", // phpcs:ignore 
                    ...array_merge( $where_values, [ $per_page, $offset ] )
                ),
                ARRAY_A
            );
        } else {
            // phpcs:ignore
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM `" . esc_sql( $table ) . "` LIMIT %d OFFSET %d",
                    $per_page,
                    $offset
                ),
                ARRAY_A
            );
        }

        // Build HTML table
        ob_start();
        echo '<thead><tr>';
        foreach ( $columns as $col ) {
            echo '<th>' . esc_html( $col ) . '</th>';
        }
        echo '</tr></thead><tbody>';

        if ( $rows ) {
            foreach ( $rows as $row ) {
                echo '<tr>';
                foreach ( $columns as $col ) {
                    $val = $row[ $col ] ?? '';
                    $escaped = esc_html( $val );
                    if ( strlen( $escaped ) > self::CHARACTER_LIMIT ) {
                        $truncated = mb_substr( $val, 0, self::CHARACTER_LIMIT ) . '...';
                        $uid = uniqid( 'ddtt_viewmore_' );
                        echo '<td>';
                        echo '<div class="ddtt-truncated-value">' . esc_html( $truncated ) . '</div>';
                        echo '<a href="#" class="ddtt-view-more" data-target="' . esc_attr( $uid ) . '">' . esc_html__( 'View More', 'dev-debug-tools' ) . '</a>';
                        echo '<div id="' . esc_attr( $uid ) . '" class="ddtt-full-value" style="display:none; max-width:600px; word-break:break-word; white-space:pre-wrap;">' . esc_html( $val ) . '<br><a href="#" class="ddtt-view-less" data-target="' . esc_attr( $uid ) . '">' . esc_html__( 'View Less', 'dev-debug-tools' ) . '</a></div>';
                        echo '</td>';
                    } else {
                        echo '<td>' . esc_html( $val ) . '</td>';
                    }
                }
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="' . esc_attr( count( $columns ) ) . '"><em>' . esc_html__( 'No records found.', 'dev-debug-tools' ) . '</em></td></tr>';
        }
        echo '</tbody>';

        echo '<tfoot><tr>';
        foreach ( $columns as $col ) {
            echo '<th>' . esc_html( $col ) . '</th>';
        }
        echo '</tr></tfoot>';

        // Pagination
        $pagination_html = '';
        $total_pages     = ceil( $total / $per_page );
        if ( $total_pages > 1 ) {
            $pagination_html .= '<div class="ddtt-pagination">';

            // First page
            if ( $page > 1 ) {
                $pagination_html .= '<span class="ddtt-pagination-first"><a href="#" data-page="1">' . esc_html__( '&laquo; First Page', 'dev-debug-tools' ) . '</a></span> ';
            } else {
                $pagination_html .= '<span class="ddtt-pagination-first ddtt-disabled">' . esc_html__( '&laquo; First Page', 'dev-debug-tools' ) . '</span> ';
            }

            // Previous page
            if ( $page > 1 ) {
                $pagination_html .= '<span class="ddtt-pagination-prev"><a href="#" data-page="' . ( $page - 1 ) . '">' . esc_html__( '&lsaquo; Previous', 'dev-debug-tools' ) . '</a></span> ';
            } else {
                $pagination_html .= '<span class="ddtt-pagination-prev ddtt-disabled">' . esc_html__( '&lsaquo; Previous', 'dev-debug-tools' ) . '</span> ';
            }

            // Page info
            /* Translators: %1$d is the current page, %2$d is the total number of pages. */
            $pagination_html .= '<span class="ddtt-pagination-info">' . sprintf( esc_html__( 'Page %1$d of %2$d', 'dev-debug-tools' ), $page, $total_pages ) . '</span> ';

            // Next page
            if ( $page < $total_pages ) {
                $pagination_html .= '<span class="ddtt-pagination-next"><a href="#" data-page="' . ( $page + 1 ) . '">' . esc_html__( 'Next &rsaquo;', 'dev-debug-tools' ) . '</a></span> ';
            } else {
                $pagination_html .= '<span class="ddtt-pagination-next ddtt-disabled">' . esc_html__( 'Next &rsaquo;', 'dev-debug-tools' ) . '</span> ';
            }

            // Last page
            if ( $page < $total_pages ) {
                $pagination_html .= '<span class="ddtt-pagination-last"><a href="#" data-page="' . $total_pages . '">' . esc_html__( 'Last Page &raquo;', 'dev-debug-tools' ) . '</a></span>';
            } else {
                $pagination_html .= '<span class="ddtt-pagination-last ddtt-disabled">' . esc_html__( 'Last Page &raquo;', 'dev-debug-tools' ) . '</span>';
            }

            $pagination_html .= '</div>';
        }

        $html = ob_get_clean();
        wp_send_json_success( [ 'html' => $html, 'pagination' => $pagination_html ] );
    } // End ajax_get_db_table()


    /**
     * Prevent cloning and unserializing
     */
    public function __clone() {}
    public function __wakeup() {}
    
}


DbTables::instance();