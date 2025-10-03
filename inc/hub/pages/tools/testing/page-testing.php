<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

$shortcut = Helpers::get_os() === 'mac' ? 'Cmd + Enter' : 'Ctrl + Enter';
?>

<div id="ddtt-page-title-section">
    <div id="ddtt-page-title-left">
        <h2 id="ddtt-page-title"><?php echo esc_html__( 'Testing', 'dev-debug-tools' ); ?></h2>
    </div>
</div>

<div class="ddtt-sections-with-sidebar">

    <div id="ddtt-testing-error-section" class="ddtt-section-content">
        <?php Testing::render_output(); ?>
        <?php Testing::render_code_box(); ?>
    </div>

    <section id="ddtt-settings-sidebar-section" class="ddtt-section-sidebar">
        <div class="ddtt-settings-sidebar">
            <div class="ddtt-settings-sidebar-content">
                <h3><?php esc_html_e( 'Actions', 'dev-debug-tools' ); ?></h3>
                <div id="ddtt-testing-actions" class="ddtt-sidebar-actions">
                    <div class="ddtt-button-has-desc">
                        <button id="ddtt-run-code-test" type="submit" class="ddtt-button full-width" title="<?php echo esc_html__( 'Run a code test', 'dev-debug-tools' ); ?>"><?php esc_html_e( 'Run Code', 'dev-debug-tools' ); ?></button>
                        <p class="description"><?php echo esc_html( $shortcut ); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>