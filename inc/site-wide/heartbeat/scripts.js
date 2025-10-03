jQuery( document ).ready( function( $ ) {
    const msgStyle = 'background: black; color: #0f0; padding: 4px; border-radius: 2px;';

    console.log( `%c[${ddtt_heartbeat_monitor.i18n.heartbeat}] ${ddtt_heartbeat_monitor.i18n.loaded}`, msgStyle );

    var counter = 0;
    var firstHeartbeatOccurred = false;

    // Create counter element
    var $counter = $( `<div id="ddtt-heartbeat-counter">0 ${ddtt_heartbeat_monitor.i18n.since_page_load}</div>` );
    $( 'body' ).append( $counter );

    // Increment counter every second
    setInterval( function() {
        counter++;
        if ( firstHeartbeatOccurred ) {
            $counter.text( counter + ' ' + ddtt_heartbeat_monitor.i18n.since_last_beat );
        } else {
            $counter.text( counter + ' ' + ddtt_heartbeat_monitor.i18n.since_page_load );
        }
    }, 1000 );

    // Listen for heartbeat ticks
    $( document ).on( 'heartbeat-tick', function( event, data ) {
        var displayData = Object.assign( {}, data );

        if ( firstHeartbeatOccurred ) {
            displayData[ 'seconds_since_last_heartbeat' ] = counter;
        }

        function formatJson( obj ) {
            return JSON.stringify( obj, null, 2 )
                .replace( /\{/g, '{ ' )
                .replace( /\}/g, ' }' )
                .replace( /\[/g, '[ ' )
                .replace( /\]/g, ' ]' )
                .replace( /\(/g, '( ' )
                .replace( /\)/g, ' )' );
        }

        console.log(
            `%c[${ ddtt_heartbeat_monitor.i18n.heartbeat }]: ${ formatJson( displayData ) }`,
            msgStyle
        );

        counter = 0;
        firstHeartbeatOccurred = true;
    } );

} );