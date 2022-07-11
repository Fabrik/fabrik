<?php
/**
 * Package controller
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controllerform');
require_once 'fabcontrollerform.php';

/**
 * Package controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */
class FabrikAdminControllerPackage extends FabControllerForm
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var	string
	 */
	protected $text_prefix = 'COM_FABRIK_PACKAGE';

	/**
	 * Export the package
	 *
	 * @return  void
	 */
	public function export()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$cid = $input->get('cid', array(), 'array');
		$model = $this->getModel();
		$model->export($cid);
		$nText = $this->text_prefix . '_N_ITEMS_EXPORTED';
		$this->setMessage(JText::plural($nText, count($cid)));
		$this->setRedirect('index.php?option=com_fabrik&view=packages');
	}

	/**
	 * View the package
	 *
	 * @return  void
	 */
	public function view()
	{
		$document = JFactory::getDocument();
		$app = JFactory::getApplication();
		$input = $app->input;
		$viewType = $document->getType();
		$this->setPath('view', COM_FABRIK_FRONTEND . '/views');
		$viewLayout = $input->get('layout', 'default');
		$view = $this->getView('form', $viewType, '');
		$view->isMambot = $this->isMambot;

		// Set the layout
		$view->setLayout($viewLayout);

		// If the view is a package create and assign the table and form views
		$listView = $this->getView('list', $viewType);
		$listModel = $this->getModel('list', 'FabrikFEModel');
		$listView->setModel($listModel, true);
		$view->tableView = $listView;

		$view->formView = $this->getView('Form', $viewType);
		$formModel = $this->getModel('Form', 'FabrikFEModel');
		$formModel->setDbo(FabrikWorker::getDbo());
		$view->formView->setModel($formModel, true);

		// Push a model into the view
		if ($this->getModel($viewName, 'FabrikFEModel'))
		{
			$view->setModel($model, true);
		}

		$model->setDbo(FabrikWorker::getDbo());

		// @TODO check for cached version
		$view->display();
	}

	/**
	 * List form
	 *
	 * @return  void
	 */
	public function listform()
	{
		$document = JFactory::getDocument();
		$this->Form = $this->get('PackageListForm');
		$viewType = $document->getType();
		$view = $this->getView('package', $viewType, '');

		// Push a model into the view
		if ($model = $this->getModel())
		{
			$view->setModel($model, true);
			$db = FabrikWorker::getDbo();
			$model->setDbo($db);
		}

		$view->listform();
	}
}
