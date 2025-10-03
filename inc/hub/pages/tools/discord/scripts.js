// Helper logs
DevDebugTools.Helpers.log_file_path();
DevDebugTools.Helpers.log_localization( 'ddtt_discord' );

// Now start jQuery
jQuery( document ).ready( function( $ ) {

    const sendButton = $( '#ddtt-send-discord-message' );
    const webhookInput = $( '#ddtt_discord_webhook_url' );
    const colorField = $( '#ddtt_discord_embed_color' );
    const settingsSection = $( '#ddtt-settings-section' );

    let embedColor = colorField.val() || '00ff00';

    // Normalize color
    if ( ! embedColor.startsWith( '#' ) ) {
        embedColor = `#${ embedColor }`;
    }

    // Set initial border with fade
    settingsSection.css( {
        'border-left-color': embedColor,
    } );

    function toggleSendButton() {
        const webhookVal = webhookInput.val().trim();
        sendButton.prop( 'disabled', ! webhookVal.length );
    }

    // Initial check
    toggleSendButton();

    // Watch for webhook input changes
    webhookInput.on( 'input', toggleSendButton );

    // Watch for color field blur
    colorField.on( 'blur', function() {
        let newColor = colorField.val().trim();

        if ( newColor ) {
            if ( ! newColor.startsWith( '#' ) ) {
                newColor = `#${ newColor }`;
            }
            settingsSection.css( 'border-left-color', newColor );
            embedColor = newColor; // update value used when sending
        }
    } );

    // Click handler
    sendButton.on( 'click', function( e ) {
        e.preventDefault();

        const button = $( this );
        const responseDiv = $( '#ddtt-discord-message-response' );

        // Get all values from the form fields
        const dataToSend = {
            action: 'ddtt_send_message',
            nonce: ddtt_discord.nonce,
            webhook: $( '#ddtt_discord_webhook_url' ).val() || '',
            title: $( '#ddtt_discord_embed_title' ).val() || '',
            title_url: $( '#ddtt_discord_title_url' ).val() || '',
            message: $( '#ddtt_discord_message_body' ).val() || '',
            color: embedColor,
            bot_name: $( '#ddtt_discord_bot_name' ).val() || '',
            bot_avatar_url: $( '#ddtt_discord_bot_avatar_url' ).val() || '',
            img_url: $( '#ddtt_discord_image_url' ).val() || '',
            thumbnail_url: $( '#ddtt_discord_thumbnail_url' ).val() || ''
        };

        button.prop( 'disabled', true ).text( ddtt_discord.i18n.sending ).addClass( 'ddtt-loading-msg' );

        $.ajax( {
            url: ajaxurl,
            method: 'POST',
            dataType: 'json',
            data: dataToSend,
            success: function( res ) {
                if ( res.success ) {
                    responseDiv.removeClass( 'ddtt-hidden' ).text( ddtt_discord.i18n.sent ).css( 'color', 'green' );
                } else {
                    responseDiv.removeClass( 'ddtt-hidden' ).text( ddtt_discord.i18n.error + ' ' + ( res.data || '' ) ).css( 'color', 'red' );
                }
            },
            error: function() {
                responseDiv.removeClass( 'ddtt-hidden' ).text( ddtt_discord.i18n.error ).css( 'color', 'red' );
            },
            complete: function() {
                button.prop( 'disabled', false ).text( ddtt_discord.i18n.send ).removeClass( 'ddtt-loading-msg' );
                toggleSendButton();
            }
        } );

    } );

} );