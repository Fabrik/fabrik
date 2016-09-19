<?php
/**
 * Image manipulation helper
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Image manipulation class
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.0
 */
class FabimageHelper
{
	/**
	 * Get an array of available graphics libraries
	 *
	 * @return  array
	 */
	public static function getLibs()
	{
		$libs = array();
		$gds  = self::testGD();

		foreach ($gds as $key => $val)
		{
			$libs[] = JHTML::_('select.option', $key, $val);
		}

		$im = self::testImagemagick();

		foreach ($im as $key => $val)
		{
			$libs[] = JHTML::_('select.option', $key, $val);
		}

		return $libs;
	}

	/**
	 * load in the correct image library
	 *
	 * @param   string $lib image lib to load
	 *
	 * @return  Fabimage  image lib
	 */
	public static function loadLib($lib)
	{
		$class = "Fabimage" . $lib;

		if (class_exists($class))
		{
			return new $class;
		}
		else
		{
			throw new RuntimeException("Fabrik: can't load image class: $class");
		}
	}

	/**
	 * Test if the GD library is available
	 *
	 * @return  array
	 */
	protected static function testGD()
	{
		$gd        = array();
		$output    = '';
		$gdVersion = null;
		$gdInfo    = null;

		//$GDfuncList = get_extension_funcs('gd');
		if (function_exists('gd_info'))
		{
			$gdInfo    = gd_info();
			$gdVersion = $gdInfo['GD Version'];
		}
		else
		{
			ob_start();
			@phpinfo(INFO_MODULES);
			$output = ob_get_contents();
			ob_end_clean();
			$matches[1] = '';
			if ($output !== '')
			{
				if (preg_match("/GD Version[ \t]*(<[^>]+>[ \t]*)+([^<>]+)/s", $output, $matches))
				{
					$gdVersion = $matches[2];
				}
				else
				{
					return $gd;
				}
			}
		}

		if (function_exists('imagecreatetruecolor') && function_exists('imagecreatefromjpeg'))
		{
			$gdVersion = isset($gdVersion) ? $gdVersion : 2;
			$gd['gd2'] = "GD: " . $gdVersion;
		}
		elseif (function_exists('imagecreatefromjpeg'))
		{
			$gdVersion = isset($gdVersion) ? $gdVersion : 1;
			$gd['gd1'] = "GD: " . $gdVersion;
		}

		return $gd;
	}

	/**
	 * Test if Imagemagick is installed on the server
	 *
	 * @return  array
	 */
	protected static function testImagemagick()
	{
		if (function_exists('NewMagickWand'))
		{
			$im['IM'] = 'Magick wand';
		}
		else
		{
			$status = '';
			$output = array();
			@exec('convert -version', $output, $status);
			$im = array();

			if (!$status && class_exists('Imagick'))
			{
				if (preg_match("/imagemagick[ \t]+([0-9\.]+)/i", $output[0], $matches))
				{
					$im['IM'] = $matches[0];
				}
			}

			unset($output, $status);
		}

		return $im;
	}
}

/**
 * Base image manipulation class
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @since       1.0
 */
class Fabimage
{
	/**
	 * Thumbnail image path
	 *
	 * @var  string
	 */
	protected $thumbPath = null;

	/**
	 * Storage class file/amazons3 etc.
	 *
	 * @var object
	 */
	public $storage = null;

	/**
	 * Set the filesystem storage manager
	 *
	 * @param   object &$storage storage object
	 *
	 * @return  void
	 */
	public function setStorage(&$storage)
	{
		$this->storage = $storage;
	}

	/**
	 * Get the image type git/jpg/png
	 *
	 * @param   string $filename image file path
	 *
	 * @return  string
	 */
	public function getImgType($filename)
	{
		$info = getimagesize($filename);

		switch ($info[2])
		{
			case 1:
				return 'gif';
				break;
			case 2:
				return 'jpg';
				break;
			case 3:
				return 'png';
				break;
			default:
				return false;
		}
	}

