<?php
/**
 * Fabrik Details Controller
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

/**
 * Fabrik Details Controller
 *
 * @static
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       1.5
 */

class FabrikControllerDetails extends JControllerLegacy
{
	/**
	 * Is the view rendered from the J content plugin
	 *
	 * @var  bool
	 */
	public $isMambot = false;

	/**
	 * Id used from content plugin when caching turned on to ensure correct element rendered)
	 *
	 * @var  int
	 */
	public $cacheId = 0;

	/**
	 * Display the view
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached - NOTE not actually used to control caching!!!
	 * @param   array    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  JController  A JController object to support chaining.
	 */

	public function display($cachable = false, $urlparams = false)
	{
		$session = JFactory::getSession();
		$document = JFactory::getDocument();
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$input = $app->input;
		$viewName = $input->get('view', 'form');
		$modelName = $viewName;

		if ($viewName == 'emailform')
		{
			$modelName = 'form';
		}

		$viewName = 'form';
		$modelName = 'form';

		$viewType = $document->getType();

		if ($viewType == 'pdf')
		{
			// In PDF view only shown the main component content.
			$input->set('tmpl', 'component');
		}

		// Set the default view name from the Request
		$view = $this->getView($viewName, $viewType);

		// Push a model into the view
		$model = !isset($this->_model) ? $this->getModel($modelName, 'FabrikFEModel') : $this->_model;

		// If errors made when submitting from a J plugin they are stored in the session lets get them back and insert them into the form model
		if (!empty($model->errors))
		{
			$context = 'com_' . $package . '.form.' . $input->getInt('formid');
			$model->errors = $session->get($context . '.errors', array());
			$session->clear($context . '.errors');
		}

		$view->setModel($model, true);
		$view->isMambot = $this->isMambot;

		// Get data as it will be needed for ACL when testing if current row is editable.
		// $model->getData();

		// Display the view
		$view->error = $this->getError();

		if (in_array($input->get('format'), array('raw', 'csv', 'pdf')))
		{
			$view->display();
		}
		else
		{
			$user = JFactory::getUser();
			$uri = JURI::getInstance();
			$uri = $uri->toString(array('path', 'query'));
			$cacheid = serialize(array($uri, $input->post, $user->get('id'), get_class($view), 'display', $this->cacheId));
			$cache = JFactory::getCache('com_' . $package, 'view');
			echo $cache->get($view, 'display', $cacheid);
		}
	}

	/**
	 * process the form
	 *
	 * @return  null
	 */

	public function process()
	{
		@set_time_limit(300);
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$input = $app->input;
		$document = JFactory::getDocument();
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
		$model->setRowId($input->get('rowid', '', 'string'));

		// Check for request forgeries
		$fbConfig = JComponentHelper::getParams('com_fabrik');

		if ($model->getParams()->get('spoof_check', $fbConfig->get('spoofcheck_on_formsubmission', true)) == true)
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
					/**
					 * $$$ rob - http://fabrikar.com/forums/showthread.php?t=17962
					 * couldn't determine the exact set up that triggered this, but we need to reset the rowid to -1
					 * if reshowing the form, otherwise it may not be editable, but rather show as a detailed view
					 */
					if ($input->get('usekey', '') !== '')
					{
						$input->set('rowid', -1);
					}

					$view->display();
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
			FabrikWorker::getPluginManager()->runPlugins('onError', $model);
			$view->display();

			return;
		}

		/**
		 * $$$ rob 31/01/2011
		 * Now redirect always occurs even with redirect thx message, $this->setRedirect
		 * will look up any redirect url specified in the session by a plugin and use that or
		 * fall back to the url defined in $this->makeRedirect()
		 */

		$listModel = $model->getListModel();
		$listModel->set('_table', null);

		$msg = $model->getSuccessMsg();

		if ($input->getInt('packageId') !== 0)
		{
			echo json_encode(array('msg' => $msg));

			return;
		}

