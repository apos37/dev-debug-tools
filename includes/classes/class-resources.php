<?php
/**
 * Resources
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Main plugin class.
 */
class DDTT_RESOURCES {

    /**
     * Resources
     *
     * @return array
     */
    public function get_resources() {
        // The links
        $links = apply_filters( 'ddtt_resource_links', [
            [ 
                'title' => 'Apos37 Support Discord Server', 
                'url'   => 'https://discord.gg/3HnzNEJVnR',
                'desc'  => 'Get support from the author of this plugin.' 
            ],
            [ 
                'title' => 'Official WordPress Support Forum', 
                'url'   => 'https://wordpress.org/support/forums/',
                'desc'  => 'Community-based Support Forums hosted by WordPress.org are a great place to learn, share, and troubleshoot.' 
            ],
            [ 
                'title' => 'WordPress Support Forum on StackExchange', 
                'url'   => 'https://wordpress.stackexchange.com/',
                'desc'  => 'Q&A for WordPress developers and administrators.'
            ],
            [ 
                'title' => 'Official WordPress Developer Code Reference', 
                'url'   => 'https://developer.wordpress.org/reference/',
                'desc'  => 'Want to know what\'s going on inside WordPress? Search the Code Reference for more information about WordPress\' functions, classes, methods, and hooks.' 
            ],
            [ 
                'title' => 'PHP.net Documentation', 
                'url'   => 'https://www.php.net/docs.php', 
                'desc'  => 'The PHP Manual is useful for looking up PHP references.' 
            ],
            [ 
                'title' => 'MDN Web Docs', 
                'url'   => 'https://developer.mozilla.org/en-US/', 
                'desc'  => 'Resources for Developers, by Developers. Documenting web technologies, including CSS, HTML, and JavaScript, since 2005.'
            ],
            [ 
                'title' => 'WordPress REST API Handbook', 
                'url'   => 'https://developer.wordpress.org/rest-api/', 
                'desc'  => 'Documentation on the WordPress REST API, which provides an interface for applications to interact with your WordPress site by sending and receiving data as JSON (JavaScript Object Notation) objects.' 
            ],
            
            [ 
                'title' => 'Securing (sanitizing) Input', 
                'url'   => 'https://developer.wordpress.org/plugins/security/securing-input/',
                'desc'  => 'Securing input is the process of <em>sanitizing</em> (cleaning, filtering) input data.<br><br>You use sanitizing when you don’t know what to expect or you don’t want to be strict with data validation.<br><br>Any time you’re accepting potentially unsafe data, it is important to validate or sanitize it.' 
            ],
            [ 
                'title' => 'Securing (escaping) Output', 
                'url'   => 'https://developer.wordpress.org/plugins/security/securing-output/', 
                'desc'  => 'Securing output is the process of <em>escaping</em> output data.<br><br>Escaping means stripping out unwanted data, like malformed HTML or script tags.<br><br>Whenever you’re rendering data, make sure to properly escape it. Escaping output prevents XSS (Cross-site scripting) attacks.' 
            ],
            [
                'title' => 'WebAIM Resources',
                'url'   => 'https://webaim.org/resources/',
                'desc'  => 'Everything you need to learn about web accessibility (sometimes referred to as "a11y"). Full introduction, checklists, blog, keyboard shortcuts, and more.'
            ],
            [
                'title' => 'WebAIM Contrast Checker',
                'url'   => 'https://webaim.org/resources/contrastchecker/',
                'desc'  => 'Contrast and color use are vital to accessibility. Users, including users with visual disabilities, must be able to perceive content on the page. There is a great deal of fine print and complexity within the Web Content Accessibility Guidelines (WCAG) 2 that can easily confuse web content creators and web accessibility evaluators.<br><br>WCAG Level AAA requires a contrast ratio of at least 7:1 for normal text and 4.5:1 for large text. Use this contrast checker to test the ratio between foreground and background colors.'
            ],
            [
                'title' => 'WAVE Web Accessibility Evaluation Tool',
                'url'   => 'https://wave.webaim.org/',
                'desc'  => 'WAVE® is a suite of evaluation tools that helps authors make their web content more accessible to individuals with disabilities. WAVE can identify many accessibility and Web Content Accessibility Guideline (WCAG) errors, but also facilitates human evaluation of web content.<br><br>Also, <a href="https://wave.webaim.org/extension/" target="_blank">see WAVE Browser Extensions</a>'
            ],
            [
                'title' => 'JitBit Screen Sharing from Browser',
                'url'   => 'https://www.jitbit.com/screensharing/',
                'desc'  => 'A free, very basic browser based screen sharing app between 2 people. Use it to quickly share your screen with a co-worker remotely.'
            ],
            [
                'title' => 'ScreenToGif',
                'url'   => 'https://www.screentogif.com/',
                'desc'  => 'Screen, webcam and sketchboard recorder with an integrated editor. Easily record your screen into short clips and turn them into compressed GIFs for sharing.'
            ],
            [ 
                'title' => 'Postman', 
                'url'   => 'https://www.postman.com/', 
                'desc'  => 'An API platform for building and using APIs. Postman simplifies each step of the API lifecycle and streamlines collaboration so you can create better APIs—faster.' 
            ],
            [ 
                'title' => 'Regex Expressions 101', 
                'url'   => 'https://regex101.com/', 
                'desc'  => 'Another Regex playground that is sometimes helpful when our built-in regex playground isn\'t sufficient for your tasks.'
            ],
            [
                'title' => 'JSFiddle - Code Playground',
                'url'   => 'https://jsfiddle.net/',
                'desc'  => 'Test your JavaScript, CSS, HTML or CoffeeScript online with JSFiddle code editor.'
            ],
            [
                'title' => 'How to MANUALLY Migrate Your WordPress Site (Video)',
                'url'   => 'https://www.youtube.com/watch?v=wROa37k_RQA',
                'desc'  => 'A great tutorial to follow if you need to migrate a site, especially if your site is too big to use a plugin.'
            ],
            // [
            //     'title' => '',
            //     'url'   => '',
            //     'desc'  => ''
            // ],
        ] );
        return $links;
    } // End ddtt_resource_links()
}