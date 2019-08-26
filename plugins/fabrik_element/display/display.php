<?php
/**
 * Plugin element to render plain text/HTML
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.display
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

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
	 * @var bool
	 */
	protected $recordInDatabase = false;

	/**
	 * Set/get if element should record its data in the database
	 *
	 * @deprecated - not used
	 *
	 * @return bool
	 */

	public function setIsRecordedInDatabase()
	{
		$this->recordInDatabase = false;
	}
	
	/*
	As of MySQL 5.7.6, CREATE TABLE supports the specification of generated columns. 
	Values of a generated column are computed from an expression included in the column definition.
	Generated columns are supported by the NDB storage engine beginning with MySQL NDB Cluster 7.5.3
	Generated columns can be added.
	The data type and expression of generated columns can be modified.
	Generated columns can be renamed or dropped, if no other column refers to them.
	Virtual generated columns cannot be altered to stored generated columns, or vice versa. To work around this, drop the column, then add it with the new definition.
	Nongenerated columns can be altered to stored but not virtual generated columns.
	Stored but not virtual generated columns can be altered to nongenerated columns. The stored generated values become the values of the nongenerated column.
	
	*/
	public function getFieldDescription()
	{
		$params = $this->getParams();
		$as = $params->get('generated_expression');
		$virtualOrStored = $params->get('virtual_or_stored');
		
		$desc = $this->fieldDesc;
		
		if (!empty($as) && !empty($virtualOrStored))
		{
			$desc .= ' GENERATED ALWAYS AS (' . $as . ') ' . $virtualOrStored;
		}
		
		return $desc;
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
			$element->label = parent::getValue(array());
			$element->label_raw = $element->label;
		}

		return parent::getLabel($repeatCounter, $tmpl);
	}

	/**
	 * Get the element's raw label (used for details view, not wrapped in <label> tags
	 *
	 * @return  string  Label
	 */
	protected function getRawLabel()
	{
		if (!$this->getParams()->get('display_showlabel', true))
		{
			return parent::getValue(array());
		}

		return parent::getRawLabel();
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

        unset($this->default);
		$value = null;
		$defeval = null;
		$parent = null;
		$sep = null;
		$w = new FabrikWorker;
		
		// Jaanus: shows data from the corresponding db field and/or default/eval data
		
		if (in_array($this->getParams()->get('display_showdata', 1), array(1,2)))
        {
			$value = $w->JSONtoData($data, true);
			$value = $w->parseMessageForPlaceHolder($value, $thisRow);
			$parent = parent::renderListData($value, $thisRow, $opts);
			//$parent = $w->parseMessageForPlaceHolder($parent, $thisRow);
		}
		
		if (in_array($this->getParams()->get('display_showdefault', 1), array(1,2)))
        {
			$defeval = $this->getDefaultOnACL(ArrayHelper::fromObject($thisRow), array()) . '';
		}
		
		if (!empty($value) && !empty($defeval))
		{
			$sep = $this->getParams()->get('display_separator', 'hr');
			if (in_array($sep, array('hr', 'br')))
			{
				$sep = '<' . $sep . ' />';
			}
		}

		return $parent . $sep . $defeval;
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
		$params = $this->getParams();
		$layout = $this->getLayout('form');
		$displayData = new stdClass;
		$displayData->id = $this->getHTMLId($repeatCounter);
		$displayData->value = $params->get('display_showlabel', true) ? $this->getValue($data, $repeatCounter) : $this->getDefaultOnACL($data, array());
		$displayData->label = !$params->get('display_showlabel', true) ? parent::getValue($data, $repeatCounter) : '';

		return $layout->render($displayData);
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
		return FArrayHelper::getValue($opts, 'use_default', true) == false ? '' : $this->getDefaultValue($data);
	}

	/**
	 * Determines the value for the element in the form view
	 *
	 * @param   array  $data           Form data
	 * @param   int    $repeatCounter  When repeating joined groups we need to know what part of the array to access
	 * @param   array  $opts           Options
	 *
	 * @return  string	value
	 */

	public function getValue($data, $repeatCounter = 0, $opts = array())
	{
		$params = $this->getParams();
		
		$d = null;
		$sep = null;
		$v = null;
		
		// Jaanus: shows data from the corresponding db field and/or default/eval data

		if (in_array($params->get('display_showdata', 1), array(1,3)))
		{
			$d =  parent::getValue($data, $repeatCounter, $opts);
		}
		
		if (in_array($params->get('display_showdefault', 1), array(1,3)))
		{
			$v =  $this->getDefaultOnACL($data, array());
		}
		
		if (!empty($d) && !empty($v))
		{
			$sep = $this->getParams()->get('display_separator', 'hr');
			if (in_array($sep, array('hr', 'br')))
			{
				$sep = '<' . $sep . ' />';
			}
		}
		
		$value = $d . $sep . $v;

		if ($value === '')
		{
			// Query string for joined data
			$value = FArrayHelper::getValue($data, $value);
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
