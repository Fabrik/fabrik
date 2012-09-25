<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.date
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * Plugin element to render date picker
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.date
 * @since       3.0
 */

class plgFabrik_ElementDate extends plgFabrik_Element
{

	/**
	 * If the element 'Include in search all' option is set to 'default' then this states if the
	 * element should be ignored from search all.
	 * @var bool  True, ignore in advanced search all.
	 */
	protected $ignoreSearchAllDefault = true;

	/**
	 * Toggle to determine if storedatabaseformat resets the date to GMT
	 * @var bool
	 */
	protected $resetToGMT = true;

	protected $rangeFilterSet = false;

	/**
	 * Dates are stored in database as GMT times
	 * i.e. with no offsets
	 * This is to allow us in the future of render dates based
	 * on user tmezone offsets
	 * Dates are displayed in forms and tables with the global timezone
	 * offset applied
	 *
	 * @return  array
	 */

	private function getNullDates()
	{
		$db = FabrikWorker::getDbo();
		return array('0000-00-000000-00-00', '0000-00-00 00:00:00', '0000-00-00', '', $db->getNullDate());
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
		if ($data == '')
		{
			return '';
		}
		// @TODO: deal with time options (currently can be defined in date_table_format param).
		$timeZone = new DateTimeZone(JFactory::getConfig()->get('offset'));
		$aNullDates = $this->getNullDates();
		$params = $this->getParams();
		$store_as_local = (int) $params->get('date_store_as_local', 0);
		$groupModel = $this->getGroup();
		$data = FabrikWorker::JSONtoData($data, true);
		$f = $params->get('date_table_format', '%Y-%m-%d');
		if ($f == 'Y-m-d')
		{
			$f = '%Y-%m-%d';
		}
		$format = array();
		foreach ($data as $d)
		{
			if (!in_array($d, $aNullDates))
			{
				$date = JFactory::getDate($d);
				/* $$$ rob - dates always stored with time (and hence timezone offset) so, unless stored_as_local
				 * we must set the timezone
				 */
				if (!$store_as_local)
				{
					$date->setTimeZone($timeZone);
				}
				if ($f == '{age}')
				{
					$format[] = date('Y') - $date->format('Y', true);
				}
				else
				{
					$format[] = $date->toFormat($f, true);
				}
			}
			else
			{
				$format[] = '';
			}
		}
		$data = json_encode($format);
		return parent::renderListData($data, $thisRow);
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
		// @TODO: deal with time options (currently can be defined in date_table_format param).
		$timeZone = new DateTimeZone(JFactory::getConfig()->get('offset'));
		$db = FabrikWorker::getDbo();
		$aNullDates = $this->getNullDates();
		$params = $this->getParams();
		$element = $this->getElement();
		$store_as_local = (int) $params->get('date_store_as_local', 0);

		$groupModel = $this->getGroup();
		$data = FabrikWorker::JSONtoData($data, true);
		$f = $params->get('date_table_format', '%Y-%m-%d');
		/* $$$ hugh - see http://fabrikar.com/forums/showthread.php?p=87507
		 * Really don't think we need to worry about 'incraw' here. The raw, GMT/MySQL data will get
		 * included in the _raw version of the element if incraw is selected. Here we just want to output
		 * the regular non-raw, formatted, TZ'ed version.
		 * $incRaw = JRequest::getVar('incraw', true);
		 */
		$incRaw = false;

		if ($f == 'Y-m-d')
		{
			$f = '%Y-%m-%d';
		}
		$format = array();
		foreach ($data as $d)
		{
			if (!in_array($d, $aNullDates))
			{
				if ($incRaw)
				{
					$format[] = $d;
				}
				else
				{
					$date = JFactory::getDate($d);
					/* $$$ hugh - added the showtime test so we don't get the day offset issue,
					 * as per regular table render.
					 */
					if ($params->get('date_showtime') && !$store_as_local)
					{
						$date->setTimeZone($timeZone);
					}
					if ($f == '{age}')
					{
						$format[] = date('Y') - $date->format('Y', true);
					}
					else
					{
						$format[] = $date->toFormat($f, true);
					}
				}
			}
			else
			{
				$format[] = '';
			}
		}
		if (count($format) > 1)
		{
			return json_encode($format);
		}
		else
		{
			return implode('', $format);
		}
	}

	/**
	 * Used in things like date when its id is suffixed with _cal
	 * called from getLabel();
	 *
	 * @param   string  &$id  initial id
	 *
	 * @return  void
	 */

