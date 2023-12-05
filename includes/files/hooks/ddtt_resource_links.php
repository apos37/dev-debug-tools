<?php 
/**
 * Add your own link to Resources.
 *
 * @param array $links
 * @return array
 */
function ddtt_resource_link_filter( $links ) {
    // Add a new resource
    $links[] = [
        'title' => 'Google',
        'url'   => 'https://www.google.com',
        'desc'  => 'This is an example description.'
    ];

    // Return the new links array
    return $links;
} // End ddtt_resource_link_filter()

add_filter( 'ddtt_resource_links', 'ddtt_resource_link_filter' );