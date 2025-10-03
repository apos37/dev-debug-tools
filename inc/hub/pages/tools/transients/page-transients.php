<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

$tool_settings = Transients::settings();

$transients = Transients::get_transients();
?>

<div id="ddtt-page-title-section">
    <div id="ddtt-page-title-left">
        <h2><?php esc_html_e( 'Transients', 'dev-debug-tools' ); ?></h2>
        <p>
            <strong><?php esc_html_e( 'What are transients?', 'dev-debug-tools' ); ?></strong>
            <?php esc_html_e( 'Transients are temporary cached data stored in the database with an optional expiration. They help improve performance by reducing repeated queries or expensive operations.', 'dev-debug-tools' ); ?>
            <?php esc_html_e( 'You can inspect them in the database, usually in the wp_options table with names starting with "_transient_".', 'dev-debug-tools' ); ?>
        </p>
    </div>
</div>

<?php Settings::render_settings_section( $tool_settings ); ?>

<section id="ddtt-tool-section" class="ddtt-transients ddtt-section-content">
    <h3>
        <?php echo esc_html__( 'Total # of Transients:', 'dev-debug-tools' ); ?> 
        <span id="ddtt-total-transients"><?php echo esc_html( count( $transients ) ); ?></span>
    </h3>

    <table class="ddtt-table">
        <thead>
            <tr>
                <th><?php echo esc_html__( 'Transient Name', 'dev-debug-tools' ); ?></th>
                <th><?php echo esc_html__( 'Timeout', 'dev-debug-tools' ); ?></th>
                <th><?php echo esc_html__( 'Value', 'dev-debug-tools' ); ?></th>
                <th style="width: 100px; text-align: right; padding-right: 2rem;"><?php echo esc_html__( 'Clear', 'dev-debug-tools' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach( $transients as $key => $data ) { 
                $rowClass = $data[ 'is_expired' ] ? 'ddtt-expired-transient' : ''; ?>
                <tr class="<?php echo esc_attr( $rowClass ); ?>">
                    <td><span class="ddtt-highlight-variable"><?php echo esc_attr( $key ); ?></span></td>
                    <td><?php echo esc_html( $data[ 'timeout' ] ); ?></td>
                    <td><?php echo wp_kses_post( $data[ 'value' ] ); ?></td>
                    <td style="text-align: right;">
                        <a class="ddtt-clear-transient ddtt-button" href="#"><?php esc_html_e( 'Clear', 'dev-debug-tools' ); ?></a>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</section>