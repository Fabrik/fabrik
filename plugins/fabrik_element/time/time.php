<?php
/**
 * Plugin element to render time dropdowns - derived from birthday element
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.time
 * @author      Jaanus Nurmoja <email@notknown.com>
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Plugin element to render time dropdowns - derived from birthday element
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.time
 * @since       3.0
 */

class PlgFabrik_ElementTime extends PlgFabrik_Element
{
	/**
	 * Does the element contain sub elements e.g checkboxes radiobuttons
	 *
	 * @var bool
	 */
	public $hasSubElements = true;

	/**
	 * Db table field type
	 *
	 * @var string
	 */
	protected $fieldDesc = 'TIME';

	/**
	 * Draws the form element
	 *
	 * @param   array  $data           Data to pre-populate element with
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return  string  returns element html
	 */

	public function render($data, $repeatCounter = 0)
	{
		$name = $this->getHTMLName($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		$params = $this->getParams();
		$element = $this->getElement();
		/*
		 * $$$ rob - not sure why we are setting $data to the form's data
		 * but in table view when getting read only filter value from url filter this
		 * _form_data was not set to no readonly value was returned
		 * added little test to see if the data was actually an array before using it
		 */
		$formModel = $this->getFormModel();

		if (is_array($formModel->data))
		{
			$data = $formModel->data;
		}

		$value = $this->getValue($data, $repeatCounter);
		$sep = $params->get('time_separatorlabel', FText::_(':'));
		$fd = $params->get('details_time_format', 'H:i:s');

		if (!$this->isEditable())
		{
			if ($value)
			{
				// Avoid 0000-00-00
				if (is_string($value))
				{
					$bits = strstr($value, ':') ? explode(':', $value) : explode(',', $value);
				}
				else
				{
					$bits = $value;
				}

				$hour = FArrayHelper::getValue($bits, 0, '00');
				$min = FArrayHelper::getValue($bits, 1, '00');
				$sec = FArrayHelper::getValue($bits, 2, '00');

				// $$$ rob - all this below is nice but ... you still need to set a default
				$detailvalue = '';

				if ($fd == 'H:i:s')
				{
					$detailvalue = $hour . $sep . $min . $sep . $sec;
				}
				else
				{
					if ($fd == 'H:i')
					{
						$detailvalue = $hour . $sep . $min;
					}

					if ($fd == 'i:s')
					{
						$detailvalue = $min . $sep . $sec;
					}
				}

				$value = $this->replaceWithIcons($detailvalue);

				return ($element->hidden == '1') ? "<!-- " . $detailvalue . " -->" : $detailvalue;
			}
			else
			{
				return '';
			}
		}
		else
		{
			// Weirdness for failed validation
			if (is_string($value))
			{
				$value = strstr($value, ',') ? (explode(',', $value)) : explode(':', $value);
			}

			$hourvalue = FArrayHelper::getValue($value, 0);
			$minvalue = FArrayHelper::getValue($value, 1);
			$secvalue = FArrayHelper::getValue($value, 2);

			$hours = array(JHTML::_('select.option', '', $params->get('time_hourlabel', FText::_('PLG_ELEMENT_TIME_SEPARATOR_HOUR'))));

			$time24h = $params->get('time_24h', '1') === '1';

			for ($i = 0; $i < 24; $i++)
			{
				$v = str_pad($i, 2, '0', STR_PAD_LEFT);

				if ($time24h)
				{
					$l = $v;
				}
				else
				{
					$l = date("ga", strtotime("$v:00"));
				}
				$hours[] = JHTML::_('select.option', $v, $l);
			}

			$mins = array(JHTML::_('select.option', '', $params->get('time_minlabel', FText::_('PLG_ELEMENT_TIME_SEPARATOR_MINUTE'))));
			$increment = (int) $params->get('minutes_increment', 1);

			// Siin oli enne $monthlabels, viisin ülespoole
			// google translation: "this was before the $monthlabels, took up the"
			for ($i = 0; $i < 60; $i += $increment)
			{
				$i = str_pad($i, 2, '0', STR_PAD_LEFT);
				$mins[] = JHTML::_('select.option', $i);
			}

			$secs = array(JHTML::_('select.option', '', $params->get('time_seclabel', FText::_('PLG_ELEMENT_TIME_SEPARATOR_SECOND'))));

			for ($i = 0; $i < 60; $i++)
			{
				$i = str_pad($i, 2, '0', STR_PAD_LEFT);
				$secs[] = JHTML::_('select.option', $i);
			}

			$layout = $this->getLayout('form');
			$layoutData = new stdClass;
			$layoutData->id = $id;
			$layoutData->name = $name;
			$layoutData->advancedClass = $this->getAdvancedSelectClass();
			$layoutData->errorCss = $this->elementError != '' ? " elementErrorHighlight" : '';;
			$layoutData->format = $fd;
			$layoutData->sep = $sep;
			$layoutData->hours = $hours;
			$layoutData->mins = $mins;
			$layoutData->secs = $secs;
			$layoutData->hourValue = $hourvalue;
			$layoutData->minValue = $minvalue;
			$layoutData->secValue = $secvalue;

			return $layout->render($layoutData);
		}
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
	 *
	 * @param   mixed  $val  (array normally but string on csv import or copy rows)
	 *
	 * @return  string  hh-mm-ss
	 */

	private function _indStoreDBFormat($val)
	{
		if (is_array($val))
		{
			$h = FArrayHelper::getValue($val, 0, '00');
			$m = FArrayHelper::getValue($val, 1, '00');
			$s = FArrayHelper::getValue($val, 2, '00');

			return $h . ':' . $m . ':' . $s;
		}

		return $val;
	}

	/**
	 * If the calculation query has had to convert the data to a machine format, use
	 * this function to convert back to human readable format. E.g. time element
	 * calcs in seconds but we'd want to convert back into h:m:s
	 *
	 * @param   array  &$rows  Calculation values
	 *
	 * @return  void
	 */

	protected function formatCalValues(&$rows)
	{
		foreach ($rows as &$row)
		{
			$seconds = $row->value;
			$h = (int) ($seconds / 3600);
			$m = (int) (($seconds - $h * 3600) / 60);
			$s = (int) ($seconds - $h * 3600 - $m * 60);
			$row->value = (($h) ? (($h < 10) ? ("0" . $h) : $h) : "00") . ":" . (($m) ? (($m < 10) ? ("0" . $m) : $m) : "00") . ":"
				. (($s) ? (($s < 10) ? ("0" . $s) : $s) : "00");
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
		$label = count($labels) == 0 ? "'calc' AS label" : 'CONCAT(' . implode(', " & " , ', $labels) . ')  AS label';
		$table = $listModel->getTable();
		$db = $listModel->getDb();
		$joinSQL = $listModel->buildQueryJoin();
		$whereSQL = $listModel->buildQueryWhere();
		$name = $this->getFullName(false, false);

		return 'SELECT SUM(substr(' . $name . ' FROM 1 FOR 2) * 60 * 60 + substr(' . $name . ' FROM 4 FOR 2) * 60
			+ substr(' . $name . ' FROM 7 FOR 2))  AS value, ' . $label . ' FROM '
				. $db->qn($table->db_table_name) . ' ' . $joinSQL . ' ' . $whereSQL;
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
		$label = count($labels) == 0 ? "'calc' AS label" : 'CONCAT(' . implode(', " & " , ', $labels) . ')  AS label';
		$item = $listModel->getTable();
		$joinSQL = $listModel->buildQueryJoin();
		$whereSQL = $listModel->buildQueryWhere();
		$name = $this->getFullName(false, false);
		$groupModel = $this->getGroup();
		$roundTo = (int) $this->getParams()->get('avg_round');

		$valueSelect = 'substr(' . $name . ' FROM 1 FOR 2) * 60 * 60 + substr(' . $name . ' FROM 4 FOR 2) * 60 + substr(' . $name . ' FROM 7 FOR 2)';

		// Element is in a joined column - lets presume the user wants to sum all cols, rather than reducing down to the main cols totals
		return "SELECT ROUND(AVG($valueSelect), $roundTo) AS value, $label FROM " . FabrikString::safeColName($item->db_table_name)
		. " $joinSQL $whereSQL";
	}

	/**
	 * Used in isempty validation rule
	 *
	 * @param   array  $data           Data
	 * @param   int    $repeatCounter  Repeat group counter
	 *
	 * @return bool
	 */

	public function dataConsideredEmpty($data, $repeatCounter)
	{
		$data = str_replace(null, '', $data);

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
		$opts->separator = $params->get('time_separatorlabel', ':');

		return array('FbTime', $id, $opts);
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

        $params = $this->getParams();
		$groupModel = $this->getGroup();
		/*
		 * Jaanus: removed condition canrepeat() from renderListData:
		 * weird result such as ["00:03:45","00 when not repeating but still join and merged. Using isJoin() instead
		 */
		$data = $groupModel->isJoin() ? FabrikWorker::JSONtoData($data, true) : array($data);
		$data = (array) $data;
		$ft = $params->get('list_time_format', 'H:i:s');
		$sep = $params->get('time_separatorlabel', FText::_(':'));
		$format = array();

		foreach ($data as $d)
		{
			if ($d)
			{
				$bits = explode(':', $d);
				$hour = FArrayHelper::getValue($bits, 0, '00');
				$min = FArrayHelper::getValue($bits, 1, '00');
				$sec = FArrayHelper::getValue($bits, 2, '00');
				$hms = $hour . $sep . $min . $sep . $sec;
				$hm = $hour . $sep . $min;
				$ms = $min . $sep . $sec;
				$timedisp = '';

				if ($ft == "H:i:s")
				{
					$timedisp = $hms;
				}
				else
				{
					if ($ft == "H:i")
					{
						$timedisp = $hm;
					}

					if ($ft == "i:s")
					{
						$timedisp = $ms;
					}
				}

				$format[] = $timedisp;
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
	 * Turn form value into email formatted value
	 *
	 * @param   mixed  $value          element value
	 * @param   array  $data           form data
	 * @param   int    $repeatCounter  group repeat counter
	 *
	 * @return  string  email formatted value
	 */

	protected function getIndEmailValue($value, $data = array(), $repeatCounter = 0)
	{
		if (is_array($value))
		{
			$params = $this->getParams();
			$sep    = $params->get('time_separatorlabel', ':');
			$value  = implode($sep, $value);
		}

		return $value;
	}

	/**
	 * Used to format the data when shown in the form's email
	 *
	 * @param   mixed $value         element's data
	 * @param   array $data          form records data
	 * @param   int   $repeatCounter repeat group counter
	 *
	 * @return  string    formatted value
	 */
	public function getEmailValue($value, $data = array(), $repeatCounter = 0)
	{
		return $this->getIndEmailValue($value, $data, $repeatCounter);
	}
}
