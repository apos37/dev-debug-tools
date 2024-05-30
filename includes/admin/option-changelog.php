<?php
/**
 * The changelog page
 */

// Include the header
include 'header.php';

// Get the dev timezone
$timezone = get_option( DDTT_GO_PF.'dev_timezone', wp_timezone_string() );

// Plugin installed date
if ( $installed_date = get_option( 'ddtt_plugin_installed' ) ) {
    $installed_date = ddtt_convert_timezone( $installed_date, 'F j, Y g:i A', $timezone );
    echo '<br>Plugin was installed/updated on '.esc_html( $installed_date );
}

// Plugin activated date
if ( $activated_date = get_option( 'ddtt_plugin_activated' ) ) {
    $activated_date = ddtt_convert_timezone( $activated_date, 'F j, Y g:i A', $timezone );
    echo '<br>Plugin was last activated on '.esc_html( $activated_date );
}

// Add CSS for just this page
echo '<style>h3 { margin-bottom: 0; }</style>';

// Fetch the changelog
$file = file_get_contents( DDTT_PLUGIN_ROOT . 'readme.txt' ); 
$changelog = strstr( $file, '= '. DDTT_VERSION .' =' );

// Replace the versions and bullets
$changelog = str_replace( '= ', '<h3>', $changelog );
$changelog = str_replace( ' =', '</h3>', $changelog );

// Add the content
echo '<br><br><br>'.wp_kses_post( nl2br($changelog) );