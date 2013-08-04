<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.exif
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
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

	var $map_field = '';
	var $upload_field = '';

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
			$base = JArrayHelper::getValue($bits, 0);
			$divider = JArrayHelper::getValue($bits, 1);
			return ($divider == 0) ? sprintf($format, 0) : sprintf($format, ($base / $divider));
		}
	}

	protected function exifToCoordinate($reference, $coordinate)
	{
		$prefix = ($reference == 'S' || $reference == 'W') ? '-' : '';

		return $prefix
			. sprintf('%.6F',
				$this->exifToNumber($coordinate[0], '%.6F')
					+ ((($this->exifToNumber($coordinate[1], '%.6F') * 60) + ($this->exifToNumber($coordinate[2], '%.6F'))) / 3600));
	}

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

	function coordinate2DMS($coordinate, $pos, $neg)
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
	 * @param   object  $params      params
	 * @param   object  &$formModel  form model
	 *
	 * @return  bool  should the form model continue to save
	 */

	public function onBeforeStore($params, &$formModel)
	{
		// Initialize some variables
		$db = FabrikWorker::getDbo();
		$data = $formModel->formData;

		$plugin = FabrikWorker::getPluginManager()->getElementPlugin($params->get('exif_map_field'));

		$element = $plugin->getElement(true);
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
				$data[$this->map_field] = $coords[0] . ',' . $coords[1] . ':4';
				$data[$this->map_field . '_raw'] = $data[$this->map_field];
			}
		}
		return true;
	}

}
