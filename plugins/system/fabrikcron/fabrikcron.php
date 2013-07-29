<?php
/**
 * Joomla! Fabrik cron job plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  System
 * @copyright   Copyright (C) 2005 - 2008 Pollen 8 Design Ltd. All rights reserved.
 * @license     GNU/GPL, see LICENSE.php
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');
jimport('joomla.filesystem.file');

/**
 * Joomla! Fabrik cron job plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  System
 * @since       3.0
 */

class plgSystemFabrikcron extends JPlugin
{

	/**
	 * Constructor
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An array that holds the plugin configuration
	 *
	 * @since	1.0
	 *
	 * return  void
	 */

	public function plgSystemFabrikcron(&$subject, $config)
	{
		parent::__construct($subject, $config);
	}

	/**
	 * Run all active cron jobs
	 *
	 * @return void
	 */

	protected function doCron()
	{
		$app = JFactory::getApplication();
		$mailer = JFactory::getMailer();
		$config = JFactory::getConfig();
		if ($app->isAdmin() || JRequest::getVar('option') == 'com_acymailing')
		{
			return;
		}
		// $$$ hugh - don't want to run on things like AJAX calls
		if (JRequest::getVar('format', '') == 'raw')
		{
			return;
		}

		// Get all active tasks
		$db = FabrikWorker::getDbo(true);
		$now = JRequest::getVar('fabrikcron_run', false);

		$log = FabTable::getInstance('Log', 'FabrikTable');

		if (!$now)
		{
			/* $$$ hugh - changed from using NOW() to JFactory::getDate(), to avoid time zone issues, see:
			 * http://fabrikar.com/forums/showthread.php?p=102245#post102245
			 * .. which seems reasonable, as we use getDate() to set 'lastrun' to at the end of this func
			 */

			$nextrun = "CASE " . "WHEN unit = 'second' THEN DATE_ADD( lastrun, INTERVAL frequency SECOND )\n"
				. "WHEN unit = 'minute' THEN DATE_ADD( lastrun, INTERVAL frequency MINUTE )\n"
				. "WHEN unit = 'hour' THEN DATE_ADD( lastrun, INTERVAL frequency HOUR )\n"
				. "WHEN unit = 'day' THEN DATE_ADD( lastrun, INTERVAL frequency DAY )\n"
				. "WHEN unit = 'week' THEN DATE_ADD( lastrun, INTERVAL frequency WEEK )\n"
				. "WHEN unit = 'month' THEN DATE_ADD( lastrun, INTERVAL frequency MONTH )\n"
				. "WHEN unit = 'year' THEN DATE_ADD( lastrun, INTERVAL frequency YEAR ) END";

			$query = "SELECT id, plugin, lastrun, unit, frequency, $nextrun AS nextrun FROM #__{package}_cron\n";
			$query .= "WHERE published = '1' ";
			$query .= "AND $nextrun < '" . JFactory::getDate()->toSql() . "'";
		}
		else
		{
			$query = "SELECT id, plugin FROM #__{package}_cron WHERE published = '1'";
		}

		$db->setQuery($query);
		$rows = $db->loadObjectList();
		if (empty($rows))
		{
			return;
		}

		$log->message = '';

		/* $$$ hugh - set 'state' to 2 for selected rows, so we don't end up running
		 * multiple copies, if this code is run again before selected plugins have
		 * finished running, see:
		 * http://fabrikar.com/forums/showthread.php?p=114008#post114008
		 */
		$ids = array();
		foreach ($rows as $row)
		{
			$ids[] = (int) $row->id;
		}
		$query = $db->getQuery(true);
		$query->update('#__{package}_cron')->set('published = 2')->where('id IN (' . implode(',', $ids) . ')');
		$db->setQuery($query);
		$db->execute();

		JModel::addIncludePath(JPATH_SITE . '/components/com_fabrik/models');
		$pluginManager = JModel::getInstance('Pluginmanager', 'FabrikFEModel');
		$listModel = JModel::getInstance('list', 'FabrikFEModel');

		foreach ($rows as $row)
		{
			// Load in the plugin
			$plugin = $pluginManager->getPluginFromId($row->id, 'Cron');

			$params = $plugin->getParams();
			$log->message = '';
			$log->id = null;
			$log->referring_url = '';

			$log->message_type = 'plg.cron.' . $row->plugin;
			if (!$plugin->queryStringActivated())
			{
				// $$$ hugh - don't forget to make it runnable again before continuing
				$query->clear();
				$query->update('#__{package}_cron')->set('published = 1')->where('id = ' . $row->id);
				$db->setQuery($query);
				$db->execute();
				continue;
			}
			$tid = (int) $params->get('table');
			$thisListModel = clone ($listModel);
			if ($tid !== 0)
			{
				$thisListModel->setId($tid);
				$log->message .= "\n\n$row->plugin\n listid = " . $thisListModel->getId();
				if ($plugin->requiresTableData())
				{
					$table = $thisListModel->getTable();
					$total = $thisListModel->getTotalRecords();
					$nav = $thisListModel->getPagination($total, 0, $total);
					$data = $thisListModel->getData();
					$log->message .= "\n" . $thisListModel->_buildQuery();
				}
			}
			else
			{
				$data = array();
			}
			$res = $plugin->process($data, $thisListModel);
			$log->message = $plugin->getLog() . "\n\n" . $log->message;
			$now = JFactory::getDate();
			$now = $now->toUnix();
			$new = JFactory::getDate($row->nextrun);
			$tmp = $new->toUnix();

			switch ($row->unit)
			{
				case 'second':
					$inc = 1;
					break;
				case 'minute':
					$inc = 60;
					break;
				case 'hour':
					$inc = 60 * 60;
					break;
				default:
				case 'day':
					$inc = 60 * 60 * 24;
					break;
			}
			/* Don't use NOW() as the last run date as this could mean that the cron
			 * jobs aren't run as frequently as specified
			 * if the lastrun date was set in admin to ages ago, then incrementally increase the
			 * last run date until it is less than now
			 */
			while ($tmp + ($inc * $row->frequency) < $now)
			{
				$tmp = $tmp + ($inc * $row->frequency);
			}

			// Mark them as being run
			// $$$ hugh - and make it runnable again by setting 'state' back to 1
			$nextrun = JFactory::getDate($tmp);
			$query->clear();
			$query->update('#__{package}_cron')->set('published = 1, lastrun = ' . $db->quote($nextrun->toSql()))->where('id = ' . $row->id);
			$db->setQuery($query);
			$db->execute();

			// Log if asked for
			if ($params->get('log', 0) == 1)
			{
				$log->store();
			}

			// Email log message
			$recipient = explode(',', $params->get('log_email', ''));
			if (!empty($recipient))
			{
				$subject = $config->get('sitename') . ': ' . $row->plugin . ' scheduled task';
				$mailer->sendMail($config->get('mailfrom'), $config->get('fromname'), $recipient, $subject, $log->message, true);
			}
		}
	}

	/**
	 * Perform the actual cron after the page has rendered
	 *
	 * @return  void
	 */

	public function onAfterRender()
	{
		$this->doCron();
	}

}
