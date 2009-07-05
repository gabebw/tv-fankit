<?php
function die_gracefully($msg='[no message provided]') {
    echo("<br />* Failing hard: {$msg}<br />\n");
    require(ADMIN_INC_PATH . 'admin-bottom.php');
    die();
}

// I use this so damn much.
function debug($var) {
    echo '<pre>';
    print_r($var);
    echo '</pre>';
}

function debug_mysql($result) {
    // Check result
    // This shows the actual query sent to MySQL, and the error.
    // Useful for debugging.
    if (!$result) {
	die_gracefully( mysql_errno().': '. mysql_error() );
    }
}

function check_season_ep_sanity($maybe_season, $maybe_ep_num){
    // Sanity check for season and ep_num;
    // sets $season, $ep_num, $ep_title
    global $season, $ep_num, $ep_title, $set_season_ep;
    // $season, $ep_num, $ep_title (below) are always available.
    if( ! ( is_numeric($maybe_season) && is_numeric($maybe_ep_num) ) ){
	return false;
    }
    $season = mysql_real_escape_string((int)$maybe_season);
    $ep_num = mysql_real_escape_string((int)$maybe_ep_num);

    // Let's make those $_GET vars valid
    // If no season, ep, category, or name is provided, then
    // the user is probably lost.
    // Or trying to mess with the site.
    // FIXME - ask user for highest season? - make it optional
    if( $season <= 0 || $ep_num <= 0 ) {
	die_gracefully('season or ep is not sane.');
    }

    $ep_title_query = sprintf('SELECT title FROM episode WHERE season=%d AND ep_num=%d',
	$season,
	$ep_num);
    $ep_title_result = mysql_fetch_row(mysql_query($ep_title_query));
    $ep_title = $ep_title_result[0]; // no need to escape; got from mysql
    
    $set_season_ep = true;
}

function print_episode_list(){
    /**
     * Prints links to all episodes from all seasons using the current page's action.
     * Technically, it prints links to the current URL and appends "&season=X&ep_num=Y" for each episode,
     * with each link having the appropriate episode's title.
     * So each page should show eps when season/ep_num aren't set and should edit them when they are.
     */
    // A little hack so I can use concatenatation in a default argument.
    $args = func_get_args();
    $file = null;

    if( empty($args) ){
	// SCRIPT_NAME and QUERY_STRING are unaffected by mod_rewrite.
	$file = $_SERVER['SCRIPT_NAME'] . "?"; // e.g. "ep.php?"
	if( ! empty($_SERVER['QUERY_STRING']) ){
	    $file .= $_SERVER['QUERY_STRING'] . '&';
	} 
    } else {
	$file = $args[0];
	$qs_set = (strpos($file, '?') !== false);
	// Allow for already-set query string in $file
	$file .= ( $qs_set ? '&' : '?' );
    }
    $season_result = mysql_query('SELECT season FROM episode');
    while($seasonrow = mysql_fetch_assoc($season_result)){
	$myseason = $seasonrow['season'];
	echo("<h2>Season {$myseason}</h2>\n");
	$season_result = mysql_query("SELECT * FROM episode WHERE season={$myseason} ORDER BY ep_num");
	if( mysql_num_rows($season_result) === 0 ) {
	    // This should NEVER happen.
	    echo("Sorry, no episodes in Season {$myseason} yet. (Bug!!)");
	} else {
	    echo('<ul>');
	    while ( $row = mysql_fetch_assoc($season_result) ){
		echo('<li>');
		printf('<a href="%sseason=%02d&ep_num=%02d">%02dx%02d - %s</a>',
		    $file,
		    $myseason,
		    $row['ep_num'],
		    $myseason,
		    $row['ep_num'],
		    $row['title']);
		echo('</li>');
		echo("\n");
	    }
	    echo('</ul>');
	}
    }
}

/**
 * Check whether we are editing a specific episode,
 * or whether we should instead show a list of episodes
 * to edit.
 * If we are in a specific ep, sets globals $season and $ep_num.
 */
function editing_specific_episode(){
    global $season, $ep_num;
    if( isset($_GET['season'], $_GET['ep_num']) ){
	$season = mysql_real_escape_string($_GET['season']);
	$ep_num = mysql_real_escape_string($_GET['ep_num']);
	return true;
    } else {
	return false;
    }
}

function get_title(){
    global $action, $season, $ep_num;
    list($myaction, $mysubaction) = explode('-', $action);
    $title = '';
    if( $myaction == 'edit' ){
	switch ($mysubaction) {
	case 'quote':
	    $title = "Edit Quotes";
	    if( editing_specific_episode() ){
		$title .= sprintf(' for Episode %dx%02d', $season, $ep_num);
	    }
	    break;
	case 'episode':
	    $title = "Edit Episode";
	    if( editing_specific_episode() ){
		$title .= sprintf(' %dx%02d', $season, $ep_num);
	    }
	    break;
	case 'ref':
	    $title = "Edit References";
	    if( editing_specific_episode() ){
		$title .= sprintf(' for Episode %dx%02d', $season, $ep_num);
	    }
	    break;
	default:
	    $title = "Edit";
	    break;
	}
    }
    elseif( $myaction == 'add' ){
	if( $mysubaction == 'episode' ){
	    // This is the only one with an add
	    $title = "Add Episode";
	}
    }
    elseif( $tab == 'site' ){
	$title = 'Edit Site Info';
    }
    elseif( $tab == 'you' ){
	$title = 'Edit You';
    }
    return $title;
}

function normalize_slashes($str){
    return str_replace('\\', '/', $str);
}

function get_html_base_path($str){
    /**
     * Converts a filesystem path to a webserver path.
     * e.g. 'C:\Program Files\Apache Group\Apache2\htdocs\relax\admin\includes'
     *    -> '/relax/admin/includes/'
     * The returned string always has a trailing slash.
     * @param $str  The string to be converted.
     */
    // $toStrip is what to remove from the filesystem path.
    $toStrip = dirname(dirname(ADMIN_BASE_PATH));
    $str = normalize_slashes($str);
    $path = str_replace($toStrip, '', $str);
    return $path;
}
?>
