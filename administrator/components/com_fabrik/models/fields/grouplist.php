<?php
/**
 * Renders a list of groups
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
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
 * Renders a list of groups
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       1.6
 */

class JFormFieldGroupList extends JFormFieldList
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'Grouplist';

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string	The field input markup.
	 */

	protected function getOptions()
	{

		if ($this->value == '')
		{
			$app = JFactory::getApplication();
			$this->value = $app->getUserStateFromRequest('com_fabrik.elements.filter.group', 'filter_groupId', $this->value);
		}

		// Initialize variables.
		$options = array();
		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);

		$query->select('id AS value, name AS text');
		$query->from('#__{package}_groups AS g');
		$query->where('published <> -2');
		$query->order('name');

		// Get the options.
		$db->setQuery($query);

		$options = $db->loadObjectList();

		// Check for a database error.
		if ($db->getErrorNum())
		{
			JError::raiseWarning(500, $db->getErrorMsg());
		}
		array_unshift($options, JHtml::_('select.option', '', JText::_('COM_FABRIK_PLEASE_SELECT')));
		return $options;
	}

}
