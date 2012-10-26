<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.time
 * @author      Jaanus Nurmoja <email@notknown.com>
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * Plugin element to render time dropdowns - derivated from birthday element
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.time
 * @since       3.0
 */

class plgFabrik_ElementTime extends plgFabrik_Element
{

	public $hasSubElements = true;

	/** @var  string  db table field type */
	protected $fieldDesc = 'TIME';

	/**
	 * Draws the form element
	 *
	 * @param   array  $data           Dat to prepopulate element with
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return  string  returns element html
	 */

	public function render($data, $repeatCounter = 0)
	{
		$db = JFactory::getDbo();
		$name = $this->getHTMLName($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		$params = $this->getParams();
		$element = $this->getElement();
		$bits = array();
		/*
		 * $$$ rob - not sure why we are setting $data to the form's data
		 * but in table view when getting read only filter value from url filter this
		 * _form_data was not set to no readonly value was returned
		 * added little test to see if the data was actually an array before using it
		 */
		if (is_array($this->getFormModel()->_data))
		{
			$data = $this->getFormModel()->_data;
		}
		$value = $this->getValue($data, $repeatCounter);
		$sep = $params->get('time_separatorlabel', JText::_(':'));
		$fd = $params->get('details_time_format', 'H:i:s');
		if (!$this->isEditable())
		{
			if ($value)
			{
				// Avoid 0000-00-00
				list($hour, $min, $sec) = strstr($value, ':') ? explode(':', $value) : explode(',', $value);

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
				$value = $this->_replaceWithIcons($detailvalue);
				return ($element->hidden == '1') ? "<!-- " . $detailvalue . " -->" : $detailvalue;
			}
			else
			{
				return '';
			}
		}
		else
		{
			// Wierdness for failed validaion
			$value = strstr($value, ',') ? (explode(',', $value)) : explode(':', $value);
			$hourvalue = JArrayHelper::getValue($value, 0);
			$minvalue = JArrayHelper::getValue($value, 1);
			$secvalue = JArrayHelper::getValue($value, 2);

			$hours = array(JHTML::_('select.option', '', $params->get('time_hourlabel', JText::_('HOUR'))));
			for ($i = 0; $i < 24; $i++)
			{
				$v = str_pad($i, 2, '0', STR_PAD_LEFT);
				$hours[] = JHTML::_('select.option', $v, $i);
			}
			$mins = array(JHTML::_('select.option', '', $params->get('time_minlabel', JText::_('MINUTE'))));

			// Siin oli enne $monthlabels, viisin ülespoole
			for ($i = 0; $i < 60; $i++)
			{
				$i = str_pad($i, 2, '0', STR_PAD_LEFT);
				$mins[] = JHTML::_('select.option', $i);
			}
			$secs = array(JHTML::_('select.option', '', $params->get('time_seclabel', JText::_('SECOND'))));
			for ($i = 0; $i < 60; $i++)
			{
				$i = str_pad($i, 2, '0', STR_PAD_LEFT);
				$secs[] = JHTML::_('select.option', $i);
			}
			$errorCSS = (isset($this->_elementError) && $this->_elementError != '') ? " elementErrorHighlight" : '';
			$attribs = 'class="fabrikinput inputbox' . $errorCSS . '"';
			$str = array();
			$str[] = '<div class="fabrikSubElementContainer" id="' . $id . '">';

			// $name already suffixed with [] as element hasSubElements = true
			if ($fd != 'i:s')
			{
				$str[] = JHTML::_('select.genericlist', $hours, preg_replace('#(\[\])$#', '[0]', $name), $attribs, 'value', 'text', $hourvalue) . ' '
					. $sep;
			}
			$str[] = JHTML::_('select.genericlist', $mins, preg_replace('#(\[\])$#', '[1]', $name), $attribs, 'value', 'text', $minvalue);
			if ($fd != 'H:i')
			{
				$str[] = $sep . ' '
					. JHTML::_('select.genericlist', $secs, preg_replace('#(\[\])$#', '[2]', $name), $attribs, 'value', 'text', $secvalue);
			}
			$str[] = '</div>';
			return implode("\n", $str);
		}
	}

	/**
	 * can be overwritten by plugin class
	 * determines the value for the element in the form view
	 *
	 * @param   array  $data           Data
	 * @param   int    $repeatCounter  When repeating joinded groups we need to know what part of the array to access
	 * @param   array  $opts           Options
	 *
	 * @return string value
	 */

	public function getValue($data, $repeatCounter = 0, $opts = array())
	{
		// @TODO rename $this->defaults to $this->values
		if (!isset($this->defaults))
		{
			$this->defaults = array();
		}
		if (!array_key_exists($repeatCounter, $this->defaults))
		{
			$groupModel = $this->getGroup();
			$joinid = $groupModel->getGroup()->join_id;
			$formModel = $this->getForm();

			// $$$rob - if no search form data submitted for the search element then the default
			// selection was being applied instead
			$value = JArrayHelper::getValue($opts, 'use_default', true) == false ? '' : $this->getDefaultValue($data);

			$name = $this->getFullName(false, true, false);
			$rawname = $name . "_raw";
			if ($groupModel->isJoin())
			{
				if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid]))
				{
					if ($groupModel->canRepeat())
					{
						if (array_key_exists($rawname, $data['join'][$joinid]) && array_key_exists($repeatCounter, $data['join'][$joinid][$rawname]))
						{
							$value = $data['join'][$joinid][$rawname][$repeatCounter];
						}
						else
						{
							if (array_key_exists($rawname, $data['join'][$joinid]) && array_key_exists($repeatCounter, $data['join'][$joinid][$name]))
							{
								$value = $data['join'][$joinid][$name][$repeatCounter];
							}
						}
					}
					else
					{
						$jVal = JArrayHelper::getValue($data['join'][$joinid], $name, $value);
						$value = JArrayHelper::getValue($data['join'][$joinid], $rawname, $jVal);

						/* $$$ rob if you have 2 tbl joins, one repeating and one not
						 * the none repeating one's values will be an array of duplicate values
						 * but we only want the first value
						 */
						if (is_array($value))
						{
							$value = array_shift($value);
						}
					}
				}
			}
			else
			{
				if (!is_array($data))
				{
					$value = $data;
				}
				else
				{
					$value = JArrayHelper::getValue($data, $name, JArrayHelper::getValue($data, $rawname, $value));
				}
			}

