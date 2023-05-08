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
?>

<?php include 'header.php'; ?>

<?php
// Check for deleting transients
if ( ddtt_get( 'lookup' ) ) {

    // Sanitize it
    $lookup = sanitize_key( ddtt_get( 'lookup' ) );
    
    // Attempt to look up an option with that name
    $option = get_option( $lookup );

    // Display it
    echo '<br><h3>$'.esc_attr( $lookup ).' returns:</h3><br>';
    ddtt_print_r( $option );
    return;
}
?>

<table class="form-table">
    <?php if ( ddtt_is_dev() ) { ?>
        <tr valign="top">
            <th scope="row">All Site Options</th>
            <td><a href="/<?php echo esc_attr( DDTT_ADMIN_URL ); ?>/options.php" target="_blank">Open</a> &#9888; <em>Warning: This page allows direct access to your site settings. You can break things here. Please be cautious!</em></td>
        </tr>
    <?php } ?>
    <tr valign="top">
        <th scope="row"><label for="option-search-input">Search Option by Keyword</label></th>
        <td><div class="search-options">
            <form method="get" action="<?php echo esc_url( $current_url ); ?>">
                <?php echo wp_kses( $hidden_path, $hidden_allowed_html ); ?>
                <input type="text" name="lookup" id="option-search-input" value="<?php echo esc_attr( $s ); ?>" required>
                <input type="submit" value="Search" id="post-search-button" class="button button-primary"/>
            </form>
        </div></td>
    </tr>
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