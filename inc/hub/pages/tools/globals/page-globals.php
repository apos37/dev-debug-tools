<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

$global_keys = array_keys( $GLOBALS );

usort( $global_keys, function ( $a, $b ) {
    if ( preg_match( '/^[^a-zA-Z0-9]/', $a ) && preg_match( '/^[^a-zA-Z0-9]/', $b ) ) {
        return strcasecmp( $a, $b );
    }
    if ( preg_match( '/^[^a-zA-Z0-9]/', $a ) ) {
        return -1;
    }
    if ( preg_match( '/^[^a-zA-Z0-9]/', $b ) ) {
        return 1;
    }
    return strcasecmp( $a, $b );
} );

$selected_global_var = get_option( 'ddtt_last_global_variable', '' );
if ( ! empty( $selected_global_var ) && ! array_key_exists( $selected_global_var, $GLOBALS ) ) {
    $selected_global_var = '';
}
?>

<div id="ddtt-page-title-section">
    <div id="ddtt-page-title-left">
        <h2><?php esc_html_e( 'Global Variables', 'dev-debug-tools' ); ?></h2>
    </div>
</div>

<section id="ddtt-tool-section" class="ddtt-global-variables ddtt-section-content">
    <h3><?php echo esc_html__( 'Select a Global Variable:', 'dev-debug-tools' ); ?></h3>

    <select id="ddtt-select-global-variable">
        <option value="" <?php selected( $selected_global_var, '' ); ?>>
            <?php esc_html_e( '-- Select a Global Variable --', 'dev-debug-tools' ); ?>
        </option>
        <?php foreach ( $global_keys as $key ) : ?>
            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $selected_global_var, $key ); ?>>
                <?php echo esc_html( $key ); ?> (<?php echo esc_html( gettype( $GLOBALS[ $key ] ) ); ?>)
            </option>
        <?php endforeach; ?>
    </select>

    <table class="ddtt-table" id="ddtt-global-variable-value-table">
        <thead id="ddtt-global-variable-value-thead">
            <tr>
                <th></th> <!-- Property -->
                <th></th> <!-- Value -->
            </tr>
        </thead>
        <tbody id="ddtt-global-variable-value-tbody">
            <tr><td colspan="2"><?php echo esc_html__( 'The selected global variable value will be displayed here.', 'dev-debug-tools' ); ?></td></tr>
        </tbody>
    </table>
</section>