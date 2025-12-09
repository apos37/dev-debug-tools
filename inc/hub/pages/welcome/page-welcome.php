<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

// Get the settings
$settings = Welcome::settings();
?>

<div id="ddtt-page-title-section">
    <div id="ddtt-page-title-left">
        <h1>
            <?php
            printf(
                /* translators: Plugin Name */
                esc_html__( 'Welcome to %s!', 'dev-debug-tools' ),
                esc_html( Bootstrap::name() )
            );
            ?>
        </h1>
        <p class="ddtt-welcome-message">
            <?php
            printf(
                /* translators: Plugin Version */
                esc_html__( 'Welcome to the latest version of our plugin! Version %s includes new features and enhancements to improve your debugging and testing workflow. Let’s dive in!', 'dev-debug-tools' ),
                esc_html( Bootstrap::version() )
            );
            ?>
        </p>
    </div>
</div>

<h2><?php esc_html_e( 'Where to start?', 'dev-debug-tools' ); ?></h2>
<div class="ddtt-welcome-steps">
    <p>
        <span class="ddtt-welcome-step"><strong><?php esc_html_e( 'Step 1:', 'dev-debug-tools' ); ?></strong> <?php esc_html_e( 'Configure Developer Settings', 'dev-debug-tools' ); ?></span>
        <span class="ddtt-welcome-desc"><?php esc_html_e( 'Begin by adding or updating developer accounts and configuring your preferences in the options below.', 'dev-debug-tools' ); ?></span>
    </p>

    <p>
        <span class="ddtt-welcome-step"><strong><?php esc_html_e( 'Step 2:', 'dev-debug-tools' ); ?></strong> <?php esc_html_e( 'Set Up Environment', 'dev-debug-tools' ); ?></span>
        <span class="ddtt-welcome-desc"><?php esc_html_e( 'Go through the rest of the settings to configure your development environment.', 'dev-debug-tools' ); ?> <strong><?php esc_html_e( 'If you are upgrading from version 2.1.2 or earlier, please review your setup carefully as some behaviors have changed.', 'dev-debug-tools' ); ?></strong></span>
    </p>

    <p>
        <span class="ddtt-welcome-step"><strong><?php esc_html_e( 'Step 3:', 'dev-debug-tools' ); ?></strong> <?php esc_html_e( 'Explore Features', 'dev-debug-tools' ); ?></span>
        <span class="ddtt-welcome-desc"><?php esc_html_e( 'Navigate through the various tools and features available in the plugin to familiarize yourself with its capabilities.', 'dev-debug-tools' ); ?></span>
    </p>

    <p>
        <span class="ddtt-welcome-step"><strong><?php esc_html_e( 'Step 4:', 'dev-debug-tools' ); ?></strong> <?php esc_html_e( 'Seek Support', 'dev-debug-tools' ); ?></span>
        <span class="ddtt-welcome-desc"><?php esc_html_e( 'If you encounter any issues or have questions, refer to our documentation or reach out to our support team for assistance. All of our support resources are available on the dashboard.', 'dev-debug-tools' ); ?></span>
    </p>
</div>

<p class="ddtt-welcome-final-message"><?php esc_html_e( 'By following these steps, you\'ll be well on your way to effectively using our plugin to enhance your WordPress development workflow. Happy debugging!', 'dev-debug-tools' ); ?></p>

<?php Settings::render_settings_section( $settings ); ?>

<button id="ddtt-welcome-complete-button" class="ddtt-button">
    <?php esc_html_e( 'Complete Setup', 'dev-debug-tools' ); ?>
</button>