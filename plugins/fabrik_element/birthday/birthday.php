<?php
/**
 * Plugin element to render day/month/year dropdowns
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.birthday
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * Plugin element to render day/month/year drop-downs
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.birthday
 * @since       3.0
 */

class PlgFabrik_ElementBirthday extends PlgFabrik_Element
{
	/**
	 * Does the element contain sub elements e.g checkboxes radio-buttons
	 *
	 * @var bool
	 */
	public $hasSubElements = true;

	/**
	 * Get db table field type
	 *
	 * @return  string
	 */
	public function getFieldDescription()
	{
		return 'DATE';
	}

	/**
	 * Determines the value for the element in the form view
	 *
	 * @param   array  $data           Form data
	 * @param   int    $repeatCounter  When repeating joined groups we need to know what part of the array to access
	 * @param   array  $opts           Options, 'raw' = 1/0 use raw value
	 *
	 * @return  string	value
	 */

	public function getValue($data, $repeatCounter = 0, $opts = array())
	{
		$value = parent::getValue($data, $repeatCounter, $opts);

		if (is_array($value))
		{
			$day = FArrayHelper::getValue($value, 0);
			$month = FArrayHelper::getValue($value, 1);
			$year = FArrayHelper::getValue($value, 2);
			$value = $year . '-' . $month . '-' . $day;
		}

		return $value;
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
		/**
		 * Jaanus: needed also here to not to show 0000-00-00 in detail view;
		 * see also 58, added && !in_array($value, $aNullDates) (same reason).
		 */
		$aNullDates = array('0000-00-000000-00-00', '0000-00-00 00:00:00', '0000-00-00', '', $this->_db->getNullDate());
		$name = $this->getHTMLName($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		$params = $this->getParams();
		$element = $this->getElement();
		$monthLabels = $this->_monthLabels();
		$monthNumbers = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12');
		$daySys = array('01', '02', '03', '04', '05', '06', '07', '08', '09');
		$daySimple = array('1', '2', '3', '4', '5', '6', '7', '8', '9');

		/**
		 * $$$ rob - not sure why we are setting $data to the form's data
		 * but in table view when getting read only filter value from url filter this
		 * _form_data was not set to no readonly value was returned
		 * added little test to see if the data was actually an array before using it
		 */
		if (is_array($this->getFormModel()->data))
		{
			$data = $this->getFormModel()->data;
		}

		$value = $this->getValue($data, $repeatCounter);
		$fd = $params->get('details_date_format', 'd.m.Y');
		$dateAndAge = (int) $params->get('details_dateandage', '0');

		if (!$this->isEditable())
		{
			if (!in_array($value, $aNullDates))
			{
				// Avoid 0000-00-00
				list($year, $month, $day) = strstr($value, '-') ? explode('-', $value) : explode(',', $value);
				$dayDisplay = str_replace($daySys, $daySimple, $day);
				$monthDisplay = str_replace($monthNumbers, $monthLabels, $month);
				$thisYear = date('Y');
				$nextYear = date('Y') + 1;
				$lastYear = date('Y') - 1;

				// $$$ rob - all this below is nice but ... you still need to set a default
				$detailValue = '';
				$year = JString::ltrim($year, '0');

				if (FabrikWorker::isDate($value))
				{
					$date = JFactory::getDate($value);
					$detailValue = $date->format($fd);
				}

				if (date('m-d') < $month . '-' . $day)
				{
					$ageYear = $lastYear;
				}
				else
				{
					$ageYear = $thisYear;
				}

				if ($fd == 'd.m.Y')
				{
					$detailValue = $day . '.' . $month . '.' . $year;
				}
				else
				{
					if ($fd == 'm.d.Y')
					{
						$detailValue = $month . '.' . $day . '.' . $year;
					}

					if ($fd == 'd/m/Y')
					{
						$detailValue = $day . '/' . $month . '/' . $year;
					}

					if ($fd == 'D. month YYYY')
					{
						$detailValue = $dayDisplay . '. ' . $monthDisplay . ' ' . $year;
					}
					
					if ($fd == 'D month YYYY')
					{
						$detailValue = $dayDisplay . ' ' . $monthDisplay . ' ' . $year;
					}

					if ($fd == 'Month d, YYYY')
					{
						$detailValue = $monthDisplay . ' ' . $dayDisplay . ', ' . $year;
					}

					if ($fd == '{age}')
					{
						$detailValue = $ageYear - $year;
					}

					if ($fd == '{age} d.m')
					{
						$monthDayValue = $dayDisplay . '. ' . $monthDisplay;
					}

					if ($fd == '{age} m.d')
					{
						$monthDayValue = $monthDisplay . ' ' . $dayDisplay;
					}

					if ($fd == '{age} d.m' || $fd == '{age} m.d')
					{
						// Always actual age
						$detailValue = $ageYear - $year;

						if (date('m-d') == $month . '-' . $day)
						{
							$detailValue .= '<span style="color:#CC0000"><b> ' . FText::_('TODAY') . '!</b></span>';

							if (date('m') == '12')
							{
								$detailValue .= ' / ' . $nextYear . ': ' . ($nextYear - $year);
							}
						}
						else
						{
							$detailValue .= ' (' . $monthDayValue;

							if (date('m-d') < $month . '-' . $day)
							{
								$detailValue .= ': ' . ($thisYear - $year);
							}
							else
							{
								$detailValue .= '';
							}

							if (date('m') == '12')
							{
								$detailValue .= ' / ' . $nextYear . ': ' . ($nextYear - $year);
							}
							else
							{
								$detailValue .= '';
							}

							$detailValue .= ')';
						}
					}
					else
					{
						if ($fd != '{age}' && $dateAndAge == 1)
						{
							$detailValue .= ' (' . ($ageYear - $year) . ')';
						}
					}
				}

				$layout = $this->getLayout('detail');
				$layoutData = new stdClass;
				$layoutData->text =  $this->replaceWithIcons($detailValue);
				$layoutData->hidden = $element->hidden;

				return $layout->render($layoutData);
			}
			else
			{
				return '';
			}
		}
		else
		{
			// Weirdness for failed validation
			$value = strstr($value, ',') ? array_reverse(explode(',', $value)) : explode('-', $value);
			$yearValue = FArrayHelper::getValue($value, 0);
			$monthValue = FArrayHelper::getValue($value, 1);
			$dayValue = FArrayHelper::getValue($value, 2);
			$errorCSS = (isset($this->_elementError) && $this->_elementError != '') ? ' elementErrorHighlight' : '';
			$advancedClass = $this->getAdvancedSelectClass();

			$attributes = 'class="input-small fabrikinput inputbox ' . $advancedClass . ' ' . $errorCSS . '"';

			$layout = $this->getLayout('form');
			$layoutData = new stdClass;
			$layoutData->id = $id;
			$layoutData->separator = $params->get('birthday_separatorlabel', FText::_('/'));
			$layoutData->attribs = $attributes;
			$layoutData->day_name = preg_replace('#(\[\])$#', '[0]', $name);
			$layoutData->day_id = $id . '_0';
			$layoutData->day_options = $this->_dayOptions();
			$layoutData->day_value = ltrim($dayValue, "0");

			$layoutData->month_name = preg_replace('#(\[\])$#', '[1]', $name);
			$layoutData->month_id = $id . '_1';
			$layoutData->month_options = $this->_monthOptions();
			$layoutData->month_value = ltrim($monthValue, '0');

			$layoutData->year_name = preg_replace('#(\[\])$#', '[2]', $name);
			$layoutData->year_id = $id . '_2';
			$layoutData->year_options = $this->_yearOptions();
			$layoutData->year_value = $yearValue;


			return $layout->render($layoutData);
		}
	}

	/**
	 * Get month labels
	 *
	 * @return array
	 */
	private function _monthLabels()
	{
		return array(FText::_('January'), FText::_('February'), FText::_('March'), FText::_('April'), FText::_('May'), FText::_('June'),
		FText::_('July'), FText::_('August'), FText::_('September'), FText::_('October'), FText::_('November'), FText::_('December'));
	}

	/**
	 * Get select list day options
	 * @return array
	 */
	private function _dayOptions()
	{
		$params = $this->getParams();
		$days = array(
			JHTML::_(
				'select.option',
				'',
				FText::_($params->get('birthday_daylabel', 'PLG_ELEMENT_BIRTHDAY_DAY')),
				'value',
				'text',
				false
			)
		);

		for ($i = 1; $i < 32; $i++)
		{
			$days[] = JHTML::_('select.option', (string) $i);
		}

		return $days;
	}

	/**
	 * Get select list month options
	 *
	 * @return array
	 */
	private function _monthOptions()
	{
		$params = $this->getParams();
		$months = array(
			JHTML::_(
				'select.option',
				'',
				FText::_($params->get('birthday_monthlabel', 'PLG_ELEMENT_BIRTHDAY_MONTH')),
				'value',
				'text',
				false
			)
		);
		$monthLabels = $this->_monthLabels();

		for ($i = 0; $i < count($monthLabels); $i++)
		{
			$months[] = JHTML::_('select.option', (string) ($i + 1), $monthLabels[$i]);
		}

		return $months;
	}

	/**
	 * Get select list year options
	 * @return array
	 */
	private function _yearOptions()
	{
		$params = $this->getParams();
		$years = array(JHTML::_('select.option', '', FText::_($params->get('birthday_yearlabel', 'PLG_ELEMENT_BIRTHDAY_YEAR'))));
		$years = array(
			JHTML::_(
				'select.option',
				'',
				FText::_($params->get('birthday_yearlabel', 'PLG_ELEMENT_BIRTHDAY_YEAR')),
				'value',
				'text',
				false
			)
		);
		// Jaanus: now we can choose one exact year A.C to begin the dropdown AND would the latest year be current year or some years earlier/later.
		$date = date('Y') + (int) $params->get('birthday_forward', 0);
		$yearOpt = $params->get('birthday_yearopt');
		$yearStart = (int) $params->get('birthday_yearstart');
		$yearDiff = $yearOpt == 'number' ? $yearStart : $date - $yearStart;

		for ($i = $date; $i >= $date - $yearDiff; $i--)
		{
			$years[] = JHTML::_('select.option', (string) $i);
		}

		return $years;
	}

	/**
	 * Manipulates posted form data for insertion into database
	 *
	 * @param   mixed  $val   this elements posted form data
	 * @param   array  $data  posted form data
	 *
	 * @return  mixed
	 */

	public function storeDatabaseFormat($val, $data)
	{
		return $this->_indStoreDBFormat($val);
	}

	/**
	 * Get the value to store the value in the db
	 * Jaanus: stores the value if all its parts (day, month, year) are selected in form, otherwise stores
	 * (or updates data to) null value. NULL is useful in many cases, e.g when using Fabrik for working
	 * with data of such components as EventList, where in #___eventlist_events.enddates (times and endtimes as well)
	 * empty data is always NULL otherwise nulldate is displayed in its views.
	 *
	 * @param   mixed  $val  (array normally but string on csv import)
	 *
	 * @TODO: if NULL value is the first in repeated group then in list view whole group is empty.
	 * Could anyone find a solution? I give up :-(
	 * Paul 20130904 I fixed the id fields and I am getting a string passed in as $val here yyyy-m-d.
	 * Jaanus: saved data could be date or nothing (null). Previous return '' wrote always '0000-00-00' as DATE field doesn't know ''. 
	 * such value as '' and therefore setting element to save null hadn't expected impact. Simple return; returns null as it should. 
	 *
	 *
	 * @return  string	yyyy-mm-dd or null or 0000-00-00 if needed and set
	 */

	private function _indStoreDBFormat($val)
	{
		$params = $this->getParams();

		if (is_array($val))
		{
			if ($params->get('empty_is_null', '1') == 0 || !in_array('', $val))
			{
				return $val[2] . '-' . $val[1] . '-' . $val[0];
			}
		}
		else
		{
			if ($params->get('empty_is_null', '1') == '0' || !in_array('', explode('-',$val)))
			{
				return $val;
			}
		}

		return;
	}

	/**
	 * Does the element consider the data to be empty
	 * Used in isempty validation rule
	 *
	 * @param   array  $data           data to test against
	 * @param   int    $repeatCounter  repeat group #
	 *
	 * @return  bool
	 */

	public function dataConsideredEmpty($data, $repeatCounter)
	{

		$data = str_replace('-', ',', $data);

		if (strstr($data, ','))
		{
			$data = explode(',', $data);
		}

		$data = (array) $data;

		foreach ($data as $d)
		{
			if (trim($d) == '')
			{
				return true;
			}
		}

		return false;
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
		$params = $this->getParams();
		$id = $this->getHTMLId($repeatCounter);
		$opts = $this->getElementJSOptions($repeatCounter);
		$opts->separator = $params->get('birthday_separatorlabel', FText::_('/'));

		return array('FbBirthday', $id, $opts);
	}

	/**
	 * Prepares the element data for CSV export
	 *
	 * @param   string  $data      Element data
	 * @param   object  &$thisRow  All the data in the lists current row
	 *
	 * @return  string	Formatted CSV export value
	 */

	public function renderListData_csv($data, &$thisRow)
	{
		return $this->renderListData($data, $thisRow);
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
        JDEBUG ? $profiler->mark("renderListData: {$this->element->plugin}: start: {$this->element->name}") : null;

        $groupModel = $this->getGroup();
		/**
		 * Jaanus: json_decode replaced with FabrikWorker::JSONtoData that made visible also single data in repeated group
		 *
		 * Jaanus: removed condition canrepeat() from renderListData: weird result such as 05",null,
		 * "1940.07.["1940 (2011) when not repeating but still join and merged. Using isJoin() instead
		*/
		$data = $groupModel->isJoin() ? FabrikWorker::JSONtoData($data, true) : array($data);
		$data = (array) $data;
		$format = array();

		foreach ($data as $d)
		{
			$format[] = $this->listFormat($d);
		}

		$data = json_encode($format);

		return parent::renderListData($data, $thisRow, $opts);
	}

	/**
	 * Format a date based on list age/date format options
	 *
	 * @param   string  $d  Date
	 *
	 * @since   3.0.9
	 *
	 * @return string|number
	 */

	private function listFormat($d)
	{
		if (!FabrikWorker::isDate($d))
		{
			return '';
		}

		$params = $this->getParams();

		$monthLabels = array(FText::_('January'), FText::_('February'), FText::_('March'), FText::_('April'), FText::_('May'), FText::_('June'),
				FText::_('July'), FText::_('August'), FText::_('September'), FText::_('October'), FText::_('November'), FText::_('December'));

		$monthNumbers = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12');
		$daySys = array('01', '02', '03', '04', '05', '06', '07', '08', '09');
		$daySimple = array('1', '2', '3', '4', '5', '6', '7', '8', '9');
		$jubileum = array('0', '25', '75');

		$ft = $params->get('list_date_format', 'd.m.Y');

		$fta = $params->get('list_age_format', 'no');

		/**
		 * $$$ rob default to a format date
		 * $date = JFactory::getDate($d);
		 * $dateDisplay = $date->toFormat($ft);
		 * Jaanus: sorry, but in this manner the element doesn't work with dates earlier than 1901
		*/

		list($year, $month, $day) = explode('-', $d);
		$dayDisplay = str_replace($daySys, $daySimple, $day);
		$monthDisplay = str_replace($monthNumbers, $monthLabels, $month);
		$nextYear = date('Y') + 1;
		$lastYear = date('Y') - 1;
		$thisYear = date('Y');
		$year = JString::ltrim($year, '0');
		$dmy = $day . '.' . $month . '.' . $year;
		$mdy = $month . '.' . $day . '.' . $year;
		$dmy_slash = $day . '/' . $month . '/' . $year;
		$dMonthYear = $dayDisplay . '. ' . $monthDisplay . ' ' . $year;
		$dMonthYear2 = $dayDisplay . ' ' . $monthDisplay . ' ' . $year;
		$monthDYear = $monthDisplay . ' ' . $dayDisplay . ', ' . $year;
		$dMonth = $dayDisplay . '  ' . $monthDisplay;

		if ($ft == "d.m.Y")
		{
			$dateDisplay = $dmy;
		}
		else
		{
			switch ($ft)
			{
				case 'm.d.Y':
					$dateDisplay = $mdy;
					break;
				case 'd/m/Y':
					$dateDisplay = $dmy_slash;
					break;
				case 'D. month YYYY':
					$dateDisplay = $dMonthYear;
					break;
				case 'D month YYYY':
					$dateDisplay = $dMonthYear2;
					break;
				case 'Month d, YYYY':
					$dateDisplay = $monthDYear;
					break;
				default:
					$dateDisplay = $dMonth;
					break;
			}
		}

		if ($fta == 'no')
		{
			return $dateDisplay;
		}
		else
		{
			if (date('m-d') == $month . '-' . $day)
			{
				if ($fta == '{age}')
				{
					return '<span style="color:#DD0000"><b>' . ($thisYear - $year) . '</b></span>';
				}
				else
				{
					if ($fta == '{age} date')
					{
						return '<span style="color:#DD0000"><b>' . $dateDisplay . ' (' . ($thisYear - $year) . ')</b></span>';
					}

					if ($fta == '{age} this')
					{
						return '<span style="color:#DD0000"><b>' . ($thisYear - $year) . ' (' . $dateDisplay . ')</b></span>';
					}

					if ($fta == '{age} next')
					{
						return '<span style="color:#DD0000"><b>' . ($nextYear - $year) . ' (' . $dateDisplay . ')</b></span>';
					}
				}
			}
			else
			{
				if ($fta == '{age} date')
				{
					if (date('m-d') > $month . '-' . $day)
					{
						return $dateDisplay . ' (' . ($thisYear - $year) . ')';
					}
					else
					{
						return $dateDisplay . ' (' . ($lastYear - $year) . ')';
					}
				}
				else
				{
					if ($fta == '{age}')
					{
						if (date('m-d') > $month . '-' . $day)
						{
							return $thisYear - $year;
						}
						else
						{
							return $lastYear - $year;
						}
					}
					else
					{
						if ($fta == '{age} this')
						{
							if (in_array(substr(($thisYear - $year), -1), $jubileum) || in_array(substr(($thisYear - $year), -2), $jubileum))
							{
								return '<b>' . ($thisYear - $year) . ' (' . $dateDisplay . ')</b>';
							}
							else
							{
								return ($thisYear - $year) . ' (' . $dateDisplay . ')';
							}
						}

						if ($fta == '{age} next')
						{
							if (in_array(substr(($nextYear - $year), -1), $jubileum) || in_array(substr(($nextYear - $year), -2), $jubileum))
							{
								return '<b>' . ($nextYear - $year) . ' (' . $dateDisplay . ')</b>';
							}
							else
							{
								return ($nextYear - $year) . ' (' . $dateDisplay . ')';
							}
						}
					}
				}
			}
		}

		return '';
	}

	/**
	 * Used by radio and dropdown elements to get a dropdown list of their unique
	 * unique values OR all options - based on filter_build_method
	 *
	 * @param   bool    $normal     Do we render as a normal filter or as an advanced search filter
	 * @param   string  $tableName  Table name to use - defaults to element's current table
	 * @param   string  $label      Field to use, defaults to element name
	 * @param   string  $id         Field to use, defaults to element name
	 * @param   bool    $incjoin    Include join
	 *
	 * @return  array  text/value objects
	 */

	public function filterValueList($normal, $tableName = '', $label = '', $id = '', $incjoin = true)
	{
		$rows = parent::filterValueList($normal, $tableName, $label, $id, $incjoin);
		$return = array();

		foreach ($rows as &$row)
		{
			$txt = $this->listFormat($row->text);

			if ($txt !== '')
			{
				$row->text = strip_tags($txt);
			}
			// Ensure unique values
			if (!array_key_exists($row->text, $return))
			{
				$return[$row->text] = $row;
			}
		}

		$return = array_values($return);

		return $return;
	}
	/**
	 * Get the list filter for the element
	 *
	 * @param   int   $counter  Filter order
	 * @param   bool  $normal   Do we render as a normal filter or as an advanced search filter
	 * if normal include the hidden fields as well (default true, use false for advanced filter rendering)
	 *
	 * @return  string	Filter html
	 */
	public function getFilter($counter = 0, $normal = true, $container = '')
	{
		$params = $this->getParams();
		$element = $this->getElement();

		if ($element->filter_type === 'dropdown' && $params->get('list_filter_layout', 'individual') === 'day_mont_year')
		{
			$layout = $this->getLayout('filter-select-day-month-year');
			$elName = $this->getFullName(true, false);
			$layoutData = new stdClass;
			$layoutData->name = $this->filterName($counter, $normal);
			$layoutData->days = $this->_dayOptions();
			$layoutData->months = $this->_monthOptions();
			$layoutData->years =  $this->_yearOptions();
			$layoutData->default = (array) $this->getDefaultFilterVal($normal, $counter);
			$layoutData->elementName = $this->getFullName(true, false);
			$this->filterDisplayValues = array($layoutData->default);

			$return = array();
			$return[] = $layout->render($layoutData);
			$return[] = $normal ? $this->getFilterHiddenFields($counter, $elName, false, $normal) : $this->getAdvancedFilterHiddenFields();

			return implode("\n", $return);
		}
		else
		{
			return parent::getFilter($counter, $normal);
		}
	}

	/**
	 * This builds an array containing the filters value and condition
	 * when using a ranged search
	 *
	 * @param   array   $value      Initial values
	 * @param   string  $condition  Filter condition e.g. BETWEEN
	 *
	 * @return  array  (value condition)
	 */

	protected function getRangedFilterValue($value, $condition = '')
	{
		$db = FabrikWorker::getDbo();
		$element = $this->getElement();

		if ($element->filter_type === 'range' || strtoupper($condition) === 'BETWEEN')
		{
			if (strtotime($value[0]) > strtotime($value[1]))
			{
				$tmp_value = $value[0];
				$value[0] = $value[1];
				$value[1] = $tmp_value;
			}

			if (is_numeric($value[0]) && is_numeric($value[1]))
			{
				$value = $value[0] . ' AND ' . $value[1];
			}
			else
			{
				$today = $this->date;
				$thisMonth = $today->format('m');
				$thisDay = $today->format('d');

				// Set start date today's month/day of start year
				$startYear = JFactory::getDate($value[0])->format('Y');
				$startDate = JFactory::getDate();
				$startDate->setDate($startYear, $thisMonth, $thisDay)->setTime(0, 0, 0);
				$value[0] = $startDate->toSql();

				// Set end date to today's month/day of year after end year (means search on age between 35 & 35 returns results)
				$endYear = JFactory::getDate($value[1])->format('Y');
				$endDate = JFactory::getDate();
				$endDate->setDate($endYear + 1, $thisMonth, $thisDay)->setTime(23, 59, 59);
				$value[1] = $endDate->toSql();

				$value = $db->quote($value[0]) . ' AND ' . $db->quote($value[1]);
			}

			$condition = 'BETWEEN';
		}
		else
		{
			if (is_array($value) && !empty($value))
			{
				foreach ($value as &$v)
				{
					$v = $db->quote($v);
				}

				$value = ' (' . implode(',', $value) . ')';
			}

			$condition = 'IN';
		}

		return array($value, $condition);
	}

	/**
	 * Build the filter query for the given element.
	 * Can be overwritten in plugin - e.g. see checkbox element which checks for partial matches
	 *
	 * @param   string  $key            element name in format `tablename`.`elementname`
	 * @param   string  $condition      =/like etc.
	 * @param   string  $value          search string - already quoted if specified in filter array options
	 * @param   string  $originalValue  original filter value without quotes or %'s applied
	 * @param   string  $type           filter type advanced/normal/prefilter/search/querystring/searchall
	 * @param   string  $evalFilter     evaled
	 *                                  
	 * @return  string	sql query part e,g, "key = value"
	 */

	public function getFilterQuery($key, $condition, $value, $originalValue, $type = 'normal', $evalFilter = '0')
	{
		$params = $this->getParams();
		$element = $this->getElement();

		if ($type === 'prefilter' || $type === 'menuPrefilter')
		{
			switch ($condition)
			{
				case 'earlierthisyear':
					throw new UnexpectedValueException('The birthday element can not deal with "Earlier This Year" prefilters');
					break;
				case 'laterthisyear':
					throw new UnexpectedValueException('The birthday element can not deal with "Later This Year" prefilters');
					break;
				case 'today':
					$search = array(date('Y'), date('n'), date('j'));
					return $this->_dayMonthYearFilterQuery($key, $search);
					break;
				case 'yesterday':
					$today = new DateTime();
					$today->sub(new DateInterval('P1D'));
					$search = array('', $today->format('n'), $today->format('j'));
					return $this->_dayMonthYearFilterQuery($key, $search);
					break;
				case 'tomorrow':
					$today = new DateTime();
					$today->add(new DateInterval('P1D'));
					$search = array('', $today->format('n'), $today->format('j'));
					return $this->_dayMonthYearFilterQuery($key, $search);
				case 'thismonth':
					$search = array('', date('n'), '');
					return $this->_dayMonthYearFilterQuery($key, $search);
					break;
				case 'lastmonth':
					$today = new DateTime();
					$today->sub(new DateInterval('P1M'));
					$search = array('', $today->format('n'), '');
					return $this->_dayMonthYearFilterQuery($key, $search);
				case 'nextmonth':
					$today = new DateTime();
					$today->add(new DateInterval('P1M'));
					$search = array('', $today->format('n'), '');
					return $this->_dayMonthYearFilterQuery($key, $search);
				case 'birthday':
					$search = array('', date('n'), date('j'));
					return $this->_dayMonthYearFilterQuery($key, $search);
					break;
			}
		}

		if ($element->filter_type === 'dropdown' && $params->get('list_filter_layout', 'individual') === 'day_mont_year')
		{
			return $this->_dayMonthYearFilterQuery($key, $originalValue);
		}
		else
		{
			$ft = $this->getParams()->get('list_date_format', 'd.m.Y');

			if ($ft === 'd m')
			{
				$value = explode('-', $originalValue);
				array_shift($value);
				$value = implode('-', $value);
				$query = 'DATE_FORMAT(' . $key . ', \'%m-%d\') = ' . $this->_db->q($value);

				return $query;
			}

			$query = parent::getFilterQuery($key, $condition, $value, $originalValue, $type, $evalFilter);

			return $query;
		}
	}

	/**
	 * Get the filter query for the day/month/year select filter
	 *
	 * @param   string  $key            Key name
	 * @param   array   $originalValue  Posted filter data
	 *
	 * @return string
	 */
	private function _dayMonthYearFilterQuery($key, $originalValue)
	{
		$search = array();

		foreach ($originalValue as $i => $val)
		{
			if ($i <> 0 && strlen($val) === 1)
			{
				$val = '0' . $val;
			}

			$search[] = $val === '' ? '%' : $val;
		}

		$search = implode('-', $search);

		return $key . ' LIKE ' . $this->_db->q($search);
	}

}
