<?php
/**
 * A cron task to email records to a give set of users
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.email
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

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
	 * @param   string $location To trigger plugin on
	 * @param   string $event    To trigger plugin on
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
	 * @param   array &$data data
	 * @param   object  &$listModel  List model
	 * @return  int  number of records updated
	 */
	public function process(&$data, &$listModel)
	{
		jimport('joomla.mail.helper');
		$params = $this->getParams();
		$msg    = $params->get('message');
		FabrikHelperHTML::runContentPlugins($msg, false);
		$to = explode(',', $params->get('to'));

		$w = new FabrikWorker;
		($params->get('cronemail_return', '') != '') ? $MailFrom = $params->get('cronemail_return') : $MailFrom = $this->app->get('mailfrom');
		($params->get('cronemail_from', '') != '') ? $FromName = $params->get('cronemail_from') : $FromName = $this->app->get('fromname');
		$subject   = $params->get('subject', 'Fabrik cron job');
		$eval      = $params->get('cronemail-eval');
		$condition = $params->get('cronemail_condition', '');
		$updates   = array();
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

					$row = ArrayHelper::fromObject($row);

					foreach ($to as $thisTo)
					{
						$thisTo = trim($w->parseMessageForPlaceHolder($thisTo, $row));

						if (FabrikWorker::isEmail($thisTo))
						{
							$thisMsg = $w->parseMessageForPlaceHolder($msg, $row);

							if ($eval)
							{
								$thisMsg = eval($thisMsg);
							}

							$thisSubject = $w->parseMessageForPlaceHolder($subject, $row);
							$mail        = JFactory::getMailer();
							$res         = $mail->sendMail($MailFrom, $FromName, $thisTo, $thisSubject, $thisMsg, true);

							if (!$res)
							{
								$this->log .= "\n failed sending to $thisTo";
							}
							else
							{
								$this->log .= "\n sent to $thisTo";
							}
						}
						else
						{
							$this->log .= "\n $thisTo is not an email address";
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
			/** @var FabrikFEModelList $listModel */
			$listModel = JModelLegacy::getInstance('list', 'FabrikFEModel');
			$listModel->setId($params->get('table'));
			$table = $listModel->getTable();
			$field = $params->get('cronemail-updatefield');
			$value = $params->get('cronemail-updatefield-value');

			if ($params->get('cronemail-updatefield-eval', '0') == '1')
			{
				$value = @eval($value);
			}

			$field    = str_replace('___', '.', $field);
			$fabrikDb = $listModel->getDb();
			$query    = $fabrikDb->getQuery(true);
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
