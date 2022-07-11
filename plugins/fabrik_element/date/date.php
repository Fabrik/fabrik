<?php
/**
 * Plugin element to render date picker
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.date
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;
use Fabrik\Helpers\Html;

/**
 * Plugin element to render date picker
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.date
 * @since       3.0
 */
class PlgFabrik_ElementDate extends PlgFabrik_ElementList
{
	/**
	 * States the element should be ignored from advanced search all queries.
	 *
	 * @var bool  True, ignore in extended search all.
	 */
	protected $ignoreSearchAllDefault = true;

	/**
	 * Toggle to determine if storeDatabaseFormat resets the date to GMT
	 *
	 * @var bool
	 */
	protected $resetToGMT = true;

	/**
	 * Is the element a ranged filter (can depend on request data)
	 *
	 * @var bool
	 */
	protected $rangeFilterSet = false;

	/**
	 * Date offset with TZ, for use in front end display
	 *
	 * @var string
	 */
	protected $offsetDate = null;

	/**
	 * Does the element contain sub elements e.g checkboxes radiobuttons
	 *
	 * @var bool
	 */
	public $hasSubElements = false;

	/**
	 * Shows the data formatted for the list view
	 *
	 * @param   string   $data     Elements data
	 * @param   stdClass &$thisRow All the data in the lists current row
	 * @param   array    $opts     Rendering options
	 *
	 * @return  string    formatted value
	 */
	public function renderListData($data, stdClass &$thisRow, $opts = array())
	{
        $profiler = JProfiler::getInstance('Application');
        JDEBUG ? $profiler->mark("renderListData: {$this->element->plugin}: start: {$this->element->name}") : null;
		Html::calendar();

        if ($data == '')
		{
			return parent::renderListData($data, $thisRow, $opts);
		}

		// @TODO: deal with time options (currently can be defined in date_table_format param).
		$timeZone = new DateTimeZone($this->config->get('offset'));
		$params   = $this->getParams();
		$data     = FabrikWorker::JSONtoData($data, true);
		$f        = $params->get('date_table_format', 'Y-m-d');
		$format   = array();

		if (strstr($f, '%'))
		{
			$f = FabDate::strftimeFormatToDateFormat($f);
		}

		foreach ($data as $d)
		{
			if (FabrikWorker::isDate($d))
			{
				$date = JFactory::getDate($d);
				$view = FArrayHelper::getValue($opts, 'view', 'list');

				if ($this->shouldApplyTz($view))
				{
					$date->setTimeZone($timeZone);
				}

				if ($f == '{age}')
				{
					$format[] = date('Y') - $date->format('Y', true);
				}
				else
				{
					$format[] = $date->format($f, true);
				}
			}
			else
			{
				$format[] = '';
			}
		}

		$data = json_encode($format);

		return parent::renderListData($data, $thisRow, $opts);
	}

