<?php
/* switchboard for episode */
$tab = 'edit';
$allowed_actions = array('add', 'edit');
$action = 'none';
if( isset($_GET['action']) ){
    if( in_array($_GET['action'], $allowed_actions) ){
	$action = $_GET['action'] . '-episode';
    }
}
require('includes/admin-top.php');
if( $action == 'add-episode' ){
    require("episode-add.php");
}
elseif( $action == 'edit-episode' ){
    require("episode-edit.php");
}
elseif( $action == 'none' ) {
    echo "<h1>Add/Edit Episode</h1>";
    printf('<h3><a href="%s">Add New Episode</a></h3>',
	ADMIN_BASE_HTML_PATH . 'episode.php?action=add');
    echo "\n";
    printf('<h3><a href="%s">Edit Episodes</a></h3>',
	ADMIN_BASE_HTML_PATH . 'episode.php?action=edit');
}
require('includes/admin-bottom.php');
?>
