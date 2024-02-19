<style>
.metakey-tr {
    display: none;
}
.no-choice {
    display: none;
}
</style>

<?php include 'header.php'; ?>

<?php 
// Build the current url
$page = ddtt_plugin_options_short_path();
$tab = 'postmeta';
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
$valid_search = false;

// Default value
$val = false;

// Get the post
$post_id = 0;
$notice = '';
if ( ddtt_get( 'post_id' ) ) {
    $post_id = filter_var( ddtt_get( 'post_id' ), FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 1 ] ] );
    $searched = true;
} elseif ( isset( $_POST['post_id'] ) && $_POST['post_id'] != '' ) {
    $post_id = filter_var( $_POST['post_id'], FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 1 ] ] );
    $searched = true;
}

// Are we returning a user not found notice?
if ( $searched && ( !$post_id || $post_id == 0 || !get_post_status( $post_id ) ) ) {
    ?>
    <div class="notice notice-error is-dismissible">
    <p><?php _e( 'Post ID cannot be found.', 'dev-debug-tools' ); ?></p>
    </div>
    <?php

// Do we have a valid post id for updating?
} else {

    // Mark valid
    $valid_search = true;

    // Empty vars
    $upd = '';
    $mk = '';
    $type = '';

    // Sanitize $_POST
    if ( $_POST ) {
        $_POST = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
    }

    // Are we updating?
    if ( isset( $_POST[ 'update' ] ) && $_POST[ 'update' ] != '' &&
        isset( $_POST[ 'mk' ] ) && $_POST[ 'mk' ] != '' ) {

        // Run action
        do_action( 'ddtt_on_update_post_meta', $_POST );

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
            add_post_meta( $post_id, $mk, $val );

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

                    // Strings
                    $strings = [
                        'post_title',
                        'post_excerpt',
                        'post_name',
                    ];

                    // Dates
                    $dates = [
                        'post_date',
                        'post_date_gmt',
                        'post_modified',
                        'post_modified_gmt'
                    ];

                    // Open/Close
                    $open_close = [
                        'comment_status',
                        'ping_status'
                    ];

                    // Post Status
                    global $wp_post_statuses;
                    $statuses = array_keys( $wp_post_statuses );

                    // Post Types
                    $post_types = get_post_types();

                    // Post Author
                    if ( $mk == 'post_author' ) {
                        $val = filter_var( $val, FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 1 ] ] );
                        if ( $val > 0 && get_userdata( $val ) ) {
                            $good = true;
                        } else {
                            $err = 'Invalid User ID.';
                        }

                    // Dates
                    } elseif ( in_array( $mk, $dates ) ) {
                        if ( $timestamp = strtotime( $val ) ) {
                            $val = date( 'Y-m-d H:i:s', $timestamp );
                            $good = true;
                        } else {
                            $err = 'Invalid date format. Please use: "Y-m-d H:i:s" or "m/d/Y g:i AM" (if you do not include a time, it will default to 0:00:00.';
                        }

                    // Strings
                    } elseif ( in_array( $mk, $strings ) ) {
                        if ( is_string( $val ) ) {
                            $good = true;
                        } else {
                            $err = 'Must be a string';
                        }
                        
                    // Post Status
                    } elseif ( $mk == 'post_status' ) {
                        if ( in_array( strtolower( $val ), $statuses ) ) {
                            $good = true;
                        } else {
                            $statuses_f = [];
                            foreach ( $statuses as $key => $status ) {
                                $statuses_f[] = '"'.$status.'"';
                            }
                            $err = 'Please use one of the following post statuses: '.implode( ', ', $statuses_f );
                        }

                    // Post Type
                    } elseif ( $mk == 'post_type' ) {
                        if ( in_array( $val, $post_types ) ) {
                            $good = true;
                        } else {
                            $types_f = [];
                            foreach ( $post_types as $key => $post_type ) {
                                $types_f[] = '"'.$post_type.'"';
                            }
                            $err = 'Please use one of the following post types: '.implode( ', ', $types_f );
                        }
                    
                    // Post Parent
                    } elseif ( $mk == 'post_parent' ) {
                        $val = filter_var( $val, FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 1 ] ] );
                        if ( get_post_status( $val ) ) {
                            $good = true;
                        } else {
                            $err = 'Invalid Post ID.';
                        }

                    // Menu Order
                    } elseif ( $mk == 'menu_order' ) {
                        $val = filter_var( $val, FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 1 ] ] );
                        if ( $val ) {
                            $good = true;
                        } else {
                            $err = 'Must use a positive number to order menu items.';
                        }    

                    // Open or Close
                    } elseif ( in_array( $mk, $open_close ) ) {
                        if ( strtolower( $val ) == 'open' || strtolower( $val ) == 'closed' ) {
                            $good = true;
                        } else {
                            $err = 'Please use "open" or "closed".';
                        }

                    // Everything else
                    } else {
                        $mk = sanitize_meta( $mk, $val, 'post' );
                    }

                    // Update the post meta
                    if ( $good ) {
                        $post = array();
                        $post['ID'] = $post_id;
                        $post[ $mk ] = $val;
                        wp_update_post( $post );

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
                update_post_meta( $post_id, $mk, $val );

                // Success message
                $success = 'The meta key "'.$mk.'" has been updated.';
            }

        // Delete
        } elseif ( $upd == 'del' ) {
            
            // Get the post meta
            $post_meta = get_post_meta( $post_id );

            // Make sure the key exists as custom meta only
            if ( !key_exists( $mk, $post_meta ) ) {
                return 'The custom meta key "'.$mk.'" does not exist.';
            }

            // Delete it
            delete_post_meta( $post_id, $mk );

            // Success message
            $success = 'The meta key "'.$mk.'" has been deleted.';

        // Delete Meta Keys Starting with Keyword
        } elseif ( $upd == 'dels' && $dels ) {

            // Get the post meta
            $post_meta = get_post_meta( $post_id );

            // Did we find any?
            $dels_found_blank = 0;
            $dels_found_all = 0;
            $dels_found = [];

            // Cycle
            foreach( $post_meta as $key => $value ) {

                // Check it
                if ( str_starts_with( $key, $mk ) ) {

                    // Blanks only?
                    if ( $dels == 'blank' && $value[0] == '' ) {

                        // Delete it
                        delete_post_meta( $post_id, $key );

                        // Found
                        $dels_found_blank++;
                        $dels_found[] = '"'.$key.'"';

                    } elseif ( $dels == 'all' ) {
                        
                        // Delete it
                        delete_post_meta( $post_id, $key );

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

// Are we hiding meta keys with a prefix
$hide_pf = ddtt_get( 'hide_pf' );
if ( $hide_pf ) {
    update_option( DDTT_GO_PF.'post_meta_hide_pf', $hide_pf );
    ddtt_remove_qs_without_refresh( 'hide_pf' );
} else {
    $hide_pf = get_option( DDTT_GO_PF.'post_meta_hide_pf' );
}
?>

<form method="get" action="<?php echo esc_url( $current_url ); ?>">
    <table class="form-table">

        <?php
        // Check that the id has been provided and is valid
        if ( $post_id && $post_id > 0 ) {

            // Get the post from the search
            if ( get_post_status ( $post_id ) ) {

                // Add the suggested post
                ?>
                <tr valign="top">
                    <th scope="row">Post Title</th>
                    <td><a href="<?php echo esc_url( get_the_permalink( $post_id ) ); ?>" target="_blank"><?php echo esc_html( get_the_title( $post_id ) ); ?></a></td>
                </tr>
                <?php

            }

        } else {

            // Get most recent post
            $recent_posts = wp_get_recent_posts( array( 
                'numberposts' => '1',
                'post_status' => 'publish',
                'post_type' => 'post'
            ));
            if ( !empty( $recent_posts ) ) {
                $most_recent_post = $recent_posts[0];
                $post_id = $most_recent_post['ID'];

                // Add the suggested post
                ?>
                <tr valign="top">
                    <th scope="row">Most Recent Post</th>
                    <td><a href="<?php echo esc_url( get_the_permalink( $post_id ) ); ?>" target="_blank"><?php echo esc_html( get_the_title( $post_id ) ); ?></a></td>
                </tr>
                <?php
            }
        }
        ?>

        <!-- Search Form -->
        <tr valign="top">
            <th scope="row"><label for="post-search-input">Post ID</label></th>
            <td><input type="text" name="post_id" id="post-search-input" value="<?php echo absint( $post_id ); ?>" style="width: 10rem;" required></td>
        </tr>
        <tr valign="top">
            <th scope="row"><label for="hide-meta-keys">Hide Meta Keys with Prefixes</label></th>
            <td><input type="text" name="hide_pf" id="hide-meta-keys" value="<?php echo esc_attr( $hide_pf ); ?>"></td>
        </tr>
    </table>
    <?php echo wp_kses( $hidden_path, $hidden_allowed_html ); ?>
    <br><br><input type="submit" value="Search" id="post-search-button" class="button button-primary"/>
</form>
<br><br>

<?php
if ( $valid_search ) {

    // Make sure we have a valid post id
    if ( $post_id == 0 ) {
        return;
    }

    // Get the post object
    $post = get_post( $post_id );
    
    // Get the meta (again if we were deleting keys that start with)
    $post_meta = get_post_meta( $post_id );

    // Sort the meta alphabetically
    ksort( $post_meta );

    // Which choices do we have for updating?
    $update_choices = [
        ['add', 'Add'],
        ['upd', 'Update'],
        ['del', 'Delete (Custom Meta Keys Only)'],
        ['dels', 'Delete Custom Meta Keys Starting with Keyword'],
    ];

    // Hidden Post ID
    $hidden_pid = '<input type="hidden" name="post_id" value="'.$post_id.'">';
    ?>

    <br><hr><br></br>
    <h2>Update Meta Key</h2>
    <br><div class="post-update-form">
    <form method="post" action="<?php echo esc_url( $current_url.'&post_id='.absint( $post_id ) ); ?>">
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
                    <option value="object">WP_POST OBJECT</option>
                    <option value="custom">POST CUSTOM METADATA</option>
                </select>
                <span class="object_keys_notice"><br>// Meta keys not listed are not available to edit due to safety vulnerabilities or the key is deprecated.</span></td>
            </tr>

            <tr valign="top" class="metakey-tr metakey-type-selects" id="metakey-object-select">
                <th scope="row"><label for="update_meta_key_object_select"><strong>Meta Key</strong> <span class="required-text">(Required)</span></label></th>
                <td><select name="" id="update_meta_key_object_select">
                    <option value="">-- Select a Meta Key-- </option>
                    <?php
                    foreach( $post as $key => $value ) {
                        if ( $key == 'ID' || $key == 'guid' ) {
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
                    foreach( $post_meta as $key => $value ) {
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
                <td><textarea name="val" id="update_meta_key_value"><?php echo wp_kses_post( $val ) ?? ''; ?></textarea></td>
            </tr>
        </table>

        <?php echo wp_kses( $hidden_path, $hidden_allowed_html ); ?>
        <?php echo wp_kses( $hidden_pid, $hidden_allowed_html ); ?>
        <div class="no-choice post-update-button"><br><br><input type="submit" value="Update" id="post-update-button" class="button button-primary"/></div>
    </form>
    </div>
    <br><br>

    <?php 
    // Check if we're clearing all tax terms
    if ( $ct = ddtt_get( 'clear_terms' ) ) {
        ?>
        <div class="notice notice-success is-dismissible">
        <p><?php _e('You have successfully cleared all '.$ct.' taxonomy terms for post '.$post_id.' (see below).', 'dev-debug-tools' ); ?></p>
        </div>
        <?php

        // Clear them
        wp_set_post_terms( $post_id, array(), $ct );
    }

    // Look up the terms
    $taxonomies = get_taxonomies( array(), 'names' );

    // Clear Terms Form
    ?>
    <br><hr><br></br>
    <h2>Clear Taxonomy Terms</h2>
    <br><div class="clear_terms_field">
        <form method="get" action="<?php echo esc_url( $current_url ); ?>">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="clear-terms-field">Select Taxonomy</label></th>
                    <td><select name="clear_terms" id="clear-terms-field">
                        <option value="">-- Select One --</option>';

                        <?php
                        foreach ( $taxonomies as $taxonomy ) {
                            ?>
                            <option value="<?php echo esc_attr( $taxonomy ); ?>"><?php echo esc_html( $taxonomy ); ?></option>
                            <?php
                        }
                        ?>
                    </select></td>
                </tr>
            </table>
            
            <?php echo wp_kses( $hidden_path, $hidden_allowed_html ); ?>
            <?php echo wp_kses( $hidden_pid, $hidden_allowed_html ); ?>
            <div class="no-choice clear-taxonomies-button"><br><br><input type="submit" value="Clear All" id="clear-taxonomies-button" class="button button-primary"/></div>
        </form>
    </div>
    <br>

    <!-- The tables -->
    <br><br><hr><br></br>
    <div class="full_width_container">
        <h2>WP_POST OBJECT</h2>
        
        <table class="admin-large-table">
            <tr>
                <th style="width: 300px;">Meta Key</th>
                <th>Meta Value</th>
            </tr>
            <?php
            foreach( $post as $key => $value ) {
                ?>
                <tr>
                    <td><span class="highlight-variable"><?php echo esc_attr( $key ); ?></span></td>
                    <td><?php echo esc_html( $value ); ?></td>
                </tr>
                <?php
            }
            ?>
        </table>
    </div>
    <br><br><br>

    <div class="full_width_container">
        <h2>POST CUSTOM METADATA</h2>
        
        <table class="admin-large-table">
            <tr>
                <th style="width: 300px;">Meta Key</th>
                <th>Meta Value</th>
            </tr>
            <?php
            foreach( $post_meta as $key => $value ) {
                $value = $value[0];
                if ( ddtt_is_serialized_array( $value ) && !empty( unserialize( $value ) ) ) {
                    $value = $value.'<br><code><pre>'.print_r( unserialize( $value ), true ).'</pre></code>';
                } else {
                    $value = esc_html( $value );
                }

                // Hide prefix
                $hide_this = false;
                if ( $hide_pf ) {
                    if ( strpos( $hide_pf, ',' ) !== false ) {
                        $pfs = explode( ',', $hide_pf );
                        foreach ( $pfs as $pf ) {
                            $pf = trim( $pf );
                            if ( str_starts_with( $key, $pf ) ) {
                                $hide_this = true;
                                break;
                            }
                        }
                    } elseif ( str_starts_with( $key, $hide_pf ) ) {
                        $hide_this = true;
                    }
                }
                if ( $hide_this ) {
                    continue;
                }
                ?>
                <tr>
                    <td><span class="highlight-variable"><?php echo esc_attr( $key ); ?></span></td>
                    <td><?php echo wp_kses_post( $value ); ?></td>
                </tr>
                <?php
            }
            ?>
        </table>
    </div>
    <br><br><br>

    <div class="full_width_container">
        <table class="admin-large-table">
            <tr>
                <th style="width: 300px;">Taxonomy</th>
                <th>Terms (Name | Description)</th>
            </tr>
            <?php
            foreach ( $taxonomies as $taxonomy ) {  
                $terms = get_the_terms( $post_id, $taxonomy );

                if ( !empty( $terms ) ) {
                    $term_display = '<ul style="margin: 0">';
                    foreach ( $terms as $term ) {
                        if ( $term->description && $term->description != '' ) {
                            $desc = $term->description;
                        } else {
                            $desc = '<em style="color: red">null</em>';
                        }
                        $term_display .= '<li style="margin: 0">Term ID #<a href="/'.DDTT_ADMIN_URL.'/term.php?taxonomy='.esc_attr( $taxonomy ).'&tag_ID='.$term->term_id.'">'.$term->term_id.'</a> -> '.esc_attr( $term->name ).' | '.$desc.'</li>';
                    }
                    $term_display .= '</ul>';
                } else {
                    $term_display = '';
                }
                $tax_allowed_html = [
                    'ul' => [
                        'style' => []
                    ],
                    'li' => [
                        'style' => []
                    ],
                    'em' => [
                        'style' => []
                    ],
                    'a' => [
                        'href' => []
                    ],
                ];
                ?>
                <tr>
                    <td><a href="/<?php echo esc_attr( DDTT_ADMIN_URL ); ?>/term.php?taxonomy=<?php echo esc_attr( $taxonomy ); ?>"><?php echo esc_attr( $taxonomy ); ?></a></td>
                    <td><?php echo wp_kses( $term_display, $tax_allowed_html ); ?></td>
                </tr>
                <?php
            }
            ?>
        </table>
    </div>

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

    // Show submit button for clear tax terms
    const selectTax = document.getElementById( "clear-terms-field" );
    selectTax.addEventListener( "change", function() {
        ddtt_show_hide_element( "#clear-terms-field", ".clear-taxonomies-button" );
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