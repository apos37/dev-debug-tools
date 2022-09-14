<?php 
if ( is_plugin_active( 'dev-debug-tools/dev-debug-tools.php' ) ) {
    add_filter( 'ddtt_admin_bar_dropdown_links', 'ddtt_admin_bar_dropdown_links_filter' );
    function ddtt_admin_bar_dropdown_links_filter( $links ) {
        // Add your own links
        $links[] = [ 'Media', esc_url( '/'.DDTT_ADMIN_URL.'/upload.php' ) ];

        // Return the new links array
        return $links;
    }
}