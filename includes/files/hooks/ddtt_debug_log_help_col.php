<?php 
if ( is_plugin_active( 'dev-debug-tools/dev-debug-tools.php' ) ) {
    add_filter( 'ddtt_debug_log_help_col', 'ddtt_debug_log_help_col_filter' );
    function ddtt_debug_log_help_col_filter( $search_links ) {
        // Remove Google Past Year
        unset( $search_links[ 'google_past_year' ] );

        // Add your own search link
        // Use anything you want as the array key; it is only used for reference
        // URL prefix must include the search parameter as the last parameter in the URL
        // Example: https://www.google.com/search?as_qdr=y&q= where q=these+are+the+keywords
        // Format must include at least one merge tag that is passed from the log line:
        //      {type} = "PHP Notice"
        //      {err}  = "Trying to get property 'xxx' of non-object"
        //      {path} = "/wp-content/plugins/woocommerce/templates/xxx.php"
        // If filter is set to 'plugin' or 'theme', it will only show if the line is a plugin or theme
        // If filter is set to 'path', it will only show if a path exists
        // Otherwise set filter to false to apply to all lines
        $search_links[ 'ask_jeeves' ] = [
            'name'   => 'Ask Jeeves',
            'url'    => 'https://askjeeves.net/results.html?q=',
            'format' => '{type}: {err}',
            'filter' => false
        ];

        // Return the new args array
        return $search_links;
    }
}