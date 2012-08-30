<?php
/**
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Pollen 8 Design Ltd. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.controller');

/**
 * Fabrik List Controller
 *
 * @static
 * @package		Joomla
 * @subpackage	Fabrik
 * @since 1.5
 */

class FabrikControllerList extends JController
{

	/* @var int  id used from content plugin when caching turned on to ensure correct element rendered)*/
	var $cacheId = 0;

	/**
	 * Display the view
	 */

	public function display($model = false, $urlparams = false)
	{
		$document = JFactory::getDocument();
		$viewName = JRequest::getVar('view', 'list', 'default', 'cmd');
		$modelName = $viewName;
		$layout = JRequest::getWord('layout', 'default');
		$viewType = $document->getType();
		if ($viewType == 'pdf')
		{
			// in PDF view only shown the main component content.
			JRequest::setVar('tmpl', 'component');
		}
		// Set the default view name from the Request
		$view = $this->getView($viewName, $viewType);
		$view->setLayout($layout);
		// Push a model into the view
		if (is_null($model) || $model == false)
		{
			$model = $this->getModel($modelName, 'FabrikFEModel');
		}
		if (!JError::isError($model) && is_object($model))
		{
			$view->setModel($model, true);
		}
		// Display the view
		$view->assign('error', $this->getError());

		$post = JRequest::get('post');
		//build unique cache id on url, post and user id
		$user = JFactory::getUser();
		$cacheid = serialize(array(JRequest::getURI(), $post, $user->get('id'), get_class($view), 'display', $this->cacheId));
		$cache = JFactory::getCache('com_fabrik', 'view');
		// f3 cache with raw view gives error
		if (in_array(JRequest::getCmd('format'), array('raw', 'csv', 'pdf', 'json', 'fabrikfeed')))
		{
			$view->display();
		}
		else
		{
			$cache->get($view, 'display', $cacheid);
		}
	}

	/**
	 * reorder the data in the list
	 * @return null
	 */

	function order()
	{
		$modelName = JRequest::getVar('view', 'list', 'default', 'cmd');
		$model = $this->getModel($modelName, 'FabrikFEModel');
		$model->setId(JRequest::getInt('listid'));
		$model->setOrderByAndDir();
		// $$$ hugh - unset 'resetfilters' in case it was set on QS of original list load.
		JRequest::setVar('resetfilters', 0);
		JRequest::setVar('clearfilters', 0);
		$this->display();
	}

	/**
	 * clear filters
	 */

	function clearfilter()
	{
		$app = JFactory::getApplication();
		$app->enqueueMessage(JText::_('COM_FABRIK_FILTERS_CLEARED'));
		// $$$ rob 28/12/20111 changed from clearfilters as clearfilters removes jpluginfilters (filters
		// set by content plugin which we want to remain sticky. Otherwise list clear button removes the
		// content plugin filters
		//JRequest::setVar('resetfilters', 1);

		// $$ rob 07/02/2012 if reset filters set in the menu options then filters not cleared
		// so instead use replacefilters which doesnt look at the menu item parameters.
		JRequest::setVar('replacefilters', 1);
		$this->filter();
	}

	/**
	 * filter the list data
	 * @return null
	 */

	function filter()
	{
		$modelName = JRequest::getVar('view', 'list', 'default', 'cmd');
		$model = $this->getModel($modelName, 'FabrikFEModel');
		$model->setId(JRequest::getInt('listid'));
		FabrikHelperHTML::debug('', 'list model: getRequestData');
		$request = $model->getRequestData();
		$model->storeRequestData($request);
		// $$$ rob pass in the model otherwise display() rebuilds it and the request data is rebuilt
		return $this->display($model);
	}

	/**
	 * delete rows from list
	 */

	function delete()
	{
		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');
		$app = JFactory::getApplication();
		$model = $this->getModel('list', 'FabrikFEModel');
		$ids = JRequest::getVar('ids', array(), 'request', 'array');
		$listid = JRequest::getInt('listid');
		$limitstart = JRequest::getInt('limitstart'. $listid);
		$length = JRequest::getInt('limit' . $listid);

		$model->setId($listid);
		$oldtotal = $model->getTotalRecords();
		$model->deleteRows($ids);

		$total = $oldtotal - count($ids);

		$ref = JRequest::getVar('fabrik_referrer', 'index.php?option=com_fabrik&view=list&listid=' . $listid, 'post');
		// $$$ hugh - for some reason fabrik_referrer is sometimes empty, so a little defensive coding ...
		if (empty($ref))
		{
			$ref = JRequest::getVar('HTTP_REFERER', 'index.php?option=com_fabrik&view=list&listid=' . $listid, 'server');
		}
		if ($total >= $limitstart)
		{
			$newlimitstart = $limitstart - $length;
			if ($newlimitstart < 0)
			{
				$newlimitstart = 0;
			}
			$ref = str_replace('limitstart' . $listid . '=  . $limitstart', 'limitstart' . $listid . '=' . $newlimitstart, $ref);
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
			//@TODO: test this
			$app->redirect($ref, count($ids) . ' ' . JText::_('COM_FABRIK_RECORDS_DELETED'));
		}
	}

	/**
	 * empty a table of records and reset its key to 0
	 */

	function doempty()
	{
		$model = $this->getModel('list', 'FabrikFEModel');
		$model->truncate();
		$this->display();
	}

	/**
	 * run a list plugin
	 */

	function doPlugin()
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
		//if showing n tables in article page then ensure that only activated table runs its plugin
		if (JRequest::getInt('id') == $model->get('id') || JRequest::getVar('origid', '') == '')
		{
			$msgs = $model->processPlugin();
			if (JRequest::getVar('format') == 'raw')
			{
				JRequest::setVar('view', 'list');
				$model->setRenderContext($model->getId());
				$context = 'com_fabrik.list' . $model->getRenderContext() . '.msg';
				$session = JFactory::getSession();
				$session->set($context, implode("\n", $msgs));
			}
			else
			{
				foreach ($msgs as $msg)
				{
					$app->enqueueMessage($msg);
				}
			}
		}
		//3.0 use redirect rather than calling view() as that gave an sql error (joins seemed not to be loaded for the list)
		$format = JRequest::getVar('format', 'html');
		$defaultRef = 'index.php?option=com_fabrik&view=list&listid=' . $model->getId() . '&format=' . $format;
		if ($format !== 'raw')
		{
			$ref = JRequest::getVar('fabrik_referrer', $defaultRef, 'post');
			// for some reason fabrik_referrer is sometimes empty, so a little defensive coding ...
			if (empty($ref))
			{
				$ref = JRequest::getVar('HTTP_REFERER', $defaultRef, 'server');
			}
		}
		else
		{
			$ref = $defaultRef;
		}
		$app->redirect($ref);
	}

	/**
	 * called via ajax when element selected in advanced search popup window
	 */

	function elementFilter()
	{
		$id = JRequest::getInt('id');
		$model = $this->getModel('list', 'FabrikFEModel');
		$model->setId($id);
		echo $model->getAdvancedElementFilter();
	}

}
?>