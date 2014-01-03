<?php
/**
 * Fabrik Google-O-Meter
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.googleometer
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_SITE . '/components/com_fabrik/models/element.php';

/**
 * Plugin element to render a google o meter chart
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.googleometer
 * @since       3.0
 */

class PlgFabrik_ElementGoogleometer extends PlgFabrik_Element
{
	/**
	 * Db table field type
	 *
	 * @var string
	 */
	protected $fieldDesc = 'TINYINT(%s)';

	/**
	 * Db table field size
	 *
	 * @var string
	 */
	protected $fieldSize = '1';

	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           to pre-populate element with
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return  string	elements html
	 */

	public function render($data, $repeatCounter = 0)
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$name = $this->getHTMLName($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		$params = $this->getParams();
		$element = $this->getElement();
		$value = $this->getValue($data, $repeatCounter);
		$range = $this->getRange();
		$fullName = $this->getDataElementFullName();
		$data = JArrayHelper::getValue($data, $fullName);
		$str = $this->_renderListData($data, $range);

		return $str;
	}

	/**
	 * Get the data element's full name
	 *
	 * @return  string
	 */

	private function getDataElementFullName()
	{
		$dataelement = $this->getDataElement();
		$fullName = $dataelement->getFullName();

		return $fullName;
	}

	/**
	 * Get the data element
	 *
	 * @return  PlgFabrik_Element
	 */

	private function getDataElement()
	{
		$params = $this->getParams();
		$elementid = (int) $params->get('googleometer_element');
		$element = FabrikWorker::getPluginManager()->getPlugIn('', 'element');
		$element->setId($elementid);

		return $element;
	}

	/**
	 * Get the min max rating range
	 *
	 * @return  object
	 */

	private function getRange()
	{
		$listModel = $this->getlistModel();
		$db = $listModel->getDb();
		$element = $this->getDataElement();
		$name = $db->quoteName($element->getElement()->name);
		$query = $db->getQuery(true);
		$query->select('MIN(' . $name . ') AS min, MAX(' . $name . ') AS max')
		->from($listModel->getTable()->db_table_name);
		$db->setQuery($query);
		$range = $db->loadObject();

		return $range;
	}

	/**
	 * Shows the data formatted for the list view
	 *
	 * @param   string    $data      elements data
	 * @param   stdClass  &$thisRow  all the data in the lists current row
	 *
	 * @return  string	formatted value
	 */

	public function renderListData($data, stdClass &$thisRow)
	{
		static $range;
		static $fullName;

		if (!isset($range))
		{
			$range = $this->getRange();
			$fullName = $this->getDataElementFullName();
		}

		$data = $thisRow->$fullName;
		$data = $this->_renderListData($data, $range);

		return parent::renderListData($data, $thisRow);
	}

	/**
	 * Render the google meter
	 *
	 * @param   string  $data   Elements data
	 * @param   object  $range  Min / Max range
	 *
	 * @return  string	formatted value
	 */

	protected function _renderListData($data, $range)
	{
		$options = array();
		$params = $this->getParams();
		$options['chartsize'] = 'chs=' . $params->get('googleometer_width', 200) . 'x' . $params->get('googleometer_height', 125);
		$options['charttype'] = 'cht=gom';
		$options['value'] = 'chd=t:' . $data;
		$options['label'] = 'chl=' . $params->get('googleometer_label');
		$options['range'] = 'chds=' . $range->min . ',' . $range->max;
		$options = implode('&amp;', $options);
		$str = '<img alt="Google-o-meter" src="http://chart.apis.google.com/chart?' . $options . '"/>';

		return $str;
	}
}
