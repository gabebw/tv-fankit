<?php
class EpisodePrinter {
    private $season;
    private $ep_num;
    private $ep_title;
    private $reserved_names_regex = '/^(scene)|(music)$/i';
    private $is_important_cache; // $is_important_cache['Veronica Mars'] => true, $is_important_cache['Jerkwad'] => false
    private $current_row; // The current mysql row
    private $lines; // mysql result containing all lines for episode
    private $usage_note;

    /**
     * PHP4 compatibility layer.
     */
    function EpisodePrinter($season, $ep_num, $usage_note=''){
	$this->__construct($season, $ep_num, $usage_note);
    }

    /**
     * @param usage_note string  Pass in any usage notes for the page, e.g.
     *			    "Click on a line to edit it."
     */
    function __construct($season, $ep_num, $usage_note=''){
	$this->season = mysql_real_escape_string($season);
	$this->ep_num = mysql_real_escape_string($ep_num);
	$this->usage_note = $usage_note;
	$this->ep_title = mysql_result(mysql_query("SELECT title FROM episode WHERE season=$season AND ep_num=$ep_num"), 0);
	$this->is_important_cache = array();
	$this->current_row = false;
	$this->lines = mysql_query(sprintf('SELECT * FROM transcript WHERE season=%d AND ep_num=%d ORDER BY line_num',
	    $season,
	    $ep_num));
    }

    function print_out(){
	printf("<h1>\"%s\" (%dx%02d)</h1>\n", $this->ep_title, $this->season, $this->ep_num);
	if( $this->usage_note != '' ){
	    echo('<div class="info">'.$this->usage_note.'</div>');
	}
	while( $this->current_row = mysql_fetch_assoc($this->lines) ){
	    $this->print_begin_line_anchor();
	    // flashback/scenebreak are all set by the line
	    // AFTER which they occur, so put them all before the line.
	    if ($this->current_row['flashback'] == 'BEGIN') {
		$this->print_begin_flashback();
	    } elseif( $this->current_row['flashback'] == 'END' ) {
		$this->print_end_flashback();
	    }
	    if( $this->current_row['scenebreak'] == 'TRUE' ) {
		$this->print_scenebreak();
	    }
	    if( ! is_null($this->current_row['quote']) ) {
		$this->print_quote_anchor();
	    }
	    if( ! is_null($this->current_row['ref']) ) {
		$this->print_ref_anchor();
	    }
	    $this->print_character_name();
	    $this->print_dialogue();
	    $this->print_end_line_anchor();
	}
    }

    function set_usage_note($str){
	$this->usage_note = $str;
    }

    function print_begin_line_anchor(){
	printf('<div id="l%s" class="line">', $this->current_row['line_num']);
	printf('<a name="l%s"></a>', $this->current_row['line_num']);
    }

    function print_end_line_anchor(){
	echo('</div><br />'."\n");
    }

    function print_quote_anchor(){
	echo('<a name="'.$this->current_row['quote'].'"></a>' . "\n");
    }

    function print_ref_anchor(){
	echo('<a name="'.$this->current_row['ref'].'"></a>' . "\n");
    }

    function is_internal_dialogue(){
	return ! is_null($this->current_row['internal']);
    }

    function print_character_name(){
	if( $this->is_important_speaker() ){
	    if( $this->is_reserved_name() ){
		// Scene direction or music. Not a real link, but add style
		echo("<a class=\"scene speaker\">{$this->current_row['speaker']}</a>: ");
	    } else {
		// Yes, this is a major castmember - do full linking
		printf('<a href="%s/cast/%s" class="speaker">%s</a>: ',
		    FK_BASENAME,
		    $this->current_row['stripname'],
		    $this->maybe_get_pseudonym());
	    }
	} else {
	    // This is throwaway character.
	    if( $this->is_reserved_name() ){
		echo("<br>ERR: Not in DB, but is reserved name! Fix your db and add a scene direction field!<br>\n");
		// Scene direction or music. Not a real link, but add style
		echo("<a class=\"scene speaker\">{$this->current_row['speaker']}</a>: ");
	    } else {
		// Not an important castmember, not a special name, just a random dude
		echo("<span class=\"throwaway speaker\">{$this->current_row['speaker']}</span>: ");
	    }
	}
    }

    function unescape($str){
	// Undo mysql's escaping for output.
	return str_replace(array('\&', '\\'), array('&', ''), $str);
    }

    function print_dialogue(){
	$css_classes=array('dialogue');
	if( $this->is_internal_dialogue() ){
	    $css_classes []= "internal";
	}
	$css_classes_str=implode($css_classes, ' ');
	$line = $this->unescape($this->current_row['line']);
	echo("<span class=\"{$css_classes_str}\">{$line}</span>");
    }

    function print_scenebreak(){
	// use <hr>?
	echo('<div class="scenebreak"></div>' . "\n");
    }

    function print_begin_flashback(){
	echo('<div class="flashback">' . "\n");
    }
    function print_end_flashback(){
	echo('</div>'."\n");
    }

    function is_important_speaker(){
	if( ! array_key_exists($this->current_row['speaker'], $this->is_important_cache) ){
	    // Check to see if the speaker is important. If they are, they're in the DB
	    $name_result = mysql_query(sprintf('SELECT name FROM cast WHERE name=\'%s\'',
		$this->current_row['speaker'])); 
	    $this->is_important_cache[ $this->current_row['speaker'] ] = is_array(mysql_fetch_assoc($name_result));
	}
	return $this->is_important_cache[ $this->current_row['speaker'] ];
    }

    function is_reserved_name(){
	return preg_match($this->reserved_names_regex, $this->current_row['speaker']);
    }

    /**
     * Returns speaker's pseudonym if it exists or speaker's name if it doesn't.
     */
    function maybe_get_pseudonym(){
	return is_null($this->current_row['pseudonym']) ? $this->current_row['speaker'] : $this->current_row['pseudonym'];
    }


}
?>
