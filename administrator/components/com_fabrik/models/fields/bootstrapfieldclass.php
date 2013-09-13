<?php
/**
 * Renders a list of Bootstrap field class sizes
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

JFormHelper::loadFieldClass('list');

/**
 * Renders a list of Bootstrap field class sizes
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       1.5
 */

class JFormFieldBootstrapfieldclass extends JFormFieldList
{

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 */

	protected function getOptions()
	{
		$sizes = array();
		$sizes[] = JHTML::_('select.option', 'input-mini');
		$sizes[] = JHTML::_('select.option', 'input-small');
		$sizes[] = JHTML::_('select.option', 'input-medium');
		$sizes[] = JHTML::_('select.option', 'input-large');
		$sizes[] = JHTML::_('select.option', 'input-xlarge');
		$sizes[] = JHTML::_('select.option', 'input-xxlarge');
		$sizes[] = JHTML::_('select.option', 'input-block-level');
		return $sizes;
	}
}
