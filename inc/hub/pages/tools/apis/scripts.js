// Helper logs
DevDebugTools.Helpers.log_file_path();
DevDebugTools.Helpers.log_localization( 'ddtt_apis' );

// Now start jQuery
jQuery( document ).ready( function( $ ) {
    
    // Flag to control scanning
    let stopScanning = false;


    /**
     * Scan an individual link
     */
    const scanLink = async ( route ) => {
        console.log( `Scanning route ( ${route} )...` );
        // Run the scan
        return await $.ajax( {
            type: 'post',
            dataType: 'json',
            url: ajaxurl,
            data: { 
                action: 'ddtt_check_api', 
                nonce: ddtt_apis.nonce,
                route: route
            }
        } );
    };


    /**
     * Scan all links
     */
    const scanLinks = async () => {
        stopScanning = false;
        // Get the post link spans
        const statusSpans = document.querySelectorAll( '.ddtt-api-status' );
        // Disable the button
        const scanAllBtn = $( '#ddtt-check-all-apis' );
        const originalBtnText = scanAllBtn.html();
        scanAllBtn.prop( 'disabled', true ).html( `<em class="ddtt-loading-msg">${ddtt_apis.i18n.checking}</em>` );
        // First count all the links for the button
        for ( const statusSpan of statusSpans ) {
            if ( stopScanning ) {
                break;
            }
            const route = statusSpan.dataset.route;
            const id = statusSpan.id;
            console.log( route );
            const checkBtnId = id.replace( '_status', '' );
            const checkBtn = $( `#${checkBtnId}` );
            const originalBtnText = checkBtn.html();
            checkBtn.addClass( 'ddtt-button-disabled' ).html( `<em class="ddtt-loading-msg">${ddtt_apis.i18n.checking}</em>` );

            const thisCodeSpan = $( `#${checkBtnId}_code` );
            const thisStatusSpan = $( `#${checkBtnId}_status` );
            thisCodeSpan.html( '' ).removeClass (function (index, className) {
                return (className.match (/(^|\s)code-\S+/g) || []).join(' ');
            });
            thisStatusSpan.html( '' ).removeClass (function (index, className) {
                return (className.match (/(^|\s)code-\S+/g) || []).join(' ');
            });

            // Scan it
            const response = await scanLink( route );
            console.log( response );

            // Reset the button
            checkBtn.removeClass( 'ddtt-button-disabled' ).html( originalBtnText );

            // Update the page
            thisCodeSpan.addClass( `code-${response.data.type}` ).html( response.data.type );
            thisStatusSpan.addClass( `code-${response.data.type}` ).html( response.data.text );
        }
        console.log( 'Done with all links' );
        scanAllBtn.prop( 'disabled', false ).html( originalBtnText );
    };


    /**
     * Only on click for "Check All" button
     */
    $( '#ddtt-check-all-apis' ).on( 'click', function( e ) {
        e.preventDefault();
        $( '#ddtt-stop-checking-all-apis' ).show();
        scanLinks();
    } );

    
    /**
     * Only on click for individual API checks
     */
    $( '.ddtt-check-api' ).on( 'click', async function( e ) {
        e.preventDefault();
        const id = $( this ).attr( 'id' );
        const route = $( this ).attr( 'data-route' );
        const originalBtnText = $( this ).html();
        $( this ).addClass( 'ddtt-button-disabled' ).html( `<em class="ddtt-loading-msg">${ddtt_apis.i18n.checking}</em>` );

        const codeSpan = $( `#${id}_code` );
        const statusSpan = $( `#${id}_status` );
        codeSpan.html( '' ).removeClass (function (index, className) {
            return (className.match (/(^|\s)code-\S+/g) || []).join(' ');
        });
        statusSpan.html( '' ).removeClass (function (index, className) {
            return (className.match (/(^|\s)code-\S+/g) || []).join(' ');
        });

        const response = await scanLink( route );
        console.log( response );

        $( this ).removeClass( 'ddtt-button-disabled' ).html( originalBtnText );
        codeSpan.addClass( `code-${response.data.type}` ).html( response.data.type );
        statusSpan.addClass( `code-${response.data.type}` ).html( response.data.text );
    } );



    /**
     * Stop checking all APIs
     */
    $( '#ddtt-stop-checking-all-apis' ).on( 'click', function( e ) {
        e.preventDefault();
        stopScanning = true;
        $( '#ddtt-stop-checking-all-apis' ).hide();
    } );

} );
