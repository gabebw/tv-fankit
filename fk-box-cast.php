<?php
/**
 * Add metaboxes for cast.
 * @package FanKit
 */

/**
 * Add all cast boxes. Called in fk-boxes.php.
 */
function fk_add_cast_boxes(){
	// Mark this ACTOR as being a specific character
	add_action('admin_menu', 'fk_box_link_cast_to_character');
}

function fk_cast_notices(){
	$txt = __("The post title is the actor or actress's name, and the post content is a description of them. To add characters that this character has played or other information, check out the boxes below the writing area.");
	fk_show_basic_notice($txt);
}

/**
 * Add metabox to add a character played by actor/actress being edited.
 */
function fk_box_link_cast_to_character(){
	if( function_exists( 'add_meta_box' )){
		add_meta_box('fk_character_name_id', __("Add Characters Played By This Actor/Actress"), 'fk_box_cb_link_cast_to_character', 'post', 'normal');
	} else {
		// FIXME
	}
}

/**
 * Print out form fields to mark the characters that the actor/actress plays.
 */
function fk_box_cb_link_cast_to_character(){
	global $fk_settings, $post;
	$all_character_posts = fk_character_get_all();

	// Use nonce for verification
	wp_nonce_field('fk_set_character_name', 'fk_set_character_name_nonce');
	echo '<p>' . __('If a character is not listed, you can <a href="'.$fk_settings->new_character_link.'">add it</a> then come back to this post.') . '</p>';
	// FIXME: i think a table would work well - that way we only have to write the header text ("All" etc) once, plus probs easier to parse for user
	echo '<p>';
	if( empty($all_character_posts) ){
		printf(__('No characters exist. Maybe you want to <a href="%s">add one</a>?'),
			$fk_settings->new_character_link);
	} else {
		foreach( (array) $all_character_posts as $character ){
			// TODO: Sort by name
			// TODO:JS accordion (jquery) list of available characters "or manually add character"
			$id = $character->character_id;
			$name = $character->name;
			$css_id = "character_$id";
			$selected = '';
			// TODO:JS javascript autocomplete, with hide-if-js div containing PHP-generated checkboxes
			if( 0 !== $post->ID && $post->ID === fk_character_get_actor($id) ){
				$selected = ' checked="checked"';
			}
			printf('<input type="checkbox"%1$s name="fk_characters[]" value="%2$d" id="%3$s" />',
				$selected, $id, $css_id);
			printf('<label for="%s">%s</label>',
				$css_id, $name);
			printf(' (<a href="%s">'.__('view').'</a> '.__('or').
			       ' <a href="%s">'.__('edit').'</a>)<br />',
				get_permalink($id), get_edit_post_link($id));
		}
		echo '</p>';
	}
}

function fk_save_post_cast($post_id){
	if( $_POST['action'] === 'inline-save' ){
		return;
	}
	check_admin_referer('fk_set_character_name', 'fk_set_character_name_nonce');
	$characters = $_POST['fk_characters']; // array(32, 9, 10)
	if( fk_cast_exists($post_id) ){
		fk_cast_edit($post_id, $characters);
	} else {
		fk_cast_add($post_id, $characters);
	}
}

function fk_delete_post_cast($page_id){
	fk_cast_delete($page_id);
}
?>
