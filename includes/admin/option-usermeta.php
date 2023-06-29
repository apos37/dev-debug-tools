<style>
.metakey-tr {
    display: none;
}
.no-choice {
    display: none;
}
.false {
    color: hotpink;
}
</style>

<?php include 'header.php'; ?>

<?php 
// Build the current url
$page = ddtt_plugin_options_short_path();
$tab = 'usermeta';
$current_url = ddtt_plugin_options_path( $tab );

// Hidden inputs
$hidden_allowed_html = [
    'input' => [
        'type'      => [],
        'name'      => [],
        'value'     => []
    ],
];
$hidden_path = '<input type="hidden" name="page" value="'.$page.'">
<input type="hidden" name="tab" value="'.$tab.'">';

// Searched default to false
$searched = false;

// Start good early so we can use it later
$good = false;

// Get the user
$s = false;
if ( ddtt_get( 'user' ) ) {
    $s = ddtt_get( 'user' );
    $searched = true;
} elseif ( isset( $_POST[ 'user' ] ) && $_POST[ 'user' ] != '' ) {
    $s = sanitize_text_field( $_POST[ 'user' ] );
    $searched = true;
}

// If we have a search keyword
$notice = false;
$user = false;
if ( $s ) {

    // Get the user from the search
    if ( filter_var( $s, FILTER_VALIDATE_EMAIL ) ) {
        $s = strtolower( $s );
        if ( $user = get_user_by( 'email', $s ) ) {
            $user_id = $user->ID;
        } else {
            $notice = true;
        }
    } elseif ( is_numeric( $s ) ) {
        if ( $user = get_user_by( 'id', $s ) ) {
            $user_id = $s;
        } else {
            $notice = true;
        }
    } else {
        $user_id = 0;
        $notice = true;
    }
    
} else {
    $user_id = get_current_user_id();
    $user = get_user_by( 'id', $user_id );
    $s = $user_id; // To populate default value for search field
}
?>

