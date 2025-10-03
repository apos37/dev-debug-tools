// Helper logs
DevDebugTools.Helpers.log_file_path();
DevDebugTools.Helpers.log_localization( 'ddtt_globals' );

// Now start jQuery
jQuery( document ).ready( function( $ ) {

    /**
     * Fetch and display the selected global variable value
     */
    function loadGlobalVariable( key ) {
        if ( ! key ) {
            $( '#ddtt-global-variable-value-thead tr th' ).eq( 0 ).text( '' );
            $( '#ddtt-global-variable-value-thead tr th' ).eq( 1 ).text( '' );
            $( '#ddtt-global-variable-value-tbody' ).html(
                '<tr><td colspan="2">' + ddtt_globals.i18n.not_selected + '</td></tr>'
            );
        } else {
            // Show loading message
            $( '#ddtt-global-variable-value-tbody' ).html(
                '<tr><td colspan="2"><em class="ddtt-loading-msg">' + ddtt_globals.i18n.loading + '</em></td></tr>'
            );
        }

        var data = {
            action: 'ddtt_get_global_variable',
            key: key,
            nonce: ddtt_globals.nonce
        };

        if ( key === 'menu' || key === 'submenu' ) {
            data[key] = ddtt_globals[key];
        }

        $.post( ajaxurl, data, function( response ) {
            if ( response.success && key !== '' ) {
                // Replace tbody with variable rows
                $( '#ddtt-global-variable-value-tbody' ).html( response.data.rows );

                // Conditionally update headers
                if ( response.data.has_data ) {
                    $( '#ddtt-global-variable-value-thead tr th' ).eq( 0 ).text( ddtt_globals.i18n.property );
                    $( '#ddtt-global-variable-value-thead tr th' ).eq( 1 ).text( ddtt_globals.i18n.value );
                } else {
                    $( '#ddtt-global-variable-value-thead tr th' ).eq( 0 ).text( '' );
                    $( '#ddtt-global-variable-value-thead tr th' ).eq( 1 ).text( '' );
                }
            }
        } );
    }

    // Trigger on change
    $( '#ddtt-select-global-variable' ).on( 'change', function() {
        loadGlobalVariable( $( this ).val() );
    } );

    // Auto-load the saved global variable or default to the first option
    var initialKey = $( '#ddtt-select-global-variable' ).val();
    loadGlobalVariable( initialKey );

} );
