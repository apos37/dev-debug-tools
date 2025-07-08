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
$tab = 'htaccess';
$current_url = ddtt_plugin_options_path( $tab );

// Prefix
$pf = 'htaccess_';

// Initiate the class
$DDTT_HTACCESS = new DDTT_HTACCESS();

// Get the snippets we use
$snippets = $DDTT_HTACCESS->snippets();

// Get the file
if ( !function_exists( 'WP_Filesystem' ) ) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
}
global $wp_filesystem;
if ( !WP_Filesystem() ) {
    ddtt_write_log( 'Failed to initialize WP_Filesystem' );
    return false;
}

// Read the file
$filename = '.htaccess';
if ( $wp_filesystem->is_readable( ABSPATH . $filename ) ) {
    $file = ABSPATH . $filename;
} elseif ( $wp_filesystem->is_readable( dirname( ABSPATH ) . '/' . $filename ) ) {
    $file = dirname( ABSPATH ) . '/' . $filename;
} else {
    return 'No file found.';
}
$file_contents = $wp_filesystem->get_contents( $file );
$file_contents = htmlspecialchars( $file_contents );

// Get what eol delimiter is used in the file
$eols_used = ddtt_get_file_eol( $file_contents, false );

// Count them
$eol_count = count( $eols_used );

// Defaults
$confirm = false;
$cancel = false;
$update = false;
$pass_eol = false;

// Check for $_POST
$safePost = filter_input_array( INPUT_POST );
$this_nonce = DDTT_GO_PF.'htaccess_cf';

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
        // dpr( $enabled );
        $DDTT_HTACCESS->rewrite( $filename, $snippets, $enabled, $eol_to_use, $testing, $confirm );

        // Reset get file contents so we check new file for existing snippets, etc.
        if ( !$confirm ) {
            $file_contents = $wp_filesystem->get_contents( $file );
            $file_contents = htmlspecialchars( $file_contents );
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
        <p><?php
            echo wp_kses(
                /* Translators: 1: filename, 2: eols used, 3: occurs, 4: eol to use, 5: cancel message */
                sprintf(
                    __(
                        'The <code class="hl">%1$s</code> end-of-line delimiters are mixed (%2$s %3$s occur). The line delimiter you have set (%4$s) will be used. If you wish to change the one to be used%5$s',
                        'dev-debug-tools'
                    ),
                    esc_html( $filename ),
                    esc_html( implode( ', ', $eols_used ) ),
                    esc_html( $occur ),
                    '<code class="hl">' . esc_html( $eol_to_use ) . '</code>',
                    wp_kses( $cancel_eol_msg, [ 'a' => [ 'href' => true ], 'code' => [ 'class' => true ] ] )
                ),
                [ 'code' => [ 'class' => true ] ]
            );
        ?></p>
    </div>
    <?php
    $default_eol = ddtt_convert_php_eol_to_string();

// Only one type amd different
} elseif ( !in_array( $eol_to_use, $eols_used ) ) {
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php 
            echo wp_kses(
                /* Translators: 1: filename, 2: eols used, 3: eol to use, 4: cancel message */
                sprintf(
                    __(
                        'The <code class="hl">%1$s</code> end-of-line delimiters are different than the one you currently have set. The file uses %2$s, but you are currently set to use %3$s. If you wish to change the one to be used%4$s',
                        'dev-debug-tools'
                    ),
                    esc_html( $filename ),
                    '<code class="hl">' . esc_html( $eols_used[0] ) . '</code>',
                    '<code class="hl">' . esc_html( $eol_to_use ) . '</code>',
                    wp_kses( $cancel_eol_msg, [ 'a' => [ 'href' => true ], 'code' => [ 'class' => true ] ] )
                ),
                [ 'code' => [ 'class' => true ] ]
            );
        ?></p>
    </div>
    <?php
    $default_eol = $eols_used[0];
} else {
    $default_eol = $eols_used[0];
}

// Are we deleting backups?
if ( ddtt_get( 'delete_backups', '==', 'true' ) ) {
    $deleted_backups = $DDTT_HTACCESS->delete_backups();
    if ( $deleted_backups ) {
        $s = count( $deleted_backups ) > 1 ? 's' : '';
        ddtt_admin_notice( 'success', '&#x1F4A5; You have successfully destroyed '.absint( count( $deleted_backups ) ).' old backup'.$s.'.' );
    }
    ddtt_remove_qs_without_refresh( [ 'delete_backups' ] );
}

