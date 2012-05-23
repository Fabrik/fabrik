<?php 
putenv("TZ=US/Eastern"); //~ Time Zone
$ip = getenv('REMOTE_ADDR'); //~ get ip address 
$song = $_POST["playSong"];
$annotation = $_POST["annotation"];
$time = date("m/d/y H:i:s");
echo ($annotation.", ".$time.", ".$ip.", ".$song);
echo ("output=Processed...");
?>