<?php
/**
 * View to make ajax json object reporting csv file creation progress.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

/**
 * View to make ajax json object reporting csv file creation progress.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       1.5
 */
class FabrikAdminViewList extends JViewLegacy
{
	/**
	 * Display the list
	 *
	 * @param   string  $tpl  Template
	 *
	 * @return  void
	 */

	public function display($tpl = null)
	{
		$session = JFactory::getSession();
		$app = JFactory::getApplication();
		$input = $app->input;
		$exporter = JModelLegacy::getInstance('Csvexport', 'FabrikFEModel');
		$model = JModelLegacy::getInstance('list', 'FabrikFEModel');
		$model->setId($input->getInt('listid'));
		$model->setOutPutFormat('csv');
		$exporter->model =& $model;
		$input->set('limitstart' . $model->getId(), $input->getInt('start', 0));
		$input->set('limit' . $model->getId(), $exporter->getStep());

		// $$$ rob moved here from csvimport::getHeadings as we need to do this before we get
		// the table total
		$selectedFields = $input->get('fields', array(), 'array');
		$model->setHeadingsForCSV($selectedFields);

		$total = $model->getTotalRecords();

		$key = 'fabrik.list.' . $model->getId() . 'csv.total';

		if (is_null($session->get($key)))
		{
			$session->set($key, $total);
		}

		$start = $input->getInt('start', 0);

		if ($start < $total)
		{
			$exporter->writeFile($total);
		}
		else
		{
			$session->clear($key);
			$exporter->downloadFile();
		}

		return;
	}
}
