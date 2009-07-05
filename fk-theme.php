<?php
// Theme functions
// Type: <?php print get_post_meta($post->ID, '_fk_type', true) ;

function fk_theme_add_post_content($content){
	switch(fk_get_post_type(get_the_ID())){
	case 'episode':
		include(TEMPLATEPATH . '/single-episode-body.php');
		break;
	case 'cast':
		include(TEMPLATEPATH . '/single-cast-body.php');
		break;
	case 'character':
		include(TEMPLATEPATH . '/single-character-body.php');
		break;
	default:
		break;
	}
	return $content;
}

function fk_theme_add_title_content($title){
	if('episode' === fk_get_post_type(get_the_ID())){
		$title = "Episode " . get_the_season_ep_num() . ': ' . $title;
	}
	return $title;
}
// Added at priority 1 so we modify the raw post_content and our stuff
// still gets wptexturize'd etc.
add_filter('the_content', 'fk_theme_add_post_content', 1);
add_filter('the_title', 'fk_theme_add_title_content', 1);
?>
