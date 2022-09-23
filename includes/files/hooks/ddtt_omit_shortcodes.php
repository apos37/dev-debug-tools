<?php 
if ( is_plugin_active( 'dev-debug-tools/dev-debug-tools.php' ) ) {
    add_filter( 'ddtt_omit_shortcodes', 'ddtt_omit_shortcodes_filter' );
    function ddtt_omit_shortcodes_filter( $omits ) {
        // Omit shortcodes starting with these keywords:
        // Ex: [shortcode_name param=""]
        $omits[] = 'shortcode_name';

        // Return the new omits array
        return $omits;
    }
}