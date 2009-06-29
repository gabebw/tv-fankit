<?php
require('admin-setup.php');
/*
According to
http://www.java2s.com/Tutorial/MySQL/0200__Data-Types/TEXTandBLOB.htm
TEXT datatype is processed more slowly than VARCHAR and takes up lots of space
*/

$tables = array('appearance', 'cast', 'episode', 'ref', 'transcript', 'website');

/*
APPEARANCES - keeps track of which eps a character appeared in and as whom
Each row is a character, a season and an ep_num
So Veronica Mars in 2x01 is a different row than Veronica Mars in 2x02
*/

$appearance_create="CREATE TABLE appearance (" .
		    "id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, " .
		    "season TINYINT UNSIGNED NOT NULL, " .
		    "ep_num TINYINT UNSIGNED NOT NULL, " .
		    "name VARCHAR(100) NOT NULL, " .
		    "pseudonym TINYTEXT, " . # can be null
		    "stripname VARCHAR(200) NOT NULL, " .
		    "INDEX name_ix (name(5)), " . 
		    "INDEX stripname_ix (stripname(5)) )" 

/*
CAST
everybody in a specific episode
*/

$cast_create="CREATE TABLE cast (" .
		    "actor VARCHAR(200), " . # actor's name
		    "imdb_id INT, " . # part after 'nm'
		    "name TINYTEXT NOT NULL, " . # PRIMARY KEY , " .
		    "pseudonym TINYTEXT, " .
		    "stripname VARCHAR(200) NOT NULL," .
		    "description MEDIUMTEXT, " . # L+3; L<2^24
		    "sortkey INT," . # define our own sort order
		    "PRIMARY KEY name( name(10) ) )";

/*
EPISODE
------
Holds title of episode, season, ep_num and
meaning of title (what it refers to, etc.)
*/
$episode_create="CREATE TABLE episode (" .
"title VARCHAR(100) NOT NULL, " .
"season TINYINT UNSIGNED NOT NULL, " .
"ep_num TINYINT UNSIGNED NOT NULL, " .
"title_meaning TEXT)" # can be null
# If you add an INDEX, do (season, ep_num) together
# because that's the only query that gets done (WHERE season=x AND ep_num=X)
# but it's so tiny, and will have max like 90 rows, I don't think it's 
# necessary.


/*
REF
----------
Each id/row is an instance that a thing was referenced
with the season and episode it appears in plus a description

To get all references for an episode (say 2x01), do:
SELECT * FROM ref WHERE season=2 AND ep_num=1

To get all times "Sherlock Holmes" is referenced, do:
SELECT * FROM ref WHERE name="Sherlock Holmes";
*/

$ref_create="CREATE TABLE ref (" . 
		"id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, " .
		"season TINYINT UNSIGNED NOT NULL, " .
		"ep_num TINYINT NOT NULL, " .
		"line_num TINYINT NOT NULL, " . # which line is this ref on?
		"thing TEXT NOT NULL, " . # the line ref'ing something
		"description VARCHAR(500) NOT NULL, " .
		"category VARCHAR(200) NOT NULL, " .
		# PHP uses catname to check if a given category exists;
		# it is easy to create from $_GET by adding '+'s
		"catname VARCHAR(100) NOT NULL, " .
		"anchor VARCHAR(10) NOT NULL, " .
		"INDEX catname_index (catname(5)) )";

# transcript for a given season and ep_num is every single line from that ep
$transcript_create="CREATE TABLE transcript (" .
		    #"id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, " .
		    "line_num MEDIUMINT UNSIGNED NOT NULL, " .
		    "season TINYINT UNSIGNED NOT NULL, " .
		    "ep_num TINYINT UNSIGNED NOT NULL, " .
		    # line/speaker/stripname can be null because 
		    # flashbacks and scenebreaks
		    # are lines but have no actual dialogue
		    "line TEXT, " . # the actual line of dialogue
		    "speaker TEXT, " . # who said it?
		    "stripname TEXT, " . # who said it? - urlencoded
		    "internal TINYTEXT, " . # whether or not this line is internal dialogue
		    "pseudonym TEXT, " .
		    "quote TINYTEXT, " . # set if this is a quote; = anchor
		    "ref TINYTEXT, " . # set if this is a ref; = anchor
		    "flashback ENUM('BEGIN', 'END'), " . # begin/end of flashback
		    "scenebreak ENUM('TRUE'), " . # is this the end of a scene?
		    "INDEX season_index (season))";
		    # not sure why, but EXPLAIN select [blah] points out
		    # that adding a season_index helps out a lot for
		    # "WHERE season=X" queries
/*
Websites, when they're mentioned, and a little bit about them
*/
$website_create="CREATE TABLE website (" . 
		    "id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, " .
		    "season TINYINT UNSIGNED NOT NULL, " .
		    "ep_num TINYINT UNSIGNED NOT NULL, " .
		    "website VARCHAR(80) NOT NULL, " .
		    "description VARCHAR(500) NOT NULL);";


foreach($tables as $t){
    mysql_query("DROP TABLE '$t'");
    mysql_query(${$table . '_create'});
}
?>
