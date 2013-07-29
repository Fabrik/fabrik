<?php
/**
 * Renders the form's database name or a field to create one
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

require_once JPATH_ADMINISTRATOR . '/components/com_fabrik/helpers/element.php';

/**
 * Renders the form's database name or a field to create one
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       1.6
 */

class JFormFieldFormDatabaseName extends JFormFieldText
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'FormDatabaseName';

	/**
	 * Method to get the field input markup.
	 *
	 * @return	string	The field input markup.
	 */

	protected function getInput()
	{
		if ($this->form->getValue('record_in_database'))
		{
			$db = FabrikWorker::getDbo(true);
			$query = $db->getQuery(true);
			$id = (int) $this->form->getValue('id');
			$query->select('db_table_name')->from('#__{package}_lists')->where('form_id = ' . $id);
			$db->setQuery($query);
			$this->element['readonly'] == true;
			$this->element['class'] = 'readonly';
			$this->value = $db->loadResult();
		}
		return parent::getInput();
	}

}
