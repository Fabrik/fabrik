<?php
/**
 * Renders a list releated forms that a db join element can be populated from
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
 * Renders a list releated forms that a db join element can be populated from
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       1.6
 */

class JFormFieldPopupforms extends JFormFieldList
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'Connections';

	/**
	 * Get list options
	 *
	 * @return  array
	 */

	protected function getOptions()
	{
		// Initialize variables.
		$options = array();

		$db	= FabrikWorker::getDbo(true);
		$query	= $db->getQuery(true);
		$query->select('f.id AS value, f.label AS text, l.id AS listid')
		->from('#__{package}_forms AS f')
		->join('LEFT', '#__{package}_lists As l ON f.id = l.form_id')
		->where('f.published = 1 AND l.db_table_name = ' . $db->quote($this->form->getValue('params.join_db_name')))
		->order('f.label');

		// Get the options.
		$db->setQuery($query);
		$options = $db->loadObjectList('value');

		// Check for a database error.
		if ($db->getErrorNum())
		{
			JError::raiseWarning(500, $db->getErrorMsg());
		}
		if (empty($options))
		{
			$options[] = JHTML::_('select.option', '', JText::_('COM_FABRIK_NO_POPUP_FORMS_AVAILABLE'));
		}
		return $options;
	}

}
