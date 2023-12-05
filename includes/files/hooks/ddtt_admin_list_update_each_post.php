<?php 
/**
 * Do something for each post or page when you load the post or page admin lists. 
 * Must have Quick Debug Links enabled in Settings.
 *
 * @param int $post_id
 * @return void
 */
function ddtt_update_each_post( $post_id ) {
    // Only update posts
    if ( get_post_status( $post_id ) == 'publish' ) {

        // Console log what I'm doing to each post
        $title = get_the_title( $post_id );
        ddtt_console( $title.' is published.' );

        // Add/update meta key/value
        update_post_meta( $post_id, 'example_meta_key', 'This is just an example' );
    }
} // End ddtt_update_each_post()

add_action( 'ddtt_admin_list_update_each_post', 'ddtt_update_each_post' );