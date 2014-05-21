<?php
/**
 * Plugin List Field class for Fabrik.
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

/**
 * Plugin List Field class for Fabrik.
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       1.6
 */

class JFormFieldPluginList extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'PluginList';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 */

	protected function getOptions()
	{
		$group = (string) $this->element['plugin'];
		$key = $this->element['key'];
		$key = ($key == 'visualization.plugin') ? "CONCAT('visualization.',element) " : 'element';

		// Initialize variables.
		$options = array();

		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select($key . ' AS value, element AS text');
		$query->from('#__extensions AS p');
		$query->where($db->quoteName('type') . ' = ' . $db->quote('plugin'));
		$query->where($db->quoteName('enabled') . ' = 1 AND state != -1');
		$query->where($db->quoteName('folder') . ' = ' . $db->quote($group));
		$query->order('text');

		// Get the options.
		$db->setQuery($query);
		$options = $db->loadObjectList();

		array_unshift($options, JHtml::_('select.option', '', FText::_('COM_FABRIK_PLEASE_SELECT')));

		return $options;
	}
}
