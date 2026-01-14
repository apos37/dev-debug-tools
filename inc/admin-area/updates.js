// Helper logs
DevDebugTools.Helpers.log_file_path();
DevDebugTools.Helpers.log_localization( 'ddtt_updates' );

// Now start jQuery
jQuery( function( $ ) {

    var title = $( '.wrap h1' );
    if ( title.length ) {
        var btn = $( '<a>', {
            href: ddtt_updates.update_url + '?force_update_check=1&_wpnonce=' + ddtt_updates.nonce,
            class: 'button button-primary',
            text: ddtt_updates.btn_label
        } ).css( 'margin-left', '1em' );

        title.append( btn );
    }

} );
