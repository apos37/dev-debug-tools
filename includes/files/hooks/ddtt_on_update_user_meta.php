<?php 
if ( is_plugin_active( 'dev-debug-tools/dev-debug-tools.php' ) ) {
    add_action( 'ddtt_on_update_user_meta', 'ddtt_console_log_when_updating_user' );
    function ddtt_console_log_when_updating_user( $results ) {
        // Console log results so we know what we are working with
        ddtt_console( $results );

        // Get the user
        $user = get_userdata( $results[ 'user' ] );

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
        ddtt_console( $msg.$user->display_name );
    }
}