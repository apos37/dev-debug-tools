<?php
/**
 * Add, remove or modify a snippet from the DDT WP-CONFIG tab.
 * Find more snippets here: https://developer.wordpress.org/apis/wp-config-php/
 *
 * @param array $snippets
 * @return array
 */
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
        ],
        'desc'  => 'Changes the number of post revisions to 3.' 
    ];

    // Remove a snippet
    unset( $snippets[ 'fs_method' ] );

    // Change memory limit snippet to 512M
    $snippets[ 'upload_size' ][ 'lines' ] = [
        [
            'prefix'   => '@ini_set',
            'variable' => 'upload_max_size',
            'value'    => '512M'
        ],
        [
            'prefix'   => '@ini_set',
            'variable' => 'post_max_size',
            'value'    => '512M'
        ]
    ];

    // Return the new snippet array
    return $snippets;
} // End ddtt_wpconfig_snippets_filter()

add_filter( 'ddtt_wpconfig_snippets', 'ddtt_wpconfig_snippets_filter' );