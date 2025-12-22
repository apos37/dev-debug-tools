<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

$is_dev = Helpers::is_dev();

// Check if WP_DEBUG is enabled
$wp_debug = defined( 'WP_DEBUG' ) && WP_DEBUG;

// Version
$versions = Dashboard::get_versions();
$plugin_version = $versions[ 'plugin' ];
$plugin_warning = $versions[ 'plugin_warning' ] ?? '';
$wp_version = $versions[ 'wp' ];
$wp_warning = $versions[ 'wp_warning' ] ?? '';
$php_version = $versions[ 'php' ];
$php_warning = $versions[ 'php_warning' ] ?? '';
$mysql_version = $versions[ 'mysql' ] ?? '';
$jquery_version = $versions[ 'jquery' ] ?? '';
$curl_version = $versions[ 'curl' ] ?? '';
$gd_version = $versions[ 'gd' ] ?? '';

// Metrics
$server_ip_address = isset( $_SERVER[ 'SERVER_ADDR' ] ) ? sanitize_text_field( wp_unslash( $_SERVER[ 'SERVER_ADDR' ] ) ) : gethostbyname( gethostname() );
$server_software = isset( $_SERVER[ 'SERVER_SOFTWARE' ] ) ? sanitize_text_field( wp_unslash( $_SERVER[ 'SERVER_SOFTWARE' ] ) ) : 'Unavailable';
$metrics = Dashboard::get_server_metrics();
$uptime = Dashboard::get_server_uptime();

// Environment Info
$env = Dashboard::get_environment_info();

// Timezone info
$wp_timezone = get_option( 'timezone_string' );
if ( empty( $wp_timezone ) ) {
    $wp_timezone = sprintf( 'UTC%+d', get_option( 'gmt_offset' ) );
}

// OPcache status
$opcache_enabled = function_exists( 'opcache_get_status' ) && opcache_get_status( false ) !== false;
$opcache_status = $opcache_enabled ? __( 'Enabled', 'dev-debug-tools' ) : __( 'Disabled', 'dev-debug-tools' );

// Get the issues
if ( $is_dev ) {
    $issues = (new Issues())->get();
}

// Support
$base_url  = Bootstrap::author_uri();
$text_domain = 'dev-debug-tools';

$our_links = [
    'video' => [
        'label' => __( 'Video Tutorial', 'dev-debug-tools' ),
        'url'   => "https://youtu.be/36aebqdzHQw",
    ],
    'guide' => [
        'label' => __( 'How-To Guide', 'dev-debug-tools' ),
        'url'   => "{$base_url}guide/plugin/{$text_domain}",
    ],
    'docs' => [
        'label' => __( 'Developer Docs', 'dev-debug-tools' ),
        'url'   => "{$base_url}docs/plugin/{$text_domain}",
    ],
    'support' => [
        'label' => __( 'Website Support Forum', 'dev-debug-tools' ),
        'url'   => "{$base_url}support/plugin/{$text_domain}",
    ],
    'discord' => [
        'label' => __( 'Discord Support Server', 'dev-debug-tools' ),
        'url'   => Bootstrap::discord_uri(),
    ],
    'wporg' => [
        'label' => __( 'WordPress.org Support Forum', 'dev-debug-tools' ),
        'url'   => "https://wordpress.org/support/plugin/{$text_domain}",
    ],
];
?>

<div id="ddtt-page-title-section">
    <div id="ddtt-page-title-left">
        <h2><?php esc_html_e( 'Dashboard', 'dev-debug-tools' ); ?></h2>
        <br>
        <code class="ddtt-code"><strong>ABSPATH:</strong> <?php echo wp_kses( Helpers::maybe_redact( ABSPATH ), [ 'i' => [ 'class' => [] ] ] ); ?></code>
    </div>
    <div id="ddtt-page-title-right">
        <?php if ( $is_dev ) : ?>
            <div id="ddtt-download-backups-cont">
                <form class="ddtt-download-form" method="post">
                    <?php wp_nonce_field( 'ddtt_dashboard_nonce_action', 'ddtt_dashboard_nonce_field' ); ?>
                    <button class="ddtt-button" type="submit" name="ddtt-download-important-files" title="Zip file with: wp-config.php, .htaccess, functions.php">
                        <span class="dashicons dashicons-media-archive"></span>
                        <span class="button-label"><?php esc_html_e( 'Download Important Files', 'dev-debug-tools' ); ?></span>
                    </button>
                    <button class="ddtt-button" type="submit" name="ddtt-download-status-report" title="Text file with system status report to share when seeking support" style="margin-top: 10px;">
                        <span class="dashicons dashicons-media-text"></span>
                        <span class="button-label"><?php esc_html_e( 'Download System Status Report', 'dev-debug-tools' ); ?></span>
                    </button>
                </form>
            </div>
        <?php endif; ?>
        <div id="ddtt-debug-info" class="<?php echo esc_html( $wp_debug ? 'enabled' : 'disabled' ); ?>">
            <span class="ddtt-debug-info-label"><?php esc_html_e( 'WP_DEBUG:', 'dev-debug-tools' ); ?></span>
            <span class="ddtt-debug-info-value"><?php echo esc_html( $wp_debug ? 'Enabled' : 'Disabled' ); ?></span>
        </div>
    </div>
