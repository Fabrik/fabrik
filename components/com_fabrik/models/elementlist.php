<?php

/**
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');
jimport('joomla.filesystem.file');

/**
 * Fabrik Element List Model
 *
 * @package  Fabrik
 * @since    3.0
 */

class plgFabrik_ElementList extends plgFabrik_Element
{

	public $hasSubElements = true;

	public $defaults = null;

	/** @var  string  db table field type */
	protected $fieldDesc = 'TEXT';

	/** @var  string  db table field size */
	protected $inputType = 'radio';

	/** @var bool - should the table render functions use html to display the data */
	public $renderWithHTML = true;

	/**
	 * Determines the label used for the browser title
	 * in the form/detail views
	 *
	 * @param   array  $data           form data
	 * @param   int    $repeatCounter  when repeating joinded groups we need to know what part of the array to access
	 * @param   array  $opts           options
	 *
	 * @return  string	default value
	 */

	public function getTitlePart($data, $repeatCounter = 0, $opts = array())
	{
		$val = $this->getValue($data, $repeatCounter, $opts);
		$labels = $this->getSubOptionLabels();
		$values = $this->getSubOptionValues();
		$str = '';
		if (is_array($val))
		{
			foreach ($val as $tmpVal)
			{
				$key = array_search($tmpVal, $values);
				$str .= ($key === false) ? $tmpVal : $labels[$key];
				$str .= ' ';
			}
		}
		else
		{
			$str = $val;
		}
		return $str;
	}

	/**
	 * Get sub elements initial selection
	 *
	 * @return  array  initially selected values
	 */

	public function getSubInitialSelection()
	{
		$params = $this->getParams();
		$opts = $params->get('sub_options');
		$r = isset($opts->sub_initial_selection) ? (array) $opts->sub_initial_selection : array();
		return $r;
	}

	/**
	 * Does the element conside the data to be empty
	 * Used in isempty validation rule
	 *
	 * @param   array  $data           data to test against
	 * @param   int    $repeatCounter  repeat group #
	 *
	 * @return  bool
	 */

