<?php include 'header.php'; ?>

<?php
// Get the crons
$crons = _get_cron_array();
// dpr( $crons );
    
// Get the schedules
$schedules = wp_get_schedules();

// Let's rebuild an array with the values we need
$my_crons = [];
if ( $crons ) {

    // Iter through each timestamp
    foreach( $crons as $timestamp => $cron ) {

        // Get the array keys
        $hooks = array_keys( $cron );
        
        // Iter the hooks
        foreach ( $hooks as $hook_name ) {
            
            // Get the hook array
            $hook = $cron[ $hook_name ];

            // Get the schedule
            $the_schedule = $hook[ array_key_first( $hook ) ][ 'schedule' ];

            // Get the schedule
            if ( isset( $schedules[ $the_schedule ][ 'display' ] ) ) {
                $recurrence = $schedules[ $the_schedule ][ 'display' ];
            } elseif ( $the_schedule == '' ) {
                $recurrence = 'None';
            } else {
                $recurrence = $the_schedule;
            }

            // Get the args
            $the_args = $hook[ array_key_first( $hook ) ][ 'args' ];

            // Get the time
            $utc_time = date( 'Y-m-d H:i:s', $timestamp );
            $dt = new DateTime( $utc_time, new DateTimeZone( 'UTC' ) );
            $dt->setTimezone( new DateTimeZone( get_option( 'ddtt_dev_timezone', wp_timezone_string() ) ) );

            // Get the WordPress filters
            global $wp_filter;

            // Store the callbacks here
            $hook_callbacks = [];

            // If the filter exists
            if ( isset( $wp_filter[ $hook_name ] ) ) {

                // Get the hook object
                $hook_obj = $wp_filter[ $hook_name ];

                // Iter each hook object
                foreach ( $hook_obj as $priority => $callbacks ) {

                    // Iter each callback
                    foreach ( $callbacks as $callback ) {

                        // Check if the function is a string and has a separator
                        if ( is_string( $callback[ 'function' ] ) && ( false !== strpos( $callback[ 'function' ], '::' ) ) ) {
                            
                            // Convert to array
                            $callback[ 'function' ] = explode( '::', $callback[ 'function' ] );

                        // Or if the function is an array
                        } elseif ( is_array( $callback[ 'function' ] ) ) {

                            // If the first item is an object
                            if ( is_object( $callback[ 'function' ][0] ) ) {
                                $class = get_class( $callback[ 'function' ][0] );
                                $sep = '->';

                            // Otherwise
                            } else {
                                $class = $callback[ 'function' ][0];
                                $sep = '::';
                            }

                            // Set the name
                            $callback['name'] = $class . $sep . $callback['function'][1] . '()';

                        // Or if the function is an object
                        } elseif ( is_object( $callback[ 'function' ] ) ) {

                            // Check if the object is of this class or has this class as one of its parents
                            if ( is_a( $callback[ 'function' ], 'Closure' ) ) {
                                $callback[ 'name' ] = 'Closure';

                            // Otherwise
                            } else {
                                $class = get_class( $callback[ 'function' ] );
                                $callback[ 'name' ] = $class . '->__invoke()';
                            }

                        // Or else
                        } else {
                            $callback[ 'name' ] = $callback[ 'function' ] . '()';
                        }

                        // If the function is not callable
                        if ( !is_callable( $callback['function'] ) ) {

                            // Set an error
                            $callback[ 'error' ] = new WP_Error(
                                'not_callable',
                                sprintf( __( 'Function %s does not exist', 'dev-debug-tools' ), $callback[ 'name' ] )
                            );
                        }

                        // Add the callbacks array
                        $hook_callbacks[] = [
                            'priority' => $priority,
                            'callback' => $callback,
                        ];
                    }
                }
            }
            
            // Store the final actions here
            $actions = [];

            // Make sure we found some
            if ( !empty( $hook_callbacks ) ) {

                // Iter the callbacks
                foreach ( $hook_callbacks as $hook_callback ) {

                    // Do we have an error?
                    if ( !empty( $hook_callback[ 'callback' ][ 'error' ] ) ) {
                        $return  = '<code>' . $hook_callback[ 'callback' ][ 'name' ] . '</code>';
                        $return .= '<br><span class="status-crontrol-error"><span class="dashicons dashicons-warning" aria-hidden="true"></span> ';
                        $return .= esc_html( $hook_callback[ 'callback' ][ 'error' ]->get_error_message() );
                        $return .= '</span>';
                        $actions[] =  $return;
                    }

                    // Add the callback name
                    $actions[] = '<code>' . $hook_callback[ 'callback' ][ 'name' ] . '</code>';
                }
            }

            $my_crons[] = [
                'hook'       => $hook_name,
                'timestamp'  => $timestamp,
                'time'       => $dt->format( 'F j, Y g:i A T' ),
                'next_run'   => ddtt_convert_timestamp_to_string( $timestamp ),
                'recurrence' => $recurrence,
                'args'       => $the_args,
                'actions'    => $actions,
            ];
        }
    }
}
// dpr( $my_crons );

// Return the table
echo '<div class="full_width_container">
    <table class="admin-large-table">
        <tr>
            <th>Hook</th>
            <th>Arguments</th>
            <th>Recurrence</th>
            <th>Next Run</th>
            <th>Action</th>
        </tr>';

        // Cycle through the options
        foreach( $my_crons as $my_cron ) {

            // The args
            if ( !empty( $my_cron[ 'args' ] ) ) {
                $print = [];
                foreach ( $my_cron[ 'args' ] as $key => $a ) {
                    $print[] = '['.$key.'] => '.$a;
                }
                $args = '<code style="padding: 0;">'.implode( '<br>', $print ).'</code>';
            } else {
                $args = '';
            }

            // Print the row
            echo '<tr>
                <td>'.esc_attr( $my_cron[ 'hook' ] ).'</td>
                <td>'.wp_kses_post( $args ).'</td>
                <td>'.esc_html( ucwords( $my_cron[ 'recurrence' ] ) ).'</td>
                <td>'.esc_html( $my_cron[ 'next_run' ] ).'<br>'.esc_html( $my_cron[ 'time' ] ).'</td>
                <td>'.wp_kses_post( implode( '<br>', $my_cron[ 'actions' ] ) ).'</td>
            </tr>';
        }

echo '</table>
</div>';