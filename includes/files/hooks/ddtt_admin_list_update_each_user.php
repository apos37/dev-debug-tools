<?php 
/**
 * Do something for each user when you load the user admin list. 
 * Must have Quick Debug Links enabled in Settings.
 *
 * @param int $user_id
 * @return void
 */
function ddtt_update_each_user( $user_id ) {    
    // Only update users with subscriber role
    if ( ddtt_has_role( 'subscriber', $user_id ) ) {

        // Console log what I'm doing to each user
        $user = get_userdata( $user_id );
        ddtt_console( $user->display_name.' is a subscriber.' );

        // Add/update meta key/value
        update_user_meta( $user_id, 'example_meta_key', 'This is just an example' );
    }
} // End ddtt_update_each_user()

add_action( 'ddtt_admin_list_update_each_user', 'ddtt_update_each_user' );