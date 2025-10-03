<?php
/**
 * Error Messages
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class ErrorMessages {

    /**
     * Returns a map of debug log patterns to their descriptions and links.
     *
     * @return string
     */
    public static function page_not_found() : string {
        $messages = [
            // Elmer Fudd
            "Siwwy wabbit! The pwace you twied to wisit doesn't exist.",
            "Shhh… be vewy, vewy quiet. Dis page ish gone, wike a wabbit in de bushes.",
            "Oops! Wooks wike you went wooking in the wong wocation.",

            // Daffy Duck
            "You’re desthpicable! Thith page doethn’t even exthist!",
            "Thath’s the lasth time I follow you! Wrong place, bub.",

            // Porky Pig
            "Th-th-th-that’s all, folks! No page here.",
            "B-b-b-but thith p-page ithn’t r-real.",

            // Yosemite Sam
            "Consarn it! You done blundered into the wrong dern page!",
            "Great horny toads! This ain’t no place for you to be!",

            // Bugs Bunny
            "Eh, what’s up, doc? Certainly not this page.",
            "Ain’t I a stinker? This page ain’t here.",

            // Sylvester
            "Thufferin’ thuccotash! This page ith long gone.",
            "I tawt I taw a webpage… but it wathn't here.",
            "Thith ain’t the plathe you’re lookin’ for, pthhh.",

            // Tweety
            "I tawt I taw a webpage! I did, I did taw a webpage! …oh, no I didn’t.",
            "Uh-oh! That page flew the coop!",

            // Foghorn Leghorn
            "I say, I say, that page is emptier than a henhouse at midnight!",
            "Boy, I say, boy, you’re barkin’ up the wrong URL!",

            // Marvin the Martian
            "Where’s the kaboom? There was supposed to be an earth-shattering kaboom… and a page here.",
            "Illudium Q-36 Explosive Space Modulator not found… and neither is this page.",

            // Tasmanian Devil
            "Rrraaarghh–blblblbl–*spin*… no page here!",
            "Grrmmphh! Page gone! *snarl*"
        ];

        /**
         * Allow filtering of the map
         */
        $messages = apply_filters( 'ddtt_page_not_found_error_messages', $messages );

        // Pick a random one
        return $messages[ array_rand( $messages ) ];
    } // End page_not_found()

}