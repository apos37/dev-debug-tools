// Helper logs
DevDebugTools.Helpers.log_file_path();
DevDebugTools.Helpers.log_localization( 'ddtt_seo' );

// Now start jQuery
jQuery( document ).ready( function( $ ) {

    const currentSubsection = ddtt_seo.subsection;
    let ddttSeoSearchTimer = null;


    /**
     * Load the view
     */
    function ddttLoadSeoView( page = 1, refresh = false ) {
        const viewerSection = document.getElementById( 'ddtt-seo-viewer-section' );
        const refreshIcon = $( '.ddtt-rerender-content .dashicons-update' );
        const sitemapSelect = $( '#ddtt-seo-sitemap-select' );
        const llmsSelect = $( '#ddtt-seo-llms-select' );

        viewerSection.classList.add( 'ddtt-loading' );
        viewerSection.innerHTML = '<span class="ddtt-loading-msg">' + ddtt_seo.i18n.loading + '</span>';

        if ( refresh ) {
            refreshIcon.addClass( 'ddtt-rotate' );
        }

        if ( sitemapSelect.length ) {
            $( '#ddtt-seo-download-sitemap' ).val( sitemapSelect.val() );
        }

        if ( llmsSelect.length ) {
            $( '#ddtt-seo-download-llms' ).val( llmsSelect.val() );
        }

        const data = {
            action: 'ddtt_seo_get_view',
            nonce: ddtt_seo.nonce,
            subsection: currentSubsection,
            type: $( '#ddtt-seo-viewer-type' ).val(),
            sitemap: sitemapSelect.length ? sitemapSelect.val() : '',
            llms: llmsSelect.length ? llmsSelect.val() : '',
            per_page: $( '#ddtt-seo-per-page' ).val(),
            search: $( '#ddtt-seo-search' ).val(),
            page: page,
            refresh: refresh ? 1 : 0
        };

        $.post( ajaxurl, data, function( response ) {
            viewerSection.classList.remove( 'ddtt-loading' );
            refreshIcon.removeClass( 'ddtt-rotate' );

            if (
                ( typeof response === 'string' && ( response.toLowerCase().indexOf( 'nonce' ) !== -1 || response.toLowerCase().indexOf( 'expired' ) !== -1 ) ) ||
                ( typeof response === 'object' && response.success === false )
            ) {
                $( '.ddtt-rerender-content' ).hide();
                viewerSection.innerHTML = '<div class="ddtt-notice ddtt-notice-error">' + ddtt_seo.i18n.nonceExpiredMsg + '</div>';
                return;
            }

            viewerSection.innerHTML = response;
        } ).fail( function() {
            viewerSection.classList.remove( 'ddtt-loading' );
            refreshIcon.removeClass( 'ddtt-rotate' );
            viewerSection.innerHTML = '<div class="ddtt-notice ddtt-notice-error">' + ddtt_seo.i18n.error + '</div>';
        } );
    }

    $( document ).on( 'change', '#ddtt-seo-viewer-type, #ddtt-seo-sitemap-select, #ddtt-seo-llms-select, #ddtt-seo-per-page', function() {
        ddttLoadSeoView();
    } );

    $( document ).on( 'input', '#ddtt-seo-search', function() {
        clearTimeout( ddttSeoSearchTimer );
        ddttSeoSearchTimer = setTimeout( function() {
            ddttLoadSeoView();
        }, 1000 );
    } );

    $( document ).on( 'keydown', '#ddtt-seo-search', function( e ) {
        if ( e.key === 'Enter' ) {
            e.preventDefault();
            clearTimeout( ddttSeoSearchTimer );
            ddttLoadSeoView();
        }
    } );

    $( document ).on( 'click', '.ddtt-rerender-content', function( e ) {
        e.preventDefault();
        ddttLoadSeoView( 1, true );
    } );

    $( document ).on( 'click', '.ddtt-seo-page', function( e ) {
        e.preventDefault();
        ddttLoadSeoView( parseInt( $( this ).data( 'page' ), 10 ) || 1 );
    } );

    $( document ).on( 'click', '.ddtt-seo-open-child', function( e ) {
        e.preventDefault();
        const childUrl = $( this ).data( 'url' );
        const sitemapSelect = $( '#ddtt-seo-sitemap-select' );

        if ( sitemapSelect.find( 'option[value="' + childUrl + '"]' ).length ) {
            sitemapSelect.val( childUrl );
            $( '#ddtt-seo-search' ).val( '' );
            ddttLoadSeoView();
        }
    } );

} );