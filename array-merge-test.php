<?php
$a = array('a' => 'old_season', 'b' => 'old_ep_num', 'c' => 'old_title');
$b = array('a' => 'old_season', 'b' => 'new_ep_num', 'c' => 'new_title', 'd' => 'i shouldnt be here');

$c = array_merge($a, $b);
print_r($c);
echo "\n";
$d = array_diff_assoc($c, $a);
print_r($d);
echo "\n";
?>
