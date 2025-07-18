<?php 
$page = ddtt_plugin_options_short_path();
$tab = 'siteoptions';
$current_url = ddtt_plugin_options_path( $tab );

// Define the character limit
$char_limit = 1000;
?>

<style>
.full-value { display: none; }
.view-more {
    display: block;
    margin-top: 1rem;
    width: fit-content;
}
.ddtt-source-label { font-weight: bold; }
tr.ddtt-type-plugin .ddtt-source-label { color: #00bcd4; /* cyan */ }
tr.ddtt-type-mu-plugin .ddtt-source-label { color: #ff9800; /* orange */ }
tr.ddtt-type-theme .ddtt-source-label { color: #ba68c8; /* purple */ }
tr.ddtt-type-unknown .ddtt-source-label { color: #ef5350; /* red */ }

.bulk-delete-link.disable {
    color: white;
    background-color: #f44336; /* red */
    border-color: #f44336;
    border-radius: 6px;
    padding: 6px 12px;
    text-decoration: none;
}
</style>

<?php include 'header.php'; ?>

<?php
// Lookup
if ( ddtt_get( 'lookup' ) ) {

    // Sanitize it
    $lookup = sanitize_key( ddtt_get( 'lookup' ) );
    
    // Attempt to look up an option with that name
    $option = get_option( $lookup );

    // Display it
    echo '<br><h3>$'.esc_attr( $lookup ).' returns:</h3><br>';
    ddtt_print_r( $option );
    return;
}

// Are we in edit mode?
$in_edit_mode = ddtt_is_dev() && ddtt_get( 'edit_mode', '==', 1, 'ddtt_toggle_edit_mode', 'ddtt_edit_mode_nonce' );

// Handle bulk delete request
if ( $in_edit_mode && isset( $_POST[ 'bulk_delete' ], $_POST[ 'ddtt_bulk_delete' ] ) && is_array( $_POST[ 'ddtt_bulk_delete' ] ) ) {

    // Sanitize and filter values
    $options_to_delete = array_filter( array_map( 'sanitize_key', $_POST[ 'ddtt_bulk_delete' ] ) );
    foreach ( $options_to_delete as $option_name ) {

        // Skip protected options
        if ( str_starts_with( $option_name, 'ddtt_' ) ) {
            continue;
        }

        // Get source if available
        $option_source = $sources[ $option_name ] ?? '';

        if ( $option_source === 'Core (WordPress)' ) {
            continue;
        }

        delete_option( $option_name );
    }

    // Refresh sources and options list
    delete_transient( 'ddtt_option_sources' );

    // Show the notice
    ?>
    <div class="notice notice-error is-dismissible">
    <p><?php esc_html_e( 'Options deleted successfully: ', 'dev-debug-tools' ); ?><?php echo esc_html( implode( ', ', $options_to_delete ) ); ?>.</p>
    </div>
    <?php
}

// Get the options
global $wpdb;

$autoload_yes = wp_load_alloptions();
$autoload_no  = $wpdb->get_results(
    "SELECT option_name, autoload FROM $wpdb->options WHERE autoload = 'no'",
    OBJECT_K
);

$all_options = $autoload_yes;
foreach ( $autoload_no as $name => $obj ) {
    if ( !isset( $all_options[ $name ] ) ) {
        $all_options[ $name ] = get_option( $name );
    }
}

$option_autoload_status = [];
foreach ( $autoload_yes as $name => $val ) {
    $option_autoload_status[ $name ] = 'yes';
}
foreach ( $autoload_no as $name => $obj ) {
    $option_autoload_status[ $name ] = 'no';
}

// $sources = get_transient( 'ddtt_option_sources' );
$sources = false;
if ( !$sources ) {
    $sources = ddtt_detect_option_sources();
    set_transient( 'ddtt_option_sources', $sources, HOUR_IN_SECONDS );
}

$reg_options = get_registered_settings();
foreach ( $reg_options as $key => $r ) {
    if ( !array_key_exists( $key, $all_options ) ) {
        $all_options[ $key ] = get_option( $key );
    }
}

// Sort them
uksort( $all_options, function( $a, $b ) {
    return strcasecmp( $a, $b );
} );
?>

<table class="form-table">
    <?php if ( ddtt_is_dev() ) { ?>
        <tr valign="top">
            <th scope="row">All Site Options</th>
            <td><a href="/<?php echo esc_attr( DDTT_ADMIN_URL ); ?>/options.php" target="_blank">Open</a> &#9888; <em>Warning: This page allows direct access to your site settings. You can break things here. Please be cautious!</em></td>
        </tr>
    <?php } ?>
    <?php if ( ddtt_is_dev() ) { ?>
        <tr valign="top">
            <th scope="row">Bulk Delete</th>
            <?php
            $nonce_action   = 'ddtt_toggle_edit_mode';
            $nonce_param    = 'ddtt_edit_mode_nonce';
            $nonce_value    = wp_create_nonce( $nonce_action );

            if ( $in_edit_mode ) {
                $toggle_url   = remove_query_arg( [ 'edit_mode', $nonce_param ] );
                $toggle_label = 'Disable';
                $confirmation = '';
            } else {
                $toggle_url   = add_query_arg( [
                    'edit_mode'   => 1,
                    $nonce_param  => $nonce_value,
                ] );
                $toggle_label = 'Enable';
                $confirmation = 'onclick="return confirm(\'Are you sure you want to enable bulk delete? This can affect site settings.\');"';
            }

            ?>
            <td><a class="bulk-delete-link <?php echo esc_attr( strtolower( $toggle_label ) ); ?>" href="<?php echo esc_url( $toggle_url ); ?>" <?php echo wp_kses( $confirmation, [ 'onclick' => [] ] ); ?>><?php echo esc_html( $toggle_label ); ?></a> &#9888; <em>Warning: This allows you to delete your site settings in bulk, which can cause your site to break if you accidentally delete ones you shouldn't. Please be careful! It is highly recommended to back-up your site first.</em></td>
        </tr>
    <?php } ?>
    <tr valign="top">
        <th scope="row"><label for="option-search-input">Search Option by Keyword</label></th>
        <td><div class="search-options">
            <form method="get" action="<?php echo esc_url( $current_url ); ?>">
                <input type="hidden" name="page" value="<?php echo esc_attr( $page ); ?>">
                <input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>">
                <input type="text" name="lookup" id="option-search-input" value="" required>
                <input type="submit" value="Search" id="post-search-button" class="button button-primary"/>
            </form>
        </div></td>
    </tr>
</table>
<br><br>
<hr>
<br><br>

<h3>Total # of Options: <?php echo esc_html( count( $all_options ) ); ?></h3>

<p><strong>Note:</strong> Some options may be labeled as <em>Unknown Source</em>. This can happen because we cannot reliably determine the source for dynamically created or runtime-generated options. Options registered or used exclusively via dynamic code, custom hooks, or without static references in plugin or theme files may not be detected by the static scanning method. Additionally, some options might be remnants from old plugins or themes no longer in use, which also results in an unknown source.</p>

<div class="full_width_container">
    <?php if ( $in_edit_mode ) {
        $bulk_delete_url = add_query_arg( [
            'edit_mode'  => 1,
            $nonce_param => $nonce_value,
        ], $current_url );
        ?>
        <form id="bulk-delete-form" method="post" action="<?php echo esc_url( $bulk_delete_url ); ?>">
            <input type="hidden" name="bulk_delete" value="1">
            <input type="submit" class="button button-primary" value="Delete Selected Options">
            <p>Select options to delete and click the button above. This action cannot be undone!</p>
    <?php } ?>
    <table class="admin-large-table">
        <tr>
            <?php if ( $in_edit_mode ) { ?>
                <th style="width: 30px;">Delete</th>
            <?php } ?>
            <th style="width: 300px;">Registered Setting/Option</th>
            <th style="width: 300px;">Option Details</th>
            <th>Value</th>
        </tr>
        <?php
        // Allowed kses
        $allowed_html = [ 
            'pre'  => [], 
            'br'   => [], 
            'code' => [], 
            'a'    => [ 
                'href'  => [], 
                'class' => [] 
            ], 
            'span' => [ 
                'class' => [], 
                'style' => [] 
            ]
        ];

        // Predetermined prefixes
        $prefixes = [
            'ddtt_'                   => 'Plugin: Developer Debug Tools',
            'blnotifier_'             => 'Plugin: Broken Link Notifier',
            'clear_cache_everywhere_' => 'Plugin: Clear Cache Everywhere',
            'cscompanion_'            => 'Plugin: Cornerstone Companion',
            'cornerstone_'            => 'Plugin: Cornerstone',
            'css-organizer-'          => 'Plugin: CSS Organizer',
            'erifl-'                  => 'Plugin: ERI File Library',
            'gfat_'                   => 'Plugin: Advanced Tools for Gravity Forms',
            'gravityformsaddon_'      => 'Plugin: A Gravity Forms Add-On',
            'helpdocs_'               => 'Plugin: Admin Help Docs',
            'role_visibility_'        => 'Plugin: Role Visibility',
            'uamonitor_'              => 'Plugin: User Account Monitor',
            'wcagaat_'                => 'Plugin: WCAG Admin Accessibility Tools',
            'wp_mail_logging_'        => 'Plugin: WP Mail Logging',
            'wp_mail_smtp_'           => 'Plugin: WP Mail SMTP',
        ];
        if ( is_plugin_active( 'gravityforms/gravityforms.php' ) ) {
            $prefixes[ 'gf_' ] = 'Plugin: Gravity Forms';
            $prefixes[ 'gform_' ] = 'Plugin: Gravity Forms';
        }
        $prefixes = apply_filters( 'ddtt_site_option_prefixes', $prefixes );

        // Loop through all options
        foreach ( $all_options as $option => $value ) {
            // Registered group or empty
            $group = isset( $reg_options[ $option ]['group'] ) ? $reg_options[ $option ]['group'] : '';

            // Autoload status or unknown
            $autoload = isset( $option_autoload_status[ $option ] ) ? $option_autoload_status[ $option ] : 'unknown';

            // Source and type
            $found_prefix = false;
            foreach ( $prefixes as $prefix => $prefix_source ) {
                if ( strpos( $option, $prefix ) === 0 ) {
                    $source = $prefix_source;
                    $type = 'plugin';
                    $found_prefix = true;
                    break;
                }
            }
            if ( ! $found_prefix ) {
                // fallback to existing logic:
                if ( isset( $sources[ $option ] ) ) {
                    $source = $sources[ $option ];
                    if ( str_starts_with( $source, 'Plugin: ' ) ) {
                        $type = 'plugin';
                    } elseif ( str_starts_with( $source, 'Mu-plugin: ' ) ) {
                        $type = 'mu-plugin';
                    } elseif ( str_starts_with( $source, 'Theme: ' ) ) {
                        $type = 'theme';
                    } elseif ( $source === 'Core (WordPress)' ) {
                        $type = 'core';
                    } else {
                        $type = 'unknown';
                    }
                } else {
                    $source = 'Unknown Source';
                    $type = 'unknown';
                }
            }

            // Prepare Option Details column
            $option_details = sprintf(
                '%s<br>Group: %s<br>Autoload: %s',
                '<span class="ddtt-source-label ddtt-type-' . esc_attr( $type ) . '">' . esc_html( $source ) . '</span>',
                esc_html( $group ?: '—' ),
                esc_html( $autoload )
            );

            // Format value display
            if ( is_array( $value ) ) {
                $display_value = '<pre>' . print_r( $value, true ) . '</pre>';
            } elseif ( ddtt_is_serialized_array( $value ) || ddtt_is_serialized_object( $value ) ) {
                $unserialized_value = @unserialize( $value );
                if ( is_string( $unserialized_value ) && ( ddtt_is_serialized_array( $unserialized_value ) || ddtt_is_serialized_object( $unserialized_value ) ) ) {
                    $unserialized_value = @unserialize( $unserialized_value );
                }
                $display_value = $value . '<br><code><pre>' . print_r( $unserialized_value, true ) . '</pre></code>';
            } elseif ( is_string( $value ) ) {
                $json_value = json_decode( $value, true );
                if ( json_last_error() === JSON_ERROR_NONE && ( is_array( $json_value ) || is_object( $json_value ) ) ) {
                    $display_value = $value . '<br><code><pre>' . print_r( $json_value, true ) . '</pre></code>';
                } else {
                    $display_value = $value;
                }
            } else {
                $display_value = $value;
            }

            // Character limit for display (1000 chars)
            $char_limit = 1000;
            if ( strlen( $display_value ) > $char_limit ) {
                $short_value = substr( $display_value, 0, $char_limit ) . '... ';
                $view_more_link = '<a href="#" class="view-more">View More</a>';
                $full_value = '<span class="full-value" style="display:none;">' . $display_value . '</span>';
                $display_value = $short_value . $full_value . $view_more_link;
            }
            ?>
            <tr class="ddtt-source-type ddtt-type-<?php echo esc_attr( $type ); ?>">
                <?php if ( $in_edit_mode ) { ?>
                    <td>
                        <?php
                        $is_ddtt   = str_starts_with( $option, 'ddtt_' );
                        $is_core   = ( $source === 'Core (WordPress)' );
                        $is_disabled = ( $is_ddtt || $is_core );
                        $disabled_reason = '';
                        if ( $is_ddtt ) {
                            $disabled_reason = 'This option belongs to Developer Debug Tools and cannot be deleted.';
                        } elseif ( $is_core ) {
                            $disabled_reason = 'This is a WordPress core option and cannot be deleted.';
                        }
                        ?>
                        <input type="checkbox"
                            name="ddtt_bulk_delete[]"
                            value="<?php echo esc_attr( $option ); ?>"
                            <?php disabled( $is_disabled ); ?>
                            <?php echo $is_disabled ? 'title="' . esc_attr( $disabled_reason ) . '"' : ''; ?>>
                    </td>
                <?php } ?>
                <td><span class="highlight-variable"><?php echo esc_html( $option ); ?></span></td>
                <td><?php echo wp_kses( $option_details, $allowed_html ); ?></td>
                <td><?php echo wp_kses( $display_value, $allowed_html ); ?></td>
            </tr>

            <?php
        }
        ?>
    </table>
    <?php if ( $in_edit_mode ) { ?>
        </form>
    <?php } ?>
</div>

<script>
jQuery( document ).ready( function( $ ) {
    $( '.view-more' ).on( 'click', function( e ) {
        e.preventDefault();
        var fullValue = $( this ).siblings( '.full-value' );
        if ( fullValue.is( ':hidden' ) ) {
            fullValue.show();
            $( this ).text( 'View Less' );
        } else {
            fullValue.hide();
            $( this ).text( 'View More' );
        }
    } );

    <?php if ( $in_edit_mode ) { ?>
        $( '#bulk-delete-form' ).on( 'submit', function( e ) {
            var checked = $( this ).find( 'input[name="ddtt_bulk_delete[]"]:checked' );
            if ( checked.length === 0 ) {
                alert( 'Please select at least one option to delete.' );
                e.preventDefault();
                return;
            }

            var selected = [];
            checked.each( function() {
                selected.push( $( this ).val() );
            } );

            var confirmed = confirm( 'Are you sure you want to delete the following options?\n\n- ' + selected.join( '\n- ' ) );
            if ( !confirmed ) {
                e.preventDefault();
            }
        } );
    <?php } ?>
} );
</script>