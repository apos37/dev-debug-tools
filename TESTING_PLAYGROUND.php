<?php 
////////  DEFAULT HELPERS - DO NOT DELETE  ////////

// Current URL for testing
$page = ddtt_plugin_options_short_path();
$tab = 'testing';
$current_url = ddtt_plugin_options_path( $tab );

// Get the user id from the query string (?user=1) or default to current user
$user_id = ddtt_get_user_from_query_string();

//////////// ADD YOUR OWN HELPERS HERE ////////////

/**
 * You can add helpers here that do not trigger the testing 
 * playground instructions to appear on the page. 
 * You may delete this comment.
 */ 


//////////////  TEST YOUR PHP BELOW  //////////////
//////////////  TEST YOUR PHP BELOW  //////////////
//////////////  TEST YOUR PHP BELOW  //////////////
//////////////  TEST YOUR PHP BELOW  //////////////



// $test = my_test_function();
// dpr( $test );