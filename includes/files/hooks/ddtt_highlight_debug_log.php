<?php 
if ( is_plugin_active( 'dev-debug-tools/dev-debug-tools.php' ) ) {
    add_filter( 'ddtt_highlight_debug_log', 'ddtt_highlight_debug_log_filter' );
    function ddtt_highlight_debug_log_filter( $args ) {
        // Remove theme highlighting
        unset( $args[ 'theme' ] );

        // Highlight a debug line that contains a keyword
        // Example: /wp-content/plugins/plugin-slug/my-plugin-file.php
        // The keyword can be "plugin-slug" or "plugin-slug/my-plugin-file.php" if column is set to 'path'
        // The $args key is used as the table row css class
        $args[ 'my-plugin' ] = [
            'name'          => 'My Plugin',     // Name that shows on the debug log key
            'keyword'       => 'plugin-slug',   // Keyword to search in the error string
            'bg_color'      => '#FFE800',       // Background color (accepts any css color)
            'font_color'    => '#000000',       // Font color (accepts any css color)
            'priority'      => true,            // Set true to prioritize over items that are false
            'column'        => 'path'           // What to search (accepts 'err', 'path' and 'type')
        ];

        // Return the new args array
        return $args;
    }
}