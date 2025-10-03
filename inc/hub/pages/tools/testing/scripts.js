// Helper logs
DevDebugTools.Helpers.log_file_path();
DevDebugTools.Helpers.log_localization( 'ddtt_testing' );

// Now start jQuery
jQuery( document ).ready( function( $ ) {


    /**
     * Line numbers
     */
    var textarea = $( '#ddtt-testing-code' );
    var gutter = $( '.lined-numbers' );

    function updateLineNumbers() {
        var lines = textarea.val().split( '\n' ).length;
        var html = '';
        for ( var i = 1; i <= lines; i++ ) {
            html += i + '<br>';
        }
        gutter.html( html );
    }

    textarea.on( 'scroll', function() {
        gutter.scrollTop( textarea.scrollTop() );
    });

    textarea.on( 'input', updateLineNumbers );

    updateLineNumbers();


    /**
     * Run code test AJAX
     */
    $( '#ddtt-run-code-test' ).on( 'click', function( e ) {
        e.preventDefault();

        var btn = $( this );
        var outputContainer = $( '#ddtt-testing-output' );
        outputContainer.hide().empty();

        var content = $( '#ddtt-testing-code' ).val() || '';

        // Spinner
        btn.prop( 'disabled', true );
        if ( btn.find( '.ddtt-spinner' ).length === 0 ) {
            btn.prepend( '<span class="ddtt-spinner"></span> ' );
        }

        $.ajax( {
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ddtt_run_code_test',
                nonce: ddtt_testing.nonce,
                content: content
            }
        } )
        .done( function( response ) {
            var html = '';

            if ( response.success ) {
                var data = response.data;

                if ( data.errors && data.errors.length > 0 ) {
                    html += '<ul class="ddtt-errors">';
                    for ( var i = 0; i < data.errors.length; i++ ) {
                        var err = data.errors[i];
                        var lineText = err.line ? ' (' + ddtt_testing.i18n.check_line + ' ' + err.line + ')' : '';
                        html += '<li>' + err.message + lineText + '</li>';
                    }
                    html += '</ul>';
                }

                if ( data.output && data.output.length > 0 ) {
                    html += '<ul class="ddtt-output">';
                    for ( var i = 0; i < data.output.length; i++ ) {
                        html += '<li>' + data.output[i] + '</li>';
                    }
                    html += '</ul>';
                }

                if ( ! html ) {
                    html = '<p>' + ddtt_testing.i18n.no_output + '</p>';
                }

                // Update CodeMirror / textarea content
                if ( data.content !== undefined ) {
                    $( '#ddtt-testing-code' ).val( data.content );
                    if ( typeof wp !== 'undefined' && wp.codeEditor ) {
                        $( '#ddtt-testing-code' ).trigger( 'change' );
                    }
                }

            } else {
                html = '<p>' + ( response.data || ddtt_testing.i18n.ajax_error ) + '</p>';
            }

            outputContainer.html( html ).show();

        } )
        .fail( function() {
            outputContainer.html( '<p>' + ddtt_testing.i18n.ajax_error + '</p>' ).show();
        } )
        .always( function() {
            btn.prop( 'disabled', false );
            btn.find( '.ddtt-spinner' ).remove();
        } );
    } );


    /**
     * Run the test on keyboard shortcut
     */
    $( document ).on( 'keydown', function( e ) {
        if ( ( e.ctrlKey || e.metaKey ) && e.key === 'Enter' ) {
            e.preventDefault();
            $( '#ddtt-run-code-test' ).trigger( 'click' );
        }
    } );

} );
