<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

$crons = _get_cron_array();
$schedules = wp_get_schedules();
$my_crons = [];

if ( $crons ) {
    foreach ( $crons as $timestamp => $cron ) {
        foreach ( array_keys( $cron ) as $hook_name ) {
            $hook = $cron[ $hook_name ];
            $hook_item = $hook[ array_key_first( $hook ) ];

            $the_schedule = $hook_item[ 'schedule' ] ?? '';
            $recurrence = $the_schedule === '' ? 'None' : ( $schedules[ $the_schedule ][ 'display' ] ?? $the_schedule );

            $the_args = $hook_item[ 'args' ] ?? [];

            $dt = new \DateTime( gmdate( 'Y-m-d H:i:s', $timestamp ), new \DateTimeZone( 'UTC' ) );
            $dt->setTimezone( new \DateTimeZone( get_option( 'ddtt_dev_timezone', wp_timezone_string() ) ) );

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

            if ( ! empty( $hook_callbacks ) ) {
                foreach ( $hook_callbacks as $hook_callback ) {
                    if ( ! empty( $hook_callback[ 'callback' ][ 'error' ] ) ) {
                        $return  = '<code>' . $hook_callback[ 'callback' ][ 'name' ] . '</code>';
                        $return .= '<br><span class="status-crontrol-error"><span class="dashicons dashicons-warning" aria-hidden="true"></span> ';
                        $return .= esc_html( $hook_callback[ 'callback' ][ 'error' ]->get_error_message() );
                        $return .= '</span>';
                        $actions[] = $return;
                    }
                    $actions[] = $hook_callback[ 'callback' ][ 'name' ];
                }
            }

            $my_crons[] = [
                'hook'       => $hook_name,
                'timestamp'  => $timestamp,
                'time'       => $dt->format( 'F j, Y g:i A T' ),
                'next_run'   => Helpers::convert_timestamp_to_string( $timestamp ),
                'recurrence' => $recurrence,
                'args'       => $the_args,
                'actions'    => $actions,
            ];
        }
    }
}
?>

<div id="ddtt-page-title-section">
    <div id="ddtt-page-title-left">
        <h2><?php esc_html_e( 'WP Cron Jobs', 'dev-debug-tools' ); ?></h2>
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
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $my_crons as $my_cron ) : 
                $actions = Helpers::truncate_string( implode( '<br>', $my_cron[ 'actions' ] ) );
                ?>
                <tr>
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
                    <td><?php echo esc_html( $my_cron[ 'next_run' ] ); ?><br><?php echo esc_html( $my_cron[ 'time' ] ); ?></td>
                    <td><div class="ddtt-cron-job-action"><?php echo wp_kses_post( $actions ); ?></div></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
