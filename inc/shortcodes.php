<?php
/**
 * Our Shortcodes
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class OurShortcodes {

    // Construct
    public function __construct() {

        // Example shortcode
        if ( ! shortcode_exists( 'example' ) ) {
            add_shortcode( 'example', [ $this, 'example' ] );
        } else {
            add_shortcode( 'ddtt_example', [ $this, 'example' ] );
        }

    } // End __construct()


    /**
     * Example shortcode with parameters
     *
     * @param array $atts
     * @return string
     */
    public function example( $atts ) {
        // If no attributes passed, show a single random joke
        if ( empty( $atts ) ) {
            return '<p>' . esc_html( Jokes::tell_one() ) . '</p>';
        }

        // Otherwise, output attributes in a table
        $table = '
            <table class="ddtt-example-shortcode-table">
                <thead>
                    <tr>
                        <th>Attribute</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ( $atts as $key => $value ) {
            $table .= '
                    <tr>
                        <td>' . esc_html( $key ) . '</td>
                        <td>' . esc_html( $value ) . '</td>
                    </tr>';
        }

        $table .= '
                </tbody>
            </table>';

        return $table;
    } // End example()

}


new OurShortcodes();