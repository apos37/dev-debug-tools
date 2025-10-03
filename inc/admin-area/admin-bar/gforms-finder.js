jQuery( document ).ready( function( $ ) {

    var $found     = $( '#ddtt-gforms-found' );
    var forms      = [];
    var condensed  = ddtt_admin_bar_gforms_finder.condensed;
    var i18n       = ddtt_admin_bar_gforms_finder.i18n;
    var form_url   = ddtt_admin_bar_gforms_finder.form_url;
    var $adminNode = $( '#wp-admin-bar-ddtt-gf-found' );

    $( '.gform_wrapper' ).each( function() {
        var wrapperId = $( this ).attr( 'id' );
        if ( wrapperId && wrapperId.match( /^gform_wrapper_(\d+)$/ ) ) {
            var formId = wrapperId.replace( 'gform_wrapper_', '' );
            if ( forms.indexOf( formId ) === -1 ) {
                forms.push( formId );
            }
        }
    } );

    // ----- MOCK: add second form for testing -----
    // if ( forms.length === 1 ) {
    //     forms.push( '999' ); // mock second form ID
    // }

    $adminNode.find( 'ul.ab-submenu, .ab-sub-wrapper' ).remove();

    // No forms found
    if ( forms.length === 0 ) {
        $found.html( condensed ? '0' : i18n.no_forms );
        $adminNode.removeClass( 'menupop' );
        $adminNode.find( '> .ab-item' ).removeAttr( 'aria-expanded' );

    // One form found
    } else if ( forms.length === 1 ) {
        var url       = form_url.replace( '%d', forms[ 0 ] );
        var labelText = condensed ? i18n.id : i18n.form_id;

        $found.text( labelText + ': ' + forms[ 0 ] );

        $adminNode.find( '> .ab-item' ).attr( 'href', url ).attr( 'target', '_blank' );

        $adminNode.removeClass( 'menupop' );
        $adminNode.find( '> .ab-item' ).removeAttr( 'aria-expanded' );

    // Multiple forms found
    } else {

        var label = condensed ? forms.length : forms.length + ' ' + i18n.forms;
        $found.text( label );

        $adminNode.addClass( 'menupop' );
        $adminNode.find( '> .ab-item' ).attr( 'aria-expanded', 'false' );

        var $submenu = $( '<ul></ul>', {
            'class': 'ab-submenu',
            'role' : 'menu',
            'id'   : 'wp-admin-bar-ddtt-gf-found-default'
        } );

        $adminNode.hover(
            function() {
                $( this ).addClass( 'hover' );
                $( this ).children( '.ab-item' ).attr( 'aria-expanded', 'true' );
            },
            function() {
                $( this ).removeClass( 'hover' );
                $( this ).children( '.ab-item' ).attr( 'aria-expanded', 'false' );
            }
        );

        $.each( forms, function( i, formId ) {
            var url = form_url.replace( '%d', formId );

            var $li = $( '<li></li>', {
                'role' : 'group',
                'id'   : 'wp-admin-bar-ddtt-gf-found-' + formId
            } );

            var $a = $( '<a></a>', {
                'href' : url,
                'target' : '_blank',
                'role' : 'menuitem',
                'class': 'ab-item',
                'html' : i18n.form_id + ': ' + formId
            } );

            $li.append( $a );
            $submenu.append( $li );
        } );

        var $wrapper = $( '<div></div>', { 'class': 'ab-sub-wrapper' } ).append( $submenu );

        $adminNode.append( $wrapper );
    }

} );
