<style>
.the-error {
    font-weight: 500;
    display: block;
    margin-bottom: 20px;
    background: white;
    padding: 7px 10px;
    color: black;
    width: fit-content;
    border-radius: 4px;
    box-shadow: 4px 4px 20px black;
}
.suppressed_error_status {
    width: 140px !important;
}
.edit-link {
    display: inline-block;
}
.edit-link.edit {
    margin-left: 10px;
}
.notes {
    width: 100%;
}
.notes input {
    width: 100% !important;
    max-width: 100% !important;
}
</style>

<?php 
// Include the header
include 'header.php';

// Build the current url
$page = ddtt_plugin_options_short_path();
$tab = 'error-suppressing';
$current_url = ddtt_plugin_options_path( $tab );

// Current settings
$enabled = get_option( DDTT_GO_PF.'suppress_errors_enable' );
$uninstalled = get_option( DDTT_GO_PF.'suppress_errors_uninstall' );

// Hidden inputs
$hidden_allowed_html = [
    'input' => [
        'type'  => [],
        'name'  => [],
        'value' => []
    ],
];
$hidden_path = '<input type="hidden" name="page" value="'.$page.'">
<input type="hidden" name="tab" value="'.$tab.'">';

// Are we updating?
if ( isset( $_POST[ '_wpnonce' ] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ '_wpnonce' ] ) ), 'update_suppressed_errors' ) ) {

    // Add Must-Use-Plugin; we will not be adding it by default
    $enable = isset( $_POST[ 'enable' ] ) ? absint( $_POST[ 'enable' ] ) : false;
    if ( $enable && !$enabled ) {
        if ( (new DDTT_ERROR_SUPPRESSING)->add_remove_mu_plugin( 'add' ) ) {
            $enabled = true;
            update_option( DDTT_GO_PF.'suppress_errors_enable', $enabled );
        }
    } elseif ( !$enable && $enabled ) {
        (new DDTT_ERROR_SUPPRESSING)->add_remove_mu_plugin( 'remove' );
        $enabled = false;
        update_option( DDTT_GO_PF.'suppress_errors_enable', $enabled );
    }

    // Remove MU at uninstall
    $uninstall = isset( $_POST[ 'uninstall' ] ) ? absint( $_POST[ 'uninstall' ] ) : false;
    if ( $uninstall && !$uninstalled ) {
        $uninstalled = true;
        update_option( DDTT_GO_PF.'suppress_errors_uninstall', $uninstalled );
    } elseif ( !$uninstall && $uninstalled ) {
        $uninstalled = false;
        update_option( DDTT_GO_PF.'suppress_errors_uninstall', $uninstalled );
    }

    // Fetch the existing
    $existing = get_option( DDTT_GO_PF.'suppressed_errors' );
    if ( !$existing ) {
        $existing = [];
    }

    // Count updates
    $updates = 0;

    // Sanitize
    if ( isset( $_POST[ 'new' ] ) && $_POST[ 'new' ] != '' ) {
        $new_errors = filter_var_array( $_POST[ 'new' ], FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if ( !empty( $new_errors ) ) {
            $count = 0;
            foreach ( $new_errors as $new_error ) {
                if ( $new_error !== '' ) {
                    $existing[ time() + $count++ ] = [
                        'string' => $new_error,
                        'status' => 'active',
                        'user'   => get_current_user_id()
                    ];
                    $updates++;
                }
            }
        }
    }
    
    if ( isset( $_POST[ 'old' ] ) && $_POST[ 'old' ] != '' ) {
        $old_errors = filter_var_array( $_POST[ 'old' ], FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        // Find them
        foreach ( $old_errors as $timestamp => $status ) {
            if ( isset( $existing[ $timestamp ] ) ) {
                if ( $status == 'remove' ) {
                    unset( $existing[ $timestamp ] );
                    $updates++;
                } elseif ( $status !== $existing[ $timestamp ][ 'status' ] ) {
                    $existing[ $timestamp ][ 'status' ] = $status;
                    $updates++;
                }
            }
        }
    }

    if ( isset( $_POST[ 'notes' ] ) && $_POST[ 'notes' ] != '' ) {
        $notes = filter_var_array( $_POST[ 'notes' ], FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        // Find them
        foreach ( $notes as $timestamp => $note ) {
            if ( isset( $existing[ $timestamp ] ) ) {
                if ( !isset( $existing[ $timestamp ][ 'note' ] ) || $note !== $existing[ $timestamp ][ 'note' ] ) {
                    $existing[ $timestamp ][ 'note' ] = $note;
                    $updates++;
                }
            }
        }
    }

    // Update
    if ( $updates > 0 ) {
        update_option( DDTT_GO_PF.'suppressed_errors', $existing );
    }
}
?>

<form id="update-suppressed-errors-form" method="post" action="<?php echo esc_url( $current_url ); ?>">
    <table class="form-table">
        <tr valign="top" id="row_ddtt_suppress_errors_enable">
            <th scope="row">Enable Error Suppressing</th>
            <td><input type="checkbox" id="ddtt_suppress_errors_enable" name="enable" value="1"<?php echo esc_html( checked( $enabled ) ); ?>> <p class="field-desc">Suppressing errors requires adding the settings via a Must-Use-Plugin so it loads before any regular plugin and before the theme is loaded.</p></td>
        </tr>
        <tr valign="top" id="row_ddtt_error_uninstall">
            <th scope="row">Remove Must-Use-Plugin When Uninstalling Developer Debug Tools</th>
            <td><input type="checkbox" id="ddtt_suppress_errors_uninstall" name="uninstall" value="1"<?php echo esc_html( checked( $uninstalled ) ); ?>> <p class="field-desc">If enabled above, selecting this option will remove the Must-Use-Plugin upon uninstall. Keep this unchecked if you want to leave it.</p></td>
        </tr>
        <tr valign="top" id="row_suppressed_errors">
            <th scope="row">Add Errors to Suppress from Debug Log</th>
            <td><div id="text_plus_ddtt_suppressed_errors">
                <a href="#" class="add_form_field">Add Another Field +</a>
                <div><input type="text" id="ddtt_suppressed_errors" name="new[]" value="" style="width: 43.75rem;" placeholder="Enter exact string that the error contains"/></div>
            </div><p class="field-desc break">All errors with the strings you enter will be suppressed (so long as your host allows for it).</p></td>
        </tr>
    </table>

    <?php echo wp_kses( $hidden_path, $hidden_allowed_html ); ?>
    <?php wp_nonce_field( 'update_suppressed_errors' ); ?>
    <div class="no-choice meta-update-button"><br><br><input type="submit" value="Update" class="button button-primary"/></div>

    <br><br><br><br>
    <h2>Currently Suppressed Errors</h2>
    <br>
    <?php
    // trigger_error( 'Test warning for custom error handler', E_USER_WARNING );
    // trigger_error( 'Test 2 warning for custom error handler', E_USER_WARNING );

    // Get the suppressed errors
    $suppressed_errors = get_option( DDTT_GO_PF.'suppressed_errors' );
    if ( !$suppressed_errors ) {
        ?>
        <em>You have not suppressed any errors.</em>
        <?php
    } else {
        
        ?>
        <div class="full_width_container">
            <table id="error-types-table" class="admin-large-table">
                <tr>
                    <th style="width: 120px;">Date Added</th>
                    <th>Error String</th>
                    <th style="width: 120px;">Added/Updated By</th>
                    <th style="width: 160px;">Status</th>
                </tr>
                <?php
                // Allowed html
                $allowed_html = [
                    'select' => [
                        'class' => [],
                        'name' => [],
                    ],
                    'option' => [
                        'value' => [],
                        'selected' => [],
                    ]
                ];

                // Iter the data
                foreach ( $suppressed_errors as $timestamp => $error ) {

                    // Variables
                    $errstr = $error[ 'string' ];
                    $status = $error[ 'status' ];
                    $notes = isset( $error[ 'note' ] ) ? stripslashes( $error[ 'note' ] ) : '';
                    $user_id = $error[ 'user' ];
                    $user = get_userdata( $user_id );
                    $display_name = $user->display_name;

                    // Edit link
                    $edit_text = !$notes ? 'Add a Note' : 'Edit Note';
                    $edit_class = !$notes ? 'add' : 'edit';

                    // Select field
                    $dropdown = '<select class="suppressed_error_status" name="old['.$timestamp.']">
                        <option value="active"'.ddtt_is_qs_selected( 'active', $status ).'>Suppressed</option>
                        <option value="paused"'.ddtt_is_qs_selected( 'paused', $status ).'>Paused</option>
                        <option value="remove">Remove</option>
                    </select>';

                    // Add the row
                    ?>
                    <tr data-id="<?php echo esc_attr( $timestamp ); ?>">
                        <td><?php echo esc_html( gmdate( 'm/d/Y', $timestamp ) ); ?></td>
                        <td><div class="the-error"><?php echo esc_html( $errstr ); ?></div>
                            <div class="notes"><?php echo esc_html( $notes ); ?><a href="#" class="edit-link <?php echo esc_attr( $edit_class ); ?>">&#9998; <?php echo esc_html( $edit_text ); ?></a></div></td>
                        <td><?php echo esc_html( $display_name ); ?></td>
                        <td><?php echo wp_kses( $dropdown, $allowed_html ); ?></td>
                    </tr>
                    <?php
                }
                ?>
            </table>
        </div>

        <div class="no-choice meta-update-button"><br><br><input type="submit" value="Update" class="button button-primary"/></div>
        <?php
    } ?>
</form>