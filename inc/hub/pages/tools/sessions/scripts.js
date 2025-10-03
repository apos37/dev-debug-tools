// Helper logs
DevDebugTools.Helpers.log_file_path();
DevDebugTools.Helpers.log_localization( 'ddtt_sessions' );

// Now start jQuery
jQuery( document ).ready( function( $ ) {
    
    /**
     * Clear single session
     */
    $( document ).on( 'click', '.ddtt-clear-session', function( e ) {
        e.preventDefault();

        let button      = $( this );
        let row         = button.closest( 'tr' );
        let sessionName = row.find( '.ddtt-highlight-variable' ).text();
        let originalText = button.text();

        button.addClass( 'ddtt-button-disabled' ).text( ddtt_sessions.i18n.btn_text_clear_one );

        $.post( ajaxurl, {
            action: 'ddtt_clear_session',
            nonce: ddtt_sessions.nonce,
            session_name: sessionName
        }, function( response ) {
            if ( response.success ) {
                row.fadeOut( 300, function() { $( this ).remove(); } );

                // Update total sessions count
                let totalElem = $( '#ddtt-total-sessions' );
                let total = parseInt( totalElem.text(), 10 );
                totalElem.text( Math.max( 0, total - 1 ) );

            } else {
                console.log( response.data.message );
                button.text( ddtt_sessions.i18n.error );
                setTimeout( function() { button.removeClass( 'ddtt-button-disabled' ).text( originalText ); }, 2000 );
            }
        } );
    });


    /**
     * Auto-check for new sessions every few seconds
     */
    let autoCheckInterval;
    let autoCheckEnabled = false;

    function startAutoCheckSessions() {
        if ( ! autoCheckInterval && autoCheckEnabled ) {
            autoCheckInterval = setInterval( function() {
                $.post( ajaxurl, {
                    action: 'ddtt_get_sessions',
                    nonce: ddtt_sessions.nonce
                }, function( response ) {
                    if ( response.success ) {
                        let existing = {};
                        $( '.ddtt-sessions tbody tr' ).each( function() {
                            let sessionName = $( this ).find( '.ddtt-highlight-variable' ).text();
                            existing[ sessionName ] = true;
                        } );

                        let tbody = $( '.ddtt-sessions tbody' );

                        $.each( response.data.sessions, function( sessionName, sessionValue ) {
                            if ( ! existing[ sessionName ] ) {
                                let newRow = $( '<tr>' +
                                    '<td><span class="ddtt-highlight-variable">' + sessionName + '</span></td>' +
                                    '<td>' + sessionValue + '</td>' +
                                    '<td style="text-align: right;"><a class="ddtt-clear-session ddtt-button" href="#">Clear</a></td>' +
                                    '</tr>' );

                                let inserted = false;
                                tbody.find( 'tr' ).each( function() {
                                    let currentName = $( this ).find( '.ddtt-highlight-variable' ).text();
                                    if ( sessionName.localeCompare( currentName ) < 0 ) {
                                        newRow.insertBefore( $( this ) );
                                        inserted = true;
                                        return false;
                                    }
                                } );

                                if ( ! inserted ) { tbody.append( newRow ); }
                                $( '#ddtt-total-sessions' ).text( tbody.find( 'tr' ).length );
                            }
                        } );
                    }
                } );
            }, 5000 );
        }
    }

    function stopAutoCheckSessions() {
        clearInterval( autoCheckInterval );
        autoCheckInterval = null;
    }

    // Listen for auto-check checkbox
    $( document ).on( 'change', '#ddtt_auto_check_sessions', function() {
        autoCheckEnabled = $( this ).is( ':checked' );
        if ( autoCheckEnabled ) { startAutoCheckSessions(); } else { stopAutoCheckSessions(); }
    });


    /**
     * Clear all sessions
     */
    $( '#ddtt_browser_sessions' ).on( 'click', function( e ) {
        e.preventDefault();
        let button = $( this );
        let originalText = button.text();

        stopAutoCheckSessions();
        button.prop( 'disabled', true ).text( ddtt_sessions.i18n.btn_text_clear_all );

        $.post( ajaxurl, {
            action: 'ddtt_clear_all_sessions',
            nonce: ddtt_sessions.nonce
        }, function( response ) {
            if ( response.success ) {
                $( '.ddtt-sessions tbody tr' ).fadeOut( 300, function() { $( this ).remove(); } );
                $( '#ddtt-total-sessions' ).text( '0' );

                setTimeout( function() { button.text( ddtt_sessions.i18n.btn_text_clear_all ); }, 2000 );
                setTimeout( function() { button.text( ddtt_sessions.i18n.btn_text_clear_all3 ); }, 4000 );
                setTimeout( function() {
                    button.prop( 'disabled', false ).text( originalText );
                    if ( autoCheckEnabled ) { startAutoCheckSessions(); }
                }, 5000 );
            } else {
                console.log( response.data.message );
                button.text( ddtt_sessions.i18n.error );
                setTimeout( function() { button.prop( 'disabled', false ).text( originalText ); }, 5000 );
                if ( autoCheckEnabled ) { startAutoCheckSessions(); }
            }
        } );
    });


    /**
     * Test session
     */
    $( '#ddtt_test_session' ).on( 'click', function( e ) {
        e.preventDefault();
        let button = $( this );
        let originalText = button.text();

        button.prop( 'disabled', true ).text( ddtt_sessions.i18n.btn_text_add_test );

        $.post( ajaxurl, {
            action: 'ddtt_test_session',
            nonce: ddtt_sessions.nonce
        }, function( response ) {
            if ( response.success ) {
                let sessionName  = response.data.session;
                let sessionValue = response.data.value;

                let newRow = $( '<tr>' +
                    '<td><span class="ddtt-highlight-variable">' + sessionName + '</span></td>' +
                    '<td>' + sessionValue + '</td>' +
                    '<td style="text-align: right;"><a class="ddtt-clear-session ddtt-button" href="#">Clear</a></td>' +
                    '</tr>' );

                let rows = $( '.ddtt-sessions tbody tr' );
                let inserted = false;

                rows.each( function() {
                    let current = $( this ).find( '.ddtt-highlight-variable' ).text();
                    if ( sessionName.localeCompare( current ) < 0 ) {
                        newRow.insertBefore( $( this ) );
                        inserted = true;
                        return false;
                    }
                } );

                if ( ! inserted ) { $( '.ddtt-sessions tbody' ).append( newRow ); }

                let totalElem = $( '#ddtt-total-sessions' );
                totalElem.text( rows.length + 1 );

                button.prop( 'disabled', false ).text( originalText );
            } else {
                console.log( response.data.message );
                button.text( ddtt_sessions.i18n.error );
                setTimeout( function() { button.prop( 'disabled', false ).text( originalText ); }, 2000 );
            }
        } );
    });


} );
