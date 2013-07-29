<?php
/**
 * Email list plug-in Controller
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.email
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.controller');

require_once COM_FABRIK_FRONTEND . '/helpers/params.php';
require_once COM_FABRIK_FRONTEND . '/helpers/string.php';

/**
 * Email list plug-in Controller
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.email
 * @since       3.0
 */

class FabrikControllerListemail extends JController
{
	/**
	 *  Path of uploaded file
	 *
	 *  @var string
	 */
	var $filepath = null;

	/**
	 * default display mode
	 *
	 * @return unknown
	 */

	function display()
	{
		echo "display";
	}

	/**
	 * set up the popup window containing the form to create the
	 * email message
	 *
	 * @return string html
	 */

	function popupwin()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$document = JFactory::getDocument();
		$viewName = 'popupwin';
		$viewType = $document->getType();

		// Set the default view name from the Request
		$view = $this->getView($viewName, $viewType);

		$listModel = $this->getModel('List', 'FabrikFEModel');
		$listModel->setId($input->getInt('id'));
		$formModel = $listModel->getFormModel();

		// Push a model into the view
		$pluginManager = JModel::getInstance('Pluginmanager', 'FabrikFEModel');
		$model = $pluginManager->getPlugIn('email', 'list');

		$model->formModel = $formModel;
		$model->listModel = $listModel;
		$model->setParams($listModel->getParams(), $input->getInt('renderOrder'));
		if (!JError::isError($model))
		{
			$view->setModel($model, true);
		}
		$view->setModel($listModel);
		$view->setModel($formModel);

		// Display the view
		$view->error = $this->getError();
		return $view->display();
	}

	/**
	 * Send the emails
	 *
	 * @return  void
	 */

	function doemail()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$pluginManager = JModel::getInstance('Pluginmanager', 'FabrikFEModel');
		$model = $pluginManager->getPlugIn('email', 'list');
		$listModel = $this->getModel('List', 'FabrikFEModel');
		$listModel->setId($input->getInt('id'));
		$model->setParams($listModel->getParams(), $input->getInt('renderOrder'));
		$model->listModel = $listModel;
		/*
		 * $$$ hugh - for some reason have to do this here, if we don't, it'll
		 * blow up when it runs later on from within the list model itself.
		 */
		$formModel = $listModel->getFormModel();
		$model->doEmail();
	}

}
