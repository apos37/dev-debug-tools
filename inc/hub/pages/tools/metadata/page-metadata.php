<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

$sections = Metadata::sections();
$types = Metadata::types();
$current_subsection = Metadata::get_current_subsection();
$meta_viewer_customizations = filter_var_array( get_option( 'ddtt_metadata_viewer_customizations', [] ), FILTER_SANITIZE_SPECIAL_CHARS );
$last_lookups = get_option( 'ddtt_metadata_last_lookups', [] );
$last_lookups = is_array( $last_lookups ) ? array_map( 'absint', $last_lookups ) : [];

$id = false;

if ( isset( $_GET[ 'reset' ] ) && sanitize_key( wp_unslash( $_GET[ 'reset' ] ) ) === 'true' &&
     isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET[ '_wpnonce' ] ) ), 'ddtt_metadata_lookup' ) ) {
    delete_option( 'ddtt_metadata_last_lookups' );
    if ( $current_subsection === 'user' ) {
        $id = get_current_user_id();
    }
} else if ( isset( $_GET[ 'lookup' ] ) && isset( $_GET[ '_wpnonce' ] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET[ '_wpnonce' ] ) ), 'ddtt_metadata_lookup' ) ) {
    $lookup = absint( wp_unslash( $_GET[ 'lookup' ] ) );
    if ( ! empty( $lookup ) ) {
        $id = $lookup;
        $last_lookups[ $current_subsection ] = $id;
        update_option( 'ddtt_metadata_last_lookups', $last_lookups );
    } else {
        wp_die( esc_html( __( 'No ID specified', 'dev-debug-tools' ) ) );
    }
} elseif ( isset( $last_lookups[ $current_subsection ] ) && ! empty( $last_lookups[ $current_subsection ] ) ) {
    $id = absint( $last_lookups[ $current_subsection ] );
} elseif ( $current_subsection === 'user' ) {
    $id = get_current_user_id();
}

$metadata_settings = Metadata::settings();
?>

<div id="ddtt-page-title-section">
    <div id="ddtt-page-title-left">
        <h2 id="ddtt-page-title"><?php echo esc_html( $sections[ $current_subsection ] ); ?> <?php echo esc_html__( 'Meta', 'dev-debug-tools' ); ?></h2>
        <br>
        <?php
        foreach ( $sections as $section_key => $section_title ) {
            $section_line_count = isset( $section_data[ 'lines' ] ) ? $section_data[ 'lines' ] : 0;
            $data_attr = 'data-section="' . esc_attr( $section_key ) . '"';
            $url = remove_query_arg( [ 'lookup', '_wpnonce' ] );
            $url = add_query_arg( 's', $section_key, $url );

            if ( $section_key === $current_subsection ) {
                echo '<span ' . esc_html( $data_attr ) . ' class="ddtt-tab-link current">' . wp_kses_post( $section_title ) . ' ' . esc_html__( 'Meta', 'dev-debug-tools' ) . '</span>';
            } else {
                echo '<a href="' . esc_url( $url ) . '" ' . esc_html( $data_attr ) . ' class="ddtt-tab-link">' . wp_kses_post( $section_title ) . ' ' . esc_html__( 'Meta', 'dev-debug-tools' ) . '</a>';
            }
        }

        echo '<a href="' . esc_url( Bootstrap::page_url( 'settings&s=metadata' ) ) . '" ' . esc_html( $data_attr ) . ' class="ddtt-tab-link">' . esc_html__( 'Settings', 'dev-debug-tools' ) . '</a>';
        ?>
    </div>
</div>

