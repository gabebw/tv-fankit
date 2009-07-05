<?php
/**
 * Set paths, set up DB.
 */
require_once('admin-constants.php');
require_once('functions.php');
require_once('db-setup.php');
if( editing_specific_episode() ){
    require(ADMIN_INC_PATH . 'functions-episode-specific.php');
}
?>
