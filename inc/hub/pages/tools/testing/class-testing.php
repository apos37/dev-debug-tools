<?php
/**
 * Testing
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Testing {

    /**
     * Nonce for updating meta
     *
     * @var string
     */
    private $nonce = 'ddtt_testing_nonce';


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?Testing $instance = null;


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
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_ddtt_run_code_test', [ $this, 'ajax_run_test' ] );
        add_action( 'wp_ajax_nopriv_ddtt_run_code_test', '__return_false' );
        add_action( 'wp_ajax_ddtt_save_testing_theme', [ $this, 'ajax_save_theme' ] );
        add_action( 'wp_ajax_nopriv_ddtt_save_testing_theme', '__return_false' );
        add_action( 'wp_ajax_ddtt_get_playground_data', [ $this, 'ajax_get_playground_data' ] );
        add_action( 'wp_ajax_nopriv_ddtt_get_playground_data', '__return_false' );
    } // End __construct()


    /**
     * Render the output from the last run
     */
    public static function render_output() {
        $output = '';
        $errors = [];

        $upload_dir = wp_upload_dir();
        $ddtt_dir   = trailingslashit( $upload_dir[ 'basedir' ] ) . 'dev-debug-tools/';

        // Initialize WP Filesystem
        if ( ! function_exists( 'WP_Filesystem' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        if ( WP_Filesystem() ) {
            global $wp_filesystem;

            $user_id   = get_current_user_id();
            $temp_file = $ddtt_dir . 'ddtt_test_' . $user_id . '.php';

            if ( $wp_filesystem->exists( $temp_file ) ) {

                // Capture output safely
                ob_start();
                try {
                    include $temp_file;
                } catch ( \Throwable $e ) {
                    $errors[] = [
                        'message' => $e->getMessage(),
                        'line'    => $e->getLine(),
                    ];
                }
                $output = ob_get_clean();
            }
        }
        ?>
        <section id="ddtt-test-output-section">
            <h3><?php esc_html_e( 'Your results will appear here.', 'dev-debug-tools' ); ?></h3>
            <div id="ddtt-testing-output">
                <?php
                // Display errors if any
                if ( ! empty( $errors ) ) {
                    echo '<ul class="ddtt-errors">';
                    foreach ( $errors as $err ) {
                        echo '<li>' . esc_html( $err[ 'message' ] );
                        if ( ! empty( $err[ 'line' ] ) ) {
                            echo ' (' . esc_html__( 'Check line', 'dev-debug-tools' ) . ' ' . intval( $err[ 'line' ] ) . ')';
                        }
                        echo '</li>';
                    }
                    echo '</ul>';
                }

                // Display captured output
                if ( $output !== '' ) {
                    echo wp_kses_post( $output );
                } else if ( empty( $errors ) ) {
                    echo '<p>' . esc_html__( 'No output was returned.', 'dev-debug-tools' ) . '</p>';
                }
                ?>
            </div>
        </section>
        <?php
    } // End render_output()


    /**
     * Render the code box
     */
    public static function render_code_box() {
        $content = '';

        $upload_dir = wp_upload_dir();
        $ddtt_dir   = trailingslashit( $upload_dir[ 'basedir' ] ) . 'dev-debug-tools/';

        // Initialize WP Filesystem
        if ( ! function_exists( 'WP_Filesystem' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        if ( WP_Filesystem() ) {
            global $wp_filesystem;

            $user_id   = get_current_user_id();
            $temp_file = $ddtt_dir . 'ddtt_test_' . $user_id . '.php';

            if ( $wp_filesystem->exists( $temp_file ) ) {
                $content = $wp_filesystem->get_contents( $temp_file );
            }
        }
        ?>
        <section id="ddtt-code-box">
            <h3><?php esc_html_e( 'Enter your code here:', 'dev-debug-tools' ); ?></h3>
            <p><?php echo wp_kses_post( __( 'Enter your HTML in the code box below and hit the "Run Code" button on the right. Your results will appear above. If you are testing PHP, please use <code>&lt;?php ... ?&gt;</code> tags.', 'dev-debug-tools' ) ); ?></p>
            <div class="lined-wrap">
                <div class="lined-numbers"></div>
                <textarea id="ddtt-testing-code" rows="100" style="width: 100%;"><?php echo esc_textarea( $content ); ?></textarea>
            </div>
        </section>
        <?php
    } // End render_code_box()


    /**
     * Render the WordPress Playground link generator sidebar section
     */
    public static function render_playground_sidebar() {
        $current_wp   = get_bloginfo( 'version' );
        $current_php  = implode( '.', array_slice( explode( '.', PHP_VERSION ), 0, 2 ) );
        $is_multisite = is_multisite();

        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $installed_plugins = get_plugins();
        ?>
        <h3><?php esc_html_e( 'WordPress Playground', 'dev-debug-tools' ); ?></h3>
        <p class="playground-description description"><?php esc_html_e( 'Generate a link to test in an in-browser WordPress instance.', 'dev-debug-tools' ); ?></p>
        <p id="ddtt-playground-loading" class="description">
            <span class="dashicons dashicons-update ddtt-rotate"></span>
            <?php esc_html_e( 'Fetching available versions...', 'dev-debug-tools' ); ?>
        </p>
        <div id="ddtt-playground-generator">
            <div class="ddtt-playground-field">
                <label for="ddtt-playground-wp"><?php esc_html_e( 'WordPress Version', 'dev-debug-tools' ); ?></label>
                <select id="ddtt-playground-wp">
                    <option value="latest"><?php esc_html_e( 'latest', 'dev-debug-tools' ); ?></option>
                    <option value="nightly"><?php esc_html_e( 'nightly', 'dev-debug-tools' ); ?></option>
                    <option id="ddtt-playground-wp-beta" value="beta"><?php esc_html_e( 'beta', 'dev-debug-tools' ); ?></option>
                    <option value="<?php echo esc_attr( $current_wp ); ?>" selected>
                        <?php
                        /* translators: %s: current WordPress version number */
                        echo esc_html( sprintf( __( '%s (current)', 'dev-debug-tools' ), $current_wp ) );
                        ?>
                    </option>
                </select>
            </div>
            <div class="ddtt-playground-field ddtt-has-help-dialog">
                <label for="ddtt-playground-php">
                    <?php esc_html_e( 'PHP Version', 'dev-debug-tools' ); ?>
                    <span class="ddtt-help-wrap">
                        <a href="#" class="ddtt-help-toggle ddtt-help-icon" aria-controls="ddtt-help-php" aria-expanded="false" aria-label="<?php esc_attr_e( 'Learn more about PHP Version', 'dev-debug-tools' ); ?>">
                            <span class="dashicons dashicons-editor-help" aria-hidden="true"></span>
                        </a>
                        <div id="ddtt-help-php" class="ddtt-help-content" hidden>
                            <button type="button" class="ddtt-help-close">&times;</button>
                            <div class="ddtt-help-body">
                                <p><?php esc_html_e( 'Playground only supports a limited set of recent PHP versions. Older versions are automatically upgraded or unavailable, so you may not see every version your production server supports.', 'dev-debug-tools' ); ?></p>
                            </div>
                        </div>
                    </span>
                </label>
                <select id="ddtt-playground-php">
                    <option value="<?php echo esc_attr( $current_php ); ?>" selected>
                        <?php
                        /* translators: %s: current PHP version number */
                        echo esc_html( sprintf( __( '%s (current)', 'dev-debug-tools' ), $current_php ) );
                        ?>
                    </option>
                </select>
            </div>
            <div class="ddtt-playground-field ddtt-has-help-dialog">
                <label for="ddtt-playground-networking">
                    <?php esc_html_e( 'Networking', 'dev-debug-tools' ); ?>
                    <span class="ddtt-help-wrap">
                        <a href="#" class="ddtt-help-toggle ddtt-help-icon" aria-controls="ddtt-help-networking" aria-expanded="false" aria-label="<?php esc_attr_e( 'Learn more about Networking', 'dev-debug-tools' ); ?>">
                            <span class="dashicons dashicons-editor-help" aria-hidden="true"></span>
                        </a>
                        <div id="ddtt-help-networking" class="ddtt-help-content" hidden>
                            <button type="button" class="ddtt-help-close">&times;</button>
                            <div class="ddtt-help-body">
                                <p><?php esc_html_e( 'Allows the Playground instance to make outbound network requests. This is needed for things like downloading language packs, installing plugins/themes from WordPress.org, or contacting external APIs. Turn it off to simulate an offline environment.', 'dev-debug-tools' ); ?></p>
                            </div>
                        </div>
                    </span>
                </label>
                <select id="ddtt-playground-networking">
                    <option value="yes"><?php esc_html_e( 'Yes', 'dev-debug-tools' ); ?></option>
                    <option value="no"><?php esc_html_e( 'No', 'dev-debug-tools' ); ?></option>
                </select>
            </div>
            <div class="ddtt-playground-field">
                <label for="ddtt-playground-multisite"><?php esc_html_e( 'Multisite', 'dev-debug-tools' ); ?></label>
                <select id="ddtt-playground-multisite">
                    <option value="no" <?php selected( ! $is_multisite ); ?>>
                        <?php echo esc_html( $is_multisite ? __( 'No', 'dev-debug-tools' ) : __( 'No (current)', 'dev-debug-tools' ) ); ?>
                    </option>
                    <option value="yes" <?php selected( $is_multisite ); ?>>
                        <?php echo esc_html( $is_multisite ? __( 'Yes (current)', 'dev-debug-tools' ) : __( 'Yes', 'dev-debug-tools' ) ); ?>
                    </option>
                </select>
            </div>
            <div class="ddtt-playground-field ddtt-has-help-dialog">
                <label for="ddtt-playground-language">
                    <?php esc_html_e( 'Language', 'dev-debug-tools' ); ?>
                    <span class="ddtt-help-wrap">
                        <a href="#" class="ddtt-help-toggle ddtt-help-icon" aria-controls="ddtt-help-language" aria-expanded="false" aria-label="<?php esc_attr_e( 'Learn more about Language', 'dev-debug-tools' ); ?>">
                            <span class="dashicons dashicons-editor-help" aria-hidden="true"></span>
                        </a>
                        <div id="ddtt-help-language" class="ddtt-help-content" hidden>
                            <button type="button" class="ddtt-help-close">&times;</button>
                            <div class="ddtt-help-body">
                                <p><?php esc_html_e( 'Sets the WordPress locale for this instance, e.g. es_ES or fr_FR. Only works when Networking is set to Yes, since Playground needs to download the translation files.', 'dev-debug-tools' ); ?></p>
                            </div>
                        </div>
                    </span>
                </label>
                <input type="text" id="ddtt-playground-language" placeholder="en_US">
            </div>
            <div class="ddtt-playground-field">
                <label for="ddtt-playground-cache-bust"><?php esc_html_e( 'Cache Busting', 'dev-debug-tools' ); ?></label>
                <select id="ddtt-playground-cache-bust">
                    <option value="yes"><?php esc_html_e( 'Yes', 'dev-debug-tools' ); ?></option>
                    <option value="no"><?php esc_html_e( 'No', 'dev-debug-tools' ); ?></option>
                </select>
            </div>
            <div class="ddtt-playground-field">
                <label for="ddtt-playground-plugin-picker"><?php esc_html_e( 'Add Installed Plugin', 'dev-debug-tools' ); ?></label>
                <select id="ddtt-playground-plugin-picker">
                    <option value=""><?php esc_html_e( '-- Select a plugin --', 'dev-debug-tools' ); ?></option>
                    <?php foreach ( $installed_plugins as $plugin_file => $plugin_data ) :
                        $slug = dirname( $plugin_file );
                        $slug = ( $slug === '.' ) ? basename( $plugin_file, '.php' ) : $slug;
                    ?>
                        <option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $plugin_data[ 'Name' ] ); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php esc_html_e( 'Plugins are matched by slug only. Premium or custom plugins that happen to share a folder name with an unrelated free plugin on WordPress.org may install the wrong plugin, so double check the slug before relying on it.', 'dev-debug-tools' ); ?></p>
            </div>
            <div class="ddtt-playground-field">
                <label for="ddtt-playground-plugin-manual"><?php esc_html_e( 'Or Enter a Plugin Slug', 'dev-debug-tools' ); ?></label>
                <div class="ddtt-playground-inline-add">
                    <input type="text" id="ddtt-playground-plugin-manual" placeholder="<?php esc_attr_e( 'e.g. query-monitor, hello-dolly', 'dev-debug-tools' ); ?>">
                    <button type="button" id="ddtt-playground-plugin-add" class="ddtt-button"><?php esc_html_e( 'Add', 'dev-debug-tools' ); ?></button>
                </div>
            </div>
            <?php
            $author_plugins_config = self::get_playground_author_plugins_config();
            if ( $author_plugins_config[ 'enabled' ] ) :
            ?>
                <div class="ddtt-playground-field">
                    <button type="button" id="ddtt-playground-add-pluginrx" class="ddtt-button full-width" data-slugs="dev-debug-tools" disabled><?php echo esc_html( $author_plugins_config[ 'label' ] ); ?></button>
                </div>
            <?php endif; ?>
            <div class="ddtt-playground-field">
                <label><?php esc_html_e( 'Plugins to Install', 'dev-debug-tools' ); ?></label>
                <ul id="ddtt-playground-plugin-list" class="ddtt-playground-tag-list"></ul>
            </div>
            <div class="ddtt-playground-field">
                <label for="ddtt-playground-url"><?php esc_html_e( 'Link Preview', 'dev-debug-tools' ); ?></label>
                <input type="text" id="ddtt-playground-url" readonly>
            </div>
            <button id="ddtt-playground-go" type="button" class="ddtt-button full-width"><?php esc_html_e( 'Go Play', 'dev-debug-tools' ); ?></button>
        </div>
        <?php
    } // End render_playground_sidebar()


    /**
     * Enqueue assets
     *
     * @param string $hook The current admin page hook.
     * @return void
     */
    public function enqueue_assets( $hook ) : void {
        if ( ! AdminMenu::is_current_screen( $hook, 'tools', 'testing' ) ) {
            return;
        }

        wp_localize_script( 'ddtt-tool-testing', 'ddtt_testing', [
            'nonce'  => wp_create_nonce( $this->nonce ),
            'i18n'   => [
                'loading'     => __( 'Please wait. Running your tests...', 'dev-debug-tools' ),
                'check_line'  => __( 'Check line', 'dev-debug-tools' ),
                'no_output'   => __( 'No output was returned.', 'dev-debug-tools' ),
                'ajax_error'  => __( 'An error occurred while processing the request.', 'dev-debug-tools' ),
            ],
            'playground' => [
                'ajaxurl'     => admin_url( 'admin-ajax.php' ),
                'current_wp'  => get_bloginfo( 'version' ),
                'current_php' => implode( '.', array_slice( explode( '.', PHP_VERSION ), 0, 2 ) ),
                // 'current_php' => '8.7', // Hardcoded for testing, as the Playground only supports a limited set of recent PHP versions
                'fallback_wp' => [ get_bloginfo( 'version' ) ],
                'base_url'    => 'https://playground.wordpress.net/',
            ],
        ] );
    } // End enqueue_assets()


    /**
     * Handle AJAX request to run the test
     *
     * @return void
     */
    public function ajax_run_test() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        $content = isset( $_POST[ 'content' ] ) ? wp_unslash( $_POST[ 'content' ] ) : ''; // phpcs:ignore

        $errors = [];
        $output = [];

        // Permanent uploads folder path
        $upload_dir = wp_upload_dir();
        $ddtt_dir   = trailingslashit( $upload_dir[ 'basedir' ] ) . 'dev-debug-tools/';

        // Initialize WP Filesystem
        if ( ! function_exists( 'WP_Filesystem' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        if ( ! WP_Filesystem( null, null, null, null, null ) ) {
            wp_send_json_error( 'filesystem_unavailable' );
        }

        global $wp_filesystem;

        // Create directory if it doesn't exist
        if ( ! $wp_filesystem->is_dir( $ddtt_dir ) ) {
            $wp_filesystem->mkdir( $ddtt_dir );
        }

        // User-specific file
        $user_id   = get_current_user_id();
        $temp_file = $ddtt_dir . 'ddtt_test_' . $user_id . '.php';

        if ( trim( $content ) === '' ) {
            // Delete temp file if content is empty
            if ( $wp_filesystem->exists( $temp_file ) ) {
                $wp_filesystem->delete( $temp_file );
            }
        } else {
            // Save content using WP Filesystem
            $wp_filesystem->put_contents( $temp_file, $content, FS_CHMOD_FILE );

            // Execute PHP + capture output
            ob_start();
            try {
                include $temp_file;
            } catch ( \Throwable $e ) {
                $errors[] = [
                    'message' => $e->getMessage(),
                    'line'    => $e->getLine(),
                ];
            }
            $captured = ob_get_clean();

            if ( $captured !== '' ) {
                $output = explode( "\n", $captured );
            }
        }

        wp_send_json_success( [
            'output'  => $output,
            'errors'  => $errors,
            'content' => $content,
        ] );
    } // End ajax_run_test()


    /**
     * Handle AJAX request to fetch WP/PHP version data for the Playground generator
     *
     * @return void
     */
    public function ajax_get_playground_data() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        $data = get_transient( 'ddtt_playground_version_data' );

        if ( false === $data ) {
            $data = [
                'wp_versions'  => [],
                'php_versions' => [],
                'beta_version' => 'none',
            ];

            $stable_check = wp_remote_get( 'https://api.wordpress.org/core/stable-check/1.0/', [ 'timeout' => 10 ] );

            if ( ! is_wp_error( $stable_check ) && wp_remote_retrieve_response_code( $stable_check ) === 200 ) {
                $body = json_decode( wp_remote_retrieve_body( $stable_check ), true );

                if ( ! empty( $body ) && is_array( $body ) ) {
                    $versions = [];
                    foreach ( $body as $version => $status ) {
                        if ( $status === 'latest' || $status === 'outdated' ) {
                            $versions[] = $version;
                        }
                    }

                    usort( $versions, function ( $a, $b ) {
                        return version_compare( $b, $a );
                    } );

                    $data[ 'wp_versions' ] = array_slice( $versions, 0, 25 );
                }
            }

            $version_check = wp_remote_get( 'https://api.wordpress.org/core/version-check/1.7/?channel=beta', [ 'timeout' => 10 ] );

            if ( ! is_wp_error( $version_check ) && wp_remote_retrieve_response_code( $version_check ) === 200 ) {
                $body = json_decode( wp_remote_retrieve_body( $version_check ), true );

                if ( ! empty( $body[ 'offers' ][ 0 ][ 'version' ] ) ) {
                    $data[ 'beta_version' ] = $body[ 'offers' ][ 0 ][ 'version' ];
                }
            }

            $schema_check = wp_remote_get( 'https://playground.wordpress.net/blueprint-schema.json', [ 'timeout' => 10 ] );

            if ( ! is_wp_error( $schema_check ) && wp_remote_retrieve_response_code( $schema_check ) === 200 ) {
                $body = json_decode( wp_remote_retrieve_body( $schema_check ), true );
                $enum = $body[ 'definitions' ][ 'SupportedPHPVersion' ][ 'enum' ] ?? [];

                if ( ! empty( $enum ) && is_array( $enum ) ) {
                    $data[ 'php_versions' ] = $enum;
                }
            }

            set_transient( 'ddtt_playground_version_data', $data, DAY_IN_SECONDS );
        }

        $author_plugins_config = self::get_playground_author_plugins_config();

        if ( $author_plugins_config[ 'enabled' ] ) {
            $config_hash = md5( wp_json_encode( [
                'author' => $author_plugins_config[ 'author' ],
                'slugs'  => $author_plugins_config[ 'slugs' ],
            ] ) );

            $cached = get_transient( 'ddtt_playground_author_plugins' );

            if ( ! empty( $cached[ 'hash' ] ) && $cached[ 'hash' ] === $config_hash ) {
                $data[ 'author_plugins' ] = $cached[ 'slugs' ];
            } else {
                if ( is_array( $author_plugins_config[ 'slugs' ] ) ) {
                    $resolved_slugs = $author_plugins_config[ 'slugs' ];
                } else {
                    $resolved_slugs = $this->get_author_plugin_slugs( $author_plugins_config[ 'author' ] );
                }

                $data[ 'author_plugins' ] = $resolved_slugs;

                if ( ! empty( $resolved_slugs ) ) {
                    set_transient( 'ddtt_playground_author_plugins', [
                        'hash'  => $config_hash,
                        'slugs' => $resolved_slugs,
                    ], DAY_IN_SECONDS );
                }
            }
        } else {
            $data[ 'author_plugins' ] = [];
            delete_transient( 'ddtt_playground_author_plugins' );
        }

        wp_send_json_success( $data );
    } // End ajax_get_playground_data()


    /**
     * Get the "Add All PluginRx Plugins" button configuration, filterable by developers
     *
     * @return array {
     *     @type bool         $enabled Whether to show the button at all. Default true.
     *     @type string       $label   Button label text.
     *     @type string       $author  WordPress.org username to query for plugins. Ignored if $slugs is set.
     *     @type array|null   $slugs   If provided, used directly instead of querying WordPress.org.
     * }
     */
    private static function get_playground_author_plugins_config() : array {
        $defaults = [
            'enabled' => true,
            'label'   => __( 'Add All PluginRx Plugins', 'dev-debug-tools' ),
            'author'  => 'apos37',
            'slugs'   => null,
        ];

        $config = apply_filters( 'ddtt_playground_author_plugins', $defaults );

        return wp_parse_args( $config, $defaults );
    } // End get_playground_author_plugins_config()


    /**
     * Fetch every plugin slug published by a given WordPress.org username
     *
     * @param string $username The WordPress.org account username
     * @return array Plugin slugs
     */
    private function get_author_plugin_slugs( string $username ) : array {
        if ( ! function_exists( 'plugins_api' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        }

        $result = plugins_api( 'query_plugins', [
            'author'   => $username,
            'per_page' => 100,
            'fields'   => [
                'slug' => true,
            ],
        ] );

        if ( is_wp_error( $result ) || empty( $result->plugins ) ) {
            return [];
        }

        $slugs = [];

        foreach ( $result->plugins as $plugin ) {
            $slug = is_array( $plugin ) ? ( $plugin[ 'slug' ] ?? '' ) : ( $plugin->slug ?? '' );

            if ( ! empty( $slug ) ) {
                $slugs[] = $slug;
            }
        }

        return $slugs;
    } // End get_author_plugin_slugs()


    /**
     * Prevent cloning and unserializing
     */
    public function __clone() {}
    public function __wakeup() {}

}


Testing::instance();