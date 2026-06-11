DevDebugTools.Helpers.log_file_path();
DevDebugTools.Helpers.log_localization( 'ddtt_updates' );

jQuery( function( $ ) {

    var title = $( '.wrap h1' );
    if ( ! title.length ) {
        return;
    }

    var btn = $( '<button>', {
        id:    'ddtt-force-update-check',
        class: 'button button-primary',
        text:  ddtt_updates.btn_label,
    } );

    var notice = $( '<span>', {
        id:  'ddtt-force-update-notice',
        class: 'notice inline'
    } );

    title.wrap( '<div id="ddtt-update-wrapper"></div>' );
    title.after( notice ).after( btn );

    btn.on( 'click', function() {
        btn.prop( 'disabled', true );
        notice.html( '' ).removeClass( 'notice-warning notice-success' ).addClass( 'notice-info' ).css( 'display', 'inline-flex' );

        var plugins        = ddtt_updates.plugins;
        var themes         = ddtt_updates.themes;
        var plugin_updates = [];
        var theme_updates  = [];

        async function checkPlugin( index ) {
            if ( index >= plugins.length ) {
                checkTheme( 0 );
                return;
            }

            var plugin = plugins[ index ];
            notice.html( '<span class="dashicons dashicons-update ddtt-rotate"></span>' + ddtt_updates.i18n.checking + ' ' + plugin.name + '...' );

            await $.post( ajaxurl, {
                action : 'ddtt_force_check_single_plugin',
                plugin : plugin.file,
                slug   : plugin.slug,
                nonce  : ddtt_updates.nonce,
            } ).then( function( response ) {
                if ( ddtt_updates.test_mode ) {
                    console.log( 'Plugin check: ' + plugin.name, response );
                    if ( response.data.error ) {
                        console.log( 'Plugin error: ' + plugin.name + ' — ' + response.data.error );
                    }
                }
                if ( response.success && response.data.has_update ) {
                    plugin_updates.push( response.data );
                }
            } );

            checkPlugin( index + 1 );
        }

        async function checkTheme( index ) {
            if ( index >= themes.length ) {
                var parts = [];
                if ( plugin_updates.length > 0 ) {
                    parts.push( plugin_updates.length + ' ' + ddtt_updates.i18n.plugin_updates );
                }
                if ( theme_updates.length > 0 ) {
                    parts.push( theme_updates.length + ' ' + ddtt_updates.i18n.theme_updates );
                }

                var summary = parts.length ? parts.join( ', ' ) : ddtt_updates.i18n.all_up_to_date;
                notice.removeClass( 'notice-info' ).addClass( parts.length ? 'notice-warning' : 'notice-success' );
                notice.html( summary );

                if ( ddtt_updates.test_mode ) {
                    console.log( 'Committing plugin updates:', plugin_updates );
                    console.log( 'Committing theme updates:', theme_updates );
                }

                await $.post( ajaxurl, {
                    action         : 'ddtt_commit_update_results',
                    plugin_updates : JSON.stringify( plugin_updates ),
                    theme_updates  : JSON.stringify( theme_updates ),
                    nonce          : ddtt_updates.nonce,
                } );

                if ( !ddtt_updates.test_mode ) {
                    setTimeout( function() {
                        window.location.reload();
                    }, 3000 );
                }
                return;
            }

            var theme = themes[ index ];
            notice.html( '<span class="dashicons dashicons-update ddtt-rotate"></span>' + ddtt_updates.i18n.checking + ' ' + theme.name + '...' );

            await $.post( ajaxurl, {
                action    : 'ddtt_force_check_single_theme',
                stylesheet: theme.stylesheet,
                nonce     : ddtt_updates.nonce,
            } ).then( function( response ) {
                if ( ddtt_updates.test_mode ) {
                    console.log( 'Theme check: ' + theme.name, response );
                    if ( response.data.error ) {
                        console.log( 'Theme error: ' + theme.name + ' — ' + response.data.error );
                    }
                }
                if ( response.success && response.data.has_update ) {
                    theme_updates.push( response.data );
                }
            } );

            checkTheme( index + 1 );
        }

        checkPlugin( 0 );
    } );

} );