<?php
/**
 * Change the Quick Debug Link icon when quick links are added to posts and users in admin lists.
 */
add_filter( 'ddtt_quick_link_icon', function() { 
    return '👍'; 
} );