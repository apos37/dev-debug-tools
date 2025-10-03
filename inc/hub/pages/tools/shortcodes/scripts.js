// Helper logs
DevDebugTools.Helpers.log_file_path();
DevDebugTools.Helpers.log_localization( 'ddtt_shortcodes' );

jQuery( document ).ready( function( $ ) {

    var $btn = $( '#ddtt-shortcode-form button[type="submit"]' );
    var $select = $( '#ddtt-shortcode' );
    var $attrInput = $( '#ddtt-shortcode-attr' );
    var $resultsSection = $( '#ddtt-search-results-section' );
    var $form = $( '#ddtt-shortcode-form' );

    // Listen for shortcode selection
    $select.on( 'change', function() {
        if ( $select.val() ) {
            $btn.prop( 'disabled', false );
        } else {
            $btn.prop( 'disabled', true );
            $resultsSection.hide();
        }
    } );

    $form.on( 'submit', function( e ) {
        e.preventDefault();

        // Clear previous results
        $( '#ddtt-search-results tbody' ).empty();

        // Show results section while searching
        $resultsSection.show();

        // Get form values
        var shortcode = $select.val();
        var attr = $attrInput.val();

        var originalText = $btn.html();

        // Disable button and show locating text
        $btn.prop( 'disabled', true ).html( '<em class="ddtt-loading-msg">' + ddtt_shortcodes.i18n.locating + '</em>' );

        $.ajax( {
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'ddtt_find_shortcode',
                nonce: ddtt_shortcodes.nonce,
                shortcode: shortcode,
                attr: attr
            },
            success: function( response ) {
                if ( response.success ) {
                    var data = response.data.data;

                    if ( ! data.length ) {
                        $( '#ddtt-search-results tbody' ).append( '<tr><td colspan="5">' + ddtt_shortcodes.i18n.no_results + '</td></tr>' );
                    } else {
                        $.each( data, function( index, item ) {
                            var row = '<tr>' +
                                '<td>' + item.id + '</td>' +
                                '<td><a href="' + item.url + '" target="_blank">' + item.title + '</a></td>' +
                                '<td>' + item.post_type + '</td>' +
                                '<td>' + item.post_status + '</td>' +
                                '<td>' + item.count + '</td>' +
                                '</tr>';
                            $( '#ddtt-search-results tbody' ).append( row );
                        } );
                    }
                } else {
                    $( '#ddtt-search-results tbody' ).append( '<tr><td colspan="5">' + ddtt_shortcodes.i18n.error + ': ' + response.data + '</td></tr>' );
                }

                $( '#ddtt-search-results-shortcode' ).html( '<code>' + response.data.shortcode + '</code>' );
            },
            error: function( xhr, status, error ) {
                $( '#ddtt-search-results tbody' ).append( '<tr><td colspan="5">' + ddtt_shortcodes.i18n.error + ': ' + error + '</td></tr>' );
            },
            complete: function() {
                // Restore button
                $btn.prop( 'disabled', false ).html( originalText );
            }
        } );
    } );

    // Handle bottom table Search buttons
    $( document ).on( 'click', '.ddtt-shortcode-search-btn', function() {
        var shortcode = $( this ).data( 'shortcode' );
        $select.val( shortcode ).trigger( 'change' );
        $attrInput.val( '' );

        // Scroll to search area
        var $searchSection = $( '#ddtt-settings-section' );
        if ( $searchSection.length ) {
            $( 'html, body' ).animate( {
                scrollTop: $searchSection.offset().top - 40
            }, 400 );
        }

        // Trigger search
        $form.submit();
    } );

} );
