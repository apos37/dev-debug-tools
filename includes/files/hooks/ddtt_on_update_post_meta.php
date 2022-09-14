<?php 
if ( is_plugin_active( 'dev-debug-tools/dev-debug-tools.php' ) ) {
    add_action( 'ddtt_on_update_post_meta', 'ddtt_console_log_when_updating_post' );
    function ddtt_console_log_when_updating_post( $results ) {
        // Console log results so we know what we are working with
        ddtt_console( $results );

        // Get the post
        $post = get_post( $results[ 'post_id' ] );

        // Add a message depending on how we are updating
        if ( $results[ 'update' ] == 'add' ) {
            $msg = 'Awesome! We added '.$results[ 'mk' ].' with '.$results[ 'val' ].' for ';

        } elseif ( $results[ 'update' ] == 'upd' ) {
            $msg = 'Woohoo! We updated '.$results[ 'mk' ].' with '.$results[ 'val' ].' for ';

        } elseif ( $results[ 'update' ] == 'del' ) {
            $msg = 'Great! We deleted the meta key '.$results[ 'mk' ].' for ';

        } elseif ( $results[ 'update' ] == 'dels' ) {
            $msg = 'You got it, dude! I deleted all meta keys starting with '.$results[ 'mk' ].' for ';
        }

        // Console a message
        ddtt_console( $msg.$post->post_title );
    }
}