<?php
/**
 * CSS for All of the Plugin Settings Pages
 * Dark mode only
 */

// Check if we are on options pages
global $current_screen;
if ( !isset( $current_screen->id ) ) {
    return;
}

// Get the options page slug
$options_page = 'toplevel_page_'.DDTT_TEXTDOMAIN;

// Allow for multisite
if ( is_network_admin() ) {
    $options_page .= '-network';
}

// Are we on an options page?
if ( $current_screen->id == $options_page ) {

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
    $c2                     = ddtt_get_syntax_color( 'color_fx_vars', '#DCDCAA' );       // Functions and variables
    $c1                     = ddtt_get_syntax_color( 'color_comments', '#5E9955' );      // Comments
    $c3                     = ddtt_get_syntax_color( 'color_syntax', '#569CD6' );        // Syntax
    $c4                     = ddtt_get_syntax_color( 'color_text_quotes', '#ACCCCC' );   // Text with quotes
    
    // Get the debug log colors
    $DDTT_LOGS = new DDTT_LOGS();
    $dl_colors = $DDTT_LOGS->highlight_args();
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
    }

    h2:not(.notice h2),
    .wrap h2:not(.wrap .notice h2) {
        background-color: #2C3338;
        padding: 10px !important;
        font-size: 1.5rem;
        line-height: normal;
        border-radius: 5px;
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
        width: initial;
    }

    /* Tables */
    .admin-large-table, .log-table {
        width: 100%;
    }
    .admin-large-table, .log-table {
        border-collapse: collapse;
    }
    .admin-large-table, .log-table,
    .admin-large-table th, .log-table th,
    .admin-large-table td, .log-table td {
        border: 1px solid <?php echo esc_attr( $borders_main ); ?>;
    }
    .admin-large-table th, .log-table th,
    .admin-large-table td, .log-table td {
        color: <?php echo esc_attr( $text_primary ); ?> !important;
        padding: 10px;
    }
    .admin-large-table td, .log-table td {
        word-break:break-all;
    }
    .admin-large-table tr:nth-child(even) {
        background: <?php echo esc_attr( $bg_primary ); ?> !important;
    }
    table.alternate-row tr:nth-child(even) {
        background: <?php echo esc_attr( $bg_primary ); ?> !important;
    }
    .form-table tr td:last-child {
        padding-right: 0;
    }
    .admin-large-table pre, .log-table pre {
        word-break: break-word;
        white-space: pre-wrap;
    }
    .admin-large-table td {
        vertical-align: top;
    }
    .form-table .checkbox_cont {
        margin-bottom: 10px;
    }

    /* Notices */
    #message.updated,
    .wp-core-ui .notice-success {
        background: #B8DCAA !important;
    }
    #message.updated p,
    .wp-core-ui .notice-success,
    .wp-core-ui .notice-success a {
        color: <?php echo esc_attr( $bg_secondary ); ?> !important;
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

    /* Redact */
    .redact {
        display: inline;
        background: black;
        color: black;
    }
    .redact.show {
        background: revert !important;
        color: revert !important;
    }

    /* Tooltips */
    .tooltip {
        position: relative;
        display: inline-block;
        cursor: help;
    }
    .tooltip .tooltiptext {
        visibility: hidden;
        white-space: nowrap;
        width: auto;
        background-color: red;
        color: #fff;
        text-align: center;
        border-radius: 6px;
        padding: 10px 20px;
        position: absolute;
        top: 1px;
        left: 36px;
        z-index: 1;
    }
    .tooltip:hover .tooltiptext {
        visibility: visible;
    }

    /* Warning Symbol */
    .warning-symbol,
    .warning-symbol::before,
    .warning-symbol::after {
        position: relative;
        padding: 0;
        margin: 0;
    }
    .warning-symbol {
        font-size: 25px;
        color: transparent;
        display: inline-block;
        top: 0.225em;
        width: 1.15em;
        height: 1.15em;
        overflow: hidden;
        border: none;
        background-color: transparent;
        border-radius: 0.625em;
    }
    .warning-symbol::before {
        content: "";
        display: block;
        top: -0.08em;
        left: 0.0em;
        position: absolute;
        border: transparent 0.6em solid;
        border-bottom-color: #fd3;
        border-bottom-width: 1em;
        border-top-width: 0;
        box-shadow: #999 0 1px 1px;
    }
    .warning-symbol::after {
        display: block;
        position: absolute;
        top: 0.3em;
        left: 0;
        width: 100%;
        padding: 0 1px;
        text-align: center;
        font-family: "Garamond";
        content: "!";
        font-size: 0.65em;
        font-weight: bold;
        color: #333;
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
    .button.hide {
        display: none;
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
    .toplevel_page_<?php echo esc_attr( DDTT_TEXTDOMAIN ); ?> input[type=text],
    .toplevel_page_<?php echo esc_attr( DDTT_TEXTDOMAIN ); ?> input[type=number],
    .toplevel_page_<?php echo esc_attr( DDTT_TEXTDOMAIN ); ?> textarea,
    .toplevel_page_<?php echo esc_attr( DDTT_TEXTDOMAIN ); ?> select {
        background-color: <?php echo esc_attr( $bg_secondary ); ?> !important;
        color: <?php echo esc_attr( $text_primary ); ?> !important;
        padding: 8px 12px !important;
        width: 43.75rem;
        max-width: 43.75rem;
        min-height: 2.85rem !important;
        vertical-align: revert;
    }
    .toplevel_page_<?php echo esc_attr( DDTT_TEXTDOMAIN ); ?> textarea {
        width: 100%;
        height: 20rem;
        cursor: auto;
    }
    .toplevel_page_<?php echo esc_attr( DDTT_TEXTDOMAIN ); ?> select {
        background: none;
        -webkit-appearance: menulist !important;
        -moz-appearance: menulist !important; 
        appearance: menulist !important;
    }
    .toplevel_page_<?php echo esc_attr( DDTT_TEXTDOMAIN ); ?> input[type=color] {
        background-color: <?php echo esc_attr( $bg_secondary ); ?> !important;
        height: 4rem;
    }

    /* Field descriptions/comments */
    .field-desc {
        background-color: #2C3338;
        display: inline-block;
        padding: 10px;
        border: 1px solid #2D2D2D;
        font-size: 12px !important;
        line-height: 1.5;
        -webkit-box-shadow: 0 1px 1px rgba(0,0,0,.04);
        box-shadow: 0 1px 1px #26BECF;
        -webkit-box-sizing: border-box;
        -moz-box-sizing: border-box;
        box-sizing: border-box;
        border-radius: 5px;
    }
    .field-desc.break {
        display: block !important;
        width: max-content;
    }
    .field-desc code {
        background-color: #2D2D2D;
        border-radius: 2px;
        font-size: revert !important;
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
        margin-left: .2em;
        margin-top: .2em;
        border-top-left-radius: 5px;
        border-top-right-radius: 5px;
        font-size: 0.9rem;
        padding: 5px 15px;
    }
    .nav-tab:hover {
        background: <?php echo esc_attr( $bg_secondary_hover ); ?> !important;
        color: <?php echo esc_attr( $text_accent_hover ); ?> !important;
    }
    .nav-tab-active {
        background: <?php echo esc_attr( $bg_accent ); ?> !important;
        color: <?php echo esc_attr( $text_accent ); ?> !important;
    }
    .nav-tab-active:hover {
        background: <?php echo esc_attr( $bg_accent ); ?> !important;
        color: <?php echo esc_attr( $text_accent ); ?> !important;
        border-bottom: none !important;
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
    .col-site {
        width: 15rem !important;
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
    .inactive {
        filter: brightness(50%);
    }
    

    /* ---------------------------------------------
                        LOGS
    --------------------------------------------- */

    /* Logs */
    .log-cell {
        vertical-align: top !important;
    }

    /* Debug log */
    #ddtt-dl-search-bar,
    #ddtt-dl-reset-btn {
        display: inline-block;
    }
    #ddtt-dl-search-options {
        display: inline !important;
        margin-left: 10px !important;
        vertical-align: top !important;
    }
    #ddtt-dl-search-form .update_choice_input {
        margin-left: 10px !important;
    }
    #ddtt-dl-search-form .update_choice_input:first-child {
        margin-left: 0 !important;
    }
    @media screen and (max-width: 1620px) {
        #ddtt-dl-search-options {
            display: block !important;
            margin: 10px 0 !important;
        }
    }
    #ddtt-dl-search-btn,
    #ddtt-dl-reset-btn {
        min-height: 46px !important;
        margin-left: 10px;
    }
    #ddtt-dl-search-btn {
        cursor: pointer;
    }
    #ddtt-dl-reset-btn {
        line-height: 1.7 !important;
    }
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
    .debug-li .ln-content {
        padding-left: 10px;
    }
    .debug-li .repeat {
        background: <?php echo esc_attr( $bg_warnings ); ?>;
        color: <?php echo esc_attr( $text_warnings ); ?>;
    }
    .debug-li.ddtt-plugin {
        background: #26BECF !important;
    }
    .debug-li.ddtt-plugin td {
        color: #000000 !important;
        font-weight: 500;
    }
    .debug-li.ddtt-plugin .debug-ln {
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

    .easy-reader th.line {
        width: 1%;
    }
    .easy-reader th.qty {
        width: 7%;
    }
    .easy-reader th.date {
        width: 12%;
    }
    .easy-reader th.type {
        width: 10%;
    }
    .easy-reader th.help {
        width: 14%;
    }
    .easy-reader th.line,
    .easy-reader th.date
    .easy-reader th.type,
    .easy-reader th.qty,
    .easy-reader th.help {
        white-space: nowrap;
    }
    .easy-reader th,
    .easy-reader td.line,
    .easy-reader td.qty {
        text-align: center;
    }
    .easy-reader td {
        vertical-align: top;
    }
    .easy-reader td.help {
        padding-right: 10px !important;
    }
    .easy-reader .debug-li .ln-content {
        padding-left: 0;
    }
    .easy-reader .php-fatal,
    .easy-reader .php-parse {
        padding-left: 0;
        font-weight: 500 !important;
        background-color: red !important;
        color: white !important;
    }
    .easy-reader .php-fatal td,
    .easy-reader .php-parse td {
        color: white !important;
    }
    .easy-reader .the-error {
        font-weight: 500;
        display: block;
        margin-bottom: 20px;
        background: white;
        padding: 7px 10px;
        color: black;
        width: fit-content;
        border-radius: 4px;
        box-shadow: 4px 4px 20px black;
    }
    .easy-reader .stack-trace {
        font-style: italic;
    }
    .easy-reader .stack-thrown {
        margin-left: 10px;
    }
    .easy-reader .help-link-icons {
        height: 20px;
        width: auto;
    }
    <?php

    // Check if there are highlight colors
    if ( !empty( $dl_colors ) ) {

        // Cycle through each debug log color
        foreach ( $dl_colors as $dl_key => $dlc ) {

            // Check if it's a priority
            if ( isset( $dlc[ 'priority' ] ) && $dlc[ 'priority' ] == true ) {

                // Is it also type?
                if ( isset( $dlc[ 'column' ] ) && $dlc[ 'column' ] == 'type' ) {
                    $priority_type = '#wpwrap #wpcontent ';
                } else {
                    $priority_type = '';
                }

                // Add more classes to make it a priority over the rest
                $priority = $priority_type.'#wpbody .tab-content .full_width_container ';
            } else {
                $priority = '';
            }

            if ( isset( $dlc[ 'bg_color' ] ) && isset( $dlc[ 'font_color' ] ) ) {
                ?>
                #color-identifiers .color-box.<?php echo esc_attr( $dl_key ); ?>,
                <?php echo esc_attr( $priority ); ?>.debug-li.<?php echo esc_attr( $dl_key ); ?> {
                    background-color: <?php echo esc_attr( $dlc[ 'bg_color' ] ); ?> !important;
                }
                #color-identifiers .color-box.<?php echo esc_attr( $dl_key ); ?>,
                <?php echo esc_attr( $priority ); ?>.debug-li.<?php echo esc_attr( $dl_key); ?>,
                <?php echo esc_attr( $priority ); ?>.debug-li.<?php echo esc_attr( $dl_key ); ?> td,
                <?php echo esc_attr( $priority ); ?>.debug-li.<?php echo esc_attr( $dl_key ); ?> td a,
                <?php echo esc_attr( $priority ); ?>.debug-li.<?php echo esc_attr( $dl_key ); ?> .ln-content,
                <?php echo esc_attr( $priority ); ?>.debug-li.<?php echo esc_attr( $dl_key ); ?> .ln-content a,
                <?php echo esc_attr( $priority ); ?>.debug-li.<?php echo esc_attr( $dl_key ); ?> .debug-ln {
                    color: <?php echo esc_attr( $dlc[ 'font_color' ] ); ?> !important;
                    font-weight: 500;
                }
                <?php
            }
        }
        ?>
        #color-identifiers .color-box {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            margin-top: 10px;
            display: inline-block;
            text-align: center;
            vertical-align: bottom;
        }
        #color-identifiers .color-cont {
            display: block;
        }
        #color-identifiers .hl-name {
            display: inline-block;
        }
        <?php
    } ?>
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

    /* Enabled */
    code.enabled,
    code.disabled,
    code.check { 
        padding: 3px 5px 4px 5px !important;
        border-radius: 3px !important;
        color: #DCF3F6 !important;
    }
    code.enabled {
        background: green !important;
    }
    code.disabled {
        background: red !important;
    }
    code.check {
        background: blue !important;
    }

    /* Syntax */
    code { background: none; }
    code.hl,
    .notice code { 
        padding: 3px 5px 4px 5px !important;
        background: #2f3136 !important;
        border-radius: 3px !important;
        color: #DCF3F6 !important;
    }
    .comment-out { color: <?php echo esc_attr( $comment_out ); ?>; } 
    .wrap a.c0 { color: <?php echo esc_attr( $c3 ); ?> !important; } /* First line */
    .wrap a.c2, .highlight-variable { color: <?php echo esc_attr( $c2 ); ?> !important; } /* Functions and variables */
    .wrap a.c1 { color: <?php echo esc_attr( $c1 ); ?> !important; } /* Comments */
    .wrap a.c3 { color: <?php echo esc_attr( $c3 ); ?> !important; } /* Syntax */
    .wrap a.c4 { color: <?php echo esc_attr( $c4 ); ?> !important; } /* Text with quotes */
    </style>

<?php }