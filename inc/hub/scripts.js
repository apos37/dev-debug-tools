// Helper logs
DevDebugTools.Helpers.log_file_path();
DevDebugTools.Helpers.log_localization( 'ddtt_header' );

// Now start jQuery
jQuery( document ).ready( function( $ ) {
    
    /**
     * Toggle test mode
     */
    $( '#ddtt-header-version' ).on( 'click', function( e ) {
        e.preventDefault();

        const $body = $( 'body' );
        let newMode;

        console.clear();

        if ( $body.hasClass( 'ddtt-test-mode' ) ) {
            $body.removeClass( 'ddtt-test-mode' );
            newMode = 0;
            DevDebugTools.Helpers.log_message( 'Test mode is now disabled.' );
        } else {
            $body.addClass( 'ddtt-test-mode' );
            newMode = 1;
            DevDebugTools.Helpers.log_message( 'Test mode is now active!', '', 'danger' );
            DevDebugTools.Helpers.show_logs();
        }

        // Save the new mode to the database
        $.post( ajaxurl, {
            action: 'ddtt_save_test_mode',
            mode: newMode,
            nonce: ddtt_header.nonce_save_mode
        } );
    } );

    
    /**
     * Toggle dark mode for the Dev Debug Tools Hub.
     */
    $( '.ddtt-mode-toggle' ).on( 'click', function() {
        const $body = $( 'body' );
        $body.toggleClass( 'ddtt-dark-mode' );

        const isDark = $body.hasClass( 'ddtt-dark-mode' );
        const newMode = isDark ? 'dark' : 'light';

        $( this ).attr( 'title', isDark ? 'Switch to light mode' : 'Switch to dark mode' );

        $.post( ajaxurl, {
            action: 'ddtt_save_mode',
            mode: newMode,
            nonce: ddtt_header.nonce_save_mode
        } ).done( function( response ) {
            console.log( 'You found our little secret.' );
        } );
    } );


    /**
     * Dismiss the "What's New" section.
     */
    $( '#ddtt-dismiss-whats-new' ).on( 'click', function() {
        $( '#ddtt-whats-new' ).fadeOut( 300, function() {
            $( this ).remove();
        } );

        $.post( ajaxurl, {
            action: 'ddtt_dismiss_whats_new',
            nonce: ddtt_header.nonce_dismiss_whats_new
        } ).done( function( response ) {
            console.log( 'Dismissed "What\'s New" section.' );
        } );
    } );


    /**
     * Scroll to a specific section based on the query parameter.
     */
    function getQueryParam( name ) {
        var params = new URLSearchParams( window.location.search );
        return params.get( name );
    }

    var scrollTo = getQueryParam( 'scroll_to' );
    if ( scrollTo ) {
        var $target = $( '#' + scrollTo );
        if ( $target.length ) {
            $( 'html, body' ).animate( { scrollTop: $target.offset().top }, 600 );
        }
    }


    /**
     * View More / View Less toggle.
     */
    $( document ).on( 'click', '.view-more', function( e ) {
        e.preventDefault();
        $( this ).closest( '.ddtt-value-wrapper' ).find( '.ddtt-value-preview' ).hide();
        $( this ).closest( '.ddtt-value-wrapper' ).find( '.ddtt-value-full' ).show();
    } );

    $( document ).on( 'click', '.view-less', function( e ) {
        e.preventDefault();
        $( this ).closest( '.ddtt-value-wrapper' ).find( '.ddtt-value-full' ).hide();
        $( this ).closest( '.ddtt-value-wrapper' ).find( '.ddtt-value-preview' ).show();
    } );


    /**
     * Checkbox Selection
     */
    $( document ).on( 'change', '#ddtt-hub input[type="checkbox"]:not(.ddtt-toggle-wrapper input[type="checkbox"])', function () {
        const row = $( this ).closest( 'tr' );
        row.toggleClass( 'ddtt-row-checked', this.checked );
    } );


    /**
     * Dismiss Our Notice
     */
    $( document ).on( 'click', '.ddtt-notice .notice-dismiss', function() {
        $( this ).closest( '.ddtt-notice' ).remove();
    } );


    /**
     * Move the notices to the top of the page.
     */
    const notices = $( '.notice, .updated, .error' );
    notices.detach();
    $( '#ddtt-hub' ).before( notices );


    /**
     * Navigation Menu
     */
    $( '#ddtt-nav-dropdown' ).on( 'change', function() {
        let url = $( this ).val();
        if ( ! url ) return;

        if ( ddtt_header.open_nav_new_tab ) {
            window.open( url, '_blank' );
        } else {
            window.location.href = url;
        }
    } );

} );
