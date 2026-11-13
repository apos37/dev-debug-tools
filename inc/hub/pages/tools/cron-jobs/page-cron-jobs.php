<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template included inside AdminMenu::render_page(); variables are local to that method.

$crons = _get_cron_array();
$schedules = wp_get_schedules();
$my_crons = [];

if ( $crons ) {
    foreach ( $crons as $timestamp => $cron ) {
        foreach ( array_keys( $cron ) as $hook_name ) {
            $hook = $cron[ $hook_name ];
            $sig = array_key_first( $hook );
            $hook_item = $hook[ $sig ];

            $the_schedule = $hook_item[ 'schedule' ] ?? '';
            $recurrence = $the_schedule === '' ? 'None' : ( $schedules[ $the_schedule ][ 'display' ] ?? $the_schedule );

            $the_args = $hook_item[ 'args' ] ?? [];

            $run_time = CronJobs::format_time( (int) $timestamp );

            global $wp_filter;
            $hook_callbacks = [];

            if ( isset( $wp_filter[ $hook_name ] ) ) {
                foreach ( $wp_filter[ $hook_name ] as $priority => $callbacks ) {
                    foreach ( $callbacks as $callback ) {
                        if ( is_string( $callback[ 'function' ] ) && strpos( $callback[ 'function' ], '::' ) !== false ) {
                            $callback[ 'function' ] = explode( '::', $callback[ 'function' ] );
                        } elseif ( is_array( $callback[ 'function' ] ) ) {
                            $class = is_object( $callback[ 'function' ][ 0 ] ) ? get_class( $callback[ 'function' ][ 0 ] ) : $callback[ 'function' ][ 0 ];
                            $sep = is_object( $callback[ 'function' ][ 0 ] ) ? '->' : '::';
                            $callback[ 'name' ] = $class . $sep . $callback[ 'function' ][ 1 ] . '()';
                        } elseif ( is_object( $callback[ 'function' ] ) ) {
                            $callback[ 'name' ] = $callback[ 'function' ] instanceof \Closure ? 'Closure' : get_class( $callback[ 'function' ] ) . '->__invoke()';
                        } else {
                            $callback[ 'name' ] = $callback[ 'function' ] . '()';
                        }

                        if ( ! is_callable( $callback[ 'function' ] ) ) {
                            // Translators: %s is the callback function name.
                            $callback[ 'error' ] = new \WP_Error( 'not_callable', sprintf( __( 'Function %s does not exist', 'dev-debug-tools' ), $callback[ 'name' ] ) );
                        }

                        $hook_callbacks[] = [
                            'priority' => $priority,
                            'callback' => $callback,
                        ];
                    }
                }
            }

            $actions = [];
            $can_run = false;

            if ( ! empty( $hook_callbacks ) ) {
                foreach ( $hook_callbacks as $hook_callback ) {
                    if ( ! empty( $hook_callback[ 'callback' ][ 'error' ] ) ) {
                        $return  = '<code>' . $hook_callback[ 'callback' ][ 'name' ] . '</code>';
                        $return .= '<br><span class="status-crontrol-error"><span class="dashicons dashicons-warning" aria-hidden="true"></span> ';
                        $return .= esc_html( $hook_callback[ 'callback' ][ 'error' ]->get_error_message() );
                        $return .= '</span>';
                        $actions[] = $return;
                    } else {
                        $can_run = true;
                    }
                    $actions[] = $hook_callback[ 'callback' ][ 'name' ];
                }
            }

            $my_crons[] = [
                'hook'       => $hook_name,
                'sig'        => $sig,
                'timestamp'  => $timestamp,
                'time'       => $run_time[ 'time' ],
                'next_run'   => $run_time[ 'next_run' ],
                'recurrence' => $recurrence,
                'args'       => $the_args,
                'actions'    => $actions,
                'can_run'    => $can_run,
            ];
        }
    }
}
?>