	public function dataConsideredEmpty($data, $repeatCounter)
	{
		$data = (array) $data;
		foreach ($data as $d)
		{
			if ($d != '')
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * This really does get just the default value (as defined in the element's settings)
	 *
	 * @param   array  $data  form data
	 *
	 * @return mixed
	 */

	public function getDefaultValue($data = array())
	{
		$params = $this->getParams();
		$opts = $params->get('sub_options');
		if (!isset($this->_default))
		{
			if (isset($opts->sub_initial_selection))
			{
				$this->_default = $this->getSubInitialSelection();
			}
			else
			{
				$this->_default = parent::getDefaultValue($data);
			}
		}
		return $this->_default;
	}

	/**
	 * Get the table filter for the element
	 *
	 * @param   int   $counter  filter order
	 * @param   bool  $normal   do we render as a normal filter or as an advanced search filter
	 * if normal include the hidden fields as well (default true, use false for advanced filter rendering)
	 *
	 * @return  string	filter html
	 */

	public function getFilter($counter = 0, $normal = true)
	{
		$element = $this->getElement();
		$values = $this->getSubOptionValues();
		$default = $this->getDefaultFilterVal($normal, $counter);
		$elName = $this->getFullName(false, true, false);
		$htmlid = $this->getHTMLId() . 'value';
		$listModel = $this->getListModel();
		$params = $this->getParams();
		$v = $this->filterName($counter, $normal);
		if (in_array($element->filter_type, array('range', 'dropdown', '')))
		{
			$rows = $this->filterValueList($normal);
			JArrayHelper::sortObjects($rows, $params->get('filter_groupby', 'text'));
			if (!in_array('', $values))
			{
				array_unshift($rows, JHTML::_('select.option', '', $this->filterSelectLabel()));
			}
		}

		$attribs = 'class="inputbox fabrik_filter" size="1" ';
		$size = $params->get('filter_length', 20);
		$return = array();
		switch ($element->filter_type)
		{
			case "range":
				if (!is_array($default))
				{
					$default = array('', '');
				}
				$return[] = JHTML::_('select.genericlist', $rows, $v . '[]', $attribs, 'value', 'text', $default[0],
					$element->name . "_filter_range_0");
				$return[] = JHTML::_('select.genericlist', $rows, $v . '[]', $attribs, 'value', 'text', $default[1],
					$element->name . "_filter_range_1");
				break;
			case "dropdown":
			default:
				$return[] = JHTML::_('select.genericlist', $rows, $v, $attribs, 'value', 'text', $default, $htmlid);
				break;

			case "field":
				if (get_magic_quotes_gpc())
				{
					$default = stripslashes($default);
				}
				$default = htmlspecialchars($default);
				$return[] = '<input type="text" name="' . $v . '" class="inputbox fabrik_filter" size="' . $size . '" value="' . $default . '" id="'
					. $htmlid . '" />';
				break;

			case "hidden":
				if (get_magic_quotes_gpc())
				{
					$default = stripslashes($default);
				}
				$default = htmlspecialchars($default);
				$return[] = '<input type="hidden" name="' . $v . '" class="inputbox fabrik_filter" value="' . $default . '" id="' . $htmlid . '" />';
				break;

			case 'auto-complete':
				if (get_magic_quotes_gpc())
				{
					$default = stripslashes($default);
				}
				$default = htmlspecialchars($default);
				$return = '<input type="hidden" name="' . $v . '" class="inputbox fabrik_filter ' . $htmlid . '" value="' . $default . '" />';
				$return .= '<input type="text" name="' . $v . '-auto-complete" class="inputbox fabrik_filter autocomplete-trigger ' . $htmlid
					. '-auto-complete" size="' . $size . '" value="' . $default . '" />';
				$selector = '#list_' . $listModel->getRenderContext() . ' .' . $htmlid;
				FabrikHelperHTML::autoComplete($selector, $this->getElement()->id);

				break;
		}
		$return[] = $normal ? $this->getFilterHiddenFields($counter, $elName) : $this->getAdvancedFilterHiddenFields();
		return implode("\n", $return);
	}

	/**
	 * Get an array of element html ids and their corresponding
	 * js events which trigger a validation.
	 * Examples of where this would be overwritten include timedate element with time field enabled
	 *
	 * @param   int  $repeatCounter  repeat group counter
	 *
	 * @return  array  html ids to watch for validation
	 */

	public function getValidationWatchElements($repeatCounter)
	{
		$id = $this->getHTMLId($repeatCounter);
		$ar = array('id' => $id, 'triggerEvent' => 'click');
		return array($ar);
	}

	/**
	 * Turn form value into email formatted value
	 *
	 * @param   mixed  $value          element value
	 * @param   array  $data           form data
	 * @param   int    $repeatCounter  group repeat counter
	 *
	 * @return  string  email formatted value
	 */

	protected function _getEmailValue($value, $data = array(), $repeatCounter = 0)
	{
		$params = $this->getParams();
		$split_str = $params->get('options_split_str', '');
		$element = $this->getElement();
		$values = $this->getSubOptionValues();
		$labels = $this->getSubOptionLabels();
		$aLabels = array();
		if (is_string($value))
		{
			$value = array($value);
		}
		if (is_array($value))
		{
			foreach ($value as $tmpVal)
			{
				$key = array_search($tmpVal, $values);
				if ($key !== false)
				{
					$aLabels[] = $labels[$key];
				}
			}
		}
		if ($split_str == '')
		{
			$val = '<ul><li>' . implode('</li><li>', $aLabels) . '</li></ul>';
		}
		else
		{
			$val = implode($split_str, $aLabels);
		}
		if ($val === '')
		{
			$val = $params->get('sub_default_label');
		}
		return $val;
	}

	/**
	 * Used by radio and dropdown elements to get a dropdown list of their unique
	 * unique values OR all options - basedon filter_build_method
	 *
	 * @param   bool    $normal     do we render as a normal filter or as an advanced search filter
	 * @param   string  $tableName  table name to use - defaults to element's current table
	 * @param   string  $label      field to use, defaults to element name
	 * @param   string  $id         field to use, defaults to element name
	 * @param   bool    $incjoin    include join
	 *
	 * @return  array  text/value objects
	 */

	public function filterValueList($normal, $tableName = '', $label = '', $id = '', $incjoin = true)
	{
		$rows = parent::filterValueList($normal, $tableName, $label, $id, $incjoin);
		$this->unmergeFilterSplits($rows);
		$this->reapplyFilterLabels($rows);
		return $rows;
	}

	/**
	 * Ajax call to get auto complete options
	 *
	 * @return  string  json encoded options
	 */

	public function onAutocomplete_options()
	{
		// Needed for ajax update (since we are calling this method via dispatcher element is not set
		$this->setId(JRequest::getInt('element_id'));
		$this->getElement(true);
		$listModel = $this->getListModel();
		$rows = $this->filterValueList(true);
		$v = addslashes(JRequest::getVar('value'));
		$start = count($rows) - 1;
		for ($i = $start; $i >= 0; $i--)
		{
			if (!preg_match("/$v(.*)/i", $rows[$i]->text))
			{
				unset($rows[$i]);
			}
		}
		$rows = array_values($rows);
		echo json_encode($rows);
	}

	/**
	 * will the element allow for multiple selections
	 *
	 * @since	3.0.6
	 *
	 * @return  bool
	 */

	protected function isMultiple()
	{
		$params = $this->getParams();
		return $params->get('multiple', 0) || $this->isJoin();
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
		$element = $this->getElement();
		$params = $this->getParams();
		$listModel = $this->getListModel();
		$multiple = $this->isMultiple();
		$mergeGroupRepeat = ($this->getGroup()->canRepeat() && $this->getListModel()->mergeJoinedData());
		$sLabels = array();

		// Repeat group data
		$gdata = FabrikWorker::JSONtoData($data, true);
		$uls = array();
		$useIcon = $params->get('icon_folder', 0);
		foreach ($gdata as $i => $d)
		{
			$lis = array();
			$vals = is_array($d) ? $d : FabrikWorker::JSONtoData($d, true);

			foreach ($vals as $val)
			{
				$l = $useIcon ? $this->_replaceWithIcons($val, 'list', $listModel->getTmpl()) : $val;
				if (!$this->iconsSet == true)
				{
					$l = $this->getLabelForValue($val);
					$l = $this->_replaceWithIcons($l, 'list', $listModel->getTmpl());
				}
				$l = $this->rollover($l, $thisRow, 'list');
				$l = $listModel->_addLink($l, $this, $thisRow, $i);
				if (trim($l) !== '')
				{
					$lis[] = $multiple || $mergeGroupRepeat ? '<li>' . $l . '</li>' : $l;
				}
			}
			if (!empty($lis))
			{
				$uls[] = ($multiple && $this->renderWithHTML) ? '<ul class="fabrikRepeatData">' . implode(' ', $lis) . '</ul>' : implode(' ', $lis);
			}
		}
		// $$$rob if only one repeat group data then dont bother encasing it in a <ul>
		return ((count($gdata) !== 1 || $mergeGroupRepeat) && $this->renderWithHTML) ? '<ul class="fabrikRepeatData">' . implode(' ', $uls) . '</ul>'
			: implode(' ', $uls);
	}

	/**
	 * Prepares the element data for CSV export
	 *
	 * @param   string  $data      element data
	 * @param   object  &$thisRow  all the data in the lists current row
	 *
	 * @return  string	formatted value
	 */

	public function renderListData_csv($data, &$thisRow)
	{
		$this->renderWithHTML = false;
		$d = $this->renderListData($data, $thisRow);
		$this->renderWithHTML = true;
		return $d;
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
		$params = $this->getParams();
		$values = $this->getSubOptionValues();
		$labels = $this->getSubOptionLabels();

		/**
		 * $$$ hugh -- working on issue with radio and checkbox, where extra blank subitem gets added
		 * if nothing selected.  this fix assumes that 'value' cannot be empty string for sub-options,
		 * and I'm not sure if we enforce that.  Problem being that if we just cast directly to
		 * an array, the array isn't "empty()", as it has a single, empty string entry.  So then
		 * the array_diff() we're about to do sees that as a diff.
		 */
		$selected = $this->getValue($data, $repeatCounter);
		if (is_string($selected) || is_null($selected))
		{

			// $$$ hugh - ooops, '0' will count as empty.
			// $selected = empty($selected) ?  array() : array($selected);
			$selected = $selected === '' ? array() : array($selected);
		}
		// $$$ rob 06/10/2011 if front end add option on, but added option not saved we should add in the selected value to the
		// values and labels.
		$diff = array_diff($selected, $values);
		if (!empty($diff))
		{
			$values = array_merge($values, $diff);

			// Swap over the default value to the default label
			if (!$this->_editable)
			{
				foreach ($diff as &$di)
				{
					if ($di === $params->get('sub_default_value'))
					{
						$di = $params->get('sub_default_label');
					}
				}
			}
			$labels = array_merge($labels, $diff);
		}
		if (!$this->_editable)
		{
			$aRoValues = array();
			for ($i = 0; $i < count($values); $i++)
			{
				if (in_array($values[$i], $selected))
				{
					$aRoValues[] = $this->getReadOnlyOutput($values[$i], $labels[$i]);
				}
			}
			$splitter = ($params->get('icon_folder') != -1 && $params->get('icon_folder') != '') ? ' ' : ', ';
			return ($this->isMultiple() && $this->renderWithHTML)
				? '<ul class="fabrikRepeatData"><li>' . implode('</li><li>', $aRoValues) . '</li></ul>' : implode($splitter, $aRoValues);
		}

		// Remove the default value
		$key = array_search($params->get('sub_default_value'), $values);
		if ($key)
		{
			unset($values[$key]);
		}

		$optionsPerRow = (int) $this->getParams()->get('options_per_row', 4);
		$elBeforeLabel = (bool) $this->getParams()->get('element_before_label', true);

		// Element_before_label
		if (JRequest::getVar('format') == 'raw')
		{
			$optionsPerRow = 1;
		}
		$grid = FabrikHelperHTML::grid($values, $labels, $selected, $name, $this->inputType, $elBeforeLabel, $optionsPerRow);

		array_unshift($grid, '<div class="fabrikSubElementContainer" id="' . $id . '">');

		$grid[] = '</div>';
		if ($params->get('allow_frontend_addto', false))
		{
			$onlylabel = $params->get('allowadd-onlylabel');
			$grid[] = $this->getAddOptionFields($repeatCounter, $onlylabel);
		}
		return implode("\n", $grid);
	}

	/**
	 * Should the sub label appear before or after the sub element?
	 *
	 * @return  bool
	 */

	protected function getElementBeforeLabel()
	{
		return (bool) $this->getParams()->get('radio_element_before_label', true);
	}

	/**
	 * called from within function getValue
	 * needed so we can append _raw to the name for elements such as db joins
	 *
	 * @param   array  $opts  options
	 *
	 * @return  string  element name inside data array
	 */

	protected function getValueFullName($opts)
	{
		return $this->getFullName(false, true, false);
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
		$data = (array) $data;
		if (!isset($this->defaults))
		{
			$this->defaults = array();
		}

		/*
		 *  $$$ rob 20/08/2012 - added $data to serialized key
		 *  Seems that db join _getOptionVals() _autocomplete_where is getting run a couple of times with key and labels being passed in
		 */
		$valueKey = $repeatCounter . serialize($opts) . serialize($data);
		if (!array_key_exists($valueKey, $this->defaults))
		{
			$value = '';
			$groupModel = $this->getGroupModel();
			$group = $groupModel->getGroup();
			$joinid = $this->isJoin() ? $this->getJoinModel()->getJoin()->id : $group->join_id;
			$formModel = $this->getForm();
			$element = $this->getElement();

			// $$$rob - if no search form data submitted for the checkbox search element then the default
			// selecton was being applied instead
			$value = JArrayHelper::getValue($opts, 'use_default', true) == false ? '' : $this->getDefaultValue($data);

			$name = $this->getValueFullName($opts);

			// $name could already be in _raw format - so get inverse name e.g. with or without raw
			$rawname = JString::substr($name, -4) === '_raw' ? JString::substr($name, 0, -4) : $name . '_raw';
			if ($groupModel->isJoin() || $this->isJoin())
			{
				$nameKey = 'join.' . $joinid . '.' . $name;
				$rawNameKey = 'join.' . $joinid . '.' . $rawname;
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
					if (is_array($value) && (array_key_exists(0, $value) && is_array($value[0])))
					{
						// Fix for http://fabrikar.com/forums/showthread.php?t=23568&page=2
						$value = $value[0];
					}
				}
			}
			else
			{
				if ($groupModel->canRepeat())
				{
					// Can repeat NO join
					if (array_key_exists($name, $data))
					{
						// Occurs on form submission for fields at least : occurs when getting from the db
						$a = is_array($data[$name]) ? $a = $data[$name] : FabrikWorker::JSONtoData($data[$name], true);
						$value = JArrayHelper::getValue($a, $repeatCounter, $value);
					}
					elseif (array_key_exists($rawname, $data))
					{
						// Occurs on form submission for fields at least : occurs when getting from the db
						$a = is_array($data[$rawname]) ? $a = $data[$rawname] : FabrikWorker::JSONtoData($data[$rawname], true);
						$value = JArrayHelper::getValue($a, $repeatCounter, $value);
					}
				}
				else
				{
					if (array_key_exists($name, $data))
					{
						// Put this back in for radio button after failed validation not picking up previously selected option
						$value = $data[$name];
					}
					elseif (array_key_exists($rawname, $data))
					{
						$value = $data[$rawname];
					}
				}
			}
			if ($value === '')
			{
				// Query string for joined data
				$value = JArrayHelper::getValue($data, $name);
			}
			/**
			 * $$$ hugh -- added this so we are consistent in what we return, otherwise uninitialized values,
			 * i.e. if you've added a checkbox element to a form with existing data, don't get set, and causes
			 * issues with methods that call getValue().
			 */
			if (!isset($value))
			{
				$value = '';
			}
			// $$$ corner case where you have a form and a list for the same table on the same page
			// and the list is being filtered with table___name[value]=foo on the query string.
			if (is_array($value) && array_key_exists('value', $value))
			{
				$value = $value['value'];
			}
			$element->default = $value;
			$formModel = $this->getForm();

			// Stops this getting called from form validation code as it messes up repeated/join group validations
			if (array_key_exists('runplugins', $opts) && $opts['runplugins'] == 1)
			{
				FabrikWorker::getPluginManager()->runPlugins('onGetElementDefault', $formModel, 'form', $this);
			}
			if (is_string($element->default))
			{
				// $$$ rob changed to false below as when saving encrypted data a stored valued of 62
				// Was being returned as [62], then [[62]] etc.
				$element->default = FabrikWorker::JSONtoData($element->default, false);
			}
			$this->defaults[$valueKey] = $element->default;
		}
		return $this->defaults[$valueKey];
	}

	/**
	 * Is the dropdowns cnn the same as the main Joomla db
	 *
	 * @return  bool
	 */

	protected function inJDb()
	{
		return $this->getlistModel()->inJDb();
	}

	/**
	 * format the read only output for the page
	 *
	 * @param   string  $value  initial value
	 * @param   string  $label  label
	 *
	 * @return  string  read only value
	 */

	protected function getReadOnlyOutput($value, $label)
	{
		$params = $this->getParams();
		if ($params->get('icon_folder') != -1 && $params->get('icon_folder') != '')
		{
			$icon = $this->_replaceWithIcons($value);
			if ($this->iconsSet)
			{
				$label = $icon;
			}
		}
		return $label;
	}

	/**
	 * Trigger called when a row is stored.
	 * Check if new options have been added and if so store them in the element for future use.
	 *
	 * @param   array  &$data  to store
	 *
	 * @return  void
	 */

	public function onStoreRow(&$data)
	{
		$element = $this->getElement();
		$params = $this->getParams();
		if ($params->get('savenewadditions') && array_key_exists($element->name . '_additions', $data))
		{
			$added = stripslashes($data[$element->name . '_additions']);
			if (trim($added) == '')
			{
				return;
			}
			$added = json_decode($added);
			$values = $this->getSubOptionValues();
			$labels = $this->getSubOptionLabels();
			$found = false;
			foreach ($added as $obj)
			{
				if (!in_array($obj->val, $values))
				{
					$values[] = $obj->val;
					$found = true;
					$labels[] = $obj->label;
				}
			}
			if ($found)
			{
				$opts = $params->get('sub_options');
				$opts->sub_values = $values;
				$opts->sub_labels = $labels;

				// $$$ rob dont json_encode this - the params object has its own toString() magic method
				$element->params = (string) $params;
				$element->store();
			}
		}
	}

	/**
	 * get the class to manage the form element
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
		$files = array('media/com_fabrik/js/element.js', 'media/com_fabrik/js/elementlist.js');
		foreach ($files as $file)
		{
			if (!in_array($file, $srcs))
			{
				$srcs[] = $file;
			}
		}
		parent::formJavascriptClass($srcs, $script);
	}

}
