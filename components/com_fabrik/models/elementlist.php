<?php
/**
 * Fabrik Element List Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

jimport('joomla.application.component.model');
jimport('joomla.filesystem.file');

/**
 * Fabrik Element List Model
 *
 * @package  Fabrik
 * @since    3.0
 */
class PlgFabrik_ElementList extends PlgFabrik_Element
{
	/**
	 * Does the element have sub elements
	 *
	 * @var bool
	 */
	public $hasSubElements = true;

	/**
	 * Default values
	 *
	 * @var array
	 */
	public $defaults = null;

	/**
	 * Db table field type
	 *
	 * @var  string
	 */
	protected $fieldDesc = 'TEXT';

	/**
	 * Db table field size
	 *
	 * @var  string
	 */
	protected $inputType = 'radio';

	/**
	 * Should the table render functions use html to display the data
	 *
	 * @var bool
	 */
	public $renderWithHTML = true;

	/**
	 * Determines the label used for the browser title
	 * in the form/detail views
	 *
	 * @param   array  $data           Form data
	 * @param   int    $repeatCounter  When repeating joined groups we need to know what part of the array to access
	 * @param   array  $opts           Options
	 *
	 * @return  string	Text to add to the browser's title
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
	 * @return  array  Initially selected values
	 */
	public function getSubInitialSelection()
	{
		$params = $this->getParams();
		$opts = $params->get('sub_options');
		$r = isset($opts->sub_initial_selection) ? (array) $opts->sub_initial_selection : array();

		return $r;
	}

	/**
	 * Does the element consider the data to be empty
	 * Used in isempty validation rule
	 *
	 * @param   array  $data           Data to test against
	 * @param   int    $repeatCounter  Repeat group #
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
	 * @param   array  $data  Form data
	 *
	 * @return mixed
	 */
	public function getDefaultValue($data = array())
	{
		$params = $this->getParams();
		$opts = $params->get('sub_options');

		if (!isset($this->default))
		{
			if (isset($opts->sub_initial_selection))
			{
				$this->default = $this->getSubInitialSelection();
			}
			else
			{
				$this->default = parent::getDefaultValue($data);
			}
		}

		return $this->default;
	}

	/**
	 * Builds an array containing the filters value and condition
	 *
	 * @param   string  $value      Initial value
	 * @param   string  $condition  Initial $condition
	 * @param   string  $eval       How the value should be handled
	 *
	 * @return  array	(value condition)
	 */
	public function getFilterValue($value, $condition, $eval)
	{
		if (is_array($value))
		{
			foreach ($value as &$v)
			{
				$v = $this->prepareFilterVal($v);
			}
		}
		else
		{
			$value = $this->prepareFilterVal($value);
		}

		return parent::getFilterValue($value, $condition, $eval);
	}

	/**
	 * Build the filter query for the given element.
	 * Can be overwritten in plugin - e.g. see checkbox element which checks for partial matches
	 *
	 * @param   string  $key            Element name in format `tablename`.`elementname`
	 * @param   string  $condition      =/like etc.
	 * @param   string  $value          Search string - already quoted if specified in filter array options
	 * @param   string  $originalValue  Original filter value without quotes or %'s applied
	 * @param   string  $type           Filter type advanced/normal/prefilter/search/querystring/searchall
	 * @param   string  $evalFilter     evaled (only used for multiselect types)
	 *                                  
	 * @return  string	sql query part e,g, "key = value"
	 */
	public function getFilterQuery($key, $condition, $value, $originalValue, $type = 'normal', $evalFilter = '0')
	{
		$element = $this->getElement();
		$condition = JString::strtoupper($condition);
		$this->encryptFieldName($key);
		$glue = 'OR';

		if ($element->filter_type == 'checkbox' || $element->filter_type == 'multiselect')
		{
			$str = $this->filterQueryMultiValues($key, $condition, $originalValue, $evalFilter, $type);
		}
		else
		{
			$originalValue = trim($value, "'");

			/*
			 * JSON stored values will back slash "/". So we need to add "\\\\"
			* before it to escape it for the query.
			*/
			$originalValue = str_replace("/", "\\\\/", $originalValue);

			if (strtoupper($condition) === 'IS NULL')
			{
				$value = '';
			}

			switch ($condition)
			{
				case '=':
				case '<>':

					$condition2 = $condition == '=' ? 'LIKE' : 'NOT LIKE';
					$glue = $condition == '=' ? 'OR' : 'AND';
					$db = FabrikWorker::getDbo();
					$str = "($key $condition $value " . " $glue $key $condition2 " . $db->q('["' . $originalValue . '"%') . " $glue $key $condition2 "
					. $db->q('%"' . $originalValue . '"%') . " $glue $key $condition2 " . $db->q('%"' . $originalValue . '"]') . ")";
					break;
				default:
					$str = " $key $condition $value ";
					break;
			}
		}

		return $str;
	}

