<?php 
/**
 * Add or remove post types that include quick links when enabled
 *
 * @param array $post_types
 * @return array
 */
function ddtt_quick_link_post_types_filter( $post_types ) {
    // Add a single post type
    $post_types[ 'listing' ] = 'listing';

    // Add multiple post types
    // Example adding Events Calendar plugin post types
    $post_types = array_merge( $post_types, [
        'tribe_events'    => 'tribe_events',
        'tribe_venue'     => 'tribe_venue',
        'tribe_organizer' => 'tribe_organizer',
    ] );

    // Remove a post type
    unset( $post_types[ 'post' ] );

    // Return the post types array
    return $post_types;
} // End ddtt_quick_link_post_types_filter()

add_filter( 'ddtt_quick_link_post_types', 'ddtt_quick_link_post_types_filter' );