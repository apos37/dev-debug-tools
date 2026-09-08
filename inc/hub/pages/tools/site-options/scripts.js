// Helper logs
DevDebugTools.Helpers.log_file_path();
DevDebugTools.Helpers.log_localization( 'ddtt_site_options' );

// Now start jQuery
jQuery( document ).ready( function( $ ) {

    /**
     * Enabling Edit Mode
     */
    $( '#ddtt_bulk_delete' ).on( 'change', function() {
        const toolSection = $( '#ddtt-tool-section' );
        const table = toolSection.find( '.ddtt-table' );

        if ( $( this ).is( ':checked' ) ) {
            if ( confirm( 'Are you sure you want to enable bulk delete? This can affect site settings.' ) ) {
                if ( ! table.parent().is( 'form#bulk-delete-form' ) ) {
                    const form = $( '<form>', {
                        id: 'bulk-delete-form',
                        method: 'post',
                        action: ''
                    } );

                    const hiddenInput = $( '<input>', {
                        type: 'hidden',
                        name: 'bulk_delete',
                        value: '1'
                    } );
                    const submitButton = $( '<input>', {
                        id: 'ddtt_bulk_delete_submit',
                        type: 'submit',
                        class: 'button button-primary',
                        value: 'Delete Selected Options',
                        disabled: true
                    } );
                    const notice = $( '<p>' ).text( ddtt_site_options.i18n.confirmationNotice );

                    form.append( hiddenInput, submitButton, notice, table );
                    toolSection.append( form );
                }
                toolSection.addClass( 'ddtt-edit-mode' );
            } else {
                $( this ).prop( 'checked', false );
            }
        } else {
            const form = $( '#bulk-delete-form' );
            if ( form.length ) {
                form.before( table );
                form.remove();
            }
            toolSection.removeClass( 'ddtt-edit-mode' );
        }
    } );


    /**
     * Enable the delete button when checkboxes are selected.
     */
    $( '#ddtt-tool-section input[type="checkbox"]' ).on( 'change', function () {
        const row = $( this ).closest( 'tr' );
        row.toggleClass( 'ddtt-row-checked', this.checked );
        const checkedCount = $( '#ddtt-tool-section input[type="checkbox"]:checked' ).length;
        $( '#ddtt_bulk_delete_submit' ).prop( 'disabled', checkedCount === 0 );
    } );


    /**
     * Bulk Delete
     */
    $( document ).on( 'submit', '#bulk-delete-form', function( e ) {
        e.preventDefault();

        const form = this;
        const submitBtn = form.querySelector( 'button[type="submit"], input[type="submit"]' );
        const originalBtnText = submitBtn.tagName.toLowerCase() === 'button' ? submitBtn.textContent : submitBtn.value;

        const selected = Array.from( form.querySelectorAll( 'input[name="ddtt_bulk_delete[]"]:checked' ) );
        if ( selected.length === 0 ) {
            alert( ddtt_site_options.i18n.noneSelected );
            return;
        }

        if ( ! confirm( ddtt_site_options.i18n.confirmDelete ) ) {
            return;
        }

        const options = selected.map( el => el.value );

        submitBtn.disabled = true;
        if ( submitBtn.tagName.toLowerCase() === 'button' ) {
            submitBtn.textContent = ddtt_site_options.i18n.deleting || 'Deleting...';
        } else {
            submitBtn.value = ddtt_site_options.i18n.deleting || 'Deleting...';
        }

        $.post( ajaxurl, {
            action: 'ddtt_bulk_delete',
            nonce: ddtt_site_options.nonce,
            options: options,
        }, function( response ) {
            if ( response.success ) {
                const currentUrl = window.location.href;
                window.location.href = currentUrl;
                window.onload = () => window.scrollTo( 0, 0 );
            } else {
                alert( ddtt_site_options.i18n.error );
                submitBtn.disabled = false;
                if ( submitBtn.tagName.toLowerCase() === 'button' ) {
                    submitBtn.textContent = originalBtnText;
                } else {
                    submitBtn.value = originalBtnText;
                }
            }
        } );
    } );


    /**
     * Scroll to Deleted Options
     */
    $( document ).on( 'click', '#scroll-to-deleted-options', function( e ) {
        e.preventDefault();

        const target = $( '#ddtt_deleted_site_options' );
        if ( target.length ) {
            const offsetTop = target.offset().top - 100;

            $( 'html, body' ).animate({
                scrollTop: offsetTop > 0 ? offsetTop : 0
            }, 400 );

            target.addClass( 'ddtt-highlight' );
        }
    } );


    /**
     * Edit/Save/Cancel Site Option
     */
    var originalOptionValue = '';
    var originalOptionAutoload = '';

    $( document ).on( 'click', '.ddtt-option-action-button[data-action="edit"], .ddtt-option-action-button[data-action="save"]', function( e ) {
        e.preventDefault();

        var button = $( this );
        var row = button.closest( 'tr' );
        var option = button.data( 'option' );
        var valueCell = row.find( '.ddtt-option-value-cell' );
        var autoloadCell = row.find( '.ddtt-option-autoload-cell' );
        var deleteButton = row.find( '.ddtt-option-action-button[data-action="delete"]' );

        if ( button.data( 'action' ) === 'edit' ) {
            originalOptionValue = valueCell.html();
            originalOptionAutoload = autoloadCell.text().trim();

            var currentAutoload = button.data( 'autoload' ) === 'yes' ? 'yes' : 'no';
            var currentValue = valueCell.text().trim();

            autoloadCell.html( '<select class="ddtt-option-edit-autoload">' +
                '<option value="yes"' + ( currentAutoload === 'yes' ? ' selected' : '' ) + '>' + ddtt_site_options.i18n.yes + '</option>' +
                '<option value="no"' + ( currentAutoload === 'no' ? ' selected' : '' ) + '>' + ddtt_site_options.i18n.no + '</option>' +
                '</select>' );
            valueCell.html( '<textarea class="ddtt-option-edit-value" style="width:100%;">' + currentValue + '</textarea>' );

            button.data( 'action', 'save' ).text( ddtt_site_options.i18n.save );
            button.after( ' <button class="ddtt-button ddtt-option-action-button" data-action="cancel">' + ddtt_site_options.i18n.cancel + '</button>' );
            deleteButton.hide();

        } else {
            var newValue = valueCell.find( 'textarea' ).val();
            var newAutoload = autoloadCell.find( 'select' ).val();

            $.post( ajaxurl, {
                action: 'ddtt_update_site_option_value',
                option: option,
                value: newValue,
                autoload: newAutoload,
                nonce: ddtt_site_options.nonce
            }, function( response ) {
                if ( response.success ) {
                    valueCell.html( response.data.value );
                    autoloadCell.text( response.data.autoload === 'yes' ? ddtt_site_options.i18n.yes : ddtt_site_options.i18n.no );
                    button.data( 'autoload', response.data.autoload );
                } else {
                    valueCell.html( originalOptionValue );
                    autoloadCell.text( originalOptionAutoload );
                    alert( ddtt_site_options.i18n.errorSaving );
                }

                button.data( 'action', 'edit' ).text( ddtt_site_options.i18n.edit );
                row.find( '.ddtt-option-action-button[data-action="cancel"]' ).remove();
                deleteButton.show();
            } ).fail( function() {
                valueCell.html( originalOptionValue );
                autoloadCell.text( originalOptionAutoload );
                button.data( 'action', 'edit' ).text( ddtt_site_options.i18n.edit );
                row.find( '.ddtt-option-action-button[data-action="cancel"]' ).remove();
                deleteButton.show();
                alert( 'Server Error: The request could not be completed.' );
            } );
        }
    } );


    /**
     * Cancel Edit Handler
     */
    $( document ).on( 'click', '.ddtt-option-action-button[data-action="cancel"]', function( e ) {
        e.preventDefault();

        var cancelButton = $( this );
        var row = cancelButton.closest( 'tr' );
        var editButton = row.find( '.ddtt-option-action-button[data-action="save"]' );
        var deleteButton = row.find( '.ddtt-option-action-button[data-action="delete"]' );

        row.find( '.ddtt-option-value-cell' ).html( originalOptionValue );
        row.find( '.ddtt-option-autoload-cell' ).text( originalOptionAutoload );

        editButton.data( 'action', 'edit' ).text( ddtt_site_options.i18n.edit );
        deleteButton.show();
        cancelButton.remove();
    } );


    /**
     * Delete Site Option
     */
    $( document ).on( 'click', '.ddtt-option-action-button[data-action="delete"]', function( e ) {
        e.preventDefault();

        var button = $( this );
        var row = button.closest( 'tr' );
        var option = button.data( 'option' );

        if ( ! confirm( ddtt_site_options.i18n.confirmDeleteOption + '\n' + option ) ) {
            return;
        }

        $.post( ajaxurl, {
            action: 'ddtt_delete_site_option',
            option: option,
            nonce: ddtt_site_options.nonce
        }, function( response ) {
            if ( response.success ) {
                row.css( 'background-color', '#ffdddd' ).fadeOut( 'slow', function() {
                    $( this ).remove();
                } );
            } else {
                alert( ddtt_site_options.i18n.errorDeletingOption );
            }
        } ).fail( function() {
            alert( 'Server Error: The request could not be completed.' );
        } );
    } );


    /**
     * Add New Option Button Handler
     */
    $( document ).on( 'click', '#ddtt-add-new-option', function( e ) {
        e.preventDefault();

        var tableBody = $( '.ddtt-all-options .ddtt-table tbody' );

        var newRow = $( '<tr class="ddtt-option-new-row">\
            <td class="ddtt-edit-mode-only"></td>\
            <td><input type="text" class="ddtt-option-new-key" placeholder="' + ddtt_site_options.i18n.enterKey + '" style="width:100%;"></td>\
            <td><select class="ddtt-option-new-autoload">\
                <option value="no">' + ddtt_site_options.i18n.no + '</option>\
                <option value="yes">' + ddtt_site_options.i18n.yes + '</option>\
            </select></td>\
            <td>&mdash;</td>\
            <td><textarea class="ddtt-option-new-value" style="width:100%;" placeholder="' + ddtt_site_options.i18n.enterValue + '"></textarea></td>\
            <td>\
                <button class="ddtt-button ddtt-option-action-button ddtt-save-new-option" data-action="save-new">' + ddtt_site_options.i18n.save + '</button> \
                <button class="ddtt-button ddtt-option-action-button ddtt-cancel-new-option" data-action="cancel-new">' + ddtt_site_options.i18n.cancel + '</button>\
            </td>\
        </tr>' );

        tableBody.prepend( newRow );
    } );

    // Restrict option key input characters
    $( document ).on( 'input', '.ddtt-option-new-key', function() {
        var input = $( this );
        var sanitized = input.val().replace( /\s+/g, '_' ).replace( /[^a-zA-Z0-9_-]/g, '' );
        if ( input.val() !== sanitized ) {
            input.val( sanitized );
        }
    } );

    // Cancel new option row
    $( document ).on( 'click', '.ddtt-cancel-new-option', function( e ) {
        e.preventDefault();
        $( this ).closest( 'tr' ).remove();
    } );

    // Save new option row
    $( document ).on( 'click', '.ddtt-save-new-option', function( e ) {
        e.preventDefault();

        var row = $( this ).closest( 'tr' );
        var option = row.find( '.ddtt-option-new-key' ).val();
        var value = row.find( '.ddtt-option-new-value' ).val();
        var autoload = row.find( '.ddtt-option-new-autoload' ).val();

        if ( ! option ) {
            alert( ddtt_site_options.i18n.enterKey );
            return;
        }

        $.post( ajaxurl, {
            action: 'ddtt_add_site_option',
            option: option,
            value: value,
            autoload: autoload,
            nonce: ddtt_site_options.nonce
        }, function( response ) {
            if ( response.success ) {
                row.removeClass( 'ddtt-option-new-row' );
                row.attr( 'id', response.data.option );
                row.html( '<td class="ddtt-edit-mode-only"><input type="checkbox" name="ddtt_bulk_delete[]" value="' + response.data.option + '"></td>' +
                    '<td><span class="ddtt-highlight-variable">' + response.data.option + '</span></td>' +
                    '<td class="ddtt-option-autoload-cell">' + ( response.data.autoload === 'yes' ? ddtt_site_options.i18n.yes : ddtt_site_options.i18n.no ) + '</td>' +
                    '<td>' + response.data.details + '</td>' +
                    '<td class="ddtt-option-value-cell">' + response.data.value + '</td>' +
                    '<td>' +
                    '<button class="ddtt-button ddtt-option-action-button" data-action="edit" data-option="' + response.data.option + '" data-autoload="' + response.data.autoload + '">' + ddtt_site_options.i18n.edit + '</button> ' +
                    '<button class="ddtt-button ddtt-option-action-button" data-action="delete" data-option="' + response.data.option + '">' + ddtt_site_options.i18n.delete + '</button>' +
                    '</td>' );
            } else {
                var errorMsg = response.data === 'option_exists' ? ddtt_site_options.i18n.optionExists : ddtt_site_options.i18n.errorAdding;
                alert( errorMsg );
            }
        } ).fail( function() {
            alert( 'Server Error: The request could not be completed.' );
        } );
    } );


    /**
     * Site Options Pagination & Search
     */
    var optionsCurrentPage = 1;
    var optionsSearchTerm = $( '#ddtt-options-table' ).data( 'search' ) || '';
    var optionsSearchTimer = null;

    function fetchOptionsPage( page ) {
        var table = $( '#ddtt-options-table' );
        var perPage = $( '#ddtt-records-per-page' ).val() || table.data( 'per-page' );

        $.post( ajaxurl, {
            action: 'ddtt_get_site_options_page',
            page: page,
            search: optionsSearchTerm,
            per_page: perPage,
            nonce: ddtt_site_options.nonce
        }, function( response ) {
            if ( response.success ) {
                table.find( 'tbody' ).html( response.data.rows );
                table.data( 'page', response.data.page );
                optionsCurrentPage = response.data.page;

                $( '#ddtt-options-total-count' ).text( response.data.total );
                renderOptionsPagination( response.data.page, response.data.total_pages );

                var toolSection = $( '#ddtt-tool-section' );
                toolSection.removeClass( 'ddtt-edit-mode' );
                $( '#ddtt_bulk_delete' ).prop( 'checked', false );
                var form = $( '#bulk-delete-form' );
                if ( form.length ) {
                    form.before( table );
                    form.remove();
                }
            }
        } );
    } // End fetchOptionsPage()

    function renderOptionsPagination( page, totalPages ) {
        var container = $( '#ddtt-options-pagination' );
        container.empty();

        if ( totalPages <= 1 ) {
            return;
        }

        var prevBtn = $( '<button class="ddtt-button" id="ddtt-options-prev">' + ddtt_site_options.i18n.prev + '</button>' );
        prevBtn.prop( 'disabled', page <= 1 );

        var nextBtn = $( '<button class="ddtt-button" id="ddtt-options-next">' + ddtt_site_options.i18n.next + '</button>' );
        nextBtn.prop( 'disabled', page >= totalPages );

        var label = $( '<span class="ddtt-pagination-label">' + ddtt_site_options.i18n.pageOf.replace( '%1$s', page ).replace( '%2$s', totalPages ) + '</span>' );

        container.append( prevBtn, label, nextBtn );
    } // End renderOptionsPagination()

    $( document ).on( 'click', '#ddtt-options-prev', function( e ) {
        e.preventDefault();
        fetchOptionsPage( optionsCurrentPage - 1 );
    } );

    $( document ).on( 'click', '#ddtt-options-next', function( e ) {
        e.preventDefault();
        fetchOptionsPage( optionsCurrentPage + 1 );
    } );

    $( document ).on( 'input', '#ddtt-options-search', function() {
        var input = $( this );

        clearTimeout( optionsSearchTimer );
        optionsSearchTimer = setTimeout( function() {
            optionsSearchTerm = input.val();
            fetchOptionsPage( 1 );
        }, 400 );
    } );

    
    /**
     * Render Pagination on Initial Load
     */
    var initialTable = $( '#ddtt-options-table' );
    if ( initialTable.length ) {
        renderOptionsPagination( parseInt( initialTable.data( 'page' ), 10 ), parseInt( initialTable.data( 'total-pages' ), 10 ) );
    }


    /**
     * Collapsible Section Toggle
     */
    $( document ).on( 'click', '.ddtt-collapsible-toggle', function() {
        $( this ).closest( '.ddtt-section-content' ).toggleClass( 'ddtt-collapsed' );
    } );


    /**
     * Records Per Page Change
     */
    $( document ).on( 'change', '#ddtt-records-per-page', function() {
        fetchOptionsPage( 1 );
    } );

} );