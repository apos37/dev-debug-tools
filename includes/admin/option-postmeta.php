<style>
.metakey-tr {
    display: none;
}
.no-choice {
    display: none;
}
#value-warning {
    background: yellow;
    width: fit-content;
    color: black;
    font-weight: bold;
    padding: 5px;
    border-radius: 4px;
    display: none;
}
.full-value {
    display: none;
}
.view-more {
    display: block;
    margin-top: 1rem;
    width: fit-content;
}
.full_width_container .admin-large-table .child-comment td {
    font-style: italic;
}
</style>

<?php 
// Include the header
include 'header.php';

// Build the current url
$page = ddtt_plugin_options_short_path();
$tab = 'postmeta';
$current_url = ddtt_plugin_options_path( $tab );

// Define the character limit
$char_limit = 1000;

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
} elseif ( isset( $_POST[ 'post_id' ] ) && $_POST[ 'post_id' ] != '' ) {
    $post_id = filter_var( $_POST[ 'post_id' ], FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 1 ] ] );
    $searched = true;
}

// Are we returning a post not found notice?
if ( $searched && ( !$post_id || $post_id == 0 || !get_post_status( $post_id ) ) ) {
    ?>
    <div class="notice notice-error is-dismissible">
    <p><?php esc_html_e( 'Post ID cannot be found.', 'dev-debug-tools' ); ?></p>
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

    // Are we updating?
    if ( isset( $_POST[ 'update' ] ) && $_POST[ 'update' ] != '' &&
        isset( $_POST[ 'mk' ] ) && $_POST[ 'mk' ] != '' &&
        isset( $_POST[ '_wpnonce' ] ) && wp_verify_nonce( sanitize_text_field( wp_unslash ( $_POST[ '_wpnonce' ] ) ), 'update_post_meta' ) ) {

        // Run action
        do_action( 'ddtt_on_update_post_meta', $_POST );

        // Verify and sanitize
        $upd = sanitize_key( $_POST[ 'update' ] );
        $mk = sanitize_key( $_POST[ 'mk' ] );
        $type = isset( $_POST[ 'type' ] ) && $_POST[ 'type' ] !== '' ? sanitize_text_field( $_POST[ 'type' ] ) : false;
        $dels = isset( $_POST[ 'dels' ] ) && $_POST[ 'dels' ] !== '' ? sanitize_text_field( $_POST[ 'dels' ] ) : false;
        $format = isset( $_POST[ 'format' ] ) && $_POST[ 'format' ] !== '' ? sanitize_key( $_POST[ 'format' ] ) : false;
        $val = isset( $_POST[ 'val' ] ) ? wp_kses_post( $_POST[ 'val' ] ) : false;

        // Format value if array or object
        if ( $format == 'array' || $format == 'object' ) {
            $val = trim( $val );
            $val = stripslashes( $val );
            $val = preg_replace( '/,(\s*[\]}])/', '$1', $val );
            $assoc = $format == 'array';
            $decoded_val = json_decode( $val, $assoc );
            if ( json_last_error() === JSON_ERROR_NONE ) {
                $val = $decoded_val;
            } 

        // String format
        } else {
            
            // Check if it's a serialized array
            $test_val = trim( $val );
            $test_val = stripslashes( $test_val );
            if ( ddtt_is_serialized_array( $test_val ) || ddtt_is_serialized_object( $test_val ) ) {
                $val = unserialize( $test_val );
            }
        }
        
        // Success notice
        $success = '';

        // Add
        if ( $upd == 'add' && $val !== false ) {

            // Add it
            add_post_meta( $post_id, $mk, $val );

            // Success message
            /* Translators: 1: meta key */
            $success = sprintf( __( 'The meta key "%s" has been added.' ), $mk );

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
                            $val = gmdate( 'Y-m-d H:i:s', $timestamp );
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
                    }

                    // Update the post meta
                    if ( $good ) {
                        $post = array();
                        $post['ID'] = $post_id;
                        $post[ $mk ] = $val;
                        wp_update_post( $post );

                        // Success message
                        /* Translators: 1: meta key */
                        $success = sprintf( __( 'The meta key "%s" has been updated.' ), $mk );

                    } else {
                        /* Translators: 1: meta key, 2: error */
                        $notice = sprintf( __( 'There was a problem updating "%1$s": %2$s', 'dev-debug-tools' ), $mk, $err );
                        ?>
                        <div class="notice notice-error is-dismissible">
                            <p><?php 
                            /* Translators: 1: Notice */
                            echo esc_html( $notice ); ?></p>
                        </div>
                        <?php
                    }
                }
                
            } elseif ( $type == 'custom' ) {

                // Update it
                update_post_meta( $post_id, $mk, $val );

                // Success message
                /* Translators: 1: meta key */
                $success = sprintf( __( 'The meta key "%s" has been updated.' ), $mk );
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
            /* Translators: 1: meta key */
            $success = sprintf( __( 'The meta key "%s" has been deleted.' ), $mk );

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
                /* Translators: 1: keyword, 2: meta keys */
                $success = sprintf( __( 'All custom meta keys starting with "%1$s" have been removed: %2$s' ), $mk, implode( ', ', $dels_found ) );
            } else {
                /* Translators: 1: keyword */
                $notice = sprintf( __( 'There were no custom meta keys starting with "%s"', 'dev-debug-tools' ), $mk );
                ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php 
                    /* Translators: 1: Notice */
                    echo esc_html( $notice ); ?></p>
                </div>
                <?php
            }
        }

        // Display notice?
        if ( $success != '' ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html( $success ); ?></p>
            </div>
            <?php
        }
    }
}

