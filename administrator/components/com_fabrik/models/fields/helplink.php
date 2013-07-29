<?php
/**
 * Create a list from an SQL query
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @since       3.0.9
 */

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();


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
		$label = '<ul><li class="button icon-32-help" style="float:right;width:32px;height:32px">
<a class="toolbar" rel="help" style="display:block;width:32px;padding-top:32px;text-align:center;" onclick="Joomla.popupWindow(\'' .  JText::_($url). '\', \'Help\', 700, 500, 1);return false" href="#">
<span class="icon-32-help">
</span>
Help
</a>
</li></ul>';
		return $label;
	}
}
