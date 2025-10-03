<?php
/**
 * Logs
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Logs {

    /**
     * Get the sections for the settings page.
     *
     * Returns an array of sections with their titles.
     *
     * @return array
     */
    public static function sections() : array {
        $tabs = [];

        $debug_log_override = get_option( 'ddtt_debug_log_path' );
        if ( empty( $debug_log_override ) && WP_DEBUG_LOG === true ) {
            $debug_log_override = ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG !== true ) ? WP_DEBUG_LOG : WP_CONTENT_DIR . '/debug.log';
        }
        if ( ( ! empty( $debug_log_override ) && is_string( $debug_log_override ) ) ) {
            $tabs[ 'debug' ] = [
                'label' => __( 'Debug Log', 'dev-debug-tools' ),
                'lines' => self::get_total_lines( self::get_path( 'debug' )[ 'abs' ] ),
            ];
        }

        $error_log_override = get_option( 'ddtt_error_log_path' );
        if ( ! empty( $error_log_override ) && is_string( $error_log_override ) ) {
            $tabs[ 'error' ] = [
                'label' => __( 'Error Log', 'dev-debug-tools' ),
                'lines' => self::get_total_lines( self::get_path( 'error' )[ 'abs' ] ),
            ];
        }

        $admin_error_log_override = get_option( 'ddtt_admin_error_log_path' );
        if ( ! empty( $admin_error_log_override ) && is_string( $admin_error_log_override ) ) {
            $tabs[ 'admin-error' ] = [
                'label' => __( 'Admin Error Log', 'dev-debug-tools' ),
                'lines' => self::get_total_lines( self::get_path( 'admin-error' )[ 'abs' ] ),
            ];
        }

        $extra_logs = get_option( 'ddtt_log_files', [] );
        if ( is_array( $extra_logs ) && ! empty( $extra_logs ) ) {
            foreach ( $extra_logs as $key => $path ) {
                $tabs[ 'custom-' . $key ] = [
                    'lines' => self::get_total_lines( self::get_path( 'custom-' . (string) $key )[ 'abs' ] ),
                    'label' => __( 'Custom Log ', 'dev-debug-tools' ) . ++$key,
                ];
            }
        }

        $activity_log = get_option( 'ddtt_activity', [] );
        if ( is_array( $activity_log ) && ! empty( $activity_log ) ) {
            $tabs[ 'activity' ] = [
                'label' => __( 'Activity Log', 'dev-debug-tools' ),
                'lines' => self::get_total_lines( self::get_path( 'activity' )[ 'abs' ] ),
            ];
        }

        // Calculate total lines
        $total_lines = 0;
        foreach ( $tabs as $tab ) {
            $total_lines += (int) ( $tab[ 'lines' ] ?? 0 );
        }

        // Add total to tabs with special key and label
        $tabs[ 'total' ] = [
            'label' => __( 'Total Lines', 'dev-debug-tools' ),
            'lines' => $total_lines,
        ];

        return $tabs;
    } // End sections()


    /**
     * Set the highlight args to be used by stylesheet, settings and error_logs
     *
     * @return array
     */
    public static function highlight_args() {
        // Get the active theme folder
        // $active_theme = str_replace( '%2F', '/', rawurlencode( get_stylesheet() ) );

        // Set the args
        $args = apply_filters( 'ddtt_highlight_debug_log', [
            'fatal-error' => [
                'name'          => __( 'Fatal Error', 'dev-debug-tools' ),
                'keyword'       => 'Fatal',
                'bg_color'      => '#FF0000',
                'font_color'    => '#FFFFFF',
                'priority'      => true,
                'column'        => 'type'
            ],
            'parse-error' => [
                'name'          => __( 'Parse Error', 'dev-debug-tools' ),
                'keyword'       => 'Parse',
                'bg_color'      => '#FF0000',
                'font_color'    => '#FFFFFF',
                'priority'      => true,
                'column'        => 'type'
            ],
            // 'ddtt-plugin' => [
            //     'name'          => 'Dev Debug Tools ' . __( 'Plugin', 'dev-debug-tools' ),
            //     'keyword'       => Bootstrap::textdomain(),
            //     'bg_color'      => '#26BECF',
            //     'font_color'    => '#1E1E1E',
            //     'priority'      => true,
            //     'column'        => 'path'
            // ],
            // 'plugin' => [
            //     'name'          => __( 'Plugin', 'dev-debug-tools' ),
            //     'keyword'       => [ 
            //         plugins_url(),
            //         'Function _load_textdomain_just_in_time was called' 
            //     ],
            //     'bg_color'      => '#0073AA',
            //     'font_color'    => '#FFFFFF',
            //     'priority'      => false,
            //     'column'        => 'path'
            // ],
            // 'theme' => [
            //     'name'          => __( 'Theme', 'dev-debug-tools' ),
            //     'keyword'       => $active_theme,
            //     'bg_color'      => '#006400',
            //     'font_color'    => '#FFFFFF',
            //     'priority'      => false,
            //     'column'        => 'path'
            // ]
        ] );

        return $args;
    } // End highlight_args()


    /**
     * Get the logs that start with [date].
     *
     * @return array
     */
    public static function logs_that_start_with_date_brackets() : array {
        return apply_filters( 'ddtt_logs_that_start_with_date_brackets', [
            'debug',
            'error',
            'admin-error',
            'activity'
        ] );
    } // End logs_that_start_with_date_brackets()


    /**
     * Nonce for clearing the log
     *
     * @var string
     */
    private $nonce = 'ddtt_log_nonce';


    /**
     * Filesystem instance
     *
     * @var \WP_Filesystem_Base|null
     */
    protected static $filesystem;


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?Logs $instance = null;


    /**
     * Get the singleton instance
     *
     * @return self
     */
    public static function instance() : self {
        return self::$instance ??= new self();
    } // End instance()


    /**
     * Constructor
     */
    private function __construct() {
        $this->init_filesystem();
        $this->cache_total_error_count();
        add_action( 'ddtt_header_notices', [ $this, 'render_header_notices' ] );
        $this->handle_button_actions();
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_ddtt_get_log', [ $this, 'ajax_get_log' ] );
        add_action( 'wp_ajax_nopriv_ddtt_get_logs', '__return_false' );
        add_action( 'wp_ajax_ddtt_log_text_wrap', [ $this, 'ajax_log_text_wrap' ] );
        add_action( 'wp_ajax_nopriv_ddtt_log_text_wrap', '__return_false' );
    } // End __construct()


    /**
     * Get the path for a specific log slug.
     *
     * Returns an array with 'abs' (absolute path) and 'url' (URL) keys.
     *
     * @param string $log_slug The slug of the log to get the path for.
     * @return array|false
     */
    public static function get_path( $log_slug = null ) : array|false {
        $upload_dir  = wp_upload_dir();
        $content_dir = WP_CONTENT_DIR;
        $content_url = content_url();
        $textdomain = Bootstrap::textdomain();

        // Default paths
        $path_data = [
            'debug'       => [
                'abs' => $content_dir . '/debug.log',
                'url' => $content_url . '/debug.log',
            ],
            'error'       => [
                'abs' => ABSPATH . 'wp-content/error_log',
                'url' => $content_url . '/error_log',
            ],
            'admin-error' => [
                'abs' => ABSPATH . 'wp-admin/error_log',
                'url' => admin_url( 'error_log' ),
            ],
            'activity'    => [
                'abs' => $upload_dir[ 'basedir' ] . '/' . $textdomain . '/activity.log',
                'url' => $upload_dir[ 'baseurl' ] . '/' . $textdomain . '/activity.log',
            ],
        ];

        $custom_log_files = get_option( 'ddtt_log_files' );
        if ( is_array( $custom_log_files ) ) {
            foreach ( $custom_log_files as $key => $custom_path ) {
                $custom_path = sanitize_text_field( $custom_path );
                if ( str_starts_with( $custom_path, '/' ) ) {
                    $abs = $custom_path;
                    $url = str_starts_with( $custom_path, get_home_path() ) ? site_url( str_replace( get_home_path(), '', $custom_path ) ) : false;
                } else {
                    $abs = get_home_path() . $custom_path;
                    $url = site_url( $custom_path );
                }

                $path_data[ 'custom-' . $key ] = [
                    'abs' => $abs,
                    'url' => $url,
                ];
            }
        }

        $override_options = [
            'debug'       => 'ddtt_debug_log_path',
            'error'       => 'ddtt_error_log_path',
            'admin-error' => 'ddtt_admin_error_log_path'
        ];

        foreach ( $path_data as $key => &$paths ) {
            if ( isset( $override_options[ $key ] ) ) {
                $option = get_option( $override_options[ $key ] );
                if ( ! empty( $option ) && is_string( $option ) ) {
                    $abs = str_starts_with( $option, '/' ) ? $option : get_home_path() . $option;
                    if ( str_starts_with( $abs, get_home_path() ) ) {
                        $relative = ltrim( str_replace( get_home_path(), '', $abs ), '/' );
                        $url = site_url( $relative );
                    } else {
                        $url = false;
                    }

                    $paths = [
                        'abs' => $abs,
                        'url' => $url,
                    ];
                } elseif ( $key == 'debug' && WP_DEBUG_LOG && WP_DEBUG_LOG !== true ) {
                    $abs = str_starts_with( WP_DEBUG_LOG, '/' ) ? WP_DEBUG_LOG : ABSPATH . WP_DEBUG_LOG;
                    if ( str_starts_with( $abs, get_home_path() ) ) {
                        $relative = ltrim( str_replace( get_home_path(), '', $abs ), '/' );
                        $url = site_url( $relative );
                    } else {
                        $url = false;
                    }

                    $paths = [
                        'abs' => $abs,
                        'url' => $url,
                    ];
                } else {
                    continue;
                }
            }
        }


        /**
         * Filter the paths for logs.
         */
        $path_data = apply_filters( 'ddtt_log_paths', $path_data, $log_slug );

        if ( is_null( $log_slug ) ) {
            return $path_data;
        }
        return isset( $path_data[ $log_slug ] ) ? $path_data[ $log_slug ] : false;
    } // End get_path()


    /**
     * Check if we are logging activity
     *
     * @return boolean
     */
    public static function is_logging_activity( $activity_key = '' ) {
        $activities = get_option( 'ddtt_activity' );
        if ( ! empty( $activities ) && is_array( $activities ) ) {
            if ( $activity_key ) {
                return in_array( $activity_key, $activities );
            }
            return true;
        }
        return false;
    } // End is_logging_activity()


    /**
     * Initialize the filesystem
     *
     * This method initializes the filesystem for reading and writing log files.
     * It is called in the constructor to ensure the filesystem is ready for use.
     */
    protected function init_filesystem() {
        global $wp_filesystem;

        if ( ! function_exists( 'request_filesystem_credentials' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        WP_Filesystem();

        if ( is_object( $wp_filesystem ) ) {
            self::$filesystem = $wp_filesystem;
        }
    } // End init_filesystem()


    /**
     * Cache the total error count
     */
    public function cache_total_error_count() {
        $total_lines = $this->sections()[ 'total' ][ 'lines' ] ?? 0;
        update_option( 'ddtt_total_error_count', $total_lines );
    } // End cache_total_error_count()


    /**
     * Render header notices
     *
     * This method is called to render notices in the header.
     * It checks for deleted options and displays a notice if any were deleted.
     */
    public function render_header_notices() {
        if ( AdminMenu::get_current_page_slug() !== 'dev-debug-tools' || AdminMenu::current_tool_slug() !== 'logs' ) {
            return;
        }
        
        if ( get_transient( 'ddtt_log_cleared' ) ) {
            delete_transient( 'ddtt_log_cleared' );
            Helpers::render_notice( __( 'Log cleared successfully.', 'dev-debug-tools' ), 'success' );
        }
    } // End render_header_notices()


    /**
     * Get the current subsection from the URL
     *
     * @return string
     */
    public static function get_current_subsection() : string {
        return isset( $_GET[ 's' ] ) ? sanitize_key( wp_unslash( $_GET[ 's' ] ) ) : 'debug'; // phpcs:ignore
    } // End get_current_subsection()


    /**
     * Get total number of lines in a log file.
     *
     * @param string $file_path Absolute path to the log file.
     * @param int    $max_lines Optional max lines to scan (0 = no limit).
     *
     * @return int Total lines counted (capped by $max_lines if set).
     */
    public static function get_total_lines( $file_path, $max_lines = 0 ) : int {
        if ( ! is_string( $file_path ) || empty( $file_path ) ) {
            return 0;
        }

        if ( ! is_readable( $file_path ) ) {
            return 0;
        }

        $handle = fopen( $file_path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
        if ( ! $handle ) {
            return 0;
        }

        $line_count = 0;
        while ( ! feof( $handle ) ) {
            if ( fgets( $handle ) !== false ) {
                $line_count++;
            }

            if ( $max_lines > 0 && $line_count >= $max_lines ) {
                break;
            }
        }

        fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

        return $line_count;
    } // End get_total_lines()


    /**
     * Get file data such as size and line count
     *
     * @param string $file The file path.
     * @return array
     */
    public static function get_file_info( $file ) {
        if ( ! file_exists( $file ) ) {
            return [];
        }

        $line_count = self::get_total_lines( $file );
        $file_size = filesize( $file );
        $last_modified = filemtime( $file );
        

        return [
            [
                'label' => __( 'Line Count', 'dev-debug-tools' ),
                'value' => $line_count,
            ],
            [
                'label' => __( 'File Size', 'dev-debug-tools' ),
                'value' => $file_size,
            ],
            [
                'label' => __( 'Last Modified', 'dev-debug-tools' ),
                'value' => Helpers::convert_timezone( $last_modified, 'F j, Y g:i A' ),
            ],
            [
                'label' => __( 'Path', 'dev-debug-tools' ),
                'value' => Helpers::maybe_redact( $file, true),
            ],
        ];
    } // End get_file_info()


    /**
     * Render file information
     *
     * @param array $path The file path information.
     */
    public static function render_file_info( $abs_path ) {
        $file_data = self::get_file_info( $abs_path );
        if ( $file_data ) : 
            $last_key = array_key_last( $file_data );
            ?>
            <div class="ddtt-file-info">
                <div class="ddtt-file-data">
                    <?php foreach ( $file_data as $index => $data ) : ?>
                        <div class="ddtt-file-data-item <?php echo esc_attr( 'ddtt-file-data-' . $index ); ?>">
                            <span class="ddtt-file-data-label"><?php echo esc_html( $data[ 'label' ] ); ?>:</span>
                            <strong><?php echo wp_kses_post( is_array( $data[ 'value' ] ) ? implode( ', ', $data[ 'value' ] ) : $data[ 'value' ] ); ?></strong>
                        </div>
                        <?php if ( $index !== $last_key ) : ?>
                            <span class="ddtt-separator">|</span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif;
    } // End render_file_info()


    /**
     * Highlight search terms in the given text.
     *
     * @param string $text The text to highlight within.
     * @param array  $terms The terms to highlight.
     * @return string The text with highlighted terms.
     */
    public static function highlight_search_terms( $text, $terms = [] ) {
        if ( empty( $terms ) ) {
            return $text;
        }
        $escaped_terms = array_map( function( $term ) {
            return preg_quote( $term, '/' );
        }, $terms );
        $pattern = '/' . implode( '|', $escaped_terms ) . '/i';

        return preg_replace_callback( $pattern, function( $matches ) {
            return '<i class="ddtt-highlight-search">' . esc_html( $matches[0] ) . '</i>';
        }, $text );
    } // End highlight_search_terms()


    /**
     * Render the easy reader activity log
     *
     * @param array $items The array of item lines.
     * @param array $search_terms Terms to highlight in the output.
     */
    public static function render_easy_activity_log( $items, $search_terms ) {
        if ( ! class_exists( 'Activity_Log' ) ) {
            require_once Bootstrap::path( 'inc/hub/pages/tools/logs/class-activity.php' );
        }

        // Get data from Activity_Log class
        $activities = Activity_Log::activities();
        $highlight_args = Activity_Log::highlight_args();

        // Parse
        $parsed_items = [];
        foreach ( $items as $line_num => &$item ) {
            $this_item_lines = explode( "\n", $item );
            $first = $this_item_lines[0] ?? '';

            // Get the datetime 
            preg_match( '/\[(\d{2}-[A-Za-z]{3}-\d{4} \d{2}:\d{2}:\d{2}) [^\]]+\]/', $first, $datetime_match );
            $datetime_raw = $datetime_match[1] ?? '';
            $datetime = $datetime_raw ? Helpers::convert_timezone( $datetime_raw ) : '';

            // Remove datetime from log line
            $line_without_datetime = preg_replace( '/^\[.*?\]\s*/', '', $first );

            // Extract activity and user
            if ( preg_match( '/^([^:]+):\s*([^(]+)\s*\(([^ ]+)\s*-\s*ID:\s*(\d+)\)\s*\|\s*(.*)$/', $line_without_datetime, $matches ) ) {
                $activity = trim( $matches[1] );
                $user_performing = $matches[2] . '<br>' . $matches[3] . '<br>User ID: ' . $matches[4];
                $notes_raw = trim( $matches[5] );
            } else {
                $activity = '';
                $user_performing = '';
                $notes_raw = '';
            }

            // Highlight search terms in the first line
            $activity  = self::highlight_search_terms( $activity, $search_terms );
            $user_performing = self::highlight_search_terms( $user_performing, $search_terms );
            $notes_raw = self::highlight_search_terms( $notes_raw, $search_terms );

            // Format notes: split on '|' and convert to line breaks
            $notes_parts = array_map( 'trim', explode( '|', $notes_raw ) );
            $notes = implode( '<br>', $notes_parts );

            // The class
            $group_class = '';
            $activity_class = '';
            foreach ( $activities as $group => $activity_data ) {
                foreach ( $activity_data as $a_key => $data ) {
                    if ( $data[ 'action' ] == $activity ) {
                        $group_class = esc_attr( $group );
                        $activity_class = esc_attr( $a_key );
                        break 2;
                    }
                }
            }

            // Build parsed item
            $parsed_item = [
                'line_num'          => $line_num,
                'datetime'          => $datetime,
                'activity'          => $activity,
                'user_performing'   => $user_performing,
                'notes'             => $notes,
                'group_class'       => $group_class,
                'activity_class'    => $activity_class,
            ];

            $parsed_items[ $line_num ] = $parsed_item;
        }
        ?>
        <div id="ddtt-log-easy">
            <table class="ddtt-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Line #', 'dev-debug-tools' ); ?></th>
                        <th><?php esc_html_e( 'Date/Time', 'dev-debug-tools' ); ?></th>
                        <th><?php esc_html_e( 'Activity', 'dev-debug-tools' ); ?></th>
                        <th><?php esc_html_e( 'User Performing Activity', 'dev-debug-tools' ); ?></th>
                        <th><?php esc_html_e( 'Notes', 'dev-debug-tools' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    if ( ! empty( $parsed_items ) ) {
                        foreach ( $parsed_items as $item ) {

                            // Ensure group_class exists and has highlight_args
                            $group_class = isset( $item[ 'group_class' ] ) ? $item[ 'group_class' ] : '';
                            $bg_color    = isset( $highlight_args[ $group_class ][ 'bg_color' ] ) ? $highlight_args[ $group_class ][ 'bg_color' ] : '';
                            $font_color  = isset( $highlight_args[ $group_class ][ 'font_color' ] ) ? $highlight_args[ $group_class ][ 'font_color' ] : '';
                            ?>
                            <tr class="ddtt-item-activity-<?php echo esc_attr( isset( $item[ 'activity_class' ] ) ? $item[ 'activity_class' ] : '' ); ?> ddtt-item-group-<?php echo esc_attr( $group_class ); ?>" style="background-color: <?php echo esc_attr( $bg_color ); ?>; color: <?php echo esc_attr( $font_color ); ?>;">
                                <td><?php echo esc_attr( isset( $item[ 'line_num' ] ) ? $item[ 'line_num' ] : '' ); ?></td>
                                <td><?php echo esc_html( isset( $item[ 'datetime' ] ) ? $item[ 'datetime' ] : '' ); ?></td>
                                <td><?php echo wp_kses_post( isset( $item[ 'activity' ] ) ? $item[ 'activity' ] : '' ); ?></td>
                                <td><?php echo wp_kses_post( isset( $item[ 'user_performing' ] ) ? $item[ 'user_performing' ] : '' ); ?></td>
                                <td><?php echo wp_kses_post( isset( $item[ 'notes' ] ) ? $item[ 'notes' ] : '' ); ?></td>
                            </tr>
                            <?php
                        }
                    }
                ?>
                </tbody>
            </table>
        </div>
        <?php
    } // End render_easy_activity_log()
    

    /**
     * Render the easy reader error log
     *
     * @param string $subsection The log subsection (e.g., 'debug', 'error').
     * @param array  $errors The array of error lines.
     * @param array  $search_terms Terms to highlight in the output.
     * @param bool   $combine_errors Whether to combine identical errors and show a count.
     */
    public static function render_easy_error_log( $subsection, $errors, $search_terms, $combine_errors = false ) {
        // Highlight args
        $highlight_args = self::highlight_args();

        // Help Map
        if ( ! class_exists( 'Help' ) ) {
            require_once Bootstrap::path( 'inc/helpers/help-map.php' );
        }
        $help_map = Help::debug_log_map();

        // Parse
        $parsed_errors = [];
        foreach ( $errors as $line_num => &$error ) {
            $this_error_lines = explode( "\n", $error );
            if ( end( $this_error_lines ) === '' ) {
                array_pop( $this_error_lines );
            }
            $first = $this_error_lines[0] ?? '';
            $ignore_help_btn = false;

            $parsed_error = apply_filters( 'ddtt_easy_log_parse_error', null, $first, $subsection );
            if ( $parsed_error === null ) {

                // Get the datetime 
                preg_match( '/\[(\d{2}-[A-Za-z]{3}-\d{4} \d{2}:\d{2}:\d{2}) [^\]]+\]/', $first, $datetime_match );
                $datetime_raw = $datetime_match[1] ?? '';
                $datetime = $datetime_raw ? Helpers::convert_timezone( $datetime_raw ) : '';

                // Remove datetime bracket and error type
                $message = trim( preg_replace( [
                    '/^\[.*?\]\s*/', // Remove leading datetime bracket and whitespace
                    '/^PHP.*?:\s*/'  // Remove leading PHP error type and colon
                ], '', $first ) );

                // Wrap DDTT LOG: prefix if present
                $message = preg_replace(
                    '/^(DDTT LOG:)/',
                    '<span class="ddtt-note-prefix">$1</span>',
                    $message
                );

                // Match error type
                preg_match( '/PHP(.*?)\:/s', $first, $type_match );
                if ( $type_match[1] ) {
                    $type = $type_match[1];
                } elseif ( strpos( $message, 'DDTT LOG:' ) !== false ) {
                    $ignore_help_btn = true;
                    $type = __( 'Note', 'dev-debug-tools' );
                } else {
                    $type = __( 'Other', 'dev-debug-tools' );
                }
                $type = trim( $type );

                // Highlight search terms in the first line
                $type    = self::highlight_search_terms( $type, $search_terms );
                $message = self::highlight_search_terms( $message, $search_terms );

                // Match file path and line number, requiring a slash in the path to avoid matching 'in object'
                if ( preg_match( '/in ((?:\/|[A-Za-z]:\\\\)[^:]+)(?::| on line )(\d+)/', $first, $file_match ) ) {
                    $file = $file_match[1];
                    $this_error_line = $file_match[2];
                } else {
                    $file = '';
                    $this_error_line = '';
                }

                // Remove the matched part from $message, accounting for either pattern
                if ( $file && $this_error_line ) {
                    $pattern = '\s*in\s+' . preg_quote( $file, '/' ) . '(?::| on line )' . preg_quote( $this_error_line, '/' ) . '\s*';
                    $message = preg_replace( '/'. $pattern .'/', '', $message );
                }

                // Match plugin or theme source
                if ( strpos( $file, '/plugins/' ) !== false ) {
                    preg_match( '#/plugins/([^/]+)/#', $file, $source_match );
                    $plugin_slug = $source_match[1] ?? '';
                    if ( $plugin_slug ) {
                        if ( ! function_exists( 'get_plugins' ) ) {
                            require_once ABSPATH . 'wp-admin/includes/plugin.php';
                        }
                        $all_plugins = get_plugins();
                        $plugin_name = '';

                        foreach ( $all_plugins as $plugin_file => $plugin_data ) {
                            if ( strpos( $plugin_file, $plugin_slug . '/' ) === 0 || strpos( $plugin_file, $plugin_slug . '.' ) === 0 ) {
                                $plugin_name = $plugin_data[ 'Name' ];
                                break;
                            }
                        }

                        $source = $plugin_name
                            ? sprintf( 
                                /* translators: %s: Plugin name or slug */
                                __( 'Plugin: %s', 'dev-debug-tools' ), $plugin_name )
                            : sprintf( 
                                /* translators: %s: Plugin name or slug */
                                __( 'Plugin: %s', 'dev-debug-tools' ), $plugin_slug );
                    } else {
                        $source = __( 'Plugin: Unknown', 'dev-debug-tools' );
                    }
                } elseif ( strpos( $file, '/themes/' ) !== false ) {
                    preg_match( '#/themes/([^/]+)/#', $file, $source_match );
                    $theme_slug = $source_match[1] ?? '';
                    if ( $theme_slug ) {
                        $theme = wp_get_theme( $theme_slug );
                        $source = $theme->exists()
                            ? sprintf( 
                                /* translators: %s: Theme name or slug */
                                __( 'Theme: %s', 'dev-debug-tools' ), $theme->get( 'Name' ) )
                            : sprintf( 
                                /* translators: %s: Theme name or slug */
                                __( 'Theme: %s', 'dev-debug-tools' ), $theme_slug );
                    } else {
                        $source = __( 'Theme: Unknown', 'dev-debug-tools' );
                    }
                } else {
                    $source = '';
                }

                // Let's see if we have a help map entry for this error
                $help_data = [];
                if ( ! $ignore_help_btn ) {
                    foreach ( $help_map as $regex => $map ) {
                        if ( preg_match( $regex, $message ) ) {
                            $help_data = $map;
                            break;
                        }
                    }
                } else {
                    $help_data = false;
                }

                // The results
                $parsed_error = [
                    'datetime'  => $datetime,
                    'type'      => $type,
                    'message'   => $message,
                    'file'      => $file,
                    'line'      => $this_error_line,
                    'source'    => $source,
                    'help'      => $help_data,
                    'stack'     => count( $this_error_lines ) > 1 ? implode( "\n", array_slice( $this_error_lines, 1 ) ) : '',
                ];
            }

            // Add the line number and class regardless
            $parsed_error[ 'line_num' ] = $line_num;
            $parsed_error[ 'class' ] = strtolower( str_replace( ' ', '-', $type ) );

            // Normalize for deduplication
            if ( $combine_errors ) {
                $key = md5( $parsed_error[ 'type' ] . $parsed_error[ 'message' ] . $parsed_error[ 'file' ] . $parsed_error[ 'line' ] . $parsed_error[ 'stack' ] );
                $parsed_error[ 'count' ] = 1;
                if ( ! isset( $parsed_errors[ $key ] ) ) {
                    $parsed_errors[ $key ] = $parsed_error;
                } else {
                    $parsed_errors[ $key ][ 'count' ]++;
                }
                
            } else {
                $key = $line_num;
                $parsed_errors[ $key ] = $parsed_error;
            }
        }

        // Available search options
        $error_help_search_options = apply_filters( 'ddtt_error_help_search_options', [
            [
                'label' => __( 'Search Google', 'dev-debug-tools' ),
                'url'   => 'https://www.google.com/search?q={string}',
            ]
        ] );
        ?>
        <div id="ddtt-log-easy">
            <table class="ddtt-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Line #', 'dev-debug-tools' ); ?></th>
                        <th><?php esc_html_e( 'Date/Time', 'dev-debug-tools' ); ?></th>
                        <th><?php esc_html_e( 'Type', 'dev-debug-tools' ); ?></th>
                        <th><?php esc_html_e( 'Error', 'dev-debug-tools' ); ?></th>
                        <?php if ( $combine_errors ) : ?>
                            <th class="ddtt-log-error-count-th"><?php esc_html_e( 'Qty', 'dev-debug-tools' ); ?></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ( ! empty( $parsed_errors ) ) {
                        foreach ( $parsed_errors as $error ) {

                            $display_stack = '';

                            if ( isset( $error[ 'stack' ] ) && $error[ 'stack' ] ) {
                                $abspath = untrailingslashit( ABSPATH );
                                $error[ 'stack' ] = str_replace( $abspath, '', $error[ 'stack' ] );
                                $display_stack = Helpers::truncate_string( $error[ 'stack' ] );
                            }

                            // Safe highlight colors
                            $error_class = isset( $error[ 'class' ] ) ? $error[ 'class' ] : '';
                            $bg_color    = isset( $highlight_args[ $error_class ][ 'bg_color' ] ) ? $highlight_args[ $error_class ][ 'bg_color' ] : '';
                            $font_color  = isset( $highlight_args[ $error_class ][ 'font_color' ] ) ? $highlight_args[ $error_class ][ 'font_color' ] : '';
                            ?>
                            <tr class="ddtt-error-type-<?php echo esc_attr( $error_class ); ?> ddtt-has-help-dialog" style="background-color: <?php echo esc_attr( $bg_color ); ?>; color: <?php echo esc_attr( $font_color ); ?>;">
                                <td><?php echo esc_attr( isset( $error[ 'line_num' ] ) ? $error[ 'line_num' ] : '' ); ?></td>
                                <td><?php echo esc_html( isset( $error[ 'datetime' ] ) ? $error[ 'datetime' ] : '' ); ?></td>
                                <td><?php echo esc_html( isset( $error[ 'type' ] ) ? $error[ 'type' ] : '' ); ?></td>
                                <td>
                                    <div class="ddtt-log-error-message"><?php echo wp_kses_post( isset( $error[ 'message' ] ) ? $error[ 'message' ] : '' ); ?></div>

                                    <?php if ( isset( $error[ 'help' ] ) && is_array( $error[ 'help' ] ) ) : ?>
                                        <div class="ddtt-log-error-help">
                                            <button type="button" class="ddtt-button ddtt-help-toggle<?php echo ( empty( $error[ 'help' ] ) ) ? ' missing-help' : ''; ?>" aria-expanded="false" aria-controls="ddtt-help-content-<?php echo esc_attr( isset( $error[ 'line_num' ] ) ? $error[ 'line_num' ] : '' ); ?>">
                                                <?php echo esc_html__( 'Help me with this error', 'dev-debug-tools' ); ?>
                                            </button>
                                            <div id="ddtt-help-content-<?php echo esc_attr( isset( $error[ 'line_num' ] ) ? $error[ 'line_num' ] : '' ); ?>" class="ddtt-help-content" hidden>
                                                <button type="button" class="ddtt-help-close" aria-label="Close help">&times;</button>
                                                <?php if ( ! empty( $error[ 'help' ] ) ) : ?>
                                                    <p><?php echo esc_html( isset( $error[ 'help' ][ 'desc' ] ) ? $error[ 'help' ][ 'desc' ] : '' ); ?></p>
                                                    <p>
                                                        <a href="<?php echo esc_url( isset( $error[ 'help' ][ 'link' ] ) ? $error[ 'help' ][ 'link' ] : '#' ); ?>" target="_blank" rel="noopener noreferrer">
                                                            <?php echo esc_html__( 'Learn more', 'dev-debug-tools' ); ?>
                                                            <span class="dashicons dashicons-external"></span>
                                                        </a>
                                                    </p>
                                                <?php else : ?>
                                                    <p><?php echo wp_kses_post(
                                                        sprintf(
                                                            /* translators: %s: link HTML */
                                                            __( 'No specific help available for this error. You may add your own help notes using the %s hook. Otherwise, try searching online:', 'dev-debug-tools' ),
                                                            '"<a href="https://pluginrx.com/docs/plugin/dev-debug-tools/#ddtt_help_map_debug_log" target="_blank" rel="noopener noreferrer">ddtt_help_map_debug_log</a>"'
                                                        )
                                                    ); ?></p>
                                                <?php endif; ?>

                                                <?php
                                                if ( isset( $error_help_search_options ) && is_array( $error_help_search_options ) ) {
                                                    foreach ( $error_help_search_options as $option_data ) {
                                                        $search_url = str_replace( '{string}', urlencode( isset( $error[ 'message' ] ) ? $error[ 'message' ] : '' ), isset( $option_data[ 'url' ] ) ? $option_data[ 'url' ] : '#' );
                                                        echo '<a class="ddtt-help-search-links" href="' . esc_url( $search_url ) . '" target="_blank" rel="noopener noreferrer">'
                                                            . esc_html( isset( $option_data[ 'label' ] ) ? $option_data[ 'label' ] : '' )
                                                            . ' <span class="dashicons dashicons-external"></span>'
                                                            . '</a>';
                                                    }
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ( isset( $error[ 'source' ] ) && $error[ 'source' ] ) : ?>
                                        <div class="ddtt-log-error-source"><?php echo esc_html( $error[ 'source' ] ); ?></div>
                                    <?php endif; ?>

                                    <?php if ( isset( $error[ 'file' ] ) && $error[ 'file' ] ) : ?>
                                        <div class="ddtt-log-error-file"><?php echo esc_html__( 'File: ', 'dev-debug-tools' ) . wp_kses_post( Helpers::maybe_redact( $error[ 'file' ], true ) ); ?></div>
                                    <?php endif; ?>

                                    <?php if ( isset( $error[ 'line' ] ) && $error[ 'line' ] ) : ?>
                                        <div class="ddtt-log-error-line"><?php echo esc_html__( 'Line: ', 'dev-debug-tools' ) . esc_html( $error[ 'line' ] ); ?></div>
                                    <?php endif; ?>

                                    <?php if ( $display_stack ) : ?>
                                        <div class="ddtt-log-error-stack"><pre><?php echo wp_kses_post( $display_stack ); ?></pre></div>
                                    <?php endif; ?>
                                </td>

                                <?php if ( isset( $combine_errors ) && $combine_errors ) : ?>
                                    <td class="ddtt-log-error-count-td"><div class="ddtt-log-error-count">x <?php echo esc_html( isset( $error[ 'count' ] ) ? $error[ 'count' ] : '' ); ?></div></td>
                                <?php endif; ?>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                    </tbody>
            </table>
        </div>
        <?php
    } // End render_easy_error_log()


    /**
     * Render the easy reader log
     *
     * @param array $log_viewer_customizations Customizations for the log viewer.
     */
    public static function render_easy_log( $subsection, $abs_path, $log_viewer_customizations = [] ) {
        if ( ! self::$filesystem ) {
            echo '<p>' . esc_html__( 'Filesystem not initialized.', 'dev-debug-tools' ) . '</p>';
            return;
        }

        // Get the content
        $log_contents = '';
        if ( isset( $abs_path ) && self::$filesystem->exists( $abs_path ) ) {
            $log_contents = self::$filesystem->get_contents( $abs_path );

            if ( $log_contents === '' || $log_contents === false ) {
                echo '<p>' . esc_html__( 'The log file is empty.', 'dev-debug-tools' ) . '</p>';
                return;
            }
        } else {
            echo '<p>' . esc_html__( 'Log file not found.', 'dev-debug-tools' ) . '</p>';
            return;
        }

        // Get the lines
        $lines = preg_split( '/\r\n|\r|\n/', $log_contents );

        $errors = [];
        $current_error = [];
        $start_line = 0;

        $subsections_with_dates = self::logs_that_start_with_date_brackets();

        $regex = apply_filters( 'ddtt_log_viewer_regex', null, $abs_path, $subsection );
        if ( $regex === null && in_array( $subsection, $subsections_with_dates ) ) {
            $regex = '/^\[.*?\]/';
        }

        foreach ( array_values( $lines ) as $i => $line ) {
            $line_num = $i + 1;

            if ( $regex && preg_match( $regex, $line ) ) {
                if ( $current_error ) {
                    $errors[ $start_line ] = implode( "\n", $current_error );
                }
                $current_error = [ $line ];
                $start_line = $line_num;
            } elseif ( $regex ) {
                if ( ! empty( $current_error ) ) {
                    $current_error[] = $line;
                }
            } else {
                $errors[ $line_num ] = $line;
            }
        }

        if ( $current_error ) {
            $errors[ $start_line ] = implode( "\n", $current_error );
        }

        // Get search and filter terms
        $search_terms = [];
        if ( ! empty( $log_viewer_customizations[ 'search' ] ) ) {
            $search_terms = array_filter( array_map( 'trim', explode( ',', $log_viewer_customizations[ 'search' ] ) ) );
        }

        $filter_terms = [];
        if ( ! empty( $log_viewer_customizations[ 'filter' ] ) ) {
            $filter_terms = array_filter( array_map( 'trim', explode( ',', $log_viewer_customizations[ 'filter' ] ) ) );
        }

        // Filter errors based on search and filter terms in a single pass.
        if ( ! empty( $search_terms ) || ! empty( $filter_terms ) ) {
            $errors = array_filter( $errors, function( $error ) use ( $search_terms, $filter_terms ) {
                $lower_error = mb_strtolower( $error );

                // First, check for filter terms. If a match is found, exclude the error immediately.
                if ( ! empty( $filter_terms ) ) {
                    foreach ( $filter_terms as $term ) {
                        if ( mb_strpos( $lower_error, mb_strtolower( $term ) ) !== false ) {
                            return false; // Exclude if a filter term is found
                        }
                    }
                }

                // If we have search terms, the error MUST contain at least one.
                if ( ! empty( $search_terms ) ) {
                    foreach ( $search_terms as $term ) {
                        if ( mb_strpos( $lower_error, mb_strtolower( $term ) ) !== false ) {
                            return true; // Include because a search term was found (and it passed the filter)
                        }
                    }
                    return false; // Exclude because no search terms were found
                }

                // If we are here, it means no filter terms were matched and there were no search terms.
                // In this case (filter-only), we keep the error.
                return true;
            });
        }

        // Handle sorting and limiting per page
        $sort_order = $log_viewer_customizations[ 'sort' ] ?? 'asc';
        $total_errors = count( $errors );
        $per_page = isset( $log_viewer_customizations[ 'per_page' ] ) ? absint( $log_viewer_customizations[ 'per_page' ] ) : 100;

        // Always slice to get the last $per_page errors
        if ( $total_errors > $per_page ) {
            $errors = array_slice( $errors, -$per_page, null, true );
        }

        // Determine final order
        if ( 'asc' === $sort_order ) {
            $final_errors = $errors; // oldest-to-newest
        } else {
            $final_errors = array_reverse( $errors, true ); // newest-to-oldest
        }

        // Render the activity log or the error log
        if ( $subsection == 'activity' ) {
            self::render_easy_activity_log( $final_errors, $search_terms );
        } else {
             $combine_errors = isset( $log_viewer_customizations[ 'combine' ] ) ? $log_viewer_customizations[ 'combine' ] : true;
            self::render_easy_error_log( $subsection, $final_errors, $search_terms, $combine_errors );
        }
    } // End render_easy_log()


    /**
     * Render the raw log
     *
     * @param array $log_viewer_customizations Customizations for the log viewer.
     */
    public static function render_raw_log( $subsection, $abs_path, $log_viewer_customizations = [] ) {
        if ( ! self::$filesystem ) {
            echo '<p>' . esc_html__( 'Filesystem not initialized.', 'dev-debug-tools' ) . '</p>';
            return;
        }

        // Get the content
        $log_contents = '';
        if ( isset( $abs_path ) && self::$filesystem->exists( $abs_path ) ) {
            $log_contents = self::$filesystem->get_contents( $abs_path );
        } else if ( ! self::$filesystem->exists( $abs_path ) ) {
            echo '<p>' . esc_html__( 'Log file not found.', 'dev-debug-tools' ) . '</p>';
            return;
        } elseif ( $log_contents == '' ) {
            echo '<p>' . esc_html__( 'The log file is empty.', 'dev-debug-tools' ) . '</p>';
            return;
        }

        // Get the lines
        $lines = preg_split( '/\r\n|\r|\n/', $log_contents );

        $errors = [];
        $current_error = [];

        $subsections_with_dates = self::logs_that_start_with_date_brackets();

        $regex = apply_filters( 'ddtt_log_viewer_regex', null, $abs_path, $subsection );
        if ( $regex === null && in_array( $subsection, $subsections_with_dates ) ) {
            $regex = '/^\[.*?\]/';
        }

        foreach ( $lines as $line ) {
            if ( $regex && preg_match( $regex, $line ) ) {
                if ( $current_error ) {
                    $errors[] = implode( "\n", $current_error );
                }
                $current_error = [ $line ];
            } elseif ( $regex ) {
                if ( ! empty( $current_error ) && trim( $line ) !== '' ) {
                    $current_error[] = $line;
                }
            } else {
                // No regex: treat each line as its own error
                $errors[] = $line;
            }
        }

        if ( $current_error ) {
            $errors[] = implode( "\n", $current_error );
        }

        // Get search and filter terms
        $search_terms = [];
        if ( ! empty( $log_viewer_customizations[ 'search' ] ) ) {
            $search_terms = array_filter( array_map( 'trim', explode( ',', $log_viewer_customizations[ 'search' ] ) ) );
        }

        $filter_terms = [];
        if ( ! empty( $log_viewer_customizations[ 'filter' ] ) ) {
            $filter_terms = array_filter( array_map( 'trim', explode( ',', $log_viewer_customizations[ 'filter' ] ) ) );
        }

        // Filter errors based on search and filter terms in a single pass.
        if ( ! empty( $search_terms ) || ! empty( $filter_terms ) ) {
            $errors = array_filter( $errors, function( $error ) use ( $search_terms, $filter_terms ) {
                $lower_error = mb_strtolower( $error );

                // First, check for filter terms. If a match is found, exclude the error immediately.
                if ( ! empty( $filter_terms ) ) {
                    foreach ( $filter_terms as $term ) {
                        if ( mb_strpos( $lower_error, mb_strtolower( $term ) ) !== false ) {
                            return false; // Exclude if a filter term is found
                        }
                    }
                }

                // If we have search terms, the error MUST contain at least one.
                if ( ! empty( $search_terms ) ) {
                    foreach ( $search_terms as $term ) {
                        if ( mb_strpos( $lower_error, mb_strtolower( $term ) ) !== false ) {
                            return true; // Include because a search term was found (and it passed the filter)
                        }
                    }
                    return false; // Exclude because no search terms were found
                }

                // If we are here, it means no filter terms were matched and there were no search terms.
                // In this case (filter-only), we keep the error.
                return true;
            });
        }

        // Handle sorting and limiting per page
        $sort_order = $log_viewer_customizations[ 'sort' ] ?? 'asc';
        
        $total_errors = count( $errors );
        $per_page = isset( $log_viewer_customizations[ 'per_page' ] ) ? absint( $log_viewer_customizations[ 'per_page' ] ) : 100;

        if ( $total_errors > $per_page ) {
            $errors = array_slice( $errors, -$per_page );
        }

        // Determine final order
        if ( 'asc' === $sort_order ) {
            $final_errors = $errors;
            $line_number  = $total_errors - count( $errors ) + 1; // Start numbering correctly
        } else {
            $final_errors = array_reverse( $errors );
            $line_number  = $total_errors; // Start numbering from the last line
        }

        // Highlight terms
        $highlighted_output = function( $text, $terms ) {
            if ( empty( $terms ) ) {
                return esc_html( $text );
            }
            $escaped_terms = array_map( function( $term ) {
                return preg_quote( $term, '/' );
            }, $terms );
            $pattern = '/' . implode( '|', $escaped_terms ) . '/i';

            return preg_replace_callback( $pattern, function( $matches ) {
                return '<i class="ddtt-highlight-search">' . esc_html( $matches[0] ) . '</i>';
            }, esc_html( $text ) );
        };

        // Wrap text
        $wrap_text = isset( $log_viewer_customizations[ 'wrap_text' ] ) ? (bool) $log_viewer_customizations[ 'wrap_text' ] : false;
        ?>
        <label id="ddtt-wrap-log-text-label" for="ddtt-wrap-log-text">
            <?php esc_html_e( 'Wrap Text', 'dev-debug-tools' ); ?>
            <input type="checkbox" id="ddtt-wrap-log-text" name="ddtt_wrap_log_text" value="1"<?php checked( $wrap_text ); ?>>
        </label>

        <div id="ddtt-log-raw" class="<?php echo $wrap_text ? esc_html( 'ddtt-log-wrap' ) : ''; ?>">
            <pre>
                <?php
                    foreach ( array_values( $final_errors ) as $error ) {
                        $error_lines = explode( "\n", $error );
                        foreach ( $error_lines as $line ) {
                            echo wp_kses_post( '<span class="ddtt-log-line"><span class="ddtt-line-num" aria-hidden="true">'
                                . str_pad( $line_number, 4, ' ', STR_PAD_LEFT ) . ':</span> '
                                . $highlighted_output( $line, $search_terms )
                                . '</span>' );

                            if ( 'asc' === $sort_order ) {
                                $line_number++;
                            } else {
                                $line_number--;
                            }
                        }
                    }
                ?>
            </pre>
        </div>
        <!--<p class="ddtt-desc"><?php esc_html_e( 'Note: Empty lines have been removed.', 'dev-debug-tools' ); ?></p>-->
        <?php
    } // End render_raw_log()


    /**
     * Render the debug log
     *
     * @param array $log_viewer_customizations Customizations for the log viewer.
     */
    public static function render_log( $subsection, $log_viewer_customizations = [] ) {
        $path = self::get_path( $subsection );
        $type = isset( $log_viewer_customizations[ 'type' ] ) ? sanitize_key( $log_viewer_customizations[ 'type' ] ) : 'easy';
        ?>
        <?php self::render_file_info( $path[ 'abs' ] ); ?>

        <div id="ddtt-log-pane" class="ddtt-log-pane-<?php echo esc_attr( $type ); ?>">
            <?php
            $method = 'render_' . $type . '_log';
            if ( method_exists( self::class, $method ) ) {
                self::$method( $subsection, $path[ 'abs' ], $log_viewer_customizations );
            } else {
                echo esc_html__( 'Log type not found.', 'dev-debug-tools' );
            }
            ?>
        </div>
        <?php
    } // End render_log()


    /**
     * Handle log actions like clearing or downloading logs
     *
     * This method processes the form submissions for clearing or downloading logs.
     * It verifies the nonce and performs the requested action.
     */
    public function handle_button_actions() {
        if ( ! is_admin() || ! current_user_can( 'manage_options' ) || ! Helpers::is_dev() ) {
            return;
        }

        if ( ! isset( $_POST['ddtt_log_action'] ) ) {
            return;
        }

        check_admin_referer( 'ddtt_log_action', $this->nonce );

        $action     = isset( $_POST['ddtt_log_action'] ) ? sanitize_text_field( wp_unslash( $_POST['ddtt_log_action'] ) ) : '';
        $subsection = isset( $_POST['subsection'] ) ? sanitize_key( wp_unslash( $_POST['subsection'] ) ) : '';

        $paths     = self::get_path( $subsection );
        $file_path = isset( $paths['abs'] ) ? $paths['abs'] : '';

        // Init WP_Filesystem
        global $wp_filesystem;
        if ( ! $wp_filesystem ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }

        if ( $action === 'clear_log' ) {
            if ( $wp_filesystem->exists( $file_path ) ) {
                $wp_filesystem->put_contents( $file_path, '' ); // clears file contents
            }
            delete_option( 'ddtt_total_error_count' );
            set_transient( 'ddtt_log_cleared', true, 30 );
            wp_safe_redirect( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : admin_url() );
            exit;
        }

        if ( $action === 'download_log' ) {
            if ( $wp_filesystem->exists( $file_path ) ) {
                $size = filesize( $file_path ); // still safe to use for headers

                header( 'Content-Description: File Transfer' );
                header( 'Content-Type: text/plain' );
                header( 'Content-Disposition: attachment; filename=' . basename( $file_path ) );
                header( 'Content-Length: ' . $size );

                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Raw file output required for download
                echo $wp_filesystem->get_contents( $file_path );
                exit;
            }
        }
    } // End handle_button_actions()


    /**
     * Enqueue assets
     *
     * @param string $hook The current admin page hook.
     * @return void
     */
    public function enqueue_assets( $hook ) : void {
        if ( ! AdminMenu::is_current_screen( $hook, 'tools', 'logs' ) ) {
            return;
        }
        
        wp_localize_script( 'ddtt-tool-logs', 'ddtt_logs', [
            'subsection' => self::get_current_subsection(),
            'nonce'      => wp_create_nonce( $this->nonce ),            
            'i18n'       => [
                'loading' => __( 'Please wait. Loading log', 'dev-debug-tools' ),
            ],
        ] );
    } // End enqueue_assets()


    /**
     * Handle AJAX request to get the log
     *
     * @return void
     */
    public function ajax_get_log() {
        check_ajax_referer( $this->nonce, 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        $stored_customizations = get_option( 'ddtt_log_viewer_customizations', [] );

        $customizations = [
            'type'     => isset( $_POST[ 'type' ] )
                ? sanitize_key( wp_unslash( $_POST[ 'type' ] ) )
                : ( isset( $stored_customizations[ 'type' ] ) ? $stored_customizations[ 'type' ] : 'easy' ),
            'sort'    => isset( $_POST[ 'sort' ] )
                ? sanitize_key( wp_unslash( $_POST[ 'sort' ] ) )
                : ( isset( $stored_customizations[ 'sort' ] ) ? $stored_customizations[ 'sort' ] : 'asc' ),
            'combine' => isset( $_POST[ 'combine' ] )
                ? absint( wp_unslash( $_POST[ 'combine' ] ) )
                : ( isset( $stored_customizations[ 'combine' ] ) ? $stored_customizations[ 'combine' ] : 1 ),
            'per_page' => isset( $_POST[ 'per_page' ] )
                ? absint( wp_unslash( $_POST[ 'per_page' ] ) )
                : ( isset( $stored_customizations[ 'per_page' ] ) ? $stored_customizations[ 'per_page' ] : 100 ),
            'search'   => isset( $_POST[ 'search' ] )
                ? sanitize_text_field( wp_unslash( $_POST[ 'search' ] ) )
                : ( isset( $stored_customizations[ 'search' ] ) ? $stored_customizations[ 'search' ] : '' ),
            'filter'   => isset( $_POST[ 'filter' ] )
                ? sanitize_text_field( wp_unslash( $_POST[ 'filter' ] ) )
                : ( isset( $stored_customizations[ 'filter' ] ) ? $stored_customizations[ 'filter' ] : '' ),
            'wrap_text' => isset( $_POST[ 'wrap_text' ] )
                ? absint( wp_unslash( $_POST[ 'wrap_text' ] ) )
                : ( isset( $stored_customizations[ 'wrap_text' ] ) ? $stored_customizations[ 'wrap_text' ] : 0 ),
        ];

        update_option( 'ddtt_log_viewer_customizations', $customizations );

        $subsection = isset( $_POST[ 'subsection' ] )
            ? sanitize_key( wp_unslash( $_POST[ 'subsection' ] ) )
            : 'debug';

        ob_start();
        self::render_log( $subsection, $customizations );
        echo ob_get_clean(); // phpcs:ignore 

        wp_die();
    } // End ajax_get_log()


    /**
     * Handle AJAX request to toggle text wrap
     *
     * @return void
     */
    public function ajax_log_text_wrap() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        $value = isset( $_POST[ 'value' ] ) ? absint( wp_unslash( $_POST[ 'value' ] ) ) : 0;

        $customizations = get_option( 'ddtt_log_viewer_customizations', [] );
        $customizations[ 'wrap_text' ] = $value;
        update_option( 'ddtt_log_viewer_customizations', $customizations );

        wp_send_json_success();
    } // End ajax_log_text_wrap()


    /**
     * Prevent cloning and unserializing
     */
    public function __clone() {}
    public function __wakeup() {}

}


Logs::instance();