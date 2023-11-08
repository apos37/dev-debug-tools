jQuery( $ => {
    // Get the Deactivate link
    $( '[data-slug="' + ddtt_deactivate.plugin_slug + '"] .deactivate a' ).on( 'click', function( e ) {
        
        // Prevent default behavior
        e.preventDefault();

        // Store the link
        const redirectLink = $( this ).attr( 'href' );


        /**
         * Create the modal
         */

        // Create the modal
        var modal = $( '<div id="ddtt-deactivate-modal-cont"><div id="ddtt-deactivate-modal" role="dialog" aria-labelledby="ddtt-deactivate-modal-header"><h2 id="ddtt-deactivate-modal-header">Quick Feedback</h2><p>If you have a moment, please let me know why you are deactivating:</p><form><div id="ddtt-dialog-cont"><ul id="ddtt-deactivate-reasons"></ul></div><div id="ddtt-deactivate-footer"></div></form></div></div>' );

        // Reasons
        const options = {
            'noneed': 'I no longer need the plugin',
            'short': 'I only needed the plugin for a short period',
            'errors': 'Found errors on the plugin',
            'conflict': 'There is a conflict with another plugin',
            'temp': 'It\'s temporary; just debugging an issue',
            'better': 'I found a better plugin',
            'other': 'Other',
        };

        // Add the radio button options
        $.each( options, function( key, value ) {
            var option = $( '<li class="reason"><input type="radio" id="reason-' + key + '" class="ddtt-reason" name="ddtt-deactivate-reason" value="' + key + '"> <label for="reason-' + key + '">' + value + '</label></li>' );
            modal.find( '#ddtt-deactivate-reasons' ).append( option );
        } );

        // Add comment section
        var comments = $( '<br><label for="ddtt-deactivate-comments">Additional comments for improving the plugin:</label><br><br><textarea id="ddtt-deactivate-comments" name="comments"></textarea><br><br>' );
        modal.find( '#ddtt-dialog-cont' ).append( comments );

        // Add Anonymous checkbox
        var anon = $( '<input type="checkbox" id="ddtt-deactivate-anonymously" class="ddtt-checkbox" name="anonymous" value="1"> <label for="ddtt-deactivate-anonymously" class="ddtt-checkbox-label">Anonymous feedback</label>' );
        modal.find( '#ddtt-deactivate-footer' ).append( anon );

        // Add contact checkbox
        var contact = $( '<br><input type="checkbox" id="ddtt-deactivate-contact" class="ddtt-checkbox" name="contact" value="1"> <label for="ddtt-deactivate-contact" id="ddtt-deactivate-contact-label" class="ddtt-checkbox-label">You may contact me for more information</label>' );
        modal.find( '#ddtt-deactivate-footer' ).append( contact );
        
        // Add disable checkbox
        var disable = $( '<br><input type="checkbox" id="ddtt-deactivate-disable" class="ddtt-checkbox" name="disable" value="1"> <label for="ddtt-deactivate-disable" id="ddtt-deactivate-disable-label" class="ddtt-checkbox-label">Don\'t show this form again</label>' );
        modal.find( '#ddtt-deactivate-footer' ).append( disable );

        // Add buttons
        var buttons = $( '<div id="ddtt-deactivate-buttons"><input type="submit" id="ddtt-submit" class="button button-primary" value="Deactivate" disabled> <input type="submit" id="ddtt-cancel"class="button button-secondary" value="Cancel"></div>' );
        modal.find( '#ddtt-deactivate-footer' ).append( buttons );

        // Add support server
        var server = $( '<p id="ddtt-footer-links"><a href="' + ddtt_deactivate.support_url + '">Discord Support Server</a> | <a href="http://apos37.com/">Apos37.com</a></p>' );
        modal.find( '#ddtt-deactivate-footer' ).append( server );

        // Add the modal
        $( 'body' ).append( modal );


        /**
         * Listen for selection
         */

        // Enable submit button only after a selection has been checked
        $( '.ddtt-reason' ).on( 'click', function( e ) {
            $( '#ddtt-submit' ).attr( 'disabled', false );
        } );


        /**
         * Listen for anonymous check
         */

        // Hide the contact checkbox if anonmyous is selected
        $( '#ddtt-deactivate-anonymously' ).on( 'click', function( e ) {
            if ( $( this ).is( ':checked' ) ) {
                $( '#ddtt-deactivate-contact' ).hide();
                $( '#ddtt-deactivate-contact-label' ).hide();
            } else {
                $( '#ddtt-deactivate-contact' ).show();
                $( '#ddtt-deactivate-contact-label' ).show();
            }
        } );


        /**
         * Close the modal
         */
        
        // Listen for escape key
        $( document ).keyup( function( e ) {

            // First check if it's escape key
            if ( e.key === "Escape" || e.keyCode === 27 ) {

                // Remove the modal complete
                $( "#ddtt-deactivate-modal-cont" ).remove();
            }
        } );

        // Now listen for cancel button
        $( '#ddtt-cancel' ).on( 'click', function( e ) {

            // Prevent default behavior
            e.preventDefault();

            // Remove the modal complete
            $( "#ddtt-deactivate-modal-cont" ).remove();
        } );


        /**
         * Send feedback
         */
        
        // Now listen for submit button
        $( '#ddtt-submit' ).on( 'click', function( e ) {

            // Prevent default behavior
            e.preventDefault();

            // Get the data from the link
            var nonce = ddtt_deactivate.nonce;
            var reasonVal = $( '.ddtt-reason:checked' ).val();
            var commentsVal = $( '#ddtt-deactivate-comments' ).val();
            var anonVal = $( '#ddtt-deactivate-anonymously' ).is( ':checked' );
            var canContact = $( '#ddtt-deactivate-contact' ).is( ':checked' );
            var disableVal = $( '#ddtt-deactivate-disable' ).is( ':checked' );

            // Validate
            if ( nonce !== '' && reasonVal !== '' ) {

                // Set up the args
                var args = {
                    type : 'post',
                    dataType : 'json',
                    url : ddtt_deactivate.ajaxurl,
                    data : { 
                        action: 'ddtt_send_feedback_on_deactivate',
                        nonce: nonce,
                        reason: reasonVal,
                        comments: commentsVal,
                        anonymous: anonVal,
                        contact: canContact,
                        disable: disableVal
                    },
                    success: function( response ) {

                        // Close the modal
                        $( "#ddtt-deactivate-modal-cont" ).remove();
                        
                        // If successful
                        if ( response.type == 'success' ) {
                            if ( response.method == 'discord' ) {
                                console.log( 'Your feedback has been sent to my Discord Support Server. Thank you!!' );
                            } else {
                                console.log( 'Your feedback has been sent to my email. Thank you!!' );
                            }
                        } else {
                            if ( response.method == 'discord' ) {
                                console.log( 'Uh oh! Something went wrong and your feedback was not sent to my Discord Support Server. Deactivating anyway...' );
                            } else {
                                console.log( 'Uh oh! Something went wrong and your feedback was not sent to my email. Deactivating anyway...' );
                            }
                        }

                        // Redirect
                        window.location.href = redirectLink;
                    }
                }
                // console.log( args );

                // Start the ajax
                $.ajax( args );
            }
        } );
    } );
} )