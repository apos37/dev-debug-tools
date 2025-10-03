<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

$current_page_slug = AdminMenu::get_current_page_slug();
if ( $current_page_slug == 'dev-debug-welcome' ) {
    ?>
    <div id="ddtt-hub" class="wrap">
        <div id="ddtt-hub-content" class="<?php echo esc_attr( $current_page_slug ); ?>">
        <?php
        return;
}

$current_tool_slug = AdminMenu::current_tool_slug();
$current_tool_class = $current_tool_slug ? ' ddtt-tool-' . esc_attr( $current_tool_slug ) : '';

// What's new button
$incl_changelog = '';
$plugin_version = Bootstrap::version();
if ( $current_page_slug !== 'dev-debug-changelog' ) {
    $last_viewed_version = sanitize_text_field( get_option( 'ddtt_last_viewed_version' ) );
    if ( ! $last_viewed_version || version_compare( $plugin_version, $last_viewed_version, '>' ) ) {
        // Translators: %s is replaced with the current plugin version number.
        $incl_changelog = '<a class="see-whats-new" href="' . Bootstrap::admin_url( 'admin.php?page=dev-debug-changelog' ) . '">' . sprintf( __( 'See what\'s new in version %s! ✨', 'dev-debug-tools' ), esc_html( $plugin_version ) ) . '</a>';
    }
}
?>
<!-- Display a message if settings were saved -->
<div id="ddtt-header-messages">
    <?php if ( Helpers::is_settings_saved() ) { ?>
        <div id="ddtt-message" class="ddtt-updated">
            <p><strong><?php esc_html_e( 'Settings saved.', 'dev-debug-tools' ) ?></strong></p>
        </div>
    <?php } ?>

    <?php
    /**
     * Fires after the plugin header but before the main content area.
     * Intended for notices like settings saved, deleted, etc.
     */
    do_action( 'ddtt_header_notices' );
    ?>
</div>

<div id="ddtt-hub" class="wrap">

    <!-- Display the What's New section if applicable -->
    <?php if ( ! Helpers::is_dev() ) { ?>
        <div id="ddtt-not-a-dev-notice" class="ddtt-notice">
            <p><strong><?php esc_html_e( 'You are not listed as a developer, so some features may be limited.', 'dev-debug-tools' ); ?></strong></p>
        </div>
    <?php } ?>

    <!-- Display the What's New section if applicable -->
    <?php if ( $incl_changelog ) { ?>
        <div id="ddtt-whats-new">
            <p><strong><?php echo wp_kses_post( $incl_changelog ); ?></strong></p>
            <button id="ddtt-dismiss-whats-new" type="button" aria-label="Dismiss">&times;</button>
        </div>
    <?php } ?>

    <!-- Header Section -->
    <div id="ddtt-header">
        <div id="ddtt-title-cont">
            <div class="ddtt-mode-toggle" title="<?php echo Helpers::is_dark_mode() ? esc_attr__( 'Switch to light mode', 'dev-debug-tools' ) : esc_attr__( 'Switch to dark mode', 'dev-debug-tools' ); ?>">
                <img id="ddtt-header-logo" src="<?php echo esc_url( Bootstrap::url( 'inc/hub/img/logo.png' ) ); ?>" alt="Developer Debug Tools Logo">
            </div>

            <h1 id="ddtt-header-title"><?php echo esc_attr( Bootstrap::name() ); ?><?php echo wp_kses_post( Helpers::multisite_suffix() ); ?></h1>

            <span id="ddtt-header-version">(v <?php echo esc_html( $plugin_version ); ?>)</span>
        </div>

        <div id="ddtt-header-menu">
            <?php AdminMenu::render_tool_navigation(); ?>
        </div>
    </div>

    <hr id="ddtt-header-hr"/>

    <div id="ddtt-hub-content" class="<?php echo esc_attr( $current_page_slug . $current_tool_class ); ?>">