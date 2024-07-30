<?php 
// Check for deleting transients
if ( ddtt_get( 'transients', '==', 'Delete All', 'clear_transients' ) ) {

    // Delete them
    if ( $delete = ddtt_delete_all_transients() ) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'All transients have been deleted from wp-options.', 'dev-debug-tools' ); ?></p>
        </div><br><br>
        <?php
    } else {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php esc_html_e( 'Sorry, but transients cannot be deleted.', 'dev-debug-tools' ); ?></p>
        </div><br><br>
        <?php
    }
    ddtt_remove_qs_without_refresh( [ 'transients', '_wpnonce' ] );

} elseif ( ddtt_get( 'transients', '==', 'Purge Expired', 'clear_transients' ) ) {

    // Delete them
    if ( $delete = ddtt_purge_expired_transients( '1 minute' ) ) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'All expired transients have been deleted from wp-options.', 'dev-debug-tools' ); ?></p>
        </div><br><br>
        <?php
    } else {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php esc_html_e( 'Sorry, but transients cannot be deleted.', 'dev-debug-tools' ); ?></p>
        </div><br><br>
        <?php
    }
    ddtt_remove_qs_without_refresh( [ 'transients', '_wpnonce' ] );
}

// The current url
$page = ddtt_plugin_options_short_path();
$tab = 'siteoptions';
$current_url = ddtt_plugin_options_path( $tab );
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
.expired {
    background: red;
    color: white;
}
</style>

<?php include 'header.php'; ?>

<p><strong>What are transients?</strong> Transients in WordPress are a way to cache data temporarily with an expiration time. They help improve performance by storing frequently accessed or expensive-to-generate data in the database, which reduces the need for repeated processing or queries.</p>
<br>

<form method="get" action="<?php echo esc_url( $current_url ); ?>">
    <?php wp_nonce_field( 'clear_transients', '_wpnonce', false ); ?>
    <input type="hidden" name="page" value="<?php echo esc_attr( $page ); ?>">
    <input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>">
    <input type="submit" name="transients" value="Delete All" id="delete-all-transients" class="button button-primary"/>
    <input type="submit" name="transients" value="Purge Expired" id="delete-exp-transients" class="button button-primary"/>
</form>

<br>

<?php
// Get the transients
$all_transients = ddtt_get_all_transients();
?>

<div class="full_width_container">
    <table class="admin-large-table">
        <tr>
            <th style="width: 300px;">Transient Name</th>
            <th style="width: 300px;">Timeout</th>
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

        // Define the character limit
        $char_limit = 1000;

        // Cycle through them
        foreach ( $all_transients as $name => $data ) {
            
            // Timeout
            if ( isset( $data[ 'timeout' ] ) ) {
                $timeout = gmdate( 'Y-m-d H:i:s', $data[ 'timeout' ] );
                if ( get_option( DDTT_GO_PF.'dev_timezone' ) && get_option( DDTT_GO_PF.'dev_timezone' ) != '' ) {
                    $dev_timezone = sanitize_text_field( get_option( DDTT_GO_PF.'dev_timezone' ) );
                } else {
                    $dev_timezone = wp_timezone_string();
                }
                
                $timeout_timestamp = strtotime( $timeout );
                $timeout = ddtt_convert_timezone( $timeout, 'F j, Y g:i A', $dev_timezone );
                if ( $timeout_timestamp < time() ) {
                    $timeout .= ' (EXPIRED)';
                    $class = 'expired';
                } else {
                    $class = 'current';
                }
            } else {
                $timeout = 'N/A';
                $class = 'no-timeout';
            }
            
            // Get the value
            $value = isset( $data[ 'value' ] ) ? $data[ 'value' ] : '';

            // Check if the value is an array
            if ( is_array( $value ) ) {
                $display_value = '<pre>'.print_r( $value, true ).'</pre>';

            // Check if the value is serialized
            } elseif ( ddtt_is_serialized_array( $value ) ) {
                $unserialized_value = @unserialize( $value );
                if ( is_string( $unserialized_value ) && ddtt_is_serialized_array( $unserialized_value ) ) {
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
                <td><span class="highlight-variable"><?php echo esc_attr( $name ); ?></span></td>
                <td class="<?php echo esc_attr( $class ); ?>"><?php echo esc_attr( $timeout ); ?></td>
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