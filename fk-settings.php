<?php
/**
 * This contains the FK_settings class which keeps track of various useful settings.
 * @package FanKit
 */

/**
 * class FK_settings
 * Holds various useful settings.
 * @package FanKit
 */
class FK_settings {
	/**
	 * @var string An even more namespaced prefix to ensure no collisions occur. Based on $wpdb->prefix.
	 */
	var $prefix;
	/**
	 * @var string The name of the table containing cast information.
	 */
	var $cast_table;
	/**
	 * @var string The name of the table containing character information.
	 */
	var $character_table;
	/**
	 * @var string The name of the table containing character/episode relation information, eg which characters are in a given ep.
	 */
	var $appearance_table;
	/**
	 * @var string The name of the table containing cast/character relation information, ie which actor plays a given character, and
	 * in which episodes.
	 * Note that a character may be played by many actors (eg Marta on Arrested Development),
	 * and one actor may play many characters (eg SNL). It's cast2character because it rolls off my mental tongue better. :)
	 */
	var $cast2character_table;
	/**
	 * @var int The current version of the plugin's database schema.
	 */
	var $db_version;
	/**
	 * @var array The types of data that we deal in: "episode"/"character"/"cast".
	 */
	var $valid_types;

	/**
	 * @var string Path to top-level directory for this plugin. Has a trailing slash.
	 */
	var $plugin_path;

	/**
	 * @var array A nice way to print out the types so users understand them.
	 * @example Use like $fk_settings->pretty_type['episode']
	 */
	var $pretty_type;

	/**
	 * @var bool Whether or not to remove all metadata and drop tables when uninstalling. Defaults to false.
	 */
	var $completely_uninstall;

	/**
	 * @var bool Determines whether to show helpful (hopefully :) getting-started notices on page editing screen.
	 */
	var $show_notices;

	/**
	 * @var bool Whether we are on a post-editing page.
	 */
	var $on_post_page;
	
	/**
	 * @var bool Whether we are on a page-editing page.
	 */
	var $on_page_page;

	// Link to add a new <foobar>. Not absolute.
	var $new_cast_link;
	var $new_character_link;
	var $new_episode_link;

	/**
	 * Constructor.
	 */
	function FK_settings(){
		global $wpdb, $pagenow;
		$this->prefix = $wpdb->prefix . 'fk_';
		$this->cast_table = $this->prefix . 'cast';
		$this->character_table = $this->prefix . 'character';
		$this->episode_table = $this->prefix . 'episode';
		$this->appearance_table = $this->prefix . 'appearances';
		$this->cast2character_table = $this->prefix . 'cast2character';

		$this->db_version = 0.6;
		$this->plugin_path =  trailingslashit( dirname(plugin_basename(__FILE__)) );
		// types are displayed in the menu metabox in order of $valid_types
		$this->valid_types = array('cast', 'character', 'episode', 'none');
		$this->type = $this->get_current_type();
		$this->pretty_type = array('episode' => __('Episode'),
			'cast' => __('Cast Member'),
			'character' => __('Character'),
			'none' => __('Regular Wordpress Page'));

		$this->completely_uninstall = ( 'on' === get_option('fk_completely_uninstall') );
		$this->show_notices = ('on' === get_option('fk_show_notices'));

		$this->on_post_page = in_array($pagenow, array('post-new.php', 'post.php', 'edit.php'));
		$this->on_page_page = in_array($pagenow, array('page-new.php', 'page.php', 'edit-pages.php'));

		$this->new_cast_link = 'page-new.php?fk_type=cast';
		$this->new_character_link = 'page-new.php?fk_type=character';
		$this->new_episode_link = 'page-new.php?fk_type=episode';
	}

	/**
	 * Check if given type is valid.
	 * @return bool
	 */
	function is_valid_type($type){
		return in_array($type, $this->valid_types);
	}

	function load_default_options(){
		if( ! update_option('fk_completely_uninstall', false) ){
			add_option('fk_completely_uninstall', false);
		}
		if( ! update_option('fk_show_notices', 'on') ){
			add_option('fk_show_notices', 'on');
		}
	}

	/**
	 * Get a pretty printout for a type, eg "Cast Member" instead of "cast".
	 * @param string $type
	 * @return string
	 */
	function get_pretty_type($type){
		if( ! $this->is_valid_type($type) ){
			$type = 'none';
		}
		return $this->pretty_type[$type];
	}

	/**
	 * @return string
	 */
	function get_current_type(){
		$my_fk_type = 'none';
		if( isset($_GET['post']) ){
			$page_id = $_GET['post'];
			$temp_type = fk_get_meta($page_id, 'type');
		} elseif( isset($_GET['fk_type']) ){
			$temp_type  = $_GET['fk_type'];
		}

		if( $this->is_valid_type($temp_type) ){
			$my_fk_type = $temp_type;
		}
		return $my_fk_type;
	}
}

/**
 * @global FK_setting $fk_settings An instance of the FK_settings class.
 */
global $fk_settings;
$fk_settings = new FK_settings();
?>
