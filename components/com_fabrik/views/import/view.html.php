<?php
/**
 * View class for CSV import
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

/**
 * View class for CSV import
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */

class fabrikViewImport extends JViewLegacy
{

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  template
	 *
	 * @return  this
	 */

	public function display($tpl = null)
	{
		FabrikHelperHTML::framework();
		$app = JFactory::getApplication();
		$input = $app->input;
		$this->listid = $input->getInt('listid', 0);
		$listModel = JModelLegacy::getInstance('List', 'FabrikFEModel');
		$listModel->setId($this->listid);
		$this->table = $listModel->getTable();
		$this->form = $this->get('Form');
		if (!$listModel->canCSVImport())
		{
			throw new RuntimeException('Naughty naughty!', 400);
		}
		$layout = FabrikWorker::j3() ? 'bootstrap' : 'default';
		$this->setLayout($layout);
		$this->fieldsets = $this->setFieldSets();
		parent::display($tpl);
		return $this;
	}

	/**
	 * Set which fieldsets should be used
	 *
	 * @since   3.0.7
	 *
	 * @return  array  fieldset names
	 */

	private function setFieldSets()
	{
		$app = JFactory::getApplication();
		$input = $app->input;

		// From list data view in admin
		$id = $input->getInt('listid', 0);

		// From list of lists checkbox selection
		$cid = $input->get('cid', array(0), 'array');
		JArrayHelper::toInteger($cid);
		if ($id === 0)
		{
			$id = $cid[0];
		}
		if (($id !== 0))
		{
			$db = FabrikWorker::getDbo();
			$query = $db->getQuery(true);
			$query->select('label')->from('#__{package}_lists')->where('id = ' . $id);
			$db->setQuery($query);
			$this->listName = $db->loadResult();
		}
		$fieldsets = array('details');
		$fieldsets[] = $id === 0 ? 'creation' : 'append';
		$fieldsets[] = 'format';
		return $fieldsets;
	}
}
