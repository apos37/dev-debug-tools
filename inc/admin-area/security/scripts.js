jQuery( document ).ready( function ( $ ) {

    // Cache DOM elements
    var input = $( '#ddtt-password-input' );
    var button = $( '#ddtt-password-submit' );
    var error = $( '#ddtt-password-error' );
    var toggleButton = $( '#ddtt-password-toggle' );

    // Show error message and focus input
    function showError( text ) {
        error.html( text ).show();
        input.val( '' ).focus();
    }

    // Clear error message
    function clearError() {
        error.text( '' ).hide();
    }

    // Show locked out screen and start countdown
    function showLockedOut( secondsRemaining ) {
        var modal = $( '#ddtt-password-modal' );
        modal.empty();

        var h1 = $( '<h1 id="ddtt-password-title">' ).text( ddtt_security.i18n.locked_out_title );

        var p = $( '<p id="ddtt-lockout-message">' ).text( ddtt_security.i18n.locked_out_message );

        var countdownWrapper = $( '<br><span id="ddtt-countdown-wrapper" style="display:none;"> ' + 
            ddtt_security.i18n.locked_out_countdown.replace( '%s', '<span id="ddtt-countdown" data-seconds="' + secondsRemaining + '"></span>' ) + 
            '</span>' 
        );

        p.append( countdownWrapper );

        var tryAgainDiv = $( '<div id="ddtt-try-again-container" style="display:none;">' );
        var tryAgainBtn = $( '<button id="ddtt-try-again" type="button">' + ddtt_security.i18n.try_again_button + '</button>' );
        tryAgainDiv.append( tryAgainBtn );

        modal.append( h1, p, tryAgainDiv );

        startCountdown( secondsRemaining );
    }

    // Countdown timer logic
    function startCountdown( secondsRemaining ) {
        var countdownWrapper = $( '#ddtt-countdown-wrapper' );
        var countdownSpan = $( '#ddtt-countdown' );

        var minutes = Math.floor( secondsRemaining / 60 );
        var seconds = secondsRemaining % 60;
        countdownSpan.text( minutes + 'm ' + seconds + 's' );
        countdownWrapper.show();

        var interval = setInterval( function () {
            secondsRemaining--;
            if ( secondsRemaining < 0 ) {
                clearInterval( interval );
                $( '#ddtt-lockout-message' ).hide();
                $( '#ddtt-try-again-container' ).show();
                return;
            }

            var minutes = Math.floor( secondsRemaining / 60 );
            var seconds = secondsRemaining % 60;
            countdownSpan.text( minutes + 'm ' + seconds + 's' );
        }, 1000 );

        $( '#ddtt-try-again' ).on( 'click', function () {
            location.reload();
        } );
    }

    // Trigger submit on Enter key
    input.on( 'keyup', function ( e ) {
        if ( e.key === 'Enter' || e.keyCode === 13 ) {
            button.trigger( 'click' );
        }
    } );

    // Handle password submit
    button.on( 'click', function () {
        clearError();

        var password = input.val();
        if ( ! password ) {
            showError( ddtt_security.i18n.error_empty );
            return;
        }

        button.prop( 'disabled', true ).text( ddtt_security.i18n.text_wait );

        $.post( ddtt_security.ajax_url, {
            action: 'ddtt_check_password',
            nonce: ddtt_security.nonce,
            password: password
        }, function ( response ) {
            // If locked out
            if ( response.data && response.data.lockout_seconds ) {
                showLockedOut( response.data.lockout_seconds );
                return;
            }

            if ( response && response.success ) {
                button.text( ddtt_security.i18n.access_granted );
                setTimeout( function () {
                    location.reload();
                }, 500 ); // short delay so user sees message
            } else {
                button.prop( 'disabled', false ).text( ddtt_security.i18n.text_unlock );

                var message = response.data && response.data.message
                    ? response.data.message
                    : ddtt_security.i18n.error_invalid;

                showError( message );
            }
        }).fail( function ( jqXHR, textStatus, errorThrown ) {
            button.prop( 'disabled', false ).text( ddtt_security.i18n.text_unlock );

            try {
                var resp = JSON.parse( jqXHR.responseText );
                if ( resp && resp.data && resp.data.lockout_seconds ) {
                    showLockedOut( resp.data.lockout_seconds );
                    return;
                }
                var errorMessage = resp && resp.data && resp.data.message
                    ? resp.data.message
                    : ddtt_security.i18n.error_invalid;
            } catch ( e ) {
                var errorMessage = errorThrown || ddtt_security.i18n.error_invalid;
            }

            showError( errorMessage );
        });
    });

    // Toggle password visibility
    toggleButton.on( 'click', function () {
        var type = input.attr( 'type' );
        if ( type === 'password' ) {
            input.attr( 'type', 'text' );
            toggleButton.text( '⊘' );
        } else {
            input.attr( 'type', 'password' );
            toggleButton.text( '👁' );
        }
    } );

    // Initialize countdown if already locked on page load
    var countdownSpan = $( '#ddtt-countdown' );
    if ( countdownSpan.length ) {
        startCountdown( parseInt( countdownSpan.data( 'seconds' ), 10 ) );
    }

} );
