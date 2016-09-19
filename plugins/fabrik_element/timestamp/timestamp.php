<?php
/**
 * Plugin element to render a timestamp
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.timestamp
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
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
		$date = JFactory::getDate();
		$tz = new DateTimeZone($this->config->get('offset'));
		$date->setTimezone($tz);
		$params = $this->getParams();
		$gmtOrLocal = $params->get('gmt_or_local');
		$gmtOrLocal += 0;

		$layout = $this->getLayout('form');
		$layoutData = new stdClass;
		$layoutData->id =  $this->getHTMLId($repeatCounter);;
		$layoutData->name = $this->getHTMLName($repeatCounter);;
		$layoutData->value = $date->toSql($gmtOrLocal);

		return $layout->render($layoutData);
	}

	/**
	 * Shows the data formatted for the list view
	 *
	 * @param   string    $data      Elements data
	 * @param   stdClass  &$thisRow  All the data in the lists current row
	 * @param   array     $opts      Rendering options
	 *
	 * @return  string	formatted value
	 */
	public function renderListData($data, stdClass &$thisRow, $opts = array())
	{
		$params = $this->getParams();
		$tz_offset = $params->get('gmt_or_local', '0') == '0';
		$data = JHTML::_('date', $data, FText::_($params->get('timestamp_format', 'DATE_FORMAT_LC2')), $tz_offset);

		return parent::renderListData($data, $thisRow, $opts);
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
