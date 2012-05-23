<?php

/**
* A cron task to email records to a give set of users
* @package Joomla
* @subpackage Fabrik
* @author Rob Clayburn
* @copyright (C) Rob Clayburn
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

//require the abstract plugin class
require_once(COM_FABRIK_FRONTEND.DS.'models'.DS.'plugin-cron.php');

class plgFabrik_Cronemail extends plgFabrik_Cron {

	function canUse()
	{
		return true;
	}

	/**
	 * do the plugin action
	 * @return number of records updated
	 */

	function process(&$data)
	{
		$app = JFactory::getApplication();
		jimport('joomla.mail.helper');
		$params = $this->getParams();
		$msg = $params->get('message');
		$to = $params->get('to');
		$w = new FabrikWorker();
		$MailFrom = $app->getCfg('mailfrom');
		$FromName = $app->getCfg('fromname');
		$subject = $params->get('subject', 'Fabrik cron job');
		$eval = $params->get('cronemail-eval');
		$condition = $params->get('cronemail_condition', '');
		$updates = array();
		$this->log = '';
		foreach ($data as $group) {
			if (is_array($group)) {
				foreach ($group as $row) {
					if (!empty($condition)) {
						$this_condition = $w->parseMessageForPlaceHolder($condition, $row);
						if (eval($this_condition) === false) {
							continue;
						}
					}
					$row = JArrayHelper::fromObject($row);
					$thisto = $w->parseMessageForPlaceHolder($to, $row);
					if (JMailHelper::isEmailAddress($thisto)) {
						$thismsg = $w->parseMessageForPlaceHolder($msg, $row);
						if ($eval) {
							$thismsg = eval($thismsg);
						}
						$thissubject = $w->parseMessageForPlaceHolder($subject, $row);
						$res = JUTility::sendMail($MailFrom, $FromName, $thisto, $thissubject, $thismsg, true);
						if (!$res) {
							$this->log .= "\n failed sending to $thisto";
						}
					} else {
						$this->log .= "\n $thisto is not an email address";
					}
					$updates[] = $row['__pk_val'];

				}
			}
		}
		$field = $params->get('cronemail-updatefield');
		if (!empty( $updates) && trim($field ) != '') {
			//do any update found
			$listModel = JModel::getInstance('list', 'FabrikFEModel');
			$listModel->setId($params->get('table'));
			$table = $listModel->getTable();

			$connection = $params->get('connection');
			$field = $params->get('cronemail-updatefield');
			$value = $params->get('cronemail-updatefield-value');
			if ($params->get('cronemail-updatefield-eval', '0') == '1') {
				$value = @eval($value);
			}
			$field = str_replace("___", ".", $field);
			$query = "UPDATE $table->db_table_name set $field = " . $value. " WHERE $table->db_primary_key IN (" . implode(',', $updates) . ")";
			$this->log .= "\n update query: $query";
			$fabrikDb = $listModel->getDb();
			$fabrikDb->setQuery($query);
			$fabrikDb->query();
		}
		$this->log .= "\n updates " . count($updates) . " records";
		return count($updates);
	}

}
?>