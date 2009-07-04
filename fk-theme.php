<?php
// Theme functions
// Type: <?php print get_post_meta($post->ID, '_fk_type', true) ;

function fk_add_post_content($content){
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
// Added at priority 1 so we modify the raw post_content and our stuff
// still gets wptexturize'd etc.
add_filter('the_content', 'fk_add_post_content', 1);
?>
