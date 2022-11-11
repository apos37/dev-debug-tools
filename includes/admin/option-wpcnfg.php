<style>
.checkbox-cell {
    width: 100px;
}
.form-table td {
    vertical-align: top !important;
}
/* .line-exists.true {
    font-weight: bold;
    color: #DCDCAA;
} */
.line-exists.false {
    color: #FF99CC;
}
.full_width_container.temp {
    filter: invert( 100% );
}
</style>

<?php include 'header.php'; ?>

<?php
// Build the current url
$page = ddtt_plugin_options_short_path();
$tab = 'wpcnfg';
$current_url = ddtt_plugin_options_path( $tab );

// Prefix
$pf = 'wpconfig_';

/**
 * Initiate the class
 */
$GLOBALS['DDTT_WPCONFIG'] = new DDTT_WPCONFIG();
global $DDTT_WPCONFIG;

// Get the snippets we use
$snippets = DDTT_WPCONFIG::snippets();

// Read the WPCONFIG
$filename = 'wp-config.php';
if ( is_readable( ABSPATH . $filename ) ) {
    $file = ABSPATH . $filename;
} elseif ( is_readable( dirname( ABSPATH ) . '/' . $filename ) ) {
    $file = dirname( ABSPATH ) . '/' . $filename;
} else {
    $file = false;
}

// Confirm first
if ( ddtt_get( 'confirm', '==', 'true' ) ) {
    $confirm = true;
    $update_btn_text = 'Confirm and update';
} else {
    $confirm = false;
    $update_btn_text = 'Preview update of';
}

// Check and rewrite wp-config.php
$testing = false;
$enabled = [];
if ( ddtt_get( $pf.'updated', '==', 'true' ) ) {
    if ( ddtt_get( 'l' ) ) {
        $enabled = ddtt_get( 'l' );
        if ( !$testing ) {
            ddtt_remove_qs_without_refresh( [ $pf.'updated', 'l' ] );
        }
    } else {
        if ( !$testing ) {
            ddtt_remove_qs_without_refresh( [ $pf.'updated' ] );
        }
    }
    $DDTT_WPCONFIG->rewrite( $filename, $snippets, $enabled, $testing, $confirm );
    // dpr( $enabled );
}?>

<form method="get" action="<?php echo esc_url( $current_url ); ?>">

    <?php
    // Are we confirming?
    if ( $confirm ) { 

        // Read the TEMP WPCONFIG
        $temp_filename = str_replace( '.php', '-'.DDTT_GO_PF.'temp.php', $filename );
        if ( is_readable( ABSPATH . $temp_filename ) ) {
            $temp_file = ABSPATH . $temp_filename;
        } elseif ( is_readable( dirname( ABSPATH ) . '/' . $temp_filename ) ) {
            $temp_file = dirname( ABSPATH ) . '/' . $temp_filename;
        } else {
            $temp_file = false;
        }

        // If the temp exists, show it
        if ( $temp_file ) { ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">THIS IS WHAT YOUR NEW FILE WILL LOOK LIKE, PLEASE CONFIRM:<br><br>
                        <input type="submit" value="CONFIRM" class="button button-warning"/><br><br>
                        <a href="<?php echo esc_url( $current_url ); ?>" class="button button-primary">Cancel</a>
                    </th>
                    <td><div class="full_width_container temp">
                        <?php ddtt_highlight_file2( $temp_file ); ?>
                    </div></td>
                </tr>
            </table>
        <?php } 
    } ?>

    <table class="form-table">
        <tr valign="top">
            <th scope="row">Current <?php echo esc_attr( $filename ); ?> file (View Only)</th>
            <td><div class="full_width_container">
                <?php ddtt_highlight_file2( $file ); ?>
            </div></td>
        </tr>
    </table>

    <?php if ( ( is_multisite() && !is_network_admin() && is_main_site() ) || !is_multisite() ) { ?>
        <br><br>
        <h2>Snippets (Beta Testing)</h2>
        <p>Add or remove snippets from here. <em>Note: this is still in testing with other users and works as expected on a large number of sites so far, but some sites have <?php echo esc_attr( $filename ); ?> files that have been heavily updated and it may not work as expected. Therefore, please make a backup and test with caution. If you have issues with this, I encourage you to give feedback on our <a href="https://discord.gg/VeMTXRVkm5">Discord Support Server</a> so we can work on improving it for everyone.</em></p>
        <p>Want to modify or add some snippets that aren't listed here? You can <a href="<?php echo esc_url( ddtt_plugin_options_path( 'hooks' ) ); ?>">hook into the snippets array</a>.</p>
        <hr />
        <br>
        <table class="form-table">        
            <?php 
            // Check if the file exists
            if ( $file ) {
                
                // Get the file once
                $file_contents = file_get_contents( $file );

                // Allowed HTML
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
            
                // Cycle each snippet
                foreach ( $snippets as $key => $snippet ) {

                    // Check if it exists
                    $exists = $DDTT_WPCONFIG->snippet_exists( $file_contents, $snippet );

                    // Are we checking the item at load?
                    if ( $confirm && in_array( $key.' ', $enabled ) ) {
                        $checked = true;
                    } elseif ( $confirm && !in_array( $key.' ', $enabled ) ) {
                        $checked = false;
                    } else {
                        $checked = $exists[ 'exists' ];
                    }

                    // Add the row to the table
                    echo wp_kses( $DDTT_WPCONFIG->options_tr( $key, $snippet[ 'label' ], $checked, $exists[ 'strings' ][ 'true' ], $exists[ 'strings' ][ 'false' ] ), $allowed_html );
                }
            }
            ?>

        </table>

        <!-- WARNING TO BACK UP -->
        <?php if ( !get_option( 'ddtt_wpconfig_og' ) ) { ?>
            <br><br>
            <span>&#9888;</span> <strong>WARNING!</strong> Modifying your <?php echo esc_attr( $filename ); ?> file can break your site if you add or remove something that isn't supposed to be changed. All sites are different, so the snippets above will not necessarily work for you. This just gives you an easy way to turn things on and off. It is <strong>ALWAYS</strong> best to make a copy of this file prior to making any changes, no matter how safe it might be.
            <br><br>
        <?php } ?>

        <input type="hidden" name="page" value="<?php echo esc_attr( $page ); ?>">
        <input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>">
        <input type="hidden" name="<?php echo esc_attr( $pf ); ?>updated" value="true">
        <?php if ( !$confirm ) { ?>
            <input type="hidden" name="confirm" value="true">
        <?php } ?>
        <br><br>
        
        <input type="submit" value="<?php echo esc_html( $update_btn_text ); ?> <?php echo esc_attr( $filename ); ?>" class="button button-warning"/>
    <?php } ?>
</form>

<br><br>
<form method="post">
    <input type="submit" value="Download current <?php echo esc_attr( $filename ); ?>" name="ddtt_download_wpconfig" class="button button-primary"/>
</form>