<?php
/**
 * Plugin element to render time dropdowns - derivated from birthday element
 * @package fabrikar
 * @author Jaanus Nurmoja
 * @copyright (C) www.fabrikar.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

//jimport('joomla.application.component.model');

class plgFabrik_ElementTime extends plgFabrik_Element
{

	public $hasSubElements = true;

	protected $fieldDesc = 'TIME';

	/**
	 * draws the form element
	 * @param array data to preopulate element with
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render($data, $repeatCounter = 0)
	{
		$db = JFactory::getDbo();
		$name = $this->getHTMLName($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		$params = $this->getParams();
		$element = $this->getElement();
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
		$sep = $params->get('time_separatorlabel', JText::_(':'));
		$fd = $params->get('details_time_format', 'H:i:s');
		if (!$this->_editable)
		{
			if ($value)
			{
				//avoid 0000-00-00
				list($hour, $min, $sec) = strstr($value, ':') ? explode(':', $value) : explode(',', $value);
				// $$$ rob - all this below is nice but ... you still need to set a default
				$detailvalue= '';
				if ($fd == 'H:i:s') {
					$detailvalue = $hour . $sep . $min . $sep . $sec;
				}
				else
				{
					if ($fd == 'H:i') {
						$detailvalue = $hour . $sep . $min;
					}
					if ($fd == 'i:s') {
						$detailvalue = $min . $sep . $sec;
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
			$value = strstr($value, ',') ? array_reverse(explode(',', $value)) : explode(':', $value);
			$hourvalue = JArrayHelper::getValue($value, 0);
			$minvalue = JArrayHelper::getValue($value, 1);
			$secvalue = JArrayHelper::getValue($value, 2);

			$hours = array(JHTML::_('select.option', '', $params->get('time_hourlabel', JText::_('HOUR'))));
			for ($i = 0; $i < 24; $i++)
			{
				$v = str_pad($i,2,'0',STR_PAD_LEFT);
				$hours[] = JHTML::_('select.option', $v, $i);
			}
			$mins = array(JHTML::_('select.option', '', $params->get('time_minlabel', JText::_('MINUTE'))));
			//siin oli enne $monthlabels, viisin ülespoole
			for ($i = 0; $i < 60; $i++)
			{
				$i = str_pad($i,2,'0',STR_PAD_LEFT);
				$mins[] = JHTML::_('select.option', $i);
			}
			$secs = array(JHTML::_('select.option', '', $params->get('time_seclabel', JText::_('SECOND'))));
			for ($i = 0; $i < 60; $i++)
			{
				$i = str_pad($i,2,'0',STR_PAD_LEFT);
				$secs[] = JHTML::_('select.option', $i);
			}
			$errorCSS = (isset($this->_elementError) && $this->_elementError != '') ? " elementErrorHighlight" : '';
			$attribs = 'class="fabrikinput inputbox'.$errorCSS.'"';
			$str = array();
			$str[] = '<div class="fabrikSubElementContainer" id="'.$id.'">';
			//$name already suffixed with [] as element hasSubElements = true
			if ($fd != 'i:s') {
			$str[] = JHTML::_('select.genericlist', $hours, preg_replace('#(\[\])$#','[0]',$name), $attribs, 'value', 'text', $hourvalue) . ' ' . $sep;
			}
			$str[] = JHTML::_('select.genericlist', $mins, preg_replace('#(\[\])$#','[1]',$name), $attribs, 'value', 'text', $minvalue);
			if ($fd != 'H:i') {
			$str[] = $sep . ' ' .JHTML::_('select.genericlist', $secs, preg_replace('#(\[\])$#','[2]',$name), $attribs, 'value', 'text', $secvalue);
			}
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
					//$data = str_replace(null, '', $data); just tried to make null data visible for rep. group

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
					if (!is_array($data)) {
						$value = $data;
					} else {
						$value = JArrayHelper::getValue($data, $name, JArrayHelper::getValue($data, $rawname, $value));
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
		if (is_array($val) && implode($val) != '') {
		return str_replace('', '00', $val[0]) . ':' . str_replace('', '00', $val[1]) . ':' . str_replace('', '00', $val[2]);
		}
	}

	/**
	 * used in isempty validation rule
	 *
	 * @param array $data
	 * @return bol
	 */

	function dataConsideredEmpty($data, $repeatCounter)
	{
		$data = str_replace(null,'',$data);
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
		return "new FbTime('$id', $opts)";
	}

	function renderListData($data, $oAllRowsData)
	{
		$db = FabrikWorker::getDbo();
		$params = $this->getParams();
		$groupModel = $this->getGroup();
		$data = $groupModel->canRepeat() ? FabrikWorker::JsonToData($data) : array($data);
		$data = (array)$data;
		$ft = $params->get('list_time_format', 'H:i:s');
		$sep = $params->get('time_separatorlabel', JText::_(':'));
		$format = array();

		foreach ($data as $d) {
			if ($d)
			{
				list($hour, $min, $sec) = explode(':', $d);
				$hms = $hour . $sep . $min . $sep . $sec;
				$hm = $hour . $sep . $min;
				$ms = $min . $sep . $sec;
				if ($ft == "d.m.Y")
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
		return parent::renderListData($data, $oAllRowsData);
	}

}
?>