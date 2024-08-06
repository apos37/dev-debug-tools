jQuery( $ => {
    // console.log( 'Password View Icon JS Loaded...' );

    $( '.view-pass-icon' ).on( 'click', function() {
        const id = $( this ).data( 'id' );
        var passwordInput = $( `#${id}` );
        var icon = $( this );

        if ( passwordInput.prop( 'type' ) === 'password' ) {
            passwordInput.prop( 'type', 'text' );
            icon.text( '🙈' );
        } else {
            passwordInput.prop( 'type', 'password' );
            icon.text( '👁️' );
        }
    } );
} )