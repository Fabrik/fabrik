<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controllerform');

/**
 * Form controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabrikControllerForm extends JControllerForm
{
	/**
	 * @var		string	The prefix to use with controller messages.
	 * @since	1.6
	 */
	protected $text_prefix = 'COM_FABRIK_FORM';

	public $isMambot = false;

	/* @var int  id used from content plugin when caching turned on to ensure correct element rendered)*/
	protected $cacheId = 0;

	/**
	 * Show the form in the admin
	 *
	 * @return null
	 */

	public function view()
	{
		$document = JFactory::getDocument();
		$model = JModel::getInstance('Form', 'FabrikFEModel');
		$viewType = $document->getType();
		$this->setPath('view', COM_FABRIK_FRONTEND . '/views');
		$viewLayout = JRequest::getCmd('layout', 'default');
		$view = $this->getView('form', $viewType, '');
		$view->setModel($model, true);
		$view->isMambot = $this->isMambot;

		// Set the layout
		$view->setLayout($viewLayout);

		// @TODO check for cached version
		JToolBarHelper::title(JText::_('COM_FABRIK_MANAGER_FORMS'), 'forms.png');

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
		FabrikHelper::addSubmenu(JRequest::getWord('view', 'lists'));
	}

	/**
	 * Handle saving posted form data from the admin pages
	 *
	 * @return  null
	 */

	public function process()
	{
		$model = JModel::getInstance('Form', 'FabrikFEModel');
		$document = JFactory::getDocument();
		$viewName = JRequest::getVar('view', 'form', 'default', 'cmd');
		$viewType = $document->getType();
		$this->setPath('view', COM_FABRIK_FRONTEND . '/views');
		$view = $this->getView($viewName, $viewType);
		if (!JError::isError($model))
		{
			$view->setModel($model, true);
		}
		$model->setId(JRequest::getInt('formid', 0));

		$this->isMambot = JRequest::getVar('_isMambot', 0);
		$model->getForm();
		$model->_rowId = JRequest::getVar('rowid', '');

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
					echo $model->getJsonErrors();
					return;
				}
			}
			$this->savepage();

			if ($this->isMambot)
			{
				JRequest::setVar('fabrik_referrer', JArrayHelper::getValue($_SERVER, 'HTTP_REFERER', ''), 'post');
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

		// Check if any plugin has created a new validation error
		if ($model->hasErrors())
		{
			FabrikWorker::getPluginManager()->runPlugins('onError', $model);
			$view->display();
			return;
		}

		$listModel = $model->getListModel();
		$tid = $listModel->getTable()->id;

		$res = $model->getRedirectURL(true, $this->isMambot);
		$this->baseRedirect = $res['baseRedirect'];
		$url = $res['url'];

		$msg = $model->getRedirectMessage($model);

		if (JRequest::getInt('_packageId') !== 0)
		{
			$rowid = JRequest::getInt('rowid');
			echo json_encode(array('msg' => $msg, 'rowid' => $rowid));
			return;
		}
		if (JRequest::getVar('format') == 'raw')
		{
			$url = COM_FABRIK_LIVESITE . '/index.php?option=com_fabrik&view=list&format=raw&listid=' . $tid;
			$this->setRedirect($url, $msg);
		}
		else
		{
			$this->setRedirect($url, $msg);
		}
	}

	/**
	 * Save a form's page to the session table
	 *
	 * @return  null
	 */

	protected function savepage()
	{
		$model = $this->getModel('Formsession', 'FabrikFEModel');
		$formModel = $this->getModel('Form', 'FabrikFEModel');
		$formModel->setId(JRequest::getInt('formid'));
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
		if (is_null($msg))
		{
			$msg = JText::_('COM_FABRIK_RECORD_ADDED_UPDATED');
		}
		if (array_key_exists('apply', $model->_formData))
		{
			$page = 'index.php?option=com_fabrik&task=form.view&formid=' . JRequest::getInt('formid') . '&listid=' . JRequest::getInt('listid')
				. '&rowid=' . JRequest::getInt('rowid');
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
		$catid = JRequest::getInt('catid');
		$db = JFactory::getDBO();
		$db->setQuery('SELECT id FROM #__fabrik_forms WHERE params LIKE \'%"cck_category":"' . $catid . '"%\'');

		$id = $db->loadResult();
		if (!$id)
		{
			FabrikHelperHTML::stylesheet('system.css', 'administrator/templates/system/css/');
			echo "<a target=\"_blank\" href=\"index.php?option=com_fabrik&c=form\">" . JText::_('VIEW_FORMS') . "</a>";
			return JError::raiseNotice(500, JText::_('SET_FORM_CCK_CATEGORY'));
		}
		JRequest::setVar('formid', $id);

		// Tell fabrik to load js scripts normally
		JRequest::setVar('iframe', 1);
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
		if (JRequest::getVar('format') == 'raw')
		{
			JRequest::setVar('view', 'list');

			$this->display();
		}
		else
		{
			$app->redirect($ref, count($ids) . " " . JText::_('COM_FABRIK_RECORDS_DELETED'));
		}
	}
}
