<?php
/**
 * Fabrik Plugin Controller
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use \Joomla\Utilities\ArrayHelper;

jimport('joomla.application.component.controller');

/**
 * Fabrik Plugin Controller
 *
 * @static
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       1.5
 */
class FabrikControllerPlugin extends JControllerLegacy
{
	/**
	 * Id used from content plugin when caching turned on to ensure correct element rendered
	 *
	 * @var  int
	 */
	public $cacheId = 0;

	/**
	 * Ajax action called from element
	 * 11/07/2011 - I've updated things so that any plugin ajax call uses 'view=plugin' rather than controller=plugin
	 * this means that the controller used is now plugin.php and not plugin.raw.php
	 *
	 * @return  null
	 */
	public function pluginAjax()
	{
		$app    = JFactory::getApplication();
		$input  = $app->input;
		$plugin = $input->get('plugin', '');
		$method = $input->get('method', '');
		$group  = $input->get('g', 'element');

		$pluginManager = FabrikWorker::getPluginManager();

		try
		{
			// First lets try the fabrik plugin manager - needed when loading namespaced plugins
			$pluginManager->loadPlugIn($plugin, $group);
		} catch (Exception $e)
		{
			if (!JPluginHelper::importPlugin('fabrik_' . $group, $plugin))
			{
				$o      = new stdClass;
				$o->err = 'unable to import plugin fabrik_' . $group . ' ' . $plugin;
				echo json_encode($o);

				return;
			}
		}

		if (substr($method, 0, 2) !== 'on')
		{
			$method = 'on' . JString::ucfirst($method);
		}

		$dispatcher = JEventDispatcher::getInstance();
		$dispatcher->trigger($method);
	}

	/**
	 * Custom user ajax class handling as per F1.0.x
	 *
	 * @return  null
	 */
	public function userAjax()
	{
		$db = FabrikWorker::getDbo();
		require_once COM_FABRIK_FRONTEND . '/user_ajax.php';
		$app      = JFactory::getApplication();
		$input    = $app->input;
		$method   = $input->get('method', '');
		$userAjax = new userAjax($db);

		if (method_exists($userAjax, $method))
		{
			$userAjax->$method();
		}
	}

	/**
	 * Run the cron job
	 *
	 * @param   object &$pluginManager Fabrik plugin manager
	 *
	 * @return  null
	 */
	public function doCron(&$pluginManager)
	{
		$db    = FabrikWorker::getDbo();
		$app   = JFactory::getApplication();
		$input = $app->input;
		$cid   = $input->get('element_id', array(), 'array');
		$cid   = ArrayHelper::toInteger($cid);

		if (empty($cid))
		{
			return;
		}

		$query = $db->getQuery();
		$query->select('id, plugin')->from('#__{package}_cron');

		if (!empty($cid))
		{
			$query->where(' id IN (' . implode(',', $cid) . ')');
		}

		$db->setQuery($query);
		$rows      = $db->loadObjectList();
		$listModel = JModelLegacy::getInstance('list', 'FabrikFEModel');
		$c         = 0;

		foreach ($rows as $row)
		{
			// Load in the plugin
			/** @var PlgFabrik_Cron $plugin */
			$plugin = $pluginManager->getPlugIn($row->plugin, 'cron');
			$plugin->setId($row->id);
			$params        = $plugin->getParams();
			$thisListModel = clone ($listModel);
			$thisListModel->setId($params->get('table'));
			$table = $listModel->getTable();
			/**
			 * $$$ hugh @TODO - really think we need to add two more options to the cron plugins
			 * 1) "Load rows?" because it really may not be practical to load ALL rows into $data
			 * on large tables, and the plugin itself may not need all data.
			 * 2) "Bypass prefilters" - I think we need a way of bypassing pre-filters for cron
			 * jobs, as they are run with access of whoever happened to hit the page at the time
			 * the cron was due to run, so it's pot luck as to what pre-filters get applied.
			 */
			$total = $thisListModel->getTotalRecords();
			$nav   = $thisListModel->getPagination($total, 0, $total);
			$data  = $thisListModel->getData();

			// $$$ hugh - added table model param, in case plugin wants to do further table processing
			$c = $c + $plugin->process($data, $thisListModel);
		}

		$query = $db->getQuery();
		$query->update('#__{package}_cron')->set('lastrun=NOW()')->where('id IN (' . implode(',', $cid) . ')');
		$db->setQuery($query);
		$db->execute();
	}
}
