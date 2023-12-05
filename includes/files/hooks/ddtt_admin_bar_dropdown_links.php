<?php 
/**
 * Add a link to the admin bar site name dropdown on the front-end.
 *
 * @param array $links
 * @return array
 */
function ddtt_admin_bar_dropdown_links_filter( $links ) {
    // Add your own links
    $links[] = [ 'Media', esc_url( '/'.DDTT_ADMIN_URL.'/upload.php' ) ];

    // Return the new links array
    return $links;
} // End ddtt_admin_bar_dropdown_links_filter()

add_filter( 'ddtt_admin_bar_dropdown_links', 'ddtt_admin_bar_dropdown_links_filter' );