jQuery( $ => {
    // console.log( 'Settings JS Loaded...' );

    // Listen for enabling/disabling cURL timeout
    $( '#ddtt_enable_curl_timeout' ).on( 'change', function() {
        if ( $( this ).is( ':checked' ) ) {
            $( '#row_ddtt_change_curl_timeout' ).show();
        } else {
            $( '#row_ddtt_change_curl_timeout' ).hide();
        }
    } );

    // Log files text+ field
    const savedLogFiles = settingsAjax.log_files;
    var logFilesWrapper = $( '#text_plus_ddtt_log_files' );

    // Count
    var x = 2;

    /**
     * LOAD EXTRA FIELDS FROM DATABASE
     */

    // Iter the values
    if ( parseInt( savedLogFiles.length ) > 1 ) {
        savedLogFiles.slice( 1 ).forEach( function( v ) {
            if ( v != '' ) {

                // Get the data
                var nonce = settingsAjax.nonce;
                if ( nonce !== '' && v !== '' ) {

                    // Set up the args
                    var args = {
                        type : 'post',
                        dataType : 'json',
                        url : settingsAjax.ajaxurl,
                        data : { 
                            action: 'ddtt_verify_logs',
                            nonce: nonce,
                            path: v
                        },
                        success: function( response ) {
                            
                            // If successful
                            if ( response.type == 'success' ) {
                                
                                // Add the input
                                $( logFilesWrapper ).append( ddttNewRow( v, true ) );

                            // No results
                            } else {
                                
                                // Add the input
                                $( logFilesWrapper ).append( ddttNewRow( v, false ) );
                            }

                            // Increase count
                            x++;

                            // Restart listening
                            ddttRestartListening();
                        }
                    }

                    // Start the ajax
                    $.ajax( args );
                }
            }
        } );
    }

    /**
     * ADD NEW FIELDS
     */

    // Listen only to Add New Field + link
    $( '#text_plus_ddtt_log_files .add_form_field' ).on( 'click', function( e ) {
        e.preventDefault();

        // Only allow 20
        if ( x < 20 ) {

            // Add what is already in the database
            $( logFilesWrapper ).append( ddttNewRow( '', false, true ) );
            x++;

            // Restart listening
            ddttRestartListening();

        } else {
            alert( 'You reached the limit.' );
        }
    } );

    // New row
    function ddttNewRow( path, verified, showCheck = false ) {
        var verifiedClass = '';
        var verifiedText = '';
        if ( verified ) {
            verifiedClass = 'enabled';
            verifiedText = 'VERIFIED';
        } else {
            verifiedClass = 'disabled';
            verifiedText = 'FILE NOT FOUND';
        }
        var displayVerification = 'inline-block';
        var displayCheck = 'none';
        var disableCheck = '';
        if ( showCheck ) {
            displayVerification = 'none';
            displayCheck = 'inline-block';
            disableCheck = ' disabled';
        }
        return '<div><input type="text" name="ddtt_log_files[]" value="' + path + '" pattern=".*\.txt$"/> <code class="verification ' + verifiedClass + '" style="display: ' + displayVerification + '">' + verifiedText + '</code> <button type="button" class="button check" style="display: ' + displayCheck + '"' + disableCheck + '>CHECK</button> <a href="javascript:void(0);" class="delete">Delete</a></div>';
    }

    // Start listening
    ddttStartListening();
    function ddttStartListening() {

        // Listen for delete
        $( logFilesWrapper ).on( 'click', '.delete', function( e ) {
            e.preventDefault();
            $( this ).parent( 'div' ).remove();
            x--;
        } );

        // Listen for log file input changes
        $( '#text_plus_ddtt_log_files input' ).on( 'input', function() {

            // Enable button if there is a value at all
            if ( this.value.length > 0 ) {
                $( this ).parent().find( 'check' ).prop( 'disabled', false );
            }
                
            // Change Verified <code> to CHECK button
            const verification = $( this ).parent().find( 'code' );
            if ( verification ) {
                var disabled = false;
                if ( this.value.length == 0 ) {
                    disabled = true;
                }
                $( this ).parent().find( 'code' ).css( 'display', 'none' );
                $( this ).parent().find( 'button' ).css( 'display', 'inline-block' ).prop( 'disabled', disabled );
            }
        } );

        // Listen for checks
        $( '#text_plus_ddtt_log_files .check' ).on( 'click', function( e ) {
            e.preventDefault();

            // Save this
            const checkBtn = $( this );

            // Get the value
            const path = checkBtn.parent().find( 'input' ).val();

            // Get the data
            var nonce = settingsAjax.nonce;
            if ( nonce !== '' && path !== '' ) {

                // Set up the args
                var args = {
                    type : 'post',
                    dataType : 'json',
                    url : settingsAjax.ajaxurl,
                    data : { 
                        action: 'ddtt_verify_logs',
                        nonce: nonce,
                        path: path
                    },
                    success: function( response ) {

                        // Display the verified <code>
                        const verifiedCode = checkBtn.parent().find( 'code' );
                        verifiedCode.css( 'display', 'inline-block' );
                        
                        // If successful
                        if ( response.type == 'success' ) {

                            // Change code
                            if ( verifiedCode.hasClass( 'disabled' ) ) {
                                verifiedCode.removeClass( 'disabled' ).addClass( 'enabled' );
                            }
                            verifiedCode.text( 'VERIFIED' );

                        // No results
                        } else {
                            
                            // Change code
                            if ( verifiedCode.hasClass( 'enabled' ) ) {
                                verifiedCode.removeClass( 'enabled' ).addClass( 'disabled' );
                            }
                            verifiedCode.text( 'FILE NOT FOUND' );
                        }

                        // Hide the check button
                        checkBtn.css( 'display', 'none' );
                    }
                }

                // Start the ajax
                $.ajax( args );

            // Empty results
            } else {
                
                // Change
                checkBtn.replaceWith( '<button type="button" class="button check">CHECK</button>' );
            }
        } );
    }

    // Stop listening for deletes
    function ddttRestartListening() {
        $( logFilesWrapper ).off( 'click' );
        $( '.text_plus_ddtt_log_files input' ).off( 'input' );
        $( '#text_plus_ddtt_log_files .check' ).off( 'click' );
        ddttStartListening();
    }
} )