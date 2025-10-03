// Helper logs
DevDebugTools.Helpers.log_file_path();
DevDebugTools.Helpers.log_localization( 'ddtt_post_types' );

// Now start jQuery
jQuery( document ).ready( function( $ ) {
    
    /**
     * Load post type details (settings, labels, taxonomies) via AJAX
     */
    var $dropdown = $( '#ddtt-post-type' );

    function loadPostType( postType ) {
        if ( ! postType ) {
            $( '#ddtt-post-type-settings-tbody' ).empty();
            $( '#ddtt-post-type-labels-tbody' ).empty();
            $( '#ddtt-post-type-taxonomies-tbody' ).empty();
            return;
        }

        $.ajax( {
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'ddtt_get_post_type',
                nonce: ddtt_post_types.nonce,
                post_type: postType
            },
            beforeSend: function() {
                $( '#ddtt-post-type-settings-tbody, #ddtt-post-type-labels-tbody, #ddtt-post-type-taxonomies-tbody' ).html(
                    '<tr><td colspan="2"><em class="ddtt-loading-msg">' + ddtt_post_types.i18n.loading + '</em></td></tr>'
                );
            },
            success: function( response ) {
                if ( ! response.success ) {
                    alert( 'Error: ' + response.data );
                    return;
                }

                // Settings
                var settingsHtml = '';
                var settingsTh = [ 'Key', 'Value' ];
                    $.each( response.data.settings, function( key, val ) {
                        var valueHtml;
                        if ( typeof val === 'string' && val.indexOf( '\n' ) !== -1 ) {
                            valueHtml = '<pre style="white-space:pre-wrap;word-break:break-word;">' + val + '</pre>';
                        } else {
                            valueHtml = JSON.stringify( val );
                        }
                        settingsHtml += '<tr><td><span class="ddtt-highlight-variable">' + key + '</span></td><td>' + valueHtml + '</td></tr>';
                    } );
                $( '#ddtt-post-type-settings-tbody' ).html( settingsHtml );
                $( '#ddtt-post-type-settings-thead tr th' ).eq( 0 ).text( settingsTh[0] );
                $( '#ddtt-post-type-settings-thead tr th' ).eq( 1 ).text( settingsTh[1] );

                // Labels
                var labelsHtml = '';
                var labelsTh = [ 'Key', 'Value' ];
                $.each( response.data.labels, function( key, val ) {
                    labelsHtml += '<tr><td><span class="ddtt-highlight-variable">' + key + '</span></td><td>' + val + '</td></tr>';
                } );
                $( '#ddtt-post-type-labels-tbody' ).html( labelsHtml );
                $( '#ddtt-post-type-labels-thead tr th' ).eq( 0 ).text( labelsTh[0] );
                $( '#ddtt-post-type-labels-thead tr th' ).eq( 1 ).text( labelsTh[1] );

                // Taxonomies
                var taxHtml = '';
                var taxTh = [ 'Slug', 'Label' ];
                $.each( response.data.taxonomies, function( i, tax ) {
                    taxHtml += '<tr><td><span class="ddtt-highlight-variable">' + tax.slug + '</span></td><td>' + tax.label + '</td></tr>';
                } );
                $( '#ddtt-post-type-taxonomies-tbody' ).html( taxHtml );
                $( '#ddtt-post-type-taxonomies-thead tr th' ).eq( 0 ).text( taxTh[0] );
                $( '#ddtt-post-type-taxonomies-thead tr th' ).eq( 1 ).text( taxTh[1] );
            }
        } );
    }

    // Bind change event
    $dropdown.on( 'change', function() {
        loadPostType( $( this ).val() );
    } );

    // Auto-select last saved post type
    if ( ddtt_post_types.last ) {
        $dropdown.val( ddtt_post_types.last ).trigger( 'change' );
    }
} );
