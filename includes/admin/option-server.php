<?php 
// Include the header
include 'header.php';

// Gather server metrics
global $wpdb;

// CPU Info
$cpu_info = [];
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
    } else {
        $memory_info = [ 'Error' => 'No permission to access <code class="hl">/proc/meminfo</code>. Contact your hosting provider or system administrator to request the necessary changes.' ];
    }
} else {
    $memory_info = [ 'Error' => 'Unable to read <code class="hl">/proc/meminfo</code>.' ];
}
?>

<div class="full_width_container">
    <h2>CPU Information (<?php echo esc_html( $num_processors ); ?> Logical Processors)</h2>
    <?php
    if ( isset( $cpu_info[ 'Error' ] ) ) {
        ?>
        <h3><?php echo wp_kses_post( $cpu_info[ 'Error' ] ); ?></h3>
        <?php
    } else {
        ?>
        <table class="admin-large-table">
            <thead>
                <tr>
                    <th width="300px">Key</th>
                    <th>Values</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $cpu_info as $key => $values ) : ?>
                    <tr>
                        <td><span class="highlight-variable"><?php echo esc_html( $key ); ?></span></td>
                        <td><?php echo esc_html( implode( ', ', $values ) ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php } ?>
</div>

<br><br><br><br>
<div class="full_width_container">
    <h2>Memory Information</h2>

    <?php
    if ( isset( $memory_info[ 'Error' ] ) ) {
        ?>
        <h3><?php echo wp_kses_post( $memory_info[ 'Error' ] ); ?></h3>
        <?php
    } else {
        ?>
        <p>Memory percentage (<?php echo esc_attr( $memory_usage_percentage ); ?>) is calculated as the ratio of used memory to total memory, multiplied by 100. Used memory is calculated by subtracting free memory, buffers, and cached memory from the total memory.</p>

        <table class="admin-large-table">
            <thead>
                <tr>
                    <th width="300px">Key</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $memory_info as $key => $value ) : ?>
                    <tr>
                        <td><span class="highlight-variable"><?php echo esc_html( $key ); ?></span></td>
                        <td><?php echo esc_html( ddtt_format_bytes( (int) $value * 1024 ) ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php } ?>
</div>
