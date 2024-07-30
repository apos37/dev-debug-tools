<?php 
$page = ddtt_plugin_options_short_path();
$tab = 'siteoptions';
$current_url = ddtt_plugin_options_path( $tab );

// Define the character limit
$char_limit = 1000;
?>

<style>
.full-value {
    display: none;
}
.view-more {
    display: block;
    margin-top: 1rem;
    width: fit-content;
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
?>

<table class="form-table">
    <?php if ( ddtt_is_dev() ) { ?>
        <tr valign="top">
            <th scope="row">All Site Options</th>
            <td><a href="/<?php echo esc_attr( DDTT_ADMIN_URL ); ?>/options.php" target="_blank">Open</a> &#9888; <em>Warning: This page allows direct access to your site settings. You can break things here. Please be cautious!</em></td>
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
<?php
// Get the options
$all_options = wp_load_alloptions();
$reg_options = get_registered_settings();

// Let's add any missing registered options to the all options array
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

<div class="full_width_container">
    <table class="admin-large-table">
        <tr>
            <th style="width: 300px;">Registered Setting/Option</th>
            <th style="width: 300px;">Setting Group</th>
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

        // Cycle through the options
        foreach ( $all_options as $option => $value ) {

            // Get the group
            if ( in_array( $option, array_keys( $reg_options ) ) ) {
                $group = $reg_options[ $option ][ 'group' ];
            } else {
                $group = '';
            }

            // Check if the value is an array
            if ( is_array( $value ) ) {
                $display_value = '<pre>'.print_r( $value, true ).'</pre>';

            // Check if the value is serialized
            } elseif ( ddtt_is_serialized_array( $value ) || ddtt_is_serialized_object( $value ) ) {
                $unserialized_value = @unserialize( $value );
                if ( is_string( $unserialized_value ) && ( ddtt_is_serialized_array( $unserialized_value ) || ddtt_is_serialized_object( $unserialized_value ) ) ) {
                    $unserialized_value = @unserialize( $unserialized_value );
                }
                $display_value = $value.'<br><code><pre>'.print_r( $unserialized_value, true ).'</pre></code>';

            // Check if the value is JSON
            } elseif ( is_string( $value ) ) {
                $json_value = json_decode( $value, true );
                if ( json_last_error() === JSON_ERROR_NONE && ( is_array( $json_value ) || is_object( $json_value ) ) ) {
                    $display_value = $value.'<br><code><pre>'.print_r( $json_value, true ).'</pre></code>';
                } else {
                    $display_value = $value;
                }

            // Default case
            } else {
                $display_value = $value;
            }

            // Check if the value exceeds the character limit
            if ( strlen( $display_value ) > $char_limit ) {
                $short_value = substr( $display_value, 0, $char_limit ) . '... ';
                $view_more_link = '<a href="#" class="view-more">View More</a>';
                $full_value = '<span class="full-value">'.$display_value.'</span>';
                $display_value = $short_value.$full_value.$view_more_link;
            }
            ?>
            <tr>
                <td><span class="highlight-variable"><?php echo esc_attr( $option ); ?></span></td>
                <td><?php echo esc_attr( $group ); ?></td>
                <td><?php echo wp_kses( $display_value, $allowed_html ); ?></td>
            </tr>
            <?php
        }
        ?>
    </table>
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
} );
</script>