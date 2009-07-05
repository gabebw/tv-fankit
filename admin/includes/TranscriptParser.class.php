<?php
// TODO: all the FIXME's, and password-protect this form so that
// only admins can get to it.
// and call rachael.
// and email dean godsoe.
/*
cast table is for general info about a character/actor
appearance table is for getting // of episodes they have appeared in, what their
pseudonym was in those eps

cast table is for getting the whole picture;
appearance is for getting info about what they did in a particular ep
 */

//require('cast-nicknames.php'); // sets $GLOBALS['castHash']
//require('actors.php'); // sets $GLOBALS['actorHash']
require 'db-setup.php';

$GLOBALS['castHash'] = array();
$GLOBALS['actorHash'] = array();

class TranscriptParser {
    private $fpath;
    /* $RESERVED_NAMES contains names that are in the script,
     * but should never be linked.
     * e.g. music starts playing and it's put in as 
     * "Music: Somebody Told Me by The Killers"
     * Obviously it shouldn't be linked to a cast page.
     */
    private $RESERVED_NAMES = array('SCENE', 'Scene', 'MUSIC', 'Music');
    private $season;
    private $ep_num;
    private $handle;
    private $line_num;
    private $openquote;
    private $qcounter;
    private $scenebreak;
    private $flashback;
    private $internal;
    private $quote_anchor;

    /**
     * PHP4 compatibility layer.
     */
    function TranscriptParser($fpath, $season, $ep_num){
	$this->__construct($fpath, $season, $ep_num);
    }
    
    public function __construct($fpath, $season, $ep_num){
	/* Pass in null for $season and $ep_num to have TranscriptParser
	 * check the first line of the file for e.g. "s2e4". 
	 */
	$this->fpath = $fpath; // for later renaming

	$this->handle = fopen($this->fpath, "r+");
	if( $season == null && $ep_num == null ){
	    // first line should be like "s1e2" (case-insensitive)
	    if( preg_match('/s(\d+)e(\d+)/i', fgets($this->handle, 1024), $matches) ){
		$this->season = mysql_real_escape_string((int)$matches[1]);
		$this->ep_num = mysql_real_escape_string((int)$matches[2]);
		if( $this->season === 0 || $this->ep_num === 0 ){
		    // Strings when cast to int are 0. Or maybe it's actually 0.
		    // Either way, it's invalid.
		    die('Season or Ep. Num is 0');
		}
	    } else {
		die('Season/Episode Number string not in first line of file');
	    }
	} else {
	    $this->season = mysql_real_escape_string($season);
	    $this->ep_num = mysql_real_escape_string($ep_num);
	}
	$this->line_num = 1;
	$this->openquote = false;
	$this->qcounter = 0; // Counter for quote anchors.
	$this->scenebreak = 'NULL';
	$this->flashback = 'NULL';
	$this->internal = 'NULL'; // Is the current line an internal thought?
	$this->quote_anchor = 'NULL';
	// pic format: [pic=$pic_name]; no need to put in directory
	// @picbase = "C:/Documents and Settings/Gabe/Desktop/veronica mars/transcripts/snapshots/#{base}"
    }

    private function check_if_ep_exists(){
	$result = mysql_query(sprintf("SELECT * FROM episode WHERE season=%d AND ep_num=%d",
	    $this->season, $this->ep_num));
	return mysql_num_rows($result) > 0;
    }

    private function cleanup(){
	fclose($this->handle);
    }

    private function tri($val){
	return (strlen($val) > 0 ? "'".mysql_real_escape_string($val)."'" : 'NULL');
    }

    private function get_sort_key( $name ){
	// MySQL's sort puts punctuation after letters, and I don't like that.
	// So we do ORDER BY sortkey in the query and set the sortkey here.
	$name = strtoupper($name);
	// Get ASCII code for first three letters; numbers' codes < letters
	$sortkey = implode('', unpack('C*', substr($name, 0, 3)));
	if( preg_match('/[A-Z0-9]/', substr($name, 0, 1)) ){
	    return $sortkey;
	} else {
	    // Put non-alphanumeric chars before alphanumeric chars
	    return $sortkey * -1;
	}
    }

    private function add_to_cast($name, $pseudonym){
	if( $actorInfo = array_search($name, $GLOBALS['actorHash']) ){
	    list($actorname, $imdb_id) = $actorInfo;
	    $actorname = "'".mysql_real_escape_string($actorname)."'";
	} else {
	    $actorname = $imdb_id = "NULL";
	}
	$stripname = $this->myurlencode($name);
	$sortkey = $this->get_sort_key($name); // can't quote; Fixnum
	$myname = mysql_real_escape_string( $name );
	$mypseudonym = $this->tri($pseudonym);
	// Add the character name to the DB
	mysql_query(sprintf("INSERT INTO cast (actor, imdb_id, name, pseudonym, stripname, sortkey) " .
			    "VALUES (%s, %s, '%s', %s, '%s', %d)",
				$actorname, $imdb_id, $myname, $mypseudonym, $stripname, $sortkey));
    }

