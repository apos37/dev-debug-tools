<?php
/**
 * Jokes
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Jokes {

    /**
     * Returns a map of debug log patterns to their descriptions and links.
     *
     * @return string
     */
    public static function tell_one() : string {
        $jokes = [
            "Why don’t programmers like nature? — Too many bugs.",
            "Why did the developer go broke? — Because he used up all his cache.",
            "How many programmers does it take to change a lightbulb? — None, that’s a hardware problem.",
            "There are only 10 types of people in the world: those who understand binary and those who don’t.",
            "A SQL query walks into a bar, walks up to two tables and asks: ‘Can I join you?’",
            "Why do Java developers wear glasses? — Because they don’t C#.",
            "Knock, knock. — Who’s there? — Recursion. — Knock, knock.",
            "Why was the JavaScript developer sad? — Because he didn’t know how to ‘null’ his feelings.",
            "What do you call 8 hobbits? — A hobbyte.",
            "Why do programmers prefer dark mode? — Because light attracts bugs.",
            "Why did the computer show up at work late? — It had a hard drive.",
            "What’s a programmer’s favourite hangout place? — Foo Bar.",
            "Why was the developer unhappy at their job? — They wanted arrays.",
            "Why do programmers hate nature? — Too many trees in the stack.",
        ];

        /**
         * Allow filtering of the joke list
         */
        $jokes = apply_filters( 'ddtt_jokes', $jokes );

        // Pick a random one
        return $jokes[ array_rand( $jokes ) ];
    } // End tell_one()


}