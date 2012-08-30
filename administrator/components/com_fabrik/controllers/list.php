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

require_once 'fabcontrollerform.php';

/**
 * List controller class.
 *
 * @package		Joomla.Administrator
 * @subpackage	Fabrik
 * @since		3.0
 */

class FabrikControllerList extends FabControllerForm
{
	/**
	 * @var		string	The prefix to use with controller messages.
	 * @since	1.6
	 */
	protected $text_prefix = 'COM_FABRIK_LIST';

	/* @var int  id used from content plugin when caching turned on to ensure correct element rendered)*/
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
			JError::raiseError(500, JText::_('COM_FABRIK_ENUSRE_ONE_CONNECTION_PUBLISHED'));
			return;
		}
		parent::edit($key, $urlVar);
	}

	/**
	 * set up a confirmation screen asking about renaming the list you want to copy
	 *
	 * @return mixed notice or null
	 */

	public function copy()
	{
		$cid = JRequest::getVar('cid', array(0), 'method', 'array');
		$model = JModel::getInstance('list', 'FabrikFEModel');
		if (count($cid) > 0)
		{
			$viewType = JFactory::getDocument()->getType();
			$view = $this->getView($this->view_item, $viewType, '');
			$view->setModel($model, true);
			$view->confirmCopy('confirm_copy');
		}
		else
		{
			return JError::raiseWarning(500, JText::_('NO ITEMS SELECTED'));
		}
	}

	/**
	 * actually copy the list
	 *
	 * @return  null
	 */

	public function doCopy()
	{
		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');
		$model = $this->getModel();
		$model->copy();
		$ntext = $this->text_prefix . '_N_ITEMS_COPIED';
		$this->setMessage(JText::plural($ntext, count(JRequest::getVar('cid'))));
		$this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
	}

	/**
	 * show the lists data in the admin
	 *
	 * @param   object  $model  list model
	 *
	 * @return  null
	 */

	public function view($model = null)
	{
		$cid = JRequest::getVar('cid', array(0), 'method', 'array');
		if(is_array($cid))
		{
			$cid = $cid[0];
		}
		if (is_null($model))
		{
			$cid = JRequest::getInt('listid', $cid);

			// Grab the model and set its id
			$model = JModel::getInstance('List', 'FabrikFEModel');
			$model->setState('list.id', $cid);
		}
		$viewType = JFactory::getDocument()->getType();

		// Use the front end renderer to show the table
		$this->setPath('view', COM_FABRIK_FRONTEND . '/views');
		$viewLayout = JRequest::getCmd('layout', 'default');
		$view = $this->getView($this->view_item, $viewType, '');
		$view->setModel($model, true);
		// Set the layout
		$view->setLayout($viewLayout);
		JToolBarHelper::title(JText::_('COM_FABRIK_MANAGER_LISTS'), 'lists.png');

		$post = JRequest::get('post');

		// Build unique cache id on url, post and user id
		$user = JFactory::getUser();
		$cacheid = serialize(array(JRequest::getURI(), $post, $user->get('id'), get_class($view), 'display', $this->cacheId));
		$cache = JFactory::getCache('com_fabrik', 'view');
		if (in_array(JRequest::getCmd('format'), array('raw', 'csv', 'pdf', 'json', 'fabrikfeed')))
		{
			$view->display();
		}
		else
		{
			$cache->get($view, 'display', $cacheid);
		}

		FabrikHelper::addSubmenu(JRequest::getWord('view', 'lists'));
	}

	public function showLinkedElements()
	{
		$document = JFactory::getDocument();
		$cid = JRequest::getVar('cid', array(0), 'method', 'array');
		$model = JModel::getInstance('List', 'FabrikFEModel');
		$model->setState('list.id', $cid[0]);
		$formModel = $model->getFormModel();
		$viewType = $document->getType();
		$viewLayout = JRequest::getCmd('layout', 'linked_elements');
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
		JRequest::checkToken() or die('Invalid Token');
		$model = JModel::getInstance('List', 'FabrikFEModel');
		$id = JRequest::getInt('listid');
		$model->setId($id);
		JRequest::setvar('cid', $id);
		$model->setOrderByAndDir();

		// $$$ hugh - unset 'resetfilters' in case it was set on QS of original table load.
		JRequest::setVar('resetfilters', 0);
		JRequest::setVar('clearfilters', 0);
		$this->view();
	}

	/**
	 * clear filters
	 *
	 * @return  null
	 */

	public function clearfilter()
	{
		$app = JFactory::getApplication();
		$app->enqueueMessage(JText::_('COM_FABRIK_FILTERS_CLEARED'));
		$this->filter();
	}

	/**
	 * filter the list data
	 */

	public function filter()
	{
		// Check for request forgeries
		JSession::checkToken() or die('Invalid Token');
		$model = JModel::getInstance('List', 'FabrikFEModel');
		$id = JRequest::getInt('listid');
		$model->setId($id);
		JRequest::setvar('cid', $id);
		$request = $model->getRequestData();
		$model->storeRequestData($request);

		// $$$ rob pass in the model otherwise display() rebuilds it and the request data is rebuilt
		$this->view($model);
	}

	/**
	 * delete rows from table
	 *
	 * @return  null
	 */

	function delete()
	{
		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');
		$app = JFactory::getApplication();
		$model = JModel::getInstance('List', 'FabrikFEModel');
		$listid = JRequest::getInt('listid');
		$model->setId($listid);
		$ids = JRequest::getVar('ids', array(), 'request', 'array');
		$limitstart = JRequest::getVar('limitstart' . $listid);
		$length = JRequest::getVar('limit' . $listid);
		$oldtotal = $model->getTotalRecords();
		$model->deleteRows($ids);
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
		if (JRequest::getVar('format') == 'raw')
		{
			JRequest::setVar('view', 'list');
			$this->view();
		}
		else
		{
			// @TODO: test this
			$app->redirect($ref, count($ids) . ' ' . JText::_('COM_FABRIK_RECORDS_DELETED'));
		}
	}

	/**
	 * empty a table of records and reset its key to 0
	 *
	 * @return  null
	 */

	public function doempty()
	{
		$model = $this->getModel('list', 'FabrikFEModel');
		$model->truncate();
		$listid = JRequest::getInt('listid');
		$ref = JRequest::getVar('fabrik_referrer', 'index.php?option=com_fabrik&view=list&cid=' . $listid, 'post');
		$this->setRedirect($ref);
	}

	/**
	 * run a list plugin
	 *
	 * @return  null
	 */

	public function doPlugin()
	{
		$app = JFactory::getApplication();
		$cid = JRequest::getVar('cid', array(0), 'method', 'array');
		if (is_array($cid))
		{
			$cid = $cid[0];
		}
		$model = $this->getModel('list', 'FabrikFEModel');
		$model->setId(JRequest::getInt('listid', $cid));

		// $$$ rob need to ask the model to get its data here as if the plugin calls $model->getData
		// then the other plugins are recalled which makes the current plugins params incorrect.
		$model->setLimits();
		$model->getData();

		// If showing n tables in article page then ensure that only activated table runs its plugin
		if (JRequest::getInt('id') == $model->get('id') || JRequest::getVar('origid', '') == '')
		{
			$msgs = $model->processPlugin();
			if (JRequest::getVar('format') == 'raw')
			{
				JRequest::setVar('view', 'list');
			}
			else
			{
				foreach ($msgs as $msg)
				{
					$app->enqueueMessage($msg);
				}
			}
		}
		$format = JRequest::getCmd('fromat', 'html');
		$ref = 'index.php?option=com_fabrik&task=list.view&cid[]=' . $model->getId() . '&format=' . $format;
		$app->redirect($ref);
	}

}
