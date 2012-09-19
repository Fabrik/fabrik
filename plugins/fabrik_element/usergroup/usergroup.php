<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.usergroup
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * Plugin element to render multi select user group list
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.usergroup
 * @since       3.0.6
 */

class plgFabrik_ElementUsergroup extends plgFabrik_Element
{

	/** @var  string  db table field type */
	protected $fieldDesc = 'TEXT';

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
			$userid = $data[$userEl->getFullName(false, true, false) . '_raw'];
			$thisUser = JFactory::getUser($userid);
		}
		$selected = $this->getValue($data, $repeatCounter);
		if ($this->canUse())
		{
			return JHtml::_('access.usergroups', $name, $selected);
		}
		else
		{
			if ($userEl && !empty($thisUser->groups))
			{
				// Get the titles for the user groups.
				$db = JFactory::getDbo();
				$query = $db->getQuery(true);
				$query->select($db->quoteName('title'));
				$query->from($db->quoteName('#__usergroups'));
				$query->where($db->quoteName('id') . ' IN ( ' . implode(' , ', $thisUser->groups). ')');
				$db->setQuery($query);
				$selected = $db->loadColumn();
			}
			else
			{
				$selected = array();
			}
		}

		return implode(', ', $selected);
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
		$opts = json_encode(parent::getElementJSOptions($repeatCounter));
		$id = $this->getHTMLId($repeatCounter);
		return "new FbUsergroup('$id', $opts)";
	}

	/***
	 * Get the class to manage the form element
	 * if a plugin class requires to load another elements class (eg user for dbjoin then it should
	 * call FabrikModelElement::formJavascriptClass('plugins/fabrik_element/databasejoin/databasejoin.js', true);
	 * to ensure that the file is loaded only once
	 *
	 * @param   array   &$srcs   scripts previously loaded (load order is important as we are loading via head.js
	 * and in ie these load async. So if you this class extends another you need to insert its location in $srcs above the
	 * current file
	 * @param   string  $script  script to load once class has loaded
	 *
	 * @return void
	 */

	public function formJavascriptClass(&$srcs, $script = '')
	{
		plgFabrik_Element::formJavascriptClass($srcs, 'plugins/fabrik_element/usergroup/usergroup.js');
		parent::formJavascriptClass($srcs, $script);
	}

	/**
	* Determines the value for the element in the form view
	*
	* @param   array  $data           form data
	* @param   int    $repeatCounter  when repeating joinded groups we need to know what part of the array to access
	* @param   array  $opts           options
	*
	* @return  string	value
	*/

	public function getValue($data, $repeatCounter = 0, $opts = array())
	{
		// @TODO rename $this->defaults to $this->values
		if (!isset($this->defaults))
		{
			$this->defaults = array();
		}
		if (!array_key_exists($repeatCounter, $this->defaults))
		{
			$groupModel = $this->getGroup();
			$group = $groupModel->getGroup();
			$joinid = $this->isJoin() ? $this->getJoinModel()->getJoin()->id : $group->join_id;
			$formModel = $this->getFormModel();
			$element = $this->getElement();
			/**
			 * $$$rob - if no search form data submitted for the search element then the default
			 * selection was being applied instead
			 * otherwise get the default value so if we don't find the element's value in $data we fall back on this value
			 */
			$value = JArrayHelper::getValue($opts, 'use_default', true) == false ? '' : $this->getDefaultValue($data);

			$name = $this->getFullName(false, true, false);
			$rawname = $name . '_raw';
			if ($groupModel->isJoin() || $this->isJoin())
			{
				$nameKey = 'join.' . $joinid . '.' . $name;
				$rawNameKey = 'join.' . $joinid . '.' . $rawname;

				// $$$ rob 22/02/2011 this test barfed on fileuploads which weren't repeating
				// if ($groupModel->canRepeat() || !$this->isJoin()) {
				if ($groupModel->canRepeat())
				{
					$v = FArrayHelper::getNestedValue($data, $nameKey . '.' . $repeatCounter, null);
					if (is_null($v))
					{
						$v = FArrayHelper::getNestedValue($data, $rawNameKey . '.' . $repeatCounter, null);
					}
					if (!is_null($v))
					{
						$value = $v;
					}
				}
				else
				{
					$v = FArrayHelper::getNestedValue($data, $nameKey, null);
					if (is_null($v))
					{
						$v = FArrayHelper::getNestedValue($data, $rawNameKey, null);
					}
					if (!is_null($v))
					{
						$value = $v;
					}
					/* $$$ rob if you have 2 tbl joins, one repeating and one not
					 * the none repeating one's values will be an array of duplicate values
					* but we only want the first value
					*/

					if (is_array($value) && $this->isJoin() && !$groupModel->canRepeat())
					{
						$value = array_shift($value);
					}
				}
			}
			else
			{
				if ($groupModel->canRepeat())
				{
					// Repeat group NO join
					$thisname = $name;
					if (!array_key_exists($name, $data))
					{
						$thisname = $rawname;
					}
					if (array_key_exists($thisname, $data))
					{
						if (is_array($data[$thisname]))
						{
							// Occurs on form submission for fields at least
							$a = $data[$thisname];
						}
						else
						{
							// Occurs when getting from the db
							$a = json_decode($data[$thisname]);
						}
						$value = JArrayHelper::getValue($a, $repeatCounter, $value);
					}

				}
				else
				{
					$value = !is_array($data) ? $data : JArrayHelper::getValue($data, $name, JArrayHelper::getValue($data, $rawname, $value));
				}
			}

			if (!is_array($value))
			{
				$value = array($value);
			}
			/*@TODO perhaps we should change this to $element->value and store $element->default as the actual default value
			 *stops this getting called from form validation code as it messes up repeated/join group validations
			*/
			if (array_key_exists('runplugins', $opts) && $opts['runplugins'] == 1)
			{
				FabrikWorker::getPluginManager()->runPlugins('onGetElementDefault', $formModel, 'form', $this);
			}
			$this->defaults[$repeatCounter] = $value;
		}
		return $this->defaults[$repeatCounter];
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
		$data = FabrikWorker::JSONtoData($data, true);
		JArrayHelper::toInteger($data);
		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);
		if (!empty($data))
		{
			$query->select('title')->from('#__usergroups')->where('id IN (' . implode(',', $data) . ')');
			$db->setQuery($query);
			$data = $db->loadColumn();
		}
		$data = json_encode($data);
		return parent::renderListData($data, $thisRow);
	}

}
