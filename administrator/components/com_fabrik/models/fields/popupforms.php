<?php
/**
 * Renders a list related forms that a db join element can be populated from
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_ADMINISTRATOR . '/components/com_fabrik/helpers/element.php';

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

/**
 * Renders a list related forms that a db join element can be populated from
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
	protected $name = 'Connections';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 */

	protected function getOptions()
	{
		// Initialize variables.
		$options = array();

		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);
		$query->select('f.id AS value, f.label AS text, l.id AS listid')->from('#__{package}_forms AS f')
			->join('LEFT', '#__{package}_lists As l ON f.id = l.form_id')
			->where('f.published = 1 AND l.db_table_name = ' . $db->quote($this->form->getValue('params.join_db_name')))->order('f.label');

		// Get the options.
		$db->setQuery($query);
		$options = $db->loadObjectList('value');

		if (empty($options))
		{
			$options[] = JHTML::_('select.option', '', FText::_('COM_FABRIK_NO_POPUP_FORMS_AVAILABLE'));
		}

		return $options;
	}
}
