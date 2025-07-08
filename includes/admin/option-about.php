<style>
ul {
    list-style: square;
    padding: revert;
    padding-top: 10px;
    padding-bottom: 5px;
}
ul li {
    padding-inline-start: 1ch;
}
#feedback-message {
    margin-bottom: 10px;
}
#feedback-message::placeholder {
    color: #ccc;
}
#feedback-sending {
    line-height: 2.25;
    font-style: italic;
    margin-left: 10px;
    display: none;
}
#feedback-sending:after {
    display: inline-block;
    animation: dotty steps(1,end) 1s infinite;
    content: '';
}
@keyframes dotty {
    0%   { content: ''; }
    25%  { content: '.'; }
    50%  { content: '..'; }
    75%  { content: '...'; }
    100% { content: ''; }
}
#feedback-result {
    color: white;
    font-weight: 500;
    width: fit-content;
    border-radius: 4px;
    padding: 6px 10px;
}
#feedback-result.success {
    background-color: green;
    display: inline-block;
    margin-left: 10px;
}
#feedback-result.fail {
    background-color: red;
    margin-top: 10px;
}
.plugin-card, .plugin-card-bottom {
    background-color: #2D2D2D;
    color: #F6F7F7 !important;
}
.plugin-card .desc p {
    color: #F6F7F7 !important;
}
body #wpbody-content .plugin-card .plugin-action-buttons a.button.install-now[aria-disabled="true"] {
    color: #2D2D2D !important;
}
#the-list {
    display: flex;
    flex-flow: wrap;
}
.plugin-card {
    display: flex;
    flex-direction: column;
    margin-left: 0 !important;
}
.plugin-card .plugin-card-top {
    flex: 1;
}
.plugin-card .plugin-card-bottom {
    margin-top: auto;
}
.plugin-card .ws_stars {
    display: inline-block;
}
.php-incompatible {
    padding: 12px 20px;
    background-color: #D1231B;
    color: #FFFFFF;
    border-top: 1px solid #dcdcde;
    overflow: hidden;
}
#wpbody-content .plugin-card .plugin-action-buttons a.install-now[aria-disabled="true"] {
    /* color: #CBB8AD !important; */
    border-color: #CBB8AD !important;
}
.plugin-action-buttons {
    list-style: none !important;   
}
</style>

<?php include 'header.php'; ?>

<br><br>
<h3>Plugin Support</h3>
<?php
echo '<a class="button button-primary" href="'.esc_url( DDTT_GUIDE_URL ).'" target="_blank">'.esc_html( __( 'How-To Guide', 'dev-debug-tools' ) ).' »</a><br><br>';
echo '<a class="button button-primary" href="'.esc_url( DDTT_DOCS_URL ).'" target="_blank">'.esc_html( __( 'Developer Docs', 'dev-debug-tools' ) ).' »</a><br><br>';
echo '<a class="button button-primary" href="'.esc_url( DDTT_SUPPORT_URL ).'" target="_blank">'.esc_html( __( 'Website Support Forum', 'dev-debug-tools' ) ).' »</a><br><br>';
echo '<a class="button button-primary" href="'.esc_url( DDTT_DISCORD_URL ).'" target="_blank">'.esc_html( __( 'Discord Support Server', 'dev-debug-tools' ) ).' »</a><br><br>'; 
echo '<a class="button button-primary" href="https://wordpress.org/support/plugin/dev-debug-tools/" target="_blank">'.esc_html( __( 'WordPress.org Plugin Support Page', 'dev-debug-tools' ) ).' »</a><br>';
?>

<br><br><br>
<h3>Like This Plugin?</h3>
<p>Please rate and review this plugin if you find it helpful. If you would give it fewer than 5 stars, please let me know how I can improve it.</p>
<?php /* translators: 1: Text for the button (default: Rate and Review on WordPress.org) */
echo '<a class="button button-primary" href="https://wordpress.org/support/plugin/dev-debug-tools/reviews/" target="_blank">'.esc_html( __( 'Rate and Review on WordPress.org', 'dev-debug-tools' ) ).' »</a><br>'; ?>

<?php if ( ddtt_get_domain() != 'playground.wordpress.net' ) { ?>
    <br><br>
    <h2><?php echo esc_html__( 'Try Our Other Plugins', 'dev-debug-tools' ); ?></h2>
    <div class="wp-list-table widefat plugin-install">
        <div id="the-list">
            <?php ddtt_plugin_card( 'simple-maintenance-redirect' ); ?>
            <?php ddtt_plugin_card( 'clear-cache-everywhere' ); ?>
            <?php ddtt_plugin_card( 'broken-link-notifier' ); ?>
            <?php ddtt_plugin_card( 'eri-file-library' ); ?>
            <?php if ( is_plugin_active( 'gravityforms/gravityforms.php' ) ) { ?>
                <?php ddtt_plugin_card( 'gf-tools' ); ?>
                <?php ddtt_plugin_card( 'gf-discord' ); ?>
                <?php ddtt_plugin_card( 'gf-msteams' ); ?>
                <?php ddtt_plugin_card( 'gravity-zwr' ); ?>
            <?php } ?>
        </div>
    </div>
<?php } ?>
