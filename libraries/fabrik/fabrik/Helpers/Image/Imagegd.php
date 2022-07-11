<?php
/**
 * @package     Joomla
 * @subpackage  Fabrik.image
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Helpers\Image;

defined('_JEXEC') or die('Restricted access');

use \JHtml;
use \JFactory;
use \JFile;
use \Error;
use \Fabrik\Helpers\StringHelper;

/**
 * GD image manipulation class
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.0
 */
class Imagegd extends Image
{
	/**
	 * Create an image object from a file path
	 *
	 * @param   string $file file to create image from
	 *
	 * @throws \Exception
	 *
	 * @return  array  (image, header string)
	 */
	public function imageFromFile($file)
	{

		$fromFile = $this->storage->preRenderPath($file);

		// Load image
		$img = null;
		$ext = $this->getImgType($fromFile);

		if (!$ext)
		{
			return array(false, false);
		}

		ini_set('display_errors', true);
		$memory    = \FabrikWorker::getMemoryLimit(true);
		$intMemory    = \FabrikWorker::getMemoryLimit();

		if ($intMemory < (64 * 1024 * 1024))
		{
			ini_set('memory_limit', '50M');
		}

		if ($ext == 'jpg' || $ext == 'jpeg')
		{
			$img    = @imagecreatefromjpeg($fromFile);
			$header = "image/jpeg";
		}
		elseif ($ext == 'png')
		{
			$img    = @imagecreatefrompng($fromFile);
			$header = "image/png";

			// Only if your version of GD includes GIF support
		}
		elseif ($ext == 'gif')
		{
			if (function_exists('imagecreatefromgif'))
			{
				$img    = @imagecreatefromgif($fromFile);
				$header = "image/gif";
			}
			else
			{
				throw new \Exception("imagecreate from gif not available");
			}
		}

		if ($intMemory < (64 * 1024 * 1024))
		{
			ini_set('memory_limit', $memory);
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
		$ext = StringHelper::strtolower(JFile::getExt($source));

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
		$ext = StringHelper::strtolower(JFile::getExt($destCropFile));
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

		return $this->storage->write($destCropFile, $image_p);
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

		$ext = StringHelper::strtolower(end(explode('.', $origFile)));

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
