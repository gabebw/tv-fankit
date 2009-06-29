<?php
// Site variables; stuff that will happen on every page.

require_once('siteinfo.php');
require_once('funcs.php'); // for die_gracefully()
require_once('db_setup.php'); // sets up database

// TODO: ref.php is currently the only one without a fallback if empty($_GET)

$set_season_ep = false; // centralizing = good.
$season = false;
$ep_num = false;
$ep_title = false;
// Sanity check for season and ep_num;
// sets $season, $ep_num, $ep_title
if( isset($_GET['season'], $_GET['ep_num']) ) {
    check_season_ep_sanity($_GET['season'], $_GET['ep_num']);
}

// Using SCRIPT_NAME instead of PHP_SELF because given
// /vmars/cast.php/2, PHP_SELF is /vmars/cast.php/2
// while SCRIPT_NAME is /vmars/cast.php
// It still works, though, because switch/case does loose comparison.
switch( $_SERVER['SCRIPT_NAME'] )
{
case('/vmars/index.php'):
    $VMARS_SITEINFO['page']['subname'] = null;
    break;
case('/vmars/cast.php'):
    if( empty($_GET) ||
	!( isset($_GET['n']) || $set_season_ep ) )
    {
	// asked for "/vmars/cast/" (empty) or gave weird input
	$VMARS_SITEINFO['query_type'] = 'all';
	$VMARS_SITEINFO['page']['subname'] = 'All';
    }
    elseif( isset($_GET['n']) ) // Asking for a specific character
    {
	/* Variables are passed in with + signs separating them,
	 * but PHP automagically turns plusses into spaces internally.
	 * So to get the stripname, put them back in.
	 * We use $stripname because actual name is NOT passed in.
	 * Sure, it is for "Veronica Mars",
	 * but 'David "Curly" Moran' is passed in as 'David Curly Moran'
	 */
	$stripname = str_replace(' ','+', $_GET['n']);
	$cast_query = sprintf('SELECT * FROM cast WHERE stripname=\'%s\'',
	    mysql_real_escape_string($stripname));
	$cast_result = mysql_fetch_assoc(mysql_query($cast_query));
	if( $cast_result ) // Yes, it's in the DB
	{
	    $VMARS_SITEINFO['query_type'] = 'character';
	    $VMARS_SITEINFO['page']['subname'] = $cast_result['name'];
	}
	else // not in DB
	{
	    $VMARS_SITEINFO['query_type'] = 'error';
	    $VMARS_SITEINFO['page']['subname'] = "ERROR: \"{$_GET['n']}\" not found";
	}
    }
    elseif( $set_season_ep )
    {
	// not asking for a character,
	// asking for the cast for an ep
	$VMARS_SITEINFO['query_type'] = 'episode';
	$VMARS_SITEINFO['page']['subname'] = sprintf('%dx%02d', $season, $ep_num);
    }
    else {
	// We shouldn't get here; all possibilities should already be
	// accounted for. 
	$VMARS_SITEINFO['query_type'] = 'error';
	$VMARS_SITEINFO['page']['subname'] = "ERROR: No character or episode provided.";
    }
    break;
case('/vmars/ep.php'):
    // checking with isset() instead of just empty($_GET)
    // because what if they set completely random stuff?
    // Then we can just ignore it and give the cast list.
    if( $set_season_ep ) {
	$VMARS_SITEINFO['query_type'] = 'episode';
	$VMARS_SITEINFO['page']['subname'] = sprintf('%dx%02d - %s',
	    $season,
	    $ep_num,
	    $ep_title);
    } else {
	$VMARS_SITEINFO['query_type'] = 'all';
	$VMARS_SITEINFO['page']['subname'] = 'All';
    }
    break;
case('/vmars/ref.php'):
    if( $set_season_ep )
    {
	$VMARS_SITEINFO['query_type'] = 'episode';
	$VMARS_SITEINFO['page']['subname'] = sprintf('%dx%02d - %s',
	    $season,
	    $ep_num,
	    $ep_title);
    }
    elseif ( isset($_GET['cat']) ) // season/ep take precedence if both are set
    {
	$VMARS_SITEINFO['query_type'] = 'category';
	$VMARS_SITEINFO['page']['subname'] = "Category: {$_GET['cat']}";
    }
    else {
	$VMARS_SITEINFO['query_type'] = 'error';
	$VMARS_SITEINFO['page']['subname'] = "ERROR: No episode or category provided.";
    }
    break;
case('/vmars/quote.php'):
    if( empty($_GET) || ! $set_season_ep ) {
	// TODO: have quote rating? Man, I am ripping off the-op.com. :(
	$VMARS_SITEINFO['query_type'] = 'all';
	$VMARS_SITEINFO['page']['subname'] = "All";
    }
    elseif( $set_season_ep ) {
	$VMARS_SITEINFO['query_type'] = 'episode';
	$VMARS_SITEINFO['page']['subname'] = sprintf('%dx%02d - %s',
	    $season,
	    $ep_num,
	    $ep_title);
    }
    break;
}

?>
