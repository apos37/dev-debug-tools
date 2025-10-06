<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

$is_dev = Helpers::is_dev();

// Reset resources to defaults
if ( $is_dev ) {
    $reset_link = add_query_arg(
        [
            'page'     => $current_page_slug,
            'reset'    => 'true',
            '_wpnonce' => wp_create_nonce( 'ddtt_resources_reset' ),
        ],
        admin_url( 'admin.php' )
    );
    if ( ! empty( $_GET[ 'reset' ] ) && sanitize_key( wp_unslash( $_GET[ 'reset' ] ) ) === 'true' && 
        isset( $_GET[ '_wpnonce' ] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET[ '_wpnonce' ] ) ), 'ddtt_resources_reset' ) ) {
        delete_option( Resources::$option_key );
        Helpers::remove_qs_without_refresh( [ 'reset', '_wpnonce' ] );
    }
}

// Get the resources
$resources = ResourceLinks::saved();
?>

<div id="ddtt-page-title-section">
    <div id="ddtt-page-title-left">
        <h2><?php esc_html_e( 'Resources', 'dev-debug-tools' ); ?></h2>
    </div>
</div>

<section id="ddtt-links-section">
    <ul id="ddtt-resources-grid" class="ddtt-grid-sortable">
        <?php foreach ( $resources as $index => $resource ) : ?>
            <li class="ddtt-resource-item" data-index="<?php echo esc_attr( $index ); ?>">
                <a href="<?php echo esc_url( $resource[ 'url' ] ); ?>" target="_blank" rel="noopener noreferrer">
                    <?php echo esc_html( $resource[ 'title' ] ); ?>
                    <span class="ddtt-external-icon" aria-hidden="true" role="img">&#xf504;</span>
                </a>
                <p><?php echo esc_html( $resource[ 'desc' ] ); ?></p>
                <?php if ( $is_dev ) : ?>
                    <button class="ddtt-delete-resource" data-key="<?php echo esc_attr( $index ); ?>" aria-label="<?php echo esc_html__( 'Remove resource', 'dev-debug-tools' ); ?>" title="<?php echo esc_html__( 'Remove resource', 'dev-debug-tools' ); ?>">
                        &minus;
                    </button>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
        <?php if ( $is_dev ) : ?>
            <li class="ddtt-resource-item ddtt-new-resource">
                <button type="button" id="ddtt-add-resource" aria-label="<?php esc_attr_e( 'Add New Resource', 'dev-debug-tools' ); ?>" title="<?php esc_attr_e( 'Add New Resource', 'dev-debug-tools' ); ?>"></button>
            </li>
        <?php endif; ?>
    </ul>
</section>

<?php if ( $is_dev ) : ?>
    <a class="ddtt-reset-resources-link" href="<?php echo esc_url( $reset_link ); ?>"><?php echo esc_html__( '[Reset Resources to Defaults]', 'dev-debug-tools' ); ?></a>
<?php endif; ?>