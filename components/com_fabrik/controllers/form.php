<?php
/**
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.controller');

/**
 * Fabrik From Controller
 *
 * @static
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       1.5
 */

class FabrikControllerForm extends JController
{

	public $isMambot = false;

	/* @var int  id used from content plugin when caching turned on to ensure correct element rendered)*/
	public $cacheId = 0;

	/**
	 * Magic method to convert the object to a string gracefully.
	 *
	 * $$$ hugh - added 08/05/2012.  No idea what's going on, but I had to add this to stop
	 * the classname 'FabrikControllerForm' being output at the bottom of the form, when rendered
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
	 * @return  null
	 *
	 * @since   3.0b
	 */

	public function inlineedit()
	{
		$document = JFactory::getDocument();
		$model = JModel::getInstance('Form', 'FabrikFEModel');
		$viewType = $document->getType();
		$viewLayout = JRequest::getCmd('layout', 'default');
		$view = $this->getView('form', $viewType, '');
		$view->setModel($model, true);

		// Set the layout
		$view->setLayout($viewLayout);

		// @TODO check for cached version
		$view->inlineEdit();
	}

	/**
	 * Display the view
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  JController  A JController object to support chaining.
	 */

	public function display($cachable = false, $urlparams = false)
	{
		$session = JFactory::getSession();
		$document = JFactory::getDocument();
		$viewName = JRequest::getVar('view', 'form', 'default', 'cmd');
		$modelName = $viewName;
		if ($viewName == 'emailform')
		{
			$modelName = 'form';
		}

		$viewType = $document->getType();

		// Set the default view name from the Request
		$view = $this->getView($viewName, $viewType);

		// Push a model into the view (may have been set in content plugin already
		$model = !isset($this->_model) ? $this->getModel($modelName, 'FabrikFEModel') : $this->_model;

		// Test for failed validation then page refresh
		$model->getErrors();
		if (!JError::isError($model) && is_object($model))
		{
			$view->setModel($model, true);
		}
		$view->isMambot = $this->isMambot;

		// Display the view
		$view->assign('error', $this->getError());

		// Workaround for token caching

		if (in_array(JRequest::getCmd('format'), array('raw', 'csv', 'pdf')))
		{
			$view->display();
		}
		else
		{
			$user = JFactory::getUser();
			$post = JRequest::get('post');
			$cacheid = serialize(array(JRequest::getURI(), $post, $user->get('id'), get_class($view), 'display', $this->cacheId));
			$cache = JFactory::getCache('com_fabrik', 'view');
			ob_start();
			$cache->get($view, 'display', $cacheid);
			$contents = ob_get_contents();
			ob_end_clean();
			$token = JUtility::getToken();
			$search = '#<input type="hidden" name="[0-9a-f]{32}" value="1" />#';
			$replacement = '<input type="hidden" name="' . $token . '" value="1" />';
			echo preg_replace($search, $replacement, $contents);
		}
		return $this;
	}

	/**
	 * Process the form
	 *
	 * @return  null
	 */

	public function process()
	{
		if (JRequest::getCmd('format', '') == 'raw')
		{
			error_reporting(error_reporting() ^ (E_WARNING | E_NOTICE));
		}
		$model = $this->getModel('form', 'FabrikFEModel');
		$viewName = JRequest::getVar('view', 'form', 'default', 'cmd');
		$view = $this->getView($viewName, JFactory::getDocument()->getType());

		if (!JError::isError($model))
		{
			$view->setModel($model, true);
		}
		$model->setId(JRequest::getInt('formid', 0));

		$this->isMambot = JRequest::getVar('isMambot', 0);
		$model->getForm();
		$model->_rowId = JRequest::getVar('rowid', '');

		/**
		 * $$$ hugh - need this in plugin manager to be able to treat a "Copy" form submission
		 * as 'new' for purposes of running plugins.  Rob's comment in model process() seems to
		 * indicate that origRowId was for this purposes, but it doesn't work, 'cos always has a value.
		 */
		if (JRequest::getVar('Copy', '') != '')
		{
			$model->copyingRow(true);
		}

		// Check for request forgeries
		if ($model->spoofCheck())
		{
			JRequest::checkToken() or die('Invalid Token');
		}

		if (!$model->validate())
		{
			// If its in a module with ajax or in a package or inline edit
			if (JRequest::getCmd('fabrik_ajax'))
			{
				if (JRequest::getInt('elid') !== 0)
				{
					// Inline edit
					$eMsgs = array();
					$errs = $model->getErrors();
					foreach ($errs as $e)
					{
						if (count($e[0]) > 0)
						{
							array_walk_recursive($e, array('FabrikString', 'forHtml'));
							$eMsgs[] = count($e[0]) === 1 ? '<li>' . $e[0][0] . '</li>' : '<ul><li>' . implode('</li><li>', $e[0]) . '</ul>';
						}
					}
					$eMsgs = '<ul>' . implode('</li><li>', $eMsgs) . '</ul>';
					JError::raiseError(500, JText::_('COM_FABRIK_FAILED_VALIDATION') . $eMsgs);
				}
				else
				{
					// Package / model
					echo $model->getJsonErrors();
				}
				return;
			}
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
				if (JRequest::getCmd('usekey') !== '')
				{
					JRequest::setVar('rowid', -1);
				}
				$view->display();
			}
			return;
		}

