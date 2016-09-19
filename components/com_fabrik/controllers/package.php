<?php
/**
 * Fabrik Package Controller
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

/**
 * Fabrik package controller
 *
 * @package  Fabrik
 * @since    3.0
 */
class FabrikControllerPackage extends JControllerLegacy
{
	/**
	 * Id used from content plugin when caching turned on to ensure correct element rendered
	 *
	 * @var  int
	 */
	public $cacheId = 0;

	/**
	 * Display the package view
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached - NOTE not actually used to control caching!!!
	 * @param   array    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  JController  A JController object to support chaining.
	 */
	public function display($cachable = false, $urlparams = false)
	{
		$document = JFactory::getDocument();
		$app = JFactory::getApplication();
		$input = $app->input;
		$viewName = $input->get('view', 'package');

		$viewType = $document->getType();

		// Set the default view name from the Request
		$view = $this->getView($viewName, $viewType);

		// If the view is a package create and assign the table and form views
		$tableView = $this->getView('list', $viewType);
		$listModel = $this->getModel('list', 'FabrikFEModel');
		$tableView->setModel($listModel, true);
		$view->tableView = $tableView;

		$view->formView = $this->getView('Form', $viewType);
		$formModel = $this->getModel('Form', 'FabrikFEModel');
		$formModel->setDbo(FabrikWorker::getDbo());
		$view->formView->setModel($formModel, true);

		// Push a model into the view
		if ($model = $this->getModel($viewName, 'FabrikFEModel'))
		{
			$model->setDbo(FabrikWorker::getDbo());
			$view->setModel($model, true);
		}
		// Display the view
		$view->error = $this->getError();
		$view->display();

		return $this;
	}
}
