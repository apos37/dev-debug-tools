<?php 
if ( is_plugin_active( 'dev-debug-tools/dev-debug-tools.php' ) ) {
    add_filter( 'ddtt_recommended_plugins', 'ddtt_recommended_plugins_filter' );
    function ddtt_recommended_plugins_filter( $plugin_slugs ) {
        // Add your own recommended plugin
        // Find the slug in the WP.org url (https://wordpress.org/plugins/{slug}/)
        // For example: https://wordpress.org/plugins/user-switching/
        $plugin_slugs[] = 'user-switching';

        // Remove a recommended plugin
        if ( ( $key = array_search( 'post-type-switcher', $plugin_slugs ) ) !== false ) {
            unset( $plugin_slugs[ $key ] );
        }

        // Return the new links array
        return $plugin_slugs;
    }
}