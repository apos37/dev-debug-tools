<?php
/**
 * Changelog
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Changelog {
    
    /**
     * Get a list of changelog entries.
     *
     * This method returns an array of changelog entries that can help users understand the changes made in the plugin.
     *
     * @return string|array
     */
    public static function get_logs() : string|array {
        // Initialize the filesystem
        global $wp_filesystem;
        if ( empty( $wp_filesystem ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }
        if ( ! $wp_filesystem ) {
            return '<p>' . esc_html__( 'Unable to access the filesystem.', 'dev-debug-tools' ) . '</p>';
        }

        // Get the file content
        $file_path = Bootstrap::path( 'readme.txt' );
        $file = $wp_filesystem->get_contents( $file_path );

        if ( false === $file ) {
            return '<p>' . esc_html__( 'Unable to fetch the changelog at this time.', 'dev-debug-tools' ) . '</p>';
        }

        // Extract changelog section
        $changelog = strstr( $file, '== Changelog ==' );
        $changelog = preg_replace( '/^==\s*Changelog\s*==\s*/mi', '', $changelog );

        // Split into entries
        $entries_raw = preg_split( '/^\s*=+\s*(.*?)\s*=+\s*$/m', $changelog, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
        $entries = [];
        for ( $i = 0; $i < count( $entries_raw ) - 1; $i += 2 ) {
            $entries[] = [
                'version' => str_replace( '// TODO:', '<span class="ddtt-version-in-progress">' . __( '(IN PROGRESS)', 'dev-debug-tools' ) . '</span>', trim( $entries_raw[ $i ] ) ),
                'content' => trim( $entries_raw[ $i + 1 ] ),
            ];
        }
        return $entries;
    } // End get_logs()

}