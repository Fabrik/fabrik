<?php
/**
 * Plugin element to yes/no radio options - render as tick/cross in list view
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.yesno
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_SITE . '/components/com_fabrik/models/element.php';
require_once JPATH_SITE . '/plugins/fabrik_element/radiobutton/radiobutton.php';

/**
 * Plugin element to yes/no radio options - render as tick/cross in list view
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.yesno
 * @since       3.0
 */

class PlgFabrik_ElementYesno extends PlgFabrik_ElementRadiobutton
{
	/**
	 * Db table field type
	 *
	 * @var string
	 */
	protected $fieldDesc = 'INT(%s)';

	/**
	 * Db table field size
	 *
	 * @var string
	 */
	protected $fieldSize = '1';

	/**
	 * This really does get just the default value (as defined in the element's settings)
	 *
	 * @param   array  $data  Form data
	 *
	 * @return mixed
	 */

	public function getDefaultValue($data = array())
	{
		if (!isset($this->default))
		{
			$params = $this->getParams();
			$this->default = $params->get('yesno_default', 0);
		}

		return $this->default;
	}

	/**
	 * Shows the data formatted for the list view
	 *
	 * @param   string  $data      Elements data
	 * @param   object  &$thisRow  All the data in the lists current row
	 *
	 * @return  string	formatted value
	 */

	public function renderListData($data, stdClass &$thisRow)
	{
		FabrikHelperHTML::addPath(COM_FABRIK_BASE . 'plugins/fabrik_element/yesno/images/', 'image', 'list', false);

		// Check if the data is in csv format, if so then the element is a multi drop down
		$raw = $this->getFullName(true, false) . '_raw';
		$data = $thisRow->$raw;
		$j3 = FabrikWorker::j3();

		if ($data == '1')
		{
			$icon = $j3 ? 'checkmark.png' : '1.png';
			$opts = array('alt' => FText::_('JYES'));
			$opts['icon-class'] = 'icon-ok-sign';

			return FabrikHelperHTML::image($icon, 'list', @$this->tmpl, $opts);
		}
		else
		{
			$icon = $j3 ? 'remove.png' : '0.png';

			return FabrikHelperHTML::image($icon, 'list', @$this->tmpl, array('alt' => FText::_('JNO')));
		}
	}

	/**
	 * Shows the data formatted for the table view with format = pdf
	 * note pdf lib doesn't support transparent pngs hence this func
	 *
	 * @param   string  $data     Cell data
	 * @param   object  $thisRow  Row data
	 *
	 * @return string formatted value
	 */

	public function renderListData_pdf($data, $thisRow)
	{
		FabrikHelperHTML::addPath(COM_FABRIK_BASE . 'plugins/fabrik_element/yesno/images/', 'image', 'list', false);
		$raw = $this->getFullName() . '_raw';
		$data = $thisRow->$raw;
		$j3 = FabrikWorker::j3();

		if ($data == '1')
		{
			$icon = $j3 ? 'checkmark.png' : '1_8bit.png';

			return FabrikHelperHTML::image($icon, 'list', $this->tmpl, array('alt' => FText::_('JYES')));
		}
		else
		{
			$icon = $j3 ? 'remove.png' : '0_8bit.png';

			return FabrikHelperHTML::image($icon, 'list', $this->tmpl, array('alt' => FText::_('JNO')));
		}
	}

	/**
	 * Prepares the element data for CSV export
	 *
	 * @param   string  $data      Element data
	 * @param   object  &$thisRow  All the data in the lists current row
	 *
	 * @return  string	formatted value
	 */

	public function renderListData_csv($data, &$thisRow)
	{
		$raw = $this->getFullName(true, false) . '_raw';
		$rawdata = $thisRow->$raw;
		$data = $rawdata ? $data : FText::_('JNO');

		return $data;
	}

	/**
	 * Get sub option values
	 *
	 * @return  array
	 */

	protected function getSubOptionValues()
	{
		return array(0, 1);
	}

	/**
	 * Get sub option labels
	 *
	 * @return  array
	 */

	protected function getSubOptionLabels()
	{
		return array(FText::_('JNO'), FText::_('JYES'));
	}

	/**
	 * Run after unmergeFilterSplits to ensure filter dropdown labels are correct
	 *
	 * @param   array  &$rows  Filter options
	 *
	 * @return  null
	 */

	protected function reapplyFilterLabels(&$rows)
	{
		$element = $this->getElement();
		$values = $this->getSubOptionValues();
		$labels = $this->getSubOptionLabels();

		foreach ($rows as &$row)
		{
			if ($row->value !== '')
			{
				$k = array_search($row->value, $values);

				if ($k !== false)
				{
					$row->text = $labels[$k];
				}
			}
		}

		$rows = array_values($rows);
	}

	/**
	 * Get the class to manage the form element
	 * to ensure that the file is loaded only once
	 *
	 * @param   array   &$srcs   Scripts previously loaded
	 * @param   string  $script  Script to load once class has loaded
	 * @param   array   &$shim   Dependant class names to load before loading the class - put in requirejs.config shim
	 *
	 * @return void
	 */

	public function formJavascriptClass(&$srcs, $script = '', &$shim = array())
	{
		$s = new stdClass;
		$s->deps = array('fab/elementlist');
		$shim['element/radiobutton/radiobutton'] = $s;

		$s = new stdClass;
		$s->deps = array('element/radiobutton/radiobutton');
		$shim['element/yesno/yesno'] = $s;

		parent::formJavascriptClass($srcs, $script, $shim);
	}