// Delete old stored values from older versions
if ( get_option( 'ddtt_htaccess_og' ) ) {
    delete_option( 'ddtt_htaccess_og' );
}
if ( get_option( 'ddtt_htaccess_last' ) ) {
    delete_option( 'ddtt_htaccess_last' );
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
    // Read the TEMP file
    $temp_filename = str_replace( '.htaccess', '.htaccess-'.DDTT_GO_PF.'temp', $filename );
    if ( $wp_filesystem->is_readable( ABSPATH . $temp_filename ) ) {
        $temp_file = ABSPATH . $temp_filename;
    } elseif ( $wp_filesystem->is_readable( dirname( ABSPATH ) . '/' . $temp_filename ) ) {
        $temp_file = dirname( ABSPATH ) . '/' . $temp_filename;
    } else {
        $temp_file = false;
    }

    // Are we confirming?
    if ( $confirm ) { 

        // If the temp exists, show it
        if ( $temp_file && !$cancel ) { ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">THIS IS WHAT YOUR NEW FILE WILL LOOK LIKE, PLEASE CONFIRM:<br><br>
                        <input type="submit" value="CONFIRM" class="button button-warning"/><br><br>
                        <input type="submit" name="cancel" value="Cancel" class="button button-primary"/>
                    </th>
                    <td><div class="full_width_container temp">
                        <?php echo wp_kses_post( ddtt_view_file_contents( $temp_filename ) ); ?>
                    </div></td>
                </tr>
            </table>
        <?php }

    } elseif ( $temp_file && $cancel ) {

        // Or delete it if cancelled
        $wp_filesystem->delete( $temp_file );
    }
    ?>

    <table class="form-table">
        <tr valign="top">
            <th scope="row">Current <?php echo esc_attr( $filename ); ?> file (View Only)</th>
            <td><div class="full_width_container">
                <?php echo wp_kses_post( ddtt_view_file_contents( $filename ) ); ?>
            </div></td>
        </tr>
    </table>

    <?php if ( !$confirm || $cancel ) { ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Backups</th>
                <td><div class="full_width_container">
                    <?php
                    $backups = ddtt_get_files( 'htaccess', '.htaccess' );
                    if ( !empty( $backups ) ) {
                        ?>
                        All files in your root directory that contain "<strong>htaccess</strong>" in the filename are shown here for reference.<br><br><em>Backups made from this plugin will be named like so:</em> <code>.htaccess-[YEAR]-[MONTH]-[DAY]-[HOUR]-[MINUTE]-[SECOND]</code><br><em>All others will be marked as possibly unsafe.</em><br><br><hr><br><strong><?php echo absint( count( $backups ) ); ?> Files Found:</strong>
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
                                $pattern = '/.htaccess\-[0-9]{4}\-[0-9]{2}\-[0-9]{2}\-[0-9]{2}\-[0-9]{2}\-[0-9]{2}/';
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
                        All files in your root directory that contain "<strong>htaccess</strong>" in the filename will show up here for reference.<br>
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
            <p>This interface allows you to add and remove code snippets on your <?php echo esc_attr( $filename ); ?> file.
            <ul>
                <li>Select actions (add, remove) using checkboxes.</li>
                <li>If a snippet is detected and you want to keep the "Current" code, no action is necessary.</li>
                <li>Unlike the WPCONFIG tab, you cannot edit snippets here. This is due to the inability to detect changes since we don't store the data in a database (for security reasons) and cannot define the snippets with a unique variable or function.</li>
                <li>You can still modify them or add some snippets that aren't listed here by <a href="<?php echo esc_url( ddtt_plugin_options_path( 'hooks' ) ); ?>">hooking into the snippets array</a>.</li>
             </p>
            </ul>
            <p style="font-size: 1rem;"><strong>Heads up!</strong> <em>This feature is under testing and might not work perfectly on all sites, especially those with heavily modified <?php echo esc_attr( $filename ); ?> files. Proceed with caution: back up your site and test thoroughly. If you encounter issues, please let us know on our <a href="<?php echo esc_url( DDTT_DISCORD_URL ); ?>">Discord Support Server</a> so we can improve it for everyone.</em></p>
            <hr />
            <br>
            <table class="form-table">
                <tr>
                    <th class="option-col">Option</th>
                    <th class="checkbox-col">Detected</th>
                    <th class="checkbox-col">Add</th>
                    <th class="checkbox-col">Remove</th>
                    <th class="snippet-col">Snippet</th>
                </tr>
                <?php 
                // Check if the file exists
                if ( $file ) {
                    
                    // Trim leading and trailing whitespace from each line
                    $trimmed_lines = array_map( 'trim', explode( "\n", $file_contents ) );

                    // Join the trimmed lines back into one long string
                    $file_string = implode( ' ', $trimmed_lines );
                
                    // Cycle each snippet
                    foreach ( $snippets as $key => $snippet ) {

                        // If removing, skip it
                        if ( isset( $snippet[ 'remove' ] ) && $snippet[ 'remove' ] ) {
                            continue;
                        }

                        // Check if it exists
                        $exists = $DDTT_HTACCESS->snippet_exists( $file_string, $snippet );
                        // dpr( $key.': '.$exists );

                        // Add the row to the table
                        echo wp_kses( $DDTT_HTACCESS->options_tr( $key, $snippet, $exists ), $allowed_html );
                    }
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
        <?php if ( !get_option( 'ddtt_htaccess_og_replaced_date' ) ) { ?>
            <br><br>
            <span>&#9888;</span> <strong>WARNING!</strong> Modifying your <?php echo esc_attr( $filename ); ?> file can break your site if you add or remove something that isn't supposed to be changed. All sites are different, so the snippets above will not necessarily work for you. This just gives you an easy way to turn things on and off. It is <strong>ALWAYS</strong> best to make a copy of this file prior to making any changes, no matter how safe it might be.
            <br><br>
        <?php } ?>
        <?php 
        if ( $safePost && $update && $confirm && !$cancel ) {
            foreach ( $safePost as $array => $sp ) {
                $pass = [ 'a', 'r' ];
                if ( !in_array( $array, $pass ) ) {
                    continue;
                }
                foreach ( $sp as $key => $s ) {
                    $name = $array.'[]';
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
    <?php wp_nonce_field( DDTT_GO_PF.'htaccess_dl', '_wpnonce' ); ?>
    <input type="submit" value="Download current <?php echo esc_attr( $filename ); ?>" name="ddtt_download_htaccess" class="button button-primary"/>
</form>