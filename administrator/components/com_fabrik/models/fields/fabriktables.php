<?php
/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

//required for menus
require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'html.php');
require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'params.php');
require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'string.php');
require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'parent.php');
require_once(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'element.php');

/**
 * Renders a list of fabrik or db tables
 *
 * @author 		Rob Clayburn
 * @package 	Joomla
 * @subpackage		Fabrik
 * @since		1.6
 */

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

class JFormFieldFabrikTables extends JFormFieldList

{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'Fabriktables';

	var $_array_counter = null;

	static $fabriktables;

	function getOptions()
	{
		if (!isset($fabriktables)) {
			$fabriktables = array();
		}
		$connectionDd = $this->element['observe'];
		$db	= FabrikWorker::getDbo(true);

		$id = $this->id;
		$fullName = $this->name;

		if ($connectionDd != '') {
			//we are not monitoring a connection drop down so load in all tables
			$query = $db->getQuery(true);
			$query->select("id AS value, label AS text")->from("#__{package}_lists")->where("published <> -2")->order("label ASC");
			$db->setQuery($query);
			$rows = $db->loadObjectList();
		} else {
			$rows = array(JHTML::_('select.option', '', JText::_('COM_FABRIK_SELECT_A_CONNECTION_FIRST'), 'value', 'text'));
		}
		return $rows;
	}

	function getInput()
	{
		//$c = ElementHelper::getRepeatCounter($this);
		$c = isset($this->form->repeatCounter) ? (int)$this->form->repeatCounter : 0;
		$connectionDd = $this->element['observe'];
		if (!isset($fabriktables)) {
			$fabriktables = array();
		}
		$script = array();
		if ($connectionDd != '' && !array_key_exists($this->id, $fabriktables)) {
			if ($this->form->repeat) {
				//in repeat fieldset/group
				$connectionDd =  $connectionDd . '-' . $this->form->repeatCounter;
			} else {
				$connectionDd = ($c === false || $this->element['connection_in_repeat'] == 'false') ?  $connectionDd :  $connectionDd . '-' . $c;
			}
			$opts = new stdClass();
			$opts->livesite = COM_FABRIK_LIVESITE;
			$opts->conn = 'jform_' . $connectionDd;
			$opts->value = $this->value;
			$opts->connInRepeat = (bool)$this->element['connection_in_repeat'][0];
			$opts->inRepeatGroup = $this->form->repeat;
			$opts->repeatCounter =  empty($this->form->repeatCounter) ? 0 : $this->form->repeatCounter;
			$opts->container = 'test';
			$opts = json_encode($opts);


			$script[] = "var p = new fabriktablesElement('$this->id', $opts);";
			$script[] = "FabrikAdmin.model.fields.fabriktable['$this->id'] = p;";
			//$script[] = "Fabrik.adminElements['$this->id'] = p;";
			$script = implode("\n", $script);

			$fabriktables[$this->id] = true;
			FabrikHelperHTML::script('administrator/components/com_fabrik/models/fields/fabriktables.js', $script);
		}

		$html = parent::getInput();
		$html .= "<img style=\"margin-left:10px;display:none\" id=\"".$this->id."_loader\" src=\"components/com_fabrik/images/ajax-loader.gif\" alt=\"" . JText::_('LOADING'). "\" />";
		$script = "<script type=\"text/javascript\">".$script."</script>";
		return $html;
	}

}