	/**
	 * @param $key
	 * @param $condition
	 * @param $originalValue
	 * @param $evalFilter
	 * @param $type
	 *
	 * @return string
	 */
	protected function filterQueryMultiValues ($key, $condition, $originalValue, $evalFilter, $type)
	{
		$str = array();

		/**
		 * Grrrr.  For some reason, $evalFilter is getting set in element filter session when it shouldn't.
		 * This code was only meant for eval'ing of prefilters, so until i can work out why eval is getting set,
		 * just restrict this to prefilter types
		 */
		if ($evalFilter && ($type === 'prefilter' || $type === 'menuprefilter'))
		{
			$originalValue = stripslashes(htmlspecialchars_decode($originalValue, ENT_QUOTES));
			FabrikWorker::clearEval();
			$originalValue = @eval($originalValue);
			FabrikWorker::logEval($originalValue, 'Caught exception on eval of elementList::filterQueryMultiValues() ' . $key . ': %s');
		}

		if ($condition === 'NOT IN')
		{
			$partialComparison = ' NOT LIKE ';
			$comparison = ' <> ';
			$glue = ' AND ';
		}
		else
		{
			$partialComparison = ' LIKE ';
			$comparison = ' = ';
			$glue = ' OR ';
		}

		switch ($condition)
		{
			case 'IN':
			case 'NOT IN':
				/**
				 * Split out 1,2,3 into an array to iterate over.
				 * It's a string if pre-filter, array if element filter
				 */
				if (!is_array($originalValue))
				{
					$originalValue = explode(',', $originalValue);
				}

				foreach ($originalValue as &$v)
				{
					$v = trim($v);
					$v = FabrikString::ltrimword($v, '"');
					$v = FabrikString::ltrimword($v, "'");
					$v = FabrikString::rtrimword($v, '"');
					$v = FabrikString::rtrimword($v, "'");
				}
				break;
			default:
				$originalValue = (array) $originalValue;
				break;
		}

		foreach ($originalValue as $v2)
		{
			$v2 = str_replace("/", "\\\\/", $v2);
			$str[] = '(' . $key . $partialComparison . $this->_db->q('%"' . $v2 . '"%') . $glue . $key .
				$comparison . $this->_db->q($v2) . ') ';
		}

		return '(' . implode($glue, $str) . ')';
	}

	/**
	 * Get the filter name
	 *
	 * @param   int   $counter  Filter order
	 * @param   bool  $normal   Do we render as a normal filter or as an advanced search filter
	 *
	 * @return  string
	 */
	protected function filterName($counter = 0, $normal = true)
	{
		$element = $this->getElement();

		if ($element->filter_type === 'checkbox' || $element->filter_type == 'range')
		{
			$listModel = $this->getListModel();
			$v = 'fabrik___filter[list_' . $listModel->getRenderContext() . '][value]';
			$v .= '[' . $counter . ']';
		}
		else
		{
			$v = parent::filterName($counter, $normal);
		}

		return $v;
	}

