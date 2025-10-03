<?php
namespace Apos37\DevDebugTools;
use Apos37\DevDebugTools\FileEditor;

if ( ! defined( 'ABSPATH' ) ) exit;

require_once Bootstrap::path( 'inc/hub/pages/tools/' . $this_tool_slug . '/class-' . $this_tool_slug . '.php' );
$editor = FileEditor::instance( $this_abspath );
$viewer_customizations = filter_var_array( get_option( 'ddtt_' . $this_shortname . '_viewer_customizations', [] ), FILTER_SANITIZE_SPECIAL_CHARS );
$raw_contents = Helpers::get_file_contents( $this_filename );

// Colors
$colors = $editor->colors( $viewer_customizations );
$color_comments = $colors[ 'comments' ];
$color_fx_vars = $colors[ 'fx_vars' ];
$color_text_quotes = $colors[ 'text_quotes' ];
$color_syntax = $colors[ 'syntax' ];
$color_background = $colors[ 'background' ];

// Mode
$mode = Helpers::is_dark_mode() ? 'dark' : 'light';
?>

<div id="ddtt-page-title-section">
    <div id="ddtt-page-title-left">
        <h2 id="ddtt-page-title"><?php echo esc_html( $this_title ); ?></h2>
        <p>
            <?php 
            /* translators: %s: filename being edited */
            echo esc_html( sprintf(
            /* translators: %s: filename being edited */
            __( "⚠ WARNING! Editing your %s file can break your site if you make incorrect changes or introduce syntax errors. Every site is different, so not all snippets will work for everyone. Always create a backup before making any changes. This tool checks for syntax errors and requirements before saving, but you should still review your edits carefully. If you don't know what you're doing here, it's probably best to seek assistance or leave it alone.", 'dev-debug-tools' ),
            esc_html( $this_filename )
            ) );
            ?>
        </p>
    </div>
</div>

