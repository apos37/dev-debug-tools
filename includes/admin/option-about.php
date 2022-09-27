<style>
ul {
    list-style: square;
    padding: revert;
}
ul li {
    padding-inline-start: 1ch;
}
</style>

<?php include 'header.php'; ?>

<br><br>
<h3>Plugin Support</h3>
<br><img class="admin_helpbox_title" src="<?php echo esc_url( DDTT_PLUGIN_IMG_PATH ); ?>discord.png" width="auto" height="100">
<p>If you need assistance with this plugin or have suggestions for improving it, please join the Discord server below.</p>
<?php echo sprintf( __( '<a class="button button-primary" href="%s" target="_blank">Join Our Support Server »</a><br>', 'dev-debug-tools' ), 'https://discord.gg/VeMTXRVkm5' ); ?>
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
$buy_me_coffee .= sprintf( __( '<a class="button button-primary" href="%s" target="_blank">Buy Me Coffee :)</a><br>', 'dev-debug-tools' ), 'https://paypal.me/apos37' );
$coffee_filter = apply_filters( 'ddtt_coffee', $buy_me_coffee );
if ( $coffee_filter ) {
    echo wp_kses_post( $buy_me_coffee );
}
?>

<br><br><br>
<h3>Planned Features</h3>
<p>The following features are currently planned, but are not necessarily in order. If you would like to request a feature, please do so on Discord at the link above.</p>
<ul>
    <li>Add common php code snippets, such as wp_query, add_shortcode, etc.</li>
    <li>Add detailed descriptions of code snippets</li>
    <li>Add field to omit lines from debug.log viewer</li>
    <li>Add fields for WPCONFIG values</li>
    <li>Make a human-readable version of the debug.log with explanations of common errors</li>
    <li>Add a color converter (hex to RGB, etc.)</li>
    <li>Add light mode (if requested)</li>
</ul>