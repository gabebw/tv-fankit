<?php
$totaleps = mysql_result(mysql_query('SELECT COUNT(*) FROM episode'), 0);
$totalseasons = mysql_result(mysql_query('SELECT COUNT(DISTINCT season) FROM episode'), 0);
$totalquotes = mysql_result(mysql_query('SELECT COUNT(DISTINCT quote,season,ep_num) FROM transcript WHERE quote IS NOT NULL'), 0);
$totalrefs = mysql_result(mysql_query('SELECT COUNT(DISTINCT ref,season,ep_num) FROM transcript WHERE ref IS NOT NULL'), 0);
$totalactors = mysql_result(mysql_query('SELECT COUNT(name) FROM cast'), 0);
function pluralize($str, $dep){
    return $dep > 1 ? $str . 's' : $str;
}
$episodeStr = pluralize('episode', $totaleps);
$seasonStr = pluralize('season', $totalseasons);
$quoteStr = pluralize('quote', $totalquotes);
$actorStr = pluralize('actor', $totalactors);
$referenceStr = pluralize('reference', $totalrefs);
?>
<h2>Edit</h2>
<div id="text">
<?php
printf('<p><a href="episode.php">Episodes</a> (%d %s in %d %s)</p>'."\n",
    $totaleps,
    $episodeStr,
    $totalseasons,
    $seasonStr);
printf('<p><a href="edit-quote.php">Quotes</a> (%d %s total)</p>'."\n",
    $totalquotes,
    $quoteStr);
printf('<p><a href="edit-ref.php">References</a> (%d %s total)</p>'."\n",
    $totalrefs,
    $referenceStr);
printf('<p><a href="cast.php">Cast</a> (%d %s total)</p>'."\n",
    $totalactors,
    $actorStr);
printf('<p><a href="character.php">Characters</a> (??? characters total)</p>'."\n");
printf('<p><a href="seriesinfo.php">Series Info</a> (series creator, premier date, run time)</p>'."\n");
?>
