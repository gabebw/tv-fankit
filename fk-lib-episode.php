<?php
/**
 * Library of episode-related functions.
 * @package FanKit
 */

/**
 * Add an episode.
 */
function fk_add_episode($episode_id, $season, $ep_num){
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
 * Takes either array or query-string style arguments.
 * Valid keys (so far):
 * season
 * ep_num
 * appearances (array)
 * @param int $episode_id The post_id of the episode
 * @param array|string $new The new values.
 * @return bool True if episode updated or created successfully, false otherwise.
 */
function fk_edit_episode($episode_id, $new){
	global $wpdb, $fk_settings;
	if( ! fk_episode_exists($episode_id) ){
		// Episode does not exist, try to create it.
		if( isset($new['season'], $new['ep_num']) ){
			wp_die("Oh no, episode doesn't exist!");
			fk_add_episode($episode_id, $new['season'], $new['ep_num']);
			return true;
		} else {
			return false;
		}
	}
	// Post does exist; check values and update as necessary.
	list($old_season, $old_ep_num) = fk_get_season_ep_num($post_id);
	$old_appearances = fk_episode_get_appearances($post_id);
	$defaults = array('season' => $old_season,
		'ep_num' => $old_ep_num,
		'appearances' => $old_appearances);
	$merged = wp_parse_args($new, $defaults);
	foreach( (array) $merged as $key => $value ){
		switch($key){
		case 'season':
			if( '' !== $value && ! is_numeric($value) ){
				var_dump($value);
				wp_die("Episode's season must be a number.");
				// must be a number or a blank string
				//wp_error("Episode's season must be a number.");
				break;
			}
			$wpdb->query($wpdb->prepare("UPDATE $fk_settings->episode_table SET season = %d WHERE episode_id = %d", $value, $episode_id));
			break;
		case 'ep_num':
			if( '' !== $value && ! is_numeric($value) ){
				//wp_error("Episode's episode number must be a number.");
				break;
			} else {
				$wpdb->query($wpdb->prepare("UPDATE $fk_settings->episode_table SET ep_num = %d WHERE episode_id = %d", $value, $episode_id));
			}
			break;
		case 'appearances':
			$new_appearances = $value;
			// characters in $delete_appearances no longer appear in the current episode.
			$delete_appearances = array_diff($old_appearances, $new_appearances);
			foreach( (array) $delete_appearances as $del ){
				// $del?
				fk_character_delete_appearance_for($character_id, $post_id);
			}
			foreach( (array) $appearances as $character_id ){
				fk_character_add_appearance_for($character_id, $post_id);
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

function fk_delete_page_episode($episode_id){
	global $wpdb, $fk_settings;
	// Delete episode - also deletes appearances by characters.
	$wpdb->query($wpdb->prepare("DELETE FROM $fk_settings->episode_table WHERE episode_id = %d",
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
function fk_get_season_ep_num($episode_id){
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

function fk_episode_get_appearances($episode_id){
	global $wpdb, $fk_settings;
	$appearances = $wpdb->get_col(
		$wpdb->prepare("SELECT character_id FROM $fk_settings->appearance_table WHERE episode_id = %d", $episode_id)
	);
	return $appearances;
}

/**
 * Get all episodes, arranged by season and episode number.
 * @return array Returns an array of objects with episode_id, season, and ep_num variables. Returns empty array if no episode pages exist.
 */
function fk_get_all_episodes(){
	global $wpdb, $fk_settings;
	$all_episodes = $wpdb->get_results($wpdb->prepare("SELECT season, ep_num, episode_id FROM $fk_settings->episode_table ORDER BY season,ep_num ASC"));
	return $all_episodes;
}
?>
