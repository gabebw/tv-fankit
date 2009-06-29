<?php
/**
 * Library of character-related functions. Nearly all of them take post_id's as arguments (instead of, say, a name),
 * and return post_id's as well. There are some obvious exceptions, such as fk_get_character_id().
 * @package FanKit
 */

/**
 * Add a character.
 * @param int $character_id The post_id for this character.
 * @param int $cast_id (optional) The id of the actor/actress who plays this character.
 * @param array $appearances (optional) An array of episode_id's that the character appeared in.
 */
function fk_add_character($character_id,  $cast_id = null, $appearances=array()){
	global $wpdb, $fk_settings;
	
	if( fk_character_exists($character_id) ){
		return false;
	} else {
		$name = get_post_field('post_title', $character_id);
		$wpdb->query($wpdb->prepare("INSERT INTO $fk_settings->character_table
			( character_id, name )
			VALUES ( %d, %s )",
				$character_id, $name));
	}
	if( is_int($cast_id) ){ // we check because a character can be added with no cast member set for it
		$wpdb->query($wpdb->prepare("INSERT INTO $fk_settings->cast2character_table
			( cast_id, character_id )
			VALUES ( %d, %d )",
				$cast_id, $character_id));
	}
	foreach( (array) $appearances as $episode_id){
		if( ! is_numeric($episode_id) ){
			continue;
		}
		// todo: is there a way to add all appearances in just one mysql query?
		// maybe have $episode_id optionally be an array
		fk_add_appearance_for($character_id, $episode_id);
	}
}

/**
 * Delete a character. Deletes appearances, relationships to cast members as well. DOES NOT delete actor who played the character.
 * @param int $character_id The id of the character to delete.
 */
function fk_delete_page_character($character_id){
	global $wpdb, $fk_settings;
	$wpdb->query($wpdb->prepare("DELETE FROM $fk_settings->character_table AS ch, $fk_settings->cast2character_table AS cc,
		$fk_settings->appearance_table AS app WHERE ch.character_id = %d AND cc.character_id = %d AND app.character_id = %d ",
		$character_id, $character_id, $character_id));
}

/**
 * Edit a character, eg add an episode, change who plays them, etc. Can pass in either array or query-string, similar to get_pages().
 * Currently the only possible parameters are
 * appearances - pass in an array of episode_id's
 * cast_member - pass in cast_id of actor/actress who plays the character
 * @see get_pages()
 * @param int $character_id
 * @param array|string $new The query-string or array of options to update.
 */
function fk_edit_character($character_id, $new){
	global $wpdb, $fk_settings;
	$defaults = array('appearances' => fk_character_get_appearances($character_id),
		'cast_member' => fk_get_actor_who_plays($character_id));
	$merged = wp_parse_args($new, $defaults);
	foreach( $merged as $field => $value ){
		if( $defaults[$field] === $value || is_null($value) ){
			// Don't do anything if value hasn't changed, or if the value is null.
			continue;
		}
		if( $field === 'appearances' ){
			// Delete episodes that are only in $defaults
			$to_delete = array_diff($defaults['appearances'], $value);
			// Add episodes that are only in $value (ie have not yet been added)
			$to_add = array_diff($value, $defaults['appearances']);
			// add
			foreach( $to_add as $episode_id ){
				fk_add_appearance_for($character_id, $episode_id);
			}
			// delete
			foreach( $to_delete as $episode_id ){
				fk_remove_appearance_for($character_id, $episode_id);
			}
		} elseif( $field === 'cast_member' ){
			$cast_id = $value;
			// Change/Add the cast member who plays this character
			$update_result = $wpdb->query($wpdb->prepare("UPDATE $fk_settings->cast2character_table
				SET cast_id = %d WHERE character_id = %d",
				$cast_id, $character_id));
			if( 0 === $update_result ){
				// Did not update anything, so relationship doesn't exist. Create it.
				$wpdb->query($wpdb->prepare("INSERT INTO $fk_settings->cast2character_table
					( cast_id, character_id )
					VALUES ( %d, %d )",
						$cast_id, $character_id));
			}
		}
	}
}

/* Small utility functions */

/**
 * Get list of episodes that character appeared in.
 * @param int $character_id The character_id corresponding to the character to search the DB for.
 * @returns array An array of episode_id's. If character does not appear in any episodes, returns empty array.
 * @todo Is array("2x1", "2x2") better?)
 */
function fk_character_get_appearances($character_id){
	global $wpdb, $fk_settings;
	$appearances = $wpdb->get_col($wpdb->prepare("SELECT episode_id FROM $fk_settings->appearance_table WHERE character_id = %d",
		$character_id));
	return $appearances;
}

/**
 * Get the actor who plays the character indicated by the id.
 * @param int $id The character_id of the character.
 * @returns int|bool $id Returns character_id of actor who plays the character, or false if character has no assigned actor.
 */
function fk_character_get_actor($character_id){
	global $wpdb, $fk_settings;
	$cast_id = $wpdb->get_var($wpdb->prepare("SELECT cast_id FROM $fk_settings->cast2character_table WHERE character_id =%d",
		$character_id));
	if( is_null($cast_id) ){
		$cast_id = false;
	}
	return $cast_id;
}

/**
 * Check if $character appeared in an episode.
 * @param int $character_id The character_id corresponding to character.
 * @param int $episode_id The episode_id of the given episode.
 * @param int $ep_num Episode number
 * @return bool True if character did appear in given episode, false otherwise.
 */
function fk_character_appears_in($character_id, $episode_id){
	$appearances = fk_character_get_appearances($character_id);
	$did_appear_in = in_array($episode_id, $appearances);
	return $did_appear_in;
}

/**
 * Check if a character exists.
 * @param int $character_id
 * @return bool True if character exists, false otherwise.
 */
function fk_character_exists($character_id){
	global $wpdb, $fk_settings;
	$var = $wpdb->get_var($wpdb->prepare("SELECT character_id FROM $fk_settings->character_table WHERE character_id = %d",
		$character_id));
	$does_exist = ( $var !== null );
	return $does_exist;
}

/**
 * Add a character to a specific episode.
 * @param int $character_id
 * @param int $episode_id
 * @return bool False if character already added to episode or episode or character does not exist, true otherwise.
 */
function fk_add_appearance_for($character_id, $episode_id){
	global $wpdb, $fk_settings;
	if( ! ( fk_character_exists($character_id) && fk_episode_exists($episode_id) ) ){
		return false;
	}
	// Check if character is already in that ep
	if( fk_character_appears_in($character_id, $episode_id) ){
		return false;
	}
	$wpdb->query($wpdb->prepare("INSERT INTO $fk_settings->appearance_table 
		( character_id, episode_id )
		VALUES ( %d, %d )",
		$character_id, $episode_id));
	return true;
}

/**
 * Remove a character from an episode.
 * @return bool False if character did not appear in episode, true otherwise. (I think.)
 */
function fk_remove_appearance_for($character_id, $episode_id){
	global $wpdb, $fk_settings;
	$return = $wpdb->query($wpdb->prepare("DELETE FROM $fk_settings->appearance_table
		WHERE character_id = %d AND episode_id = %d",
		$character_id, $episode_id));
	return $return;
}

/**
 * Get all characters.
 * @return array Returns an array of objects with character_id and name variables. Returns empty array if no character pages exist.
 */
function fk_get_all_characters(){
	global $wpdb, $fk_settings;
	$characters = $wpdb->get_results($wpdb->prepare("SELECT character_id, name FROM $fk_settings->character_table ORDER BY name ASC"));
	return $characters;
}
?>
