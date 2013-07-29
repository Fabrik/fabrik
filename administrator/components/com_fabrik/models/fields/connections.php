<?php
/**
 * Renders a list of connections
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

require_once JPATH_ADMINISTRATOR . '/components/com_fabrik/helpers/element.php';

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

/**
 * Renders a list of connections
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       3.0
 */

class JFormFieldConnections extends JFormFieldList
{
	/**
	 * Element name
	 *
	 * @var		string
	 */
	var $_name = 'Connections';

	/**
	 * Get list options
	 *
	 * @return  array  options
	 */

	protected function getOptions()
	{

		// Initialize variables.
		$options = array();
		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);

		$query->select('id AS value, description AS text, ' . $db->quoteName('default'));
		$query->from('#__fabrik_connections AS c');
		$query->where('published = 1');
		$query->order('host');

		// Get the options.
		$db->setQuery($query);
		$options = $db->loadObjectList();

		// Check for a database error.
		if ($db->getErrorNum())
		{
			JError::raiseWarning(500, $db->getErrorMsg());
		}
		$sel = JHtml::_('select.option', '', JText::_('COM_FABRIK_PLEASE_SELECT'));
		$sel->default = false;
		array_unshift($options, $sel);
		return $options;
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @return	string	The field input markup.
	 */

	protected function getInput()
	{
		if ((int) $this->form->getValue('id') == 0 && $this->value == '')
		{
			// Default to default connection on new form where no value specified
			$options = (array) $this->getOptions();
			foreach ($options as $opt)
			{
				if ($opt->default == 1)
				{
					$this->value = $opt->value;
				}
			}
		}
		if ((int) $this->form->getValue('id') == 0 || !$this->element['readonlyonedit'])
		{
			return parent::getInput();
		}
		else
		{
			$options = (array) $this->getOptions();
			$v = '';
			foreach ($options as $opt)
			{
				if ($opt->value == $this->value)
				{
					$v = $opt->text;
				}
			}
		}
		return '<input type="hidden" value="' . $this->value . '" name="' . $this->name . '" />' . '<input type="text" value="' . $v
			. '" name="connection_justalabel" class="readonly" readonly="true" />';
	}

}
