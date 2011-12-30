<?php
/**
 * Trivial image serving script, to work round IE caching static CAPTCHA IMG's
 * @package fabrikar
 * @author Hugh Messenger
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

//$id = session_id();
/*
$name = session_name($_GET['session_name']);
$id = session_id($_GET['session_id']);
$path = session_save_path($_GET['session_save_path']);
$name = session_name();
$path = session_save_path();
$id = session_id();
$result = session_start();
*/
define( '_JEXEC', 1);

//define('JPATH_BASE', dirname(__FILE__) . '/../../../../../..');
$jpath = dirname(__FILE__);
$jpath = str_replace('/plugins/fabrik_element/captcha', '', $jpath);
$jpath = str_replace('\plugins\fabrik_element\captcha', '', $jpath);
define('JPATH_BASE', $jpath);

define( 'DS', DIRECTORY_SEPARATOR);

require_once ( JPATH_BASE .DS.'includes'.DS.'defines.php');
require_once ( JPATH_BASE .DS.'includes'.DS.'framework.php');
$app 				=& JFactory::getApplication('site');
$app->initialise();
$session = JFactory::getSession();
$code = $session->get('com_fabrik.element.captach.security_code', false);

if (!($code)) {
	exit;
}

//***** e-kinst
$width = $session->get('com_fabrik.element.captach.width', 100);
$height = $session->get('com_fabrik.element.captach.height', 50);
$font = $session->get('com_fabrik.element.captach.font', 'monofont.ttf');
$b_color = $session->get('com_fabrik.element.captach.bg_color', '255+255+255');
$bc = explode('+', $b_color); 
$n_color = $session->get('com_fabrik.element.captach.noise_color', '0+0+255');
$nc = explode('+', $n_color); 
$t_color = $session->get('com_fabrik.element.captach.text_color', '0+0+255');
$tc = explode('+', $t_color); 
// * /e-kinst

$font_size = $height * 0.75;
$image = @imagecreate($width, $height) or die('Cannot initialize new GD image stream');
/* set the colours */

//***** e-kinst
$background_color = imagecolorallocate($image, $bc[0], $bc[1], $bc[2]);
$text_color = imagecolorallocate($image, $tc[0], $tc[1], $tc[2]);
$noise_color = imagecolorallocate($image, $nc[0], $nc[1], $nc[2]);
// * /e-kinst
/* generate random dots in background */
for ($i=0; $i<($width*$height)/3; $i++) {
	imagefilledellipse($image, mt_rand(0,$width), mt_rand(0,$height), 1, 1, $noise_color);
}
/* generate random lines in background */
for ($i=0; $i<($width*$height)/150; $i++) {
	imageline($image, mt_rand(0,$width), mt_rand(0,$height), mt_rand(0,$width), mt_rand(0,$height), $noise_color);
}
/* create textbox and add text */
$fontPath = JPATH_SITE . '/plugins/fabrik_element/captcha/' . $font;

$textbox = imagettfbbox($font_size, 0, $fontPath, $code) or die('Error in imagettfbbox function ' . $fontPath);
$x = ($width - $textbox[4])/2;
$y = ($height - $textbox[5])/2;
imagettftext($image, $font_size, 0, $x, $y, $text_color, $fontPath , $code) or die('Error in imagettftext function');
// $$$ hugh - @TODO - add some session identifier to the image name (maybe using the hash we use in the formsession stuff)
ob_start();
imagejpeg($image);
$img = ob_get_contents();
ob_end_clean();
imagedestroy($image);

if( !empty($img) ) {
	// it exists, so grab the contents ...
	//$img = file_get_contents('./image.jpg');

	// ... set no-cache (and friends) headers ...
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Some time in the past
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	header('Accept-Ranges: bytes');
	header('Content-Length: '.strlen($img));
	header('Content-Type: image/jpeg');

	// ... serve up the image ...
	echo $img;

	// ... and we're done.
	exit();
}
?>
