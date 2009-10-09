<?php
/**
 * Useful debug functions.
 * @package FanKit
 */

/**
 * Show actions
 */
function fk_show_a(){
	global $fk_fired_actions, $fk_not_fired_actions;
	echo '<pre>';
	echo "<h4>Fired actions:</h4>";
	foreach( (array) $fk_fired_actions as $a ){
		echo "$a<br/>";
	}
	echo '</pre>';
}

function dbg($x){
	echo '<pre>';
	print_r($x);
	echo '</pre>';
}
?>
