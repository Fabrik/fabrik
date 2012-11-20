<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.calc
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * Plugin element to render field with PHP calculated value
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.calc
 * @since       3.0
 */

class plgFabrik_ElementCalc extends plgFabrik_Element
{

	/**
	 * This really does get just the default value (as defined in the element's settings)
	 *
	 * @param   array  $data  form data
	 *
	 * @return mixed
	 */

	public function getDefaultValue($data = array())
	{
		if (!isset($this->_default))
		{
			$w = new FabrikWorker;
			$element = $this->getElement();
			$default = $w->parseMessageForPlaceHolder($element->default, $data, true, true);
			if ($element->eval == '1')
			{
				$default = @eval($default);
				FabrikWorker::logEval($default, 'Caught exception on eval of ' . $element->name . ': %s');
			}
			$this->_default = $default;
		}
		return $this->_default;
	}

	/**
	 * Get value
	 *
	 * @param   string  $data           value
	 * @param   int     $repeatCounter  repeat group counter
	 *
	 * @return  string
	 */

	private function _getV($data, $repeatCounter)
	{
		$w = new FabrikWorker;
		$groupModel = $this->getGroup();
		$joinid = $groupModel->getGroup()->join_id;
		$name = $this->getFullName(false, true, false);
		$params = $this->getParams();

		// $$$ hugh - if we don't do this, we get the cached default from the previous repeat
		if ($repeatCounter > 0)
		{
			unset($this->_default);
		}
		/**
		 *  $$$ hugh - don't think we want to do this here, otherwise calc gets run regardless of calc_on_save_only,
		 *  it just won't get used if 'true'
		 *  $default = $this->getDefaultValue($data, $repeatCounter);
		 */
		$default = '';
		/**
		 *  if viewing form or details view and calc set to always run then return the $default
		 *  which has had the calculation run on it.
		 */
		if (!$params->get('calc_on_save_only', true))
		{
			// $default = $this->getDefaultValue($data, $repeatCounter);
			$this->swapValuesForLabels($data);

			// $$$ hugh need to remove repeated joined data which is not part of this repeatCount
			$groupModel = $this->getGroup();
			if ($groupModel->isJoin())
			{
				if ($groupModel->canRepeat())
				{
					$joinid = $groupModel->getGroup()->join_id;
					if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid]))
					{
						foreach ($data['join'][$joinid] as $name => $values)
						{
							foreach ($data['join'][$joinid][$name] as $key => $val)
							{
								if ($key != $repeatCounter)
								{
									unset($data['join'][$joinid][$name][$key]);
								}
							}
						}
					}
				}
			}
			else
			{
				$data_copy = $data;
			}
			$default = $w->parseMessageForPlaceHolder($params->get('calc_calculation'), $data, true, true);
			$default = @eval($default);
			FabrikWorker::logEval($default, 'Caught exception on eval of ' . $this->getElement()->name . '::_getV(): %s');
			return $default;
		}
		$rawname = $name . '_raw';
		if ($groupModel->isJoin())
		{
			if ($groupModel->canRepeat())
			{
				if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid])
					&& array_key_exists($name, $data['join'][$joinid]) && array_key_exists($repeatCounter, $data['join'][$joinid][$name]))
				{
					$default = $data['join'][$joinid][$name][$repeatCounter];
				}
				else
				{
					if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid])
						&& array_key_exists($name, $data['join'][$joinid]) && array_key_exists($repeatCounter, $data['join'][$joinid][$name]))
					{
						$default = $data['join'][$joinid][$name][$repeatCounter];
					}
				}
			}
			else
			{
				if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid])
					&& array_key_exists($name, $data['join'][$joinid]))
				{
					$default = $data['join'][$joinid][$name];
				}
				else
				{
					if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid])
						&& array_key_exists($rawname, $data['join'][$joinid]))
					{
						$default = $data['join'][$joinid][$rawname];
					}
				}
			}
		}
		else
		{
			if ($groupModel->canRepeat())
			{
				// Repeat group NO join
				if (is_array($data))
				{
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
						if (array_key_exists($repeatCounter, $a))
						{
							$default = $a[$repeatCounter];
						}
					}
				}
			}
			else
			{
				// When called from getFilterArray via _getROElement, $data doesn't exist
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
		}
		return $default;
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
	 *
	 * @param   int  $c  repeat group counter
	 *
	 * @return void
	 */

	public function preProcess($c)
	{
		$params = $this->getParams();
		$w = new FabrikWorker;
		$form = $this->getForm();
		// $$$ hugh - need to copy the array, otherwise we blow away join data
		// from _formData in $joindata foreach below.
		//$d = $form->_formData;
		$d = unserialize(serialize($form->_formData));
		$joindata = JArrayHelper::getValue($d, 'join', array());
		$calc = $params->get('calc_calculation');
		$group = $this->getGroup();
		$joinid = $group->getGroup()->join_id;
		foreach ($joindata as $joinid => $thisJoindata)
		{
			foreach ($thisJoindata as $key => $val)
			{
				// If the joined group isn't repeated, $val will be a string
				if (is_array($val))
				{
					$d[$key] = JArrayHelper::getValue($val, $c, JArrayHelper::getValue($val, 0, ''));
				}
				else
				{
					$d[$key] = $val;
				}
			}
			unset($d['join'][$joinid]);
		}
		/**
		 * get the key name in dot format for updateFormData method
		 * $$$ hugh - added $rawkey stuff, otherwise when we did "$key . '_raw'" in the updateFormData
		 * below on repeat data, it ended up in the wrong format, like join.XX.table___element.0_raw
		 */
		$key = $this->getFullName(true, true, false);
		$shortkey = $this->getFullName(false, true, false);
		$rawkey = $key . '_raw';
		if ($group->canRepeat())
		{
			if ($group->isJoin())
			{
				$key = str_replace("][", '.', $key);
				$key = str_replace(array('[', ']'), '.', $key) . $c;
				$rawkey = str_replace($shortkey, $shortkey . '_raw', $key);
			}
			else
			{
				$key = $key . '.' . $c;
				$rawkey = $rawkey . '.' . $c;
			}
		}
		else
		{
			if ($group->isJoin())
			{
				$key = str_replace('][', '.', $key);
				$key = str_replace(array('[', ']'), '.', $key);
				$key = rtrim($key, '.');
				$rawkey = str_replace($shortkey, $shortkey . '_raw', $key);
			}
		}
		$this->swapValuesForLabels($d);

		// $$$ hugh - add $data same-same as $d, for consistency so user scripts know where data is
		$data = $d;
		$calc = @eval($w->parseMessageForPlaceHolder($calc, $d));
		FabrikWorker::logEval($calc, 'Caught exception on eval of ' . $this->getElement()->name . '::preProcess(): %s');
		$form->updateFormData($key, $calc);
		$form->updateFormData($rawkey, $calc);
	}

	/**
	 * Swap values for labels
	 *
	 * @param   array  &$d  data
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
				$elkey = $elementModel->getFullName(false, true, false);
				$v = JArrayHelper::getValue($d, $elkey);
				if (is_array($v))
				{
					$origdata = JArrayHelper::getValue($d, $elkey, array());
					foreach (array_keys($v) as $x)
					{
						$origval = JArrayHelper::getValue($origdata, $x);
						$d[$elkey][$x] = $elementModel->getLabelForValue($v[$x], $origval, $d);
					}
				}
				else
				{
					$d[$elkey] = $elementModel->getLabelForValue($v, JArrayHelper::getValue($d, $elkey), $d);
				}
			}
		}
	}

	/**
	 * Allows the element to pre-process a rows data before and join mergeing of rows
	 * occurs. Used in calc element to do cals on actual row rather than merged row
	 *
	 * @param   string  $element_data  elements data for the current row
	 * @param   object  $row           current row's data
	 *
	 * @since	3.0.5
	 *
	 * @return  string	formatted value
	 */

	public function preFormatFormJoins($element_data, $row)
	{
		$params = $this->getParams();
		$format = trim($params->get('calc_format_string'));
		/**
		 * $$$ hugh - the 'calculated value' bit is for legacy data that was created
		 * before we started storing a value when row is saved
		 */
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
			/**
			 *  $$$ hugh - trying to standardize on $data so scripts know where data is,
			 *  need $d here for backward compat
			 */
			$d = $data;
			$res = $listModel->parseMessageForRowHolder($cal, $data, true);
			$res = @eval($res);
			FabrikWorker::logEval($res, 'Caught exception on eval in ' . $element->name . '::renderListData() : %s');
			if ($format != '')
			{
				$res = sprintf($format, $res);
			}
			// $$$ hugh - need to set _raw, might be needed if (say) calc is being used as 'use_as_row_class'
			// See comments in formatData() in table model, we might could move this to a renderRawListData() method.
			$raw_name = $this->getFullName(false, true, false) . '_raw';
			$row->$raw_name = str_replace(GROUPSPLITTER, ',', $res);
			return parent::preFormatFormJoins($res, $row);
		}
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
		$val = $this->renderListData($data, $thisRow);
		$col = $this->getFullName(false, true, false);
		$raw = $col . '_raw';
		$thisRow->$raw = $val;
		return $val;
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
		$params = $this->getParams();
		$element = $this->getElement();
		$data = $this->getFormModel()->_data;
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
				$value = $this->_replaceWithIcons($value);
				$str[] = $value;
			}
			else
			{
				/*
				$str[] = '<input class="fabrikinput inputbox" disabled="disabled" name="'.$name.'" id="'.$id.'" value="'.$value.'" size="'.$element->width.'" />';
				 */
				if ($element->height<=1) {
					$str[] = '<span class="fabrikinput" name="' . $name . '" id="' . $id . '">' . $value . '</span>';
				} else {
					$str[] = '<textarea class="fabrikinput" disabled="disabled" name="' . $name . '" id="' . $id . '" cols="' . $element->width . '" rows="' . $element->height . '">' . $value . '</textarea>\n';
				}
			}
		}
		else
		{
			/* make a hidden field instead*/
			$str[] = '<input type="hidden" class="fabrikinput" name="' . $name . '" id="' . $id . '" value="' . $value . '" />';
		}
		$opts = array('alt' => JText::_('PLG_ELEMENT_CALC_LOADING'), 'style' => 'display:none;padding-left:10px;', 'class' => 'loader');
		$str[] = FabrikHelperHTML::image("ajax-loader.gif", 'form', @$this->tmpl, $opts);
		return implode("\n", $str);
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
		$opts->observe = $obs;
		$opts->id = $this->_id;
		$validations = $this->getValidations();
		$opts->validations = empty($validations) ? false : true;
		$opts = json_encode($opts);
		return "new FbCalc('$id', $opts)";
	}

	/**
	 * Perform calculation from ajax request
	 *
	 * @return  void
	 */

	public function onAjax_calc()
	{
		$this->setId(JRequest::getInt('element_id'));
		$this->getElement();
		$params = $this->getParams();
		$w = new FabrikWorker;
		$d = JRequest::get('request');

		$formModel = $this->getFormModel();
		$formModel->addEncrytedVarsToArray($d);
		$this->getFormModel()->_data = $d;
		$this->swapValuesForLabels($d);
		$calc = $params->get('calc_calculation');

		// $$$ hugh - trying to standardize on $data so scripts know where data is
		$data = $d;
		$calc = $w->parseMessageForPlaceHolder($calc, $d);
		$c = @eval($calc);
		$c = preg_replace('#(\/\*.*?\*\/)#', '', $c);
		echo $c;
	}

	/**
	 * Get sum query
	 *
	 * @param   object  &$listModel  list model
	 * @param   string  $label       label
	 *
	 * @return string
	 */

	protected function getSumQuery(&$listModel, $label = "'calc'")
	{
		$db = $listModel->getDb();
		$fields = $listModel->getDBFields($this->getTableName(), 'Field');
		$name = $this->getElement()->name;
		$field = JArrayHelper::getValue($fields, $name, false);
		if ($field !== false && $field->Type == 'time')
		{
			$name = $this->getFullName(false, false, false);
			$table = $listModel->getTable();
			$joinSQL = $listModel->_buildQueryJoin();
			$whereSQL = $listModel->_buildQueryWhere();
			return "SELECT SEC_TO_TIME(SUM(TIME_TO_SEC($name))) AS value, $label AS label FROM " . $db->quoteName($table->db_table_name)
				. " $joinSQL $whereSQL";
		}
		else
		{
			return parent::getSumQuery($listModel, $label);
		}
	}

	/**
	 * Build the query for the avg calculation
	 *
	 * @param   model   &$listModel  list model
	 * @param   string  $label       the label to apply to each avg
	 *
	 * @return  string	sql statement
	 */

	protected function getAvgQuery(&$listModel, $label = "'calc'")
	{
		$db = $listModel->getDb();
		$fields = $listModel->getDBFields($this->getTableName(), 'Field');
		$name = $this->getElement()->name;
		$field = JArrayHelper::getValue($fields, $name, false);
		if ($field !== false && $field->Type == 'time')
		{
			$name = $this->getFullName(false, false, false);
			$table = $listModel->getTable();
			$joinSQL = $listModel->_buildQueryJoin();
			$whereSQL = $listModel->_buildQueryWhere();
			return "SELECT SEC_TO_TIME(AVG(TIME_TO_SEC($name))) AS value, $label AS label FROM " . $db->quoteName($table->db_table_name)
				. " $joinSQL $whereSQL";
		}
		else
		{
			return parent::getAvgQuery($listModel, $label);
		}
	}

	/**
	 * Get a query for our media query
	 *
	 * @param   object  &$listModel  list
	 * @param   string  $label       label
	 *
	 * @return string
	 */

	protected function getMedianQuery(&$listModel, $label = "'calc'")
	{
		$db = $listModel->getDb();
		$fields = $listModel->getDBFields($this->getTableName(), 'Field');
		$name = $this->getElement()->name;
		$field = JArrayHelper::getValue($fields, $name, false);
		if ($field !== false && $field->Type == 'time')
		{
			$name = $this->getFullName(false, false, false);
			$table = $listModel->getTable();
			$joinSQL = $listModel->_buildQueryJoin();
			$whereSQL = $listModel->_buildQueryWhere();
			return "SELECT SEC_TO_TIME(TIME_TO_SEC($name)) AS value, $label AS label FROM " . $db->quoteName($table->db_table_name)
				. " $joinSQL $whereSQL";
		}
		else
		{
			return parent::getMedianQuery($listModel, $label);
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
}
