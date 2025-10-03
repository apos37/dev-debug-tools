<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

// Get all registered taxonomies, including private ones
$taxonomies = get_taxonomies( [], 'objects' );
$taxonomies_dropdown = $taxonomies;

// Sort the taxonomies alphabetically by their labels
usort( $taxonomies_dropdown, function( $a, $b ) {
    return strcmp( $a->labels->name, $b->labels->name );
} );

// Last selected taxonomy
$selected_taxonomy = get_option( 'ddtt_last_selected_taxonomy', '' );
?>

<div id="ddtt-page-title-section">
    <div id="ddtt-page-title-left">
        <h2><?php esc_html_e( 'Taxonomies', 'dev-debug-tools' ); ?></h2>
    </div>
</div>

<section id="ddtt-settings-section" class="ddtt-section-content">
    <div class="ddtt-settings-content">
        <div id="post-type" class="ddtt-settings-row type-select">
            <div class="ddtt-settings-label">
                <label class="ddtt-label" for="post-type"><?php echo esc_html( __( 'Select a Taxonomy', 'dev-debug-tools' ) ); ?></label>
                <p class="ddtt-desc"><?php echo esc_html( __( 'Choose a taxonomy to view its settings, labels, and associated post types.', 'dev-debug-tools' ) ); ?></p>
            </div>
            <div class="ddtt-settings-field">
                <select id="ddtt-taxonomy">
                    <option value=""><?php esc_html_e( '-- Select a Taxonomy --', 'dev-debug-tools' ); ?></option>
                    <optgroup label="<?php esc_attr_e( 'Public Taxonomies', 'dev-debug-tools' ); ?>">
                    <?php foreach ( $taxonomies_dropdown as $taxonomy ) : ?>
                        <?php if ( $taxonomy->public ) : ?>
                            <option value="<?php echo esc_attr( $taxonomy->name ); ?>"><?php echo esc_html( $taxonomy->labels->name ); ?> (<?php echo esc_html( $taxonomy->name ); ?>)</option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="<?php esc_attr_e( 'Private Taxonomies', 'dev-debug-tools' ); ?>">
                    <?php foreach ( $taxonomies_dropdown as $taxonomy ) : ?>
                        <?php if ( ! $taxonomy->public ) : ?>
                            <option value="<?php echo esc_attr( $taxonomy->name ); ?>"><?php echo esc_html( $taxonomy->labels->name ); ?> (<?php echo esc_html( $taxonomy->name ); ?>)</option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    </optgroup>
                </select>
            </div>
        </div>
    </div>
</section>

<section id="ddtt-tool-section" class="ddtt-taxonomies ddtt-section-content">
    <h3><?php esc_html_e( 'Settings', 'dev-debug-tools' ); ?></h3>

    <table class="ddtt-table" id="ddtt-taxonomy-settings-table">
        <thead id="ddtt-taxonomy-settings-thead">
            <tr>
                <th></th> <!-- Key -->
                <th></th> <!-- Value -->
            </tr>
        </thead>
        <tbody id="ddtt-taxonomy-settings-tbody"></tbody>
    </table>
</section>

<section id="ddtt-tool-section" class="ddtt-taxonomies ddtt-section-content">
    <h3><?php esc_html_e( 'Labels', 'dev-debug-tools' ); ?></h3>

    <table class="ddtt-table" id="ddtt-taxonomy-labels-table">
        <thead id="ddtt-taxonomy-labels-thead">
            <tr>
                <th></th> <!-- Key -->
                <th></th> <!-- Value -->
            </tr>
        </thead>
        <tbody id="ddtt-taxonomy-labels-tbody"></tbody>
    </table>
</section>

<section id="ddtt-tool-section" class="ddtt-taxonomies ddtt-section-content">
    <h3><?php esc_html_e( 'Associated Post Types', 'dev-debug-tools' ); ?></h3>

    <table class="ddtt-table" id="ddtt-taxonomy-post-types-table">
        <thead id="ddtt-taxonomy-post-types-thead">
            <tr>
                <th></th> <!-- Slug -->
                <th></th> <!-- Label -->
            </tr>
        </thead>
        <tbody id="ddtt-taxonomy-post-types-tbody"></tbody>
    </table>
</section>