	/**
	 * Prepares the element data for CSV export
	 *
	 * @param   string $data     Element data
	 * @param   object &$thisRow All the data in the lists current row
	 *
	 * @return  string    formatted value
	 */
	public function renderListData_csv($data, &$thisRow)
	{
		// @TODO: deal with time options (currently can be defined in date_table_format param).
		$timeZone = new DateTimeZone($this->config->get('offset'));
		$params   = $this->getParams();

		$this->getGroup();
		$data = FabrikWorker::JSONtoData($data, true);
		$f    = $params->get('date_table_format', 'Y-m-d');
		/* $$$ hugh - see http://fabrikar.com/forums/showthread.php?p=87507
		 * Really don't think we need to worry about $app->input 'incraw' here. The raw, GMT/MySQL data will get
		 * included in the _raw version of the element if incraw is selected. Here we just want to output
		 * the regular non-raw, formatted, TZ'ed version.
		 */
		$incRaw = false;
		$format = array();

		foreach ($data as $d)
		{
			if (FabrikWorker::isDate($d))
			{
				if ($incRaw)
				{
					$format[] = $d;
				}
				else
				{
					$date = JFactory::getDate($d);

					if ($this->shouldApplyTz('list'))
					{
						$date->setTimeZone($timeZone);
					}

					if ($f == '{age}')
					{
						$format[] = date('Y') - $date->format('Y', true);
					}
					else
					{
						$format[] = $date->format($f, true);
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
	 * @param   string &$id Initial id
	 *
	 * @return  void
	 */
	protected function modHTMLId(&$id)
	{
		$id = $id . '_cal';
	}

	/**
	 * Called by form model to build an array of values to encrypt
	 *
	 * Dates should have tz applied if needed - their value should be the
	 * same as the displayed readonly date.
	 *
	 * @param   array &$values Previously encrypted values
	 * @param   array $data    Form data
	 * @param   int   $c       Repeat group counter
	 *
	 * @return  void
	 */
	public function getValuesToEncrypt(&$values, $data, $c)
	{
		$name  = $this->getFullName(true, false);
		$group = $this->getGroup();

		if ($group->canRepeat())
		{
			if (!array_key_exists($name, $values))
			{
				$values[$name]['data'] = array();
			}

			$gmt                       = $this->getValue($data, $c);
			$localDate                 = $this->displayDate($gmt);
			$values[$name]['data'][$c] = FabrikWorker::isDate($gmt) ? $localDate->toSql(true) : '';
		}
		else
		{
			$gmt                   = $this->getValue($data, $c);
			$localDate             = $this->displayDate($gmt);
			$values[$name]['data'] = FabrikWorker::isDate($gmt) ? $localDate->toSql(true) : '';
		}
	}

	/**
	 * Draws the html form element
	 *
	 * @param   array $data          To pre-populate element with
	 * @param   int   $repeatCounter Repeat group counter
	 *
	 * @return  string    elements html
	 */
	public function render($data, $repeatCounter = 0)
	{
		$this->offsetDate = '';
		$name             = $this->getHTMLName($repeatCounter);
		$id               = $this->getHTMLId($repeatCounter);
		$params           = $this->getParams();
		$element          = $this->getElement();
		$format           = $this->getFormFormat();

		if ($params->get('date_which_time_picker', 'wicked') === 'clock')
		{
			Html::stylesheet(COM_FABRIK_LIVESITE . 'media/com_fabrik/js/lib/clockpicker/bootstrap-clockpicker.css');
		}
		else
		{
			Html::stylesheet(COM_FABRIK_LIVESITE . 'media/com_fabrik/js/lib/wickedpicker/wickedpicker.css');
		}

		if (strstr($format, '%'))
		{
			$format = FabDate::strftimeFormatToDateFormat($format);
		}

		$timeFormat = $this->getTimeFormat();

		// Value is in mySQL format GMT
		$gmt = $this->getValue($data, $repeatCounter);

		if (!FabrikWorker::isDate($gmt))
		{
			$date = '';
			$time = '';
		}
		else
		{
			// Set the date to display local time
			$localDate = $this->displayDate($gmt);

			// Get the formatted time
			$time = ((bool) $element->hidden || $params->get('date_showtime', 0)) ? $localDate->format($timeFormat, true) : '';

			// Formatted date
			$date = $localDate->format($format, true);
		}

		if (!$this->isEditable())
		{
			return $date . ' ' . $time;
		}

		// Build HTML widget
		if ($params->get('date_showtime', 0) || (bool) $element->hidden)
		{
			// Can't have names as simply [] as json only picks up the last one
			$timeElName = $name . '[time]';
			$name .= '[date]';
		}

		$class          = 'fabrikinput inputbox input ' . $params->get('bootstrap_class', 'input-small');
		$element->width = (int) $element->width < 0 ? 1 : (int) $element->width;
		$calOpts        = array('class' => $class, 'size' => $element->width, 'maxlength' => '19');

		if ($params->get('date_allow_typing_in_field', true) == false)
		{
			$calOpts['readonly'] = 'readonly';
		}

		if ($placeholder = $params->get('placeholder'))
		{
			$calOpts['placeholder'] = FText::_($placeholder);
		}

		$str[] = '<div class="fabrikSubElementContainer" id="' . $id . '">';
		$str[] = $this->calendar($date, $name, $id . '_cal', $format, $calOpts, $repeatCounter);

		if ($params->get('date_showtime', 0) || (bool) $element->hidden)
		{
			$this->timeButton($timeElName, $time, $str);
		}

		$str[] = '</div>';

		return implode("\n", $str);
	}

	/**
	 * Render time button
	 *
	 * @param   string $timeElName Element name
	 * @param   string $time       Time value
	 * @param   array  &$str       Markup to append time button to
	 *
	 * @since   3.1b2
	 *
	 * @return  void
	 */
	protected function timeButton($timeElName, $time, &$str)
	{
		$j3         = FabrikWorker::j3();
		$params     = $this->getParams();
		$timeFormat = $this->getTimeFormat();
		$class      = 'inputbox fabrikinput timeField input ' . $params->get('bootstrap_time_class', 'input-mini');
		$readOnly   = $params->get('date_allow_typing_in_field', true) == false ? ' readonly="readonly" ' : '';

		if ($j3)
		{
			$str[] = '<div class="input-append">';
		}

		$timeLength = JString::strlen($timeFormat);
		Html::addPath(COM_FABRIK_BASE . 'plugins/fabrik_element/date/images/', 'image', 'form', false);
		$str[] = '<input type="text" class="' . $class . '" ' . $readOnly . ' size="' . $timeLength . '" value="' . $time . '" name="'
			. $timeElName . '" />';
		$opts  = array('alt' => FText::_('PLG_ELEMENT_DATE_TIME'), 'class' => 'timeButton');
		$file  = FabrikWorker::j3() ? 'clock' : 'time.png';

		$btnLayout  = Html::getLayout('fabrik-button');
		$layoutData = (object) array(
			'class' => 'timeButton',
			'label' => Html::image($file, 'form', @$this->tmpl, $opts),
			'aria'	=> ' aria-label="' . FText::_('PLG_ELEMENT_DATE_ARIA_LABEL_TIME') . '"'
		);

		$str[] = $btnLayout->render($layoutData);

		if ($j3)
		{
			$str[] = '</div>';
		}
	}

	/**
	 * Create the local datetime
	 *
	 * @param   string $gmt Datetime
	 *
	 * @since  3.0.9
	 *
	 * @return  JDate
	 */
	protected function displayDate($gmt)
	{
		$date = JFactory::getDate($gmt);

		if ($this->shouldApplyTz())
		{
			$timeZone = new DateTimeZone($this->config->get('offset'));
			$date->setTimeZone($timeZone);
		}

		$this->offsetDate = $date->toSql(true);

		return $date;
	}

	/**
	 * Should we apply the users timezone offset to the date for display purposes
	 *
	 * This is a bit long winded and could be reduced. BUT the logic is such that I think the
	 * verbosity maintains the readability of the logic
	 *
	 * @param   string $view Component view [list/form]
	 *
	 * @since   3.0.9
	 *
	 * @return  boolean
	 */
	protected function shouldApplyTz($view = 'form')
	{
		// if formatting for plugins (getProcessData), TZ has already been dealt with
		if ($view == 'email')
		{
			return false;
		}

		// if AJAX validating, TZ is set
		if (FabrikWorker::inAJAXValidation())
		{
			return false;
		}

		$formModel    = $this->getFormModel();
		$params       = $this->getParams();
		$storeAsLocal = (bool) $params->get('date_store_as_local', 0);
		$newRecord    = $formModel->isNewRecord();
		$alwaysToday  = $params->get('date_alwaystoday', false);
		$defaultToday = $params->get('date_defaulttotoday');

		if ($storeAsLocal)
		{
			if ($view === 'list')
			{
				return false;
			}

			if ($newRecord)
			{
				if (($alwaysToday || $defaultToday) || $formModel->hasErrors())
				//if (($alwaysToday || $defaultToday))
				{
					// User supplied defaults should be in GMT, they are only applied if no other default found.
					return true;
				}
			}

			if (!$newRecord)
			{
				return true;
			}
		}
		else
		{
			if ($newRecord)
			{
				if (($alwaysToday || $defaultToday) || $formModel->hasErrors() || $view === 'list')
				{
					// User supplied defaults should be in GMT, they are only applied if no other default found.
					return true;
				}
			}

			if (!$newRecord)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Individual store database format
	 *
	 * @param   string $val Value
	 *
	 * @return  string    MySQL formatted date
	 */
	private function _indStoreDBFormat($val)
	{
		$params       = $this->getParams();
		$timeZone     = new DateTimeZone($this->config->get('offset'));
		$storeAsLocal = (bool) $params->get('date_store_as_local', false);
		$alwaysToday  = $params->get('date_alwaystoday', false);

		if ($alwaysToday)
		{
			if (!$storeAsLocal)
			{
				$val = JFactory::getDate('now');
			}
			else
			{
				$val = JFactory::getDate('now', $timeZone);
			}

			$val = $val->toSql(true);

			return $val;
		}

		// $$$ hugh - sometimes still getting $val as an array with date and time,
		// like on AJAX submissions?  Or maybe from getEmailData()?  Or both?
		if (is_array($val))
		{
		    if (array_key_exists('time', $val))
            {
                $date = FArrayHelper::getValue($val, 'date', '');
                $bits = explode(' ', $date);
                $date = $bits[0];
                $time = FArrayHelper::getValue($val, 'time', '');
                $val = $date . ' ' . $time;
            }
		    else if (array_key_exists('date', $val))
		    {
                $val = FArrayHelper::getValue($val, 'date', '');
            }
		    else
            {
                $val = array_shift($val);
            }
		}
		else
		{
			$val = urldecode($val);
		}

		if (!FabrikWorker::isDate($val))
		{
			return '';
		}

		jimport('joomla.utilities.date');
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
		$timeZone = new DateTimeZone($this->config->get('offset'));
		$val      = JFactory::getDate($val, $timeZone)->toSql($storeAsLocal);

		return $val;
	}

	/**
	 * reset the date to GMT - reversing the offset
	 *
	 * @param   object $date Date to convert
	 *
	 * @return  string    MySQL formatted GMT date
	 */
	protected function toMySQLGMT($date)
	{
		if ($this->resetToGMT)
		{
			// $$$ rob 3.0 offset is no longer an integer but a timezone string
			$timeZone = new DateTimeZone($this->config->get('offset'));
			$hours    = $timeZone->getOffset($date) / (60 * 60);
			$invert   = false;

			if ($hours < 0)
			{
				$invert = true;

				// Intervals can only be positive - set invert property
				$hours = $hours * -1;
			}
			// 5.3 only
			if (class_exists('DateInterval'))
			{
				// need to use minutes, as DateInterval will barf on fractional hours like 5.5 (India)
				$dateInterval         = new DateInterval('PT' . $hours * 60 . 'M');
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
	 * Manipulates posted form data for insertion into database
	 *
	 * @param   mixed $val  This elements posted form data
	 * @param   array $data Posted form data
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
			$val = FArrayHelper::getValue($val, 'date', '');
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
	 * @param   mixed $value         Element's data
	 * @param   array $data          Form records data
	 * @param   int   $repeatCounter Repeat group counter
	 *
	 * @return  string    formatted value
	 */
	public function getEmailValue($value, $data = array(), $repeatCounter = 0)
	{
		if (
			(is_array($value) && empty($value))
			|| (
				is_array($value)
				&& FArrayHelper::getValue($value, 'date', '') === ''
				&& FArrayHelper::getValue($value, 'time', '') === ''
			)
			|| (!is_array($value) && trim($value) === '')
		)
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
			$date = FArrayHelper::getValue($value, 'date');
			$d    = JFactory::getDate($date);
			$time = FArrayHelper::getValue($value, 'time', '');

			if ($time !== '')
			{
				$bits = explode(':', $time);
				$h    = (int)FArrayHelper::getValue($bits, 0, 0);
				$m    = (int)FArrayHelper::getValue($bits, 1, 0);
				$s    = (int)FArrayHelper::getValue($bits, 2, 0);
				$d->setTime($h, $m, $s);
			}

			$value = $d->toSql();
		}

		// $$$ hugh - need to convert to database format so we GMT-ified date
		$dummy = new stdClass;
		$opts  = array(
			'view' => 'email'
		);
		return $this->renderListData($value, $dummy, $opts);
		/* $$$ rob - no need to covert to db format now as its posted as db format already.
		 *return $this->renderListData($this->storeDatabaseFormat($value, $data), new stdClass);
		 */
	}

	/**
	 * Determines the label used for the browser title
	 * in the form/detail views
	 *
	 * @param   array $data          Form data
	 * @param   int   $repeatCounter When repeating joined groups we need to know what part of the array to access
	 * @param   array $opts          Options
	 *
	 * @return  string    Text to add to the browser's title
	 */
	public function getTitlePart($data, $repeatCounter = 0, $opts = array())
	{
		$gmt_date = $this->getValue($data, $repeatCounter, $opts);
		/* OK, now we've got the GMT date, convert it
		 * ripped the following off from renderListData ... SURELY we must have a func
		 * somewhere that does this?
		 */
		$params       = $this->getParams();
		$storeAsLocal = (int) $params->get('date_store_as_local', 0);
		$timeZone     = new DateTimeZone($this->config->get('offset'));
		$f            = $params->get('date_table_format', 'Y-m-d');
		$tz_date      = '';

		if (FabrikWorker::isDate($gmt_date))
		{
			$date = JFactory::getDate($gmt_date);

			if (!$storeAsLocal)
			{
				$date->setTimeZone($timeZone);
			}

			if ($f == '{age}')
			{
				$tz_date = date('Y') - $date->format('Y', true);
			}
			else
			{
				$tz_date = $date->format($f, true);
			}
		}

		return $tz_date;
	}

	/**
	 * Converts a raw value into its label equivalent
	 *
	 * @param   string &$v Raw value
	 *
	 * @return  void
	 */
	protected function toLabel(&$v)
	{
		$params   = $this->getParams();
		$f        = $params->get('date_table_format', 'Y-m-d');
		$timeZone = new DateTimeZone($this->config->get('offset'));

		if (FabrikWorker::isDate($v))
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
				$v = $date->format($f, true);
			}
		}
		else
		{
			$v = '';
		}
	}

	/**
	 * Displays a calendar control field
	 *
	 * hacked from behaviour as you need to check if the element exists
	 * it might not as you could be using a custom template
	 *
	 * @param   string $value         The date value (must be in the same format as supplied by $format)
	 * @param   string $name          The name of the text field
	 * @param   string $id            The id of the text field
	 * @param   string $format        The date format (not used)
	 * @param   array  $attribs       Additional html attributes
	 * @param   int    $repeatCounter Repeat group counter (not used)
	 *
	 * @deprecated - use JLayouts instead
	 *
	 * @return  string
	 */
	public function calendar($value, $name, $id, $format = '%Y-%m-%d', $attribs = null, $repeatCounter = 0)
	{
		Html::calendar();
		$j3 = FabrikWorker::j3();

		if (is_array($attribs))
		{
			$attribs = ArrayHelper::toString($attribs);
		}

		Html::addPath(COM_FABRIK_BASE . 'media/system/images/', 'image', 'form', false);
		$opts = $j3 ? array('alt' => 'calendar') : array('alt' => 'calendar', 'class' => 'calendarbutton', 'id' => $id . '_cal_img');
		$img  = Html::image('calendar', 'form', @$this->tmpl, $opts);
		$html = array();

		if ($j3)
		{
			$btnLayout  = Html::getLayout('fabrik-button');
			$layoutData = (object) array(
				'class' => 'calendarbutton',
				'id'    => $id . '_cal_img',
				'label' => $img,
				'aria'	=> ' aria-label="' . FText::_('PLG_ELEMENT_DATE_ARIA_LABEL_DATE') . '"'
			);
			$img        = $btnLayout->render($layoutData);
			$html[]     = '<div class="input-append">';
		}

		$html[] = '<input type="text" name="' . $name . '" id="' . $id . '" value="' . $value . '" ' . $attribs . ' />' . $img;

		if ($j3)
		{
			$html[] = '</div>';
		}

		return implode("\n", $html);
	}

	/**
	 * get the options used for the date elements calendar
	 *
	 * @param   int $id Repeat counter
	 *
	 * @return object ready for js encoding
	 */
	protected function _CalendarJSOpts($id)
	{
		$params            = $this->getParams();
		$opts              = new stdClass;
		$opts->inputField  = $id;
		$opts->button      = $id . "_cal_img";
		$opts->align       = "Tl";
		$opts->singleClick = true;
		$opts->firstDay    = intval($params->get('date_firstday'));
		$opts->ifFormat    = $params->get('date_form_format', $params->get('date_table_format', 'Y-m-d'));
		$opts->timeFormat  = 24;
		$opts->ifFormat    = FabDate::dateFormatToStrftimeFormat($opts->ifFormat);
		$opts->dateAllowFunc = $params->get('date_allow_func');

		return $opts;
	}

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int $repeatCounter Repeat group counter
	 *
	 * @return  array
	 */
	public function elementJavascript($repeatCounter)
	{
		$params       = $this->getParams();
		$element      = $this->getElement();
		$id           = $this->getHTMLId($repeatCounter);
		$opts         = $this->getElementJSOptions($repeatCounter);
		$opts->hidden = (bool) $this->getElement()->hidden;

		// Used uniquely in reset();
		$opts->defaultVal     = $this->getFrontDefaultValue();
		$opts->showtime       = $params->get('date_showtime', 0) ? true : false;
		$opts->whichTimePicker = $params->get('date_which_time_picker', 'wicked');
		$opts->timelabel      = FText::_('PLG_ELEMENT_DATE_TIME_LABEL', true);
		$opts->timePickerLabel = FText::_('PLG_ELEMENT_DATE_TIMEPICKER', true);
		$opts->typing         = (bool) $params->get('date_allow_typing_in_field', true);
		$opts->timedisplay    = $params->get('date_timedisplay', 1);
		$opts->dateTimeFormat = FabDate::dateFormatToStrftimeFormat($this->getTimeFormat());
		$opts->showSeconds    = $opts->whichTimePicker === 'clock' ? false : $params->get('date_show_seconds', '1') === '1';
		$opts->hour24         = $params->get('date_24hour', '1') === '1';
		$opts->allowedDates   = $this->getAllowedPHPDates();
		$opts->watchElement   = $this->getWatchId();
		$opts->id             = $this->getId();
		$opts->locale         = JFactory::getLanguage()->getTag();

		// For reuse if element is duplicated in repeat group
		$opts->calendarSetup = $this->_CalendarJSOpts($id);
		$opts->advanced      = $params->get('date_advanced', '0') == '1';

		JText::script('JLIB_HTML_BEHAVIOR_CLOSE');

		return array('FbDateTime', $id, $opts);
	}

	/**
	 * Get the HTML id for the watch element
	 *
	 * @param   int $repeatCounter repeat group counter
	 *
	 * @return  string
	 */
	protected function getWatchId($repeatCounter = 0)
	{
		$elementModel = $this->getWatchElement();

		return $elementModel ? $elementModel->getHTMLId($repeatCounter) : '';
	}

	/**
	 * Get the element to watch. Changes to this element will trigger the cdd's lookup
	 *
	 * @return  plgFabrik_Element
	 */
	protected function getWatchElement()
	{
		if (!isset($this->watchElement))
		{
			$watch = $this->getParams()->get('date_observe', '');

			if ($watch === '')
			{
				return false;
			}

			$this->watchElement = $this->getFormModel()->getElement($watch, true);

			if (!$this->watchElement)
			{
				// This element is a child element, so $watch is in the parent element (in another form)
				$pluginManager = FabrikWorker::getPluginManager();
				$parent        = $pluginManager->getElementPlugin($watch);

				// These are the possible watch elements
				$children = $parent->getElementDescendents();

				// Match the possible element ids with the current form's element ids
				$elIds   = $this->getFormModel()->getElementIds();
				$matched = array_values(array_intersect($elIds, $children));

				// Load the matched element
				$this->watchElement = $pluginManager->getElementPlugin($matched[0]);
			}
		}

		return $this->watchElement;
	}

	/**
	 * Ajax - get the allowed dates - called when watched element changes
	 *
	 * @return  void
	 */
	public function onAjax_getAllowedDates()
	{
		$this->loadMeForAjax();
		$dates = $this->getAllowedPHPDates();
		echo json_encode($dates);
	}

	/**
	 * Get the allowed dates based on evaluated PHP code
	 *
	 * @return multitype:|array
	 */
	protected function getAllowedPHPDates()
	{
		$params = $this->getParams();
		$data   = $this->getFormModel()->data;
		$php    = $params->get('date_allow_php_func', '');
		$dates  = array();

		if ($php === '')
		{
			return $dates;
		}

		$dates = Html::isDebug() ? eval($php) : @eval($php);
		FabrikWorker::logEval($dates, 'Eval exception : ' . $this->getElement()->name . '::getAllowedPHPDates() : %s');

		return (array) $dates;
	}

	/**
	 * Get database field description
	 *
	 * @return  string  Db field type
	 */
	public function getFieldDescription()
	{
		if ($this->encryptMe())
		{
			return 'BLOB';
		}

		return "DATETIME";
	}

	/**
	 * Get an array of element html ids and their corresponding
	 * js events which trigger a validation.
	 * Examples of where this would be overwritten include timedate element with time field enabled
	 *
	 * @param   int $repeatCounter Repeat group counter
	 *
	 * @return  array  html ids to watch for validation
	 */
	public function getValidationWatchElements($repeatCounter)
	{
		$return   = array();
		$id       = $this->getHTMLId($repeatCounter);
		$return[] = array('id' => $id, 'triggerEvent' => 'blur');

		return $return;
	}

	/**
	 * Element plugin specific method for setting unencrypted values back into post data
	 *
	 * @param   array  &$post Data passed by ref
	 * @param   string $key   Key
	 * @param   string $data  Elements unencrypted data
	 *
	 * @return  void
	 */
	public function setValuesFromEncryt(&$post, $key, $data)
	{
        $group     = $this->getGroup();

        if ($group->canRepeat())
        {
            foreach ($data as &$d)
            {
                if (FabrikWorker::isDate($d)) {
                    // Only apply date logic to actual date data, if blank for example we should leave blank
                    $date = JFactory::getDate($d);
                    $date = $date->toSql();

                    // Put in the correct format
                    list($dateOnly, $time) = explode(' ', $date);
                }
                else {
                    $date = '';
                    $time = '';
                }

                $d = array('date' => $date, 'time' => $time);
            }
        }
        else
        {
            $d = $data[0];

            if (FabrikWorker::isDate($d)) {
                // Only apply date logic to actual date data, if blank for example we should leave blank
                $date = JFactory::getDate($d);
                $date = $date->toSql();

                // Put in the correct format
                list($dateOnly, $time) = explode(' ', $date);
            }
            else {
                $date = '';
                $time = '';
            }

            $data = array('date' => $date, 'time' => $time);
        }

		parent::setValuesFromEncryt($post, $key, $data);
	}

	/**
	 * Get the GMT Date time - tz offset applied in render() if needed
	 *
	 * @param   array $data          Form data date will be GMT if store as local OFF, otherwise as local time
	 * @param   int   $repeatCounter When repeating joined groups we need to know what part of the array to access
	 * @param   array $opts          Options
	 *
	 * @return  string    value  Date as GMT time
	 */
	public function getValue($data, $repeatCounter = 0, $opts = array())
	{
		$params       = $this->getParams();
		$alwaysToday  = $params->get('date_alwaystoday', false);
		$defaultToday = $params->get('date_defaulttotoday', false);
		$formModel    = $this->getFormModel();
		$value        = parent::getValue($data, $repeatCounter, $opts);
		$db           = FabrikWorker::getDbo();

		if (is_array($value))
		{
			// Submission posted as array but date & time in date key. Can be keyed to 0 if parent class casts string to array.
			$value = FArrayHelper::getValue($value, 'date', FArrayHelper::getValue($value, 0));
		}

		// in some corner cases, date will be db name quoted, like in CSV export after an advanced search!
		$value = trim($value, "'");

		if (FabrikWorker::inFormProcess() || FabrikWorker::inAJAXValidation())
		{
			// Don't mess with posted value - can cause double offsets - instead do in _indStoareDBFormat();
			return $value;
		}

		// Element could be a date element (in which case no time stored) - check for both datetime and date null dates.
		$nullDate      = $db->getNullDate();
		$shortNullDate = explode(' ', $nullDate);
		$shortNullDate = FArrayHelper::getValue($shortNullDate, 0);
		$isNullDate    = $nullDate == $value || $shortNullDate == $value;

		if (!(($formModel->isNewRecord() || $this->newGroup) && $defaultToday) && $value == '')
		{
			if (($value == '' || $isNullDate) && !$alwaysToday)
			{
				return $value;
			}

			if ($alwaysToday && $formModel->isEditable())
			{
				$value = '';
			}
		}
		// Don't offset if null date.
		if ($isNullDate)
		{
			return $value;
		}

		$timeZone = new DateTimeZone($this->config->get('offset'));
		$date     = JFactory::getDate($value, $timeZone);

		// Querystring value passed into new record
		if ($formModel->isNewRecord() && $value !== '')
		{
			// OK for : Default to current  = no Local time = yes
			if (!$defaultToday && !$formModel->failedValidation())
			{
				$date = new DateTime($date, $timeZone);
				return $date->format('Y-m-d H:i:s');
			}

			// Ok for : Default to current = yes, Local time = yes OR no
			$date = new DateTime($date, $timeZone);
			$date->setTimeZone(new DateTimeZone('UTC'));
			return $date->format('Y-m-d H:i:s');
		}

		// If value = '' don't offset it (not sure what the logic is but testing seems to indicate this to be true)
		$local = $formModel->hasErrors() || $value == '' || $params->get('date_store_as_local', 0) == 1 ? false : true;
		$value = $date->toSQL($local);

		return $value;
	}

	/**
	 * Does the format string contain time formatting options
	 *
	 * @param   string $format Date format
	 *
	 * @since 2.1.1
	 *
	 * @return  bool
	 */
	protected function formatContainsTime($format)
	{
		$format = FabDate::dateFormatToStrftimeFormat($format);

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
	 * Should the element's data be returned in the search all?
	 *
	 * @param   bool   $advancedMode Is the elements' list is extended search all mode?
	 * @param   string $search       Search string
	 *
	 * @return  bool    true
	 */
	public function includeInSearchAll($advancedMode = false, $search = '')
	{
		if (!FabrikWorker::isDate($search))
		{
			return false;
		}

		return parent::includeInSearchAll($advancedMode);
	}

	/**
	 * Builds an array containing the filters value and condition
	 *
	 * @param   string $value     Initial value
	 * @param   string $condition Initial $condition
	 * @param   string $eval      How the value should be handled
	 *
	 * @return  array    (value condition)
	 */
	public function getFilterValue($value, $condition, $eval)
	{
		/* if its a search all value it may not be a date - so use parent method.
		 * see http://fabrikar.com/forums/showthread.php?t=25255
		 */
		if (!is_array($value) && !FabrikWorker::isDate($value))
		{
			if (($this->rangeFilterSet))
			{
				// It's already been set as a range expression - so split that into an array
				$condition = 'between';
				$value     = explode(' AND ', $value);

				foreach ($value as &$v)
				{
					$v = str_replace(array("'", '"'), '', $v);
				}

				$filterType = FABRIKFILTER_NOQUOTES;
			}
			else
			{
				$filterType = FabrikWorker::isNullDate($value) ? FABRIKFILTER_TEXT : $eval;
			}

			return parent::getFilterValue($value, $condition, $filterType);
		}

		$params       = $this->getParams();
		$storeAsLocal = (int) $params->get('date_store_as_local', 0);

		$timeZone     = $storeAsLocal ? new DateTimeZone($this->config->get('offset')) : null;

		if (!$params->get('date_showtime', 0) || $storeAsLocal)
		{
			$this->resetToGMT = false;
		}

		$exactTime = $params->get('date_showtime', 0) && $this->formatContainsTime($params->get('date_table_format'));

		// $$$ rob if filtering in querystring and ranged value set then force filter type to range

		$filterType = is_array($value) ? 'range' : $this->getFilterType();

		switch ($filterType)
		{
			case 'range':
				// Ranged dates should be sent in sql format
				break;
			case 'field':
			case 'dropdown':
			case 'auto-complete':
			default:
				// Oddity when filtering from qs
				$value = str_replace("'", '', $value);

				/**
				 *  parse through JDate, to allow for special filters such as 'now' 'tomorrow' etc.
				 *  for searches on simply the year - JDate will presume its a timestamp and mung the results
				 *  so we have to use this specific format string to get now and next
				 */
				if (is_numeric($value) && JString::strlen($value) == 4)
				{
					// Will only work on php 5.3.6
					$value = JFactory::getDate('first day of January ' . $value)->toSql();
					$next  = JFactory::getDate('first day of January ' . ($value + 1));
				}
				elseif ($this->isMonth($value))
				{
					$value = JFactory::getDate('first day of ' . $this->untranslateMonth($value))->toSql();
					$next  = JFactory::getDate('last day of ' . $this->untranslateMonth($value))->setTime(23, 59, 59);
				}
				elseif (trim(JString::strtolower($value)) === 'last week')
				{
					$value = JFactory::getDate('last week')->toSql();
					$next  = JFactory::getDate();
				}
				elseif (trim(JString::strtolower($value)) === 'last month')
				{
					$value = JFactory::getDate('last month')->toSql();
					$next  = JFactory::getDate();
				}
				elseif (trim(JString::strtolower($value)) === 'last year')
				{
					$value = JFactory::getDate('last year')->toSql();
					$next  = JFactory::getDate();
				}
				elseif (trim(JString::strtolower($value)) === 'next week')
				{
					$value = JFactory::getDate()->toSql();
					$next  = JFactory::getDate('next week');
				}
				elseif (trim(JString::strtolower($value)) === 'next month')
				{
					$value = JFactory::getDate()->toSql();
					$next  = JFactory::getDate('next month');
				}
				elseif (trim(JString::strtolower($value)) === 'next year')
				{
					$value = JFactory::getDate()->toSql();
					$next  = JFactory::getDate('next year');
				}
				else
				{
					$value = JFactory::getDate($value, $timeZone)->toSql();

					/**
					 *  $$$ hugh - strip time if not needed.  Specific case is element filter,
					 *  first time submitting filter from list, will have arbitrary "now" time.
					 *  Dunno if this will break anything else!
					 */
					if (!$exactTime)
					{
						$value = $this->setMySQLTimeToZero($value);
					}

					$next = JFactory::getDate(strtotime($this->addDays($value, 1)) - 1, $timeZone);
					/**
					 *  $$$ now we need to reset $value to GMT.
					 *  Probably need to take $storeAsLocal into account here?
					 */
					if (!$storeAsLocal)
					{
						$this->resetToGMT = true;
						$value            = $this->toMySQLGMT(JFactory::getDate($value));
						$this->resetToGMT = false;
					}
					else
					{
						$seconds  = $timeZone->getOffset($next);
						$invert = true;
						if ($seconds < 0)
						{
							$invert = false;
							$seconds  = $seconds * -1;
						}
						// need to use minutes, as DateInterval will barf on fractional hours like 5.5 (India)
						$dateInterval         = new DateInterval('PT' . $seconds . 'S');
						$dateInterval->invert = $invert;
						$next->sub($dateInterval);
					}
				}

				// Only set to a range if condition is matching (so don't set to range for < or > conditions)
				if ($condition == 'contains' || $condition == '=' || $condition == 'REGEXP')
				{
					if (!$params->get('date_showtime', 0) || $exactTime == false)
					{
						// $$$ rob turn into a ranged filter to search the entire day  values should be in sql format
						$value     = (array) $value;
						$condition = 'BETWEEN';

						if ($storeAsLocal)
                        {
                            $value[1]  = $next->toSql();
                        }
						else
                        {
                            $this->resetToGMT = true;
                            $value[1]            = $this->toMySQLGMT($next);
                            $this->resetToGMT = false;
                        }

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
		$value            = parent::getFilterValue($value, $condition, $eval);

		return $value;
	}

	/**
	 * Is a string a month?
	 *
	 * @param   string $test String to test
	 *
	 * @return  bool
	 */
	protected function isMonth($test)
	{
		$months = array(FText::_('JANUARY_SHORT'), FText::_('JANUARY'), FText::_('FEBRUARY_SHORT'), FText::_('FEBRUARY'), FText::_('MARCH_SHORT'),
			FText::_('MARCH'), FText::_('APRIL'), FText::_('APRIL_SHORT'), FText::_('MAY_SHORT'), FText::_('MAY'), FText::_('JUNE_SHORT'),
			FText::_('JUNE'), FText::_('JULY_SHORT'), FText::_('JULY'), FText::_('AUGUST_SHORT'), FText::_('AUGUST'), FText::_('SEPTEMBER_SHORT'),
			FText::_('SEPTEMBER'), FText::_('OCTOBER_SHORT'), FText::_('OCTOBER'), FText::_('NOVEMBER_SHORT'), FText::_('NOVEMBER'),
			FText::_('DECEMBER_SHORT'), FText::_('DECEMBER'));

		return in_array($test, $months);
	}

	/**
	 * Get English name for translated month
	 *
	 * @param   string $test Month name
	 *
	 * @return string|boolean
	 */
	protected function untranslateMonth($test)
	{
		switch ($test)
		{
			case FText::_('JANUARY_SHORT'):
			case FText::_('JANUARY'):
				return 'January';
				break;
			case FText::_('FEBRUARY_SHORT'):
			case FText::_('FEBRUARY'):
				return 'February';
				break;
			case FText::_('MARCH_SHORT'):
			case FText::_('MARCH'):
				return 'March';
				break;
			case FText::_('APRIL_SHORT'):
			case FText::_('APRIL'):
				return 'April';
				break;
			case FText::_('MAY_SHORT'):
			case FText::_('MAY'):
				return 'May';
				break;
			case FText::_('JUNE_SHORT'):
			case FText::_('JUNE'):
				return 'June';
				break;
			case FText::_('JULY_SHORT'):
			case FText::_('JULY'):
				return 'July';
				break;
			case FText::_('AUGUST_SHORT'):
			case FText::_('AUGUST'):
				return 'August';
				break;
			case FText::_('SEPTEMBER_SHORT'):
			case FText::_('SEPTEMBER'):
				return 'September';
				break;
			case FText::_('OCTOBER_SHORT'):
			case FText::_('OCTOBER'):
				return 'October';
				break;
			case FText::_('NOVEMBER_SHORT'):
			case FText::_('NOVEMBER'):
				return 'November';
				break;
			case FText::_('DECEMBER_SHORT'):
			case FText::_('DECEMBER'):
				return 'December';
				break;
		}

		return false;
	}

	/**
	 * Get a value to use as an empty filter value
	 *
	 * @return  string
	 */
	public function emptyFilterValue()
	{
		$listModel = $this->getListModel();
		$db        = $listModel->getDb();

		return $db->quote($db->getNullDate());
	}

	/**
	 * Get the list filter for the element
	 * Note: uses FabDate as if date element first to be found in advanced search, and advanced search run on another
	 * element the list model in getAdvancedSearchElementList() builds the first filter (this element) with the data
	 * from the first search which was throwing '"500 - DateTime::__construct() ' errors
	 *
	 * see: http://fabrikar.com/forums/showthread.php?t=28231
	 *
	 * @param   int  $counter Filter order
	 * @param   bool $normal  Do we render as a normal filter or as an advanced search filter
	 *                        if normal include the hidden fields as well (default true, use false for advanced filter
	 *                        rendering)
	 *
	 * @return  string    filter html
	 */
	public function getFilter($counter = 0, $normal = true, $container = '')
	{
		$listModel = $this->getListModel();
		$table     = $listModel->getTable();
		$element   = $this->getElement();
		$origTable = $table->db_table_name;
		$elName    = $this->getFullName(true, false);
		$v         = $this->filterName($counter, $normal);

		// Correct default got
		$default                   = $this->getDefaultFilterVal($normal, $counter);
		$this->filterDisplayValues = (array) $default;

		// $$$ hugh - in advanced search, _aJoins wasn't getting set
		$joins = $listModel->getJoins();

		foreach ($joins as $aJoin)
		{
			// Not sure why the group id key wasn't found - but put here to remove error
			if (property_exists($aJoin, 'group_id'))
			{
				if ($aJoin->group_id == $element->group_id && $aJoin->element_id == 0)
				{
					$fromTable = $aJoin->table_join;
					$elName    = str_replace($origTable . '.', $fromTable . '.', $elName);
				}
			}
		}

		$where       = $listModel->buildQueryPrefilterWhere($this);
		$elName      = FabrikString::safeColName($elName);
		$requestName = $elName . '___filter';

		if (array_key_exists($elName, $_REQUEST))
		{
			if (is_array($_REQUEST[$elName]) && array_key_exists('value', $_REQUEST[$elName]))
			{
				$_REQUEST[$requestName] = $_REQUEST[$elName]['value'];
			}
		}

		$fType = $this->getFilterType();

		if (in_array($fType, array('dropdown', 'checkbox', 'multiselect')))
		{
			$rows = $this->filterValueList($normal);
			$this->getFilterDisplayValues($default, $rows);
		}

		$return = array();

		switch ($fType)
		{
			case 'checkbox':
				$return[] = $this->checkboxFilter($rows, $default, $v);
				break;
			case 'range':
			case 'range-hidden':
				$return[] = $this->rangeFilter($default, $v);
				break;

			case 'dropdown':
			case 'multiselect':
				$return[] = $this->selectListFilter($rows, $default, $v);
				break;
			default:
			case 'field':
				$return[] = $this->singleFilter($default, $v);
				break;

			case 'hidden':
				$return[] = $this->hiddenFilter($default, $v);
				break;

			case 'auto-complete':
				$return[] = $this->autoCompleteFilter($default, $v);
				break;
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
	 * Override for main model getFilterRO to handle search all corner case
	 *
	 * @param   mixed $data String or array of filter value(s)
	 *
	 * @since   3.0.7
	 *
	 * @return  string
	 */
	public function getFilterRO($data)
	{
		/**
		 * This rather fugly chunk of code is needed to handle 'search all', where the filter data has already
		 * been converted into textual AND format.
		 */
		$v = FArrayHelper::getValue($data, $this->getFullName(true, false), '');

		if (is_string($v) && strstr($v, ' AND '))
		{
			foreach (explode(' AND ', FArrayHelper::getValue($data, $this->getFullName(true, false), array())) as $d)
			{
				$return[] = $this->getROElement(trim($d, "'"));
			}

			return FText::_('COM_FABRIK_BETWEEN') . '<br />' . implode('<br />' . FText::_('COM_FABRIK_AND') . "<br />", $return);
		}

		return parent::getFilterRO($data);
	}

	/**
	 * Build the HTML for the auto-complete filter
	 *
	 * @param   string $default    Label
	 * @param   string $v          Field name
	 * @param   string $labelValue Label value
	 * @param   bool   $normal     Do we render as a normal filter or as an advanced search filter
	 *                             if normal include the hidden fields as well (default true, use false for advanced
	 *                             filter rendering)
	 *
	 * @return  string JLayout render
	 */
	protected function autoCompleteFilter($default, $v, $labelValue = null, $normal = true, $container = null)
	{
		if (get_magic_quotes_gpc())
		{
			$default = stripslashes($default);
		}

		$default = htmlspecialchars($default);
		$htmlId  = $this->getHTMLId();

		$layout             = $this->getLayout('list-filter-autocomplete');
		$displayData        = new stdClass;
		$displayData->class = $this->filterClass();
		$displayData->name  = $v;

		$displayData->default = $default;
		$displayData->htmlId  = $htmlId;

		$autoId = '#' . $htmlId . '-auto-complete';

		if (!$normal)
		{
			$autoId = '.advanced-search-list .autocomplete-trigger';
		}

		Html::autoComplete($autoId, $this->getElement()->id, $this->getFormModel()->getId(), 'date');

		return $layout->render($displayData);
	}

	/**
	 * @param $default
	 * @param $v
	 *
	 * @return  string  filter
	 */
	protected function hiddenFilter($default, $v)
	{
		if (is_array($default))
		{
			$default = array_shift($default);
		}

		if (get_magic_quotes_gpc())
		{
			$default = stripslashes($default);
		}

		$default = htmlspecialchars($default);
		$class   = $this->filterClass();

		// Don't add id as caused issues with inline edit plugin and clashing ids.
		return '<input type="hidden" name="' . $v . '" class="' . $class . '" value="' . $default . '" />';
	}

	/**
	 * @param $rows
	 * @param $default
	 * @param $v
	 *
	 * @return string filter
	 */
	protected function selectListFilter($rows, $default, $v)
	{
		$element   = $this->getElement();
		$listModel = $this->getListModel();
		$fabrikDb  = $listModel->getDb();
		$timeZone  = new DateTimeZone($this->config->get('offset'));
		$params    = $this->getParams();
		$format    = $params->get('date_table_format', 'Y-m-d');
		$storeAsLocal = $params->get('date_store_as_local', '0') == '1';

		/**
		 *  cant do the format in the MySQL query as its not the same formatting
		 *  e.g. M in MySQL is month and J's date code its minute
		 */
		$max   = count($rows) < 7 ? count($rows) : 7;
		$fType = $this->getFilterType();
		$size  = $element->filter_type === 'multiselect' ? 'multiple="multiple" size="' . $max . '"' : 'size="1"';
		$v     = $fType === 'multiselect' ? $v . '[]' : $v;

		jimport('joomla.utilities.date');
		$ddData = array();

		foreach ($rows as $k => $o)
		{
			if ($fabrikDb->getNullDate() === $o->text)
			{
				$o->text  = '';
				$o->value = '';
			}
			else
			{
				$d = new FabDate($o->text);
				if (!$storeAsLocal)
				{
					$d->setTimeZone($timeZone);
					$o->value = $d->toSql(true);
					$o->text  = $d->format($format, true);
				}
				else
				{
					$o->value = $d->toSql(false);
					$o->text  = $d->format($format, false);

				}
			}

			if (!array_key_exists($o->value, $ddData))
			{
				$ddData[$o->value] = $o;
			}
		}

		array_unshift($ddData, JHTML::_('select.option', '', $this->filterSelectLabel()));
		$layout               = $this->getLayout('list-filter-dropdown');
		$displayData          = new stdClass;
		$displayData->rows    = $ddData;
		$displayData->name    = $v;
		$displayData->class   = $this->filterClass();
		$displayData->size    = $size;
		$displayData->default = $default;
		$displayData->htmlId  = $this->getHTMLId();

		return $layout->render($displayData);
	}

	/**
	 * Create a input type field filter
	 *
	 * @param $default
	 * @param $v
	 *
	 * @return string  filter
	 */
	protected function singleFilter($default, $v, $type = 'text')
	{
		$params = $this->getParams();
		$format = $params->get('date_table_format', 'Y-m-d');

		if (is_array($default))
		{
			$default = array_shift($default);
		}

		if ($default !== '')
		{
			$d       = new FabDate($default);
			$default = $d->format($format);
		}

		$layout          = $this->getLayout('list-filter-field');
		$displayData     = new stdClass;
		$displayData->j3 = FabrikWorker::j3();
		$from            = new stdClass;
		$from->id        = $this->getFilterHtmlId(0);
		$from->value     = $default;
		$from->name      = $v;

		$imageOpts = $displayData->j3 ? array('alt' => 'calendar') : array('alt'   => 'calendar',
		                                                                   'class' => 'calendarbutton', 'id' => $from->id . '_cal_img');
		$from->img = Html::image('calendar', 'form', @$this->tmpl, $imageOpts);

		$displayData->from = $from;

		$displayData->format  = $format;
		$displayData->calOpts = $this->filterCalendarOpts();

		return $layout->render($displayData);
	}

	/**
	 * @param $default
	 * @param $v
	 *
	 * @return string filter
	 */
	protected function rangeFilter($default, $v)
	{
		$params = $this->getParams();
		$format = $params->get('date_table_format', 'Y-m-d');

		if (empty($default))
		{
			$default = array('', '');
		}
		else
		{
			$d          = new FabDate($default[0]);
			$default[0] = $d->format($format);
			$d          = new FabDate($default[1]);
			$default[1] = $d->format($format);
		}

		// Add wrapper div for list filter toggling
		$return[] = '<div class="fabrik_filter_container">';

		$layout              = $this->getLayout('list-filter-range');
		$displayData         = new stdClass;
		$displayData->htmlId = $this->getHTMLId();
		$displayData->class  = $this->filterClass();
		$displayData->j3     = FabrikWorker::j3();
		$from                = new stdClass;
		$from->id            = $this->getFilterHtmlId(0);
		$from->value         = $default[0];
		$from->name          = $v . '[0]';

		$imageOpts = $displayData->j3 ? array('alt' => 'calendar') : array('alt' => 'calendar', 'class' => 'calendarbutton', 'id' => $from->id . '_cal_img');
		$from->img = Html::image('calendar', 'form', @$this->tmpl, $imageOpts);

		$displayData->from = $from;

		$displayData->format  = $format;
		$displayData->calOpts = $this->filterCalendarOpts();

		$to        = new stdClass;
		$to->id    = $this->getFilterHtmlId(1);
		$to->value = $default[1];
		$to->name  = $v . '[1]';

		$imageOpts = $displayData->j3 ? array('alt' => 'calendar') : array('alt' => 'calendar', 'class' => 'calendarbutton', 'id' => $to->id . '_cal_img');
		$to->img   = Html::image('calendar', 'form', @$this->tmpl, $imageOpts);

		$displayData->to         = $to;
		$displayData->filterType = $this->getFilterType();

		return $layout->render($displayData);
	}

	/**
	 * Get    filter HTML id
	 *
	 * @param   int $range Which ranged filter we are getting
	 *
	 * @return  string  html filter id
	 */
	protected function getFilterHtmlId($range)
	{
		$input   = $this->app->input;
		$counter = $input->get('counter', 0);
		$listRef = $this->getListModel()->getRenderContext();

		return $this->getHTMLId() . '_' . $listRef . '_filter_range_' . $range . '_' . $input->get('task') . '.' . $counter;
	}

	/**
	 * Cache method to populate auto-complete options
	 *
	 * @param   plgFabrik_Element $elementModel Element model
	 * @param   string            $search       Search string
	 * @param   array             $opts         Options, 'label' => field to use for label (db join)
	 *
	 * @since   3.0.7
	 *
	 * @return string  json encoded search results
	 */
	public static function cacheAutoCompleteOptions($elementModel, $search, $opts = array())
	{
		$listModel = $elementModel->getListModel();
		$table     = $listModel->getTable();
		$db        = $listModel->getDb();
		$name      = $elementModel->getFullName(false, false);
		$query     = $db->getQuery(true);
		$params    = $elementModel->getParams();
		$format    = $params->get('date_table_format');
		$elementModel->strftimeTFormatToMySQL($format);
		$search = $db->quote('%' . addslashes($search) . '%');
		$query->select('DISTINCT(' . $name . ') AS value, ' . $name . ' AS text')->from($table->db_table_name)
			->where($name . ' LIKE ' . $search . ' OR DATE_FORMAT(' . $name . ', "' . $format . '" ) LIKE ' . $search);
		$db->setQuery($query);
		$tmp    = $db->loadObjectList();
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
	 * @param   array  &$data To prepare
	 * @param   string $key   List column heading
	 * @param   bool   $isRaw Data is raw
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
		$csvFormat = $params->get('date_csv_offset_tz', '0');

		// 0 is "format format", 1 is "list format"
		if ($csvFormat === '0' || $csvFormat === '3')
		{
			$paramName = $csvFormat === '0' ? 'date_form_format' : 'date_table_format';

			if ($paramName === 'date_form_format')
			{
				$format = $this->getFormFormat();
				$format .= ' ' . $this->getTimeFormat();
			}
			else
			{
				$format = $params->get($paramName, 'Y-m-d H:i:s');
			}

			// Go through data and convert specified format to MySQL
			for ($j = 0; $j < count($data); $j++)
			{
				$orig_data = $data[$j][$key];

				if (empty($orig_data))
				{
					$data[$j][$key] = '0000-00-00 00:00:00';
					continue;
				}

				try
				{
					$date           = DateTime::createFromFormat($format, $orig_data);
					$exactTime = $this->formatContainsTime($format);

					if ($date !== false)
					{
						if (!$params->get('date_showtime', 0) || $exactTime == false)
						{
							$date->setTime(0, 0, 0);
						}

						$data[$j][$key] = $date->format('Y-m-d H:i:s');
					}
				}
				catch (Exception $e)
				{
					// Suppress date time format error
				}
			}
		}
	}

	/**
	 * Does the element consider the data to be empty
	 * Used in isempty validation rule
	 *
	 * @param   array $data          Data to test against
	 * @param   int   $repeatCounter Repeat group #
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
	 * @param   array  $value     Initial value
	 * @param   string $condition Filter condition e.g BETWEEN
	 *
	 * @return  array  (value condition)
	 */
	protected function getRangedFilterValue($value, $condition = '')
	{
		$db     = FabrikWorker::getDbo();
		$params = $this->getParams();
		/* $$$ hugh - need to convert dates to MySQL format for the query
		 * $$$ hugh - not any more, since we changed to always submit in MySQL format
		 * $$$ hugh - removing the MySQL conversion has broken 'special' range handling,
		 * which used to happen in the MySQL conversion function.  So ...
		 * Created new helper function specialStrToMySQL() which turns things
		 * like 'midnight yesterday' etc. into MySQL dates, defaulting to GMT.
		 * This lets us do ranged query string and content plugin filters like ...
		 * table___date[value][]=midnight%20yesterday&table___date[value][]=midnight%20today&table___date[condition]=BETWEEN
		 */
		$value[0] = FabrikWorker::specialStrToMySQL(FArrayHelper::getValue($value, 0));
		$value[1] = FabrikWorker::specialStrToMySQL(FArrayHelper::getValue($value, 1));

		// $$$ hugh - if the first date is later than the second, swap 'em round  to keep 'BETWEEN' in the query happy
		if (strtotime($value[0]) > strtotime($value[1]))
		{
			$tmp_value = $value[0];
			$value[0]  = $value[1];
			$value[1]  = $tmp_value;
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
		$localTimeZone = new DateTimeZone($this->config->get('offset'));

		$params       = $this->getParams();
		$storeAsLocal = $params->get('date_store_as_local', '0') == '1';

		$date     = JFactory::getDate($value[0], $localTimeZone);
		$value[0] = $date->toSql($storeAsLocal);

		$date = JFactory::getDate($value[1], $localTimeZone);
		/* $$$ hugh - why are we setting the 'local' arg on toSql() for end date but not the start date of the range?
		 * This ends up with queries like "BETWEEN '2012-01-26 06:00:00' AND '2012-01-26 23:59:59'"
		 * with CST (GMT -6), which chops out 6 hours of the day range.
		 * Also, see comment above about maybe needing to take "save as local" in to account on this.
		 */

		// $value[1] = $date->toSql(true);
		$value[1] = $date->toSql($storeAsLocal);

		$value     = $db->quote($value[0]) . ' AND ' . $db->quote($value[1]);
		$condition = 'BETWEEN';

		return array($value, $condition);
	}

	/**
	 * simple minded method to set a MySQL formatted date's time to 00:00:00
	 *
	 * @param   string $date In MySQL format
	 *
	 * @return  string    MySQL formatted date with time set to 0
	 */
	protected function setMySQLTimeToZero($date)
	{
		$date_array    = explode(' ', $date);
		$date_array[1] = '00:00:00';

		return implode(' ', $date_array);
	}

	/**
	 * Add days to a date
	 *
	 * @param   mixed   $date The initial time for the FabDate object
	 * @param   integer $add  Number of days to add (negative to remove days)
	 *
	 * @return  string    MySQL formatted date
	 */
	protected function addDays($date, $add = 0)
	{
		$params          = $this->getParams();
		$storeAsLocal    = $params->get('date_store_as_local', 0) == 1;
		$date            = JFactory::getDate($date);
		/*
		$PHPDate         = getdate($date->toUnix());
		$PHPDate['mday'] = $PHPDate['mday'] + $add;
		$v               = mktime($PHPDate['hours'], $PHPDate['minutes'], $PHPDate['seconds'], $PHPDate['mon'], $PHPDate['mday'], $PHPDate['year']);
		$date            = JFactory::getDate($v);
		*/
		$hours = 'PT' . abs($add) * 24 . 'H';

		if ($add < 0)
		{
			$date->sub(new DateInterval($hours));
		}
		else
		{
			$date->add(new DateInterval($hours));
		}

		return $date->toSql($storeAsLocal);
	}

	/**
	 * Build the query for the avg calculation
	 *
	 * @param   model &$listModel list model
	 * @param   array $labels     Labels
	 *
	 * @return  string    sql statement
	 */
	protected function getAvgQuery(&$listModel, $labels = array())
	{
		$label    = count($labels) == 0 ? "'calc' AS label" : 'CONCAT(' . implode(', " & " , ', $labels) . ')  AS label';
		$table    = $listModel->getTable();
		$db       = $listModel->getDb();
		$joinSQL  = $listModel->buildQueryJoin();
		$whereSQL = $listModel->buildQueryWhere();
		$name     = $this->getFullName(false, false);

		return 'SELECT FROM_UNIXTIME(AVG(UNIX_TIMESTAMP(' . $name . '))) AS value, ' . $label . ' FROM '
		. $db->qn($table->db_table_name) . ' ' . $joinSQL . ' ' . $whereSQL;
	}

	/**
	 * Get sum query
	 *
	 * @param   object &$listModel List model
	 * @param   array  $labels     Label
	 *
	 * @return string
	 */
	protected function getSumQuery(&$listModel, $labels = array())
	{
		$label    = count($labels) == 0 ? "'calc' AS label" : 'CONCAT(' . implode(', " & " , ', $labels) . ')  AS label';
		$table    = $listModel->getTable();
		$db       = $listModel->getDb();
		$joinSQL  = $listModel->buildQueryJoin();
		$whereSQL = $listModel->buildQueryWhere();
		$name     = $this->getFullName(false, false);

		// $$$rob not actually likely to work due to the query easily exceeding MySQL's TIMESTAMP_MAX_VALUE value but the query in itself is correct
		return 'SELECT FROM_UNIXTIME(SUM(UNIX_TIMESTAMP(' . $name . '))) AS value, ' . $label . ' FROM '
		. $db->qn($table->db_table_name) . ' ' . $joinSQL . ' ' . $whereSQL;
	}

	/**
	 * find an average from a set of data
	 * can be overwritten in plugin - see date for example of averaging dates
	 *
	 * @param   array $data To average
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
	 * @param   array $data To sum
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
	 * Takes date's time value and turns it into seconds. Used in timer
	 *
	 * @param   string $date Object $date
	 *
	 * @return  int        seconds
	 */
	protected function toSeconds($date)
	{
		return (int) ($date->format('H') * 60 * 60) + (int) ($date->format('i') * 60) + (int) $date->format('s');
	}

	/**
	 * Takes strftime time formatting - http://fr.php.net/manual/en/function.strftime.php
	 * and converts to format used in MySQL DATE_FORMAT
	 * http://dev.mysql.com/doc/refman/5.1/en/date-and-time-functions.html
	 *
	 * @param   string &$format PHP date format string => MySQL string format
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
	 * @param   string $key           Element name in format `tablename`.`elementname`
	 * @param   string $condition     =/like etc
	 * @param   string $value         Search string - already quoted if specified in filter array options
	 * @param   string $originalValue Original filter value without quotes or %'s applied
	 * @param   string $type          Filter type advanced/normal/prefilter/search/querystring/searchall
	 * @param   string  $evalFilter     evaled
	 *                                  
	 * @return  string    sql query part e,g, "key = value"
	 */
	public function getFilterQuery($key, $condition, $value, $originalValue, $type = 'normal', $evalFilter = '0')
	{
		$this->encryptFieldName($key);

		switch ($condition)
		{
			case 'thisyear':
				$query = ' YEAR(' . $this->addConvert($key) . ') = YEAR(NOW()) ';
				break;
			case 'earlierthisyear':
				$query = ' (DAYOFYEAR(' . $this->addConvert($key) . ') <= DAYOFYEAR(NOW()) AND YEAR(' . $this->addConvert($key) . ') = YEAR(NOW())) ';
				break;
			case 'laterthisyear':
				$query = ' (DAYOFYEAR(' . $this->addConvert($key) . ') >= DAYOFYEAR(NOW()) AND YEAR(' . $this->addConvert($key) . ') = YEAR(NOW())) ';
				break;
			case 'today':
				$query = ' (' . $this->addConvert($key) . ' >= CURDATE() AND ' . $this->addConvert($key) . ' < CURDATE() + INTERVAL 1 DAY) ';
				break;
			case 'yesterday':
				$query = ' (' . $this->addConvert($key) . ' >= CURDATE() - INTERVAL 1 DAY AND ' . $this->addConvert($key) . ' < CURDATE()) ';
				break;
			case 'tomorrow':
				$query = ' (' . $this->addConvert($key) . ' >= CURDATE() + INTERVAL 1 DAY  AND ' . $this->addConvert($key) . ' < CURDATE() + INTERVAL 2 DAY ) ';
				break;
			case 'thismonth':
				$query = ' (' . $this->addConvert($key) . ' >= DATE_ADD(LAST_DAY(DATE_SUB(now(), INTERVAL 1 MONTH)), INTERVAL 1 DAY)  AND ' . $this->addConvert($key)
					. ' <= LAST_DAY(NOW()) ) ';
				break;
			case 'lastmonth':
				$query = ' (' . $this->addConvert($key) . ' >= DATE_ADD(LAST_DAY(DATE_SUB(now(), INTERVAL 2 MONTH)), INTERVAL 1 DAY)  AND ' . $this->addConvert($key)
					. ' <= LAST_DAY(DATE_SUB(NOW(), INTERVAL 1 MONTH)) ) ';
				break;
			case 'nextmonth':
				$query = ' (' . $this->addConvert($key) . ' >= DATE_ADD(LAST_DAY(now()), INTERVAL 1 DAY)  AND ' . $this->addConvert($key)
					. ' <= DATE_ADD(LAST_DAY(NOW()), INTERVAL 1 MONTH) ) ';
				break;
			case 'nextweek1':
				$query = ' (YEARWEEK(' . $this->addConvert($key) . ',1) = YEARWEEK(DATE_ADD(NOW(), INTERVAL 1 WEEK), 1))';
				break;
			case 'birthday':
				$query = '(MONTH(' . $this->addConvert($key) . ') = MONTH(CURDATE()) AND  DAY(' . $this->addConvert($key) . ') = DAY(CURDATE())) ';
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
						$en_month   = $this->_monthToEnglish($this_month);
						$value      = str_replace($this_month, $en_month, $value);
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
	 * @param   mixed $val Value to copy into new record
	 *
	 * @return mixed value to copy into new record
	 */
	public function onCopyRow($val)
	{
		if ($this->defaultOnCopy())
		{
			$val = $this->getFrontDefaultValue();
		}

		if (!FabrikWorker::isDate($val))
		{
			return $val;
		}

		$params = $this->getParams();

		if ($params->get('date_showtime', 0))
		{
			$storeAsLocal = (int) $params->get('date_store_as_local', 0);

			if (!$storeAsLocal)
			{
				$date     = JFactory::getDate($val);
				$timeZone = new DateTimeZone($this->config->get('offset'));
				$date->setTimeZone($timeZone);
				$val = $date->toSql(true);
			}
		}

		return $val;
	}

	/**
	 * Called when save as copy form button clicked
	 *
	 * @param   mixed $val value to copy into new record
	 *
	 * @return  mixed  value to copy into new record
	 */
	public function onSaveAsCopy($val)
	{
		return $this->onCopyRow($val);
	}

	/**
	 * Used by validations
	 *
	 * @param   string $data    This elements data
	 * @param   string $cond    What condition to apply
	 * @param   string $compare Data to compare element's data to (if date already set to Y-m-d H:I:S so no need to
	 *                          apply storeDatabaseForm() on it
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
		$data    = JFactory::getDate($data)->toUnix();
		$compare = JFactory::getDate($compare)->toUnix();

		return parent::greaterOrLessThan($data, $cond, $compare);
	}

	/**
	 * Part of horrible hack for translating non-English words back
	 * to something MySQL will understand.
	 *
	 * @param   string $month Original month name
	 * @param   bool   $abbr  Is the month abbreviated
	 *
	 * @return  string  English month name
	 */
	private function _monthToEnglish($month, $abbr = false)
	{
		if ($abbr)
		{
			if (JString::strcmp($month, FText::_('JANUARY_SHORT')) === 0)
			{
				return 'Jan';
			}

			if (JString::strcmp($month, FText::_('FEBRUARY_SHORT')) === 0)
			{
				return 'Feb';
			}

			if (JString::strcmp($month, FText::_('MARCH_SHORT')) === 0)
			{
				return 'Mar';
			}

			if (JString::strcmp($month, FText::_('APRIL_SHORT')) === 0)
			{
				return 'Apr';
			}

			if (JString::strcmp($month, FText::_('MAY_SHORT')) === 0)
			{
				return 'May';
			}

			if (JString::strcmp($month, FText::_('JUNE_SHORT')) === 0)
			{
				return 'Jun';
			}

			if (JString::strcmp($month, FText::_('JULY_SHORT')) === 0)
			{
				return 'Jul';
			}

			if (JString::strcmp($month, FText::_('AUGUST_SHORT')) === 0)
			{
				return 'Aug';
			}

			if (JString::strcmp($month, FText::_('SEPTEMBER_SHORT')) === 0)
			{
				return 'Sep';
			}

			if (JString::strcmp($month, FText::_('OCTOBER_SHORT')) === 0)
			{
				return 'Oct';
			}

			if (JString::strcmp($month, FText::_('NOVEMBER_SHORT')) === 0)
			{
				return 'Nov';
			}

			if (JString::strcmp($month, FText::_('DECEMBER_SHORT')) === 0)
			{
				return 'Dec';
			}
		}
		else
		{
			if (JString::strcmp($month, FText::_('JANUARY')) === 0)
			{
				return 'January';
			}

			if (JString::strcmp($month, FText::_('FEBRUARY')) === 0)
			{
				return 'February';
			}

			if (JString::strcmp($month, FText::_('MARCH')) === 0)
			{
				return 'March';
			}

			if (JString::strcmp($month, FText::_('APRIL')) === 0)
			{
				return 'April';
			}

			if (JString::strcmp($month, FText::_('MAY')) === 0)
			{
				return 'May';
			}

			if (JString::strcmp($month, FText::_('JUNE')) === 0)
			{
				return 'June';
			}

			if (JString::strcmp($month, FText::_('JULY')) === 0)
			{
				return 'July';
			}

			if (JString::strcmp($month, FText::_('AUGUST')) === 0)
			{
				return 'August';
			}

			if (JString::strcmp($month, FText::_('SEPTEMBER')) === 0)
			{
				return 'September';
			}

			if (JString::strcmp($month, FText::_('OCTOBER')) === 0)
			{
				return 'October';
			}

			if (JString::strcmp($month, FText::_('NOVEMBER')) === 0)
			{
				return 'November';
			}

			if (JString::strcmp($month, FText::_('DECEMBER')) === 0)
			{
				return 'December';
			}
		}

		return $month;
	}

	/**
	 * load a new set of default properties and params for the element
	 *
	 * @param   array $properties Default props
	 *
	 * @return  FabrikTableElement    element (id = 0)
	 */
	public function getDefaultProperties($properties = array())
	{
		$item         = parent::getDefaultProperties();
		$item->hidden = 1;

		return $item;
	}

	/**
	 * convert XML format data into fabrik data (used by web services)
	 *
	 * @param   mixed $v Data
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
	 * @param   bool   $normal    Is the filter a normal (true) or advanced filter
	 * @param   string $container Container
	 *
	 * @return  void
	 */
	public function filterJS($normal, $container)
	{
		$element = $this->getElement();
		$type    = $this->getFilterType();

		if ($normal && ($type !== 'field' && $type !== 'range'))
		{
			return;
		}

		$params                        = $this->getParams();
		$id                            = $this->getFilterHtmlId(0);
		$id2                           = $this->getFilterHtmlId(1);
		$opts                          = new stdClass;
		$opts->calendarSetup           = $this->_CalendarJSOpts($id);
		$opts->calendarSetup->ifFormat = $params->get('date_table_format', 'Y-m-d');
		$opts->calendarSetup->ifFormat = FabDate::dateFormatToStrftimeFormat($opts->calendarSetup->ifFormat);
		$opts->type    = $type;
		$opts->ids     = $type == 'field' ? array($id) : array($id, $id2);
		$opts->buttons = $type == 'field' ? array($id . '_cal_img') : array($id . '_cal_img', $id2 . '_cal_img');
		$opts          = json_encode($opts);
		$script        = 'Fabrik.filter_' . $container . '.addFilter(\'' . $element->plugin . '\', new DateFilter(' . $opts . '));' . "\n";
		Html::calendar();

		if ($normal)
		{
			Html::script('plugins/fabrik_element/date/filter.js');

			return $script;
		}
		else
		{
			Html::script('plugins/fabrik_element/date/filter.js', $script);
		}
	}

	/**
	 * Get calendar filter widget options
	 *
	 * @return array  options
	 */
	protected function filterCalendarOpts()
	{
		$params  = $this->getParams();
		$class   = $this->filterClass();
		$calOpts = array('class' => $class, 'maxlength' => '19', 'size' => 16);

		if ($params->get('date_allow_typing_in_field', true) == false)
		{
			$calOpts['readonly'] = 'readonly';
		}

		return $calOpts;
	}

	/**
	 * Get the class to manage the form element
	 * to ensure that the file is loaded only once
	 *
	 * @param   array  &$srcs  Scripts previously loaded
	 * @param   string $script Script to load once class has loaded
	 * @param   array  &$shim  Dependant class names to load before loading the class - put in requirejs.config shim
	 *
	 * @return void
	 */
	public function formJavascriptClass(&$srcs, $script = '', &$shim = array())
	{
		$key = Html::isDebug() ? 'element/date/date' : 'element/date/date-min';
		// Ensure that we keep advanced dependencies from previous date elements regardless of current elements settings.
		$deps   = array_key_exists($key, $shim) ? $shim[$key]->deps : array();
		$params = $this->getParams();

		if ($params->get('date_advanced', '0') == '1' && !in_array('lib/datejs/date', $deps))
		{
			$deps[] = 'lib/datejs/globalization/' . JFactory::getLanguage()->getTag();
			$deps[] = 'lib/datejs/core';
			$deps[] = 'lib/datejs/parser';
			$deps[] = 'lib/datejs/extras';
		}

		if ($params->get('date_which_time_picker', 'wicked') === 'clock')
		{
			$pickerDep = Html::isDebug() ? 'lib/clockpicker/bootstrap-clockpicker' : 'lib/clockpicker/bootstrap-clockpicker.min';
		}
		else
		{
			$pickerDep = Html::isDebug() ? 'lib/wickedpicker/wickedpicker' : 'lib/wickedpicker/wickedpicker.min';
		}

		if (!in_array($pickerDep, $deps))
		{
			$deps[] = $pickerDep;
		}

		if (count($deps) > 0)
		{
			$s          = new stdClass;
			$s->deps    = $deps;
			$shim[$key] = $s;
		}

		parent::formJavascriptClass($srcs, $script, $shim);

		// Return false, as we need to be called on per-element (not per-plugin) basis
		return false;
	}

	/**
	 * Get the Front end JS default date
	 *
	 * @param   array $data Form data
	 *
	 * @return string
	 */
	public function getFrontDefaultValue($data = array())
	{
		$params       = $this->getParams();
		$db           = JFactory::getDbo();
		$alwaysToday  = $params->get('date_alwaystoday', false);
		$defaultToday = $params->get('date_defaulttotoday', false);

		if ($alwaysToday || $defaultToday)
		{
			$this->default = JHtml::_('date', 'now', $db->getDateFormat());
		}
		else
		{
			$this->default = parent::getDefaultValue($data);
		}

		return $this->default;
	}

	/*
	 * If date is stored as UTC, wrap the necessary CONVERT_TZ() around the key name to offset it, so 'foo'
	 * becomes 'CONVERT_TZ(foo, "+0:00", "+5:00")'.  If storing as local, leave key intact.  Used when building things
	 * like pre-filter queries for "today".
	 */
	protected function addConvert($key)
	{
		$params       = $this->getParams();
		$storeAsLocal = (int) $params->get('date_store_as_local', 0);

		if ($params->get('date_store_as_local', '0') !== '1')
		{
			$timeZone = new DateTimeZone($this->config->get('offset'));
			$zoneDate = new DateTime('now', $timeZone);
			$tzStr    = $zoneDate->format('P');
			$key = 'CONVERT_TZ(' . $key . ', "+0:00", "' . $tzStr . '")';
		}

		return $key;
	}

	/**
	 * Internal element validation
	 *
	 * For the date element, this is just here to add the element to the 'modified' array for AJAX validation,
	 * so it gets re-displayed.  Need to do this because we change the displayed date format when submitting (or on
	 * change page in multipage forms, etc).
	 *
	 * @param   array $data          form data
	 * @param   int   $repeatCounter repeat group counter
	 *
	 * @return bool
	 */
	public function validate($data, $repeatCounter = 0)
	{
		if ($this->app->input->get('fabrik_ajax', '0') === '1')
		{
			if (FabrikWorker::isDate($data))
			{
				$params    = $this->getParams();
				$localDate = $this->displayDate($data);
				$formModel = $this->getFormModel();
				$name      = $this->getFullName(true, false);
				$group     = $this->getGroup();

				if ($group->canRepeat())
				{
					$formModel->modifiedValidationData[$name][$repeatCounter] = $localDate->format('Y-m-d H:i:s', true);
				}
				else
				{
					$formModel->modifiedValidationData[$name] = $localDate->format('Y-m-d H:i:s', true);
				}
			}
		}

		return true;
	}

	/**
	 * Get the date_form_format, attempt to strip any time formatting
	 *
	 * @return string
	 */
	private function getFormFormat()
	{
		$params = $this->getParams();
		$format = $params->get('date_form_format', 'Y-m-d');

		// attempt to strip a few of the more common time formats
		$format = preg_replace('/\s+(H:i:s|H:i|g:i:s A|g:i A|g:i:s a|g:i a)$/', '', $format);

		return $format;
	}

	/**
	 * Get the time format according to time settings
	 *
	 * @return mixed|string
	 */
	private function getTimeFormat()
	{
		$params = $this->getParams();
		$timeFormat = '';

		if ($params->get('date_showtime', '0'))
		{
			if ($params->get('date_24hour', '1'))
			{
				if ($params->get('date_which_time_picker', 'nunbers') !== 'clock'
					&& $params->get('date_show_seconds', '1'))
				{
					$timeFormat = 'H:i:s';
				}
				else
				{
					$timeFormat = 'H:i';
				}
			}
			else
			{
				if ($params->get('date_which_time_picker', 'wicked') !== 'clock'
					&& $params->get('date_show_seconds', '1'))
				{
					$timeFormat = 'g:i:s A';
				}
				else
				{
					$timeFormat = 'g:i A';
				}
			}
		}
		else if ((bool) $this->getElement()->hidden)
		{
			$timeFormat = 'H:i:s';
		}

		return $timeFormat;
	}
}

/**
 * very small override to JDate to stop 500 errors occurring (when Jdebug is on) if $date is not a valid date string
 *
 * @package  Fabrik
 * @since    3.0
 */
class FabDate extends JDate
{
	/**
	 * GMT Date
	 *
	 * @var DateTimeZone
	 */
	protected static $gmt;

	/**
	 * Default tz date
	 *
	 * @var DateTimeZone
	 */
	protected static $stz;

	/**
	 * Construct
	 *
	 * @param   string $date Date
	 * @param   mixed  $tz   Timezone
	 */
	public function __construct($date = 'now', $tz = null)
	{
		$app  = JFactory::getApplication();
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
			JDEBUG ? $app->enqueueMessage('date format unknown for ' . $orig . ' replacing with today\'s date', 'notice') : '';
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
	 * @param   string $str String to remove - from
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
	 * @param   string $str Month name
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
					$str      = JString::str_ireplace($month, $monthNum, $str);
				}
			}
		}

		return $str;
	}

	/**
	 * Converts strftime format into PHP date() format
	 *
	 * @param   string $format  Strftime format
	 *
	 * @since   3.0.7
	 *
	 * @return  string  converted format
	 */
	static public function strftimeFormatToDateFormat($format)
	{
		$app = JFactory::getApplication();

		if (strstr($format, '%C'))
		{
			$app->enqueueMessage('Cant convert %C strftime date format to date format, substituted with Y', 'notice');

			return;
		}

		$search = array('%e', '%j', '%u', '%V', '%W', '%h', '%B', '%C', '%g', '%G', '%M', '%P', '%r', '%R', '%T', '%X', '%z', '%Z', '%D', '%F', '%s',
			'%x', '%A', '%Y', '%m', '%d', '%H', '%S');

		$replace = array('j', 'z', 'w', 'W', 'W', 'M', 'F', 'Y', 'y', 'Y', 'i', 'a', '"g:i:s a', 'H:i', 'H:i:s', 'H:i:s', 'O', 'O', 'm/d/y"', 'Y-m-d', 'U',
			'Y-m-d', 'l', 'Y', 'm', 'd', 'H', 's');

		return str_replace($search, $replace, $format);
	}

	/**
	 * Convert strftime to PHP time format
	 *
	 * @param   string  $format Format
	 *
	 * @return  string  converted format
	 */
	static public function dateFormatToStrftimeFormat($format)
	{
		$trs = array(
			'd' => '%d',
			'D' => '%a',
			'j' => '%e',
			'l' => '%A',
			'N' => '%u',
			'S' => '',
			'w' => '%w',
			'z' => '%j',
			'W' => '%V',
			'F' => '%B',
			'm' => '%m',
			'M' => '%b',
			'n' => '%m',
			't' => '',
			'L' => '',
			'o' => '%g',
			'Y' => '%Y',
			'y' => '%y',
			'a' => '%P',
			'A' => '%p',
			'B' => '',
			'g' => '%l',
			'G' => '%H',
			'h' => '%I',
			'H' => '%H',
			'i' => '%M',
			's' => '%S',
			'e' => '%z',
			'u' => '',
			'I' => '',
			'O' => '',
			'P' => '',
			'T' => '%z',
			'Z' => '',
			'c' => '%c',
			'r' => '%a, %d %b %Y %H:%M:%S %z',
			'U' => '%s'
		);

		return strtr($format, $trs);
	}

	/**
	 * Strip days
	 *
	 * @param   string $str Date string
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
