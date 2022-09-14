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
<img class="admin_helpbox_title" src="<?php echo esc_url( DDTT_PLUGIN_IMG_PATH ); ?>discord.png" width="auto" height="100">
<p>If you need assistance with this plugin or have suggestions for improving it, please join the Discord server below.</p>
<?php echo sprintf( __( '<a class="button button-primary" href="%s" target="_blank">Join Our Support Server</a><br>', 'dev-debug-tools' ), 'https://discord.gg/VeMTXRVkm5' ); ?>

<?php
$buy_me_coffee = '<br><br><br><h3>'. __( 'Support this Plugin', 'dev-debug-tools' ).'</h3>
<p>At this time, there are no premium add-ons so the only source of income I have to maintain this plugin is from donations.</p>';
$buy_me_coffee .= sprintf( __( '<a class="button button-primary" href="%s" target="_blank">Buy Me Coffee :)</a><br>', 'dev-debug-tools' ), 'https://paypal.me/apos37' );
$coffee_filter = apply_filters( 'ddtt_coffee', $buy_me_coffee );
if ( $coffee_filter ) {
    echo wp_kses_post( $buy_me_coffee );
}
?>

<br><br><br>
<h3>Planned Features</h3>
<ul>
    <li>Add common php code snippets, such as wp_query, add_shortcode, etc.</li>
    <li>Add a grid overlay to front-end that can be toggled on/off from admin bar</li>
    <li>Add an API playground</li>
    <li>Add a toggle switch for enabling/disabling debug mode/logging to admin bar</li>
    <li>Add detailed descriptions of code snippets</li>
    <li>Add fields to omit lines from debug.log viewer</li>
    <li>Add fields for WPCONFIG values</li>
    <li>Add a how-to on Chrome Devtools</li>
    <li>Add field for changing debug.log search engine</li>
    <li>Make a human readable version of the debug.log with explanations of common errors</li>
    <li>Add a built-in accessibility contrast checker?</li>
    <li>Add a CSS/JS/HTML playground similar to JSFiddle?</li>
    <li>Add a color converter (hex to RGB, etc.)</li>
    <li>Add debug quick links to Gravity Forms forms and entries</li>
    <li>Add a broken link checker</li>
    <li>Add light mode (if requested)</li>
</ul>