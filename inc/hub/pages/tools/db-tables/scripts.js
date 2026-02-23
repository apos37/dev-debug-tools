// Helper logs
DevDebugTools.Helpers.log_file_path();
DevDebugTools.Helpers.log_localization( 'ddtt_db_tables' );

// Now start jQuery
jQuery( document ).ready( function( $ ) {
    
    /**
     * Load table records via AJAX
     */
    function loadTableRecords( table, page = 1, search = '' ) {
        var perPage = $( '#ddtt-records-per-page' ).val() || 20;
        $( '#ddtt-record-value-table' ).html(
            '<tr><td colspan="100%"><em class="ddtt-loading-msg">' + ddtt_db_tables.i18n.loading + '</em></td></tr>'
        );
        $( '#ddtt-total-records-count' ).text( '...' );

        $.post( ajaxurl, {
            action: 'ddtt_get_table_records',
            nonce: ddtt_db_tables.nonce,
            table: table,
            page: page,
            search: search,
            per_page: perPage
        }, function( response ) {
            if ( response.success ) {
                $( '#ddtt-record-value-table' ).html( response.data.html );
                $( '#ddtt-records-pagination' ).html( response.data.pagination );
                $( '#ddtt-total-records-count' ).text( response.data.total_records );
            } else {
                $( '#ddtt-record-value-table' ).html(
                    '<tr><td colspan="100%"><em>' + response.data.message + '</em></td></tr>'
                );
                $( '#ddtt-records-pagination' ).html( '' );
                $( '#ddtt-total-records-count' ).text( '0' );
            }
        } );
    }

    // Handle dropdown change
    $( '#ddtt-table-list' ).on( 'change', function() {
        let table = $( this ).val();
        if ( table ) {
            $( '#ddtt-record-search-form' ).show();
            loadTableRecords( table, 1 );
        } else {
            $( '#ddtt-record-search-form' ).hide();
            $( '#ddtt-record-value-table' ).html(
                '<tr><td>The selected table records will be displayed here.</td></tr>'
            );
        }
    } );

    // Handle records per page change
    $( '#ddtt-records-per-page' ).on( 'change', function() {
        let table = $( '#ddtt-table-list' ).val();
        if ( table ) {
            loadTableRecords( table, 1 );
        }
    } );

    // Handle pagination clicks
    $( document ).on( 'click', '.ddtt-pagination a', function( e ) {
        e.preventDefault();
        let table  = $( '#ddtt-table-list' ).val();
        let page   = $( this ).data( 'page' );
        let search = $( '#ddtt-record-search' ).val();
        loadTableRecords( table, page, search );
    } );

    // Handle search
    $( '#ddtt-record-search-form' ).on( 'submit', function( e ) {
        e.preventDefault();
        let table  = $( '#ddtt-table-list' ).val();
        let search = $( '#ddtt-record-search' ).val();
        loadTableRecords( table, 1, search );
    } );

    // Restore last state if provided
    if ( typeof ddtt_db_tables !== 'undefined' && ddtt_db_tables.last_table.table ) {
        let table  = ddtt_db_tables.last_table.table;
        let page   = ddtt_db_tables.last_table.page;
        let search = ddtt_db_tables.last_table.search;

        // Set dropdown
        $( '#ddtt-table-list' ).val( table );

        if ( table ) {
            $( '#ddtt-record-search-form' ).show();
            $( '#ddtt-record-search' ).val( search );
            loadTableRecords( table, page, search );
        }
    }
    
    // Handle view more/less for truncated values
    $( document ).on( 'click', '.ddtt-view-more', function( e ) {
        e.preventDefault();
        var target = $( this ).data( 'target' );
        $( '#' + target ).show();
        $( this ).prev( '.ddtt-truncated-value' ).hide();
        $( this ).hide();
    } );

    $( document ).on( 'click', '.ddtt-view-less', function( e ) {
        e.preventDefault();
        var target = $( this ).data( 'target' );
        $( '#' + target ).hide();
        var td = $( '#' + target ).closest( 'td' );
        td.find( '.ddtt-truncated-value' ).show();
        td.find( '.ddtt-view-more' ).show();
    } );

} );
