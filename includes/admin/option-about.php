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
<?php echo sprintf( __( '<a class="button button-primary" href="%s" target="_blank">Join Our Support Server »</a><br>', 'dev-debug-tools' ), 'https://discord.gg/3HnzNEJVnR' ); ?>
<br>
<p>Or if you would rather get support on WordPress.org, you can do so here:</p>
<?php echo sprintf( __( '<a class="button button-primary" href="%s" target="_blank">WordPress.org Plugin Support Page »</a><br>', 'dev-debug-tools' ), 'https://wordpress.org/support/plugin/dev-debug-tools/' ); ?>

<br><br><br>
<h3>Like This Plugin?</h3>
<p>Please rate and review this plugin if you find it helpful. If you would give it fewer than 5 stars, please let me know how I can improve it.</p>
<?php echo sprintf( __( '<a class="button button-primary" href="%s" target="_blank">Rate and Review on WordPress.org »</a><br>', 'dev-debug-tools' ), 'https://wordpress.org/support/plugin/dev-debug-tools/reviews/' ); ?>

<?php
$buy_me_coffee = '<br><br><br><h3>'. __( 'Support This Plugin', 'dev-debug-tools' ).'</h3>
<p>At this time, there are no premium add-ons so the only source of income I have to maintain this plugin is from donations.</p>';
$buy_me_coffee .= sprintf( __( '<a class="button button-primary" href="%s" target="_blank">Buy Me Coffee :)</a><br>', 'dev-debug-tools' ), 'https://paypal.com/donate/?business=3XHJUEHGTMK3N' );
$coffee_filter = apply_filters( 'ddtt_coffee', $buy_me_coffee );
if ( $coffee_filter ) {
    echo wp_kses_post( $buy_me_coffee );
}
?>

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

<br><br><br>
<h3>Planned Features</h3>
<p>The following features are currently planned, but are not necessarily in order. If you would like to request a feature, please do so on Discord at the link above.</p>
<ul>
    <li>Add a link organizer that shows all links on all pages to easily find one that you're looking for</li>
    <li>Add a keyword finder to search for code in all pages of the site</li>
    <li>Add ability to download redacted versions of wp-config.php and .htaccess together to give to support</li>
    <li>Hide sensitive info such as DB password and IP address, and add a "view sensitive info" option that reveals it</li>
    <li>Add ability to restore old backups of wp-config and .htaccess files</li>
    <li>Add common php code snippets, such as wp_query, add_shortcode, etc.</li>
    <li>Add detailed descriptions of code snippets</li>
    <li>Make a human-readable version of the debug.log with explanations of common errors</li>
    <li>Add a color converter (hex to RGB, etc.)</li>
    <li>Add light mode (if requested)</li>
</ul>

<br><br>
<h3>Try My Other Plugin</h3>
<?php echo wp_kses_post( ddtt_plugin_card( 'admin-help-docs' ) ); ?>