	protected function modHTMLId(&$id)
	{
		$id = $id . '_cal';
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
		$timeZone = new DateTimeZone(JFactory::getConfig()->get('offset'));
		$this->offsetDate = '';
		$aNullDates = $this->getNullDates();
		FabrikHelperHTML::loadcalendar();
		$name = $this->getHTMLName($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		$params = $this->getParams();
		$element = $this->getElement();
		$format = $params->get('date_form_format', $params->get('date_table_format', '%Y-%m-%d'));
		$timeformat = $params->get('date_time_format');

		// Value is in mySQL format
		$value = $this->getValue($data, $repeatCounter);
		$store_as_local = (bool) $params->get('date_store_as_local', 0);

		if ($params->get('date_showtime', 0) && !$element->hidden)
		{
			// Can't have names as simply [] as json only picks up the last one
			$timeElName = $name . '[time]';
			$name .= '[date]';
		}

		$readonly = $params->get('date_allow_typing_in_field', true) == false ? ' readonly="readonly" ' : '';
		$calopts = array('class' => 'fabrikinput inputbox', 'size' => $element->width, 'maxlength' => '19');
		if ($params->get('date_allow_typing_in_field', true) == false)
		{
			$calopts['readonly'] = 'readonly';
		}

		$str[] = '<div class="fabrikSubElementContainer" id="' . $id . '">';
		if (!in_array($value, $aNullDates) && FabrikWorker::isDate($value))
		{
			$oDate = JFactory::getDate($value);

			// If we are coming back from a validation then we don't want to re-offset the date
			if (JRequest::getVar('Submit', '') == '' || $params->get('date_defaulttotoday', 0))
			{
				// $$$ rob - date is always stored with time now, so always apply tz unless store_as_local set
				// or if we are defaulting to today
				$showLocale = ($params->get('date_defaulttotoday', 0) && JRequest::getInt('rowid') == 0 || $params->get('date_alwaystoday', false));
				if (!$store_as_local || $showLocale)
				{
					$oDate->setTimeZone($timeZone);
				}
			}
			// Get the formatted date
			$local = true;
			$date = $oDate->toFormat($format, true);
			$this->offsetDate = $oDate->toSql(true);
			if (!$this->_editable)
			{
				$time = ($params->get('date_showtime', 0)) ? ' ' . $oDate->toFormat($timeformat, true) : '';
				return $date . $time;
			}

			// Get the formatted time
			if ($params->get('date_showtime', 0))
			{
				$time = $oDate->toFormat($timeformat, true);
			}
		}
		else
		{
			if (!$this->_editable)
			{
				return '';
			}
			$date = '';
			$time = '';
		}
		$this->formattedDate = $date;
		/* $$$ hugh - OK, I am, as usual, confused.  We can't hand calendar() a date formatted in the
		 * form/table format.
		 * $$$rob - its because the calendar js code takes the formatted value in the field and the $format and builds its date objects from
		 * the two.
		 */
		$str[] = $this->calendar($date, $name, $id . '_cal', $format, $calopts, $repeatCounter);
		if ($params->get('date_showtime', 0) && !$element->hidden)
		{
			$timelength = JString::strlen($timeformat);
			FabrikHelperHTML::addPath(COM_FABRIK_BASE . 'plugins/fabrik_element/date/images/', 'image', 'form', false);
			$str[] = '<input class="inputbox fabrikinput timeField" ' . $readonly . ' size="' . $timelength . '" value="' . $time . '" name="'
				. $timeElName . '" />';
			$opts = array('alt' => JText::_('PLG_ELEMENT_DATE_TIME'), 'class' => 'timeButton');
			$str[] = FabrikHelperHTML::image('time.png', 'form', @$this->tmpl, $opts);
		}
		$str[] = '</div>';
		return implode("\n", $str);
	}

	/**
	 * Individual store database format
	 *
	 * @param   string  $val  value
	 *
	 * @return  string	mySQL formatted date
	 */

	private function _indStoreDBFormat($val)
	{
		// $$$ hugh - sometimes still getting $val as an array with date and time,
		// like on AJAX submissions?  Or maybe from getEmailData()?  Or both?
		if (is_array($val))
		{
			/* $$$ rob do url decode on time as if its passed from ajax save the : is in format %3C or something
			 * $val = $val['date'].' '.$this->_fixTime(urldecode($val['time']));
			 * $$$ rob 'date' should contain the time
			 */
			$val = JArrayHelper::getValue($val, 'date', '');
		}
		else
		{
			$val = urldecode($val);
		}

		if (in_array(trim($val), $this->getNullDates()))
		{
			return '';
		}
		jimport('joomla.utilities.date');
		$params = $this->getParams();
		$store_as_local = (bool) $params->get('date_store_as_local', false);

		$listModel = $this->getListModel();

		// $$$ hugh - offset_tz of 1 means 'in MySQL format, GMT'
		// $$$ hugh - offset_tz of 2 means 'in MySQL format, Local TZ'
		if ($listModel->importingCSV && $params->get('date_csv_offset_tz', '0') == '1')
		{
			return $val;
		}
		elseif ($listModel->importingCSV && $params->get('date_csv_offset_tz', '0') == '2')
		{
			return $this->toMySQLGMT(JFactory::getDate($val));
		}

		// $$$ rob - as the date js code formats to the db format - just return the value.
		$timeZone = new DateTimeZone(JFactory::getConfig()->get('offset'));
		$val = JFactory::getDate($val, $timeZone)->toSql($store_as_local);
		return $val;
	}

	/**
	 * reset the date to GMT - inversing the offset
	 *
	 * @param   object  $date  date to convert
	 *
	 * @return  string	mysql formatted date
	 */

	protected function toMySQLGMT($date)
	{
		if ($this->resetToGMT)
		{
			// $$$ rob 3.0 offset is no longer an integer but a timezone string
			$timeZone = new DateTimeZone(JFactory::getConfig()->get('offset'));
			$hours = $timeZone->getOffset($date) / (60 * 60);
			$invert = false;
			if ($hours < 0)
			{
				$invert = true;

				// Intervals can only be positive - set invert propery
				$hours = $hours * -1;
			}
			// 5.3 only
			if (class_exists('DateInterval'))
			{
				$dateInterval = new DateInterval('PT' . $hours . 'H');
				$dateInterval->invert = $invert;
				$date->sub($dateInterval);
			}
			else
			{
				$date->modify('+' . $hours . ' hour');
			}
			return $date->toSql(true);
		}
		return $date->toSql();
	}

	/**
	 * Manupulates posted form data for insertion into database
	 *
	 * @param   mixed  $val   this elements posted form data
	 * @param   array  $data  posted form data
	 *
	 * @return  mixed
	 */

	public function storeDatabaseFormat($val, $data)
	{
		if (!is_array($val))
		{
			/* $$$ hugh - we really need to work out why some AJAX data is not getting urldecoded.
			 * but for now ... a bandaid.
			 */
			$val = urldecode($val);
		}
		// @TODO: deal with failed validations
		$groupModel = $this->getGroup();
		if ($groupModel->isJoin() && is_array($val))
		{
			$val = JArrayHelper::getValue($val, 'date', '');
		}
		else
		{
			if ($groupModel->canRepeat())
			{
				if (is_array($val))
				{
					$res = array();
					foreach ($val as $v)
					{
						$res[] = $this->_indStoreDBFormat($v);

					}
					return json_encode($res);
				}
			}
		}
		return $this->_indStoreDBFormat($val);
	}

	/**
	 * Used to format the data when shown in the form's email
	 *
	 * @param   mixed  $value          element's data
	 * @param   array  $data           form records data
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return  string	formatted value
	 */

	public function getEmailValue($value, $data = array(), $repeatCounter = 0)
	{
		if ((is_array($value) && empty($value)) || (!is_array($value) && trim($value) == ''))
		{
			return '';
		}
		$groupModel = $this->getGroup();
		if ($groupModel->isJoin() && $groupModel->canRepeat())
		{
			$value = $value[$repeatCounter];
		}
		if (is_array($value))
		{
			$date = JArrayHelper::getValue($value, 'date');
			$d = JFactory::getDate($date);
			$time = JArrayHelper::getValue($value, 'time', '');
			if ($time !== '')
			{
				$bits = explode(':', $time);
				$h = JArrayHelper::getValue($bits, 0, 0);
				$m = JArrayHelper::getValue($bits, 1, 0);
				$s = JArrayHelper::getValue($bits, 2, 0);
				$d->setTime($h, $m, $s);
			}
			$value = $d->toSql();
		}
		// $$$ hugh - need to convert to database format so we GMT-ified date
		return $this->renderListData($value, new stdClass);
		/* $$$ rob - no need to covert to db format now as its posted as db format already.
		 *return $this->renderListData($this->storeDatabaseFormat($value, $data), new stdClass);
		 */
	}

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
		$gmt_date = $this->getValue($data, $repeatCounter, $opts);
		/* OK, now we've got the GMT date, convert it
		 * ripped the following off from renderListData ... SURELY we must have a func
		 * somewhere that does this?
		 */
		$params = $this->getParams();
		$store_as_local = (int) $params->get('date_store_as_local', 0);
		$timeZone = new DateTimeZone(JFactory::getConfig()->get('offset'));
		$aNullDates = $this->getNullDates();
		$f = $params->get('date_table_format', '%Y-%m-%d');
		if ($f == 'Y-m-d')
		{
			$f = '%Y-%m-%d';
		}
		$tz_date = '';
		if (!in_array($gmt_date, $aNullDates))
		{
			$date = JFactory::getDate($gmt_date);
			if (!$store_as_local)
			{
				$date->setTimeZone($timeZone);
			}
			if ($f == '{age}')
			{
				$tz_date = date('Y') - $date->format('Y', true);
			}
			else
			{
				$tz_date = $date->toFormat($f, true);
			}
		}
		return $tz_date;
	}

	/**
	 * Converts a raw value into its label equivalent
	 *
	 * @param   string  &$v  raw value
	 *
	 * @return  void
	 */

	protected function toLabel(&$v)
	{
		$params = $this->getParams();
		$store_as_local = (int) $params->get('date_store_as_local', 0);
		$f = $params->get('date_table_format', '%Y-%m-%d');
		$timeZone = new DateTimeZone(JFactory::getConfig()->get('offset'));
		$aNullDates = $this->getNullDates();
		$format = array();
		if (!in_array($v, $aNullDates))
		{
			$date = JFactory::getDate($v);
			/**
			 * $$$ rob - if not time selector then the date gets stored as 2009-11-13 00:00:00
			 * if we have a -1 timezone then date gets set to 2009-11-12 23:00:00
			 * then shown as 2009-11-12 which is wrong
			 */
			if ($params->get('date_showtime'))
			{
				$date->setTimeZone($timeZone);
			}
			if ($f == '{age}')
			{
				$v = date('Y') - $date->format('Y', true);
			}
			else
			{
				$v = $date->toFormat($f, true);
			}
		}
		else
		{
			$v = '';
		}
	}

	/**
	 * ensure the time is in a full length format
	 *
	 * @param   string  $time  time
	 *
	 * @return  formatted	time
	 */

	protected function _fixTime($time)
	{
		// If its 5:00 rather than 05:00
		if (!preg_match("/^[0-9]{2}/", $time))
		{
			$time = "0" . $time;
		}
		// If no seconds
		if (preg_match("/[0-9]{2}:[0-9]{2}/", $time) && JString::strlen($time) <= 5)
		{
			$time .= ":00";
		}
		// If it doesnt match reset it to 0
		if (!preg_match("/[0-9]{2}:[0-9]{2}:[0-9]{2}/", $time))
		{
			$time = "00:00:00";
		}
		return $time;
	}

	/**
	 * Displays a calendar control field
	 *
	 * hacked from behaviour as you need to check if the element exists
	 * it might not as you could be using a custom template
	 *
	 * @param   string  $value          The date value (must be in the same format as supplied by $format)
	 * @param   string  $name           The name of the text field
	 * @param   string  $id             The id of the text field
	 * @param   string  $format         The date format
	 * @param   array   $attribs        Additional html attributes
	 * @param   int     $repeatCounter  repeat group counter
	 *
	 * @deprecated - don't think its used
	 *
	 * @return  string
	 */

	public function calendar($value, $name, $id, $format = '%Y-%m-%d', $attribs = null, $repeatCounter = 0)
	{
		FabrikHelperHTML::loadcalendar();
		if (is_array($attribs))
		{
			$attribs = JArrayHelper::toString($attribs);
		}
		$paths = FabrikHelperHTML::addPath(COM_FABRIK_BASE . 'media/system/images/', 'image', 'form', false);
		$opts = array('alt' => 'calendar', 'class' => 'calendarbutton', 'id' => $id . '_cal_img');
		$img = FabrikHelperHTML::image('calendar.png', 'form', @$this->tmpl, $opts);
		$value = htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
		return '<input type="text" name="' . $name . '" id="' . $id . '" value="' . $value . '" ' . $attribs . ' />' . $img;
	}

