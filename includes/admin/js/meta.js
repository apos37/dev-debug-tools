jQuery( $ => {
    console.log( 'Meta JS Loaded...' );

    // Tab
    const tab = metaAjax.tab;
    if ( !tab ) {
        return;
    }

    // Postmeta only
    if ( tab == 'postmeta' ) {

        // Listen for select taxonomy dropdown
        $( '#clear-terms-field' ).on( 'change', function( e ) {
            const taxonomy = $( this ).val();
            var clearBtn = $( '.no-choice.clear-taxonomies-button' );
            if ( taxonomy != '' ) {
                clearBtn.show();
            } else {
                clearBtn.hide();
            }
        } );
    }

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

    // Listen for action
    $( 'input[type="radio"][name="update"]' ).on( 'change', function() {

        // Action
        const action = $( this ).val();

        // Rows
        var metaKeyRowID = '#metakey-text';
        var metaKeyTypeRowID = '#metakey-type';
        var formatRowID = '#metakey-format';
        var customKeysRowID = '#metakey-custom-select';
        var delsChoiceRowID = '#metakey-dels-choice';
        var newValueRowID = '#metakey-value';

        // Inputs
        var metaKeyTextInputID = '#update_meta_key_text';
        var formatSelectID = '#update_meta_key_format';
        var objectKeysSelectID = '#update_meta_key_object_select';
        var customKeysSelectID = '#update_meta_key_custom_select';

        // Hide all conditional rows at start
        $( `.metakey-tr` ).hide();

        // Clear values no matter what
        $( '#update-meta-form' ).find( 'input[type="text"], select:not([name="format"]), textarea' ).val( '' );
        $( `${formatSelectID}` ).val( 'string' );
        $( '#update-meta-form' ).find( 'input[type="text"], select, textarea' ).each( function() {
            $( this ).removeAttr( 'required' );
        } );
        $( `${metaKeyTextInputID}, ${objectKeysSelectID}, ${customKeysSelectID}` ).attr( 'name', '' );

        // Text label
        var textLabel = ( action != 'dels' ) ? 'Meta Key' : 'All Custom Meta Keys Starting With';
        $( '#metakey-text-label' ).text( textLabel );

        // Add
        if ( action == 'add' ) {
            $( `${metaKeyRowID}, ${formatRowID}, ${newValueRowID}` ).show().find( 'input, select, textarea' ).attr( 'required', 'required' );
            $( `${metaKeyTextInputID}` ).attr( 'name', 'mk' );
        
        // Upd
        } else if ( action == 'upd' ) {
            $( `${metaKeyTypeRowID}, ${formatRowID}, ${newValueRowID}` ).show().find( 'select, textarea' ).attr( 'required', 'required' );
        
        // Del
        } else if ( action == 'del' ) {
            $( `${customKeysRowID}` ).show().find( 'select' ).attr( 'required', 'required' );
            $( `${customKeysSelectID}` ).attr( 'name', 'mk' );
        
        // Dels
        } else if ( action == 'dels' ) {
            $( `${metaKeyRowID}, ${delsChoiceRowID}` ).show().find( 'input, select' ).attr( 'required', 'required' );
            $( `${metaKeyTextInputID}` ).attr( 'name', 'mk' );
        }

        // The update button
        $( '.no-choice.meta-update-button' ).show();
    } );

    // Listen for meta key type
    $( '#update_meta_key_type' ).on( 'change', function( e ) {

        // Type
        const type = $( this ).val();

        // Rows
        var objectKeysRowID = '#metakey-object-select';
        var customKeysRowID = '#metakey-custom-select';

        // Fields
        var objectKeysSelectID = '#update_meta_key_object_select';
        var customKeysSelectID = '#update_meta_key_custom_select';

        // Objects
        if ( type == 'object' ) {
            $( `${customKeysRowID}` ).hide();
            $( `${objectKeysRowID}` ).show();
            $( `${objectKeysSelectID}` ).attr( 'name', 'mk' ).attr( 'required', 'required' );
            $( `${customKeysSelectID}` ).attr( 'name', '' ).removeAttr( 'required' );
        
        // Custom
        } else if ( type == 'custom' ) {
            $( `${objectKeysRowID}` ).hide();
            $( `${customKeysRowID}` ).show();
            $( `${customKeysSelectID}` ).attr( 'name', 'mk' ).attr( 'required', 'required' );
            $( `${objectKeysSelectID}` ).attr( 'name', '' ).removeAttr( 'required' );

        // Nothing
        } else {
            $( `${objectKeysRowID}, ${customKeysRowID}` ).hide();
            $( `${objectKeysSelectID}, ${customKeysSelectID}` ).attr( 'name', '' );
        }
    } );

    // Populate value field on update
    $( '#update_meta_key_object_select, #update_meta_key_custom_select' ).on( 'change', function( e ) {
        
        // Get the stuff
        const type =  this.id.includes('object') ? 'object' : 'custom';
        const metaKey = $( this ).val();
        const id = metaAjax.id;
        const nonce = metaAjax.nonce;

        // Get the data
        if ( nonce && tab && id && type && metaKey ) {

            // Set up the args
            var args = {
                type : 'post',
                dataType : 'json',
                url : metaAjax.ajaxurl,
                data : { 
                    action: 'ddtt_update_meta',
                    nonce: nonce,
                    tab: tab,
                    id: id,
                    type: type,
                    metaKey: metaKey
                },
                success: function( response ) {
                    if ( response.type == 'success' ) {
                        $( '#update_meta_key_value' ).val( response.value );
                        $( '#update_meta_key_format' ).val( response.format );
                    } else {
                        console.log( 'Failed to retrieve meta value...' );                
                    }
                }
            }

            // Start the ajax
            $.ajax( args );
        }
    } );

    // Updated value status
    var hasUpdatedValue = false;

    // Listen for value not being correct format
    $( '#update_meta_key_value' ).on( 'input', function() {
        // Get the values
        const format = $( '#update_meta_key_format' ).val();
        const val = $( this ).val().trim();

        // Update value status
        if ( val != '' ) {
            hasUpdatedValue = true;
        }

        // Spit out warning
        incorrectFormatWarning( format, val );
    } );

    // Listen for value not being correct format
    $( '#update_meta_key_format' ).on( 'change', function() {
        // The value field
        var valueTextArea = $( '#update_meta_key_value' );

        // Get the values
        const format = $( this ).val();
        var val = valueTextArea.val().trim();

        // Reset value status if no value
        if ( valueTextArea.val() == '' ) {
            hasUpdatedValue = false;
        }

        // Populate value if nothing has been updated yet
        if ( !hasUpdatedValue &&
             $( 'input[type="radio"][name="update"]:checked' ).val() == 'add' ) {
            const format = $( this ).val();

            // Arrays and objects, populate JSON
            if ( format == 'array' || format == 'object' ) {
                var newVal = {
                    "key_1": "value 1", 
                    "key_2": "value 2", 
                    "key_3": {
                        0: "item 1",
                        1: "item 2",
                        2: "item 3"
                    }
                };
                val = JSON.stringify( newVal, null, 4 );
                valueTextArea.val( val )

            // String, empty box
            } else if ( format == 'string' ) {
                val = '';
                valueTextArea.val( val );
            }
        }

        // Warn
        incorrectFormatWarning( format, val );
    } );

    // Warn about wrong format
    function incorrectFormatWarning( format, val ) {
        // Warning div
        var warningDiv = $( '#value-warning' );

        // If val is blank
        if ( !val || val == '' ) {
            hasUpdatedValue = false;
            warningDiv.hide().text( '' );
            return;
        }

        // Check if the value starts with [ or { and ends with ] or }
        const isJsonLike = ( val.startsWith( '[' ) && val.endsWith( ']' ) ) || ( val.startsWith( '{' ) && val.endsWith( '}' ) );

        // Check for serialized string pattern
        const isSerializedString = /^(a:\d+:{.*}|O:\d+:"[^"]+":\d+:{.*})$/.test( val );

        // Check
        if ( isJsonLike && format == 'string' ) {
            warningDiv.html( '&#9888; JSON detected with String format selected! You may want to use an Array or Object format.' ).show();
        } else if ( isSerializedString && format != 'string' ) {
            warningDiv.html( '&#9888; Serialized string detected, please use String format!' ).show();
        } else if ( !isJsonLike && format != 'string' ) {
            warningDiv.html( '&#9888; No JSON detected! You may want to use String format.' ).show();
        } else {
            warningDiv.hide().text( '' );
        }
    } // End incorrectFormatWarning()
} )