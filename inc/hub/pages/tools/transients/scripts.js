// Helper logs
DevDebugTools.Helpers.log_file_path();
DevDebugTools.Helpers.log_localization( 'ddtt_transients' );

// Now start jQuery
jQuery( document ).ready( function( $ ) {

    
    /**
     * Clear single transient
     */
    $( document ).on( 'click', '.ddtt-clear-transient', function( e ) {
        e.preventDefault();

        let button       = $( this );
        let row          = button.closest( 'tr' );
        let transientKey = row.find( '.ddtt-highlight-variable' ).text();
        let originalText = button.text();

        button.addClass( 'ddtt-button-disabled' ).text( ddtt_transients.i18n.btn_text_clear_one );

        $.post( ajaxurl, {
            action: 'ddtt_clear_transient',
            nonce: ddtt_transients.nonce,
            transient_name: transientKey
        }, function( response ) {
            if ( response.success ) {
                row.fadeOut( 300, function() { $( this ).remove(); } );

                let totalElem = $( '#ddtt-total-transients' );
                let total = parseInt( totalElem.text(), 10 );
                totalElem.text( Math.max( 0, total - 1 ) );

            } else {
                console.log( response.data.message );
                button.text( ddtt_transients.i18n.error );
                setTimeout( function() { button.removeClass( 'ddtt-button-disabled' ).text( originalText ); }, 2000 );
            }
        } );
    });


    /**
     * Auto-check for new transients
     */
    let autoCheckInterval;
    let autoCheckEnabled = false;

    function startAutoCheckTransients() {
        console.log( 'Starting auto-check for transients...' );
        if ( ! autoCheckInterval && autoCheckEnabled ) {
            
            autoCheckInterval = setInterval( function() {
                $.post( ajaxurl, {
                    action: 'ddtt_get_transients',
                    nonce: ddtt_transients.nonce
                }, function( response ) {
                    if ( response.success ) {
                        console.log( response.data.transients );
                        let tbody = $( '.ddtt-transients tbody' );
                        let existingRows = {};

                        // Map current DOM rows
                        tbody.find( 'tr' ).each( function() {
                            let key = $( this ).find( '.ddtt-highlight-variable' ).text();
                            existingRows[ key ] = $( this );
                        } );

                        // Add or update rows
                        $.each( response.data.transients, function( key, data ) {
                            let value    = data.value || '';
                            let timeout  = data.timeout || ddtt_transients.i18n.no_timeout;
                            let rowClass = data.is_expired ? 'ddtt-expired-transient' : '';

                            if ( existingRows[ key ] ) {
                                // Update row
                                let row = existingRows[ key ];
                                row.attr( 'class', rowClass );
                                row.find( 'td:eq(1)' ).text( timeout );
                                row.find( 'td:eq(2)' ).html( value );
                                delete existingRows[ key ];
                            } else {
                                // Insert new row
                                let newRow = $( '<tr class="' + rowClass + '">' +
                                    '<td><span class="ddtt-highlight-variable">' + key + '</span></td>' +
                                    '<td>' + timeout + '</td>' +
                                    '<td>' + value + '</td>' +
                                    '<td style="text-align: right;"><a class="ddtt-clear-transient ddtt-button" href="#">Clear</a></td>' +
                                '</tr>' );

                                let inserted = false;
                                tbody.find( 'tr' ).each( function() {
                                    let current = $( this ).find( '.ddtt-highlight-variable' ).text();
                                    if ( key.localeCompare( current ) < 0 ) {
                                        newRow.insertBefore( $( this ) );
                                        inserted = true;
                                        return false;
                                    }
                                } );

                                if ( ! inserted ) {
                                    tbody.append( newRow );
                                }
                            }
                        } );

                        // Remove rows no longer present
                        $.each( existingRows, function( key, row ) {
                            row.remove();
                        } );

                        // Update total
                        $( '#ddtt-total-transients' ).text( tbody.find( 'tr' ).length );
                    }
                } );
            }, 5000 );
        }
    }

    // Stop auto-check
    function stopAutoCheckTransients() {
        clearInterval( autoCheckInterval );
        autoCheckInterval = null;
        console.log( 'Stopped auto-check for transients.' );
    }

    // Listen for auto-check checkbox
    $( document ).on( 'change', '#ddtt_auto_check', function() {
        autoCheckEnabled = $( this ).is( ':checked' );
        if ( autoCheckEnabled ) {
            startAutoCheckTransients();
        } else {
            stopAutoCheckTransients();
        }
    } );


    /**
     * Clear all transients
     */
    $( '#ddtt_clear_transients' ).on( 'click', function( e ) {
        e.preventDefault();
        let button = $( this );
        let originalText = button.text();

        stopAutoCheckTransients();
        button.prop( 'disabled', true ).text( ddtt_transients.i18n.btn_text_clear_all );

        $.post( ajaxurl, {
            action: 'ddtt_clear_all_transients',
            nonce: ddtt_transients.nonce
        }, function( response ) {
            if ( response.success ) {
                $( '.ddtt-transients tbody tr' ).fadeOut( 300, function() { $( this ).remove(); } );
                $( '#ddtt-total-transients' ).text( '0' );

                setTimeout( function() { button.text( ddtt_transients.i18n.btn_text_clear_all ); }, 2000 );
                setTimeout( function() { button.text( ddtt_transients.i18n.btn_text_clear_all3 ); }, 4000 );
                setTimeout( function() {
                    button.prop( 'disabled', false ).text( originalText );
                    if ( autoCheckEnabled ) { startAutoCheckTransients(); }
                }, 5000 );
            } else {
                console.log( response.data.message );
                button.text( ddtt_transients.i18n.error );
                setTimeout( function() { button.prop( 'disabled', false ).text( originalText ); }, 5000 );
                if ( autoCheckEnabled ) { startAutoCheckTransients(); }
            }
        } );
    });


    /**
     * Purge expired transients
     */
    $( '#ddtt_purge_expired' ).on( 'click', function( e ) {
        e.preventDefault();
        let button = $( this );
        let originalText = button.text();

        stopAutoCheckTransients();
        button.prop( 'disabled', true ).text( ddtt_transients.i18n.btn_text_clear_all2 );

        $.post( ajaxurl, {
            action: 'ddtt_purge_expired_transients',
            nonce: ddtt_transients.nonce
        }, function( response ) {
            if ( response.success ) {
                // Remove only rows for expired transients
                let expired = response.data.expired || [];
                let removed = 0;
                $( '.ddtt-transients tbody tr' ).each( function() {
                    let key = $( this ).find( '.ddtt-highlight-variable' ).text();
                    if ( expired.indexOf( key ) !== -1 ) {
                        $( this ).fadeOut( 300, function() { $( this ).remove(); });
                        removed++;
                    }
                });
                let totalElem = $( '#ddtt-total-transients' );
                let total = parseInt( totalElem.text(), 10 );
                totalElem.text( Math.max( 0, total - removed ) );

                setTimeout( function() { button.text( ddtt_transients.i18n.btn_text_clear_all ); }, 2000 );
                setTimeout( function() { button.text( ddtt_transients.i18n.btn_text_clear_all3 ); }, 4000 );
                setTimeout( function() {
                    button.prop( 'disabled', false ).text( originalText );
                    if ( autoCheckEnabled ) { startAutoCheckTransients(); }
                }, 5000 );
            } else {
                console.log( response.data.message );
                button.text( ddtt_transients.i18n.error );
                setTimeout( function() { button.prop( 'disabled', false ).text( originalText ); }, 5000 );
                if ( autoCheckEnabled ) { startAutoCheckTransients(); }
            }
        });
    });


    /**
     * Add test transient
     */
    $( '#ddtt_test_transient' ).on( 'click', function( e ) {
        e.preventDefault();
        let button = $( this );
        let originalText = button.text();

        button.prop( 'disabled', true ).text( ddtt_transients.i18n.btn_text_add_test );

        $.post( ajaxurl, {
            action: 'ddtt_test_transient',
            nonce: ddtt_transients.nonce
        }, function( response ) {
            if ( response.success ) {
				let key   = response.data.transient;
				let value = response.data.label ? response.data.label : response.data.value;
				let timeout = response.data.timeout ? response.data.timeout : ddtt_transients.i18n.no_timeout;

				let newRow = $( '<tr>' +
					'<td><span class="ddtt-highlight-variable">' + key + '</span></td>' +
					'<td>' + timeout + '</td>' +
					'<td>' + value + '</td>' +
					'<td style="text-align: right;"><a class="ddtt-clear-transient ddtt-button" href="#">Clear</a></td>' +
					'</tr>' );

                    let rows = $( '.ddtt-transients tbody tr' );
                    let inserted = false;

                    rows.each( function() {
                        let current = $( this ).find( '.ddtt-highlight-variable' ).text();
                        if ( key.localeCompare( current ) < 0 ) {
                            newRow.insertBefore( $( this ) );
                            inserted = true;
                            return false;
                        }
                    } );

                    if ( ! inserted ) { $( '.ddtt-transients tbody' ).append( newRow ); }

                    let totalElem = $( '#ddtt-total-transients' );
                    totalElem.text( rows.length + 1 );

                    button.prop( 'disabled', false ).text( originalText );
            } else {
                console.log( response.data.message );
                button.text( ddtt_transients.i18n.error );
                setTimeout( function() { button.prop( 'disabled', false ).text( originalText ); }, 2000 );
            }
        } );
    } );

} );
