// Helper logs
DevDebugTools.Helpers.log_file_path();
DevDebugTools.Helpers.log_localization( 'ddtt_metadata' );

// Now start jQuery
jQuery( document ).ready( function( $ ) {

    const currentSubsection = ddtt_metadata.subsection;

    /**
     * Put a button into its saving state
     */
    function ddttSetSaving( button ) {
        button.addClass( 'ddtt-saving' ).prop( 'disabled', true ).html( '<span class="ddtt-saving-spinner"></span>' + ddtt_metadata.i18n.saving );
    }

    /**
     * Load metadata based on sidebar inputs
     */
    function ddttLoadMetadata( extra = {} ) {
        const viewerSection = document.getElementById( 'ddtt-metadata-viewer-section' );

        viewerSection.classList.add( 'ddtt-loading' );
        viewerSection.innerHTML = '<span class="ddtt-loading-metadata-msg ddtt-loading-msg">' + ddtt_metadata.i18n.loading + '</span>';

        const types = {};
        $( '#ddtt-metadata-types input[type=checkbox]' ).each( function() {
            const key = this.id.replace( 'ddtt-show-', '' );
            types[ key ] = this.checked;
        } );

        const data = {
            action: 'ddtt_get_metadata',
            nonce: ddtt_metadata.nonce,
            subsection: currentSubsection,
            id: $( '#ddtt-settings-sidebar-section' ).data( 'object-id' ) || 0,
            types: types,
            search: $( '#ddtt-metadata-search' ).val(),
            filter: $( '#ddtt-metadata-filter' ).val()
        };

        // Only add hide_transients if element exists
        const hideTransientsElem = $( '#ddtt-hide-transients' );
        if ( hideTransientsElem.length ) {
            data.hide_transients = hideTransientsElem.is( ':checked' ) ? 1 : 0;
        }

        // Only add hide_ui_settings if element exists
        const hideUiSettingsElem = $( '#ddtt-hide-ui-settings' );
        if ( hideUiSettingsElem.length ) {
            data.hide_ui_settings = hideUiSettingsElem.is( ':checked' ) ? 1 : 0;
        }

        $.post( ajaxurl, data, function( response ) {
            viewerSection.classList.remove( 'ddtt-loading' );
            viewerSection.innerHTML = response;
        } );
    }

    // Listen to `change` for <select> elements
    $( document ).on( 'change', '#ddtt-metadata-types input[type=checkbox]', function() {
        ddttLoadMetadata();
    } );

    // Listen to `input` for <input> and <textarea> elements
    let ddttSearchTimer = null;

    $( document ).on( 'input', '#ddtt-metadata-search, #ddtt-metadata-filter', function() {
        clearTimeout( ddttSearchTimer );
        ddttSearchTimer = setTimeout( function() {
            ddttLoadMetadata();
        }, 2000 );
    } );

    $( document ).on( 'keydown', '#ddtt-metadata-search, #ddtt-metadata-filter', function( e ) {
        if ( e.key === 'Enter' ) {
            e.preventDefault();
            clearTimeout( ddttSearchTimer );
            ddttLoadMetadata();
        }
    } );

    
    /**
     * Toggle visibility of transients and UI settings
     */
    if ( currentSubsection === 'user' || currentSubsection === 'post' ) {
        $( document ).on( 'change', '#ddtt-hide-transients, #ddtt-hide-ui-settings', function() {
            const hideTransients = $( '#ddtt-hide-transients' ).is( ':checked' ) ? 1 : 0;
            const hideUiSettings = $( '#ddtt-hide-ui-settings' ).is( ':checked' ) ? 1 : 0;

            $( '#ddtt-custom-meta-table' )
                .toggleClass( 'ddtt-hide-transients', hideTransients === 1 )
                .toggleClass( 'ddtt-hide-ui-settings', hideUiSettings === 1 );

            $.post( ajaxurl, {
                action: 'ddtt_metadata_table_actions',
                nonce: ddtt_metadata.nonce,
                hide_transients: hideTransients,
                hide_ui_settings: hideUiSettings
            } );
        } );
    }


    /**
     * Add New Button Handlers
     */
    $( document ).on( 'click', '#ddtt-add-new-meta', function( e ) {
        e.preventDefault();

        var tableBody = $( '#ddtt-custom-meta-table tbody' );

        var newRow = $( '<tr class="ddtt-meta-new-row">\
            <td><input type="text" class="ddtt-meta-new-key" placeholder="' + ddtt_metadata.i18n.enterKey + '" style="width:100%;"></td>\
            <td><textarea class="ddtt-meta-new-value" style="width:100%;" placeholder="' + ddtt_metadata.i18n.enterValue + '"></textarea></td>\
            <td>\
                <button class="ddtt-button ddtt-meta-action-button ddtt-save-new" data-action="save-new">' + ddtt_metadata.i18n.save + '</button>\
                <button class="ddtt-button ddtt-meta-action-button ddtt-cancel-new" data-action="cancel-new">' + ddtt_metadata.i18n.cancel + '</button>\
            </td>\
        </tr>' );

        tableBody.prepend( newRow );
    } );

    // Restrict meta key input and convert spaces to underscores
    $( document ).on( 'input', '.ddtt-meta-new-key', function( e ) {
        var $input = $( this );
        // Convert spaces to underscores and allow only a-z, 0-9, _, and -
        var sanitized = $input.val().toLowerCase().replace( /\s+/g, '_' ).replace( /[^a-z0-9_-]/g, '' );
        if ( $input.val() !== sanitized ) {
            $input.val( sanitized );
        }
    } );

    // Cancel new row
    $( document ).on( 'click', '.ddtt-cancel-new', function( e ) {
        e.preventDefault();
        $( this ).closest( 'tr' ).remove();
    } );

    // Save new row
    $( document ).on( 'click', '.ddtt-save-new', function( e ) {
        e.preventDefault();

        var button = $( this );
        var row = button.closest( 'tr' );
        var cancelButton = row.find( '.ddtt-cancel-new' );
        var key = row.find( '.ddtt-meta-new-key' ).val();
        var value = row.find( '.ddtt-meta-new-value' ).val();
        var id = $( '#ddtt-custom-meta-table' ).closest( 'section' ).data( 'object-id' );

        if ( ! key ) {
            alert( ddtt_metadata.i18n.enterKeyAlert );
            return;
        }

        var resetButtons = function() {
            button.removeClass( 'ddtt-saving' ).prop( 'disabled', false ).text( ddtt_metadata.i18n.save );
            cancelButton.prop( 'disabled', false );
        };

        ddttSetSaving( button );
        cancelButton.prop( 'disabled', true );

        $.post( ajaxurl, {
            action: 'ddtt_update_meta_value',
            key: key,
            value: value,
            object_id: id,
            subsection: currentSubsection,
            nonce: ddtt_metadata.nonce
        }, function( response ) {
            if ( response.success ) {
                row.removeClass( 'ddtt-meta-new-row' )
                    .addClass( 'ddtt-meta-key-row' )
                    .addClass( `ddtt-meta-key-${key}` );

                row.html( '<td><span class="ddtt-highlight-variable">' + key + '</span></td>' +
                        '<td>' + response.data.value + '</td>' +
                        '<td>' +
                        '<button class="ddtt-button ddtt-meta-action-button" data-action="edit" data-key="' + key + '">' + ddtt_metadata.i18n.edit + '</button> ' +
                        '<button class="ddtt-button ddtt-meta-action-button" data-action="delete" data-key="' + key + '">' + ddtt_metadata.i18n.delete + '</button>' +
                        '</td>' );
            } else {
                resetButtons();
                alert( response.data ? response.data : ddtt_metadata.i18n.errorSaving );
            }
        } ).fail( function() {
            resetButtons();
            alert( ddtt_metadata.i18n.errorSaving );
        } );
    } );


    /**
     * Edit/Save Button Handler
     */
    var originalValue = '';
    var originalKey = '';
    var originalKeyText = '';
    $( document ).on( 'click', '.ddtt-meta-action-button[data-action="edit"], .ddtt-meta-action-button[data-action="save"]', function( e ) {
        e.preventDefault();

        var button = $( this );
        var row = button.closest( 'tr' );
        var key = button.data( 'key' );
        var keyCell = row.find( 'td' ).eq( 0 );
        var valueCell = row.find( 'td' ).eq( 1 );
        var deleteButton = row.find( '.ddtt-meta-action-button[data-action="delete"]' );
        var id = button.closest( 'section' ).data( 'object-id' );
        var isCustomMeta = row.closest( '#ddtt-custom-meta-table' ).length > 0;

        var exitEditMode = function() {
            originalValue = '';
            originalKey = '';
            originalKeyText = '';
            button.removeClass( 'ddtt-saving' ).prop( 'disabled', false ).data( 'action', 'edit' ).text( ddtt_metadata.i18n.edit );
            deleteButton.prop( 'disabled', false );
        };

        var restoreCells = function() {
            valueCell.html( originalValue );
            if ( isCustomMeta ) {
                keyCell.html( originalKey );
            }
        };

        if ( button.data( 'action' ) === 'edit' ) {
            originalValue = valueCell.html();
            originalKey = keyCell.html();
            originalKeyText = keyCell.text().trim();
        }

        var cellClone = valueCell.clone();
        cellClone.find( '.ddtt-table-code' ).prev( 'br' ).remove();
        cellClone.find( '.ddtt-table-code' ).remove();
        cellClone.find( '.ddtt-meta-value-note' ).remove();
        var currentValue = cellClone.text().trim();

        if ( button.data( 'action' ) === 'edit' ) {
            if ( key === 'user_pass' ) {
                currentValue = '';
            }
            var placeholder = key === 'user_pass' ? ddtt_metadata.i18n.enterPassword : '';
            valueCell.html( '<textarea class="ddtt-meta-edit-field" style="width:100%;" placeholder="' + placeholder + '">' + currentValue + '</textarea>' );

            if ( isCustomMeta ) {
                keyCell.empty().append( $( '<input type="text" class="ddtt-meta-edit-key" style="width:100%;">' ).val( originalKeyText ) );
            }

            button.data( 'action', 'save' ).text( ddtt_metadata.i18n.save );
            deleteButton.prop( 'disabled', true );

        } else {

            var newValue = valueCell.find( 'textarea' ).val();
            var newKey = isCustomMeta ? keyCell.find( '.ddtt-meta-edit-key' ).val().trim() : '';
            var keyChanged = isCustomMeta && newKey !== originalKeyText;
            var valueChanged = ! ( ( newValue === currentValue ) || ( key === 'user_pass' && newValue === '' ) );

            if ( isCustomMeta && ! newKey ) {
                alert( ddtt_metadata.i18n.enterKeyAlert );
                return;
            }

            if ( ! keyChanged && ! valueChanged ) {
                restoreCells();
                exitEditMode();
                return;
            }

            if ( key === 'user_email' && newValue && ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( newValue ) ) {
                alert( ddtt_metadata.i18n.invalidEmail );
                return;
            }

            var postData = {
                action: 'ddtt_update_meta_value',
                key: isCustomMeta ? originalKeyText : key,
                object_id: id,
                subsection: currentSubsection,
                nonce: ddtt_metadata.nonce
            };

            if ( keyChanged ) {
                postData.new_key = newKey;
            }

            if ( valueChanged ) {
                postData.value = newValue;
            }

            ddttSetSaving( button );

            $.post( ajaxurl, postData, function( response ) {
                if ( response.success ) {
                    valueCell.html( response.data.value );

                    if ( isCustomMeta ) {
                        keyCell.empty().append( $( '<span class="ddtt-highlight-variable"></span>' ).text( response.data.key ) );

                        if ( keyChanged ) {
                            row.removeClass( 'ddtt-meta-key-' + originalKeyText ).addClass( 'ddtt-meta-key-' + response.data.key );
                            row.find( '.ddtt-meta-action-button' ).attr( 'data-key', response.data.key ).data( 'key', response.data.key );
                        }
                    }
                } else {
                    restoreCells();
                    var errorMsg = response.data ? response.data : ddtt_metadata.i18n.errorSaving;
                    alert( errorMsg );
                }

                exitEditMode();
            } ).fail( function() {
                restoreCells();
                alert( 'Server Error: The request could not be completed.' );
                exitEditMode();
            } );
        }
    } );


    /**
     * Delete Button Handler
     */
    $( document ).on( 'click', '.ddtt-meta-action-button[data-action="delete"]', function( e ) {
        e.preventDefault();

        var button = $( this );
        var row = button.closest( 'tr' );
        var key = button.data( 'key' );
        var id = button.closest( 'section' ).data( 'object-id' );

        if ( confirm( ddtt_metadata.i18n.confirm + '\n' + key ) ) {
            $.post( ajaxurl, {
                action: 'ddtt_delete_meta_key',
                key: key,
                object_id: id,
                subsection: currentSubsection,
                _wpnonce: ddtt_metadata.nonce
            }, function( response ) {
                if ( response.success ) {
                    row.css( 'background-color', '#ffdddd' ).fadeOut( 'slow', function() {
                        $( this ).remove();
                    } );
                } else {
                    alert( ddtt_metadata.i18n.errorDeleting );
                }
            } );
        }
    } );


    /**
     * Add Role Handler
     */
    if ( currentSubsection === 'user' ) {
        $( document ).on( 'click', '#ddtt-metadata-roles-section .ddtt-meta-action-button[data-action="add"], #ddtt-metadata-roles-section .ddtt-meta-action-button[data-action="remove"]', function( e ) {
            e.preventDefault();

            var button = $( this );
            var row = button.closest( 'tr' );
            var key = button.data( 'key' );
            var section = button.closest( 'section' );
            var object_id = section.data( 'object-id' );
            var activeCell = row.find( 'td' ).eq( 1 );
            var currentAction = button.data( 'action' );
            var originalText = button.text();

            button.prop( 'disabled', true ).html( '<span class="spinner is-active" style="float:none;margin:0;"></span>' );

            $.post( ajaxurl, {
                action: 'ddtt_update_user_role',
                role: key,
                object_id: object_id,
                toggle: currentAction,
                _wpnonce: ddtt_metadata.nonce
            }, function( response ) {
                if ( response.success ) {
                    if ( currentAction === 'add' ) {
                        button.data( 'action', 'remove' ).text( ddtt_metadata.i18n.remove );
                        activeCell.text( ddtt_metadata.i18n.yes );
                        row.removeClass( 'inactive' ).addClass( 'active' );

                        section.find( '.ddtt-meta-action-button[data-action="remove"]' ).prop( 'disabled', false );
                    } else {
                        button.data( 'action', 'add' ).text( ddtt_metadata.i18n.add );
                        activeCell.text( ddtt_metadata.i18n.no );
                        row.removeClass( 'active' ).addClass( 'inactive' );

                        var activeRoles = section.find( 'tr' ).filter( function() {
                            return $( this ).find( 'td' ).eq( 1 ).text() === ddtt_metadata.i18n.yes;
                        } );

                        if ( activeRoles.length === 1 ) {
                            activeRoles.find( '.ddtt-meta-action-button[data-action="remove"]' ).prop( 'disabled', true );
                        }
                    }

                    // Re-check after every update so "cannot remove last role" stays accurate
                    var activeRolesCount = section.find( 'tr' ).filter( function() {
                        return $( this ).find( 'td' ).eq( 1 ).text() === ddtt_metadata.i18n.yes;
                    } ).length;

                    if ( activeRolesCount <= 1 ) {
                        section.find( '.ddtt-meta-action-button[data-action="remove"]' ).prop( 'disabled', true );
                    } else {
                        section.find( '.ddtt-meta-action-button[data-action="remove"]' ).prop( 'disabled', false );
                    }

                    // Update capabilities table if provided
                    if ( response.data && response.data.capabilities ) {
                        var capsSection = $( '#ddtt-metadata-capabilities-section tbody' );
                        if ( capsSection.length && response.data && response.data.capabilities ) {
                            capsSection.find( 'tr' ).each( function() {
                                var row = $( this );
                                var cap = row.find( 'td' ).eq( 0 ).text().trim();
                                var active = response.data.capabilities[ cap ] ? true : false; // true if exists and truthy, false otherwise
                                var cell = row.find( 'td' ).eq( 1 );
                                var button = row.find( '.ddtt-meta-action-button' );

                                if ( active ) {
                                    cell.text( ddtt_metadata.i18n.yes );
                                    row.removeClass( 'inactive' ).addClass( 'active' );
                                    button.data( 'action', 'remove' ).text( ddtt_metadata.i18n.remove );
                                } else {
                                    cell.text( ddtt_metadata.i18n.no );
                                    row.removeClass( 'active' ).addClass( 'inactive' );
                                    button.data( 'action', 'add' ).text( ddtt_metadata.i18n.add );
                                }
                            } );
                        }
                    }

                } else {
                    alert( ddtt_metadata.i18n.errorSaving );
                    button.text( originalText );
                }

                button.prop( 'disabled', false );
            } );
        } );
    }


    /**
     * User Capability Management
     */
    if ( currentSubsection === 'user' ) {
        $( document ).on( 'click', '#ddtt-metadata-capabilities-section .ddtt-meta-action-button[data-action="add"], #ddtt-metadata-capabilities-section .ddtt-meta-action-button[data-action="remove"]', function( e ) {
            e.preventDefault();

            var button = $( this );
            var row = button.closest( 'tr' );
            var key = button.data( 'key' );
            var section = button.closest( 'section' );
            var object_id = section.data( 'object-id' );
            var activeCell = row.find( 'td' ).eq( 1 );

            var currentAction = button.data( 'action' );
            var originalText = button.text();

            button.prop( 'disabled', true ).html( '<span class="spinner is-active" style="float:none;margin:0;"></span>' );

            $.post( ajaxurl, {
                action: 'ddtt_update_user_capability',
                capability: key,
                object_id: object_id,
                toggle: currentAction, // 'add' or 'remove'
                _wpnonce: ddtt_metadata.nonce
            }, function( response ) {
                if ( response.success ) {
                    if ( currentAction === 'add' ) {
                        button.data( 'action', 'remove' ).text( ddtt_metadata.i18n.remove );
                        activeCell.text( ddtt_metadata.i18n.yes );
                        row.removeClass( 'inactive' ).addClass( 'active' );
                    } else {
                        button.data( 'action', 'add' ).text( ddtt_metadata.i18n.add );
                        activeCell.text( ddtt_metadata.i18n.no );
                        row.removeClass( 'active' ).addClass( 'inactive' );
                    }
                } else {
                    alert( ddtt_metadata.i18n.errorSaving );
                    button.text( originalText );
                }
                button.prop( 'disabled', false );
            } );
        } );
    }


    /**
     * Update Terms
     */
    if ( currentSubsection === 'post' ) {
        var originalValue = '';
        $( document ).on( 'click', '#ddtt-metadata-taxonomies-section .ddtt-meta-action-button[data-action="update"], #ddtt-metadata-taxonomies-section .ddtt-meta-action-button[data-action="save"]', function( e ) {
            e.preventDefault();
    
            var button = $( this );
            var row = button.closest( 'tr' );
            var taxonomy = button.data( 'key' );
            var valueCell = row.find( 'td' ).eq( 1 );
            var id = button.closest( 'section' ).data( 'object-id' );
            
            button.prop( 'disabled', true ).html( '<span class="spinner is-active" style="float:none;margin:0;"></span>' );
            
            // Update
            if ( button.data( 'action' ) === 'update' ) {
                originalValue = valueCell.html();

                $.post( ajaxurl, {
                    action: 'ddtt_get_tax_terms_editor',
                    nonce: ddtt_metadata.nonce,
                    taxonomy: taxonomy,
                    object_id: id
                }, function( response ) {
                    valueCell.html( response );

                    valueCell.find( '.tagsdiv' ).each( function() {
                        if ( window.tagBox ) {
                            window.tagBox.init( $( this ) );
                        }
                    } );
                    
                    button.data( 'action', 'save' ).text( ddtt_metadata.i18n.save ).prop( 'disabled', false );
                } );

            // Save
            } else {
                var data = { 
                    action: 'ddtt_update_tax_terms',
                    nonce: ddtt_metadata.nonce,
                    taxonomy: taxonomy, 
                    object_id: id
                };

                // Hierarchical checkboxes
                var checkboxes = valueCell.find( 'input[type="checkbox"]' );
                if ( checkboxes.length ) {
                    data.terms = [];
                    checkboxes.each( function() {
                        if ( $( this ).is( ':checked' ) ) {
                            data.terms.push( $( this ).val() );
                        }
                    } );
                } else {
                    // Non-hierarchical tags
                    var tags = valueCell.find( '.the-tags' ).val();
                    data.terms = tags;
                }

                $.post( ajaxurl, data, function( response ) {
                    if ( response.success ) {
                        valueCell.html( response.data );
                        button.data( 'action', 'update' ).text( ddtt_metadata.i18n.updateTerms ).prop( 'disabled', false );
                    } else {
                        valueCell.html( originalValue );
                        button.data( 'action', 'update' ).text( ddtt_metadata.i18n.updateTerms ).prop( 'disabled', false );
                        alert( response.data );
                    }
                } );
            }
        } );
    }


    /**
     * Reset Confirmation
     */
    // Add this to your scripts.js or relevant JS file
    $( document ).on( 'click', '#ddtt-metadata-actions #ddtt-reset-meta', function ( e ) {
        e.preventDefault();
        if ( ! confirm( ddtt_metadata.i18n.confirmReset ) ) {
            return;
        }

        // Remove #ddtt-metadata-general-section from the URL if present
        if ( window.location.hash === '#ddtt-metadata-general-section' ) {
            history.replaceState( null, '', window.location.pathname + window.location.search );
        }
        
        // Submit the parent form after confirmation
        $( this ).closest( 'form' )[ 0 ].submit();
    } );

} );
