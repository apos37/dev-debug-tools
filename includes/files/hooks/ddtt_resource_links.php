<?php 
if ( is_plugin_active( 'dev-debug-tools/dev-debug-tools.php' ) ) {
    add_filter( 'ddtt_resource_links', 'ddtt_resource_link_filter' );
    function ddtt_resource_link_filter( $links ) {
        // Add a new resource
        $links[] = [
            'title' => 'Google',
            'url'   => 'https://www.google.com',
            'desc'  => 'This is an example description.'
        ];

        // Return the new links array
        return $links;
    }
}