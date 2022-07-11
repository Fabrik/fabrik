<?php
/**
 * CSV View Front End View
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

/**
 * CSV View Front End View
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0.6
 */
class FabrikViewCsv extends FabrikView
{
	/**
	 * Display the view
	 *
	 * @param   string $tpl Template name
	 *
	 * @return  mixed  A string if successful, otherwise a JError object.
	 */
	public function display($tpl = null)
	{
		$this->listid = $this->app->input->get('listid', 0);
		$listModel    = JModelLegacy::getInstance('List', 'FabrikFEModel');
		$listModel->setId($this->listid);
		$this->setModel($listModel, true);
		$this->table = $listModel->getTable();
		$data        = array();
		$this->getManagementJS($data);
		$this->id   = $listModel->getId();
		$this->form = $listModel->getForm();
		$this->shim();

		if (!$listModel->canCSVExport())
		{
			throw new RuntimeException('Naughty naughty!', 400);
		}

		$this->addTemplatePath(JPATH_SITE . '/components/com_fabrik/views/csv/tmpl');

		return parent::display($tpl);
	}

	/**
	 * Ini the Fabrik requirejs framework files
	 *
	 * @return  void
	 */
	protected function shim()
	{
		$shim             = array();
		$dep              = new stdClass;
		$dep->deps        = array('fab/fabrik', 'fab/listfilter', 'fab/advanced-search', 'fab/encoder');
		$shim['fab/list'] = $dep;
		FabrikHelperHTML::iniRequireJS($shim);
	}

	/**
	 * Get the js needed for the view
	 *
	 * @param   array $data empty array
	 *
	 * @return  void
	 */

	protected function getManagementJS($data = array())
	{
		$model          = $this->getModel();
		$listId         = $model->getId();
		$script         = array();
		$opts           = new stdClass;
		$opts->admin    = $this->app->isAdmin();
		$opts->form     = 'listform_' . $listId;
		$opts->headings = $model->jsonHeadings();
		list($this->headings, $groupHeadings, $this->headingClass, $this->cellClass) = $model->getHeadings();
		$labels = $this->headings;

		foreach ($labels as &$l)
		{
			$l = strip_tags($l);
		}

		$listParams            = $model->getParams();
		$opts->labels          = $labels;
		$opts->csvChoose       = (bool) $listParams->get('csv_frontend_selection');
		$csvOpts               = new stdClass;
		$csvOpts->excel        = (int) $listParams->get('csv_format');
		$csvOpts->inctabledata = (int) $listParams->get('csv_include_data');
		$csvOpts->incraw       = (int) $listParams->get('csv_include_raw_data');
		$csvOpts->inccalcs     = (int) $listParams->get('csv_include_calculations');
		$csvOpts->custom_qs    = $listParams->get('csv_custom_qs', '');
		$opts->csvOpts         = $csvOpts;
		$opts->csvFields       = $this->get('CsvFields');
		$csvOpts->incfilters   = 0;
		$opts->view            = 'csv';

		// $$$rob if you are loading a table in a window from a form db join select record option
		// then we want to know the id of the window so we can set its showSpinner() method
		$opts->winid = $this->app->input->get('winid', '');
		$opts        = json_encode($opts);

		JText::script('COM_FABRIK_CSV_COMPLETE');
		JText::script('COM_FABRIK_CSV_DOWNLOAD_HERE');
		JText::script('COM_FABRIK_CONFIRM_DELETE');
		JText::script('COM_FABRIK_CSV_DOWNLOADING');
		JText::script('COM_FABRIK_FILE_TYPE');
		JText::script('COM_FABRIK_INCLUDE_FILTERS');
		JText::script('COM_FABRIK_INCLUDE_RAW_DATA');
		JText::script('COM_FABRIK_INCLUDE_DATA');
		JText::script('COM_FABRIK_INCLUDE_CALCULATIONS');
		JText::script('COM_FABRIK_EXPORT');
		JText::script('COM_FABRIK_LOADING');
		JText::script('COM_FABRIK_RECORDS');
		JText::script('JNO');
		JText::script('JYES');
		JText::script('COM_FABRIK_SAVING_TO');

		$srcs   = FabrikHelperHTML::framework();
		$srcs['ListPlugin'] = 'media/com_fabrik/js/list-plugin.js';
		$srcs['List'] = 'media/com_fabrik/js/list.js';

		$script[] = 'var list = new FbList(' . $listId . ',' . $opts . ');';
		$script[] = 'Fabrik.addBlock(\'list_' . $listId . '\', list);';
		FabrikHelperHTML::script($srcs, implode("\n", $script));
	}
}
