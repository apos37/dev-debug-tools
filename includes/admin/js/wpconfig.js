jQuery( $ => {
    // console.log( 'WP Config JS Loaded...' );

    // Set editing flag
    let editing = false;

    // Show/Hide Descriptions
    $( '.learn-more' ).on( 'click', function( e ) {
        e.preventDefault();
        if ( !editing ) {
            const name = $( this ).data( 'name' );
            $( `#desc-${name}` ).toggleClass( 'is-open' );
        }
    } );

    // Show/Hide Preview Button
    $( `.checkbox-cell input[name="a[]"], .checkbox-cell input[name="r[]"], .checkbox-cell input[name="u[]"]` ).on( 'change', function() {
        if ( $( `.checkbox-cell input[name="a[]"]:checked, .checkbox-cell input[name="r[]"]:checked, .checkbox-cell input[name="u[]"]:checked` ).length > 0 ) {
            $( '#preview_btn' ).prop( 'disabled', false );
        } else {
            $( '#preview_btn' ).prop( 'disabled', true );
        }
    } );

    // Don't Allow Remove && Update
    $( `.checkbox-cell input[name="a[]"], .checkbox-cell input[name="r[]"], .checkbox-cell input[name="u[]"]` ).on( 'change', function() {
        const name = $( this ).attr( 'name' );
        var val = $( this ).val();
        if ( this.checked ) {
            const proposedTab = $( `.snippet-cell[data-name="${val}"] .snippet-tab.proposed` );
            if ( name == 'a[]' ) {
                $( `.snippet-cell[data-name="${val}"] .snippet-edit-links` ).addClass( 'active' );
            } else if ( name == 'r[]' ) {
                $( `.checkbox-cell input[name="u[]"][value="${val}"]` ).prop( 'checked', false );
                const currentTab = $( `.snippet-cell[data-name="${val}"] .snippet-tab.current` );
                openCurrentTab( currentTab );
                proposedTab.hide();
                $( `.snippet-cell[data-name="${val}"] .snippet-edit-links` ).removeClass( 'active' );
            } else if ( name == 'u[]' ) {
                $( `.checkbox-cell input[name="r[]"][value="${val}"]` ).prop( 'checked', false );
                openProposedTab( proposedTab );
                proposedTab.show();
                $( `.snippet-cell[data-name="${val}"] .snippet-edit-links` ).addClass( 'active' );
            }
        } else {
            if ( name == 'a[]' || name == 'u[]' ) {
                $( `.snippet-cell[data-name="${val}"] .snippet-edit-links` ).removeClass( 'active' );
            }
        }
    } );

    // Snippet Tabs
    $( `.snippet-tab.current` ).on( 'click', function( e ) {
        e.preventDefault();
        if ( !editing ) {
            openCurrentTab( $( this ) );
        }
    } );
    $( `.snippet-tab.proposed` ).on( 'click', function( e ) {
        e.preventDefault();
        if ( !editing ) {
            openProposedTab( $( this ) );
        }
    } );

    // Open Proposed tab
    function openCurrentTab( currentTab ) {
        currentTab.addClass( 'active' );
        currentTab.parent().find( '.snippet-tab.proposed' ).removeClass( 'active' );
        currentTab.parent().find( '.snippet_container.current' ).addClass( 'active' );
        currentTab.parent().find( '.snippet_container.proposed' ).removeClass( 'active' );
        currentTab.parent().find( '.snippet-edit-links' ).removeClass( 'active' );
    } // End openCurrentTab

    // Open Current tab
    function openProposedTab( proposedTab ) {
        proposedTab.addClass( 'active' );
        proposedTab.parent().find( '.snippet-tab.current' ).removeClass( 'active' );
        proposedTab.parent().find( '.snippet_container.proposed' ).addClass( 'active' );
        proposedTab.parent().find( '.snippet_container.current' ).removeClass( 'active' );
        const name = proposedTab.parent().data( 'name' );
        if ( $( `.checkbox-cell input[name="a[]"][value="${name}"]` ).prop( 'checked' ) || $( `.checkbox-cell input[name="u[]"][value="${name}"]` ).prop( 'checked' ) ) {
            proposedTab.parent().find( '.snippet-edit-links' ).addClass( 'active' );
        }
    } // End openProposedTab

    // Edit Snippet
    $( '.edit' ).on( 'click', function( e ) {
        e.preventDefault();
        if ( !editing ) {
            var snippetCell = $( this ).parent().parent();
            const name = snippetCell.data( 'name' );
            var proposedCont = $( this ).parent().parent().find( '.snippet_container.proposed' );
            const text = proposedCont.text();
            proposedCont.html( `<textarea id="update-${name}">${text}</textarea>` );
            $( this ).hide();                                                       // Edit
            $( this ).parent().find( '.save' ).css( 'display', 'inline-block' );    // Save
            $( this ).parent().find( '.sep' ).css( 'display', 'inline-block' );     // Sep
            $( this ).parent().find( '.cancel' ).css( 'display', 'inline-block' );  // Cancel

            // Disable clicks on other elements while editing
            editing = true;
            $( '#file-update-form' ).on( 'click', 'a, button, input[type="checkbox"]', function( e ) {
                if ( editing && 
                    !$( e.target ).closest( `#update-${name}` ).length && 
                    !$( e.target ).closest( `.snippet-cell[data-name="${name}"] .snippet-edit-links` ).length &&
                    !$( e.target ).closest( '#edit-notice' ).length &&
                    !$( e.target ).closest( '#edit-error-notice' ).length ) {
                    e.preventDefault();
                    e.stopPropagation();
                    $( '#edit-notice' ).show();
                    
                    $( document ).on( 'click', function() {
                        $( '#edit-notice' ).hide();
                        $( document ).off( 'click' );
                    } );
                }
            } );

            // Cancel Editing
            $( '.cancel' ).on( 'click', function( e ) {
                e.preventDefault();
                proposedCont.text( text );
                resetEditLinks( name );
                editing = false;
                $( '#file-update-form' ).off( 'click' );
                $( '.cancel' ).off( 'click' );
            } );

            // Save Editing
            $( '.save' ).on( 'click', function( e ) {
                e.preventDefault();
                const newText = $( `#update-${name}` ).val();
                const saveLink = $( this );
                if ( text !== newText ) {

                    // Get the data
                    var nonce = validateAjax.nonce;
                    if ( nonce !== '' ) {

                        // Set up the args
                        var args = {
                            type : 'post',
                            dataType : 'json',
                            url : validateAjax.ajaxurl,
                            data : { 
                                action: 'ddtt_validate_code',
                                nonce: nonce,
                                code: newText
                            },
                            beforeSend: function() {
                                saveLink.css( 'cursor', 'wait' );
                                $( document.body ).css( 'cursor', 'wait' );
                            },
                            success: function( response ) {
                                saveLink.css( 'cursor', 'default' );
                                $( document.body ).css( 'cursor', 'default' );
                                
                                // If successful
                                if ( response.type == 'success' ) {
                                    saveSnippet( name, newText );

                                // Error found
                                } else {
                                    if ( response.msg == 'No code found' ) {
                                        saveSnippet( name, newText );
                                    } else {
                                        $( '#edit-error-notice .error-msg' ).text( response.msg );
                                        $( '#edit-error-notice .yes' ).attr( 'data-name', name );
                                        $( '#edit-error-notice' ).show();

                                        // Continue Editing
                                        $( '#edit-error-notice .no' ).on( 'click', function( e ) {
                                            e.preventDefault();
                                            $( this ).parent().parent().hide();
                                            $( '#edit-error-notice .no' ).off( 'click' );
                                        } );

                                        // Cancel Editing
                                        $( '#edit-error-notice .yes' ).on( 'click', function( e ) {
                                            e.preventDefault();
                                            $( this ).parent().parent().hide();
                                            saveSnippet( name, newText );
                                        } );
                                    }
                                }
                            },
                            error: function( jqXHR, textStatus, errorThrown ) {
                                console.error( "AJAX Error:", textStatus, errorThrown );
                                saveLink.css( 'cursor', 'default' );
                                $( document.body ).css( 'cursor', 'default' );
                                saveSnippet( name, newText );
                            }
                        }

                        // Start the ajax
                        $.ajax( args );
                    }

                    // Save the snippet
                    function saveSnippet( name, newText ) {
                        proposedCont.text( newText );
                        resetEditLinks( name );
                        editing = false;
    
                        if ( $( `#updated-${name}` ).length > 0 ) {
                            $( `#updated-${name}` ).val( newText );
                        } else {
                            snippetCell.append( `<input id="updated-${name}" type="hidden" name="s[${name}]" value="${newText}">` );
                        }
                        proposedCont.addClass( 'changed' );

                        $( '#file-update-form' ).off( 'click' );
                        $( '.save' ).off( 'click' );
                    }
                } else {
                    proposedCont.text( text );
                    resetEditLinks( name );
                    editing = false;
                    $( '#file-update-form' ).off( 'click' );
                    $( '.save' ).off( 'click' );
                }
            } );
        }
    } );

    // Reset Edit Links
    function resetEditLinks( name ) {
        var snippetCell = $( `.snippet-cell[data-name="${name}"]` );
        snippetCell.find( '.edit' ).show();     // Edit
        snippetCell.find( '.save' ).hide();     // Save
        snippetCell.find( '.sep' ).hide();      // Sep
        snippetCell.find( '.cancel' ).hide();   // Cancel
    } // End resetEditLinks()
} )