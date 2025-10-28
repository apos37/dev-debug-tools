// Helper logs
DevDebugTools.Helpers.log_file_path();

// Now start jQuery
jQuery( document ).ready( function( $ ) {

    var rawEditor = $( '#ddtt-raw-editor' );

    
    /**
     * Magic Cleaner
     */
    $( '#ddtt-magic-cleaner' ).on( 'click', function( e ) {
        e.preventDefault();

        var content = rawEditor.text();
        var rules   = ddtt_file_editor.magic_cleaner_rules;

        // Move old DDTT block
        if ( rules.wpconfig_move_old_ddtt ) {
            var ddttBlockMatch = content.match(
                /\/\*\*[\s\S]*?Added via Developer Debug Tools[\s\S]*?\*\/([\s\S]*?)\/\* End of snippets added via Developer Debug Tools \*\//m
            );

            if ( ddttBlockMatch ) {
                // Extract inner content and trim leading/trailing newlines
                var ddttInner = ddttBlockMatch[1].replace(/^\s*\n/, '').replace(/\n\s*$/, '');

                // Convert all // comments to /** comment */ format, preserving internal newlines
                ddttInner = ddttInner.replace( /^(\s*)\/\/\s*(.+)$/gm, function( m, p1, p2 ) {
                    return p1 + '/** ' + p2.trim() + ' */';
                });

                // Remove the original block
                content = content.replace(ddttBlockMatch[0], '');

                // Insert cleaned block above "That's all, stop editing!" line
                content = content.replace(
                    /(\/\*\s*That's all, stop editing!.*\*\/)/,
                    ddttInner + '\n\n\n$1'
                );
            }
        }

        // Simplify MySQL settings
        if ( rules.wpconfig_simplify_mysql_settings ) {
            // Remove the multi-line base configuration comment
            content = content.replace( /\/\*\*\s*\*\s*The base configurations of the WordPress\.[\s\S]*?\*\//, '' );

            // Remove individual comments above each MySQL define (single-line and multi-line)
            content = content.replace( /\/\/.*MySQL.*$/gm, '' );
            content = content.replace( /\/\*\*.*name of the database.*\*\//i, '' );
            content = content.replace( /(define\s*\(\s*'DB_NAME'.*?define\s*\(\s*'DB_COLLATE'.*?\);)/s, function( match ) {
                return match.replace( /\/\*\*.*?\*\//g, '' );
            } );

            // Remove blank lines left behind
            content = content.replace( /(define\s*\(\s*'DB_NAME'.*?define\s*\(\s*'DB_COLLATE'.*?\);)/s, function( match ) {
                return match.replace( /^\s*\n/gm, '' );
            } );

            // Collapse multiple blank lines anywhere
            content = content.replace( /\n{3,}/g, '\n\n\n' );

            // Insert single-line comment above the first MySQL define
            content = content.replace( /(define\s*\(\s*'DB_NAME'.*?\);)/, function( match ) {
                if ( content.match( /\/\*\* MySQL Database Settings \*\// ) ) {
                    return match;
                }
                return '/** MySQL Database Settings */\n' + match;
            } );
        }


        // Minimize auth comments
        if ( rules.wpconfig_minimize_auth_comments ) {
            content = content.replace(
                /\/\*\*#@\+[\s\S]*?Authentication Unique Keys and Salts\.[\s\S]*?\*\/\n/,
                '/**#@+\n * Authentication Unique Keys and Salts.\n * Regenerate: https://api.wordpress.org/secret-key/1.1/salt/\n */\n'
            );
        }


        // Improve ABSPATH formatting
        if ( rules.wpconfig_improve_abs_path ) {
            content = content.replace(
                /if\s*\(\s*!defined\(\s*'ABSPATH'\s*\)\s*\)\s*define\(\s*'ABSPATH'\s*,\s*dirname\(__FILE__\)\s*\.\s*'\/'\s*\)\s*;/g,
                "if ( !defined( 'ABSPATH' ) ) {\n    define( 'ABSPATH', dirname( __FILE__ ) . '/' );\n}"
            );
        }

        // Remove double line spaces
        if ( rules.wpconfig_remove_double_line_spaces ) {
            content = content.replace( /\n{2,}/g, '\n\n' );
            content = content.replace(
                /(\n*)(\/\*\s*That's all, stop editing!.*\*\/)/,
                '\n\n\n$2'
            );
        }

        // Convert multi-line comments to single-line
        if ( rules.wpconfig_convert_multi_line_to_single_line ) {
            content = content.replace( /\/\*[\s\S]*?\*\/(?=[\r\n])/g, function( match ) {
                var lines = match.split( '\n' );

                // Skip single-line comments
                if ( lines.length <= 2 ) {
                    return match;
                }

                // Skip comments that start with /**#@+
                if ( lines[0].trim().startsWith('/**#@+')) {
                    return match;
                }

                for ( var i = 1; i < lines.length; i++ ) { // skip /** line
                    var line = lines[i].replace( /^\s*\*\s?/, '' ).trim();
                    if ( line.length > 0 && line.indexOf('@') !== 0 ) {
                        return '/** ' + line + ' */';
                    }
                }

                return ''; // remove empty multi-line comments entirely
            } );
        }

        // Add spaces inside parentheses and brackets
        if ( rules.wpconfig_add_spaces_inside_parenthesis_and_brackets ) {
            var placeholders = [];
            content = content.replace( /(["'`])((?:\\\1|.)*?)\1/g, function( match ) {
                placeholders.push( match );
                return '___PLACEHOLDER___';
            } );

            content = content.replace( /\(\s*(?=\S)/g, '( ' );
            content = content.replace( /(?<=\S)\)/g, ' )' );
            content = content.replace( /\[\s*(?=\S)/g, '[ ' );
            content = content.replace( /(?<=\S)\]/g, ' ]' );
            content = content.replace( /___PLACEHOLDER___/g, function() {
                return placeholders.shift();
            } );
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

        // Replace + button row with form fields
        $row.html(
            '<td colspan="6" class="editing">' +
                '<div class="ddtt-new-snippet-inputs">' +
                    '<textarea class="ddtt-new-label" placeholder="' + ddtt_file_editor.i18n.label_placeholder + '"></textarea> ' +
                    '<textarea class="ddtt-new-desc" placeholder="' + ddtt_file_editor.i18n.desc_placeholder + '"></textarea> ' +
                    '<select class="ddtt-new-code-prefix">' +
                        '<option value="define">' + ddtt_file_editor.i18n.prefix_define + '</option>' +
                        '<option value="@ini_set">' + ddtt_file_editor.i18n.prefix_ini_set + '</option>' +
                    '</select>' +
                    '<input type="text" class="ddtt-new-code-variable" placeholder="' + ddtt_file_editor.i18n.variable_placeholder + '">' +
                    '<input type="text" class="ddtt-new-code-value" placeholder="' + ddtt_file_editor.i18n.value_placeholder + '">' +
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
        var prefix = $row.find( '.ddtt-new-code-prefix' ).val();
        var variable = $row.find( '.ddtt-new-code-variable' ).val();
        var value = $row.find( '.ddtt-new-code-value' ).val();

        if ( ! label || ! variable || ! value ) {
            alert( ddtt_file_editor.i18n.error_required );
            return;
        }

        var code = '';
        var normalizedValue;

        if ( typeof value === 'string' ) {
            var lower = value.toLowerCase();
            if ( lower === 'true' ) {
                normalizedValue = true;
            } else if ( lower === 'false' ) {
                normalizedValue = false;
            } else if ( !isNaN( value ) ) {
                normalizedValue = Number( value );
            } else {
                normalizedValue = value; // keep as string
            }
        } else {
            normalizedValue = value;
        }

        // Build formattedValue
        var formattedValue = (typeof normalizedValue === 'boolean' || typeof normalizedValue === 'number')
            ? normalizedValue
            : "'" + normalizedValue + "'";

        // Build code
        if ( prefix === 'define' ) {
            code = "define( '" + variable + "', " + formattedValue + " );";
        } else if ( prefix === '@ini_set' ) {
            code = "@ini_set( '" + variable + "', " + formattedValue + " );";
        }

        var snippet = {
            label: label,
            desc: desc,
            lines: [
                {
                    prefix: prefix,
                    variable: variable,
                    value: value
                }
            ]
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
                                '<code contenteditable="false">' + code + '</code>' +
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
