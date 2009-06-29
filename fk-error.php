<?php
/**
 * Let's add our own error messages!
 * @package FanKit
 */

/*
 * Use wp_redirect filter on save_post to add GET variables after posting like fk_error
 * so we can then add our error messages to admin_notices
 * $location = apply_filters('wp_redirect', $location, $status);
 *
 */

//add_filter('wp_redirect', 'fk_add_error_arg');

function fk_add_error_arg($location, $status, $err){
	$new_loc = add_query_arg('fk_error', 2, $location);
	return $new_loc;
}
?>
