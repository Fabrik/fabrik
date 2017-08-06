<?php

require_once 'bootstrap.php';

$pull = new Pull($config);
$pull->execute();
echo $pull->_log_messages;