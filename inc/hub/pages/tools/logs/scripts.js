// Helper logs
DevDebugTools.Helpers.log_file_path();
DevDebugTools.Helpers.log_localization( 'ddtt_logs' );

// Now start jQuery
jQuery( document ).ready( function( $ ) {

    const currentSubsection = ddtt_logs.subsection;


    /**
     * Load logs based on sidebar inputs
     */
    function ddttLoadLogs( extra = {}, rotate = false ) {
        const viewerSection = document.getElementById( 'ddtt-log-viewer-section' );

        viewerSection.classList.add( 'ddtt-loading' );
        viewerSection.innerHTML = '<span class="ddtt-loading-msg">' + ddtt_logs.i18n.loading + '</span>';

        const type = $( '#ddtt-log-viewer-type' ).val();
        const combineEl = $( '#ddtt-log-viewer-combine' );
        if ( type === 'raw' ) {
            combineEl.prop( 'disabled', true );
        } else {
            combineEl.prop( 'disabled', false );
        }

        const wrapTextEl = $( '#ddtt-wrap-log-text' );

        const refreshIcon = $( '.ddtt-rerender-content .dashicons-update' );
        if ( rotate ) {
            refreshIcon.addClass( 'ddtt-rotate' );
        }

        const data = {
            action: 'ddtt_get_log',
            nonce: ddtt_logs.nonce,
            subsection: currentSubsection,
            type: type,
            sort: $( '#ddtt-log-viewer-sort' ).val(),
            combine: combineEl.val(),
            per_page: $( '#ddtt-log-items-per-page' ).val(),
            search: $( '#ddtt-log-search' ).val(),
            filter: $( '#ddtt-log-filter' ).val(),
            wrap_text: wrapTextEl.length && wrapTextEl.is( ':checked' ) ? 1 : 0
        };

        $.post( ajaxurl, data, function( response ) {
            viewerSection.classList.remove( 'ddtt-loading' );
            if ( rotate ) {
                refreshIcon.removeClass( 'ddtt-rotate' );
            }

            // Check for nonce error (WordPress usually returns a string with 'nonce' or 'expired')
            if (
                ( typeof response === 'string' && ( response.toLowerCase().indexOf( 'nonce' ) !== -1 || response.toLowerCase().indexOf( 'expired' ) !== -1 ) ) ||
                ( typeof response === 'object' && response.success === false && response.data && ( response.data.indexOf( 'nonce' ) !== -1 || response.data.indexOf( 'expired' ) !== -1 ) )
            ) {
                $( '.ddtt-rerender-content' ).hide();
                viewerSection.innerHTML = '<div class="ddtt-notice ddtt-notice-error">' + ddtt_logs.i18n.nonceExpiredMsg + '</div>';
                return;
            }

            viewerSection.innerHTML = response;
        } );
    }

    // Listen to `change` for <select> elements
    $( document ).on( 'change', '#ddtt-log-viewer-type, #ddtt-log-viewer-sort, #ddtt-log-viewer-combine, #ddtt-log-items-per-page', function() {
        ddttLoadLogs();
    } );

    // Listen to `input` for <input> and <textarea> elements
    let ddttSearchTimer = null;

    $( document ).on( 'input', '#ddtt-log-search, #ddtt-log-filter', function() {
        clearTimeout( ddttSearchTimer );
        ddttSearchTimer = setTimeout( function() {
            ddttLoadLogs();
        }, 2000 );
    } );

    $( document ).on( 'keydown', '#ddtt-log-search, #ddtt-log-filter', function( e ) {
        if ( e.key === 'Enter' ) {
            e.preventDefault();
            clearTimeout( ddttSearchTimer );
            ddttLoadLogs();
        }
    } );

    // Bind color identifier clicks
    $( document ).on( 'click', '#ddtt-color-identifiers a', function( e ) {
        e.preventDefault();
        const keyword = $( this ).text().trim();
        $( '#ddtt-log-search' ).val( keyword );
        ddttLoadLogs();
    } );

    // Wrap Text toggle
    $( document ).on( 'change', '#ddtt-wrap-log-text', function() {
        const wrap = $( this ).is( ':checked' ) ? 1 : 0;
        $( '#ddtt-log-raw' ).toggleClass( 'ddtt-log-wrap', wrap === 1 );
        
        $.post( ajaxurl, {
            action: 'ddtt_log_text_wrap',
            nonce: ddtt_logs.nonce,
            value: wrap
        } );
    } );

    // Refresh Log Viewer
    $( document ).on( 'click', '.ddtt-rerender-content', function( e ) {
        e.preventDefault();
        $( '#ddtt-header-messages' ).empty();
        ddttLoadLogs( {}, true );
    } );


    /**
     * Toggle wrap text with Alt + Z command (similar to VSCode)
     */
    $( document ).on( 'keydown', function( event ) {
        if ( event.altKey && event.key.toLowerCase() === 'z' ) {
            event.preventDefault();
            $( '#ddtt-wrap-log-text' ).trigger( 'click' );
        }
    } );


    /**
     * Copy log error message on double-click
     */
    $( document ).on( 'click', '.ddtt-copy-icon', function( e ) {
        e.stopPropagation();
        const messageEl = $( this ).siblings( '.ddtt-log-error-message' );
        const text = messageEl.text().replace(/\s+$/gm, '').trim();

        if ( navigator.clipboard && navigator.clipboard.writeText ) {
            navigator.clipboard.writeText( text ).then( function() {
                showTooltip( e.pageX, e.pageY, ddtt_logs.i18n.copied );
            } );
        } else {
            showTooltip( e.pageX, e.pageY, 'Clipboard API not supported' );
        }
    } );

    function showTooltip( x, y, message, duration = 1000 ) {
        const tooltip = $( '<div>' )
            .text( message )
            .css({
                position: 'absolute',
                top: y + 10 + 'px',
                left: x + 10 + 'px',
                background: '#333',
                color: '#fff',
                padding: '5px 10px',
                borderRadius: '4px',
                fontSize: '12px',
                zIndex: 9999,
                pointerEvents: 'none',
                opacity: 0,
                transition: 'opacity 0.3s'
            })
            .appendTo( 'body' );

        requestAnimationFrame( function() {
            tooltip.css( 'opacity', 1 );
        } );

        setTimeout( function() {
            tooltip.css( 'opacity', 0 );
            setTimeout( function() {
                tooltip.remove();
            }, 300 );
        }, duration );
    }

} );
