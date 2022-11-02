<?php 
if ( is_plugin_active( 'dev-debug-tools/dev-debug-tools.php' ) ) {
    add_filter( 'ddtt_admin_bar_condensed_items', 'ddtt_admin_bar_condensed_items_filter' );
    function ddtt_admin_bar_condensed_items_filter( $condensed_items ) {
        // $condensed_items key is the menu item id
        // Lookup menu item ids with global $wp_admin_bar; or with console log <li id="wp-admin-bar-{id}">

        // Stop condensing the site name
        unset( $condensed_items[ 'site-name' ] );

        // Condense a menu item that has an icon by simply removing the name
        $condensed_items[ 'customize' ] = '';

        // Condense a menu item that doesn't have an icon by replacing it with a shorter title
        $condensed_items[ 'menu-item1-id' ] = 'WP';

        // Condense a menu item that has an icon by giving it a shorter title (add left margin for space between icon and text)
        $condensed_items[ 'edit' ] = '<span style="margin-left: 6px;">Edit</span>';

        // Or change the title to a dashicon when there is no icon
        // Lookup dashicons here: https://developer.wordpress.org/resource/dashicons/
        $condensed_items[ 'menu-item2-id' ] = '<span class="ab-icon dashicons dashicons-carrot" style="margin-top: 2px;"></span>';

        // Always return the condensed items array
        return $condensed_items;
    }
}