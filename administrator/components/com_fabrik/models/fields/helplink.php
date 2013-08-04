<?php
/**
 * Create a list from an SQL query
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0.9
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Renders a Fabrik Help link
 *
 * @package  Fabrik
 * @since    3.0.9
 */

//JFormHelper::loadFieldClass('spacer');

class JFormFieldHelpLink extends JFormField
{

	/**
	 * Return blank label
	 *
	 * @return  string  The field label markup.
	 */

	protected function getLabel()
	{
		return '';
	}

	/**
	 * Get the input - a right floated help icon
	 *
	 * @return string
	 */

	public function getInput()
	{
		$url = $this->element['url'] ? (string) $this->element['url'] : '';
		$label = '<div style="float:right;"><a class="btn btn-small btn-info" href="#" rel="help" onclick="Joomla.popupWindow(\'' .  JText::_($url). '\', \'Help\', 800, 600, 1);return false"><i class="icon-help icon-32-help icon-question-sign"></i> ' . JText::_('JHELP') . '</a></div>';
		return $label;
	}
}
