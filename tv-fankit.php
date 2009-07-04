<?php
/**
 * Main plugin file.
 * @package FanKit
 * @author Gabe B-W
 */
/*
Plugin Name: TV Fan Kit
Plugin URI: http://localhost/
Description: Add actors/actresses, characters, and episodes for a TV show. Automagically handles linking of which characters are in which episodes, which actor plays which character, etc.
Version: 0.5
Author: Gabe Berke-Williams
Author URI: http://people.brandeis.edu/~gbw/


Copyright 2009  Gabriel Berke-Williams (email: gbw@brandeis.edu)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

if( is_admin() ){
	// Useful debug functions.
	require_once('fk-debug.php');
	// General-purpose functions that all page types use.
	// This also loads fk-lib-{character,cast,episode}.php
	require_once('fk-lib.php');
	require_once('fk-settings.php');
	require_once('fk-error.php');
	// (Un)install functions and hooks.
	require_once('fk-install.php');
	// Set up menu, scripts, css.
	require_once('fk-admin-menu.php');
	// Callbacks for scripts
	require_once('fk-callbacks.php');
	if( $fk_settings->on_page_page ){
		// fk-boxes.php loads each type's box file as well
		require_once('fk-boxes.php'); // Add extra data to the page
	}
	// For translation. TODO: sed the __,_e,_c, etc.
	load_plugin_textdomain('fankit', $fk_settings->plugin_path, plugin_basename(__FILE__));
}

?>
