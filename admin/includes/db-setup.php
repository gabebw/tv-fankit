<?php
if( ! function_exists('die_gracefully') ){
    require_once('functions.php');
    /*
    $err_msg = 'db_setup.php: calling db_setup.php, but die_gracefully does not exist!';
    if( in_array('functions.php', array_map('basename', get_included_files())) ){
	$err_msg .= " [already included functions.php - maybe it's somewhere else?]";
    }
    die($err_msg);
     */
}
// Pretty much just the mysql config.
$host = "127.0.0.1";
$user = "kbell";
$password = "planet";
$db_name = "vmars";

if (! $link = mysql_connect($host, $user, $password) )
{
    // wrap in die_gracefully so we can suppress output in production server.
    die_gracefully(
	"Cannot connect to database server at this time.<br />\n" .
	"Error: " . mysql_error()
    );
}

if(!mysql_select_db($db_name)) {
    die_gracefully(
	"Cannot select database ('{$db_name}') now.<br />\n" .
	"Error: " . mysql_error()
    );
}
?>
