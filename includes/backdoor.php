<?php 
// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


/************************* USE AT YOUR OWN RISK!! *************************/
/************************* USE AT YOUR OWN RISK!! *************************/
/************************* USE AT YOUR OWN RISK!! *************************/
/************************* USE AT YOUR OWN RISK!! *************************/
/************************* USE AT YOUR OWN RISK!! *************************/


/**
 * Force admin login if locked out
 * Occassionally you might get locked out of your own site and can't log in.
 * You can use this as a back door to force a login.
 * 
 * Step 1: Look up your IP address at https://www.whatismyip.com (Labeled IPv4)
 * Step 2: Update your IP address below and uncomment the "define" reference
 * Step 3: Uncomment the "add_action" hook below
 * Step 4: Save this file and update it via FTP
 * Step 5: Go to your website and use the following query strings:
 * https://yourdomain.com/?ip=55.555.555.555&un={username}&pw={password}
 * Step 6: Once you are logged in, comment out the hook for security purposes
 * 
 */


/**
 * UNCOMMENT THIS TO RUN THE FUNCTION ON INIT
 * UPDATE WITH YOUR OWN IP ADDRESS
 * Replace with 55.555.555.555 when not in use
 */

// define( 'DDTT_MY_IP_ADDRESS', '55.555.555.555' ); 


 /**
  * UNCOMMENT THIS TO RUN THE FUNCTION ON INIT
  */

// add_action( 'init', 'ddtt_force_login' );


/**
 * Force login user
 *
 * @return void
 */
function ddtt_force_login() {
    if ( ddtt_get( 'ip' ) && ddtt_get( 'pw' ) && ddtt_get( 'un' ) ){
        
        // Specify the ip address and password in the query string
        $ip = ddtt_get( 'ip' );
        $pass = ddtt_get( 'pw' );
        $username = ddtt_get( 'un' );

        // Get the actual IP address
        $current_user_ip = DDTT_MY_IP_ADDRESS;

        // If they match, continue
        if ( $current_user_ip == $ip ) {

            // Sign the user in
            $creds = array();
            $creds['user_login'] = $username;
            $creds['user_password'] = $pass;
            $sign = wp_signon( $creds, false );

            // Catch error or set current user
            if ( is_wp_error( $sign ) ) {
                ddtt_print_r( $sign->get_error_message(), 0);
            } else {
                wp_set_current_user( $sign->ID );
            }
        } else {
            ddtt_print_r( 'Sorry, your IP address does not match the IP address provided in the url.', 0 );
        }
    }
} // End ddtt_force_login()