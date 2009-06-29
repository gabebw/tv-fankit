<?php
header('Content-Type: application/json');
require(dirname(__FILE__) . '/../includes/admin-setup.php');
if( ! function_exists('json_encode') ){
    require(ADMIN_INC_PATH . '/php-json/json.php');
    $json = new Services_JSON();
    function json_encode($obj){
	return $json->encode($obj);
    }
}

$season = mysql_real_escape_string($_GET['season']);
$ep_num = mysql_real_escape_string($_GET['ep_num']);

// $result is automatically sorted like q1, q2, ..., q10
$result = mysql_query(sprintf('SELECT line_num, quote FROM transcript WHERE season=%d AND ep_num=%d AND quote IS NOT NULL', $season, $ep_num));
$line_nums = array();
$anchorsToLines = array();
while( $row = mysql_fetch_assoc($result) ){
    $num = $row['line_num'];
    $anchor = explode(',', $row['quote']);
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
?>
