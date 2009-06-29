<?php
require 'includes/TranscriptParser.class.php';
$tp = new TranscriptParser('buffy.txt', 1, 1);
print_r($tp);
echo "\n";
$tp->parse();
echo "\n";
?>
