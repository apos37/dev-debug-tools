jQuery( $ => {
    // console.log( 'Error Suppressing JS Loaded...' );

    // Suppressed errors text+ field
    var suppressedErrorsWrapper = $( '#text_plus_ddtt_suppressed_errors' );

    // Count
    var x = 2;

    /**
     * ADD NEW FIELDS
     */

    // Listen only to Add New Field + link
    $( '#text_plus_ddtt_suppressed_errors .add_form_field' ).on( 'click', function( e ) {
        e.preventDefault();

        // Only allow 10 at a time
        if ( x < 10 ) {

            // Add what is already in the database
            $( suppressedErrorsWrapper ).append( ddttNewRow() );
            x++;

            // Restart listening
            ddttRestartListening();

        } else {
            alert( 'You reached the limit.' );
        }
    } );

    // New row
    function ddttNewRow() {
        return '<div><input type="text" name="new[]" value=""/> <a href="javascript:void(0);" class="delete">Delete</a></div>';
    }

    // Start listening
    ddttStartListening();
    function ddttStartListening() {

        // Listen for delete
        $( suppressedErrorsWrapper ).on( 'click', '.delete', function( e ) {
            e.preventDefault();
            $( this ).parent( 'div' ).remove();
            x--;
        } );
    }

    // Stop listening for deletes
    function ddttRestartListening() {
        $( suppressedErrorsWrapper ).off( 'click' );
        // $( '.text_plus_ddtt_suppressed_errors input' ).off( 'input' );
        $( '#text_plus_ddtt_suppressed_errors .check' ).off( 'click' );
        ddttStartListening();
    }


    /**
     * NOTES
     */
    $( '.edit-link' ).on( 'click', function( e ) {
        e.preventDefault();
        
        var $notesDiv = $( this ).closest( '.notes' );
        var $row = $( this ).closest( 'tr' );
        var id = $row.data( 'id' );
        var currentText = $notesDiv.contents().filter(function() {
            return this.nodeType === 3;
        } ).text().trim();
        
        var $input = $( '<input>' , {
            type: 'text',
            name: 'notes[' + id + ']',
            value: currentText
        } );
        
        $notesDiv.empty().append( $input );
        $input.focus();
    });    
} )