<?php
/**
 * Functions that work with head or menu stuff. Behind-the-scenes bits.
 * @package FanKit
 */

// Quote callbacks
add_action('wp_ajax_get_all_quotes', 'fk_cb_get_all_quotes');
add_action('admin_post_add_remove_quote', 'fk_cb_add_remove_quote');

add_action('admin_menu', 'fk_generate_menu');
// admin_head is the earliest action where $title is set
add_action('admin_head', 'fk_change_title');
add_action('wp_print_scripts', 'fk_admin_scripts');
add_action('wp_print_styles', 'fk_admin_css');
// Many thanks to http://scompt.com/archives/2007/10/20/adding-custom-columns-to-the-wordpress-manage-posts-screen
add_filter('manage_posts_columns', 'add_custom_column_hook');
add_action('manage_posts_custom_column', 'fill_custom_column', 10, 2);
$fk_plugin_url = trailingslashit( WP_PLUGIN_URL . '/' . dirname( plugin_basename(__FILE__) ) );

/**
 * Add admin scripts for FanKit.
 */
function fk_admin_scripts(){
	global $fk_settings;
	if( $fk_settings->type === 'none' ){
		return;
	}
	fk_admin_register_scripts();
	// TODO:JS
	if( $fk_settings->type === 'episode' ){
		global $post;
		list($season, $ep_num) = fk_episode_get_season_ep_num($post->ID);
		wp_localize_script('fk-quote-editor', 'fkQuoteVars', array(
			'season' => $season,
			'ep_num' => $ep_num,
			'callback_get' => get_bloginfo('wpurl') . '/wp-admin/admin-ajax.php',
			'callback_post' => get_bloginfo('wpurl') . '/wp-admin/admin-post.php'));
		wp_enqueue_script('fk-quote-editor');
	} elseif( $fk_settings->type === 'character' ){
		wp_enqueue_script('fk-mark-appearances');
	}
}

function fk_admin_css(){
	global $fk_plugin_url;
	wp_enqueue_style('fk-style', $fk_plugin_url . 'css/style.css');
}

function fk_admin_register_scripts(){
	global $fk_plugin_url;
	// TODO: register "jquery-ui-dialog"?
	// Script to list episodes so user can mark appearances of a character. 
	// Requires "interface" for accordion effect.
	// "suggest" is jquery suggest
	wp_register_script('fk-mark-appearances', $fk_plugin_url . 'js/fk-mark-appearances.js', array('interface', 'suggest'), '1'); 
	// will this fail hard when used with 1.2.6?
	wp_register_script('fk-quote-editor', $fk_plugin_url . 'js/quoteEditor-jquery.class.js', array('jquery', 'jquery-color'), '1.0');
}

/**
 * Add submenus under Write menu
 * These don't have a page-generating function so WP assumes that the file will do the generating.
 */
function fk_generate_menu(){
	global $fk_settings;
	add_posts_page(__('Add New Episode'), __('Add New Episode'), 'edit_posts', $fk_settings->new_episode_link);
	add_posts_page(__('Add New Cast Member'), __('Add New Cast Member'), 'edit_posts', $fk_settings->new_cast_link);
	add_posts_page(__('Add New Character'), __('Add New Character'), 'edit_posts', $fk_settings->new_character_link);
	add_options_page(__('TV Fan Kit Options'), __('TV Fan Kit'), 'edit_posts', $fk_settings->plugin_path . 'fk-options-page.php');

}

function fk_change_title(){
	global $editing, $fk_settings, $action, $title;
	if( ! $editing // only change title if we're editing (eg not just looking at list of posts) - this is only set in post/page context
	    || $fk_settings->type === 'none' // don't change title for normal pages
	    || ! $fk_settings->on_post_page ){ // Only change title for posts.
		return false;
	}
	
	if( $action === 'edit' ){
		$prefix = _c('Edit|post');
	} else {
		$prefix = _c('Add New|post');
	}
	$title = $prefix . ' ' . $fk_settings->get_pretty_type();
}

/**
 * Add a column hook that we then fill out with add_action('manage_pages_custom_column')
 * @see fill_custom_column()
 * @see _page_row()
 */
function add_custom_column_hook($defaults) {
	$defaults['fk_type'] = __('TV Fan Kit Post Type');
	return $defaults;
}

/**
 * Provides the content for our custom column.
 */
function fill_custom_column($column_name, $post_id){
	global $fk_settings;
	if( $column_name === 'fk_type' ) {
		$type = fk_get_post_type($post_id);
		$pretty_type = $fk_settings->get_pretty_type($type);
		echo $pretty_type;
	}
}
?>
