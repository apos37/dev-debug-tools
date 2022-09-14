<?php include 'header.php'; ?>
<?php $allowed_html = ddtt_wp_kses_allowed_html(); ?>

<br><br>
<h3>DEBUG.LOG</h3>
<table class="form-table">
    <?php ddtt_error_logs( 'options.php' ); ?>
</table>