<?php
/**
 * List controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once 'fabcontrollerform.php';

/**
 * Admin List controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabrikAdminControllerList extends FabControllerForm
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var	string
	 */
	protected $text_prefix = 'COM_FABRIK_LIST';

	/**
	 * Used from content plugin when caching turned on to ensure correct element rendered)
	 * @var int
	 */
	protected $cacheId = 0;

	/**
	 * Method to edit an existing record.
	 *
	 * @param   string  $key     The name of the primary key of the URL variable.
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key
	 * (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if access level check and checkout passes, false otherwise.
	 */

	public function edit($key = null, $urlVar = null)
	{
		$model = $this->getModel('connections');

		if (count($model->activeConnections()) == 0)
		{
			throw new RuntimeException(FText::_('COM_FABRIK_ENUSRE_ONE_CONNECTION_PUBLISHED'));
		}

		parent::edit($key, $urlVar);
	}

	/**
	 * Set up a confirmation screen asking about renaming the list you want to copy
	 *
	 * @return mixed notice or null
	 */

	public function copy()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$cid = $input->get('cid', array(0), 'array');
		$model = JModelLegacy::getInstance('list', 'FabrikFEModel');

		if (count($cid) > 0)
		{
			$viewType = JFactory::getDocument()->getType();
			$view = $this->getView($this->view_item, $viewType, '');
			$view->setModel($model, true);
			$view->confirmCopy('confirm_copy');
		}
		else
		{
			return JError::raiseWarning(500, FText::_('NO ITEMS SELECTED'));
		}
	}

	/**
	 * Actually copy the list
	 *
	 * @return  null
	 */

	public function doCopy()
	{
		// Check for request forgeries
		JSession::checkToken() or die('Invalid Token');
		$app = JFactory::getApplication();
		$input = $app->input;
		$model = $this->getModel();
		$model->copy();
		$ntext = $this->text_prefix . '_N_ITEMS_COPIED';
		$this->setMessage(JText::plural($ntext, count($input->get('cid', array(), 'array'))));
		$this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
	}

	/**
	 * Show the lists data in the admin
	 *
	 * @param   object  $model  list model
	 *
	 * @return  null
	 */

	public function view($model = null)
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$cid = $input->get('cid', array(0), 'array');
		$cid = $cid[0];

		if (is_null($model))
		{
			$cid = $input->getInt('listid', $cid);

			// Grab the model and set its id
			$model = JModelLegacy::getInstance('List', 'FabrikFEModel');
			$model->setState('list.id', $cid);
		}

		$app = JFactory::getApplication();
		$input = $app->input;

		$viewType = JFactory::getDocument()->getType();

		// Use the front end renderer to show the table
		$this->setPath('view', COM_FABRIK_FRONTEND . '/views');
		$viewLayout = $input->getWord('layout', 'default');
		$view = $this->getView($this->view_item, $viewType, 'FabrikView');
		$view->setModel($model, true);

		// Set the layout
		$view->setLayout($viewLayout);
		JToolBarHelper::title(FText::_('COM_FABRIK_MANAGER_LISTS'), 'lists.png');

		// Build unique cache id on url, post and user id
		$user = JFactory::getUser();
		$uri = JURI::getInstance();
		$uri = $uri->toString(array('path', 'query'));
		$cacheid = serialize(array($uri, $input->post, $user->get('id'), get_class($view), 'display', $this->cacheId));
		$cache = JFactory::getCache('com_fabrik', 'view');

		if (in_array($input->get('format'), array('raw', 'csv', 'pdf', 'json', 'fabrikfeed')))
		{
			$view->display();
		}
		else
		{
			$cache->get($view, 'display', $cacheid);
		}

		FabrikAdminHelper::addSubmenu($input->getWord('view', 'lists'));
	}

	/**
	 * Show the elements associated with the list
	 *
	 * @return  void
	 */
	public function showLinkedElements()
	{
		$document = JFactory::getDocument();
		$app = JFactory::getApplication();
		$input = $app->input;
		$cid = $input->get('cid', array(0), 'array');
		$model = JModelLegacy::getInstance('List', 'FabrikFEModel');
		$model->setState('list.id', $cid[0]);
		$formModel = $model->getFormModel();
		$viewType = $document->getType();
		$viewLayout = $input->getWord('layout', 'linked_elements');
		$view = $this->getView($this->view_item, $viewType, '');
		$view->setModel($model, true);
		$view->setModel($formModel);

		// Set the layout
		$view->setLayout($viewLayout);
		$view->showLinkedElements();
	}

	/**
	 * actally delete the requested lists forms etc
	 * // $$$ rob refractored to FabControllerAdmin
	 */

	/*public function dodelete()
	 {
	}
	 */

	/**
	 * Order the lists
	 *
	 * @return  null
	 */

	public function order()
	{
		// Check for request forgeries
		JSession::checkToken() or die('Invalid Token');
		$app = JFactory::getApplication();
		$input = $app->input;
		$model = JModelLegacy::getInstance('List', 'FabrikFEModel');
		$id = $input->getInt('listid');
		$model->setId($id);
		$input->set('cid', $id);
		$model->setOrderByAndDir();

		// $$$ hugh - unset 'resetfilters' in case it was set on QS of original table load.
		$input->set('resetfilters', 0);
		$input->set('clearfilters', 0);
		$this->view();
	}

	/**
	 * Clear filters
	 *
	 * @return  null
	 */

	public function clearfilter()
	{
		$app = JFactory::getApplication();
		$app->enqueueMessage(FText::_('COM_FABRIK_FILTERS_CLEARED'));
		$this->filter();
	}

	/**
	 * Filter the list data
	 *
	 * @return  void
	 */

	public function filter()
	{
		// Check for request forgeries
		JSession::checkToken() or die('Invalid Token');
		$app = JFactory::getApplication();
		$input = $app->input;
		$model = JModelLegacy::getInstance('List', 'FabrikFEModel');
		$id = $input->get('listid');
		$model->setId($id);
		$input->set('cid', $id);
		$request = $model->getRequestData();
		$model->storeRequestData($request);

		// $$$ rob pass in the model otherwise display() rebuilds it and the request data is rebuilt
		$this->view($model);
	}

	/**
	 * Delete rows from table
	 *
	 * @return  null
	 */

	public function delete()
	{
		// Check for request forgeries
		JSession::checkToken() or die('Invalid Token');
		$app = JFactory::getApplication();
		$input = $app->input;
		$model = JModelLegacy::getInstance('List', 'FabrikFEModel');
		$listid = $input->getInt('listid');
		$model->setId($listid);
		$ids = $input->get('ids', array(), 'array');
		$limitstart = $input->getInt('limitstart' . $listid);
		$length = $input->getInt('limit' . $listid);
		$oldtotal = $model->getTotalRecords();
		$ok = $model->deleteRows($ids);
		$total = $oldtotal - count($ids);
		$ref = 'index.php?option=com_fabrik&task=list.view&cid=' . $listid;

		if ($total >= $limitstart)
		{
			$newlimitstart = $limitstart - $length;

			if ($newlimitstart < 0)
			{
				$newlimitstart = 0;
			}

			$ref = str_replace('limitstart' . $listid . '=' . $limitstart, 'limitstart' . $listid . '=' . $newlimitstart, $ref);
			$context = 'com_fabrik.list' . $model->getRenderContext() . '.list.';
			$app->setUserState($context . 'limitstart' . $listid, $newlimitstart);
		}

		if ($input->get('format') == 'raw')
		{
			$input->set('view', 'list');
			$this->view();
		}
		else
		{
			$msg = $ok ? count($ids) . ' ' . FText::_('COM_FABRIK_RECORDS_DELETED') : '';
			$app->enqueueMessage($msg);
			$app->redirect($ref);
		}
	}

	/**
	 * Empty a table of records and reset its key to 0
	 *
	 * @return  null
	 */

	public function doempty()
	{
		$model = $this->getModel('list', 'FabrikFEModel');
		$app = JFactory::getApplication();
		$input = $app->input;
		$model->truncate();
		$listid = $input->getInt('listid');
		$ref = $input->get('fabrik_referrer', 'index.php?option=com_fabrik&view=list&cid=' . $listid, 'string');
		$this->setRedirect($ref);
	}

	/**
	 * Run a list plugin
	 *
	 * @return  null
	 */

	public function doPlugin()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$cid = $input->get('cid', array(0), 'array');
		$cid = $cid[0];
		$model = $this->getModel('list', 'FabrikFEModel');
		$model->setId($input->getInt('listid', $cid));

		// $$$ rob need to ask the model to get its data here as if the plugin calls $model->getData
		// then the other plugins are recalled which makes the current plugins params incorrect.
		$model->setLimits();
		$model->getData();

		// If showing n tables in article page then ensure that only activated table runs its plugin
		if ($input->getInt('id') == $model->get('id') || $input->get('origid', '', 'string') == '')
		{
			$msgs = $model->processPlugin();

			if ($input->get('format') == 'raw')
			{
				$input->set('view', 'list');
			}
			else
			{
				foreach ($msgs as $msg)
				{
					$app->enqueueMessage($msg);
				}
			}
		}

		$format = $input->get('fromat', 'html');
		$ref = 'index.php?option=com_fabrik&task=list.view&listid=' . $model->getId() . '&format=' . $format;
		$app->redirect($ref);
	}
}