	/**
	 * Get the table filter for the element
	 *
	 * @param   int   $counter  Filter order
	 * @param   bool  $normal   Do we render as a normal filter or as an advanced search filter
	 * if normal include the hidden fields as well (default true, use false for advanced filter rendering)
	 *
	 * @return  string	Filter HTML
	 */
	public function getFilter($counter = 0, $normal = true, $container = '')
	{
		$element = $this->getElement();
		$values = $this->getSubOptionValues();
		$default = $this->getDefaultFilterVal($normal, $counter);
		$this->filterDisplayValues = array($default);
		$elName = $this->getFullName(true, false);
		$params = $this->getParams();
		$v = $this->filterName($counter, $normal);

		if (in_array($element->filter_type, array('range', 'dropdown', '', 'checkbox', 'multiselect')))
		{
			$rows = $this->filterValueList($normal);

			if ($params->get('filter_groupby') != -1)
			{
				$rows = ArrayHelper::sortObjects($rows, $params->get('filter_groupby', 'text'));
			}

			$this->getFilterDisplayValues($default, $rows);

			if (!in_array('', $values) && !in_array($element->filter_type, array('checkbox', 'multiselect')))
			{
				array_unshift($rows, JHTML::_('select.option', '', $this->filterSelectLabel()));
			}

			foreach ($rows as &$r)
			{
				// translate
				$r->text = FText::_($r->text);

				// decode first, to decode all hex entities (like &#39;)
				$r->text = html_entity_decode($r->text, ENT_QUOTES | ENT_XML1, 'UTF-8');

				// Encode if necessary
				if (!in_array($element->get('filter_type'), array('checkbox')))
				{
					$r->text = strip_tags($r->text);
					$r->text = htmlspecialchars($r->text, ENT_NOQUOTES, 'UTF-8', false);
				}
			}

		}

		$return = array();

		switch ($element->filter_type)
		{
			case 'range':

				if (!is_array($default))
				{
					$default = array('', '');
				}

				$this->rangedFilterFields($default, $return, $rows, $v, 'list');
				break;
			case 'checkbox':
				$return[] = $this->checkboxFilter($rows, $default, $v);
				break;
			case 'dropdown':
			case 'multiselect':
			default:
				$return[] = $this->selectFilter($rows, $default, $v);
				break;

			case 'field':
				$return[] = $this->singleFilter($default, $v);
				break;

			case 'hidden':
				$return[] = $this->singleFilter($default, $v, 'hidden');
				break;

			case 'auto-complete':
				$defaultLabel = $this->getLabelForValue($default);
				$autoComplete = $this->autoCompleteFilter($default, $v, $defaultLabel, $normal, $container);
				$return = array_merge($return, $autoComplete);
				break;
		}

		$return[] = $normal ? $this->getFilterHiddenFields($counter, $elName, false, $normal) : $this->getAdvancedFilterHiddenFields();

		return implode("\n", $return);
	}

