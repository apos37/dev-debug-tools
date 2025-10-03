// Helper logs
DevDebugTools.Helpers.log_file_path();

// Now start jQuery
jQuery( document ).ready( function( $ ) {

    const shortname = ddtt_file_editor.properties.shortname;
    var rawEditor = $( '#ddtt-raw-editor' );


    /**
     * Magic Cleaner
     */
    $( '#ddtt-magic-cleaner' ).on( 'click', function( e ) {
        e.preventDefault();

        var content = rawEditor.text();
        var rules   = ddtt_file_editor.magic_cleaner_rules;

        // Move top content
        if ( rules.htaccess_move_all_code_at_top ) {
            // Match everything before # BEGIN WordPress
            var topMatch = content.match( /([\s\S]*?)(# BEGIN WordPress)/ );
            if ( topMatch ) {
                var topCode = topMatch[1].replace( /^\s+|\s+$/g, '' );
                if ( topCode ) {
                    // Remove top code
                    content = content.replace( topCode, '' );

                    // Insert after # END WordPress
                    content = content.replace( /(# END WordPress)/, function( match ) {
                        return match + '\n\n' + topCode;
                    });
                }
            }
        }

        // Move old DDTT block
        if ( rules.htaccess_move_old_ddtt ) {
            var ddttBlockMatch = content.match(
                /################ ADDED VIA DEVELOPER DEBUG TOOLS ################[\s\S]*?# Last updated:.*\n([\s\S]*?)################# END OF DEVELOPER DEBUG TOOLS ##################/m
            );

            if ( ddttBlockMatch ) {
                // Extract inner content and trim leading/trailing newlines
                var ddttInner = ddttBlockMatch[1].replace(/^\s*\n/, '').replace(/\n\s*$/, '');

                // Convert all # comments to ## comment format, preserving internal newlines
                ddttInner = ddttInner.replace( /^(\s*)#\s*(.+)$/gm, function( m, p1, p2 ) {
                    return p1 + '## ' + p2.trim();
                });

                // Remove the original block
                content = content.replace(ddttBlockMatch[0], '');

                // Insert cleaned block at the end of the file, separated by blank lines
                content = content.replace(/\s*$/, '');
                content += '\n\n' + ddttInner + '\n';
            }
        }

        // Minimize Begin End Comments
        if ( rules.htaccess_minimize_begin_end_comments ) {
            content = content.replace(
                /(# BEGIN WordPress)([\s\S]*?)(# END WordPress)/m,
                function ( match, begin, middle, end ) {
                    var lines = middle.split(/\r?\n/);

                    // Remove directive comments (those immediately after BEGIN)
                    var cleaned = [];
                    var foundDirectives = false;
                    for ( var i = 0; i < lines.length; i++ ) {
                        var line = lines[i];
                        if (
                            !foundDirectives &&
                            line.trim().startsWith('#') &&
                            (
                                line.includes('The directives') ||
                                line.includes('dynamically generated') ||
                                line.includes('Any changes to the directives')
                            )
                        ) {
                            continue;
                        }
                        if ( line.trim() !== '' && !line.trim().startsWith('#') ) {
                            foundDirectives = true;
                        }
                        if ( foundDirectives || !line.trim().startsWith('#') ) {
                            cleaned.push(line);
                        }
                    }
                    // Remove blank lines at the start and end before # END WordPress
                    while ( cleaned.length && cleaned[0].trim() === '' ) {
                        cleaned.shift();
                    }
                    while ( cleaned.length && cleaned[cleaned.length - 1].trim() === '' ) {
                        cleaned.pop();
                    }
                    return begin + '\n' + cleaned.join('\n') + '\n' + end;
                }
            );
        }

        // Remove double comment hashes
        if ( rules.htaccess_remove_double_comment_hashes ) {
            content = content.replace( /##+/g, '#' );
        }

        // Remove double line spaces
        if ( rules.htaccess_remove_double_line_spaces ) {
            content = content.replace( /\n{2,}/g, '\n\n' );
        }

        // Remove spaces from the top and bottom of the file
        if ( rules.htaccess_remove_spaces_at_top_and_bottom ) {
            content = content.replace( /^\s+|\s+$/g, '' );
        }

        // Add a single line break above comments that do not have another comment above
        if ( rules.htaccess_add_line_breaks_between_blocks ) {
            var lines = content.split( /\r?\n/ );
            var newLines = [];
            for ( var i = 0; i < lines.length; i++ ) {
                var line = lines[i];
                var trimLine = line.trim();

                // Skip inserting above # END comments
                if ( trimLine.startsWith( '#' ) && !trimLine.startsWith( '# END' ) ) {
                    var prevLine = newLines.length > 0 ? newLines[ newLines.length - 1 ] : '';
                    var prevTrim = prevLine.trim();

                    // Insert blank line if previous is not blank and either not a comment or is a # END comment
                    if ( prevTrim !== '' && ( !prevTrim.startsWith( '#' ) || prevTrim.startsWith( '# END' ) ) ) {
                        newLines.push( '' );
                    }
                }

                // Always push the current line as-is
                newLines.push( line );
            }

            content = newLines.join( '\n' );
        }


        // Update editor content
        rawEditor.text( content );
    } );


    /**
     * New Snippets
     */
    var ddttOriginalSnippetRowHtml = '';

    // Add new snippet
    $( document ).on( 'click', '#ddtt-add-snippet', function( e ) {
        e.preventDefault();

        var $row = $( this ).closest( 'tr' );
        ddttOriginalSnippetRowHtml = $row.html();

        // Replace + button row with form fields (single textarea for code lines)
        $row.html(
            '<td colspan="6" class="editing">' +
                '<div class="ddtt-new-snippet-inputs">' +
                    '<textarea class="ddtt-new-label" placeholder="' + ddtt_file_editor.i18n.label_placeholder + '"></textarea> ' +
                    '<textarea class="ddtt-new-desc" placeholder="' + ddtt_file_editor.i18n.desc_placeholder + '"></textarea> ' +
                    '<textarea class="ddtt-new-code-lines" placeholder="Enter each .htaccess line on a new line"></textarea>' +
                '</div>' +
                '<div class="ddtt-new-snippet-buttons">' +
                    '<button type="button" class="ddtt-button ddtt-submit-new">' + ddtt_file_editor.i18n.btn_add_snippet + '</button> ' +
                    '<button type="button" class="ddtt-button ddtt-cancel-new">' + ddtt_file_editor.i18n.btn_cancel + '</button>' +
                '</div>' +
            '</td>'
        );
    } );

    // Cancel new snippet
    $( document ).on( 'click', '.ddtt-cancel-new', function() {
        var $row = $( this ).closest( 'tr' );
        $row.html( ddttOriginalSnippetRowHtml );
    } );

    // Submit new snippet
    $( document ).on( 'click', '.ddtt-submit-new', function( e ) {
        e.preventDefault();

        var $row = $( this ).closest( 'tr' );
        var label = $row.find( '.ddtt-new-label' ).val();
        var desc = $row.find( '.ddtt-new-desc' ).val();
        var codeLinesRaw = $row.find( '.ddtt-new-code-lines' ).val();

        if ( ! label || ! codeLinesRaw ) {
            alert( ddtt_file_editor.i18n.error_required );
            return;
        }

        // Split code lines by new line and trim each line
        var codeLines = codeLinesRaw.split( /\r?\n/ ).map( function( line ) {
            return line.trim();
        }).filter( function( line ) {
            return line.length > 0;
        });

        var snippet = {
            label: label,
            desc: desc,
            lines: codeLines
        };

        $.post(
            ajaxurl,
            {
                action: 'ddtt_' + shortname + '_add_snippet',
                nonce: ddtt_file_editor.nonce,
                snippet: snippet
            },
            function( response ) {
                if ( response.success ) {
                    var key = response.data.key;
                    var snippetExists = response.data.exists;

                    var firstTdContent = '';
                    var addCheckboxAttr = '';
                    var removeCheckboxAttr = '';
                    var updateCheckboxAttr = '';

                    if ( snippetExists ) {
                        firstTdContent = '<span class="ddtt-snippet-detected">' + ddtt_file_editor.i18n.detected + '</span>';
                        addCheckboxAttr = ' disabled';
                    } else {
                        removeCheckboxAttr = ' disabled';
                        updateCheckboxAttr = ' disabled';
                    }

                    var codeHtml = codeLines.map(function(line){
                        return $('<div>').text(line).html();
                    }).join('<br>');
                    codeHtml = '<code contenteditable="false">' + codeHtml + '</code>';

                    var newRow = '<tr class="ddtt-snippet-item" data-index="' + key + '" data-detected="' + ( snippetExists ? 'true' : 'false' ) + '">' +
                        '<td>' + firstTdContent + '</td>' +
                        '<td><input type="checkbox" name="a[]" value="' + key + '"' + addCheckboxAttr + '></td>' +
                        '<td><input type="checkbox" name="r[]" value="' + key + '"' + removeCheckboxAttr + '></td>' +
                        '<td><input type="checkbox" class="ddtt-update-checkbox" name="u[]" value="' + key + '"' + updateCheckboxAttr + '></td>' +
                        '<td class="ddtt-has-help-dialog">' +
                            '<div class="ddtt-help-description">' + label + '</div>' +
                            '<a href="#" class="ddtt-help-toggle" aria-controls="ddtt-help-' + key + '" aria-expanded="false">' + ddtt_file_editor.i18n.learn_more + '</a>' +
                            '<div id="ddtt-help-' + key + '" class="ddtt-help-content" hidden>' +
                                '<button type="button" class="ddtt-help-close">×</button>' +
                                '<div class="ddtt-help-body">' + desc + '<p class="added-by">' + ddtt_file_editor.i18n.added_by + ' ' + response.data.added_by + '</p></div>' +
                            '</div>' +
                        '</td>' +
                        '<td class="snippet-code" data-key="' + key + '">' +
                            '<div class="ddtt-snippet-code">' +
                                codeHtml +
                                '<button type="button" class="ddtt-delete-snippet" data-key="' + key + '" aria-label="' + ddtt_file_editor.i18n.remove_snippet + '" title="' + ddtt_file_editor.i18n.remove_snippet + '">−</button>' +
                            '</div>' +
                        '</td>' +
                    '</tr>';

                    $( newRow ).insertBefore( $row );

                    $row.html(
                        '<td colspan="6"><button type="button" class="button" id="ddtt-add-snippet" aria-label="' + ddtt_file_editor.i18n.btn_add_snippet + '"></button></td>'
                    );
                } else {
                    alert( response.data || ddtt_file_editor.i18n.error_save );
                }
            }
        );
    } );

} );
