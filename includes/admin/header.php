<?php 
/**
 * The admin options page header for all tabs
 */

// Get the active tab
$tab = ddtt_get( 'tab' ) ?? 'Options';
?>
<br><br>
<h2><?php echo wp_kses_post( ddtt_plugin_menu_items( $tab ) ); ?></h2>
<p><?php echo wp_kses_post( ddtt_plugin_menu_items( $tab, true ) ); ?></p>
<hr />
<br>