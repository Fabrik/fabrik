<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.exif
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Process exif info from images, allowing you to insert the exif data into selected fields
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.exif
 * @since       3.0
 */

class PlgFabrik_FormExif extends PlgFabrik_Form
{
	/**
	 * Map field
	 *
	 * @var string
	 */
	protected $map_field = '';

	/**
	 * Upload field
	 *
	 * @var string
	 */
	protected $upload_field = '';

	/**
	 * Exif to number
	 *
	 * @param   string  $value   Value
	 * @param   string  $format  Format
	 *
	 * @return string
	 */
	protected function exifToNumber($value, $format)
	{
		$spos = JString::strpos($value, '/');

		if ($spos === false)
		{
			return sprintf($format, $value);
		}
		else
		{
			$bits = explode('/', $value, 2);
			$base = FArrayHelper::getValue($bits, 0);
			$divider = FArrayHelper::getValue($bits, 1);

			return ($divider == 0) ? sprintf($format, 0) : sprintf($format, ($base / $divider));
		}
	}

	/**
	 * Exif to coordinate
	 *
	 * @param   string  $reference   Reference
	 * @param   string  $coordinate  Coordinates
	 *
	 * @return string
	 */
	protected function exifToCoordinate($reference, $coordinate)
	{
		$prefix = ($reference == 'S' || $reference == 'W') ? '-' : '';

		return $prefix
			. sprintf('%.6F',
				$this->exifToNumber($coordinate[0], '%.6F') +
				((($this->exifToNumber($coordinate[1], '%.6F') * 60) + ($this->exifToNumber($coordinate[2], '%.6F'))) / 3600)
		);
	}

	/**
	 * Get coordinates
	 *
	 * @param   string  $filename  File name
	 *
	 * @return multitype:string |boolean
	 */
	protected function getCoordinates($filename)
	{
		if (extension_loaded('exif'))
		{
			$exif = exif_read_data($filename, 'EXIF');

			if (isset($exif['GPSLatitudeRef']) && isset($exif['GPSLatitude']) && isset($exif['GPSLongitudeRef']) && isset($exif['GPSLongitude']))
			{
				return array($this->exifToCoordinate($exif['GPSLatitudeRef'], $exif['GPSLatitude']),
					$this->exifToCoordinate($exif['GPSLongitudeRef'], $exif['GPSLongitude']));
			}
		}

		return false;
	}

	/**
	 * Set coordinates to DMS
	 *
	 * @param   string  $coordinate  Image coordinate
	 * @param   number  $pos         Postion
	 * @param   number  $neg         Negative
	 *
	 * @return string
	 */
	protected function coordinate2DMS($coordinate, $pos, $neg)
	{
		$sign = $coordinate >= 0 ? $pos : $neg;
		$coordinate = abs($coordinate);
		$degree = intval($coordinate);
		$coordinate = ($coordinate - $degree) * 60;
		$minute = intval($coordinate);
		$second = ($coordinate - $minute) * 60;

		return sprintf("%s %d&#xB0; %02d&#x2032; %05.2f&#x2033;", $sign, $degree, $minute, $second);
	}

	/**
	 * Before the record is stored, this plugin will see if it should process
	 * and if so store the form data in the session.
	 *
	 * @return  bool  should the form model continue to save
	 */

	public function onBeforeStore()
	{
		// Initialize some variables
		$formModel = $this->getModel();
		$data = $formModel->formData;
		$params = $this->getParams();
		$plugin = FabrikWorker::getPluginManager()->getElementPlugin($params->get('exif_map_field'));
		$this->map_field = $plugin->getFullName();
		$plugin->setId($params->get('exif_upload_field'));
		$element = $plugin->getElement(true);
		$this->upload_field = $plugin->getFullName();
		$file_path = JPATH_SITE . '/' . $data[$this->upload_field];

		if (JFile::exists($file_path))
		{
			$coords = $this->getCoordinates($file_path);

			if (!empty($coords))
			{
				$c = $coords[0] . ',' . $coords[1] . ':4';
				$formModel->updateFormData($this->map_field, $c, true);
			}
		}

		return true;
	}
}
