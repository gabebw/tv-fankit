<?php
/**
 * Library of episode-related functions.
 * @package FanKit
 */

/**
 * Add an episode.
 */
function fk_episode_add($episode_id, $season, $ep_num, $characters){
	global $wpdb, $fk_settings;
	// Check for nulls because when passed from POST data we don't actually check for sanity before passing in here.
	$query = $wpdb->prepare("INSERT INTO $fk_settings->episode_table
		( episode_id, season, ep_num )
		VALUES ( %d, %d, %d )",
			$episode_id, $season, $ep_num);
	$wpdb->query($query);
}

/**
 * Updates data for the given episode, if necessary. If episode does not exist, creates it.
 * @param int $episode_id The post_id of the episode
 * @param int $new_season The new season
 * @param int $new_ep_num The new episode number
 * @param array $new_characters The new array of characters that appear in the episode (this is an array of post ids)
 * @return bool|WP_Error True if episode updated or created successfully, WP_Error otherwise
 */
function fk_episode_edit($episode_id, $new_season, $new_ep_num, $new_characters){
	global $wpdb, $fk_settings;
	if( ! fk_episode_exists($episode_id) ){
		return new WP_Error('episode_does_not_exist', "Oh no, episode doesn't exist!");
	}
	// Page does exist; check values and update as necessary.
	list($old_season, $old_ep_num) = fk_episode_get_season_ep_num($episode_id);
	$old_characters = fk_episode_get_characters($episode_id);
	$old = array('season' => $old_season,
		'ep_num' => $old_ep_num,
		'characters' => $old_characters);
	$new = array('season' => $new_season,
		'ep_num' => $new_ep_num,
		'characters' => $new_characters);
	// wp_parse_args will overwrite values in $old with $values in $new
	$merged = wp_parse_args($new, $old);
	foreach( (array) $merged as $key => $new_value ){
		switch($key){
		case 'season':
			if( '' !== $new_value && ! is_numeric($new_value) ){
				var_dump($new_value);
				// must be a number or a blank string
				return new WP_Error('bad_season', "Episode's season must be a number.");
			} else {
				$wpdb->query($wpdb->prepare("UPDATE $fk_settings->episode_table SET season = %d WHERE episode_id = %d", $new_value, $episode_id));
			}
			break;
		case 'ep_num':
			if( '' !== $new_value && ! is_numeric($new_value) ){
				return new WP_Error('bad_ep_num', "Episode's episode number must be a number.");
			} else {
				$wpdb->query($wpdb->prepare("UPDATE $fk_settings->episode_table SET ep_num = %d WHERE episode_id = %d", $new_value, $episode_id));
			}
			break;
		case 'characters':
			// $delete_characters is characters that are only in $old_characters
			$delete_characters = array_diff($old_characters, $new_characters);
			$add_characters = array_diff($new_value, $delete_characters);
			foreach( (array) $delete_characters as $del_id ){
				fk_character_delete_appearance($del_id, $episode_id);
			}
			foreach( (array) $add_characters as $add_id){
				fk_character_add_appearance($add_id, $episode_id);
			}
			break;
		}
	}
}

function fk_episode_exists($episode_id){
	global $wpdb, $fk_settings;
	$does_exist = (null !== $wpdb->get_row($wpdb->prepare("SELECT episode_id FROM $fk_settings->episode_table WHERE episode_id = %d", $episode_id)) );
	return $does_exist;
}

function fk_episode_get_id($season, $ep_num){
	global $wpdb, $fk_settings;
	$id = $wpdb->get_var($wpdb->prepare("SELECT episode_id FROM $fk_settings->episode_table WHERE season = %d AND ep_num = %d",
		$season, $ep_num));
	return $id;
}

function fk_episode_delete($episode_id){
	global $wpdb, $fk_settings;
	// Delete episode - also deletes appearances by characters.
	$wpdb->query($wpdb->prepare("DELETE ep, app FROM $fk_settings->episode_table AS ep, $fk_settings->appearance_table AS app ".
	       	'WHERE ep.episode_id = %d AND app.episode_id = %1$d',
		$episode_id));
	return true;
}

/**
 * Returns 2-element array of season and ep_num.
 * Returns array(false, false) if episode_id does not correspond to an episode.
 * 
 * @param int $episode_id the id of the page.
 * @return array 2-element array($season, $ep_num). Usually we use list() on the return value of this function.
 */
function fk_episode_get_season_ep_num($episode_id){
	global $wpdb, $fk_settings;
	$bad = array(false, false);
	if( fk_get_page_type($episode_id) !== 'episode' ){
		return $bad;
	}
	$season_ep_num_array = $wpdb->get_row($wpdb->prepare("SELECT season, ep_num FROM $fk_settings->episode_table WHERE episode_id=%d", $episode_id), ARRAY_N);
	if( is_null($season_ep_num_array) ){
		return $bad;
	}
	return $season_ep_num_array;
}

function fk_episode_get_characters($episode_id){
	global $wpdb, $fk_settings;
	$characters = $wpdb->get_col(
		$wpdb->prepare("SELECT character_id FROM $fk_settings->appearance_table WHERE episode_id = %d", $episode_id)
	);
	return $characters;
}

/**
 * Get all episodes, arranged by season and episode number.
 * @return array Returns an array of objects with episode_id, season, and ep_num variables. Returns empty array if no episode pages exist.
 */
function fk_episode_get_all(){
	global $wpdb, $fk_settings;
	$all_episodes = $wpdb->get_results($wpdb->prepare("SELECT season, ep_num, episode_id FROM $fk_settings->episode_table ORDER BY season,ep_num ASC"));
	return $all_episodes;
}
?>
