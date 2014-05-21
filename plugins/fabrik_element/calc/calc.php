<?php
/**
 * Plugin element to render field with PHP calculated value
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.calc
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Plugin element to render field with PHP calculated value
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.calc
 * @since       3.0
 */

class PlgFabrik_ElementCalc extends PlgFabrik_Element
{
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
			$w = new FabrikWorker;
			$element = $this->getElement();
			$default = $w->parseMessageForPlaceHolder($element->default, $data, true, true);
			/* calc in fabrik3.0/3.1 doesn't have eval, issues if F2.0 calc elements are migrated*/
			/*if ($element->eval == '1')
			{
				if (FabrikHelperHTML::isDebug())
				{
					$res = eval($default);
				}
				else
				{
					$res = @eval($default);
				}
				FabrikWorker::logEval($res, 'Eval exception : ' . $element->name . '::getDefaultValue() : ' . $default . ' : %s');
				$default = $res;
			}
			*/
			$this->default = $default;
		}

		return $this->default;
	}

	/**
	 * Get value
	 *
	 * @param   string  $data           Value
	 * @param   int     $repeatCounter  Repeat group counter
	 *
	 * @return  string
	 */

	private function _getV($data, $repeatCounter)
	{
		$w = new FabrikWorker;
		$groupModel = $this->getGroup();
		$joinid = $groupModel->getGroup()->join_id;
		$name = $this->getFullName(true, false);
		$params = $this->getParams();

		// $$$ hugh - if we don't do this, we get the cached default from the previous repeat
		if ($repeatCounter > 0)
		{
			unset($this->default);
		}

		/**
		 *  $$$ hugh - don't think we want to do this here, otherwise calc gets run regardless of calc_on_save_only,
		 *  it just won't get used if 'true'
		 *  $default = $this->getDefaultValue($data, $repeatCounter);
		 */
		$default = '';
		/**
		 *  If viewing form or details view and calc set to always run then return the $default
		 *  which has had the calculation run on it.
		 */
		$task = JFactory::getApplication()->input->get('task', '');

		if (!$params->get('calc_on_save_only', true) || $task == 'form.process' || $task == 'process')
		{
			// $default = $this->getDefaultValue($data, $repeatCounter);
			$this->swapValuesForLabels($data);

			// $$$ hugh need to remove repeated joined data which is not part of this repeatCount
			$groupModel = $this->getGroup();

			if ($groupModel->isJoin())
			{
				if ($groupModel->canRepeat())
				{
					foreach ($data as $name => $values)
					{
						// $$$ Paul - Because $data contains stuff other than placeholders, we have to exclude e.g. fabrik_repeat_group
						if (is_array($values) && count($values) > 1 & isset($values[$repeatCounter]) && $name != 'fabrik_repeat_group')
						{
							$data[$name] = $data[$name][$repeatCounter];
						}
					}
				}
			}

			$this->setStoreDatabaseFormat($data, $repeatCounter);
			$default = $w->parseMessageForPlaceHolder($params->get('calc_calculation'), $data, true, true);

			//  $$$ hugh - standardizing on $data but need need $d here for backward compat
			$d = $data;

			$res = FabrikHelperHTML::isDebug() ? eval($default) : @eval($default);
			FabrikWorker::logEval($res, 'Eval exception : ' . $this->getElement()->name . '::_getV() : ' . $default . ' : %s');

			return $res;
		}

		$rawname = $name . '_raw';

		if ($groupModel->isJoin())
		{
			$data = (array) $data;
			$data[$name] = (array) $data[$name];

			if ($groupModel->canRepeat())
			{
				if (array_key_exists($name, $data) && array_key_exists($repeatCounter, $data[$name]))
				{
					$default = $data[$name][$repeatCounter];
				}
				else
				{
					if (array_key_exists($name, $data) && array_key_exists($repeatCounter, $data[$name]))
					{
						$default = $data[$name][$repeatCounter];
					}
				}
			}
			else
			{
				if (array_key_exists($name, $data))
				{
					$default = $data[$name];
				}
				else
				{
					if (array_key_exists($rawname, $data))
					{
						$default = $data[$rawname];
					}
				}
			}
		}
		else
		{
			// When called from getFilterArray via getROElement, $data doesn't exist
			// (i.e. when specified as a table___name=foo in a content plugin)
			if (is_array($data))
			{
				if (array_key_exists($name, $data))
				{
					$default = $data[$name];
				}
				else
				{
					if (array_key_exists($rawname, $data))
					{
						$default = $data[$rawname];
					}
				}
			}
		}

		return $default;
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
		if (!isset($this->defaults) || is_null($this->defaults))
		{
			$this->defaults = array();
		}

		if (!array_key_exists($repeatCounter, $this->defaults))
		{
			$element = $this->getElement();
			$element->default = $this->_getV($data, $repeatCounter);
			$formModel = $this->getForm();

			// Stops this getting called from form validation code as it messes up repeated/join group validations
			if (array_key_exists('runplugins', $opts) && $opts['runplugins'] == 1)
			{
				FabrikWorker::getPluginManager()->runPlugins('onGetElementDefault', $formModel, 'form', $this);
			}

			if (is_array($element->default))
			{
				$element->default = implode(',', $element->default);
			}

			$this->defaults[$repeatCounter] = $element->default;
		}

		return $this->defaults[$repeatCounter];
	}

	/**
	 * run on formModel::setFormData()
	 * Appends the calculation to the form's data when the form is submitted
	 *
	 * @param   int  $c  Repeat group counter
	 *
	 * @return void
	 */

	public function preProcess($c)
	{
		$params = $this->getParams();
		$w = new FabrikWorker;
		$form = $this->getForm();
		$data = unserialize(serialize($form->formData));
		$calc = $params->get('calc_calculation');
		$group = $this->getGroup();

		/**
		 * get the key name in dot format for updateFormData method
		 * $$$ hugh - added $rawkey stuff, otherwise when we did "$key . '_raw'" in the updateFormData
		 * below on repeat data, it ended up in the wrong format, like join.XX.table___element.0_raw
		 */
		$key = $this->getFullName(true, false);
		$shortkey = $this->getFullName(true, false);
		$rawkey = $key . '_raw';
		$this->swapValuesForLabels($data);
		$res = $this->_getV($data, $c);

		// Create arrays for calc values as needed
		if (is_array($data[$key]))
		{
			$data[$key][$c] = $res;
		}
		elseif (!isset($data[$key]) && $c == 0)
		{
			$data[$key] = $res;
		}
		else
		{
			if (!isset($data[$key]))
			{
				$data[$key] = array();
			}
			else
			{
				$data[$key] = array($data[$key]);
			}

			$data[$key][$c] = $res;
		}

		$form->updateFormData($key, $data[$key]);
		$form->updateFormData($rawkey, $data[$key]);
	}

	/**
	 * Swap values for labels
	 *
	 * @param   array  &$d  Data
	 *
	 * @return  void
	 */

	protected function swapValuesForLabels(&$d)
	{
		$groups = $this->getForm()->getGroupsHiarachy();

		foreach (array_keys($groups) as $gkey)
		{
			$group = $groups[$gkey];
			$elementModels = $group->getPublishedElements();

			for ($j = 0; $j < count($elementModels); $j++)
			{
				$elementModel = $elementModels[$j];
				$elkey = $elementModel->getFullName(true, false);
				$v = JArrayHelper::getValue($d, $elkey);

				if (is_array($v))
				{
					$origdata = JArrayHelper::getValue($d, $elkey, array());

					foreach (array_keys($v) as $x)
					{
						$origval = JArrayHelper::getValue($origdata, $x);
						$d[$elkey][$x] = $elementModel->getLabelForValue($v[$x], $origval, true, $x);
					}
				}
				else
				{
					$d[$elkey] = $elementModel->getLabelForValue($v, JArrayHelper::getValue($d, $elkey), true);
				}
			}
		}
	}

	/**
	 * Allows the element to pre-process a rows data before and join merging of rows
	 * occurs. Used in calc element to do calcs on actual row rather than merged row
	 *
	 * @param   string  $data  Elements data for the current row
	 * @param   object  $row   Current row's data
	 *
	 * @since	3.0.5
	 *
	 * @return  string	Formatted value
	 */

	public function preFormatFormJoins($data, $row)
	{
		$params = $this->getParams();
		$format = trim($params->get('calc_format_string'));
		$element_data = $data;

		if ($params->get('calc_on_save_only', 0))
		{
			if ($format != '')
			{
				$element_data = sprintf($format, $element_data);
			}

			return parent::preFormatFormJoins($element_data, $row);
		}
		else
		{
			$element = $this->getElement();
			$cal = $params->get('calc_calculation', '');
			$listModel = $this->getlistModel();
			$formModel = $this->getFormModel();
			$data = JArrayHelper::fromObject($row);
			$data['rowid'] = $data['__pk_val'];
			$data['fabrik'] = $formModel->getId();

			//  $$$ Paul - Because this is run on List rows before repeat-group merges, repeat group placeholders are OK.
			//  $$$ hugh - standardizing on $data but need need $d here for backward compat
			$d = $data;
			$cal = $listModel->parseMessageForRowHolder($cal, $data, true);

			if (FabrikHelperHTML::isDebug())
			{
				$res = eval($cal);
			}
			else
			{
				$res = @eval($cal);
			}

			FabrikWorker::logEval($res, 'Eval exception : ' . $element->name . '::preFormatFormJoins() : ' . $cal . ' : %s');

			if ($format != '')
			{
				$res = sprintf($format, $res);
			}

			// $$$ hugh - need to set _raw, might be needed if (say) calc is being used as 'use_as_row_class'
			// See comments in formatData() in table model, we might could move this to a renderRawListData() method.
			$raw_name = $this->getFullName(true, false) . '_raw';
			$row->$raw_name = str_replace(GROUPSPLITTER, ',', $res);

			return parent::preFormatFormJoins($res, $row);
		}
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
		$val = $this->renderListData($data, $thisRow);
		$col = $this->getFullName(true, false);
		$raw = $col . '_raw';
		$thisRow->$raw = $val;

		return $val;
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
		$params = $this->getParams();
		$element = $this->getElement();
		$data = $this->getFormModel()->data;
		$value = $this->getValue($data, $repeatCounter);
		$format = $params->get('calc_format_string');

		if ($format != '')
		{
			$value = sprintf($format, $value);
		}

		$name = $this->getHTMLName($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		$str = array();

		if ($this->canView())
		{
			if (!$this->isEditable())
			{
				$value = $this->replaceWithIcons($value);
				$str[] = $value;
			}
			else
			{
				if ($element->height <= 1)
				{
					$str[] = '<span class="fabrikinput" name="' . $name . '" id="' . $id . '">' . $value . '</span>';
				}
				else
				{
					$str[] = '<textarea class="fabrikinput" disabled="disabled" name="' . $name . '" id="' . $id
					. '" cols="' . $element->width . '" rows="' . $element->height . '">' . $value . '</textarea>';
				}
			}
		}
		else
		{
			/* make a hidden field instead*/
			$str[] = '<input type="hidden" class="fabrikinput" name="' . $name . '" id="' . $id . '" value="' . $value . '" />';
		}

		$opts = array('alt' => FText::_('PLG_ELEMENT_CALC_LOADING'), 'style' => 'display:none;padding-left:10px;', 'class' => 'loader');
		$str[] = FabrikHelperHTML::image("ajax-loader.gif", 'form', @$this->tmpl, $opts);

		return implode("\n", $str);
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
		$params = $this->getParams();
		$calc = $params->get('calc_calculation');
		$obs = preg_replace('#\s#', '', $params->get('calc_ajax_observe'));
		$obs = explode(',', $obs);

		if (preg_match_all("/{[^}\s]+}/i", $calc, $matches) !== 0)
		{
			$matches = $matches[0];
			$obs = array_merge($obs, $matches);
		}

		$obs = array_unique($obs);

		foreach ($obs as &$m)
		{
			$m = str_replace(array('{', '}'), '', $m);

			// $$$ hugh - we need to knock any _raw off, so JS can match actual element ID
			$m = preg_replace('#_raw$#', '', $m);
		}

		$opts->ajax = $params->get('calc_ajax', 0) == 0 ? false : true;
		$opts->observe = array_values($obs);
		$opts->calcOnLoad = (bool) $params->get('calc_on_load', false);
		$opts->id = $this->id;

		return array('FbCalc', $id, $opts);
	}

	/**
	 * Perform calculation from ajax request
	 *
	 * @return  void
	 */

	public function onAjax_calc()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$this->setId($input->getInt('element_id'));
		$this->loadMeForAjax();
		$params = $this->getParams();
		$w = new FabrikWorker;
		$filter = JFilterInput::getInstance();
		$d = $filter->clean($_REQUEST, 'array');
		$formModel = $this->getFormModel();
		$formModel->addEncrytedVarsToArray($d);
		$this->getFormModel()->data = $d;
		$this->swapValuesForLabels($d);
		$calc = $params->get('calc_calculation');
		$this->setStoreDatabaseFormat($d);

		// $$$ hugh - trying to standardize on $data so scripts know where data is
		$data = $d;
		$calc = $w->parseMessageForPlaceHolder($calc, $d);
		$c = FabrikHelperHTML::isDebug() ? eval($calc): @eval($calc);
		$c = preg_replace('#(\/\*.*?\*\/)#', '', $c);
		echo $c;
	}

	/**
	 * When running parseMesssageForPlaceholder on data we need to set the none-raw value of things like birthday/time
	 * elements to that stored in the listModel::storeRow() method
	 *
	 * @param   array  &$data          Form data
	 * @param   int    $repeatCounter  Repeat group counter
	 *
	 * @return  void
	 */
	protected function setStoreDatabaseFormat(&$data, $repeatCounter = 0)
	{
		$formModel = $this->getFormModel();
		$groups = $formModel->getGroupsHiarachy();

		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();

			foreach ($elementModels as $elementModel)
			{
				$element = $elementModel->getElement();
				$fullkey = $elementModel->getFullName(true, false);
				$value = $data[$fullkey];

				if ($this->getGroupModel()->canRepeat() && is_array($value))
				{
					$value = JArrayHelper::getValue($value, $repeatCounter);
				}

				// For radio buttons and dropdowns otherwise nothing is stored for them??
				$data[$fullkey] = $elementModel->storeDatabaseFormat($value, $data);
			}
		}
	}

	/**
	 * Get sum query
	 *
	 * @param   object  &$listModel  List model
	 * @param   array   $labels      Label
	 *
	 * @return string
	 */

	protected function getSumQuery(&$listModel, $labels = array())
	{
		$fields = $listModel->getDBFields($this->getTableName(), 'Field');
		$name = $this->getElement()->name;
		$field = JArrayHelper::getValue($fields, $name, false);

		if ($field !== false && $field->Type == 'time')
		{
			$db = $listModel->getDb();
			$label = count($labels) == 0 ? "'calc' AS label" : 'CONCAT(' . implode(', " & " , ', $labels) . ')  AS label';
			$name = $this->getFullName(false, false);
			$table = $listModel->getTable();
			$joinSQL = $listModel->buildQueryJoin();
			$whereSQL = $listModel->buildQueryWhere();

			return "SELECT SEC_TO_TIME(SUM(TIME_TO_SEC($name))) AS value, $label FROM " . $db->quoteName($table->db_table_name)
				. " $joinSQL $whereSQL";
		}
		else
		{
			return parent::getSumQuery($listModel, $labels);
		}
	}

	/**
	 * Build the query for the avg calculation
	 *
	 * @param   model  &$listModel  list model
	 * @param   array  $labels      Labels
	 *
	 * @return  string	sql statement
	 */

	protected function getAvgQuery(&$listModel, $labels = array())
	{
		$fields = $listModel->getDBFields($this->getTableName(), 'Field');
		$name = $this->getElement()->name;
		$field = JArrayHelper::getValue($fields, $name, false);

		if ($field !== false && $field->Type == 'time')
		{
			$db = $listModel->getDb();
			$label = count($labels) == 0 ? "'calc' AS label" : 'CONCAT(' . implode(', " & " , ', $labels) . ')  AS label';
			$name = $this->getFullName(false, false);
			$table = $listModel->getTable();
			$joinSQL = $listModel->buildQueryJoin();
			$whereSQL = $listModel->buildQueryWhere();

			return "SELECT SEC_TO_TIME(AVG(TIME_TO_SEC($name))) AS value, $label FROM " . $db->quoteName($table->db_table_name)
				. " $joinSQL $whereSQL";
		}
		else
		{
			return parent::getAvgQuery($listModel, $labels);
		}
	}

	/**
	 * Get a query for our median query
	 *
	 * @param   object  &$listModel  List
	 * @param   array   $labels      Label
	 *
	 * @return string
	 */

	protected function getMedianQuery(&$listModel, $labels = array())
	{
		$fields = $listModel->getDBFields($this->getTableName(), 'Field');
		$name = $this->getElement()->name;
		$field = JArrayHelper::getValue($fields, $name, false);

		if ($field !== false && $field->Type == 'time')
		{
			$db = $listModel->getDb();
			$label = count($labels) == 0 ? "'calc' AS label" : 'CONCAT(' . implode(', " & " , ', $labels) . ')  AS label';
			$name = $this->getFullName(false, false);
			$table = $listModel->getTable();
			$joinSQL = $listModel->buildQueryJoin();
			$whereSQL = $listModel->buildQueryWhere();

			return "SELECT SEC_TO_TIME(TIME_TO_SEC($name)) AS value, $label FROM " . $db->quoteName($table->db_table_name)
				. " $joinSQL $whereSQL";
		}
		else
		{
			return parent::getMedianQuery($listModel, $labels);
		}
	}

	/**
	 * Get the sprintf format string
	 *
	 * @since 3.0.4
	 *
	 * @return string
	 */

	public function getFormatString()
	{
		$params = $this->getParams();

		return $params->get('calc_format_string');
	}

	/**
	 * Get JS code for ini element list js
	 * Overwritten in plugin classes
	 *
	 * @return string
	 */

	public function elementListJavascript()
	{
		$params = $this->getParams();
		$id = $this->getHTMLId();
		$list = $this->getlistModel()->getTable();
		$opts = new stdClass;
		$opts->listid = $list->id;
		$opts->listRef = 'list_' . $this->getlistModel()->getRenderContext();
		$opts->formid = $this->getFormModel()->getId();
		$opts->elid = $this->getElement()->id;
		$opts = json_encode($opts);

		return "new FbCalcList('$id', $opts);\n";
	}

	/**
	 * Update list data
	 *
	 * @return  void
	 */

	public function onAjax_listUpdate()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$ids = $input->get('rows', array(), 'array');
		$listId = $input->getInt('listid');
		$elId = $input->getInt('element_id');
		$this->setId($elId);
		$this->loadMeForAjax();
		$params = $this->getParams();

		$listModel = JModelLegacy::getInstance('List', 'FabrikFEModel');
		$listModel->setId($listId);
		$data = $listModel->getData();
		$return = new stdClass;
		$w = new FabrikWorker;
		/**
		 * $$$ hugh ... no, we never need to store in this context.  The 'calc_on_save_only' param simply dictates
		 * whether we re-calc when displaying the element, or just use the stored value.  So if calc_on_save_only is
		 * set, then when displaying in lists, we don't execute the calc, we just used the stored value fro the database.
		 * And that logic is handled in _getV(), so we don't need to do the $store stuff.
		 */
		$listRef = 'list_' . $listModel->getRenderContext() . '_row_';

		foreach ($data as $group)
		{
			foreach ($group as $row)
			{
				$key = $listRef . $row->__pk_val;

				/*
				$default = $w->parseMessageForPlaceHolder($params->get('calc_calculation'), $row);

				if (FabrikHelperHTML::isDebug())
				{
					$return->$key = eval($default);
				}
				else
				{
					$return->$key = @eval($default);
				}

				if ($store)
				{
					$listModel->storeCell($row->__pk_val, $storeKey, $return->$key);
				}
				*/

				$return->$key = $this->_getV(JArrayHelper::fromObject($row), 0);
			}
		}

		echo json_encode($return);
	}

	/**
	 * Turn form value into email formatted value
	 * $$$ hugh - I added this as for reasons I don't understand, something to do with
	 * how the value gets calc'ed during preProcess, sometimes the calc is "right" when
	 * it's submitted to the database, but wrong during form email plugin processing.  So
	 * I gave up trying to work out why, and now just re-calc it during getEmailData()
	 *
	 * @param   mixed  $value          Element value
	 * @param   array  $data           Form data
	 * @param   int    $repeatCounter  Group repeat counter
	 *
	 * @return  string  email formatted value
	 */

	protected function getIndEmailValue($value, $data = array(), $repeatCounter = 0)
	{
		$params = $this->getParams();

		if (!$params->get('calc_on_save_only', true))
		{
			$value = $this->_getV($data, $repeatCounter);
		}

		return $value;
	}

	/**
	 * Get database field description
	 * For calc, as we have no idea what they will be storing, needs to be TEXT.
	 *
	 * @return  string  db field type
	 */

	public function getFieldDescription()
	{
		if ($this->encryptMe())
		{
			return 'BLOB';
		}

		return "TEXT";
	}
}
