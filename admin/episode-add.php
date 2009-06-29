<?php
/* add episode */
if( ! empty($_POST) ){
} else {
?>
<h1>Add Episode</h1>
<form action="episode-add.php" method="post">
<label for="season">Season (<span class="required">required</span>):</label>
<br/><input type="text" id="season" />
<br/><label for="ep_num">Episode Number (<span class="required">required</span>):</label>
<br/><input type="text" id="ep_num" />
<div class="info">Everything below here is optional. You can always edit this episode's information later.</div>
<br/><label for="title">Episode Title:</label>
<br/><input type="text" id="title" />
<br/><label for="air_date">Air Date (like "10/28/08"):</label>
<br/><input type="text" id="air_date" />
<br/><label for="producer">Producer(s):</label>
<br/><input type="text" id="producer" />
<br/><label for="writer">Writer(s):</label>
<br/><input type="text" id="writer" />
<br /><label for="network">Network (e.g. CBS):<label>
<br/><input type="text" id="network" />

<h2>Transcript</h2>
<h4>
Simply type the transcript into the box below. Each line should be of the form:
<br/>
Character_name: dialogue.
<br/>
You shouldn't put quote marks around the dialogue.
</h4>
<h5>
There are some special bits:
<br/>
Use "[Q]" to create quotes. Put a "[Q]" on its own line before and after a quote, e.g.:
<br/>
<p>
[Q]
<br/>
Character: Witty remark.
<br/>
Character: Quotable retort!
<br/>
[Q]
</p>
Type a "===" on its own line to mark the beginning of a new scene.
</h5>
<textarea cols="100" rows="60"></textarea>
<input type="submit"/>
</form>
<?php } ?>