	/**
	 * Resize an image to a specific width/height
	 *
	 * @param   int    $maxWidth  maximum image Width (px)
	 * @param   int    $maxHeight maximum image Height (px)
	 * @param   string $origFile  current images folder path (must have trailing end slash)
	 * @param   string $destFile  destination folder path for resized image (must have trailing end slash)
	 * @param   int    $quality   Percentage image save quality 100 = no compression, 0 = max compression
	 *
	 * @return  object  image
	 */
	public function resize($maxWidth, $maxHeight, $origFile, $destFile, $quality = 100)
	{
	}

	/**
	 * Grab an image from a remote URI and store in cache, then serve cached image
	 *
	 * @param   string  $src      Remote URI to image
	 * @param   string  $path     Local folder to store the image in e.g. 'cache/com_fabrik/images'
	 * @param   string  $file     Local filename
	 * @param   integer $lifeTime Number of days to cache the image for
	 *
	 * @return  boolean|string  Local URI to cached image
	 */
	public static function cacheRemote($src, $path, $file, $lifeTime = 29)
	{
		/**
		 * $$$ @FIXME we may need to find something other than file_get_contents($src)
		 * to use for this, as it requires allow_url_fopen to be enabled in PHP to fetch a URL,
		 * which a lot of shared hosts don't allow.
		 *
		 * -Rob - well JFile::read is deprecated and in the code it says to use file_get_contents
		 * The Joomla updater won't work with out file_get_contents so I think we should make it a requirement
		 * Wiki updated here - http://fabrikar.com/forums/index.php?wiki/prerequisites/
		 *
		 * hugh - Okie Dokie.
		 */

		/**
		 * $$$ @FIXME - hugh - as we're producing files with names like:
		 *
		 * center=34.732267,-86.587593.zoom=10.size=300x250.maptype=roadmap.mobile=true.markers=34.732267,-86.587593.sensor=false.png
		 *
		 * ... we should probably clean $file, replace non alphanumeric chars with
		 * underscores, as filenames with things like commas, = signs etc. could be problematic, both in
		 * the file system, and on the IMG URL.
		 *
		 * EDIT - hopefully just md5()'ing the file should fix the above, needed to do it as we finally had
		 * someone report a problem with invalid file name, see ...
		 *
		 * https://github.com/Fabrik/fabrik/pull/1307
		 *
		 * So ... just preserve original extension (if any) and append it to md5() of file name.
		 */

		$ext  = pathinfo($file, PATHINFO_EXTENSION);
		$file = md5($file);

		if (!empty($ext))
		{
			$file .= '.' . $ext;
		}

		$folder = JPATH_SITE . '/' . ltrim($path, '/');

		// For SSL a user agent may need to be set.
		ini_set('user_agent', 'Mozilla/4.0 (compatible; MSIE 6.0)');

		if (!JFolder::exists($folder))
		{
			JFolder::create($folder);
		}

		// make sure we have one, and only one, / on the end of folder.  Really should add a helper for this which looks for legacy \ as well!
		$folder    = rtrim($folder, '/') . '/';
		$cacheFile = $folder . $file;

		// result to test file_put_contents() with
		$res = false;

		// Check for cached version
		if (JFile::exists($cacheFile))
		{
			// Check its age- Google T&C allow you to store for no more than 30 days.
			$createDate = JFactory::getDate(filemtime($cacheFile));
			$now        = JFactory::getDate();
			$interval   = $now->diff($createDate);
			$daysOld    = (float) $interval->format('%R%a');

			if ($daysOld < -$lifeTime)
			{
				// Remove out of date
				JFile::delete($cacheFile);

				// Grab image from Google and store
				$res = file_put_contents($cacheFile, file_get_contents($src));
			}
			else
			{
				$res = true;
			}
		}
		else
		{
			// No cached image, grab image from remote URI and store locally
			$res = file_put_contents($cacheFile, file_get_contents($src));
		}

		if ($res === false)
		{
			$src = false;
		}
		else
		{
			$src = COM_FABRIK_LIVESITE . $path . $file;
		}

		return $src;
	}

