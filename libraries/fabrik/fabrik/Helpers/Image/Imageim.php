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
use \Fabrik\Helpers\StringHelper;
use \NewMagickWand;

/**
 * Image magic image manipulation class
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.0
 */
class Imageim extends Image
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
		// Check if the file exists
		if (!$this->storage->exists($origFile))
		{
			throw new RuntimeException("no file found for $origFile");
		}

		$fromFile = $this->storage->preRenderPath($origFile);

		$ext = $this->getImgType($fromFile);

		if (!$ext)
		{
			// False so not an image type so cant resize
			// $$$ hugh - testing making thumbs for PDF's, so need a little tweak here
			$origInfo = pathinfo($fromFile);

			if (StringHelper::strtolower($origInfo['extension']) != 'pdf')
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

			$origInfo = pathinfo($fromFile);

			if (StringHelper::strtolower($origInfo['extension']) == 'pdf')
			{
				$pdfThumbType = 'png';

				// OK, it's a PDF, so first we need to add the page number we want to the source filename
				$pdfFile = $fromFile . '[0]';

				/*
				if (is_callable('exec'))
				{
					$destFile = str_replace('.pdf', '.png', $destFile); // Output File
					$convert    = "convert " . $pdfFile . "  -colorspace RGB -resize " . $maxWidth . " " . $destFile; // Command creating
					exec($convert); // Execution of complete command.
				}
				else
				{
				*/
					// Now just load it, set format, resize, save and garbage collect.
					// Hopefully IM will call the right delegate (ghostscript) to load the PDF.
					$im = new \Imagick($pdfFile);
					$im->setImageFormat($pdfThumbType);
					$im->thumbnailImage($maxWidth, $maxHeight, true);
					$im->writeImage($destFile);
					// as destroy() is deprecated
					$im->clear();
				/*
				}
				*/
			}
			else
			{
				$im = new \Imagick;

				/* Read the image file */
				$im->readImage($fromFile);

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

			if (!MagickReadImage($resource, $fromFile))
			{
				echo "ERROR!";
				print_r(MagickGetException($resource));
			}

			$resource        = MagickTransformImage($resource, '0x0', $maxWidth . 'x' . $maxWidth);
			$this->thumbPath = $destFile;
			MagickWriteImage($resource, $destFile);
		}
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

}
