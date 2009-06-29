<div class="wrap">
<h2>TV Fan Kit</h2>
<form method="post" action="options.php">
<?php wp_nonce_field('update-options'); ?>
<input type="hidden" name="action" value="update" />
<?php
// a hidden field called page_options containing a comma separated list
// of all the options in the page that should be written on save. 
?>
<input type="hidden" name="page_options" value="fk_completely_uninstall,fk_show_notices,fk_default_air_time" />
<table class="form-table">
<tr valign="top">
<th scope="row">Completely Uninstall On Deactivation</th>
<td>
<label for="cu-id">
<?php
if( $fk_settings->completely_uninstall ){
	$cu_checked = ' checked="checked"';
} else {
	$cu_checked = '';
}
printf('<input type="checkbox" id="cu-id" name="fk_completely_uninstall"%s />',
	$cu_checked);
_e('Check this box to delete all TV Fan Kit information when TV Fan Kit is deactivated. This WILL NOT delete your posts; they will just not have the extra Fan Kit data anymore. Default: not checked.');
?>
</label>
</td>
</tr>
<?php /* new row */ ?>
<tr valign="top">
<th scope="row">Show Notices</th>
<td>
<label for="sn-id">
<?php
if( $fk_settings->show_notices ){
	$sn_checked = ' checked="checked"';
} else {
	$sn_checked = '';
}
printf('<input type="checkbox" id="sn-id" name="fk_show_notices"%s />',
	$sn_checked);
_e('Check this box to show helpful getting-started notices when editing posts.');
?>
</label>
</td>
</tr>
<?php /* new row */ ?>
<tr valign="top">
<th scope="row">Default Air Time</th>
<td>
<label for="default-air-time-id">
<?php
printf('<input type="text" id="default-air-time-id" name="fk_default_air_time" value="%s" />',
	$fk_settings->default_air_time);
_e('Default air time of the episodes (for example, 6:00pm)');
?>
</label>
</td>
</tr>

</table>
<p class="submit">
<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
</p>
</form>
</div>
