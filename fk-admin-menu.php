<?php
/**
 * Functions that work with head or menu stuff. Behind-the-scenes bits.
 * @package FanKit
 */

add_action('admin_menu', 'fk_generate_menu');
// admin_head is the earliest action where $title is set
add_action('admin_head', 'fk_change_title');
add_action('wp_print_scripts', 'fk_admin_scripts');
add_action('wp_print_styles', 'fk_admin_css');
// Many thanks to http://scompt.com/archives/2007/10/20/adding-custom-columns-to-the-wordpress-manage-posts-screen
add_filter('manage_pages_columns', 'add_custom_column_hook');
add_action('manage_pages_custom_column', 'fill_custom_column', 10, 2);
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
	// which page actually requires this?
	wp_enqueue_script('jquery-ui-accordion');
	// TODO:JS
	if( $fk_settings->type === 'episode' ){
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
	wp_register_script('jquery-1.3', $fk_plugin_url . 'js/jquery-1.3.2.minified.js', array(), '1.3.2');
	wp_register_script('jquery-ui-core-1.7', $fk_plugin_url . 'js/ui/jquery-ui-1.7-core.minified.js', array('jquery-1.3'), '1.7');
	wp_register_script('jquery-ui-accordion', $fk_plugin_url . 'js/ui/jquery-ui-accordion.js', array('jquery-ui-core'), '1.5.3');
	wp_register_script('bgiframe', $fk_plugin_url . 'js/bgiframe/bgiframe.minified.js', array('jquery'), '2.1.1');
	// Script to list episodes so user can mark appearances of a character. Requires "interface" for accordion effect.
	wp_register_script('fk-mark-appearances', $fk_plugin_url . 'js/fk-mark-appearances.js', array('interface', 'jquery-suggest'), '1');
	wp_register_script('global-load', $fk_plugin_url . 'js/global_load-jquery.js', array('jquery'), '1.0');
	// will this fail hard when used with 1.2.6?
	wp_register_script('fk-quote-editor', $fk_plugin_url . 'js/quoteEditor-jquery.class.js', array('jquery', 'jquery-color', 'global-load'), '1.0');
}

/**
 * Add submenus under Write menu
 * These don't have a page-generating function so WP assumes that the file will do the generating.
 */
function fk_generate_menu(){
	global $fk_settings;
	add_pages_page(__('Add New Episode'), __('Add New Episode'), 'edit_pages', $fk_settings->new_episode_link);
	add_pages_page(__('Add New Cast Member'), __('Add New Cast Member'), 'edit_pages', $fk_settings->new_cast_link);
	add_pages_page(__('Add New Character'), __('Add New Character'), 'edit_pages', $fk_settings->new_character_link);
	add_options_page(__('TV Fan Kit Options'), __('TV Fan Kit'), 'edit_pages', $fk_settings->plugin_path . 'fk-options-page.php');

}

function fk_change_title(){
	global $editing, $fk_settings, $action, $title;
	if( ! $editing  // don't change title if we're not editing (eg just looking at list of pages) - this is only set in post/page context
	    || $fk_settings->type === 'none' // don't change title for normal pages
	    || ! $fk_settings->on_page_page ){ // Only change title for pages.
		return false;
	}
	
	if( $action === 'edit' ){
		$prefix = _c('Edit|page');
	} else {
		$prefix = _c('Add New|page');
	}
	$title = "$prefix " . $fk_settings->get_pretty_type($fk_settings->type);
}

/**
 * Add a column hook that we then fill out with add_action('manage_pages_custom_column')
 * @see fill_custom_column()
 * @see _page_row()
 */
function add_custom_column_hook($defaults) {
	$defaults['fk_type'] = __('TV FanKit Post Type');
	return $defaults;
}

/**
 * Provides the content for our custom column.
 */
function fill_custom_column($column_name, $page_id){
	global $fk_settings;
	if( $column_name === 'fk_type' ) {
		$type = fk_get_page_type($page_id);
		$pretty_type = $fk_settings->get_pretty_type($type);
		echo $pretty_type;
	}
}
?>
