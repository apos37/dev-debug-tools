<?php
/**
 * The password page
 */

// Include the header
include 'header.php';

// Check for enabled
$password_enabled = get_option( DDTT_GO_PF.'enable_pass' );
if ( !$password_enabled ) {
    echo 'Passwords are not enabled. If you would like to enable them, please visit the <a href="'.esc_url( ddtt_plugin_options_path( 'settings' ) ).'">Settings</a> tab under the Security options.';
    return;
}

// Check if password is set
$stored_pass = get_option( DDTT_GO_PF.'pass' );
if ( !$stored_pass ) {
    echo 'Even though passwords are enabled, you do not have a password set. Please visit the <a href="'.esc_url( ddtt_plugin_options_path( 'settings' ) ).'">Settings</a> tab under the Security options to set your password.';
    return;
}

// Build the current url
$page = ddtt_plugin_options_short_path();
$tab = 'pw';
$current_url = ddtt_plugin_options_path( $tab );

// Allowed attempts
$allowed_attempts = 4;
$locked_minutes = false;

// Get attempts and expiration
$attempts_exp = get_transient( DDTT_GO_PF.'pass_attempts_exp' );
if ( $attempts_exp !== false && time() < $attempts_exp ) {

    // Lock out
    $locked_minutes = round( ( $attempts_exp - time() ) / 60 );
    ?>
    <div class="notice notice-error is-dismissible">
        <p>You have exhausted all password attempts. Please wait <?php echo esc_attr( $locked_minutes ); ?> minutes and then try again.</p>
    </div>
    <?php
}

// Check password
if ( !$locked_minutes && 
    isset( $_POST[ 'pass' ] ) && sanitize_text_field( $_POST[ 'pass' ] ) != '' &&
    isset( $_POST[ 'redirect_to' ] ) && sanitize_text_field( $_POST[ 'redirect_to' ] ) != '' &&
    isset( $_POST[ '_wpnonce' ] ) && wp_verify_nonce( sanitize_text_field( wp_unslash ( $_POST[ '_wpnonce' ] ) ), 'enter_pass' ) ) {

    // Redirect to
    $redirect_to = sanitize_text_field( $_POST[ 'redirect_to' ] );
    $redirect_to = esc_url_raw( $redirect_to );
    $redirect_to = urldecode( $redirect_to );

    // Validate URL structure
    if ( !filter_var( $redirect_to, FILTER_VALIDATE_URL ) ) {
        die( 'Invalid redirect URL' );
    }

    // Check the password
    $entered_pass = sanitize_text_field( $_POST[ 'pass' ] );
    if ( wp_check_password( $entered_pass, $stored_pass ) ) {

        // Get the expiration time
        $pass_exp = get_option( DDTT_GO_PF.'pass_exp', 5 );

        // Create transient to keep them logged in
        set_transient( DDTT_GO_PF.'pass_active', true, $pass_exp * MINUTE_IN_SECONDS );
        delete_transient( DDTT_GO_PF.'pass_attempts' );
        delete_transient( DDTT_GO_PF.'pass_attempts_exp' );

        // redirect
        wp_safe_redirect( $redirect_to );
        exit;

    } else {

        // Get attempts
        $attempts = get_transient( DDTT_GO_PF.'pass_attempts' );
        if ( $attempts === false ) {
            $attempts = 0;
        }
        $attempts++;

        // Check attempt count
        if ( $attempts < $allowed_attempts ) {

            // Update attempt count
            set_transient( DDTT_GO_PF.'pass_attempts', $attempts );
            $diff = $allowed_attempts - $attempts;

            // Response
            ?>
            <div class="notice notice-error is-dismissible">
                <p>The password you entered is incorrect. Please try again. You are allowed <?php echo esc_attr( $diff ); ?> more attempts.</p>
            </div>
            <?php

        } else {

            // Seconds for lock
            $lockout_seconds = 20 * MINUTE_IN_SECONDS;
            $exp_time = time() + $lockout_seconds;

            // If there is no lock out, set a new one
            if ( $attempts_exp === false || time() > $exp_time ) {
                set_transient( DDTT_GO_PF.'pass_attempts', $attempts, $lockout_seconds );
                set_transient( DDTT_GO_PF.'pass_attempts_exp', $exp_time, $lockout_seconds );
                $attempts_exp = $exp_time;
            }

            // Locked minutes
            $locked_minutes = round( ( $attempts_exp - time() ) / 60 );

            // Say it ain't so
            ?>
            <div class="notice notice-error is-dismissible">
                <p>The password you entered is incorrect. You have exhausted all attempts. Please wait <?php echo esc_attr( $locked_minutes ); ?> minutes and then try again.</p>
            </div>
            <?php
        }
    }

// Or get redirect from query string
} else {
    $redirect_to = ddtt_get( 'redirect_to' );
}

// Locked out
if ( $locked_minutes ) {
    ?>
    <br><br>
    <h3>Good job, buddy! You couldn't remember your password, and now we have to wait another <?php echo esc_attr( $locked_minutes ); ?> minutes to try again. 😢</h3>

    <?php
    if ( ddtt_is_dev() ) {
        $reset_nonce = wp_create_nonce( 'reset_pw' );
        $reset_url = add_query_arg( [ '_wpnonce' => $reset_nonce, 'reset' => 1 ], ddtt_plugin_options_path( 'pw-reset' ) );
        ?>
        <p>Or <a href="<?php echo esc_url( $reset_url ); ?>">reset your password</a>.</p>
        <?php
    }

// Not locked out
} elseif ( $redirect_to ) {

    // Create the form
    ?>
    <p>The page you are trying to access requires a developer password.</p>
    <br><br>
    <form id="pass-form" method="post" action="<?php echo esc_url( $current_url ); ?>">
        <?php wp_nonce_field( 'enter_pass' ); ?>
        <input type="hidden" name="redirect_to" value="<?php echo esc_html( $redirect_to ); ?>">
        <h3><label for="ddtt_pass">Enter Password</label></h3>
        <div class="password-container">
            <input type="password" id="ddtt_pass" name="pass" value="" style="width: 20rem">
            <span class="view-pass-icon" data-id="ddtt_pass">👁️</span>
        </div>
        <p class="submit">
            <input type="submit" class="button button-primary" value="Open Sesame">
        </p>
    </form>
    <?php

// Missing redirect
} else {
     ?>
    <br><br><h3>No redirect URL provided.</h3>
     <?php
}