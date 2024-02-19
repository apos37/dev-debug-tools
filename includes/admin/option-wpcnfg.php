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
.wp-core-ui .button:disabled, 
.wp-core-ui .button[disabled] {
    cursor: not-allowed;
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
$DDTT_WPCONFIG = new DDTT_WPCONFIG();

// Get the snippets we use
$snippets = $DDTT_WPCONFIG->snippets();

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
    $cancel = false;
    $update_btn_text = 'Confirm and update';

// Cancelled update
} elseif ( ddtt_get( 'confirm', '==', 'cancel' ) ) {
    $confirm = true;
    $cancel = true;
    $update_btn_text = 'Preview update of';
    ddtt_remove_qs_without_refresh( [ 'confirm' ] );

// No confirm param
} else {
    $confirm = false;
    $cancel = false;
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
}

// Are we deleting backups?
if ( ddtt_get( 'delete_backups', '==', 'true' ) ) {
    $deleted_backups = $DDTT_WPCONFIG->delete_backups();
    if ( $deleted_backups ) {
        $s = count( $deleted_backups ) > 1 ? 's' : '';
        ddtt_admin_notice( 'success', '&#x1F4A5; You have successfully destroyed '.absint( count( $deleted_backups ) ).' old backup'.$s.'.' );
    }
    ddtt_remove_qs_without_refresh( [ 'delete_backups' ] );
}
?>

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
        if ( $temp_file && !$cancel ) { ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">THIS IS WHAT YOUR NEW FILE WILL LOOK LIKE, PLEASE CONFIRM:<br><br>
                        <input type="submit" value="CONFIRM" class="button button-warning"/><br><br>
                        <a href="<?php echo esc_url( $current_url ); ?>&confirm=cancel" class="button button-primary">Cancel</a>
                    </th>
                    <td><div class="full_width_container temp">
                        <?php ddtt_highlight_file2( $temp_file ); ?>
                    </div></td>
                </tr>
            </table>
        <?php } elseif ( $temp_file && $cancel ) {

            // Or delete it if cancelled
            unlink( $temp_file );
        }
    }
    ?>

    <table class="form-table">
        <tr valign="top">
            <th scope="row">Current <?php echo esc_attr( $filename ); ?> file (View Only)</th>
            <td><div class="full_width_container">
                <?php ddtt_highlight_file2( $file ); ?>
            </div></td>
        </tr>
    </table>

    <table class="form-table">
        <tr valign="top">
            <th scope="row">Backups</th>
            <td><div class="full_width_container">
                <?php
                $backups = ddtt_get_files( 'wp-config', 'wp-config.php' );
                if ( !empty( $backups ) ) {
                    ?>
                    <!--All files in your root directory that contain "<strong>wp-config</strong>" in the filename will show up here with links to preview them. By clicking on a "Preview" link, you will be able to preview the file's contents and choose whether or not you want to restore it or delete it. Restoring it replaces the current "<strong>wp-config.php</strong>" file.<br><br><em>Backups made from this plugin will be named like so:</em> <code>wp-config-[YEAR]-[MONTH]-[DAY]-[HOUR]-[MINUTE]-[SECOND].php</code><br><em>All others will be marked as possibly unsafe to restore.</em>-->
                    All files in your root directory that contain "<strong>wp-config</strong>" in the filename are shown here for reference.<br><br><em>Backups made from this plugin will be named like so:</em> <code>wp-config-[YEAR]-[MONTH]-[DAY]-[HOUR]-[MINUTE]-[SECOND].php</code><br><em>All others will be marked as possibly unsafe.</em><br><br><hr><br><strong><?php echo absint( count( $backups ) ); ?> Files Found:</strong>
                    <ul>
                        <?php
                        // Count ones that can be deleted
                        $can_delete = 0;

                        // Number them
                        $count_backups = 0;
                        
                        // Iter the backups
                        foreach ( $backups as $backup ) {

                            // Remove the filepath
                            $exp = explode( '/', $backup );
                            $short = trim( array_pop( $exp ) );
                            
                            // Check if it's ours
                            $ours = ' <span class="warning-symbol" style="margin-left: 10px;"></span> <strong>Possibly Unsafe —</strong> <em>Remove via FTP or File Manager on Host</em>';
                            $pattern = '/wp\-config\-[0-9]{4}\-[0-9]{2}\-[0-9]{2}\-[0-9]{2}\-[0-9]{2}\-[0-9]{2}.php/';
                            if ( preg_match( $pattern, $short ) ) {
                                $ours = '';

                                // Skip the first one as it will always be the most recent
                                $count_backups++;
                                if ( $count_backups > 1 ) {
                                    $can_delete++;
                                }
                            }
                            ?>
                            <li><?php echo esc_attr( $short ); ?> <?php echo wp_kses_post( $ours ); ?></li>
                            <?php
                        }
                        ?>
                    </ul>
                    <?php
                    if ( $can_delete > 0 ) {
                        ?>
                        <br><hr>
                        <a href="<?php echo esc_url( $current_url.'&delete_backups=true' ); ?>">Clear All Except Most Recent</a> (Doing so will delete all other files added by this plugin only)
                        <?php
                    }
                } else {
                    ?>
                    All files in your root directory that contain "<strong>wp-config</strong>" in the filename will show up here for reference.<br>
                    <br><strong><em>No backups found...</em></strong>
                    <?php
                }
                ?>
            </div></td>
        </tr>
    </table>

    <?php if ( ( is_multisite() && !is_network_admin() && is_main_site() ) || !is_multisite() ) { ?>
        <br><br>
        <h2>Snippets (Beta Testing)</h2>
        <p>Add or remove snippets from here. <em>Note: this is still in testing with other users and works as expected on a large number of sites so far, but some sites have <?php echo esc_attr( $filename ); ?> files that have been heavily updated and it may not work as expected. Therefore, please make a backup and test with caution. If you have issues with this, I encourage you to give feedback on our <a href="<?php echo esc_url( DDTT_DISCORD_SUPPORT_URL ); ?>">Discord Support Server</a> so we can work on improving it for everyone.</em></p>
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
        <input id="preview_btn" type="submit" value="<?php echo esc_html( $update_btn_text ); ?> <?php echo esc_attr( $filename ); ?>" class="button button-warning" disabled/>
    <?php } ?>
</form>

<br><br>
<form method="post">
    <?php wp_nonce_field( DDTT_GO_PF.'wpconfig_dl', '_wpnonce' ); ?>
    <input type="submit" value="Download current <?php echo esc_attr( $filename ); ?>" name="ddtt_download_wpconfig" class="button button-primary"/>
</form>

<script>
// Show/Hide Preview Button
var ddttCheckBox = document.querySelectorAll( ".checkbox-cell input[type='checkbox']" );
ddttCheckBox.forEach( function( item ) {
  item.addEventListener( 'click', function() {
    var previewBtn = document.getElementById( "preview_btn" );
    if ( previewBtn.disabled == true ) {
        previewBtn.disabled = false;
    }
  } )
} )
</script>