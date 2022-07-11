<?php
/**
 * Plugin element to render a user controllable stopwatch timer
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.timer
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

require_once JPATH_SITE . '/components/com_fabrik/models/element.php';
require_once JPATH_SITE . '/plugins/fabrik_element/date/date.php';

/**
 * Plugin element to render a user controllable stopwatch timer
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.timer
 * @since       3.0
 */

class PlgFabrik_ElementTimer extends PlgFabrik_Element
{
	/**
	 * Does the element contain sub elements e.g checkboxes radiobuttons
	 *
	 * @var bool
	 */
	public $hasSubElements = false;

	/**
	 * Db table field type
	 * Jaanus: works better when using datatype 'TIME' as above and forgetting any date part of data :)
	 *
	 * @var string
	 */
	protected $fieldDesc = 'TIME';

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
		$name = $this->getHTMLName($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		$params = $this->getParams();
		$element = $this->getElement();
		$size = $params->get('timer_width', 9);
		$value = $this->getValue($data, $repeatCounter);

		if ($value == '')
		{
			$value = '00:00:00';
		}
		else
		{
			$value = explode(' ', $value);
			$value = array_pop($value);
		}

		if (!$this->isEditable())
		{
			return ($element->hidden == '1') ? "<!-- " . $value . " -->" : $value;
		}

		$layout = $this->getLayout('form');
		$layoutData = new stdClass;
		$layoutData->id = $id;
		$layoutData->type = $element->hidden ? 'hidden' : 'text';
		$layoutData->name = $name;
		$layoutData->value = $value;
		$layoutData->size = $size;
		$layoutData->elementError = $this->elementError;
		$layoutData->icon = $params->get('icon', 'icon-clock');
		$layoutData->timerReadOnly = $params->get('timer_readonly');

		return $layout->render($layoutData);
	}

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int  $repeatCounter  Repeat group counter
	 *
	 * @return  array
	 */

	public function elementJavascript($repeatCounter)
	{
		$params = $this->getParams();
		$id = $this->getHTMLId($repeatCounter);
		$opts = $this->getElementJSOptions($repeatCounter);
		$opts->autostart = (bool) $params->get('timer_autostart', false);
		JText::script('PLG_ELEMENT_TIMER_START');
		JText::script('PLG_ELEMENT_TIMER_STOP');

		return array('FbTimer', $id, $opts);
	}

	/**
	 * Get sum query
	 *
	 * @param   object  &$listModel  List model
	 * @param   array   $labels      Label
	 *
	 * @return string
	 */

	protected function getSumQuery(&$listModel, $labels = array())
	{
		$label = count($labels) == 0 ? "'calc' AS label" : 'CONCAT(' . implode(', " & " , ', $labels) . ')  AS label';
		$table = $listModel->getTable();
		$joinSQL = $listModel->buildQueryJoin();
		$whereSQL = $listModel->buildQueryWhere();
		$name = $this->getFullName(false, false);

		// $$$rob not actually likely to work due to the query easily exceeding MySQL's TIMESTAMP_MAX_VALUE value but the query in itself is correct
		return "SELECT DATE_FORMAT(FROM_UNIXTIME(SUM(UNIX_TIMESTAMP($name))), '%H:%i:%s') AS value, $label FROM
		`$table->db_table_name` $joinSQL $whereSQL";
	}

	/**
	 * Build the query for the avg calculation
	 *
	 * @param   model  &$listModel  list model
	 * @param   array  $labels      Labels
	 *
	 * @return  string	sql statement
	 */

	protected function getAvgQuery(&$listModel, $labels = array())
	{
		$label = count($labels) == 0 ? "'calc' AS label" : 'CONCAT(' . implode(', " & " , ', $labels) . ')  AS label';
		$table = $listModel->getTable();
		$db = $listModel->getDb();
		$joinSQL = $listModel->buildQueryJoin();
		$whereSQL = $listModel->buildQueryWhere();
		$name = $this->getFullName(false, false);

		return "SELECT DATE_FORMAT(FROM_UNIXTIME(AVG(UNIX_TIMESTAMP($name))), '%H:%i:%s') AS value, $label FROM " .
		$db->qn($table->db_table_name) . " $joinSQL $whereSQL";
	}

	/**
	 * Get a query for our median query
	 *
	 * @param   object  &$listModel  List
	 * @param   array   $labels      Label
	 *
	 * @return string
	 */

	protected function getMedianQuery(&$listModel, $labels = array())
	{
		$label = count($labels) == 0 ? "'calc' AS label" : 'CONCAT(' . implode(', " & " , ', $labels) . ')  AS label';
		$table = $listModel->getTable();
		$db = $listModel->getDbo();
		$joinSQL = $listModel->buildQueryJoin();
		$whereSQL = $listModel->buildQueryWhere();
		$name = $this->getFullName(false, false);

		return "SELECT DATE_FORMAT(FROM_UNIXTIME((UNIX_TIMESTAMP($name))), '%H:%i:%s') AS value, $label FROM
		" . $db->qn($table->db_table_name) . " $joinSQL $whereSQL";
	}

	/**
	 * Find the sum from a set of data
	 *
	 * @param   array  $data  to sum
	 *
	 * @return  string	sum result
	 */

	public function simpleSum($data)
	{
		$sum = 0;

		foreach ($data as $d)
		{
			if ($d != '')
			{
				$date = JFactory::getDate($d);
				$sum += $this->toSeconds($date);
			}
		}

		return $sum;
	}

	/**
	 * Get the value to use for graph calculations
	 * Timer converts the value into seconds
	 *
	 * @param   string  $v  standard value
	 *
	 * @return  mixed calculation value
	 */

	public function getCalculationValue($v)
	{
		if ($v == '')
		{
			return 0;
		}

		$date = JFactory::getDate($v);

		return $this->toSeconds($date);
	}
}
