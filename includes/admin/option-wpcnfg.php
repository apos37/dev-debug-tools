<style>
.form-table td:first-child {
    padding-left: 0 !important;
}
.form-table td {
    vertical-align: top !important;
    border-top: 1px solid #292929 !important;
}
.option-col,
.option-cell {
    width: auto !important;
}
.checkbox-col,
.checkbox-cell {
    width: 50px !important;
    text-align: center !important;
}
.snippet-col,
.snippet-cell {
    width: 40% !important;
    padding-left: 10px !important;
}
ul {
    list-style: square;
    padding: revert;
}
ul li {
    padding-inline-start: 1ch;
}
ul, ol {
    padding-top: 10px;
    padding-bottom: 5px;
}
.learn-more {
    display: inline-block;
    font-family: sans-serif;
    font-weight: bold;
    text-align: center;
    width: 2ex;
    height: 2ex;
    font-size: 1.4ex;
    line-height: 2ex;
    border-radius: 1.8ex;
    margin-left: 4px;
    padding: 1px;
    color: blue !important;
    background: white;
    border: 1px solid blue;
    text-decoration: none;
}
.field-desc {
    margin-top: 10px;
    display: none;
}
.field-desc.is-open {
    display: block;
}
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
.detected-indicator {
    background-color: #2D2D2D;
    padding: 6px 10px;
    border-radius: 5px;
    vertical-align: middle;
    margin: auto; 
}
.detected-indicator.yes {
    background-color: #556B2F;
    font-weight: bold;
}
.detected-indicator.yes.diff {
    border: 1px solid cornflowerblue;
}
input[type=checkbox]:disabled {
    background-color: #2D2D2D;
    border: none !important;
}
.snippet-tab {
    display: inline-block;
    border: 0px;
    margin-left: .2em;
    margin-top: .2em;
    border-top-left-radius: 5px;
    border-top-right-radius: 5px;
    font-size: 0.9rem;
    padding: 5px 15px;
    font-weight: bold;
    text-decoration: none;
    color: #909696 !important;
}
.snippet-tab.current {
    background: #37373D;
}
.snippet-tab.proposed {
    background: #2D2D2D;
}
.snippet-tab.active {
    color: white !important;
    pointer-events: none;
}
.snippet_container {
    width: revert;
    white-space: pre;
}
.snippet_container.current {
    background: #37373D;
}
.snippet_container.proposed {
    color: #FF99CC;
}
.snippet_container.proposed.changed {
    color: cornflowerblue;
}
.snippet_container textarea {
    width: 100%;
    height: 6rem !important;
    white-space: pre;
}
.snippet-edit-links {
    margin-left: 10px;
}
.snippet-edit-links .save,
.snippet-edit-links .sep,
.snippet-edit-links .cancel {
    display: none;
}
.snippet_container,
.snippet-edit-links {
    display: none;
}
.snippet_container.active {
    display: block !important;
}
.snippet-edit-links.active {
    display: inline-block !important;
}
#edit-notice,
#edit-error-notice {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    padding: 1rem;
    background-color: rgba(255, 0, 0, 0.8);
    color: #fff;
    border: 1px solid #fff;
    border-radius: 5px;
    font-weight: bold;
    text-align: center;
    z-index: 99;
    display: none;
}
#edit-notice {
    cursor: pointer;
}
#edit-error-notice .error-msg {
    width: auto;
    padding: 20px 10px;
    background: #2D2D2D;
    border-radius: 5px;
    white-space: pre;
    margin: 20px 0;
}
#edit-error-notice .buttons {
    margin-top: 10px;
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

// Instantiate the class
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

// Validate
if ( $file ) {
    $file_contents = file_get_contents( $file );

    // Get what eol delimiter is used in the file
    $eols_used = ddtt_get_file_eol( $file_contents, false );

    // Count them
    $eol_count = count( $eols_used );

// We can't do anything if no file is found
} else {
    return 'No file found.';
}

// Defaults
$confirm = false;
$cancel = false;
$update = false;
$pass_eol = false;

// Check for $_POST
$safePost = filter_input_array( INPUT_POST );
$this_nonce = DDTT_GO_PF.'wpconfig_cf';