	/**
	 * Get an array of element html ids and their corresponding
	 * js events which trigger a validation.
	 * Examples of where this would be overwritten include timedate element with time field enabled
	 *
	 * @param   int  $repeatCounter  Repeat group counter
	 *
	 * @return  array  HTML ids to watch for validation
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
	 * @param   mixed  $value          Element value
	 * @param   array  $data           Form data
	 * @param   int    $repeatCounter  Group repeat counter
	 *
	 * @return  string  Email formatted value
	 */
	protected function getIndEmailValue($value, $data = array(), $repeatCounter = 0)
	{
		$params = $this->getParams();
		$split_str = $params->get('options_split_str', '');

		// Pass in data - otherwise if using multiple plugins of the same type the plugin order gets messed
		// up. Occurs for drop-down element using php eval options.
		$values = $this->getSubOptionValues($data);
		$labels = $this->getSubOptionLabels($data);
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
			if (count($aLabels) === 1)
			{
				$val = $aLabels[0];
			}
			else
			{
				$val = '<ul><li>' . implode('</li><li>', $aLabels) . '</li></ul>';
			}
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
	 * Used by radio and drop-down elements to get a drop-down list of their unique
	 * unique values OR all options - based on filter_build_method
	 *
	 * @param   bool    $normal     Do we render as a normal filter or as an advanced search filter
	 * @param   string  $tableName  Table name to use - defaults to element's current table
	 * @param   string  $label      Field to use, defaults to element name
	 * @param   string  $id         Field to use, defaults to element name
	 * @param   bool    $incjoin    Include join
	 *
	 * @return  array  Text/value objects
	 */
	public function filterValueList($normal, $tableName = '', $label = '', $id = '', $incjoin = true)
	{
		$rows = parent::filterValueList($normal, $tableName, $label, $id, $incjoin);
		$this->unmergeFilterSplits($rows);
		$this->reapplyFilterLabels($rows);

		return $rows;
	}

	/**
	 * Cache method to populate auto-complete options
	 *
	 * @param   plgFabrik_Element  $elementModel  Element model
	 * @param   string             $search        Search string
	 * @param   array              $opts          Options, 'label' => field to use for label (db join)
	 *
	 * @since   3.0.7
	 *
	 * @return string  Json encoded search results
	 */
	public static function cacheAutoCompleteOptions($elementModel, $search, $opts = array())
	{
		$app = JFactory::getApplication();
		$label = FArrayHelper::getValue($opts, 'label', '');
		$rows = $elementModel->filterValueList(true, '', $label);
		$v = $app->input->get('value', '', 'string');

		/**
		 * Search for every word separately in the result rather than the single string (of multiple words)
		 *
		 * Added u switch, for UTF8
		 */
		$regex  = "/(?=.*" .
			implode(")(?=.*",
				array_filter(explode(" ", preg_quote($v, '/')))
			) . ").*/ui";
		$start = count($rows) - 1;

		for ($i = $start; $i >= 0; $i--)
		{
			$rows[$i]->text = strip_tags($rows[$i]->text);

			// Check that search strings are not in the HTML we just stripped
			if (!preg_match($regex, $rows[$i]->text))
			{
				unset($rows[$i]);
			}
		}

		$rows = array_values($rows);
		echo json_encode($rows);
	}

	/**
	 * Will the element allow for multiple selections
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
	 * Optionally pre-format list data before rendering to <ul>
	 *
	 * @param   array  &$data    Element Data
	 * @param   array  $thisRow  Row data
	 *
	 * @return  void
	 */
	protected function listPreformat(&$data, $thisRow)
	{
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
        $profiler = JProfiler::getInstance('Application');
        JDEBUG ? $profiler->mark("renderListData: parent: start: {$this->element->name}") : null;

        $params = $this->getParams();
		$listModel = $this->getListModel();
		$multiple = $this->isMultiple();
		$mergeGroupRepeat = ($this->getGroup()->canRepeat() && $this->getListModel()->mergeJoinedData());
		$useIcon = (int)$params->get('icon_folder', 0) > 0 && ArrayHelper::getValue($opts, 'icon', 1);

		// Give priority to raw value icons (podion)
		$raw = $this->isJoin() ? $this->getFullName(true, false) . '_raw' : $this->getFullName(true, false) . '_id';

		if (isset($thisRow->$raw))
		{
			$rawData = FabrikWorker::JSONtoData($thisRow->$raw, true);

			foreach ($rawData as &$val)
			{
				$val = $useIcon ? $this->replaceWithIcons($val, 'list', $listModel->getTmpl()) : $val;
			}

			if ($this->iconsSet)
			{
				// Use raw icons
				$data = $rawData;
				$useIcon = false;
			}
		}

		// Repeat group data
		$gdata = FabrikWorker::JSONtoData($data, true);
		$this->listPreformat($gdata, $thisRow);
		$addHtml = (count($gdata) !== 1 || $multiple || $mergeGroupRepeat) && $this->renderWithHTML;
		$uls = array();

		foreach ($gdata as $i => $d)
		{
			$lis = array();
			$values = is_array($d) ? $d : FabrikWorker::JSONtoData($d, true);

			foreach ($values as $tmpVal)
			{
				$l = $useIcon ? $this->replaceWithIcons($tmpVal, 'list', $listModel->getTmpl()) : $tmpVal;

				if (!$this->iconsSet == true)
				{
					if (!is_a($this, 'PlgFabrik_ElementDatabasejoin'))
					{
						$l = $this->getLabelForValue($tmpVal);
					}
					else
					{
						$l = $tmpVal;
					}

					$l = $this->replaceWithIcons($l, 'list', $listModel->getTmpl());
				}

				if ($this->renderWithHTML)
				{
					if (ArrayHelper::getValue($opts, 'rollover', 1))
					{
						$l = $this->rollover($l, $thisRow, 'list');
					}
					if (ArrayHelper::getValue($opts, 'link', 1))
					{
						$l = $listModel->_addLink($l, $this, $thisRow, $i);
					}
				}

				if (trim($l) !== '')
				{
					$lis[] = $l;
				}
				else
				{
					// was trying to fix issue with empty merged repeat rows not having height but messes CSV export
					//$lis[] = '&nbsp;';
					$lis[] = '';
				}
			}

			if (!empty($lis))
			{
				$uls[] = $lis;
			}
		}

		// Do all uls only contain one record, if so condense to 1 ul (avoids nested <ul>'s each with one <li>
		$condense = true;

		foreach ($uls as $ul)
		{
			if (count($ul) > 1)
			{
				$condense = false;
			}
		}

		$layout = FabrikHelperHTML::getLayout('fabrik-element-elementlist-details',
			array(COM_FABRIK_FRONTEND . '/layouts/element'));

		$displayData = array(
			'uls' => $uls,
			'condense' => $condense,
			'addHtml' => $addHtml,
			'sepChar' => ArrayHelper::getValue($opts, 'sepChar', ' ')
		);

        JDEBUG ? $profiler->mark("renderListData: parent: end: {$this->element->name}") : null;

		return $layout->render((object) $displayData);
	}

	/**
	 * Prepares the element data for CSV export
	 *
	 * @param   string  $data      Element data
	 * @param   object  &$thisRow  All the data in the lists current row
	 *
	 * @return  string	Formatted value
	 */
	public function renderListData_csv($data, &$thisRow)
	{
		$this->renderWithHTML = false;
		$d = $this->renderListData(
			$data,
			$thisRow,
			array(
				'sepChar' => $this->getlistModel()->getParams()->get('csv_multi_join_split', ',')
			)
		);

		if ($this->isJoin())
		{
			// Set the linking table's pk as the raw value.
			$raw = $this->getFullName(true, false) . '_raw';
			$id = $this->getFullName(true, false) . '_id';
			$data = $thisRow->$id;

			$rawData = FabrikWorker::JSONtoData($data, true);
			$thisRow->$raw = json_encode($rawData);
		}

		$this->renderWithHTML = true;

		return $d;
	}

	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           To pre-populate element with
	 * @param   int    $repeatCounter  Repeat group counter
	 *
	 * @return  string	Elements html
	 */
	public function render($data, $repeatCounter = 0)
	{
		$name = $this->getHTMLName($repeatCounter);
		$input = $this->app->input;
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
		 *
		 * $$$ rob - Need more logic that the previous test, as we weren't applying default value/label if set and data empty
		*/
		$selected = (array) $this->getValue($data, $repeatCounter);

		if (FArrayHelper::emptyIsh($selected))
		{
			$selected = array();

			// Nothing previously selected, and not editable, set selected to default value, which later on is replaced with default label
			if (!$this->isEditable() && $params->get('sub_default_value', '') !== '')
			{
				$selected[] = $params->get('sub_default_value');
			}
		}

		// $$$ rob 06/10/2011 if front end add option on, but added option not saved we should add in the selected value to the
		// values and labels.
		$diff = array_diff($selected, $values);

		if (!empty($diff))
		{
			$values = array_merge($values, $diff);

			// Swap over the default value to the default label
			if (!$this->isEditable())
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

		if (!$this->isEditable())
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

			if (empty($aRoValues))
			{
				return'';
			}

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
		if ($input->get('format') == 'raw')
		{
			$optionsPerRow = 1;
		}

		$classes = $this->gridClasses();
		$dataAttributes = $this->dataAttributes();
		$buttonGroup = $this->buttonGroup();
		$grid = FabrikHelperHTML::grid($values, $labels, $selected, $name, $this->inputType, $elBeforeLabel, $optionsPerRow, $classes, $buttonGroup, $dataAttributes);
		array_unshift($grid, '<div class="fabrikSubElementContainer" id="' . $id . '">');
		$grid[] = '</div><!-- close subElementContainer -->';

		if ($params->get('allow_frontend_addto', false))
		{
			$onlyLabel = $params->get('allowadd-onlylabel');
			$grid[] = $this->getAddOptionFields($repeatCounter, $onlyLabel);
		}

		return implode("\n", $grid);
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

		return FabrikWorker::j3() && $params->get('btnGroup', false);
	}

	/**
	 * Get classes to assign to the grid
	 * An array of arrays of class names, keyed as 'container', 'label' or 'input',
	 *
	 * @return  array
	 */
	protected function gridClasses()
	{
		return array();
	}

	/**
	 * Get data attributes to assign to the container
	 *
	 * @return  array
	 */
	protected function dataAttributes()
	{
		return array();
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
	 * @param   array  $opts  Options
	 *
	 * @return  string  Element name inside data array
	 */
	protected function getValueFullName($opts)
	{
		return $this->getFullName(true, false);
	}

	/**
	 * Determines the value for the element in the form view
	 *
	 * @param   array  $data           Form data
	 * @param   int    $repeatCounter  When repeating joined groups we need to know what part of the array to access
	 * @param   array  $opts           Options
	 *
	 * @return  string	value
	 */
	public function getValue($data, $repeatCounter = 0, $opts = array())
	{
		$v = parent::getValue($data, $repeatCounter, $opts);

		if (is_string($v))
		{
			$v = FabrikWorker::JSONtoData($v, true);
		}

		return $v;
	}

	/**
	 * Is the drop-downs cnn the same as the main Joomla db
	 *
	 * @return  bool
	 */
	protected function inJDb()
	{
		return $this->getlistModel()->inJDb();
	}

	/**
	 * Trigger called when a row is stored.
	 * Check if new options have been added and if so store them in the element for future use.
	 *
	 * @param   array  &$data          Data to store
	 * @param   int    $repeatCounter  Repeat group index
	 *
	 * @return  bool
	 */
	public function onStoreRow(&$data, $repeatCounter = 0)
	{
		if (!parent::onStoreRow($data, $repeatCounter))
		{
			return false;
		}

		$element = $this->getElement();
		$params = $this->getParams();
		$formModel = $this->getFormModel();
		$formData = $formModel->formData;

		if ($params->get('savenewadditions') && array_key_exists($element->name . '_additions', $formData))
		{
			$added = stripslashes($formData[$element->name . '_additions']);

			if (trim($added) == '')
			{
				return true;
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

				// $$$ rob don't json_encode this - the params object has its own toString() magic method
				$element->params = (string) $params;
				$element->store();
			}
		}

		return true;
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
		$mediaFolder = FabrikHelperHTML::getMediaFolder();
		$files = array(
			'Element' => $mediaFolder . '/element.js',
			'ElementList' => $mediaFolder . '/elementlist.js'
		);

		$srcs = array_merge($srcs, $files);

		parent::formJavascriptClass($srcs, $script, $shim);
	}

	/**
	 * used by elements with sub-options
	 *
	 * $$$ hugh - started working on adding this to elementlist, as we need to handle
	 * JSON-ified options for multi-select elements, which the main element model getLabelForValue()
	 * doesn't do.  But I need to sort out how this gets handled in rendering as well.
	 *
	 * @param   string  $v             Value
	 * @param   string  $defaultLabel  Default label
	 *
	 * @return  string	label
	 */

	public function notreadyyet_getLabelForValue($v, $defaultLabel = '')
	{
		$labels = $this->getSubOptionLabels();
		$multiple = $this->isMultiple();
		$vals = is_array($v) ? $v : FabrikWorker::JSONtoData($v, true);

		foreach ($vals as $val)
		{
			$l = FArrayHelper::getValue($labels, $val, $defaultLabel);

			if (trim($l) !== '')
			{
				if ($multiple && $this->renderWithHTML)
				{
					$lis[] = '<li>' . $l . '</li>';
				}
				else
				{
					$lis[] = $l;
				}
			}
		}

		$return = '';

		if (!empty($lis))
		{
			$return = ($multiple && $this->renderWithHTML) ? '<ul class="fabrikRepeatData">' . implode(' ', $lis) . '</ul>' : implode(' ', $lis);
		}

		/**
		 * $$$ rob if we allow adding to the drop-down but not recording
		 * then there will be no $key set to revert to the $val instead
		 */
		/*
		if ($v === $params->get('sub_default_value'))
		{
		$v = $params->get('sub_default_label');
		}
		return ($key === false) ? $v : FArrayHelper::getValue($labels, $key, $defaultLabel);
		*/
		return $return;
	}

	/**
	 * Internal element validation
	 *
	 * @param   array $data          Form data
	 * @param   int   $repeatCounter Repeat group counter
	 *
	 * @return bool
	 */
	public function validate($data, $repeatCounter = 0)
	{
		return true;
	}
}
