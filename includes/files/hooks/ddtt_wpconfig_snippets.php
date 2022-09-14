<?php
if ( is_plugin_active( 'dev-debug-tools/dev-debug-tools.php' ) ) {
    add_filter( 'ddtt_wpconfig_snippets', 'ddtt_wpconfig_snippets_filter' );
    function ddtt_wpconfig_snippets_filter( $snippets ) {
        // Add a new snippet
        $snippets[ 'post_revisions' ] = [
            'label' => 'Change Number of Post Revisions',
            'lines' => [
                [
                    'prefix' => 'define',
                    'variable' => 'WP_POST_REVISIONS',
                    'value' => 3
                ]
            ]
        ];

        // Remove a snippet
        unset( $snippets[ 'fs_method' ] );

        // Return the new snippet array
        return $snippets;
    }
}