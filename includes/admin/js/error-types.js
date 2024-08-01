jQuery( $ => {
    // console.log( 'Error Reporting JS Loaded...' );

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
    let typingTimer;
    const doneTypingInterval = 500; // Time in milliseconds (500 ms = 0.5 sec)
    let previousCode = '';
    
    $( '#convert-error-code' ).on( 'input', function() {
        clearTimeout( typingTimer );
        typingTimer = setTimeout( () => {
            // console.log( 'Typing stopped' );
            
            // Only allow numbers
            var code = this.value.replace( /[^0-9\.]/g, '' );

            // Reset highlight
            // $( '#error-types-table tr' ).removeClass( 'highlight-row' );

            // Get the code
            if ( code === '' || code === '0' ) {
                $( '#error-code-constants' ).css( 'visibility', 'hidden' );
                $( '#error-code-constants' ).text( '' );
                $( '#error-code-constants-notice' ).css( 'visibility', 'hidden' );
                previousCode = code;
                return;
            }

            // Nonce
            var nonce = $( this ).data( 'nonce' );
            if ( nonce !== '' && code !== '' && code > 0 ) {

                // Set up the args
                var args = {
                    type : 'post',
                    dataType : 'json',
                    url : errorReportingAjax.ajaxurl,
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
                            $( '#error-code-constants' ).text( constantsString ).css( 'visibility', 'visible' );
                            $( '#error-code-constants-notice' ).css( 'visibility', 'visible' );

                            // Iter the table rows
                            $( '#error-types-table tr' ).each(function() {
                                const constantSpan = $( this ).find( '.highlight-variable' );
    
                                if ( constantSpan.length > 0 ) {
                                    const constant = constantSpan.text();
    
                                    if ( code == errorReportingAjax.E_ALL ) {
                                        $( this ).addClass( 'highlight-row' );
                                    } else if ( $.inArray( constant, constants ) !== -1 ) {
                                        $( this ).addClass( 'highlight-row' );
                                    }
                                }
                            } );

                            // Only remove highlights if the new code is less than the previous code
                            if ( code < previousCode ) {
                                $( '#error-types-table tr' ).each( function() {
                                    const constantSpan = $( this ).find( '.highlight-variable' );
                                    
                                    if ( constantSpan.length > 0 ) {
                                        const constant = constantSpan.text();
                                        if ( $.inArray( constant, constants ) === -1 ) {
                                            $( this ).removeClass( 'highlight-row' );
                                        }
                                    }
                                } );
                            }

                            // Update previous code
                            previousCode = code;

                        // No results
                        } else {
                            console.log( 'No results found.' );
                        }
                    }
                }

                // Start the ajax
                $.ajax( args );

            // Empty results
            } else if ( code == '' || code == 0 ) {
                $( '#error-code-constants' ).css( 'visibility', 'hidden' );
                $( '#error-code-constants' ).text( '' );
                $( '#error-code-constants-notice' ).css( 'visibility', 'hidden' );
            }
        }, doneTypingInterval );
    } );

    // Clear typing timeout
    $( '#convert-error-code' ).on( 'keydown', function() {
        clearTimeout( typingTimer );
    } );
} )