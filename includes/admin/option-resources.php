<!-- Add CSS to this table only -->
<style>
.admin-large-table td {
    vertical-align: top;
}
td.usage code {
    padding: 0;
    margin: 0;
}
</style>

<?php include 'header.php'; ?>

<?php if ( !is_network_admin() ) { ?>
    <form method="post" action="options.php">
        <?php settings_fields( DDTT_PF.'group_resources' ); ?>
        <?php do_settings_sections( DDTT_PF.'group_resources' ); ?>
        <table class="form-table">

            <?php 
            $allowed_html = ddtt_wp_kses_allowed_html();

            echo wp_kses( ddtt_options_tr( 'switch_discord_link', 'I am a member of the <a href="https://discord.gg/VeMTXRVkm5">WordPress Support Server</a>', 'checkbox', ' // Swaps out the discord invite link with the plugin support channel link' ), $allowed_html );
            ?>

        </table>
        <?php submit_button( 'Update' ); ?>
    </form>
    <br><br>
<?php } ?>

<?php 
$DDTT_RESOURCES = new DDTT_RESOURCES();
$links = $DDTT_RESOURCES->get_resources();
?>

<div class="full_width_container">
    <table class="admin-large-table">
        <tr>
            <th style="width: 300px;">Link</th>
            <th style="width: auto;">Description</th>
        </tr>
        <?php
        // Add the hooks
        foreach ( $links as $link ) {

            // Add the row
            ?>
            <tr>
                <td><?php echo '<a href="'.esc_url( $link[ 'url' ] ).'" target="_blank">'.esc_html( $link[ 'title' ] ).'</a>'; ?></td>
                <td><?php echo wp_kses_post( $link[ 'desc' ] ); ?></td>
            </tr>
            <?php
        }
        ?>
    </table>
</div>