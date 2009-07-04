<?php
/**
 * Add global and type-specific metaboxes. The metabox "switchboard" file.
 * @package FanKit
 */

require_once('fk-box-cast.php');
require_once('fk-box-character.php');
require_once('fk-box-episode.php');
add_action('init', 'fk_boxes_init');

/**
 * Add global metaboxe - eg metaboxes that appear for every post.
 */
function fk_boxes_init(){
	global $fk_settings;
	$fk_type = $fk_settings->type;
	add_action('admin_menu', 'fk_box_type');
	if( true === $fk_settings->show_notices ){
		add_action('admin_notices', 'fk_'.$fk_type.'_notices');
	}
	if( 'none' !== $fk_type ){
		call_user_func('fk_add_'.$fk_type.'_boxes');
	}
	// save_post passes post ID to callback function. It fires after WP has inserted the post.
	add_action('save_post', 'fk_save_post');
	// delete_post fires before WP actually deletes the post.
	add_action('delete_post', 'fk_delete_post');
}

/**
 * Notices that are shown for a regular wordpress post.
 */
function fk_none_notices(){
	global $editing;
	if( true === $editing ){
		// Don't show this when we're on the "edit all posts" page
		$pre_txt = __('This is a regular Wordpress post.') . ' ';
		$post_txt = ' ' . __('You can also change the type of existing posts in the "Set Page Type" box under the writing area.');
	}
	$txt = $pre_txt . __('To take advantage of everything that TV Fan Kit offers, try clicking on "Add New Episode", "Add New Cast Member", or "Add New Character" under the Posts menu to the left.') . $post_txt;
	fk_show_basic_notice($txt);
}

function fk_box_type(){
	if( function_exists( 'add_meta_box' )){
		add_meta_box('fk_change_type_id', __("TV Fan Kit - Set Page Type"), 'fk_box_cb_type', 'post', 'normal');
	}
}

/**
 * Callback function for the meta box to change post type.
 * Buttons so that once user selects it, it is changed. This means
 * we don't have to load all metaboxes and hide via javascript.
 */
function fk_box_cb_type(){
	global $fk_settings;
	$fk_type = $fk_settings->type;
	$checked = ' checked="checked"';
	$selected = array(
		'episode' => ($fk_type === 'episode') ? $checked : '',
		'cast' => ($fk_type === 'cast') ? $checked : '',
		'character' => ($fk_type === 'character') ? $checked : '',
		'none' => ($fk_type === 'none') ? $checked : ''
	);
	echo '<p>';
	_e('Select a type and click "Publish" or "Update Page" to change the type of this post. New boxes will appear after you save the new type, so click "Publish" or "Update Page" immediately after changing the type to take advantage of it.');
	echo '<br />';
	// hide-if-js because user can only drag if JS is enabled.
	echo '<span class="hide-if-js">';
	_e("Note: if you drag this box, the type may look like it is deselected. This is fine. Simply re-select your desired type, or leave it alone if you don't want to change the type.");
	echo '</span>';
	echo '</p>';
	printf('<p>%s %s</p>', __('Current type:'), $fk_settings->get_pretty_type($fk_type));
	wp_nonce_field('fk_set_type', 'fk_type_nonce');
	printf('<input id="fk-type-old" type="hidden" name="fk_old_type" value="%s" />', $fk_type);
	foreach( $fk_settings->valid_types as $t){
		if( $t === 'none' && $fk_type !== 'none' ){
			$warning_remove_type = __('(selecting this will remove all FanKit info associated with this post)');
		} else {
			$warning_remove_type = '';
		}
		printf('<input id="fk-type-%1$s" type="radio"%2$s name="fk_type" value="%3$s" />',
			$t, $selected[$t], $t);
		printf('<label for="fk-type-%1$s">%2$s%3$s</label>',
			$t, $fk_settings->get_pretty_type($t), ' ' . $warning_remove_type);
		echo "<br />\n";
	}
}

/* When the post is saved, saves our custom data. Calls each post type's postdata handler function. */
function fk_save_post($post_id){
	global $fk_settings;
	// Check if this is a quick edit. If it is, the metaboxes won't have shown up, so there's nothing for us to do.
	// TODO: episodes actually do parse post content, so somehow check if transcript has changed,
	// and if it has then do stuff - wp supplies the nonce
	if( $_POST['action'] === 'inline-save' ){
		return $post_id;
	}
	// verify this came from the our screen and with proper authorization,
	// because save_post can be triggered at other times
	if( 'page' == $_POST['post_type'] ){
		if ( !current_user_can( 'delete_page', $post_id ))
			return $post_id;
	} else {
		if ( !current_user_can( 'edit_post', $post_id ))
			return $post_id;
	}
	check_admin_referer('fk_set_type', 'fk_type_nonce');
	// Okay, we're authenticated.

	if( $real_post_id = wp_is_post_revision($post_id) ){
		$post_id = $real_post_id;
	}
	// Figure out which type of data we're dealing with.
	$new_fk_type = $_POST['fk_type'];
	// $old_fk_type is set to "none" for posts where it wasn't explicitly set, eg posts written before this plugin was activated
	$old_fk_type = $_POST['fk_old_type'];
	if( $old_fk_type !== 'none' && $new_fk_type === 'none' ){
		// This used to be a fankit post but has since been changed to a regular WP post.
		// FIXME: do we also delete when changing from any FK type to any other FK type?
		fk_delete_meta($post_id, 'type');
		if( function_exists('fk_delete_post_'.$old_fk_type) ){
			call_user_func('fk_delete_post_'.$old_fk_type, $post_id);
		}
	}
	elseif( $fk_settings->is_valid_type($new_fk_type) ){
		fk_set_meta($post_id, 'type', $new_fk_type);
		if( $new_fk_type === $old_fk_type ){
			// If they are NOT equal, then there's no point in handling the postdata,
			// since the metaboxes were not shown for the new type.
			// FIXME: or were they? Javascript!
			// have javascript mark a hidden input showing that it exists, so this function knows what to do
			if( function_exists('fk_save_post_'.$new_fk_type) ){
				call_user_func('fk_save_post_'.$new_fk_type, $post_id);
			}
		}
	}
}

function fk_delete_post($post_id){
	global $fk_settings;
	if($fk_settings->type === 'none' ){
		return;
	}

	// Authenticate
	if( 'page' == $_POST['post_type'] ){
		if ( !current_user_can( 'delete_page', $post_id ))
			return $post_id;
	} else {
		if ( !current_user_can( 'delete_post', $post_id ))
			return $post_id;
	}

	if( $real_post_id = wp_is_post_revision($post_id) ){
		$post_id = $real_post_id;
	}

	call_user_func('fk_delete_post_'.$fk_settings->type, $post_id);
	fk_delete_meta_all($post_id);
}
?>