<div class="ddtt-sections-with-sidebar">

    <div id="ddtt-file-editor-viewer-section" class="ddtt-section-content">
        <?php $editor->render_file_viewer( $raw_contents, $viewer_customizations ); ?>
    </div>

    <section id="ddtt-settings-sidebar-section" class="ddtt-section-sidebar">
        <div class="ddtt-settings-sidebar">
            <div class="ddtt-settings-sidebar-content">
                <h3><?php esc_html_e( 'Actions', 'dev-debug-tools' ); ?></h3>
                <div id="ddtt-file-editor-actions">
                    <div class="ddtt-sidebar-actions downloading">
                        <form method="post">
                            <?php wp_nonce_field( 'ddtt_' . $this_shortname . '_action', 'ddtt_file_editor_nonce' ); ?>
                            <input type="hidden" name="ddtt_<?php echo esc_attr( $this_shortname ); ?>_action" value="download_file">
                            <button id="ddtt-download-file" type="submit" class="ddtt-button full-width"><?php esc_html_e( 'Download File', 'dev-debug-tools' ); ?></button>
                        </form>
                    </div>
                    <div class="ddtt-sidebar-actions view-button" style="display: none;">
                        <button id="ddtt-view-current-btn" type="submit" class="ddtt-button full-width" title="<?php echo esc_html__( 'Switch to view the Current File', 'dev-debug-tools' ); ?>"><?php esc_html_e( 'View Currently Used File', 'dev-debug-tools' ); ?></button>
                    </div>
                    <div class="ddtt-sidebar-actions switch-views half-buttons">
                        <button id="ddtt-snippet-mgr-btn" type="submit" class="ddtt-button full-width" title="<?php echo esc_html__( 'Switch to the Snippet Manager', 'dev-debug-tools' ); ?>"><?php esc_html_e( 'Snippets', 'dev-debug-tools' ); ?></button>

                        <button id="ddtt-raw-editor-btn" type="submit" class="ddtt-button full-width" title="<?php echo esc_html__( 'Switch to the Raw Editor', 'dev-debug-tools' ); ?>"><?php esc_html_e( 'Raw Editor', 'dev-debug-tools' ); ?></button>

                        <button id="ddtt-delete-backup-btn" type="submit" class="ddtt-button full-width ddtt-caution" style="display: none;"><?php esc_html_e( 'Delete Backup', 'dev-debug-tools' ); ?></button>

                        <button id="ddtt-save-as-current" type="submit" class="ddtt-button full-width ddtt-caution" style="display: none;"><?php esc_html_e( 'Save as Current', 'dev-debug-tools' ); ?></button>
                    </div>
                    <div class="ddtt-sidebar-actions editing-only" style="display:none;">
                        <button id="ddtt-cancel-edits-btn" type="submit" class="ddtt-button full-width"><?php esc_html_e( 'Cancel Edits', 'dev-debug-tools' ); ?></button>

                        <button id="ddtt-save-edits-btn" type="submit" class="ddtt-button full-width ddtt-caution""><?php esc_html_e( 'Save Edits', 'dev-debug-tools' ); ?></button>
                    </div>
                    <div class="ddtt-sidebar-actions snippets-only" style="display:none;">
                        <button id="ddtt-preview-snippets-file" type="submit" class="ddtt-button full-width"><?php esc_html_e( 'Preview File', 'dev-debug-tools' ); ?></button>
                    </div>
                </div>

                <h3 class="ddtt-sidebar-title-with-link">
                    <?php esc_html_e( 'Syntax Colors', 'dev-debug-tools' ); ?>
                    <?php
                    /* translators: %s: color mode (dark or light) */
                    $reset_colors_text = sprintf( __( 'Reset syntax colors for %s mode to defaults', 'dev-debug-tools' ), $mode );
                    $reset_colors_link_text = __( '[Reset]', 'dev-debug-tools' );
                    ?>
                    <a href="#" id="ddtt-reset-colors" class="ddtt-reset-link"
                        aria-label="<?php echo esc_attr( $reset_colors_text ); ?>"
                        title="<?php echo esc_attr( $reset_colors_text ); ?>">
                        <?php echo esc_html( $reset_colors_link_text ); ?>
                    </a>
                </h3>
                <div id="ddtt-file-editor-color-settings" class="ddtt-color-settings">
                    <label>
                        <input type="color" name="ddtt_color_comments" value="<?php echo esc_attr( $color_comments ); ?>">
                        <?php esc_html_e( 'Comments', 'dev-debug-tools' ); ?>
                    </label>
                    <label>
                        <input type="color" name="ddtt_color_fx_vars" value="<?php echo esc_attr( $color_fx_vars ); ?>">
                        <?php esc_html_e( 'FX / Variables', 'dev-debug-tools' ); ?>
                    </label>
                    <label>
                        <input type="color" name="ddtt_color_text_quotes" value="<?php echo esc_attr( $color_text_quotes ); ?>">
                        <?php esc_html_e( 'Text / Quotes', 'dev-debug-tools' ); ?>
                    </label>
                    <label>
                        <input type="color" name="ddtt_color_syntax" value="<?php echo esc_attr( $color_syntax ); ?>">
                        <?php esc_html_e( 'Keywords / Functions', 'dev-debug-tools' ); ?>
                    </label>
                    <label>
                        <input type="color" name="ddtt_color_background" value="<?php echo esc_attr( $color_background ); ?>">
                        <?php esc_html_e( 'Background', 'dev-debug-tools' ); ?>
                    </label>
                </div>

                <h3 class="ddtt-sidebar-title-with-link">
                    <?php esc_html_e( 'Backups', 'dev-debug-tools' ); ?>
                    <?php
                    $clear_backups_label = __( 'Clear all extra backups besides the most recent', 'dev-debug-tools' );
                    ?>
                    <a href="#" id="ddtt-clear-backups" class="ddtt-reset-link"
                        aria-label="<?php echo esc_attr( $clear_backups_label ); ?>"
                        title="<?php echo esc_attr( $clear_backups_label ); ?>">
                        <?php esc_html_e( '[Clear Extra]', 'dev-debug-tools' ); ?>
                    </a>
                </h3>
                <div id="ddtt-backup-select">
                    <select id="ddtt-backups">
                        <option value=""><?php esc_html_e( '-- Select a Backup to View --', 'dev-debug-tools' ); ?></option>
                        <?php 
                        $backups = $editor->get_backups();
                        foreach ( $backups as $filename => $label ) {
                            echo '<option value="' . esc_attr( $filename ) . '">' . esc_html( $label ) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
    </section>
</div>