<?php
// Are we returning a user not found notice?
if ( $notice ) {
    ?>
    <div class="notice notice-error is-dismissible">
    <p><?php _e( 'User <strong>'.$s.'</strong> cannot be found.', 'dev-debug-tools' ); ?></p>
    </div>
    <?php

// Otherwise continue
} elseif ( $user ) { 

    // Empty vars
    $upd = '';
    $mk = '';
    $type = '';
    $val = '';

    // Sanitize $_POST
    if ( $_POST ) {
        $_POST = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
    }

    // Are we updating?
    if ( isset( $_POST[ 'update' ] ) && $_POST[ 'update' ] != '' &&
        isset( $_POST[ 'mk' ] ) && $_POST[ 'mk' ] != '' ) {

        // Run action
        do_action( 'ddtt_on_update_user_meta', $_POST );

        // Verify and sanitize
        $upd = sanitize_text_field( $_POST[ 'update' ] );
        $mk = sanitize_text_field( $_POST[ 'mk' ] );

        if ( isset( $_POST[ 'val' ] ) ) {
            $val = sanitize_text_field( $_POST[ 'val' ] );
        } else {
            $val = false;
        }

        if ( isset( $_POST[ 'type' ] ) && $_POST[ 'type' ] != '' ) {
            $type = sanitize_text_field( $_POST[ 'type' ] );
        } else {
            $type = false;
        }

        if ( isset( $_POST[ 'dels' ] ) && $_POST[ 'dels' ] != '' ) {
            $dels = sanitize_text_field( $_POST[ 'dels' ] );
        } else {
            $dels = false;
        }

        // Success notice
        $success = '';

        // Add
        if ( $upd == 'add' && $val !== false ) {

            // Sanitize it
            $val = sanitize_meta( $mk, $val, 'post' );

            // Add it
            add_user_meta( $user_id, $mk, $val );

            // Success message
            $success = 'The meta key "'.$mk.'" has been added.';

        // Update
        } elseif ( $upd == 'upd' && $type && $val !== false ) {

            // Object or custom?
            if ( $type == 'object' ) {

                // Only allow updating, not adding or deleting
                if ( $upd == 'upd' ) {

                    // Sanitize the post meta
                    $good = false;

                    // Dates
                    $dates = [
                        'user_registered'
                    ];

                    // Define err
                    $err = '';

                    // User Login
                    if ( $mk == 'user_login' ) {
                        if ( validate_username( $val ) ) {
                            $val = sanitize_user( $val );
                            $good = true;
                        } else {
                            $err = 'Invalid Username.';
                        }

                    // User Email
                    } elseif ( $mk == 'user_email' ) {
                        if ( is_email( $val ) ) {
                            $val = sanitize_email( $val );
                            $good = true;
                        } else {
                            $err = 'Invalid Email.';
                        }

                    // User URL
                    } elseif ( $mk == 'user_url' ) {
                        $val = esc_url( $val );
                        if ( $val ) {
                            $good = true;
                        } else {
                            $err = 'Invalid URL.';
                        }

                    // Dates
                    } elseif ( in_array( $mk, $dates ) ) {
                        if ( $timestamp = strtotime( $val ) ) {
                            $val = date( 'Y-m-d H:i:s', $timestamp );
                            $good = true;
                        } else {
                            $err = 'Invalid date format. Please use: "Y-m-d H:i:s" or "m/d/Y g:i AM" (if you do not include a time, it will default to 0:00:00.';
                        }

                    // Everything else
                    } else {
                        $mk = sanitize_meta( $mk, $val, 'post' );
                    }

                    // Update the user meta
                    if ( $good ) {

                        // Update username
                        if ( $mk == 'user_login' ) {
                            global $wpdb;
                            $wpdb->update( 
                                $wpdb->users, 
                                [ 'user_login' => $val ], 
                                [ 'ID' => $user_id ]
                            );

                        // Or update everything else
                        } else {
                            $user_data = array();
                            $user_data['ID'] = $user_id;
                            $user_data[ $mk ] = $val;
                            wp_update_user( $user_data );
                        }

                        // Success message
                        $success = 'The meta key "'.$mk.'" has been updated.';

                    } else {
                        $notice = 'There was a problem updating "'.$mk.'": '.$err;
                        ?>
                        <div class="notice notice-error is-dismissible">
                        <p><?php _e( $notice, 'dev-debug-tools' ); ?></p>
                        </div>
                        <?php
                    }
                }
                
            } elseif ( $type == 'custom' && $val ) {

                // Sanitize it
                $val = sanitize_meta( $mk, $val, 'post' );

                // Update it
                update_user_meta( $user_id, $mk, $val );

                // Success message
                $success = 'The meta key "'.$mk.'" has been updated.';
            }

        // Delete
        } elseif ( $upd == 'del' ) {
            
            // Get the user meta
            $user_meta = get_user_meta( $user_id );

            // Make sure the key exists as custom meta only
            if ( !key_exists( $mk, $user_meta ) ) {
                echo 'The custom meta key "'.esc_attr( $mk ).'" does not exist.';
            }

            // Delete it
            delete_user_meta( $user_id, $mk );

            // Success message
            $success = 'The meta key "'.$mk.'" has been deleted.';

        // Delete Meta Keys Starting with Keyword
        } elseif ( $upd == 'dels' && $dels ) {

            // Get the user meta
            $user_meta = get_user_meta( $user_id );

            // Did we find any?
            $dels_found_blank = 0;
            $dels_found_all = 0;
            $dels_found = [];

            // Cycle
            foreach( $user_meta as $key => $value ) {

                // Check it
                if ( str_starts_with( $key, $mk ) ) {

                    // Blanks only?
                    if ( $dels == 'blank' && $value[0] == '' ) {

                        // Delete it
                        delete_user_meta( $user_id, $key );

                        // Found
                        $dels_found_blank++;
                        $dels_found[] = '"'.$key.'"';

                    } elseif ( $dels == 'all' ) {
                        
                        // Delete it
                        delete_user_meta( $user_id, $key );

                        // Found
                        $dels_found_all++;
                        $dels_found[] = '"'.$key.'"';
                    }
                }
            }

            // Success message
            if ( ( $dels_found_blank > 0 || $dels_found_all > 0 ) && !empty( $dels_found ) ) {
                $success = 'All custom meta keys starting with "'.$mk.'" have been removed: '.implode( ', ', $dels_found );
            } else {
                $notice = 'There were no custom meta keys starting with "'.$mk.'"';
                ?>
                <div class="notice notice-error is-dismissible">
                <p><?php _e( $notice, 'dev-debug-tools' ); ?></p>
                </div>
                <?php
            }
        }

        // Display notice?
        if ( $success != '' ) {
            ?>
            <div class="notice notice-success is-dismissible">
            <p><?php _e( $success, 'dev-debug-tools' ); ?></p>
            </div>
            <?php
        }
    }
}
?>

