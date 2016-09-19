<?php
/**
 * Count Element
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.count
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

require_once JPATH_SITE . '/components/com_fabrik/models/element.php';

/**
 * Plugin element to:
 * Counts records in a row - so adds "COUNT(x) .... GROUP BY (y)" to the main db query
 *
 * Note implementing this element will mean that only the first row of data is returned in
 * the joined group
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.count
 * @since       3.0
 */
class PlgFabrik_ElementCount extends PlgFabrik_Element
{
	/**
	 * Get group by query
	 *
	 * @return  string
	 */
	public function getGroupByQuery()
	{
		$params = $this->getParams();

		return $params->get('count_groupbyfield');
	}

	/**
	 * Create the SQL select 'name AS alias' segment for list/form queries
	 *
	 * @param   array  &$aFields    array of element names
	 * @param   array  &$aAsFields  array of 'name AS alias' fields
	 * @param   array  $opts        options
	 *
	 * @return  void
	 */
	public function getAsField_html(&$aFields, &$aAsFields, $opts = array())
	{
		$dbTable = $this->actualTableName();
		$db = FabrikWorker::getDbo();

		if ($this->app->input->get('c') != 'form')
		{
			$params = $this->getParams();
			$fullElName = FArrayHelper::getValue($opts, 'alias', $db->qn($dbTable . '___' . $this->getElement()->name));
			$r = 'COUNT(' . $params->get('count_field', '*') . ')';
			$aFields[] = $r . ' AS ' . $fullElName;
			$aAsFields[] = $fullElName;
			$aAsFields[] = $db->qn($dbTable . '___' . $this->getElement()->name . '_raw');
		}
	}

	/**
	 * Check if the user can use the active element
	 *
	 * @param   string  $location  To trigger plugin on
	 * @param   string  $event     To trigger plugin on
	 *
	 * @return  bool can use or not
	 */
	public function canUse($location = null, $event = null)
	{
		return false;
	}

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
		return '';
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
		$id = $this->getHTMLId($repeatCounter);
		$opts = $this->getElementJSOptions($repeatCounter);

		return array('FbCount', $id, $opts);
	}
}