	/**
	 * Exif to number
	 *
	 * @param   string $value  Value
	 * @param   string $format Format
	 *
	 * @return string
	 */
	public static function exifToNumber($value, $format)
	{
		$pos = JString::strpos($value, '/');

		if ($pos === false)
		{
			return sprintf($format, $value);
		}
		else
		{
			$bits    = explode('/', $value, 2);
			$base    = FArrayHelper::getValue($bits, 0);
			$divider = FArrayHelper::getValue($bits, 1);

			return ($divider == 0) ? sprintf($format, 0) : sprintf($format, ($base / $divider));
		}
	}

	/**
	 * Exif to coordinate
	 *
	 * @param   string $reference  Reference
	 * @param   string $coordinate Coordinates
	 *
	 * @return string
	 */
	public static function exifToCoordinate($reference, $coordinate)
	{
		$prefix = ($reference == 'S' || $reference == 'W') ? '-' : '';

		return $prefix
		. sprintf('%.6F',
			self::exifToNumber($coordinate[0], '%.6F') +
			(((self::exifToNumber($coordinate[1], '%.6F') * 60) + (self::exifToNumber($coordinate[2], '%.6F'))) / 3600)
		);
	}

	/**
	 * Get coordinates
	 *
	 * @param   string $filename File name
	 *
	 * @return array|boolean
	 */
	public static function getExifCoordinates($filename)
	{
		if (extension_loaded('exif'))
		{
			$exif = exif_read_data($filename, 'EXIF');

			if (isset($exif['GPSLatitudeRef']) && isset($exif['GPSLatitude']) && isset($exif['GPSLongitudeRef']) && isset($exif['GPSLongitude']))
			{
				return array(self::exifToCoordinate($exif['GPSLatitudeRef'], $exif['GPSLatitude']),
					self::exifToCoordinate($exif['GPSLongitudeRef'], $exif['GPSLongitude']));
			}
		}

		return false;
	}

	/**
	 * Set coordinates to DMS
	 *
	 * @param   string $coordinate Image coordinate
	 * @param   number $pos        Position
	 * @param   number $neg        Negative
	 *
	 * @return string
	 */
	public static function coordinate2DMS($coordinate, $pos, $neg)
	{
		$sign       = $coordinate >= 0 ? $pos : $neg;
		$coordinate = abs($coordinate);
		$degree     = intval($coordinate);
		$coordinate = ($coordinate - $degree) * 60;
		$minute     = intval($coordinate);
		$second     = ($coordinate - $minute) * 60;

		return sprintf("%s %d&#xB0; %02d&#x2032; %05.2f&#x2033;", $sign, $degree, $minute, $second);
	}

}

/**
 * GD image manipulation class
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.0
 */
class FabimageGD extends Fabimage
{
	/**
	 * Create an image object from a file path
	 *
	 * @param   string $file file to create image from
	 *
	 * @throws Exception
	 *
	 * @return  array  (image, header string)
	 */
	public function imageFromFile($file)
	{
		$img = false;
		$ext = JString::strtolower(end(explode('.', $file)));

		if ($ext == 'jpg' || $ext == 'jpeg')
		{
			$img    = @imagecreatefromjpeg($file);
			$header = "image/jpeg";
		}
		elseif ($ext == 'png')
		{
			$img    = @imagecreatefrompng($file);
			$header = "image/png";

			// Only if your version of GD includes GIF support
		}
		elseif ($ext == 'gif')
		{
			if (function_exists('imagecreatefromgif'))
			{
				$img    = @imagecreatefromgif($file);
				$header = "image/gif";
			}
			else
			{
				throw new Exception("imagecreate from gif not available");
			}
		}

		return array($img, $header);
	}

	/**
	 * Create a gd image from a file
	 *
	 * @param   string $source path to file
	 *
	 * @return image
	 */
	protected function imageCreateFrom($source)
	{
		$ext = JString::strtolower(JFile::getExt($source));

		switch ($ext)
		{
			case 'jpg':
			case 'jpeg':
				$source = imagecreatefromjpeg($source);
				break;
			case 'png':
				$source = imagecreatefrompng($source);
				break;
			case 'gif':
				$source = imagecreatefromgif($source);
				break;
		}

		return $source;
	}

