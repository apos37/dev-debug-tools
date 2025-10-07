// Helper logs
DevDebugTools.Helpers.log_file_path();
DevDebugTools.Helpers.log_localization( 'ddtt_welcome' );

// Now start jQuery
jQuery( document ).ready( function( $ ) {


    /**
     * User Select Field
     */
    $( '.ddtt-users-field' ).each( function() {
        let container = $( this );
        let fieldId   = container.data( 'field' );
        let hidden    = $( '#' + fieldId );
        let current   = JSON.parse( hidden.val() || '[]' );

        let chipsContainer = container.find( '.ddtt-users-selected' );
        let input          = container.find( '.ddtt-users-input' );
        let dropdown       = container.find( '.ddtt-user-dropdown' );
        
        // If dropdown doesn't exist yet, create it
        if ( ! dropdown.length ) {
            dropdown = $( '<div class="ddtt-user-dropdown" style="display:none;"></div>' );
            container.append( dropdown );
        }

        function renderChips() {
            chipsContainer.empty();
            current.forEach( function( user ) {
                let chip = $( '<span class="ddtt-user-chip">' + user.text + '<span class="ddtt-remove-user">×</span></span>' );
                chip.find( '.ddtt-remove-user' ).on( 'click', function() {
                    current = current.filter( function( u ) {
                        return u.id !== user.id;
                    } );
                    renderChips();
                    hidden.val( JSON.stringify( current ) );
                } );
                chipsContainer.append( chip );
            } );
        }

        renderChips();

        function searchUsers( term ) {
            if ( term.length < 1 ) {
                dropdown.hide();
                return;
            }

            $.ajax( {
                url      : ajaxurl,
                dataType : 'json',
                data     : {
                    action  : 'ddtt_user_select',
                    nonce   : ddtt_welcome.settings_nonce,
                    q       : term,
                    exclude : current.map( u => u.id )
                },
                success: function( data ) {
                    dropdown.empty();
                    if ( data.length === 0 ) {
                        dropdown.hide();
                        return;
                    }

                    data.forEach( function( user ) {
                        let option = $( '<div class="ddtt-user-option">' + user.text + '</div>' );
                        option.on( 'click', function() {
                            current.push( user );
                            renderChips();
                            hidden.val( JSON.stringify( current ) );
                            input.val( '' );
                            dropdown.hide();
                        } );
                        dropdown.append( option );
                    } );

                    dropdown.show();
                }
            } );
        }

        input.on( 'input', function() {
            searchUsers( $( this ).val() );
        } );

        $( document ).on( 'click', function( e ) {
            if ( ! container.is( e.target ) && container.has( e.target ).length === 0 ) {
                dropdown.hide();
            }
        } );
    } );


    /**
     * Toggle dark mode for the welcome screen.
     */
    $( '#ddtt_default_mode' ).on( 'change', function() {
        const newMode = $( this ).val();
        if ( newMode == 'dark' ) {
            $( 'body' ).addClass( 'ddtt-dark-mode' );
        } else {
            $( 'body' ).removeClass( 'ddtt-dark-mode' );
        }
    } );


    /**
     * Ajax for saving settings.
     */
    $( document ).on( 'click', '#ddtt-welcome-complete-button', function( e ) {
        e.preventDefault();

        const $button = $( this );
        const $fields = $( '.ddtt-settings-row .ddtt-settings-field :input' );
        var isDarkMode = false;

        const data = {
            action: 'ddtt_save_welcome_settings',
            nonce: ddtt_welcome.welcome_nonce,
            options: {},
        };

        $fields.each( function() {
            const $input = $( this );
            const name = $input.attr( 'name' );
            if ( ! name ) {
                return;
            }

            if ( name === 'ddtt_default_mode' ) {
                isDarkMode = $input.val() === 'dark';
            }

            // Get the name without the '[]' suffix
            const cleanName = name.endsWith('[]') ? name.slice(0, -2) : name;

            if ( $input.is( ':checkbox' ) ) {
                if ( name.endsWith( '[]' ) ) {
                    if ( ! data.options[ cleanName ] ) {
                        data.options[ cleanName ] = [];
                    }
                    if ( $input.is( ':checked' ) ) {
                        data.options[ cleanName ].push( $input.val() );
                    }
                } else {
                    data.options[ cleanName ] = $input.is( ':checked' );
                }
            } else {
                if ( name.endsWith( '[]' ) ) {
                    if ( ! data.options[ cleanName ] ) {
                        data.options[ cleanName ] = [];
                    }
                    data.options[ cleanName ].push( $input.val() );
                } else {
                    data.options[ cleanName ] = $input.val();
                }
            }
        } );

        $button.prop( 'disabled', true ).text( ddtt_welcome.i18n.saving ).addClass( 'ddtt-loading-msg' );

        $.post( ajaxurl, data )
            .done( function ( response ) {
                if ( response.success ) {

                    const $overlay = $( `
                        <div class="ddtt-overlay${ isDarkMode ? ' ddtt-dark-mode' : '' }">
                            <div class="ddtt-success-screen">
                                <div class="ddtt-checkmark" style="position: relative; width: 60px; height: 60px;">
                                    <svg viewBox="0 0 60 60" style="position: absolute; top: 0; left: 0; z-index: 1;">
                                        <circle class="checkmark-circle" cx="30" cy="30" r="28" fill="none"/>
                                        <path class="checkmark-check" fill="none" stroke="#4caf50" stroke-width="4" stroke-linecap="round" d="M15 30l10 10 20-20"/>
                                    </svg>
                                    <div class="ddtt-bug-container" style="
                                        position: absolute;
                                        top: 0;
                                        left: 0;
                                        width: 60px;
                                        height: 60px;
                                        clip-path: circle(28px at 30px 30px);
                                        z-index: 2;
                                        overflow: hidden;
                                    ">
                                        <img src="${ ddtt_welcome.bug_icon }" alt="Bug" style="
                                            position: absolute;
                                            bottom: -40px;
                                            left: 50%;
                                            transform: translateX(-50%);
                                            width: 40px;
                                            height: 40px;
                                            transition: bottom 0.5s ease, transform 0.2s ease;
                                        ">
                                    </div>
                                </div>
                                <h2>${ ddtt_welcome.i18n.setupComplete }</h2>
                                <div class="ddtt-second-message" style="
                                    margin-top: 10px;
                                    font-size: 16px;
                                    opacity: 0;
                                    min-height: 20px;
                                    white-space: pre;
                                    font-family: monospace;
                                "></div>
                            </div>
                        </div>
                    ` ).appendTo( 'body' );

                    $overlay.hide().fadeIn( 300 );

                    const $check = $overlay.find( '.checkmark-check' );
                    const $bug = $overlay.find( '.ddtt-bug-container img' );
                    const $secondMessage = $overlay.find( '.ddtt-second-message' );

                    // Animate checkmark draw
                    const pathLength = $check[ 0 ].getTotalLength();
                    $check.css({
                        'stroke-dasharray': pathLength,
                        'stroke-dashoffset': pathLength,
                        'transition': 'stroke-dashoffset 0.6s ease'
                    });

                    setTimeout( function ( ) {
                        $check.css( 'stroke-dashoffset', 0 );
                    }, 300 );

                    // Fade out checkmark BEFORE bug appears
                    setTimeout( function ( ) {
                        $check.css({ 'transition': 'opacity 0.3s ease', 'opacity': 0 });
                    }, 1700 );

                    // Animate bug pop up and wiggle
                    setTimeout( function ( ) {
                        $bug.css( 'bottom', '5px' );
                        $bug.css( 'animation', 'bugWiggle 0.6s ease-in-out 4' );

                        // Type second message after bug animation
                        setTimeout( function ( ) {
                            const msg = ddtt_welcome.i18n.setupComplete2;
                            $secondMessage.css( 'opacity', 1 );
                            let index = 0;
                            const typingInterval = setInterval( function ( ) {
                                if ( index < msg.length ) {
                                    $secondMessage.text( msg.substring( 0, index + 1 ) );
                                    index++;
                                } else {
                                    clearInterval( typingInterval );
                                    // Redirect after typing completes
                                    setTimeout( function ( ) {
                                        window.location.href = ddtt_welcome.redirect_url;
                                    }, 2000 );
                                }
                            }, 30 );

                        }, 800 ); // slightly after bug pops
                    }, 1800 ); // bug pops slightly after fade starts

                } else {
                    $button.text( ddtt_welcome.i18n.saveError ).removeClass( 'ddtt-loading-msg' );
                }
            } )
            .fail( function( jqXHR, textStatus, errorThrown ) {
                console.error( 'AJAX error:', {
                    status: textStatus,
                    error: errorThrown,
                    responseText: jqXHR.responseText
                } );
                $button.text( ddtt_welcome.i18n.saveError ).removeClass( 'ddtt-loading-msg' );

                $( `<div class="ddtt-toast ddtt-toast-error' }">${ ddtt_welcome.i18n.saveError } ${ errorThrown }</div>` )
                    .appendTo( 'body' )
                    .hide()
                    .fadeIn( 200 )
                    .delay( 2000 )
                    .fadeOut( 400, function() {
                        // $( this ).remove();
                    } );
            } )
    } );

} );
