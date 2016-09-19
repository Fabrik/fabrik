<?php
/**
 * Renders a list of elements found in the current group
 * for use in setting the element's order
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');
require_once JPATH_ADMINISTRATOR . '/components/com_fabrik/helpers/element.php';

/**
 * Renders a list of elements found in the current group
 * for use in setting the element's order
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       1.6
 */

class JFormFieldSpecificordering extends JFormFieldList
{
	/**
	 * Element name
	 * @access	protected
	 * @var		string
	 */
	protected $name = 'Specificordering';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 */

	protected function getOptions()
	{
		// ONLY WORKS INSIDE ELEMENT :(
		$db = FabrikWorker::getDbo();
		$group_id = $this->form->getValue('group_id');
		$query = "SELECT ordering AS value, name AS text" . "\n FROM #__{package}_elements " . "\n WHERE group_id = " . (int) $group_id
			. "\n AND published >= 0" . "\n ORDER BY ordering";
		/**
		 * $$$ rob - rather than trying to override the JHTML class lets
		 * just swap {package} for the current package.
		 */
		$query = FabrikWorker::getDbo(true)->replacePrefix($query);

		return JHTML::_('list.genericordering', $query);
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string	The field input markup.
	 */

	protected function getInput()
	{
		$id = $this->form->getValue('id');

		if ($id)
		{
			// Get the field options.
			$options = (array) $this->getOptions();
			$ordering = JHTML::_('select.genericlist', $options, $this->name, 'class="inputbox" size="1"', 'value', 'text', $this->value);
		}
		else
		{
			$text = FText::_('COM_FABRIK_NEW_ITEMS_LAST');
			$ordering = '<input type="text" size="40" readonly="readonly" class="readonly" name="' . $this->name . '" value="' . $this->value . $text
				. '" />';
		}

		return $ordering;
	}
}
