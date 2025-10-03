// Helper logs
DevDebugTools.Helpers.log_file_path();
DevDebugTools.Helpers.log_localization( 'ddtt_plugins' );

// Now start jQuery
jQuery( document ).ready( function( $ ) {

    /**
     * Last Modified Functionality
     */
    // Highlight warning and danger rows and set tooltip
    $( 'span.ddtt-last-modified.ddtt-old, span.ddtt-last-modified.ddtt-very-old' ).each( function() {
        var span    = $( this );
        var row     = span.closest( 'tr' );
        var isDanger = span.hasClass( 'ddtt-very-old' );

        if ( ! row.length ) return;

        // Tooltip message
        var tooltipMsg = isDanger 
            ? ddtt_plugins.i18n.tooltip_updated_danger 
            : ddtt_plugins.i18n.tooltip_updated_warning;

        row.attr( 'data-tooltip', tooltipMsg );

        // Just add the class — CSS handles the fade
        row.addClass( isDanger ? 'ddtt-danger-row' : 'ddtt-warning-row' );
    } );

    // Tooltip follows cursor
    $( 'tr.ddtt-warning-row, tr.ddtt-danger-row' ).each( function() {
        var row = $( this );

        row.on( 'mousemove.tooltip', function( e ) {
            var target = $( e.target );

            // Hide tooltip if hovering over a link
            if ( target.closest( 'a' ).length ) {
                $( '#ddtt-tooltip' ).remove();
                return;
            }

            var message = row.data( 'tooltip' ) || '';
            if ( ! message ) return;

            var tooltip = $( '#ddtt-tooltip' );
            if ( ! tooltip.length ) {
                tooltip = $( '<div id="ddtt-tooltip" style="position:absolute; z-index:9999; padding:6px 10px; background:#333; color:#fff; border-radius:4px; font-size:12px; pointer-events:none;"></div>' );
                tooltip.text( message );
                $( 'body' ).append( tooltip );
            }

            tooltip.css( {
                left: e.pageX + 25 + 'px',
                top: e.pageY - 25 + 'px'
            } );
        } );

        row.on( 'mouseleave.tooltip', function() {
            $( '#ddtt-tooltip' ).remove();
        } );
    } );


    /**
     * Notes functionality
     */
    $( document ).on( 'click', '.ddtt-notes-link', function( e ) {
        e.preventDefault();

        var link    = $( this );
        var plugin  = link.data( 'plugin' );
        var cell    = link.closest( 'tr' ).find( 'td.column-description' );
        var wrapper = cell.find( '.ddtt-notes-wrapper[data-plugin="' + plugin + '"]' );
        var noteEl  = cell.find( '.ddtt-note-display[data-plugin="' + plugin + '"]' );
        var current = noteEl.length ? noteEl.text() : '';

        // Toggle behavior
        if ( wrapper.length ) {
            // Cancel edit: remove textarea wrapper, restore static note
            wrapper.remove();
            if ( noteEl.length ) {
                noteEl.show();
            } else if ( current.length ) {
                var noteDiv = $( '<div class="ddtt-note-display" data-plugin="' + plugin + '" style="margin-top:6px; font-style:italic; color:#444;"></div>' ).text( current );
                cell.append( noteDiv );
            }
            return;
        }

        // Remove static note while editing
        noteEl.hide();

        // Create edit wrapper
        var editWrapper = $( '<div class="ddtt-notes-wrapper" data-plugin="' + plugin + '" style="margin-top:8px;"></div>' );
        var textarea    = $( '<textarea class="ddtt-note-textarea" style="width:100%;min-height:60px;"></textarea>' ).val( current );
        var saveBtn     = $( '<button type="button" class="button ddtt-save-note" data-plugin="' + plugin + '">' + ddtt_plugins.i18n.note_save + '</button>' );

        editWrapper.append( textarea ).append( '<br />' ).append( saveBtn );
        cell.append( editWrapper );
    } );

    // Click Save Note
    $( document ).on( 'click', '.ddtt-save-note', function( e ) {
        e.preventDefault();

        var btn      = $( this );
        var plugin   = btn.data( 'plugin' );
        var wrapper  = btn.closest( '.ddtt-notes-wrapper' );
        var textarea = wrapper.find( '.ddtt-note-textarea' );
        var noteVal  = textarea.val();
        var cell     = btn.closest( 'td.column-description' );
        var link     = cell.find( '.ddtt-notes-link[data-plugin="' + plugin + '"]' );

        btn.prop( 'disabled', true ).text( ddtt_plugins.i18n.note_save + '...' );

        $.post( ajaxurl, {
            action: 'ddtt_save_plugin_note',
            plugin: plugin,
            note: noteVal,
            nonce: ddtt_plugins.nonce
        } )
        .done( function( response ) {
            if ( response.success ) {
                // Remove edit wrapper
                wrapper.remove();

                // Add static note display
                var noteDiv = $( '<div class="ddtt-note-display" data-plugin="' + plugin + '" style="margin-top:6px; font-style:italic; color:#444;"></div>' ).text( noteVal );
                cell.append( noteDiv );

                // Show Edit Note link again
                link.show();
            } else {
                alert( response.data || 'Error saving note' );
                btn.prop( 'disabled', false ).text( ddtt_plugins.i18n.note_save );
            }
        } )
        .fail( function() {
            alert( 'AJAX request failed.' );
            btn.prop( 'disabled', false ).text( ddtt_plugins.i18n.note_save );
        } );
    } );


    /**
     * Installed By Functionality
     */
    if ( ddtt_plugins.is_dev ) {
        $( document ).on( 'click', '.ddtt-edit-installed-by', function() {
            var pencil     = $( this );
            var plugin     = pencil.data( 'plugin' );
            var span       = pencil.siblings( '.ddtt-installed-by' );

            // Prevent multiple inputs
            if ( span.siblings( 'input.ddtt-inline-input' ).length || span.siblings( '.ddtt-save-installed-by' ).length ) {
                return;
            }

            var current    = span.text();
            var input      = $( '<input type="text" class="ddtt-inline-input">' ).val( current );

            span.hide().after( input );
            pencil.hide();

            // Create Save link
            var saveLink   = $( '<a href="#" class="ddtt-save-installed-by" style="margin-left:5px;">💾</a>' );
            input.after( saveLink );

            // Show the duplicate-unknowns link if current value is Unknown
            var duplicateLink = pencil.closest( 'div' ).siblings( '.ddtt-duplicate-unknown-installers' );
            if ( current === ddtt_plugins.i18n.unknown ) {
                duplicateLink.show();
            } else {
                duplicateLink.hide();
            }

            input.focus();

            function saveInstaller( doAll ) {
                var newVal = input.val();

                var data = {
                    action : 'ddtt_update_installer',
                    plugin : plugin,
                    name   : newVal,
                    nonce  : ddtt_plugins.nonce
                };

                if ( doAll ) {
                    data.do_all = true;
                }

                $.post( ajaxurl, data, function( response ) {
                    if ( response.success ) {
                        if ( doAll && Array.isArray( response.data.updated ) ) {
                            // Update all unknown cells in the table
                            response.data.updated.forEach( function(p) {
                                $( '.ddtt-installed-by' ).filter( function() {
                                    return $( this ).siblings( '.ddtt-edit-installed-by' ).data( 'plugin' ) === p;
                                } ).text( newVal );
                            } );
                        } else {
                            span.text( newVal ).show();
                        }
                    } else {
                        alert( response.data || 'Error saving installer' );
                        span.show();
                    }

                    // Clean up inputs and links
                    input.remove();
                    saveLink.remove();
                    pencil.show();
                    duplicateLink.hide();
                } );
            }

            // Save on Enter
            input.on( 'keydown', function( e ) {
                if ( e.which === 13 ) {
                    saveInstaller();
                }
                if ( e.which === 27 ) {
                    input.remove();
                    saveLink.remove();
                    pencil.show();
                    span.show();
                    duplicateLink.hide();
                }
            } );

            // Save on blur
            input.on( 'blur', function() {
                saveInstaller();
            } );

            // Save link click
            saveLink.on( 'click', function( e ) {
                e.preventDefault();
                saveInstaller();
            } );

            // Duplicate unknown installers link
            duplicateLink.off( 'click' ).on( 'click', function( e ) {
                e.preventDefault();
                saveInstaller(true);
            } );
        } );
    }

} );
