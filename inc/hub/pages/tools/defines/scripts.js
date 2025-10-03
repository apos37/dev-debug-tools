// Helper logs
DevDebugTools.Helpers.log_file_path();
DevDebugTools.Helpers.log_localization( 'ddtt_defines' );

// Now start jQuery
jQuery( document ).ready( function( $ ) {
    // Search handler
    $( '#ddtt-constant-search-form' ).on( 'submit', function( e ) {
        e.preventDefault();
        var keyword = $( '#ddtt-constant-search' ).val().trim().toLowerCase();
        if ( ! keyword ) {
            $( '#ddtt-defined-constant-value-thead tr th' ).eq( 0 ).text( '' );
            $( '#ddtt-defined-constant-value-thead tr th' ).eq( 1 ).text( '' );
            $( '#ddtt-defined-constant-value-thead tr th' ).eq( 2 ).text( '' );
            $( '#ddtt-defined-constant-value-tbody' ).html(
                '<tr><td colspan="3">' + ddtt_defines.i18n.not_selected + '</td></tr>'
            );
            return;
        }
        // Show loading
        $( '#ddtt-defined-constant-value-tbody' ).html(
            '<tr><td colspan="3"><em>' + ddtt_defines.i18n.loading + '</em></td></tr>'
        );

        var matches = [];
        $.each( ddtt_defines.categories, function( cat, consts ) {
            $.each( consts, function( name, value ) {
                if ( name.toLowerCase().indexOf( keyword ) !== -1 ) {
                    matches.push( name );
                }
            } );
        } );

        if ( matches.length === 0 ) {
            $( '#ddtt-defined-constant-value-thead tr th' ).eq( 0 ).text( '' );
            $( '#ddtt-defined-constant-value-thead tr th' ).eq( 1 ).text( '' );
            $( '#ddtt-defined-constant-value-thead tr th' ).eq( 2 ).text( '' );
            $( '#ddtt-defined-constant-value-tbody' ).html(
                '<tr><td colspan="3"><em>' + ddtt_defines.i18n.not_selected + '</em></td></tr>'
            );
            return;
        }

        // Show loading for all
        $( '#ddtt-defined-constant-value-tbody' ).html(
            '<tr><td colspan="3"><em class="ddtt-loading-msg">' + ddtt_defines.i18n.loading + '</em></td></tr>'
        );

        $.post(
            ajaxurl,
            {
                action: 'ddtt_get_defined_constant',
                key: matches,
                nonce: ddtt_defines.nonce
            },
            function( response ) {
                if ( response.success ) {
                    $( '#ddtt-defined-constant-value-tbody' ).html( response.data.rows );
                    $( '#ddtt-defined-constant-value-thead tr th' ).eq( 0 ).text( ddtt_defines.i18n.category );
                    $( '#ddtt-defined-constant-value-thead tr th' ).eq( 1 ).text( ddtt_defines.i18n.property );
                    $( '#ddtt-defined-constant-value-thead tr th' ).eq( 2 ).text( ddtt_defines.i18n.value );
                }
            }
        );
    } );

    /**
     * Fetch and display the selected defined constant value
     */
    var ddtt_constants = ddtt_defines.categories;

    $( '#ddtt-constant-category' ).on( 'change', function() {

        var category = $( this ).val();
        var $list = $( '#ddtt-constant-list' );

        $list.empty(); // Clear previous constants
        $list.append( '<option value="">' + '-- Select a Constant --' + '</option>' );

        if ( category && ddtt_constants[ category ] ) {
            // Get and sort keys alphabetically
            var keys = Object.keys( ddtt_constants[ category ] ).sort( function( a, b ) {
                return a.localeCompare( b );
            } );
            $.each( keys, function( i, key ) {
                var value = ddtt_constants[ category ][ key ];
                $list.append(
                    '<option value="' + key + '">' + key + ' (' + typeof value + ')</option>'
                );
            } );
            $list.show();
        } else {
            $list.hide();
        }

        // Reset table
        $( '#ddtt-defined-constant-value-thead tr th' ).eq( 0 ).text( '' );
        $( '#ddtt-defined-constant-value-thead tr th' ).eq( 1 ).text( '' );
        $( '#ddtt-defined-constant-value-thead tr th' ).eq( 2 ).text( '' );
        $( '#ddtt-defined-constant-value-tbody' ).html(
            '<tr><td colspan="3">' + ddtt_defines.i18n.not_selected + '</td></tr>'
        );

    } );

    $( '#ddtt-constant-list' ).on( 'change', function() {

        var key = $( this ).val();

        if ( ! key ) {
            $( '#ddtt-defined-constant-value-thead tr th' ).eq( 0 ).text( '' );
            $( '#ddtt-defined-constant-value-thead tr th' ).eq( 1 ).text( '' );
            $( '#ddtt-defined-constant-value-thead tr th' ).eq( 2 ).text( '' );
            $( '#ddtt-defined-constant-value-tbody' ).html(
                '<tr><td colspan="3">' + ddtt_defines.i18n.not_selected + '</td></tr>'
            );
            return;
        }

        // Show loading
        $( '#ddtt-defined-constant-value-tbody' ).html(
            '<tr><td colspan="3"><em>' + ddtt_defines.i18n.loading + '</em></td></tr>'
        );

        $.post(
            ajaxurl,
            {
                action: 'ddtt_get_defined_constant',
                key: key,
                nonce: ddtt_defines.nonce
            },
            function( response ) {
                if ( response.success ) {
                    $( '#ddtt-defined-constant-value-tbody' ).html( response.data.rows );
                    // Always set headers when a constant is selected
                    $( '#ddtt-defined-constant-value-thead tr th' ).eq( 0 ).text( 'Category' );
                    $( '#ddtt-defined-constant-value-thead tr th' ).eq( 1 ).text( ddtt_defines.i18n.property );
                    $( '#ddtt-defined-constant-value-thead tr th' ).eq( 2 ).text( ddtt_defines.i18n.value );
                }
            }
        );

    } );

    // Trigger on select change
    var initialKey = $( '#ddtt-select-defined-constant' ).val();

    if ( ! initialKey ) {
        // Don't override the placeholder; only load if a non-empty key is desired.
        initialKey = ''; 
    }

    // Load if there is a valid selection
    if ( initialKey ) {
        loadDefinedConstant( initialKey );
    }

} );
