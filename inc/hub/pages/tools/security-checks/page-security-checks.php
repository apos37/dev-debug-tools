<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template included inside AdminMenu::render_page(); variables are local to that method.

$all_checks = SecurityChecks::checks();
$checks = SecurityChecks::enabled_checks();
$results = SecurityChecks::get_results();
$categories = SecurityChecks::categories();
$can_run = Helpers::is_dev();
$disabled_count = count( $all_checks ) - count( $checks );
$settings_url = Bootstrap::page_url( 'settings&s=security_checks' );
$schedule = SecurityChecksCron::schedule();
$next_run = wp_next_scheduled( SecurityChecksCron::HOOK );
$paused = SecurityChecksCron::paused_reason();
?>

<div id="ddtt-page-title-section">
    <div id="ddtt-page-title-left">
        <h2 id="ddtt-page-title"><?php esc_html_e( 'Security Checks', 'dev-debug-tools' ); ?></h2>
        <p><?php esc_html_e( 'Read-only checks for this site. Nothing is changed. Outside checks request this site\'s own URLs the way a logged-out visitor would, and the core files check downloads checksums from api.wordpress.org.', 'dev-debug-tools' ); ?></p>
        <br>
        <a href="<?php echo esc_url( $settings_url ); ?>" class="ddtt-tab-link"><?php esc_html_e( 'Settings', 'dev-debug-tools' ); ?></a>
    </div>
</div>

