<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.logs
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Log form submissions
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.logs
 * @since       3.0
 */

class PlgFabrik_FormLogs extends PlgFabrik_Form
{

	/**
	 * Run when the form loads
	 *
	 * @param   object  $params      plugin parameters
	 * @param   object  &$formModel  form model
	 *
	 * @return  void
	 */

	public function onLoad($params, &$formModel)
	{
		$app = JFactory::getApplication();
		$view = $app->input->get('view', 'form');
		if ((!$formModel->isEditable() || $view == 'details') && ($params->get('log_details') != '0'))
		{
			$this->log($params, $formModel, 'form.load.details');
		}
		elseif ($formModel->isEditable() && ($params->get('log_form_load') != '0'))
		{
			$this->log($params, $formModel, 'form.load.form');
		}
		return true;
	}

	/**
	 * Get message type
	 *
	 * @param   string  $rowid  row reference
	 *
	 * @return  string
	 */

	protected function getMessageType($rowid)
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		if ($input->get('view') == 'details')
		{
			return 'form.details';
		}
		if (($rowid == "=") || ($rowid == '&') || ($rowid == '') || $rowid == 0)
		{
			return 'form.add';
		}
		else
		{
			return 'form.edit';
		}

	}

	/**
	 * Run right at the end of the form processing
	 * form needs to be set to record in database for this to hook to be called
	 *
	 * @param   object  $params      plugin params
	 * @param   object  &$formModel  form model
	 *
	 * @return	bool
	 */

	public function onAfterProcess($params, &$formModel)
	{
		$type = empty($formModel->origRowId) ? 'form.submit.add' : 'form.submit.edit';
		return $this->log($params, $formModel, $type);
	}

	/**
	 * Get new data
	 *
	 * @param   object  $formModel  form model
	 *
	 * @return  array
	 */

	protected function getNewData($formModel)
	{
		$listModel = $formModel->getListModel();
		$fabrikDb = $listModel->getDb();
		$sql = $formModel->buildQuery();
		$fabrikDb->setQuery($sql);
		return $fabrikDb->loadObjectList();
	}

	/**
	 * Perform log
	 *
	 * @param   object  $params       plugin params
	 * @param   object  $formModel    form model
	 * @param   string  $messageType  message type
	 *
	 * @return	bool
	 */

	protected function log($params, $formModel, $messageType)
	{
		$this->formModel = $formModel;
		$app = JFactory::getApplication();
		$input = $app->input;
		$db = FabrikWorker::getDBO();
		$query = $db->getQuery(true);
		$rowid = $input->get('rowid', '', 'string');
		$loading = strstr($messageType, 'form.load');
		$http_referrer = $input->server->get('HTTP_REFERER', 'no HTTP_REFERER', 'string');
		$user = JFactory::getUser();
		$userid = $user->get('id');
		$username = $user->get('username');

		// Generate random filename
		if ($params->get('logs_random_filename') == 1)
		{
			/**
			 * Internal generate file name
			 *
			 * @param   int  $length  Length of file name
			 *
			 * @return  string
			 */
			function generate_filename ($length)
			{
				$key = "";
				$possible = "0123456789bcdfghjkmnpqrstvwxyzBCDFGHJKLMNPQRTVWXYZ";
				$i = 0;
				while ($i < $length)
				{
					$char = JString::substr($possible, mt_rand(0, JString::strlen($possible) - 1), 1);
					$key .= $char;
					$i++;
				}
				return $key;
			}
			$random_filename = '_' . generate_filename($params->get('logs_random_filename_length'));
		}
		else
		{
			$random_filename = '';
		}

		$w = new FabrikWorker;
		$logs_path = $w->parseMessageForPlaceHolder($params->get('logs_path'));
		if (strpos($logs_path, '/') !== 0)
		{
			$logs_path = JPATH_ROOT . '/' . $logs_path;
		}
		$logs_path = rtrim($logs_path, '/');
		if (!JFolder::exists($logs_path))
		{
			if (!JFolder::create($logs_path))
			{
				return;
			}
		}
		$ext = $params->get('logs_file_format');
		$sep = $params->get('logs_separator');

		// Making complete path + filename + extension
		$w = new FabrikWorker;
		$logs_file = $logs_path . '/' . $w->parseMessageForPlaceHolder($params->get('logs_file')) . $random_filename . '.' . $ext;
		$logs_mode = $params->get('logs_append_or_overwrite');
		$date_element = $params->get('logs_date_field');
		$date_now = $params->get('logs_date_now');

		// COMPARE DATA
		$result_compare = '';
		if ($params->get('compare_data'))
		{
			if ($ext == 'csv')
			{
				$sep_compare = '';
				$sep_2compare = '/ ';
			}
			elseif ($ext == 'txt')
			{
				$sep_compare = "\n";
				$sep_2compare = "\n";
			}
			elseif ($ext == 'htm')
			{
				$sep_compare = '<br/>';
				$sep_2compare = '<br/>';
			}
			if ($loading)
			{
				$result_compare = JText::_('COMPARE_DATA_LOADING') . $sep_2compare;
			}
			else
			{
				$data = $this->getEmailData();
				$newData = $this->getNewData($formModel);
				if (!empty($data))
				{
					$filter = JFilterInput::getInstance();
					$post = $filter->clean($_POST, 'array');
					$tableModel = $formModel->getTable();

					$origDataCount = count(array_keys(JArrayHelper::fromObject($formModel->_origData[0])));
					if ($origDataCount > 0)
					{
						$c = 0;
						$origData = $formModel->_origData;

						$log_elements = explode(',', str_replace(' ', '', $params->get('logs_element_list', '')));
						$groups = $formModel->getGroupsHiarachy();
						foreach ($groups as $groupModel)
						{
							$group = $groupModel->getGroup();
							$elementModels = $groupModel->getPublishedElements();
							foreach ($elementModels as $elementModel)
							{
								$element = $elementModel->getElement();
								$fullName = $elementModel->getFullName(true, false);
								if (empty($log_elements) || in_array($fullName, $log_elements))
								{
									if ($newData[$c]->$fullName != $origData[$c]->$fullName)
									{
										$result_compare .= JText::_('COMPARE_DATA_CHANGE_ON') . ' ' . $element->label . ' ' . $sep_compare
											. JText::_('COMPARE_DATA_FROM') . ' ' . $origData[0]->$fullName . ' ' . $sep_compare
											. JText::_('COMPARE_DATA_TO') . ' ' . $newData[$c]->$fullName . ' ' . $sep_2compare;
									}
								}
							}
						}
						if (empty($result_compare))
						{
							$result_compare = JText::_('COMPARE_DATA_NO_DIFFERENCES');
						}
					}
					else
					{
						$result_compare .= "New record:" . $sep_2compare;
						foreach ($data as $key => $val)
						{
							if (isset($val) && (substr($key, -4, 4) != '_raw'))
							{
								$result_compare .= "$key : $val" . $sep_2compare;
							}
						}
					}
				}
				else
				{
					$result_compare = "No data to compare!";
				}
			}
		}

		// Defining the date to use - Not used anymore as logs should really only record the current time_date
		if ($date_now != '')
		{
			$date = date("$date_now");
		}
		else
		{
			$date = date("Y-m-d H:i:s");
		}

		// Custom Message
		if ($params->get('custom_msg') != '')
		{
			$rep_add_edit = $messageType == 'form.add' ? JText::_('REP_ADD')
				: ($messageType == 'form.edit' ? JText::_('REP_EDIT') : JText::_('DETAILS'));
			$custom_msg = $params->get('custom_msg');
			$custom_msg = preg_replace('/{Add\/Edit}/', $rep_add_edit, $custom_msg);
			$custom_msg = preg_replace('/{DATE}/', $date, $custom_msg);
			$excl_clabels = preg_replace('/([-{2}| |"][0-9a-zA-Z.:$_>]*)/', '', $custom_msg);
			$split_clabels = preg_split('/[+]{1,}/', $excl_clabels);
			$clabels = preg_replace('/[={2}]+[a-zA-Z0-9_-]*/', '', $split_clabels);
			$ctypes = preg_replace('/[a-zA-Z0-9_-]*[={2}]/', '', $split_clabels);
			$labtyp = array_combine($clabels, $ctypes);

			$w = new FabrikWorker;
			$custom_msg = $w->parseMessageForPlaceHolder($custom_msg);
			$regex = '/((?!("[^"]*))([ |\w|+|.])+(?=[^"]*"\b)|(?!\b"[^"]*)( +)+(?=([^"]*)$)|(?=\b"[^"]*)( +)+(?=[^"]*"\b))/';
			$excl_cdata = preg_replace($regex, '', $custom_msg);
			$cdata = preg_split('/["]{1,}/', $excl_cdata);

			// Labels for CSV & for DB
			$clabels_csv_imp = implode("\",\"", $clabels);
			$clabels_csv_p1 = preg_replace('/^(",)/', '', $clabels_csv_imp);
			$clabels_csv = '';

			$clabels_csv .= preg_replace('/(,")$/', '', $clabels_csv_p1);
			if ($params->get('compare_data') == 1)
			{
				$clabels_csv .= ', "' . JText::_('PLG_FORM_LOG_COMPARE_DATA_LABEL_CSV') . '"';
			}
			$clabels_createdb_imp = '';
			foreach ($labtyp as $klb => $vlb)
			{
				$klb = $db->quoteName($klb);
				if ($vlb == 'varchar')
				{
					$clabels_createdb_imp .= $klb . ' ' . $vlb . '(255) NOT NULL, ';
				}
				elseif ($vlb == 'int')
				{
					$clabels_createdb_imp .= $klb . ' ' . $vlb . '(11) NOT NULL, ';
				}
				elseif ($vlb == 'datetime')
				{
					$clabels_createdb_imp .= $klb . ' ' . $vlb . ' NOT NULL, ';
				}
			}
			$clabels_createdb = JString::substr_replace($clabels_createdb_imp, '', -2);

			if ($params->get('compare_data') == 1)
			{
				$clabels_createdb .= ', ' . $db->quoteName(JText::_('COMPARE_DATA_LABEL_DB')) . ' text NOT NULL';
			}

			// @todo - what if we use differnt db driver which doesnt name quote with `??
			$clabels_db_imp = implode("`,`", $clabels);
			$clabels_db_p1 = preg_replace('/^(`,)/', '', $clabels_db_imp);
			$clabels_db = preg_replace('/(,`)$/', '', $clabels_db_p1);

			if ($params->get('compare_data') == 1)
			{
				$clabels_db .= ', ' . $db->quoteName(JText::_('PLG_FORM_LOG_COMPARE_DATA_LABEL_DB'));
			}
			// Data for CSV & for DB
			$cdata_csv_imp = implode("\",\"", $cdata);
			$cdata_csv_p1 = preg_replace('/^(",)/', '', $cdata_csv_imp);
			$cdata_csv = preg_replace('/(,")$/', '', $cdata_csv_p1);
			$cdata_csv = preg_replace('/={1,}",/', '', $cdata_csv);
			$cdata_csv = preg_replace('/""/', '"', $cdata_csv);

			if ($params->get('compare_data') == 1)
			{
				$cdata_csv .= ', "' . $result_compare . '"';
			}
			$cdata_db_imp = implode("','", $cdata);
			$cdata_db_p1 = preg_replace("/^(',)/", '', $cdata_db_imp);
			$cdata_db = preg_replace("/(,')$/", '', $cdata_db_p1);
			$cdata_db = preg_replace("/={1,}',/", '', $cdata_db);
			$cdata_db = preg_replace("/''/", "'", $cdata_db);

			if ($params->get('compare_data') == 1 && !$loading)
			{
				$result_compare = preg_replace('/<br\/>/', '- ', $result_compare);
				$result_compare = preg_replace('/\\n/', '- ', $result_compare);
				$cdata_db .= ", '" . $result_compare . "'";
			}
			$custom_msg = preg_replace('/([++][0-9a-zA-Z.:_]*)/', '', $custom_msg);
			$custom_msg = preg_replace('/^[ ]/', '', $custom_msg);
			$custom_msg = preg_replace('/  /', ' ', $custom_msg);
			$custom_msg = preg_replace('/"/', '', $custom_msg);
			if ($params->get('compare_data') == 1 && !$loading)
			{
				$custom_msg .= '<br />' . $result_compare;
			}

		}
		else
		{
			$clabelsCreateDb = array();
			$clabelsDb = array();
			$cdataDb = array();

			$clabelsCreateDb[] = $db->quoteName('date') . " datetime NOT NULL";
			$clabelsDb[] = $db->quoteName('date');
			$cdataDb[] = "NOW()";

			$clabelsCreateDb[] = $db->quoteName('ip') . " varchar(32) NOT NULL";
			$clabelsDb[] = $db->quoteName('ip');
			$cdataDb[] = $params->get('logs_record_ip') == '1' ? $db->quote($_SERVER['REMOTE_ADDR']) : $db->quote('');

			$clabelsCreateDb[] = $db->quoteName('referer') . " varchar(255) NOT NULL";
			$clabelsDb[] = $db->quoteName('referer');
			$cdataDb[] = $params->get('logs_record_referer') == '1' ? $db->quote($http_referrer) : $db->quote('');

			$clabelsCreateDb[] = $db->quoteName('user_agent') . " varchar(255) NOT NULL";
			$clabelsDb[] = $db->quoteName('user_agent');
			$cdataDb[] = $params->get('logs_record_useragent') == '1' ? $db->quote($_SERVER['HTTP_USER_AGENT']) : $db->quote('');

			$clabelsCreateDb[] = $db->quoteName('data_comparison') . " TEXT NOT NULL";
			$clabelsDb[] = $db->quoteName('data_comparison');
			$cdataDb[] = $params->get('compare_data') == '1' ? $db->quote($result_compare) : $db->quote('');

			$clabelsCreateDb[] = $db->quoteName('rowid') . " INT(11) NOT NULL";
			$clabelsDb[] = $db->quoteName('rowid');
			$cdataDb[] = $db->quote($rowid);

			$clabelsCreateDb[] = $db->quoteName('userid') . " INT(11) NOT NULL";
			$clabelsDb[] = $db->quoteName('userid');
			$cdataDb[] = $db->quote((int) $userid);

			$clabelsCreateDb[] = $db->quoteName('tableid') . " INT(11) NOT NULL";
			$clabelsDb[] = $db->quoteName('tableid');
			$cdataDb[] = $db->quote($formModel->getTableModel()->getId());

			$clabelsCreateDb[] = $db->quoteName('formid') . " INT(11) NOT NULL";
			$clabelsDb[] = $db->quoteName('formid');
			$cdataDb[] = $db->quote($formModel->getId());

			$clabels_createdb = implode(", ", $clabelsCreateDb);
			$clabels_db = implode(", ", $clabelsDb);
			$cdata_db = implode(", ", $cdataDb);
		}
		/* For CSV files
		 * If 'Append' method is used, you don't want to repeat the labels (Date, IP, ...)
		 * each time you add a line in the file */
		$labels = (!JFile::exists($logs_file) || $logs_mode == 'w') ? 1 : 0;

		$buffer = ($logs_mode == 'a' && JFile::exists($logs_file)) ? file_get_contents($logs_file) : '';

		$send_email = $params->get('log_send_email') == '1';
		$make_file = $params->get('make_file') == '1';
		if ($send_email && !$make_file)
		{
			$ext = 'txt';
		}
		$email_msg = '';

		// @TODO redo all this with JFile API and only writing a string once - needless overhead doing fwrite all the time
		if ($make_file || $send_email)
		{
			// Opening or creating the file
			if ($params->get('custom_msg') != '')
			{
				if ($send_email)
				{
					$email_msg = $custom_msg;
				}
				if ($make_file)
				{
					$custMsg = $buffer;
					if ($ext != 'csv')
					{
						JFile::write($logs_file, $buffer . $custom_msg . "\n" . $sep . "\n");
					}
					else
					{
						// Making the CSV file
						// If the file already exists, do not add the 'label line'
						if ($labels == 1)
						{
							$custMsg .= $clabels_csv;
						}
						// Inserting data in CSV with actual line break as row separator
						$custMsg .= "\n" . $cdata_csv;
						JFile::write($logs_file, $custMsg);
					}
				}
			}
			else
			{
				// Making HTM File
				if ($ext == 'htm')
				{
					$htmlMsg = "<b>Date:</b> " . $date . "<br/>";
					if ($params->get('logs_record_ip') == 1)
					{
						$htmlMsg .= "<b>IP Address:</b> " . $_SERVER['REMOTE_ADDR'] . "<br/>";
					}
					if ($params->get('logs_record_referer') == 1)
					{
						$htmlMsg .= "<b>Referer:</b> " . $http_referrer . "<br/>";
					}
					if ($params->get('logs_record_useragent') == 1)
					{
						$htmlMsg .= "<b>UserAgent: </b>" . $_SERVER['HTTP_USER_AGENT'] . "<br/>";
					}
					$htmlMsg .= $result_compare . $sep . "<br/>";
					if ($send_email)
					{
						$email_msg = $htmlMsg;
					}
					if ($make_file)
					{
						$htmlMsg = $buffer . $htmlMsg;
						$res = JFile::write($logs_file, $htmlMsg);
						if (!$res)
						{
							$app->enqueueMessage("error writing html to log file: " . $logs_file, 'notice');
						}
					}
				}
				// Making the TXT file
				elseif ($ext == 'txt')
				{
					$txtMsg = "Date: " . $date . "\n";
					$txtMsg .= "Form ID: " . $formModel->getId() . "\n";
					$txtMsg .= "Table ID: " . $formModel->getListModel()->getId() . "\n";
					$txtMsg .= "Row ID: " . $rowid . "\n";
					$txtMsg .= "User ID: $userid ($username)\n";
					if ($params->get('logs_record_ip') == 1)
					{
						$txtMsg .= "IP Address: " . $_SERVER['REMOTE_ADDR'] . "\n";
					}
					if ($params->get('logs_record_referer') == 1)
					{
						$txtMsg .= "Referer: " . $http_referrer . "\n";
					}
					if ($params->get('logs_record_useragent') == 1)
					{
						$txtMsg .= "UserAgent: " . $_SERVER['HTTP_USER_AGENT'] . "\n";
					}
					$txtMsg .= $result_compare . $sep . "\n";
					if ($send_email)
					{
						$email_msg = $txtMsg;
					}
					if ($make_file)
					{
						$txtMsg = $buffer . $txtMsg;
						JFile::write($logs_file, $txtMsg);
					}
				}
				elseif ($ext == 'csv')
				{
					// Making the CSV file
					$csvMsg = array();

					// If the file already exists, do not add the 'label line'
					if ($labels == 1)
					{
						$csvMsg[] = "Date";
						if ($params->get('logs_record_ip') == 1)
						{
							// Putting some "" around the label to avoid two different fields
							$csvMsg[] = "\"IP Address\"";
						}
						if ($params->get('logs_record_referer') == 1)
						{
							$csvMsg[] = "Referer";
						}
						if ($params->get('logs_record_useragent') == 1)
						{
							$csvMsg[] = "UserAgent";
						}
						if ($params->get('compare_data') == 1)
						{
							$csvMsg[] = "\"" . JText::_('COMPARE_DATA_LABEL_CSV') . "\"";
						}
					}
					// Inserting data in CSV with actual line break as row separator
					$csvMsg[] = "\n\"" . $date . "\"";

					if ($params->get('logs_record_ip') == 1)
					{
						$csvMsg[] = "\"" . $_SERVER['REMOTE_ADDR'] . "\"";
					}
					if ($params->get('logs_record_referer') == 1)
					{

						$csvMsg[] = "\"" . $http_referrer . "\"";
					}
					if ($params->get('logs_record_useragent') == 1)
					{
						$csvMsg[] = "\"" . $_SERVER['HTTP_USER_AGENT'] . "\"";
					}
					if ($params->get('compare_data') == 1)
					{
						$csvMsg[] = "\"" . $result_compare . "\"";
					}
					$csvMsg = implode(",", $csvMsg);
					if ($send_email)
					{
						$email_msg = $csvMsg;
					}
					if ($make_file)
					{
						if ($buffer !== '')
						{
							$csvMsg = $buffer . $csvMsg;
						}
						JFile::write($logs_file, $csvMsg);
					}
				}
			}
		}

		if ($params->get('logs_record_in_db') == 1)
		{
			// In which table?
			if ($params->get('record_in') == '')
			{
				$rdb = '#__fabrik_log';
			}
			else
			{

				$db_suff = $params->get('record_in');
				$form = $formModel->getForm();
				$fid = $form->id;
				$db
					->setQuery(
						"SELECT " . $db->quoteName('db_table_name') . " FROM " . $db->quoteName('#__fabrik_lists') . " WHERE "
							. $db->quoteName('form_id') . " = " . (int) $fid
				);
				$tname = $db->loadResult();
				$rdb = $db->quoteName($tname . $db_suff);
			}

			// Making the message to record
			if ($params->get('custom_msg') != '')
			{
				$message = preg_replace('/<br\/>/', ' ', $custom_msg);
			}
			else
			{
				$message = $this->makeStandardMessage($params, $result_compare);
			}

			/* $$$ hugh - FIXME - not sure about the option driven $create_custom_table stuff, as this won't work
			 * if they add an option to an existing log table.  We should probably just create all the optional columns
			 * regardless.
			 */
			if ($params->get('record_in') == '')
			{
				$in_db = "INSERT INTO $rdb (" . $db->quoteName('referring_url') . ", " . $db->quoteName('message_type') . ", "
					. $db->quoteName('message') . ") VALUES (" . $db->quote($http_referrer) . ", " . $db->quote($messageType) . ", "
					. $db->quote($message) . ");";
				$db->setQuery($in_db);
				$db->execute();
			}
			else
			{
				$create_custom_table = "CREATE TABLE IF NOT EXISTS $rdb (" . $db->quoteName('id')
					. " int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, $clabels_createdb);";
				$db->setQuery($create_custom_table);
				$db->execute();

				$in_db = "INSERT INTO $rdb ($clabels_db) VALUES ($cdata_db);";
				$db->setQuery($in_db);
				if (!$db->execute())
				{
					/* $$$ changed to always use db fields even if not selected
					 * so logs already created may need optional fields added.
					 * try adding every field we should have, don't care if query fails.
					 */
					foreach ($clabelsCreateDb as $insert)
					{
						$db->setQuery("ALTER TABLE ADD $insert AFTER `id`");
						$db->execute();
					}
					// ... and try the insert query again
					$db->setQuery($in_db);
					$db->execute();
				}
			}

		}

		if ($send_email)
		{
			jimport('joomla.mail.helper');
			$config = &JFactory::getConfig();
			$email_from = $config->get('mailfrom');
			$email_to = explode(',', $w->parseMessageForPlaceholder($params->get('log_send_email_to', '')));
			$subject = strip_tags($w->parseMessageForPlaceholder($params->get('log_send_email_subject', 'log event')));
			foreach ($email_to as $email)
			{
				$email = trim($email);
				if (empty($email))
				{
					continue;
				}
				if (FabrikWorker::isEmail($email))
				{
					$res = JUtility::sendMail($email_from, $email_from, $email, $subject, $email_msg, true);
				}
				else
				{
					$app->enqueueMessage(JText::sprintf('DID_NOT_SEND_EMAIL_INVALID_ADDRESS', $email));
				}
			}
		}
		return true;
	}

	/**
	 * Make a standard log message
	 *
	 * @param   object  $params          plugin params
	 * @param   string  $result_compare  not sure?!
	 *
	 * @return  string  json encoded objects
	 */

	protected function makeStandardMessage($params, $result_compare)
	{
		$msg = new stdClass;

		$message = '';
		if ($params->get('logs_record_ip') == 1)
		{
			$msg->ip = $_SERVER['REMOTE_ADDR'];
		}
		if ($params->get('logs_record_useragent') == 1)
		{
			$msg->userAgent = $_SERVER['HTTP_USER_AGENT'];
		}
		if ($params->get('compare_data') == 1)
		{
			$result_compare = preg_replace('/<br\/>/', '- ', $result_compare);
			$msg->comparison = preg_replace('/\\n/', '- ', $result_compare);
		}
		return json_encode($msg);
	}

}
