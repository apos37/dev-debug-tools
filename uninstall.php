<?php
/**
 * Uninstall script for Developer Debug Tools
 */

use Apos37\DevDebugTools\Cleanup;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$ddtt_remove_all = get_option( 'ddtt_remove_data_on_uninstall', false );
if ( ! $ddtt_remove_all ) {
    return;
}

require_once __DIR__ . '/inc/cleanup.php';
Cleanup::run();