</div>

<section id="ddtt-block-section">
    <div class="ddtt-dashboard-block">
        <h3><?php esc_html_e( 'Versions', 'dev-debug-tools' ); ?></h3>
        <ul>
            <li>
                <strong><?php esc_html_e( 'Plugin:', 'dev-debug-tools' ); ?></strong>
                <span><?php echo esc_html( $plugin_version ); ?>
                <?php echo wp_kses_post( $plugin_warning ); ?></span>
            </li>
            <li>
                <strong><?php esc_html_e( 'WordPress:', 'dev-debug-tools' ); ?></strong>
                <span><?php echo esc_html( $wp_version ); ?>
                <?php echo wp_kses_post( $wp_warning ); ?></span>
            </li>
            <li>
                <strong><?php esc_html_e( 'PHP:', 'dev-debug-tools' ); ?></strong>
                <span><?php echo esc_html( $php_version ); ?>
                <?php echo wp_kses_post( $php_warning ); ?></span>
            </li>
            <li>
                <strong><?php esc_html_e( 'MySQL:', 'dev-debug-tools' ); ?></strong>
                <span><?php echo esc_html( $mysql_version ); ?></span>
            </li>
            <?php if ( $jquery_version ) : ?>
                <li>
                    <strong><?php esc_html_e( 'jQuery:', 'dev-debug-tools' ); ?></strong>
                    <span><?php echo esc_html( $jquery_version ); ?></span>
                </li>
            <?php endif; ?>
            <?php if ( $curl_version ) : ?>
                <li>
                    <strong><?php esc_html_e( 'cURL:', 'dev-debug-tools' ); ?></strong>
                    <span><?php echo esc_html( $curl_version ); ?></span>
                </li>
            <?php endif; ?>
            <?php if ( $gd_version ) : ?>
                <li>
                    <strong><?php esc_html_e( 'GD Library:', 'dev-debug-tools' ); ?></strong>
                    <span><?php echo esc_html( $gd_version ); ?></span>
                </li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="ddtt-dashboard-block">
        <h3><?php esc_html_e( 'Server Metrics', 'dev-debug-tools' ); ?></h3>
        <ul>
            <li>
                <strong><?php esc_html_e( 'IP Address:', 'dev-debug-tools' ); ?></strong>
                <span class="ddtt-maybe-redact"><?php echo esc_html( $server_ip_address ); ?></span>
            </li>
            <li>
                <strong><?php esc_html_e( 'Software:', 'dev-debug-tools' ); ?></strong>
                <span><?php echo esc_html( $server_software ); ?></span>
            </li>
            <?php if ( is_array( $metrics ) ) : ?>
                <?php if ( $metrics[ 'load' ] !== 'N/A' && $metrics[ 'num_processors' ] ) : ?>
                    <li>
                        <strong><?php esc_html_e( 'CPU Load:', 'dev-debug-tools' ); ?></strong>
                        <span><?php echo esc_html( round( $metrics[ 'load' ], 2 ) ); ?> 
                        (<?php echo esc_html( $metrics[ 'num_processors' ] ); ?> <?php echo esc_html__( 'cores', 'dev-debug-tools' ); ?>, <?php echo esc_html( $metrics[ 'load_percentage' ] ); ?>)</span>
                    </li>
                    <li>
                        <strong><?php esc_html_e( 'CPU Load Status:', 'dev-debug-tools' ); ?></strong>
                        <span><?php echo esc_html( ucwords( $metrics[ 'load_class' ] ) ); ?></span>
                    </li>
                <?php endif; ?>

                <?php if ( $metrics[ 'memory_usage_percentage' ] !== 'N/A' ) : ?>
                    <li>
                        <strong><?php esc_html_e( 'Memory Usage:', 'dev-debug-tools' ); ?></strong>
                        <span><?php echo esc_html( $metrics[ 'memory_usage_percentage' ] ); ?>%</span>
                    </li>
                    <li>
                        <strong><?php esc_html_e( 'Memory Usage Status:', 'dev-debug-tools' ); ?></strong>
                        <span><?php echo esc_html( ucwords( $metrics[ 'memory_class' ] ) ); ?></span>
                    </li>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ( $uptime ) : ?>
                <?php if ( isset( $uptime[ 'readable' ] ) ) : ?>
                    <li>
                        <strong><?php esc_html_e( 'Uptime:', 'dev-debug-tools' ); ?></strong>
                        <span><?php echo esc_html( $uptime[ 'readable' ] ); ?></span>
                    </li>
                <?php elseif ( isset( $uptime[ 'raw' ] ) ) : ?>
                    <li>
                        <strong><?php esc_html_e( 'Uptime:', 'dev-debug-tools' ); ?></strong>
                        <span><?php echo esc_html( $uptime[ 'raw' ] ); ?></span>
                    </li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>
    </div>

    <div class="ddtt-dashboard-block">
        <h3><?php esc_html_e( 'Environment', 'dev-debug-tools' ); ?></h3>
        <ul>
            <li>
                <strong><?php esc_html_e( 'Multisite:', 'dev-debug-tools' ); ?></strong>
                <span><?php echo $env[ 'is_multisite' ] ? esc_html__( 'Yes', 'dev-debug-tools' ) : esc_html__( 'No', 'dev-debug-tools' ); ?></span>
            </li>
            <?php if ( $env[ 'is_multisite' ] ) : ?>
                <li>
                    <strong><?php esc_html_e( 'Blog ID:', 'dev-debug-tools' ); ?></strong>
                    <span><?php echo esc_html( $env[ 'blog_id' ] ); ?></span>
                </li>
            <?php endif; ?>
            <li>
                <strong><?php esc_html_e( 'Active Theme:', 'dev-debug-tools' ); ?></strong>
                <span><?php echo esc_html( $env[ 'theme' ] ) . ' ' . esc_html( $env[ 'theme_version' ] ); ?></span>
            </li>
            <li>
                <strong><?php esc_html_e( 'Active Plugins:', 'dev-debug-tools' ); ?></strong>
                <span><?php echo esc_html( $env[ 'active_plugins' ] ); ?></span>
            </li>
            <li>
                <strong><?php esc_html_e( 'Must Use Plugins:', 'dev-debug-tools' ); ?></strong>
                <span><?php echo esc_html( $env[ 'mu_plugins' ] ); ?></span>
            </li>
            <li>
                <strong><?php esc_html_e( 'Inactive Plugins:', 'dev-debug-tools' ); ?></strong>
                <span><?php echo esc_html( $env[ 'inactive_plugins' ] ); ?></span>
            </li>
            <li>
                <strong><?php esc_html_e( 'Timezone (WordPress):', 'dev-debug-tools' ); ?></strong>
                <span><?php echo esc_html( $wp_timezone ); ?></span>
            </li>
            <li>
                <strong><?php esc_html_e( 'OPcache:', 'dev-debug-tools' ); ?></strong>
                <span><?php echo esc_html( $opcache_status ); ?></span>
            </li>
        </ul>
    </div>
