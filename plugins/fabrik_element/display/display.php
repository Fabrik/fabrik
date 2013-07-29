<?php
/**
 * Plugin element to render plain text/HTML
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.display
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * Plugin element to render plain text/HTML
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.display
 * @since       3.0
 */

class PlgFabrik_ElementDisplay extends PlgFabrik_Element
{

	/**
	 * Db table field type
	 *
	 * @var  string
	 */
	protected $fieldDesc = 'TEXT';

	/**
	 * Does the element's data get recorded in the db
	 *
	 * @var bol
	 */
	protected $recordInDatabase = false;

	/**
	 * Set/get if element should record its data in the dabase
	 *
	 * @deprecated - not used
	 *
	 * @return bool
	 */

	function setIsRecordedInDatabase()
	{
		$this->recordInDatabase = false;
	}

	/**
	 * Get the element's HTML label
	 *
	 * @param   int     $repeatCounter  Group repeat counter
	 * @param   string  $tmpl           Form template
	 *
	 * @return  string  label
	 */

	public function getLabel($repeatCounter = 0, $tmpl = '')
	{
		$params = $this->getParams();
		$element = $this->getElement();
		if (!$params->get('display_showlabel', true))
		{
			$element->label = $this->getValue(array());
		}
		return parent::getLabel($repeatCounter, $tmpl);
	}

	/**
	 * Shows the data formatted for the list view
	 *
	 * @param   string  $data      Elements data
	 * @param   object  &$thisRow  All the data in the lists current row
	 *
	 * @return  string	formatted value
	 */

	public function renderListData($data, &$thisRow)
	{
		unset($this->_default);
		$value = $this->getValue(JArrayHelper::fromObject($thisRow));
		return parent::renderListData($value, $thisRow);
	}

	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           To preopulate element with
	 * @param   int    $repeatCounter  Repeat group counter
	 *
	 * @return  string	elements html
	 */

	public function render($data, $repeatCounter = 0)
	{
		$params = $this->getParams();
		$id = $this->getHTMLId($repeatCounter);
		$value = $params->get('display_showlabel', true) ? $this->getValue($data, $repeatCounter) : '';
		return '<div class="fabrikSubElementContainer" id="' . $id . '">' . $value . '</div>';
	}

	/**
	 * Helper method to get the default value used in getValue()
	 * Unlike other elements where readonly effects what is displayed, the display element is always
	 * read only, so get the default value.
	 *
	 * @param   array  $data  Form data
	 * @param   array  $opts  Options
	 *
	 * @since  3.0.7
	 *
	 * @return  mixed	value
	 */

	protected function getDefaultOnACL($data, $opts)
	{

		return JArrayHelper::getValue($opts, 'use_default', true) == false ? '' : $this->getDefaultValue($data);
	}

	/**
	 * Determines the value for the element in the form view
	 *
	 * @param   array  $data           Form data
	 * @param   int    $repeatCounter  When repeating joinded groups we need to know what part of the array to access
	 * @param   array  $opts           Options
	 *
	 * @return  string	value
	 */

	public function getValue($data, $repeatCounter = 0, $opts = array())
	{
		$element = $this->getElement();
		$params = $this->getParams();
		$value = $this->getDefaultOnACL($data, $opts);
		if ($value === '')
		{
			// Query string for joined data
			$value = JArrayHelper::getValue($data, $value);
		}
		$formModel = $this->getFormModel();

		// Stops this getting called from form validation code as it messes up repeated/join group validations
		if (array_key_exists('runplugins', $opts) && $opts['runplugins'] == 1)
		{
			FabrikWorker::getPluginManager()->runPlugins('onGetElementDefault', $formModel, 'form', $this);
		}
		return $value;
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
		return array('FbDisplay', $id, $opts);
	}

}
