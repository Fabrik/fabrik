<?php
/**
 * Form controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controllerform');

require_once 'fabcontrollerform.php';

/**
 * Form controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabrikAdminControllerForm extends FabControllerForm
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var	string
	 */
	protected $text_prefix = 'COM_FABRIK_FORM';

	/**
	 * Is in J content plugin
	 *
	 * @var bool
	 */
	public $isMambot = false;

	/**
	 * Used from content plugin when caching turned on to ensure correct element rendered)
	 *
	 * @var int
	 */
	protected $cacheId = 0;

	/**
	 * Show the form in the admin
	 *
	 * @return null
	 */

	public function view()
	{
		$document = JFactory::getDocument();
		$app = JFactory::getApplication();
		$input = $app->input;
		$model = JModelLegacy::getInstance('Form', 'FabrikFEModel');
		$viewType = $document->getType();
		$this->setPath('view', COM_FABRIK_FRONTEND . '/views');
		$viewLayout = $input->get('layout', 'default');
		$this->name = 'Fabrik';
		$view = $this->getView('Form', $viewType, '');
		$view->setModel($model, true);
		$view->isMambot = $this->isMambot;

		// Set the layout
		$view->setLayout($viewLayout);

		// @TODO check for cached version
		JToolBarHelper::title(FText::_('COM_FABRIK_MANAGER_FORMS'), 'forms.png');

		$view->display();

		return;

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
			$cache = JFactory::getCache('com_fabrik', 'view');
			ob_start();
			$cache->get($view, 'display', $cacheid);
			$contents = ob_get_contents();
			ob_end_clean();
			$token = JSession::getFormToken();
			$search = '#<input type="hidden" name="[0-9a-f]{32}" value="1" />#';
			$replacement = '<input type="hidden" name="' . $token . '" value="1" />';
			echo preg_replace($search, $replacement, $contents);
		}

		FabrikAdminHelper::addSubmenu($input->get('view', 'lists', 'word'));
	}

	/**
	 * Handle saving posted form data from the admin pages
	 *
	 * @return  null
	 */

	public function process()
	{
		$this->name = 'Fabrik';
		$app = JFactory::getApplication();
		$input = $app->input;
		$document = JFactory::getDocument();
		$viewName = $input->get('view', 'form');
		$viewType = $document->getType();
		$this->setPath('view', COM_FABRIK_FRONTEND . '/views');
		$view = $this->getView($viewName, $viewType);

		if ($model = JModelLegacy::getInstance('Form', 'FabrikFEModel'))
		{
			$view->setModel($model, true);
		}

		$model->setId($input->getInt('formid', 0));

		$this->isMambot = $input->get('_isMambot', 0);
		$model->getForm();
		$model->rowId = $input->get('rowid', '', 'string');

		// Check for request forgeries
		if ($model->spoofCheck())
		{
			JSession::checkToken() or die('Invalid Token');
		}

		$validated = $model->validate();

		if (!$validated)
		{
			$this->handleError($view, $model);

			return;
		}

		// Reset errors as validate() now returns ok validations as empty arrays
		$model->clearErrors();
		$model->process();

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

		$listModel = $model->getListModel();
		$tid = $listModel->getTable()->id;

		$res = $model->getRedirectURL(true, $this->isMambot);
		$this->baseRedirect = $res['baseRedirect'];
		$url = $res['url'];

		$msg = $model->getRedirectMessage($model);

		if ($input->getInt('packageId') !== 0)
		{
			$rowid = $input->get('rowid', '', 'string');
			echo json_encode(array('msg' => $msg, 'rowid' => $rowid));

			return;
		}

		if ($input->get('format') == 'raw')
		{
			$url = COM_FABRIK_LIVESITE . 'index.php?option=com_fabrik&view=list&format=raw&listid=' . $tid;
			$this->setRedirect($url, $msg);
		}
		else
		{
			$this->setRedirect($url, $msg);
		}
	}

	/**
	 * Handle the view error
	 *
	 * @param   JView   $view   View
	 * @param   JModel  $model  Form Model
	 *
	 * @since   3.1b
	 *
	 * @return  void
	 */
	protected function handleError($view, $model)
	{
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$input = $app->input;
		$validated = false;

		// If its in a module with ajax or in a package or inline edit
		if ($input->get('fabrik_ajax'))
		{
			if ($input->getInt('elid') !== 0)
			{
				// Inline edit
				$eMsgs = array();
				$errs = $model->getErrors();

				// Only raise errors for fields that are present in the inline edit plugin
				$toValidate = array_keys($input->get('toValidate', array(), 'array'));

				foreach ($errs as $errorKey => $e)
				{
					if (in_array($errorKey, $toValidate) && count($e[0]) > 0)
					{
						array_walk_recursive($e, array('FabrikString', 'forHtml'));
						$eMsgs[] = count($e[0]) === 1 ? '<li>' . $e[0][0] . '</li>' : '<ul><li>' . implode('</li><li>', $e[0]) . '</ul>';
					}
				}

				if (!empty($eMsgs))
				{
					$eMsgs = '<ul>' . implode('</li><li>', $eMsgs) . '</ul>';
					header('HTTP/1.1 500 ' . FText::_('COM_FABRIK_FAILED_VALIDATION') . $eMsgs);
					jexit();
				}
				else
				{
					$validated = true;
				}
			}
			else
			{
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
				if ($input->get('usekey') !== '')
				{
					$input->set('rowid', -1);
				}

				$view->display();
			}

			return;
		}
	}

	/**
	 * Save a form's page to the session table
	 *
	 * @return  null
	 */

	protected function savepage()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$model = $this->getModel('Formsession', 'FabrikFEModel');
		$formModel = $this->getModel('Form', 'FabrikFEModel');
		$formModel->setId($input->getInt('formid'));
		$model->savePage($formModel);
	}

	/**
	 * Generic function to redirect
	 *
	 * @param   object  &$model  form model
	 * @param   string  $msg     optional redirect message
	 *
	 * @deprecated - since 3.0.6 not used
	 * @return  null
	 */

	protected function makeRedirect(&$model, $msg = null)
	{
		$app = JFactory::getApplication();
		$input = $app->input;

		if (is_null($msg))
		{
			$msg = FText::_('COM_FABRIK_RECORD_ADDED_UPDATED');
		}

		if (array_key_exists('apply', $model->formData))
		{
			$page = 'index.php?option=com_fabrik&task=form.view&formid=' . $input->getInt('formid') . '&listid=' . $input->getInt('listid')
				. '&rowid=' . $input->getString('rowid', '', 'string');
		}
		else
		{
			$page = 'index.php?option=com_fabrik&task=list.view&listid=' . $model->getlistModel()->getTable()->id;
		}

		$this->setRedirect($page, $msg);
	}

	/**
	 * CCK - not used atm
	 *
	 * @return void
	 */

	public function cck()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$catid = $input->getInt('catid');
		$db = JFactory::getDBO();
		$db->setQuery('SELECT id FROM #__fabrik_forms WHERE params LIKE \'%"cck_category":"' . $catid . '"%\'');
		$id = $db->loadResult();

		if (!$id)
		{
			throw new RuntimeException(FText::_('SET_FORM_CCK_CATEGORY'));
		}

		$input->set('formid', $id);

		// Tell fabrik to load js scripts normally
		$input->set('iframe', 1);
		$this->view();
	}

	/**
	 * Delete a record from a form
	 *
	 * @since 3.0.6.2
	 *
	 * @return  null
	 */

	public function delete()
	{
		// Check for request forgeries
		JSession::checkToken() or die('Invalid Token');
		$app = JFactory::getApplication();
		$input = $app->input;
		$model = $this->getModel('list', 'FabrikFEModel');
		$ids = array($input->get('rowid', 0, 'string'));

		$listid = $input->get('listid');
		$limitstart = $input->getInt('limitstart' . $listid);
		$length = $input->getInt('limit' . $listid);

		$oldtotal = $model->getTotalRecords();
		$model->setId($listid);
		$ok = $model->deleteRows($ids);

		$total = $oldtotal - count($ids);

		$ref = 'index.php?option=com_fabrik&task=list.view&listid=' . $listid;

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

		if ($input->get('format') == 'raw')
		{
			$input->set('view', 'list');

			$this->display();
		}
		else
		{
			$msg = $ok ? count($ids) . ' ' . FText::_('COM_FABRIK_RECORDS_DELETED') : '';
			$app->enqueueMessage($msg);
			$app->redirect($ref);
		}
	}
}
