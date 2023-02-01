<style>
.checkbox-cell {
    width: 100px;
}
.form-table td {
    vertical-align: top !important;
}
/* .line-exists.true {
    font-weight: bold;
    color: #DCDCAA;
} */
.line-exists.false {
    color: #FF99CC;
}
.full_width_container.temp {
    filter: invert( 100% );
}
.wp-core-ui .button:disabled, 
.wp-core-ui .button[disabled] {
    cursor: not-allowed;
}
</style>

<?php include 'header.php'; ?>

<?php
// Build the current url
$page = ddtt_plugin_options_short_path();
$tab = 'fx';
$current_url = ddtt_plugin_options_path( $tab );

// Read the functions.php
$filename = 'functions.php';
if ( is_readable( get_template_directory().'/'.$filename ) ) {
    $file = get_template_directory().'/'.$filename;
} else {
    echo wp_kses_post( '<br><em>Sorry, your <strong>functions.php</strong> file cannot be read.</em>' );
    return false;
}
?>

<table class="form-table">
    <tr valign="top">
        <th scope="row">Current <?php echo esc_attr( $filename ); ?> file (View Only)</th>
        <td><div class="full_width_container">
            <?php ddtt_highlight_file2( $file ); ?>
        </div></td>
    </tr>
</table>