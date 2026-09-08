<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

$single_option = false;
$total_options = 0;

$last_view = get_option( 'ddtt_site_options_last_view', [
    'per_page' => SiteOptions::RECORDS_PER_PAGE_DEFAULT,
    'search'   => '',
] );
$per_page = absint( $last_view[ 'per_page' ] ?? SiteOptions::RECORDS_PER_PAGE_DEFAULT );
$per_page = $per_page > 0 ? $per_page : SiteOptions::RECORDS_PER_PAGE_DEFAULT;
$all_options = [];

// Lookup a single site option
if ( isset( $_GET[ 'lookup' ] ) && isset( $_GET[ '_wpnonce' ] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET[ '_wpnonce' ] ) ), 'ddtt_site_option_lookup' ) ) {
    $lookup = sanitize_text_field( wp_unslash( $_GET[ 'lookup' ] ) );
    if ( ! empty( $lookup ) ) {
        $single_option_value = get_option( $lookup, '' );
    } else {
        wp_die( esc_html( __( 'No option specified', 'dev-debug-tools' ) ) );
    }
    
    $source_array = SiteOptions::detect_option_sources( $lookup ) ?? [];
    $single_option = [
        'option'   => $lookup,
        'value'    => $single_option_value,
        'source'   => reset( $source_array ) ?? [],
        'autoload' => SiteOptions::get_option_autoload_status( $lookup ),
    ];

// Getting all options
} else {
    $total_options = SiteOptions::get_options_count( $last_view[ 'search' ] ?? '' );
    $all_options = SiteOptions::get_site_options_page( 1, $per_page, $last_view[ 'search' ] ?? '' );
}

$tool_settings = SiteOptions::settings();
?>

<div id="ddtt-page-title-section">
    <div id="ddtt-page-title-left">
        <h2><?php ! empty( $single_option ) ? esc_html_e( 'Site Option', 'dev-debug-tools' ) : esc_html_e( 'Site Options', 'dev-debug-tools' ); ?></h2>
    </div>
</div>

<?php Settings::render_settings_section( $tool_settings ); ?>

<?php
global $wpdb;
$blog_id = get_current_blog_id();
$prefix = $wpdb->get_blog_prefix( $blog_id );
$table = $prefix . 'options';

$autoload_on = [ 'auto', 'on', 'yes', '1' ];
$list = "'" . implode( "', '", $autoload_on ) . "'";

$autoload_total = $wpdb->get_var(
    "SELECT SUM( LENGTH( option_value ) )
     FROM {$table}
     WHERE autoload IN ( {$list} )"
);

$autoload_heavy = $wpdb->get_results(
    "SELECT option_name, LENGTH( option_value ) AS bytes
     FROM {$table}
     WHERE autoload IN ( {$list} )
     ORDER BY bytes DESC
     LIMIT 20",
    ARRAY_A
);

$autoload_status = '';
$autoload_color  = '';

if ( $autoload_total < 300000 ) {
    $autoload_status = __( 'Healthy', 'dev-debug-tools' );
    $autoload_color  = 'var(--color-success)';
} elseif ( $autoload_total < 500000 ) {
    $autoload_status = __( 'Moderate', 'dev-debug-tools' );
    $autoload_color  = 'var(--color-warning)';
} else {
    $autoload_status = __( 'Heavy', 'dev-debug-tools' );
    $autoload_color  = 'var(--color-error)';
}
?>

<section id="ddtt-autoload-stats-section" class="ddtt-section-content ddtt-collapsed">
    <h3 class="ddtt-collapsible-toggle" data-target="ddtt-autoload-stats-content"><?php echo esc_html__( 'Autoload Size Summary', 'dev-debug-tools' ); ?> <span class="ddtt-collapse-arrow">&#9656;</span></h3>

    <div id="ddtt-autoload-stats-content" class="ddtt-collapsible-content">

    <p>
        <?php echo esc_html__( 'Total autoloaded size:', 'dev-debug-tools' ); ?>
        <strong><?php echo esc_html( Helpers::format_bytes( ( int ) $autoload_total ) ); ?></strong>
        (
        <strong style="color: <?php echo esc_attr( $autoload_color ); ?>;">
            <?php echo esc_html( $autoload_status ); ?>
        </strong>
        )
    </p>

    <table class="ddtt-table">
        <thead>
            <tr>
                <th style="width: 300px;" ><?php echo esc_html__( 'Option Name', 'dev-debug-tools' ); ?></th>
                <th style="width: 300px;"><?php echo esc_html__( 'Size', 'dev-debug-tools' ); ?></th>
                <th><?php echo esc_html__( 'Status', 'dev-debug-tools' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $autoload_heavy as $row ) : ?>
                <?php
                $key   = $row[ 'option_name' ];
                $bytes = ( int ) $row[ 'bytes' ];

                if ( $bytes < 40000 ) {
                    $status = __( 'Healthy', 'dev-debug-tools' );
                    $color  = 'var(--color-success)';
                } elseif ( $bytes < 100000 ) {
                    $status = __( 'Moderate', 'dev-debug-tools' );
                    $color  = 'var(--color-warning)';
                } else {
                    $status = __( 'Heavy', 'dev-debug-tools' );
                    $color  = 'var(--color-error)';
                }
                ?>
                <tr>
                    <td>
                        <a class="ddtt-highlight-variable" href="#<?php echo esc_attr( $key ); ?>">
                            <?php echo esc_html( $key ); ?>
                        </a>
                    </td>
                    <td>
                        <?php echo esc_html( Helpers::format_bytes( $bytes ) ); ?>
                    </td>
                    <td>
                        <strong style="color: <?php echo esc_attr( $color ); ?>;">
                            <?php echo esc_html( $status ); ?>
                        </strong>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    </div>