    private function add_cast_pseudonym($name, $pseudonym){
	$currentPseudonymStr = mysql_result(mysql_query("SELECT pseudonym FROM cast WHERE name='".mysql_real_escape_string($name)."' AND pseudonym IS NOT NULL"), 0);
	$currentPseudonymList = explode(',', $currentPseudonymStr);
	if (! in_array($pseudonym, $currentPseudonymList) ){ // make sure it's a NEW pseudonym
	    $newPseudonym = $currentPseudonymStr . "," . $pseudonym;
	    $updatePseudonymQuery = sprintf("UPDATE cast SET pseudonym='%s' WHERE name='%s'",
					   mysql_real_escape_string($newPseudonym),
					   mysql_real_escape_string($name));
	    mysql_query($updatePseudonymQuery);
	}
    }

    private function myurlencode($str){
	// Doesn't really urlencode; rather, strips out non-allowed chars.
	// This is an easy way to pass in cast names via GET and not have
	// browsers choke. Of course, I could *actually* urlencode, but this
	// way the URLs are guessable: e.g. "David+Curly+Moran". Easy
	// enough for users to figure out. The +'s aren't even necessary.
	// These are the technically, RFC-approved allowed chars:
	// allowed_chars = "A-Za-z0-9$-_.+!*'(),"
	// And these are what I'm allowing to make the URL guessable:
	$allowed_chars = "A-Za-z0-9 "; // note the space!
	$one = preg_replace("/[^{$allowed_chars}]/", '', $str);
	$two = str_replace(' ', '+', $one);
	return mysql_real_escape_string($two);
    }

    private function add_title_meaning($title, $title_meaning){
	mysql_query(sprintf('INSERT INTO episode (season, ep_num, title, title_meaning) ' .
	"VALUES (%d, %d, '%s', %s )",
			      $this->season,
			      $this->ep_num,
			      mysql_real_escape_string($title),
			      $this->tri($title_meaning)
			   ));
    }

    private function quote_test($line){
	// Test for quote.
	// returns true if current line opens/closes quote, false otherwise.
	// TODO: have only part of the line be quotable.
	if( preg_match('/^\[Q\]$/', $line) ){
	    if($this->openquote == true){
		// this is a closing [Q]
		$this->openquote = false;
	    } else {
		// this is an opening [Q]
		$this->qcounter += 1;
		$this->quote_anchor = "'".mysql_real_escape_string('q'.$this->qcounter)."'";
		$this->openquote = true;
	    }
	    return true;
	} else {
	    return false;
	}
    }
    
    private function parse_title($line){
	// Get title and episode title meaning
	// format:
	// TITLE: 2x01 - Normal is the watchword [meaning=blah]
	// Wow, PHP's preg matching blows hard.
	// Maybe you should fucking recognize and parse non-greedy selectors
	// like EVERY OTHER PROGRAM YOU WHORE.
	// Oh well. I'll just use two regexes, I guess.
	$title_meaning = '';
	$this->add_title_meaning('Welcome To The Hellmouth', $title_meaning);
	return true;
    }

    private function add_to_appearance($name, $pseudonym){
	$stripname = $this->myurlencode($name);
	mysql_query(sprintf('INSERT INTO appearance ' .
			    '(season, ep_num, name, stripname, pseudonym) ' .
			    "VALUES (%d, %d, '%s', '%s', %s)",
			    $this->season, $this->ep_num,
			    mysql_real_escape_string($name),
			    $stripname,
			    $this->tri($pseudonym)));
    }

