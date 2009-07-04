<?php
// Theme functions
// Type: <?php print get_post_meta($post->ID, '_fk_type', true) ;

/**
 * Includes the correct page template based on page type
 */
function fk_type_page() {
	global $fk_settings;
	switch($fk_settings->type){
	case "none":
		// Don't do anything
		return;
		break;
	case "cast":
		include(TEMPLATEPATH . '/page-cast.php');
		exit;
	case "character":
		include(TEMPLATEPATH . '/page-character.php');
		exit;
	case "episode":
		include(TEMPLATEPATH . '/page-episode.php');
		exit;
	}
}

add_action('template_redirect', 'fk_type_page');
?>
