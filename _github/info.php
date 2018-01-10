<?php

require_once 'bootstrap.php';

$info = new Info($config);
$info->execute();
echo $info->_log_messages;