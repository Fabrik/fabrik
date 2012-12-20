<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.timer
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

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

class plgFabrik_ElementTimer extends plgFabrik_Element
{

	public $hasSubElements = false;

	/** @var  string  db table field type */
	protected $fieldDesc = 'TIME';

	 // Jaanus: works better when using datatype 'TIME' as above and forgetting any date part of data :)

	/**
	 * Determines if the element can contain data used in sending receipts,
	 * e.g. fabrikfield returns true
	 *
	 * @return  bool
	 */

	public function isReceiptElement()
	{
		return true;
	}

	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           to preopulate element with
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return  string	elements html
	 */

	public function render($data, $repeatCounter = 0)
	{
		$name = $this->getHTMLName($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		$params = &$this->getParams();
		$element = $this->getElement();
		$size = $params->get('timer_width', 9);
		$value = $this->getValue($data, $repeatCounter);
		if ($value == '')
		{
			$value = '00:00:00';
		}
		else
		{
			$value = explode(" ", $value);
			$value = array_pop($value);
		}
		$type = "text";
		if (isset($this->_elementError) && $this->_elementError != '')
		{
			$type .= " elementErrorHighlight";
		}
		if ($element->hidden == '1')
		{
			$type = "hidden";
		}
		$sizeInfo = " size=\"$size\" ";
		if ($params->get('timer_readonly'))
		{
			$sizeInfo .= " readonly=\"readonly\" ";
			$type .= " readonly";
		}
		if (!$this->isEditable())
		{
			return ($element->hidden == '1') ? "<!-- " . $value . " -->" : $value;
		}

		$str = "<input class=\"fabrikinput inputbox $type\" type=\"$type\" name=\"$name\" id=\"$id\" $sizeInfo value=\"$value\" />\n";
		if (!$params->get('timer_readonly'))
		{
			$str .= "<input type=\"button\" id=\"{$id}_button\" value=\"" . JText::_('PLG_ELEMENT_TIMER_START') . "\" />";
		}
		return $str;
	}

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int  $repeatCounter  repeat group counter
	 *
	 * @return  string
	 */

	public function elementJavascript($repeatCounter)
	{
		$params = $this->getParams();
		$id = $this->getHTMLId($repeatCounter);
		$opts = $this->getElementJSOptions($repeatCounter);
		$opts->autostart = (bool)$params->get('timer_autostart', false);
		$opts = json_encode($opts);
		JText::script('PLG_ELEMENT_TIMER_START');
		JText::script('PLG_ELEMENT_TIMER_STOP');
		return "new FbTimer('$id', $opts)";
	}

	/**
	 * Get sum query
	 *
	 * @param   object  &$listModel  list model
	 * @param   string  $label       label
	 *
	 * @return string
	 */

	protected function getSumQuery(&$listModel, $label = "'calc'")
	{
		$table = $listModel->getTable();
		$joinSQL = $listModel->_buildQueryJoin();
		$whereSQL = $listModel->_buildQueryWhere();
		$name = $this->getFullName(false, false, false);

		// $$$rob not actaully likely to work due to the query easily exceeding mySQL's  TIMESTAMP_MAX_VALUE value but the query in itself is correct
		return "SELECT DATE_FORMAT(FROM_UNIXTIME(SUM(UNIX_TIMESTAMP($name))), '%H:%i:%s') AS value, $label AS label FROM `$table->db_table_name` $joinSQL $whereSQL";
	}

	/**
	 * Build the query for the avg calculation
	 *
	 * @param   model   &$listModel  list model
	 * @param   string  $label       the label to apply to each avg
	 *
	 * @return  string	sql statement
	 */

	protected function getAvgQuery(&$listModel, $label = "'calc'")
	{
		$table = &$listModel->getTable();
		$joinSQL = $listModel->_buildQueryJoin();
		$whereSQL = $listModel->_buildQueryWhere();
		$name = $this->getFullName(false, false, false);
		return "SELECT DATE_FORMAT(FROM_UNIXTIME(AVG(UNIX_TIMESTAMP($name))), '%H:%i:%s') AS value, $label AS label FROM `$table->db_table_name` $joinSQL $whereSQL";
	}

	/**
	 * Get a query for our media query
	 *
	 * @param   object  &$listModel  list
	 * @param   string  $label       label
	 *
	 * @return string
	 */

	protected function getMedianQuery(&$listModel, $label = "'calc'")
	{
		$table = $listModel->getTable();
		$joinSQL = $listModel->_buildQueryJoin();
		$whereSQL = $listModel->_buildQueryWhere();
		$name = $this->getFullName(false, false, false);
		return "SELECT DATE_FORMAT(FROM_UNIXTIME((UNIX_TIMESTAMP($name))), '%H:%i:%s') AS value, $label AS label FROM `$table->db_table_name` $joinSQL $whereSQL";
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
