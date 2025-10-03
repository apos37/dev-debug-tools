<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

$all_options = ini_get_all( null, false );
?>

<div id="ddtt-page-title-section">
    <div id="ddtt-page-title-left">
        <h2><?php esc_html_e( 'PHP.ini', 'dev-debug-tools' ); ?></h2>
    </div>
</div>

<section id="ddtt-tool-section" class="ddtt-all-options ddtt-section-content">
    <h3><?php echo esc_html__( 'Total # of Options:', 'dev-debug-tools' ); ?> <?php echo esc_html( count( $all_options ) ); ?></h3>

    <table class="ddtt-table">
        <thead>
            <tr>
                <th style="width: 300px;"><?php echo esc_html__( 'Registered Configuration Option', 'dev-debug-tools' ); ?></th>
                <th><?php echo esc_html__( 'Value', 'dev-debug-tools' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Loop through all options
            foreach ( $all_options as $option => $value ) {
                $formatted_value = Helpers::print_stored_value_to_table( $value );
                $display_value = Helpers::truncate_string( $formatted_value, true );
                ?>
                <tr id="<?php echo esc_html( $option ); ?>">
                    <td><span class="ddtt-highlight-variable"><?php echo esc_html( $option ); ?></span></td>
                    <td><?php echo wp_kses_post( $display_value ); ?></td>
                </tr>
                <?php
            }
            ?>
        </tbody>
    </table>
</section>