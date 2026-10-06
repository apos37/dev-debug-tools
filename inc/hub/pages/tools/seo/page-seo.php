<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

$sections = Seo::sections();
$current_subsection = Seo::get_current_subsection();
$seo_customizations = Seo::get_customizations();
$sitemap_tree = $current_subsection === 'sitemap' ? Seo::get_sitemap_tree() : [];
$seo_customizations[ 'sitemap' ] = $current_subsection === 'sitemap' ? Seo::validate_sitemap_url( $seo_customizations[ 'sitemap' ] ) : $seo_customizations[ 'sitemap' ];
$llms_files = Seo::get_llms_files();
$seo_customizations[ 'llms' ] = Seo::validate_llms_url( $seo_customizations[ 'llms' ] );
$download_labels = [
    'robots'  => __( 'Download robots.txt', 'dev-debug-tools' ),
    'sitemap' => __( 'Download Sitemap', 'dev-debug-tools' ),
    'llms'    => __( 'Download File', 'dev-debug-tools' ),
];
?>

<div id="ddtt-page-title-section">
    <div id="ddtt-page-title-left">
        <h2 id="ddtt-page-title"><?php echo esc_html( $sections[ $current_subsection ] ); ?></h2>
        <br>
        <?php
        foreach ( $sections as $section_key => $section_title ) {
            $data_attr = 'data-section="' . esc_attr( $section_key ) . '"';
            $url = add_query_arg( 's', $section_key );

            if ( $section_key === $current_subsection ) {
                echo '<span ' . esc_html( $data_attr ) . ' class="ddtt-tab-link current">' . esc_html( $section_title ) . '</span>';
            } else {
                echo '<a href="' . esc_url( $url ) . '" ' . esc_html( $data_attr ) . ' class="ddtt-tab-link">' . esc_html( $section_title ) . '</a>';
            }
        }

        echo '<a href="' . esc_url( Bootstrap::page_url( 'settings&s=seo' ) ) . '" class="ddtt-tab-link">' . esc_html__( 'Settings', 'dev-debug-tools' ) . '</a>';
        ?>
        <a href="#" class="ddtt-rerender-content" title="<?php esc_attr_e( 'Fetch a fresh copy (skips the cache)', 'dev-debug-tools' ); ?>"><span class="dashicons dashicons-update"></span></a>
    </div>
</div>

<div class="ddtt-sections-with-sidebar">
    <section id="ddtt-seo-viewer-section" class="ddtt-section-content" data-subsection="<?php echo esc_attr( $current_subsection ); ?>">
        <?php Seo::render_view( $current_subsection, $seo_customizations ); ?>
    </section>

    <section id="ddtt-settings-sidebar-section" class="ddtt-section-sidebar" data-subsection="<?php echo esc_attr( $current_subsection ); ?>">
        <div class="ddtt-settings-sidebar">
            <div class="ddtt-settings-sidebar-content">
                <h3><?php esc_html_e( 'Actions', 'dev-debug-tools' ); ?></h3>

                <div id="ddtt-seo-actions" class="ddtt-sidebar-actions">
                    <form method="post">
                        <?php wp_nonce_field( 'ddtt_seo_action', 'ddtt_seo_nonce' ); ?>
                        <input type="hidden" name="ddtt_seo_action" value="download">
                        <input type="hidden" name="subsection" value="<?php echo esc_attr( $current_subsection ); ?>">
                        <input type="hidden" id="ddtt-seo-download-sitemap" name="sitemap" value="<?php echo esc_url( $seo_customizations[ 'sitemap' ] ); ?>">
                        <input type="hidden" id="ddtt-seo-download-llms" name="llms" value="<?php echo esc_url( $seo_customizations[ 'llms' ] ); ?>">
                        <button id="ddtt-seo-download" type="submit" class="ddtt-button full-width"><?php echo esc_html( $download_labels[ $current_subsection ] ); ?></button>
                    </form>
                </div>

                <h3><?php esc_html_e( 'Customize', 'dev-debug-tools' ); ?></h3>

                <select id="ddtt-seo-viewer-type">
                    <option value="output" <?php selected( $seo_customizations[ 'type' ], 'output' ); ?>><?php esc_html_e( 'Output View', 'dev-debug-tools' ); ?></option>
                    <option value="code" <?php selected( $seo_customizations[ 'type' ], 'code' ); ?>><?php esc_html_e( 'Code View', 'dev-debug-tools' ); ?></option>
                </select>

                <?php if ( $current_subsection === 'llms' ) : ?>
                    <select id="ddtt-seo-llms-select">
                        <?php foreach ( $llms_files as $llms_url => $llms_label ) : ?>
                            <option value="<?php echo esc_url( $llms_url ); ?>" <?php selected( $seo_customizations[ 'llms' ], $llms_url ); ?>><?php echo esc_html( $llms_label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>

                <?php if ( $current_subsection === 'sitemap' && ! empty( $sitemap_tree ) ) : ?>
                    <select id="ddtt-seo-sitemap-select">
                        <?php foreach ( $sitemap_tree as $main_url => $main_data ) : ?>
                            <option value="<?php echo esc_url( $main_url ); ?>" <?php selected( $seo_customizations[ 'sitemap' ], $main_url ); ?>><?php echo esc_html( $main_data[ 'label' ] . ': ' . basename( (string) wp_parse_url( $main_url, PHP_URL_PATH ) ) ); ?></option>
                            <?php foreach ( $main_data[ 'children' ] as $child_url ) : ?>
                                <option value="<?php echo esc_url( $child_url ); ?>" <?php selected( $seo_customizations[ 'sitemap' ], $child_url ); ?>>&nbsp;&nbsp;&mdash; <?php echo esc_html( basename( (string) wp_parse_url( $child_url, PHP_URL_PATH ) ) ); ?></option>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>

                <?php if ( $current_subsection === 'llms' || ( $current_subsection === 'sitemap' && ! empty( $sitemap_tree ) ) ) : ?>
                    <select id="ddtt-seo-per-page">
                        <?php foreach ( [ 25, 50, 100, 250, 500 ] as $per_page_option ) : ?>
                            <?php /* translators: %d: number of entries */ ?>
                            <option value="<?php echo esc_attr( $per_page_option ); ?>" <?php selected( $seo_customizations[ 'per_page' ], $per_page_option ); ?>><?php echo esc_html( sprintf( __( '%d Per Page', 'dev-debug-tools' ), $per_page_option ) ); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <form id="ddtt-seo-search-form" method="get" action="">
                        <div id="ddtt-seo-search-bar">
                            <input type="text" id="ddtt-seo-search" value="<?php echo esc_attr( $seo_customizations[ 'search' ] ); ?>" style="width: 100%;" placeholder="<?php echo esc_attr( $current_subsection === 'llms' ? __( 'Search links...', 'dev-debug-tools' ) : __( 'Search URLs...', 'dev-debug-tools' ) ); ?>">
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>