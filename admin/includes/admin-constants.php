<?php
/* Set paths.
 * Note that all "blah_PATH" constants have trailing slashes.
 * blah_HTML_PATH are like "/fankit/js/" instead of "C:\Documents and Settings\....\fankit\js".
 * Sets up:
 * ADMIN_BASE_PATH
 * ADMIN_BASE_HTML_PATH
 * ADMIN_INC_PATH
 * CALLBACK_HTML_PATH
 * CSS_PATH
 * CSS_HTML_PATH
 * JS_PATH
 * JS_HTML_PATH
 * QUOTE_PATH
 * REF_PATH
 */

/**
 * Path to base vmars installation: "/Library/WebServer/Documents/vmars/"
 */
if( ! defined('BASE_PATH') ){
    define('BASE_PATH', realpath(dirname(dirname(dirname(__FILE__)))) . '/');
}
/**
 * siteinfo.php sets the following constants:
 * FK_DEBUG
 * FK_SITENAME
 * FK_BASENAME (like "/~gabe/vmars")
 * FK_PAGE_NAME
 */
//require_once(BASE_PATH . 'setup/siteinfo.php');
if( ! defined('ADMIN_DEBUG') ){
    define('ADMIN_DEBUG', false);
}
if( ! defined('ADMIN_INCLUDE_PATH') ){
    define('ADMIN_INCLUDE_PATH', realpath(dirname(__FILE__)) . '/');
}
if( ! defined('ADMIN_BASE_PATH') ){
    define('ADMIN_BASE_PATH', dirname(ADMIN_INCLUDE_PATH) . '/');
}
if( ! defined('ADMIN_BASE_HTML_PATH') ){
    define('ADMIN_BASE_HTML_PATH', dirname(dirname($_SERVER['PHP_SELF'])) . '/admin/' );
}
if( ! defined('ADMIN_INC_PATH') ){
    define('ADMIN_INC_PATH', dirname(__FILE__) . '/');
}
if( ! defined('CALLBACK_HTML_PATH') ){
    define('CALLBACK_HTML_PATH', ADMIN_BASE_HTML_PATH . 'callbacks/');
}
if( ! defined('CSS_PATH') ){
    define('CSS_PATH', ADMIN_BASE_PATH . 'css/');
}
if( ! defined('CSS_HTML_PATH') ){
    define('CSS_HTML_PATH', ADMIN_BASE_HTML_PATH . basename(CSS_PATH) . '/');
}
if( ! defined('JS_PATH') ){
    define('JS_PATH', ADMIN_BASE_PATH . 'js/');
}
if( ! defined('JS_HTML_PATH') ){
    define('JS_HTML_PATH', ADMIN_BASE_HTML_PATH . basename(JS_PATH) . '/');
}
if( ! defined('QUOTE_PATH') ){
    define('QUOTE_PATH', ADMIN_BASE_PATH . 'edit/quote/');
}
if( ! defined('REF_PATH') ){
    define('REF_PATH', ADMIN_BASE_PATH . 'edit/ref/');
}
?>
