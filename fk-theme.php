<?php
// Theme functions
// Type: <?php print get_post_meta($post->ID, '_fk_type', true) ;

/**
 * Includes the correct page template based on post type
 */
function fk_type_post() {
	if( ! is_single() ){
		return;
	}
	global $fk_settings;
	switch($fk_settings->type){
	case "none":
		// Don't do anything
		return;
		break;
	case "cast":
		include(TEMPLATEPATH . '/single-cast.php');
		exit;
	case "character":
		include(TEMPLATEPATH . '/single-character.php');
		exit;
	case "episode":
		include(TEMPLATEPATH . '/single-episode.php');
		exit;
	}
}

add_action('template_redirect', 'fk_type_post');
?>
