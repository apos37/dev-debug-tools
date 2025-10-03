<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

$logs = Changelog::get_logs();
if ( is_string( $logs ) ) {
    echo wp_kses_post( $logs ); // Display error message
    return;
}
?>
<div id="ddtt-page-title-section">
    <div id="ddtt-page-title-left">
        <h2><?php esc_html_e( 'Changelog', 'dev-debug-tools' ); ?></h2>
    </div>
</div>

<section id="ddtt-log-section">
    <ul id="ddtt-log-list">
        <?php foreach ( $logs as $log ) : ?>
            <li class="ddtt-log-item">
                <div class="ddtt-changelog-header">
                    <strong><?php echo wp_kses_post( $log[ 'version' ] ); ?></strong>
                </div>
                <div class="ddtt-changelog-body">
                    <ul>
                        <?php foreach ( explode( "\n", $log[ 'content' ] ) as $line ) :
                            $line = trim( $line );
                            if ( empty( $line ) ) {
                                continue;
                            }
                        ?>
                            <li><?php echo esc_html( ltrim( $line, "-* " ) ); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</section>