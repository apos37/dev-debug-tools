<?php include 'header.php'; ?>

<?php 
$page = ddtt_plugin_options_short_path();
$tab = 'plugins';
$current_url = ddtt_plugin_options_path( $tab );
?>

<br><br>
<div class="full_width_container">
    <?php if ( ddtt_get( 'simple_plugin_list', '==', 'true' ) ) { ?>
        <a href="<?php echo esc_url( $current_url ); ?>">View Table</a>
    <?php } else { ?>
        <a href="<?php echo esc_url( $current_url ); ?>&simple_plugin_list=true">View Simple List</a>
    <?php } ?>
    <br><br>
    <?php echo wp_kses_post( ddtt_get_active_plugins( true, true, true ) ); ?>
</div>