			if (is_array($value))
			{
				$value = implode(',', $value);
			}
			if ($value === '')
			{
				// Query string for joined data
				$value = JArrayHelper::getValue($data, $name, $value);
			}
			// @TODO perhaps we should change this to $element->value and store $element->default as the actual default value
			// Stops this getting called from form validation code as it messes up repeated/join group validations
			if (array_key_exists('runplugins', $opts) && $opts['runplugins'] == 1)
			{
				FabrikWorker::getPluginManager()->runPlugins('onGetElementDefault', $formModel, 'form', $this);
			}
			$this->defaults[$repeatCounter] = $value;
		}
		return $this->defaults[$repeatCounter];
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
		return $this->_indStoreDBFormat($val);
	}

	/**
	 * get the value to store the value in the db
	 *
	 * @param   mixed  $val  (array normally but string on csv import)
	 *
	 * @return  string  yyyy-mm-dd
	 */

	private function _indStoreDBFormat($val)
	{
		if (is_array($val) && implode($val) != '')
		{
			return str_replace('', '00', $val[0]) . ':' . str_replace('', '00', $val[1]) . ':' . str_replace('', '00', $val[2]);
		}
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
	 * @param   int  $repeatCounter  repeat group counter
	 *
	 * @return  string
	 */

	public function elementJavascript($repeatCounter)
	{
		$id = $this->getHTMLId($repeatCounter);
		$opts = $this->getElementJSOptions($repeatCounter);
		$opts = json_encode($opts);
		return "new FbTime('$id', $opts)";
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
		$db = FabrikWorker::getDbo();
		$params = $this->getParams();
		$groupModel = $this->getGroup();
		/*
		 * Jaanus: removed condition canrepeat() from renderListData:
		 * weird result such as ["00:03:45","00 when not repeating but still join and merged. Using isJoin() instead
		 */
		$data = $groupModel->isJoin() ? FabrikWorker::JSONtoData($data, true) : array($data);
		$data = (array) $data;
		$ft = $params->get('list_time_format', 'H:i:s');
		$sep = $params->get('time_separatorlabel', JText::_(':'));
		$format = array();

		foreach ($data as $d)
		{
			if ($d)
			{
				list($hour, $min, $sec) = explode(':', $d);
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
		return parent::renderListData($data, $thisRow);
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

	protected function _getEmailValue($value, $data = array(), $repeatCounter = 0)
	{
		$params = $this->getParams();
		$sep = $params->get('time_separatorlabel', JText::_(':'));
		$value = implode($sep, $value);
		return $value;
	}

}
