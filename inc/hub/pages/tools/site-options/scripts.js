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
                    // Create form
                    const form = $( '<form>', {
                        id: 'bulk-delete-form',
                        method: 'post',
                        action: ''
                    } );

                    // Create inputs and notice
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

                    // Build form
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
                form.before( table ); // move table back
                form.remove(); // remove form
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

        // Indicate loading
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


} );
