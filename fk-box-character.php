<?php
/**
 * Add metaboxes for an character page.
 * @package FanKit
 */

// TODO: add meta box saying "The character is in the following episodes"
// "the episode parser adds characters to episodes automatically,"
// "but if you'd like to manually mark the character as being in an episode"
// "simply check the box"
// - have episodes arranged by season
// - do accordion dealie with jQuery so we don't show like 7 seasons at once
//   - one season at a time
// - "You may want to drag the name box up or on the side so you don't
//    have to scroll down"
// 2 things, 2 boxes

// TODO
// - remember, parse ep; this is just extra manual bits
function fk_add_character_boxes(){
	// Mark this CHARACTER as being played by a specific actor
	// TODO
	//add_action('admin_menu', 'fk_box_link_cast_to_character');
	// Mark this CHARACTER as being in a specific episode
	add_action('admin_menu', 'fk_box_add_character_appearances');
	// Mark this character as being played by an actor
	// - maybe wrap in JS so if gets really big it doesn't overwhelm things
	// - maybe wrap in js ONLY if it gets really big, ie track number of actors
	add_action('admin_menu', 'fk_box_add_actor_for_character');
}

function fk_character_notices(){
	global $post;
	if( 0 === $post->ID ){
		$pre_character_txt = __("You are adding a new character.");
	} else {
		$pre_character_txt = __("You are editing a character.");
	}
	$txt = $pre_character_txt . ' ' . __("The page title is the character's name, and the page content is a description of this character. To mark which actor or actress plays this character, which episodes this character appears in, and more, see the boxes below.");
	fk_show_basic_notice($txt);
}

/**
 * Add metabox so user can mark episodes that current character has been in.
 */
function fk_box_add_character_appearances(){
	if( function_exists( 'add_meta_box' )){
		add_meta_box('fk_episode_id', __("TV Fan Kit - Episode Appearances"), 'fk_box_cb_character_appearances', 'page', 'normal');
	}
}

/**
 * Metabox callback function for filling the box with content.
 */
function fk_box_cb_character_appearances(){
	global $fk_settings, $post;
	wp_nonce_field('fk_character_appearance', 'fk_character_appearance_nonce');

	$all_episodes = fk_episode_get_all();
	echo '<p>';
	if( empty($all_episodes) ){
		printf(__('No episodes have been added. Maybe you want to <a href="%s">add one</a>?'),
			$fk_settings->new_episode_link);
		echo '<br />';
		_e("After you've added at least one episode, you can come back here and mark which episodes this character appears in.");
	} else {
		// TODO: select all eps in a season - even w/o JS
		// - place the following in a div with class hide-if-js (handled by WP):
		// "If you check this, all episodes in the season will be added even if others in the season are checked"
		// because if we DO have js, we have an event that (un)checks all the episodes depending on the status of the all input
		//  - have the id/class be guessable
		//  - maybe have a class like "episode-all", with a parsable ID, then if they check a box with class episode-all, we 
		//  parse the id and check all boxes with class "episode-<blah>"
		//echo "<h4>".__('Select each episode in which this character appears.')."</h4>"; // give users some benefit of doubt :)
		foreach( $all_episodes as $ep ){
			$title = get_post_field('post_title', $ep->episode_id);
			$css_id = "episode-{$ep->episode_id}"; // "episode-52" - not using season/ep-num because those might not be unique
			$checked = fk_character_appears_in($post->ID, $ep->episode_id) ? ' checked="checked"' : '';
			echo "<label for=\"$css_id\">";
			// prints out a checkbox with a label like
			// 02x01 - "Normal Is The Watchword"
			printf('<input id="%s" type="checkbox"%s name="fk_appearances[]" value="%d" />',
				$css_id, $checked, $ep->episode_id);
			printf('%02dx%02d - "%s"</label> (<a href="%s">view</a> or <a href="%s">edit</a>)',
				$ep->season,$ep->ep_num, $title, get_permalink($ep->episode_id), get_edit_post_link($ep->episode_id));
			echo '<br />';
		}
	}
	echo '</p>';
}

function fk_box_add_actor_for_character(){
	if( function_exists( 'add_meta_box' )){
		add_meta_box('fk_character_actor_id', __("Mark the Actor/Actress Who Plays This Character"), 'fk_box_cb_add_actor_for_character', 'page', 'normal');
	} else {
		// FIXME
	}
}

function fk_box_cb_add_actor_for_character(){
	global $post, $fk_settings;
	$all_cast = fk_cast_get_all();
	if( 0 === $post->ID ){
		$played_by = false;
	} else {
		$played_by = fk_character_get_actor($post->ID);
	}

	echo '<p>';
	if( empty($all_cast) ){
		printf(__('No actors or actresses have been added. Maybe you want to <a href="%s">add one</a>?'),
			$fk_settings->new_cast_link);
		echo '<br />';
		_e("After you've added a cast member, you can come back here and mark which actor or actress plays this character.");
	} else {
		foreach( (array) $all_cast as $cm ){
			$id = $cm->cast_id;
			$css_id = "actor-$id"; // "actor-21"
			$checked = ($played_by === $id) ? ' checked="checked"' : '';
			echo "<label for=\"$css_id\">";
			printf('<input id="%1$s" type="radio"%2$s name="fk_cast" value="%3$d" />',
				$css_id, $checked, $id);
			printf('%s</label> (<a href="%s">'.__('view').'</a> '.
				__('or') . ' <a href="%s">'.__('edit').'</a>)',
				$cm->name, get_permalink($id), get_edit_post_link($ep->episode_id));
			echo '<br />';
		}
	}
	echo '</p>';
}

function fk_save_page_character($post_id){
	if( $_POST['action'] === 'inline-save' ){
		return;
	}
	check_admin_referer('fk_character_appearance', 'fk_character_appearance_nonce');

	$cast = $_POST['fk_cast'];
	$appearances = $_POST['fk_appearances']; // array
	if( fk_character_exists($post_id) ){
		fk_character_edit($post_id, $cast, $appearances);
	} else {
		// new character
		fk_character_add($post_id, $cast, $appearances);
	}
	return true;
}

function fk_delete_page_character($post_id){
	fk_character_delete($post_id);
}
?>
