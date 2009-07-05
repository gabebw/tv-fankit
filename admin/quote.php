<?php
$tab = 'edit';
require 'includes/admin-setup.php';

if( editing_specific_episode() ){
    $action = 'edit-quote';
    require 'includes/admin-top.php';
    echo <<<HTML
<div id="modebox">
<p>Status: <span id="status">[empty]</span></p>
<p><input id="undo" type="button" value="Undo last selection" /></p>
</div>
HTML;
    $ep_printer->print_out();
    } else {
	$action = 'select-quote';
	require 'includes/admin-top.php';
	echo '<h1>Select Episode</h1>';
	print_episode_list();
    }
?>
