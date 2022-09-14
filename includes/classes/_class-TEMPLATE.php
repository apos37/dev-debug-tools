<?php
/**
 * Class template file. Copy and use for other classes.
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Initiate the class
 */
new DDTT_CLASS_NAME;


/**
 * Main plugin class.
 */
class DDTT_CLASS_NAME {

    /**
	 * Constructor
	 */
	public function __construct() {

        // Hooks
        // add_filter( 'filter_name', [$this, 'function_name' ] );

        // Run functions directly
        $this->fake_function();
	} // End __construct()


    /**
     * Function
     * 
     * @return string
     * @since   1.0.0
     */
    public function fake_function() {
        return false;
    } // End fake_function()
}