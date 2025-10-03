<?php
/**
 * Resource Links
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class ResourceLinks {

    /**
     * Get the resource links
     *
     * @return array The array of resource links.
     */
    public static function defaults() : array {
        $links = [
            'mx_toolbox' => [
                'title' => __( 'MX Toolbox', 'dev-debug-tools' ),
                'url'   => 'https://mxtoolbox.com/',
                'desc'  => __( 'MX Toolbox provides a suite of tools to help you manage your email and domain health.', 'dev-debug-tools' )
            ],
            'postman' => [
                'title' => __( 'Postman', 'dev-debug-tools' ),
                'url'   => 'https://www.postman.com/',
                'desc'  => __( 'An API platform for building and using APIs. Postman simplifies each step of the API lifecycle and streamlines collaboration so you can create better APIs—faster.', 'dev-debug-tools' )
            ],
            'regex101' => [
                'title' => __( 'Regex Expressions 101', 'dev-debug-tools' ),
                'url'   => 'https://regex101.com/',
                'desc'  => __( 'Another Regex playground that is sometimes helpful when our built-in regex playground isn\'t sufficient for your tasks.', 'dev-debug-tools' )
            ],
            'wp_support' => [
                'title' => __( 'Official WordPress Support Forum', 'dev-debug-tools' ),
                'url'   => 'https://wordpress.org/support/forums/',
                'desc'  => __( 'Community-based Support Forums hosted by WordPress.org are a great place to learn, share, and troubleshoot.', 'dev-debug-tools' )
            ],
            'wp_stackexchange' => [
                'title' => __( 'WordPress Support Forum on StackExchange', 'dev-debug-tools' ),
                'url'   => 'https://wordpress.stackexchange.com/',
                'desc'  => __( 'Q&A for WordPress developers and administrators.', 'dev-debug-tools' )
            ],
            'wp_developer' => [
                'title' => __( 'Official WordPress Developer Code Reference', 'dev-debug-tools' ),
                'url'   => 'https://developer.wordpress.org/reference/',
                'desc'  => __( 'Want to know what\'s going on inside WordPress? Search the Code Reference for more information about WordPress\' functions, classes, methods, and hooks.', 'dev-debug-tools' )
            ],
            'php_net' => [
                'title' => __( 'PHP.net Documentation', 'dev-debug-tools' ),
                'url'   => 'https://www.php.net/docs.php',
                'desc'  => __( 'The PHP Manual is useful for looking up PHP references.', 'dev-debug-tools' )
            ],
            'mdn_web_docs' => [
                'title' => __( 'MDN Web Docs', 'dev-debug-tools' ),
                'url'   => 'https://developer.mozilla.org/en-US/',
                'desc'  => __( 'Resources for Developers, by Developers. Documenting web technologies, including CSS, HTML, and JavaScript, since 2005.', 'dev-debug-tools' )
            ],
            'wp_rest_api' => [
                'title' => __( 'WordPress REST API Handbook', 'dev-debug-tools' ),
                'url'   => 'https://developer.wordpress.org/rest-api/',
                'desc'  => __( 'Documentation on the WordPress REST API, which provides an interface for applications to interact with your WordPress site by sending and receiving data as JSON (JavaScript Object Notation) objects.', 'dev-debug-tools' )
            ],
            'wp_sanitizing' => [
                'title' => __( 'Securing (sanitizing) Input', 'dev-debug-tools' ),
                'url'   => 'https://developer.wordpress.org/apis/security/sanitizing/',
                'desc'  => __( 'Securing input is the process of sanitizing (cleaning, filtering) input data. You use sanitizing when you don’t know what to expect or you don’t want to be strict with data validation. Any time you’re accepting potentially unsafe data, it is important to validate or sanitize it.', 'dev-debug-tools' )
            ],
            'wp_escaping' => [
                'title' => __( 'Securing (escaping) Output', 'dev-debug-tools' ),
                'url'   => 'https://developer.wordpress.org/apis/security/escaping/',
                'desc'  => __( 'Securing output is the process of escaping output data. Escaping means stripping out unwanted data, like malformed HTML or script tags. Whenever you’re rendering data, make sure to properly escape it. Escaping output prevents XSS (Cross-site scripting) attacks.', 'dev-debug-tools' )
            ],
            'webaim_resources' => [
                'title' => __( 'WebAIM Resources', 'dev-debug-tools' ),
                'url'   => 'https://webaim.org/resources/',
                'desc'  => __( 'Everything you need to learn about web accessibility (sometimes referred to as "a11y"). Full introduction, checklists, blog, keyboard shortcuts, and more.', 'dev-debug-tools' )
            ],
            'webaim_contrast_checker' => [
                'title' => __( 'WebAIM Contrast Checker', 'dev-debug-tools' ),
                'url'   => 'https://webaim.org/resources/contrastchecker/',
                'desc'  => __( 'Contrast and color use are vital to accessibility. Users, including users with visual disabilities, must be able to perceive content on the page. WCAG Level AAA requires a contrast ratio of at least 7:1 for normal text and 4.5:1 for large text. Use this contrast checker to test the ratio between foreground and background colors.', 'dev-debug-tools' )
            ],
            'wave_eval_tool' => [
                'title' => __( 'WAVE Web Accessibility Evaluation Tool', 'dev-debug-tools' ),
                'url'   => 'https://wave.webaim.org/',
                'desc'  => __( 'WAVE is a suite of evaluation tools that helps authors make their web content more accessible to individuals with disabilities. WAVE can identify many accessibility and WCAG errors, but also facilitates human evaluation of web content. Also, see the WAVE browser extensions.', 'dev-debug-tools' )
            ],
            'jitbit' => [
                'title' => __( 'JitBit Screen Sharing from Browser', 'dev-debug-tools' ),
                'url'   => 'https://www.jitbit.com/screensharing/',
                'desc'  => __( 'A free, very basic browser based screen sharing app between 2 people. Use it to quickly share your screen with a co-worker remotely.', 'dev-debug-tools' )
            ],
            'screen_to_gif' => [
                'title' => __( 'ScreenToGif', 'dev-debug-tools' ),
                'url'   => 'https://www.screentogif.com/',
                'desc'  => __( 'Screen, webcam and sketchboard recorder with an integrated editor. Easily record your screen into short clips and turn them into compressed GIFs for sharing.', 'dev-debug-tools' )
            ],
            'jsfiddle' => [
                'title' => __( 'JSFiddle - Code Playground', 'dev-debug-tools' ),
                'url'   => 'https://jsfiddle.net/',
                'desc'  => __( 'Test your JavaScript, CSS, HTML or CoffeeScript online with JSFiddle code editor.', 'dev-debug-tools' )
            ],
            'how_to_migrate' => [
                'title' => __( 'How to MANUALLY Migrate Your WordPress Site (Video)', 'dev-debug-tools' ),
                'url'   => 'https://www.youtube.com/watch?v=wROa37k_RQA',
                'desc'  => __( 'A great tutorial to follow if you need to migrate a site, especially if your site is too big to use a plugin.', 'dev-debug-tools' )
            ],
            'wp_beginner' => [
                'title' => __( 'WPBeginner', 'dev-debug-tools' ),
                'url'   => 'https://www.wpbeginner.com/',
                'desc'  => __( 'Comprehensive tutorials, guides, and tips for WordPress users and developers of all skill levels.', 'dev-debug-tools' )
            ],
            'smashing_magazine' => [
                'title' => __( 'Smashing Magazine', 'dev-debug-tools' ),
                'url'   => 'https://www.smashingmagazine.com/',
                'desc'  => __( 'High-quality articles, tutorials, and resources about web design, development, UX, and WordPress.', 'dev-debug-tools' )
            ],
            'css_tricks' => [
                'title' => __( 'CSS-Tricks', 'dev-debug-tools' ),
                'url'   => 'https://css-tricks.com/',
                'desc'  => __( 'Articles, tips, and tutorials focused on CSS but covering all aspects of front-end development.', 'dev-debug-tools' )
            ],
            'can_i_use' => [
                'title' => __( 'Can I Use', 'dev-debug-tools' ),
                'url'   => 'https://caniuse.com/',
                'desc'  => __( 'Up-to-date browser support tables for modern web technologies, helping you write compatible code.', 'dev-debug-tools' )
            ],
            'web_dev' => [
                'title' => __( 'Web.dev', 'dev-debug-tools' ),
                'url'   => 'https://web.dev/',
                'desc'  => __( 'Google’s resource site for modern web development with articles, guides, and tools focused on performance and best practices.', 'dev-debug-tools' )
            ]
        ];

        /**
         * Allow filtering of the resource links
         */
        $links = apply_filters( 'ddtt_resource_links', $links );

        /**
         * Sort and return
         */
        uasort( $links, function( $a, $b ) {
            return strcmp( $a[ 'title' ], $b[ 'title' ] );
        } );

        return $links;
    } // End get_defaults()


    /**
     * Get the resources (merged defaults and saved)
     *
     * @return array The array of resource links.
     */
    public static function saved() : array {
        $defaults = self::defaults();
        $saved = get_option( 'ddtt_resources', [] );

        if ( ! empty( $saved ) && is_array( $saved ) && isset( $saved[ 'order' ] ) && isset( $saved[ 'custom' ] ) ) {
            $ordered  = [];
            $order    = isset( $saved[ 'order' ] ) && is_array( $saved[ 'order' ] ) ? $saved[ 'order' ] : [];
            $custom   = isset( $saved[ 'custom' ] ) && is_array( $saved[ 'custom' ] ) ? $saved[ 'custom' ] : [];

            foreach ( $order as $key ) {
                if ( isset( $custom[ $key ] ) ) {
                    $ordered[ $key ] = $custom[ $key ];
                } elseif ( isset( $defaults[ $key ] ) ) {
                    $ordered[ $key ] = $defaults[ $key ];
                }
            }
            $resources = $ordered;
        } else {
            $resources = $defaults;
        }

        return $resources;
    } // End saved()

}