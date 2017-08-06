<?php

header("Content-Type:text/plain");

ini_set('display_errors', 1);
error_reporting(E_ALL + E_STRICT + E_DEPRECATED);

// Suppress error: date(): It is not safe to rely on the system's timezone settings...
if(!ini_get('date.timezone')) {
  date_default_timezone_set('GMT');
}

require_once './inc/config.php';
require_once './inc/VcsInterface.php';
require_once './inc/VcsAbstract.php';
require_once './inc/Pull.php';
require_once './inc/Push.php';
require_once './inc/Info.php';
