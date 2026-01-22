jQuery( document ).ready( function( $ ) {

    function buildCenteringTool( opts ) {
        // Preserve expanded/collapsed state
        var prevExpanded = $( '#ddtt-ct-top' ).attr( 'data-expanded' ) || 'false';
        $( '#ddtt-ct-top' ).remove();

        opts = opts || {};
        var cellWidth    = opts.cellWidth    || ddtt_admin_bar_centering_tool.prefs[ 'cell-width' ];
        var cellHeight   = opts.cellHeight   || ddtt_admin_bar_centering_tool.prefs[ 'cell-height' ];
        var screenWidth  = opts.screenWidth  || ddtt_admin_bar_centering_tool.prefs[ 'screen-width' ];
        var screenHeight = opts.screenHeight || ddtt_admin_bar_centering_tool.prefs[ 'screen-height' ];
        var background   = opts.background   || ddtt_admin_bar_centering_tool.prefs[ 'background' ];
        var opacity      = opts.opacity      || ddtt_admin_bar_centering_tool.prefs[ 'opacity' ];

        var widthVal  = parseInt( cellWidth, 10 ) || 100;
        var heightVal = parseInt( cellHeight, 10 ) || 50;
        var screenW   = parseInt( screenWidth, 10 ) || 1920;
        var screenH   = parseInt( screenHeight, 10 ) || 1080;

        screenW += 2; // Add 2px to account for center line

        // Calculate max odd columns so grid fits: (columns - 1) * cellWidth + 2 <= screenWidth
        var maxPossible = Math.floor( ( screenW - 2 ) / widthVal ) + 1;
        var columns = maxPossible % 2 === 0 ? maxPossible - 1 : maxPossible;
        var rows    = Math.floor( screenH / heightVal );

        const $container = $( '<div>', {
            id: 'ddtt-ct-top',
            class: 'centering-tool',
            'data-expanded': prevExpanded
        } );

        var centerCellWidth = 2;
        var tableWidth = ( columns - 1 ) * widthVal + centerCellWidth;
        const $table = $( '<div>', { class: 'ddtt-ct-table' } )
            .css( {
                width: tableWidth + 'px',
                marginLeft: 'calc(50% - ' + ( tableWidth / 2 ) + 'px)',
                marginRight: 'calc(50% - ' + ( tableWidth / 2 ) + 'px)'
            } );

        for ( let i = 0; i < rows; i++ ) {
            const $row = $( '<div>', { class: 'ddtt-ct-row' } );

            for ( let j = 0; j < columns; j++ ) {
                let cellClass = 'ddtt-ct-cell';
                let cellW = widthVal;
                if ( j === Math.floor( columns / 2 ) ) {
                    cellClass += ' ddtt-ct-center';
                    cellW = centerCellWidth;
                }
                $( '<div>', { class: cellClass } ).css( {
                    width: cellW + 'px',
                    height: cellHeight
                } ).appendTo( $row );
            }

            $row.appendTo( $table );
        }

        $table.appendTo( $container );

        // Tooltip element
        const $tooltip = $( '<div>', {
            id: 'ddtt-ct-tooltip',
            css: {
                position: 'fixed',
                pointerEvents: 'none',
                background: '#222',
                color: '#fff',
                padding: '4px 8px',
                borderRadius: '4px',
                fontSize: '13px',
                zIndex: 10000,
                display: 'none',
                boxShadow: '0 2px 8px rgba(0,0,0,0.2)'
            }
        } );
        $container.append( $tooltip );

        $( '#wpadminbar' ).after( $container );

        // Apply background color and opacity
        var rgbaColor = hexToRgba( background, parseFloat( opacity ) );
        $container.css( 'background-color', rgbaColor );

        // Restore height style based on previous expanded/collapsed state
        if ( prevExpanded === 'true' ) {
            $container.css( 'height', '100%' );
        } else {
            $container.css( 'height', '25px' );
        }
    }

    const $link  = $( '#wp-admin-bar-ddtt-centering-tool .ab-item' );
    const $label = $link.find( '.ddtt-centering-tool-label' );

    // Build on page load if user preference is on
    if ( ddtt_admin_bar_centering_tool.prefs.on ) {
        buildCenteringTool();
        $label.text( ddtt_admin_bar_centering_tool.i18n.on );
        $link.attr( 'title', ddtt_admin_bar_centering_tool.i18n.title_on );
    }

    // Live update grid on admin bar input change
    $( document ).on( 'blur', '#ddtt-ct-cell-width, #ddtt-ct-cell-height, #ddtt-ct-screen-width, #ddtt-ct-screen-height, #ddtt-ct-background, #ddtt-ct-opacity', function() {
        var cellWidth    = $( '#ddtt-ct-cell-width' ).val()    || ddtt_admin_bar_centering_tool.prefs[ 'cell-width' ];
        var cellHeight   = $( '#ddtt-ct-cell-height' ).val()   || ddtt_admin_bar_centering_tool.prefs[ 'cell-height' ];
        var screenWidth  = $( '#ddtt-ct-screen-width' ).val()  || ddtt_admin_bar_centering_tool.prefs[ 'screen-width' ];
        var screenHeight = $( '#ddtt-ct-screen-height' ).val() || ddtt_admin_bar_centering_tool.prefs[ 'screen-height' ];
        var background   = $( '#ddtt-ct-background' ).val()    || ddtt_admin_bar_centering_tool.prefs[ 'background' ];
        var opacity      = $( '#ddtt-ct-opacity' ).val()       || ddtt_admin_bar_centering_tool.prefs[ 'opacity' ];
        buildCenteringTool( {
            cellWidth: cellWidth,
            cellHeight: cellHeight,
            screenWidth: screenWidth,
            screenHeight: screenHeight,
            background: background,
            opacity: opacity
        } );

        // Save values via AJAX
        $.post( ddtt_admin_bar_centering_tool.ajaxurl, {
            action: 'ddtt_save_centering_tool',
            nonce: ddtt_admin_bar_centering_tool.nonce,
            cell_width: cellWidth,
            cell_height: cellHeight,
            screen_width: screenWidth,
            screen_height: screenHeight,
            background: background,
            opacity: opacity
        } );
    } );

    // Add ajax
    $( document ).on( 'click', '#wp-admin-bar-ddtt-centering-tool a.ab-item', function( e ) {
        e.preventDefault();

        const $tool  = $( '#ddtt-ct-top' );
        const $label = $( this ).find( '.ddtt-centering-tool-label' );
        const $link  = $( this );

        let userOn;

        if ( $tool.length ) {
            $tool.remove(); // Toggle off
            $label.text( ddtt_admin_bar_centering_tool.i18n.off );
            $link.attr( 'title', ddtt_admin_bar_centering_tool.i18n.title_off );
            userOn = false;
        } else {
            buildCenteringTool(); // Toggle on
            $label.text( ddtt_admin_bar_centering_tool.i18n.on );
            $link.attr( 'title', ddtt_admin_bar_centering_tool.i18n.title_on );
            userOn = true;
        }

        // Send AJAX to save preference
        $.post( ddtt_admin_bar_centering_tool.ajaxurl, {
            action: 'ddtt_save_centering_tool',
            nonce: ddtt_admin_bar_centering_tool.nonce,
            on: userOn ? 1 : 0
        } );
    } );

    // Expand/collapse using delegated event, disabled if submenu is open
    $( document ).on( 'click', '#ddtt-ct-top', function() {
        if ( $( '#wp-admin-bar-ddtt-centering-tool' ).hasClass( 'hover' ) ) {
            return;
        }

        var exp = $( this ).attr( 'data-expanded' );
        if ( exp === 'false' ) {
            $( this ).css( 'height', '100%' ).attr( 'data-expanded', 'true' );
        } else {
            $( this ).css( 'height', '25px' ).attr( 'data-expanded', 'false' );
        }
        // Hide tooltip when collapsed
        var $tooltip = $( this ).find( '#ddtt-ct-tooltip' );
        if ( exp !== 'true' ) {
            $tooltip.hide();
        }
    } );

    // Tooltip logic
    $( document ).on( 'mousemove', '#ddtt-ct-top', function( e ) {
        var $container = $( this );
        var expanded = $container.attr( 'data-expanded' );
        var $tooltip = $container.find( '#ddtt-ct-tooltip' );
        if ( expanded !== 'true' ) {
            $tooltip.hide();
            return;
        }
        var containerOffset = $container.offset();
        var containerWidth = $container.outerWidth();
        var centerX = containerOffset.left + containerWidth / 2;
        var mouseX = e.pageX;
        var distance = Math.abs( mouseX - centerX );
        var value = Math.round( distance * 2 );
        $tooltip.text( value + 'px wide' );
        $tooltip.css({
            left: e.clientX + 16,
            top: e.clientY + 16,
            display: 'block'
        });
    } );

    $( document ).on( 'mouseleave', '#ddtt-ct-top', function() {
        var $tooltip = $( this ).find( '#ddtt-ct-tooltip' );
        $tooltip.hide();
    } );

    // Apply background color and opacity
    function hexToRgba( hex, opacity ) {
        hex = hex.replace( '#', '' );
        if ( hex.length === 3 ) {
            hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
        }
        var r = parseInt( hex.substring( 0, 2 ), 16 );
        var g = parseInt( hex.substring( 2, 4 ), 16 );
        var b = parseInt( hex.substring( 4, 6 ), 16 );
        return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + opacity + ')';
    }

} );