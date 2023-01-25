jQuery( $ => {
    // The send feedback button
    var btn = $( '#feedback-form .submit' );

    // Check if the message field is blank
    $( '#feedback-message' ).on( 'keyup', function( e ) {
        if ( $( this ).val().length > 0 ) {
            btn.attr( 'disabled', false );
        } else {
            btn.attr( 'disabled', 'disabled' );
        }
    } );

    // Get the id
    $( '#feedback-form .submit' ).on( 'click', function( e ) {

        // Prevent reloading page
        e.preventDefault();

        // Get the data from the link
        var nonce = $( this ).data( 'nonce' );
        var name = $( this ).data( 'name' );
        var email = $( this ).data( 'email' );
        var msg = $( '#feedback-message' ).val();

        // Validate
        if ( nonce !== '' && name !== '' && email != '' && msg != '' ) {

            // Set up the args
            var args = {
                type : 'post',
                dataType : 'json',
                url : feedbackAjax.ajaxurl,
                data : { 
                    action: 'ddtt_send_feedback',
                    nonce: nonce,
                    name: name,
                    email: email,
                    msg: msg
                },
                beforeSend: function () {    
                    $( '#feedback-sending' ).css( 'display', 'inline-block' );
                },
                success: function( response ) {

                    // Hide the sending message
                    $( '#feedback-sending' ).hide();

                    // Add the result
                    var result = $( '#feedback-result' );
                    
                    // If successful
                    if ( response.type == 'success' ) {
                        result.addClass( 'success' );
                        result.text( 'Your feedback has been sent. Thank you!!' );

                        // Clear the message field
                        $( '#feedback-message' ).val( '' );
                        btn.attr( 'disabled', 'disabled' );

                    } else {
                        result.addClass( 'fail' );
                        result.text( 'Uh oh! Something went wrong and your feedback was not sent. ' + response.what + 'Please report this error on our discord server above.' );
                        console.log( response.what );
                        console.log( response.err );
                    }
                }
            }
            // console.log( args );

            // Start the ajax
            $.ajax( args );
        }
    } );
} )