jQuery( $ => {
    console.log( 'API Validation JS Loaded...' );

    

    // Nonce
    var nonce = apiAjax.nonce;
   
    // Scan an individual link
    const scanLink = async ( id, route ) => {
        console.log( `Scanning route (${route})...` );

        // Say it started
        var span = $( `#${id}` );
        span.addClass( 'scanning' ).html( `<em>Checking</em>` );

        // Run the scan
        return await $.ajax( {
            type: 'post',
            dataType: 'json',
            url: apiAjax.ajaxurl,
            data: { 
                action: 'ddtt_check_api', 
                nonce: nonce,
                route: route
            }
        } )
    }

    // Rescan all links
    const scanLinks = async () => {
        
        // Get the post link spans
        const apiSpans = document.querySelectorAll( '.api-status' );

        // First count all the link for the button
        for ( const apiSpan of apiSpans ) {
            const route = apiSpan.dataset.route;
            console.log( route );

            // Scan it
            const data = await scanLink( apiSpan.id, route );
            console.log( data );

            // Update the page
            $( `#${apiSpan.id}_code` ).addClass( `code-${data.type}` ).html( data.type );
            $( `#${apiSpan.id}` ).removeClass( 'scanning' ).addClass( `code-${data.type}` ).html( data.text );
        }

        return console.log( 'Done with all links' );
    }

    // Only on click
    $( '#validate-apis' ).on( 'click', function( e ) {
        e.preventDefault();
        $( '.api-status-col' ).show();
        scanLinks();
    } );

    // Only on click
    $( '.api-check' ).on( 'click', async function( e ) {
        e.preventDefault();
        $( '.api-status-col' ).show();
        const parentId = $( this ).parent().attr( 'id' );
        const route = $( this ).parent().attr( 'data-route' );
        const data = await scanLink( parentId, route );
        console.log( data );

        // Update the page
        $( `#${parentId}_code` ).addClass( `code-${data.type}` ).html( data.type );
        $( `#${parentId}` ).removeClass( 'scanning' ).addClass( `code-${data.type}` ).html( data.text );
    } );
} )