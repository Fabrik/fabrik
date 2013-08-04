<?php
/**
 * Cron list controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once 'fabcontrolleradmin.php';

/**
 * Cron list controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabrikAdminControllerCrons extends FabControllerAdmin
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var	string
	 */
	protected $text_prefix = 'COM_FABRIK_CRONS';

	/**
	 * View item name
	 *
	 * @var string
	 */
	protected $view_item = 'crons';

	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    model name
	 * @param   string  $prefix  model prefix
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  J model
	 */

	public function getModel($name = 'Cron', $prefix = 'FabrikAdminModel', $config = array())
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));
		return $model;
	}

	/**
	 * Run the selected cron plugins
	 *
	 * @return  void
	 */

	public function run()
	{
		$mailer = JFactory::getMailer();
		$config = JFactory::getConfig();
		$db = FabrikWorker::getDbo(true);
		$app = JFactory::getApplication();
		$input = $app->input;
		$cid = $input->get('cid', array(), 'array');
		JArrayHelper::toInteger($cid);
		$cid = implode(',', $cid);
		$query = $db->getQuery(true);
		$query->select('*')->from('#__{package}_cron')->where('id IN (' . $cid . ')');
		$db->setQuery($query);
		$rows = $db->loadObjectList();
		$adminListModel = JModelLegacy::getInstance('List', 'FabrikAdminModel');
		$pluginManager = JModelLegacy::getInstance('Pluginmanager', 'FabrikFEModel');
		$listModel = JModelLegacy::getInstance('list', 'FabrikFEModel');
		$c = 0;
		$log = FabTable::getInstance('Log', 'FabrikTable');
		foreach ($rows as $row)
		{
			// Load in the plugin
			$rowParams = json_decode($row->params);
			$log->message = '';
			$log->id = null;
			$log->referring_url = '';
			$log->message_type = 'plg.cron.' . $row->plugin;
			$plugin = $pluginManager->getPlugIn($row->plugin, 'cron');
			$table = FabTable::getInstance('cron', 'FabrikTable');
			$table->load($row->id);
			$plugin->setRow($table);
			$params = $plugin->getParams();
			$thisListModel = clone ($listModel);
			$thisAdminListModel = clone ($adminListModel);
			$tid = (int) $rowParams->table;
			if ($tid !== 0)
			{
				$thisListModel->setId($tid);
				$log->message .= "\n\n$row->plugin\n listid = " . $thisListModel->getId();
				if ($plugin->requiresTableData())
				{
					$table = $listModel->getTable();
					$total = $thisListModel->getTotalRecords();
					$nav = $thisListModel->getPagination($total, 0, $total);
					$data = $thisListModel->getData();
					$log->message .= "\n" . $thisListModel->buildQuery();
				}
			}
			else
			{
				$data = array();
			}
			// $$$ hugh - added table model param, in case plugin wants to do further table processing
			$c = $c + $plugin->process($data, $thisListModel, $thisAdminListModel);

			$log->message = $plugin->getLog() . "\n\n" . $log->message;
			if ($plugin->getParams()->get('log', 0) == 1)
			{
				$log->store();
			}

			// Email log message
			$recipient = $plugin->getParams()->get('log_email', '');
			if ($recipient != '')
			{
				$recipient = explode(',', $recipient);
				$subject = $config->get('sitename') . ': ' . $row->plugin . ' scheduled task';
				$mailer->sendMail($config->get('mailfrom'), $config->get('fromname'), $recipient, $subject, $log->message, true);
			}
		}
		$this->setRedirect('index.php?option=com_fabrik&view=crons', $c . ' records updated');
	}

}
