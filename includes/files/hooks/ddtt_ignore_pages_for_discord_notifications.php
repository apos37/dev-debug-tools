<?php 
/**
 * Add, remove or modify pages that should be ignored when using Discord Notifications found in settings under Show Online Users.
 *
 * @param array $pages
 * @return array
 */
function ddtt_ignore_pages_for_discord_notifications_filter( $pages ) {
    // If `prefix` is true, it ignores all urls that start with the url you provide
    // If `prefix` is false, the url must match exactly
    // In this example we will ignore the media library page and all of it's queries (ie. upload.php?item=#, etc.)
    $pages[] = [ 
        'url'    => ddtt_admin_url( 'upload.php' ),
        'prefix' => true
    ];

    // Return the new args array
    return $pages;
} // End ddtt_ignore_pages_for_discord_notifications_filter()

add_filter( 'ddtt_ignore_pages_for_discord_notifications', 'ddtt_ignore_pages_for_discord_notifications_filter' );