jQuery( document ).ready( function ( $ ) {

    function logMessage( label, message ) {
        console.log(
            '%c' + label + '%c ' + message,
            'color: #ffffff; background-color: #0073aa; font-weight: bold; padding: 2px 6px; border-radius: 2px;',
            'color: #0073aa; font-weight: normal;'
        );
    }

    // Initial log
    logMessage( 'Tracking Info:', ddtt_online_users.i18n.being_tracked );

    function sendHeartbeat() {
        $.ajax( {
            url: ddtt_online_users.ajax_url,
            type: 'POST',
            data: {
                action: 'ddtt_online_users_heartbeat',
                nonce: ddtt_online_users.nonce
            },
            success: function( response ) {
                if ( response.success ) {
                    // logMessage( 'Heartbeat:', response.data.message );
                } else {
                    logMessage( 'Heartbeat Error:', response.data.message );
                }
            },
            error: function( jqXHR, textStatus, errorThrown ) {
                logMessage( 'AJAX Error:', textStatus + ' - ' + errorThrown );
            }
        } );
    }

    // Repeat heartbeat at configured interval
    setInterval( sendHeartbeat, ddtt_online_users.interval * 1000 );

} );
