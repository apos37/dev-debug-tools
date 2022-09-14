<?php
/**
 * CSS for All of the Plugin Settings Pages
 * Dark mode only
 */

// Check if we are on options pages
$screen = get_current_screen();
$options_page = str_replace('.php', '', ddtt_plugin_options_short_path());
if ( $screen && $screen->id == $options_page ) {

    // Set default colors here
    $bg_primary             = '#1E1E1E'; // Background primary
    $bg_secondary           = '#2D2D2D'; // Background secondary
    $bg_secondary_hover     = '#37373D'; // Background secondary hover

    $text_primary           = '#ACCCCC'; // Text primary
    $text_secondary         = '#909696'; // Text secondary
    $text_secondary_hover   = '#8CDCDA'; // Text secondary hover
    $links                  = '#DCDC8B'; // Links

    $bg_accent              = '#26BECF'; // Accent color background
    $text_accent            = '#1E1E1E'; // Accent color text
    $text_accent_hover      = '#FFFFFF'; // Accent color text hover

    $bg_warnings            = '#CA4A1F'; // Warnings background
    $text_warnings          = '#FFFFFF'; // Warnings text

    $borders_main           = '#909696'; // Form field and table borders

    // Syntax
    $comment_out            = ddtt_get_syntax_color( 'color_comments', '#5E9955' );      // Comments
    $c0                     = ddtt_get_syntax_color( 'color_fx_vars', '#DCDCAA' );       // Functions and variables
    $c1                     = ddtt_get_syntax_color( 'color_comments', '#5E9955' );      // Comments
    $c2                     = ddtt_get_syntax_color( 'color_syntax', '#569CD6' );        // Syntax
    $c3                     = ddtt_get_syntax_color( 'color_text_quotes', '#ACCCCC' );   // Text with quotes
    ?>
    <style>
    /* ---------------------------------------------
                    ALL PAGES - GENERAL
    --------------------------------------------- */

    /* Headers */
    h2, 
    h3,
    .wrap h2,
    .wrap h3 {
        margin-top: 0 !important;
        border-top: 0 !important;
        padding-top: 0 !important;
    }

    /* Main backgrounds */
    html,
    body,
    #wpwrap, 
    #wpcontent,
    #wpbody,
    #wpbody-content,
    .wrap {
        background: <?php echo esc_attr( $bg_primary ); ?> !important;
    }

    /* Main text */
    .wrap,
    .wrap h1,
    .wrap h2,
    .wrap h3,
    .form-table th,
    .form-table td,
    .invert-dark-mode span {
        color: <?php echo esc_attr( $text_primary ); ?> !important;
    }
    .indent {
        margin-left: 1rem;
    }

    /* Links */
    .wrap a {
        color: <?php echo esc_attr( $links ); ?>;
    }

    /* HR */
    .tab-content hr {
        border-top: 2px solid #404040 !important;
        border-bottom: 0px !important;
    }

    /* Containers */
    .full_width_container,
    .half_width_container,
    .snippet_container {
        background-color: <?php echo esc_attr( $bg_secondary ); ?>;
        padding: 15px;
        border-radius: 4px;
        height: auto;
    }
    .full_width_container {
        width: initial;
    }
    .half_width_container {
        width: 50%;
    }
    .snippet_container {
        width: max-content;
    }

    /* Tables */
    .admin-large-table {
        width: 100%;
    }
    .admin-large-table {
        border-collapse: collapse;
    }
    .admin-large-table,
    .admin-large-table th,
    .admin-large-table td {
        border: 1px solid <?php echo esc_attr( $borders_main ); ?>;
    }
    .admin-large-table th,
    .admin-large-table td {
        color: <?php echo esc_attr( $text_primary ); ?> !important;
        padding: 10px;
    }
    .admin-large-table td {
        word-break:break-all;
    }
    .admin-large-table tr:nth-child(even) {
        background: <?php echo esc_attr( $bg_primary ); ?>;
    }
    table.alternate-row tr:nth-child(even) {
        background: <?php echo esc_attr( $bg_primary ); ?>;
    }
    .form-table tr td:last-child {
        padding-right: 0;
    }

    /* Notices */
    #message.updated,
    .wp-core-ui .notice-success {
        background: #B8DCAA !important;
    }
    #message.updated p,
    .wp-core-ui .notice-success {
        color: <?php echo esc_attr( $bg_secondary ); ?>;
    }
    .notice-dismiss {
        background: 0 0 !important;
    }
    .notice {
        color: #000000;
        font-weight: 500;
    }

    /* Hide Screen Options */
    #screen-meta,
    #screen-meta-links {
        display: none !important;
    }

    /* Click to copy */
    .click-to-copy {
        background: transparent;
        color: <?php echo esc_attr( $links ); ?>;
        padding: 0;
        border-radius: 0;
    }


    /* ---------------------------------------------
                    ALL PAGES - FORMS
    --------------------------------------------- */

    /* Buttons */
    button,
    .button,
    .btn,
    .wp-core-ui .button-primary,
    input[type=submit] {
        background: <?php echo esc_attr( $bg_accent ); ?> !important;
        color: <?php echo esc_attr( $text_accent ); ?> !important;
        border: 1px solid transparent !important;
        -webkit-backface-visibility: hidden;
        backface-visibility: hidden;
        border-radius: 3px;
        font-family: inherit !important;
        font-size: .875rem !important;
        font-weight: 500 !important;
        height: auto;
        height: initial;
        line-height: 1 !important;
        margin-left: 0;
        padding: .625rem 1.125rem !important;
        transition: transform .3s ease, box-shadow .3s ease, background-color .3s ease;
    }
    button:hover,
    .button:hover,
    .btn:hover,
    .wp-core-ui .button-primary:hover,
    input[type=submit]:hover {
        color: <?php echo esc_attr( $text_accent_hover ); ?>;
    }
    .button.button-warning {
        background-color: red !important;
        color: white !important;
    }

    /* Checkboxes and Radios */
    input[type="checkbox"],
    input[type="radio"] {
        background-color: <?php echo esc_attr( $bg_secondary ); ?>;
        border: 1px solid <?php echo esc_attr( $bg_accent ); ?>;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        vertical-align: middle;
        -webkit-appearance: none;
        outline: none;
        cursor: pointer;
        transition: all 1s ease;
    }
    input[type="checkbox"]:checked:before {
        color: <?php echo esc_attr( $text_accent ); ?>;
        content: '\2713';
        margin: 15px 3px !important;
        font-size: 16px;
        font-weight: bold;
    }
    input[type="radio"]:checked:before {
        color: <?php echo esc_attr( $text_accent ); ?>;
        background-color: <?php echo esc_attr( $text_accent ); ?>;
        margin: 4px 4px !important;
        width: 20px;
        height: 20px;
    }
    input[type="checkbox"]:checked,
    input[type="radio"]:checked {
        background: <?php echo esc_attr( $bg_accent ); ?>
    }
    .gfield_radio div,
    .update_choice {
        height: 30px;
        margin-bottom: 2px;
    }

    /* Input fields */
    .<?php echo esc_attr( DDTT_TEXTDOMAIN ); ?>-includes-admin-options-php input[type=text],
    .<?php echo esc_attr( DDTT_TEXTDOMAIN ); ?>-includes-admin-options-php input[type=number],
    .<?php echo esc_attr( DDTT_TEXTDOMAIN ); ?>-includes-admin-options-php textarea,
    .<?php echo esc_attr( DDTT_TEXTDOMAIN ); ?>-includes-admin-options-php select {
        background-color: <?php echo esc_attr( $bg_secondary ); ?> !important;
        color: <?php echo esc_attr( $text_primary ); ?> !important;
        padding: 8px 12px !important;
        width: 43.75rem;
        max-width: 43.75rem;
    }
    .<?php echo esc_attr( DDTT_TEXTDOMAIN ); ?>-includes-admin-options-php textarea {
        width: 100%;
        height: 20rem;
        cursor: auto;
    }
    .<?php echo esc_attr( DDTT_TEXTDOMAIN ); ?>-includes-admin-options-php select {
        background: none;
        -webkit-appearance: menulist !important;
        -moz-appearance: menulist !important; 
        appearance: menulist !important;
    }
    .<?php echo esc_attr( DDTT_TEXTDOMAIN ); ?>-includes-admin-options-php input[type=color] {
        background-color: <?php echo esc_attr( $bg_secondary ); ?> !important;
        height: 4rem;
    }

    /* Color field sample */
    .options_color_sample {
        height: 30px;
        width: 50px;
        border-radius: 4px;
        display: inline-block;
        position: absolute;
        margin-left: 10px
    }

    /* Required text */
    .gfield_required_text,
    .required-text {
        font-style: italic;
        color: #FF99CC !important;
    }

    /* Scroll bars */
    ::-webkit-scrollbar {
        width: 1em;
    }
    ::-webkit-scrollbar-track {
        -webkit-box-shadow: inset 0 0 6px rgba(0,0,0,0.3);
        box-shadow: inset 0 0 6px rgba(0,0,0,0.3);
    }
    ::-webkit-scrollbar-thumb {
        background-color: darkgrey;
        outline: 1px solid slategrey;
    }

    /* Box resizer */
    ::-webkit-resizer {
        background-color: <?php echo esc_attr( $bg_secondary ); ?>;
        background-image: url("<?php echo esc_url( DDTT_PLUGIN_IMG_PATH ); ?>text-area-resizer_wh_sm.png");
        background-size: cover;
        /* box-shadow: inset 0 0 6px rgba(0,0,0,0.3); */
    }


    /* ---------------------------------------------
                        MENU / NAV
    --------------------------------------------- */
    
    /* Warning count */
    .awaiting-mod {
        background-color: <?php echo esc_attr( $bg_warnings ); ?>;
        color: <?php echo esc_attr( $text_warnings ); ?>;
        display: inline-block;
        vertical-align: top;
        box-sizing: border-box;
        margin: 1px 0 -1px 2px;
        padding: 0 5px;
        min-width: 18px;
        height: 18px;
        border-radius: 9px;
        font-size: 11px;
        line-height: 1.6;
        text-align: center;
        z-index: 26;
    }

    /* Nav bar */
    .nav-tab {
        background: <?php echo esc_attr( $bg_secondary ); ?> !important;
        color: <?php echo esc_attr( $text_secondary ); ?> !important;
        border: 0px;
        margin-left: .1em;
    }
    .nav-tab:hover {
        background: <?php echo esc_attr( $bg_secondary_hover ); ?> !important;
        color: <?php echo esc_attr( $text_accent_hover ); ?> !important;
    }
    .nav-tab-active {
        background: <?php echo esc_attr( $bg_accent ); ?> !important;
        color: <?php echo esc_attr( $text_accent ); ?> !important;
        border-bottom: 1px solid <?php echo esc_attr( $text_accent ); ?>;
    }
    .nav-tab-active:hover {
        background: <?php echo esc_attr( $bg_accent ); ?> !important;
        color: <?php echo esc_attr( $text_accent ); ?> !important;
    }
    .nav-tab-wrapper {
        border-bottom: 1px solid <?php echo esc_attr( $bg_secondary ); ?>;
    }


    /* ---------------------------------------------
                        ACTIVE PLUGINS
    --------------------------------------------- */

    .col-plugin {
        width: 40rem !important;
    }
    .col-path {
        width: auto !important;
    }
    .warning {
        background: red;
        font-weight: bold;
    }
    .admin-large-table td {
        word-break: break-word;
    }
    .red-example {
        background: red;
        color: white;
        padding: 2px 9px 4px 8px;
        border-radius: 2px;
        font-weight: 500;
    }
    

    /* ---------------------------------------------
                        LOGS
    --------------------------------------------- */

    /* Debug log */
    .debug-li {
        white-space: normal;
    }
    .debug-li .debug-ln,
    .debug-ln {
        color: <?php echo esc_attr( $bg_accent ); ?> !important;
        border-right: 1px solid <?php echo esc_attr( $bg_accent ); ?>;
        min-width: 40px;
        text-align: right;
        display: inline-block;
        padding-right: 10px;
    }
    /* .debug-ln {
        color: #ccc;
    } */
    .debug-li .ln-content {
        padding-left: 10px;
    }
    .debug-li .repeat {
        background: <?php echo esc_attr( $bg_warnings ); ?>;
        color: <?php echo esc_attr( $text_warnings ); ?>;
    }
    .debug-li.my-plugin {
        background: #E14F72;
        color: #FFFFFF;
    }
    .debug-li.my-plugin .debug-ln {
        color: #CCCCCC !important;
    }
    .debug-li.my-functions {
        background: #006400;
        color: #FFFFFF;
    }
    .debug-li.theme-functions {
        background: #37373D;
        color: #FFFFFF;
    }
    .repeat {
        color: white; 
        background-color: red; 
        border-radius: 4px; 
        padding: 0 6px;
    }
    .fatal {
        font-weight: bold;
    }

    /* Allow debug lines to be selectable without the line numbers */
    .unselectable {
        -webkit-touch-callout: none;
        -webkit-user-select: none;
        -khtml-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        -o-user-select:none;
        user-select: none;
    }
    .selectable {
        -webkit-touch-callout: all;
        -webkit-user-select: all;
        -khtml-user-select: all;
        -moz-user-select: all;
        -ms-user-select: all;
        -o-user-select:all;
        user-select: all;
    }

    /* Syntax */
    code { background: none; }
    code.hl { 
        padding: 3px 5px 4px 5px !important;
        background: #2f3136 !important;
        border-radius: 3px !important;
    }
    .comment-out { color: <?php echo esc_attr( $comment_out ); ?>; } 
    .wrap a.c0 { color: <?php echo esc_attr( $c0 ); ?> !important; } /* Functions and variables */
    .wrap a.c1 { color: <?php echo esc_attr( $c1 ); ?> !important; } /* Comments */
    .wrap a.c2 { color: <?php echo esc_attr( $c2 ); ?> !important; } /* Syntax */
    .wrap a.c3 { color: <?php echo esc_attr( $c3 ); ?> !important; } /* Text with quotes */
    </style>

<?php }