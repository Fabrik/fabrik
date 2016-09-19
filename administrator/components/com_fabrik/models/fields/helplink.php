<?php
/**
 * Renders a Fabrik Help link
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
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
		$js = 'Joomla.popupWindow(\'' . FText::_($url) . '\', \'Help\', 800, 600, 1);return false';
		$label = '<div style="float:right;">';
		$label .= '<a class="btn btn-small btn-info" href="#" rel="help" onclick="' . $js . '">';
		$label .= '<i class="icon-help icon-32-help icon-question-sign"></i> ' . FText::_('JHELP') . '</a></div>';

		return $label;
	}
}
