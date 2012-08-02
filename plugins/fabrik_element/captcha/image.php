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

require_once ( JPATH_BASE . '/includes/defines.php');
require_once ( JPATH_BASE . '/includes/framework.php');
$app 				=& JFactory::getApplication('site');
$app->initialise();
$session = JFactory::getSession();
$code = $session->get('com_fabrik.element.captach.security_code', false);

if (!($code)) {
	exit;
}

//***** e-kinst
// Felixkat - Removed width and height.  Now specified by font size.

//$width = $session->get('com_fabrik.element.captach.width', 100);
//$height = $session->get('com_fabrik.element.captach.height', 50);

$fontsize = $session->get('com_fabrik.element.captach.fontsize', 30);
$angle = $session->get('com_fabrik.element.captach.angle', 0);
$padding  = $session->get('com_fabrik.element.captach.padding', 10);
$font = $session->get('com_fabrik.element.captach.font', 'monofont.ttf');
$b_color = $session->get('com_fabrik.element.captach.bg_color', '255+255+255');
$bc = explode('+', $b_color); 
$n_color = $session->get('com_fabrik.element.captach.noise_color', '0+0+255');
$nc = explode('+', $n_color); 
$t_color = $session->get('com_fabrik.element.captach.text_color', '0+0+255');
$tc = explode('+', $t_color); 

/* create textbox and add text */
$fontPath = JPATH_SITE . '/plugins/fabrik_element/captcha/' . $font;

$the_box = calculateTextBox($code,$fontPath,$fontsize,$angle);

$imgWidth    = $the_box["width"] + $padding;
$imgHeight    = $the_box["height"] + $padding;

$image = imagecreate($imgWidth,$imgHeight) or die ('Cannot initialize new GD image stream');

$background_color = imagecolorallocate($image, $bc[0], $bc[1], $bc[2]);
$text_color = imagecolorallocate($image, $tc[0], $tc[1], $tc[2]);
$noise_color = imagecolorallocate($image, $nc[0], $nc[1], $nc[2]);

	/* generate random dots in background */
for ($i=0; $i<($imgWidth*$imgHeight)/3; $i++) {
	imagefilledellipse($image, mt_rand(0,$imgWidth), mt_rand(0,$imgHeight), 1, 1, $noise_color);
 }

/*  generate random lines in background */
for ($i=0; $i<($imgWidth*$imgHeight)/150; $i++) {
	imageline($image, mt_rand(0,$imgWidth), mt_rand(0,$imgHeight), mt_rand(0,$imgWidth), mt_rand(0,$imgHeight), $noise_color);
}

imagettftext($image,
    $fontsize,
    $angle,
    $the_box["left"] + ($imgWidth / 2) - ($the_box["width"] / 2),
    $the_box["top"] + ($imgHeight / 2) - ($the_box["height"] / 2),
    $text_color,
    $fontPath,
    $code) or die('Error in imagettftext function');
	

// $$$ hugh - @TODO - add some session identifier to the image name (maybe using the hash we use in the formsession stuff)


ob_start();
imagejpeg($image);
$img = ob_get_contents();
//
// Felixkat - Clean has been replaced with flush due to a image truncating issue
// Haven't been able to pinpoint the exact issue yet, possibly PHP version related
// http://fabrikar.com/forums/showthread.php?p=147606#post147606
//
//ob_end_clean();
ob_end_flush();
imagedestroy($image);

if( !empty($img) ) {
	// it exists, so grab the contents ...
	// $img = file_get_contents('./image.jpg');

	// ... set no-cache (and friends) headers ...
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Some time in the past
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	header('Accept-Ranges: bytes');
	header('Content-Length: '. JString::strlen($img));
	header('Content-Type: image/jpeg');

	// ... serve up the image ...
	echo $img;

	// ... and we're done.
	exit();
}
?>

<?php
function calculateTextBox($code,$fontPath,$fontsize,$angle) {
    /************
    simple function that calculates the *exact* bounding box (single pixel precision).
    The function returns an associative array with these keys:
    left, top:  coordinates you will pass to imagettftext
    width, height: dimension of the image you have to create
    *************/
    $rect = imagettfbbox($fontsize,$angle,$fontPath,$code);
    $minX = min(array($rect[0],$rect[2],$rect[4],$rect[6]));
    $maxX = max(array($rect[0],$rect[2],$rect[4],$rect[6]));
    $minY = min(array($rect[1],$rect[3],$rect[5],$rect[7]));
    $maxY = max(array($rect[1],$rect[3],$rect[5],$rect[7]));
   
    return array(
     "left"   => abs($minX) - 1,
     "top"    => abs($minY) - 1,
     "width"  => $maxX - $minX,
     "height" => $maxY - $minY,
     "box"    => $rect
    );
}

?>