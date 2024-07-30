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
}
body #wpbody-content .plugin-card .plugin-action-buttons a.button.install-now[aria-disabled="true"] {
    color: #2D2D2D !important;
}
</style>

<?php include 'header.php'; ?>

<br><br>
<h3>Plugin Support</h3>
<br><img class="admin_helpbox_title" src="<?php echo esc_url( DDTT_PLUGIN_IMG_PATH ); ?>discord.png" width="auto" height="100">
<p>If you need assistance with this plugin or have suggestions for improving it, please join the Discord server below.</p>
<?php /* translators: 1: Text for the button (default: Join Our Support Server) */
echo '<a class="button button-primary" href="'.esc_url( DDTT_DISCORD_SUPPORT_URL ).'" target="_blank">'.esc_html( __( 'Join Our Support Server', 'dev-debug-tools' ) ).' »</a><br>'; ?>

<br>
<p>Or if you would rather get support on WordPress.org, you can do so here:</p>
<?php /* translators: 1: Text for the button (default: WordPress.org Plugin Support Page) */
echo '<a class="button button-primary" href="https://wordpress.org/support/plugin/dev-debug-tools/" target="_blank">'.esc_html( __( 'WordPress.org Plugin Support Page', 'dev-debug-tools' ) ).' »</a><br>'; ?>

<br><br><br>
<h3>Like This Plugin?</h3>
<p>Please rate and review this plugin if you find it helpful. If you would give it fewer than 5 stars, please let me know how I can improve it.</p>
<?php /* translators: 1: Text for the button (default: Rate and Review on WordPress.org) */
echo '<a class="button button-primary" href="https://wordpress.org/support/plugin/dev-debug-tools/reviews/" target="_blank">'.esc_html( __( 'Rate and Review on WordPress.org', 'dev-debug-tools' ) ).' »</a><br>'; ?>


<?php if ( ddtt_get_domain() != 'playground.wordpress.net' ) { ?>
    <br><br><br>
    <h3>How Can We Improve?</h3>
    <div id="feedback-form">
        <div class="form-group">
            <label for="message" style="display: block;">If there was one thing you would change about this plugin, what would it be?</label> 
            <br><textarea id="feedback-message" name="message" class="form-control input-message" rows="6" style="width: 40rem; height: 10rem;" placeholder="Your feedback..."></textarea><br>
        </div>
        <?php 
        $nonce = wp_create_nonce( DDTT_GO_PF.'feedback' );
        $user = get_userdata( get_current_user_id() ); 
        $display_name = $user->display_name; 
        $email = $user->user_email; 
        ?>
        <button class="button button-secondary submit" data-nonce="<?php echo esc_attr( $nonce ); ?>" data-name="<?php echo esc_attr( $display_name ); ?>" data-email="<?php echo esc_attr( $email ); ?>" disabled>Send Feedback</button>
        <div id="feedback-sending">Sending</div>
        <div id="feedback-result"></div>
    </div>
<?php } ?>

<?php if ( ddtt_get_domain() != 'playground.wordpress.net' ) { ?>
    <br><br>
    <h3>Try My Other Plugins</h3>
    <?php ddtt_plugin_card( 'admin-help-docs' ); ?>
    <?php ddtt_plugin_card( 'gf-discord' ); ?>
    <?php ddtt_plugin_card( 'gf-msteams' ); ?>
    <?php ddtt_plugin_card( 'broken-link-notifier' ); ?>
<?php } ?>
