<?php
/**
 * Fabrik Raw From Controller
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.controller');

/**
 * Fabrik Raw From Controller
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 *
 * @deprecated? Don't think this is used, code seems out of date, cetainly for process anyway - redirect urls are
 * for Fabrik 2 !
 */

class FabrikControllerForm extends JController
{

	/**
	 * Is the view rendered from the J content plugin
	 *
	 * @var  bool
	 */
	public $isMambot = false;

	/**
	 * Display the view
	 *
	 * @return  null
	 */

	public function display()
	{
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$document = JFactory::getDocument();
		$viewName = JRequest::getVar('view', 'form', 'default', 'cmd');
		$modelName = $viewName;

		if ($viewName == 'details')
		{
			$viewName = 'form';
		}

		$viewType = $document->getType();

		// Set the default view name from the Request
		$view = $this->getView($viewName, $viewType);

		// Push a model into the view
		$model = $this->getModel($modelName, 'FabrikFEModel');
		/**
		 * If errors made when submitting from a J plugin they are stored in the session
		 * lets get them back and insert them into the form model
		 */
		if (!$model->hasErrors())
		{
			$context = 'com_' . $package . '.form.' . JRequest::getInt('formid');
			$model->_arErrors = $session->get($context . '.errors', array());
			$session->clear($context . '.errors');
		}
		if (!JError::isError($model) && is_object($model))
		{
			$view->setModel($model, true);
		}
		$view->isMambot = $this->isMambot;

		// Display the view
		$view->assign('error', $this->getError());
		$user = JFactory::getUser();

		// Only allow cached pages for users not logged in.
		return $view->display();
		if ($viewType != 'feed' && !$this->isMambot && $user->get('id') == 0)
		{
			$cache = JFactory::getCache('com_' . $package, 'view');
			return $cache->get($view, 'display');
		}
		else
		{
			return $view->display();
		}
	}

	/**
	 * Process the form
	 *
	 * @return  null
	 */

	public function process()
	{
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$document = JFactory::getDocument();
		$viewName = JRequest::getVar('view', 'form', 'default', 'cmd');
		$viewType = $document->getType();
		$view = $this->getView($viewName, $viewType);
		$model = $this->getModel('form', 'FabrikFEModel');
		if (!JError::isError($model))
		{
			$view->setModel($model, true);
		}
		$model->setId(JRequest::getInt('formid', 0));
		$this->isMambot = JRequest::getVar('isMambot', 0);
		$model->getForm();
		$model->_rowId = JRequest::getVar('rowid', '');

		// Check for request forgeries
		if ($model->spoofCheck())
		{
			JRequest::checkToken() or die('Invalid Token');
		}
		if (JRequest::getBool('fabrik_ignorevalidation', false) != true)
		{
			// Put in when saving page of form
			if (!$model->validate())
			{
				// If its in a module with ajax or in a package
				if (JRequest::getInt('packageId') !== 0)
				{
					$data = array('modified' => $model->modifiedValidationData);

					// Validating entire group when navigating form pages
					$data['errors'] = $model->_arErrors;
					echo json_encode($data);
					return;
				}
				if ($this->isMambot)
				{
					// Store errors in session
					$context = 'com_' . $package . '.form.' . $model->get('id') . '.';
					$session->set($context . 'errors', $model->_arErrors);
					/**
					 * $$$ hugh - testing way of preserving form values after validation fails with form plugin
					 * might as well use the 'savepage' mechanism, as it's already there!
					 */
					$session->set($context . 'session.on', true);
					$this->savepage();
					$this->makeRedirect($model, '');
				}
				else
				{
					echo $view->display();
				}
				return;
			}
		}

		// Reset errors as validate() now returns ok validations as empty arrays
		$model->_arErrors = array();
		$defaultAction = $model->process();

		// Check if any plugin has created a new validation error
		if (!empty($model->_arErrors))
		{
			$pluginManager = FabrikWorker::getPluginManager();
			$pluginManager->runPlugins('onError', $model);
			echo $view->display();
			return;
		}

		// One of the plugins returned false stopping the default redirect action from taking place
		if (!$defaultAction)
		{
			return;
		}

		$msg = $model->showSuccessMsg() ? $model->getParams()->get('submit-success-msg', JText::_('COM_FABRIK_RECORD_ADDED_UPDATED')) : '';

		if (JRequest::getInt('elid') !== 0)
		{
			// Inline edit show the edited element
			echo $model->inLineEditResult();
			return;
		}

		if (JRequest::getInt('packageId') !== 0)
		{
			echo json_encode(array('msg' => $msg));
			return;
		}
		JRequest::setVar('view', 'list');
		echo $this->display();
	}

	/**
	 * Generic function to redirect
	 *
	 * @param   object  &$model  form model
	 * @param   string  $msg     redirection message to show
	 *
	 * @return  string  redirect url
	 */

	protected function makeRedirect(&$model, $msg = null)
	{
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		if (is_null($msg))
		{
			$msg = JText::_('COM_FABRIK_RECORD_ADDED_UPDATED');
		}
		if ($app->isAdmin())
		{
			// Admin option is always com_fabrik
			if (array_key_exists('apply', $model->_formData))
			{
				$url = "index.php?option=com_fabrik&c=form&task=form&formid=" . JRequest::getInt('formid') . "&listid=" . JRequest::getInt('listid')
					. "&rowid=" . JRequest::getInt('rowid');
			}
			else
			{
				$url = "index.php?option=com_fabrik&c=table&task=viewTable&cid[]=" . $model->getTable()->id;
			}
			$this->setRedirect($url, $msg);
		}
		else
		{
			if (array_key_exists('apply', $model->_formData))
			{
				$url = "index.php?option=com_' . $package . '&c=form&view=form&formid=" . JRequest::getInt('formid') . "&rowid=" . JRequest::getInt('rowid')
					. "&listid=" . JRequest::getInt('listid');
			}
			else
			{
				if ($this->isMambot)
				{
					// Return to the same page
					$url = JArrayHelper::getValue($_SERVER, 'REQUEST_URI', 'index.php');
				}
				else
				{
					// Return to the page that called the form
					$url = JRequest::getVar('fabrik_referrer', "index.php", 'post');
				}
				$Itemid = FabrikWorker::itemId();
				if ($url == '')
				{
					$url = 'index.php?option=com_' . $option . '&Itemid=' . $Itemid;
				}
			}
			$config = JFactory::getConfig();
			if ($config->get('sef'))
			{
				$url = JRoute::_($url);
			}
			$this->setRedirect($url, $msg);
		}
	}

}