<div class="ddtt-sections-with-sidebar">
    <section id="ddtt-security-section" class="ddtt-section-content">
        <div id="ddtt-security-summary" aria-live="polite"></div>

        <div id="ddtt-security-disclaimer">
            <p class="description">
                <strong><?php esc_html_e( 'This is not a guarantee of security.', 'dev-debug-tools' ); ?></strong>
                <?php esc_html_e( 'A high score does not mean this site is secure or that it will not be hacked. These checks cover a limited set of common problems and can miss things or be wrong.', 'dev-debug-tools' ); ?>
            </p>
            <details>
                <summary><?php esc_html_e( 'Read the full notice', 'dev-debug-tools' ); ?></summary>
                <p class="description"><?php esc_html_e( 'These checks are a starting point, not a security audit, and they are not a substitute for professional advice. They look for a limited set of common problems and only from the point of view of this site. Many serious problems cannot be detected this way, including stolen or weak passwords, vulnerabilities in plugins, themes or custom code that have not been published yet, problems at the server or network level, and a site that is already compromised. Results can be wrong or incomplete, including checks that pass when they should not and warnings that do not apply to your setup. The score is a rough way to track progress on this one site. It is not a rating and cannot be compared with other tools.', 'dev-debug-tools' ); ?></p>
                <p class="description"><?php esc_html_e( 'Keep WordPress, plugins and themes updated, use strong unique passwords and two-factor authentication, keep tested backups, and consider a reputable security service or firewall. For important sites, have a qualified professional review your security. This tool is provided as is, without warranty of any kind. You are responsible for the security of your site and for any changes you make to it.', 'dev-debug-tools' ); ?></p>
            </details>
        </div>

        <?php if ( empty( $checks ) ) : ?>
            <p>
                <?php
                printf(
                    /* translators: %s: link to settings */
                    esc_html__( 'All checks are turned off. Turn some on in %s.', 'dev-debug-tools' ),
                    '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'dev-debug-tools' ) . '</a>'
                );
                ?>
            </p>
        <?php endif; ?>

        <?php foreach ( $categories as $category => $category_data ) : ?>
            <?php
            $category_checks = array_filter( $checks, function( $check ) use ( $category ) {
                return $check[ 'category' ] === $category;
            } );

            if ( empty( $category_checks ) ) {
                continue;
            }
            ?>
            <div class="ddtt-security-category">
                <h3><?php echo esc_html( $category_data[ 'label' ] ); ?></h3>
                <p class="ddtt-security-category-desc"><?php echo esc_html( $category_data[ 'desc' ] ); ?></p>
                <table class="ddtt-table ddtt-security-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Status', 'dev-debug-tools' ); ?></th>
                            <th><?php esc_html_e( 'Check', 'dev-debug-tools' ); ?></th>
                            <th><?php esc_html_e( 'Result', 'dev-debug-tools' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'dev-debug-tools' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ( $category_checks as $key => $check ) {
                            SecurityChecks::render_row( $key, $check, $results[ $key ] ?? null );
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </section>

    <section id="ddtt-settings-sidebar-section" class="ddtt-section-sidebar">
        <div class="ddtt-settings-sidebar">
            <div class="ddtt-settings-sidebar-content">
                <h3><?php esc_html_e( 'Actions', 'dev-debug-tools' ); ?></h3>

                <?php if ( $can_run ) : ?>
                    <div id="ddtt-security-actions" class="ddtt-sidebar-actions">
                        <button id="ddtt-security-run-all" type="button" class="ddtt-button full-width" <?php disabled( empty( $checks ) ); ?>><?php esc_html_e( 'Run All Checks', 'dev-debug-tools' ); ?></button>
                        <button id="ddtt-security-stop" type="button" class="ddtt-button full-width" hidden><?php esc_html_e( 'Stop', 'dev-debug-tools' ); ?></button>
                        <button id="ddtt-security-clear" type="button" class="ddtt-button full-width" <?php disabled( empty( $results ) ); ?>><?php esc_html_e( 'Clear Results', 'dev-debug-tools' ); ?></button>
                        <p id="ddtt-security-progress" class="description" aria-live="polite"></p>
                    </div>
                    <p class="description">
                        <?php if ( $schedule === 'off' ) : ?>
                            <?php
                            printf(
                                /* translators: %s: link to settings */
                                esc_html__( 'Scheduled runs are off. Turn them on in %s to get alerts when a check starts failing.', 'dev-debug-tools' ),
                                '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'dev-debug-tools' ) . '</a>'
                            );
                            ?>
                        <?php elseif ( $paused !== '' ) : ?>
                            <?php
                            /* translators: %s: why scheduled runs are paused */
                            echo esc_html( sprintf( __( 'Scheduled runs are paused. %s They resume when maintenance mode is turned off.', 'dev-debug-tools' ), $paused ) );
                            ?>
                        <?php elseif ( $next_run && $next_run > time() ) : ?>
                            <?php
                            /* translators: 1: daily or weekly, 2: time until the next run */
                            echo esc_html( sprintf( __( 'Checks run %1$s on a schedule. Next run in %2$s.', 'dev-debug-tools' ), $schedule === 'daily' ? __( 'daily', 'dev-debug-tools' ) : __( 'weekly', 'dev-debug-tools' ), human_time_diff( time(), $next_run ) ) );
                            ?>
                        <?php else : ?>
                            <?php
                            /* translators: %s: daily or weekly */
                            echo esc_html( sprintf( __( 'Checks run %s on a schedule. The next run is due and starts when WP-Cron runs.', 'dev-debug-tools' ), $schedule === 'daily' ? __( 'daily', 'dev-debug-tools' ) : __( 'weekly', 'dev-debug-tools' ) ) );
                            ?>
                        <?php endif; ?>
                    </p>
                <?php else : ?>
                    <p><?php esc_html_e( 'Only developers can run security checks. Developer accounts are set in Settings > General.', 'dev-debug-tools' ); ?></p>
                <?php endif; ?>

                <h3><?php esc_html_e( 'Filter', 'dev-debug-tools' ); ?></h3>

                <select id="ddtt-security-filter">
                    <option value="all"><?php esc_html_e( 'All Checks', 'dev-debug-tools' ); ?></option>
                    <option value="issues"><?php esc_html_e( 'Needs Attention', 'dev-debug-tools' ); ?></option>
                    <option value="pass"><?php esc_html_e( 'Passed', 'dev-debug-tools' ); ?></option>
                    <option value="none"><?php esc_html_e( 'Not Run', 'dev-debug-tools' ); ?></option>
                </select>

                <?php if ( $disabled_count > 0 ) : ?>
                    <p class="description">
                        <?php
                        printf(
                            /* translators: 1: number of checks, 2: link to settings */
                            esc_html( _n( '%1$d check is turned off in %2$s.', '%1$d checks are turned off in %2$s.', $disabled_count, 'dev-debug-tools' ) ),
                            esc_html( $disabled_count ),
                            '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'dev-debug-tools' ) . '</a>'
                        );
                        ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>
