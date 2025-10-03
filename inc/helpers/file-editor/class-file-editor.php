<?php
/**
 * File Editor
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class FileEditor {

    /**
     * The file path
     *
     * @var string
     */
    private string $abspath;


    /**
     * The file directory
     *
     * @var string
     */
    private string $absdir;


    /**
     * The file name
     *
     * @var string
     */
    private string $filename;


    /**
     * The class name for the file editor
     *
     * @var string
     */
    private string $class_name;


    /**
     * The short name
     *
     * @var string
     */
    private string $shortname;


    /**
     * The tool slug
     *
     * @var string
     */
    private string $tool_slug;


    /**
     * Meta key for storing resources
     *
     * @var string
     */
    public string $option_key;


    /**
     * Get the magic cleaner rules.
     *
     * @return array
     */
    private function magic_cleaner_rules() : array {
        $settings = Settings::config_files_options();

        $option_key = $this->shortname . '_cleaner';

        $rules = [];
        if ( isset( $settings[ $option_key ][ 'fields' ] ) ) {
            foreach ( $settings[ $option_key ][ 'fields' ] as $key => $field ) {
                $rules[ $key ] = get_option( 'ddtt_' . $key, $field[ 'default' ] );
            }
        }

        return $rules;
    } // End magic_cleaner_rules()


    /**
     * Default colors for syntax highlighting.
     *
     * @var array
     */
    public $default_colors = [
        'dark' => [
            'comments'   => '#5E9955',
            'fx_vars'    => '#DCDCAA',
            'text_quotes'=> '#ACCCCC',
            'syntax'     => '#569CD6',
            'background' => '#181818',
        ],
        'light' => [
            'comments'   => '#008000',
            'fx_vars'    => '#0000FF',
            'text_quotes'=> '#A31515',
            'syntax'     => '#0000FF',
            'background' => '#f5f5f5',
        ],
    ];


    /**
     * Get the colors for syntax highlighting.
     *
     * @param array $viewer_customizations
     * @param bool  $return_both_modes
     * @return array
     */
    public function colors( $viewer_customizations = [], $return_both_modes = false ) : array {
        if ( empty( $viewer_customizations ) ) {
            $viewer_customizations = filter_var_array( get_option( 'ddtt_' . $this->shortname . '_viewer_customizations', [] ), FILTER_SANITIZE_SPECIAL_CHARS );
        }

        $custom_colors = [];
        if ( ! empty( $viewer_customizations ) && isset( $viewer_customizations[ 'colors' ] ) && is_array( $viewer_customizations[ 'colors' ] ) ) {
            $custom_colors = $viewer_customizations[ 'colors' ];
        }

        // Merge with defaults to fill any missing mode or color
        $colors = [
            'dark'  => array_merge( $this->default_colors[ 'dark' ], $custom_colors[ 'dark' ] ?? [] ),
            'light' => array_merge( $this->default_colors[ 'light' ], $custom_colors[ 'light' ] ?? [] ),
        ];

        if ( $return_both_modes ) {
            return $colors;
        }

        $mode = Helpers::is_dark_mode() ? 'dark' : 'light';
        return $colors[ $mode ];
    } // End colors()


    /**
     * Nonce for updating the file editor
     *
     * @var string
     */
    private $nonce = 'ddtt_file_editor_nonce';


    /**
     * Instances
     *
     * @var array
     */
    private static array $instances = [];


    /**
     * Constructor
     */
    private function __construct( $abspath = '' ) {

        // Validate the file path
        if ( ! file_exists( $abspath ) ) {
            throw new \InvalidArgumentException( "File not found: " . esc_html( $abspath ) );
        }

        // Set class properties
        $this->abspath = $abspath;
        $this->absdir  = rtrim( dirname( $abspath ), '/' ) . '/';
        $this->filename = basename( $abspath );
        $this->class_name = '\\Apos37\\DevDebugTools\\' . $this->get_class_name( $this->filename );
        if ( ! class_exists( $this->class_name ) ) {
            throw new \RuntimeException( "Class not found for file editor: " . esc_html( $this->class_name ) );
        }

        $this->tool_slug = str_replace( [ '.php', '.' ], '', $this->filename );
        $this->shortname = str_replace( '-', '', $this->tool_slug );
        $this->option_key = 'ddtt_' . $this->shortname . '_snippets';

        // Header notices
        add_action( 'ddtt_header_notices', [ $this, 'render_header_notices' ] );

        // Action handler
        $this->handle_button_actions();

        // Enqueue scripts
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

        // Ajax handlers
        $ajax_handlers = [
            'update_colors',
            'save_edits',
            'preview_snippets',
            'delete_preview_file',
            'load_previewer',
            'add_snippet',
            'delete_snippet',
            'delete_backup_file',
            'clear_all_backups'
        ];
        foreach ( $ajax_handlers as $handler ) {
            add_action( 'wp_ajax_ddtt_' . $this->shortname . '_' . $handler, [ $this, 'ajax_' . $handler ] );
            add_action( 'wp_ajax_nopriv_ddtt_' . $this->shortname . '_' . $handler, '__return_false' );
        }

    } // End __construct()


    /**
     * Singleton getter
     *
     * @param string $abspath
     * @return self
     */
    public static function instance( string $abspath ) : self {
        if ( ! isset( self::$instances[ $abspath ] ) ) {
            self::$instances[ $abspath ] = new self( $abspath );
        }
        return self::$instances[ $abspath ];
    } // End instance()


    // Getter for option key
    public function get_option_key(): string {
        return $this->option_key;
    } // End get_option_key()


    /**
     * Get the class name for the file
     *
     * @param string $filename
     * @return string
     */
    private function get_class_name( $filename ) : string {
        $filename = preg_replace( '/^\.+/', '', $filename );
        $filename = preg_replace( '/\.[^.]+$/', '', $filename );
        $parts = preg_split( '/[-_]/', $filename );
        $parts = array_map( 'ucfirst', $parts );
        return implode( '', $parts );
    } // End get_class_name()


    /**
     * Render header notices
     *
     * This method is called to render notices in the header.
     * It checks for deleted options and displays a notice if any were deleted.
     */
    public function render_header_notices() {
        if ( AdminMenu::get_current_page_slug() !== 'dev-debug-tools' || AdminMenu::current_tool_slug() !== $this->tool_slug ) {
            return;
        }

        // File update notice
        if ( get_transient( 'ddtt_' . $this->shortname . '_file_updated' ) ) {
            delete_transient( 'ddtt_' . $this->shortname . '_file_updated' );
            /* translators: %s: filename */
            Helpers::render_notice( sprintf( __( 'Your %s file has been updated successfully.', 'dev-debug-tools' ), esc_html( $this->filename ) ), 'success' );
        }

        // Backup deleted notice
        if ( $deleted_backup = get_transient( 'ddtt_' . $this->shortname . '_backup_deleted' ) ) {
            delete_transient( 'ddtt_' . $this->shortname . '_backup_deleted' );
            /* translators: %s: filename */
            Helpers::render_notice( sprintf( __( 'Backup file %s has been deleted.', 'dev-debug-tools' ), esc_html( $deleted_backup ) ), 'success' );
        }

        // Backups deleted notice
        if ( $deleted_backups = get_transient( 'ddtt_' . $this->shortname . '_backups_deleted' ) ) {
            delete_transient( 'ddtt_' . $this->shortname . '_backups_deleted' );
            /* translators: 1: number of deleted backups, 2: total backups */
            Helpers::render_notice( sprintf( __( '%1$d out of %2$d backup files have been deleted.', 'dev-debug-tools' ), intval( $deleted_backups[ 'deleted' ] ), intval( $deleted_backups[ 'total' ] ) ), 'success' );
        }

        // Reset resources to defaults
        if ( ! empty( $_GET[ 'reset' ] ) && sanitize_key( wp_unslash( $_GET[ 'reset' ] ) ) === 'true' && 
            isset( $_GET[ '_wpnonce' ] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET[ '_wpnonce' ] ) ), 'ddtt_snippets_reset' ) ) {
            delete_option( $this->option_key );
            Helpers::remove_qs_without_refresh( [ 'reset', '_wpnonce' ] );
            Helpers::render_notice( __( 'Snippets list has been reset to the defaults.', 'dev-debug-tools' ) );
        }
    } // End render_header_notices()


    /**
     * Handle metadata actions.
     */
    public function handle_button_actions() {
        if ( ! is_admin() || ! current_user_can( 'manage_options' ) || ! Helpers::is_dev() ) {
            return;
        }

        if ( empty( $_POST[ 'ddtt_' . $this->shortname . '_action' ] ) ) {
            return;
        }

        check_admin_referer( 'ddtt_' . $this->shortname . '_action', $this->nonce );

        $action = sanitize_text_field( wp_unslash( $_POST[ 'ddtt_' . $this->shortname . '_action' ] ) );

        if ( $action !== 'download_file' ) {
            return;
        }

        $contents = Helpers::get_file_contents( $this->filename );

        if ( $contents === false || is_wp_error( $contents ) ) {
            $error_message = is_wp_error( $contents ) ? $contents->get_error_message() : __( 'Unknown error while reading file.', 'dev-debug-tools' );
            wp_die( esc_html( $error_message ) );
        }

        $mime_type = Helpers::get_mime_type( $this->filename );

        // Clean any previous buffer
        if ( ob_get_length() ) {
            ob_end_clean();
        }

        header( 'Content-Description: File Transfer' );
        header( 'Content-Type: ' . $mime_type );
        header( 'Content-Disposition: attachment; filename="' . basename( $this->filename ) . '"' );
        header( 'Content-Length: ' . strlen( $contents ) );
        header( 'Cache-Control: must-revalidate' );
        header( 'Pragma: public' );
        header( 'Expires: 0' );

        echo $contents; // phpcs:ignore
        exit;
    } // End handle_button_actions()


    /**
     * Get a list of backups from the WordPress root directory.
     *
     * @return array Array of backups in the format [ 'filename_without_php' => 'Formatted Date' ].
     */
    public function get_backups( $skip_most_recent = false ) : array {
        $backups = [];
        $root_dir = $this->absdir;

        if ( ! is_dir( $root_dir ) ) {
            return $backups;
        }

        $files = scandir( $root_dir );
        if ( ! $files ) {
            return $backups;
        }

        if ( $this->filename === '.htaccess' ) {
            $pattern = '/^\.htaccess-(\d{4})-(\d{2})-(\d{2})-(\d{2})-(\d{2})-(\d{2})$/';
        } else {
            $pattern = '/^' . preg_quote( $this->tool_slug, '/' ) . '-(\d{4})-(\d{2})-(\d{2})-(\d{2})-(\d{2})-(\d{2})\.php$/';
        }
        $filename_key_func = function( $file ) { return $file; };

        foreach ( $files as $file ) {
            if ( preg_match( $pattern, $file, $matches ) ) {
                $filename_key = $filename_key_func( $file );
                $date = sprintf(
                    '%s-%s-%s %s:%s:%s',
                    $matches[1], // year
                    $matches[2], // month
                    $matches[3], // day
                    $matches[4], // hour
                    $matches[5], // minute
                    $matches[6]  // seconds
                );
                $backups[ $filename_key ] = Helpers::convert_timezone( $date );
            }
        }

        // Sort descending by date
        arsort( $backups );

        // Remove the most recent backup if requested
        if ( $skip_most_recent && ! empty( $backups ) ) {
            array_shift( $backups );
        }

        return $backups;
    } // End get_backups()


    /**
     * Get all snippets.
     *
     * @return array
     */
    public function get_all_snippets() : array {
        $custom_snippets = get_option( $this->option_key, [] );
        $custom_snippets[ 'order' ]  = isset( $custom_snippets[ 'order' ] ) ? $custom_snippets[ 'order' ] : [];
        $custom_snippets[ 'custom' ] = isset( $custom_snippets[ 'custom' ] ) ? $custom_snippets[ 'custom' ] : [];
        $sanitized_snippets  = array_map( [ $this->class_name, 'sanitize_existing_snippet' ], $custom_snippets[ 'custom' ] );

        $default_snippets = $this->class_name::snippets();

        // Only filter defaults if order array is not empty
        if ( ! empty( $custom_snippets[ 'order' ] ) ) {
            $default_snippets = array_filter( $default_snippets, function( $key ) use ( $custom_snippets ) {
                return in_array( $key, $custom_snippets[ 'order' ], true );
            }, ARRAY_FILTER_USE_KEY );
        }

        // Merge filtered defaults with sanitized custom snippets
        $snippets = array_merge( $default_snippets, $sanitized_snippets );

        return $snippets;
    } // End get_all_snippets()


    /**
     * Render the snippets manager
     */
    private function render_snippets_manager( $raw_contents ) {
        // Get all the snippets
        $snippets = $this->get_all_snippets();

        // Extract current defines and ini_set calls
        $code_blocks = $this->class_name::extract_snippets_from_content( $raw_contents );
        ?>
        <h3><?php esc_html_e( 'Manage Snippets', 'dev-debug-tools' ); ?></h3>
        <table class="ddtt-table">
            <thead>
                <tr>
                    <th></th>
                    <th><?php esc_html_e( 'Add', 'dev-debug-tools' ); ?></th>
                    <th><?php esc_html_e( 'Remove', 'dev-debug-tools' ); ?></th>
                    <th><?php esc_html_e( 'Update', 'dev-debug-tools' ); ?></th>
                    <th><?php esc_html_e( 'Label', 'dev-debug-tools' ); ?></th>
                    <th><?php esc_html_e( 'Code', 'dev-debug-tools' ); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $snippets as $key => $snippet ) {
                
                $line_data = $this->class_name::get_current_code_lines( $snippet[ 'lines' ], $code_blocks );
                $detected = $line_data[ 'detected' ];
                $current_code = $line_data[ 'code' ];

                $desc       = isset( $snippet[ 'desc' ] ) ? $snippet[ 'desc' ] : '';
                $added_by   = isset( $snippet[ 'added' ][ 'author' ] ) ? intval( $snippet[ 'added' ][ 'author' ] ) : 0;
                $added_when = isset( $snippet[ 'added' ][ 'date' ] ) ? sanitize_text_field( $snippet[ 'added' ][ 'date' ] ) : '';

                if ( $added_by && $user = get_userdata( $added_by ) ) {
                    $added_by_name = $user->display_name;
                    $added_when    = Helpers::convert_date_format( $added_when );

                    $desc .= sprintf(
                        /* translators: 1: Author display name, 2: Date snippet was added */
                        __( 'Added by: %1$s on %2$s', 'dev-debug-tools' ),
                        esc_html( $added_by_name ),
                        esc_html( $added_when )
                    );
                }
                ?>
                <tr class="ddtt-snippet-item" data-index="<?php echo esc_attr( $key ); ?>" data-detected="<?php echo $detected ? esc_html( 'true' ) : esc_html( 'false' ); ?>">
                    <td>
                        <?php if ( $detected ) { ?>
                            <span class="ddtt-snippet-detected"><?php esc_html_e( 'Detected', 'dev-debug-tools' ); ?></span>
                        <?php } ?>
                    </td>
                    <td>
                        <input type="checkbox" name="a[]" value="<?php echo esc_attr( $key ); ?>" <?php disabled( $detected ); ?>>
                    </td>
                    <td>
                        <input type="checkbox" name="r[]" value="<?php echo esc_attr( $key ); ?>" <?php disabled( ! $detected ); ?>>
                    </td>
                    <td>
                        <input type="checkbox" class="ddtt-update-checkbox" name="u[]" value="<?php echo esc_attr( $key ); ?>" <?php disabled( ! $detected ); ?>>
                    </td>
                    <td class="ddtt-has-help-dialog">
                        <div class="ddtt-help-description">
                            <?php echo esc_html( $snippet[ 'label' ] ); ?>
                        </div>
                        <a href="#" class="ddtt-help-toggle" aria-controls="ddtt-help-<?php echo esc_attr( $key ); ?>" aria-expanded="false">[Learn More]</a>
                        <div id="ddtt-help-<?php echo esc_attr( $key ); ?>" class="ddtt-help-content" hidden>
                            <button type="button" class="ddtt-help-close">×</button>
                            <div class="ddtt-help-body">
                                <?php echo wp_kses_post( $desc ); ?>
                            </div>
                        </div>
                    </td>
                    <td class="snippet-code" data-key="<?php echo esc_attr( $key ); ?>">
                        <div class="ddtt-snippet-code">
                            <code><?php
                                // Convert lines to HTML with <br>
                                $code_html = implode( '<br>', $current_code );
                                $code_html = esc_html( $code_html );
                                $code_html = str_replace( '&lt;br&gt;', '<br>', $code_html );
                                echo wp_kses( $code_html, [ 'br' => [] ] );
                            ?></code>
                        </div>

                        <button type="button" class="ddtt-delete-snippet" data-key="<?php echo esc_attr( $key ); ?>" aria-label="<?php esc_attr_e( 'Remove snippet', 'dev-debug-tools' ); ?>" title="<?php esc_attr_e( 'Remove snippet', 'dev-debug-tools' ); ?>">−</button>
                    </td>
                </tr>
            <?php } ?>
            <tr class="ddtt-snippet-item ddtt-new-snippet" colspan="6">
                <td colspan="6">
                    <button type="button" class="button" id="ddtt-add-snippet" title="<?php esc_attr_e( 'Add a new snippet', 'dev-debug-tools' ); ?>" aria-label="<?php esc_attr_e( 'Add Snippet', 'dev-debug-tools' ); ?>"></button>
                </td>
            </tr>
            </tbody>
        </table>

        <?php
        $reset_link = add_query_arg(
            [
                'page'     => 'dev-debug-tools',
                'tool'     => $this->tool_slug,
                'reset'    => 'true',
                '_wpnonce' => wp_create_nonce( 'ddtt_snippets_reset' ),
            ],
            admin_url( 'admin.php' )
        );
        ?>
        <a class="ddtt-reset-snippets-link ddtt-reset-link" href="<?php echo esc_url( $reset_link ); ?>"><?php echo esc_html__( '[Reset Snippet List to Defaults]', 'dev-debug-tools' ); ?></a>
        <?php
    } // End render_snippets_manager()


    /**
     * Get file data
     *
     * @param string $file The file path.
     * @return array
     */
    public function get_file_info( $raw_contents ) {
        $file_ts = filemtime( ABSPATH . $this->filename );

        // Sanitize saved option
        $saved_info = get_option( 'ddtt_' . $this->shortname . '_last_modified', [] );
        $saved_info = is_array( $saved_info ) ? $saved_info : [];
        $saved_ts = isset( $saved_info[ 'timestamp' ] ) ? intval( $saved_info[ 'timestamp' ] ) : 0;
        $saved_uid = isset( $saved_info[ 'user_id' ] ) ? intval( $saved_info[ 'user_id' ] ) : 0;

        // Compare timestamps
        $diff = $file_ts - $saved_ts;
        if ( $saved_ts > 0 && $diff <= 60 ) {
            $user = get_userdata( $saved_uid );
            if ( $user ) {
                /* translators: %s: user display name */
                $editor = sprintf( __( 'Edited by %s', 'dev-debug-tools' ), $user->display_name );
            } else {
                $editor = __( 'Edited by Unknown', 'dev-debug-tools' );
            }
        } elseif ( $saved_ts > 0 ) {
            $editor = __( 'Edited outside plugin', 'dev-debug-tools' );
        } else {
            $editor = __( 'Unknown', 'dev-debug-tools' );
        }

        return [
            'eol' => [
                'label' => __( 'EOL Delimiter', 'dev-debug-tools' ),
                'value' => Helpers::get_file_eol( $raw_contents ),
            ],
            'last_modified' => [
                'label' => __( 'Last Modified', 'dev-debug-tools' ),
                'value' => Helpers::convert_timezone( $file_ts, 'F j, Y g:i A' ) . ' (' . $editor . ')',
            ],
        ];
    } // End get_file_info()


    /**
     * Render file information
     *
     * @param array $path The file path information.
     */
    public function render_file_info( $raw_contents ) {
        $file_data = $this->get_file_info( $raw_contents );
        if ( $file_data ) :
            $last_key = array_key_last( $file_data );
            ?>
            <div class="ddtt-file-info">
                <div class="ddtt-file-data">
                    <?php foreach ( $file_data as $index => $data ) : ?>
                        <div class="ddtt-file-data-item <?php echo esc_attr( 'ddtt-file-data-' . $index ); ?>">
                            <span class="ddtt-file-data-label"><?php echo esc_html( $data[ 'label' ] ); ?>:</span>
                            <strong><?php echo esc_html( is_array( $data[ 'value' ] ) ? implode( ', ', $data[ 'value' ] ) : $data[ 'value' ] ); ?></strong>
                        </div>
                        <?php if ( $index !== $last_key ) : ?>
                            <span class="ddtt-separator">|</span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <div id="ddtt-file-notice"><?php esc_html_e( 'VIEWING CURRENT FILE', 'dev-debug-tools' ); ?></div>
            </div>
        <?php
        endif;
    } // End render_file_info()


    /**
     * Highlight PHP code
     *
     * @param string $raw_contents The raw file contents.
     * @param string $eol The end-of-line character(s).
     * @param array $colors The color settings.
     * @param bool $redacting Whether redacting is enabled.
     * @return string
     */
    public function highlight_php( $raw_contents, $eol, $colors, $redacting ) {
        $sensitive_values = [];
        if ( ! $redacting ) {
            $redacted_content = $this->class_name::redact_content( $raw_contents );
            $sensitive_values = $redacted_content[ 'sensitive_values' ];
            $raw_contents = $redacted_content[ 'raw_contents' ];
        }

        $comments = $colors[ 'comments' ];
        $fx_vars = $colors[ 'fx_vars' ];
        $text_quotes = $colors[ 'text_quotes' ];
        $syntax = $colors[ 'syntax' ];

        ini_set( 'highlight.comment', $comments ); // phpcs:ignore 
        ini_set( 'highlight.default', $fx_vars ); // phpcs:ignore
        ini_set( 'highlight.html', $text_quotes ); // phpcs:ignore
        ini_set( 'highlight.keyword', $syntax ); // phpcs:ignore
        ini_set( 'highlight.string', $text_quotes ); // phpcs:ignore

        $for_highlight = 'REMOVE_FIRST_SPACE' . $raw_contents;
        $highlighted = highlight_string( $for_highlight, true );
        $highlighted = preg_replace( '~^<code.*?>|</code>$~i', '', $highlighted );

        $lines = explode( $eol, $highlighted );
        if ( isset( $lines[0] ) && strpos( wp_strip_all_tags( $lines[0] ), 'REMOVE_FIRST_SPACE' ) !== false ) {
            array_shift( $lines );
        }

        array_unshift( $lines, '<span class="ddtt-fx-vars" style="color: ' . $fx_vars . '">&lt;?php</span>' );
        $highlighted = implode( $eol, $lines );

        if ( ! $redacting && ! empty( $sensitive_values ) ) {
            $highlighted = $this->class_name::unredact_content( $highlighted, $sensitive_values );
        }

        // Add classes
        $highlighted = preg_replace_callback(
            '/<span style="color: ([^"]+)">([^<]+)<\/span>/i',
            function( $matches ) use ( $comments, $fx_vars, $text_quotes, $syntax ) {
                $color = strtolower( $matches[ 1 ] );
                $content = $matches[ 2 ];
                $class = '';

                if ( $color === strtolower( $comments ) ) {
                    $class = 'ddtt-comment';
                } elseif ( $color === strtolower( $fx_vars ) ) {
                    $class = 'ddtt-fx-vars';
                } elseif ( $color === strtolower( $text_quotes ) ) {
                    $class = 'ddtt-text-quotes';
                } elseif ( $color === strtolower( $syntax ) ) {
                    $class = 'ddtt-syntax';
                }

                return '<span class="' . $class . '" style="color: ' . $matches[ 1 ] . '">' . $content . '</span>';
            },
            $highlighted
        );

        return $highlighted;
    } // End highlight_php()


    /**
     * Highlight .htaccess files
     *
     * @param string $raw_contents The raw file contents.
     * @param string $eol The end-of-line character(s).
     * @param array $colors The color settings.
     * @param bool $redacting Whether redacting is enabled.
     * @return string
     */
    public function highlight_htaccess( $raw_contents, $eol, $colors, $redacting ) {
        $sensitive_values = [];
        if ( ! $redacting ) {
            $redacted_content = $this->class_name::redact_content( $raw_contents );
            $sensitive_values = $redacted_content[ 'sensitive_values' ];
            $raw_contents = $redacted_content[ 'raw_contents' ];
        }

        $comments = $colors[ 'comments' ];
        $fx_vars = $colors[ 'fx_vars' ];
        $text_quotes = $colors[ 'text_quotes' ];
        $syntax = $colors[ 'syntax' ];

        // Escape HTML entities
        $highlighted = htmlspecialchars( $raw_contents );

        // Comments
        $highlighted = preg_replace( '/^(#.*)$/m', '<span style="color:' . $comments . ';">$1</span>', $highlighted );

        // Highlight anything in <>
        $highlighted = preg_replace(
            '/(&lt;\/?.*?&gt;)/i',
            '<span style="color:' . $syntax . '; font-weight: bold;">$1</span>',
            $highlighted
        );

        // Rewrite rules
        $highlighted = preg_replace(
            '/\b(RewriteEngine|RewriteRule|RewriteCond|RewriteBase)\b/i',
            '<span style="color:' . $fx_vars . '; font-weight: bold;">$1</span>',
            $highlighted
        );

        // Split lines and re-join with EOL
        $lines = explode( $eol, $highlighted );
        $highlighted = implode( $eol, $lines );

        if ( ! $redacting && ! empty( $sensitive_values ) ) {
            $highlighted = $this->class_name::unredact_content( $highlighted, $sensitive_values );
        }

        // Add classes matching PHP highlighter convention
        $highlighted = preg_replace_callback(
            '/<span\s+[^>]*style="[^"]*color:\s*([^;"\s]+)[^"]*"[^>]*>(.*?)<\/span>/i',
            function( $matches ) use ( $comments, $fx_vars, $text_quotes, $syntax ) {
                $color = strtolower( $matches[ 1 ] );
                $content = $matches[ 2 ];
                $class = '';

                if ( $color === strtolower( $comments ) ) {
                    $class = 'ddtt-comment';
                } elseif ( $color === strtolower( $fx_vars ) ) {
                    $class = 'ddtt-fx-vars';
                } elseif ( $color === strtolower( $text_quotes ) ) {
                    $class = 'ddtt-text-quotes';
                } elseif ( $color === strtolower( $syntax ) ) {
                    $class = 'ddtt-syntax';
                }

                return '<span class="' . $class . '" style="color: ' . $matches[ 1 ] . '">' . $content . '</span>';
            },
            $highlighted
        );

        return $highlighted;
    } // End highlight_htaccess()


    /**
     * Render the file viewer
     *
     * @param array $meta_viewer_customizations Customizations for the metadata viewer.
     */
    public function render_file_viewer( $raw_contents, $viewer_customizations = [], $rerender_viewer_only = false ) {
        $raw_editor_contents = $raw_contents;
        $eol = Helpers::get_eol_char( Helpers::get_file_eol( $raw_contents ) );
        $redacting = filter_var( get_option( 'ddtt_view_sensitive_info' ), FILTER_VALIDATE_BOOLEAN );
        $colors = $this->colors( $viewer_customizations );
        $text_quotes = $colors[ 'text_quotes' ];
        $background = $colors[ 'background' ];

        $ext = pathinfo( $this->filename, PATHINFO_EXTENSION );

        if ( strtolower( $ext ) === 'php' ) {
            $formatted_content = $this->highlight_php( $raw_contents, $eol, $colors, $redacting );
        } elseif ( strtolower( $ext ) === 'htaccess' || strtolower( $ext ) === 'conf' ) {
            $formatted_content = $this->highlight_htaccess( $raw_contents, $eol, $colors, $redacting );
        } else {
            $formatted_content = '<pre>' . esc_html( $raw_contents ) . '</pre>';
        }

        if ( $rerender_viewer_only ) {
            echo wp_kses_post( $formatted_content );
            return;
        }
        ?>
        <section id="ddtt-file-editor-section">
            <?php $this->render_file_info( $raw_contents ); ?>

            <pre id="ddtt-current-view" class="ddtt-code-block" style="background: <?php echo esc_attr( $background ); ?>; color: <?php echo esc_attr( $text_quotes ); ?>;"><?php echo wp_kses_post( $formatted_content ); ?></pre>

            <div id="ddtt-editor-errors"></div>
            <div id="ddtt-raw-editor-cont" style="display: none;">
                <div id="ddtt-magic-cleaner" title="<?php echo esc_html__( 'Clean this file by removing excessive comments, whitespace, and other non-essential elements.', 'dev-debug-tools' ); ?>">✨</div>
                <div id="ddtt-raw-editor" contenteditable="true"><?php echo esc_textarea( $raw_editor_contents ); ?></div>
            </div>

            <div id="ddtt-snippet-manager" style="display: none;">
                <?php 
                if ( class_exists( $this->class_name ) || method_exists( $this->class_name, 'snippets' ) && ! empty( $this->class_name::snippets() ) ) {
                    $this->render_snippets_manager( $raw_editor_contents ); 
                }
                ?>
            </div>

            <pre id="ddtt-file-previewer" class="ddtt-code-block" style="display: none; background: <?php echo esc_attr( $background ); ?>; color: <?php echo esc_attr( $text_quotes ); ?>;"></pre>
        </section>
        <?php
    } // End render_file_viewer()


    /**
     * Enqueue assets
     *
     * @param string $hook The current admin page hook.
     * @return void
     */
    public function enqueue_assets( $hook ) : void {
        if ( ! AdminMenu::is_current_screen( $hook, 'tools', $this->tool_slug ) ) {
            return;
        }

        wp_localize_script( 'ddtt-file-editor', 'ddtt_file_editor', [
            'properties'          => [
                'filename'   => $this->filename,
                'shortname'  => $this->shortname,
                'tool_slug'  => $this->tool_slug,
            ],
            'nonce'               => wp_create_nonce( $this->nonce ),      
            'snippets'            => $this->class_name::snippets(),
            'default_colors'      => $this->default_colors,
            'colors'              => $this->colors( [], true ),
            'magic_cleaner_rules' => $this->magic_cleaner_rules(),
            'i18n'                => [
                'loading'                => __( 'Please wait. Loading metadata', 'dev-debug-tools' ),
                'confirm_reset'          => __( 'Are you sure you want to reset the colors to default?', 'dev-debug-tools' ),
                'confirm_save'           => __( 'Are you sure you want to save changes?', 'dev-debug-tools' ),
                'confirm_regen'          => __( 'Are you sure you want to regenerate the file with new auth keys and salts?', 'dev-debug-tools' ),
                'save_success'           => __( 'Temp file saved and validated successfully.', 'dev-debug-tools' ),
                'save_error'             => __( 'Errors found: ', 'dev-debug-tools' ),
                'ajax_error'             => __( 'AJAX error. Please try again.', 'dev-debug-tools' ),
                'regen_error'            => __( 'Regeneration error: ', 'dev-debug-tools' ),
                'db_connection_fail'     => __( 'Database connection failed: ', 'dev-debug-tools' ),
                'db_credentials_missing' => __( 'Cannot test database connection; credentials missing.', 'dev-debug-tools' ),
                'missing_constants'      => __( 'Missing required items: ', 'dev-debug-tools' ),
                'check_line'             => __( 'Check line: ', 'dev-debug-tools' ),
                'label_placeholder'      => __( 'Label', 'dev-debug-tools' ),
                'desc_placeholder'       => __( 'Description', 'dev-debug-tools' ),
                'prefix_define'          => __( 'define', 'dev-debug-tools' ),
                'prefix_ini_set'         => __( '@ini_set', 'dev-debug-tools' ),
                'variable_placeholder'   => __( 'Variable', 'dev-debug-tools' ),
                'value_placeholder'      => __( 'Value', 'dev-debug-tools' ),
                'btn_add_snippet'        => __( 'Add New Snippet', 'dev-debug-tools' ),
                'btn_cancel'             => __( 'Cancel', 'dev-debug-tools' ),
                'error_required'         => __( 'Label, variable, and value are required.', 'dev-debug-tools' ),
                'learn_more'             => __( '[Learn More]', 'dev-debug-tools' ),
                'error_save'             => __( 'Error saving snippet.', 'dev-debug-tools' ),
                'detected'               => __( 'Detected', 'dev-debug-tools' ),
                'remove_snippet'         => __( 'Remove Snippet', 'dev-debug-tools' ),
                'delete_confirm'         => __( 'Are you sure you want to delete this snippet from your list? This does not remove it from your file, only from the list of snippets you can add and remove from here.', 'dev-debug-tools' ),
                'reset_confirm'          => __( 'Are you sure you want to reset the snippets list? Any default snippets you have removed will be restored, and any custom snippets that you have added will be lost.', 'dev-debug-tools' ),
                'delete_backup_confirm'  => __( 'Are you sure you want to delete this backup file? This action cannot be undone.', 'dev-debug-tools' ),
                'delete_backups_confirm' => __( 'Are you sure you want to delete all backup files except the most recent one? This action cannot be undone.', 'dev-debug-tools' ),
                'added_by'               => __( 'Added by: ', 'dev-debug-tools' ),
                'viewing_current'        => __( 'VIEWING CURRENT FILE', 'dev-debug-tools' ),
                'editing_raw'            => __( 'EDITING RAW FILE', 'dev-debug-tools' ),
                'previewing_backup'      => __( 'PREVIEWING BACKUP FILE', 'dev-debug-tools' ),
                'previewing_snippets'    => __( 'PREVIEWING FILE WITH SNIPPETS', 'dev-debug-tools' ),
                'loading_preview'        => __( 'Loading preview...', 'dev-debug-tools' ),
                'saving'                 => __( 'Saving...', 'dev-debug-tools' ),
                'reloading_page'         => __( 'Reloading...', 'dev-debug-tools' ),
                'deleting'               => __( 'Deleting...', 'dev-debug-tools' ),
                'clearing'               => __( 'Clearing', 'dev-debug-tools' ),
                'htaccess_warning'       => __( '⚠️ Note: Browsers remove the leading dot from files like ".htaccess". If you plan to use it, please rename the downloaded file to include the dot before using it.', 'dev-debug-tools' ),
            ],
        ] );
    } // End enqueue_assets()


    /**
     * Handle AJAX request to update the colors
     *
     * @return void
     */
    public function ajax_update_colors() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        $colors_input = isset( $_POST[ 'colors' ] ) ? array_map( function( $mode_colors ) {
            return array_map( 'sanitize_hex_color', $mode_colors );
        }, wp_unslash( $_POST[ 'colors' ] ) ) : []; // phpcs:ignore

        if ( empty( $colors_input ) || ! is_array( $colors_input ) ) {
            wp_send_json_error( 'invalid_colors' );
        }

        $customizations = filter_var_array( get_option( 'ddtt_' . $this->shortname . '_viewer_customizations', [] ), FILTER_SANITIZE_SPECIAL_CHARS );

        // Ensure proper structure
        if ( ! isset( $customizations[ 'colors' ] ) || ! is_array( $customizations[ 'colors' ] ) ) {
            $customizations[ 'colors' ] = [
                'dark'  => [],
                'light' => []
            ];
        } else {
            if ( ! isset( $customizations[ 'colors' ][ 'dark' ] ) || ! is_array( $customizations[ 'colors' ][ 'dark' ] ) ) {
                $customizations[ 'colors' ][ 'dark' ] = [];
            }
            if ( ! isset( $customizations[ 'colors' ][ 'light' ] ) || ! is_array( $customizations[ 'colors' ][ 'light' ] ) ) {
                $customizations[ 'colors' ][ 'light' ] = [];
            }
        }

        // Merge updated colors for each mode
        foreach ( $colors_input as $mode => $mode_colors ) {
            if ( ! isset( $customizations[ 'colors' ][ $mode ] ) ) {
                $customizations[ 'colors' ][ $mode ] = [];
            }
            $customizations[ 'colors' ][ $mode ] = array_merge( $customizations[ 'colors' ][ $mode ], $mode_colors );
        }

        update_option( 'ddtt_' . $this->shortname . '_viewer_customizations', $customizations );

        wp_send_json_success( $customizations );
    } // End ajax_update_colors()


    /**
     * Get the temporary file path
     *
     * @return string
     */
    private function get_temp_file_path() : string {
        $basename = basename( $this->abspath );
        $dir      = dirname( $this->abspath );

        if ( strtolower( $basename ) === '.htaccess' ) {
            // Special case for .htaccess
            return $dir . '/' . $basename . '-ddtt-temp';
        }

        $parts     = pathinfo( $basename );
        $temp_file = $dir . '/' . $parts[ 'filename' ] . '-ddtt-temp';
        if ( ! empty( $parts['extension'] ) ) {
            $temp_file .= '.' . $parts[ 'extension' ];
        }

        return $temp_file;
    } // End get_temp_file_path()


    /**
     * Get the backup file path
     *
     * @return string
     */
    private function get_backup_file_path() : string {
        $basename = basename( $this->abspath );
        $dir      = dirname( $this->abspath );
        $timestamp = gmdate( 'Y-m-d-H-i-s' );

        if ( strtolower( $basename ) === '.htaccess' ) {
            return $dir . '/' . $basename . '-' . $timestamp;
        }

        $parts       = pathinfo( $basename );
        $backup_file = $dir . '/' . $parts[ 'filename' ] . '-' . $timestamp;
        if ( ! empty( $parts[ 'extension' ] ) ) {
            $backup_file .= '.' . $parts[ 'extension' ];
        }

        return $backup_file;
    } // End get_backup_file_path()


    /**
     * Handle AJAX request to save edits
     *
     * @return void
     */
    public function ajax_save_edits() {
        $errors = [];

        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            $errors[] = __( 'Unauthorized', 'dev-debug-tools' );
            wp_send_json_error( [ 'errors' => $errors ] );
        }

        // --- 0. Prepare filesystem and make sure we can write to the folder ---
        if ( ! function_exists( 'request_filesystem_credentials' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        global $wp_filesystem;
        if ( ! WP_Filesystem() || ! is_object( $wp_filesystem ) ) {
            $errors[] = __( 'Could not access filesystem. Check file permissions.', 'dev-debug-tools' );
            wp_send_json_error( [ 'errors' => $errors ] );
        }

        if ( ! $wp_filesystem->exists( $this->abspath ) ) {
            /* translators: %s: filename */
            $errors[] = sprintf( __( '%s not found.', 'dev-debug-tools' ), esc_html( $this->filename ) );
            wp_send_json_error( [ 'errors' => $errors ] );
        }

        if ( ! $wp_filesystem->is_writable( $this->abspath ) ) {
            /* translators: %s: filename */
            $errors[] = sprintf( __( '%s is not writable. Please check file permissions.', 'dev-debug-tools' ), esc_html( $this->filename ) );
            wp_send_json_error( [ 'errors' => $errors ] );
        }

        if ( isset( $_POST[ 'use_backup_file' ] ) && sanitize_file_name( wp_unslash( $_POST[ 'use_backup_file' ] ) ) !== '' ) {
            $temp_file = $this->absdir . '/' . sanitize_file_name( wp_unslash( $_POST[ 'use_backup_file' ] ) );
            $type = 'backup';
        } elseif ( isset( $_POST[ 'use_temp_file' ] ) && filter_var( wp_unslash( $_POST[ 'use_temp_file' ] ), FILTER_VALIDATE_BOOLEAN ) ) {
            $temp_file = $this->get_temp_file_path();
            $type = 'temp'; 
        } else {
            $temp_file = $this->get_temp_file_path();
            $type = 'content';
        }

        // Always clean up the temp file when the script exits
        if ( $type === 'temp' || $type === 'content' ) {
            register_shutdown_function( function() use ( $temp_file ) {
                if ( file_exists( $temp_file ) ) {
                    wp_delete_file( $temp_file );
                }
            } );
        }

        // --- 2. Get the contents and create the temp file ---
        if ( $type === 'backup' || $type === 'temp' ) {
            $content = $wp_filesystem->get_contents( $temp_file );
        } elseif ( isset( $_POST[ 'content' ] ) && ! empty( $_POST[ 'content' ] ) ) {
            $content = wp_unslash( $_POST[ 'content' ] ); // phpcs:ignore
        } else {
            $errors[] = __( 'No content received.', 'dev-debug-tools' );
            wp_send_json_error( [ 'errors' => $errors ] );
        }

        $eol = Helpers::get_eol_char( Helpers::get_file_eol( $content ) );
        $content = preg_replace( "/\r\n|\n\r|\r|\n/", $eol, $content );

        if ( ! $wp_filesystem->put_contents( $temp_file, $content, FS_CHMOD_FILE ) ) {
            $errors[] = __( 'Could not write temp file. Check file permissions.', 'dev-debug-tools' );
            wp_send_json_error( [ 'errors' => $errors ] );
        }

        // --- 3. Check for errors ---
        try {
            $validation_errors = $this->class_name::validate_file( $content, $temp_file );
            $errors = array_merge( $errors, $validation_errors );
        } catch ( \Throwable $e ) {
            wp_send_json_error( [ 'errors' => [ $e->getMessage() ] ] );
        }

        // --- 4. No errors, proceed to override ---
        if ( empty( $errors ) ) {

            // Back up the original wp-config.php file
            $backup_file = $this->get_backup_file_path();
            if ( copy( $this->abspath, $backup_file ) ) {

                // Backup succeeded, now override the original file
                if ( $wp_filesystem->move( $temp_file, $this->abspath, true ) ) {

                    // Flush WordPress cache
                    if ( function_exists( 'wp_cache_flush' ) ) {
                        wp_cache_flush();
                    }

                } else {
                    $errors[] = __( 'Could not override file. Check file permissions.', 'dev-debug-tools' );
                    wp_delete_file( $temp_file );
                }

            } else {
                $errors[] = __( 'Could not create backup of file. Check file permissions.', 'dev-debug-tools' );
                wp_delete_file( $temp_file );
            }

        } else {
            wp_delete_file( $temp_file );
        }

        // --- 5. Send the response ---
        if ( empty( $errors ) ) {
            set_transient( 'ddtt_' . $this->shortname . '_file_updated', true, 30 );
        }

        update_option( 'ddtt_' . $this->shortname . '_last_modified', [
            'timestamp' => time(),
            'user_id'   => get_current_user_id()
        ] );

        if ( ! empty( $errors ) ) {
            wp_send_json_error( [ 'errors' => $errors ] );
        } else {
            wp_send_json_success( [ 'errors' => [] ] );
        }
    } // End ajax_save_edits()


    /**
     * Preview snippets
     */
    public function ajax_preview_snippets() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        $temp_file = $this->get_temp_file_path();

        $errors = [];

        // --- 0. Prepare filesystem and make sure we can write to the folder ---
        if ( ! function_exists( 'request_filesystem_credentials' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        global $wp_filesystem;
        if ( ! WP_Filesystem() || ! is_object( $wp_filesystem ) ) {
            $errors[] = __( 'Could not access filesystem. Check file permissions.', 'dev-debug-tools' );
            wp_send_json_error( $errors );
        }

        if ( ! $wp_filesystem->exists( $this->abspath ) ) {
            /* translators: %s: filename */
            $errors[] = sprintf( __( '%s not found.', 'dev-debug-tools' ), esc_html( $this->filename ) );
            wp_send_json_error( $errors );
        }

        if ( ! $wp_filesystem->is_writable( $this->abspath ) ) {
            /* translators: %s: filename */
            $errors[] = sprintf( __( '%s is not writable. Please check file permissions.', 'dev-debug-tools' ), esc_html( $this->filename ) );
            wp_send_json_error( $errors );
        }

        // --- 2. Get the contents ---
        $contents = $wp_filesystem->get_contents( $this->abspath );
        if ( $contents === false ) {
            $errors[] = __( 'Failed to read file contents.', 'dev-debug-tools' );
            wp_send_json_error( $errors );
        }

        $eol = Helpers::get_eol_char( Helpers::get_file_eol( $contents ) );
        $contents = preg_replace( "/\r\n|\n\r|\r|\n/", $eol, $contents );

        $add    = isset( $_POST[ 'add' ] ) ? wp_unslash( $_POST[ 'add' ] ) : [ ]; // phpcs:ignore
        $update = isset( $_POST[ 'update' ] ) ? wp_unslash( $_POST[ 'update' ] ) : [ ]; // phpcs:ignore
        $remove = isset( $_POST[ 'remove' ] ) ? wp_unslash( $_POST[ 'remove' ] ) : [ ]; // phpcs:ignore
        $remove = array_map( 'sanitize_text_field', $remove );

        $lines = explode( $eol, $contents );

        // --- 3. Update the file with the changes ---
        $all_snippets = $this->get_all_snippets();

        $lines = $this->class_name::update_content_with_snippets( $all_snippets, $lines, [
            'add'    => $add,
            'update' => $update,
            'remove' => $remove,
        ] );

        $temp_contents = implode( $eol, $lines );

        // --- 4. Create the temp file ---
        if ( ! $wp_filesystem->put_contents( $temp_file, $temp_contents, FS_CHMOD_FILE ) ) {
            $errors[] = __( 'Could not write temp file. Check file permissions.', 'dev-debug-tools' );
            wp_send_json_success( [ 'errors' => $errors ] );
        }

        // --- 5. Get viewer HTML and date ---
        $customizations = filter_var_array( get_option( 'ddtt_' . $this->shortname . '_viewer_customizations', [] ), FILTER_SANITIZE_SPECIAL_CHARS );
        ob_start();
        $this->render_file_viewer( $temp_contents, $customizations, true );
        $viewer_html = ob_get_clean();

        $formatted_date = Helpers::convert_timezone( time(), 'F j, Y g:i A' );

        // --- 6. Send the response ---
        wp_send_json_success( [
            'viewer' => $viewer_html,
            'date'   => $formatted_date,
            'errors' => $errors
        ] );
    } // End ajax_preview_snippets()


    /**
     * Handle AJAX request to delete temp preview file
     *
     * @return void
     */
    public function ajax_delete_preview_file() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        global $wp_filesystem;
        if ( ! function_exists( 'WP_Filesystem' ) ) {
            WP_Filesystem();
        }

        $temp_file = $this->get_temp_file_path();

        if ( $wp_filesystem->exists( $temp_file ) ) {
            $wp_filesystem->delete( $temp_file );
            wp_send_json_success( 'deleted' );
        } else {
            wp_send_json_error( 'file_not_found' );
        }
    } // End ajax_delete_preview_file()


    /**
     * Handle AJAX request to update the EOL type
     *
     * @return void
     */
    public function ajax_load_previewer() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        $filename = isset( $_POST[ 'filename' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'filename' ] ) ) : '';
        $customizations = filter_var_array( get_option( 'ddtt_' . $this->shortname . '_viewer_customizations', [] ), FILTER_SANITIZE_SPECIAL_CHARS );

        $raw_contents = Helpers::get_file_contents( $filename );

        if ( ! $raw_contents || is_wp_error( $raw_contents ) ) {
            wp_send_json_error( 'file_not_found' );
        }

        // Determine regex dynamically based on file
        if ( strtolower( $this->filename ) === '.htaccess' ) {
            $pattern = '/^\.htaccess-(\d{4})-(\d{2})-(\d{2})-(\d{2})-(\d{2})-(\d{2})$/';
            $file_with_ext = $filename; // already full name
        } else {
            $pattern = '/^' . preg_quote( $this->tool_slug, '/' ) . '-(\d{4})-(\d{2})-(\d{2})-(\d{2})-(\d{2})-(\d{2})\.php$/';
            $file_with_ext = $filename . '.php';
        }

        $formatted_date = '';
        if ( preg_match( $pattern, $file_with_ext, $matches ) ) {
            $formatted_date = Helpers::convert_timezone(
                sprintf(
                    '%s-%s-%s %s:%s:%s',
                    $matches[1], $matches[2], $matches[3],
                    $matches[4], $matches[5], $matches[6]
                ),
                'F j, Y g:i A'
            );
        }

        ob_start();
        $this->render_file_viewer( $raw_contents, $customizations, true );
        $viewer_html = ob_get_clean();

        wp_send_json_success( [
            'viewer' => $viewer_html,
            'date'   => $formatted_date,
        ] );
    } // End ajax_load_previewer()


    /**
     * Add a new snippet
     */
    public function ajax_add_snippet() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Unauthorized.', 'dev-debug-tools' ) );
        }

        $snippet = isset( $_POST[ 'snippet' ] ) ? wp_unslash( $_POST[ 'snippet' ] ) : []; // phpcs:ignore
        if ( empty( $snippet[ 'lines' ] ) || empty( $snippet[ 'label' ] ) ) {
            wp_send_json_error( __( 'Invalid snippet data.', 'dev-debug-tools' ) );
        }

        // Sanitize label and description
        $label = sanitize_text_field( $snippet[ 'label' ] );
        $desc  = wp_kses_post( $snippet[ 'desc' ] );
        $lines = $snippet[ 'lines' ];

        // Determine key from first variable
        $key = $this->class_name::create_key_from_snippet( $snippet );

        if ( empty( $key ) ) {
            wp_send_json_error( __( 'Variable name cannot be empty.', 'dev-debug-tools' ) );
        }

        // Gather all existing snippets
        $default_snippets = $this->class_name::snippets();
        $custom_snippets = get_option( $this->option_key, [] );
        $existing_snippets = array_merge( $custom_snippets, $default_snippets );

        // Collect all variables across existing snippets
        $snippet_key = $this->class_name::does_snippet_key_exist( $existing_snippets, $snippet );
        if ( $snippet_key ) {
            wp_send_json_error( __( 'This snippet already exists. You can either edit or delete the existing snippet or choose a different variable name.', 'dev-debug-tools' ) );
        }

        // Let's detect it if it's already on the file
        $raw_contents = Helpers::get_file_contents( 'wp-config.php' );
        $snippet_exists = $this->class_name::does_snippet_exist_in_content( $raw_contents, $snippet );

        // Save the core snippets in this list so we can track removals and prep for sorting in the future
        if ( ! isset( $custom_snippets[ 'order' ] ) ) {
            $custom_snippets[ 'order' ] = array_keys( $default_snippets );
        }

        // Add the custom key to the order as well
        if ( ! in_array( $key, $custom_snippets[ 'order' ], true ) ) {
            $custom_snippets[ 'order' ][] = $key;
        }

        // Added by
        $added_by = get_current_user_id();
        $added_by_name = get_userdata( $added_by )->display_name;
        $added_when = current_time( 'mysql' );

        // Save new snippet
        $custom_snippets[ 'custom' ][ $key ] = [
            'label' => $label,
            'desc'  => $desc,
            'lines' => $lines,
            'added' => [
                'author' => $added_by,
                'date'   => $added_when,
            ],
        ];

        update_option( $this->option_key, $custom_snippets );

        wp_send_json_success( [ 
            'key'      => $key,
            'exists'   => $snippet_exists,
            'added_by' => $added_by_name
        ] );
    } // End ajax_add_snippet()


    /**
     * Add a new snippet
     */
    public function ajax_delete_snippet() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Unauthorized.', 'dev-debug-tools' ) );
        }

        $key = sanitize_text_field( wp_unslash( $_POST[ 'key' ] ?? '' ) );
        if ( ! $key ) wp_send_json_error();

        $custom_snippets = filter_var_array( get_option( $this->option_key, [] ), FILTER_SANITIZE_SPECIAL_CHARS );
        $order  = isset( $custom_snippets[ 'order' ] ) && is_array( $custom_snippets[ 'order' ] ) ? $custom_snippets[ 'order' ] : [];
        $custom = isset( $custom_snippets[ 'custom' ] ) && is_array( $custom_snippets[ 'custom' ] ) ? $custom_snippets[ 'custom' ] : [];

        if ( empty( $order ) ) {
            $defaults = array_keys( $this->class_name::snippets() );
            $custom_keys = array_keys( $custom );
            $order = array_merge( $defaults, $custom_keys );
            $order = array_unique( $order );
        }

        $order = array_values( array_diff( $order, [ $key ] ) );
        unset( $custom[ $key ] );

        update_option( $this->option_key, [
            'order'  => $order,
            'custom' => $custom
        ] );

        wp_send_json_success();
    } // End ajax_delete_snippet()


    /**
     * Handle AJAX request to update the EOL type
     *
     * @return void
     */
    public function ajax_delete_backup_file() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        $filename = isset( $_POST[ 'filename' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'filename' ] ) ) : '';
        $filepath = $this->absdir . $filename;

        if ( empty( $filename ) || ! file_exists( $filepath ) ) {
            wp_send_json_error( 'file_not_found' );
        }

        if ( ! function_exists( 'request_filesystem_credentials' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        global $wp_filesystem;
        if ( ! WP_Filesystem() || ! is_object( $wp_filesystem ) ) {
            wp_send_json_error( 'filesystem_unavailable' );
        }

        if ( $wp_filesystem->exists( $filepath ) ) {
            if ( $wp_filesystem->delete( $filepath ) ) {
                set_transient( 'ddtt_' . $this->shortname . '_backup_deleted', $filename, 30 );
                wp_send_json_success( 'deleted' );
            } else {
                wp_send_json_error( 'delete_failed' );
            }
        } else {
            wp_send_json_error( 'file_not_found' );
        }
    } // End ajax_load_previewer()


    /**
     * Handle AJAX request to update the EOL type
     *
     * @return void
     */
    public function ajax_clear_all_backups() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        $backups = $this->get_backups( true ); // Skip most recent

        if ( empty( $backups ) ) {
            wp_send_json_error( 'no_backups_found' );
        }

        if ( ! function_exists( 'request_filesystem_credentials' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        global $wp_filesystem;
        if ( ! WP_Filesystem() || ! is_object( $wp_filesystem ) ) {
            wp_send_json_error( 'filesystem_unavailable' );
        }

        $deleted = 0;
        foreach ( $backups as $filename => $date ) {
            $filepath = $this->absdir . $filename;
            if ( $wp_filesystem->exists( $filepath ) ) {
                if ( $wp_filesystem->delete( $filepath ) ) {
                    $deleted++;
                }
            }
        }

        if ( $deleted ) {
            set_transient( 'ddtt_' . $this->shortname . '_backups_deleted', [
                'deleted' => $deleted,
                'total'   => count( $backups )
            ], 30 );
            wp_send_json_success( [ 'deleted' => $deleted ] );
        } else {
            wp_send_json_error( 'delete_failed' );
        }
    } // End ajax_clear_all_backups()


    /**
     * Prevent cloning and unserializing
     */
    private function __clone() {}
    private function __wakeup() {}

}