	/**
	 * Convert an image object into a file
	 *
	 * @param   string $destCropFile file path
	 * @param   object $image        image object to save
	 *
	 * @return  bool  True on success
	 */
	public function imageToFile($destCropFile, $image)
	{
		$ext = JString::strtolower(JFile::getExt($destCropFile));
		ob_start();

		switch ($ext)
		{
			case 'jpg':
			case 'jpeg':
				$source = imagejpeg($image, null, 100);
				break;
			case 'png':
				$source = imagepng($image, null);
				break;
			case 'gif':
				$source = imagegif($image, null);
				break;
		}

		$image_p = ob_get_contents();
		ob_end_clean();

		return JFile::write($destCropFile, $image_p);
	}

	/**
	 * Rotate an image
	 *
	 * @param   string $source  filepath
	 * @param   string $dest    output path, if empty defaults to source
	 * @param   int    $degrees number of degrees to rotate
	 *
	 * @return  array  (image object, rotated images width, rotated images height)
	 */
	public function rotate($source, $dest = '', $degrees = 0)
	{
		if (empty($dest))
		{
			$dest = $source;
		}

		$source = $this->imageCreateFrom($source);
		$app    = JFactory::getApplication();

		// Rotates the image
		$rotate = imagerotate($source, $degrees, 0);

		if ($rotate === false)
		{
			$app->enqueueMessage('Image rotation failed', 'notice');
		}

		$this->imageToFile($dest, $rotate);
		list($width, $height) = getimagesize($dest);

		return array($rotate, $width, $height);
	}

	/*
	 * Check for EXIF orientation data, and rotate image accordingly
	*
	* @param   string   path to image file
	*/
	public function rotateImageFromExif($src, $dest)
	{
		if (function_exists('exif_read_data'))
		{
			$exif = exif_read_data($src);
			if ($exif && isset($exif['Orientation']))
			{
				$orientation = $exif['Orientation'];
				if ($orientation != 1)
				{
					$deg = 0;
					switch ($orientation)
					{
						case 3:
							$deg = 180;
							break;
						case 6:
							$deg = 270;
							break;
						case 8:
							$deg = 90;
							break;
					}
					if ($deg)
					{
						self::rotate($src, $dest, $deg);
					}
				}
			}
		}
	}

	/**
	 * Scale an image
	 *
	 * @param   string $file       file to scale
	 * @param   string $dest       save location
	 * @param   int    $percentage scale percentage
	 * @param   int    $destX      start scale from x coord
	 * @param   int    $destY      start scale from y coord
	 *
	 * @return  object  image
	 */
	public function scale($file, $dest = '', $percentage = 100, $destX = 0, $destY = 0)
	{
		list($image, $header) = $this->imageFromFile($file);

		jimport('joomla.filesystem.file');

		list($width, $height) = getimagesize($file);

		$new_width  = $width * ((float) $percentage / 100);
		$new_height = $height * ((float) $percentage / 100);
		$image_p    = imagecreatetruecolor($new_width, $new_height);
		imagecopyresampled($image_p, $image, $destX, $destY, 0, 0, $new_width, $new_height, $width, $height);

		if ($dest != '')
		{
			$this->imageToFile($dest, $image_p);
		}

		return $image_p;
	}

