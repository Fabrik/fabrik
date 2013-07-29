<?php
/**
 * Renders a repeating drop down list of packages
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
 * Renders a repeating drop down list of packages
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       1.6
 */

class JFormFieldPackageList extends JFormFieldList
{
	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	var $_name = 'Packagelist';

	/**
	 * Get list options
	 *
	 * @return  array
	 */

	protected function getOptions()
	{
		$db = FabrikWorker::getDbo();
		$query = $db->getQuery(true);
		$query->select("id AS value, CONCAT(label, '(', version , ')') AS " . $db->quote('text'));
		$query->from('#__{package}_packages');
		$query->order('value DESC');
		$db->setQuery($query);
		$rows = $db->loadObjectList();
		$o = new stdClass;
		$o->value = 0;
		$o->text = JText::_('COM_FABRIK_NO_PACKAGE');
		array_unshift($rows, $o);
		return $rows;
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @return	string	The field input markup.
	 */

	protected function getInput()
	{
		if ($this->element['active'] == 1)
		{
			$this->element['readonly'] = '';
		}
		else
		{
			$this->element['readonly'] = 'true';
		}
		return parent::getInput();
	}

}
