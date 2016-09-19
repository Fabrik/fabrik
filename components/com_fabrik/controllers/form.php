<?php
/**
 * Fabrik From Controller
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
 * Fabrik From Controller
 *
 * @static
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       1.5
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
	 * Id used from content plugin when caching turned on to ensure correct element rendered
	 *
	 * @var  int
	 */
	public $cacheId = 0;

	/**
	 * @var boolean
	 */
	protected $baseRedirect = false;

	/**
	 * Magic method to convert the object to a string gracefully.
	 *
	 * $$$ hugh - added 08/05/2012.  No idea what's going on, but I had to add this to stop
	 * the class name 'FabrikControllerForm' being output at the bottom of the form, when rendered
	 * through a Fabrik form module.  See:
	 *
	 * https://github.com/Fabrik/fabrik/issues/398
	 *
	 * @return  string  empty string.
	 */
	public function __toString()
	{
		return '';
	}

	/**
	 * Inline edit control
	 *
	 * @since   3.0b
	 *
	 * @return  null
	 */
	public function inlineedit()
	{
		$model = JModelLegacy::getInstance('FormInlineEdit', 'FabrikFEModel');
		$model->render();
	}

	/**
	 * Display the view
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached - NOTE not actually used to control caching!!!
	 * @param   array    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  JController|void  A JController object to support chaining.
	 */
	public function display($cachable = false, $urlparams = array())
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$document = JFactory::getDocument();
		$viewName = $input->get('view', 'form');
		$modelName = $viewName;

		if ($viewName == 'emailform')
		{
			$modelName = 'form';
		}

		$viewType = $document->getType();

		// Set the default view name from the Request
		$view = $this->getView($viewName, $viewType);

		// Push a model into the view (may have been set in content plugin already)
		/** @var FabrikFEModelForm $model */
		$model = !isset($this->_model) ? $this->getModel($modelName, 'FabrikFEModel') : $this->_model;
		$model->isMambot = $this->isMambot;
		$model->packageId = $app->input->getInt('packageId');

		$view->setModel($model, true);
		$view->isMambot = $this->isMambot;

		FabrikWorker::getPluginManager()->runPlugins('onBeforeGetData', $model);

		// Get data as it will be needed for ACL when testing if current row is editable.
		$model->getData();

		// If we can't edit the record redirect to details view
		if ($model->checkAccessFromListSettings() <= 1)
		{
			$app = JFactory::getApplication();
			$input = $app->input;

			if ($app->isAdmin())
			{
				$url = 'index.php?option=com_fabrik&task=details.view&formid=' . $input->getInt('formid') . '&rowid=' . $input->get('rowid', '', 'string');
			}
			else
			{
				$url = 'index.php?option=com_' . $package . '&view=details&formid=' . $input->getInt('formid') . '&rowid=' . $input->get('rowid', '', 'string');
			}

			// So we can determine in form PHP plugin's that the original request was for a form.
			$url .= '&fromForm=1';
			$msg = $model->aclMessage();
			$this->setRedirect(JRoute::_($url), $msg, 'notice');

			return;
		}
		// Display the view
		$view->error = $this->getError();

		// Redirect plugin message if coming from content plugin - reloading in same page
		$model->applyMsgOnce();

		// $$$ hugh - added disable caching option, and no caching if not logged in (unless we can come up with a unique cacheid for guests)
		// NOTE - can't use IP of client, as could be two users behind same NAT'ing proxy / firewall.
		$listModel = $model->getListModel();
		$listParams = $listModel->getParams();

		$user = JFactory::getUser();

		if ($user->get('id') == 0
			|| $listParams->get('list_disable_caching', '0') === '1'
			|| in_array($input->get('format'), array('raw', 'csv', 'pdf')))
		{
			$view->display();
		}
		else
		{
			$uri = JURI::getInstance();
			$uri = $uri->toString(array('path', 'query'));
			$cacheId = serialize(array($uri, $input->post, $user->get('id'), get_class($view), 'display', $this->cacheId));
			$cache = JFactory::getCache('com_' . $package, 'view');
			ob_start();
			$cache->get($view, 'display', $cacheId);
			$contents = ob_get_contents();
			ob_end_clean();

			// Workaround for token caching
			$token = JSession::getFormToken();
			$search = '#<input type="hidden" name="[0-9a-f]{32}" value="1" />#';
			$replacement = '<input type="hidden" name="' . $token . '" value="1" />';
			echo preg_replace($search, $replacement, $contents);
		}

		return $this;
	}

	/**
	 * Process the form
	 * Inline edit save routed here (not in raw)
	 *
	 * @return  null
	 */
	public function process()
	{
		$profiler = JProfiler::getInstance('Application');
		JDEBUG ? $profiler->mark('controller process: start') : null;

		$app = JFactory::getApplication();
		$input = $app->input;

		if ($input->get('format', '') == 'raw')
		{
			error_reporting(error_reporting() ^ (E_WARNING | E_NOTICE));
		}

		$viewName = $input->get('view', 'form');
		$view = $this->getView($viewName, JFactory::getDocument()->getType());

		/** @var FabrikFEModelForm $model */
		if ($model = $this->getModel('form', 'FabrikFEModel'))
		{
			$view->setModel($model, true);
		}

		$model->setId($input->getInt('formid', 0));
		$model->packageId = $input->getInt('packageId');
		$this->isMambot = $input->get('isMambot', 0);
		$model->rowId = $input->get('rowid', '', 'string');

		/**
		 * $$$ hugh - need this in plugin manager to be able to treat a "Copy" form submission
		 * as 'new' for purposes of running plugins.  Rob's comment in model process() seems to
		 * indicate that origRowId was for this purposes, but it doesn't work, 'cos always has a value.
		 */
		if ($input->get('Copy', 'no') !== 'no')
		{
			$model->copyingRow(true);
		}

		// Check for request forgeries
		if ($model->spoofCheck())
		{
			JSession::checkToken() or die('Invalid Token');
		}

		JDEBUG ? $profiler->mark('controller process validate: start') : null;

		if (!$model->validate())
		{
			$this->handleError($view, $model);

			return;
		}
		JDEBUG ? $profiler->mark('controller process validate: end') : null;

		// Reset errors as validate() now returns ok validations as empty arrays
		$model->clearErrors();

		try
		{
			$model->process();
		}
		catch (Exception $e)
		{
			$model->errors['process_error'] = true;
			$app->enqueueMessage($e->getMessage(), 'error');
		}

		if ($input->getInt('elid', 0) !== 0)
		{
			// Inline edit show the edited element - ignores validations for now
			$inlineModel = $this->getModel('forminlineedit', 'FabrikFEModel');
			$inlineModel->setFormModel($model);
			echo $inlineModel->showResults();

			return;
		}

		// Check if any plugin has created a new validation error
		if ($model->hasErrors())
		{
			FabrikWorker::getPluginManager()->runPlugins('onError', $model);
			$this->handleError($view, $model);

			return;
		}

		$url = $this->getRedirectURL($model);
		$msg = $this->getRedirectMessage($model);

		/**
		 * If debug submit is requested (&fabrikdebug=2, and J! debug on, and Fabrik debug allowed),
		 * bypass any and all redirects, so we can see the profile for the submit
		 */
		if (FabrikHelperHTML::isDebugSubmit())
		{
			echo '<p>' . $msg . '</p>';
			echo '<p>Form submission profiling has stopped the automatic redirect. </p>';
			echo '<a href="' . $url . '">continue to redirect URL</a>';
			return;
		}

		$listModel = $model->getListModel();
		$listModel->set('_table', null);

		// @todo -should get handed off to the json view to do this
		if ($input->getInt('fabrik_ajax') == 1)
		{
			// $$$ hugh - adding some options for what to do with redirect when in content plugin
			// Should probably do this elsewhere, but for now ...
			$redirect_opts = array(
					'msg' => $msg,
					'url' => $url,
					'baseRedirect' => $this->baseRedirect,
					'rowid' => $input->get('rowid', '', 'string'),
					'suppressMsg' => !$model->showSuccessMsg()
			);

			if (!$this->baseRedirect && $this->isMambot)
			{
				$session = JFactory::getSession();
				$context = $model->getRedirectContext();
				$redirect_opts['redirect_how'] = $session->get($context . 'redirect_content_how', 'popup');
				$redirect_opts['width'] = (int) $session->get($context . 'redirect_content_popup_width', '300');
				$redirect_opts['height'] = (int) $session->get($context . 'redirect_content_popup_height', '300');
				$redirect_opts['x_offset'] = (int) $session->get($context . 'redirect_content_popup_x_offset', '0');
				$redirect_opts['y_offset'] = (int) $session->get($context . 'redirect_content_popup_y_offset', '0');
				$redirect_opts['title'] = $session->get($context . 'redirect_content_popup_title', '');
				$redirect_opts['reset_form'] = $session->get($context . 'redirect_content_reset_form', '1') == '1';
			}
			elseif (!$this->baseRedirect && !$this->isMambot)
			{
				/**
				 * $$$ hugh - I think this case only happens when we're a popup form from a list
				 * in which case I don't think "popup" is realy a valid option.  Anyway, need to set something,
				 * so for now just do the same as we do for isMambot, but default redirect_how to 'samepage'
				 */
				$session = JFactory::getSession();
				$context = $model->getRedirectContext();
				$redirect_opts['redirect_how'] = $session->get($context . 'redirect_content_how', 'samepage');
				$redirect_opts['width'] = (int) $session->get($context . 'redirect_content_popup_width', '300');
				$redirect_opts['height'] = (int) $session->get($context . 'redirect_content_popup_height', '300');
				$redirect_opts['x_offset'] = (int) $session->get($context . 'redirect_content_popup_x_offset', '0');
				$redirect_opts['y_offset'] = (int) $session->get($context . 'redirect_content_popup_y_offset', '0');
				$redirect_opts['title'] = $session->get($context . 'redirect_content_popup_title', '');
				$redirect_opts['reset_form'] = $session->get($context . 'redirect_content_reset_form', '1') == '1';

			}
			elseif ($this->isMambot)
			{
				// $$$ hugh - special case to allow custom code to specify that
				// the form should not be cleared after a failed AJAX submit
				$session = JFactory::getSession();
				$context = 'com_fabrik.form.' . $model->get('id') . '.redirect.';
				$redirect_opts['reset_form'] = $session->get($context . 'redirect_content_reset_form', '1') == '1';
			}
			// Let form.js handle the redirect logic
			echo json_encode($redirect_opts);

			// Stop require.js being added to output
			exit;
		}

		if ($input->get('format') == 'raw')
		{
			$input->set('view', 'list');
			$this->display();

			return;
		}
		else
		{

			// If no msg, set to null, so J! doesn't create an empty "Message" area
			if (empty($msg))
			{
				$msg = null;
			}

			$this->setRedirect($url, $msg);
		}
	}

	/**
	 * Handle the view error
	 *
	 * @param   JViewLegacy        $view   View
	 * @param   FabrikFEModelForm  $model  Form Model
	 *
	 * @since   3.1b
	 *
	 * @return  void
	 */
	protected function handleError($view, $model)
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$validated = false;

		// If its in a module with ajax or in a package or inline edit
		if ($input->get('fabrik_ajax'))
		{
			if ($input->getInt('elid', 0) !== 0)
			{
				// Inline edit
				$messages = array();
				$errs = $model->getErrors();

				// Only raise errors for fields that are present in the inline edit plugin
				$toValidate = array_keys($input->get('toValidate', array(), 'array'));

				foreach ($errs as $errorKey => $e)
				{
					if (in_array($errorKey, $toValidate) && count($e[0]) > 0)
					{
						array_walk_recursive($e, array('FabrikString', 'forHtml'));
						$messages[] = count($e[0]) === 1 ? '<li>' . $e[0][0] . '</li>' : '<ul><li>' . implode('</li><li>', $e[0]) . '</ul>';
					}
				}

				if (!empty($messages))
				{
					$messages = '<ul>' . implode('</li><li>', $messages) . '</ul>';
					header('HTTP/1.1 500 ' . FText::_('COM_FABRIK_FAILED_VALIDATION') . $messages);
					jexit();
				}
				else
				{
					$validated = true;
				}
			}
			else
			{
				// Package / model
				echo $model->getJsonErrors();
			}

			if (!$validated)
			{
				return;
			}
		}

		if (!$validated)
		{
			$this->savepage();

			if ($this->isMambot)
			{
				$this->setRedirect($this->getRedirectURL($model, false));
			}
			else
			{
				/**
				 * $$$ rob - http://fabrikar.com/forums/showthread.php?t=17962
				 * couldn't determine the exact set up that triggered this, but we need to reset the rowid to -1
				 * if reshowing the form, otherwise it may not be editable, but rather show as a detailed view
				 */
				if ($input->get('usekey', '') !== '')
				{
					$input->set('rowid', -1);
				}
				// Meant that the form's data was in different format - so redirect to ensure that its showing the same data.
				$input->set('task', '');
				$view->display();
			}

			return;
		}
	}

	/**
	 * Get redirect message
	 *
	 * @param   object  $model  form model
	 *
	 * @since   3.0
	 *
	 * @deprecated - use form model getRedirectMessage instead
	 *
	 * @return  string  redirect message
	 */
	protected function getRedirectMessage($model)
	{
		return $model->getRedirectMessage();
	}

	/**
	 * Get redirect URL
	 *
	 * @param   FabrikFEModelForm  $model       Form model
	 * @param   bool               $incSession  Set url in session?
	 *
	 * @since 3.0
	 *
	 * @deprecated - use form model getRedirectUrl() instead
	 *
	 * @return   string  redirect url
	 */
	protected function getRedirectURL($model, $incSession = true)
	{
		$res = $model->getRedirectURL($incSession, $this->isMambot);
		$this->baseRedirect = $res['baseRedirect'];

		return $res['url'];
	}

	/**
	 * Validate via ajax
	 *
	 * @return  null
	 */
	public function ajax_validate()
	{
		$app = JFactory::getApplication();
		$input = $app->input;

		/** @var FabrikFEModelForm $model */
		$model = $this->getModel('form', 'FabrikFEModel');
		$model->setId($input->getInt('formid', 0));
		$model->getForm();
		$model->setRowId($input->get('rowid', '', 'string'));
		$model->validate();
		$data = array('modified' => $model->modifiedValidationData);

		// Validating entire group when navigating form pages
		$data['errors'] = $model->errors;
		echo json_encode($data);
	}

	/**
	 * Save a form's page to the session table
	 *
	 * @return  null
	 */
	public function savepage()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$model = $this->getModel('Formsession', 'FabrikFEModel');
		$formModel = $this->getModel('Form', 'FabrikFEModel');
		$formModel->setId($input->getInt('formid'));
		$model->savePage($formModel);
	}

	/**
	 * Clear down any temp db records or cookies
	 * containing partially filled in form data
	 *
	 * @return  null
	 */
	public function removeSession()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$sessionModel = $this->getModel('formsession', 'FabrikFEModel');
		$sessionModel->setFormId($input->getInt('formid', 0));
		$sessionModel->setRowId($input->get('rowid', '', 'string'));
		$sessionModel->remove();
		$this->display();
	}

	/**
	 * Called via ajax to page through form records
	 *
	 * @return  null
	 */
	public function paginate()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$model = $this->getModel('Form', 'FabrikFEModel');
		$model->setId($input->getInt('formid'));
		$model->paginateRowId($input->get('dir'));
		$this->display();
	}

	/**
	 * Delete a record from a form
	 *
	 * @return  null
	 */
	public function delete()
	{
		// Check for request forgeries
		JSession::checkToken() or die('Invalid Token');
		$app = JFactory::getApplication();
		$input = $app->input;
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$model = $this->getModel('list', 'FabrikFEModel');
		$ids = array($input->get('rowid', 0));

		$listId = $input->getInt('listid');
		$limitStart = $input->getInt('limitstart' . $listId);
		$length = $input->getInt('limit' . $listId);

		$oldTotal = $model->getTotalRecords();
		$model->setId($listId);
		$ok = $model->deleteRows($ids);

		$total = $oldTotal - count($ids);

		$ref = $input->get('fabrik_referrer', 'index.php?option=com_' . $package . '&view=list&listid=' . $listId, 'string');

		if ($total >= $limitStart)
		{
			$newLimitStart = $limitStart - $length;

			if ($newLimitStart < 0)
			{
				$newLimitStart = 0;
			}

			$ref = str_replace("limitstart$listId=$limitStart", "limitstart$listId=$newLimitStart", $ref);
			$context = 'com_' . $package . '.list.' . $model->getRenderContext() . '.';
			$app->setUserState($context . 'limitstart', $newLimitStart);
		}

		if ($input->get('format') == 'raw')
		{
			$app->redirect('index.php?option=com_fabrik&view=list&listid=' . $listId . '&format=raw');
		}
		else
		{
			$msg = $ok ? count($ids) . ' ' . FText::_('COM_FABRIK_RECORDS_DELETED') : '';
			$app->enqueueMessage($msg);
			$app->redirect($ref);
		}
	}
}