	/**
	 * get the options used for the date elements calendar
	 *
	 * @param   int  $id  repeat counter
	 *
	 * @return object ready for js encoding
	 */

	protected function _CalendarJSOpts($id)
	{
		$params = $this->getParams();
		$opts = new stdClass;
		$opts->inputField = $id;
		$opts->ifFormat = $params->get('date_form_format');
		$opts->button = $id . "_cal_img";
		$opts->align = "Tl";
		$opts->singleClick = true;
		$opts->firstDay = intval($params->get('date_firstday'));
		$validations = $this->getValidations();
		$opts->ifFormat = $params->get('date_form_format', $params->get('date_table_format', '%Y-%m-%d'));
		$opts->hasValidations = empty($validations) ? false : true;
		$opts->dateAllowFunc = $params->get('date_allow_func');

		// Test
		$opts->range = array(1066, 2999);
		return $opts;
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
		$params = $this->getParams();
		$element = $this->getElement();
		$id = $this->getHTMLId($repeatCounter);
		$opts = $this->getElementJSOptions($repeatCounter);
		$opts->hidden = (bool) $this->getElement()->hidden;
		$opts->defaultVal = $this->offsetDate;
		$opts->showtime = (!$element->hidden && $params->get('date_showtime', 0)) ? true : false;
		$opts->timelabel = JText::_('time');
		$opts->typing = $params->get('date_allow_typing_in_field', true);
		$opts->timedisplay = $params->get('date_timedisplay', 1);
		$validations = $this->getValidations();
		$opts->validations = empty($validations) ? false : true;
		$opts->dateTimeFormat = $params->get('date_time_format', '');

		// For reuse if element is duplicated in repeat group
		$opts->calendarSetup = $this->_CalendarJSOpts($id);
		$opts->advanced = $params->get('date_advanced', '0') == '1';
		$opts = json_encode($opts);
		return "new FbDateTime('$id', $opts)";
	}

	/**
	 * Get database field description
	 *
	 * @return  string  db field type
	 */

