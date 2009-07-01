<div class="wrap">
<h2>TV Fan Kit</h2>
<form method="post" action="options.php">
<?php wp_nonce_field('update-options'); ?>
<?php
//settings_fields('fankit-options');
// a hidden field called page_options containing a comma separated list
// of all the options in the page that should be written on save. 
?>
<input type="hidden" name="page_options" value="fk_completely_uninstall,fk_show_notices,fk_delete_characters" />
<input type="hidden" name="action" value="update" />
<table class="form-table">
<?php /* new row */ ?>
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
printf('<input type="checkbox" id="cu-id" name="fk_completely_uninstall"%s /> ',
	$cu_checked);
_e('Check this box to remove all TV Fan Kit information when TV Fan Kit is deactivated. This <em>will not</em> delete your pages; they will just not have the extra Fan Kit data (characters, seasons, etc.) anymore. If you leave it unchecked (recommended), then TV Fan Kit will keep the information.');
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
printf('<input type="checkbox" id="sn-id" name="fk_show_notices"%s /> ',
	$sn_checked);
_e('Enable helpful getting-started notices when editing pages.');
?>
</label>
</td>
</tr>
<?php /* new row */ ?>
<tr valign="top">
<th scope="row">Delete Characters</th>
<td>
<label for="delete-characters-id">
<?php
printf('<input type="checkbox" id="delete-characters-id" name="fk_delete_characters" value="%s"%s /> ',
	$fk_settings->delete_characters,  $fk_settings->delete_characters ? ' checked = "checked"' : '');
_e('Check this to delete characters when the actor/actress who plays them is deleted. This <em>will not</em> delete the actual Wordpress pages. It will simply remove the TV Fan Kit data for the characters.');
?>
</label>
</td>
</tr>
<?php /* end rows */ ?>
</table>
<p class="submit">
<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
</p>
</form>
</div>
