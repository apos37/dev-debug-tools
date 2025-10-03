<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! session_id() ) {
    session_start();
}

$sessions = isset( $_SESSION ) && is_array( $_SESSION ) ? $_SESSION : [];
ksort( $sessions );

$tool_settings = Sessions::settings();
?>

<div id="ddtt-page-title-section">
    <div id="ddtt-page-title-left">
        <h2><?php esc_html_e( 'Sessions', 'dev-debug-tools' ); ?></h2>
        <p>
            <strong><?php esc_html_e( 'What are sessions?', 'dev-debug-tools' ); ?></strong>
            <?php esc_html_e( 'Sessions store information across multiple pages for the current user. Unlike cookies, session data is stored on the server and not on the user’s computer.', 'dev-debug-tools' ); ?>
            <?php
            $session_path = session_save_path();
            if ( empty( $session_path ) ) {
                $session_path = ini_get( 'session.save_path' );
            }
            ?>
            <?php printf(
                // Translators: %s: session save path.
                esc_html__( 'On the server, PHP session data is stored in: %s.', 'dev-debug-tools' ),
                '<code>' . esc_html( $session_path ) . '</code>'
            ); ?>
        </p>
    </div>
</div>

<?php Settings::render_settings_section( $tool_settings ); ?>

<section id="ddtt-tool-section" class="ddtt-sessions ddtt-section-content">
    <h3>
        <?php echo esc_html__( 'Total # of Sessions:', 'dev-debug-tools' ); ?> 
        <span id="ddtt-total-sessions"><?php echo esc_html( count( $sessions ) ); ?></span>
    </h3>

    <table class="ddtt-table">
        <thead>
            <tr>
                <th><?php echo esc_html__( 'Session Name', 'dev-debug-tools' ); ?></th>
                <th><?php echo esc_html__( 'Value', 'dev-debug-tools' ); ?></th>
                <th style="width: 100px; text-align: right; padding-right: 2rem;"><?php echo esc_html__( 'Clear', 'dev-debug-tools' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach( $sessions as $key => $session ) { ?>
                <tr>
                    <td><span class="ddtt-highlight-variable"><?php echo esc_attr( $key ); ?></span></td>
                    <td><?php echo esc_html( $session ); ?></td>
                    <td style="text-align: right;">
                        <a class="ddtt-clear-session ddtt-button" href="#"><?php esc_html_e( 'Clear', 'dev-debug-tools' ); ?></a>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</section>