	public function getFieldDescription()
	{
		$p = $this->getParams();
		if ($this->encryptMe())
		{
			return 'BLOB';
		}
		$groupModel = $this->getGroup();
		if (is_object($groupModel) && !$groupModel->isJoin() && $groupModel->canRepeat())
		{
			return "VARCHAR(255)";
		}
		else
		{
			return "DATETIME";
		}
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
		$params = $this->getParams();
		$return = array();
		$elName = $this->getHTMLName($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		$return[] = array('id' => $id, 'triggerEvent' => 'blur');
		return $return;
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
		if (!isset($this->_default))
		{
			$params = $this->getParams();
			$element = $this->getElement();
			$timeZone = new DateTimeZone(JFactory::getConfig()->get('offset'));
			$store_as_local = (int) $params->get('date_store_as_local', 0);
			if ($params->get('date_defaulttotoday', 0))
			{
				if ($store_as_local)
				{
					$localDate = date('Y-m-d H:i:s');
					$oTmpDate = JFactory::getDate(strtotime($localDate));
				}
				else
				{
					$oTmpDate = JFactory::getDate();
				}
				$default = $oTmpDate->toSql();
			}
			else
			{
				// Deafult date should always be entered as gmt date e.g. eval'd default of:
				$w = new FabrikWorker();
				$default = $w->parseMessageForPlaceHolder($element->default, $data);

				if ($element->eval == "1")
				{
					$default = @eval(stripslashes($default));
					FabrikWorker::logEval($default, 'Caught exception on eval in ' . $element->name . '::getDefaultValue() : %s');
				}
				if (trim($default) != '')
				{
					$oTmpDate = JFactory::getDate($default);
					$default = $oTmpDate->toSql();
				}
			}
			$this->_default = $default;
		}
		return $this->_default;
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
		// @TODO: allow {now} and {today} to be replaced with current datetime
		if (!isset($this->defaults) || is_null($this->defaults))
		{
			$this->defaults = array();
		}
		if (!array_key_exists($repeatCounter, $this->defaults))
		{
			$groupModel = $this->getGroup();
			$group = $groupModel->getGroup();
			$joinid = $group->join_id;
			$element = $this->getElement();
			$params = $this->getParams();
			$store_as_local = (int) $params->get('date_store_as_local', 0);
			if ($params->get('date_alwaystoday', false))
			{
				// $value = JFactory::getDate()->toSql(false);
				// $$$ rob fix for http://fabrik.unfuddle.com/projects/17220/tickets/by_number/700?cycle=true
				if ($store_as_local)
				{
					$localDate = date('Y-m-d H:i:s');
					$date = JFactory::getDate(strtotime($localDate));
				}
				else
				{
					$date = JFactory::getDate();
				}
				$value = $date->toSql();
			}
			else
			{
				// $$$rob - if no search form data submitted for the search element then the default
				// selecton was being applied instead
				if (array_key_exists('use_default', $opts) && $opts['use_default'] == false)
				{
					$value = '';
				}
				else
				{
					$value = $this->getDefaultValue($data);
				}
				// $$$ hugh - as we now run removeTableNameFromSaveData(), I think we just need the short name?
				$name = $this->getFullName(false, true, false);
				if ($groupModel->isJoin())
				{
					if ($groupModel->canRepeat())
					{
						if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid])
							&& array_key_exists($name, $data['join'][$joinid]) && array_key_exists($repeatCounter, $data['join'][$joinid][$name]))
						{
							$value = $data['join'][$joinid][$name][$repeatCounter];
						}
					}
					else
					{
						if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid])
							&& array_key_exists($name, $data['join'][$joinid]))
						{
							$value = $data['join'][$joinid][$name];
						}
					}
				}
				else
				{
					if ($groupModel->canRepeat())
					{
						// Repeat group NO join
						if (array_key_exists($name, $data))
						{
							if (is_array($data[$name]))
							{
								// Occurs on form submission for fields at least
								$a = $data[$name];

							}
							else
							{
								// Occurs when getting from the db
								$a = FabrikWorker::JSONtoData($data[$name], true);
							}
							if (array_key_exists($repeatCounter, $a))
							{
								$value = $a[$repeatCounter];
							}
						}

					}
					else
					{
						$value = JArrayHelper::getValue($data, $name, $value);
					}
				}

				if (is_array($value))
				{
					// 'date' should now contain the time, as we include in on js onsubmit() method
					$value = JArrayHelper::getValue($value, 'date', JArrayHelper::getValue($value, 0));
				}
				$formModel = $this->getForm();

				// Stops this getting called from form validation code as it messes up repeated/join group validations
				if (array_key_exists('runplugins', $opts) && $opts['runplugins'] == 1)
				{
					FabrikWorker::getPluginManager()->runPlugins('onGetElementDefault', $formModel, 'form', $this);
				}
				// For validations (empty time and date element gives ' ')
				if ($value == ' ')
				{
					$value = '';
				}
			}
			$this->defaults[$repeatCounter] = $value;
		}
		return $this->defaults[$repeatCounter];
	}

	/**
	 * Called on failed form validation.
	 * Ensures submitted form data is converted back into the format
	 * that the form would expect to get it in, if the data had been
	 * draw from the database record
	 *
	 * @param   string  $str  submitted form value
	 *
	 * @return  string	formated value
	 */

	public function toDbVal($str)
	{
		/**
		 * Only format if not empty otherwise search forms will filter
		 * for todays date even when no date entered
		 */
		$this->resetToGMT = false;
		if ($str != '')
		{
			$str = $this->storeDatabaseFormat($str, array());
		}
		$this->resetToGMT = true;
		return $str;
	}

	/**
	 * Does the format string contain time formatting options
	 *
	 * @param   string  $format  date format
	 *
	 * @since 2.1.1
	 *
	 * @return  bool
	 */

	protected function formatContainsTime($format)
	{
		$times = array('%H', '%I', '%l', '%M', '%p', '%P', '%r', '%R', '%S', '%T', '%X', '%z', '%Z');
		foreach ($times as $t)
		{
			if (strpos($format, $t))
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * Builds an array containing the filters value and condition
	 *
	 * @param   string  $value      initial value
	 * @param   string  $condition  intial $condition
	 * @param   string  $eval       how the value should be handled
	 *
	 * @return  array	(value condition)
	 */

	public function getFilterValue($value, $condition, $eval)
	{
		/* if its a search all value it may not be a date - so use parent method.
		 * see http://fabrikar.com/forums/showthread.php?t=25255
		 */
		if (!FabrikWorker::isDate($value))
		{
			if (($this->rangeFilterSet))
			{
				// Its alreay been set as a range expression - so split that into an array
				$condition = 'between';
				$value = explode(' AND ', $value);
				foreach ($value as &$v)
				{
					$v = str_replace(array("'", '"'), '', $v);
				}
			}
			return parent::getFilterValue($value, $condition, FABRIKFILTER_QUERY);
		}
		$params = $this->getParams();
		$store_as_local = (int) $params->get('date_store_as_local', 0);
		if (!$params->get('date_showtime', 0) || $store_as_local)
		{
			$this->resetToGMT = false;
		}

		$exactTime = $this->formatContainsTime($params->get('date_table_format'));

		// $$$ rob if filtering in querystring and ranged value set then force filter type to range
		$filterType = is_array($value) ? 'range' : $this->getElement()->filter_type;
		switch ($filterType)
		{
			case 'range':
			// Ranged dates should be sent in sql format
				break;
			case 'field':
			case 'dropdown':
			case 'auto-complete':
			default:
			// Odity when filtering from qs
				$value = str_replace("'", '', $value);

				/**
				 *  parse through JDate, to allow for special filters such as 'now' 'tomorrow' etc
				 *  for searches on simply the year - JDate will presume its a timestamp and mung the results
				 *  so we have to use this specific format string to get now and next
				 */
				if (is_numeric($value) && JString::strlen($value) == 4)
				{
					// Will only work on php 5.3.6
					$value = JFactory::getDate('first day of January ' . $value)->toSql();
					$next = JFactory::getDate('first day of January ' . ($value + 1));
				}
				elseif ($this->isMonth($value))
				{
					$value = JFactory::getDate('first day of ' . $this->untranslateMonth($value))->toSql();
					$next = JFactory::getDate('last day of ' . $this->untranslateMonth($value))->setTime(23, 59, 59);
				}
				elseif (trim(JString::strtolower($value)) === 'last week')
				{
					$value = JFactory::getDate('last week')->toSql();
					$next = JFactory::getDate();
				}
				elseif (trim(JString::strtolower($value)) === 'last month')
				{
					$value = JFactory::getDate('last month')->toSql();
					$next = JFactory::getDate();
				}
				elseif (trim(JString::strtolower($value)) === 'last year')
				{
					$value = JFactory::getDate('last year')->toSql();
					$next = JFactory::getDate();
				}
				elseif (trim(JString::strtolower($value)) === 'next week')
				{
					$value = JFactory::getDate()->toSql();
					$next = JFactory::getDate('next week');
				}
				elseif (trim(JString::strtolower($value)) === 'next month')
				{
					$value = JFactory::getDate()->toSql();
					$next = JFactory::getDate('next month');
				}
				elseif (trim(JString::strtolower($value)) === 'next year')
				{
					$value = JFactory::getDate()->toSql();
					$next = JFactory::getDate('next year');
				}
				else
				{
					$value = JFactory::getDate($value)->toSql();

					/**
					 *  $$$ hugh - strip time if not needed.  Specific case is element filter,
					 *  first time submitting filter from list, will have arbitrary "now" time.
					 *  Dunno if this will break anything else!
					 */
					if (!$exactTime)
					{
						$value = $this->setMySQLTimeToZero($value);
					}
					$next = JFactory::getDate(strtotime($this->addDays($value, 1)) - 1);
					/**
					 *  $$$ now we need to reset $value to GMT.
					 *  Probably need to take $store_as_local into account here?
					 */
					$this->resetToGMT = true;
					$value = $this->toMySQLGMT(JFactory::getDate($value));
					$this->resetToGMT = false;
				}

				// Only set to a range if condition is matching (so dont set to range for < or > conditions)
				if ($condition == 'contains' || $condition == '=' || $condition == 'REGEXP')
				{
					if (!$params->get('date_showtime', 0) || $exactTime == false)
					{
						// $$$ rob turn into a ranged filter to search the entire day  values should be in sql format
						$value = (array) $value;
						$condition = 'BETWEEN';
						$value[1] = $next->toSql();

						// Set a flat to stop getRangedFilterValue from adding an additional day to end value
						$this->rangeFilterSet = true;
					}
				}
				elseif ($condition == 'is null')
				{
					$value = "";
				}
				break;
		}
		$this->resetToGMT = true;
		$value = parent::getFilterValue($value, $condition, $eval);
		return $value;
	}

	/**
	 * Is a string a month?
	 *
	 * @param   string  $test  string to test
	 *
	 * @return  bool
	 */

	protected function isMonth($test)
	{
		$months = array(JText::_('JANUARY_SHORT'), JText::_('JANUARY'), JText::_('FEBRUARY_SHORT'), JText::_('FEBRUARY'), JText::_('MARCH_SHORT'),
			JText::_('MARCH'), JText::_('APRIL'), JText::_('APRIL_SHORT'), JText::_('MAY_SHORT'), JText::_('MAY'), JText::_('JUNE_SHORT'),
			JText::_('JUNE'), JText::_('JULY_SHORT'), JText::_('JULY'), JText::_('AUGUST_SHORT'), JText::_('AUGUST'), JText::_('SEPTEMBER_SHORT'),
			JText::_('SEPTEMBER'), JText::_('OCTOBER_SHORT'), JText::_('OCTOBER'), JText::_('NOVEMBER_SHORT'), JText::_('NOVEMBER'),
			JText::_('DECEMBER_SHORT'), JText::_('DECEMBER'));
		return in_array($test, $months);
	}

	/**
	 * Get English name for translated month
	 *
	 * @param   string  $test  month name
	 *
	 * @return string|boolean
	 */

	protected function untranslateMonth($test)
	{
		switch ($test)
		{
			case JText::_('JANUARY_SHORT'):
			case JText::_('JANUARY'):
				return 'January';
				break;
			case JText::_('FEBRUARY_SHORT'):
			case JText::_('FEBRUARY'):
				return 'February';
				break;
			case JText::_('MARCH_SHORT'):
			case JText::_('MARCH'):
				return 'March';
				break;
			case JText::_('APRIL_SHORT'):
			case JText::_('APRIL'):
				return 'April';
				break;
			case JText::_('MAY_SHORT'):
			case JText::_('MAY'):
				return 'May';
				break;
			case JText::_('JUNE_SHORT'):
			case JText::_('JUNE'):
				return 'June';
				break;
			case JText::_('JULY_SHORT'):
			case JText::_('JULY'):
				return 'July';
				break;
			case JText::_('AUGUST_SHORT'):
			case JText::_('AUGUST'):
				return 'August';
				break;
			case JText::_('SEPTEMBER_SHORT'):
			case JText::_('SEPTEMBER'):
				return 'September';
				break;
			case JText::_('OCTOBER_SHORT'):
			case JText::_('OCTOBER'):
				return 'October';
				break;
			case JText::_('NOVEMBER_SHORT'):
			case JText::_('NOVEMBER'):
				return 'November';
				break;
			case JText::_('DECEMBER_SHORT'):
			case JText::_('DECEMBER'):
				return 'December';
				break;
		}
		return false;
	}

	/**
	 * Get the table filter for the element
	 * Note: uses FabDate as if date element first to be found in advanced search, and advanced search run on another element
	 * the list model in getAdvancedSearchElementList() builds the first filter (this element) with the data from the first search
	 * which was throwing '"500 - DateTime::__construct() ' errors
	 *
	 * see: http://fabrikar.com/forums/showthread.php?t=28231
	 *
	 * @param   int   $counter  filter order
	 * @param   bool  $normal   do we render as a normal filter or as an advanced search filter
	 * if normal include the hidden fields as well (default true, use false for advanced filter rendering)
	 *
	 * @return  string	filter html
	 */

	public function getFilter($counter = 0, $normal = true)
	{
		$params = $this->getParams();
		$listModel = $this->getListModel();
		$table = $listModel->getTable();
		$element = $this->getElement();
		$origTable = $table->db_table_name;
		$fabrikDb = $listModel->getDb();
		$elName = $this->getFullName(false, true, false);
		$elName2 = $this->getFullName(false, false, false);
		$v = $this->filterName($counter, $normal);

		// Corect default got
		$default = $this->getDefaultFilterVal($normal, $counter);
		$format = $params->get('date_table_format', '%Y-%m-%d');

		$fromTable = $origTable;

		// $$$ hugh - in advanced search, _aJoins wasn't getting set
		$joins = $listModel->getJoins();
		foreach ($joins as $aJoin)
		{
			// Not sure why the group id key wasnt found - but put here to remove error
			if (array_key_exists('group_id', $aJoin))
			{
				if ($aJoin->group_id == $element->group_id && $aJoin->element_id == 0)
				{
					$fromTable = $aJoin->table_join;
					$elName = str_replace($origTable . '.', $fromTable . '.', $elName);
				}
			}
		}
		$where = $listModel->_buildQueryPrefilterWhere($this);
		$elName = FabrikString::safeColName($elName);
		$requestName = $elName . '___filter';
		if (array_key_exists($elName, $_REQUEST))
		{
			if (is_array($_REQUEST[$elName]) && array_key_exists('value', $_REQUEST[$elName]))
			{
				$_REQUEST[$requestName] = $_REQUEST[$elName]['value'];
			}
		}
		$htmlid = $this->getHTMLId();

		$timeZone = new DateTimeZone(JFactory::getConfig()->get('offset'));
		if (in_array($element->filter_type, array('dropdown')))
		{
			$rows = $this->filterValueList($normal);
		}
		$calOpts = $this->filterCalendarOpts();
		$return = array();
		switch ($element->filter_type)
		{
			case "range":
				FabrikHelperHTML::loadcalendar();
				if (empty($default))
				{
					$default = array('', '');
				}
				else
				{
					$d = new FabDate($default[0]);
					$default[0] = $d->toFormat($format);
					$d = new FabDate($default[1]);
					$default[1] = $d->toFormat($format);
				}
				// Add wrapper div for list filter toggeling
				$return[] = '<div class="fabrik_filter_container">';
				$return[] = JText::_('COM_FABRIK_DATE_RANGE_BETWEEN')
					. $this->calendar($default[0], $v . '[0]', $this->getFilterHtmlId(0), $format, $calOpts);
				$return[] = '<br />' . JText::_('COM_FABRIK_DATE_RANGE_AND')
					. $this->calendar($default[1], $v . '[1]', $this->getFilterHtmlId(1), $format, $calOpts);
				$return[] = '</div>';
				break;

			case "dropdown": /**
							  *  cant do the format in the MySQL query as its not the same formatting
							  *  e.g. M in mysql is month and J's date code its minute
							  */
				jimport('joomla.utilities.date');
				$ddData = array();
				foreach ($rows as $k => $o)
				{
					if ($fabrikDb->getNullDate() === $o->text)
					{
						$o->text = '';
						$o->value = '';
					}
					else
					{
						$d = new FabDate($o->text);
						$o->value = $d->toSql();
						$o->text = $d->toFormat($format);
					}
					if (!array_key_exists($o->value, $ddData))
					{
						$ddData[$o->value] = $o;
					}
				}

				array_unshift($ddData, JHTML::_('select.option', '', $this->filterSelectLabel()));
				$return[] = JHTML::_('select.genericlist', $ddData, $v, 'class="inputbox fabrik_filter" size="1" maxlength="19"', 'value', 'text',
					$default, $htmlid . '0');
				break;
			default:
			case "field":
				FabrikHelperHTML::loadcalendar();
				if (is_array($default))
				{
					$default = array_shift($default);
				}
				if ($default !== '')
				{
					$d = new FabDate($default);
					$default = $d->toFormat($format);
				}
				// Add wrapper div for list filter toggeling
				$return[] = '<div class="fabrik_filter_container">';
				$return[] = $this->calendar($default, $v, $this->getFilterHtmlId(0), $format, $calOpts);
				$return[] = '</div>';
				break;

			case 'hidden':
				if (is_array($default))
				{
					$default = array_shift($default);
				}
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
				$return[] = '<input type="hidden" name="' . $v . '" class="inputbox fabrik_filter" value="' . $default . '" id="' . $htmlid . '" />';
				$return[] = '<input type="text" name="' . $v . '-auto-complete" class="inputbox fabrik_filter autocomplete-trigger" value="'
					. $default . '" id="' . $htmlid . '-auto-complete" />';

				$autoId = '#' . $htmlid . '-auto-complete';
				if (!$normal)
				{
					$autoId = '.advanced-search-list .autocomplete-trigger';
				}
				FabrikHelperHTML::autoComplete($autoId, $this->getElement()->id, 'date');
				break;
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
	 * Get 	filter HTML id
	 *
	 * @param   int  $range  which ranged filter we are getting
	 *
	 * @return  string  html filter id
	 */

	protected function getFilterHtmlId($range)
	{
		$counter = JRequest::getVar('counter', 0);
		return $this->getHTMLId() . '_filter_range_' . $range . '_' . JRequest::getVar('task') . '.' . $counter;
	}

	/**
	 * Takes a date from the server and applies the timezone offset
	 * probably not the right way to do this but ive been at it all day
	 *
	 * @param   object  &$d  FabDate
	 *
	 * @since 3.0
	 *
	 * @return  void
	 */

	protected function toLocalTime(&$d)
	{
		$timeZone = new DateTimeZone(JFactory::getConfig()->get('offset'));
		$hours = $timeZone->getOffset($d) / (60 * 60);
		$dateInterval = new DateInterval('PT' . $hours . 'H');
		$d->add($dateInterval);
	}

	/**
	 * Cache method to populate autocomplete options
	 *
	 * @param   plgFabrik_Element  $elementModel  element model
	 * @param   string             $search        serch string
	 *
	 * @since   3.0.7
	 *
	 * @return string  json encoded search results
	 */

	public static function cacheAutoCompleteOptions($elementModel, $search)
	{
		$listModel = $elementModel->getListModel();
		$table = $listModel->getTable();
		$db = $listModel->getDb();
		$name = $elementModel->getFullName(false, false, false);
		$query = $db->getQuery(true);

		$params = $elementModel->getParams();
		$format = $params->get('date_table_format');
		$elementModel->strftimeTFormatToMySQL($format);

		$search = $db->quote('%' . addslashes($search) . '%');
		$query->select('DISTINCT(' . $name . ') AS value, ' . $name . ' AS text')->from($table->db_table_name)
			->where($name . ' LIKE ' . $search . ' OR DATE_FORMAT(' . $name . ', "' . $format . '" ) LIKE ' . $search);
		$db->setQuery($query);
		$tmp = $db->loadObjectList();
		$ddData = array();
		foreach ($tmp as &$t)
		{
			$elementModel->toLabel($t->text);
			if (!array_key_exists($t->text, $ddData))
			{
				$ddData[$t->text] = $t;
			}
		}
		$ddData = array_values($ddData);
		echo json_encode($ddData);
	}

	/**
	 * When importing csv data you can run this function on all the data to
	 * format it into the format that the form would have submitted the date
	 *
	 * @param   array   &$data  to prepare
	 * @param   string  $key    list column heading
	 * @param   bool    $isRaw  data is raw
	 *
	 * @return  array  data
	 */

	public function prepareCSVData(&$data, $key, $isRaw = false)
	{
		if ($isRaw)
		{
			return;
		}
		$params = $this->getParams();
		$format = $params->get('date_form_format', '%Y-%m-%d %H:%S:%I');

		// Go through data and turn any dates into unix timestamps
		for ($j = 0; $j < count($data); $j++)
		{
			$orig_data = $data[$j][$key];
			$date = JFactory::getDate($data[$j][$key]);
			$data[$j][$key] = $date->toFormat($format, true);
			/* $$$ hugh - bit of a hack specific to a customer who needs to import dates with year as 1899,
			 * which we then change to 1999 using a tablecsv import script (don't ask!). But of course FabDate doesn't
			 * like dates outside of UNIX timestamp range, so the previous line was zapping them. So I'm just restoring
			 * the date as found in the CSV file. This could have side effects if someone else tries to import invalid dates,
			 * but ... ah well.
			 * */
			if (empty($data[$j][$key]) && !empty($orig_data))
			{
				$data[$j][$key] = $orig_data;
			}
		}
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
		return ($data == '') ? true : false;
	}

	/**
	 * This builds an array containing the filters value and condition
	 * when using a ranged search
	 *
	 * @param   string  $value  initial value
	 *
	 * @return  array  (value condition)
	 */

	protected function getRangedFilterValue($value)
	{
		$db = FabrikWorker::getDbo();
		$params = $this->getParams();
		/* $$$ hugh - need to convert dates to MySQL format for the query
		 * $$$ hugh - not any more, since we changed to always submit in MySQL format
		 * $$$ hugh - removing the MySQL conversion has broken 'special' range handling,
		 * which used to happen in the MySQL conversion function.  So ...
		 * Created new helper funcion specialStrToMySQL() which turns things
		 * like 'midnight yesterday' etc into MySQL dates, defaulting to GMT.
		 * This lets us do ranged query string and content plugin filters like ...
		 * table___date[value][]=midnight%20yesterday&table___date[value][]=midnight%20today&table___date[condition]=BETWEEN
		 */
		$value[0] = FabrikWorker::specialStrToMySQL(JArrayHelper::getValue($value, 0));
		$value[1] = FabrikWorker::specialStrToMySQL(JArrayHelper::getValue($value, 1));

		// $$$ hugh - if the first date is later than the second, swap 'em round  to keep 'BETWEEN' in the query happy
		if (strtotime($value[0]) > strtotime($value[1]))
		{
			$tmp_value = $value[0];
			$value[0] = $value[1];
			$value[1] = $tmp_value;
		}

		$exactTime = $this->formatContainsTime($params->get('date_table_format'));
		if (!$params->get('date_showtime', 0) || $exactTime == false)
		{
			// Range values could already have been set in getFilterValue
			if (!$this->rangeFilterSet)
			{
				/* $$$ due to some changes in how we handle ranges, the following was no longer getting
				 * applied in getFilterValue, needed because on first submit of a filter an arbitrary time
				 * is being set (i.e. time "now").
				 */
				$value[0] = $this->setMySQLTimeToZero($value[0]);
				$value[1] = $this->setMySQLTimeToZero($value[1]);

				/* $$$ hugh - need to back this out by one second, otherwise we're including next day.
				 * So ... say we are searching from '2009-07-17' to '2009-07-21', the
				 * addDays(1) changes '2009-07-21 00:00:00' to '2009-07-22 00:00:00',
				 * but what we really want is '2009-07-21 23:59:59'
				 */
				$value[1] = date("Y-m-d H:i:s", strtotime($this->addDays($value[1], 1)) - 1);
			}

		}
		// $$$ rob 20/07/2012 Date is posted as local time, need to set it back to GMT. Seems needed even if dates are saved without timeselector
		// $$$ hugh - think we may need to take 'store as local' in to account here?
		$localTimeZone = new DateTimeZone(JFactory::getConfig()->get('offset'));

		$params = $this->getParams();
		$store_as_local =  $params->get('date_store_as_local', '0') == '1';

		$date = JFactory::getDate($value[0], $localTimeZone);
		$value[0] = $date->toSql($store_as_local);

		$date = JFactory::getDate($value[1], $localTimeZone);
		/* $$$ hugh - why are we setting the 'local' arg on toSql() for end date but not the start date of the range?
		 * This ends up with queries like "BETWEEN '2012-01-26 06:00:00' AND '2012-01-26 23:59:59'"
		 * with CST (GMT -6), which chops out 6 hours of the day range.
		 * Also, see comment above about maybe needing to take "save as local" in to account on this.
		 */

		// $value[1] = $date->toSql(true);
		$value[1] = $date->toSql($store_as_local);

		$value = $db->quote($value[0]) . ' AND ' . $db->quote($value[1]);
		$condition = 'BETWEEN';
		return array($value, $condition);
	}

	/**
	 * Convert a table formatted date string into a mySQL formatted date string
	 * (if already in mySQL format returns the date)
	 *
	 * @param   string  $v  date in table view format
	 *
	 * @deprecated not used
	 *
	 * @return  string	date in mySQL format or false if string date could not be converted
	 */

	protected function tableDateToMySQL($v)
	{
		$params = $this->getParams();
		$store_as_local = (int) $params->get('date_store_as_local', 0);
		$format = $params->get('date_table_format', '%Y-%m-%d');
		$b = FabrikWorker::strToDateTime($v, $format);
		if (!is_array($b))
		{
			return false;
		}
		$bstr = $b['year'] . '-' . $b['mon'] . '-' . $b['day'] . ' ' . $b['hour'] . ':' . $b['min'] . ':' . $b['sec'];
		$date = JFactory::getDate($bstr);
		if (in_array($v, $this->getNullDates()) || $v === $date->toSql())
		{
			return $v;
		}
		if ($store_as_local)
		{
			$this->resetToGMT = false;
		}
		$retval = $this->toMySQLGMT($date);
		$this->resetToGMT = true;
		return $retval;
	}

	/**
	 * Set a dates time to 00:00:00
	 *
	 * @param   mixed  $date  The initial time for the FabDate object
	 *
	 * @deprecated
	 *
	 * @return  string	mysql formatted date
	 */

	protected function setTimeToZero($date)
	{
		$date = JFactory::getDate($date);
		$PHPDate = getdate($date->toUnix());
		$PHPDate['hours'] = 0;
		$PHPDate['minutes'] = 0;
		$PHPDate['seconds'] = 0;
		$v = mktime($PHPDate['hours'], $PHPDate['minutes'], $PHPDate['seconds'], $PHPDate['mon'], $PHPDate['mday'], $PHPDate['year']);
		$date = JFactory::getDate($v);
		return $date->toSql($v);
	}

	/**
	 * simple minded method to set a MySQL formatted date's time to 00:00:00
	 *
	 * @param   string  $date  in MySQL format
	 *
	 * @return  string	mysql formatted date with time set to 0
	 */

	protected function setMySQLTimeToZero($date)
	{
		$date_array = explode(' ', $date);
		$date_array[1] = '00:00:00';
		return implode(' ', $date_array);
	}

	/**
	 * Add days to a date
	 *
	 * @param   mixed    $date  The initial time for the FabDate object
	 * @param   integer  $add   number of days to add (negtive to remove days)
	 *
	 * @return  string	mysql formatted date
	 */

	protected function addDays($date, $add = 0)
	{
		$date = JFactory::getDate($date);
		$PHPDate = getdate($date->toUnix());
		$PHPDate['mday'] = $PHPDate['mday'] + $add;
		$v = mktime($PHPDate['hours'], $PHPDate['minutes'], $PHPDate['seconds'], $PHPDate['mon'], $PHPDate['mday'], $PHPDate['year']);
		$date = JFactory::getDate($v);
		return $date->toSql($v);
	}

	/**
	 * Add hours to a date
	 *
	 * @param   mixed    $date  The initial time for the FabDate object
	 * @param   integer  $add   number of days to add (negtive to remove days)
	 *
	 * @depreacted  - not used
	 *
	 * @return  string	mysql formatted date
	 */

	protected function addHours($date, $add = 0)
	{
		$date = JFactory::getDate($date);
		$PHPDate = getdate($date->toUnix());
		if ($PHPDate['hours'] + $add >= 24)
		{
			$PHPDate['hours'] = 0;
			$PHPDate['mday']++;
		}
		elseif ($PHPDate['hours'] + $add < 0)
		{
			$PHPDate['hours'] = 0;
			$PHPDate['mday']--;
		}
		else
		{
			$PHPDate['hours'] = $PHPDate['hours'] + $add;
		}
		$v = mktime($PHPDate['hours'], $PHPDate['minutes'], $PHPDate['seconds'], $PHPDate['mon'], $PHPDate['mday'], $PHPDate['year']);
		$date = JFactory::getDate($v);
		return $date->toSql($v);
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
		$table = $listModel->getTable();
		$db = $listModel->getDb();
		$joinSQL = $listModel->_buildQueryJoin();
		$whereSQL = $listModel->_buildQueryWhere();
		$name = $this->getFullName(false, false, false);
		return 'SELECT FROM_UNIXTIME(AVG(UNIX_TIMESTAMP(' . $name . '))) AS value, ' . $label . ' AS label FROM '
			. $db->quoteName($table->db_table_name) . ' ' . $joinSQL . ' ' . $whereSQL;
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
		$table = $listModel->getTable();
		$db = $listModel->getDb();
		$joinSQL = $listModel->_buildQueryJoin();
		$whereSQL = $listModel->_buildQueryWhere();
		$name = $this->getFullName(false, false, false);

		// $$$rob not actaully likely to work due to the query easily exceeding mySQL's TIMESTAMP_MAX_VALUE value but the query in itself is correct
		return 'SELECT FROM_UNIXTIME(SUM(UNIX_TIMESTAMP(' . $name . '))) AS value, ' . $label . ' AS label FROM '
			. $db->quoteName($table->db_table_name) . ' ' . $joinSQL . ' ' . $whereSQL;
	}

	/**
	 * find an average from a set of data
	 * can be overwritten in plugin - see date for example of averaging dates
	 *
	 * @param   array  $data  to average
	 *
	 * @return  string  average result
	 */

	public function simpleAvg($data)
	{
		$avg = $this->simpleSum($data) / count($data);
		return JFactory::getDate($avg)->toSql();
	}

	/**
	 * find the sum from a set of data
	 * can be overwritten in plugin - see date for example of averaging dates
	 *
	 * @param   array  $data  to sum
	 *
	 * @return  string  sum result
	 */

	public function simpleSum($data)
	{
		$sum = 0;
		foreach ($data as $d)
		{
			$date = JFactory::getDate($d);
			$sum += $date->toUnix();
		}
		return $sum;
	}

	/**
	 * Takes date's time value and turns it into seconds
	 *
	 * @param   string  $date  object $date
	 *
	 * @return  int		seconds
	 */

	protected function toSeconds($date)
	{
		return (int) ($date->toFormat('%H') * 60 * 60) + (int) ($date->toFormat('%M') * 60) + (int) $date->toFormat('%S');
	}

	/**
	 * Takes strftime time formatting - http://fr.php.net/manual/en/function.strftime.php
	 * and converts to format used in mySQL DATE_FORMAT http://dev.mysql.com/doc/refman/5.1/en/date-and-time-functions.html
	 *
	 * @param   string  &$format  PHP date format string => mysql string format
	 *
	 * @return  void
	 */

	protected function strftimeTFormatToMySQL(&$format)
	{
		/**
		 * $$$ hugh - can't do direct %x to %y, because str_replace's left to right processing,
		 * so (for instance) %B translates to %M, which then gets translated again to %i
		 * So ... do %x to ^@y (hopefully nobody will ever use ^@ in their format string!),
		 * then replace all ^@'s with %'s.
		 */
		$search = array('%e', '%j', '%u', '%V', '%W', '%h', '%B', '%C', '%g', '%G', '%M', '%P', '%r', '%R', '%T', '%X', '%z', '%Z', '%D', '%F', '%s',
			'%x', '%A');

		$replace = array('^@e', '^@j', '^@w', '^@U', '^@U', '^@b', '^@M', '', '^@y', '^@Y', '^@i', '^@p', '^@I:^@i:^@S ^@p', '^@H:^@i',
			'^@H:^@i:^@S', '', '', '^@H:^@i:^@S', '^@m/^@c/^@y', '^@Y-^@m-^@c', '', '^@Y-^@m-^@c', '^@W');

		$format = str_replace($search, $replace, $format);
		$format = str_replace('^@', '%', $format);
	}

	/**
	 * Build the filter query for the given element.
	 * Can be overwritten in plugin - e.g. see checkbox element which checks for partial matches
	 *
	 * @param   string  $key            element name in format `tablename`.`elementname`
	 * @param   string  $condition      =/like etc
	 * @param   string  $value          search string - already quoted if specified in filter array options
	 * @param   string  $originalValue  original filter value without quotes or %'s applied
	 * @param   string  $type           filter type advanced/normal/prefilter/search/querystring/searchall
	 *
	 * @return  string	sql query part e,g, "key = value"
	 */

	public function getFilterQuery($key, $condition, $value, $originalValue, $type = 'normal')
	{
		$this->encryptFieldName($key);
		switch ($condition)
		{
			case 'earlierthisyear':
				$query = ' DAYOFYEAR(' . $key . ') <= DAYOFYEAR(now()) ';
				break;
			case 'laterthisyear':
				$query = ' DAYOFYEAR(' . $key . ') >= DAYOFYEAR(now()) ';
				break;
			case 'today':
				$query = ' (' . $key . ' >= CURDATE() AND ' . $key . ' < CURDATE() + INTERVAL 1 DAY) ';
				break;
			case 'yesterday':
				$query = ' (' . $key . ' >= CURDATE() - INTERVAL 1 DAY AND ' . $key . ' < CURDATE()) ';
				break;
			case 'tomorrow':
				$query = ' (' . $key . ' >= CURDATE() + INTERVAL 1 DAY  AND ' . $key . ' < CURDATE() + INTERVAL 2 DAY ) ';
				break;
			case 'thismonth':
				$query = ' (' . $key . ' >= DATE_ADD(LAST_DAY(DATE_SUB(now(), INTERVAL 1 MONTH)), INTERVAL 1 DAY)  AND ' . $key
					. ' <= LAST_DAY(NOW()) ) ';
				break;
			case 'lastmonth':
				$query = ' (' . $key . ' >= DATE_ADD(LAST_DAY(DATE_SUB(now(), INTERVAL 2 MONTH)), INTERVAL 1 DAY)  AND ' . $key
					. ' <= LAST_DAY(DATE_SUB(NOW(), INTERVAL 1 MONTH)) ) ';
				break;
			case 'nextmonth':
				$query = ' (' . $key . ' >= DATE_ADD(LAST_DAY(now()), INTERVAL 1 DAY)  AND ' . $key
					. ' <= DATE_ADD(LAST_DAY(NOW()), INTERVAL 1 MONTH) ) ';
				break;

			default:
				$params = $this->getParams();
				$format = $params->get('date_table_format');
				if ($format == '%a' || $format == '%A')
				{
					/**
					 * special cases where we want to search on a given day of the week
					 * note it wont work with ranged searches
					 */
					$this->strftimeTFormatToMySQL($format);
					$key = "DATE_FORMAT( $key , '$format')";
				}
				elseif ($format == '%Y %B')
				{
					/* $$$ hugh - testing horrible hack for different languages, initially for andorapro's site
					 * Problem is, he has multiple language versions of the site, and needs to filter tables
					 * by "%Y %B" dropdown (i.e. "2010 November") in multiple languages.
					 * FabDate automagically uses the selected language when we render the date
					 * but when we get to this point, month names are still localized, i.e. in French or German
					 * which MySQL won't grok (until 5.1.12)
					 * So we need to translate them back again, *sigh*
					 * FIXME - need to make all this more generic, so we can handle any date format which uses
					 * month or day names.
					 */
					$matches = array();
					if (preg_match('#\d\d\d\d\s+(\S+)\b#', $value, $matches))
					{
						$this_month = $matches[1];
						$en_month = $this->_monthToEnglish($this_month);
						$value = str_replace($this_month, $en_month, $value);
						$this->strftimeTFormatToMySQL($format);
						$key = "DATE_FORMAT( $key , '$format')";
					}
				}
				if ($type == 'querystring' && JString::strtolower($value) == 'now')
				{
					$value = 'NOW()';
				}
				$query = " $key $condition $value ";
				break;
		}
		return $query;
	}

	/**
	 * Called when copy row list plugin called
	 *
	 * @param   mixed  $val  value to copy into new record
	 *
	 * @return mixed value to copy into new record
	 */

	public function onCopyRow($val)
	{
		$aNullDates = $this->getNullDates();
		if (empty($val) || in_array($val, $aNullDates))
		{
			return $val;
		}
		$params = $this->getParams();
		if ($params->get('date_showtime', 0))
		{
			$store_as_local = (int) $params->get('date_store_as_local', 0);
			if (!$store_as_local)
			{
				$date = JFactory::getDate($val);
				$timeZone = new DateTimeZone(JFactory::getConfig()->get('offset'));
				$date->setTimeZone($timeZone);
				$val = $date->toSql(true);
			}
		}
		return $val;
	}

	/**
	 * Used by validations
	 *
	 * @param   string  $data     this elements data
	 * @param   string  $cond     what condiion to apply
	 * @param   string  $compare  data to compare element's data to (if date already set to Y-m-d H:I:S so no need to apply storeDatabaseForm() on it
	 *
	 * @return bool
	 */

	public function greaterOrLessThan($data, $cond, $compare)
	{
		$data = $this->storeDatabaseFormat($data, null);

		/**
		 * $$$ rob 30/06/2011 the line below was commented out - but if doing date compare on 2 fields
		 * formatting %d/%m/%Y then the compare unix time was not right
		 */
		$compare = $this->storeDatabaseFormat($compare, null);
		$data = JFactory::getDate($data)->toUnix();
		$compare = JFactory::getDate($compare)->toUnix();
		return parent::greaterOrLessThan($data, $cond, $compare);
	}

	/**
	 * Part of horrible hack for translating non-English words back
	 * to something MySQL will understand.
	 *
	 * @param   string  $month  original month name
	 * @param   bool    $abbr   is the month abbreviated
	 *
	 * @return  string  english month name
	 */

	private function _monthToEnglish($month, $abbr = false)
	{
		if ($abbr)
		{
			if (JString::strcmp($month, JText::_('JANUARY_SHORT')) === 0)
			{
				return 'Jan';
			}
			if (JString::strcmp($month, JText::_('FEBRUARY_SHORT')) === 0)
			{
				return 'Feb';
			}
			if (JString::strcmp($month, JText::_('MARCH_SHORT')) === 0)
			{
				return 'Mar';
			}
			if (JString::strcmp($month, JText::_('APRIL_SHORT')) === 0)
			{
				return 'Apr';
			}
			if (JString::strcmp($month, JText::_('MAY_SHORT')) === 0)
			{
				return 'May';
			}
			if (JString::strcmp($month, JText::_('JUNE_SHORT')) === 0)
			{
				return 'Jun';
			}
			if (JString::strcmp($month, JText::_('JULY_SHORT')) === 0)
			{
				return 'Jul';
			}
			if (JString::strcmp($month, JText::_('AUGUST_SHORT')) === 0)
			{
				return 'Aug';
			}
			if (JString::strcmp($month, JText::_('SEPTEMBER_SHORT')) === 0)
			{
				return 'Sep';
			}
			if (JString::strcmp($month, JText::_('OCTOBER_SHORT')) === 0)
			{
				return 'Oct';
			}
			if (JString::strcmp($month, JText::_('NOVEMBER_SHORT')) === 0)
			{
				return 'Nov';
			}
			if (JString::strcmp($month, JText::_('DECEMBER_SHORT')) === 0)
			{
				return 'Dec';
			}
		}
		else
		{
			if (JString::strcmp($month, JText::_('JANUARY')) === 0)
			{
				return 'January';
			}
			if (JString::strcmp($month, JText::_('FEBRUARY')) === 0)
			{
				return 'February';
			}
			if (JString::strcmp($month, JText::_('MARCH')) === 0)
			{
				return 'March';
			}
			if (JString::strcmp($month, JText::_('APRIL')) === 0)
			{
				return 'April';
			}
			if (JString::strcmp($month, JText::_('MAY')) === 0)
			{
				return 'May';
			}
			if (JString::strcmp($month, JText::_('JUNE')) === 0)
			{
				return 'June';
			}
			if (JString::strcmp($month, JText::_('JULY')) === 0)
			{
				return 'July';
			}
			if (JString::strcmp($month, JText::_('AUGUST')) === 0)
			{
				return 'August';
			}
			if (JString::strcmp($month, JText::_('SEPTEMBER')) === 0)
			{
				return 'September';
			}
			if (JString::strcmp($month, JText::_('OCTOBER')) === 0)
			{
				return 'October';
			}
			if (JString::strcmp($month, JText::_('NOVEMBER')) === 0)
			{
				return 'November';
			}
			if (JString::strcmp($month, JText::_('DECEMBER')) === 0)
			{
				return 'December';
			}
		}
		return $month;
	}

	/**
	 * Load a new set of default properites and params for the element
	 *
	 * @return object element (id = 0)
	 */

	public function getDefaultProperties()
	{
		$item = parent::getDefaultProperties();
		$item->hidden = 1;
		return $item;
	}

	/**
	 * convert XML format data into fabrik data (used by web services)
	 *
	 * @param   mixed  $v  data
	 *
	 * @return  mixed  data
	 */

	public function fromXMLFormat($v)
	{
		return JFactory::getDate($v)->toSql();
	}

	/**
	 * If used as a filter add in some JS code to watch observed filter element's changes
	 * when it changes update the contents of this elements dd filter's options
	 *
	 * @param   bool    $normal     is the filter a normal (true) or advanced filter
	 * @param   string  $container  container
	 *
	 * @return  void
	 */

	public function filterJS($normal, $container)
	{

		$element = $this->getElement();
		if ($normal && ($element->filter_type !== 'field' && $element->filter_type !== 'range'))
		{
			return;
		}
		$htmlid = $this->getHTMLId();
		$params = $this->getParams();
		$id = $this->getFilterHtmlId(0);
		$id2 = $this->getFilterHtmlId(1);

		$opts->calendarSetup = $this->_CalendarJSOpts($id);

		$opts->calendarSetup->ifFormat = $params->get('date_table_format', '%Y-%m-%d');
		$opts->type = $element->filter_type;
		$opts->ids = $element->filter_type == 'field' ? array($id) : array($id, $id2);
		$opts->buttons = $element->filter_type == 'field' ? array($id . '_cal_img') : array($id . '_cal_img', $id2 . '_cal_img');
		$opts = json_encode($opts);

		$script = 'Fabrik.filter_' . $container . '.addFilter(\'' . $element->plugin . '\', new DateFilter(' . $opts . '));' . "\n";
		if ($normal)
		{
			FabrikHelperHTML::script('plugins/fabrik_element/date/filter.js');
			return $script;
		}
		else
		{
			FabrikHelperHTML::script('plugins/fabrik_element/date/filter.js', $script);
		}
	}

	/**
	 * Get calendar filter widget options
	 *
	 * @return array  options
	 */

	protected function filterCalendarOpts()
	{
		$params = $this->getParams();
		$calOpts = array('class' => 'inputbox fabrik_filter', 'maxlength' => '19', 'size' => 16);
		if ($params->get('date_allow_typing_in_field', true) == false)
		{
			$calopts['readonly'] = 'readonly';
		}
		return $calOpts;
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
		$prefix = JDEBUG ? '' : '-min';
		$params = $this->getParams();
		if ($params->get('date_advanced', '0') == '1')
		{
			if (empty($prefix))
			{
				parent::formJavascriptClass($srcs, 'media/com_fabrik/js/lib/datejs/date' . $prefix . '.js');
				parent::formJavascriptClass($srcs, 'media/com_fabrik/js/lib/datejs/core' . $prefix . '.js');
				parent::formJavascriptClass($srcs, 'media/com_fabrik/js/lib/datejs/parser' . $prefix . '.js');
				parent::formJavascriptClass($srcs, 'media/com_fabrik/js/lib/datejs/extras' . $prefix . '.js');
			}
			else
			{
				parent::formJavascriptClass($srcs, 'media/com_fabrik/js/lib/datejs/date.js');
				parent::formJavascriptClass($srcs, 'media/com_fabrik/js/lib/datejs/core.js');
				parent::formJavascriptClass($srcs, 'media/com_fabrik/js/lib/datejs/parser.js');
				parent::formJavascriptClass($srcs, 'media/com_fabrik/js/lib/datejs/extras.js');
			}
		}
		parent::formJavascriptClass($srcs);

		// Return false, as we need to be called on per-element (not per-plugin) basis
		return false;
	}

}

/**
 * very small override to JDate to stop 500 errors occuring (when Jdebug is on) if $date is not a valid date string
 *
 * @package  Fabrik
 * @since    3.0
 */

class FabDate extends JDate
{

	protected static $gmt;

	protected static $stz;

	/**
	 * Construct
	 *
	 * @param   string  $date  date
	 * @param   mixed   $tz    timezone
	 */

	public function __construct($date = 'now', $tz = null)
	{
		$orig = $date;
		$date = $this->stripDays($date);
		/* not sure if this one needed?
		 * $date = $this->monthToInt($date);
		 */
		$date = $this->removeDashes($date);
		try
		{
			$dt = new DateTime($date);
		}
		catch (Exception $e)
		{
			JDEBUG ? JError::raiseNotice(500, 'date format unknown for ' . $orig . ' replacing with todays date') : '';
			$date = 'now';
			/* catches 'Failed to parse time string (ublingah!) at position 0 (u)' exception.
			 * don't use this object
			 */
		}
		// Create the base GMT and server time zone objects.
		if (empty(self::$gmt) || empty(self::$stz))
		{
			self::$gmt = new DateTimeZone('GMT');
			self::$stz = new DateTimeZone(@date_default_timezone_get());
		}
		parent::__construct($date, $tz);
	}

	/**
	 * Remove '-' from string
	 *
	 * @param   string  $str  string to remove - from
	 *
	 * @return  string
	 */

	protected function removeDashes($str)
	{
		$str = FabrikString::ltrimword($str, '-');
		return $str;
	}

	/**
	 * Month name to integer
	 *
	 * @param   string  $str  month name
	 *
	 * @return  int  month number
	 */

	protected function monthToInt($str)
	{
		$abbrs = array(true, false);
		for ($a = 0; $a < count($abbrs); $a++)
		{
			for ($i = 0; $i < 13; $i++)
			{
				$month = $this->monthToString($i, $abbrs[$a]);
				if (JString::stristr($str, $month))
				{
					$monthNum = JString::strlen($i) === 1 ? '0' . $i : $i;
					$str = JString::str_ireplace($month, $monthNum, $str);
				}
			}
		}
		return $str;
	}

	/**
	 * Strip days
	 *
	 * @param   string  $str  date string
	 *
	 * @return  string date without days
	 */

	protected function stripDays($str)
	{
		$abbrs = array(true, false);
		for ($a = 0; $a < count($abbrs); $a++)
		{
			for ($i = 0; $i < 7; $i++)
			{
				$day = $this->dayToString($i, $abbrs[$a]);
				if (JString::stristr($str, $day))
				{
					$str = JString::str_ireplace($day, '', $str);
				}
			}
		}
		return $str;
	}
}
