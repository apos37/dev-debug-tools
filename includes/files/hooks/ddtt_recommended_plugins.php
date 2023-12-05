<?php 
/**
 * Add or remove a recommended plugin from the plugins page.
 * Note that you can only add plugins that are in the WordPress.org repository.
 *
 * @param array $plugin_slugs
 * @return array
 */
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
} // End ddtt_recommended_plugins_filter()

add_filter( 'ddtt_recommended_plugins', 'ddtt_recommended_plugins_filter' );