	/**
	 * Resize an image to a specific width/height
	 *
	 * @param   int    $maxWidth  maximum image Width (px)
	 * @param   int    $maxHeight maximum image Height (px)
	 * @param   string $origFile  current images folder path (must have trailing end slash)
	 * @param   string $destFile  destination folder path for resized image (must have trailing end slash)
	 * @param   int    $quality   Percentage image save quality 100 = no compression, 0 = max compression
	 *
	 * @throws Error
	 *
	 * @return  object  image
	 */
	public function resize($maxWidth, $maxHeight, $origFile, $destFile, $quality = 100)
	{
		// Check if the file exists
		if (!$this->storage->exists($origFile))
		{
			throw new RuntimeException("Fabrik: no file found for $origFile");
		}
		// Load image
		list($img, $header) = $this->imageFromFile($origFile);

		if (!$img)
		{
			return $img;
		}

		$ext = JString::strtolower(end(explode('.', $origFile)));

		// If an image was successfully loaded, test the image for size
		if ($img)
		{
			// Handle image transparency for original image
			if (function_exists('imagealphablending'))
			{
				imagealphablending($img, false);
				imagesavealpha($img, true);
			}
			// Get image size and scale ratio
			$width  = imagesx($img);
			$height = imagesy($img);
			$scale  = min($maxWidth / $width, $maxHeight / $height);

			// If the image is larger than the max shrink it
			if ($scale < 1)
			{
				$new_width  = floor($scale * $width);
				$new_height = floor($scale * $height);

				// Create a new temporary image
				$tmp_img = imagecreatetruecolor($new_width, $new_height);

				// Handle image transparency for resized image
				if (function_exists('imagealphablending'))
				{
					imagealphablending($tmp_img, false);
					imagesavealpha($tmp_img, true);
				}
				// Copy and resize old image into new image
				imagecopyresampled($tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
				imagedestroy($img);
				$img = $tmp_img;
			}
		}
		// Create error image if necessary
		if (!$img)
		{
			throw new Error("resize: no image created for $origFile, extension = $ext, destination = $destFile");
		}
		// Save the file
		$this->writeImg($img, $destFile, $header, $quality);

		$this->thumbPath = $destFile;
	}

	/**
	 * Crop an image to specific dimensions
	 *
	 * @param   string $origFile path to image to crop from
	 * @param   string $destFile path to cropped file
	 * @param   int    $srcX     x coord on $origFile to start crop from
	 * @param   int    $srcY     y coord on $origFile to start crop from
	 * @param   int    $dstW     cropped image width
	 * @param   int    $dstH     cropped image height
	 * @param   int    $dstX     destination x coord of destination point
	 * @param   int    $dstY     destination y coord of destination point
	 * @param   string $bg       hex background colour
	 *
	 * @return  void
	 */
	public function crop($origFile, $destFile, $srcX, $srcY, $dstW, $dstH, $dstX = 0, $dstY = 0, $bg = '#FFFFFF')
	{
		// Convert hex to rgb colours.
		list($r, $g, $b) = sscanf($bg, '#%2x%2x%2x');
		list($origImg, $header) = $this->imageFromFile($origFile);
		$destImg = imagecreatetruecolor($dstW, $dstH);
		$bg      = imagecolorallocate($destImg, $r, $g, $b);

		// Draw a bg rectangle
		imagefilledrectangle($destImg, 0, 0, (int) $dstW, (int) $dstH, $bg);

		$this->writeImg($destImg, $destFile, $header);
		$srcW = imagesx($destImg);
		$srcH = imagesy($destImg);

		$origW = imagesx($origImg);
		$origH = imagesy($origImg);

		// If the orig image is smaller than the destination then increase its canvas and fill it with the bg
		if ($origW < $srcW || $origH < $srcH)
		{
			$srcBg = imagecreatetruecolor($srcW, $srcH);
			imagefilledrectangle($srcBg, 0, 0, (int) $srcW, (int) $srcH, $bg);
			imagecopyresampled($srcBg, $origImg, 0, 0, 0, 0, $origW, $origH, $origW, $origH);
			$origImg = $srcBg;
		}

		imagecopyresampled($destImg, $origImg, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);
		$this->writeImg($destImg, $destFile, $header);
	}

	/**
	 * Write an image to the server
	 *
	 * @param   object $img      image object
	 * @param   string $destFile file path to save to
	 * @param   string $header   image type
	 * @param   int    $quality  Percentage image save quality 100 = no compression, 0 = max compression
	 *
	 * @throws Error
	 *
	 * @return  void
	 */
	public function writeImg($img, $destFile, $header, $quality = 100)
	{
		if ($quality < 0)
		{
			$quality = 0;
		}

		if ($quality > 100)
		{
			$quality = 100;
		}

		if ($header == "image/jpeg")
		{
			ob_start();
			imagejpeg($img, null, $quality);
			$image = ob_get_contents();
			ob_end_clean();
			$this->storage->write($destFile, $image);
		}
		else
		{
			if ($header == "image/png")
			{
				$quality = round((100 - $quality) * 9 / 100);
				ob_start();
				imagepng($img, null, $quality);
				$image = ob_get_contents();
				ob_end_clean();
				$this->storage->write($destFile, $image);
			}
			else
			{
				if (function_exists("imagegif"))
				{
					ob_start();
					imagegif($img, null, $quality);
					$image = ob_get_contents();
					ob_end_clean();
					$this->storage->write($destFile, $image);
				}
				else
				{
					throw new Error('trying to save a gif by imagegif support not present in the GD library');
				}
			}
		}
	}
}

/**
 * GD2 image manipulation class
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.0
 */
class FabimageGD2 extends FabimageGD
{
	/**
	 * Resize an image to a specific width/height
	 *
	 * @param   int    $maxWidth  maximum image Width (px)
	 * @param   int    $maxHeight maximum image Height (px)
	 * @param   string $origFile  current images folder path (must have trailing end slash)
	 * @param   string $destFile  destination folder path for resized image (must have trailing end slash)
	 * @param   int    $quality   Percentage image save quality 100 = no compression, 0 = max compression
	 *
	 * @return  object  image
	 */
	public function resize($maxWidth, $maxHeight, $origFile, $destFile, $quality = 100)
	{
		$app = JFactory::getApplication();

		// Check if the file exists
		if (!$this->storage->exists($origFile))
		{
			throw new RuntimeException("no file found for $origFile");
		}

		// Load image
		$img = null;
		$ext = $this->getImgType($origFile);

		if (!$ext)
		{
			return;
		}

		ini_set('display_errors', true);
		$memory    = ini_get('memory_limit');
		$intMemory = FabrikString::rtrimword($memory, 'M');

		if ($intMemory < 50)
		{
			ini_set('memory_limit', '50M');
		}

		if ($ext == 'jpg' || $ext == 'jpeg')
		{
			$img    = imagecreatefromjpeg($origFile);
			$header = "image/jpeg";
		}
		elseif ($ext == 'png')
		{
			$img    = imagecreatefrompng($origFile);
			$header = "image/png";

			// Only if your version of GD includes GIF support
		}
		elseif ($ext == 'gif')
		{
			if (function_exists('imagecreatefromgif'))
			{
				$img    = imagecreatefromgif($origFile);
				$header = "image/gif";
			}
			else
			{
				$app->enqueueMessage("imagecreate from gif not available");
			}
		}
		// If an image was successfully loaded, test the image for size
		if ($img)
		{
			// Handle image transparency for original image
			if (function_exists('imagealphablending'))
			{
				imagealphablending($img, false);
				imagesavealpha($img, true);
			}

			// Get image size and scale ratio
			$width  = imagesx($img);
			$height = imagesy($img);
			$scale  = min($maxWidth / $width, $maxHeight / $height);

			// If the image is larger than the max shrink it
			if ($scale < 1)
			{
				$new_width  = floor($scale * $width);
				$new_height = floor($scale * $height);

				// Create a new temporary image
				$tmp_img = imagecreatetruecolor($new_width, $new_height);

				// handle transparency for tmp image
				if (function_exists('imagealphablending'))
				{
					imagealphablending($tmp_img, false);
					imagesavealpha($tmp_img, true);
				}

				// Copy and resize old image into new image
				imagecopyresampled($tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
				imagedestroy($img);
				$img = $tmp_img;
			}
		}

		if (!$img)
		{
			$app->enqueueMessage("no image created for $origFile, extension = $ext , destination = $destFile ");
		}

		/* save the file
		 * write them out to output buffer first so that we can use JFile to write them
		 to the server (potential using J ftp layer)  */
		if ($header == "image/jpeg")
		{
			ob_start();
			imagejpeg($img, null, $quality);
			$image = ob_get_contents();
			ob_end_clean();
			$this->storage->write($destFile, $image);
		}
		else
		{
			if ($header == "image/png")
			{
				ob_start();
				$quality = round((100 - $quality) * 9 / 100);
				imagepng($img, null, $quality);
				$image = ob_get_contents();
				ob_end_clean();
				$this->storage->write($destFile, $image);
			}
			else
			{
				if (function_exists("imagegif"))
				{
					ob_start();
					imagegif($img, null, $quality);
					$image = ob_get_contents();
					ob_end_clean();
					$this->storage->write($destFile, $image);
				}
				else
				{
					$app->enqueueMessage("GD gif support not available: could not resize image");
				}
			}
		}

		$this->thumbPath = $destFile;
		ini_set('memory_limit', $memory);
	}
}

/**
 * Image magic image manipulation class
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.0
 */
class FabimageIM extends Fabimage
{
	/**
	 * Resize an image to a specific width/height
	 *
	 * @param   int    $maxWidth  maximum image Width (px)
	 * @param   int    $maxHeight maximum image Height (px)
	 * @param   string $origFile  current images folder path (must have trailing end slash)
	 * @param   string $destFile  destination folder path for resized image (must have trailing end slash)
	 * @param   int    $quality   Percentage image save quality 100 = no compression, 0 = max compression
	 *
	 * @return  object  image
	 */
	public function resize($maxWidth, $maxHeight, $origFile, $destFile, $quality = 100)
	{
		$ext = $this->getImgType($origFile);

		if (!$ext)
		{
			// False so not an image type so cant resize
			// $$$ hugh - testing making thumbs for PDF's, so need a little tweak here
			$origInfo = pathinfo($origFile);

			if (JString::strtolower($origInfo['extension']) != 'pdf')
			{
				return;
			}
		}

		ini_set('display_errors', true);

		// See if the imagick image lib is installed
		if (class_exists('Imagick'))
		{
			/* $$$ hugh - having a go at handling PDF thumbnails, which should work as long as the server
			 * has ghostscript (GS) installed.  Don't have a generic test for GS being available, so
			 * it'll just fail if no GS.
			 */

			$origInfo = pathinfo($origFile);

			if (JString::strtolower($origInfo['extension']) == 'pdf')
			{
				$pdfThumbType = 'png';

				// OK, it's a PDF, so first we need to add the page number we want to the source filename
				$pdfFile = $origFile . '[0]';

				if (is_callable('exec'))
				{
					$destFile = str_replace('.pdf', '.png', $destFile); // Output File
					$convert    = "convert " . $pdfFile . "  -colorspace RGB -resize " . $maxWidth . " " . $destFile; // Command creating
					exec($convert); // Execution of complete command.
				}
				else
				{
					// Now just load it, set format, resize, save and garbage collect.
					// Hopefully IM will call the right delegate (ghostscript) to load the PDF.
					$im = new Imagick($pdfFile);
					$im->setImageFormat($pdfThumbType);
					$im->thumbnailImage($maxWidth, $maxHeight, true);
					$im->writeImage($destFile);
					// as destroy() is deprecated
					$im->clear();
				}
			}
			else
			{
				$im = new Imagick;

				/* Read the image file */
				$im->readImage($origFile);

				/* Thumbnail the image ( width 100, preserve dimensions ) */
				$im->thumbnailImage($maxWidth, $maxHeight, true);

				/* Write the thumbnail to disk */
				$im->writeImage($destFile);

				/* Free resources associated to the Imagick object */
				$im->destroy();
			}

			$this->thumbPath = $destFile;
		}
		else
		{
			$resource = NewMagickWand();

			if (!MagickReadImage($resource, $origFile))
			{
				echo "ERROR!";
				print_r(MagickGetException($resource));
			}

			$resource        = MagickTransformImage($resource, '0x0', $maxWidth . 'x' . $maxWidth);
			$this->thumbPath = $destFile;
			MagickWriteImage($resource, $destFile);
		}
	}
}
