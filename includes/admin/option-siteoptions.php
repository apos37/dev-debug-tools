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
                <input type="text" name="lookup" id="option-search-input" value="" required>
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
$all_options = wp_load_alloptions();
$reg_options = get_registered_settings();

// Let's add any missing registered options to the all options array
foreach ( $reg_options as $key => $r ) {
    if ( !array_key_exists( $key, $all_options ) ) {
        $all_options[ $key ] = get_option( $key );
    }
}

// Sort them
uksort( $all_options, function( $a, $b ) {
    return strcasecmp( $a, $b );
} );
?>

<div class="full_width_container">
    <table class="admin-large-table">
        <tr>
            <th style="width: 300px;">Registered Setting/Option</th>
            <th style="width: 300px;">Setting Group</th>
            <th>Value</th>
        </tr>
        <?php
        // Cycle through the options
        foreach ( $all_options as $option => $value ) {

            // Get the group
            if ( in_array( $option, array_keys( $reg_options ) ) ) {
                $group = $reg_options[ $option ][ 'group' ];
            } else {
                $group = '';
            }

            // Get the value and print properly if an array
            if ( is_array( $value ) ) {
                $display_value = '<pre>'.print_r( $get_option, true ).'</pre>';
            } elseif ( ddtt_is_serialized_array( $value ) && !empty( unserialize( $value ) ) ) {
                $display_value = $value.'<br><code><pre>'.print_r( unserialize( $value ), true ).'</pre></code>';
            } else {
                $display_value = $value;
            }
            ?>
            <tr>
                <td><span class="highlight-variable"><?php echo esc_attr( $option ); ?></span></td>
                <td><?php echo esc_attr( $group ); ?></td>
                <td><?php echo wp_kses( $display_value, [ 'pre' => [] ] ); ?></td>
            </tr>
            <?php
        }
        ?>
    </table>
</div>