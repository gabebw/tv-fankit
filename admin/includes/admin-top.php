<?php
/**
 * This file prints out everything up to the content div, including the <head>.
 * It also sets the constant MODE.
 */
if( ! isset($tab, $action) ){
    die('Incorrect menu parameters.');
}
require_once('admin-setup.php');
require_once('CssGenerator.class.php');
require_once('JsGenerator.class.php');
require_once('functions-html.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="application/xhtml+xml; charset=utf-8" />
<title>Admin | <?php echo get_title(); ?></title>
<?php
$cssgen = new CssGenerator();
$jsgen = new JsGenerator();
$cssgen->generate();
$jsgen->generate();
?>
</head>
<body>
<a href="<?php echo(ADMIN_BASE_HTML_PATH); ?>"><div id="logo"></div></a>
<div id="all">
<ul id="tabs">
<li id="edit"<?php if($tab=="edit"){ echo ' class="selected"'; } ?>><a href="<?php echo(ADMIN_BASE_HTML_PATH); ?>index.php">Edit</a></li>
<li id="site"<?php if($tab=="site"){ echo ' class="selected"'; } ?>><a href="<?php echo(ADMIN_BASE_HTML_PATH); ?>site.php">Site</a></li>
<li id="you"<?php if($tab=="you"){ echo ' class="selected"'; } ?>><a href="<?php echo(ADMIN_BASE_HTML_PATH); ?>you.php">You</a></li>
</ul>
<div id="content">
