<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

$shortcode_tags = Shortcodes::get_all_shortcodes();
$grouped_shortcodes = Shortcodes::group_shortcodes_by_source( $shortcode_tags );
?>

<div id="ddtt-page-title-section">
    <div id="ddtt-page-title-left">
        <h2><?php esc_html_e( 'Shortcodes', 'dev-debug-tools' ); ?></h2>
    </div>
</div>

<section id="ddtt-settings-section" class="ddtt-section-content">
    <h3><?php echo esc_html__( 'Search for a Shortcode:', 'dev-debug-tools' ); ?></h3>

    <div class="ddtt-settings-content">
        <form id="ddtt-shortcode-form">
            <div id="search-shortcode" class="ddtt-settings-row type-select">
                <div class="ddtt-settings-label">
                    <label class="ddtt-label" for="all-shortcodes"><?php echo esc_html( __( 'Select a Shortcode', 'dev-debug-tools' ) ); ?></label>
                    <p class="ddtt-desc"><?php echo esc_html( __( 'Choose a shortcode to find what pages they are used on.', 'dev-debug-tools' ) ); ?></p>
                </div>
                <div class="ddtt-settings-field">
                    <select id="ddtt-shortcode">
                        <option value=""><?php esc_html_e( '-- Select a Shortcode --', 'dev-debug-tools' ); ?></option>
                        <?php foreach ( $shortcode_tags as $sc => $val ) : ?>
                            <option value="<?php echo esc_attr( $sc ); ?>">[<?php echo esc_html( $sc ); ?>]</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div id="search-shortcode-attr" class="ddtt-settings-row type-text">
                <div class="ddtt-settings-label">
                    <label class="ddtt-label" for="ddtt-shortcode-attr"><?php esc_html_e( 'Attributes (optional)', 'dev-debug-tools' ); ?></label>
                    <p class="ddtt-desc"><?php echo wp_kses_post( '<em>Can be in any order</em>', 'dev-debug-tools' ); ?></p>
                </div>
                <div class="ddtt-settings-field">
                    <input type="text" id="ddtt-shortcode-attr" value="" placeholder="e.g. key=&quot;value&quot; key=&quot;value&quot;" class="regular-text">
                </div>
            </div>

            <button type="submit" class="ddtt-button" disabled><?php esc_html_e( 'Search', 'dev-debug-tools' ); ?></button>
        </form>
    </div>
</section>

<section id="ddtt-search-results-section" class="ddtt-shortcodes ddtt-section-content" style="display:none;">
    <h3><?php echo esc_html__( 'Search Results for', 'dev-debug-tools' ); ?> <span id="ddtt-search-results-shortcode"></span>:</h3>

    <table id="ddtt-search-results" class="ddtt-table">
        <thead>
            <tr>
                <th><?php echo esc_html__( 'Page ID', 'dev-debug-tools' ); ?></th>
                <th><?php echo esc_html__( 'Title', 'dev-debug-tools' ); ?></th>
                <th><?php echo esc_html__( 'Post Type', 'dev-debug-tools' ); ?></th>
                <th><?php echo esc_html__( 'Post Status', 'dev-debug-tools' ); ?></th>
                <th><?php echo esc_html__( 'Count', 'dev-debug-tools' ); ?></th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</section>

<section id="ddtt-tool-section" class="ddtt-shortcodes ddtt-section-content">
    <h3><?php echo esc_html__( 'Total # of Shortcodes:', 'dev-debug-tools' ); ?> 
        <?php echo esc_html( count( $shortcode_tags ) ); ?>
    </h3>

    <?php foreach ( $grouped_shortcodes as $group => $shortcodes ) : ?>
        <h4><?php echo esc_html( $group ); ?></h4>
        <table class="ddtt-table">
            <thead>
                <tr>
                    <th><?php echo esc_html__( 'Shortcode', 'dev-debug-tools' ); ?></th>
                    <th><?php echo esc_html__( 'Source Path', 'dev-debug-tools' ); ?></th>
                    <th><?php echo esc_html__( 'Search', 'dev-debug-tools' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $shortcodes as $sc_data ) : ?>
                    <tr>
                        <td><span class="ddtt-highlight-variable">[<?php echo esc_html( $sc_data[ 'shortcode' ] ); ?>]</span></td>
                        <td>
                            <?php if ( ! empty( $sc_data['errors'] ) ) : ?>
                                <?php echo implode( '<br>', array_map( 'esc_html', $sc_data[ 'errors' ] ) ); ?>
                            <?php else : ?>
                                <?php echo esc_html( $sc_data[ 'file' ] ); ?>
                                <?php if ( $sc_data[ 'line' ] ) : ?>
                                    (<?php echo esc_html__( 'Line', 'dev-debug-tools' ); ?> <?php echo intval( $sc_data[ 'line' ] ); ?>)
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="ddtt-button ddtt-shortcode-search-btn" data-shortcode="<?php echo esc_attr( $sc_data[ 'shortcode' ] ); ?>">
                                <?php echo esc_html__( 'Search', 'dev-debug-tools' ); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endforeach; ?>
</section>
