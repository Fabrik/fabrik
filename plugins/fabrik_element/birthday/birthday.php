<?php
/**
 * Plugin element to render day/month/year dropdowns
 * @package fabrikar
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

//jimport('joomla.application.component.model');

class plgFabrik_ElementBirthday extends plgFabrik_Element
{

	public $hasSubElements = true;

	protected $fieldDesc = 'DATE';

	/**
	 * draws the form element
	 * @param array data to preopulate element with
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render($data, $repeatCounter = 0)
	{
		//Jaanus: needed also here to not to show 0000-00-00 in detail view;
		//see also 58, added && !in_array($value, $aNullDates) (same reason).
		$db = JFactory::getDbo();
		$aNullDates = array('0000-00-000000-00-00','0000-00-00 00:00:00','0000-00-00','', $db->getNullDate());
		$name = $this->getHTMLName($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		$params = $this->getParams();
		$element = $this->getElement();
		$monthlabels = array(JText::_('January'), JText::_('February'), JText::_('March'), JText::_('April'), JText::_('May'), JText::_('June'), JText::_('July'), JText::_('August'), JText::_('September'), JText::_('October'), JText::_('November'), JText::_('December'));
		$monthnumbers = array('01','02','03','04','05','06','07','08','09','10','11','12');
		$daysys = array('01','02','03','04','05','06','07','08','09');
		$daysimple = array('1','2','3','4','5','6','7','8','9');

		$bits = array();
		// $$$ rob - not sure why we are setting $data to the form's data
		//but in table view when getting read only filter value from url filter this
		// _form_data was not set to no readonly value was returned
		// added little test to see if the data was actually an array before using it
		if (is_array($this->_form->_data))
		{
			$data = $this->_form->_data;
		}
		$value = $this->getValue($data, $repeatCounter);
		$fd = $params->get('details_date_format', 'd.m.Y');
		if (!$this->_editable)
		{
			if (!in_array($value, $aNullDates))
			{
				//avoid 0000-00-00
				list($year, $month, $day) = strstr($value, '-') ? explode('-', $value) : explode(',', $value);;
				$daydisp = str_replace($daysys, $daysimple, $day);
				$monthdisp = str_replace($monthnumbers, $monthlabels, $month);
				$thisyear = date('Y');
				$nextyear = date('Y') + 1;
				$lastyear = date('Y') - 1;
				// $$$ rob - all this below is nice but ... you still need to set a default
				$detailvalue= '';
				$year = ltrim($year, '0');
				if (FabrikWorker::isDate($value))
				{
					$date = JFactory::getDate($value);
					$detailvalue = $date->toFormat($fd);
				}
				if (date('m-d') < $month.'-'.$day) {
					$ageyear = $lastyear;
				}
				else
				{
					$ageyear = $thisyear;
				}
				if ($fd == 'd.m.Y') {
					$detailvalue = $day . '.' . $month . '.' . $year;
				}
				else
				{
					if ($fd == 'm.d.Y') {
						$detailvalue = $month . '/' . $day . '/' . $year;
					}
					if ($fd == 'D. month YYYY')
					{
						$detailvalue = $daydisp.'. '.$monthdisp.' '.$year;
					}
					if ($fd == 'Month d, YYYY')
					{
						$detailvalue = $monthdisp.' '.$daydisp.', '.$year;
					}
					if ($fd == '{age}')
					{
						$detailvalue = $ageyear - $year;
					}
					if ($fd == '{age} d.m')
					{
						$mdvalue = $daydisp.'. '.$monthdisp;
					}
					if ($fd == '{age} m.d')
					{
						$mdvalue = $monthdisp.' '.$daydisp;
					}
					if ($fd == '{age} d.m' || $fd == '{age} m.d')
					{
						$detailvalue = $ageyear - $year; // always actual age
						if (date('m-d') == $month.'-'.$day)
						{
							$detailvalue .= '<font color = "#CC0000"><b> ' . JText::_('TODAY') . '!</b></font>';
							if (date('m') == '12')
							{
								$detailvalue .= ' / ' . $nextyear . ': ' . ($nextyear - $year);
							}
						}
						else
						{
							$detailvalue .= ' (' . $mdvalue;
							if (date('m-d') < $month . '-' . $day)
							{
								$detailvalue .= ': '.($thisyear - $year);
							}
							else
							{
								$detailvalue .= '';
							}
							if (date('m') == '12') {
								$detailvalue .= ' / ' . $nextyear . ': ' . ($nextyear - $year);
							}
							else
							{
								$detailvalue .= '';
							}
							$detailvalue .= ')';
						}
					}
				}
				$value = $this->_replaceWithIcons($detailvalue);
				return($element->hidden == '1') ? "<!-- " . $detailvalue . " -->" : $detailvalue;
			}
			else
			{
				return '';
			}
		}
		else
		{
			//wierdness for failed validaion
			$value = strstr($value, ',') ? array_reverse(explode(',', $value)) : explode('-', $value);
			$yearvalue = JArrayHelper::getValue($value, 0);
			$monthvalue = JArrayHelper::getValue($value, 1);
			$dayvalue = JArrayHelper::getValue($value, 2);

			$days = array(JHTML::_('select.option', '', $params->get('birthday_daylabel', JText::_('DAY'))));
			for ($i = 1; $i < 32; $i++)
			{
				$days[] = JHTML::_('select.option', $i);
			}
			$months = array(JHTML::_('select.option', '', $params->get('birthday_monthlabel', JText::_('MONTH'))));
			//siin oli enne $monthlabels, viisin ülespoole
			for ($i = 0; $i<count($monthlabels); $i++)
			{
				$months[] = JHTML::_('select.option', $i + 1, $monthlabels[$i]);
			}
			$years = array(JHTML::_('select.option', '', $params->get('birthday_yearlabel', JText::_('YEAR'))));
			//Jaanus: now we can choose one exact year A.C to begin the dropdown AND would the latest year be current year or some years earlier/later.
			$date = date('Y') + (int)$params->get('birthday_forward', 0);
			$yearopt = $params->get('birthday_yearopt');
			$yearstart = (int)$params->get('birthday_yearstart');
			$yeardiff = $yearopt == 'number' ? $yearstart : $date - $yearstart;
			for ($i = $date; $i >= $date - $yeardiff; $i--)
			{
				$years[] = JHTML::_('select.option', $i);
			}
			$errorCSS = (isset($this->_elementError) && $this->_elementError != '') ? " elementErrorHighlight" : '';
			$attribs = 'class="fabrikinput inputbox'.$errorCSS.'"';
			$str = array();
			$str[] = '<div class="fabrikSubElementContainer" id="'.$id.'">';
			//$name already suffixed with [] as element hasSubElements = true
			$str[] = JHTML::_('select.genericlist', $days, preg_replace('#(\[\])$#','[0]',$name), $attribs, 'value', 'text', $dayvalue);
			$str[] = $params->get('birthday_separatorlabel', JText::_('/')) . ' ' .JHTML::_('select.genericlist', $months, preg_replace('#(\[\])$#','[1]',$name), $attribs, 'value', 'text', $monthvalue);
			$str[] = $params->get('birthday_separatorlabel', JText::_('/')) . ' ' .JHTML::_('select.genericlist', $years, preg_replace('#(\[\])$#','[2]',$name), $attribs, 'value', 'text', $yearvalue);
			$str[] = '</div>';
			return implode("\n", $str);
		}
	}

	/**
	 * can be overwritten by plugin class
	 * determines the value for the element in the form view
	 * @param array data
	 * @param int when repeating joinded groups we need to know what part of the array to access
	 * @param array options
	 * @return string value
	 */

	function getValue($data, $repeatCounter = 0, $opts = array())
	{
		//@TODO rename $this->defaults to $this->values
		if (!isset($this->defaults)) {
			$this->defaults = array();
		}
		if (!array_key_exists($repeatCounter, $this->defaults)) {
			$groupModel = $this->getGroup();
			$joinid = $groupModel->getGroup()->join_id;
			$formModel = $this->getForm();

			// $$$rob - if no search form data submitted for the search element then the default
			// selection was being applied instead
			$value = JArrayHelper::getValue($opts, 'use_default', true) == false ? '' : $this->getDefaultValue($data);

			$name = $this->getFullName(false, true, false);
			$rawname = $name . "_raw";
			if ($groupModel->isJoin()) {
				if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid])) {
					if ($groupModel->canRepeat()) {

						if (array_key_exists($rawname, $data['join'][$joinid]) && array_key_exists($repeatCounter, $data['join'][$joinid][$rawname])) {
							$value = $data['join'][$joinid][$rawname][$repeatCounter];
						} else {
							if (array_key_exists($rawname, $data['join'][$joinid]) && array_key_exists($repeatCounter, $data['join'][$joinid][$name])) {
								$value = $data['join'][$joinid][$name][$repeatCounter];
							}
						}
					} else {
						$value = JArrayHelper::getValue($data['join'][$joinid], $rawname, JArrayHelper::getValue($data['join'][$joinid], $name, $value));

						// $$$ rob if you have 2 tbl joins, one repeating and one not
						// the none repeating one's values will be an array of duplicate values
						// but we only want the first value
						if (is_array($value)) {
							$value = array_shift($value);
						}
					}
				}
			} else {
				if ($groupModel->canRepeat()) {
					//repeat group NO join
					$thisname = $rawname;
					if (!array_key_exists($name, $data)) {
						$thisname = $name;
					}
					if (array_key_exists($thisname, $data)) {
						if (is_array($data[$thisname])) {
							//occurs on form submission for fields at least
							$a = $data[$thisname];
						} else {
							//occurs when getting from the db
							$a = json_decode($data[$thisname]);
						}
						$value = JArrayHelper::getValue($a, $repeatCounter, $value);
					}

				} else {
					if (!is_array($data)) {
						$value = $data;
					} else {
						$value = JArrayHelper::getValue($data, $name, JArrayHelper::getValue($data, $rawname, $value));
					}
				}
			}

			if (is_array($value)) {
				$value = implode(',', $value);
			}
			if ($value === '') {
				//query string for joined data
				$value = JArrayHelper::getValue($data, $name, $value);
			}
			//@TODO perhaps we should change this to $element->value and store $element->default as the actual default value
			//stops this getting called from form validation code as it messes up repeated/join group validations
			if (array_key_exists('runplugins', $opts) && $opts['runplugins'] == 1) {
				FabrikWorker::getPluginManager()->runPlugins('onGetElementDefault', $formModel, 'form', $this);
			}
			$this->defaults[$repeatCounter] = $value;
		}
		return $this->defaults[$repeatCounter];
	}

	/**
	 * formats the posted data for insertion into the database
	 * @param mixed the elements posted form data
	 * @param array posted form data
	 */

	function storeDatabaseFormat($val, $data)
	{
		$groupModel = $this->getGroup();
		if ($groupModel->canRepeat()) {
			if (is_array($val)) {
				$res = array();
				foreach ($val as $v) {
					$res[] = $this->_indStoreDBFormat($v);
				}
				return json_encode($res);
			}
		}
		return $this->_indStoreDBFormat($val);
	}

	/**
	 * get the value to store the value in the db
	 *
	 * @param	mixed	$val (array normally but string on csv import)
	 * @return	string	yyyy-mm-dd 
	 */

	private function _indStoreDBFormat($val)
	{
		return is_array($val) ? $val[2].'-'.$val[1].'-'.$val[0] : '';
	}

	/**
	 * used in isempty validation rule
	 *
	 * @param array $data
	 * @return bol
	 */

	function dataConsideredEmpty($data, $repeatCounter)
	{
		if (strstr($data, ',')) {
			$data = explode(',', $data);
		}
		$data = (array)$data;
		foreach ($data as $d) {
			if (trim($d) == '') {
				return true;
			}
		}
		return false;
	}

	/**
	 * return the javascript to create an instance of the class defined in formJavascriptClass
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function elementJavascript($repeatCounter)
	{
		$id = $this->getHTMLId($repeatCounter);
		$opts = $this->getElementJSOptions($repeatCounter);
		$opts = json_encode($opts);
		return "new FbBirthday('$id', $opts)";
	}

	function renderListData($data, $oAllRowsData)
	{
		$db = FabrikWorker::getDbo();
		$aNullDates = array('0000-00-000000-00-00','0000-00-00 00:00:00','0000-00-00','', $db->getNullDate());
		$params = $this->getParams();
		$monthlabels = array(JText::_('January'), JText::_('February'), JText::_('March'), JText::_('April'), JText::_('May'), JText::_('June'), JText::_('July'), JText::_('August'), JText::_('September'), JText::_('October'), JText::_('November'), JText::_('December'));
		$monthnumbers = array('01','02','03','04','05','06','07','08','09','10','11','12');
		$daysys = array('01','02','03','04','05','06','07','08','09');
		$daysimple = array('1','2','3','4','5','6','7','8','9');
		$jubileum = array('0','25','75');

		$groupModel = $this->getGroup();
		$data = $groupModel->canRepeat() ? json_decode($data) : array($data);
		$data = (array)$data;
		$ft = $params->get('list_date_format', 'd.m.Y');
		//$ft = $params->get('birthday_format', 'd.m.Y'); //$ft = $params->get('birthday_format', '%Y-%m-%d');
		$fta = $params->get('list_age_format', 'no');
		$format = array();
		
		
		
		
		foreach ($data as $d) {
			if (!in_array($d, $aNullDates))
			{
				// $$$ rob default to a format date
				//$date = JFactory::getDate($d);
				//$datedisp = $date->toFormat($ft);
				// Jaanus: sorry, but in this manner the element doesn't work with dates earlier than 1901
				
				list($year, $month, $day) = explode('-', $d);
				$daydisp = str_replace($daysys, $daysimple, $day);
				$monthdisp = str_replace($monthnumbers, $monthlabels, $month);
				$nextyear = date('Y') + 1;
				$lastyear = date('Y') - 1;
				$thisyear = date('Y');
				$year = ltrim($year, '0');
				$dmy = $day . '.' . $month . '.' . $year;
				$mdy = $month . '/' . $day . '/' . $year;
				$dmonthyear = $daydisp . '. ' . $monthdisp . ' ' . $year;
				$monthdyear = $monthdisp . ' ' . $daydisp . ', ' . $year;
				if ($ft == "d.m.Y")
				{
					$datedisp = $dmy;
				}
				else
				{
					if ($ft == "m.d.Y")
					{
						$datedisp = $mdy;
					}
					if ($ft == "D. month YYYY")
					{
						$datedisp = $dmonthyear;
					}
					if ($ft == "Month d, YYYY")
					{
						$datedisp = $monthdyear;
					}
				}
				if ($fta == 'no')
				{
					$format[] = $datedisp;
				}
				else
				{
					if (date('m-d') == $month.'-'.$day)
					{
						if ($fta == '{age}')
						{
							$format[] = '<font color ="#DD0000"><b>' . ($thisyear - $year)."</b></font>";
						}
						else
						{
							if ($fta == '{age} date')
							{
								$format[] = '<font color ="#DD0000"><b>' . $datedisp . ' (' . ($thisyear - $year) . ')</b></font>';
							}
							if ($fta == '{age} this')
							{
								$format[] = '<font color ="#DD0000"><b>' . ($thisyear - $year) . ' (' . $datedisp . ')</b></font>';
							}
							if ($fta == '{age} next')
							{
								$format[] = '<font color ="#DD0000"><b>' . ($nextyear - $year) . ' (' . $datedisp . ')</b></font>';
							}
						}
					}
					else
					{
						if ($fta == '{age} date')
						{
							if (date('m-d') > $month . '-' . $day)
							{
								$format[] = $datedisp . ' (' . ($thisyear - $year) . ')';
							}
							else
							{
								$format[] = $datedisp . ' (' . ($lastyear - $year) . ')';
							}
						}
						else
						{
							if ($fta == '{age}')
							{
								if (date('m-d') > $month . '-' . $day)
								{
									$format[] = $thisyear - $year;
								}
								else
								{
									$format[] = $lastyear - $year;
								}
							}
							else
							{
								if ($fta == '{age} this')
								{
									if (in_array(substr(($thisyear - $year),-1), $jubileum) || in_array(substr(($thisyear - $year), -2), $jubileum))
									{
										$format[] = '<b>'.($thisyear - $year).' ('.$datedisp.')</b>';
									}
									else
									{
										$format[] = ($thisyear - $year).' ('.$datedisp.')';
									}
								}
								if ($fta == '{age} next')
								{
									if (in_array(substr(($nextyear - $year), -1), $jubileum) || in_array(substr(($nextyear - $year),-2),$jubileum))
									{
										$format[] = '<b>' . ($nextyear - $year) . ' (' . $datedisp . ')</b>';
									}
									else
									{
										$format[] = ($nextyear - $year).' ('.$datedisp.')';
									}
								}
							}
						}
					}
				}
			}
			else
			{
				$format[] = '';
			}
		}
		$data = json_encode($format);
		return parent::renderListData($data, $oAllRowsData);
	}

}
?>