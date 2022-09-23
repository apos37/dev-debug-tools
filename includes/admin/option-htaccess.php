<?php
// Prefix
$pf = 'htaccess_';

/**
 * Initiate the class
 */
$GLOBALS['DDTT_HTACCESS'] = new DDTT_HTACCESS();
global $DDTT_HTACCESS;

// Get the snippets we use
$snippets = DDTT_HTACCESS::snippets();

// Read the HTACCESS
$filename = '.htaccess';
if ( is_readable( ABSPATH . $filename ) ) {
    $file = ABSPATH . $filename;
} elseif ( is_readable( dirname( ABSPATH ) . '/' . $filename ) ) {
    $file = dirname( ABSPATH ) . '/' . $filename;
} else {
    $file = false;
}

// Check and rewrite wp-config.php
if ( ddtt_get( $pf.'updated', '==', 'true' ) ) {
    if ( ddtt_get( 'l' ) ) {
        $enabled = ddtt_get( 'l' );
        ddtt_remove_qs_without_refresh( [ $pf.'updated', 'l' ] );
    } else {
        $enabled = [];
        ddtt_remove_qs_without_refresh( [ $pf.'updated' ] );
    }
    $testing = false;
    $DDTT_HTACCESS->rewrite( $filename, $snippets, $enabled, $testing );
    // ddtt_print_r( $enabled );
}

// Build the current url
$page = ddtt_plugin_options_short_path();
$tab = 'htaccess';
$current_url = ddtt_plugin_options_path( $tab );
?>
<style>
.checkbox-cell {
    width: 100px;
}
.form-table td {
    vertical-align: top !important;
}
/* .snippet-exists.true {
    font-weight: bold;
    color: #DCDCAA;
} */
.snippet-exists.false {
    color: #FF99CC;
}
</style>
<?php include 'header.php'; ?>

<form method="get" action="<?php echo esc_url( $current_url ); ?>">
    <table class="form-table">
        <tr valign="top">
            <th scope="row">Current <?php echo esc_html( $filename ); ?> file (View Only)</th>
            <td><div class="full_width_container">
                <?php echo wp_kses_post( ddtt_view_file_contents( '.htaccess' ) ); ?>
            </div></td>
        </tr>
    </table>

    <br><br>
    <h2>Snippets (Beta Testing)</h2>
    <p>Add or remove snippets from here. <em>Note: this is still in testing with other users and works as expected on a number of sites so far, but some sites have <?php echo esc_attr( $filename ); ?> files that have been heavily updated and it may not work as expected. Therefore, please make a backup and test with caution. If you have issues with this, I encourage you to give feedback on our <a href="https://discord.gg/VeMTXRVkm5">Discord Support Server</a> so we can work on improving it for everyone.</em></p>
    <p>Want to modify or add some snippets that aren't listed here? You can <a href="<?php echo esc_url( ddtt_plugin_options_path( 'hooks' ) ); ?>">hook into the snippets array</a>.</p>
    <hr />
    <br>
    <table class="form-table">        
        <?php 
        // Check if the file exists
        if ( $file ) {
            
            // Get the file once
            $file_contents = file_get_contents( $file );

            // Display html tags
            $file_contents = htmlspecialchars( $file_contents );

            // Replace line breaks
            $file_contents = strtr( $file_contents, chr(10), chr(32) );
        
            // Cycle each snippet
            foreach ( $snippets as $key => $snippet ) {
        
                // Check if it exists
                $exists = $DDTT_HTACCESS->snippet_exists( $file_contents, $snippet );
                // ddtt_print_r($exists);

                // Add the row to the table
                $allowed_html = [
                    'tr' => [
                        'valign' => []
                    ],
                    'th' => [
                        'scope' => []
                    ],
                    'td' => [
                        'class' => []
                    ],
                    'div' => [
                        'class' => []
                    ],
                    'span' => [
                        'class' => []
                    ],
                    'input' => [
                        'type'      => [],
                        'name'      => [],
                        'value'     => [],
                        'checked'   => []
                    ],
                    'br' => []
                ];
                echo wp_kses( $DDTT_HTACCESS->options_tr( $key, $snippet, $exists ), $allowed_html );
            }
        }
        ?>

    </table>

    <!-- WARNING TO BACK UP -->
    <?php if ( !get_option( 'ddtt_htaccess_og' ) ) { ?>
        <br><br>
        <span>&#9888;</span> <strong>WARNING!</strong> Modifying your <?php echo esc_html( $filename ); ?> file can break your site if you add or remove something that isn't supposed to be changed. All sites are different, so the snippets above will not necessarily work for you. This just gives you an easy way to turn things on and off. It is <strong>ALWAYS</strong> best to make a copy of this file prior to making any changes, no matter how safe it might be.
        <br><br>
    <?php } ?>

    <input type="hidden" name="page" value="<?php echo esc_html( $page ); ?>">
    <input type="hidden" name="tab" value="<?php echo esc_html( $tab ); ?>">
    <input type="hidden" name="<?php echo esc_attr( $pf ); ?>updated" value="true">
    <br><br><input type="submit" value="Update <?php echo esc_html( $filename ); ?>" class="button button-warning"/>
</form>

<br><br>
<form method="post">
    <input type="submit" value="Download current <?php echo esc_html( $filename ); ?>" name="ddtt_download_htaccess" class="button button-primary"/>
</form>