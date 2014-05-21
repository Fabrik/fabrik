<?php
/**
 * A cron task to email records to a give set of users
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.email
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-cron.php';

/**
 * A cron task to email records to a give set of users
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.email
 * @since       3.0
 */

class PlgFabrik_Cronemail extends PlgFabrik_Cron
{
	/**
	 * Check if the user can use the plugin
	 *
	 * @param   string  $location  To trigger plugin on
	 * @param   string  $event     To trigger plugin on
	 *
	 * @return  bool can use or not
	 */

	public function canUse($location = null, $event = null)
	{
		return true;
	}

	/**
	 * Do the plugin action
	 *
	 * @param   array  &$data  data
	 *
	 * @return  int  number of records updated
	 */

	public function process(&$data)
	{
		$app = JFactory::getApplication();
		jimport('joomla.mail.helper');
		$params = $this->getParams();
		$msg = $params->get('message');
		FabrikHelperHTML::runContentPlugins($msg);
		$to = explode(',', $params->get('to'));

		$w = new FabrikWorker;
		$MailFrom = $app->getCfg('mailfrom');
		$FromName = $app->getCfg('fromname');
		$subject = $params->get('subject', 'Fabrik cron job');
		$eval = $params->get('cronemail-eval');
		$condition = $params->get('cronemail_condition', '');
		$updates = array();
		$this->log = '';

		foreach ($data as $group)
		{
			if (is_array($group))
			{
				foreach ($group as $row)
				{
					if (!empty($condition))
					{
						$this_condition = $w->parseMessageForPlaceHolder($condition, $row);

						if (eval($this_condition) === false)
						{
							continue;
						}
					}

					$row = JArrayHelper::fromObject($row);

					foreach ($to as $thisto)
					{
						$thisto = $w->parseMessageForPlaceHolder($thisto, $row);

						if (FabrikWorker::isEmail($thisto))
						{
							$thismsg = $w->parseMessageForPlaceHolder($msg, $row);

							if ($eval)
							{
								$thismsg = eval($thismsg);
							}

							$thissubject = $w->parseMessageForPlaceHolder($subject, $row);
							$mail = JFactory::getMailer();
							$res = $mail->sendMail($MailFrom, $FromName, $thisto, $thissubject, $thismsg, true);

							if (!$res)
							{
								$this->log .= "\n failed sending to $thisto";
							}
						}
						else
						{
							$this->log .= "\n $thisto is not an email address";
						}
					}

					$updates[] = $row['__pk_val'];
				}
			}
		}

		$field = $params->get('cronemail-updatefield');

		if (!empty($updates) && trim($field) != '')
		{
			// Do any update found
			$listModel = JModelLegacy::getInstance('list', 'FabrikFEModel');
			$listModel->setId($params->get('table'));
			$table = $listModel->getTable();
			$connection = $params->get('connection');
			$field = $params->get('cronemail-updatefield');
			$value = $params->get('cronemail-updatefield-value');

			if ($params->get('cronemail-updatefield-eval', '0') == '1')
			{
				$value = @eval($value);
			}

			$field = str_replace('___', '.', $field);
			$fabrikDb = $listModel->getDb();
			$query = $fabrikDb->getQuery(true);
			$query->update($table->db_table_name)->set($field . ' = ' . $fabrikDb->quote($value))
				->where($table->db_primary_key . ' IN (' . implode(',', $updates) . ')');
			$this->log .= "\n update query: $query";
			$fabrikDb->setQuery($query);
			$fabrikDb->execute();
		}

		$this->log .= "\n updates " . count($updates) . " records";

		return count($updates);
	}
}
