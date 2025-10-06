<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

$sections = Logs::sections();
$current_subsection = Logs::get_current_subsection();
$log_viewer_customizations = filter_var_array( get_option( 'ddtt_log_viewer_customizations', [] ), FILTER_SANITIZE_SPECIAL_CHARS );
$highlight_args = $current_subsection == 'activity' ? Activity_Log::highlight_args() : Logs::highlight_args();
?>

<div id="ddtt-page-title-section">
    <div id="ddtt-page-title-left">
        <h2 id="ddtt-page-title"><?php echo esc_html( isset( $sections[ $current_subsection ][ 'label' ] ) ? $sections[ $current_subsection ][ 'label' ] : __( 'Log Viewer', 'dev-debug-tools' ) ); ?></h2>
        <br>
        <?php
        foreach ( $sections as $section_key => $section_data ) {
            if ( $section_key == 'total' ) {
                continue; // Skip the total section
            }

            $section_title = isset( $section_data[ 'label' ] ) ? $section_data[ 'label' ] : $section_data;
            $section_line_count = isset( $section_data[ 'lines' ] ) ? $section_data[ 'lines' ] : 0;
            if ( $section_line_count > 0 ) {
                $section_title .= ' <span class="ddtt-log-count-indicator">' . esc_html( $section_line_count ) . '</span>';
            }

            $data_attr = 'data-section="' . esc_attr( $section_key ) . '"';
            $url = add_query_arg( 's', $section_key );

            if ( $section_key === $current_subsection ) {
                echo '<span ' . esc_html( $data_attr ) . ' class="ddtt-tab-link current">' . wp_kses_post( $section_title ) . '</span>';
            } else {
                echo '<a href="' . esc_url( $url ) . '" ' . esc_html( $data_attr ) . ' class="ddtt-tab-link">' . wp_kses_post( $section_title ) . '</a>';
            }
        }

        echo '<a href="' . esc_url( Bootstrap::page_url( 'settings&s=logging' ) ) . '" class="ddtt-tab-link">' . esc_html__( 'Settings', 'dev-debug-tools' ) . '</a>';
        ?>
        <a href="#" class="ddtt-rerender-content" title="<?php esc_attr_e( 'Refresh Log Viewer', 'dev-debug-tools' ); ?>"><span class="dashicons dashicons-update"></span></a>
    </div>
</div>

