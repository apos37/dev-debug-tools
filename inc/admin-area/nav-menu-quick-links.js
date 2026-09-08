( function ( jq ) {
    jq( function () {
        jq( '.menu-item-title' ).each( function () {
            var titleEl = jq( this );
            var listItem = titleEl.closest( 'li.menu-item' );
            var itemId = listItem.attr( 'id' );

            if ( ! itemId ) {
                return;
            }

            var menuItemId = itemId.replace( 'menu-item-', '' );
            var url = ddtt_nav_menu_quick_links.quick_link_url.replace( '%d', menuItemId );

            var link = jq( '<a>', {
                href: url,
                target: '_blank',
                class: 'ddtt-nav-menu-quick-link',
                html: ddtt_nav_menu_quick_links.quick_link_icon
            } );

            titleEl.after( ' ', link );
        } );
    } );
} )( jQuery );