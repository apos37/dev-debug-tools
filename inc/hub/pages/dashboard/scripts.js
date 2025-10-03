// Helper logs
DevDebugTools.Helpers.log_file_path();
DevDebugTools.Helpers.log_localization( 'ddtt_dashboard' );

// Now start jQuery
jQuery( document ).ready( function( $ ) {

    /**
     * Accordion
     */
    $( '.accordion-header' ).each( function() {
        const $header = $( this );
        const $body = $header.next( '.accordion-body' );

        const bodyId = $body.attr( 'id' );
        const headerId = 'header-' + bodyId;

        $header.attr( {
            'id': headerId,
            'role': 'button',
            'aria-controls': bodyId,
            'aria-expanded': 'false',
            'tabindex': '0'
        } );

        $body.attr( {
            'role': 'region',
            'aria-labelledby': headerId,
            'hidden': true
        } );

        function toggleAccordion() {
            const isExpanded = $header.attr( 'aria-expanded' ) === 'true';
            $header.attr( 'aria-expanded', String( ! isExpanded ) );
            $body.attr( 'hidden', isExpanded );
            $header.toggleClass( 'open', ! isExpanded );
        }

        $header.on( 'click', toggleAccordion );
        $header.on( 'keydown', function( e ) {
            if ( e.key === 'Enter' || e.key === ' ' ) {
                e.preventDefault();
                toggleAccordion();
            }
        } );
    } );


    /**
     * Check Issues
     */
    const checkButton = $( '#ddtt-check-issues-button' );
    const issueRows   = $( '#ddtt-issues-section tbody tr' );

    function checkNext( index ) {
        if ( index >= issueRows.length ) {
            // Restore button state after all issues are checked
            checkButton.prop( 'disabled', false );
            const labelSpan = checkButton.find( '.button-label' );
            labelSpan.text( checkButton.data( 'original-label' ) ).removeClass( 'ddtt-loading-msg' );
            checkButton.find( '.dashicons-update' ).removeClass( 'ddtt-rotate' );
            return;
        }

        const row = $( issueRows[ index ] );
        const issueKey = row.data( 'issue-key' );
        const resultCell = row.find( '.ddtt-issue-result' );

        // Show checking message with animated ellipses
        resultCell.text( ddtt_dashboard.i18n.checking ).addClass( 'ddtt-loading-msg' );

        $.post( ajaxurl, {
            action: 'ddtt_check_issue',
            nonce: ddtt_dashboard.nonce,
            issue_key: issueKey
        }, function( response ) {
            resultCell.removeClass( 'ddtt-loading-msg' );

            if ( response.success ) {
                if ( response.data.found ) {
                    resultCell.html( '<span class="ddtt-issue-found">' + ddtt_dashboard.i18n.issue_found + '</span>' );

                    // Show actions dynamically
                    const actionsCell = row.find( 'td:last' );
                    actionsCell.empty();
                    if ( response.data.actions.length ) {
                        response.data.actions.forEach( function( action ) {
                            const link = $( '<a>', {
                                class: 'ddtt-button',
                                href: action.url,
                                target: '_blank',
                                rel: 'noopener noreferrer',
                                text: action.label
                            } );
                            actionsCell.append( link );
                        } );
                    }
                } else {
                    resultCell.html( '<span class="ddtt-issue-good">' + ddtt_dashboard.i18n.good + '</span>' );
                }
            } else {
                resultCell.text( response.data?.message || 'Error' );
            }

            // Move to next issue
            checkNext( index + 1 );

        } ).fail( function() {
            resultCell.removeClass( 'ddtt-loading-msg' ).text( 'Error' );
            checkNext( index + 1 );
        } );
    }

    checkButton.on( 'click', function( e ) {
        e.preventDefault();

        const labelSpan = checkButton.find( '.button-label' );

        // Save original button label in data attribute
        if ( ! checkButton.data( 'original-label' ) ) {
            checkButton.data( 'original-label', labelSpan.text() );
        }

        checkButton.prop( 'disabled', true );
        labelSpan.text( ddtt_dashboard.i18n.checking ).addClass( 'ddtt-loading-msg' );
        checkButton.find( '.dashicons-update' ).addClass( 'ddtt-rotate' );

        checkNext( 0 );
    } );

} );