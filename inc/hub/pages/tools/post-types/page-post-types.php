<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

// Get all registered post types, including private ones
$post_types = get_post_types( [], 'objects' );
$post_types_dropdown = $post_types;
global $_wp_post_type_features;

// Sort the post types alphabetically by their labels
usort( $post_types_dropdown, function( $a, $b ) {
    return strcmp( $a->labels->name, $b->labels->name );
} );

// Last selected post type
$selected_post_type = get_option( 'ddtt_last_selected_post_type', '' );
?>

<div id="ddtt-page-title-section">
    <div id="ddtt-page-title-left">
        <h2><?php esc_html_e( 'Post Types', 'dev-debug-tools' ); ?></h2>
    </div>
</div>

<section id="ddtt-settings-section" class="ddtt-section-content">
    <div class="ddtt-settings-content">
        <div id="post-type" class="ddtt-settings-row type-select">
            <div class="ddtt-settings-label">
                <label class="ddtt-label" for="post-type"><?php echo esc_html( __( 'Select a Post Type', 'dev-debug-tools' ) ); ?></label>
                <p class="ddtt-desc"><?php echo esc_html( __( 'Choose a post type to view its settings, labels, and associated taxonomies.', 'dev-debug-tools' ) ); ?></p>
            </div>
            <div class="ddtt-settings-field">
                <select id="ddtt-post-type">
                    <option value=""><?php esc_html_e( '-- Select a Post Type --', 'dev-debug-tools' ); ?></option>
                    <optgroup label="<?php esc_attr_e( 'Public Post Types', 'dev-debug-tools' ); ?>">
                    <?php foreach ( $post_types_dropdown as $post_type ) : ?>
                        <?php if ( $post_type->public ) : ?>
                            <option value="<?php echo esc_attr( $post_type->name ); ?>"><?php echo esc_html( $post_type->labels->name ); ?> (<?php echo esc_html( $post_type->name ); ?>)</option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="<?php esc_attr_e( 'Private Post Types', 'dev-debug-tools' ); ?>">
                    <?php foreach ( $post_types_dropdown as $post_type ) : ?>
                        <?php if ( ! $post_type->public ) : ?>
                            <option value="<?php echo esc_attr( $post_type->name ); ?>"><?php echo esc_html( $post_type->labels->name ); ?> (<?php echo esc_html( $post_type->name ); ?>)</option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    </optgroup>
                </select>
            </div>
        </div>
    </div>
</section>

<section id="ddtt-tool-section" class="ddtt-post-types ddtt-section-content">
    <h3><?php esc_html_e( 'Settings', 'dev-debug-tools' ); ?></h3>

    <table class="ddtt-table" id="ddtt-post-type-settings-table">
        <thead id="ddtt-post-type-settings-thead">
            <tr>
                <th></th> <!-- Key -->
                <th></th> <!-- Value -->
            </tr>
        </thead>
        <tbody id="ddtt-post-type-settings-tbody"></tbody>
    </table>
</section>

<section id="ddtt-tool-section" class="ddtt-post-types ddtt-section-content">
    <h3><?php esc_html_e( 'Labels', 'dev-debug-tools' ); ?></h3>

    <table class="ddtt-table" id="ddtt-post-type-labels-table">
        <thead id="ddtt-post-type-labels-thead">
            <tr>
                <th></th> <!-- Key -->
                <th></th> <!-- Value -->
            </tr>
        </thead>
        <tbody id="ddtt-post-type-labels-tbody"></tbody>
    </table>
</section>

<section id="ddtt-tool-section" class="ddtt-post-types ddtt-section-content">
    <h3><?php esc_html_e( 'Associated Taxonomies', 'dev-debug-tools' ); ?></h3>

    <table class="ddtt-table" id="ddtt-post-type-taxonomies-table">
        <thead id="ddtt-post-type-taxonomies-thead">
            <tr>
                <th></th> <!-- Slug -->
                <th></th> <!-- Label -->
            </tr>
        </thead>
        <tbody id="ddtt-post-type-taxonomies-tbody"></tbody>
    </table>
</section>