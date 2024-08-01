jQuery( $ => {
    console.log( 'DB JS Loaded...' );

    // View more link
    $( '.view-more' ).on( 'click', function( e ) {
        e.preventDefault();
        var fullValue = $( this ).siblings( '.full-value' );
        if ( fullValue.is( ':hidden' ) ) {
            fullValue.show();
            $( this ).text( 'View Less' );
        } else {
            fullValue.hide();
            $( this ).text( 'View More' );
        }
    } );
} )