<div class="ddtt-sections-with-sidebar">

    <div class="ddtt-section-content" data-subsection="<?php echo esc_attr( $current_subsection ); ?>">
        <div id="ddtt-metadata-settings-section" data-subsection="<?php echo esc_attr( $current_subsection ); ?>">
            <?php Settings::render_settings_section( $metadata_settings ); ?>
        </div>

        <div id="ddtt-metadata-viewer-section" data-subsection="<?php echo esc_attr( $current_subsection ); ?>">
            <?php 
            if ( method_exists( Metadata::class, 'render_metadata' ) ) {
                Metadata::render_metadata( $current_subsection, $id, $meta_viewer_customizations );
            } else {
                echo '<p>' . esc_html__( 'Metadata not found.', 'dev-debug-tools' ) . '</p>';
            }
            ?>
        </div>
    </div>

    <section id="ddtt-settings-sidebar-section" class="ddtt-section-sidebar" data-subsection="<?php echo esc_attr( $current_subsection ); ?>" data-object-id="<?php echo esc_attr( $id ); ?>">
        <div class="ddtt-settings-sidebar">
            <div class="ddtt-settings-sidebar-content">
                <?php if ( $current_subsection === 'user' || $current_subsection === 'post' ) : ?>
                    <h3><?php esc_html_e( 'Actions', 'dev-debug-tools' ); ?></h3>
                    <div id="ddtt-metadata-actions" class="ddtt-sidebar-actions">
                        <form method="post">
                            <?php wp_nonce_field( 'ddtt_metadata_action', 'ddtt_metadata_nonce' ); ?>
                            <input type="hidden" name="ddtt_metadata_action" value="download_meta">
                            <input type="hidden" name="subsection" value="<?php echo esc_attr( $current_subsection ); ?>">
                            <input type="hidden" name="object_id" value="<?php echo esc_attr( $id ); ?>">
                            <button id="ddtt-download-meta" type="submit" class="ddtt-button full-width" title="<?php echo esc_html__( 'You can import metadata from Settings > Metadata.', 'dev-debug-tools' ); ?>"><?php esc_html_e( 'Download', 'dev-debug-tools' ); ?> <?php echo esc_html( $sections[ $current_subsection ] ); ?></button>
                        </form>

                        <?php
                        if ( $current_subsection === 'user' ) {
                            $title_attr = __( 'This will delete all non-protected meta keys from the Custom Meta area, as well as roles and capabilities. You can define which keys are protected in Settings > Metadata.', 'dev-debug-tools' );
                        } else {
                            $title_attr = __( 'This will delete all non-protected meta keys from the Custom Meta area. You can define which keys are protected in Settings > Metadata.', 'dev-debug-tools' );
                        }
                        ?>
                        <form method="post">
                            <?php wp_nonce_field( 'ddtt_metadata_action', 'ddtt_metadata_nonce' ); ?>
                            <input type="hidden" name="ddtt_metadata_action" value="reset_meta">
                            <input type="hidden" name="subsection" value="<?php echo esc_attr( $current_subsection ); ?>">
                            <input type="hidden" name="object_id" value="<?php echo esc_attr( $id ); ?>">
                            <button id="ddtt-reset-meta" type="submit" class="ddtt-button full-width ddtt-caution" title="<?php echo esc_html( $title_attr ); ?>"><?php esc_html_e( 'Reset', 'dev-debug-tools' ); ?> <?php echo esc_html( $sections[ $current_subsection ] ); ?></button>
                        </form>
                    </div>
                <?php endif; ?>

                <h3><?php esc_html_e( 'Customize', 'dev-debug-tools' ); ?></h3>

                <div id="ddtt-metadata-types">
                    <?php foreach ( $types as $type_key => $type_data ) : ?>
                        <?php
                        $sections = $type_data[ 'sections' ];
                        if ( $sections !== 'all' && ! in_array( $current_subsection, $sections, true ) ) {
                            continue;
                        }
                        $is_checked = ! isset( $meta_viewer_customizations[ 'types' ][ $type_key ] ) 
                            || filter_var( $meta_viewer_customizations[ 'types' ][ $type_key ], FILTER_VALIDATE_BOOLEAN );
                        ?>
                        <label>
                            <input type="checkbox"
                                id="ddtt-show-<?php echo esc_attr( $type_key ); ?>"
                                name="ddtt_show_<?php echo esc_attr( $type_key ); ?>"
                                value="1"
                                <?php checked( $is_checked ); ?>>
                            <?php echo esc_html( $type_data[ 'label' ] ); ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <?php $search = isset( $meta_viewer_customizations[ 'search' ] ) ? sanitize_text_field( $meta_viewer_customizations[ 'search' ] ) : ''; ?>
                <?php $filter = isset( $meta_viewer_customizations[ 'filter' ] ) ? sanitize_text_field( $meta_viewer_customizations[ 'filter' ] ) : ''; ?>

                <form id="ddtt-metadata-search-form" method="get" action="">
                    <div id="ddtt-metadata-search-bar">
                        <input type="text" id="ddtt-metadata-search" value="<?php echo esc_attr( $search ); ?>" style="width: 100%;" placeholder="<?php echo esc_attr__( 'Search metadata...', 'dev-debug-tools' ); ?>">
                    </div>
                    <div id="ddtt-metadata-filters">
                        <textarea id="ddtt-metadata-filter" placeholder="<?php echo esc_attr__( 'Exclude keywords (separated by commas)', 'dev-debug-tools' ); ?>"><?php echo esc_textarea( $filter ); ?></textarea>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>