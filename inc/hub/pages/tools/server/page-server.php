<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

// Gather server metrics
global $wpdb;

// CPU Info
$cpu_info = [];
$num_processors = 0;
if ( is_readable( '/proc/cpuinfo' ) ) {
    $cpuinfo = @file( '/proc/cpuinfo' );
    if ( $cpuinfo !== false ) {
        foreach ( $cpuinfo as $line ) {
            if ( trim( $line ) !== '' ) {
                list( $key, $value ) = explode( ':', $line, 2 ) + [null, null];
                $key = trim( $key );
                $value = trim( $value );
    
                if ( $key !== null && $key !== '' ) {
                    if ( !isset( $cpu_info[ $key ] ) ) {
                        $cpu_info[ $key ] = [];
                    }
                    $cpu_info[ $key ][] = $value;
                }
            }
        }

        // Count number of processors
        foreach ( $cpuinfo as $line ) {
            if ( preg_match( '/^processor\s*:/', $line ) ) {
                $num_processors++;
            }
        }
    
        // Format values for display
        foreach ( $cpu_info as $key => $values ) {
            $value_counts = array_count_values( $values );
            $cpu_info[ $key ] = [];
            foreach ( $value_counts as $value => $count ) {
                $cpu_info[ $key ][] = $count > 1 ? "$value (x$count)" : $value;
            }
        }
    } else {
        $cpu_info = [ 'Error' => 'No permission to access <code class="hl">/proc/cpuinfo</code>. Contact your hosting provider or system administrator to request the necessary changes.' ];
    }
} else {
    $cpu_info = [ 'Error' => 'Unable to read <code class="hl">/proc/cpuinfo</code>.' ];
}

// Memory Info
$memory_info = [];
$memory_usage_percentage = false;
if ( is_readable( '/proc/meminfo' ) ) {
    $meminfo = @file( '/proc/meminfo' );
    if ( $meminfo !== false ) {
        foreach ( $meminfo as $line ) {
            list( $key, $value ) = explode( ':', $line, 2 ) + [null, null];
            $key = trim( $key );
            $value = trim( $value );
            if ( $key && $value ) {
                $memory_info[ $key ] = $value;
            }
        }

        if ( isset( $memory_info[ 'MemTotal' ], $memory_info[ 'MemFree' ], $memory_info[ 'Buffers' ], $memory_info[ 'Cached' ] ) ) {
            $mem_total  = (int) filter_var( $memory_info[ 'MemTotal' ], FILTER_SANITIZE_NUMBER_INT );
            $mem_free   = (int) filter_var( $memory_info[ 'MemFree' ], FILTER_SANITIZE_NUMBER_INT );
            $buffers    = (int) filter_var( $memory_info[ 'Buffers' ], FILTER_SANITIZE_NUMBER_INT );
            $cached     = (int) filter_var( $memory_info[ 'Cached' ], FILTER_SANITIZE_NUMBER_INT );

            $mem_used   = $mem_total - ( $mem_free + $buffers + $cached );
            $memory_usage_percentage = round( ( $mem_used / $mem_total ) * 100, 2 );
        }

    } else {
        $memory_info = [ 'Error' => 'No permission to access <code class="hl">/proc/meminfo</code>. Contact your hosting provider or system administrator to request the necessary changes.' ];
    }
} else {
    $memory_info = [ 'Error' => 'Unable to read <code class="hl">/proc/meminfo</code>.' ];
}
?>

<div id="ddtt-page-title-section">
    <div id="ddtt-page-title-left">
        <h2><?php esc_html_e( 'Server Info', 'dev-debug-tools' ); ?></h2>
    </div>
</div>

<section id="ddtt-tool-section" class="ddtt-cpu-info ddtt-section-content">
    <h3>
        <?php esc_html_e( 'CPU Info', 'dev-debug-tools' ); ?>
        (<?php echo esc_html( $num_processors ); ?> <?php esc_html_e( 'Logical Processors', 'dev-debug-tools' ); ?>)
    </h3></h3>
    <?php
    if ( isset( $cpu_info[ 'Error' ] ) ) {
        ?>
        <h3><?php echo wp_kses_post( $cpu_info[ 'Error' ] ); ?></h3>
        <?php
    } else {
        ?>
        <table class="ddtt-table">
            <thead>
                <tr>
                    <th width="300px"><?php esc_html_e( 'Key', 'dev-debug-tools' ); ?></th>
                    <th><?php esc_html_e( 'Values', 'dev-debug-tools' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $cpu_info as $key => $values ) : ?>
                    <tr>
                        <td><span class="ddtt-highlight-variable"><?php echo esc_html( $key ); ?></span></td>
                        <td><?php echo esc_html( implode( ', ', $values ) ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php } ?>
</section>

<section id="ddtt-tool-section" class="ddtt-memory-usage ddtt-section-content">
    <h3><?php esc_html_e( 'Memory Info', 'dev-debug-tools' ); ?> (<?php esc_html_e( 'Usage', 'dev-debug-tools' ); ?>: <?php echo esc_attr( $memory_usage_percentage ); ?>%)</h3>
    <?php
    if ( isset( $memory_info[ 'Error' ] ) ) {
        ?>
        <h3><?php echo wp_kses_post( $memory_info[ 'Error' ] ); ?></h3>
        <?php
    } else {
        ?>
        <p>
          <?php esc_html_e( 'Memory usage percentage is calculated as the ratio of used memory to total memory, multiplied by 100. Used memory is calculated by subtracting free memory, buffers, and cached memory from the total memory.', 'dev-debug-tools' ); ?>
        </p>

        <table class="ddtt-table">
            <thead>
                <tr>
                    <th width="300px"><?php esc_html_e( 'Key', 'dev-debug-tools' ); ?></th>
                    <th><?php esc_html_e( 'Value', 'dev-debug-tools' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $memory_info as $key => $value ) : ?>
                    <tr>
                        <td><span class="ddtt-highlight-variable"><?php echo esc_html( $key ); ?></span></td>
                        <td><?php echo esc_html( Helpers::format_bytes( (int) $value * 1024 ) ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php } ?>
</section>
