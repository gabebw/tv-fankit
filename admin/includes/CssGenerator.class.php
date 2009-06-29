<?php
class CssGenerator {
    private $mode = false;
    private $to_load;

    /**
     * PHP4 compatibility layer
     */
    function CssGenerator($mode){
	$this->__construct($mode);
    }

    function __construct(){
	$this->to_load = array();
    }

    function generate(){
	$this->add_global_css();
	$this->add_mode_specific_css();
	$this->write_all();
    }

    function add($src){
	$this->to_load []= new CSSobj($src);
    }

    function add_global_css(){
	$this->add(CSS_HTML_PATH . 'admin.css');
    }

    function add_mode_specific_css(){
	global $action;
	if( ! isset($_GET['season'], $_GET['ep_num']) ){
	    // We only have extra CSS to load if we're editing a specific
	    // episode. Otherwise we use site-wide CSS which is
	    // hardcoded in.
	    // ...at least for quote mode. We'll see about adding transcripts,
	    // which might have specific CSS but not have season/ep_num set.
	    return false;
	}
	if( $action == 'edit-quote' ){
	    $this->add(CSS_HTML_PATH . 'edit.css');
	    $this->add(CSS_HTML_PATH . 'modalbox.css');
	} elseif( $action == 'ref' ){
	    // empty
	} elseif( $action == 'episode' ){
	    // empty
	}
    }

    function write_all(){
	foreach($this->to_load as $css_obj){
	    $css_obj->write();
	}
    }
}

class CSSobj {
    private $src;
    function __construct($src){
	$this->src = $src;
    }

    function write(){
	echo('<link rel="stylesheet" type="text/css" href="' . $this->src . '" />'."\n");
    }
}
?>
