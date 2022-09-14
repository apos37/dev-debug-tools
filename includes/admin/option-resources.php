<!-- Add CSS to this table only -->
<style>
.admin-large-table td {
    vertical-align: top;
}
td.usage code {
    padding: 0;
    margin: 0;
}
</style>

<?php include 'header.php'; ?>

<?php 
$links = apply_filters( 'ddtt_resource_links', [
    [ 
        'title' => 'WordPress Support Discord Server', 
        'url'   => 'https://discord.gg/VeMTXRVkm5',
        'desc'  => 'Get WP support from other developers, including the author of this plugin. Support for this plugin specifically is also available on this server.' 
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
        'title' => 'REST API Handbook', 
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
    
    // [
    //     'title' => '',
    //     'url'   => '',
    //     'desc'  => ''
    // ],
] );
?>

<div class="full_width_container">
    <table class="admin-large-table">
        <tr>
            <th style="width: 300px;">Link</th>
            <th style="width: auto;">Description</th>
        </tr>
        <?php
        // Add the hooks
        foreach ( $links as $link ) {

            // Add the row
            ?>
            <tr>
                <td><?php echo '<a href="'.esc_url( $link[ 'url' ] ).'" target="_blank">'.esc_html( $link[ 'title' ] ).'</a>'; ?></td>
                <td><?php echo wp_kses_post( $link[ 'desc' ] ); ?></td>
            </tr>
            <?php
        }
        ?>
    </table>
</div>