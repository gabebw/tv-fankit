<?php
// Functions that generate <head> content.
function print_title(){
    global $title;
    if( MODE ){
	$mode_titles = array('quote' => 'Quote', 'ref' => 'Reference', 'transcript' => 'Transcript');
	$title .= ' &raquo; ' . $mode_titles[MODE];
	if( isset($_GET['season'], $_GET['ep_num']) ){
	    $title .= sprintf(" &raquo; %02dx%02d", $_GET['season'], $_GET['ep_num']);
	}
    } 
    echo($title);
}