// Check and rewrite
$testing = false;
$enabled = [];
if ( $safePost && $file ) {
    // dpr( $safePost );

    // Safety first
    if ( !wp_verify_nonce( sanitize_text_field( wp_unslash ( $safePost[ '_wpnonce' ] ) ), $this_nonce ) ) {
        exit( 'No naughty business please.' );
    }

    // Get the eol type to use
    if ( isset( $safePost[ DDTT_GO_PF.'eol_'.$tab ] ) ) {
        $eol_to_use = $safePost[ DDTT_GO_PF.'eol_'.$tab ];
        $pass_eol = $eol_to_use;

    // Or default if canceling
    } else {
        if ( $eol_count > 1 ) {
            $eol_to_use = ddtt_convert_php_eol_to_string();
        } else {
            $eol_to_use = $eols_used[0];
        }
    }

    // Confirmation
    if ( isset( $safePost[ 'confirm' ] ) && $safePost[ 'confirm' ] == 'true' ) {
        $confirm = true;
    }

    // Cancelled
    if ( isset( $safePost[ 'cancel' ] ) && $safePost[ 'cancel' ] == 'Cancel' ) {
        $cancel = true;
    }

    // Rewrite
    if ( isset( $safePost[ $pf.'updated' ] ) && $safePost[ $pf.'updated' ] == 'true' && !$cancel ) {
        $update = true;
        if ( isset( $safePost[ 'a' ] ) ) {
            $enabled[ 'add' ] = $safePost[ 'a' ];
        }
        if ( isset( $safePost[ 'r' ] ) ) {
            $enabled[ 'remove' ] = $safePost[ 'r' ];
        }
        if ( isset( $safePost[ 'u' ] ) ) {
            $enabled[ 'update' ] = $safePost[ 'u' ];
        }
        if ( isset( $safePost[ 's' ] ) ) {
            $enabled[ 'snippets' ] = $safePost[ 's' ];
        }
        // dpr( $enabled );
        $DDTT_WPCONFIG->rewrite( $filename, $snippets, $enabled, $eol_to_use, $testing, $confirm );

        // Reset get file contents so we check new file for existing snippets, etc.
        if ( !$confirm ) {
            $file_contents = file_get_contents( $file );
            $eols_used = ddtt_get_file_eol( $file_contents, false );
            $eol_count = count( $eols_used );
        }
    }

    // Delete the option after we are done
    if ( $cancel ) {
        $eol_to_use = $eols_used[0];
    }

// No post
} else {

    // Default eol
    if ( $eol_count > 1 ) {
        $eol_to_use = ddtt_convert_php_eol_to_string();
    } else {
        $eol_to_use = $eols_used[0];
    }
}

