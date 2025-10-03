<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

$tools = Tools::get_tool_links();
$favorites = filter_var_array( get_option( 'ddtt_favorite_tools', [] ), FILTER_SANITIZE_SPECIAL_CHARS );
?>

<div id="ddtt-page-title-section">
    <div id="ddtt-page-title-left">
        <h2><?php esc_html_e( 'Tools', 'dev-debug-tools' ); ?></h2>
    </div>
</div>

<section id="ddtt-tools-section">
    <ul id="ddtt-tools-grid" class="ddtt-grid-sortable">
        <?php foreach ( $tools as $index => $tool ) : ?>

            <li class="ddtt-tool-item <?php echo esc_attr( $tool[ 'enabled' ] ? 'enabled' : 'disabled' ); ?>" data-slug="<?php echo esc_attr( $tool[ 'slug' ] ); ?>" data-index="<?php echo esc_attr( $index ); ?>">
                <a class="ddtt-tool-link" href="<?php echo esc_url( $tool[ 'url' ] ); ?>">
                    <?php echo esc_html( $tool[ 'title' ] ); ?>
                </a>
                <p><?php echo wp_kses_post( $tool[ 'desc' ] ); ?></p>
                <button
                    class="ddtt-favorite-tool<?php echo in_array( $tool[ 'slug' ], $favorites, true ) ? ' favorited' : ''; ?>"
                    data-slug="<?php echo esc_attr( $tool[ 'slug' ] ); ?>"
                    data-title="<?php echo esc_attr( $tool[ 'title' ] ); ?>"
                    data-favorited="<?php echo in_array( $tool[ 'slug' ], $favorites, true ) ? '1' : '0'; ?>"
                    aria-label="<?php echo esc_html__( 'Add to Favorites', 'dev-debug-tools' ); ?>"
                    title="<?php echo esc_html__( 'Add to Favorites', 'dev-debug-tools' ); ?>"
                >
                    &hearts;
                </button>

                <div class="ddtt-toggle-wrapper">
                    <input type="checkbox" id="ddtt_tool_toggle_<?php echo esc_attr( $tool[ 'slug' ] ); ?>" name="ddtt_tools_enabled[<?php echo esc_attr( $tool[ 'slug' ] ); ?>]" value="yes" <?php checked( $tool[ 'enabled' ] ?? true, true ); ?> />
                    <label for="ddtt_tool_toggle_<?php echo esc_attr( $tool[ 'slug' ] ); ?>" class="ddtt-toggle-label" aria-label="<?php esc_attr_e( 'Enable Tool', 'dev-debug-tools' ); ?>"></label>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</section>