// Helper logs
DevDebugTools.Helpers.log_file_path();
DevDebugTools.Helpers.log_localization( 'ddtt_signups' );

// Now start jQuery
jQuery( document ).ready( function( $ ) {
    
    /**
     * Clear single signup
     */
    $( document ).on( 'click', '.ddtt-clear-signup', function( e ) {
        e.preventDefault();

        let button     = $( this );
        let row        = button.closest( 'tr' );
        let signupId   = row.data( 'key' );
        let originalText = button.text();

        button.addClass( 'ddtt-button-disabled' ).text( ddtt_signups.i18n.btn_text_clear_one );

        $.post( ajaxurl, {
            action: 'ddtt_clear_signup',
            nonce: ddtt_signups.nonce,
            signup_id: signupId
        }, function( response ) {
            if ( response.success ) {
                row.fadeOut( 300, function() {
                    $( this ).remove();
                });

                let totalSignupsElem = $( '#ddtt-total-signups' );
                let totalSignups = parseInt( totalSignupsElem.text(), 10 );
                totalSignupsElem.text( Math.max(0, totalSignups - 1) );

            } else {
                console.log( response.data.message );
                button.text( ddtt_signups.i18n.error );

                setTimeout( function() {
                    button.removeClass( 'ddtt-button-disabled' ).text( originalText );
                }, 2000 );
            }
        } );
    } );

} );
