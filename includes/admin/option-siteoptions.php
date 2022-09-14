<?php 
// Check for deleting transients
if ( ddtt_get( 'transients', '==', 'Delete All' ) ) {
    // Add a notice
    ?>
    <div class="notice notice-success">
        <p><?php _e( 'All transients have been deleted from wp-options.', 'dev-debug-tools' ); ?></p>
    </div><br><br>
    <?php

    // Delete them
    ddtt_delete_all_transients();
} elseif ( ddtt_get( 'transients', '==', 'Purge Expired' ) ) {
    // Add a notice
    ?>
    <div class="notice notice-success">
        <p><?php _e( 'All expired transients have been deleted from wp-options.', 'dev-debug-tools' ); ?></p>
    </div><br><br>
    <?php

    // Delete them
    ddtt_purge_expired_transients();
}

$page = ddtt_plugin_options_short_path();
$tab = 'siteoptions';
$current_url = ddtt_plugin_options_path( $tab );
?>

<?php include 'header.php'; ?>

<table class="form-table">
    <?php if ( ddtt_is_dev() ) { ?>
        <tr valign="top">
            <th scope="row">All Site Options</th>
            <td><a href="/<?php echo esc_attr( DDTT_ADMIN_URL ); ?>/options.php" target="_blank">Open</a> &#9888; <em>Warning: This page allows direct access to your site settings. You can break things here. Please be cautious!</em></td>
        </tr>
    <?php } ?>
    <tr valign="top">
        <th scope="row">Clean Transients</th>
        <td><div class="delete-transients">
            <form method="get" action="<?php echo esc_url( $current_url ); ?>">
                <input type="hidden" name="page" value="<?php echo esc_attr( $page ); ?>">
                <input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>">
                <input type="submit" name="transients" value="Delete All" id="delete-all-transients" class="button button-primary"/>
                <input type="submit" name="transients" value="Purge Expired" id="delete-exp-transients" class="button button-primary"/>
            </form>
        </div></td>
    </tr>
</table>
<br><br>
<hr>
<br><br>
<?php
// Get the options
$all_options = get_registered_settings();

// Return the table
echo '<div class="full_width_container">
    <table class="admin-large-table">
        <tr>
            <th>Registered Setting/Option</th>
            <th>Setting Group</th>
            <th>Value</th>
        </tr>';

        // Cycle through the options
        foreach( $all_options as $option => $value ) {
            $get_option = get_option( $option );
            if ( !is_array( $get_option ) ) {
                $display_value = $get_option;
            } else {
                $display_value = '<pre>'.print_r( $get_option, true ).'</pre>';
            }
            echo '<tr>
                <td>'.esc_attr( $option ).'</td>
                <td>'.esc_attr( $value['group'] ).'</td>
                <td>'.wp_kses_post( $display_value ).'</td>
            </tr>';
        }

echo '</table>
</div>';