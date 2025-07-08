jQuery( $ => {
    console.log( 'Plugins JS Loaded...' );

    let editing = false;
    let originalValue = '';
    let editingCell = null;

    $( document ).on( 'click', '.edit-added-by', function( e ) {
        e.preventDefault();

        if ( editing ) {
            alert( 'You can only edit one field at a time.' );
            return;
        }

        const cell = $( this ).closest( '.added-by-cell' );
        originalValue = cell.find( '.added-by-input' ).val() || '';
        cell.find( '.added-by-display' ).hide();
        cell.find( '.added-by-edit' ).show().find( '.added-by-input' ).focus();

        editing = true;
        editingCell = cell;

        const savingText = cell.find( '.saving-text' );
        if ( savingText.length ) {
            savingText.replaceWith( '<a href="#" class="save-added-by">[Save]</a>' );
            cell.find( '.added-by-input' ).prop( 'disabled', false );
        }
    } );

    $( document ).on( 'click', '.save-added-by', function( e ) {
        e.preventDefault();

        const cell = $( this ).closest( '.added-by-cell' );
        const row = cell.closest( 'tr' );
        const plugin = row.data( 'plugin' );
        const input = cell.find( '.added-by-input' );
        const user_id = parseInt( input.val(), 10 );
        const applyToAll = cell.find( '.apply-to-all' ).is( ':checked' );

        if ( isNaN( user_id ) || user_id < 0 || ! Number.isInteger( user_id ) ) {
            alert( 'Please enter a valid user ID (0 or greater).' );
            return;
        }

        input.prop( 'disabled', true );
        const saveLink = $( this );
        saveLink.replaceWith( '<span class="saving-text">Saving...</span>' );

        $.ajax( {
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ddtt_update_plugin_user',
                plugin: plugin,
                user_id: user_id,
                apply_all: applyToAll ? 1 : 0
            },
            success: function( response ) {
                if ( response.success ) {
                    const newHtml = $( '<div>' ).append( response.data.html + ' <a href="#" class="edit-added-by">[Edit]</a>' ).html();

                    if ( response.data.applyAll ) {
                        $( '.added-by-cell' ).each( function() {
                            const cell = $( this );
                            const displayElem = cell.find( '.added-by-display' );
                            const currentText = displayElem.text().trim().toLowerCase();

                            if ( currentText === 'unknown' || displayElem.find( 'em' ).length ) {
                                displayElem.html( newHtml );
                                cell.find( '.added-by-input' ).val( user_id );
                            }
                        } );
                    } else {
                        cell.find( '.added-by-display' ).html( newHtml );
                    }

                    cell.find( '.added-by-edit' ).hide();
                    cell.find( '.added-by-display' ).show();
                } else {
                    alert( response.data?.message || 'Failed to update.' );
                    input.prop( 'disabled', false );
                    cell.find( '.saving-text' ).replaceWith( saveLink );
                }

                const anyUnassigned = $( '.added-by-cell .added-by-display em' ).length > 0;
                if ( !anyUnassigned ) {
                    $( '.apply-to-all' ).closest( 'label' ).remove();
                }

                editing = false;
                editingCell = null;
            },
            error: function() {
                alert( 'AJAX error occurred.' );
                input.prop( 'disabled', false );
                cell.find( '.saving-text' ).replaceWith( saveLink );
                editing = false;
                editingCell = null;
            }
        } );
    } );

    $( document ).on( 'keydown', '.added-by-input', function( e ) {
        if ( e.key === 'Enter' || e.keyCode === 13 ) {
            e.preventDefault();
            $( this ).closest( '.added-by-edit' ).find( '.save-added-by' ).trigger( 'click' );
        }
    } );

    $( window ).on( 'beforeunload', function() {
        if ( editing ) {
            return 'You have unsaved changes. Are you sure you want to leave?';
        }
    } );

} );