	/**
	 * Format the read only output for the page
	 *
	 * @param   string  $value  Initial value
	 * @param   string  $label  Label
	 *
	 * @return  string  read only value
	 */

	protected function getReadOnlyOutput($value, $label)
	{
		$j3 = FabrikWorker::j3();

		if ($value == '1')
		{
			$img = $j3 ? 'checkmark.png' : '1.png';
		}
		else
		{
			$img = $j3 ? 'remove.png' : '0.png';
		}

		FabrikHelperHTML::addPath(COM_FABRIK_BASE . 'plugins/fabrik_element/yesno/images/', 'image', 'form', false);

		return FabrikHelperHTML::image($img, 'form', @$this->tmpl, array('alt' => $label));
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
		$params = $this->getParams();
		$params->set('options_per_row', 4);

		return parent::render($data, $repeatCounter);
	}

	/**
	 * Should the grid be rendered as a Bootstrap button-group
	 *
	 * @since 3.1
	 *
	 * @return  bool
	 */

	protected function buttonGroup()
	{
		$params = $this->getParams();
		$ok = FabrikWorker::j3() && $params->get('btnGroup', true);

		return $ok;
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

		return array('FbYesno', $id, $opts);
	}

	/**
	 * Get the table filter for the element
	 *
	 * @param   int   $counter  Filter order
	 * @param   bool  $normal   Do we render as a normal filter or as an advanced search filter
	 * if normal include the hidden fields as well (default true, use false for advanced filter rendering)
	 *
	 * @return  string	Filter html
	 */

	public function getFilter($counter = 0, $normal = true)
	{
		$listModel = $this->getlistModel();
		$table = $listModel->getTable();
		$elName = $this->getFullName(true, false);
		$htmlid = $this->getHTMLId() . 'value';
		$elName = FabrikString::safeColName($elName);
		$v = 'fabrik___filter[list_' . $listModel->getRenderContext() . '][value]';
		$v .= ($normal) ? '[' . $counter . ']' : '[]';
		$default = $this->getDefaultFilterVal($normal, $counter);
		$rows = $this->filterValueList($normal);
		$return = array();
		$element = $this->getElement();
		$params = $this->getParams();
		$class = $this->filterClass();

		if ($element->filter_type == 'hidden')
		{
			$return[] = '<input type="text" name="' . $v . '" class="' . $class . '" value="' . $default . '" id="' . $htmlid . '" />';
		}
		else
		{
			$return[] = JHTML::_('select.genericlist', $rows, $v, 'class="' . $class . '" size="1" ', 'value', 'text', $default, $htmlid);
		}

		if ($normal)
		{
			$return[] = $this->getFilterHiddenFields($counter, $elName);
		}
		else
		{
			$return[] = $this->getAdvancedFilterHiddenFields();
		}

		return implode("\n", $return);
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
	 * @return  array	filter value and labels
	 */

	protected function filterValueList_Exact($normal, $tableName = '', $label = '', $id = '', $incjoin = true)
	{
		$o = new stdClass;
		$o->value = '';
		$o->text = $this->filterSelectLabel();
		$opt = array($o);
		$rows = parent::filterValueList_Exact($normal, $tableName, $label, $id, $incjoin);

		foreach ($rows as &$row)
		{
			if ($row->value == 1)
			{
				$row->text = FText::_('JYES');
			}

			if ($row->value == 0)
			{
				$row->text = FText::_('JNO');
			}
		}

		$rows = array_merge($opt, $rows);

		return $rows;
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
	 * @return  array	filter value and labels
	 */

	protected function filterValueList_All($normal, $tableName = '', $label = '', $id = '', $incjoin = true)
	{
		$rows = array(JHTML::_('select.option', '', $this->filterSelectLabel()), JHTML::_('select.option', '0', FText::_('JNO')),
			JHTML::_('select.option', '1', FText::_('JYES')));

		return $rows;
	}

	/**
	 * Get the condition statement to use in the filters hidden field
	 *
	 * @return  string	=, begins or contains
	 */

	protected function getFilterCondition()
	{
		return '=';
	}

	/**
	 * Trigger called when a row is stored.
	 * If toggle_others on then set other records yesno value to 0
	 *
	 * @param   array  &$data          Data to store
	 * @param   int    $repeatCounter  Repeat group index
	 *
	 * @return  void
	 */

	public function onStoreRow(&$data, $repeatCounter = 0)
	{
		if (!parent::onStoreRow($data, $repeatCounter))
		{
			return false;
		}

		$params = $this->getParams();
		$toggle = (bool) $params->get('toggle_others', false);

		if ($toggle === false)
		{
			return;
		}

		$listModel = $this->getListModel();

		$name = $this->getElement()->name;
		$db = $listModel->getDb();
		$query = $db->getQuery(true);

		if ($this->isJoin())
		{
			$joinModel = $this->getJoinModel();
			$pk = $joinModel->getJoinedToTablePk('.');
		}
		else
		{
			$pk = $listModel->getTable()->db_primary_key;
		}

		$shortPk = FabrikString::shortColName($pk);
		$rowid = JArrayHelper::getValue($data, $shortPk, null);
		$query->update($this->actualTableName())->set($name . ' = 0')->where($pk . ' <> ' . $rowid);
		$db->setQuery($query);
		$db->execute();
	}
}
