<?php
/**
 * Form Field class for showing hidden parameters
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

jimport('joomla.form.formfield');

/**
 * Hidden params
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       1.6
 */

class JFormFieldHiddenparams extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'Hiddenparams';

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string	The field input markup.
	 * 
	 * @since	1.6
	 */

	protected function getInput()
	{
		// Initialize some field attributes.
		$class = $this->element['class'] ? ' class="' . (string) $this->element['class'] . '"' : '';
		$disabled = ((string) $this->element['disabled'] == 'true') ? ' disabled="disabled"' : '';

		// Initialize JavaScript field attributes.
		$onchange = $this->element['onchange'] ? ' onchange="' . (string) $this->element['onchange'] . '"' : '';
		$this->value = json_encode($this->value);
		return '<input type="hidden" name="' . $this->name . '" id="' . $this->id . '"' .
				' value="' . htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') . '"' .
				$class . $disabled . $onchange . ' />';
	}
}
