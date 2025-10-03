<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

$single_option = false;

// Lookup a single site option
if ( isset( $_GET[ 'lookup' ] ) && isset( $_GET[ '_wpnonce' ] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET[ '_wpnonce' ] ) ), 'ddtt_site_option_lookup' ) ) {
    $lookup = sanitize_text_field( wp_unslash( $_GET[ 'lookup' ] ) );
    if ( ! empty( $lookup ) ) {
        $single_option_value = get_option( $lookup, '' );
    } else {
        wp_die( esc_html( __( 'No option specified', 'dev-debug-tools' ) ) );
    }
    
    $source_array = SiteOptions::detect_option_sources( $lookup ) ?? [];
    $single_option = [
        'option'   => $lookup,
        'value'    => $single_option_value,
        'source'   => reset( $source_array ) ?? [],
        'autoload' => SiteOptions::get_option_autoload_status( $lookup ),
    ];

// Getting all options
} else {
    // update_option( '__test_option_1', 'Test Value 1', false );
    // update_option( '__test_option_2', [ 'key1' => 'value1', 'key2' => 'value2' ] );
    // update_option( '__test_option_3', serialize( [ 'a', 'b', 'c' ] ), false );
    $all_options = SiteOptions::get_site_options();
}

$tool_settings = SiteOptions::settings();
?>

<div id="ddtt-page-title-section">
    <div id="ddtt-page-title-left">
        <h2><?php ! empty( $single_option ) ? esc_html_e( 'Site Option', 'dev-debug-tools' ) : esc_html_e( 'Site Options', 'dev-debug-tools' ); ?></h2>
    </div>
</div>

<?php Settings::render_settings_section( $tool_settings ); ?>

<?php if ( ! empty( $single_option ) ) : ?>

    <section id="ddtt-tool-section" class="ddtt-single-option ddtt-section-content">
        <h3><code style="font-size: revert;">$<?php echo esc_html( $single_option[ 'option' ] ); ?></code> — <strong><?php echo esc_html__( 'Source:', 'dev-debug-tools' ); ?></strong> <?php echo esc_html( $single_option[ 'source' ][ 'name' ] ?? 'Unknown Source' ); ?> | <strong><?php echo esc_html__( 'Autoload:', 'dev-debug-tools' ); ?></strong> <?php echo esc_html( $single_option[ 'autoload' ] ?? 'unknown' ); ?></h3>
        <?php ddtt_print_r( $single_option[ 'value' ] ); ?>
    </section>

