<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.timestamp
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once JPATH_SITE . '/components/com_fabrik/models/element.php';

/**
 * Plugin element to render a timestamp
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.timestamp
 * @since       3.0
 */

class plgFabrik_ElementTimestamp extends plgFabrik_Element
{

	/**
	 * If the element 'Include in search all' option is set to 'default' then this states if the
	 * element should be ignored from search all.
	 * @var bool  True, ignore in advanced search all.
	 */
	protected $ignoreSearchAllDefault = true;

	var $_recordInDatabase = false;

	/**
	 * Get the element's HTML label
	 *
	 * @param   int     $repeatCounter  group repeat counter
	 * @param   string  $tmpl           form template
	 *
	 * @return  string  label
	 */

	public function getLabel($repeatCounter, $tmpl = '')
	{
		return '';
	}

	/**
	 * Set/get if element should record its data in the dabase
	 *
	 * @deprecated - not used
	 *
	 * @return bool
	 */

	public function setIsRecordedInDatabase()
	{
		$this->_recordInDatabase = false;
	}

	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           to preopulate element with
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return  string	elements html
	 */

	function render($data, $repeatCounter = 0)
	{
		$name = $this->getHTMLName($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		$date = JFactory::getDate();
		$config = JFactory::getConfig();
		$tz = new DateTimeZone($config->get('offset'));
		$date->setTimezone($tz);
		$params = $this->getParams();
		$gmt_or_local = $params->get('gmt_or_local');
		$gmt_or_local += 0;
		return '<input name="' . $name . '" id="' . $id . '" type="hidden" value="' . $date->toSql($gmt_or_local) . '" />';
	}

	/**
	 * Shows the data formatted for the list view
	 *
	 * @param   string  $data      elements data
	 * @param   object  &$thisRow  all the data in the lists current row
	 *
	 * @return  string	formatted value
	 */

	public function renderListData($data, &$thisRow)
	{
		$params = $this->getParams();
		$tz_offset = $params->get('gmt_or_local', '0') == '0';
		$data = JHTML::_('date', $data, JText::_($params->get('timestamp_format', 'DATE_FORMAT_LC2')), $tz_offset);
		return parent::renderListData($data, $thisRow);
	}

	/**
	 * Get database field description
	 *
	 * @return  string  db field type
	 */

	public function getFieldDescription()
	{
		$params = $this->getParams();
		if ($params->get('encrypt', false))
		{
			return 'BLOB';
		}
		if ($params->get('timestamp_update_on_edit'))
		{
			return "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
		}
		else
		{
			return "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP";
		}
	}

	/**
	 * Is the element hidden or not - if not set then return false
	 *
	 * @return  bool
	 */

	protected function isHidden()
	{
		return true;
	}

}