		if ($input->get('format') == 'raw')
		{
			$input->set('view', 'list');
			$this->display();

			return;
		}
		else
		{
			$this->makeRedirect($model, $msg);
		}
	}

	/**
	 * Set the redirect url
	 *
	 * @param   string  $url   default url
	 * @param   string  $msg   optional message to apply on redirect
	 * @param   string  $type  optional message type
	 *
	 * @return  null
	 */

	public function setRedirect($url, $msg = null, $type = 'message')
	{
		$session = JFactory::getSession();
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$formdata = $session->get('com_' . $package . '.form.data');
		$context = 'com_' . $package . '.form.' . $formdata['fabrik'] . '.redirect.';

		// If the redirect plug-in has set a url use that in preference to the default url
		$surl = $session->get($context . 'url', array($url));

		if (!is_array($surl))
		{
			$surl = array($surl);
		}

		if (empty($surl))
		{
			$surl[] = $url;
		}

		$smsg = $session->get($context . 'msg', array($msg));

		if (!is_array($smsg))
		{
			$smsg = array($smsg);
		}

		if (empty($smsg))
		{
			$smsg[] = $msg;
		}

		$url = array_shift($surl);
		$msg = array_shift($smsg);

		$app = JFactory::getApplication();
		$q = $app->getMessageQueue();
		$found = false;

		foreach ($q as $m)
		{
			// Custom message already queued - unset default msg
			if ($m['type'] == 'message' && trim($m['message']) !== '')
			{
				$found = true;
				break;
			}
		}

		if ($found)
		{
			$msg = null;
		}

		$session->set($context . 'url', $surl);
		$session->set($context . 'msg', $smsg);
		$showmsg = array_shift($session->get($context . 'showsystemmsg', array(true)));
		$msg = $showmsg ? $msg : null;
		parent::setRedirect($url, $msg, $type);
	}

	/**
	 * generic function to redirect
	 *
	 * @param   object  &$model  form model
	 * @param   string  $msg     redirection message to show
	 *
	 * @return  null
	 */

	protected function makeRedirect(&$model, $msg = null)
	{
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$input = $app->input;
		$formId = $input->getInt('formid');
		$listId = $input->getInt('listid');
		$rowId = $input->getString('rowid');

		if (is_null($msg))
		{
			$msg = FText::_('COM_FABRIK_RECORD_ADDED_UPDATED');
		}

		if ($app->isAdmin())
		{
			// Admin links use com_fabrik over package option
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
					$url = JArrayHelper::getvalue($_SERVER, 'HTTP_REFERER', 'index.php');
				}
				else
				{
					// Return to the page that called the form
					$url = urldecode($input->post->get('fabrik_referrer', 'index.php', 'string'));
				}

				$Itemid = FabrikWorker::itemId();

				if ($url == '')
				{
					$url = 'index.php?option=com_' . $package . '&Itemid=' . $Itemid;
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

	/**
	 * validate via ajax
	 *
	 * @return  null
	 */

	public function ajax_validate()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
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
	 * save a form's page to the session table
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
	 * clear down any temp db records or cookies
	 * containing partially filled in form data
	 *
	 * @return  null
	 */

	public function removeSession()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$sessionModel = $this->getModel('Formsession', 'FabrikFEModel');
		$sessionModel->setFormId($input->getInt('formid', 0));
		$sessionModel->setRowId($input->get('rowid', '', 'string'));
		$sessionModel->remove();
		$this->display();
	}

	/**
	 * called via ajax to page through form records
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
	 * delete a record from a form
	 *
	 * @return  null
	 */

	public function delete()
	{
		// Check for request forgeries
		JSession::checkToken() or die('Invalid Token');
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$input = $app->input;
		$model = $this->getModel('list', 'FabrikFEModel');
		$ids = array($input->get('rowid', 0, 'string'));

		$listid = $input->getInt('listid');
		$limitstart = $input->getInt('limitstart' . $listid);
		$length = $input->getInt('limit' . $listid);

		$oldtotal = $model->getTotalRecords();
		$model->setId($listid);
		$model->deleteRows($ids);

		$total = $oldtotal - count($ids);

		$ref = $input->get('fabrik_referrer', "index.php?option=com_' . $package . '&view=table&listid=$listid", 'string');

		if ($total >= $limitstart)
		{
			$newlimitstart = $limitstart - $length;

			if ($newlimitstart < 0)
			{
				$newlimitstart = 0;
			}

			$ref = str_replace("limitstart$listid=$limitstart", "limitstart$listid=$newlimitstart", $ref);
			$app = JFactory::getApplication();
			$context = 'com_' . $package . '.list.' . $model->getRenderContext() . '.';
			$app->setUserState($context . 'limitstart', $newlimitstart);
		}

		if ($input->get('format') == 'raw')
		{
			$input->set('view', 'list');
			$this->display();
		}
		else
		{
			// @TODO: test this
			$app->enqueueMessage(count($ids) . " " . FText::_('COM_FABRIK_RECORDS_DELETED'));
			$app->redirect($ref);
		}
	}
}
