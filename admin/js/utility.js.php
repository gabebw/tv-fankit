<?php /* Set global vars */ ?>
<script type="text/javascript">
window.cb_dir = "<?php echo(CALLBACK_HTML_PATH); ?>";
window.season = <?php echo(isset($_GET['season']) ? (int)$_GET['season'] : 'false'); ?>;
window.ep_num = <?php echo(isset($_GET['ep_num']) ? (int)$_GET['ep_num'] : 'false'); ?>;
</script>
