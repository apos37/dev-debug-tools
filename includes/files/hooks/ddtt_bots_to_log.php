<?php
/**
 * Add a bot crawler to activity log "Bots Crawling Posts and Pages"
 *
 * @param array $bots
 * @return array
 */
function ddtt_bots_to_log_filter( $bots ) {
    // Key is the keyword to search for in the user agent. For example, if the user agent is
    // Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; custombot/2.0; +http://www.example.com/custombot.htm) Chrome/xxx.xx.xxx.xxx Safari/537.36
    // Then the key would be "custombot"
    $bots[ 'custombot' ] = [
        'name' => 'Custom Bot',
        'url'  => 'http://www.example.com/custombot.htm'
    ];
    return $bots;
} // End ddtt_bots_to_log_filter()

add_filter( 'ddtt_bots_to_log', 'ddtt_bots_to_log_filter' );