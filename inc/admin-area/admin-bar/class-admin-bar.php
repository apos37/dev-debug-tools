<?php
/**
 * Admin Bar
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class AdminBar {


    /**
     * The resources icon
     *
     * @var string
     */
    public function resources_icon() {
        return apply_filters( 'ddtt_resources_icon', '<span class="ab-icon dashicons dashicons-book"></span>' );
    } // End resources_icon()


    /**
     * Default settings for the centering tool
     *
     * @var array
     */
    public $centering_tool_defaults = [
        'cell-width'    => '100px',
        'cell-height'   => '50px',
        'screen-width'  => '1920px',
        'screen-height' => '1080px',
        'background'    => '#ffffff',
        'opacity'       => '0.2',
    ];


    /**
     * Nonce for AJAX
     *
     * @var string
     */
    private $nonce = 'ddtt_admin_bar_nonce';


    /**
     * Constructor
     */
    public function __construct() {

        // Customize the admin bar menu
        add_action( 'admin_bar_menu', [ $this, 'admin_bar' ], 9999998 );

        // Store the admin menu options on plugin changes
        add_action( 'admin_init', [ $this, 'maybe_store_admin_menu_options' ] );
        if ( get_option( 'ddtt_admin_bar_add_links', true ) ) {
            add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_menu_links' ] );
            add_action( 'activated_plugin', [ $this, 'clear_cached_admin_menu_options' ] );
            add_action( 'deactivated_plugin', [ $this, 'clear_cached_admin_menu_options' ] );
            add_action( 'upgrader_process_complete', [ $this, 'clear_cached_admin_menu_options' ], 10, 2 );
            add_action( 'wp_ajax_ddtt_admin_bar_refresh_menu_links', [ $this, 'ajax_refresh_admin_bar_menu_links' ] );
            add_action( 'wp_ajax_nopriv_ddtt_admin_bar_refresh_menu_links', '__return_false' );
        }

        // Centering tool enqueue
        if ( get_option( 'ddtt_admin_bar_centering_tool', true ) ) {
            add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_centering_tool' ] );
            add_action( 'wp_ajax_ddtt_save_centering_tool', [ $this, 'ajax_save_centering_tool' ] );
            add_action( 'wp_ajax_nopriv_ddtt_save_centering_tool', '__return_false' );
        }

        // Gravity Forms finder enqueue
        if ( get_option( 'ddtt_admin_bar_gravity_form_finder', true ) && ! is_admin() && is_plugin_active( 'gravityforms/gravityforms.php' ) ) {
            add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_gravity_forms_finder' ] );
        }

    } // End __construct()


    /**
     * Check if admin bar is condensed
     *
     * @return bool
     */
    public function is_condensed() {
        $condense = sanitize_text_field( get_option( 'ddtt_admin_bar_condense', 'No' ) );
        $is_dev = Helpers::is_dev();
        if ( $condense == 'Everyone' || 
            ( $condense == 'Developer Only' && $is_dev ) || 
            ( $condense == 'Everyone Excluding Developer' && ! $is_dev ) ) {
            return true;
        }
        return false;
    } // End is_condensed()


    /**
     * Customize the admin bar menu
     *
     * @param WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance, passed by reference.
     */
    public function admin_bar( $wp_admin_bar ) {

        $is_dev = Helpers::is_dev();
        $is_administrator = current_user_can( 'administrator' );

        // Check if we should condense items, and use in various places below
        $condense_items = $this->is_condensed();

        // Remove the WordPress logo
        if ( get_option( 'ddtt_admin_bar_wp_logo', false ) ) {
            $this->remove_wordpress_logo( $wp_admin_bar );
        }

        // Add Logs Link
        if ( get_option( 'ddtt_admin_bar_logs', true ) && $is_dev ) {
            $this->render_log_count_indicator( $wp_admin_bar );
        }

        // Add Resources Menu
        if ( get_option( 'ddtt_admin_bar_resources', true ) && $is_dev ) {
            $this->render_resources_menu( $wp_admin_bar );
        }

        // Add User ID Display
        if ( get_option( 'ddtt_admin_bar_user_id', true ) ) {
            $this->render_user_id( $wp_admin_bar, $condense_items );
        }

        // Add Page Loaded Time
        if ( get_option( 'ddtt_admin_bar_page_loaded', false ) && $is_dev ) {
            $this->render_page_loaded_time( $wp_admin_bar );
        }

        // Condense Admin Bar Items
        if ( $condense_items ) {
            $this->condense_admin_bar_items( $wp_admin_bar );
        }

        // Add Admin Menu Links to Front End
        if ( get_option( 'ddtt_admin_bar_add_links', true ) && ! is_admin() && $is_administrator ) {
            $this->render_add_admin_menu_links( $wp_admin_bar );
        }

        // Add Current Page/Post ID and Status
        if ( get_option( 'ddtt_admin_bar_post_id', true ) && ! is_admin() && $is_administrator ) {
            $this->render_post_details( $wp_admin_bar );
        }

        // Add Shortcodes Finder
        if ( get_option( 'ddtt_admin_bar_shortcodes', true ) && ! is_admin() && $is_dev ) {
            $this->render_shortcode_finder( $wp_admin_bar );
        }

        // Add Gravity Forms Finder
        if ( get_option( 'ddtt_admin_bar_gravity_form_finder', true ) && ! is_admin() && is_plugin_active( 'gravityforms/gravityforms.php' ) && $is_dev ) {
            $this->render_gravity_forms_finder( $wp_admin_bar, $condense_items );
        }

        // Add Centering Tool
        if ( get_option( 'ddtt_admin_bar_centering_tool', true ) && ! is_admin() && $is_administrator ) {
            $this->render_centering_tool( $wp_admin_bar );
        }


        /**
         * CSS for Resources icon alignment
         * Only for admin bar icons, otherwise enqueueing CSS in the admin bar is too late
         */
        // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
        echo '<style>
        #wp-admin-bar-ddtt-resources .ab-icon { height:5px; width:13px; margin-top:0; margin-right:8px; text-decoration:none!important; }
        #wp-admin-bar-ddtt-resources .ab-icon:before { font-size:16px; }
        #wp-admin-bar-my-account, #wp-admin-bar-search { float: right !important; }
        </style>';
    } // End admin_bar()


    /**
     * Remove the WordPress logo from the admin bar
     *
     * @param WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance, passed by reference.
     */
    private function remove_wordpress_logo( $wp_admin_bar ) {
        $wp_admin_bar->remove_node( 'wp-logo' );
    } // End remove_wordpress_logo()


    /**
     * Render the log count indicator in the admin bar
     *
     * @param WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance, passed by reference.
     */
    private function render_log_count_indicator( $wp_admin_bar ) {
        $total_lines = absint( get_option( 'ddtt_total_error_count', 0 ) );
        $icon_url = Helpers::icon();
        $indicator_class = $total_lines > 0 ? ' ddtt-log-count-indicator' : '';
        $wp_admin_bar->add_node( [
            'id'    => 'ddtt-logs',
            'title' => '<span class="ab-icon" style="background-image:url(' . esc_attr( $icon_url ) . ') !important; background-size:contain; background-repeat:no-repeat; width: 16px; height: 16px; margin-top: 8px;"></span> <span class="ddtt-log-count' . $indicator_class . '" title="' . esc_attr__( 'Total Log Entries', 'dev-debug-tools' ) . '" style="padding 0 5px !important;">' . $total_lines . '</span>',
            'href'  => Bootstrap::tool_url( 'logs' ),
            'meta'  => [
                'menu_title' => __( 'View Debug Logs', 'dev-debug-tools' ),
                'title'      => __( 'View Debug Logs', 'dev-debug-tools' ),
            ],
        ] );
    } // End render_log_count_indicator()


    /**
     * Render the resources menu in the admin bar
     *
     * @param WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance, passed by reference.
     */
    private function render_resources_menu( $wp_admin_bar ) {
        $resources = ResourceLinks::saved();
        if ( !empty( $resources ) ) {
            $resources_icon = $this->resources_icon();
            $wp_admin_bar->add_node( [
                'id'    => 'ddtt-resources',
                'title' => $resources_icon,
                'meta'  => [
                    'menu_title' => __( 'Dev Debug Tools Resources', 'dev-debug-tools' ),
                    'title'      => __( 'Dev Debug Tools Resources', 'dev-debug-tools' ),
                ],
            ] );

            // Add each link
            foreach ( $resources as $key => $resource ) {
                $wp_admin_bar->add_node( [
                    'id'     => 'ddtt-resource-'.$key,
                    'parent' => 'ddtt-resources',
                    'title'  => $resource[ 'title' ],
                    'href'   => $resource[ 'url' ],
                    'meta'   => [
                        'class'  => 'ddtt-resource',
                        'target' => '_blank',
                    ],
                ] );
            }
        }
    } // End render_resources_menu()


    /**
     * Render the user ID in the admin bar
     *
     * @param WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance, passed by reference.
     * @param bool $condense_items Whether to condense items or not.
     */
    private function render_user_id( $wp_admin_bar, $condense_items ) {
        $user_id = get_current_user_id();
        $my_account = $wp_admin_bar->get_node( 'my-account' );
        $label = $condense_items ? __( 'ID', 'dev-debug-tools' ) : __( 'User ID', 'dev-debug-tools' );
        $greeting = str_replace( 'Howdy,', '(' . $label . ': ' . $user_id . ')', $my_account->title );
        $wp_admin_bar->add_node( [
            'id'    => 'my-account',
            'title' => $greeting,
        ] );
    } // End render_user_id()


    /**
     * Render the page loaded time in the admin bar
     *
     * @param WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance, passed by reference.
     */
    private function render_page_loaded_time( $wp_admin_bar ) {
        $current_timedate = Helpers::convert_timezone( time() );
        $loaded_text = __( 'Page loaded: ', 'dev-debug-tools' ) . $current_timedate;
        $wp_admin_bar->add_node( [
            'id'     => 'ddtt-page-loaded',
            'parent' => 'user-actions',
            'title'  => $loaded_text
        ] );
    } // End render_page_loaded_time()


    /**
     * Condense admin bar items to icons only where possible
     *
     * @param WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance, passed by reference.
     */
    private function condense_admin_bar_items( $wp_admin_bar ) {
        $nodes = $wp_admin_bar->get_nodes();

        // Add Cornerstone to filtered:
        // 'tco-main' => '<span class="tco-admin-bar-logo ab-item" style="background-image: url(data:image/svg+xml;base64,CiAgICA8c3ZnIGZpbGw9IiNhN2FhYWQiIHZpZXdCb3g9IjAgMCA3OTIgNzgwIiB2ZXJzaW9uPSIxLjEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiPgogICAgICA8ZyBzdHJva2U9Im5vbmUiIHN0cm9rZS13aWR0aD0iMSIgZmlsbC1ydWxlPSJldmVub2RkIj4KICAgICAgICA8cGF0aCBkPSJNNDMuMzYzNjA5NSw4Ni45NjQxMjgzIEw3MzYuMzYzNjA5LDAuMzg2ODI3NTk5IEM3NjMuNDkwODI2LC0zLjAwMjIwNzMyIDc4OC4yMjkxMzYsMTYuMjQxMzkxMiA3OTEuNjE4MTcxLDQzLjM2ODYwNzkgQzc5MS44NzI0NzgsNDUuNDA0MTgzNCA3OTIsNDcuNDUzNTk5MSA3OTIsNDkuNTA0OTk4NSBMNzkyLDcyOS42MjU5NDEgQzc5Miw3NTYuOTY0MDM2IDc2OS44MzgwOTUsNzc5LjEyNTk0MSA3NDIuNSw3NzkuMTI1OTQxIEM3NDAuNjUzMDk4LDc3OS4xMjU5NDEgNzM4LjgwNzY0Myw3NzkuMDIyNTc2IDczNi45NzIyOTIsNzc4LjgxNjMzMSBMNDMuOTcyMjkyMSw3MDAuOTQxMzMxIEMxOC45MzA3OTg3LDY5OC4xMjczMjUgLTEuMDM0OTU0MWUtMTMsNjc2Ljk1MDA0OSAtMS4wNjU4MTQxZS0xMyw2NTEuNzUwOTQxIEwtMS4xMzY4NjgzOGUtMTMsMTM2LjA4MjI5OSBDLTEuMTY3NDQyMDNlLTEzLDExMS4xMTcwMTkgMTguNTkwOTA0Niw5MC4wNTkwMTEzIDQzLjM2MzYwOTUsODYuOTY0MTI4MyBaIE0zNzMuNTk5NDc1LDQ2My4zNDI4MDggQzM1NS4zODM0NzUsNDgxLjU0ODc3NyAzMjguMDU5NDc1LDQ5MS40NDMzMjYgMzAzLjkwMzQ3NSw0OTEuNDQzMzI2IEMyMzUuMzk1NDc1LDQ5MS40NDMzMjYgMjA4Ljg2MzQ3NSw0NDMuNTUzNzExIDIwOC40Njc0NzUsMzk3LjY0MzAwNSBDMjA4LjA3MTQ3NSwzNTEuMzM2NTE3IDIzNi45Nzk0NzUsMzAxLjQ2Nzk5MiAzMDMuOTAzNDc1LDMwMS40Njc5OTIgQzMyOC4wNTk0NzUsMzAxLjQ2Nzk5MiAzNTIuNjExNDc1LDMwOS43Nzk0MTMgMzcwLjgyNzQ3NSwzMjcuNTg5NiBMNDA1LjY3NTQ3NSwyOTMuOTQ4MTM1IEMzNzcuMTYzNDc1LDI2NS44NDc2MTcgMzQxLjUyMzQ3NSwyNTEuNTk5NDY3IDMwMy45MDM0NzUsMjUxLjU5OTQ2NyBDMjAzLjcxNTQ3NSwyNTEuNTk5NDY3IDE1Ni41OTE0NzUsMzI1LjIxNDkwOSAxNTYuOTg3NDc1LDM5Ny42NDMwMDUgQzE1Ny4zODM0NzUsNDY5LjY3NTMxOSAyMDAuOTQzNDc1LDU0MC41MjAyODcgMzAzLjkwMzQ3NSw1NDAuNTIwMjg3IEMzNDMuODk5NDc1LDU0MC41MjAyODcgMzgwLjcyNzQ3NSw1MjcuNDU5NDgzIDQwOS4yMzk0NzUsNDk5LjM1ODk2NSBMMzczLjU5OTQ3NSw0NjMuMzQyODA4IFogTTYzOC45MTk0NzUsMzAyLjY1NTMzOCBDNjE3LjkzMTQ3NSwyNTkuOTEwODg4IDU3My4xODM0NzUsMjQ3LjY0MTY0NyA1MzAuMDE5NDc1LDI0Ny42NDE2NDcgQzQ3OC45MzU0NzUsMjQ4LjAzNzQyOSA0MjIuNzAzNDc1LDI3MS4zODg1NjQgNDIyLjcwMzQ3NSwzMjguMzgxMTY0IEM0MjIuNzAzNDc1LDM5MC41MTg5MyA0NzQuOTc1NDc1LDQwNS41NTg2NDQgNTMxLjYwMzQ3NSw0MTIuMjg2OTM3IEM1NjguNDMxNDc1LDQxNi4yNDQ3NTYgNTk1Ljc1NTQ3NSw0MjYuOTMwODY5IDU5NS43NTU0NzUsNDUzLjA1MjQ3NyBDNTk1Ljc1NTQ3NSw0ODMuMTMxOTA1IDU2NC44Njc0NzUsNDk0LjYwOTU4MiA1MzEuOTk5NDc1LDQ5NC42MDk1ODIgQzQ5OC4zMzk0NzUsNDk0LjYwOTU4MiA0NjYuMjYzNDc1LDQ4MS4xNTI5OTUgNDUzLjk4NzQ3NSw0NTAuNjc3Nzg2IEw0MTAuNDI3NDc1LDQ3My4yMzczNTcgQzQzMS4wMTk0NzUsNTIzLjg5NzQ0NiA0NzQuNTc5NDc1LDU0MS4zMTE4NTEgNTMxLjIwNzQ3NSw1NDEuMzExODUxIEM1OTIuOTgzNDc1LDU0MS4zMTE4NTEgNjQ3LjYzMTQ3NSw1MTQuNzk0NDYxIDY0Ny42MzE0NzUsNDUzLjA1MjQ3NyBDNjQ3LjYzMTQ3NSwzODYuOTU2ODkyIDU5My43NzU0NzUsMzcxLjkxNzE3OCA1MzUuOTU5NDc1LDM2NC43OTMxMDMgQzUwMi42OTU0NzUsMzYwLjgzNTI4NCA0NzQuMTgzNDc1LDM1NC4xMDY5OTEgNDc0LjE4MzQ3NSwzMjkuOTY0MjkyIEM0NzQuMTgzNDc1LDMwOS4zODM2MzEgNDkyLjc5NTQ3NSwyOTMuMTU2NTcxIDUzMS42MDM0NzUsMjkzLjE1NjU3MSBDNTYxLjY5OTQ3NSwyOTMuMTU2NTcxIDU4Ny44MzU0NzUsMzA4LjE5NjI4NSA1OTcuMzM5NDc1LDMyNC4wMjc1NjMgTDYzOC45MTk0NzUsMzAyLjY1NTMzOCBaIj48L3BhdGg+CiAgICAgIDwvZz4KICAgIDwvc3ZnPgoKICAgIA==)"></span>',
        
        $filtered = apply_filters( 'ddtt_admin_bar_condensed_items', [
            'wpengine_adminbar' => 'WPE',
        ] );

        $ignore = apply_filters( 'ddtt_admin_bar_condensed_ignore', [
            'ddtt-logs',
            'my-account',
            'menu-toggle',
            'wp-logo',
            'comments',
            'updates',
            'blnotifier-notify',
            'query-monitor'
        ] );

        $only_remove_ab_labels = apply_filters( 'ddtt_admin_bar_condensed_only_remove_ab_labels', [
            'wcagaat',
        ] );

        // Nodes that use CSS stylesheet for their icon such as :before content
        $css_icon_nodes = apply_filters( 'ddtt_admin_bar_condensed_css_icon_nodes', [
            'site-name',
            'customize',
            'edit',
        ] );

        foreach ( $nodes as $node ) {

            // Ignore parents and items in the ignore list
            if ( $node->parent || in_array( $node->id, $ignore ) ) {
                continue;
            }

            // Explicit override wins
            if ( isset( $filtered[ $node->id ] ) ) {
                $original_text = wp_strip_all_tags( $node->title );
                $node->title = $filtered[ $node->id ];
                $node->meta[ 'menu_title' ] = $original_text;
                $node->meta[ 'title' ] = $original_text;
                $wp_admin_bar->add_node( $node );
                continue;
            }

            if ( ! empty( $node->title ) && 
                    ( stripos( $node->title, 'ab-icon' ) !== false || 
                    stripos( $node->title, 'background-image' ) !== false || 
                    preg_match( '/<(svg|img|i)[\s>]/i', $node->title ) ||
                    in_array( $node->id, $css_icon_nodes, true ) ) ) {

                $original_html  = $node->title;
                $visible_text   = '';
                $icon_html      = '';

                // Try DOMDocument first (safer)
                if ( class_exists( 'DOMDocument' ) ) {
                    libxml_use_internal_errors( true );
                    $dom = new \DOMDocument();

                    // decode entities first
                    $fragment = html_entity_decode( $original_html, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
                    $fragment = mb_decode_numericentity( $fragment, [ 0x0, 0x2FFFF, 0, 0xFFFF ], 'UTF-8' );

                    $fragment = '<div>' . $fragment . '</div>';
                    $dom->loadHTML( $fragment, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

                    $div = $dom->getElementsByTagName( 'div' )->item(0);
                    if ( $div ) {

                        if ( in_array( $node->id, $only_remove_ab_labels, true ) ) {
                            $labels = $div->getElementsByTagName( '*' ); // all elements
                            foreach ( $labels as $el ) {
                                $classAttr = $el->attributes->getNamedItem( 'class' );
                                if ( $classAttr && stripos( $classAttr->nodeValue, 'ab-label' ) !== false ) {
                                    $visible_text .= ( $visible_text ? ' ' : '' ) . trim( $el->textContent );
                                }
                            }

                        } else {

                            foreach ( $div->childNodes as $child ) {

                                // Check if this child looks like an icon
                                if ( $child->nodeType === XML_ELEMENT_NODE ) {
                                    $keep = false;

                                    // quick attribute checks
                                    if ( $child->hasAttributes() ) {
                                        $classAttr = $child->attributes->getNamedItem( 'class' );
                                        $styleAttr = $child->attributes->getNamedItem( 'style' );

                                        if ( $classAttr && stripos( $classAttr->nodeValue, 'ab-icon' ) !== false ) {
                                            $keep = true;
                                        }
                                        if ( $classAttr && stripos( $classAttr->nodeValue, 'dashicons' ) !== false ) {
                                            $keep = true;
                                        }
                                        if ( $styleAttr && stripos( $styleAttr->nodeValue, 'background-image' ) !== false ) {
                                            $keep = true;
                                        }
                                        if ( $classAttr && stripos( $classAttr->nodeValue, 'ab-count' ) !== false ) {
                                            $keep = true;
                                        }
                                    }

                                    $tag = strtolower( $child->nodeName );
                                    if ( in_array( $tag, [ 'svg', 'img', 'i' ], true ) ) {
                                        $keep = true;
                                    }

                                    if ( $keep ) {
                                        $icon_html  .= $dom->saveHTML( $child );
                                    } else {
                                        $t = trim( $child->textContent );
                                        if ( $t !== '' ) {
                                            $visible_text .= ( $visible_text ? ' ' : '' ) . $t;
                                        }
                                    }
                                } elseif ( $child->nodeType === XML_TEXT_NODE ) {
                                    $t = trim( $child->nodeValue );
                                    if ( $t !== '' ) {
                                        $visible_text .= ( $visible_text ? ' ' : '' ) . $t;
                                    }
                                }
                            }
                        }
                    }

                    libxml_clear_errors();

                } else {
                    // Fallback: regex to pluck icon-like tags and remaining text
                    // capture icon-like tags (span/img/svg/i/div) that contain ab-icon / dashicons / background-image
                    if ( preg_match_all(
                        '/<(?:(?:span|i|img|svg|div|a|button)[^>]*?(?:class=[\'"][^\'"]*(?:ab-icon|dashicons)[^\'"]*[\'"]|style=[\'"][^\'"]*background-image[^\'"]*[\'"])[^>]*>.*?<\/(?:span|i|svg|div|a|button)>)/is',
                        $original_html,
                        $matches
                    ) ) {
                        $icon_html = implode( '', $matches[0] );
                    }

                    // remove those icon tags, then strip tags to get visible text
                    $text_only = trim( preg_replace( '/<(?:span|i|img|svg|div|a|button)[^>]*>.*?<\/(?:span|i|svg|div|a|button)>/is', ' ', $original_html ) );
                    $visible_text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $text_only ) ) );
                }

                // If we found actual icon HTML
                if ( $icon_html !== '' ) {
                    $visible_text = $visible_text !== '' ? $visible_text : trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $original_html ) ) );

                    $node->title              = $icon_html; // keep icon markup
                    $node->meta[ 'menu_title' ] = $visible_text;
                    $node->meta[ 'title' ]      = $visible_text;

                    // Add marker class
                    if ( $visible_text !== '' ) {
                        $node->meta[ 'class' ] = trim( ( $node->meta[ 'class' ] ?? '' ) . ' ddtt-has-text-label' );
                    }

                // Special case: CSS-icon nodes (like site-name) → no inline icon markup
                } elseif ( in_array( $node->id, $css_icon_nodes, true ) ) {
                    $visible_text               = trim( wp_strip_all_tags( $node->title ) );
                    $node->title                = ''; // remove visible text completely
                    $node->meta[ 'menu_title' ] = $visible_text;
                    $node->meta[ 'title' ]      = $visible_text;

                    if ( $visible_text !== '' ) {
                        $node->meta[ 'class' ] = trim( ( $node->meta[ 'class' ] ?? '' ) . ' ddtt-has-text-label ddtt-has-css-icon' );
                    }
                }

                // After your icon / CSS handling
                if ( ! empty( $visible_text ) ) {
                    $classes = isset( $node->meta[ 'class' ] ) ? explode( ' ', $node->meta[ 'class' ] ) : [];
                    
                    if ( ! in_array( 'ddtt-has-text-label', $classes, true ) ) {
                        $classes[] = 'ddtt-has-text-label';
                    }

                    $node->meta[ 'class' ] = implode( ' ', $classes );
                }
            }

            $wp_admin_bar->add_node( $node );
        }

        // Only for admin bar icons, otherwise enqueueing CSS in the admin bar is too late
        // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
        echo '<style>
        #wp-toolbar .ddtt-has-text-label .ab-icon { margin-right: 0 !important; }
        #wp-toolbar .ddtt-has-text-label .ab-count { margin-left: 6px !important; }
        #wp-toolbar .ddtt-has-css-icon .ab-item:before { margin-right: 0 !important; }
        #wp-toolbar #wp-admin-bar-tco-main .tco-admin-bar-logo.ab-item { margin-right: 0 !important; }
        #wp-toolbar li:not(#wp-admin-bar-query-monitor) > .ab-item > .ab-label { display: none; }
        #wp-admin-bar-my-account .display-name { display: none; }
        #wp-admin-bar-comments .ab-icon, #wp-admin-bar-duplicate-post .ab-icon { margin-right: 0 !important; }
        </style>';
    } // End condense_admin_bar_items()


    /**
     * Enqueue the condensed admin bar styles
     */
    public function enqueue_menu_links() {
        if ( ! is_admin() && current_user_can( 'administrator' ) ) {
            $version = Bootstrap::script_version();
            $handle = 'ddtt-admin-bar-menu-links';

            wp_enqueue_style(
                $handle,
                Bootstrap::url( 'inc/admin-area/admin-bar/menu-links.css' ),
                [],
                $version
            );
        }
    } // End enqueue_menu_links()


    /**
     * Render additional admin menu links in the admin bar on the front end
     *
     * @param WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance, passed by reference.
     */
    private function render_add_admin_menu_links( $wp_admin_bar ) {
        $saved_admin_menu_items = filter_var_array( get_option( 'ddtt_admin_menu_items', [] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS );


        /**
         * Allow filtering of the admin menu items
         */
        $saved_admin_menu_items = apply_filters( 'ddtt_admin_bar_dropdown_links', $saved_admin_menu_items );
        
        if ( ! empty( $saved_admin_menu_items ) ) {

            $has_access = Helpers::has_access();

            foreach ( $saved_admin_menu_items as $admin_menu_link ) {
                if ( ! current_user_can( $admin_menu_link[ 'perm' ] ) ) {
                    continue;
                }

                if ( $admin_menu_link[ 'slug' ] === 'dev-debug-dashboard' && ! $has_access ) {
                    continue;
                }

                $wp_admin_bar->add_node( [
                    'parent' => 'site-name',
                    'id'     => isset( $admin_menu_link[ 'slug' ] ) ? $admin_menu_link[ 'slug' ] : strtolower( str_replace( ' ', '_', $admin_menu_link[ 'label' ] ) ),
                    'title'  => $admin_menu_link[ 'label' ],
                    'href'   => $admin_menu_link[ 'url' ]
                ] );
            }
        }
    } // End render_add_admin_menu_links()


    /**
     * Maybe store the admin menu options in an option for later use
     */
    public function maybe_store_admin_menu_options() {
        $adding_links = filter_var( get_option( 'ddtt_admin_bar_add_links', true ), FILTER_VALIDATE_BOOLEAN );
        $saved_admin_menu_items = filter_var_array( get_option( 'ddtt_admin_menu_items', [] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        
        if ( $adding_links && empty( $saved_admin_menu_items ) ) {
            $admin_menu_items = $this->fetch_admin_menu_options();
            update_option( 'ddtt_admin_menu_items', $admin_menu_items, false );
        } elseif ( ! $adding_links && ! empty( $saved_admin_menu_items ) ) {
            $this->clear_cached_admin_menu_options();
        }
    } // End maybe_store_admin_menu_options()


    /**
     * Store the admin menu options in an option for later use
     */
    public function clear_cached_admin_menu_options() {
        delete_option( 'ddtt_admin_menu_items' );
    } // End clear_cached_admin_menu_options()


    /**
     * Check if a URL exists (returns 200-399)
     * 
     * @param string $url The URL to check.
     * @return bool True if the URL exists, false otherwise.
     */
    private function url_exists( string $url ): bool {
        $response = wp_remote_head( $url, [ 'timeout' => 2 ] );
        if ( is_wp_error( $response ) ) {
            return false;
        }
        $code = wp_remote_retrieve_response_code( $response );
        return $code >= 200 && $code < 400;
    } // End url_exists()


    /**
     * Get the admin menu options
     * 
     * @return array The admin menu options.
     */
    public function fetch_admin_menu_options() : array {
        global $menu;
        if ( empty( $menu ) ) {
            return [];
        }

        $special_urls = [
            'admin-help-docs'      => 'admin.php?page=admin-help-docs&tab=documentation',
            'broken-link-notifier' => 'edit.php?post_type=broken-link-notifier',
            'learndash-lms'        => 'edit.php?post_type=sfwd-courses',
        ];
        $special_urls = apply_filters( 'ddtt_admin_menu_special_urls', $special_urls );

        $core_urls = [
            'users.php'               => admin_url( 'users.php' ),
            'upload.php'              => admin_url( 'upload.php' ),
            'edit.php?post_type=page' => admin_url( 'edit.php?post_type=page' ),
            'edit.php'                => admin_url( 'edit.php' ),
            'edit-comments.php'       => admin_url( 'edit-comments.php' ),
            'tools.php'               => admin_url( 'tools.php' ),
            'options-general.php'     => admin_url( 'options-general.php' ),
        ];

        $already_added = [
            'index.php',
            'plugins.php',
            'themes.php',
        ];

        $admin_menu_items = [];
        foreach( $menu as $item ) {
            
            $slug = $item[2];

            // Skip separators and non-admin URLs
            if ( $slug === null || $slug === '' || in_array( $slug, $already_added, true ) || strpos( $slug, 'separator' ) !== false || strpos( $slug, 'http' ) === 0 || strpos( $slug, 'https' ) === 0 ) {
                continue;
            }

            // Remove HTML tags and any content inside them
            $label = preg_replace( '/<[^>]+>.*?<\/[^>]+>/', '', $item[0] ); 
            $label = wp_strip_all_tags( $label ); 
            $label = html_entity_decode( $label );
            $label = trim( $label );

            if ( isset( $core_urls[ $slug ] ) ) {
                $url = $core_urls[ $slug ];
            } elseif ( isset( $special_urls[ $slug ] ) ) {
                $url = admin_url( $special_urls[ $slug ] );
            } elseif ( strpos( $slug, '.php' ) !== false || strpos( $slug, '?' ) !== false ) {
                $url = admin_url( $slug );
            } else {
                $url = menu_page_url( $slug, false );
                if ( ! $url ) {
                    continue; // skip if WP can’t generate a page URL
                }
            }

            $admin_menu_items[] = [
                'slug'  => $slug,
                'label' => $label,
                'url'   => $url,
                'perm'  => $item[1],
            ];
        }

        return $admin_menu_items;
    } // End fetch_admin_menu_options()


    /**
     * AJAX handler to refresh the admin bar menu links
     */
    public function ajax_refresh_admin_bar_menu_links() {
        check_ajax_referer( 'ddtt_save_settings_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'dev-debug-tools' ) ], 403 );
        }

        $this->clear_cached_admin_menu_options();
        wp_send_json_success( [ 'message' => __( 'Please refresh the page to take effect.', 'dev-debug-tools' ), 'updated' => true ] );
    } // End ajax_refresh_admin_bar_menu_links()


    /**
     * Render the current page/post ID and status in the admin bar on the front end
     *
     * @param WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance, passed by reference.
     */
    private function render_post_details( $wp_admin_bar ) {
        if ( is_search() ) {
            $post_info_title = __( 'Search Results Page', 'dev-debug-tools' );
        } elseif ( is_404() ) {
            $post_info_title = __( '404 Page', 'dev-debug-tools' );
        } else {
            $post_id = get_the_ID();
            if ( $post_id ) {
                
                $post_type     = get_post_type( $post_id );
                $post_type_obj = get_post_type_object( $post_type );
                if ( $post_type_obj ) {
                    $pt_name = sanitize_text_field( $post_type_obj->labels->singular_name ) . ' ' . __( 'ID', 'dev-debug-tools' );
                } else {
                    $pt_name = '';
                }

                $post_status_obj = get_post_status_object( get_post_status( $post_id ) );
                if ( $post_status_obj && isset( $post_status_obj->label ) ) {
                    $post_status = $post_status_obj->label;
                } else {
                    $post_status = ucfirst( get_post_status( $post_id ) );
                }

                $post_info_title = $pt_name . ' ' . $post_id . ': (' . $post_status . ')';

            } else {
                $post_info_title = __( 'Not Singular', 'dev-debug-tools' );
            }
        }

        $nonce = wp_create_nonce( 'ddtt_metadata_lookup' );
        $url = ( Helpers::is_dev() && $post_id ) ? Bootstrap::tool_url( 'metadata&s=post&lookup=' . $post_id . '&_wpnonce=' . $nonce ) : '';

        $wp_admin_bar->add_node( [
            'id'     => 'ddtt-admin-post-id',
            'parent' => 'top-secondary',
            'title'  => $post_info_title,
            'href'   => $url,
            'meta'   => [
                'target' => '_blank',
            ],
        ] );
    } // End render_post_details()


    /**
     * Render the shortcode finder in the admin bar
     *
     * @param WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance, passed by reference.
     */
    private function render_shortcode_finder( $wp_admin_bar ) {
        $post_id = get_the_ID();
        if ( ! $post_id ) {
            return;
        }

        $post = get_post( $post_id );
        if ( ! $post ) {
            return;
        }

        $content = $post->post_content;
        $content = apply_filters( 'ddtt_shortcode_finder_content', $content );

        // Get all registered shortcodes
        global $shortcode_tags;
        $found_shortcodes = [];

        foreach ( $shortcode_tags as $shortcode => $callback ) {
            if ( has_shortcode( $content, $shortcode ) ) {
                // Count occurrences
                preg_match_all( '/' . get_shortcode_regex( [ $shortcode ] ) . '/', $content, $matches );
                $found_shortcodes[ $shortcode ] = count( $matches[0] );
            }
        }

        // Omit these
        $omits = apply_filters( 'ddtt_omit_shortcodes', [
            'cs_content',
            'cs_element'
        ] );
        foreach ( $omits as $omit ) {
            if ( isset( $found_shortcodes[ $omit ] ) ) {
                unset( $found_shortcodes[ $omit ] );
            }
        }

        $count = count( $found_shortcodes );

        // Parent node
        $wp_admin_bar->add_node( [
            'id'     => 'ddtt-shortcodes-found',
            'parent' => 'top-secondary',
            'title'  => '[' . $count . ']',
        ] );

        // Add each shortcode
        foreach ( $found_shortcodes as $shortcode => $occurrences ) {
            $wp_admin_bar->add_node( [
                'id'     => 'ddtt-shortcode-' . md5( $shortcode ),
                'parent' => 'ddtt-shortcodes-found',
                'title'  => '[' .esc_html( $shortcode ) . '] <span class="ddtt-shortcode-count">(' . absint( $occurrences ) . ')</span>',
            ] );
        }
    } // End render_shortcode_finder()


    /**
     * Render the Gravity Forms finder in the admin bar
     *
     * @param WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance, passed by reference.
     */
    private function render_gravity_forms_finder( $wp_admin_bar, $condense_items ) {
        $icon = '<div id="ddtt-gforms-finder-icon" class="gforms-menu-icon svg" style="background-image: url(&quot;data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz48c3ZnIHdpZHRoPSIyMSIgaGVpZ2h0PSIyMSIgdmlld0JveD0iMCAwIDIxIDIxIiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxtYXNrIGlkPSJtYXNrMCIgbWFzay10eXBlPSJhbHBoYSIgbWFza1VuaXRzPSJ1c2VyU3BhY2VPblVzZSIgeD0iMiIgeT0iMSIgd2lkdGg9IjE3IiBoZWlnaHQ9IjIwIj48cGF0aCBmaWxsLXJ1bGU9ImV2ZW5vZGQiIGNsaXAtcnVsZT0iZXZlbm9kZCIgZD0iTTExLjU5MDYgMi4wMzcwM0wxNy4xNzkzIDUuNDQ4MjRDMTcuODk0IDUuODg0IDE4LjQ3NjcgNi45NTI5OCAxOC40NzY3IDcuODI0NTFWMTQuNjUwM0MxOC40NzY3IDE1LjUxODUgMTcuODk0IDE2LjU4NzQgMTcuMTc5MyAxNy4wMjMyTDExLjU5MDYgMjAuNDMxQzEwLjg3OTIgMjAuODY2OCA5LjcxMDU1IDIwLjg2NjggOC45OTkwOSAyMC40MzFMMy40MTA0MSAxNy4wMTk4QzIuNjk1NzMgMTYuNTg0IDIuMTEzMDQgMTUuNTE4NSAyLjExMzA0IDE0LjY0NjlWNy44MjExQzIuMTEzMDQgNi45NTI5OCAyLjY5ODk1IDUuODg0IDMuNDEwNDEgNS40NDgyNEw4Ljk5OTA5IDIuMDM3MDNDOS43MTA1NSAxLjYwMTI2IDEwLjg3OTIgMS42MDEyNiAxMS41OTA2IDIuMDM3MDNaTTE1Ljc0OTQgOS4zNzUwM0g4LjgxMDQ5QzguMzgyOTkgOS4zNzUwMyA4LjA2MjM3IDkuNTAxNjQgNy44MDkwNCA5Ljc3MDY4QzcuMjU0ODggMTAuMzYwMiA2Ljk2MTk2IDExLjUwMzYgNi45MTg0MiAxMi4xNDA2SDEzLjc1MDVWMTAuNDI3NUgxNS43MDE5VjE0LjA5MTJINC44NDAzMUM0Ljg0MDMxIDE0LjA5MTIgNC44Nzk4OSAxMC4wMzk3IDYuMzkxOTcgOC40MzMzOUM3LjAxNzM4IDcuNzY0NzUgNy44NDA3IDcuNDI0NDkgOC44MzAyOCA3LjQyNDQ5SDE1Ljc0OTRWOS4zNzUwM1oiIGZpbGw9IndoaXRlIi8+PC9tYXNrPjxnIG1hc2s9InVybCgjbWFzazApIj48cmVjdCB4PSIwLjI5NDkyMiIgeT0iMC43NTc4MTIiIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgZmlsbD0iIzg4ODg4OCIvPjwvZz48L3N2Zz4=&quot;) !important;" aria-hidden="true"></div>';

        $wp_admin_bar->add_node( [
            'id'     => 'ddtt-gf-found',
            'parent' => 'top-secondary',
            'title'  => $icon . ' <span id="ddtt-gforms-found">0</span>',
            'href'   => '#',
        ] );
    } // End render_gravity_forms_finder()


    /**
     * Enqueue the centering tool script on the front end if the user has it enabled
     */
    public function enqueue_gravity_forms_finder() {
        $version = Bootstrap::script_version();
        $handle = 'ddtt-admin-bar-gforms-finder';

        wp_enqueue_script(
            $handle,
            Bootstrap::url( 'inc/admin-area/admin-bar/gforms-finder.js' ),
            [ 'jquery' ],
            $version,
            true
        );

        $condense_items = $this->is_condensed();

        $form_url = add_query_arg( [
            'page'    => 'gf_edit_forms',
            // 'view'    => 'settings',
            // 'subview' => 'settings',
            'id'      => '%d',
        ], admin_url( 'admin.php' ) );

        wp_localize_script( $handle, 'ddtt_admin_bar_gforms_finder', [
            'condensed' => $condense_items,
            'form_url'  => $form_url,
            'i18n'      => [
                'no_forms'  => __( 'No Forms', 'dev-debug-tools' ),
                'forms'     => __( 'Forms', 'dev-debug-tools' ),
                'form_id'   => __( 'Form ID', 'dev-debug-tools' ),
                'id'        => __( 'ID', 'dev-debug-tools' )
            ],
        ] );

        wp_enqueue_style(
            $handle,
            Bootstrap::url( 'inc/admin-area/admin-bar/gforms-finder.css' ),
            [],
            $version
        );
    } // End enqueue_gravity_forms_finder()


    /**
     * Render the centering tool in the admin bar
     *
     * @param WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance, passed by reference.
     */
    private function render_centering_tool( $wp_admin_bar ) {
        $user_id = get_current_user_id();
        $user_prefs = get_user_meta( $user_id, 'ddtt_centering_tool', true );
        if ( ! empty( $user_prefs ) ) {
            $prefs = filter_var_array( $user_prefs, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            $user_on = isset( $user_prefs[ 'on' ] ) && filter_var( $user_prefs[ 'on' ], FILTER_VALIDATE_BOOLEAN ) ? true : false;
            $cell_width = isset( $prefs[ 'cell-width' ] ) ? $prefs[ 'cell-width' ] : $this->centering_tool_defaults[ 'cell-width' ];
            $cell_height = isset( $prefs[ 'cell-height' ] ) ? $prefs[ 'cell-height' ] : $this->centering_tool_defaults[ 'cell-height' ];
            $screen_width = isset( $prefs[ 'screen-width' ] ) ? $prefs[ 'screen-width' ] : $this->centering_tool_defaults[ 'screen-width' ];
            $screen_height = isset( $prefs[ 'screen-height' ] ) ? $prefs[ 'screen-height' ] : $this->centering_tool_defaults[ 'screen-height' ];
            $background = isset( $prefs[ 'background' ] ) ? $prefs[ 'background' ] : $this->centering_tool_defaults[ 'background' ];
            $opacity = isset( $prefs[ 'opacity' ] ) ? $prefs[ 'opacity' ] : $this->centering_tool_defaults[ 'opacity' ];
        } else {
            $prefs = $this->centering_tool_defaults;
            $user_on = false;
            $cell_width = $this->centering_tool_defaults[ 'cell-width' ];
            $cell_height = $this->centering_tool_defaults[ 'cell-height' ];
            $screen_width = $this->centering_tool_defaults[ 'screen-width' ];
            $screen_height = $this->centering_tool_defaults[ 'screen-height' ];
            $background = $this->centering_tool_defaults[ 'background' ];
            $opacity = $this->centering_tool_defaults[ 'opacity' ];
        }

        // Main toggle
        $text = $user_on ? __( 'On', 'dev-debug-tools' ) : __( 'Off', 'dev-debug-tools' );
        $title = $user_on ? __( 'Turn Centering Tool Off', 'dev-debug-tools' ) : __( 'Turn Centering Tool On', 'dev-debug-tools' );

        $wp_admin_bar->add_node( [
            'id'     => 'ddtt-centering-tool',
            'parent' => 'top-secondary',
            'title'  => '&#x271B; <span class="ddtt-centering-tool-label">' . $text . '</span>',
            'href'   => '#',
            'meta'   => [
                'title' => $title,
            ],
        ] );

        // Options
        $options = [
            'cell-width'    => [
                'label' => __( 'Cell Width', 'dev-debug-tools' ),
                'value' => $cell_width,
            ],
            'cell-height'   => [
                'label' => __( 'Cell Height', 'dev-debug-tools' ),
                'value' => $cell_height,
            ],
            'screen-width'  => [
                'label' => __( 'Screen Width', 'dev-debug-tools' ),
                'value' => $screen_width,
            ],
            'screen-height' => [
                'label' => __( 'Screen Height', 'dev-debug-tools' ),
                'value' => $screen_height,
            ],
            'background'    => [
                'label' => __( 'Background', 'dev-debug-tools' ),
                'value' => $background,
            ],
            'opacity'       => [
                'label' => __( 'Opacity (0-1)', 'dev-debug-tools' ),
                'value' => $opacity,
            ],
        ];

        foreach ( $options as $key => $option ) {
            if ( $key === 'opacity' ) {
                $type = 'number';
                $max = 'max="1" step="0.1"';
                $min = 'min="0"';
            } else {
                $type = 'text';
                $max = '';
                $min = '';
            }

            $wp_admin_bar->add_node( [
                'id'     => 'ddtt-ct-' . $key,
                'parent' => 'ddtt-centering-tool',
                'title'  => '<label for="ddtt-ct-' . $key . '">' . esc_html( $option[ 'label' ] ) . '<input type="' . esc_attr( $type ) . '" id="ddtt-ct-' . $key . '" value="' . esc_attr( $option[ 'value' ] ) . '"' . $max . $min . '/></label>',
            ] );
        }
    } // End render_centering_tool()

    
    /**
     * Enqueue the centering tool script on the front end if the user has it enabled
     */
    public function enqueue_centering_tool() {
        if ( ! is_admin() && current_user_can( 'administrator' ) ) {
            $version = Bootstrap::script_version();
            $handle = 'ddtt-admin-bar-centering-tool';

            // delete_user_meta( get_current_user_id(), 'ddtt_centering_tool' );
            $user_prefs = get_user_meta( get_current_user_id(), 'ddtt_centering_tool', true );
            if ( ! empty( $user_prefs ) ) {
                $prefs = filter_var_array( $user_prefs, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            } else {
                $prefs = $this->centering_tool_defaults;
            }

            // Parse width and height values safely
            $width_value  = isset( $prefs[ 'cell-width' ] ) ? (int) str_replace( [ 'px', 'rem' ], '', $prefs[ 'cell-width' ] ) : 1;
            $width_unit   = isset( $prefs[ 'cell-width' ] ) ? str_replace( $width_value, '', $prefs[ 'cell-width' ] ) : 'px';
            $height_value = isset( $prefs[ 'cell-height' ] ) ? (int) str_replace( [ 'px', 'rem' ], '', $prefs[ 'cell-height' ] ) : 1;

            // Ensure screen dimensions exist and are numeric
            $screen_width  = isset( $prefs[ 'screen-width' ] ) ? (float) $prefs[ 'screen-width' ] : 0;
            $screen_height = isset( $prefs[ 'screen-height' ] ) ? (float) $prefs[ 'screen-height' ] : 0;

            // Calculate number of columns and rows safely
            $num_columns = $width_value > 0 ? floor( $screen_width / $width_value ) : 0;
            $num_rows    = $height_value > 0 ? floor( $screen_height / $height_value ) : 0;


            /**
             * JS
             */
            wp_enqueue_script(
                $handle,
                Bootstrap::url( 'inc/admin-area/admin-bar/centering-tool.js' ),
                [ 'jquery' ],
                $version,
                true
            );

            wp_localize_script( $handle, 'ddtt_admin_bar_centering_tool', [
                'ajaxurl'  => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( $this->nonce ),
                'rows'     => $num_rows,
                'columns'  => $num_columns,
                'prefs'    => $prefs,
                'i18n'     => [
                    'on'        => __( 'On', 'dev-debug-tools' ),
                    'off'       => __( 'Off', 'dev-debug-tools' ),
                    'title_on'  => __( 'Turn Centering Tool Off', 'dev-debug-tools' ),
                    'title_off' => __( 'Turn Centering Tool On', 'dev-debug-tools' )
                ],
            ] );


            /**
             * CSS
             */
            wp_enqueue_style(
                $handle,
                Bootstrap::url( 'inc/admin-area/admin-bar/centering-tool.css' ),
                [],
                $version
            );

            $bg_rgba = Helpers::hex_to_rgba( $prefs[ 'background' ], $prefs[ 'opacity' ] );

            $css = '.ddtt-ct-table {
                margin-left: calc(50% - ' . ($width_value * $num_columns / 2) . $width_unit . '); /* Center the grid */
                margin-right: calc(50% - ' . ($width_value * $num_columns / 2) . $width_unit . '); /* Center the grid */
            }
            .ddtt-ct-cell {
                width: ' . $prefs[ 'cell-width' ] . ';
                height: ' . $prefs[ 'cell-height' ] . ';
            }
            .ddtt-ct-cell.ddtt-ct-center {
                width: calc(' . $width_value . ' * 2' . $width_unit . '); /* Double the width for center column */
            }
            #ddtt-ct-top {
                background: ' . $bg_rgba . ';
            }';

            wp_add_inline_style( $handle, $css );
        }
    } // End enqueue_centering_tool()


    /**
     * AJAX handler to save the centering tool preferences
     */
    public function ajax_save_centering_tool() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( __( 'Permission denied.', 'dev-debug-tools' ) );
        }

        $user_id = get_current_user_id();
        $prefs = get_user_meta( $user_id, 'ddtt_centering_tool', true );
        if ( ! empty( $prefs ) ) {
            $prefs = filter_var_array( $prefs, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        } else {
            $prefs = $this->centering_tool_defaults;
        }

        // Save toggle if present
        if ( isset( $_POST[ 'on' ] ) ) {
            $prefs[ 'on' ] = absint( $_POST[ 'on' ] ) ? true : false;
        }

        // Save values if present
        if ( isset( $_POST[ 'cell_width' ] ) ) {
            $prefs[ 'cell-width' ] = sanitize_text_field( wp_unslash( $_POST[ 'cell_width' ] ) );
        }
        if ( isset( $_POST[ 'cell_height' ] ) ) {
            $prefs[ 'cell-height' ] = sanitize_text_field( wp_unslash( $_POST[ 'cell_height' ] ) );
        }
        if ( isset( $_POST[ 'screen_width' ] ) ) {
            $prefs[ 'screen-width' ] = sanitize_text_field( wp_unslash( $_POST[ 'screen_width' ] ) );
        }
        if ( isset( $_POST[ 'screen_height' ] ) ) {
            $prefs[ 'screen-height' ] = sanitize_text_field( wp_unslash( $_POST[ 'screen_height' ] ) );
        }
        if ( isset( $_POST[ 'background' ] ) ) {
            $prefs[ 'background' ] = sanitize_text_field( wp_unslash( $_POST[ 'background' ] ) );
        }
        if ( isset( $_POST[ 'opacity' ] ) ) {
            $prefs[ 'opacity' ] = floatval( $_POST[ 'opacity' ] );
            if ( $prefs[ 'opacity' ] < 0 ) {
                $prefs[ 'opacity' ] = 0;
            } elseif ( $prefs[ 'opacity' ] > 1 ) {
                $prefs[ 'opacity' ] = 1;
            }
        }

        update_user_meta( $user_id, 'ddtt_centering_tool', $prefs );

        wp_send_json_success();
    } // End ajax_save_centering_tool()

}


new AdminBar();