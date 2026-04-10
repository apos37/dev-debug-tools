<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

$tool_settings = InactiveUsers::settings();
$total_users = count_users();
$last_state = get_option( 'ddtt_inactive_users_last_lookups', [] );
$selected_per_page = $last_state[ 'per_page' ] ?? InactiveUsers::USERS_PER_PAGE_DEFAULT;
?>

<div id="ddtt-page-title-section">
    <div id="ddtt-page-title-left">
        <h2><?php esc_html_e( 'Inactive Users', 'dev-debug-tools' ); ?></h2>
        <p><?php esc_html_e( 'Manage your site\'s health by identifying and removing stagnant accounts through a multi-step safety pipeline. First, use the "Identify Inactive Accounts" scanner to find accounts based on your threshold; once identified, you can "Queue All for Deletion" to enter them into a Grace Period. This adds a pending timestamp to their profile, acting as a safety net that allows you to review or "Cancel All Pending Deletions" before any permanent action is taken. After the defined grace period (e.g., 90 days) expires, users move to a "Ready for Deletion" status where they can be permanently purged using the "Purge Expired Users" action.', 'dev-debug-tools' ); ?></p>
        <p><strong><?php esc_html_e( 'Warning: Deleting users is a permanent action. Please ensure you have a full backup of your site and database before performing bulk deletions.', 'dev-debug-tools' ); ?></strong></p>
    </div>
</div>

<?php Settings::render_settings_section( $tool_settings ); ?>

<section id="ddtt-tool-section" class="ddtt-inactive-users ddtt-section-content">
    <div class="ddtt-file-info">
        <div class="ddtt-file-data">
            <div class="ddtt-file-data-item">
                <span class="ddtt-file-data-label"><?php esc_html_e( 'Total Users', 'dev-debug-tools' ); ?>:</span>
                <strong><span id="ddtt-total-records-count-all"><?php echo esc_html( number_format( $total_users[ 'total_users' ] ) ); ?></span></strong>
            </div>
            <span class="ddtt-separator">|</span>
            <div class="ddtt-file-data-item">
                <span class="ddtt-file-data-label"><?php esc_html_e( 'Total Inactive Users', 'dev-debug-tools' ); ?>:</span>
                <strong><span id="ddtt-total-records-count">0</span> (<span id="ddtt-inactive-users-percentage">0</span>%)</strong>
            </div>
        </div>
    </div>

    <h3><?php esc_html_e( 'Scan Results:', 'dev-debug-tools' ); ?></h3>

    <div class="ddtt-filter-section">
        <div class="ddtt-filters-left">
            <div class="ddtt-filters">
                <select id="ddtt-records-per-page">
                    <option value="10"<?php echo ( $selected_per_page == 10 ) ? ' selected' : ''; ?>><?php esc_html_e( '10 per page', 'dev-debug-tools' ); ?></option>
                    <option value="25"<?php echo ( $selected_per_page == 25 ) ? ' selected' : ''; ?>><?php esc_html_e( '25 per page', 'dev-debug-tools' ); ?></option>
                    <option value="50"<?php echo ( $selected_per_page == 50 ) ? ' selected' : ''; ?>><?php esc_html_e( '50 per page', 'dev-debug-tools' ); ?></option>
                    <option value="100"<?php echo ( $selected_per_page == 100 ) ? ' selected' : ''; ?>><?php esc_html_e( '100 per page', 'dev-debug-tools' ); ?></option>
                </select>

                <button id="ddtt-mark-selected-users-pending-btn" class="ddtt-button ddtt-button-primary" disabled><?php esc_html_e( 'Mark Selected Users as Pending', 'dev-debug-tools' ); ?></button>

                <button id="ddtt-remove-selected-users-as-pending-btn" class="ddtt-button ddtt-button-primary" disabled><?php esc_html_e( 'Remove Selected Users as Pending', 'dev-debug-tools' ); ?></button>

                <button id="ddtt-delete-selected-users-btn" class="ddtt-button ddtt-button-secondary" disabled><?php esc_html_e( 'Delete Selected Users', 'dev-debug-tools' ); ?></button>
            </div>
        </div>
    </div>

    <div id="ddtt-users-table-cont">
        <table class="ddtt-table" id="ddtt-users-table">
            <tr><th></th></tr>
            <tr><td><?php esc_html_e( 'The inactive users will be displayed here.', 'dev-debug-tools' ); ?></td></tr>
        </table>
    </div>

    <div id="ddtt-users-pagination"></div>

</section>