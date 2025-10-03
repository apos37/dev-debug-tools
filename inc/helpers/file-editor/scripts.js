// Helper logs
DevDebugTools.Helpers.log_file_path();
DevDebugTools.Helpers.log_localization( 'ddtt_file_editor' );

// Now start jQuery
jQuery( document ).ready( function( $ ) {

    const shortname = ddtt_file_editor.properties.shortname;

    // Cache selectors
    var snippetBtn           = $( '#ddtt-snippet-mgr-btn' );
    var rawBtn               = $( '#ddtt-raw-editor-btn' );
    var viewBtn              = $( '#ddtt-view-current-btn' );
    var cancelBtn            = $( '#ddtt-cancel-edits-btn' );
    var saveBtn              = $( '#ddtt-save-edits-btn' );
    var previewSnippetsBtn   = $( '#ddtt-preview-snippets-file' );
    var deleteBackupBtn      = $( '#ddtt-delete-backup-btn' );
    var saveCurrentBtn       = $( '#ddtt-save-as-current' );
    var downloadingSection   = $( '.ddtt-sidebar-actions.downloading' );
    var viewBtnSection       = $( '.ddtt-sidebar-actions.view-button' );
    var halfButtonsSections  = $( '.ddtt-sidebar-actions.half-buttons' );
    var editingOnlySection   = $( '.ddtt-sidebar-actions.editing-only' );
    var snippetsOnlySection  = $( '.ddtt-sidebar-actions.snippets-only' );
    var backupsDropDown      = $( '#ddtt-backups' );

    var errorContainer       = $( '#ddtt-editor-errors' );
    var currentView          = $( '#ddtt-current-view' );
    var rawEditorCont        = $( '#ddtt-raw-editor-cont' );
    var rawEditor            = $( '#ddtt-raw-editor' );
    var snippetMgr           = $( '#ddtt-snippet-manager' );
    var previewer            = $( '#ddtt-file-previewer' );
    var fileNotice           = $( '#ddtt-file-notice' );


    /**
     * Syntax Colors
     */
    // Helper to apply colors to viewer and sidebar, and optionally save via AJAX
    function applyFileEditorColors( colors, save = false ) {
        const $body = $( 'body' );
        const isDark = $body.hasClass( 'ddtt-dark-mode' );
        const newMode = isDark ? 'dark' : 'light';

        var $viewer = $( '#ddtt-file-editor-section pre.ddtt-code-block' );

        // Apply colors to viewer
        $viewer.css( 'background-color', colors.background );
        $viewer.css( 'color', colors.text_quotes );

        $viewer.find( 'span.ddtt-comment' ).css( 'color', colors.comments );
        $viewer.find( 'span.ddtt-fx-vars' ).css( 'color', colors.fx_vars );
        $viewer.find( 'span.ddtt-syntax' ).css( 'color', colors.syntax );

        // Update sidebar inputs
        $( '#ddtt-file-editor-color-settings input[name="ddtt_color_background"]' ).val( colors.background );
        $( '#ddtt-file-editor-color-settings input[name="ddtt_color_text_quotes"]' ).val( colors.text_quotes );
        $( '#ddtt-file-editor-color-settings input[name="ddtt_color_comments"]' ).val( colors.comments );
        $( '#ddtt-file-editor-color-settings input[name="ddtt_color_fx_vars"]' ).val( colors.fx_vars );
        $( '#ddtt-file-editor-color-settings input[name="ddtt_color_syntax"]' ).val( colors.syntax );

        // Optionally save via AJAX
        if ( save ) {
            var updatedColors = {};
            updatedColors[ newMode ] = colors;

            $.ajax( {
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'ddtt_' + shortname + '_update_colors',
                    nonce: ddtt_file_editor.nonce,
                    colors: updatedColors
                }
            } );
        }
    }

    // Dark Mode Toggle
    $( '.ddtt-mode-toggle' ).on( 'click', function() {
        const $body = $( 'body' );
        const isDark = $body.hasClass( 'ddtt-dark-mode' );
        const newMode = isDark ? 'dark' : 'light';
        const colors = ddtt_file_editor.colors[ newMode ];

        applyFileEditorColors( colors );
    } );

    // Reset Colors
    $( document ).on( 'click', '#ddtt-reset-colors', function( e ) {
        e.preventDefault();
        if ( ! confirm( ddtt_file_editor.i18n.confirm_reset ) ) {
            return;
        }
        
        const $body = $( 'body' );
        const isDark = $body.hasClass( 'ddtt-dark-mode' );
        const newMode = isDark ? 'dark' : 'light';
        const colors = $.extend( {}, ddtt_file_editor.default_colors[ newMode ] );

        applyFileEditorColors( colors, true );
    } );

    // Color Inputs
    $( document ).on( 'change', '#ddtt-file-editor-color-settings input[type="color"]', function () {
        var colorName = $( this ).attr( 'name' );
        var colorValue = $( this ).val();

        const $body = $( 'body' );
        const isDark = $body.hasClass( 'ddtt-dark-mode' );
        const newMode = isDark ? 'dark' : 'light';
        const colors = ddtt_file_editor.colors[ newMode ];

        switch ( colorName ) {
            case 'ddtt_color_background': colors.background = colorValue; break;
            case 'ddtt_color_text_quotes': colors.text_quotes = colorValue; break;
            case 'ddtt_color_comments': colors.comments = colorValue; break;
            case 'ddtt_color_fx_vars': colors.fx_vars = colorValue; break;
            case 'ddtt_color_syntax': colors.syntax = colorValue; break;
        }

        applyFileEditorColors( colors, true );
    } );


    /**
     * Switch between views
     */
    // Store original raw content for cancel
    var originalRaw = rawEditor.text();

    function switchToView( view ) {
        // Hide all views
        currentView.hide();
        rawEditorCont.hide();
        snippetMgr.hide();
        previewer.hide();

        // Hide all buttons
        downloadingSection.hide();
        viewBtnSection.hide();
        halfButtonsSections.hide();
        editingOnlySection.hide();
        snippetsOnlySection.hide();
        deleteBackupBtn.hide();
        saveCurrentBtn.hide();
        snippetBtn.hide();
        rawBtn.hide();
        previewSnippetsBtn.prop( 'disabled', true );

        // Clear the errors
        errorContainer.hide().empty();

        // Hide notice
        fileNotice.hide().removeClass( 'ddtt-error' ).removeClass( 'ddtt-warning' ).text( '' );

        // Show what we need
        if ( view === 'snippets' ) {
            downloadingSection.show();
            snippetMgr.show();
            viewBtnSection.show();
            snippetsOnlySection.show();
        } else if ( view === 'raw' ) {
            downloadingSection.show();
            rawEditorCont.show();
            rawEditor.focus();
            viewBtnSection.show();
            editingOnlySection.show();
            fileNotice.text( ddtt_file_editor.i18n.editing_raw ).addClass( 'ddtt-error' ).show();
        } else if ( view === 'current' ) {
            downloadingSection.show();
            halfButtonsSections.show();
            currentView.show();
            snippetBtn.show();
            rawBtn.show();
            fileNotice.text( ddtt_file_editor.i18n.viewing_current ).show();
            backupsDropDown.val( '' );
            previewer.empty();
        } else if ( view === 'previewer_backup' ) {
            halfButtonsSections.show();
            viewBtnSection.show();
            deleteBackupBtn.show();
            saveCurrentBtn.show();
            previewer.show();
            fileNotice.text( ddtt_file_editor.i18n.previewing_backup ).addClass( 'ddtt-warning' ).show();
        } else if ( view === 'previewer_snippets' ) {
            halfButtonsSections.show();
            viewBtnSection.show();
            snippetBtn.show();
            saveCurrentBtn.show();
            previewer.show();
            fileNotice.text( ddtt_file_editor.i18n.previewing_snippets ).addClass( 'ddtt-warning' ).show();
        }
    }

    snippetBtn.on( 'click', function( e ) {
        e.preventDefault();
        editing = false;
        switchToView( 'snippets' );
    } );

    rawBtn.on( 'click', function( e ) {
        e.preventDefault();
        editing = true;
        switchToView( 'raw' );
    } );

    viewBtn.on( 'click', function( e ) {
        e.preventDefault();
        editing = false;
        switchToView( 'current' );
        viewBtn.removeData( 'previewing' );
    } );

    cancelBtn.on( 'click', function( e ) {
        e.preventDefault();
        editing = false;
        rawEditor.text( originalRaw );
        errorContainer.hide().empty();
        switchToView( 'current' );
    } );


    /**
     * Save Edits
     */
    saveBtn.on( 'click', function ( e ) {
        e.preventDefault();

        if ( ! confirm( ddtt_file_editor.i18n.confirm_save ) ) {
            return;
        }

        var editedContent = $( '#ddtt-raw-editor' ).html();

        // Convert <div> and <br> to newline
        editedContent = editedContent.replace( /<div\s*\/?>/gi, '\n' );
        editedContent = editedContent.replace( /<br\s*\/?>/gi, '\n' );

        // Remove closing div tags (they already produce newlines)
        editedContent = editedContent.replace( /<\/div>/gi, '' );

        // Decode HTML entities
        editedContent = $( '<textarea>' ).html( editedContent ).text();

        var btn = $( this );

        // Clear previous errors
        errorContainer.hide().empty();

        // Loading
        var originalBtnText = btn.text();
        btn.text( ddtt_file_editor.i18n.saving );
        btn.prop( 'disabled', true );
        if ( btn.find( '.ddtt-spinner' ).length === 0 ) {
            btn.prepend( '<span class="ddtt-spinner"></span> ' );
        }

        $.ajax( {
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ddtt_' + shortname + '_save_edits',
                nonce: ddtt_file_editor.nonce,
                content: editedContent
            }
        } )
        .done( function ( response ) {
            var errors = response.data.errors || [];

            if ( response.success && Array.isArray( errors ) && errors.length === 0 ) {
                btn.text( ddtt_file_editor.i18n.reloading_page );
                DevDebugTools.Helpers.reload_page();
            } else {
                btn.prop( 'disabled', false );
                btn.text( originalBtnText );

                var html = '<ul>';
                for ( var i = 0; i < errors.length; i++ ) {
                    if ( typeof errors[i] === 'string' ) {
                        html += '<li>' + errors[i] + '</li>';
                    } else if ( errors[i].message ) {
                        var lineText = errors[i].line ? ' (' + ddtt_file_editor.i18n.check_line + ' ' + errors[i].line + ')' : '';
                        html += '<li>' + errors[i].message + lineText + '</li>';
                    }
                }
                html += '</ul>';
                errorContainer.html( html ).show();
            }
        } )
        .fail( function ( jqXHR, textStatus, errorThrown ) {
            console.error( 'AJAX request failed:' );
            console.error( 'Status:', textStatus );
            console.error( 'Error thrown:', errorThrown );
            console.error( 'Response text:', jqXHR.responseText );
            alert( ddtt_file_editor.i18n.ajax_error );
        } )
        .always( function () {
            btn.find( '.ddtt-spinner' ).remove();
        } );
    } );


    /**
     * Snippet Manager
     */
    $( document ).on( 'click', '#ddtt-snippet-mgr-btn', function() {
        var enable = false;

        $( '#ddtt-snippet-manager .ddtt-snippet-item' ).each( function() {
            var row = $( this );

            var addCheckbox = row.find( 'input[name="a[]"]' );
            var removeCheckbox = row.find( 'input[name="r[]"]' );
            var updateCheckbox = row.find( 'input[name="u[]"]' );

            if ( ( ! addCheckbox.prop( 'disabled' ) && addCheckbox.is( ':checked' ) ) ||
                ( ! removeCheckbox.prop( 'disabled' ) && removeCheckbox.is( ':checked' ) ) ||
                ( ! updateCheckbox.prop( 'disabled' ) && updateCheckbox.is( ':checked' ) ) ) {
                enable = true;
                return false; // exit .each() early
            }
        } );

        previewSnippetsBtn.prop( 'disabled', ! enable );
    } );


    /**
     * Make the snippet code editable
     */
    $( document ).on( 'change', '.ddtt-update-checkbox', function() {
        var $row = $( this ).closest( 'tr' );
        var editable = $( this ).is( ':checked' );
        var $codes = $row.find( '.ddtt-snippet-code code' );

        $codes.attr( 'contenteditable', editable );

        if ( editable && $codes.length ) {
            $codes.first().focus();
        }
    } );

    // Make code editable on double click
    $( document ).on( 'dblclick', '.snippet-code code', function() {
        var $code = $( this );
        $code.attr( 'contenteditable', true ).focus().css( 'cssText', 'background-color: var(--color-code-block-bg) !important;' );
    } );

    // Revert to uneditable on Enter (ignoring Shift+Enter) or when focus leaves the code box
    $( document ).on( 'keydown focusout', '.snippet-code code', function( e ) {
        if ( ( e.type === 'keydown' && e.key === 'Enter' && ! e.shiftKey ) || e.type === 'focusout' ) {
            if ( e.type === 'keydown' ) {
                e.preventDefault();
            }
            $( this ).attr( 'contenteditable', false ).css( 'cssText', '' );
        }
    } );


    /**
     * Delete Snippets
     */
    $( document ).on( 'click', '.ddtt-delete-snippet', function() {
        const $row = $( this ).closest( 'tr' );
        const key = $( this ).data( 'key' );

        if ( ! key ) return;

        if ( ! confirm( ddtt_file_editor.i18n.delete_confirm ) ) return;

        $.post( ajaxurl, {
            action: 'ddtt_' + shortname + '_delete_snippet',
            key,
            nonce: ddtt_file_editor.nonce
        }, function( response ) {
            if ( response.success ) {
                $row.remove();
            } else {
                console.log( 'Delete snippet failed:', response );
            }
        } ).fail( function( xhr, status, error ) {
            console.log( 'AJAX error:', status, error );
        } );
    } );


    /**
     * Reset Snippets
     */
    $( '.ddtt-reset-snippets-link' ).on( 'click', function( e ) {
        e.preventDefault();

        if ( ! confirm( ddtt_file_editor.i18n.reset_confirm ) ) {
            return;
        }

        window.location.href = $( this ).attr( 'href' );
    } );


    /**
     * Reset Snippet Checkboxes
     */
    function resetSnippetCheckboxes() {
        $( '.ddtt-snippet-item' ).each( function() {
            var row = $( this );

            var addCheckbox = row.find( 'input[name="a[]"]' );
            var removeCheckbox = row.find( 'input[name="r[]"]' );

            if ( ! addCheckbox.prop( 'disabled' ) ) {
                addCheckbox.prop( 'checked', false );
            }

            if ( ! removeCheckbox.prop( 'disabled' ) ) {
                removeCheckbox.prop( 'checked', false );
            }
        } );

        $( '#ddtt-preview-snippets-btn' ).prop( 'disabled', true );
    }


    /**
     * Enable/disable Preview Snippets button based on user-selected checkboxes
     */
    $( document ).on( 'change', '.ddtt-snippet-item input[type="checkbox"]', function() {
        var $row = $( this ).closest( 'tr' );

        // Only allow one checkbox per row to be checked
        if ( this.checked ) {
            $row.find( 'input[type="checkbox"]' ).not( this ).each( function() {
                if ( ! $( this ).prop( 'disabled' ) ) {
                    $( this ).prop( 'checked', false );
                }
            } );
        }

        // Enable or disable the preview button
        var enable = false;
        $( '.ddtt-snippet-item' ).each( function() {
            var row = $( this );

            var addCheckbox = row.find( 'input[name="a[]"]' );
            var removeCheckbox = row.find( 'input[name="r[]"]' );
            var updateCheckbox = row.find( 'input[name="u[]"]' );

            // Check add when it's not disabled
            if ( ! addCheckbox.prop( 'disabled' ) && addCheckbox.is( ':checked' ) ) {
                enable = true;
                return false;
            }

            // Check remove when add is disabled (i.e., detected)
            if ( addCheckbox.prop( 'disabled' ) && removeCheckbox.is( ':checked' ) ) {
                enable = true;
                return false;
            }

            // Update checkbox works as before
            if ( ! updateCheckbox.prop( 'disabled' ) && updateCheckbox.is( ':checked' ) ) {
                enable = true;
                return false;
            }
        } );

        previewSnippetsBtn.prop( 'disabled', ! enable );
    } );


    /**
     * Preview File with Snippets
     */
    $( document ).on( 'click', '#ddtt-preview-snippets-file', function( e ) {
        e.preventDefault();
        editing = false;
        switchToView( 'previewer_snippets' );
        
        const btn = $( this );
        btn.prop( 'disabled', true );
        if ( btn.find( '.ddtt-spinner' ).length === 0 ) {
            btn.prepend( '<span class="ddtt-spinner"></span> ' );
        }
        previewer.text( ddtt_file_editor.i18n.loading_preview );

        // Gather snippet data
        const addSnippets = [];
        const updateSnippets = [];
        const removeSnippets = [];

        $( '#ddtt-snippet-manager .ddtt-snippet-item' ).each( function() {
            const row = $( this );
            const key = row.data( 'index' );
            const code = row.find( '.ddtt-snippet-code code' ).html();

            if ( row.find( 'input[name="a[]"]' ).is( ':checked' ) ) {
                addSnippets.push({ key: key, code: code });
            }
            if ( row.find( 'input[name="u[]"]' ).is( ':checked' ) ) {
                updateSnippets.push({ key: key, code: code });
            }
            if ( row.find( 'input[name="r[]"]' ).is( ':checked' ) ) {
                removeSnippets.push( key );
            }
        } );

        // Clear previous errors
        errorContainer.hide().empty();

        $.post( ajaxurl, {
            action: 'ddtt_' + shortname + '_preview_snippets',
            add: addSnippets,
            update: updateSnippets,
            remove: removeSnippets,
            nonce: ddtt_file_editor.nonce
        }, function( response ) {
            var errors = response.data.errors || [];

            if ( response.success && Array.isArray( errors ) && errors.length === 0 ) {
                previewer.html( response.data.viewer );
                if ( response.data.date ) {
                    $( '.ddtt-file-data-last_modified strong' ).text( response.data.date );
                }
            } else {
                var html = '<ul>';
                for ( var i = 0; i < errors.length; i++ ) {
                    if ( typeof errors[i] === 'string' ) {
                        html += '<li>' + errors[i] + '</li>';
                    } else if ( errors[i].message ) {
                        var lineText = errors[i].line ? ' (' + ddtt_file_editor.i18n.check_line + ' ' + errors[i].line + ')' : '';
                        html += '<li>' + errors[i].message + lineText + '</li>';
                    }
                }
                html += '</ul>';
                errorContainer.html( html ).show();
            }
        } ).fail( function( xhr, status, error ) {
            console.log( 'AJAX error:', status, error );
        } ).always( function() {
            btn.prop( 'disabled', false );
            btn.find( '.ddtt-spinner' ).remove();
            $( 'html, body' ).animate( { scrollTop: $( '#ddtt-file-editor-viewer-section' ).offset().top }, 400 );
            switchToView( 'previewer_snippets' );
            viewBtn.attr( 'data-previewing', 'snippets' );
        } );
    } );


    /**
     * Save Snippets Editor Content
     */
    saveCurrentBtn.on( 'click', function ( e ) {
        e.preventDefault();

        if ( ! confirm( ddtt_file_editor.i18n.confirm_save ) ) {
            return;
        }

        var btn = $( this );

        errorContainer.hide().empty();

        btn.text( ddtt_file_editor.i18n.saving );
        btn.prop( 'disabled', true );
        if ( btn.find( '.ddtt-spinner' ).length === 0 ) {
            btn.prepend( '<span class="ddtt-spinner"></span> ' );
        }

        var data = {
            action: 'ddtt_' + shortname + '_save_edits',
            nonce: ddtt_file_editor.nonce,
        };

        if ( btn.data( 'backup' ) === true ) {
            var selectedBackup = backupsDropDown.val();
            data.use_backup_file = selectedBackup;
        } else {
            data.use_temp_file = true;
        }

        $.ajax( {
            url: ajaxurl,
            type: 'POST',
            data: data
        } )
        .done( function ( response ) {
            var errors = response.data.errors || [];

            if ( response.success && Array.isArray( errors ) && errors.length === 0 ) {
                DevDebugTools.Helpers.reload_page();
            } else {
                var html = '<ul>';
                for ( var i = 0; i < errors.length; i++ ) {
                    if ( typeof errors[i] === 'string' ) {
                        html += '<li>' + errors[i] + '</li>';
                    } else if ( errors[i].message ) {
                        var lineText = errors[i].line ? ' (' + ddtt_file_editor.i18n.check_line + ' ' + errors[i].line + ')' : '';
                        html += '<li>' + errors[i].message + lineText + '</li>';
                    }
                }
                html += '</ul>';
                errorContainer.html( html ).show();
            }
        } )
        .fail( function () {
            alert( ddtt_file_editor.i18n.ajax_error );
        } )
        .always( function () {
            btn.find( '.ddtt-spinner' ).remove();
            btn.text( ddtt_file_editor.i18n.reloading_page );
        } );
    } );


    /**
     * Delete temp preview file and reset checkboxes if 
     */
    $( document ).on( 'click', '#ddtt-view-current-btn', function( e ) {
        var btn = $( this );

        if ( btn.data( 'previewing' ) === 'snippets' ) {
            e.preventDefault();

            $.post( ajaxurl, {
                action: 'ddtt_delete_preview_file',
                nonce: ddtt_file_editor.nonce
            }, function( response ) {
                if ( response.success ) {
                    console.log( 'Temp preview file deleted.' );
                } else {
                    console.log( 'Error deleting temp file:', response );
                }
            } ).fail( function( xhr, status, error ) {
                console.log( 'AJAX error:', status, error );
            } ).done( function() {
                resetSnippetCheckboxes();
            } );
        }
    } );


    /**
     * Backups
     */
    backupsDropDown.on( 'change', function( e ) {
        e.preventDefault();

        const selectField = $( this );
        const backup = selectField.val();
        if ( ! backup ) return;

        if ( $( '#ddtt-backups-loading' ).length === 0 ) {
            selectField.after(
                '<div id="ddtt-backups-loading" class="ddtt-loading">' +
                    '<span class="ddtt-spinner"></span>' +
                    '<span class="ddtt-loading-msg">' + ddtt_file_editor.i18n.loading_preview + '</span>' +
                '</div>'
            );
        }

        $.post( ajaxurl, {
            action: 'ddtt_' + shortname + '_load_previewer',
            filename: backup,
            nonce: ddtt_file_editor.nonce
        }, function( response ) {
            if ( response.success ) {
                previewer.html( response.data.viewer );
                if ( response.data.date ) {
                    $( '.ddtt-file-data-last_modified strong' ).text( response.data.date );
                }
            } else {
                console.log( 'Error loading previewer:', response );
            }
        } ).fail( function( xhr, status, error ) {
            console.log( 'AJAX error:', status, error );
        } ).always( function() {
            $( '#ddtt-backups-loading' ).remove();
            $( 'html, body' ).animate( { scrollTop: $( '#ddtt-file-editor-viewer-section' ).offset().top }, 400 );
            switchToView( 'previewer_backup' );
            saveCurrentBtn.attr( 'data-backup', true );
        } );
    } );


    /**
     * Delete a single backup file
     */
    $( document ).on( 'click', '#ddtt-delete-backup-btn', function( e ) {
        if ( confirm( ddtt_file_editor.i18n.delete_backup_confirm ) ) {
            e.preventDefault();

            var btn = $( this );
            btn.text( ddtt_file_editor.i18n.deleting );
            btn.prop( 'disabled', true );
            if ( btn.find( '.ddtt-spinner' ).length === 0 ) {
                btn.prepend( '<span class="ddtt-spinner"></span> ' );
            }

            var selectedBacktup = backupsDropDown.val();

            $.post( ajaxurl, {
                action: 'ddtt_' + shortname + '_delete_backup_file',
                nonce: ddtt_file_editor.nonce,
                filename: selectedBacktup
            }, function( response ) {
                DevDebugTools.Helpers.reload_page();
            } ).fail( function( xhr, status, error ) {
                console.log( 'AJAX error:', status, error );
            } ).always( function () {
                btn.find( '.ddtt-spinner' ).remove();
                btn.text( ddtt_file_editor.i18n.reloading_page );
            } );
        }
    } );


    /**
     * Delete all backup files except the most recent
     */
    $( document ).on( 'click', '#ddtt-clear-backups', function( e ) {
        if ( confirm( ddtt_file_editor.i18n.delete_backups_confirm ) ) {
            e.preventDefault();

            var link = $( this );
            link.text( ddtt_file_editor.i18n.clearing ).addClass( 'ddtt-loading-msg' );

            $.post( ajaxurl, {
                action: 'ddtt_' + shortname + '_clear_all_backups',
                nonce: ddtt_file_editor.nonce
            }, function( response ) {
                DevDebugTools.Helpers.reload_page();
            } ).fail( function( xhr, status, error ) {
                console.log( 'AJAX error:', status, error );
            } ).always( function () {
                link.text( ddtt_file_editor.i18n.reloading_page );
            } );
        }
    } );


    /**
     * Close all dismissible notices when an action button is clicked
     */
    $( '#ddtt-file-editor-actions .ddtt-button' ).on( 'click', function() {
        $( '#ddtt-header-messages .ddtt-notice.is-dismissible' ).fadeOut( 200, function() {
            $( this ).remove();
        } );
    } );


    /**
     * Warning for htaccess file download
     */
    if ( shortname === 'htaccess' ) {
        $( '#ddtt-download-file' ).on( 'click', function() {
            alert( ddtt_file_editor.i18n.htaccess_warning );
        } );
    }

} );
