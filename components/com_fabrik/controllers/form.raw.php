<?php
/**
 * Fabrik Raw From Controller
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

/**
 * Fabrik Raw From Controller
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 *
 * @deprecated? Don't think this is used, code seems out of date, certainly for process anyway - redirect urls are
 * for Fabrik 2 !
 */
class FabrikControllerForm extends JControllerLegacy
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
		$session = JFactory::getSession();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$document = JFactory::getDocument();
		$input = $app->input;
		$viewName = $input->get('view', 'form');
		$modelName = $viewName;

		if ($viewName == 'details')
		{
			$viewName = 'form';
		}

		$viewType = $document->getType();

		// Set the default view name from the Request
		$view = $this->getView($viewName, $viewType);

		// Push a model into the view
		if ($model = $this->getModel($modelName, 'FabrikFEModel'))
		{
			$view->setModel($model, true);
		}

		/**
		 * If errors made when submitting from a J plugin they are stored in the session
		 * lets get them back and insert them into the form model
		 */
		if (!$model->hasErrors())
		{
			$context = 'com_' . $package . '.form.' . $input->getInt('formid');
			$model->errors = $session->get($context . '.errors', array());
			$session->clear($context . '.errors');
		}

		$view->isMambot = $this->isMambot;

		// Display the view
		$view->error = $this->getError();

		// run the view (no caching)
		return $view->display();
	}

	/**
	 * Process the form
	 *
	 * @return  null
	 */
	public function process()
	{
		$app = JFactory::getApplication();
		$session = JFactory::getSession();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$document = JFactory::getDocument();
		$input = $app->input;
		$viewName = $input->get('view', 'form');
		$viewType = $document->getType();
		$view = $this->getView($viewName, $viewType);

		if ($model = $this->getModel('form', 'FabrikFEModel'))
		{
			$view->setModel($model, true);
		}

		$model->setId($input->getInt('formid', 0));
		$this->isMambot = $input->get('isMambot', 0);
		$model->getForm();
		$model->rowId = $input->get('rowid', '', 'string');

		// Check for request forgeries
		if ($model->spoofCheck())
		{
			JSession::checkToken() or die('Invalid Token');
		}

		if ($input->getBool('fabrik_ignorevalidation', false) != true)
		{
			// Put in when saving page of form
			if (!$model->validate())
			{
				// If its in a module with ajax or in a package
				if ($input->getInt('packageId') !== 0)
				{
					$data = array('modified' => $model->modifiedValidationData);

					// Validating entire group when navigating form pages
					$data['errors'] = $model->errors;
					echo json_encode($data);

					return;
				}

				if ($this->isMambot)
				{
					// Store errors in session
					$context = 'com_' . $package . '.form.' . $model->get('id') . '.';
					$session->set($context . 'errors', $model->errors);

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
		$model->errors = array();
		$defaultAction = $model->process();

		// Check if any plugin has created a new validation error
		if (!empty($model->errors))
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

		$msg = $model->getSuccessMsg();

		if ($input->getInt('elid') !== 0)
		{
			// Inline edit show the edited element
			$inlineModel = $this->getModel('forminlineedit', 'FabrikFEModel');
			$inlineModel->setFormModel($model);
			echo $inlineModel->showResults();

			return;
		}

		if ($input->getInt('packageId') !== 0)
		{
			echo json_encode(array('msg' => $msg));

			return;
		}

		$input->set('view', 'list');
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
		$input = $app->input;
		$formId = $input->getInt('formid');
		$listId = $input->getInt('listid');
		$rowId = $input->getString('rowid', '', 'string');

		if (is_null($msg))
		{
			$msg = FText::_('COM_FABRIK_RECORD_ADDED_UPDATED');
		}

		if ($app->isClient('administrator'))
		{
			// Admin option is always com_fabrik
			if (array_key_exists('apply', $model->formData))
			{
				$url = 'index.php?option=com_fabrik&c=form&task=form&formid=' . $formId . '&listid=' . $listId . '&rowid=' . $rowId;
			}
			else
			{
				$url = 'index.php?option=com_fabrik&c=table&task=viewTable&cid[]=' . $model->getTable()->id;
			}

			$this->setRedirect($url, $msg);
		}
		else
		{
			if (array_key_exists('apply', $model->formData))
			{
				$url = 'index.php?option=com_' . $package . '&c=form&view=form&formid=' . $formId . '&rowid=' . $rowId . '&listid=' . $listId;
			}
			else
			{
				if ($this->isMambot)
				{
					// Return to the same page
					$url = filter_var(ArrayHelper::getValue($_SERVER, 'REQUEST_URI', 'index.php'), FILTER_SANITIZE_URL);
				}
				else
				{
					// Return to the page that called the form
					$url = $input->get('fabrik_referrer', 'index.php', 'string');
				}

				$itemId = FabrikWorker::itemId();

				if ($url == '')
				{
					$url = 'index.php?option=com_' . $package . '&Itemid=' . $itemId;
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
