<?php include 'header.php'; ?>
<?php $allowed_html = ddtt_wp_kses_allowed_html(); ?>
<?php $DDTT_LOGS = new DDTT_LOGS(); ?>

<table class="form-table">
    <?php $DDTT_LOGS->error_logs( 'options.php' ); ?>
</table>