// Helper logs
DevDebugTools.Helpers.log_file_path();
DevDebugTools.Helpers.log_localization( 'ddtt_auto_drafts' );

// Now start jQuery
jQuery( document ).ready( function( $ ) {
    
    /**
     * Clear single auto-draft
     */
    $( document ).on( 'click', '.ddtt-clear-auto-draft', function( e ) {
        e.preventDefault();

        let button = $( this );
        let row = button.closest( 'tr' );
        let postId = row.find( '.ddtt-highlight-variable' ).text();
        let originalText = button.text();

        button.addClass( 'ddtt-button-disabled' ).text( ddtt_auto_drafts.i18n.btn_text_clear_one );

        $.post( ajaxurl, {
            action: 'ddtt_clear_auto_draft',
            nonce: ddtt_auto_drafts.nonce,
            post_id: postId
        }, function( response ) {
            if ( response.success ) {
                row.fadeOut( 300, function() {
                    $( this ).remove();
                } );

                // Update total drafts count
                let totalDraftsElem = $( '#ddtt-total-auto-drafts' );
                let totalDrafts = parseInt( totalDraftsElem.text(), 10 );
                totalDraftsElem.text( Math.max(0, totalDrafts - 1) );

            } else {
                console.log( response.data.message );
                button.text( ddtt_auto_drafts.i18n.error );

                // Wait for 2 seconds and then revert button text
                setTimeout( function() {
                    button.removeClass( 'ddtt-button-disabled' ).text( originalText );
                }, 2000 );
            }
        } );
    } );


    /**
     * Clear all auto drafts
     */
    $( '#ddtt_auto_drafts_all' ).on( 'click', function( e ) {
        e.preventDefault();
        let button = $( this );
        let originalText = button.text();

        button.prop( 'disabled', true ).text( ddtt_auto_drafts.i18n.btn_text_clear_all );

        $.post( ajaxurl, {
            action: 'ddtt_clear_all_auto_drafts',
            nonce: ddtt_auto_drafts.nonce
        }, function( response ) {
            if ( response.success ) {
                $( '.ddtt-auto-drafts tbody tr' ).fadeOut( 300, function() {
                    $( this ).remove();
                } );

                $( '#ddtt-total-auto-drafts' ).text( '0' );

                // Cycle messages and re-enable button
                setTimeout( function() { button.text( ddtt_auto_drafts.i18n.btn_text_clear_all ); }, 2000 );
                setTimeout( function() { button.text( ddtt_auto_drafts.i18n.btn_text_clear_all3 ); }, 4000 );
                setTimeout( function() {
                    button.prop( 'disabled', false ).text( originalText );
                }, 5000 );
            } else {
                console.log( response.data.message );
                button.text( ddtt_auto_drafts.i18n.error );
                setTimeout( function() { button.prop( 'disabled', false ).text( originalText ); }, 5000 );
            }
        } );
    } );


    /**
     * Clear old auto drafts
     */
    $( '#ddtt_auto_drafts_old' ).on( 'click', function( e ) {
        e.preventDefault();
        let button = $( this );
        let originalText = button.text();

        button.prop( 'disabled', true ).text( ddtt_auto_drafts.i18n.btn_text_clear_old );

        $.post( ajaxurl, {
            action: 'ddtt_clear_old_auto_drafts',
            nonce: ddtt_auto_drafts.nonce
        }, function( response ) {
            if ( response.success ) {
                let deletedIds = response.data.deleted;
                console.log( 'Deleted IDs:', deletedIds );
                console.log( 'Deleted Count:', deletedIds.length );

                if ( deletedIds.length === 0 ) {
                    alert( response.data.message );
                } else {
                    // Remove only the deleted rows
                    deletedIds.forEach( function( id ) {
                        $( '.ddtt-auto-drafts tbody tr' ).filter( function() {
                            return $( this ).find( 'td:first a' ).text() == id;
                        } ).fadeOut( 300, function() { $( this ).remove(); } );
                    } );

                    // Update count
                    let remaining = parseInt( $( '#ddtt-total-auto-drafts' ).text(), 10 ) - deletedIds.length;
                    $( '#ddtt-total-auto-drafts' ).text( remaining );
                }

                // Cycle messages and re-enable button
                setTimeout( function() { button.text( ddtt_auto_drafts.i18n.btn_text_clear_old ); }, 2000 );
                setTimeout( function() { button.text( ddtt_auto_drafts.i18n.btn_text_clear_old3 ); }, 4000 );
                setTimeout( function() {
                    button.prop( 'disabled', false ).text( originalText );
                }, 5000 );

            } else {
                console.log( response.data.message );
                button.text( ddtt_auto_drafts.i18n.error );
                setTimeout( function() { button.prop( 'disabled', false ).text( originalText ); }, 5000 );
            }
        } );
    } );


} );
