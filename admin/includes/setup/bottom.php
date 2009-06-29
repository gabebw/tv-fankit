</div><?php // div#content.column - end center column ?>
<div id="right" class="column"><?php // right column ?>
<div id="sitenav"><?php // right column ?>
<h3>Site Map</h3>
<p><a href="/vmars/">Home Page</a></p>
<p><a href="/vmars/ep/">Episodes</a></p>
<p><a href="/vmars/quote/">Quotes</a></p>
</div>
<?php
// If we're viewing info about an ep, show other links with more info about it
// NOT just on the episode page
if( $VMARS_SITEINFO['query_type'] == 'episode' ) {
    echo('<div id="moreinfo">'."\n");
    printf('<h3>Episode Info</h3>'."\n",
	$season,
	$ep_num);
    printf('<p><a href="/vmars/ep/%dx%02d">Transcript for %1$dx%2$02d</a></p>',
	$season,
	$ep_num);
    printf('<p><a href="/vmars/quote/%dx%02d">Quotes for %1$dx%2$02d</a></p>',
	$season,
	$ep_num);
    echo("\n");
    printf('<p><a href="/vmars/ref/%dx%02d">References for %1$dx%2$02d</a></p>',
	$season,
	$ep_num);
    echo("\n");
    printf('<p><a href="/vmars/cast/ep/%dx%02d">Cast for %1$dx%2$02d</a></p>',
	$season,
	$ep_num);
    echo("\n");
    echo("</div>"); // div#moreinfo
    echo("\n");
}
?>
</div><?php // end right column ?>
</div><?php // div#all ?>
<div id="footer"></div>
</body>
</html>
