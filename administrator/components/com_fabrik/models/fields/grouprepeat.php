<?php
/**
 * Renders a radio group but only if the fabrik group is assigned to a form
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_ADMINISTRATOR . '/components/com_fabrik/helpers/element.php';

JFormHelper::loadFieldClass('radio');

/**
 * Renders a radio group but only if the fabrik group is assigned to a form
 * see: https://github.com/Fabrik/fabrik/issues/95
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       1.6
 */

class JFormFieldGrouprepeat extends JFormFieldRadio
{
	/**
	 * Element name
	 *
	 * @var		string
	 */
	protected $name = 'Grouprepeat';

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string	The field input markup.
	 */

	/**
	 * Method to get the field input markup.
	 *
	 * @return	string	The field input markup.
	 */

	protected function getInput()
	{
		if ((int) $this->form->getValue('form') === 0)
		{
			return '<input class="readonly" size="60" value="' . FText::_('COM_FABRIK_FIELD_ASSIGN_GROUP_TO_FORM_FIRST') . '" type="readonly" />';
		}
		else
		{
			return parent::getInput();
		}
	}
}
