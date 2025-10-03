<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

// Get all defined constants grouped by category
$categories = @get_defined_constants( true );
$category_keys = array_keys( $categories );
sort( $category_keys, SORT_NATURAL | SORT_FLAG_CASE );

// Last selected constant
$selected_defined_constant = get_option( 'ddtt_last_defined_constant', '' );
?>

<div id="ddtt-page-title-section">
    <div id="ddtt-page-title-left">
        <h2><?php esc_html_e( 'Defined Constants', 'dev-debug-tools' ); ?></h2>
    </div>
</div>

<section id="ddtt-tool-section" class="ddtt-defined-constants ddtt-section-content">
    <h3><?php esc_html_e( 'Select a Defined Constant Category:', 'dev-debug-tools' ); ?></h3>

    <div class="ddtt-filter-section">
        <div class="ddtt-filters">
            <select id="ddtt-constant-category">
                <option value=""><?php esc_html_e( '-- Select a Category --', 'dev-debug-tools' ); ?></option>
                <?php foreach ( $category_keys as $category ) : ?>
                    <option value="<?php echo esc_attr( $category ); ?>"><?php echo esc_html( $category ); ?></option>
                <?php endforeach; ?>
            </select>

            <select id="ddtt-constant-list" style="display:none;">
                <option value=""><?php esc_html_e( '-- Select a Constant --', 'dev-debug-tools' ); ?></option>
                <!-- Options will be populated via JS -->
            </select>
        </div>
        <div class="ddtt-search-box">
            <form id="ddtt-constant-search-form">
                <input type="text" id="ddtt-constant-search" placeholder="<?php echo esc_attr__( 'Search Constants...', 'dev-debug-tools' ); ?>">
                <button type="submit" id="ddtt-constant-search-btn" class="ddtt-button"><?php esc_html_e( 'Search', 'dev-debug-tools' ); ?></button>
            </form>
        </div>
    </div>

    <table class="ddtt-table" id="ddtt-defined-constant-value-table">
        <thead id="ddtt-defined-constant-value-thead">
            <tr>
                <th></th> <!-- Category -->
                <th></th> <!-- Property -->
                <th></th> <!-- Value -->
            </tr>
        </thead>
        <tbody id="ddtt-defined-constant-value-tbody">
            <tr><td colspan="2"><?php esc_html_e( 'The selected defined constant value will be displayed here.', 'dev-debug-tools' ); ?></td></tr>
        </tbody>
    </table>

</section>