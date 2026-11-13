DevDebugTools.Helpers.log_file_path();
DevDebugTools.Helpers.log_localization( 'ddtt_updates' );

jQuery( function( $ ) {

    var title = $( '.wrap h1' );
    if ( ! title.length ) {
        return;
    }

    var btnPlugins = $( '<button>', {
        id:    'ddtt-update-plugins',
        type:  'button',
        class: 'button button-primary',
        text:  ddtt_updates.i18n.btn_plugins,
    } );

    var btnThemes = $( '<button>', {
        id:    'ddtt-update-themes',
        type:  'button',
        class: 'button button-primary',
        text:  ddtt_updates.i18n.btn_themes,
    } );

    var buttons = $( '<span>', { id: 'ddtt-update-buttons' } ).append( btnPlugins, btnThemes );

    var notice = $( '<span>', {
        id:    'ddtt-force-update-notice',
        class: 'notice inline'
    } );

    title.wrap( '<div id="ddtt-update-wrapper"></div>' );
    title.after( notice ).after( buttons );

    var config = {
        plugins: {
            list:     ddtt_updates.plugins,
            action:   'ddtt_force_check_single_plugin',
            checking: ddtt_updates.i18n.checking_plugins,
            upToDate: ddtt_updates.i18n.plugins_up_to_date,
            params:   function( item ) {
                return { plugin: item.file, slug: item.slug };
            },
        },
        themes: {
            list:     ddtt_updates.themes,
            action:   'ddtt_force_check_single_theme',
            checking: ddtt_updates.i18n.checking_themes,
            upToDate: ddtt_updates.i18n.themes_up_to_date,
            params:   function( item ) {
                return { stylesheet: item.stylesheet };
            },
        },
    };

    function setNotice( status, text, spin ) {
        notice.removeClass( 'notice-info notice-warning notice-success notice-error' ).addClass( 'notice-' + status ).empty();

        if ( spin ) {
            notice.append( $( '<span>', { class: 'dashicons dashicons-update ddtt-rotate' } ) );
        }

        notice.append( document.createTextNode( text ) ).css( 'display', 'inline-flex' );
    }

    function setButtons( disabled ) {
        buttons.find( 'button' ).prop( 'disabled', disabled );
    }

    function submitUpdate( data ) {
        var form = $( '<form>', { method: 'post', action: data.url } );

        form.append( $( '<input>', { type: 'hidden', name: '_wpnonce', value: data.nonce } ) );

        $.each( data.items, function( index, item ) {
            form.append( $( '<input>', { type: 'hidden', name: 'checked[]', value: item } ) );
        } );

        form.appendTo( 'body' ).get( 0 ).submit();
    }

    async function run( type ) {
        var cfg     = config[ type ];
        var total   = cfg.list.length;
        var updates = [];

        setButtons( true );

        for ( var i = 0; i < total; i++ ) {
            var item = cfg.list[ i ];

            setNotice( 'info', cfg.checking.replace( '%1$d', i + 1 ).replace( '%2$d', total ), true );

            try {
                var response = await $.post( ajaxurl, $.extend( { action: cfg.action, nonce: ddtt_updates.nonce }, cfg.params( item ) ) );

                if ( ddtt_updates.test_mode ) {
                    console.log( 'Check: ' + item.name, response );
                }

                if ( response.success && response.data.has_update ) {
                    updates.push( response.data );
                }
            } catch ( error ) {
                if ( ddtt_updates.test_mode ) {
                    console.log( 'Check failed: ' + item.name, error );
                }
            }
        }

        if ( ! updates.length ) {
            setNotice( 'success', cfg.upToDate, false );
            setButtons( false );
            return;
        }

        setNotice( 'warning', ddtt_updates.i18n.updating, true );

        var commit = await $.post( ajaxurl, {
            action  : 'ddtt_commit_update_results',
            type    : type,
            updates : JSON.stringify( updates ),
            nonce   : ddtt_updates.nonce,
        } ).catch( function() {
            return null;
        } );

        if ( ! commit || ! commit.success || ! commit.data.url ) {
            setNotice( 'error', ddtt_updates.i18n.error, false );
            setButtons( false );
            return;
        }

        submitUpdate( commit.data );
    }

    btnPlugins.on( 'click', function() {
        run( 'plugins' );
    } );

    btnThemes.on( 'click', function() {
        run( 'themes' );
    } );

} );