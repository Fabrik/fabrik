<?php
/**
 * View class for CSV import
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

jimport('joomla.application.component.view');

/**
 * View class for CSV import
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */
class FabrikViewImport extends FabrikView
{
	/**
	 * Display the view
	 *
	 * @param   string $tpl template
	 *
	 * @return  this
	 */
	public function display($tpl = null)
	{
		$srcs = FabrikHelperHTML::framework();
		FabrikHelperHTML::script($srcs);
        FabrikHelperHTML::iniRequireJs();
		$input        = $this->app->input;
		$this->listid = $input->getInt('listid', 0);
		$this->model  = JModelLegacy::getInstance('List', 'FabrikFEModel');
		$this->model->setId($this->listid);
		$this->table = $this->model->getTable();
		$this->form  = $this->get('Form');

		if (!$this->model->canCSVImport())
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
		$input = $this->app->input;

		// From list data view in admin
		$id = $input->getInt('listid', 0);

		// From list of lists checkbox selection
		$cid = $input->get('cid', array(0), 'array');
		$cid = ArrayHelper::toInteger($cid);

		if ($id === 0)
		{
			$id = $cid[0];
		}

		if (($id !== 0))
		{
			$db    = FabrikWorker::getDbo();
			$query = $db->getQuery(true);
			$query->select('label')->from('#__{package}_lists')->where('id = ' . $id);
			$db->setQuery($query);
			$this->listName = $db->loadResult();
		}

		$fieldsets = array('details');

		if ($this->model->canEmpty())
		{
			$fieldsets[] = 'drop';
		}

		$fieldsets[] = $id === 0 ? 'creation' : 'append';
		$fieldsets[] = 'format';

		return $fieldsets;
	}
}
