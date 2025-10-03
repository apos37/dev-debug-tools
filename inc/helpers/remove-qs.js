// Helper logs
DevDebugTools.Helpers.log_file_path();
DevDebugTools.Helpers.log_localization( 'ddtt_file_editor' );

// Now start jQuery
jQuery( document ).ready( function( $ ) {
    if ( typeof ddtt_remove_qs !== 'undefined' && ddtt_remove_qs && ddtt_remove_qs.title !== '' ) {
        if ( history.pushState ) {
            let obj = { Title: ddtt_remove_qs.title, Url: ddtt_remove_qs.url };
            window.history.pushState( obj, obj.Title, obj.Url );
        }
    }
} );