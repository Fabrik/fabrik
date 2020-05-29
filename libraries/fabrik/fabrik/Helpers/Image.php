<?php
/**
 * Image manipulation helper
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Helpers;

// No direct access

use \RuntimeException;
use \JHtml;

/**
 * Image manipulation class
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.0
 */
class Image
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
			$libs[] = JHtml::_('select.option', $key, $val);
		}

		$im = self::testImagemagick();

		foreach ($im as $key => $val)
		{
			$libs[] = JHtml::_('select.option', $key, $val);
		}

		return $libs;
	}

	/**
	 * load in the correct image library
	 *
	 * @param   string $lib image lib to load
	 *
	 * @throws RuntimeException
	 *
	 * @return  \Fabrik\Helpers\Image\Image  image lib
	 */
	public static function loadLib($lib)
	{
		if ($lib === 'value')
		{
			throw new RuntimeException("Fabrik: No image image processing library is available, make sure GD is installed in PHP and check your upload element settings!");
		}

		$className = '\Fabrik\Helpers\Image\Image' . strtolower($lib);

		try {
            $class = new $className;
        }
        catch (RuntimeException $e)
        {
			throw new RuntimeException("Fabrik: can't load image class: $className");
		}

		return $class;
	}

	/**
	 * Test if the GD library is available
	 *
	 * @return  array
	 */
	protected static function testGD()
	{
		$gd        = array();
		$gdVersion = null;
		$gdInfo    = null;

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
		$im = array();

		if (function_exists('NewMagickWand'))
		{
			$im['IM'] = 'Magick wand';
		}
		else
		{
			/*
			$status = '';
			$output = array();
			@exec('convert -version', $output, $status);
			$im = array();

			if ($status && class_exists('Imagick'))
			{
				if (preg_match("/imagemagick[ \t]+([0-9\.]+)/i", $output[0], $matches))
				{
					$im['IM'] = $matches[0];
				}
			}

			unset($output, $status);
			*/

			if (class_exists('Imagick'))
			{
				$im['IM'] = 'Imagick';
			}
		}

		return $im;
	}
}

