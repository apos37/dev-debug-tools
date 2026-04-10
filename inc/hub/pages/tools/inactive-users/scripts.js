// Helper logs
DevDebugTools.Helpers.log_file_path();
DevDebugTools.Helpers.log_localization( 'ddtt_inactive_users' );

// Now start jQuery
jQuery( document ).ready( function( $ ) {

    /**
     * Fetch users via AJAX based on current settings
     */
    function fetchUsers( page = 1 ) {
        const $tableCont = $( '#ddtt-users-table' );
        const $paginationCont = $( '#ddtt-users-pagination' );
        const $totalCount = $( '#ddtt-total-records-count' );
        const $scanBtn = $( '#ddtt_fetch_users' );
        const originalText = $scanBtn.text();

        // Show loading state
        $scanBtn.prop( 'disabled', true ).text( ddtt_inactive_users.i18n.scanning ).addClass( 'ddtt-button-disabled ddtt-loading-msg' );
        $tableCont.css( 'opacity', '0.5' );
        $tableCont.html( 
            '<tr><td colspan="100%"><em class="ddtt-loading-msg">' + ddtt_inactive_users.i18n.scanning + '</em></td></tr>' 
        );
        $totalCount.text( '0' );

        $( '.ddtt-scan-results-link' ).remove();

        // Gather data from your settings fields
        // Note: IDs usually follow ddtt_{key} based on your render method
        const data = {
            action: 'ddtt_get_inactive_users',
            nonce: ddtt_inactive_users.nonce,
            threshold_val: $( '#ddtt_threshold_val' ).val(),
            threshold_unit: $( '#ddtt_threshold_unit' ).val(),
            deletion_status: $( '#ddtt_deletion_status' ).val(),
            grace_period: $( '#ddtt_grace_period' ).val(),
            exclude_roles: $( 'input[name="ddtt_exclude_roles[]"]:checked' ).map( function() {
                return $( this ).val();
            } ).get(),
            keywords: $( '#ddtt_keywords' ).val(),
            table_cols: $( '#ddtt_table_cols' ).val(),
            per_page: $( '#ddtt-records-per-page' ).val(),
            page: page
        };

        $tableCont.css( 'opacity', '0.5' );

        $.post( ajaxurl, data, function( response ) {
            $scanBtn.removeClass( 'ddtt-loading-msg' );
            $tableCont.css( 'opacity', '1' );

            if ( response.success ) {
                $scanBtn.text( ddtt_inactive_users.i18n.scanSuccess );
                $tableCont.html( response.data.html );
                $paginationCont.html( response.data.pagination );
                $totalCount.text( response.data.total_records );
                
                const totalUsersRaw = $( '#ddtt-total-records-count-all' ).text().replace( /,/g, '' );
                const totalUsersCount = parseFloat( totalUsersRaw ) || 0;
                const inactiveCount = response.data.total_records;
                $totalCount.text( inactiveCount.toLocaleString() );
                const percentage = totalUsersCount > 0 ? ( ( inactiveCount / totalUsersCount ) * 100 ).toFixed( 2 ) : '0';
                $( '#ddtt-inactive-users-percentage' ).text( percentage );

                $scanBtn.parent().append( ' <a href="#ddtt-tool-section" class="ddtt-scan-results-link">' + ddtt_inactive_users.i18n.scanLink + '</a>' );

                // Reset button after a delay
                setTimeout( function() {
                    $scanBtn.prop( 'disabled', false ).removeClass( 'ddtt-button-disabled' ).text( originalText );

                    if ( $( '#ddtt_deletion_status' ).val() === 'all' && response.data.total_records > 0 ) {
                        $( '#ddtt_mark_inactive_users' ).prop( 'disabled', false ).removeClass( 'ddtt-button-disabled' );
                    }

                    if ( response.data.total_records > 0 ) {
                        $( '#ddtt_delete_eligible_users' ).prop( 'disabled', false ).removeClass( 'ddtt-button-disabled' );
                    }
                }, 3000 );

            } else {
                $scanBtn.text( ddtt_inactive_users.i18n.scanError );
                const errorMsg = response.data && response.data.message ? response.data.message : 'Error fetching users.';
                $tableCont.html( 
                    '<tr><td colspan="100%"><em>' + errorMsg + '</em></td></tr>' 
                );
                $totalCount.text( '0' );
                console.error( 'Error fetching users:', response );
            }

        } ).fail( function( jqXHR, textStatus, errorThrown ) {
            $scanBtn.text( ddtt_inactive_users.i18n.scanError );

            $scanBtn.prop( 'disabled', false ).removeClass( 'ddtt-button-disabled ddtt-loading-msg' );
            $tableCont.css( 'opacity', '1' );

            const failMsg = 'Critical Error: ' + jqXHR.status + ' ' + errorThrown;
            
            $tableCont.html( 
                '<tr><td colspan="100%"><em style="color: #d63638;">' + failMsg + '</em></td></tr>' 
            );
            $totalCount.text( '0' );
            console.error( 'AJAX Fail:', textStatus, errorThrown );
        } );
    }

    /**
     * Event: Click "Scan Now" button
     */
    $( document ).on( 'click', '#ddtt_fetch_users', function( e ) {
        e.preventDefault();
        fetchUsers( 1 );
    } );

    /**
     * Event: Pagination Links
     */
    $( document ).on( 'click', '.ddtt-pagination a', function( e ) {
        e.preventDefault();
        const page = $( this ).data( 'page' );
        fetchUsers( page );
    } );

    /**
     * Event: Select All Checkbox
     */
    $( document ).on( 'change', '.ddtt-select-all-toggle', function() {
        const isChecked = $( this ).prop( 'checked' );

        const userCheckboxes = $( '.ddtt-user-checkbox' );
        userCheckboxes.prop( 'checked', isChecked );
        userCheckboxes.closest( 'tr' ).toggleClass( 'ddtt-row-checked', isChecked );

        const selectAllToggles = $( '.ddtt-select-all-toggle' );
        selectAllToggles.prop( 'checked', isChecked );
        selectAllToggles.closest( 'tr' ).toggleClass( 'ddtt-row-checked', isChecked );
    } );

    /**
     * Event: Update button states based on checkbox selection
     */
    $( document ).on( 'change', '.ddtt-user-checkbox, .ddtt-select-all-toggle', function() {
        const selectedCount = $( '.ddtt-user-checkbox:checked' ).length;
        const $markBtn = $( '#ddtt-mark-selected-users-pending-btn' );
        const $markNotPendingBtn = $( '#ddtt-remove-selected-users-as-pending-btn' );
        const $deleteBtn = $( '#ddtt-delete-selected-users-btn' );
        const deletionStatus = $( '#ddtt_deletion_status' ).val();

        if ( selectedCount > 0 ) {
            if ( deletionStatus === 'all' || deletionStatus === 'unmarked_only' ) {
                $markBtn.prop( 'disabled', false ).removeClass( 'ddtt-button-disabled' );
            } else {
                $markBtn.prop( 'disabled', true ).addClass( 'ddtt-button-disabled' );
            }
            if ( deletionStatus === 'all' || deletionStatus === 'pending_only' ) {
                $markNotPendingBtn.prop( 'disabled', false ).removeClass( 'ddtt-button-disabled' );
            } else {
                $markNotPendingBtn.prop( 'disabled', true ).addClass( 'ddtt-button-disabled' );
            }
            $deleteBtn.prop( 'disabled', false ).removeClass( 'ddtt-button-disabled' );
        } else {
            $markBtn.prop( 'disabled', true ).addClass( 'ddtt-button-disabled' );
            $markNotPendingBtn.prop( 'disabled', true ).addClass( 'ddtt-button-disabled' );
            $deleteBtn.prop( 'disabled', true ).addClass( 'ddtt-button-disabled' );
        }
    } );

    /**
     * Event: Records Per Page Change
     */
    $( document ).on( 'change', '#ddtt-records-per-page', function() {
        fetchUsers( 1 );
    } );


    /**
     * Sync Missing Last Online Keys
     */
    $( '#ddtt_sync_users_last_online' ).on( 'click', function( e ) {
        e.preventDefault();
        
        if ( ! confirm( ddtt_inactive_users.i18n.syncConfirm ) ) {
            return;
        }
        console.log( 'Starting sync of missing last online keys...' );

        const $btn = $( this );
        const last_synced = $( '.ddtt-last-synced' );
        const originalText = $btn.text();

        // Disable button and start the process
        $btn.prop( 'disabled', true ).addClass( 'ddtt-button-disabled ddtt-loading-msg' ).text( ddtt_inactive_users.i18n.syncingUsers );

        function runSyncChunk() {
            $.post( ajaxurl, {
                action: 'ddtt_sync_users_last_online',
                nonce: ddtt_inactive_users.syncUsersNonce
            }, function( response ) {
                if ( response.success ) {
                    if ( ! response.data.done ) {
                        // Update button text with remaining count to show progress
                        const remaining = response.data.remaining > 0 ? ' (' + response.data.remaining + ' left)' : '';
                        $btn.text( ddtt_inactive_users.i18n.syncingUsers + remaining );
                        
                        // Start the next chunk immediately
                        runSyncChunk();
                    } else {
                        // All done!
                        $btn.text( ddtt_inactive_users.i18n.syncSuccess ).removeClass( 'ddtt-loading-msg' );
                        last_synced.html( 'Last synced: Just now' );

                        // Reset button after a delay
                        setTimeout( function() {
                            $btn.prop( 'disabled', false ).removeClass( 'ddtt-button-disabled' ).text( originalText );
                            fetchUsers( 1 );
                        }, 3000 );
                    }
                } else {
                    alert( ddtt_inactive_users.i18n.syncError );
                    $btn.prop( 'disabled', false ).removeClass( 'ddtt-button-disabled ddtt-loading-msg' ).text( originalText );
                }
            } ).fail( function() {
                alert( ddtt_inactive_users.i18n.syncError );
                $btn.prop( 'disabled', false ).removeClass( 'ddtt-button-disabled ddtt-loading-msg' ).text( originalText );
            } );
        }

        // Trigger the first chunk
        runSyncChunk();
    } );


    /**
     * Event: Mark All Inactive Users
     */
    $( document ).on( 'click', '#ddtt_mark_inactive_users', function( e ) {
        e.preventDefault();

        const deletionStatus = $( '#ddtt_deletion_status' ).val();
        if ( deletionStatus !== 'all' && deletionStatus !== 'unmarked_only' ) {
            alert( ddtt_inactive_users.i18n.markingCond );
            return;
        }

        if ( ! confirm( ddtt_inactive_users.i18n.markingConfirm ) ) {
            return;
        }

        const $btn = $( this );
        const originalText = $btn.text();
        
        // Gather current UI values to match the scan exactly
        const thresholdVal  = $( '#ddtt_threshold_val' ).val();
        const thresholdUnit = $( '#ddtt_threshold_unit' ).val();
        const keywords      = $( '#ddtt_keywords' ).val();
        const excludeRoles  = $( 'input[name="ddtt_exclude_roles[]"]:checked' ).map( function() {
            return $( this ).val();
        } ).get();

        $btn.prop( 'disabled', true )
            .addClass( 'ddtt-button-disabled ddtt-loading-msg' )
            .text( ddtt_inactive_users.i18n.marking );

        function runMarkingChunk() {
            $.post( ajaxurl, {
                action: 'ddtt_mark_all_inactive_users',
                nonce: ddtt_inactive_users.markInactiveNonce,
                threshold_val: thresholdVal,
                threshold_unit: thresholdUnit,
                exclude_roles: excludeRoles,
                keywords: keywords
            }, function( response ) {
                if ( response.success ) {
                    if ( ! response.data.done ) {
                        const remaining = response.data.remaining > 0 ? ' (' + response.data.remaining + ' left)' : '';
                        $btn.text( ddtt_inactive_users.i18n.marking + remaining );
                        runMarkingChunk();
                    } else {
                        $btn.text( ddtt_inactive_users.i18n.markedSuccess ).removeClass( 'ddtt-loading-msg' );
                        
                        setTimeout( function() {
                            $btn.prop( 'disabled', false ).removeClass( 'ddtt-button-disabled' ).text( originalText );
                            // Re-scan to update the table rows with their new "Pending" status
                            fetchUsers( 1 );
                        }, 3000 );
                    }
                } else {
                    alert( ddtt_inactive_users.i18n.markedError );
                    $btn.prop( 'disabled', false ).removeClass( 'ddtt-button-disabled ddtt-loading-msg' ).text( originalText );
                }
            } ).fail( function() {
                alert( ddtt_inactive_users.i18n.markedError );
                $btn.prop( 'disabled', false ).removeClass( 'ddtt-button-disabled ddtt-loading-msg' ).text( originalText );
            } );
        }

        runMarkingChunk();
    } );


    /**
     * Event: Mark Selected Users as Pending
     */
    $( document ).on( 'click', '#ddtt-mark-selected-users-pending-btn', function( e ) {
        e.preventDefault();

        const deletionStatus = $( '#ddtt_deletion_status' ).val();
        if ( deletionStatus !== 'all' && deletionStatus !== 'unmarked_only' ) {
            alert( ddtt_inactive_users.i18n.markingCond );
            return;
        }

        const selectedIds = $( '.ddtt-user-checkbox:checked' ).map( function() {
            return $( this ).val();
        } ).get();

        if ( selectedIds.length === 0 ) return;

        if ( ! confirm( ddtt_inactive_users.i18n.markingConfirm ) ) {
            return;
        }

        const $btn = $( this );
        const originalText = $btn.text();

        $btn.prop( 'disabled', true )
            .addClass( 'ddtt-button-disabled ddtt-loading-msg' )
            .text( ddtt_inactive_users.i18n.marking );

        $.post( ajaxurl, {
            action: 'ddtt_mark_selected_inactive_users',
            nonce: ddtt_inactive_users.markInactiveNonce,
            user_ids: selectedIds
        }, function( response ) {
            if ( response.success ) {
                $btn.text( ddtt_inactive_users.i18n.markedSuccess ).removeClass( 'ddtt-loading-msg' );
                
                setTimeout( function() {
                    $btn.text( originalText );
                    // Re-scan to refresh the table and reset button states
                    fetchUsers( 1 );
                }, 3000 );
            } else {
                alert( response.data.message || ddtt_inactive_users.i18n.markedError );
                $btn.prop( 'disabled', false ).removeClass( 'ddtt-button-disabled ddtt-loading-msg' ).text( originalText );
            }
        } ).fail( function() {
            alert( ddtt_inactive_users.i18n.markedError );
            $btn.prop( 'disabled', false ).removeClass( 'ddtt-button-disabled ddtt-loading-msg' ).text( originalText );
        } );
    } );


    /**
     * Event: Remove All Pending Delete Keys (Global Reset)
     */
    $( document ).on( 'click', '#ddtt_remove_pending_delete_keys', function( e ) {
        e.preventDefault();

        const deletionStatus = $( '#ddtt_deletion_status' ).val();
        if ( deletionStatus === 'unmarked_only' ) {
            alert( ddtt_inactive_users.i18n.removingCond );
            return;
        }

        if ( ! confirm( ddtt_inactive_users.i18n.removeConfirm ) ) {
            return;
        }

        const $btn = $( this );
        const originalText = $btn.text();

        $btn.prop( 'disabled', true )
            .addClass( 'ddtt-button-disabled ddtt-loading-msg' )
            .text( ddtt_inactive_users.i18n.removing );

        function runRemoveAllChunks() {
            $.post( ajaxurl, {
                action: 'ddtt_remove_all_pending_keys',
                nonce: ddtt_inactive_users.markInactiveNonce
            }, function( response ) {
                if ( response.success ) {
                    if ( ! response.data.done ) {
                        const remaining = response.data.remaining > 0 ? ' (' + response.data.remaining + ' left)' : '';
                        $btn.text( ddtt_inactive_users.i18n.removing + remaining );
                        runRemoveAllChunks();
                    } else {
                        $btn.text( ddtt_inactive_users.i18n.removeSuccess ).removeClass( 'ddtt-loading-msg' );
                        
                        setTimeout( function() {
                            $btn.prop( 'disabled', false ).removeClass( 'ddtt-button-disabled' ).text( originalText );
                            fetchUsers( 1 );
                        }, 3000 );
                    }
                } else {
                    alert( ddtt_inactive_users.i18n.removeError );
                    $btn.prop( 'disabled', false ).removeClass( 'ddtt-button-disabled ddtt-loading-msg' ).text( originalText );
                }
            } ).fail( function() {
                alert( ddtt_inactive_users.i18n.removeError );
                $btn.prop( 'disabled', false ).removeClass( 'ddtt-button-disabled ddtt-loading-msg' ).text( originalText );
            } );
        }

        runRemoveAllChunks();
    } );


    /**
     * Event: Remove Selected Users as Pending
     */
    $( document ).on( 'click', '#ddtt-remove-selected-users-as-pending-btn', function( e ) {
        e.preventDefault();

        const deletionStatus = $( '#ddtt_deletion_status' ).val();
        if ( deletionStatus === 'unmarked_only' ) {
            alert( ddtt_inactive_users.i18n.removingCond );
            return;
        }

        const selectedIds = $( '.ddtt-user-checkbox:checked' ).map( function() {
            return $( this ).val();
        } ).get();

        if ( selectedIds.length === 0 ) return;

        if ( ! confirm( ddtt_inactive_users.i18n.removeConfirm ) ) {
            return;
        }

        const $btn = $( this );
        const originalText = $btn.text();

        $btn.prop( 'disabled', true )
            .addClass( 'ddtt-button-disabled ddtt-loading-msg' )
            .text( ddtt_inactive_users.i18n.removing );

        $.post( ajaxurl, {
            action: 'ddtt_remove_selected_pending_users',
            nonce: ddtt_inactive_users.markInactiveNonce,
            user_ids: selectedIds
        }, function( response ) {
            if ( response.success ) {
                $btn.text( ddtt_inactive_users.i18n.removeSuccess ).removeClass( 'ddtt-loading-msg' );
                
                setTimeout( function() {
                    $btn.text( originalText );
                    fetchUsers( 1 );
                }, 3000 );
            } else {
                alert( response.data.message || ddtt_inactive_users.i18n.removeError );
                $btn.prop( 'disabled', false ).removeClass( 'ddtt-button-disabled ddtt-loading-msg' ).text( originalText );
            }
        } ).fail( function() {
            alert( ddtt_inactive_users.i18n.removeError );
            $btn.prop( 'disabled', false ).removeClass( 'ddtt-button-disabled ddtt-loading-msg' ).text( originalText );
        } );
    } );


    /**
     * Event: Remove All Tracking Keys (Global Activity Reset)
     */
    $( document ).on( 'click', '#ddtt_remove_tracking_keys', function( e ) {
        e.preventDefault();

        if ( ! confirm( ddtt_inactive_users.i18n.resetTrackingConfirm ) ) {
            return;
        }

        const $btn = $( this );
        const originalText = $btn.text();

        $btn.prop( 'disabled', true )
            .addClass( 'ddtt-button-disabled ddtt-loading-msg' )
            .text( ddtt_inactive_users.i18n.resetTracking );

        function runRemoveTrackingChunks() {
            $.post( ajaxurl, {
                action: 'ddtt_remove_all_tracking_keys',
                nonce: ddtt_inactive_users.resetTrackingNonce
            }, function( response ) {
                if ( response.success ) {
                    if ( ! response.data.done ) {
                        const remaining = response.data.remaining > 0 ? ' (' + response.data.remaining + ' left)' : '';
                        $btn.text( ddtt_inactive_users.i18n.resetTracking + remaining );
                        runRemoveTrackingChunks();
                    } else {
                        $btn.text( ddtt_inactive_users.i18n.resetTrackingSuccess ).removeClass( 'ddtt-loading-msg' );

                        $( '.ddtt-last-synced' ).html( 'Last synced: Never' );
                        
                        setTimeout( function() {
                            $btn.prop( 'disabled', false ).removeClass( 'ddtt-button-disabled' ).text( originalText );
                            fetchUsers( 1 );
                        }, 3000 );
                    }
                } else {
                    alert( ddtt_inactive_users.i18n.resetTrackingError );
                    $btn.prop( 'disabled', false ).removeClass( 'ddtt-button-disabled ddtt-loading-msg' ).text( originalText );
                }
            } ).fail( function() {
                alert( ddtt_inactive_users.i18n.resetTrackingError );
                $btn.prop( 'disabled', false ).removeClass( 'ddtt-button-disabled ddtt-loading-msg' ).text( originalText );
            } );
        }

        runRemoveTrackingChunks();
    } );


    /**
     * Event: Delete All Eligible Users (Bulk Global Deletion)
     */
    $( document ).on( 'click', '#ddtt_delete_eligible_users', function( e ) {
        e.preventDefault();

        const gracePeriod = $( '#ddtt_grace_period' ).val();

        if ( ! confirm( ddtt_inactive_users.i18n.deleteConfirm ) ) {
            return;
        }

        const $btn = $( this );
        const originalText = $btn.text();

        $btn.prop( 'disabled', true )
            .addClass( 'ddtt-button-disabled ddtt-loading-msg' )
            .text( ddtt_inactive_users.i18n.deleting );

        function runDeleteEligibleChunks() {
            $.post( ajaxurl, {
                action: 'ddtt_delete_all_eligible_users',
                nonce: ddtt_inactive_users.deleteUsersNonce,
                grace_period: gracePeriod
            }, function( response ) {
                if ( response.success ) {
                    if ( ! response.data.done ) {
                        const remaining = response.data.remaining > 0 ? ' (' + response.data.remaining + ' left)' : '';
                        $btn.text( ddtt_inactive_users.i18n.deleting + remaining );
                        runDeleteEligibleChunks();
                    } else {
                        $btn.text( response.data.total_deleted + ' ' + ddtt_inactive_users.i18n.deleteSuccess )
                            .removeClass( 'ddtt-loading-msg' );
                        
                        setTimeout( function() {
                            $btn.prop( 'disabled', false ).removeClass( 'ddtt-button-disabled' ).text( originalText );
                            fetchUsers( 1 );
                        }, 3000 );
                    }
                } else {
                    alert( response.data.message || ddtt_inactive_users.i18n.deleteError );
                    $btn.prop( 'disabled', false ).removeClass( 'ddtt-button-disabled ddtt-loading-msg' ).text( originalText );
                }
            } ).fail( function() {
                alert( ddtt_inactive_users.i18n.deleteError );
                $btn.prop( 'disabled', false ).removeClass( 'ddtt-button-disabled ddtt-loading-msg' ).text( originalText );
            } );
        }

        runDeleteEligibleChunks();
    } );


    /**
     * Event: Delete Selected Users
     */
    $( document ).on( 'click', '#ddtt-delete-selected-users-btn', function( e ) {
        e.preventDefault();

        const selectedIds = $( '.ddtt-user-checkbox:checked' ).map( function() {
            return $( this ).val();
        } ).get();

        if ( selectedIds.length === 0 ) return;

        // Use the confirmed delete i18n message
        if ( ! confirm( ddtt_inactive_users.i18n.deleteConfirm ) ) {
            return;
        }

        const $btn = $( this );
        const originalText = $btn.text();

        $btn.prop( 'disabled', true )
            .addClass( 'ddtt-button-disabled ddtt-loading-msg' )
            .text( ddtt_inactive_users.i18n.deleting );

        $.post( ajaxurl, {
            action: 'ddtt_delete_selected_users',
            nonce: ddtt_inactive_users.deleteUsersNonce,
            user_ids: selectedIds
        }, function( response ) {
            if ( response.success ) {
                // Give immediate feedback
                $btn.text( response.data.total_deleted + ' ' + ddtt_inactive_users.i18n.deleteSuccess ).removeClass( 'ddtt-loading-msg' );
                
                setTimeout( function() {
                    $btn.text( originalText );
                    // Refresh table to show users are gone
                    fetchUsers( 1 );
                }, 2000 );
            } else {
                alert( response.data.message || ddtt_inactive_users.i18n.deleteError );
                $btn.prop( 'disabled', false ).removeClass( 'ddtt-button-disabled ddtt-loading-msg' ).text( originalText );
            }
        } ).fail( function() {
            alert( ddtt_inactive_users.i18n.deleteError );
            $btn.prop( 'disabled', false ).removeClass( 'ddtt-button-disabled ddtt-loading-msg' ).text( originalText );
        } );
    } );


    /**
     * Event: Generate 20 Fake Test Users
     */
    $( document ).on( 'click', '#ddtt_add_fake_accounts', function( e ) {
        e.preventDefault();

        if ( ! confirm( ddtt_inactive_users.i18n.generateConfirm ) ) {
            return;
        }

        const $btn = $( this );
        const originalText = $btn.text();

        $btn.prop( 'disabled', true )
            .addClass( 'ddtt-button-disabled ddtt-loading-msg' )
            .text( ddtt_inactive_users.i18n.generating );

        $.post( ajaxurl, {
            action: 'ddtt_generate_test_users',
            nonce: ddtt_inactive_users.addFakeUsersNonce
        }, function( response ) {
            if ( response.success ) {
                $btn.text( ddtt_inactive_users.i18n.generateSuccess ).removeClass( 'ddtt-loading-msg' );
                
                setTimeout( function() {
                    $btn.prop( 'disabled', false ).removeClass( 'ddtt-button-disabled' ).text( originalText );
                    // Refresh the list to show our new "old" users
                    fetchUsers( 1 );
                }, 3000 );
            } else {
                alert( response.data.message || ddtt_inactive_users.i18n.generateError );
                $btn.prop( 'disabled', false ).removeClass( 'ddtt-button-disabled ddtt-loading-msg' ).text( originalText );
            }
        } ).fail( function() {
            alert( ddtt_inactive_users.i18n.generateError );
            $btn.prop( 'disabled', false ).removeClass( 'ddtt-button-disabled ddtt-loading-msg' ).text( originalText );
        } );
    } );

} );