    public function parse() {
	if( $this->check_if_ep_exists() === true ){
	    printf('<span style="color: red;">Episode %02dx%02d exists, skipping.</span><br>', $this->season, $this->ep_num);
	    $this->cleanup();
	    return false;
	}

	while( ! feof($this->handle) ) {
	    // until newline or 4096-1 bytes have been read, whichever comes first
	    $line = fgets($this->handle, 4096);
	    print "$line\n";
	    if($line == "\n" || $line == "\r\n"){
		continue;
	    }
	    $line = rtrim($line);
	    if($this->openquote == false){
		$this->quote_anchor = 'NULL';
	    }
	    if( $this->parse_title($line) ){
		continue;
	    }
	    if( $this->quote_test($line) ){
		continue;
	    }

	    switch( substr($line, 0, 3) ){
		// Scene breaks. Set if the NEXT line *begins* a new scene.
		// So, given:
		// ===
		// Sexy Detective: Hey there.
		// 'scenebreak' will be set for Sexy Detective's line.
		// If they are set to 'NULL' in an else loop here, then you destroy
		// the previous line's info. Don't do it.
		case '===':
		    $this->scenebreak = "'TRUE'";
		    continue;
		// Begin/end a flashback.
		// The problem with marking a line as a flashback BEGIN/END instead
		// of having separate entries is that if parsing fails for the stuff
		// inside the transcript, then only the END is parsed and we get
		// an extra <div>. Thus: improve parsing! This is (as far as I can tell)
		// only a problem with Music's multi-line stuff
		case '+++':
		    $this->flashback = "'BEGIN'";
		    continue;
		case '---':
		    $this->flashback = "'END'";
		    continue;
	    }
	   
	    // Character names.
	    // Possible variations:
	    // 1) Not spoiling who a mystery person is.
	    //   Veronica Mars [AS=Sexy Detective]: Hi there.
	    //   Will have: "Sexy Detective: Hi there." in the script, but
	    //   Sexy Detective will link to Veronica Mars's cast page.
	    // 2) Not linking names (e.g. a throwaway reporter)
	    //   Just preface the name with a '!'.
	    //   !Redshirt: OMG, a monster.
	    #
	    // FIXME work on [A1]
	    
	    if ( preg_match('/^(!)?(?:\[A1\])?(.+?)( \[AS=(.*)\])?: (.+?)(?:\[A1\])?$/', $line, $matches) ){
		// All of these are, of course, "if they exist".
		// When operating on them, use .nil?
		// 1: Exclamation mark - if present, means not important
		// 2: name
		// 3: " AS=pseudonym"
		// 4: "pseudonym"
		// 5: line of dialogue
		$throwaway = $matches[1];
		$name = $matches[2];
		$pseudonym = $matches[4];
		$myline = $matches[5];
		#Replace with full name if shortened name was used when transcribing
		if( array_key_exists($name, $GLOBALS['castHash']) ){
		    $name = $GLOBALS['castHash'][$name];
		}
		$stripname = $this->myurlencode($name);
		
		if( strlen($throwaway) === 0 ){ // not a throwaway.
		    $in_cast_db = mysql_num_rows(mysql_query("SELECT name FROM cast WHERE name='".mysql_real_escape_string($name)."'")) > 0;
		    // Name isn't in DB. Add it.
		    if(! $in_cast_db){
			$this->add_to_cast($name, $pseudonym);
		    }
		    if( strlen($pseudonym) > 0 ){
			// only adds if it's a new pseudonym 
			$this->add_cast_pseudonym($name, $pseudonym);
		    }
		}

		// Finally done processing. Let's INSERT it.
		mysql_query(sprintf("INSERT INTO transcript " .
				    "(season, ep_num, speaker, pseudonym, stripname, " .
				    "quote, line, internal, " .
				    "scenebreak, flashback, line_num)" .
				    'VALUES ( ' .
				    '%d, %d, \'%s\', %s, \'%s\',' .
				    '%s, \'%s\', %s, ' .
				    '%s, %s, %d )',
				    $this->season, $this->ep_num, mysql_real_escape_string($name), $this->tri($pseudonym), $stripname,
				    $this->quote_anchor, mysql_real_escape_string($myline), $this->internal,
				    $this->scenebreak, $this->flashback, $this->line_num));
		$this->flashback = $this->scenebreak = 'NULL'; // reset *after* query because if we do it before, we destroy the previous line's info
		$this->line_num += 1;

		// Add to appearance as well - lots of rows of one appearance each, with a unique id
		// Check if we already added this character's pseudonyms for *THIS* ep
		$appearance_result = mysql_query(sprintf("SELECT pseudonym FROM appearance " .
						 "WHERE name='%s' " .
						 "AND season=%d " .
						 "AND ep_num=%d",
						 mysql_real_escape_string($name),
						 $this->season, $this->ep_num));
		$in_appearance = (mysql_num_rows($appearance_result) > 0);
		if( ! $in_appearance ){
		    $this->add_to_appearance($name, $pseudonym);
		} else {
		    // already added to appearance
		    if( $pseudonym ){
			$appearanceHash = mysql_fetch_assoc($appearance_result);
			// make sure it's a NEW pseudonym
			// Note: we don't need to check if a pseudonym is given in the DB (appearanceHash['pseudonym'])
			// because if they have a pseudonym now, they had one when they were first introduced, too,
			// and that's when the pseudonym field was set.
			if ( ! in_array($pseudonym, explode(',', $appearanceHash['pseudonym'])) ){
			    $newPseudonym = $appearanceHash['pseudonym'] . ',' . $pseudonym;
			    mysql_query(sprintf("UPDATE appearance SET pseudonym='%s' WHERE name='%s' " .
				"AND season=%d AND ep_num=%d",
				$newPseudonym, mysql_real_escape_string($name), $this->season, $this->ep_num));
			}
		    }
		}
	    } // end dialogue line processing
	} // end while loop through transcript lines
	printf('<span style="color: blue;">done.</span><br>', $this->season, $this->ep_num);
	$this->cleanup(); // close file handle and rename file.
    } // end parse
}
