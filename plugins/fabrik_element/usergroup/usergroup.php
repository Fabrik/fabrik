<?php
/**
 * Plugin element to render multi select user group list
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.usergroup
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Plugin element to render multi select user group list
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.usergroup
 * @since       3.0.6
*/

class PlgFabrik_ElementUsergroup extends PlgFabrik_ElementList
{

	/**
	 * Db table field type
	 *
	 * @var string
	 */
	protected $fieldDesc = 'TEXT';

	/**
	 * Array of id, label's queried from #__usergroups
	 *
	 * @var array
	 */
	protected $allOpts = null;

	/**
	 * Does the element contain sub elements e.g checkboxes radiobuttons
	 *
	 * @var bool
	 */
	public $hasSubElements = false;

	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           To preopulate element with
	 * @param   int    $repeatCounter  Repeat group counter
	 *
	 * @return  string	elements html
	 */

	public function render($data, $repeatCounter = 0)
	{
		$element = $this->getElement();
		$name = $this->getHTMLName($repeatCounter);
		$html_id = $this->getHTMLId($repeatCounter);
		$id = $html_id;
		$params = $this->getParams();

		$formModel = $this->getFormModel();
		$userEl = $formModel->getElement($params->get('user_element'), true);
		if ($userEl)
		{
			$data = $formModel->getData();
			$userid = JArrayHelper::getValue($data, $userEl->getFullName(true, false) . '_raw', 0);

			// Failed validation
			if (is_array($userid))
			{
				$userid = JArrayHelper::getValue($userid, 0);
			}
			$thisUser = JFactory::getUser($userid);
		}
		$selected = $this->getValue($data, $repeatCounter);
		if (is_string($selected))
		{
			$selected = json_decode($selected);
		}
		if ($this->isEditable())
		{
			return JHtml::_('access.usergroups', $name, $selected);
		}
		else
		{
			if ($userEl)
			{
				$selected = $thisUser->groups;
			}

			// Get the titles for the user groups.
			if (count($selected) > 0)
			{
				$db = JFactory::getDbo();
				$query = $db->getQuery(true);
				$query->select($db->quoteName('title'));
				$query->from($db->quoteName('#__usergroups'));
				$query->where($db->quoteName('id') . ' IN ( ' . implode(' , ', $selected) . ')');
				$db->setQuery($query);
				$selected = $db->loadColumn();
			}
		}

		return implode(', ', $selected);
	}

	/**
	 * Get sub option values
	 *
	 * @return  array
	 */

	protected function getSubOptionValues()
	{
		$opts = $this->allOpts();
		$return = array();
		foreach ($opts as $opt)
		{
			$return[] = $opt->id;
		}
		return $return;
	}

	/**
	 * Get sub option labels
	 *
	 * @return  array
	 */

	protected function getSubOptionLabels()
	{
		$opts = $this->allOpts();
		$return = array();
		foreach ($opts as $opt)
		{
			$return[] = $opt->title;
		}
		return $return;
	}

	/**
	 * Create an array of label/values which will be used to populate the elements filter dropdown
	 * returns only data found in the table you are filtering on
	 *
	 * @param   bool    $normal     Do we render as a normal filter or as an advanced search filter
	 * @param   string  $tableName  Table name to use - defaults to element's current table
	 * @param   string  $label      Field to use, defaults to element name
	 * @param   string  $id         Field to use, defaults to element name
	 * @param   bool    $incjoin    Include join
	 *
	 * @return  array	Filter value and labels
	 */

	protected function filterValueList_Exact($normal, $tableName = '', $label = '', $id = '', $incjoin = true)
	{
		$listModel = $this->getListModel();
		$table = $listModel->getTable();
		$elName2 = $this->getFullName(false, false, false);
		$tmpIds = $listModel->getColumnData($elName2);
		$ids = array();
		foreach ($tmpIds as $tmpId)
		{
			$tmpId = FabrikWorker::JSONtoData($tmpId, true);
			$ids = array_merge($ids, $tmpId);
		}
		$ids = array_unique($ids);
		$opts = $this->allOpts();
		$return = array();
		foreach ($ids as $id)
		{
			if (array_key_exists($id, $opts))
			{
				// 3.0 its an array - 3.1 its an object
				$opt = new stdClass;
				$opt->value = $id;
				$opt->text = $opts[$id]->title;
				$return[] = $opt;
			}
		}
		return $return;
	}

	/**
	 * Get all user groups (id/title)
	 *
	 * @return  array
	 */
	private function allOpts()
	{
		if (!isset($this->allOpts))
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('id, title');
			$query->from($db->quoteName('#__usergroups'));
			$db->setQuery($query);
			$this->allOpts = $db->loadObjectList('id');
		}
		return $this->allOpts;
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
		$opts = parent::getElementJSOptions($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		return array('FbUsergroup', $id, $opts);
	}

	/**
	 * Determines the value for the element in the form view
	 *
	 * @param   array  $data           Form data
	 * @param   int    $repeatCounter  When repeating joinded groups we need to know what part of the array to access
	 * @param   array  $opts           Options
	 *
	 * @return  string	value
	 */

	public function getValue($data, $repeatCounter = 0, $opts = array())
	{
		$value = parent::getValue($data, $repeatCounter, $opts);
		$value = FabrikWorker::JSONtoData($value);
		if (is_string($value))
		{
			// New record or failed validation
			$value = trim($value);
			$value = $value === '' ? array() : explode(',', $value);
		}
		return $value;
	}

	/**
	 * This really does get just the default value (as defined in the element's settings)
	 *
	 * @param   array  $data  Form data
	 *
	 * @return mixed
	 */

	public function getDefaultValue($data = array())
	{
		if (!isset($this->_default))
		{
			$user = JFactory::getUser();
			$this->_default = $user->get('groups');
			$this->_default = array_values($this->_default);
			$this->_default = json_encode($this->_default);
		}
		return $this->_default;
	}

	/**
	 * Create an array of label/values which will be used to populate the elements filter dropdown
	 * returns all possible options
	 *
	 * @param   bool    $normal     Do we render as a normal filter or as an advanced search filter
	 * @param   string  $tableName  Table name to use - defaults to element's current table
	 * @param   string  $label      Field to use, defaults to element name
	 * @param   string  $id         Field to use, defaults to element name
	 * @param   bool    $incjoin    Include join
	 *
	 * @return  array	Filter value and labels
	 */

	protected function filterValueList_All($normal, $tableName = '', $label = '', $id = '', $incjoin = true)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('id, title');
		$query->from($db->quoteName('#__usergroups'));
		$db->setQuery($query);
		$selected = $db->loadObjectList();

		for ($i = 0; $i < count($selected); $i++)
		{
			$return[] = JHTML::_('select.option', $selected[$i]->id, $selected[$i]->title);
		}
		return $return;
	}

}