<?php 
// Get the user meta
if ( $user ) {
    $name = ddtt_user_meta( 'display_name', $user_id );
    $email = ddtt_user_meta( 'user_email', $user_id );
    $incl_name = '<a href="/'.DDTT_ADMIN_URL.'/user-edit.php?user_id='.$user_id.'" target="_blank">'.$name.'</a> (User ID: '.$user_id.' | Email: '.$email.')';
} else {
    $incl_name = 'Not Found';
}
?>

<form method="get" action="<?php echo esc_url( $current_url ); ?>">
    <table class="form-table">
        <tr valign="top">
            <th scope="row">User</th>
            <td><?php echo wp_kses_post( $incl_name ); ?></td>
        </tr>

        <!-- Search Form -->
        <tr valign="top">
            <th scope="row"><label for="user-search-input">User ID or Email</label></th>
            <td><input type="text" name="user" id="user-search-input" value="<?php echo esc_attr( $s ); ?>" required></td>
        </tr>
    </table>
    <?php echo wp_kses( $hidden_path, $hidden_allowed_html ); ?>
    <br><br><input type="submit" value="Search" id="post-search-button" class="button button-primary"/>
</form>
<br><br>

<?php
// Update role
$add_role = false;
$remove_role = false;

if ( ddtt_get( 'role' ) && $user ) {

    // Get the role
    $role = ddtt_get( 'role' );

    // Start a new instance of the user
    $u = new WP_User( $user_id );

    // Add a new role
    if ( ddtt_get( 'update_role', '==', 'Add' ) ) {
        $u->add_role( $role );
        $add_role = true;

    // Or remove a role
    } elseif ( ddtt_get( 'update_role', '==', 'Remove' ) ) {
        $u->remove_role( $role );
        $remove_role = true;
    }

    // Remove the qs params
    ddtt_remove_qs_without_refresh( [ 'role', 'update_role' ] );
}

// Roles Form
if ( $user ) {

    // Get the user's roles
    $user_roles = $user->roles;

    // Adjust
    if ( $add_role ) {
        $user_roles[] = $role;
    } elseif ( $remove_role ) {
        if ( ( $key = array_search( $role, $user_roles ) ) !== false ) {
            unset( $user_roles[ $key ] );
        }
    }

    // Turn into a string
    if ( empty( $user_roles ) ) {
        $roles_string = 'None';
    } else {
        sort( $user_roles );
        $roles_string = implode( '<br>', $user_roles );
    }

    // Get all available roles
    $roles = get_editable_roles();
    ksort( $roles );
    ?>
    <form method="get" action="<?php echo esc_url( $current_url ); ?>">
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Current Roles</th>
                <td><?php echo wp_kses_post( $roles_string ); ?></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="select-role">Choose a Role</label></th>
                <td><select id="select-role" name="role" required>
                    <option value="">-- Select One --</option>
                    <?php
                    foreach ( $roles as $role => $value ) {
                        ?>
                        <option value="<?php echo esc_attr( $role ); ?>"><?php echo esc_attr( $role ); ?> - (<?php echo esc_html( $value['name'] ); ?>)</option>
                        <?php
                    }
                    ?>
                </select></td>
            </tr>
        </table>
        <?php echo wp_kses( $hidden_path, $hidden_allowed_html ); ?>
        <input type="hidden" name="user" value="<?php echo esc_attr( $s ); ?>">
        <br><br><input type="submit" name="update_role" value="Add" id="role-add-button" class="button button-primary"/>
        <input type="submit" name="update_role" value="Remove" id="role-remove-button" class="button button-primary"/>
    </form>
    <br><br>
    <?php
}

