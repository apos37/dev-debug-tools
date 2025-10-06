// Helper logs
DevDebugTools.Helpers.log_file_path();
DevDebugTools.Helpers.log_localization( 'ddtt_resources' );

// Now start jQuery
jQuery( document ).ready( function( $ ) {

    ////////////////////////////////// DEV ONLY //////////////////////////////////
    if ( ! ddtt_resources.isDev ) {
        return;
    }


    /**
     * Sortable
     */
    $( '#ddtt-resources-grid' ).sortable( {
        placeholder: 'ddtt-sortable-placeholder',
        cancel: '.ddtt-new-resource',
        update: function( event, ui ) {
            let sortedIDs = $( this ).sortable( 'toArray', { attribute: 'data-index' } );

            $.post( ajaxurl, {
                action: 'ddtt_save_resources',
                resources: sortedIDs,
                _ajax_nonce: ddtt_resources.nonce
            } );
        }
    } );


    /**
     * Add New Resource
     */
    function getResourceFormHTML() {
        return `
            <input type="text" class="ddtt-input-title" placeholder="${ddtt_resources.i18n.titlePlaceholder}" required>
            <input type="url" class="ddtt-input-link" placeholder="${ddtt_resources.i18n.linkPlaceholder}" required>
            <textarea class="ddtt-input-desc" placeholder="${ddtt_resources.i18n.descPlaceholder}" required></textarea>
            <div class="ddtt-button-row">
                <button type="button" class="ddtt-button ddtt-save-resource">${ddtt_resources.i18n.save}</button>
                <button type="button" class="ddtt-button ddtt-cancel-resource">${ddtt_resources.i18n.cancel}</button>
            </div>
        `;
    }

    $( document ).on( 'click', '#ddtt-add-resource', function( e ) {
        e.stopPropagation();
        const $addCard = $( this ).closest( 'li' );
        $addCard.data( 'original', $addCard.html() );
        $addCard.html( getResourceFormHTML() );
    } );

    $( document ).on( 'click', '.ddtt-resource-item.ddtt-new-resource', function( e ) {
        if ( $( e.target ).is( '#ddtt-add-resource, .ddtt-button' ) ) return;
        e.preventDefault();
        $( this ).find( '#ddtt-add-resource' ).trigger( 'click' );
    } );

    /**
     * Cancel Resource Creation
     */
    $( document ).on( 'click', '.ddtt-cancel-resource', function( e ) {
        e.stopPropagation();
        const $card = $( this ).closest( 'li' );
        const original = $card.data( 'original' );
        if ( original ) {
            $card.html( original );
        }
    } );


    /**
     * Save Resource
     */
    function isValidUrl( url ) {
        try {
            new URL( url );
            return true;
        } catch {
            return false;
        }
    }

    $( document ).on( 'click', '.ddtt-save-resource', function( e ) {
        e.preventDefault();

        const $card = $( this ).closest( 'li' );
        const title = $card.find( '.ddtt-input-title' ).val().trim();
        const url = $card.find( '.ddtt-input-link' ).val().trim();
        const desc = $card.find( '.ddtt-input-desc' ).val().trim();

        if ( ! title ) {
            alert( ddtt_resources.i18n.alertTitleRequired );
            $card.find( '.ddtt-input-title' ).focus();
            return;
        }

        if ( ! url ) {
            alert( ddtt_resources.i18n.alertLinkRequired );
            $card.find( '.ddtt-input-link' ).focus();
            return;
        }

        if ( ! isValidUrl( url ) ) {
            alert( ddtt_resources.i18n.alertLinkInvalid );
            $card.find( '.ddtt-input-link' ).focus();
            return;
        }

        if ( ! desc ) {
            alert( ddtt_resources.i18n.alertDescRequired );
            $card.find( '.ddtt-input-desc' ).focus();
            return;
        }

        const key = title.toLowerCase().replace( /[^a-z0-9]+/g, '_' ).replace( /^_|_$/g, '' );

        $.post( ajaxurl, {
            action: 'ddtt_add_resource',
            title,
            url,
            desc,
            key,
            _ajax_nonce: ddtt_resources.nonce
        }, function( response ) {
            if ( response.success && response.data ) {
                const newItem = `
                    <li class="ddtt-resource-item" data-index="${ key }">
                        <a href="${ response.data.url }" target="_blank" rel="noopener noreferrer">
                            ${ response.data.title }
                            <span class="ddtt-external-icon" aria-hidden="true" role="img">&#xf504;</span>
                        </a>
                        <p>${ response.data.desc }</p>
                        <button class="ddtt-delete-resource" data-key="${ key }" aria-label="${ ddtt_resources.i18n.removeResource }" title="${ ddtt_resources.i18n.removeResource }">&minus;</button>
                    </li>`;
                $card.before( newItem );
                $card.html( $card.data( 'original' ) );
            } else {
                alert( response.data || ddtt_resources.i18n.failedToSave );
            }
        } );
    } );


    /**
     * Delete Resource
     */
    $( document ).on( 'click', '.ddtt-delete-resource', function() {
        const $card = $( this ).closest( 'li' );
        const key = $card.data( 'index' );

        if ( ! key ) return;

        if ( ! confirm( ddtt_resources.i18n.deleteConfirm ) ) return;

        $.post( ajaxurl, {
            action: 'ddtt_delete_resource',
            key,
            _ajax_nonce: ddtt_resources.nonce
        }, function( response ) {
            if ( response.success ) {
                $card.remove();
            }
        } );
    } );


    /**
     * Reset Snippets
     */
    $( '.ddtt-reset-resources-link' ).on( 'click', function( e ) {
        e.preventDefault();

        if ( ! confirm( ddtt_resources.i18n.reset_confirm ) ) {
            return;
        }

        window.location.href = $( this ).attr( 'href' );
    } );

} );
