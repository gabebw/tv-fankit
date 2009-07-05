<?php
/**
 * This file contains functions that are used in AJAX callbacks.
 */

/**
 * Gets all quotes and returns them in an ordered JSON array.
 */
function fk_cb_get_all_quotes(){
	global $wpdb;
	header('Content-Type: application/json');
	if( ! function_exists('json_encode') ){
		require_once(ABSPATH.'/wp-includes/js/tinymce/plugins/spellchecker/classes/utils/JSON.php');
		function json_encode($obj){
			$json = new Moxiecode_JSON();
			return $json->encode($obj);
		}
	}

	$season = mysql_real_escape_string($_GET['season']);
	$ep_num = mysql_real_escape_string($_GET['ep_num']);

	// $result is automatically sorted like q1, q2, ..., q10
	$result = $wpdb->get_results($wpdb->prepare('SELECT line_num, quote FROM transcript WHERE season=%d AND ep_num=%d AND quote IS NOT NULL', $season, $ep_num));
	$line_nums = array();
	$anchorsToLines = array();

	foreach($result as $row){
	    $num = $row->line_num;
	    $anchor = explode(',', $row->quote);
	    foreach($anchor as $a){
		if( array_key_exists($a, $line_nums)){
		    array_push($line_nums[$a], "l{$num}");
		} else {
		    $line_nums[$a] = array("l{$num}");
		}
	    }
	}

	function minmax($arr){
	    return array(min($arr), max($arr));
	}

	$line_nums = array_map('minmax', $line_nums);
	uksort($line_nums, 'strnatcmp');
	foreach(array_keys($line_nums) as $k){
	    $arr = $line_nums[$k];
	    $anchorToLines[$k] = array($arr[0], $arr[1]);
	}
	print json_encode($anchorToLines);
	exit();
}

function fk_cb_add_remove_quote(){
	if( ! ( isset($_POST['season'], $_POST['ep_num']) && (isset($_POST['add']) || isset($_POST['remove'])) ) ){
	    print 'incorrect parameters';
	    die();
	}

	global $wpdb;

	$season = mysql_real_escape_string($_POST['season']);
	$ep_num = mysql_real_escape_string($_POST['ep_num']);
	$add = array();
	$remove = array();
	if( isset($_POST['add']) ){
	    $add = array_map('mysql_real_escape_string', $_POST['add']);
	}
	if( isset($_POST['remove']) ){
	    $remove = array_map('mysql_real_escape_string', $_POST['remove']);
	}

	/* we can't just SET quote to anything as long is it isn't NULL;
	 * we need to keep grouping so quote will be comma-separated like q3,q9. 
	 * Note that q9 should come right after q3, so go by the first number then do each comma-separated value after it,
	 * then go to the next line
	 * so for (q3,q9,q16) we'd do:
	 * q3
	 * q9
	 * q16
	 * q4 <-- return to normal order
	 * TODO: in quote.php, DO IT BY LINE, not by quote number, i.e.
	 * get all lines with quote field set and go down them, grouping as you go.
	 * Check off used quote anchors in an array.
	 */

	/** ADD **/
	foreach($add as $idStr){
	    list($anchor, $start, $end) = explode(' ', $idStr);
	    $start = substr($start, 1); // strip off leading 'l'
	    $end = substr($end, 1);
	    // CONCAT_WS(<separator>, str1, str2,...strN) concatenates using <separator>
	    $addQuery = $wpdb->prepare("UPDATE transcript SET quote=CONCAT_WS(',', quote,'%s') WHERE season=%d AND ep_num=%d AND line_num BETWEEN %d AND %d",
		mysql_real_escape_string($anchor),
		$season,
		$ep_num,
		mysql_real_escape_string($start),
		mysql_real_escape_string($end));
	    //print "addQuery: $addQuery\n";
	    $addResult = $wpdb->query($addQuery);
	    if(!$addResult){
		print "failed: $removeQuery";
	    }
	}

	/** REMOVE **/
	foreach($remove as $anchor){
	    $quote_result = $wpdb->get_results($wpdb->prepare("SELECT quote FROM transcript WHERE season=%d AND ep_num=%d AND FIND_IN_SET('%s',quote)",
		$season, $ep_num, $anchor));
	    // $oldToNew is an array with a mapping of old keys -> new keys.
	    // Obviously since we are removing e.g. 'q17', if a row's quote field
	    // is just 'q17', set that to NULL.
	    $oldToNew = array($anchor => 'NULL');
	    foreach($quote_result as $row){
		$oldQuoteStr = $row->quote;
		if( array_key_exists($oldQuoteStr, $oldToNew) ){
		    continue;
		}
		$newQuoteArr = array_flip(explode(',', $oldQuoteStr));
		unset($newQuoteArr[$anchor]);
		if( count($newQuoteArr) === 0){
		    // If it's 0, then it just contains the anchor we're removing
		    // and should have been continue'd above.
		    print "uh-oh\n";
		    print_r($newQuoteArr);
		}
		$newQuoteStr = "'" . implode(',', array_keys($newQuoteArr)) . "'" ;
		$oldToNew[$oldQuoteStr] = $newQuoteStr;
	    }
	    foreach($oldToNew as $old => $new){
		$removeQuery = $wpdb->prepare("UPDATE transcript SET quote=%s WHERE quote='%s'  AND season=%d AND ep_num=%d",
		    $new, $old, $season, $ep_num);
		//print "removeQuery: $removeQuery\n";
		$removeResult = $wpdb->query($removeQuery);
		if(!$removeResult){
		    print "failed: $removeQuery";
		}
	    }
	}
	print "success";
	exit();
}
?>
