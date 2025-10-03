// Helper logs
DevDebugTools.Helpers.log_file_path();
DevDebugTools.Helpers.log_localization( 'ddtt_cookies' );

// Now start jQuery
jQuery( document ).ready( function( $ ) {
    
    /**
     * Clear single cookie
     */
    $( document ).on( 'click', '.ddtt-clear-cookie', function( e ) {
        e.preventDefault();

        let button     = $( this );
        let row        = button.closest( 'tr' );
        let cookieName = row.find( '.ddtt-highlight-variable' ).text();
        let originalText = button.text();

        // Skip WordPress login/auth cookies
        if ( cookieName.startsWith( 'wordpress_logged_in_' ) || cookieName.startsWith( 'wordpress_sec_' ) || cookieName === 'wordpress_test_cookie' ) {
            if ( ! confirm( ddtt_cookies.i18n.skip_wp_login ) ) {
                return;
            }
        }

        button.addClass( 'ddtt-button-disabled' ).text( ddtt_cookies.i18n.btn_text_clear_one );

        $.post( ajaxurl, {
            action: 'ddtt_clear_cookie',
            nonce: ddtt_cookies.nonce,
            cookie_name: cookieName
        }, function( response ) {
            if ( response.success ) {
                row.fadeOut( 300, function() {
                    $( this ).remove();
                });

                let totalCookiesElem = $( '#ddtt-total-cookies' );
                let totalCookies = parseInt( totalCookiesElem.text(), 10 );
                totalCookiesElem.text( Math.max(0, totalCookies - 1) );

            } else {
                console.log( response.data.message );
                button.text( ddtt_cookies.i18n.error );

                setTimeout( function() {
                    button.removeClass( 'ddtt-button-disabled' ).text( originalText );
                }, 2000 );
            }
        });
    });


    /**
     * Auto-check for new cookies every few seconds
     */
    let autoCheckInterval;
    let autoCheckEnabled = false;

    function startAutoCheck() {
        if ( ! autoCheckInterval && autoCheckEnabled ) {
            autoCheckInterval = setInterval( function() {
                $.post( ajaxurl, {
                    action: 'ddtt_get_cookies',
                    nonce: ddtt_cookies.nonce
                }, function( response ) {
                    if ( response.success ) {
                        let existingCookies = {};
                        $( '.ddtt-cookies tbody tr' ).each( function() {
                            let cookieName = $( this ).find( '.ddtt-highlight-variable' ).text();
                            existingCookies[ cookieName ] = true;
                        } );

                        let tbody = $( '.ddtt-cookies tbody' );

                        $.each( response.data.cookies, function( cookieName, cookieValue ) {
                            if ( ! existingCookies[ cookieName ] ) {
                                let newRow = $( '<tr>' +
                                    '<td><span class="ddtt-highlight-variable">' + cookieName + '</span></td>' +
                                    '<td>' + cookieValue + '</td>' +
                                    '<td style="text-align: right;"><a class="ddtt-clear-cookie ddtt-button" href="#">Clear</a></td>' +
                                    '</tr>' );

                                let inserted = false;
                                tbody.find( 'tr' ).each( function() {
                                    let currentName = $( this ).find( '.ddtt-highlight-variable' ).text();
                                    if ( cookieName.localeCompare( currentName ) < 0 ) {
                                        newRow.insertBefore( $( this ) );
                                        inserted = true;
                                        return false;
                                    }
                                } );

                                if ( ! inserted ) {
                                    tbody.append( newRow );
                                }

                                $( '#ddtt-total-cookies' ).text( tbody.find( 'tr' ).length );
                            }
                        } );
                    }
                } );
            }, 5000 );
        }
    }

    function stopAutoCheck() {
        clearInterval( autoCheckInterval );
        autoCheckInterval = null;
    }

    // Listen for the auto-check checkbox
    $( document ).on( 'change', '#ddtt_auto_check', function() {
        autoCheckEnabled = $( this ).is( ':checked' );
        if ( autoCheckEnabled ) {
            startAutoCheck();
        } else {
            stopAutoCheck();
        }
    } );


    /**
     * Clear all cookies
     */
    $( '#ddtt_browser_cookies' ).on( 'click', function( e ) {
        e.preventDefault();
        let button = $( this );
        let originalText = button.text();

        stopAutoCheck();

        button.prop( 'disabled', true ).text( ddtt_cookies.i18n.btn_text_clear_all );

        $.post( ajaxurl, {
            action: 'ddtt_clear_all_cookies',
            nonce: ddtt_cookies.nonce
        }, function( response ) {
            if ( response.success ) {
                let allRows = $( '.ddtt-cookies tbody tr' );
                let rowsToRemove = allRows.filter( function() {
                    let key = $( this ).data( 'key' );
                    return !( key.startsWith( 'wordpress_logged_in_' ) || key.startsWith( 'wordpress_sec_' ) || key === 'wordpress_test_cookie' );
                });

                let remaining = allRows.length; // total count including the ones we skip
                $( '#ddtt-total-cookies' ).text( remaining );

                if ( rowsToRemove.length === 0 ) {
                    button.text( ddtt_cookies.i18n.btn_text_clear_all3 );
                    return;
                }

                rowsToRemove.each( function( index ) {
                    let row = $( this );
                    setTimeout( function() {
                        row.fadeOut( 300, function() {
                            $( this ).remove();
                            remaining--; // decrement total
                            $( '#ddtt-total-cookies' ).text( remaining );

                            if ( index === rowsToRemove.length - 1 ) {
                                // Last removed row, show message after short delay
                                setTimeout( function() {
                                    button.text( ddtt_cookies.i18n.btn_text_clear_all3 );
                                    setTimeout( function() {
                                        button.prop( 'disabled', false ).text( originalText );
                                        if ( autoCheckEnabled ) {
                                            startAutoCheck();
                                        }
                                    }, 1000 );
                                }, 200 );
                            }
                        });
                    }, index * 200 );
                });
            } else {
                console.log( response.data.message );
                button.text( ddtt_cookies.i18n.error );
                setTimeout( function() { button.prop( 'disabled', false ).text( originalText ); }, 5000 );
                if ( autoCheckEnabled ) {
                    startAutoCheck();
                }
            }
        });
    });


    /**
     * Test cookie
     */
    $( '#ddtt_test_cookie' ).on( 'click', function( e ) {
        e.preventDefault();

        let button = $( this );
        let originalText = button.text();

        button.prop( 'disabled', true ).text( ddtt_cookies.i18n.btn_text_add_test );

        $.post( ajaxurl, {
            action: 'ddtt_test_cookie',
            nonce: ddtt_cookies.nonce
        }, function( response ) {
            if ( response.success ) {
                let cookieName  = response.data.cookie;
                let cookieValue = response.data.value;

                let newRow = $( '<tr>' +
                    '<td><span class="ddtt-highlight-variable">' + cookieName + '</span></td>' +
                    '<td>' + cookieValue + '</td>' +
                    '<td style="text-align: right;"><a class="ddtt-clear-cookie ddtt-button" href="#">Clear</a></td>' +
                    '</tr>' );

                let rows = $( '.ddtt-cookies tbody tr' );
                let inserted = false;

                rows.each( function() {
                    let currentCookie = $( this ).find( '.ddtt-highlight-variable' ).text();

                    if ( cookieName.localeCompare( currentCookie ) < 0 ) {
                        newRow.insertBefore( $( this ) );
                        inserted = true;
                        return false;
                    }
                } );

                if ( ! inserted ) {
                    $( '.ddtt-cookies tbody' ).append( newRow );
                }

                // Update total cookies count
                let totalCookiesElem = $( '#ddtt-total-cookies' );
                let totalCookies = parseInt( totalCookiesElem.text(), 10 );
                totalCookiesElem.text( totalCookies + 1 );

                button.prop( 'disabled', false ).text( originalText );
            } else {
                console.log( response.data.message );
                button.text( ddtt_cookies.i18n.error );

                // Wait for 2 seconds and then revert button text
                setTimeout( function() {
                    button.prop( 'disabled', false ).text( originalText );
                }, 2000 );
            }
        } );
    } );

} );