// Are we hiding meta keys with a prefix
$hide_pf = '';
if ( isset( $_GET[ 'hide_pf' ] ) ) {
    $hide_pf = sanitize_text_field( $_GET[ 'hide_pf' ] );
    ddtt_remove_qs_without_refresh( 'hide_pf' );
} else {
    $hide_pf = get_option( DDTT_GO_PF.'post_meta_hide_pf', '' );
}
update_option( DDTT_GO_PF.'post_meta_hide_pf', $hide_pf );

// Are we redacting some info
$is_redacting = !get_option( DDTT_GO_PF.'view_sensitive_info' ) || get_option( DDTT_GO_PF.'view_sensitive_info' ) != 1;
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
            $recent_posts = wp_get_recent_posts( [ 
                'numberposts' => '1',
                'post_status' => 'publish',
                'post_type'   => 'post'
            ] );
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

        <?php
        // Get featured image
        if ( has_post_thumbnail( $post_id ) ) {
            $featured_image_url = get_the_post_thumbnail_url( $post_id, 'full' ); // Full size image URL
        }
        ?>
        <tr valign="top">
            <th scope="row"><label for="post-featured-image">Featured Image</label></th>
            <td>
                <?php if ( isset( $featured_image_url ) && !empty( $featured_image_url ) ) { ?>
                    <img src="<?php echo esc_url( $featured_image_url ); ?>" alt="Featured Image" style="max-width: 200px; max-height: 200px; object-fit: cover;">
                <?php } else { ?>
                    <em>None</em>
                <?php } ?>
            </td>
        </tr>

        <?php
        $post_url = rest_url( "wp/v2/posts/{$post_id}" );
        $rest_status = ddtt_check_url_status_code( $post_url );
        $rest_code = $rest_status[ 'code' ];
        $rest_text = $rest_status[ 'text' ];
        ?>
        <tr valign="top">
            <th scope="row"><label for="rest-url">API Rest URL</label></th>
            <td><a href="<?php echo esc_url( $post_url ); ?>" target="_blank"><?php echo esc_url( $post_url ); ?></a><br>Status: <?php echo esc_attr( $rest_code ); ?> — <?php echo esc_html( $rest_text ); ?></td>
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
        [ 'add', 'Add' ],
        [ 'upd', 'Update' ],
        [ 'del', 'Delete (Custom Meta Keys Only)' ],
        [ 'dels', 'Delete Custom Meta Keys Starting with Keyword' ],
    ];

    // Hidden Post ID
    $hidden_pid = '<input type="hidden" name="post_id" value="'.$post_id.'">';
    ?>

    <br><hr><br></br>
    <h2>Update Meta Key</h2>
    <br><div class="post-update-form">
    <form id="update-meta-form" method="post" action="<?php echo esc_url( $current_url.'&post_id='.absint( $post_id ) ); ?>">
        <table class="form-table">
            <tr valign="top">
                <th scope="row">What do you want to do?</th>
                <td>
                <?php 
                foreach ( $update_choices as $update_choice ) {
                    ?>
                    <div class="update_choice">
                        <input class="update_choice_input" name="update" type="radio" value="<?php echo esc_attr( $update_choice[0] ); ?>" id="update_choice_<?php echo esc_attr( $update_choice[0] ); ?>"> <label for="update_choice_<?php echo esc_attr( $update_choice[0] ); ?>"><?php echo esc_html( $update_choice[1] ); ?></label>
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
                    <option value="">-- Select One -- </option>
                    <option value="object">WP_POST OBJECT</option>
                    <option value="custom">POST CUSTOM METADATA</option>
                </select>
                <span class="field-desc break">Meta keys not listed are not available to edit due to safety vulnerabilities or the key is deprecated.</span></td>
            </tr>

            <tr valign="top" class="metakey-tr metakey-type-selects" id="metakey-object-select">
                <th scope="row"><label for="update_meta_key_object_select"><strong>Meta Key</strong> <span class="required-text">(Required)</span></label></th>
                <td><select name="" id="update_meta_key_object_select">
                    <option value="">-- Select a Meta Key -- </option>
                    <?php
                    foreach( $post as $key => $value ) {
                        if ( $key == 'ID' || $key == 'guid' ) {
                            continue;
                        }
                        ?>
                        <option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_attr( $key ); ?></option>
                        <?php
                    }
                    ?>
                </select></td>
            </tr>

            <tr valign="top" class="metakey-tr metakey-type-selects" id="metakey-custom-select">
                <th scope="row"><label for="update_meta_key_custom_select"><strong>Meta Key</strong> <span class="required-text">(Required)</span></label></th>
                <td><select name="" id="update_meta_key_custom_select">
                    <option value="">-- Select a Custom Meta Key -- </option>
                    <?php
                    foreach( $post_meta as $key => $value ) {
                        ?>
                        <option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_attr( $key ); ?></option>
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

            <tr valign="top" class="metakey-tr" id="metakey-format">
                <th scope="row"><label for="update_meta_key_format"><strong>Format</strong> <span class="required-text">(Required)</span>: </label></th>
                <td><select name="format" id="update_meta_key_format">
                    <option value="string">String</option>
                    <option value="array">Array - Enter Value as JSON String w/ Double Quotes</option>
                    <option value="object">Object - Enter Value as JSON String w/ Double Quotes</option>
                </select></td>
            </tr>

            <tr valign="top" class="metakey-tr" id="metakey-value">
                <th scope="row"><label for="update_meta_key_value"><strong>Value</strong><br><br><em>(IMPORTANT: Please be careful updating array and object values. Test adding and updating one first before updating something critical. It's also a good idea to copy the serialized data just in case. You can enter a serialized array or object as a string. THIS DOES NOT WORK WELL WITH COMBINED ARRAY OF OBJECTS OR ARRAYS IN OBJECTS, SO DON'T TRY IT!)</em></label></th>
                <td><textarea name="val" id="update_meta_key_value"></textarea>
                <div id="value-warning"></div></td>
            </tr>
        </table>

        <?php echo wp_kses( $hidden_path, $hidden_allowed_html ); ?>
        <?php echo wp_kses( $hidden_pid, $hidden_allowed_html ); ?>
        <?php wp_nonce_field( 'update_post_meta' ); ?>
        <div class="no-choice meta-update-button"><br><br><input type="submit" value="Update" id="meta-update-button" class="button button-primary"/></div>
    </form>
    </div>
    <br><br>

    <?php 
    // Check if we're clearing all tax terms
    if ( $ct = ddtt_get( 'clear_terms' ) &&
         isset( $_POST[ '_wpnonce' ] ) && wp_verify_nonce( sanitize_text_field( wp_unslash ( $_POST[ '_wpnonce' ] ) ), 'update_taxonomies' ) ) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php 
            /* Translators: 1: count, 2: post id */
            echo esc_html( sprintf( __( 'You have successfully cleared all %1$s taxonomy terms for post %2$s (see below).', 'dev-debug-tools' ) ), $ct, $post_id ); ?></p>
        </div>
        <?php

        // Clear them
        wp_set_post_terms( $post_id, [], $ct );
    }

    // Look up the terms
    $taxonomies = get_taxonomies( [], 'names' );

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
            <?php wp_nonce_field( 'update_taxonomies' ); ?>
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
                // Post author
                if ( $key == 'post_author' ) {
                    $user_id = $value;
                    $user = get_userdata( $user_id );
                    $value = $value . ' — <em>' . $user->display_name . ' (<span class="' . ( $is_redacting ? 'redact' : '' ) . '">' . $user->user_email . '</span>)</em>';
                }

                // Check if the value exceeds the character limit
                if ( strlen( $value ) > $char_limit ) {
                    if ( $key == 'post_content' ) {
                        $value = esc_html( $value );
                    } 
                    $short_value = substr( $value, 0, $char_limit ) . '... ';
                    $view_more_link = '<a href="#" class="view-more">View More</a>';
                    $full_value = '<span class="full-value">'.$value.'</span>';
                    $value = $short_value.$full_value.$view_more_link;
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
        <h2>POST CUSTOM METADATA</h2>
        
        <table class="admin-large-table">
            <tr>
                <th style="width: 300px;">Meta Key</th>
                <th>Meta Value</th>
            </tr>
            <?php
            foreach( $post_meta as $key => $value ) {
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

                // Get the value
                $key_desc = '';
                $value = $value[0];
                if ( ( ddtt_is_serialized_array( $value ) || ddtt_is_serialized_object( $value ) ) && !empty( unserialize( $value ) ) ) {
                    $value = $value.'<br><code><pre>'.print_r( unserialize( $value ), true ).'</pre></code>';
                } elseif ( $key == '_edit_lock' ) {
                    $key_desc = 'Last Edited Date';
                    list( $timestamp, $user_id ) = explode( ':', $value );
                    $value = $value . ' — <em>' . gmdate( 'F j, Y \a\t g:i A', (int) $timestamp ) . '</em>';
                } elseif ( $key == '_edit_last' ) {
                    $key_desc = 'Last Edited By';
                    $last_user = get_userdata( $value );
                    $value = $value . ' — <em>' . esc_html( $last_user->display_name ) . ' (<span class="' . $is_redacting ? 'redact' : '' . '">' . esc_html( $last_user->user_email ) . '</span>)</em>';
                } else {
                    $value = esc_html( $value );
                }

                // Check if the value exceeds the character limit
                if ( strlen( $value ) > $char_limit ) {
                    $short_value = substr( $value, 0, $char_limit ) . '... ';
                    $view_more_link = '<a href="#" class="view-more">View More</a>';
                    $full_value = '<span class="full-value">'.$value.'</span>';
                    $value = $short_value.$full_value.$view_more_link;
                }
                ?>
                <tr>
                    <td><span class="highlight-variable"><?php echo esc_attr( $key ); ?></span><?php echo wp_kses_post( $key_desc ? ' <em>(' . $key_desc . ')</em>' : '' ); ?></td>
                    <td><?php echo wp_kses_post( $value ); ?></td>
                </tr>
                <?php
            }
            ?>
        </table>
    </div>
    <br><br><br>

    <div class="full_width_container">
        <h2>POST TAXONOMIES</h2>

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
    <br><br><br>

    <div class="full_width_container">
        <h2>POST COMMENTS</h2>

        <table class="admin-large-table">
            <tr>
                <th style="width: 50px;">Comment ID</th>
                <th style="width: 100px;">Reply?</th>
                <th style="width: 200px;">Date</th>
                <th>Content</th>
                <th style="width: 200px;">Author</th>
                <th style="width: 70px;">Approved</th>
                <th style="width: 50px;">Karma</th>
            </tr>
            
            <?php
            // Fetch comments for the current post
            $comments = get_comments( [
                'post_id' => $post_id,
                'orderby' => 'comment_date',
                'order'   => 'ASC',
            ] );

            if ( $comments ) {
                foreach ( $comments as $comment ) {
                    $comment_class = !empty( $comment->comment_parent ) ? 'child-comment' : 'parent-comment';
                    ?>
                    <tr class="<?php echo esc_attr( $comment_class); ?>">
                        <td><?php echo esc_attr( $comment->comment_ID ); ?></td>
                        <td><?php echo wp_kses_post( $comment->comment_parent ? 'Replying to ' . $comment->comment_parent : 'No' ); ?></td>
                        <td><?php echo esc_html( gmdate( 'n/j/Y \a\t g:i A', strtotime( $comment->comment_date ) ) ); ?></td>
                        <td><?php echo wp_kses_post( $comment->comment_content ); ?></td>
                        <td>
                            <?php echo esc_html( $comment->comment_author ); ?><br>
                            <span class="<?php echo esc_attr( $is_redacting ? 'redact' : '' ); ?>"><?php echo esc_html( $comment->comment_author_email ); ?></span><br>
                            User ID: <?php echo esc_html( $comment->user_id ); ?><br>
                            IP: <span class="<?php echo esc_attr( $is_redacting ? 'redact' : '' ); ?>"><?php echo esc_html( $comment->comment_author_IP ); ?></span>
                        </td>
                        <td style="text-align: center;"><?php echo esc_html( $comment->comment_approved ? 'Yes' : 'No' ); ?></td>
                        <td style="text-align: center;"><?php echo esc_attr( $comment->karma ? $comment->karma : 0 ); ?></td>
                    </tr>
                <?php }
            } else { ?>
                <tr>
                    <td colspan="7">No comments found.</td>
                </tr>
            <?php } ?>
        </table>
    </div>


    <?php
}