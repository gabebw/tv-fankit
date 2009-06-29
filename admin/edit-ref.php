<?php
$tab = 'edit';
$action = 'edit-ref';
if( $editing_specific_episode ){
    $ep_printer->print_out();
} else {
    echo '<h1>Select Episode</h1>';
    print_episode_list();
}
?>
