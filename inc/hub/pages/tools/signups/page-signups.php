<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

$signups = Signups::get_signups();
usort( $signups, function( $a, $b ) {
    return (int) $b[ 'signup_id' ] <=> (int) $a[ 'signup_id' ];
} );
?>

<div id="ddtt-page-title-section">
    <div id="ddtt-page-title-left">
        <h2><?php esc_html_e( 'Signups', 'dev-debug-tools' ); ?></h2>
        <p>
          <strong><?php esc_html_e( 'What are signups?', 'dev-debug-tools' ); ?></strong>
          <?php esc_html_e( 'Signups are requests from users to create an account on your website. They help you manage user registrations and keep track of new users. Sometimes a verification link stops working and you need to clear the signup for a user.', 'dev-debug-tools' ); ?>
        </p>
    </div>
</div>

<section id="ddtt-tool-section" class="ddtt-signups ddtt-section-content">
    <h3><?php echo esc_html__( 'Total # of Signups:', 'dev-debug-tools' ); ?> <span id="ddtt-total-signups"><?php echo esc_html( count( $signups ) ); ?></span></h3>

    <table class="ddtt-table">
        <thead>
            <tr>
                <th><?php echo esc_html__( 'Signup ID', 'dev-debug-tools' ); ?></th>
                <th><?php echo esc_html__( 'Login', 'dev-debug-tools' ); ?></th>
                <th><?php echo esc_html__( 'Email', 'dev-debug-tools' ); ?></th>
                <th><?php echo esc_html__( 'Registered', 'dev-debug-tools' ); ?></th>
                <th><?php echo esc_html__( 'Activated', 'dev-debug-tools' ); ?></th>
                <th><?php echo esc_html__( 'Activation Key', 'dev-debug-tools' ); ?></th>
                <th>
                    <?php 
                        if ( is_plugin_active( 'gravityforms/gravityforms.php' ) ) {
                            echo esc_html__( 'Gravity Forms Activation Link', 'dev-debug-tools' ); 
                        }
                    ?>
                </th>
                <th style="width: 100px; text-align: right; padding-right: 2rem;"><?php echo esc_html__( 'Clear', 'dev-debug-tools' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach( $signups as $key => $signup ) { ?>
                <tr data-key="<?php echo esc_attr( $signup[ 'signup_id' ] ); ?>">
                    <td><span class="ddtt-highlight-variable"><?php echo esc_html( $signup[ 'signup_id' ] ); ?></span></td>
                    <td><?php echo esc_html( $signup[ 'user_login' ] ); ?></td>
                    <td><?php echo esc_html( $signup[ 'user_email' ] ); ?></td>
                    <td><?php echo esc_html( Helpers::convert_timezone( $signup[ 'registered' ] ) ); ?></td>
                    <td><?php echo esc_html( $signup[ 'active' ] ? Helpers::convert_timezone( $signup[ 'activated' ] ) : __( 'No', 'dev-debug-tools' ) ); ?></td>
                    <td><?php echo esc_html( $signup[ 'activation_key' ] ); ?></td>
                    <td>
                        <?php
                        $activation_link = false;
                        if ( is_plugin_active( 'gravityforms/gravityforms.php' ) ) {
                            $activation_link = home_url( '?gfur_activation=' . $signup[ 'activation_key' ] );
                        }
                        if ( $activation_link ) {
                            echo '<a href="' . esc_url( $activation_link ) . '" target="_blank">' . esc_html( $activation_link ) . '</a>';
                        }
                        ?>
                    </td>
                    <td style="text-align: right;"><a class="ddtt-clear-signup ddtt-button" href="#"><?php echo esc_html__( 'Clear', 'dev-debug-tools' ); ?></a></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</section>