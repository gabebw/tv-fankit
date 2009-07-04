<?php
/**
 * Library of cast-related functions.
 * @package FanKit
 */

/**
 * Add a cast member. Note that the character's name is the name of the Wordpress post corresponding to $cast_id.
 * @param int $cast_id  the post id corresponding to the wordpress post that holds the character.
 * @param array $characters (Optional) An array of characters played by this actor. 
 */
function fk_cast_add($cast_id, $characters=array()){
	// TODO: pseudonym?
	global $wpdb, $fk_settings;
	$name = get_post_field('post_title', $cast_id);
	// Add cast member. Right now very basic, but may expand later.
	$x = $wpdb->query($wpdb->prepare("INSERT INTO $fk_settings->cast_table
		( cast_id, name )
		VALUES ( %d, %s )",
			$cast_id, $name));

	// Add a new cast-character relation.
	foreach( (array) $characters as $character_id ){
		$wpdb->query($wpdb->prepare("INSERT INTO $fk_settings->cast2character_table
			( cast_id, character_id )
			VALUES ( %d, %d )",
				$cast_id, $character_id));
	}
}

/**
 * Update a castmember.
 * Valid keys for $new:
 * cast_id	Assign a new id to the castmember. (This also changes the name.)
 * characters	(array) Set the characters played by this castmember. Adds/subtracts as necessary.
 * ...that's it so far.
 * @param int $cast_id
 * @param array $characters Array of character post IDs that this cast member plays.
 */
function fk_cast_edit($cast_id, $characters){
	global $wpdb, $fk_settings;
	$old = array(
		'name' => get_the_title($cast_id),
		'characters' => fk_cast_get_characters_for($cast_id)
	);
	$new = array(
		'name' => $_POST['post_title'],
		'characters' => $characters);
	$merged = wp_parse_args($new, $old);

	foreach( $merged as $field => $value ){
		if( $old[$field] === $value ){
			// Don't do anything if value hasn't changed.
			continue;
		}
		switch($field){
		case 'characters':
			// Delete characters that are only in $old
			$to_delete = array_diff($old['characters'], $value);
			$to_delete = implode(',', $to_delete);
			// Add characters that are only in $value
			$to_add = array_diff($value, $old['characters']);
			$to_add = implode(',', $to_add);
			// add
			$wpdb->query($wpdb->prepare("UPDATE $fk_settings->cast2character_table SET cast_id = %d WHERE cast_id = $cast_id AND character_id IN %s",
				$value, $to_add));
			// delete by setting cast to 0 - FIXME
			$wpdb->query($wpdb->prepare("UPDATE $fk_settings->cast2character_table SET cast_id = 0 WHERE cast_id = $cast_id AND character_id IN %s",
				$to_delete));
			break;
		case 'name':
			$wpdb->query($wpdb->prepare("UPDATE $fk_settings->cast_table SET name = %s WHERE cast_id = %d",
				$value, $cast_id));
			break;
		}
	}
}

/**
 * Delete a cast member.
 * @param int $cast_id The post_id of the character to delete.
 * @param bool $delete_characters (default false) If set to true, deletes characters played by this actor.
 * TODO
 */
function fk_cast_delete($cast_id, $delete_characters = false){
	global $wpdb, $fk_settings;
	// todo: have option for deleting characters
	if( $delete_characters === true ){
		// TODO: does this work?
		// Delete all characters from episodes where they were played by this actor (other actors may have played them in other episodes).
		$wpdb->query($wpdb->prepare("DELETE app FROM $fk_settings->appearance_table AS app
			INNER JOIN app $fk_settings->cast2character_table AS cc
			WHERE app.cast_id = %d AND app.character_id =  cc.character_id", $cast_id));
	}
}

function fk_cast_exists($cast_id){
	global $wpdb, $fk_settings;
	$does_exist = (null !== $wpdb->get_row($wpdb->prepare("SELECT cast_id FROM $fk_settings->cast_table WHERE cast_id = %d", $cast_id)) );
	return $does_exist;
}

/**
 * Returns array of objects with cast_id and name properties set, ordered alphabetically by first name.
 */
function fk_cast_get_all(){
	global $wpdb, $fk_settings;
	$all_cast = $wpdb->get_results($wpdb->prepare("SELECT cast_id, name FROM $fk_settings->cast_table"));
	return $all_cast;
}

/**
 * Get all characters played by actor with id $cast_id.
 * @param int $cast_id
 * @return array Array of character_id's, or empty array if actor does not play any characters.
 */
function fk_cast_get_characters_for($cast_id){
	global $wpdb, $fk_settings;
	$characters = $wpdb->get_col($wpdb->prepare("SELECT character_id FROM $fk_settings->cast2character_table
		WHERE cast_id = %d", $cast_id));
	return $characters;
}

// AJAX
/**
 * Returns JSON-encoded array of post ids for episodes.
 */
add_action('wp_ajax_fk_ajax_get_episodes', 'wp_ajax_fk_ajax_get_episodes');
function fk_ajax_get_episodes(){
	if( ! function_exists('json_encode') ){
		require_once(ABSPATH.'/wp-includes/js/tinymce/plugins/spellchecker/classes/utils/JSON.php');
		function json_encode($obj){
			$json = new Moxiecode_JSON();
			return $json->encode($obj);
		}
	}
	$episodes = get_posts('meta_key=_fk_type&meta_value=episode');
	print_r($episodes);
}
?>