<div id="ddtt-page-title-section">
    <div id="ddtt-page-title-left">
        <h2><?php esc_html_e( 'Cron Jobs', 'dev-debug-tools' ); ?></h2>
        <p>
            <strong><?php esc_html_e( 'What are cron jobs?', 'dev-debug-tools' ); ?></strong>
            <?php esc_html_e( 'Cron jobs are tasks that WordPress, plugins and themes schedule to run later or on a repeating schedule, such as checking for updates, publishing scheduled posts and sending emails. WordPress runs them with WP-Cron, which checks for due tasks when someone visits the site, so on low-traffic sites they can run late. Run Now runs an event right away in your browser, and a recurring event is then rescheduled from that moment.', 'dev-debug-tools' ); ?>
        </p>
        <p>
            <?php
            printf(
                /* translators: %s: link to the WP Crontrol plugin */
                esc_html__( 'For more control, such as adding, editing and deleting events, use the %s plugin.', 'dev-debug-tools' ),
                '<a href="https://wordpress.org/plugins/wp-crontrol/" target="_blank" rel="noopener noreferrer">WP Crontrol</a>'
            );
            ?>
        </p>
    </div>
</div>

<section id="ddtt-tool-section" class="ddtt-cron-jobs ddtt-section-content">
    <h3><?php echo esc_html__( 'Total # of Cron Jobs:', 'dev-debug-tools' ); ?> <?php echo esc_html( count( $my_crons ) ); ?></h3>

    <table class="ddtt-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Hook', 'dev-debug-tools' ); ?></th>
                <th><?php esc_html_e( 'Arguments', 'dev-debug-tools' ); ?></th>
                <th><?php esc_html_e( 'Recurrence', 'dev-debug-tools' ); ?></th>
                <th><?php esc_html_e( 'Next Run', 'dev-debug-tools' ); ?></th>
                <th><?php esc_html_e( 'Action', 'dev-debug-tools' ); ?></th>
                <th class="ddtt-cron-run-col"><?php esc_html_e( 'Run', 'dev-debug-tools' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $my_crons as $my_cron ) : 
                $actions = Helpers::truncate_string( implode( '<br>', $my_cron[ 'actions' ] ) );
                ?>
                <tr data-hook="<?php echo esc_attr( $my_cron[ 'hook' ] ); ?>" data-timestamp="<?php echo esc_attr( $my_cron[ 'timestamp' ] ); ?>" data-sig="<?php echo esc_attr( $my_cron[ 'sig' ] ); ?>">
                    <td><span class="ddtt-highlight-variable"><?php echo esc_html( $my_cron[ 'hook' ] ); ?></span></td>
                    <td>
                        <?php
                        if ( ! empty( $my_cron[ 'args' ] ) ) {
                            $print = [];
                            foreach ( $my_cron[ 'args' ] as $key => $a ) {
                                $print[] = '[' . $key . '] => ' . $a;
                            }
                            echo '<code style="padding: 0;">' . wp_kses( implode( '<br>', $print ), [ 'br' => [] ] ) . '</code>';
                        }
                        ?>
                    </td>
                    <td><?php echo esc_html( ucwords( $my_cron[ 'recurrence' ] ) ); ?></td>
                    <td><span class="ddtt-cron-next-run"><?php echo esc_html( $my_cron[ 'next_run' ] ); ?></span><br><span class="ddtt-cron-next-time"><?php echo esc_html( $my_cron[ 'time' ] ); ?></span></td>
                    <td><div class="ddtt-cron-job-action"><?php echo wp_kses_post( $actions ); ?></div></td>
                    <td class="ddtt-cron-run-col">
                        <button type="button" class="ddtt-button ddtt-cron-run"<?php disabled( ! $my_cron[ 'can_run' ] ); ?> title="<?php echo esc_attr( $my_cron[ 'can_run' ] ? __( 'Run this event now. Recurring events are rescheduled from now.', 'dev-debug-tools' ) : __( 'Nothing is hooked to this event, so there is nothing to run.', 'dev-debug-tools' ) ); ?>"><?php esc_html_e( 'Run Now', 'dev-debug-tools' ); ?></button>
                        <span class="ddtt-cron-run-status" aria-live="polite"></span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
