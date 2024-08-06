<?php
/**
 * The password page
 */

// Include the header
include 'header.php';

// Build the current url
$page = ddtt_plugin_options_short_path();
$tab = 'pw-reset';
$current_url = ddtt_plugin_options_path( $tab );

// Reset password confirmation
$reset_requested = ddtt_get( 'reset', '==', '1', 'reset_pw' );
if ( $reset_requested && ddtt_is_dev() ) {

    // Email setup
    $user = wp_get_current_user();
    $email = $user->user_email;
    $subject = 'Reset Password for Developer Debug Tools';
    $site_name = get_bloginfo( 'name' );
    $reset_link_nonce = wp_create_nonce( 'reset_pw_link' );
    $reset_url = add_query_arg( [ 'reset_nonce' => $reset_link_nonce, 'reset_approved' => true ], $current_url );
    $message = 'A request has been made to reset your Developer Debug Tools password on <a href="'.home_url().'">'.$site_name.'</a>. If this wasn\'t you, it would be a good idea to look into why this happened and ignore resetting this password. If it was you, click on the link below to reset your password now:<br><br><a href="'.$reset_url.'">'.$reset_url.'</a><br><br><em>- Developer Debug Tools</em>';
    $from = get_option( 'admin_email' );
    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: Developer Debug Tools <'.$from.'>',
        'Reply-To: '.$from
    ];

    // Email a link to the user
    wp_mail( $email, $subject, $message, $headers );

    // Verification
    ?>
    <div class="notice notice-success is-dismissible">
        <p>An email has been sent to you for confirmation. Please check your email and click on the link to reset your password.</p>
    </div>

    <br><br>
    <h3>Almost there... Please check your email and click on the link to reset your password.</h3>
    <?php

    // Stop processing
    return;
} 

// The form to reset
$enter_new_password = ddtt_get( 'reset_approved', '==', '1', 'reset_pw_link', 'reset_nonce' );

if ( isset( $_POST[ 'pass' ] ) && sanitize_text_field( $_POST[ 'pass' ] ) != '' &&
    isset( $_POST[ 'update_now' ] ) && absint( $_POST[ 'update_now' ] ) == 1 &&
    isset( $_POST[ 'update_nonce' ] ) && wp_verify_nonce( sanitize_text_field( wp_unslash ( $_POST[ 'update_nonce' ] ) ), 'update_pass' ) ) {

    // Update the password
    update_option( DDTT_GO_PF.'pass', sanitize_text_field( $_POST[ 'pass' ] ) );

    // Remove the transients so we can try again
    delete_transient( DDTT_GO_PF.'pass_attempts' );
    delete_transient( DDTT_GO_PF.'pass_attempts_exp' );

    // Verification
    ?>
    <div class="notice notice-success is-dismissible">
        <p>Your security password has been updated. :)</p>
    </div>

    <br><br>
    <h3>You're good to go!</h3>
    <?php

    // Stop processing
    return;

// Update it
} else if ( $enter_new_password ) {

    // Add the reset password field
    ?>
    <br><br>
    <form id="pass-update-form" method="post" action="<?php echo esc_url( $current_url ); ?>">
        <?php wp_nonce_field( 'update_pass', 'update_nonce' ); ?>
        <input type="hidden" name="update_now" value="1">
        <h3><label for="ddtt_pass">Enter New Password</label></h3>
        <div class="password-container">
            <input type="password" id="ddtt_pass" name="pass" value="" style="width: 20rem">
            <span class="view-pass-icon" data-id="ddtt_pass">👁️</span>
        </div>
        <p class="submit">
            <input type="submit" class="button button-primary" value="Reset Password">
        </p>
    </form>
    <?php

    // Stop processing
    return;
}

// Only allow devs to reset
if ( ddtt_is_dev() ) {
    $reset_nonce = wp_create_nonce( 'reset_pw' );
    $reset_url = add_query_arg( [ 
        '_wpnonce' => $reset_nonce, 
        'reset' => 1 
    ], $current_url );
    ?>
    <p><a href="<?php echo esc_url( $reset_url ); ?>">Reset your password</a></p>
    <?php

} else {

    ?>
    <p>You do not have authorization to reset this security password.</p>
    <?php
}