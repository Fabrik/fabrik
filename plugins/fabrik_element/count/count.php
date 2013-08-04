<?php
/**
 * Count Element
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.count
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

require_once JPATH_SITE . '/components/com_fabrik/models/element.php';

/**
 * Plugin element to:
 * Counts records in a row - so adds "COUNT(x) .... GROUP BY (y)" to the main db query
 *
 * Note implementing this element will mean that only the first row of data is returned in
 * the joined group
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.count
 * @since       3.0
 */

class PlgFabrik_ElementCount extends PlgFabrik_Element
{

	/**
	 * Get group by query
	 * @see PlgFabrik_Element::getGroupByQuery()
	 */
	public function getGroupByQuery()
	{
		$params = $this->getParams();
		return $params->get('count_groupbyfield');
	}

	/**
	 * Create the SQL select 'name AS alias' segment for list/form queries
	 *
	 * @param   array  &$aFields    array of element names
	 * @param   array  &$aAsFields  array of 'name AS alias' fields
	 * @param   array  $opts        options
	 *
	 * @return  void
	 */

	public function getAsField_html(&$aFields, &$aAsFields, $opts = array())
	{
		$dbtable = $this->actualTableName();
		$app = JFactory::getApplication();
		$db = FabrikWorker::getDbo();
		if ($app->input->get('c') != 'form')
		{
			$params = $this->getParams();
			$fullElName = JArrayHelper::getValue($opts, 'alias', $db->quoteName($dbtable . '___' . $this->getElement()->name));
			$r = 'COUNT(' . $params->get('count_field', '*') . ')';
			$aFields[] = $r . ' AS ' . $fullElName;
			$aAsFields[] = $fullElName;
			$aAsFields[] = $db->quoteName($dbtable . '___' . $this->getElement()->name . '_raw');
		}
	}

	/**
	 * Determines if the element can contain data used in sending receipts,
	 * e.g. fabrikfield returns true
	 *
	 * @deprecated - not used
	 *
	 * @return  bool
	 */

	public function isReceiptElement()
	{
		return false;
	}

	/**
	 * Check if the user can use the active element
	 *
	 * @param   object  &$model    calling the plugin list/form
	 * @param   string  $location  to trigger plugin on
	 * @param   string  $event     to trigger plugin on
	 *
	 * @return  bool can use or not
	 */

	public function canUse(&$model = null, $location = null, $event = null)
	{
		return false;
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
		return '';
		/*$name 			= $this->getHTMLName($repeatCounter);
		 $id 				= $this->getHTMLId($repeatCounter);
		 $params 		=& $this->getParams();
		 $element 		= $this->getElement();
		 $size 			= $element->width;

		 $bits = array();
		 // $$$ rob - not sure why we are setting $data to the form's data
		 //but in table view when getting read only filter value from url filter this
		 // _form_data was not set to no readonly value was returned
		 // added little test to see if the data was actually an array before using it
		 if (is_array($this->getFormModel()->data)) {
		    $data 	=& $this->getFormModel()->data;
		    }
		    $value 	= $this->getValue($data, $repeatCounter);
		    $type = "text";
		    if ($this->elementError != '') {
		    $type .= " elementErrorHighlight";
		    }
		    if ($element->hidden == '1') {
		    $type = "hidden";
		    }
		    if (!$this->isEditable()) {
		    return($element->hidden == '1') ? "<!-- " . $value . " -->" : $value;
		    }

		    $bits['class']		= "fabrikinput inputbox $type";
		    $bits['type']		= $type;
		    $bits['name']		= $name;
		    $bits['id']			= $id;

		    //stop "'s from breaking the content out of the field.
		    // $$$ rob below now seemed to set text in field from "test's" to "test&#039;s" when failed validation
		    //so add false flag to ensure its encoded once only
		    // $$$ hugh - the 'double encode' arg was only added in 5.2.3, so this is blowing some sites up
		    if (version_compare( phpversion(), '5.2.3', '<')) {
		    $bits['value']		= htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
		    }
		    else {
		    $bits['value']		= htmlspecialchars($value, ENT_COMPAT, 'UTF-8', false);
		    }
		    $bits['size']		= $size;


		    //cant be used with hidden element types
		    if ($element->hidden != '1') {
		    if ($params->get('readonly')) {
		    $bits['readonly'] = "readonly";
		    $bits['class'] .= " readonly";
		    }
		    if ($params->get('disable')) {
		    $bits['class'] .= " disabled";
		    $bits['disabled'] = 'disabled';
		    }
		    }
		    $str = "<input ";
		    foreach ($bits as $key=>$val) {
		    $str.= "$key = \"$val\" ";
		    }
		    $str .= " />\n";
		    return $str;*/
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
		$id = $this->getHTMLId($repeatCounter);
		$opts = $this->getElementJSOptions($repeatCounter);
		return array('FbCount', $id, $opts);
	}

}
