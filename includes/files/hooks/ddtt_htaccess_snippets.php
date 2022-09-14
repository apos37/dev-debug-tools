<?php
if ( is_plugin_active( 'dev-debug-tools/dev-debug-tools.php' ) ) {
    add_filter( 'ddtt_htaccess_snippets', 'ddtt_htaccess_snippets_filter' );
    function ddtt_htaccess_snippets_filter( $snippets ) {
        // Add a new snippet
        $snippets[ 'force_trailing_slash' ] = [
            'label' => 'Force Trailing Slash',
            'lines' => [
                'RewriteCond %{REQUEST_URI} /+[^\.]+$',
                'RewriteRule ^(.+[^/])$ %{REQUEST_URI}/ [R=301,L]',
            ]
        ];

        // Remove a snippet
        unset( $snippets[ 'allow_backups' ] );

        // Return the new snippet array
        return $snippets;
    }
}