<?php
/**
 * Plugin element to render a timestamp
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.timestamp
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_SITE . '/components/com_fabrik/models/element.php';

/**
 * Plugin element to render a timestamp
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.timestamp
 * @since       3.0
 */

class PlgFabrik_ElementTimestamp extends PlgFabrik_Element
{
	/**
	 * If the element 'Include in search all' option is set to 'default' then this states if the
	 * element should be ignored from search all.
	 *
	 * @var bool  True, ignore in extended search all.
	 */
	protected $ignoreSearchAllDefault = true;

	/**
	 * Does the element's data get recorded in the db
	 *
	 * @var bool
	 */
	protected $recordInDatabase = false;

	/**
	 * Get the element's HTML label
	 *
	 * @param   int     $repeatCounter  Group repeat counter
	 * @param   string  $tmpl           Form template
	 *
	 * @return  string  label
	 */

	public function getLabel($repeatCounter, $tmpl = '')
	{
		return '';
	}

	/**
	 * Set/get if element should record its data in the database
	 *
	 * @deprecated - not used
	 *
	 * @return bool
	 */

	public function setIsRecordedInDatabase()
	{
		$this->recordInDatabase = false;
	}

	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           To pre-populate element with
	 * @param   int    $repeatCounter  Repeat group counter
	 *
	 * @return  string	elements html
	 */

	public function render($data, $repeatCounter = 0)
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
	 * @param   string    $data      elements data
	 * @param   stdClass  &$thisRow  all the data in the lists current row
	 *
	 * @return  string	formatted value
	 */

	public function renderListData($data, stdClass &$thisRow)
	{
		$params = $this->getParams();
		$tz_offset = $params->get('gmt_or_local', '0') == '0';
		$data = JHTML::_('date', $data, FText::_($params->get('timestamp_format', 'DATE_FORMAT_LC2')), $tz_offset);

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

	public function isHidden()
	{
		return true;
	}
}
