// Helper logs
DevDebugTools.Helpers.log_file_path();
DevDebugTools.Helpers.log_localization( 'ddtt_settings' );

// Now start jQuery
jQuery( function( $ ) {

    /**
     * Handle conditional visibility of settings rows based on the value of a select field.
     */
    $( document ).on( 'change', '[id^="ddtt_"]', function() {
        $( '[data-condition]' ).each( function() {
            const $row = $( this );
            const requiredValue = String( $row.data( 'value' ) ).replace(/^["']|["']$/g, '');
            const conditionField = String( $row.data( 'condition' ) ).replace( /[^a-zA-Z0-9_\-]/g, '' );
            const $field = $( `#ddtt_${conditionField}` );

            let currentValue;
            if ( $field.is( ':checkbox' ) ) {
                currentValue = $field.is( ':checked' ) ? $field.val() : '';
            } else {
                currentValue = $field.val();
            }

            // console.log( 'Condition field:', conditionField, 'Required value:', requiredValue, 'Current value:', currentValue );

            if ( currentValue == requiredValue ) {
                $row.removeClass( 'ddtt-hidden' );
            } else {
                $row.addClass( 'ddtt-hidden' );
            }
        } );
    } );


    /**
     * Paths
     */
    $( document ).on( 'click', '.ddtt-add-path', function( e ) {
        e.preventDefault();

        const key = $( this ).data( 'key' );

        const $newField = $( `
            <div class="ddtt-text-field-wrap has-verify">
                <input type="text" name="${key}[]" value="" class="regular-text" />
                <button type="button" class="ddtt-button ddtt-verify-path" data-key="${key}">${ddtt_settings.i18n.verifyButton}</button>
                <button type="button" class="ddtt-button ddtt-remove-path">–</button>
            </div>
        ` );

        $newField.insertBefore( $( this ) );
    } );

    $( document ).on( 'click', '.ddtt-remove-path', function( e ) {
        e.preventDefault();
        $( this ).closest( '.ddtt-text-field-wrap' ).remove();
    } );

    $( document ).on( 'click', '.ddtt-verify-path', function( e ) {
        e.preventDefault();

        const button = $( this );
        const input = button.siblings( 'input[type="text"]' );
        let path = input.val().trim();

        if ( path.startsWith( 'http://' ) || path.startsWith( 'https://' ) ) {
            try {
                const url = new URL( path );
                path = url.pathname.replace( /^\/+/, '' );
                input.val( path );
            } catch ( error ) {
                // invalid URL, do nothing
            }
        }

        if ( ! path ) {
            alert( ddtt_settings.i18n.verifyFail );
            button.removeClass( 'ddtt-status-verified ddtt-status-failed' ).addClass( 'ddtt-status-failed' ).text( ddtt_settings.i18n.failed );
            return;
        }

        button.prop( 'disabled', true ).removeClass( 'ddtt-status-verified ddtt-status-failed' ).text( ddtt_settings.i18n.verifying );

        $.post( ajaxurl, {
            action: 'ddtt_verify_settings_path',
            nonce: ddtt_settings.nonce,
            path: path,
        } )
        .done( function( response ) {
            if ( response.success && response.data.exists ) {
                button.text( ddtt_settings.i18n.verified ).addClass( 'ddtt-status-verified' );
            } else {
                const msg = ddtt_settings.i18n.verifyFail + ( response.data.path ? ' — ' + response.data.path : '' );
                alert( msg );
                button.text( ddtt_settings.i18n.failed ).addClass( 'ddtt-status-failed' );
            }
        } )
        .fail( function() {
            alert( ddtt_settings.i18n.verifyError );
            button.text( ddtt_settings.i18n.failed ).addClass( 'ddtt-status-failed' );
        } )
        .always( function() {
            button.prop( 'disabled', false );
        } );
    } );

    $( document ).on( 'input', '.ddtt-text-field-wrap.has-verify input[type="text"]', function() {
        const button = $( this ).siblings( '.ddtt-verify-path' );
        button.text( ddtt_settings.i18n.verifyButton )
            .removeClass( 'ddtt-status-verified ddtt-status-failed' );
    } );


    /**
     * URL Plus
     */
    $( document ).on( 'click', '.ddtt-add-url', function( e ) {
        e.preventDefault();

        const $container = $( this ).closest( '.ddtt-url-plus-wrap' );
        const key = $( this ).data( 'key' );

        const $newField = $( `
            <div class="ddtt-text-field-wrap">
                <input type="url" name="${key}[]" value="" class="regular-text" />
                <button type="button" class="ddtt-button ddtt-remove-url">–</button>
            </div>
        ` );

        $newField.insertBefore( $container.find( '.ddtt-add-url' ) );
    } );

    $( document ).on( 'click', '.ddtt-remove-url', function( e ) {
        e.preventDefault();
        $( this ).closest( '.ddtt-text-field-wrap' ).remove();
    } );


    /**
     * Toggle password visibility
     */
    $( document ).on( 'click', '.ddtt-toggle-password', function( e ) {
        e.preventDefault();
        const $input = $( this ).siblings( '.ddtt-password-input' );
        const isPassword = $input.attr( 'type' ) === 'password';
        $input.attr( 'type', isPassword ? 'text' : 'password' );
    } );


    /**
     * Dirty handler using MutationObserver for Select2 users field
     */
    let isDirty = false;
    const settingsContent = $( '.ddtt-settings-content' );
    const submitButton   = $( '#ddtt-save-settings' );

    // Helper function to mark the form as dirty and update the button
    function markFormAsDirty() {
        if ( ! isDirty ) {
            isDirty = true;
            submitButton.prop( 'disabled', false ).text( ddtt_settings.i18n.saveButton );
        }
    }

    // Standard input, textarea, select listener
    settingsContent.find( 'input, select, textarea' ).on( 'input change', function() {
        if ( $( this ).is( '.ddtt-upload-input' ) || $( this ).is( '[data-ignore="yes"]' ) ) {
            return;
        }
        markFormAsDirty();
    } );

    $( document ).on( 'click', '.ddtt-remove-user', markFormAsDirty );
    $( '.ddtt-add-path' ).on( 'click', markFormAsDirty );


    /**
     * Ajax for saving settings.
     */
    $( document ).on( 'click', '#ddtt-save-settings', function( e ) {
        e.preventDefault();

        isDirty = false;

        const $button = $( this );
        const subsection = $button.data( 'subsection' );
        const $fields = $( '.ddtt-settings-row .ddtt-settings-field :input' ).filter( function() {
            // Ignore inputs inside rows that are not part of a ddtt-settings-row
            if ( $( this ).closest( '.ddtt-settings-row' ).closest( '[id^="ddtt_"]' ).length !== 0 ) {
                return false;
            }
            // Ignore inputs with data-ignore="yes"
            if ( $( this ).is( '[data-ignore="yes"]' ) ) {
                return false;
            }
            return true;
        } );

        if ( subsection == 'general' && $( '#ddtt_disable_error_counts' ).is( ':checked' ) ) {
            $( '#ddtt-error-count' ).hide();
        } else {
            $( '#ddtt-error-count' ).show();
        }

        const data = {
            action: 'ddtt_save_settings',
            nonce: ddtt_settings.nonce,
            subsection: subsection,
            options: {},
        };

        $fields.each( function() {
            const $input = $( this );
            const name = $input.attr( 'name' );
            if ( ! name ) {
                return;
            }

            // Get the name without the '[]' suffix
            const cleanName = name.endsWith('[]') ? name.slice(0, -2) : name;

            if ( $input.is( ':checkbox' ) ) {
                if ( name.endsWith( '[]' ) ) {
                    if ( ! data.options[ cleanName ] ) {
                        data.options[ cleanName ] = [];
                    }
                    if ( $input.is( ':checked' ) ) {
                        data.options[ cleanName ].push( $input.val() );
                    }
                } else {
                    data.options[ cleanName ] = $input.is( ':checked' );
                }
            } else if ( $input.is( ':radio' ) ) {
                if ( $input.is( ':checked' ) ) {
                    data.options[ cleanName ] = $input.val();
                }
            } else {
                if ( name.endsWith( '[]' ) ) {
                    if ( ! data.options[ cleanName ] ) {
                        data.options[ cleanName ] = [];
                    }
                    data.options[ cleanName ].push( $input.val() );
                } else {
                    data.options[ cleanName ] = $input.val();
                }
            }
        } );

        $button.prop( 'disabled', true ).text( ddtt_settings.i18n.saving );

        $.post( ajaxurl, data )
            .done( function( response ) {
                if ( response.success ) {
                    $button.text( ddtt_settings.i18n.saved );
                    // console.log( 'Settings saved successfully:', response.data );
                } else {
                    console.error( 'Save failed:', response );
                    $button.text( ddtt_settings.i18n.saveError );
                    // alert( response.data?.message || ddtt_settings.i18n.saveError );
                }

                $( `<div class="ddtt-toast ${ response.success ? 'ddtt-toast-success' : 'ddtt-toast-error' }">${ response.success ? ddtt_settings.i18n.saveSuccess : ddtt_settings.i18n.saveError }</div>` )
                    .appendTo( 'body' )
                    .hide()
                    .fadeIn( 200 )
                    .delay( 2000 )
                    .fadeOut( 400, function() {
                        // $( this ).remove();
                    } );
            } )
            .fail( function( jqXHR, textStatus, errorThrown ) {
                console.error( 'AJAX error:', {
                    status: textStatus,
                    error: errorThrown,
                    responseText: jqXHR.responseText
                } );
                $button.text( ddtt_settings.i18n.saveError );

                $( `<div class="ddtt-toast ddtt-toast-error'">${ ddtt_settings.i18n.saveError } ${ errorThrown }</div>` )
                    .appendTo( 'body' )
                    .hide()
                    .fadeIn( 200 )
                    .delay( 2000 )
                    .fadeOut( 400, function() {
                        // $( this ).remove();
                    } );
            } )
    } );


    // The 'beforeunload' event listener should remain separate
    window.addEventListener( 'beforeunload', function( event ) {
        if ( isDirty ) {
            event.preventDefault();
            return '';
        }
    } );


    /**
     * Save changes on Ctrl+S or Cmd+S
     */
    $( document ).on( 'keydown', function( event ) {
        // Check for Ctrl key (or Cmd key on Mac) and 'S' key
        if ( ( event.ctrlKey || event.metaKey ) && event.key === 's' ) {
            event.preventDefault();

            // Check if the form is dirty and the button is enabled before saving
            const submitButton = $( '#ddtt-save-settings' );
            if ( ! submitButton.prop( 'disabled' ) ) {
                submitButton.trigger( 'click' );
            }
        }
    } );


    /**
     * Upload button
     */
    // Add to your admin JS file
    $( document ).on( 'change', '.ddtt-upload-input', function () {
        var filename = this.files && this.files.length ? this.files[ 0 ].name : '';
        var id = $( this ).attr( 'id' );
        $( '#' + id + '_filename' ).text( filename );
        $( '#' + id + '_upload' ).prop( 'disabled', ! filename );
    } );


    /**
     * Handle the Import Object
     */
    $( document ).on( 'click', '#ddtt_import_object_upload', function ( e ) {
        e.preventDefault();

        var $input = $( '#ddtt_import_object' );
        var file = $input[ 0 ].files[ 0 ];
        if ( ! file ) {
            return;
        }

        var reader = new FileReader();
        reader.onload = function ( e ) {
            var fileContents = e.target.result;
            var jsonData = fileContents;

            $.post( ajaxurl, {
                action: 'ddtt_metadata_import',
                nonce: ddtt_settings.nonce,
                jsonData: jsonData
            }, function ( response ) {
                if ( response.success ) {
                    window.location.href = response.data.redirect;
                } else {
                    alert( 'Import failed: ' + ( response.data || 'Unknown error.' ) );
                }
            } );
        };
        reader.readAsText( file );
    } );


    /**
     * Handle the Import Settings
     */
    $( document ).on( 'click', '#ddtt_import_settings_upload', function ( e ) {
        e.preventDefault();

        var $input = $( '#ddtt_import_settings' );
        var file = $input[ 0 ].files[ 0 ];
        if ( ! file ) {
            return;
        }

        $( this ).addClass( 'ddtt-loading-msg' ).prop( 'disabled', true ).text( ddtt_settings.i18n.importing );

        var reader = new FileReader();
        reader.onload = function ( e ) {
            var fileContents = e.target.result;
            var jsonData = fileContents;

            $.post( ajaxurl, {
                action: 'ddtt_settings_import',
                nonce: ddtt_settings.nonce,
                jsonData: jsonData
            }, function ( response ) {
                if ( response.success ) {
                    DevDebugTools.Helpers.reload_page();
                } else {
                    alert( 'Import failed: ' + ( response.data || 'Unknown error.' ) );
                }
            } );
        };
        reader.readAsText( file );
    } );


    /**
     * Refresh Admin Bar Menu Links
     */
    $( document ).on( 'click', '#ddtt-refresh-admin-bar-menu-links', function( e ) {
        e.preventDefault( );

        var button = $( this );

        var successMark = button.next( '.ddtt-refresh-success' );
        if ( successMark.length ) {
            successMark.remove( );
            return;
        }

        button.addClass( 'ddtt-loading-msg ddtt-button-disabled' );

        $.post( ajaxurl, {
            action: 'ddtt_admin_bar_refresh_menu_links',
            nonce: ddtt_settings.nonce,
        }, function( response ) {
            button.removeClass( 'ddtt-loading-msg ddtt-button-disabled' );

            if ( response.success ) {
                console.log( 'Manual refresh successful:', response );
                button.after(
                    '<span class="ddtt-refresh-success" style="display:inline-flex; align-items:center; justify-content:center; width:1.5rem; height:1.5rem; background-color: var(--color-success); border-radius:50%; font-size:0.8rem; margin-left:15px;">✔</span>' + 
                    '<span class="ddtt-refresh-message" style="margin-top:5px; display: block; color: var(--color-success);">' + response.data.message + '</span>'
                );
            } else {
                console.log( 'Manual refresh failed:', response );
            }
        } );
    } );


    /**
     * Reset All Plugin Data Now
     */
    $( document ).on( 'click', '#ddtt_reset_plugin_data_now', function( e ) {
        e.preventDefault( );

        if ( ! confirm( ddtt_settings.i18n.resetConfirm ) ) {
            return;
        }

        console.log( 'Resetting all plugin data now...' );
        
        var button = $( this );
        var originalText = button.text();
        button.text( ddtt_settings.i18n.resetting );
        button.addClass( 'ddtt-loading-msg ddtt-button-disabled' );

        $.post( ajaxurl, {
            action: 'ddtt_reset_all_plugin_data',
            nonce: ddtt_settings.resetNonce,
        }, function( response ) {
            if ( response.success ) {
                button.text( ddtt_settings.i18n.resetSuccess );
                window.location.reload();
            } else {
                button.text( originalText );
                button.removeClass( 'ddtt-loading-msg ddtt-button-disabled' );
                alert( 'Reset failed: ' + ( response.data || 'Unknown error.' ) );
            }
        } );
    } );

    
    /**
     * User Select Field
     */
    $( '.ddtt-users-field' ).each( function() {
        let container = $( this );
        let fieldId   = container.data( 'field' );
        let hidden    = $( '#' + fieldId );
        let current   = JSON.parse( hidden.val() || '[]' );

        let chipsContainer = container.find( '.ddtt-users-selected' );
        let input          = container.find( '.ddtt-users-input' );
        let dropdown       = container.find( '.ddtt-user-dropdown' );
        
        // If dropdown doesn't exist yet, create it
        if ( ! dropdown.length ) {
            dropdown = $( '<div class="ddtt-user-dropdown" style="display:none;"></div>' );
            container.append( dropdown );
        }

        function renderChips() {
            chipsContainer.empty();
            current.forEach( function( user ) {
                let chip = $( '<span class="ddtt-user-chip">' + user.text + '<span class="ddtt-remove-user">×</span></span>' );
                chip.find( '.ddtt-remove-user' ).on( 'click', function() {
                    current = current.filter( function( u ) {
                        return u.id !== user.id;
                    } );
                    renderChips();
                    hidden.val( JSON.stringify( current.map( u => u.id ) ) );
                } );
                chipsContainer.append( chip );
            } );
        }

        renderChips();

        function searchUsers( term ) {
            if ( term.length < 1 ) {
                dropdown.hide();
                return;
            }

            $.ajax( {
                url      : ajaxurl,
                dataType : 'json',
                data     : {
                    action  : 'ddtt_user_select',
                    nonce   : ddtt_settings.nonce,
                    q       : term,
                    exclude : current.map( u => u.id )
                },
                success: function( data ) {
                    dropdown.empty();
                    if ( data.length === 0 ) {
                        dropdown.hide();
                        return;
                    }

                    data.forEach( function( user ) {
                        let option = $( '<div class="ddtt-user-option">' + user.text + '</div>' );
                        option.on( 'click', function() {
                            current.push( user );
                            renderChips();
                            hidden.val( JSON.stringify( current.map( u => u.id ) ) );
                            input.val( '' );
                            dropdown.hide();
                        } );
                        dropdown.append( option );
                    } );

                    dropdown.show();
                }
            } );
        }

        input.on( 'input', function() {
            searchUsers( $( this ).val() );
        } );

        $( document ).on( 'click', function( e ) {
            if ( ! container.is( e.target ) && container.has( e.target ).length === 0 ) {
                dropdown.hide();
            }
        } );
    } );


} );
