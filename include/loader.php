<?php
define("FRONTEND_PATH", ROOT_PATH);
define("CONFIG_PATH", ROOT_PATH . DS . "etc");
define("LIB_PATH", ROOT_PATH . DS . "vendor");
define("LOG_PATH", ROOT_PATH . DS . "var" . DS . "log");
define("TMP_PATH", ROOT_PATH . DS . "var" . DS . "tmp");
define("COOKIE_PATH", ROOT_PATH . DS . "var" . DS . "cookies");

if (DEBUG) {
    error_reporting(E_ALL & ~E_NOTICE);
    ini_set("display_errors", "On");
    ini_set("display_startup_errors", "On");
}

require_once(LIB_PATH . DS . "autoload.php");
require_once(INC_PATH . DS . "functions.php");
require_once(INC_PATH . DS . "helpers" . DS . "ConfigHelper.php");
require_once(INC_PATH . DS . "helpers" . DS . "Mailer.php");
require_once(INC_PATH . DS . "helpers" . DS . "LogWriter.php");
require_once(INC_PATH . DS . "helpers" . DS . "CurlHelper.php");
require_once(INC_PATH . DS . "helpers" . DS . "DomParser.php");
require_once(INC_PATH . DS . "Script.php");

date_default_timezone_set(Script::config("general/timezone"));
header("Content-type: text/html; charset=" . Script::config("general/charset"));