</section>

<?php if ( $is_dev ) : ?>
    <section id="ddtt-issues-section" class="ddtt-section-content">
        <h3 class="ddtt-issues-title"><?php esc_html_e( 'Check for Site Issues', 'dev-debug-tools' ); ?></h3>
        <button id="ddtt-check-issues-button" class="ddtt-button">
            <span class="dashicons dashicons-update"></span>
            <span class="button-label"><?php esc_html_e( 'Check Now', 'dev-debug-tools' ); ?></span>
        </button>
        <table class="ddtt-table ddtt-issues-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Issue to Check', 'dev-debug-tools' ); ?></th>
                    <th style="width: 200px;"><?php esc_html_e( 'Result', 'dev-debug-tools' ); ?></th>
                    <th style="width: 300px; text-align: right;"><?php esc_html_e( 'Actions', 'dev-debug-tools' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $issues as $key => $issue ) : ?>
                    <tr data-issue-key="<?php echo esc_attr( $key ); ?>" data-issue-severity="<?php echo esc_attr( $issue[ 'severity' ] ); ?>">
                        <td>
                            <button class="accordion-header" aria-expanded="false" aria-controls="<?php echo esc_attr( $key ); ?>" id="header-<?php echo esc_attr( $key ); ?>">
                                <span class="dashicons dashicons-arrow-right-alt accordion-icon" aria-hidden="true"></span>
                                <strong><?php echo esc_html( $issue[ 'label' ] ); ?></strong>
                            </button>
                            <div class="accordion-body" id="<?php echo esc_attr( $key ); ?>" role="region" aria-labelledby="header-<?php echo esc_attr( $key ); ?>" hidden>
                                <?php echo wp_kses_post( $issue[ 'details' ] ); ?>
                            </div>
                        </td>
                        <td class="ddtt-issue-result"></td>
                        <td style="text-align: right;"></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <?php
    // apply_filters( 'ddtt_log_error', 'test_1', new \Exception( 'This is just a test.' ), [ 'step' => 'testing' ] );
    // delete_option( 'ddtt_last_error' );

    $last_error = get_option( 'ddtt_last_error', [] );

    $last_error = wp_parse_args(
        $last_error,
        [
            'context' => '—',
            'message' => '—',
            'file'    => '—',
            'line'    => '—',
            'version' => '—',
            'time'    => 0,
            'user'    => [
                'id'       => 0,
                'roles'    => [],
            ],
            'extra'   => [],
        ]
    );

    $formatted_date = $last_error[ 'time' ] 
        ? Helpers::convert_timezone( $last_error[ 'time' ] )
        : '—';

    if ( isset( $last_error[ 'user' ][ 'id' ] ) && $last_error[ 'user' ][ 'id' ] > 0 ) {
        $userdata = get_userdata( $last_error[ 'user' ][ 'id' ] );
        if ( $userdata ) {
            $user_display = $userdata->display_name . ' (ID: ' . $last_error[ 'user' ][ 'id' ] . ')';
        } else {
            $user_display = $last_error[ 'user' ][ 'username' ] . ' (ID: ' . $last_error[ 'user' ][ 'id' ] . ')';
        }
    } else {
        $user_display = '—';
    }

    $user_roles = ! empty( $last_error[ 'user' ][ 'roles' ] )
        ? implode( ', ', $last_error[ 'user' ][ 'roles' ] )
        : '—';

    if ( isset( $last_error[ 'extra' ] ) && ! empty( $last_error[ 'extra' ] ) ) {
        $extra = Helpers::print_stored_value_to_table( $last_error[ 'extra' ], true );
        $extra = Helpers::truncate_string( $extra, true, 500, '…' );
    } else {
        $extra = '—';
    }
    ?>
    <section id="ddtt-last-error-section" class="ddtt-section-content">
        <h3 class="ddtt-last-error-title"><?php esc_html_e( 'Last Plugin Error', 'dev-debug-tools' ); ?></h3>
        <p><em><?php esc_html_e( 'If you are experiencing issues with this plugin, this error may provide some insight.', 'dev-debug-tools' ); ?></em></p>
        <table class="ddtt-table ddtt-last-error-table">
            <tbody>
                <tr>
                    <th><?php esc_html_e( 'Message', 'dev-debug-tools' ); ?></th>
                    <td><?php echo esc_html( $last_error[ 'message' ] ?? '—' ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Context', 'dev-debug-tools' ); ?></th>
                    <td><?php echo esc_html( $last_error[ 'context' ] ?? '—' ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'File', 'dev-debug-tools' ); ?></th>
                    <td>
                        <?php if ( $last_error[ 'file' ] && $last_error[ 'file' ] !== '—' ) : ?>
                            <?php echo esc_html( $last_error[ 'file' ] ); ?>
                            <?php if ( is_numeric( $last_error[ 'line' ] ?? false ) ) : ?>
                                <span> : <?php echo esc_html( $last_error[ 'line' ] ); ?></span>
                            <?php endif; ?>
                        <?php else : ?>
                            — 
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Extra', 'dev-debug-tools' ); ?></th>
                    <td><?php echo wp_kses_post( $extra ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Plugin Version', 'dev-debug-tools' ); ?></th>
                    <td><?php echo esc_html( $last_error[ 'version' ] ?? '—' ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Occurred At', 'dev-debug-tools' ); ?></th>
                    <td><?php echo esc_html( $formatted_date ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'User', 'dev-debug-tools' ); ?></th>
                    <td><?php echo esc_html( $user_display ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'User Roles', 'dev-debug-tools' ); ?></th>
                    <td><?php echo esc_html( $user_roles ); ?></td>
                </tr>
            </tbody>
        </table>
    </section>
<?php endif; ?>

<section id="ddtt-support-section" class="ddtt-section ddtt-support">
    <h3 class="ddtt-support-title"><?php esc_html_e( 'Need Help?', 'dev-debug-tools' ); ?></h3>
    <ul class="ddtt-support-links">
        <?php foreach ( $our_links as $link ) : ?>
            <li class="ddtt-support-item">
                <a class="ddtt-button" href="<?php echo esc_url( $link[ 'url' ] ); ?>" target="_blank" rel="noopener noreferrer" class="ddtt-support-link">
                    <?php echo esc_html( $link[ 'label' ] ); ?> <span class="dashicons dashicons-external" aria-label="External link"></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
