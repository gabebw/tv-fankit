<?php
// Template tags! Yay!

/*************************/
/**** CHARACTER TAGS  ****/
/*************************/
/**
 * Shows list of episodes this character has appeared in
 */
function the_character_appearances(){
	echo get_the_character_appearances();
}

function get_the_character_appearances(){
	global $fk_settings;
	$app_str = '';
	$app_str .= get_the_title() . ' appears in the following episodes:<br />';
	$app_str .= '<ul>';
	$appearances = fk_character_get_appearances(get_the_ID());
	foreach($appearances as $ep_id){
		list($season, $ep_num) = fk_episode_get_season_ep_num($ep_id);
		$app_str .= sprintf('<li><a href="%s">%s</a> (%dx%d)</li>',
			get_permalink($ep_id), get_the_title($ep_id), $season, $ep_num);
	}
	$app_str .= '</ul>';
	return $app_str;
}

/**
 * Prints the actor who plays a character given by $character_id. Defaults to current post.
 * @param $character_id The post id of the character.
 */
function the_actor($character_id = 0){
	echo get_the_actor($character_id);
}

/**
 * Prints a link to the post with the actor who plays a character given by $character_id. Defaults to current post.
 * @param $character_id The post id of the character.
 */
function the_actor_link($character_id = 0){
	echo get_the_actor_link($character_id);
}

function get_the_actor_link($character_id = 0){
	global $post;
	if( $character_id === 0 ){
		$character_id = get_the_ID();
	}
	$actor_id = fk_character_get_actor($character_id);
	$actor = get_the_title($actor_id);
	return sprintf('<a href="%s">%s</a>',
		get_permalink($actor_id), $actor);
}

/**
 * Gets the actor who plays a character given by $character_id. Does not print. Defaults to current post.
 * @param $character_id The post id of the character.
 */
function get_the_actor($character_id = 0){
	global $post;
	if( $character_id === 0 ){
		$character_id = get_the_ID();
	}
	$actor_id = fk_character_get_actor($character_id);
	$actor = get_the_title($actor_id);
	return $actor;
}

/********************/
/**** CAST TAGS  ****/
/********************/
/**
 * Return the characters played by cast member.
 * Pretty formatted according to how many characters the cast member plays.
 * 1 character:
 * "CastMember plays <a href="permalink">Character</a>"
 * so if there's >1 character:
 * "CastMember plays the following characters:
 * <ul>
 * <li><a href="permalink_1">Character_1</a></li>
 * <li><a href="permalink_2">Character_2</a></li>
 * </ul>
 */
function get_the_cast_characters(){
	$characters = fk_cast_get_characters_for(get_the_ID());
	$ret_str = '';
	if( empty($characters) ){
		$ret_str = "This cast member does not play any characters.";
	} elseif( count($characters) === 1 ){
		$ch_id = $characters[0];
		$ret_str = sprintf('%s plays <a href="%s">%s</a>.',
			get_the_title(), get_permalink($ch_id), get_the_title($ch_id));

	} elseif( count($characters) > 1 ){
		$ret_str = get_the_title() . ' plays the following characters:';
		$ret_str .= '<ul>';
		foreach($characters as $chid){
			$ret_str .= '<li>';
			$ret_str .= sprintf('<a href="%s">%s</a>',
				get_permalink($chid), get_the_title($chid));
			$ret_str .= '</li>';
		}
		$ret_str .= '</ul>';
	}
	return $ret_str;
}

/***********************/
/**** EPISODE TAGS  ****/
/***********************/
/**
 * Prints the season and episode number
 */
function the_season_ep_num(){
	echo get_the_season_ep_num();
}

function get_the_season_ep_num(){
	list($season, $ep_num) = fk_episode_get_season_ep_num(get_the_ID());
	$str = "{$season}x{$ep_num}";
	return $str;
}

/**
 * List of characters who appear in this episode
 */
function the_episode_characters(){
	echo get_the_episode_characters();
}

function get_the_episode_characters(){
	$characters = fk_episode_get_characters(get_the_ID());
	$ch_str = '<p>The following characters appear in this episode:<br />';
	$ch_str .= '<ul>';
	foreach($characters as $ch_id){
		$actor_id = fk_character_get_actor($ch_id);
		if( $actor_id === false ){
			$played_by = '';
		} else {
			$played_by = sprintf(' (played by <a href="%s">%s</a>)',
				get_permalink($actor_id), get_the_title($actor_id));
		}
		$ch_str .= sprintf('<li><a href="%s">%s</a>%s</li>',
			get_permalink($ch_id), get_the_title($ch_id), $played_by);
	}
	$ch_str .= '</ul>';
	$ch_str .= '</p>';
	return $ch_str;
}