		// Reset errors as validate() now returns ok validations as empty arrays
		$model->clearErrors();

		$model->process();
		if (JRequest::getInt('elid') !== 0)
		{
			// Inline edit show the edited element - ignores validations for now
			echo $model->inLineEditResult();
			return;
		}

		// Check if any plugin has created a new validation error
		if ($model->hasErrors())
		{
			FabrikWorker::getPluginManager()->runPlugins('onError', $model);
			$view->display();
			return;
		}

		$listModel = $model->getListModel();
		$listModel->set('_table', null);

		$url = $this->getRedirectURL($model);
		$msg = $this->getRedirectMessage($model);

		// @todo -should get handed off to the json view to do this
		if (JRequest::getInt('fabrik_ajax') == 1)
		{
			// $$$ hugh - adding some options for what to do with redirect when in content plugin
			// Should probably do this elsewhere, but for now ...
			$redirect_opts = array('msg' => $msg, 'url' => $url, 'baseRedirect' => $this->baseRedirect, 'rowid' => JRequest::getVar('rowid'));
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
			elseif ($this->isMambot)
			{
				// $$$ hugh - special case to allow custom code to specify that
				// the form should not be cleared after a failed AJAX submit
				$session = JFactory::getSession();
				$context = 'com_fabrik.form.' . $model->get('id') . '.redirect.';
				$redirect_opts['reset_form'] = $session->get($context . 'redirect_content_reset_form', '1') == '1';
			}
			// Let form.js handle the redirect logic (will also send out a
			echo json_encode($redirect_opts);
			return;
		}

		if (JRequest::getVar('format') == 'raw')
		{
			JRequest::setVar('view', 'list');
			$this->display();
			return;
		}
		else
		{
			$this->setRedirect($url, $msg);
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
	 * @param   object  $model       form model
	 * @param   bool    $incSession  set url in session?
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
		$model = $this->getModel('form', 'FabrikFEModel');
		$model->setId(JRequest::getInt('formid', 0));
		$model->getForm();
		$model->_rowId = JRequest::getVar('rowid', '');
		$model->validate();
		$data = array('modified' => $model->_modifiedValidationData);

		// Validating entire group when navigating form pages
		$data['errors'] = $model->_arErrors;
		echo json_encode($data);
	}

	/**
	 * Save a form's page to the session table
	 *
	 * @return  null
	 */

	public function savepage()
	{
		$model = $this->getModel('Formsession', 'FabrikFEModel');
		$formModel = $this->getModel('Form', 'FabrikFEModel');
		$formModel->setId(JRequest::getInt('formid'));
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
		$sessionModel = $this->getModel('formsession', 'FabrikFEModel');
		$sessionModel->setFormId(JRequest::getInt('formid', 0));
		$sessionModel->setRowId(JRequest::getInt('rowid', 0));
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
		$model = $this->getModel('Form', 'FabrikFEModel');
		$model->setId(JRequest::getInt('formid'));
		$model->paginateRowId(JRequest::getVar('dir'));
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
		JRequest::checkToken() or die('Invalid Token');
		$app = JFactory::getApplication();
		$model = $this->getModel('list', 'FabrikFEModel');
		$ids = array(JRequest::getVar('rowid', 0));

		$listid = JRequest::getInt('listid');
		$limitstart = JRequest::getVar('limitstart' . $listid);
		$length = JRequest::getVar('limit' . $listid);

		$oldtotal = $model->getTotalRecords();
		$model->setId($listid);
		$model->deleteRows($ids);

		$total = $oldtotal - count($ids);

		$ref = JRequest::getVar('fabrik_referrer', "index.php?option=com_fabrik&view=table&listid=$listid", 'post');
		if ($total >= $limitstart)
		{
			$newlimitstart = $limitstart - $length;
			if ($newlimitstart < 0)
			{
				$newlimitstart = 0;
			}
			$ref = str_replace("limitstart$listid=$limitstart", "limitstart$listid=$newlimitstart", $ref);
			$app = JFactory::getApplication();
			$context = 'com_fabrik.list.' . $model->getRenderContext() . '.';
			$app->setUserState($context . 'limitstart', $newlimitstart);
		}
		if (JRequest::getVar('format') == 'raw')
		{
			JRequest::setVar('view', 'list');

			$this->display();
		}
		else
		{
			// @TODO: test this
			$app->redirect($ref, count($ids) . " " . JText::_('COM_FABRIK_RECORDS_DELETED'));
		}
	}
}
