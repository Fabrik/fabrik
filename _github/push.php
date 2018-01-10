<?php

require_once 'bootstrap.php';

$push = new Push($config);
$push->execute();
echo $push->_log_messages;