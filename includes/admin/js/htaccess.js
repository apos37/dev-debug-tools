jQuery( $ => {
    // console.log( 'Htaccess JS Loaded...' );

    // Show/Hide Descriptions
    $( '.learn-more' ).on( 'click', function( e ) {
        e.preventDefault();
        const name = $( this ).data( 'name' );
        $( `#desc-${name}` ).toggleClass( 'is-open' );
    } );

    // Show/Hide Preview Button
    $( `.checkbox-cell input[name="a[]"], .checkbox-cell input[name="r[]"]` ).on( 'change', function() {
        if ( $( `.checkbox-cell input[name="a[]"]:checked, .checkbox-cell input[name="r[]"]:checked` ).length > 0 ) {
            $( '#preview_btn' ).prop( 'disabled', false );
        } else {
            $( '#preview_btn' ).prop( 'disabled', true );
        }
    } );
} )