// If more than one eol type is used
if ( $confirm && !$cancel ) {
    $cancel_eol_msg = ', please cancel the update and change it at the bottom of the page.';
} else {
    $cancel_eol_msg = ' when updating snippets, please change it at the bottom of the page before continuing.';
    $eols_used = [ $eol_to_use ];
    $eol_count = 1;
}
if ( $eol_count > 1 ) {
    $occur = ( $eol_count == 2 ) ? 'both' : 'all';
    foreach ( $eols_used as &$eol_used ) {
        $eol_used = '<code class="hl">'.$eol_used.'</code>';
    }
    ?>
    <div class="notice notice-success is-dismissible">
    <p><?php echo sprintf(
        __(
            'The <code class="hl">%s</code> end-of-line delimiters are mixed (%s %s occur). The line delimiter you have set (%s) will be used. If you wish to change the one to be used%s',
            'dev-debug-tools'
        ),
        esc_attr( $filename ),
        wp_kses( implode( ', ', $eols_used ), $allow_code_tag ),
        esc_attr( $occur ),
        wp_kses( '<code class="hl">'.$eol_to_use.'</code>', $allow_code_tag ),
        wp_kses( $cancel_eol_msg, $allow_code_tag )
    ); ?></p>
    </div>
    <?php
    $default_eol = ddtt_convert_php_eol_to_string();

// Only one type amd different
} elseif ( !in_array( $eol_to_use, $eols_used ) ) {
    ?>
    <div class="notice notice-success is-dismissible">
    <p><?php echo sprintf(
        __(
            'The <code class="hl">%s</code> end-of-line delimiters are different than the one you currently have set. The file uses %s, but you are currently set to use %s. If you wish to change the one to be used%s',
            'dev-debug-tools'
        ),
        esc_attr( $filename ),
        wp_kses( '<code class="hl">'.$eols_used[0].'</code>', $allow_code_tag ),
        wp_kses( '<code class="hl">'.$eol_to_use.'</code>', $allow_code_tag ),
        wp_kses( $cancel_eol_msg, $allow_code_tag )
    ); ?></p>
    </div>
    <?php
    $default_eol = $eols_used[0];
} else {
    $default_eol = $eols_used[0];
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

// Delete old stored values from older versions
if ( get_option( 'ddtt_wpconfig_og' ) ) {
    delete_option( 'ddtt_wpconfig_og' );
}
if ( get_option( 'ddtt_wpconfig_last' ) ) {
    delete_option( 'ddtt_wpconfig_last' );
}

// Allowed HTML
$allowed_html = ddtt_wp_kses_allowed_html();
$allow_code_tag = [
    'code' => [
        'class'     => []
    ]
];
?>

<form id="file-update-form" method="post" action="<?php echo esc_url( $current_url ); ?>">
    <?php wp_nonce_field( $this_nonce, '_wpnonce' ); ?>

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
                        <input type="submit" name="cancel" value="Cancel" class="button button-primary"/>
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
    } ?>

    <table class="form-table">
        <tr valign="top">
            <th scope="row">Current <?php echo esc_attr( $filename ); ?> file (View Only)</th>
            <td><div class="full_width_container">
                <?php ddtt_highlight_file2( $file ); ?>
            </div></td>
        </tr>
    </table>

    <?php if ( !$confirm || $cancel ) { ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Backups</th>
                <td><div class="full_width_container">
                    <?php
                    $backups = ddtt_get_files( 'wp-config', 'wp-config.php' );
                    if ( !empty( $backups ) ) {
                        ?>
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
    <?php } ?>

    <?php if ( ( is_multisite() && !is_network_admin() && is_main_site() ) || !is_multisite() ) { ?>
        <?php if ( !$confirm || $cancel ) { ?>
            <br><br>
            <h2>Snippets (Beta Testing)</h2>
            <p>This interface allows you to modify code snippets on your <?php echo esc_attr( $filename ); ?> file.
            <ul>
                <li>Select actions (add, remove, update) using checkboxes.</li>
                <li>"Proposed" code is used for adding/updating, not "Current" code. If a snippet is detected and you want to keep the "Current" code, no action is necessary.</li>
                <li>Edited snippets will be moved to DDT section if detected elsewhere.</li>
                <li>Detected indicator shows with a blue border if the snippet is found, but does not match the proposed code.</li>
                <li>Plugin checks for errors when saving edited snippets (not foolproof, double-check).</li>
                <li>Saving an edited snippet doesn't modify the file yet. Preview changes before confirming or cancelling.</li>
                <li>Want to modify or add some snippets that aren't listed here? You can <a href="<?php echo esc_url( ddtt_plugin_options_path( 'hooks' ) ); ?>">hook into the snippets array</a>.</li>
             </p>
            </ul>
            <p style="font-size: 1rem;"><strong>Heads up!</strong> <em>This feature is under testing and might not work perfectly on all sites, especially those with heavily modified <?php echo esc_attr( $filename ); ?> files. Proceed with caution: back up your site and test thoroughly. If you encounter issues, please let us know on our <a href="<?php echo esc_url( DDTT_DISCORD_SUPPORT_URL ); ?>">Discord Support Server</a> so we can improve it for everyone.</em></p>
            <hr />
            <br>
            <table class="form-table">
                <tr>
                    <th class="option-col">Option</th>
                    <th class="checkbox-col">Detected</th>
                    <th class="checkbox-col">Add</th>
                    <th class="checkbox-col">Remove</th>
                    <th class="checkbox-col">Update</th>
                    <th class="snippet-col">Snippet</th>
                </tr>
                <?php 
                // Cycle each snippet
                foreach ( $snippets as $key => $snippet ) {

                    // If removing, skip it
                    if ( isset( $snippet[ 'remove' ] ) && $snippet[ 'remove' ] ) {
                        continue;
                    }

                    // Check if it exists
                    $exists = $DDTT_WPCONFIG->snippet_exists( $file_contents, $snippet );

                    // Are we checking the item at load?
                    $checked = $exists[ 'exists' ];

                    // Description
                    if ( isset( $snippet[ 'desc' ] ) ) {
                        $desc = $snippet[ 'desc' ];
                    } else {
                        $desc = '';
                    }
                    // dpr( $exists );

                    // Add the row to the table
                    echo wp_kses( $DDTT_WPCONFIG->options_tr( $key, $snippet[ 'label' ], $checked, $exists[ 'strings' ][ 'current' ], $exists[ 'strings' ][ 'proposed' ], $desc, $eol_to_use ), $allowed_html );
                }
                ?>
            </table>
            <div id="edit-notice">Please save or cancel editing the snippet before proceeding.</div>
            <div id="edit-error-notice">Uh-oh! Your code snippet update found an error:
                <div class="error-msg"></div>
                Do you want to save anyway?
                <div class="buttons">
                    <a href="#" class="no button button-secondary">No, continue editing</a> 
                    <a href="#" class="yes button button-secondary" data-name="">Yes</a>
                </div>
            </div>

            <table class="form-table">
                <?php
                // Display what is used
                foreach ( $eols_used as &$eol_used ) {
                    $eol_used = '<code class="hl">'.$eol_used.'</code>';
                }
                $incl_used = ' The file is currently using '.implode( ', ', $eols_used ).'.';

                // Options for select field
                $eol_types = [
                    'options' => [
                        [ 'label' => '\n — Unix/Linux (LF - Line Feed)', 'value' => '\n' ],
                        [ 'label' => '\r\n — MS Windows (CRLF - Carriage Return Line Feed)', 'value' => '\r\n' ],
                        [ 'label' => '\r — Old Macintosh (CR - Carriage Return)', 'value' => '\r' ],
                        [ 'label' => '\n\r — Acorn BBC (LFCR - Line Feed Carriage Return)', 'value' => '\n\r' ],
                    ],
                    'default' => $default_eol,
                ]; ?>

                <?php echo wp_kses( ddtt_options_tr( 'eol_'.$tab, 'End-Of-Line Delimiter to Use', 'select', '<br>You can change which end-of-line delimiter to use. The file is currently using '.implode( ', ', $eols_used ).', and your <code class="hl">PHP_EOL</code> constant is set to <code class="hl">'.ddtt_convert_php_eol_to_string().'</code>. If you don\'t know what this means, leave it alone.', $eol_types ), $allowed_html ); ?>
            </table>
        <?php } else {
            if ( $pass_eol ) {
                ?>
                <input type="hidden" name="<?php echo esc_attr( DDTT_GO_PF ); ?>eol_<?php echo esc_attr( $tab ); ?>" value="<?php echo esc_attr( $pass_eol ); ?>">
                <?php
            }
        } ?>

        <!-- WARNING TO BACK UP -->
        <?php if ( !get_option( 'ddtt_wpconfig_og' ) ) { ?>
            <br><br>
            <span>&#9888;</span> <strong>WARNING!</strong> Modifying your <?php echo esc_attr( $filename ); ?> file can break your site if you add or remove something that isn't supposed to be changed. All sites are different, so the snippets above will not necessarily work for you. This just gives you an easy way to turn things on and off. It is <strong>ALWAYS</strong> best to make a copy of this file prior to making any changes, no matter how safe it might be.
            <br><br>
        <?php } ?>
        
        <?php 
        if ( $safePost && $update && $confirm && !$cancel ) {
            foreach ( $safePost as $array => $sp ) {
                $pass = [ 'a', 'r', 'u', 's' ];
                if ( !in_array( $array, $pass ) ) {
                    continue;
                }
                foreach ( $sp as $key => $s ) {
                    if ( $array == 's' ) {
                        $name = $array.'['.$key.']';
                    } else {
                        $name = $array.'[]';
                    }
                    ?>
                    <input type="hidden" name="<?php echo esc_html( $name ) ?>" value="<?php echo esc_html( $s ) ?>">
                    <?php
                }
            }
        }
        ?>
        <input type="hidden" name="<?php echo esc_attr( $pf ); ?>updated" value="true">
        <?php if ( !$confirm || $cancel ) { ?>
            <input type="hidden" name="confirm" value="true">
        <?php } ?>
        <br><br>
        <?php if ( $confirm && !$cancel ) { ?>
            <input type="submit" value="Confirm and update <?php echo esc_attr( $filename ); ?>" class="button button-warning"/>
            <input type="submit" name="cancel" value="Cancel" class="button button-primary"/>
        <?php } else { ?>
            <input id="preview_btn" type="submit" value="Preview update of <?php echo esc_attr( $filename ); ?>" class="button button-warning" disabled/>
        <?php } ?>
    <?php } ?>
</form>

<br><br>
<form method="post">
    <?php wp_nonce_field( DDTT_GO_PF.'wpconfig_dl', '_wpnonce' ); ?>
    <input type="submit" value="Download current <?php echo esc_attr( $filename ); ?>" name="ddtt_download_wpconfig" class="button button-primary"/>
</form>