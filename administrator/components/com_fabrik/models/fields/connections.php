<?php
/**
 * Renders a list of connections
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

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
	 * @var        string
	 */
	protected $name = 'Connections';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 */
	protected function getOptions()
	{
		// Initialize variables.
		$db    = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);

		$query->select('id AS value, description AS text, ' . $db->quoteName('default'));
		$query->from('#__fabrik_connections AS c');
		$query->where('published = 1');
		$query->order('host');

		// Get the options.
		$db->setQuery($query);
		$options      = $db->loadObjectList();
		$sel          = JHtml::_('select.option', '', FText::_('COM_FABRIK_PLEASE_SELECT'));
		$sel->default = false;
		array_unshift($options, $sel);

		return $options;
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @return    string    The field input markup.
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
			$v       = '';

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
