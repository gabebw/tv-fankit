<?php
// For existing transcripts.
// All JS is in <head>
if( editing_specific_episode() ){
    $ep_printer->set_usage_note("Click on a line to edit it.");
    $ep_printer->print_out();
} else {
    print_episode_list();
}
?>
