<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

$sections = Settings::sections();
$current_subsection = isset( $_GET[ 's' ] ) ? sanitize_key( wp_unslash( $_GET[ 's' ] ) ) : 'general'; // phpcs:ignore 
if ( !isset( $sections[ $current_subsection ] ) ) {
    wp_die(
        '<div class="ddtt-page-not-found-message">' . esc_html( ErrorMessages::page_not_found() ) . '</div>',
        '',
        [ 'response' => 404 ]
    );
}

$options_method = $current_subsection . '_options';
if ( method_exists( Settings::class, $options_method ) ) {
    $option_subsections = Settings::$options_method();
} else {
    $option_subsections = [];
}

$shortcut = Helpers::get_os() === 'mac' ? 'Cmd + S' : 'Ctrl + S';
?>
<div id="ddtt-page-title-section">
    <div id="ddtt-page-title-left">
        <h2 id="ddtt-page-title"><?php echo esc_html( $sections[ $current_subsection ] ); ?> <?php echo esc_html__( 'Settings', 'dev-debug-tools' ); ?></h2>
        <br>
        <?php
        foreach ( $sections as $section_key => $section_title ) {
            $data_attr = 'data-section="' . $section_key . '"';
            $url = add_query_arg( 's', $section_key );

            if ( $section_key === $current_subsection ) {
                echo '<span ' . esc_attr( $data_attr ) . ' class="ddtt-tab-link current">' . esc_html( $section_title ) . '</span>';
            } else {
                echo '<a href="' . esc_url( $url ) . '" ' . esc_attr( $data_attr ) . ' class="ddtt-tab-link">' . esc_html( $section_title ) . '</a>';
            }
        }
        ?>
    </div>
</div>

<div class="ddtt-sections-with-sidebar">
    <div class="ddtt-section-content">
        <?php Settings::render_settings_section( $option_subsections, false ); ?>
        <?php if ( $current_subsection == 'metadata' ) : Settings::render_settings_section( Settings::upload_options(), false ); endif; ?>
    </div>

    <section id="ddtt-settings-sidebar-section" class="ddtt-section-sidebar">
        <div class="ddtt-settings-sidebar">
            <div class="ddtt-settings-sidebar-content">
                <h3><?php esc_html_e( 'Save Settings', 'dev-debug-tools' ); ?></h3>
                <p><?php esc_html_e( 'Once you are satisfied with your settings, click the button below to save them.', 'dev-debug-tools' ); ?></p>
                <br>
                <div class="ddtt-button-has-desc">
                    <button id="ddtt-save-settings" class="ddtt-button full-width" data-subsection="<?php echo esc_attr( $current_subsection ); ?>" disabled><?php esc_html_e( 'Update', 'dev-debug-tools' ); ?></button>
                    <p class="description"><?php echo esc_html( $shortcut ); ?></p>
                </div>
            </div>
        </div>
    </section>
</div>