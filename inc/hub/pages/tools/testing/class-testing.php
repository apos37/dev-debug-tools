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
     * Prevent cloning and unserializing
     */
    private function __clone() {}
    private function __wakeup() {}

}


Testing::instance();