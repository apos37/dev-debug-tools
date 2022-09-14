<?php 
if ( is_plugin_active( 'dev-debug-tools/dev-debug-tools.php' ) ) {
    add_action( 'ddtt_admin_list_update_each_post', 'ddtt_update_each_post' );
    function ddtt_update_each_post( $post_id ) {
        // Only update posts
        if ( get_post_status( $post_id ) == 'publish' ) {

            // Console log what I'm doing to each post
            $title = get_the_title( $post_id );
            ddtt_console( $title.' is published.' );

            // Add/update meta key/value
            update_post_meta( $post_id, 'example_meta_key', 'This is an example' );
        }
    }
}