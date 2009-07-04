<?php
// Template tags! Yay!

/**** CHARACTER TAGS  ****/
/**
 * Shows list of episodes this character has appeared in
 */
function the_character_appearances(){
	global $fk_settings;
	if( $fk_settings->type !== 'character' ){
		return;
	}
	echo get_the_title() . ' appears in the following episodes:<br />';
	echo '<ul>';
	$appearances = fk_character_get_appearances(get_the_ID());
	foreach($appearances as $ep_id){
		list($season, $ep_num) = fk_episode_get_season_ep_num($ep_id);
		printf('<li><a href="%s">%s</a> (%dx%d)</li>',
			get_permalink($ep_id), get_the_title($ep_id), $season, $ep_num);
	}
	echo '</ul>';
}

/**** EPISODE TAGS  ****/
/**
 * Prints the season and episode number
 */
function the_season_ep_num(){
	echo '<p>';
	list($season, $ep_num) = fk_episode_get_season_ep_num(get_the_ID());
	print("Season: $season<br />");
	print("Episode Number: $ep_num");
	echo '</p>';
}

/**
 * List of characters who appear in this episode
 */
function the_episode_characters(){
	$characters = fk_episode_get_characters(get_the_ID());
	echo 'The following characters appear in this episode<br />';
	echo '<ul>';
	foreach($characters as $ch_id){
		$actor_id = fk_character_get_actor($ch_id);
		if( $actor_id === false ){
			$played_by = '';
		} else {
			$played_by = sprintf(' (played by <a href="%s">%s</a>)',
				get_permalink($actor_id), get_the_title($actor_id));
		}
		printf('<li><a href="%s">%s</a>%s</li>',
			get_permalink($ch_id), get_the_title($ch_id), $played_by);
	}
	echo '</ul>';
}
