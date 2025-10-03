<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

$tool_settings = Discord::settings();
?>

<div id="ddtt-page-title-section">
    <div id="ddtt-page-title-left">
        <h2><?php esc_html_e( 'Discord Messenger', 'dev-debug-tools' ); ?></h2>
    </div>
</div>

<div class="ddtt-sections-with-sidebar">
    <div class="ddtt-section-content">
        <?php Settings::render_settings_section( $tool_settings, false ); ?>
    </div>

    <section id="ddtt-settings-sidebar-section" class="ddtt-section-sidebar">
        <div class="ddtt-settings-sidebar">
            <div class="ddtt-settings-sidebar-content">
                <h3><?php esc_html_e( 'Send Message', 'dev-debug-tools' ); ?></h3>
                <p><?php esc_html_e( 'Click the button below to send a test message to your webhook.', 'dev-debug-tools' ); ?></p>
                <br>
                <div class="ddtt-button-has-desc">
                    <button id="ddtt-send-discord-message" class="ddtt-button full-width" disabled><?php esc_html_e( 'Send Message', 'dev-debug-tools' ); ?></button>
                </div>
                <div id="ddtt-discord-message-response" class="ddtt-hidden"></div>
            </div>
        </div>
    </section>
</div>