<?php
/**
 * Fabrik Import Controller
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Pollen 8 Design Ltd. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.controller');

/**
 * Fabrik Import Controller
 *
 * @package  Fabrik
 * @since    3.0
 */

class FabrikControllerImport extends JController
{
	/**
	 * Display the view
	 *
	 * @return  null
	 */

	public function display()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$this->getModel('Importcsv', 'FabrikFEModel')->clearSession();
		$this->listid = $input->getInt('listid', 0);
		$listModel = $this->getModel('list', 'FabrikFEModel');
		$listModel->setId($this->listid);
		$this->table = $listModel->getTable();
		$document = JFactory::getDocument();
		$viewName = $input->get('view', 'form');
		$viewType = $document->getType();

		// Set the default view name from the Request
		$view = $this->getView($viewName, $viewType);
		$model = $this->getModel('Importcsv', 'FabrikFEModel');
		$view->setModel($model, true);
		$view->display();
	}

	/**
	 * Perform the file upload and set the session state
	 * Unlike back end import if there are unmatched headings we bail out
	 *
	 * @return null
	 */

	public function doimport()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$model = $this->getModel('Importcsv', 'FabrikFEModel');
		$listModel = $model->getListModel();

		if (!$listModel->canCSVImport())
		{
			JError::raiseError(400, 'Naughty naughty!');
			jexit();
		}

		if (!$model->checkUpload())
		{
			$this->display();
			return;
		}
		$id = $listModel->getId();
		$document = JFactory::getDocument();
		$viewName = $input->get('view', 'form');
		$viewType = $document->getType();

		// Set the default view name from the Request
		$view = $this->getView($viewName, $viewType);
		$model->import();
		$Itemid = $input->getInt('Itemid');
		if (!empty($model->newHeadings))
		{
			// As opposed to admin you can't alter table structure with a CSV import from the front end
			JError::raiseNotice(500, $model->makeError());
			$this->setRedirect('index.php?option=com_fabrik&view=import&fietype=csv&listid=' . $id . '&Itemid=' . $Itemid);
		}
		else
		{
			$input->set('fabrik_list', $id);
			$model->insertData();
			$msg = $model->updateMessage();
			$this->setRedirect('index.php?option=com_fabrik&view=list&listid=' . $id . "&resetfilters=1&Itemid=" . $Itemid, $msg);
		}
	}

}
