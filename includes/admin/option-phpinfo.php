<?php include 'header.php'; ?>

<style>
.tab-content a:link { color: #009; }
.tab-content table { border-collapse: collapse; border: 0; width: 100%; }
.tab-content .center { text-align: center; }
.tab-content .center table { margin: 1em auto; text-align: left; }
.tab-content .center th { text-align: center !important; }
.tab-content td, th { border: 1px solid #666; vertical-align: baseline; padding: 4px 5px; }
.tab-content th { position: sticky; top: 0; background: inherit; }
.tab-content h1 { font-size: 150%; }
.tab-content h2 { font-size: 125%; }
.tab-content .p { text-align: left; }
.tab-content .e { background-color: #1E1E1E; width: 300px; font-weight: bold; }
.tab-content .h { background-color: #2D2D2D; font-weight: bold; }
.tab-content .v { background-color: #37373D; max-width: 300px; overflow-x: auto; word-wrap: break-word; }
.tab-content .v i { color: #999; }
.tab-content img { float: right; border: 0; }
.tab-content hr { width: 100%; background-color: #ccc; border: 0; height: 1px; }
</style>

<?php
// Let's get the php info
ob_start();
phpinfo();
$phpinfo = ob_get_contents();
ob_end_clean();

// Strip the title, meta name, and built-in style
$phpinfo = preg_replace( '/<title>(.*)<\/title>/', '', $phpinfo );
$phpinfo = preg_replace( '/<meta name(.*)>/', '', $phpinfo );
$phpinfo = preg_replace( '/<style.*?>([^>]*)<\/style>/', '', $phpinfo );

// Add it
echo wp_kses_post( $phpinfo );