// Continue if user is found
if ( $user ) {
    
    // Get the meta
    $user_meta = get_user_meta( $user_id );

    // Sort the meta alphabetically
    ksort( $user_meta );

    // Which choices do we have for updating?
    $update_choices = [
        ['add', 'Add'],
        ['upd', 'Update'],
        ['del', 'Delete (Custom Meta Keys Only)'],
        ['dels', 'Delete Custom Meta Keys Starting with Keyword'],
    ];

    // Hidden User ID
    $hidden_uid = '<input type="hidden" name="user" value="'.$user_id.'">';
    ?>

    <br><hr><br></br>
    <h2>Update Meta Key</h2>
    <br><div class="user-update-form">
    <form method="post" action="<?php echo esc_url( $current_url.'&user='.esc_attr( $s ) ); ?>">
        <table class="form-table">
            <tr valign="top">
                <th scope="row">What do you want to do?</th>
                <td>
                <?php 
                foreach ( $update_choices as $update_choice ) {
                    ?>
                    <div class="update_choice">
                        <input class="update_choice_input" name="update" type="radio" value="<?php echo esc_attr( $update_choice[0] ); ?>" id="update_choice_<?php echo esc_attr( $update_choice[0] ); ?>"<?php echo esc_attr( ddtt_is_qs_checked( $upd, $update_choice[0] ) ); ?>> <label for="update_choice_<?php echo esc_attr( $update_choice[0] ); ?>"><?php echo esc_html( $update_choice[1] ); ?></label>
                    </div>
                    <?php
                }
                ?>
                </td>
            </tr>
            
            <tr valign="top" class="metakey-tr" id="metakey-text">
                <th scope="row"><label for="update_meta_key_text"><strong><span id="metakey-text-label">Meta Key</span></strong> <span class="required-text">(Required)</span></label></th>
                <td><input type="text" name="" id="update_meta_key_text" value="<?php echo esc_attr( $mk ); ?>" size="50"  style="text-transform: lowercase"></td>
            </tr>

            <tr valign="top" class="metakey-tr" id="metakey-type">
                <th scope="row"><label for="update_meta_key_type"><strong>Type</strong> <span class="required-text">(Required)</span>: </label></th>
                <td><select name="type" id="update_meta_key_type">
                    <option value="">-- Select One-- </option>
                    <option value="object">WP_USER OBJECT</option>
                    <option value="custom">USER CUSTOM METADATA</option>
                </select>
                <span class="object_keys_notice"><br>// Meta keys not listed are not available to edit due to safety vulnerabilities or the key is deprecated.</span></td>
            </tr>

            <tr valign="top" class="metakey-tr metakey-type-selects" id="metakey-object-select">
                <th scope="row"><label for="update_meta_key_object_select"><strong>Meta Key</strong> <span class="required-text">(Required)</span></label></th>
                <td><select name="" id="update_meta_key_object_select">
                    <option value="">-- Select a Meta Key-- </option>
                    <?php
                    foreach( $user->data as $key => $value ) {
                        $skip_keys = [
                            'ID',
                            'user_pass',
                            'user_activation_key',
                            'user_status'
                        ];
                        if ( in_array( $key, $skip_keys ) ) {
                            continue;
                        }
                        ?>
                        <option value="<?php echo esc_attr( $key ); ?>"<?php echo esc_attr( ddtt_is_qs_selected( $mk, $key ) ); ?>><?php echo esc_attr( $key ); ?></option>
                        <?php
                    }
                    ?>
                </select></td>
            </tr>

            <tr valign="top" class="metakey-tr metakey-type-selects" id="metakey-custom-select">
                <th scope="row"><label for="update_meta_key_custom_select"><strong>Meta Key</strong> <span class="required-text">(Required)</span></label></th>
                <td><select name="" id="update_meta_key_custom_select">
                    <option value="">-- Select a Custom Meta Key-- </option>
                    <?php
                    foreach( $user_meta as $key => $value ) {
                        ?>
                        <option value="<?php echo esc_attr( $key ); ?>"<?php echo esc_attr( ddtt_is_qs_selected( $mk, $key ) ); ?>><?php echo esc_attr( $key ); ?></option>
                        <?php
                    }
                    ?>
                </select></td>
            </tr>

            <tr valign="top" class="metakey-tr" id="metakey-dels-choice">
                <th scope="row"><label for="update_meta_key_dels_choice"><strong>Choose One</strong> <span class="required-text">(Required)</span></label></th>
                <td><select name="dels" id="update_meta_key_dels_choice">
                    <option value="">-- Select One --</option>
                    <option value="blank">Delete Blank Only</option>
                    <option value="all">Delete All</option>
                </select></td>
            </tr>

            <tr valign="top" class="metakey-tr" id="metakey-value">
                <th scope="row"><label for="update_meta_key_value"><strong>New Value</strong> </label></th>
                <td><textarea name="val" id="update_meta_key_value"><?php echo wp_kses_post( $val ); ?></textarea></td>
            </tr>
        </table>

        <?php echo wp_kses( $hidden_path, $hidden_allowed_html ); ?>
        <?php echo wp_kses( $hidden_uid, $hidden_allowed_html ); ?>
        <div class="no-choice post-update-button"><br><br><input type="submit" value="Update" id="post-update-button" class="button button-primary"/></div>
    </form>
    </div>
    <br><br>

    <!-- The tables -->
    <br><br><hr><br></br>
    <div class="full_width_container">
        <h2>WP_USER OBJECT</h2>
        
        <table class="admin-large-table">
            <tr>
                <th style="width: 300px;">Meta Key</th>
                <th>Meta Value</th>
            </tr>
            <?php
            foreach( $user->data as $key => $value ) {
                if ( $key == $mk && $good ) {
                    $value = $val;
                }

                // Are we redacting?
                if ( !get_option( DDTT_GO_PF.'view_sensitive_info' ) || get_option( DDTT_GO_PF.'view_sensitive_info' ) != 1 ) {

                    // Check if the value is an ip address
                    if ( $key == 'user_login' ) {
                        $value = str_replace( $value, '<div class="redact">'.$value.'</div>', $value );
                    }
                }

                if ( $key == 'user_pass' ) {
                    $value = '<em><a href="/'.DDTT_ADMIN_URL.'/user-edit.php?user_id='.$user_id.'" target="_blank">Edit profile to change password</a></em>';
                }
                ?>
                <tr>
                    <td><?php echo esc_attr( $key ); ?></td>
                    <td><?php echo wp_kses_post( $value ); ?></td>
                </tr>
                <?php
            }
            ?>
        </table>
    </div>
    <br><br><br>

    <div class="full_width_container">
        <h2>USER CUSTOM METADATA</h2>
        
        <table class="admin-large-table">
            <tr>
                <th style="width: 300px;">Meta Key</th>
                <th>Meta Value</th>
            </tr>
            <?php
            foreach( $user_meta as $key => $value ) {
                if ( $key == $mk && $good ) {
                    $value = $val;
                }
                $value = $value[0];

                // Are we redacting?
                if ( !get_option( DDTT_GO_PF.'view_sensitive_info' ) || get_option( DDTT_GO_PF.'view_sensitive_info' ) != 1 ) {

                    // Check if the value is an ip address
                    if ( preg_match( '/^((25[0-5]|(2[0-4]|1\d|[1-9]|)\d)\.?\b){4}$/', $value ) ) {
                        $value = str_replace( $value, '<div class="redact">'.$value.'</div>', $value );

                    // Or if it's an array, let's search for an ip
                    } elseif ( $value && is_serialized( $value ) ) {
                        $array = unserialize( $value );
                        $new_array = [];
                        foreach ( $array as $k => $a ) {
                            if ( strtolower( $k ) == 'ip' ) {
                                $new_array[ $k ] = str_replace( $a, '<div class="redact">'.$a.'</div>', $a );
                            } elseif ( is_array( $a ) ) {
                                $new_sub_array = [];
                                foreach ( $a as $sub_k => $sub_a ) {
                                    if ( strtolower( $sub_k ) == 'ip' ) {
                                        $new_sub_array[ $sub_k ] = str_replace( $sub_a, '<div class="redact">'.$sub_a.'</div>', $sub_a );
                                    } else {
                                        $new_sub_array[ $sub_k ] = $sub_a;
                                    }
                                }
                                $new_array[ $k ] = $new_sub_array;
                            } else {
                                $new_array[ $k ] = $a;
                            }
                        }
                        $value = serialize( $new_array );
                    }
                }
                ?>
                <tr>
                    <td><?php echo esc_attr( $key ); ?></td>
                    <td><?php echo wp_kses_post( $value ); ?></td>
                </tr>
                <?php
            }
            ?>
        </table>
    </div>
    <br><br><br>

    <div class="full_width_container">
        <h2>USER CAPABILITIES</h2>
        
        <table class="admin-large-table">
            <tr>
                <th style="width: 300px;">Capability</th>
                <th>Enabled</th>
            </tr>
            <?php
            foreach( $user->allcaps as $key => $value ) {
                if ( $value ) {
                    $value = '<span class="true">TRUE</span>';
                } else {
                    $value = '<span class="false">FALSE</span>';
                }
                ?>
                <tr>
                    <td><?php echo esc_attr( $key ); ?></td>
                    <td><?php echo wp_kses_post( $value ); ?></td>
                </tr>
                <?php
            }
            ?>
        </table>
    </div>
    <br><br><br>

    <script>
    // Radio buttons
    const selectUpdates = document.querySelectorAll( ".update_choice_input" );
    for( const selectUpdate of selectUpdates ) {

        // Unselect items on load
        selectUpdate.checked = false;

        // Show submit button for update form
        selectUpdate.onclick = function() {
            ddtt_show_hide_element( ".update_choice_input", ".post-update-button" );
        }
    }

    // Update Form
    const mkText = document.getElementById( "metakey-text" );
    const mkTextLabel = document.getElementById( "metakey-text-label" );
    const mkTextInput = document.getElementById( "update_meta_key_text" );
    const mkType = document.getElementById( "metakey-type" );
    const mkTypeSelect = document.getElementById( "update_meta_key_type" );
    const mkDelsChoice = document.getElementById( "metakey-dels-choice" );
    const mkDelsChoiceSelect = document.getElementById( "update_meta_key_dels_choice" );
    const mkValue = document.getElementById( "metakey-value" );
    const mkObjectSelect = document.getElementById( "metakey-object-select" );
    const mkObjectSelectInput = document.getElementById( "update_meta_key_object_select" );
    const mkCustomSelect = document.getElementById( "metakey-custom-select" );
    const mkCustomSelectInput = document.getElementById( "update_meta_key_custom_select" );
    
    // Prevent spaces in new meta keys
    jQuery( "#update_meta_key_text" ).keyup( function () {
        this.value = this.value.replace(/ /g, "_");
    } );

    // Conditional Logic
    <?php
    foreach ( $update_choices as $update_choice ) {
        ?>
        const update_<?php echo esc_attr( $update_choice[0] ); ?> = document.getElementById( "update_choice_<?php echo esc_attr( $update_choice[0] ); ?>" );
        update_<?php echo esc_attr( $update_choice[0] ); ?>.addEventListener( "change", function() {

            // Add
            if ( "<?php echo esc_attr( $update_choice[0] ); ?>" == 'add' ) {
                if ( update_<?php echo esc_attr( $update_choice[0] ); ?>.checked ) {
                    mkText.style.display = "revert";
                    mkType.style.display = "none";
                    mkValue.style.display = "revert";
                    mkObjectSelect.style.display = "none";
                    mkCustomSelect.style.display = "none";
                    mkDelsChoice.style.display = "none";

                    mkTextLabel.innerText = 'Meta Keys';
                    
                    mkTypeSelect.value = '';

                    mkTextInput.setAttribute( "name", "mk" );
                    mkObjectSelectInput.setAttribute( "name", "" );
                    mkCustomSelectInput.setAttribute( "name", "" );

                    mkTextInput.required = true;
                    mkTypeSelect.required = false;
                    mkObjectSelectInput.required = false;
                    mkCustomSelectInput.required = false;
                    mkDelsChoiceSelect.required = false;
                }
            }

            // Update
            if ( "<?php echo esc_attr( $update_choice[0] ); ?>" == 'upd' ) {
                if ( update_<?php echo esc_attr( $update_choice[0] ); ?>.checked ) {
                    mkText.style.display = "none";
                    mkType.style.display = "revert";
                    mkValue.style.display = "revert";
                    mkObjectSelect.style.display = "none";
                    mkCustomSelect.style.display = "none";
                    mkDelsChoice.style.display = "none";

                    mkTextInput.setAttribute( "name", "" );
                    mkObjectSelectInput.setAttribute( "name", "" );
                    mkCustomSelectInput.setAttribute( "name", "" );

                    mkTextInput.required = false;
                    mkTypeSelect.required = true;
                    mkDelsChoiceSelect.required = false;
                }
            }

            // Delete
            if ( "<?php echo esc_attr( $update_choice[0] ); ?>" == 'del' ) {
                if ( update_<?php echo esc_attr( $update_choice[0] ); ?>.checked ) {
                    mkText.style.display = "none";
                    mkType.style.display = "none";
                    mkValue.style.display = "none";
                    mkObjectSelect.style.display = "none";
                    mkCustomSelect.style.display = "revert";
                    mkDelsChoice.style.display = "none";

                    mkTypeSelect.value = '';

                    mkTextInput.setAttribute( "name", "" );
                    mkObjectSelectInput.setAttribute( "name", "" );
                    mkCustomSelectInput.setAttribute( "name", "mk" );

                    mkTextInput.required = false;
                    mkTypeSelect.required = false;
                    mkObjectSelectInput.required = false;
                    mkCustomSelectInput.required = true;
                    mkDelsChoiceSelect.required = false;
                }
            }

            // Delete Starting With
            if ( "<?php echo esc_attr( $update_choice[0] ); ?>" == 'dels' ) {
                if ( update_<?php echo esc_attr( $update_choice[0] ); ?>.checked ) {
                    mkText.style.display = "revert";
                    mkType.style.display = "none";
                    mkValue.style.display = "none";
                    mkObjectSelect.style.display = "none";
                    mkCustomSelect.style.display = "none";
                    mkDelsChoice.style.display = "revert";

                    mkTextLabel.innerText = 'All Custom Meta Keys Starting With';

                    mkTypeSelect.value = '';

                    mkTextInput.setAttribute( "name", "mk" );
                    mkObjectSelectInput.setAttribute( "name", "" );
                    mkCustomSelectInput.setAttribute( "name", "" );

                    mkTextInput.required = true;
                    mkTypeSelect.required = false;
                    mkObjectSelectInput.required = false;
                    mkCustomSelectInput.required = false;
                    mkDelsChoiceSelect.required = true;
                }
            }
        } );
        <?php
    }
    ?>

    // Show hide other select fields
    mkTypeSelect.addEventListener( "change", function() {
        if ( mkTypeSelect.value == 'object' ) {
            mkObjectSelect.style.display = "revert";
            mkCustomSelect.style.display = "none";

            mkObjectSelectInput.setAttribute( "name", "mk" );
            mkCustomSelectInput.setAttribute( "name", "" );

            mkObjectSelectInput.required = true;
            mkCustomSelectInput.required = false;

        } else if ( mkTypeSelect.value == 'custom' ) {
            mkObjectSelect.style.display = "none";
            mkCustomSelect.style.display = "revert";

            mkObjectSelectInput.setAttribute( "name", "" );
            mkCustomSelectInput.setAttribute( "name", "mk" );

            mkObjectSelectInput.required = false;
            mkCustomSelectInput.required = true;

        } else {
            mkObjectSelect.style.display = "none";
            mkCustomSelect.style.display = "none";

            mkObjectSelectInput.setAttribute( "name", "" );
            mkCustomSelectInput.setAttribute( "name", "" );

            mkObjectSelectInput.required = false;
            mkCustomSelectInput.required = false;
        }
    } );

    // Toggle function
    function ddtt_show_hide_element( $select_element, $element_to_toggle ) {
        const select = document.querySelector( $select_element );
        const element = document.querySelector( $element_to_toggle );
        if ( select.value && select.value != "" ) {
            element.style.display = "revert";
        } else {
            element.style.display = "none";
        }
    }
    </script>

    <?php
}