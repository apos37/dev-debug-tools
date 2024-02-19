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

<?php 
$links = (new DDTT_RESOURCES)->get_resources();
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