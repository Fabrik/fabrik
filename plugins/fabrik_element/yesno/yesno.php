<?php
/**
 * Plugin element to yes/no radio options - render as tick/cross in list view
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.yesno
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
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
	 * @param   string    $data      Elements data
	 * @param   stdClass  &$thisRow  All the data in the lists current row
	 * @param   array     $opts      Rendering options
	 *
	 * @return  string	formatted value
	 */
	public function renderListData($data, stdClass &$thisRow, $opts = array())
	{
		FabrikHelperHTML::addPath(COM_FABRIK_BASE . 'plugins/fabrik_element/yesno/images/', 'image', 'list', false);

		// Check if the data is in csv format, if so then the element is a multi drop down
		$raw = $this->getFullName(true, false) . '_raw';
		$rawData = $thisRow->$raw;
		$rawData = FabrikWorker::JSONtoData($rawData, true);
		$displayData        = new stdClass;
		$displayData->tmpl  = isset($this->tmpl) ? $this->tmpl : '';
		$basePath           = JPATH_ROOT . '/plugins/fabrik_element/yesno/layouts';
		$layout             = new FabrikLayoutFile('fabrik_element_yesno_list', $basePath);
		$layout->addIncludePaths(JPATH_THEMES . '/' . $this->app->getTemplate() . '/html/layouts');
		$labelData = array();

		foreach ($rawData as $d)
		{
			$displayData->value = $d;
			$labelData[] = $layout->render($displayData);
		}

		$data = json_encode($labelData);

		return parent::renderListData($data, $thisRow, $opts);
	}

	/**
	 * Shows the data formatted for the table view with format = pdf
	 * note pdf lib doesn't support transparent PNGs hence this func
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

			return FabrikHelperHTML::image($icon, 'list', @$this->tmpl, array('alt' => FText::_('JYES')));
		}
		else
		{
			$icon = $j3 ? 'remove.png' : '0_8bit.png';

			return FabrikHelperHTML::image($icon, 'list', @$this->tmpl, array('alt' => FText::_('JNO')));
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
		$ret     = array();
		$raw     = $this->getFullName(true, false) . '_raw';
		$rawData = $thisRow->$raw;
		$rawData = FabrikWorker::JSONtoData($rawData, true);

		foreach ($rawData as $d)
		{
			$ret[]    = (bool) $d ? FText::_('JYES') : FText::_('JNO');
		}

		if (count($ret) > 1)
		{
			$ret = json_encode($ret);
		}
		else
		{
			$ret = implode('', $ret);
		}

	    return $ret;
	}

	/**
	 * Get sub option values
	 *
	 * @param   array  $data  Form data. If submitting a form, we want to use that form's data and not
	 *                        re-query the form Model for its data as with multiple plugins of the same type
	 *                        this was getting the plugin params out of sync.
	 *
	 * @return  array
	 */
	protected function getSubOptionValues($data = array())
	{
		return array(0, 1);
	}

	/**
	 * Get sub option labels
	 *
	 * @param   array  $data  Form data. If submitting a form, we want to use that form's data and not
	 *                        re-query the form Model for its data as with multiple plugins of the same type
	 *                        this was getting the plugin params out of sync.
	 *
	 * @return  array
	 */
	protected function getSubOptionLabels($data = array())
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
	 * Format the read only output for the page
	 *
	 * @param   string  $value  Initial value
	 * @param   string  $label  Label
	 *
	 * @return  string  read only value
	 */
	protected function getReadOnlyOutput($value, $label)
	{
		$displayData = new stdClass;
		$displayData->value = $value;
		$displayData->tmpl = @$this->tmpl;
		$displayData->format = $this->app->input->get('format', '');;
		$basePath = JPATH_ROOT . '/plugins/fabrik_element/yesno/layouts';
		$layout = new FabrikLayoutFile('fabrik_element_yesno_details', $basePath);
		$layout->addIncludePaths(JPATH_THEMES . '/' . $this->app->getTemplate() . '/html/layouts');

		return $layout->render($displayData);
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
		$opts->changeEvent = $this->getChangeEvent();

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
		$elName = $this->getFullName(true, false);
		$elName = FabrikString::safeColName($elName);
		$v = 'fabrik___filter[list_' . $listModel->getRenderContext() . '][value]';
		$v .= ($normal) ? '[' . $counter . ']' : '[]';
		$default = $this->getDefaultFilterVal($normal, $counter);
		$rows = $this->filterValueList($normal);
		$this->getFilterDisplayValues($default, $rows);

		$return = array();
		$element = $this->getElement();

		if ($element->filter_type == 'hidden')
		{
			$return[] = $this->singleFilter($default, $v, 'hidden');
		}
		else
		{
			$return[] = $this->selectFilter($rows, $default, $v);
		}

		if ($normal)
		{
			$return[] = $this->getFilterHiddenFields($counter, $elName, false, $normal);
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

		$value = $this->getValue($data, $repeatCounter);

		if ($value == '1')
		{
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
				$pk = $listModel->getPrimaryKey();
			}

			$shortPk = FabrikString::shortColName($pk);
			$rowId = FArrayHelper::getValue($data, $shortPk, null);

			$query->update($this->actualTableName())->set($name . ' = 0');

			if (!empty($rowId))
			{
				$query->where($pk . ' <> ' . $rowId);
			}

			$toggle_where = $params->get('toggle_where', '');
			FabrikString::ltrimiword($toggle_where, 'where');

			if (!empty($toggle_where))
			{
				$w = new FabrikWorker;
				$toggle_where = $w->parseMessageForPlaceHolder($toggle_where);
				$query->where($toggle_where);
			}

			$db->setQuery($query);
			$db->execute();
		}
	}

	/**
	 * Return JS event required to trigger a 'change', this is overriding default element model.
	 * When in BS mode with button-grp, needs to be 'click'.
	 *
	 * @return  string
	 */
	public function getChangeEvent()
	{
		return $this->buttonGroup() ? 'click' : 'change';
	}

	/**
	 * Get classes to assign to the grid
	 * An array of arrays of class names, keyed as 'container', 'label' or 'input',
	 *
	 * @return  array
	 */
	protected function gridClasses()
	{
		return array(
			'label' => array('btn-default'),
			'container' => array('btn-radio')
		);
	}

	/**
	 * Get data attributes to assign to the container
	 *
	 * @return  array
	 */
	protected function dataAttributes()
	{
		return array('data-toggle="buttons"');
	}

}
