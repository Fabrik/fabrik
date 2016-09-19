<?php
/**
 * Trivial image serving script, to work round IE caching static CAPTCHA IMG's
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// Allow direct access!!!!
define('_JEXEC', 1);

$jpath = dirname(__FILE__);
$jpath = str_replace('/plugins/fabrik_element/captcha', '', $jpath);
$jpath = str_replace('\plugins\fabrik_element\captcha', '', $jpath);
define('JPATH_BASE', $jpath);

define('DS', DIRECTORY_SEPARATOR);

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
$app = JFactory::getApplication('site');
$app->initialise();
$package = $app->getUserState('com_fabrik.package', 'fabrik');
$session = JFactory::getSession();

$code = $session->get('com_' . $package . '.element.captcha.security_code', false);

if (!($code))
{
	exit;
}

// Width and height used as back up if imagettfbbox not available
$width = $session->get('com_' . $package . '.element.captcha.width', 100);
$height = $session->get('com_' . $package . '.element.captcha.height', 50);

$fontsize = $session->get('com_' . $package . '.element.captcha.fontsize', 30);
$angle = $session->get('com_' . $package . '.element.captcha.angle', 0);
$padding  = $session->get('com_' . $package . '.element.captcha.padding', 10);
$font = $session->get('com_' . $package . '.element.captcha.font', 'monofont.ttf');
$b_color = $session->get('com_' . $package . '.element.captcha.bg_color', '255+255+255');
$bc = explode('+', $b_color);
$n_color = $session->get('com_' . $package . '.element.captcha.noise_color', '0+0+255');
$nc = explode('+', $n_color);
$t_color = $session->get('com_' . $package . '.element.captcha.text_color', '0+0+255');
$tc = explode('+', $t_color);

// Create textbox and add text
$fontPath = JPATH_SITE . '/plugins/fabrik_element/captcha/' . $font;

if (function_exists('imagettfbbox'))
{
	$the_box = calculateTextBox($code, $fontPath, $fontsize, $angle);
}
else
{
	$the_box = array('width' => 150, 'height' => 50, 'top' => 0, 'left' => 0);
}


$imgWidth = $the_box["width"] + $padding;
$imgHeight = $the_box["height"] + $padding;

$image = imagecreate($imgWidth, $imgHeight) or die ('Cannot initialize new GD image stream');

$background_color = imagecolorallocate($image, $bc[0], $bc[1], $bc[2]);
$text_color = imagecolorallocate($image, $tc[0], $tc[1], $tc[2]);
$noise_color = imagecolorallocate($image, $nc[0], $nc[1], $nc[2]);

// Generate random dots in background
for ($i = 0; $i < ($imgWidth * $imgHeight) / 3; $i++)
{
	imagefilledellipse($image, mt_rand(0, $imgWidth), mt_rand(0, $imgHeight), 1, 1, $noise_color);
}

// Generate random lines in background
for ($i = 0; $i < ($imgWidth * $imgHeight) / 150; $i++)
{
	imageline($image, mt_rand(0, $imgWidth), mt_rand(0, $imgHeight), mt_rand(0, $imgWidth), mt_rand(0, $imgHeight), $noise_color);
}

$left = $the_box["left"] + ($imgWidth / 2) - ($the_box["width"] / 2);
$top = $the_box["top"] + ($imgHeight / 2) - ($the_box["height"] / 2);

if (function_exists('imagettfbbox'))
{
imagettftext(
	$image,
	$fontsize,
	$angle,
	$left,
	$top,
	$text_color,
	$fontPath,
	$code
) or die('Error in imagettftext function');
}
else
{
	$font = 6;
	imagestring($image, $font, $left, $top, $code, $text_color);
}
// $$$ hugh - @TODO - add some session identifier to the image name (maybe using the hash we use in the formsession stuff)

// ... set no-cache (and friends) headers ...
// Some time in the past
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Accept-Ranges: bytes');

// For some weird reason if we do this in 5.2.x the image gets truncated
// http://fabrikar.com/forums/showthread.php?t=26941&page=5
if (version_compare(PHP_VERSION, '5.3.0') < 0)
{
	header('Content-Length: ' . JString::strlen($img));
}

header('Content-Type: image/jpeg');

ob_start();
imagejpeg($image);
$img = ob_get_contents();

/**
Felixkat - Clean has been replaced with flush due to a image truncating issue
Haven't been able to pinpoint the exact issue yet, possibly PHP version related
http://fabrikar.com/forums/showthread.php?p=147606#post147606
 */
// Not this: ob_end_clean();
ob_end_flush();
imagedestroy($image);
echo $img;

// ... and we're done.
exit();

/**
 *  Simple function that calculates the *exact* bounding box (single pixel precision).
 *  The function returns an associative array with these keys:
 *  left, top:  coordinates you will pass to imagettftext
 *  width, height: dimension of the image you have to create
 *
 * @param   string  $code      Code
 * @param   string  $fontPath  Font path
 * @param   int     $fontsize  Font size
 * @param   int     $angle     Text angle
 *
 * @return  array
 */
function calculateTextBox($code, $fontPath, $fontsize, $angle)
{
	$rect = imagettfbbox($fontsize, $angle, $fontPath, $code);
	$minX = min(array($rect[0], $rect[2], $rect[4], $rect[6]));
	$maxX = max(array($rect[0], $rect[2], $rect[4], $rect[6]));
	$minY = min(array($rect[1], $rect[3], $rect[5], $rect[7]));
	$maxY = max(array($rect[1], $rect[3], $rect[5], $rect[7]));

	return array
	(
		"left"   => abs($minX) - 1,
		"top"    => abs($minY) - 1,
		"width"  => $maxX - $minX,
		"height" => $maxY - $minY,
		"box"    => $rect
	);
}
