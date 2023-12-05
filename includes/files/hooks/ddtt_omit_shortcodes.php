<?php 
/**
 * Omit shortcodes from the admin bar shortcode finder.
 *
 * @param array $omits
 * @return array
 */
function ddtt_omit_shortcodes_filter( $omits ) {
    // Omit shortcodes starting with these keywords:
    // Ex: [shortcode_name param=""]
    $omits[] = 'shortcode_name';

    // Return the new omits array
    return $omits;
} // End ddtt_omit_shortcodes_filter()

add_filter( 'ddtt_omit_shortcodes', 'ddtt_omit_shortcodes_filter' );