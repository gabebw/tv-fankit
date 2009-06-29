<?php
// TODO: add dependencies.
class JsGenerator {
    private $specific_ep;
    private $to_load;
    private $loaded;
    private $registered;

    /**
     * PHP4 compatibility layer.
     */
    function JsGenerator(){
	$this->__construct();
    }
    
    function __construct(){
	$this->specific_ep = isset($_GET['season'], $_GET['ep_num']);
	/**
	 * to_load is an associative array of handle -> jsobj
	 * like ['prototype' -> JS_obj]
	 */
	$this->to_load = array();
	/**
	 * loaded is an array of handles marking stuff that's alread been written out.
	 */
	$this->loaded = array();
	/**
	 * $this->registered is the same type of thing as to_load, but
	 * its objects are not written out. They are simply made available
	 * as dependencies for other scripts.
	 */
	$this->registered = array();
    }

    function generate(){
	$this->add_global_scripts();
	$this->add_mode_specific_ep_scripts();
	$this->write_all();
    }

    function write($handle, $depsLoaded=false){
	if( in_array($handle, $this->loaded) ){
	    return true;
	}
	if( ! array_key_exists($handle, $this->to_load) ){
	    if( ! array_key_exists($handle, $this->registered) ){
		echo '<!-- Could not load library ' . $handle . " (not registered). -->\n";
		return false;
	    } else {
		$jsobj = $this->registered[$handle];
	    }
	} else {
	    $jsobj = $this->to_load[$handle];
	}
	$dephandles = $jsobj->deps;
	if( empty($dephandles) || $depsLoaded === true ){
	    // No deps, or deps are already loaded.
	    if( $jsobj->writeout ){
		require($jsobj->src);
	    } else {
		echo('<script type="text/javascript" src="' . $jsobj->src . '?v=' . $jsobj->version .  '"></script>'."\n");
	    }
	    $this->loaded []= $handle;
	} else {
	    // Yes, deps.
	    foreach($dephandles as $dh){
		$this->write($dh);
	    }
	    // Don't recurse since we loaded all the deps.
	    $this->write($handle, true);
	    //echo('<script type="text/javascript" src="' . $jsobj->src . '?v=' . $jsobj->version .  '"></script>'."\n");
	    $this->loaded []= $handle;
	}
    }

    /**
     * Use add() to register and write it out.
     */
    function add($name, $src, $version='00', $deps=array(), $writeout=false){
	$this->to_load[$name] = new JSobj($name, $src, $version, $deps, $writeout);
    }
    
    /**
     * register() to make a script available as a dependency but not write it out.
     */
    function register($name, $src, $version='00', $deps=array(), $writeout=false){
	$this->registered[$name] = new JSobj($name, $src, $version, $deps, $writeout);
    }

    function write_all(){
	foreach($this->to_load as $handle => $jsobj){
	    $this->write($handle);
	}
    }

    function add_global_scripts(){
	/* Useful stuff */
	$this->add('debug', JS_HTML_PATH . 'debug.js');
	$this->add('utility', JS_PATH . 'utility.js.php', '1', array('global_load-jquery'), true);
	//$this->add('global_load-proto', JS_HTML_PATH . 'global_load.js', '1.0', array('prototype'));
	$this->add('global_load-jquery', JS_HTML_PATH . 'global_load-jquery.js', '1.0', array('jquery'));
	/* prototype */
	$this->register('prototype', JS_HTML_PATH . 'prototype.js', '1.6.0.2');
	/* scriptaculous */
	$this->register('scriptaculous', JS_HTML_PATH . 'scriptaculous/scriptaculous.js', '1.8.1', array('prototype'));
	$this->register('scriptaculous.effects', JS_HTML_PATH . 'scriptaculous/effects.js', '1.8.1', array('scriptaculous'));
	$this->register('scriptaculous.controls', JS_HTML_PATH . 'scriptaculous/controls.js', '1.8.1', array('scriptaculous', 'scriptaculous.effects'));
	$this->register('scriptaculous.builder', JS_HTML_PATH . 'scriptaculous/builder.js', '1.8.1', array('scriptaculous', 'scriptaculous.effects'));
	$this->register('scriptaculous.dragdrop', JS_HTML_PATH . 'scriptaculous/dragdrop.js', '1.8.1', array('scriptaculous'));
	$this->register('scriptaculous.slider', JS_HTML_PATH . 'scriptaculous/slider.js', '1.8.1', array('scriptaculous'));
	/* jquery */
	$this->register('jquery', JS_HTML_PATH . 'jquery-1.3.2.js', '1.3.2');
	$this->register('jquery-min', JS_HTML_PATH . 'jquery-1.3.2.minified.js', '1.3.2');
	$this->register('jquery.color', JS_HTML_PATH . 'jquery.color.js', '1.0', array('jquery'));
	//$this->add('jquery-ui-core-1.6rc6', JS_HTML_PATH . 'jquery-ui-core-1.6rc6.js');
	//$this->add('jquery-ui-all-1.6rc6', JS_HTML_PATH . 'jquery-ui-all-1.6rc6.js');
    }

    function add_mode_specific_ep_scripts(){
	global $action;
	if( $action == 'edit-quote' ){
	    $this->add('quoteEditor-jquery', JS_HTML_PATH . 'quoteEditor-jquery.class.js', '1.0', array('jquery-min', 'jquery.color'));
	    /*
	    $this->add('modalbox-proto', JS_HTML_PATH . 'modalbox.js', '1.6.0', array('prototype', 'scriptaculous.effects'));
	    $this->add('quoteeditor-proto', JS_HTML_PATH . 'quoteEditor.class.js', '0.5', array('utility', 'prototype', 'modalbox-proto'));
	    */
	} elseif( $this->mode == 'ref' ){
	    // empty
	} elseif( $action == 'add-episode' ){
	    // empty
	} elseif( $action == 'edit-episode' ){
	    //$this->add('edit_transcript', JS_HTML_PATH . 'edit_transcript.js', '1.0', array('prototype', 'scriptaculous.controls'));
	    $this->add('jquery.jeditable', JS_HTML_PATH . 'jquery.jeditable.js', '1.6.2', array('jquery'));
	    $this->add('edit_transcript', JS_HTML_PATH . 'edit_transcript.js', '1.0', array('jquery', 'jquery.jeditable'));
	}
    }
}

class JSobj {
    /**
     * Holds a <script> element
     */
    
    var $handle;
    var $src;
    var $version;
    var $deps;
    var $writeout;

    /**
     * @param handle	string	The handle to refer to the script if you want to use it as a dependency.
     * @param src	string	The script's location.
     * @param version	string	The version of the script. Appended to the script name in a query string.
     * @param deps	array	The handles for the scripts that this script requires.
     * @param writeout  bool    Whether or not to include() the script. If set to true, assumes $src is a file path.
     */
    function __construct($handle, $src, $version='00', $deps=array(), $writeout=false){
	$this->handle = $handle;
	$this->src = $src;
	$this->version = $version;
	$this->deps = $deps;
	$this->writeout = $writeout;
    }
}
?>
