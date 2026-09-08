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


    /**
     * WordPress Playground
     */
    var wpSelect = $( '#ddtt-playground-wp' );
    var wpBetaOption = $( '#ddtt-playground-wp-beta' );
    var phpSelect = $( '#ddtt-playground-php' );
    var networkingSelect = $( '#ddtt-playground-networking' );
    var multisiteSelect = $( '#ddtt-playground-multisite' );
    var cacheBustSelect = $( '#ddtt-playground-cache-bust' );
    var languageField = $( '#ddtt-playground-language' );
    var urlField = $( '#ddtt-playground-url' );
    var goButton = $( '#ddtt-playground-go' );
    var loadingIndicator = $( '#ddtt-playground-loading' );
    var pluginPicker = $( '#ddtt-playground-plugin-picker' );
    var pluginManualField = $( '#ddtt-playground-plugin-manual' );
    var pluginAddButton = $( '#ddtt-playground-plugin-add' );
    var pluginList = $( '#ddtt-playground-plugin-list' );
    var pluginRxButton = $( '#ddtt-playground-add-pluginrx' );

    var randomToken = '';
    var pluginSlugs = [ 'dev-debug-tools' ];

    function sanitize_slug( value ) {
        return value.trim().toLowerCase().replace( /\s+/g, '-' ).replace( /[^a-z0-9-]/g, '' );
    } // End sanitize_slug()

    function sync_plugin_picker_state() {
        pluginPicker.find( 'option' ).each( function () {
            var option = $( this );
            var slug = option.val();

            if ( ! slug ) {
                return;
            }

            option.prop( 'disabled', pluginSlugs.indexOf( slug ) !== -1 );
        } );
    } // End sync_plugin_picker_state()

    function render_plugin_list() {
        pluginList.empty();

        for ( var i = 0; i < pluginSlugs.length; i++ ) {
            var slug = pluginSlugs[i];
            var tag = $( '<li>', { class: 'ddtt-playground-tag' } );
            tag.append( $( '<span>', { text: slug } ) );
            tag.append( $( '<button>', { type: 'button', class: 'ddtt-playground-tag-remove', text: '\u00d7', 'data-slug': slug } ) );
            pluginList.append( tag );
        }

        sync_plugin_picker_state();
    } // End render_plugin_list()

    function add_plugin_slug( slug ) {
        slug = sanitize_slug( slug );

        if ( ! slug || pluginSlugs.indexOf( slug ) !== -1 ) {
            return;
        }

        pluginSlugs.push( slug );
    } // End add_plugin_slug()

    function add_plugin_slugs( rawValue ) {
        var rawSlugs = rawValue.split( ',' );
        var addedAny = false;

        for ( var i = 0; i < rawSlugs.length; i++ ) {
            var slug = sanitize_slug( rawSlugs[i] );

            if ( slug && pluginSlugs.indexOf( slug ) === -1 ) {
                pluginSlugs.push( slug );
                addedAny = true;
            }
        }

        if ( addedAny ) {
            render_plugin_list();
            update_url();
        }
    } // End add_plugin_slugs()

    function remove_plugin_slug( slug ) {
        var index = pluginSlugs.indexOf( slug );

        if ( index !== -1 ) {
            pluginSlugs.splice( index, 1 );
            render_plugin_list();
            update_url();
        }
    } // End remove_plugin_slug()

    function generate_random_token() {
        return Math.random().toString( 36 ).slice( 2, 12 );
    } // End generate_random_token()

    function append_wp_versions( versions ) {
        for ( var i = 0; i < versions.length; i++ ) {
            if ( ! wpSelect.find( 'option[value="' + versions[i] + '"]' ).length ) {
                wpSelect.append( $( '<option>', { value: versions[i], text: versions[i] } ) );
            }
        }
    } // End append_wp_versions()

    function compare_versions( a, b ) {
        var partsA = a.split( '.' ).map( Number );
        var partsB = b.split( '.' ).map( Number );

        for ( var i = 0; i < Math.max( partsA.length, partsB.length ); i++ ) {
            var numA = partsA[i] || 0;
            var numB = partsB[i] || 0;

            if ( numA !== numB ) {
                return numA - numB;
            }
        }

        return 0;
    } // End compare_versions()

    function refresh_php_versions( versions ) {
        var currentSitePhp = ddtt_testing.playground.current_php;
        var allVersions = versions.slice();

        var notSupported = allVersions.indexOf( currentSitePhp ) === -1;
        if ( notSupported ) {
            allVersions.push( currentSitePhp );
        }

        allVersions.sort( function ( a, b ) {
            return compare_versions( b, a );
        } );

        allVersions.unshift( 'latest' );
        allVersions.push( 'next' );

        phpSelect.empty();
        for ( var i = 0; i < allVersions.length; i++ ) {
            var version = allVersions[i];
            var isCurrent = version === currentSitePhp;
            var label = version;

            if ( isCurrent && notSupported ) {
                label = version + ' (current, not supported by Playground)';
            } else if ( isCurrent ) {
                label = version + ' (current)';
            }

            phpSelect.append( $( '<option>', {
                value: version,
                text: label,
                disabled: isCurrent && notSupported
            } ) );
        }

        phpSelect.val( currentSitePhp );

        if ( ! phpSelect.val() ) {
            phpSelect.val( 'latest' );
        }
    } // End refresh_php_versions()

    function fetch_playground_data() {
        loadingIndicator.removeClass( 'ddtt-hidden' );

        $.post( ddtt_testing.playground.ajaxurl, {
            action: 'ddtt_get_playground_data',
            nonce: ddtt_testing.nonce,
        } )
        .done( function ( response ) {
            if ( ! response.success || ! response.data ) {
                return;
            }

            var data = response.data;

            if ( data.wp_versions && data.wp_versions.length ) {
                append_wp_versions( data.wp_versions );
            }

            if ( data.beta_version === 'none' ) {
                wpBetaOption.text( 'beta (none available)' );
            } else if ( data.beta_version ) {
                wpBetaOption.text( 'beta (' + data.beta_version + ')' );
            }

            if ( data.php_versions && data.php_versions.length ) {
                refresh_php_versions( data.php_versions );
                update_url();
            }

            if ( data.author_plugins && data.author_plugins.length ) {
                var existingSlugs = ( pluginRxButton.data( 'slugs' ) + '' ).split( ',' ).filter( Boolean );
                var mergedSlugs = existingSlugs.concat( data.author_plugins.filter( function ( slug ) {
                    return existingSlugs.indexOf( slug ) === -1;
                } ) );

                pluginRxButton.data( 'slugs', mergedSlugs.join( ',' ) );
                pluginRxButton.prop( 'disabled', false );
            }
        } )
        .always( function () {
            loadingIndicator.addClass( 'ddtt-hidden' );
        } );
    } // End fetch_playground_data()

    function toggle_language_field() {
        var networkingOn = networkingSelect.val() === 'yes';
        languageField.prop( 'disabled', ! networkingOn );

        if ( ! networkingOn ) {
            languageField.val( '' );
        }
    } // End toggle_language_field()

    function update_url() {
        var params = {
            wp: wpSelect.val(),
            php: phpSelect.val(),
        };

        if ( networkingSelect.val() === 'no' ) {
            params.networking = 'no';
        }

        if ( multisiteSelect.val() === 'yes' ) {
            params.multisite = 'yes';
        }

        if ( networkingSelect.val() === 'yes' && languageField.val() ) {
            params.language = languageField.val();
        }

        if ( cacheBustSelect.val() === 'yes' ) {
            params.random = randomToken;
        }

        if ( pluginSlugs.length ) {
            params.plugin = pluginSlugs;
        }

        var query = $.param( params, true );
        urlField.val( ddtt_testing.playground.base_url + '?' + query );
    } // End update_url()

    randomToken = generate_random_token();
    render_plugin_list();
    fetch_playground_data();
    toggle_language_field();
    update_url();

    wpSelect.on( 'change', update_url );
    phpSelect.on( 'change', update_url );
    networkingSelect.on( 'change', function () {
        toggle_language_field();
        update_url();
    } );
    multisiteSelect.on( 'change', update_url );
    cacheBustSelect.on( 'change', function () {
        randomToken = generate_random_token();
        update_url();
    } );
    languageField.on( 'input', update_url );

    pluginPicker.on( 'change', function () {
        if ( pluginPicker.val() ) {
            add_plugin_slug( pluginPicker.val() );
            render_plugin_list();
            update_url();
            pluginPicker.val( '' );
        }
    } );

    pluginAddButton.on( 'click', function () {
        add_plugin_slugs( pluginManualField.val() );
        pluginManualField.val( '' );
    } );

    pluginManualField.on( 'keydown', function ( e ) {
        if ( e.key === 'Enter' ) {
            e.preventDefault();
            pluginAddButton.trigger( 'click' );
        }
    } );

    pluginList.on( 'click', '.ddtt-playground-tag-remove', function () {
        remove_plugin_slug( $( this ).data( 'slug' ) );
    } );

    pluginRxButton.on( 'click', function () {
        var slugs = ( $( this ).data( 'slugs' ) + '' );
        add_plugin_slugs( slugs );
    } );

    goButton.on( 'click', function () {
        randomToken = generate_random_token();
        update_url();
        window.open( urlField.val(), '_blank' );
    } );

} );
