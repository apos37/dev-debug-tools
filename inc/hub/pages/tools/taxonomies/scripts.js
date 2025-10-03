// Helper logs

DevDebugTools.Helpers.log_file_path();
DevDebugTools.Helpers.log_localization( 'ddtt_taxonomies' );

jQuery( document ).ready( function( $ ) {
    /**
     * Load taxonomy details (settings, labels, associated post types) via AJAX
     */
    var $dropdown = $( '#ddtt-taxonomy' );

    function loadTaxonomy( taxonomy ) {
        if ( ! taxonomy ) {
            $( '#ddtt-taxonomy-settings-tbody' ).empty();
            $( '#ddtt-taxonomy-labels-tbody' ).empty();
            $( '#ddtt-taxonomy-post-types-tbody' ).empty();
            return;
        }

        $.ajax( {
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'ddtt_get_taxonomy',
                nonce: ddtt_taxonomies.nonce,
                taxonomy: taxonomy
            },
            beforeSend: function() {
                $( '#ddtt-taxonomy-settings-tbody, #ddtt-taxonomy-labels-tbody, #ddtt-taxonomy-post-types-tbody' ).html(
                    '<tr><td colspan="2"><em class="ddtt-loading-msg">' + ddtt_taxonomies.i18n.loading + '</em></td></tr>'
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
                $( '#ddtt-taxonomy-settings-tbody' ).html( settingsHtml );
                $( '#ddtt-taxonomy-settings-thead tr th' ).eq( 0 ).text( settingsTh[0] );
                $( '#ddtt-taxonomy-settings-thead tr th' ).eq( 1 ).text( settingsTh[1] );

                // Labels
                var labelsHtml = '';
                var labelsTh = [ 'Key', 'Value' ];
                $.each( response.data.labels, function( key, val ) {
                    labelsHtml += '<tr><td><span class="ddtt-highlight-variable">' + key + '</span></td><td>' + val + '</td></tr>';
                } );
                $( '#ddtt-taxonomy-labels-tbody' ).html( labelsHtml );
                $( '#ddtt-taxonomy-labels-thead tr th' ).eq( 0 ).text( labelsTh[0] );
                $( '#ddtt-taxonomy-labels-thead tr th' ).eq( 1 ).text( labelsTh[1] );

                // Associated Post Types
                var ptHtml = '';
                var ptTh = [ 'Slug', 'Label' ];
                $.each( response.data.post_types, function( i, pt ) {
                    ptHtml += '<tr><td><span class="ddtt-highlight-variable">' + pt.slug + '</span></td><td>' + pt.label + '</td></tr>';
                } );
                $( '#ddtt-taxonomy-post-types-tbody' ).html( ptHtml );
                $( '#ddtt-taxonomy-post-types-thead tr th' ).eq( 0 ).text( ptTh[0] );
                $( '#ddtt-taxonomy-post-types-thead tr th' ).eq( 1 ).text( ptTh[1] );
            }
        } );
    }

    // Bind change event
    $dropdown.on( 'change', function() {
        loadTaxonomy( $( this ).val() );
    } );

    // Auto-select last saved taxonomy
    if ( ddtt_taxonomies.last ) {
        $dropdown.val( ddtt_taxonomies.last ).trigger( 'change' );
    }
} );
