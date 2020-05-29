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
use \RuntimeException;
use \Fabrik\Helpers\StringHelper;

/**
 * GD2 image manipulation class
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.0
 */
class Imagegd2 extends Imagegd
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

		$fromFile = $this->storage->preRenderPath($origFile);

		// Load image
		$img = null;
		$ext = $this->getImgType($fromFile);

		if (!$ext)
		{
			return;
		}

		ini_set('display_errors', true);
		$memory    = \FabrikWorker::getMemoryLimit(true);
		$intMemory = \FabrikWorker::getMemoryLimit();

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

		if ($intMemory < (64 * 1024 * 1024))
		{
			ini_set('memory_limit', $memory);
		}
	}
}

