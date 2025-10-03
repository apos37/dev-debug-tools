<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

$tool_settings = Cookies::settings();

$cookies = $_COOKIE;
ksort( $cookies );
?>

<div id="ddtt-page-title-section">
    <div id="ddtt-page-title-left">
        <h2><?php esc_html_e( 'Cookies', 'dev-debug-tools' ); ?></h2>
        <p>
          <strong><?php esc_html_e( 'What are cookies?', 'dev-debug-tools' ); ?></strong>
          <?php esc_html_e( 'Cookies are small pieces of text sent to your browser by a website you visit. They help that website remember information about your visit, which can both make it easier to visit the site again and make the site more useful to you.', 'dev-debug-tools' ); ?>
          <?php esc_html_e( 'You can also see your cookies in your developer console under Application > Store > Cookies >', 'dev-debug-tools' ); ?>
          <?php echo esc_url( home_url() ); ?>.
        </p>
    </div>
</div>

<?php Settings::render_settings_section( $tool_settings ); ?>

<section id="ddtt-tool-section" class="ddtt-cookies ddtt-section-content">
    <h3><?php echo esc_html__( 'Total # of Cookies:', 'dev-debug-tools' ); ?> <span id="ddtt-total-cookies"><?php echo esc_html( count( $cookies ) ); ?></span></h3>

    <table class="ddtt-table">
        <thead>
            <tr>
                <th><?php echo esc_html__( 'Cookie Name', 'dev-debug-tools' ); ?></th>
                <th><?php echo esc_html__( 'Value', 'dev-debug-tools' ); ?></th>
                <th style="width: 100px; text-align: right; padding-right: 2rem;"><?php echo esc_html__( 'Clear', 'dev-debug-tools' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach( $cookies as $key => $cookie ) { ?>
                <tr data-key="<?php echo esc_attr( $key ); ?>">
                    <td><span class="ddtt-highlight-variable"><?php echo esc_attr( $key ); ?></span></td>
                    <td><?php echo esc_html( $cookie ); ?></td>
                    <td style="text-align: right;"><a class="ddtt-clear-cookie ddtt-button" href="#">Clear</a></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</section>