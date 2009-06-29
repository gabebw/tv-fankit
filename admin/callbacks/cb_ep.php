<?php
// Callback for InPlaceEditor.
require('../includes/admin-setup.php');
if( !( isset($_POST['season'], $_POST['ep_num'], $_POST['line_num']) && ( isset($_POST['speaker']) || isset($_POST['new_line_text']) ) ) ){
    // Always need season, ep_num, line_num but then can either update speaker or new_line_text (or both)
    die('ep_cb.php: Incorrect parameters.');
}
$season = mysql_real_escape_string($_POST['season']);
$ep_num = mysql_real_escape_string($_POST['ep_num']);
$line_num = mysql_real_escape_string($_POST['line_num']);
$new_line_text = mysql_real_escape_string(htmlentities($_POST['new_line_text'], ENT_QUOTES));

function myurlencode($str){
    $allowed_chars = "A-Za-z0-9 "; // note the space!
    $one = preg_replace("/[^{$allowed_chars}]/", '', $str);
    $two = str_replace(' ', '+', $one);
    return mysql_real_escape_string($two);
}

function unescape($str){
    return str_replace("\\", '', $str);
}

if( isset($_POST['new']) ){
    // Add a new line. Pass in ep_cb.php?new&line_num=43
    // and this line will be the new line 43
    if( ! isset($_POST['speaker']) ){
	die('New line, but no speaker provided.');
    }
    $speaker = mysql_real_escape_string($_POST['speaker']);
    mysql_query('UPDATE transcript SET line_num=line_num+1 WHERE line_num >= ' . $line_num);
    mysql_query(sprintf('INSERT INTO transcript (season, ep_num, speaker, stripname, line, line_num) ' .
			'VALUES (%d, %d, \'%s\', \'%s\', \'%s\', %d)',
	$season, $ep_num, $speaker, myurlencode($speaker), $new_line_text, $line_num));
} else {
    /* single EXISTING line */
    if( isset($_POST['new_line_text']) ) {
	mysql_query(sprintf('UPDATE transcript SET line=\'%s\' WHERE line_num=%d AND season=%d AND ep_num=%d',
	    $new_line_text, $line_num, $season, $ep_num));
	print unescape($new_line_text);
    } elseif( isset($_POST['speaker']) ) {
	mysql_query(sprintf('UPDATE transcript SET speaker=\'%s\',stripname=\'%s\' WHERE line_num=%d AND season=%d AND ep_num=%d',
	    $speaker, myurlencode($speaker), $line_num, $season, $ep_num));
	print unescape($speaker);
    }
}

?>
