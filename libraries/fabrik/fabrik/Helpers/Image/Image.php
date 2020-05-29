<?php
/**
 * @package     Joomla
 * @subpackage  Fabrik.image
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Helpers\Image;

use Fabrik\Helpers\ArrayHelper;
use Fabrik\Helpers\StringHelper;
use \JFactory;
use \JFile;
use \JFolder;

/**
 * Base image manipulation class
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @since       1.0
 */
class Image
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
				$pathInfo = pathInfo($filename);

				if (ArrayHelper::getValue($pathInfo, 'extension', '') === 'pdf')
				{
					return 'pdf';
				};

				return false;
				break;
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
		$pos = StringHelper::strpos($value, '/');

		if ($pos === false)
		{
			return sprintf($format, $value);
		}
		else
		{
			$bits    = explode('/', $value, 2);
			$base    = ArrayHelper::getValue($bits, 0);
			$divider = ArrayHelper::getValue($bits, 1);

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
