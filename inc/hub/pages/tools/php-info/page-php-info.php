<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

ob_start();
phpinfo(); // phpcs:ignore
$phpinfo = ob_get_contents();
ob_end_clean();

// Strip the title, meta name, and built-in style
$phpinfo = preg_replace( '/<title>(.*)<\/title>/', '', $phpinfo );
$phpinfo = preg_replace( '/<meta name(.*)>/', '', $phpinfo );
$phpinfo = preg_replace( '/<style.*?>([^>]*)<\/style>/', '', $phpinfo );
$phpinfo = preg_replace( '/<td class="e">(.*?)<\/td>/', '<td class="e"><span class="ddtt-highlight-variable">$1</span></td>', $phpinfo );
?>

<div id="ddtt-page-title-section">
    <div id="ddtt-page-title-left">
        <h2><?php esc_html_e( 'PHP Info', 'dev-debug-tools' ); ?></h2>
    </div>
</div>

<section id="ddtt-tool-section" class="ddtt-php-info ddtt-section-content">
    <?php echo wp_kses_post( $phpinfo ); ?>
</section>