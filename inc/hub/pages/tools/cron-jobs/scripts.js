// Helper logs
DevDebugTools.Helpers.log_file_path();
DevDebugTools.Helpers.log_localization( 'ddtt_cron_jobs' );

// Now start jQuery
jQuery( document ).ready( function( $ ) {

    const i18n = ddtt_cron_jobs.i18n;


    /**
     * Run an event now and update its Next Run
     */
    $( document ).on( 'click', '.ddtt-cron-run', function( e ) {
        e.preventDefault();

        const button = $( this );
        const row = button.closest( 'tr' );
        const status = row.find( '.ddtt-cron-run-status' );

        button.prop( 'disabled', true );
        status.removeClass( 'is-error' ).html( `<em class="ddtt-loading-msg">${i18n.running}</em>` );

        $.post( ajaxurl, {
            action: 'ddtt_run_cron_event',
            nonce: ddtt_cron_jobs.nonce,
            hook: row.attr( 'data-hook' ),
            timestamp: row.attr( 'data-timestamp' ),
            sig: row.attr( 'data-sig' )
        } ).done( function( response ) {
            if ( ! response || ! response.success ) {
                status.addClass( 'is-error' ).text( response && typeof response.data === 'string' ? response.data : i18n.error );
                button.prop( 'disabled', false );
                return;
            }

            const next = response.data.next;

            if ( next ) {
                row.attr( 'data-timestamp', next.timestamp );
                row.find( '.ddtt-cron-next-run' ).text( next.next_run );
                row.find( '.ddtt-cron-next-time' ).text( next.time );
                button.prop( 'disabled', false );
            } else {
                row.find( '.ddtt-cron-next-run' ).text( i18n.notScheduled );
                row.find( '.ddtt-cron-next-time' ).text( '' );
            }

            status.text( response.data.message );
        } ).fail( function() {
            status.addClass( 'is-error' ).text( i18n.error );
            button.prop( 'disabled', false );
        } );
    } );

} );
