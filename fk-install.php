<?php
/**
 * Install/uninstall functions.
 *
 * @package FanKit
 */

/**
 * Add activation/deactivation hooks.
 */
register_activation_hook(WP_PLUGIN_DIR . '/tv-fankit/tv-fankit.php', 'fk_install');
register_deactivation_hook(WP_PLUGIN_DIR . '/tv-fankit/tv-fankit.php', 'fk_uninstall');

/**
 * Check plugin's database version and create/update table as
 * necessary.
 */
function fk_install(){
	global $fk_settings;
	$fk_settings->load_default_options();
	$current_db_version = $fk_settings->db_version;
	$installed_db_version = get_option('fk_db_version');
	if( $installed_db_version != $current_db_version ){
		if( $installed_db_version === false ){
			// New install.
			add_option('fk_db_version', $current_db_version);
		} else {
			// Old install. (Or at least a different version.)
			update_option('fk_db_version', $current_db_version);
		}
		fk_create_tables();
	}
}

/**
 * @todo
 * use register_deactivation_hook() to print a message about purging
 * all info - cannot be undone, etc.
 * It will NOT remove the posts, but will remove the extra FK data
 * associated with them.
 *
 * If they say yes, then:
 * Remove all custom metadata fields (_fk_nicename, etc.)
 * - track metadata (all prefixed with '_' so user can't edit them)
 * -- do: grep add_post_meta *
 * so far:
 * _character_nicename
 * _fk_type
 *
 * Drop tables
 * ...that's it.
 */
function fk_uninstall(){
	global $fk_settings, $wpdb;
	if( $fk_settings->completely_uninstall === true ){
		delete_option('fk_db_version');
		$tables_arr = array($fk_settings->cast_table, $fk_settings->character_table,
			$fk_settings->episode_table, $fk_settings->appearance_table,
			$fk_settings->cast2character_table);
		$tables = implode(',', $tables_arr);
		$wpdb->query("DROP TABLE IF EXISTS $tables");
		// Get all posts. -1 means no limit.
		$all_posts = get_posts(array('numberposts' => -1));
		foreach( (array) $all_posts as $p ){
			fk_delete_meta_all($p->ID);
		}
	}
}

/*
 * You have to put each field on its own line in your SQL statement.
 * You have to have two spaces between the words PRIMARY KEY and the definition of your primary key.
 * You must use the key word KEY rather than its synonym INDEX 
 */
/**
 * Create all of the tables for FanKit.
 */
function fk_create_tables(){
	global $wpdb, $fk_settings;
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php'); // for dbDelta
	// <blah>_id is the same type as post_id in WP schema.php. It links the WP db and ours.
	/*
	 * imdb_id int(11) default NULL,
	 * pseudonym tinytext,
	 */
	
	// straight out of schema.php
	$charset_collate = '';
	if ( $wpdb->has_cap( 'collation' ) ) {
		if ( ! empty($wpdb->charset) )
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if ( ! empty($wpdb->collate) )
			$charset_collate .= " COLLATE $wpdb->collate";
	}

	// nicename is used in cast/character tables so we can get the post_id from their name
	// - is a cast table even needed? It just holds the post id and (in effect) the post slug.
	// Okay, the nice thing about having our own cast table (vs metadata) is that we can just DROP the table and
	// remove all our stuff, instead of looping over metadata. 
	// by metadata, which isn't that hard, but still.
	// This allows for future expansion of the cast table, too.
	//
	// We have name fields in the cast_table and character_table
	// so that we can select characters alphabetically, which is great
	// when displaying all of them, eg when selecting which characters
	// appear in an episode.
	//
	$sql = "CREATE TABLE $fk_settings->cast_table (
		cast_id bigint(20) unsigned NOT NULL,
		name text NOT NULL,
		PRIMARY KEY  pk_cast_id (cast_id)
	) $charset_collate;
	CREATE TABLE $fk_settings->character_table (
		character_id bigint(20) unsigned NOT NULL,
		name text NOT NULL,
		PRIMARY KEY  pk_character_id (character_id)
	) $charset_collate;
	CREATE TABLE $fk_settings->episode_table (
		season tinyint unsigned NOT NULL,
		ep_num tinyint unsigned NOT NULL,
		episode_id bigint(20) unsigned NOT NULL,
		PRIMARY KEY  pk_episode_id (episode_id),
		KEY  season_ep_num_ix (season,ep_num)
	) $charset_collate;
	CREATE TABLE $fk_settings->appearance_table (
		character_id bigint(20) unsigned NOT NULL,
		episode_id bigint(20) unsigned NOT NULL,
		PRIMARY KEY  pk_character_episode_ix (character_id, episode_id)
	) $charset_collate;
	CREATE TABLE $fk_settings->cast2character_table (
		cast_id bigint(20) unsigned NOT NULL,
		character_id bigint(20) unsigned NOT NULL,
		PRIMARY KEY  pk_cast_character (cast_id, character_id)
	) $charset_collate;
	";
		// episode_id bigint(20) unsigned NOT NULL,
	dbDelta($sql);
}
?>
