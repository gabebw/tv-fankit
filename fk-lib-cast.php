<?php
/**
 * Library of cast-related functions.
 * @package FanKit
 */

/**
 * Add a cast member. Note that the character's name is the name of the Wordpress page corresponding to $cast_id.
 * @param int $cast_id  the page id corresponding to the wordpress page that holds the character.
 * @param array $characters (Optional) An array of characters played by this actor. 
 */
function fk_add_cast($cast_id, $characters=array()){
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
 * @param array $new Specify only the parameters you wish to change. You can use query-string syntax like in get_pages.
 * @see get_pages()
 */
function fk_edit_cast($cast_id, $new){
	global $wpdb, $fk_settings;
	$defaults = array('cast_id' => $cast_id,
		'characters' => fk_cast_get_characters_for($cast_id)
	);
	// http://localhost/wpdocs/WordPress/_wp-includes---functions.php.html#functionwp_parse_args
	$merged = wp_parse_args($new, $defaults);
	// Update cast_id first, since it impacts all other queries
	if( array_key_exists($merged, 'cast_id') && $defaults['cast_id'] !== $merged['cast_id'] ){
		$new_cast_id = $merged['cast_id'];
		$new_name = get_post_field('post_title', $cast_id);
		// Change cast_id, ie point this actor at a different WP post.
		$wpdb->query($wpdb->prepare('UPDATE '.$fk_settings->cast2character_table.' AS cc, '.$fk_settings->character_table.' AS ch
			SET cc.cast_id = %1$d, ch.cast_id = %1$d, cc.name = %2$s WHERE cc.cast_id = %3$d AND ch.cast_id = %3$d',
			$new_cast_id, $new_name, $cast_id));
		$cast_id = $new_cast_id;
		unset($merged['cast_id']);
	}

	foreach( $merged as $field => $value ){
		if( $defaults[$field] === $value ){
			// Don't do anything if value hasn't changed.
			continue;
		}
		if( $field === 'characters' ){
			// Delete characters that are only in $defaults
			$to_delete = array_diff($defaults['characters'], $value);
			$to_delete = implode(',', $to_delete);
			// Add characters that are only in $value
			$to_add = array_diff($value, $defaults['characters']);
			$to_add = implode(',', $to_add);
			// add
			$wpdb->query($wpdb->prepare("UPDATE $fk_settings->cast2character_table SET cast_id = %d WHERE cast_id = $cast_id AND character_id IN %s",
				$value, $to_add));
			// delete by setting cast to 0 - FIXME
			$wpdb->query($wpdb->prepare("UPDATE $fk_settings->cast2character_table SET cast_id = 0 WHERE cast_id = $cast_id AND character_id IN %s",
				$to_delete));
		}
	}
}

/**
 * Delete a cast member.
 * @param int $cast_id The post_id of the character to delete.
 * @param bool $delete_characters (default false) If set to true, deletes characters played by this actor.
 * TODO
 */
function fk_delete_page_cast($cast_id, $delete_characters = false){
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

/**
 * Returns array of objects with cast_id and name properties set, ordered alphabetically by first name.
 */
function fk_get_all_cast(){
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
 * Returns JSON-encoded array of page ids for episodes.
 */
add_action('wp_ajax_fk_ajax_get_episodes', 'wp_ajax_fk_ajax_get_episodes');
function fk_ajax_get_episodes(){
	if( ! function_exists('json_encode') ){
		require( WP_CONTENT_URL . plugin_basename(__FILE__) . '/php-json/json.php');
		function json_encode($obj){
			$json = new Services_JSON();
			return $json->encode($obj);
		}
	}
	$episodes = get_pages('meta_key=_fk_type&meta_value=episode');
	print_r($episodes);
}
?>
