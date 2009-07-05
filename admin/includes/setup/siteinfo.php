<?php
// Global site and page variables.
global $VMARS_SITEINFO;
$VMARS_SITEINFO = array();

$VMARS_SITEINFO['debug'] = true; // set to false to suppress debug messages.

$VMARS_SITEINFO['site_name'] = "Earth to Mars";
// $PAGE is an array of information about the current page
// - name: <title> from PAGENAMES
// - subname: stuff to put after the divider in <title>
// so <title> becomes "name <divider> subname"
$VMARS_SITEINFO['page'] = array('name' => '', 'subname' => '');
$PAGENAMES = array('/vmars/index.php' => 'Home',
		   '/vmars/cast.php' => 'Cast',
		   '/vmars/ref.php' => 'References',
		   '/vmars/ep.php' => 'Episodes',
		   '/vmars/quote.php' => 'Quotes');
$VMARS_SITEINFO['page']['name'] = $PAGENAMES[ $_SERVER['SCRIPT_NAME'] ];
// instead of doing the checks twice, just set a variable here to see what 
// type of data to get (or error).
// It's a much more elegant solution than keeping this file's checks in my head.
$VMARS_SITEINFO['query_type'] = null;
?>
