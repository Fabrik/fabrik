<?php
/**
 * Fabrik Google-O-Meter
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.googleometer
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

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
	 * @param   array $data          to pre-populate element with
	 * @param   int   $repeatCounter repeat group counter
	 *
	 * @return  string    elements html
	 */
	public function render($data, $repeatCounter = 0)
	{
		$range    = $this->getRange();
		$fullName = $this->getDataElementFullName();
		$data     = FArrayHelper::getValue($data, $fullName);

		if (is_array($data))
		{
			$data = ArrayHelper::getValue($data, $repeatCounter);
		}

		return $this->_renderListData($data, $range, $repeatCounter);
	}

	/**
	 * Get the data element's full name
	 *
	 * @return  string
	 */
	private function getDataElementFullName()
	{
		$dataElement = $this->getDataElement();
		$fullName    = $dataElement->getFullName(true, false);

		return $fullName;
	}

	/**
	 * Get the data element
	 *
	 * @return  PlgFabrik_Element
	 */
	private function getDataElement()
	{
		$params    = $this->getParams();
		$elementId = (int) $params->get('googleometer_element');
		$element   = FabrikWorker::getPluginManager()->getPlugIn('', 'element');
		$element->setId($elementId);

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
		$db        = $listModel->getDb();
		$element   = $this->getDataElement();
		$table     = $element->getTableName();
		$name      = $db->qn($element->getElement()->name);
		$query     = $db->getQuery(true);
		$query->select('MIN(' . $name . ') AS min, MAX(' . $name . ') AS max')
			->from($table);
		$db->setQuery($query);
		$range = $db->loadObject();

		return $range;
	}

	/**
	 * Shows the data formatted for the list view
	 *
	 * @param   string   $data     Elements data
	 * @param   stdClass &$thisRow All the data in the lists current row
	 * @param   array    $opts     Rendering options
	 *
	 * @return  string    formatted value
	 */
	public function renderListData($data, stdClass &$thisRow, $opts = array())
	{
		static $range;
		static $fullName;

		if (!isset($range))
		{
			$range    = $this->getRange();
			$fullName = $this->getDataElementFullName() . '_raw';
		}

		$dataElement = $this->getDataElement();
		$data = $thisRow->$fullName;

		if ($dataElement->getGroupModel()->canRepeat())
		{
			$data = FabrikWorker::JSONtoData($data, true);

			foreach ($data as $i => &$d)
			{
				$d = $this->_renderListData($d, $range);
			}
		}
		else
		{
			$data = $this->_renderListData($data, $range);
		}

		return parent::renderListData($data, $thisRow, $opts);
	}

	/**
	 * Render the google meter
	 *
	 * @param   string $data  Elements data
	 * @param   object $range Min / Max range
	 *
	 * @return  string    formatted value
	 */
	protected function _renderListData($data, $range)
	{
		$options              = array();
		$params               = $this->getParams();
		$options['chartsize'] = 'chs=' . $params->get('googleometer_width', 200) . 'x' . $params->get('googleometer_height', 125);
		$options['charttype'] = 'cht=gom';
		$options['value']     = 'chd=t:' . $data;
		$options['label']     = 'chl=' . $params->get('googleometer_label');
		$options['range']     = 'chds=' . $range->min . ',' . $range->max;

		$layout              = $this->getLayout('chart');
		$layoutData          = new stdClass;
		$layoutData->options = implode('&amp;', $options);

		return $layout->render($layoutData);
	}
}
