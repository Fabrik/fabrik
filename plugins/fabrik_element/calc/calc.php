<?php
/**
 * Plugin element to render field with PHP calculated value
 * @package fabrikar
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

class plgFabrik_ElementCalc extends plgFabrik_Element
{

	/**
	 * this really does get just the default value (as defined in the element's settings)
	 * @param array data
	 * @return string
	 */

	function getDefaultValue($data = array(), $repeatCounter = 0)
	{
		if (!isset($this->_default)) {
			$w = new FabrikWorker();
			$element = $this->getElement();
			$default = $w->parseMessageForPlaceHolder($element->default, $data, true, true);
			if ($element->eval == "1") {
				$default = @eval($default);
				FabrikWorker::logEval($default, 'Caught exception on eval of ' . $element->name . ': %s');
			}
			$this->_default = $default;
		}
		return $this->_default;
	}

	private function _getV($data, $repeatCounter)
	{
		$w = new FabrikWorker();
		$groupModel = $this->getGroup();
		$joinid = $groupModel->getGroup()->join_id;
		$name = $this->getFullName(false, true, false);
		$params = $this->getParams();
		// $$$ hugh - if we don't do this, we get the cached default from the previous repeat
		if ($repeatCounter > 0) {
			unset($this->_default);
		}
		// $$$ hugh - don't think we want to do this here, otherwise calc gets run regardless of calc_on_save_only,
		// it just won't get used if 'true'
		//$default = $this->getDefaultValue($data, $repeatCounter);
		$default = '';
		// if viewing form or details view and calc set to always run then return the $default
		//which has had the calculation run on it.
		if (!$params->get('calc_on_save_only', true)) {
			//$default = $this->getDefaultValue($data, $repeatCounter);
			$this->swapValuesForLabels($data);
			// $$$ hugh need to remove repeated joined data which is not part of this repeatCount
			$groupModel = $this->getGroup();
			if ($groupModel->isJoin()) {
				if ($groupModel->canRepeat()) {
					$joinid = $groupModel->getGroup()->join_id;
					if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid])) {
						foreach ($data['join'][$joinid] as $name => $values) {
							foreach ($data['join'][$joinid][$name] as $key => $val) {
								if ($key != $repeatCounter) {
									unset($data['join'][$joinid][$name][$key]);
								}
							}
						}
					}
				}
			}
			$default = $w->parseMessageForPlaceHolder($params->get('calc_calculation'), $data, true, true);
			$default = @eval($default);
			FabrikWorker::logEval($default, 'Caught exception on eval of ' . $this->getElement()->name . '::_getV(): %s');
			return $default;
		}
		$rawname = $name . "_raw";
		if ($groupModel->isJoin()) {
			if ($groupModel->canRepeat()) {
				if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid]) && array_key_exists($name, $data['join'][$joinid]) && array_key_exists($repeatCounter, $data['join'][$joinid][$name])) {
					$default = $data['join'][$joinid][$name][$repeatCounter];
				}else{
					if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid]) && array_key_exists($name, $data['join'][$joinid]) && array_key_exists($repeatCounter, $data['join'][$joinid][$name])) {
						$default = $data['join'][$joinid][$name][$repeatCounter];
					}
				}
			} else {
				if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid]) && array_key_exists($name, $data['join'][$joinid])) {
					$default = $data['join'][$joinid][$name];
				} else {
					if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid]) && array_key_exists($rawname, $data['join'][$joinid])) {
						$default = $data['join'][$joinid][$rawname];
					}
				}
			}
		} else {
			if ($groupModel->canRepeat()) {
				//repeat group NO join
				if (is_array($data)) {
					$thisname = $name;
					if (!array_key_exists($name, $data)) {
						$thisname = $rawname;
					}
					if (array_key_exists($thisname, $data)) {
						if (is_array($data[$thisname])) {
							//occurs on form submission for fields at least
							$a = $data[$thisname];
						} else {
							//occurs when getting from the db
							$a = json_decode($data[$thisname]);
						}
						if (array_key_exists($repeatCounter, $a)) {
							$default = $a[$repeatCounter];
						}
					}
				}
			} else {
				// when called from getFilterArray via _getROElement, $data doesn't exist
				// (i.e. when specified as a table___name=foo in a content plugin)
				if (is_array($data)) {
					if (array_key_exists($name, $data)) {
						$default = $data[$name];
					}else{
						if (array_key_exists($rawname, $data)) {
							$default = $data[$rawname];
						}
					}
				}
			}
		}
		return $default;
	}
	/**
	 * determines the value for the element in the form view
	 * @param array data
	 * @param int when repeating joinded groups we need to know what part of the array to access
	 * @param array options
	 * @return string value
	 */

	function getValue($data, $repeatCounter = 0, $opts = array())
	{
		if (!isset($this->defaults) || is_null($this->defaults)) {
			$this->defaults = array();
		}
		if (!array_key_exists($repeatCounter, $this->defaults)) {
			$element = $this->getElement();
			$element->default = $this->_getV($data, $repeatCounter);
			if ($element->default === '') { //query string for joined data
				// $$$ rob commented out as $name not defined and not sure what t should be
				//$element->default = JArrayHelper::getValue($data, $name);
			}
			$formModel = $this->getForm();
			//stops this getting called from form validation code as it messes up repeated/join group validations
			if (array_key_exists('runplugins', $opts) && $opts['runplugins'] == 1) {
				FabrikWorker::getPluginManager()->runPlugins('onGetElementDefault', $formModel, 'form', $this);
			}
			if (is_array($element->default)) {
				$element->default = implode(',', $element->default);
			}
			$this->defaults[$repeatCounter] = $element->default;
		}
		return $this->defaults[$repeatCounter];
	}

	/**
	 * run on formModel::setFormData()
	 * set before form is validated
	 * @param int repeat group counter
	 * @return null
	 */

	public function preProcess($c)
	{
		$params = $this->getParams();
		$w = new FabrikWorker();
		$form = $this->getForm();
		$d = $form->_formData;
		$joindata = JArrayHelper::getValue($d, 'join', array());
		$calc = $params->get('calc_calculation');
		$group = $this->getGroup();
		$joinid = $group->getGroup()->join_id;

		foreach ($joindata as $joinid => $thisJoindata) {
			foreach ($thisJoindata as $key => $val) {
				// if the joined group isn't repeated, $val will be a string
				if (is_array($val)) {
					$d[$key] = JArrayHelper::getValue($val, $c, JArrayHelper::getValue($val, 0, ''));
				} else {
					$d[$key] = $val;
				}
			}
			unset($d['join'][$joinid]);
		}
		//get the key name in dot format for updateFormData method
		// $$$ hugh - added $rawkey stuff, otherwise when we did "$key . '_raw'" in the updateFormData
		// below on repeat data, it ended up in the wrong format, like join.XX.table___element.0_raw
		// instead of join.XX.table___element_raw.0
		$key = $this->getFullName(true, true, false);
		$shortkey = $this->getFullName(false, true, false);
		$rawkey = $key . '_raw';
		if ($group->canRepeat()) {
			if ($group->isJoin()) {
				$key = str_replace("][", '.', $key);
				$key = str_replace(array('[',']'), '.', $key)."$c";
				$rawkey = str_replace($shortkey, $shortkey . '_raw', $key);
			}
			else {
				$key = $key . '.' . $c;
				$rawkey = $rawkey . '.' . $c;
			}
		}
		else {
			if ($group->isJoin()) {
				$key = str_replace("][", ".", $key);
				$key = str_replace(array('[',']'), '.', $key);
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

	function swapValuesForLabels(&$d)
	{
		$groups = $this->getForm()->getGroupsHiarachy();
		foreach (array_keys($groups) as $gkey) {
			$group = $groups[$gkey];
			$elementModels = $group->getPublishedElements();
			for ($j=0; $j < count($elementModels); $j++) {
				$elementModel = $elementModels[$j];
				$elkey = $elementModel->getFullName(false, true, false);
				$v = JArrayHelper::getValue($d, $elkey);
				if (is_array($v)) {
					$origdata = JArrayHelper::getValue($d, $elkey, array());
					foreach (array_keys($v) as $x) {
						$origval = JArrayHelper::getValue($origdata, $x);
						$d[$elkey][$x] = $elementModel->getLabelForValue($v[$x], $origval, $d);
					}
				} else {
					$d[$elkey] = $elementModel->getLabelForValue($v, JArrayHelper::getValue($d, $elkey), $d);
				}
			}
		}
	}
	/**
	 * shows the data formatted for the table view
	 * @param string data
	 * @param object current row's data
	 * @return string formatted value
	 */

	function renderListData($element_data, &$thisRow)
	{
		$params = $this->getParams();
		$format = trim($params->get('calc_format_string'));
		// $$$ hugh - the 'calculated value' bit is for legacy data that was created
		// before we started storing a value when row is saved
		if ($params->get('calc_on_save_only', 0) && $element_data != 'calculated value') {
			if ($format != '') {
				$element_data = sprintf($format, $element_data);
			}
			return parent::renderListData($element_data, $thisRow);
		}
		else {
			$element = $this->getElement();
			$cal = $params->get('calc_calculation', '');
			$listModel = $this->getlistModel();
			$formModel = $this->getFormModel();
			$data = JArrayHelper::fromObject($thisRow);
			$data['rowid'] = $data['__pk_val'];
			$data['fabrik'] = $formModel->getId();
			// $$$ hugh - trying to standardize on $data so scripts know where data is,
			// need $d here for backward compat
			$d = $data;
			$res = $listModel->parseMessageForRowHolder($cal, $data, true);
			$res = @eval($res);
			FabrikWorker::logEval($res, 'Caught exception on eval in '.$element->name.'::renderListData() : %s');
			if ($format != '') {
				$res = sprintf($format, $res);
			}
			// $$$ hugh - need to set _raw, might be needed if (say) calc is being used as 'use_as_row_class'
			// See comments in formatData() in table model, we might could move this to a renderRawListData() method.
			$raw_name = $this->getFullName(false, true, false) . '_raw';
			$thisRow->$raw_name = str_replace(GROUPSPLITTER, ',', $res);
			return parent::renderListData($res, $thisRow);
		}
	}

	/**
	 * fudge the CSV export so that we get the calculated result regardless of whether
	 * the value has been stored in the database base (mimics what the user would see in the table view)
	 * @see components/com_fabrik/models/plgFabrik_Element#renderListData($data, $oAllRowsData)
	 */

	function renderListData_csv($data, &$thisRow)
	{
		$val = $this->renderListData($data, $thisRow);
		$col = $this->getFullName(false, true, false);
		$raw = $col . '_raw';
		$thisRow->$raw = $val;
		return $val;
	}

	/**
	 * draws the form element
	 * @param array data
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render($data, $repeatCounter = 0)
	{
		$params = $this->getParams();
		$element = $this->getElement();
		$data = $this->getFormModel()->_data;
		$value = $this->getValue($data, $repeatCounter);
		$format = $params->get('calc_format_string');
		if ($format != '') {
			$value = sprintf($format, $value);
		}
		$name = $this->getHTMLName($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		$str = array();
		if ($this->canView()) {
			if (!$this->_editable) {
				$value = $this->_replaceWithIcons($value);
				$str[] = $value;
			}
			else {
				$str[] = '<input class="fabrikinput inputbox" disabled="disabled" name="'.$name.'" id="'.$id.'" value="'.$value.'" size="'.$element->width.'" />';
			}
		} else {
			/* make a hidden field instead*/
			$str[] = '<input type="hidden" class="fabrikinput" name="'.$name.'" id="'.$id.'" value="'.$value.'" />';
		}
		$str[] = FabrikHelperHTML::image("ajax-loader.gif", 'form', @$this->tmpl, array('alt' => JText::_('PLG_ELEMENT_CALC_LOADING'), 'style' => 'display:none;padding-left:10px;', 'class' => 'loader'));
		return implode("\n", $str);
	}

	/**
	 * return tehe javascript to create an instance of the class defined in formJavascriptClass
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function elementJavascript($repeatCounter)
	{
		$id = $this->getHTMLId($repeatCounter);
		$opts = $this->getElementJSOptions($repeatCounter);
		$params = $this->getParams();
		$calc = $params->get('calc_calculation');
		$obs = explode(',', $params->get('calc_ajax_observe'));
		if (preg_match_all("/{[^}\s]+}/i", $calc, $matches) !== 0) {
			$matches = $matches[0];
			$obs = array_merge($obs, $matches);
		}

		foreach ($obs as &$m) {
			$m = str_replace(array('{', '}'), '', $m);
			// $$$ hugh - we need to knock any _raw off, so JS can match actual element ID
			$m = preg_replace('#_raw$#', '', $m);
		}

		$opts->ajax = $params->get('calc_ajax', 0) == 0 ? false : true;
		$opts->observe = $obs;
		$opts->id = $this->_id;
		$opts = json_encode($opts);
		return "new FbCalc('$id', $opts)";
	}

	function onAjax_calc()
	{
		$this->setId(JRequest::getInt('element_id'));
		$this->getElement();
		$params = $this->getParams();
		$w = new FabrikWorker();
		$d = JRequest::get('request');
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
	 * find the sum from a set of data
	 * @param object list model
	 * @return string sum result
	 */

	protected function getSumQuery(&$listModel, $label = "'calc'")
	{
		$db = $listModel->getDb();
		$fields = $listModel->getDBFields($this->getTableName(), 'Field');
		if ($fields[$this->getElement()->name]->Type == 'time') {
			$name = $this->getFullName(false, false, false);
			$table = $listModel->getTable();
			$joinSQL = $listModel->_buildQueryJoin();
			$whereSQL = $listModel->_buildQueryWhere();
			return "SELECT SEC_TO_TIME(SUM(TIME_TO_SEC($name))) AS value, $label AS label FROM " . $db->nameQuote($table->db_table_name) . " $joinSQL $whereSQL";
		} else {
			return parent::getSumQuery($listModel, $label);
		}
	}

	/**
	 * build the query for the avg caclculation
	 * @param model $listModel
	 * @param string $label the label to apply to each avg
	 * @return string sql statement
	 */

	protected function getAvgQuery(&$listModel, $label = "'calc'")
	{
		$db = $listModel->getDb();
		$fields = $listModel->getDBFields($this->getTableName(), 'Field');
		if ($fields[$this->getElement()->name]->Type == 'time') {
			$name = $this->getFullName(false, false, false);
			$table = $listModel->getTable();
			$joinSQL = $listModel->_buildQueryJoin();
			$whereSQL = $listModel->_buildQueryWhere();
			return "SELECT SEC_TO_TIME(AVG(TIME_TO_SEC($name))) AS value, $label AS label FROM " . $db->nameQuote($table->db_table_name) . " $joinSQL $whereSQL";
		} else {
			return parent::getAvgQuery($listModel, $label);
		}
	}

	/**
	 * build the query for the avg caclculation
	 * @param model $listModel
	 * @param string $label the label to apply to each avg
	 * @return string sql statement
	 */

	protected function getMedianQuery(&$listModel, $label = "'calc'")
	{
		$db = $listModel->getDb();
		$fields = $listModel->getDBFields($this->getTableName(), 'Field');
		if ($fields[$this->getElement()->name]->Type == 'time') {
			$name = $this->getFullName(false, false, false);
			$table = $listModel->getTable();
			$joinSQL = $listModel->_buildQueryJoin();
			$whereSQL = $listModel->_buildQueryWhere();
			return "SELECT SEC_TO_TIME(TIME_TO_SEC($name)) AS value, $label AS label FROM " . $db->nameQuote($table->db_table_name) . " $joinSQL $whereSQL";
		} else {
			return parent::getMedianQuery($listModel, $label);
		}
	}
	
	/**
	 * @since 3.0.4
	 * get the sprintf format string 
	 * @return string
	 */
	
	public function getFormatString()
	{
		$params = $this->getParams();
		return $params->get('calc_format_string');
	}
}
?>