<?php else : ?>

    <section id="ddtt-tool-section" class="ddtt-all-options ddtt-section-content">
        <h3><?php echo esc_html__( 'Total # of Options:', 'dev-debug-tools' ); ?> <?php echo esc_html( count( $all_options ) ); ?></h3>
        <p><strong><?php echo esc_html__( 'Note:', 'dev-debug-tools' ); ?></strong> <?php echo wp_kses( __( 'Some options may be labeled as <em>Unknown Source</em>. This can happen because we cannot reliably determine the source for dynamically created or runtime-generated options. Options registered or used exclusively via dynamic code, custom hooks, or without static references in plugin or theme files may not be detected by the static scanning method. Additionally, some options might be remnants from old plugins or themes no longer in use, which also results in an unknown source.', 'dev-debug-tools' ), [ 'em' => [] ] ); ?></p>

        <table class="ddtt-table">
            <thead>
                <tr>
                    <th style="width: 30px;" class="ddtt-edit-mode-only"><?php echo esc_html__( 'Delete', 'dev-debug-tools' ); ?></th>
                    <th style="width: 300px;"><?php echo esc_html__( 'Registered Setting/Option', 'dev-debug-tools' ); ?></th>
                    <th style="width: 300px;"><?php echo esc_html__( 'Option Details', 'dev-debug-tools' ); ?></th>
                    <th><?php echo esc_html__( 'Value', 'dev-debug-tools' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Allowed kses
                $allowed_html = [ 
                    'pre'  => [], 
                    'br'   => [], 
                    'code' => [], 
                    'a'    => [ 
                        'href'  => [], 
                        'class' => [] 
                    ], 
                    'span' => [ 
                        'class' => [], 
                        'style' => [] 
                    ]
                ];

                // Prefixes for known plugins
                $plugin_label = __( 'Plugin', 'dev-debug-tools' );

                $prefixes = [
                    'ddtt_'                   => $plugin_label . ': Developer Debug Tools',
                    'blnotifier_'             => $plugin_label . ': Broken Link Notifier',
                    'clear_cache_everywhere_' => $plugin_label . ': Clear Cache Everywhere',
                    'cscompanion_'            => $plugin_label . ': Cornerstone Companion',
                    'cornerstone_'            => $plugin_label . ': Cornerstone',
                    'css-organizer-'          => $plugin_label . ': CSS Organizer',
                    'erifl-'                  => $plugin_label . ': ERI File Library',
                    'gfat_'                   => $plugin_label . ': Advanced Tools for Gravity Forms',
                    'gravityformsaddon_'      => $plugin_label . ': A Gravity Forms Add-On',
                    'helpdocs_'               => $plugin_label . ': Admin Help Docs',
                    'role_visibility_'        => $plugin_label . ': Role Visibility',
                    'uamonitor_'              => $plugin_label . ': User Account Monitor',
                    'wcagaat_'                => $plugin_label . ': WCAG Admin Accessibility Tools',
                    'wp_mail_logging_'        => $plugin_label . ': WP Mail Logging',
                    'wp_mail_smtp_'           => $plugin_label . ': WP Mail SMTP',
                ];

                if ( is_plugin_active( 'gravityforms/gravityforms.php' ) ) {
                    $prefixes[ 'gf_' ]    = $plugin_label . ': Gravity Forms';
                    $prefixes[ 'gform_' ] = $plugin_label . ': Gravity Forms';
                }

                $prefixes = apply_filters( 'ddtt_site_option_prefixes', $prefixes );

                // Loop through all options
                foreach ( $all_options as $option => $data ) {
                    $value    = $data[ 'value' ];
                    $source   = $data[ 'source' ][ 'name' ] ?? 'Unknown Source';
                    $type     = $data[ 'source' ][ 'type' ] ?? 'unknown';
                    $autoload = $data[ 'autoload' ] ?? 'unknown';

                    $group = $reg_options[ $option ][ 'group' ] ?? '';

                    // Override source/type if matched by prefix
                    $found_prefix = false;
                    foreach ( $prefixes as $prefix => $prefix_source ) {
                        if ( strpos( $option, $prefix ) === 0 ) {
                            $source       = $prefix_source;
                            $type         = 'plugin';
                            $found_prefix = true;
                            break;
                        }
                    }

                    /* translators: %s: Source label HTML, Group name, Autoload status */
                    $option_details = sprintf(
                        '%1$s<br>%2$s %3$s<br>%4$s %5$s',
                        '<span class="ddtt-source-label ddtt-type-' . esc_attr( $type ) . '">' . esc_html( $source ) . '</span>',
                        esc_html__( 'Group:', 'dev-debug-tools' ),
                        esc_html( $group ?: '—' ),
                        esc_html__( 'Autoload:', 'dev-debug-tools' ),
                        esc_html( $autoload )
                    );

                    $formatted_value = Helpers::print_stored_value_to_table( $value );

                    $display_value = Helpers::truncate_string( $formatted_value, true );

                    ?>
                    <tr id="<?php echo esc_html( $option ); ?>" class="ddtt-source-type ddtt-type-<?php echo esc_attr( $type ); ?>" data-source="<?php echo esc_attr( $source ); ?>" data-group="<?php echo esc_attr( $group ); ?>">
                        <?php
                        $is_ddtt   = ( $option === 'ddtt_deleted_site_options' );
                        $is_core   = ( $source === 'Core (WordPress)' );
                        $is_disabled = ( $is_ddtt || $is_core );
                        $disabled_reason = $is_ddtt ? __( 'This option belongs to Developer Debug Tools and cannot be deleted.', 'dev-debug-tools' )
                                        : ( $is_core ? __( 'This is a WordPress core option and cannot be deleted.', 'dev-debug-tools' ) : '' );
                        ?>
                        <td class="ddtt-edit-mode-only">
                            <input type="checkbox"
                                name="ddtt_bulk_delete[]"
                                value="<?php echo esc_attr( $option ); ?>"
                                <?php disabled( $is_disabled ); ?>
                                <?php echo $is_disabled ? 'title="' . esc_attr( $disabled_reason ) . '"' : ''; ?>>
                        </td>
                        <td><span class="ddtt-highlight-variable"><?php echo esc_html( $option ); ?></span></td>
                        <td><?php echo wp_kses( $option_details, $allowed_html ); ?></td>
                        <td><?php echo wp_kses_post( $display_value ); ?></td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    </section>

<?php endif; ?>