</section>

<?php if ( ! empty( $single_option ) ) : ?>

    <section id="ddtt-tool-section" class="ddtt-single-option ddtt-section-content">
        <h3><code style="font-size: revert;">$<?php echo esc_html( $single_option[ 'option' ] ); ?></code> — <strong><?php echo esc_html__( 'Source:', 'dev-debug-tools' ); ?></strong> <?php echo esc_html( $single_option[ 'source' ][ 'name' ] ?? 'Unknown Source' ); ?> | <strong><?php echo esc_html__( 'Autoload:', 'dev-debug-tools' ); ?></strong> <?php echo esc_html( $single_option[ 'autoload' ] ?? 'unknown' ); ?></h3>
        <?php ddtt_print_r( $single_option[ 'value' ] ); ?>
    </section>

<?php else : ?>

    <section id="ddtt-tool-section" class="ddtt-all-options ddtt-section-content">
        <div class="ddtt-title-addnew">
            <h3><?php echo esc_html__( 'Total # of Options:', 'dev-debug-tools' ); ?> <span id="ddtt-options-total-count"><?php echo esc_html( $total_options ); ?></span></h3>
            <button id="ddtt-add-new-option" class="ddtt-button">+ <?php esc_html_e( 'Add New Option', 'dev-debug-tools' ); ?></button>
        </div>
        <p><strong><?php echo esc_html__( 'Note:', 'dev-debug-tools' ); ?></strong> <?php echo wp_kses( __( 'Some options may be labeled as <em>Unknown Source</em>. This can happen because we cannot reliably determine the source for dynamically created or runtime-generated options. Options registered or used exclusively via dynamic code, custom hooks, or without static references in plugin or theme files may not be detected by the static scanning method. Additionally, some options might be remnants from old plugins or themes no longer in use, which also results in an unknown source.', 'dev-debug-tools' ), [ 'em' => [] ] ); ?></p>

        <p class="ddtt-notice-inline ddtt-warning"><strong><?php echo esc_html__( 'Caution:', 'dev-debug-tools' ); ?></strong> <?php echo wp_kses( __( 'Editing or deleting site options directly can break your site if done carelessly. Use this feature at your own risk. It is a good idea to first test with a newly-created option before touching existing ones, and to copy an option\'s current value somewhere safe before editing it, so you can restore it if something goes wrong.', 'dev-debug-tools' ), [] ); ?></p>

        <div class="ddtt-options-toolbar">
            <input type="search" id="ddtt-options-search" placeholder="<?php esc_attr_e( 'Search options…', 'dev-debug-tools' ); ?>" value="<?php echo esc_attr( $last_view[ 'search' ] ?? '' ); ?>">

            <select id="ddtt-records-per-page">
                <option value="10"<?php echo ( $per_page == 10 ) ? ' selected' : ''; ?>><?php esc_html_e( '10 per page', 'dev-debug-tools' ); ?></option>
                <option value="25"<?php echo ( $per_page == 25 ) ? ' selected' : ''; ?>><?php esc_html_e( '25 per page', 'dev-debug-tools' ); ?></option>
                <option value="50"<?php echo ( $per_page == 50 ) ? ' selected' : ''; ?>><?php esc_html_e( '50 per page', 'dev-debug-tools' ); ?></option>
                <option value="100"<?php echo ( $per_page == 100 ) ? ' selected' : ''; ?>><?php esc_html_e( '100 per page', 'dev-debug-tools' ); ?></option>
            </select>
        </div>

        <?php $total_pages = max( 1, (int) ceil( $total_options / $per_page ) ); ?>
        <table class="ddtt-table" id="ddtt-options-table" data-page="1" data-per-page="<?php echo esc_attr( $per_page ); ?>" data-search="<?php echo esc_attr( $last_view[ 'search' ] ?? '' ); ?>" data-total="<?php echo esc_attr( $total_options ); ?>" data-total-pages="<?php echo esc_attr( $total_pages ); ?>">
            <thead>
                <tr>
                    <th style="width: 30px;" class="ddtt-edit-mode-only"><?php echo esc_html__( 'Delete', 'dev-debug-tools' ); ?></th>
                    <th style="width: 300px;"><?php echo esc_html__( 'Registered Setting/Option', 'dev-debug-tools' ); ?></th>
                    <th style="width: 90px;"><?php echo esc_html__( 'Autoload', 'dev-debug-tools' ); ?></th>
                    <th style="width: 300px;"><?php echo esc_html__( 'Option Details', 'dev-debug-tools' ); ?></th>
                    <th><?php echo esc_html__( 'Value', 'dev-debug-tools' ); ?></th>
                    <th style="width: 90px;"><?php echo esc_html__( 'Actions', 'dev-debug-tools' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php SiteOptions::render_options_rows( $all_options ); ?>
            </tbody>
        </table>

        <div id="ddtt-options-pagination" class="ddtt-pagination"></div>
    </section>

<?php endif; ?>