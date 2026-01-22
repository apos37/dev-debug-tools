// Helper logs
DevDebugTools.Helpers.log_file_path();
DevDebugTools.Helpers.log_localization( 'ddtt_tools' );

// Now start jQuery
jQuery( document ).ready( function( $ ) {

    /**
     * Help button
     */
    $( document ).on( 'click', function( e ) {
        var $target = $( e.target );
        var openClass = 'ddtt-help-content--open';

        // Toggle help popup on button click
        if ( $target.is( '.ddtt-help-toggle' ) ) {
            // preventDefault if the element is an anchor tag
            if ( $target.is( 'a' ) ) {
                e.preventDefault();
            }

            var btn = $target;
            var contentId = btn.attr( 'aria-controls' );
            var $content = $( '#' + contentId );
            var isOpen = btn.attr( 'aria-expanded' ) === 'true';

            btn.attr( 'aria-expanded', ! isOpen );
            if ( $content.length ) {
                if ( isOpen ) {
                    $content.prop( 'hidden', true ).removeClass( openClass );

                    // Remove darken class from table
                    btn.closest( 'table' ).removeClass( 'ddtt-help-darken' );
                } else {
                    // Close any other open popups
                    $( '.ddtt-help-content.' + openClass ).each( function() {
                        var $otherContent = $( this );
                        $otherContent.prop( 'hidden', true ).removeClass( openClass );
                        var $otherBtn = $( '[aria-controls="' + $otherContent.attr( 'id' ) + '"]' );
                        if ( $otherBtn.length ) {
                            $otherBtn.attr( 'aria-expanded', 'false' );
                            $otherBtn.closest( 'table' ).removeClass( 'ddtt-help-darken' );
                        }
                    });

                    $content.prop( 'hidden', false ).addClass( openClass );

                    // Add darken class to table containing the clicked button
                    btn.closest( 'table' ).addClass( 'ddtt-help-darken' );
                }
            }
            return;
        }

        // Close popup if click outside open help content or toggle button
        if ( $target.closest( '.ddtt-has-help-dialog' ).length === 0 ) {
            $( '.ddtt-help-content.' + openClass ).each( function() {
                var $content = $( this );
                $content.prop( 'hidden', true ).removeClass( openClass );
                var $btn = $( '[aria-controls="' + $content.attr( 'id' ) + '"]' );
                if ( $btn.length ) {
                    $btn.attr( 'aria-expanded', 'false' );
                    $btn.closest( 'table' ).removeClass( 'ddtt-help-darken' );
                }
            } );
        }

        // Close popup on close button click
        if ( $target.is( '.ddtt-help-close' ) ) {
            var $content = $target.closest( '.ddtt-help-content' );
            if ( $content.length ) {
                $content.prop( 'hidden', true ).removeClass( openClass );
                var $btn = $( '[aria-controls="' + $content.attr( 'id' ) + '"]' );
                if ( $btn.length ) {
                    $btn.attr( 'aria-expanded', 'false' );
                    $btn.closest( 'table' ).removeClass( 'ddtt-help-darken' );
                }
            }
        }
    } );


    /////////////////// TOOL MANAGEMENT (DEV ONLY) ///////////////////
    if ( ! ddtt_tools.is_dev ) {
        return;
    }


    /**
     * Drag and Drop Tool Reordering
     */
    const grid = document.getElementById( 'ddtt-tools-grid' );
    let draggedItem = null;
    let scrollAnimationFrame = null;
    let pointerY = 0;

    if ( grid ) {

        grid.querySelectorAll( 'li' ).forEach( function( item ) {
            item.setAttribute( 'draggable', 'true' );
        } );

        grid.addEventListener( 'dragstart', function( e ) {
            const item = e.target.closest( 'li' );
            if ( ! item ) return;

            draggedItem = item;
            item.classList.add( 'ddtt-dragging' );
        } );

        grid.addEventListener( 'dragend', function() {
            if ( draggedItem ) {
                draggedItem.classList.remove( 'ddtt-dragging' );
                draggedItem = null;

                cancelAnimationFrame( scrollAnimationFrame );
                scrollAnimationFrame = null;

                persistToolsOrder();
            }
        } );

        grid.addEventListener( 'dragover', function( e ) {
            e.preventDefault();
            pointerY = e.clientY;

            const target = e.target.closest( 'li' );
            if ( ! target || target === draggedItem ) return;

            const rect = target.getBoundingClientRect();
            const offset = e.clientY - rect.top;

            if ( offset > rect.height / 2 ) {
                target.after( draggedItem );
            } else {
                target.before( draggedItem );
            }

            startAutoScroll();
        } );

        grid.addEventListener( 'wheel', function( e ) {
            if ( draggedItem ) {
                e.stopPropagation();
            }
        }, { passive: true } );
    }

    function startAutoScroll() {
        if ( scrollAnimationFrame ) return;

        function step() {
            const margin = 80;
            const speed = 12;

            if ( pointerY < margin ) window.scrollBy( 0, -speed );
            else if ( pointerY > window.innerHeight - margin ) window.scrollBy( 0, speed );

            scrollAnimationFrame = requestAnimationFrame( step );
        }

        step();
    }

    function persistToolsOrder() {
        const sortedMap = {};

        grid.querySelectorAll( 'li' ).forEach( function( item, index ) {
            const slug = item.getAttribute( 'data-slug' ) || item.dataset.slug;
            if ( slug ) sortedMap[ slug ] = index;
        } );

        $.post( ajaxurl, {
            action: 'ddtt_save_tools',
            tools: sortedMap,
            nonce: ddtt_tools.nonce
        } );
    }


    /**
     * Update the favorite menu item
     *
     * @param string slug The tool slug.
     * @param string label The tool label.
     * @param boolean favorited Whether the tool is favorited.
     * @param boolean enabled Whether the tool is enabled.
     */
    function updateFavoriteMenuItem ( slug, label, favorited, enabled ) {
        const $toolsMenuItem = $( '#toplevel_page_dev-debug-dashboard' );
        const $submenu = $toolsMenuItem.children( '.wp-submenu' );
        const href = 'admin.php?page=dev-debug-tools&tool=' + slug;
        const arrow = '⇢ ';

        const sortedSlugs = $( '#ddtt-tools-grid' ).children( 'li' ).map( function () {
            return $( this ).data( 'slug' );
        } ).get();

        // Remove if not favorited or not enabled
        if ( ! favorited || ! enabled ) {
            $submenu.find( 'a[href="' + href + '"]' ).parent().remove();
            return;
        }

        // If already exists, do nothing
        if ( $submenu.find( 'a[href="' + href + '"]' ).length > 0 ) {
            return;
        }

        // Find all favorited slugs currently in the submenu
        const favoritedSlugs = $submenu.find( 'li > a' ).map( function () {
            const href = $( this ).attr( 'href' );
            const text = $( this ).text();
            // Favorited items start with the arrow and match a slug in sortedSlugs
            const match = href ? href.match( /tool=([^&]+)/ ) : null;
            if ( match && text.trim().startsWith( '⇢' ) ) {
                return match[ 1 ];
            }
            return null;
        } ).get().filter( Boolean );

        // Find index of this slug in sortedSlugs
        const idx = sortedSlugs.indexOf( slug );

        // Find the next favorited slug in sortedSlugs after this one
        let insertBefore = null;
        for ( let i = idx + 1; i < sortedSlugs.length; i++ ) {
            if ( favoritedSlugs.includes( sortedSlugs[ i ] ) ) {
                insertBefore = $submenu.find( 'li > a' ).filter( function () {
                    const href = $( this ).attr( 'href' );
                    const text = $( this ).text();
                    const match = href ? href.match( /tool=([^&]+)/ ) : null;
                    return match && match[ 1 ] === sortedSlugs[ i ] && text.trim().startsWith( '⇢' );
                } ).parent();
                break;
            }
        }

        if ( insertBefore && insertBefore.length ) {
            $( '<li><a href="' + href + '">' + arrow + label + '</a></li>' ).insertBefore( insertBefore );
        } else {
            // Find previous favorited slug
            let insertAfter = $submenu.find( 'li.current' );
            for ( let i = idx - 1; i >= 0; i-- ) {
                if ( favoritedSlugs.includes( sortedSlugs[ i ] ) ) {
                    insertAfter = $submenu.find( 'li > a' ).filter( function () {
                        const href = $( this ).attr( 'href' );
                        const text = $( this ).text();
                        const match = href ? href.match( /tool=([^&]+)/ ) : null;
                        return match && match[ 1 ] === sortedSlugs[ i ] && text.trim().startsWith( '⇢' );
                    } ).parent();
                    break;
                }
            }
            $( '<li><a href="' + href + '">' + arrow + label + '</a></li>' ).insertAfter( insertAfter );
        }
    }


    /**
     * Favorite Tool
     */
    $( document ).on( 'click', '.ddtt-favorite-tool', function( e ) {
        e.preventDefault();

        const $button = $( this );
        const slug = $button.data( 'slug' );
        let favorited = $button.data( 'favorited' ) === 1 || $button.data( 'favorited' ) === '1';

        favorited = ! favorited;
        $button.data( 'favorited', favorited ? 1 : 0 );
        $button.toggleClass( 'favorited', favorited );

        const label = $button.data( 'title' ) || slug;
        const $toolItem = $( '.ddtt-tool-item[data-slug="' + slug + '"]' );
        const enabled = $toolItem.find( '.ddtt-toggle-wrapper input[type="checkbox"]' ).is( ':checked' );

        updateFavoriteMenuItem( slug, label, favorited, enabled );

        $.post( ajaxurl, {
            action: 'ddtt_favorite_tool',
            slug: slug,
            favorited: favorited,
            nonce: ddtt_tools.nonce
        } );
    } );


    /**
     * Enable/Disable Tool
     */
    $( document ).on( 'change', '.ddtt-toggle-wrapper input[type="checkbox"]', function() {
        const $checkbox = $( this );
        const slug = $checkbox.closest( '.ddtt-tool-item' ).data( 'slug' );
        const enabled = $checkbox.is( ':checked' );

        const $button = $( '.ddtt-favorite-tool[data-slug="' + slug + '"]' );
        const favorited = $button.data( 'favorited' ) === 1 || $button.data( 'favorited' ) === '1';
        const label = $button.data( 'title' ) || slug;

        $( this ).closest( '.ddtt-tool-item' ).toggleClass( 'enabled', enabled ).toggleClass( 'disabled', ! enabled );

        updateFavoriteMenuItem( slug, label, favorited, enabled );

        $.post( ajaxurl, {
            action: 'ddtt_toggle_tool',
            slug: slug,
            enabled: enabled,
            nonce: ddtt_tools.nonce
        } );
    } );

} );
