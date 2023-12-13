jQuery( $ => {
    // Listen for constant changes
    $( '.error_constants_checkboxes' ).on( 'change', function() {

        // Check if we selecting the E_ALL box
        const isEALL = $( this ).attr( 'id' ) == 'error_constants_e_all';

        // Uncheck
        if ( !$( this ).is( ':checked' ) ) {

            // Ensure that we don't uncheck all
            var allUnchecked = true;
            if ( !isEALL ) {
                $( '.error_constants_checkboxes' ).each( function() {
                    if ( $( this ).is( ':checked' ) ) {
                        allUnchecked = false;
                    }
                } );
            }
            if ( isEALL || allUnchecked ) {
                $( '#submit' ).prop( 'disabled', true );
                $( '#all-unchecked' ).css( 'display', 'inline-block' );
            }

            // Make sure E_ALL is unchecked
            $( '#error_constants_e_all' ).prop( 'checked', false );

            // De-select all
            if ( isEALL ) {
                $( '.error_constants_checkboxes' ).not( this ).prop( 'checked', this.checked );
            }
            
        // Check
        } else {

            // Ensure that we don't uncheck all
            if ( $( '#submit' ).prop( 'disabled' ) ) {
                $( '#submit' ).prop( 'disabled', false );
                $( '#all-unchecked' ).hide();
            }

            // Auto select all
            var allChecked = true;
            $( '.error_constants_checkboxes' ).each( function() {
                if ( $( this ).attr( 'id' ) !== 'error_constants_e_all' && !$( this ).is( ':checked' ) ) {
                    allChecked = false;
                }
            } );
            if ( allChecked ) {
                $( '#error_constants_e_all' ).prop( 'checked', true );
            }
            
            // Select all
            if ( isEALL ) {
                $( '.error_constants_checkboxes' ).not( this ).prop( 'checked', this.checked );
            }
        }
    } );

    // Listen for code checker
    $( '#convert-error-code' ).on( 'input', function() {

        // Only allow numbers
        this.value = this.value.replace( /[^0-9\.]/g, '' );

        // Get the data
        var code = this.value;
        var nonce = $( this ).data( 'nonce' );
        if ( nonce !== '' && code !== '' && code > 0 ) {

            // Set up the args
            var args = {
                type : 'post',
                dataType : 'json',
                url : errorAjax.ajaxurl,
                data : { 
                    action: 'ddtt_check_error_code',
                    nonce: nonce,
                    code: code
                },
                success: function( response ) {
                    
                    // If successful
                    if ( response.type == 'success' ) {

                        // The constants
                        const constants = response.constants;
                        const constantsString = constants.join( ' | ' );
                        $( '#error-code-constants' ).text( constantsString );
                        $( '#error-code-constants' ).css( 'visibility', 'visible' );
                        $( '#error-code-constants-notice' ).css( 'visibility', 'visible' );

                        console.log( constants );
                        console.log( code );

                        // Iter the table rows
                        $( '#error-reporting-table tr' ).each( function() {

                            // Get the constant span
                            const constantSpan = $( this ).find( '.highlight-variable' );

                            // If E_ALL, then just highlight all of them
                            if ( constantSpan.length > 0 && code == errorAjax.E_ALL ) {
                                $( this ).addClass( 'highlight-row' );

                            // Otherwise, check if in array
                            } else {

                                var constant = '';
                                if ( constantSpan.length > 0 ) {

                                    // Check the text
                                    constant = constantSpan.text();

                                    // Check for the matching constant
                                    if ( $.inArray( constant, constants ) !== -1 ) {
                                        console.log( constant );
                                        console.log( $( this ) );
                                        $( this ).addClass( 'highlight-row' );
                                    }
                                }
                            }
                        } );

                    // No results
                    } else {
                        console.log( 'no results found' );
                    }
                }
            }
            // console.log( args );

            // Start the ajax
            $.ajax( args );

        // Empty results
        } else if ( code == '' || code == 0 ) {
            $( '#error-code-constants' ).css( 'visibility', 'hidden' );
            $( '#error-code-constants' ).text( '' );
            $( '#error-code-constants-notice' ).css( 'visibility', 'hidden' );
        }
    } );
} )