<div class="ddtt-sections-with-sidebar">
    <section id="ddtt-log-viewer-section" class="ddtt-section-content" data-subsection="<?php echo esc_attr( $current_subsection ); ?>">
        <?php 
        if ( method_exists( Logs::class, 'render_log' ) ) {
            Logs::render_log( $current_subsection, $log_viewer_customizations );
        } else {
            echo '<p>' . esc_html__( 'Log section not found.', 'dev-debug-tools' ) . '</p>';
        }
        ?>
    </section>

    <section id="ddtt-settings-sidebar-section" class="ddtt-section-sidebar" data-subsection="<?php echo esc_attr( $current_subsection ); ?>">
        <div class="ddtt-settings-sidebar">
            <div class="ddtt-settings-sidebar-content">
                <h3><?php esc_html_e( 'Actions', 'dev-debug-tools' ); ?></h3>

                <div id="ddtt-log-actions" class="ddtt-sidebar-actions">
                    <form method="post">
                        <?php wp_nonce_field( 'ddtt_log_action', 'ddtt_log_nonce' ); ?>
                        <input type="hidden" name="ddtt_log_action" value="download_log">
                        <input type="hidden" name="subsection" value="<?php echo esc_attr( $current_subsection ); ?>">
                        <button id="ddtt-download-log" type="submit" class="ddtt-button full-width"><?php esc_html_e( 'Download Log', 'dev-debug-tools' ); ?></button>
                    </form>

                    <form method="post">
                        <?php wp_nonce_field( 'ddtt_log_action', 'ddtt_log_nonce' ); ?>
                        <input type="hidden" name="ddtt_log_action" value="clear_log">
                        <input type="hidden" name="subsection" value="<?php echo esc_attr( $current_subsection ); ?>">
                        <button id="ddtt-clear-log" type="submit" class="ddtt-button full-width ddtt-caution"><?php esc_html_e( 'Clear Log', 'dev-debug-tools' ); ?></button>
                    </form>
                </div>

                <h3><?php esc_html_e( 'Customize', 'dev-debug-tools' ); ?></h3>
                
                <select id="ddtt-log-viewer-type">
                    <?php 
                    $log_viewer_type = isset( $log_viewer_customizations[ 'type' ] ) ? sanitize_key( $log_viewer_customizations[ 'type' ] ) : 'easy';
                    $log_viewer_types = [ 
                        'easy' => __( 'Easy Reader', 'dev-debug-tools' ),
                        'raw'  => __( 'Raw View', 'dev-debug-tools' ) 
                    ];
                    foreach ( $log_viewer_types as $type_key => $type_label ) {
                        echo '<option value="' . esc_attr( $type_key ) . '" ' . selected( $log_viewer_type, $type_key, false ) . '>' . esc_html( $type_label ) . '</option>';
                    }
                    ?>
                </select>

                <select id="ddtt-log-viewer-sort">
                    <?php 
                    $log_viewer_sort = isset( $log_viewer_customizations[ 'sort' ] ) ? sanitize_key( $log_viewer_customizations[ 'sort' ] ) : 'asc';
                    $log_viewer_sorts = [ 
                        'asc'  => __( 'Most Recent on Bottom', 'dev-debug-tools' ), 
                        'desc' => __( 'Most Recent on Top', 'dev-debug-tools' ) 
                    ];
                    foreach ( $log_viewer_sorts as $sort_key => $sort_label ) {
                        echo '<option value="' . esc_attr( $sort_key ) . '" ' . selected( $log_viewer_sort, $sort_key, false ) . '>' . esc_html( $sort_label ) . '</option>';
                    }
                    ?>
                </select>

                <?php $disable_combine = $log_viewer_type === 'raw' || $current_subsection == 'activity' ? true : false; ?>
                <select id="ddtt-log-viewer-combine" <?php disabled( $disable_combine ); ?>>
                    <?php 
                    $log_viewer_combine = isset( $log_viewer_customizations[ 'combine' ] ) ? (bool) $log_viewer_customizations[ 'combine' ] : true;
                    ?>
                    <option value="1" <?php selected( $log_viewer_combine, true ); ?>><?php esc_html_e( 'Combine Log Entries', 'dev-debug-tools' ); ?></option>
                    <option value="0" <?php selected( $log_viewer_combine, false ); ?>><?php esc_html_e( 'Do Not Combine', 'dev-debug-tools' ); ?></option>
                </select>

                <select id="ddtt-log-items-per-page">
                    <?php 
                    $log_items_per_page = isset( $log_viewer_customizations[ 'per_page' ] ) ? absint( $log_viewer_customizations[ 'per_page' ] ) : 100;
                    $log_items_options = [ 1, 5, 10, 25, 50, 100, 200 ];
                    foreach ( $log_items_options as $option ) {
                        // Translators: %d: number of items.
                        $per_page_label = $option === 1 ? __( 'Most Recent Item', 'dev-debug-tools' ) : sprintf( _n( 'Last %d Item', 'Last %d Items', $option, 'dev-debug-tools' ), $option );
                        echo '<option value="' . esc_attr( $option ) . '" ' . selected( $log_items_per_page, $option, false ) . '>' . esc_html( $per_page_label ) . '</option>';
                    }
                    ?>
                </select>

                <?php $search = isset( $log_viewer_customizations[ 'search' ] ) ? sanitize_text_field( $log_viewer_customizations[ 'search' ] ) : ''; ?>
                <?php $filter = isset( $log_viewer_customizations[ 'filter' ] ) ? sanitize_text_field( $log_viewer_customizations[ 'filter' ] ) : ''; ?>

                <form id="ddtt-log-search-form" method="get" action="">
                    <div id="ddtt-log-search-bar">
                        <input type="text" id="ddtt-log-search" value="<?php echo esc_attr( $search ); ?>" style="width: 100%;" placeholder="<?php echo esc_attr__( 'Search logs...', 'dev-debug-tools' ); ?>">
                    </div>
                    <div id="ddtt-log-filters">
                        <textarea id="ddtt-log-filter" placeholder="<?php echo esc_attr__( 'Exclude keywords (separated by commas)', 'dev-debug-tools' ); ?>"><?php echo esc_textarea( $filter ); ?></textarea>
                    </div>
                </form>

                <?php if ( $log_viewer_type == 'easy' ) { ?>
                    <div id="ddtt-color-identifiers">
                        <?php
                        foreach ( $highlight_args as $hl_key => $hl ) {

                            $name = isset( $hl[ 'name' ] ) ? $hl[ 'name' ] : '';

                            // Add the link
                            if ( $current_subsection == 'activity' ) {
                                $text = '<span id="ddtt-identifier-' . esc_attr( $hl_key ) . '">' . $name . '</span>';
                            } else {
                                $text = '<a id="ddtt-identifier-' . esc_attr( $hl_key ) . '" href="#">' . $name . '</a>';
                            }

                            $priority = isset( $hl[ 'priority' ] ) && $hl[ 'priority' ] ? '!' : '';
                            $bg_color = isset( $hl[ 'bg_color' ] ) ? $hl[ 'bg_color' ] : '';
                            $font_color = isset( $hl[ 'font_color' ] ) ? $hl[ 'font_color' ] : '';

                            // Add the color
                            ?>
                            <div class="color-cont">
                                <div class="color-box <?php echo esc_attr( $hl_key ); ?>" style="background-color: <?php echo esc_attr( $bg_color ); ?>; color: <?php echo esc_attr( $font_color ); ?>;"><?php echo esc_html( $priority ); ?></div>
                                <div class="hl-name"><?php echo wp_kses( $text, [ 'a' => [ 'href' => [] ] ] ); ?></div>
                            </div>
                            <?php
                        }
                        ?> 
                    </div>
                <?php } ?>
            </div>
        </div>
    </section>
</div>