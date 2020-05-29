<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.logs
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

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
	 * @return  void
	 */
	public function onLoad()
	{
		$params    = $this->getParams();
		$formModel = $this->getModel();
		$view      = $this->app->input->get('view', 'form');

		if ((!$formModel->isEditable() || $view == 'details') && ($params->get('log_details') != '0'))
		{
			$this->log('form.load.details');
		}
		elseif ($formModel->isEditable() && ($params->get('log_form_load') != '0'))
		{
			$this->log('form.load.form');
		}

		return true;
	}

	/**
	 * Get message type
	 *
	 * @param   string $rowId row reference
	 *
	 * @return  string
	 */
	protected function getMessageType($rowId)
	{
		$input = $this->app->input;

		if ($input->get('view') == 'details')
		{
			return 'form.details';
		}

		if ($rowId == '')
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
	 * @return    bool
	 */
	public function onAfterProcess()
	{
		$formModel = $this->getModel();
		$type      = empty($formModel->origRowId) ? 'form.submit.add' : 'form.submit.edit';

		return $this->log($type);
	}

	/**
	 * Get new data
	 *
	 * @return  array
	 */
	protected function getNewData()
	{
		$formModel = $this->getModel();
		$listModel = $formModel->getListModel();
		$fabrikDb  = $listModel->getDb();
		$sql       = $formModel->buildQuery();
		$fabrikDb->setQuery($sql);

		return $fabrikDb->loadObjectList();
	}

	/**
	 * Internal generate file name
	 *
	 * @param   int $length Length of file name
	 *
	 * @return  string
	 */
	private function generateFilename($length)
	{
		$key      = "";
		$possible = "0123456789bcdfghjkmnpqrstvwxyzBCDFGHJKLMNPQRTVWXYZ";
		$i        = 0;

		while ($i < $length)
		{
			$char = JString::substr($possible, mt_rand(0, JString::strlen($possible) - 1), 1);
			$key .= $char;
			$i++;
		}

		return $key;
	}

	/**
	 * Perform log
	 *
	 * @param   string $messageType message type
	 *
	 * @return    bool
	 */
	protected function log($messageType)
	{
		$params        = $this->getParams();
		$formModel     = $this->getModel();
		$input         = $this->app->input;
		$db            = JFactory::getDBO();
		$rowId         = $input->get('rowid', '', 'string');
		$loading       = strstr($messageType, 'form.load');
		$http_referrer = $input->server->get('HTTP_REFERER', 'no HTTP_REFERER', 'string');
		$userId        = $this->user->get('id');
		$username      = $this->user->get('username');

		// Generate random filename
		if ($params->get('logs_random_filename') == 1)
		{
			$randomFileName = '_' . $this->generateFilename($params->get('logs_random_filename_length'));
		}
		else
		{
			$randomFileName = '';
		}

		$w        = new FabrikWorker;
		$logsPath = $w->parseMessageForPlaceHolder($params->get('logs_path'));

		if (strpos($logsPath, '/') !== 0)
		{
			$logsPath = JPATH_ROOT . '/' . $logsPath;
		}

		$logsPath = rtrim($logsPath, '/');

		if (!JFolder::exists($logsPath))
		{
			if (!JFolder::create($logsPath))
			{
				return;
			}
		}

		$ext = $params->get('logs_file_format');
		$sep = $params->get('logs_separator');

		// Making complete path + filename + extension
		$w            = new FabrikWorker;
		$logsFile     = $logsPath . '/' . $w->parseMessageForPlaceHolder($params->get('logs_file')) . $randomFileName . '.' . $ext;
		$logsMode     = $params->get('logs_append_or_overwrite');
		$date_element = $params->get('logs_date_field');
		$date_now     = $params->get('logs_date_now');

		// COMPARE DATA
		$result_compare = '';

		if ($params->get('compare_data'))
		{
			if ($ext == 'csv')
			{
				$sep_compare  = '';
				$sep_2compare = '/ ';
			}
			elseif ($ext == 'txt')
			{
				$sep_compare  = "\n";
				$sep_2compare = "\n";
			}
			elseif ($ext == 'htm')
			{
				$sep_compare  = '<br/>';
				$sep_2compare = '<br/>';
			}

			if ($loading)
			{
				$result_compare = FText::_('PLG_FORM_LOG_COMPARE_DATA_LOADING') . $sep_2compare;
			}
			else
			{
				$data    = $this->getProcessData();
				$newData = $this->getNewData();

				if (!empty($data))
				{
					$filter        = JFilterInput::getInstance();
					$post          = $filter->clean($_POST, 'array');
					$tableModel    = $formModel->getTable();
					$origDataCount = count(array_keys(ArrayHelper::fromObject($formModel->_origData[0])));

					if ($origDataCount > 0)
					{
						$c            = 0;
						$origData     = $formModel->_origData;
						$log_elements = $params->get('logs_element_list', '');

						if (!empty($log_elements))
						{
							$log_elements = explode(',', str_replace(' ', '', $log_elements));
						}

						$groups = $formModel->getGroupsHiarachy();

						foreach ($groups as $groupModel)
						{
							$group         = $groupModel->getGroup();
							$elementModels = $groupModel->getPublishedElements();

							foreach ($elementModels as $elementModel)
							{
								$element  = $elementModel->getElement();
								$fullName = $elementModel->getFullName(true, false);

								if (empty($log_elements) || in_array($fullName, $log_elements))
								{
									if ($newData[$c]->$fullName != $origData[$c]->$fullName)
									{
										$result_compare .= FText::_('PLG_FORM_LOG_COMPARE_DATA_CHANGE_ON') . ' ' . $element->label . ' ' . $sep_compare
											. FText::_('PLG_FORM_LOG_COMPARE_DATA_FROM') . ' ' . $origData[0]->$fullName . ' ' . $sep_compare
											. FText::_('PLG_FORM_LOG_COMPARE_DATA_TO') . ' ' . $newData[$c]->$fullName . ' ' . $sep_2compare;
									}
								}
							}
						}

						if (empty($result_compare))
						{
							$result_compare = FText::_('PLG_FORM_LOG_COMPARE_DATA_NO_DIFFERENCES');
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

		// Defining the date to use - Not used any more as logs should really only record the current time_date
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
			$rep_add_edit  = $messageType == 'form.add' ? FText::_('REP_ADD')
				: ($messageType == 'form.edit' ? FText::_('REP_EDIT') : FText::_('DETAILS'));
			$custom_msg    = $params->get('custom_msg');
			$custom_msg    = preg_replace('/{Add\/Edit}/', $rep_add_edit, $custom_msg);
			$custom_msg    = preg_replace('/{DATE}/', $date, $custom_msg);
			$excl_clabels  = preg_replace('/([-{2}| |"][0-9a-zA-Z.:$_>]*)/', '', $custom_msg);
			$split_clabels = preg_split('/[+]{1,}/', $excl_clabels);
			$clabels       = preg_replace('/[={2}]+[a-zA-Z0-9_-]*/', '', $split_clabels);
			$ctypes        = preg_replace('/[a-zA-Z0-9_-]*[={2}]/', '', $split_clabels);
			$labtyp        = array_combine($clabels, $ctypes);

			$w          = new FabrikWorker;
			$custom_msg = $w->parseMessageForPlaceHolder($custom_msg, null, true, false, null, false);
			$regex      = '/((?!("[^"]*))([ |\w|+|.])+(?=[^"]*"\b)|(?!\b"[^"]*)( +)+(?=([^"]*)$)|(?=\b"[^"]*)( +)+(?=[^"]*"\b))/';
			$excl_cdata = preg_replace($regex, '', $custom_msg);
			$cdata      = preg_split('/["]{1,}/', $excl_cdata);

			// Labels for CSV & for DB
			$clabels_csv_imp = implode("\",\"", $clabels);
			$clabels_csv_p1  = preg_replace('/^(",)/', '', $clabels_csv_imp);
			$clabels_csv     = '';
			$clabels_csv .= preg_replace('/(,")$/', '', $clabels_csv_p1);

			if ($params->get('compare_data') == 1)
			{
				$clabels_csv .= ', "' . FText::_('PLG_FORM_LOG_COMPARE_DATA_LABEL_CSV') . '"';
			}

			$clabels_createdb_imp = '';

			foreach ($labtyp as $klb => $vlb)
			{
				$klb = $db->qn($klb);

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
				$clabels_createdb .= ', ' . $db->qn(FText::_('PLG_FORM_LOG_COMPARE_DATA_LABEL_DB')) . ' text NOT NULL';
			}

			// @todo - what if we use different db driver which doesn't name quote with `??
			$clabels_db_imp = implode("`,`", $clabels);
			$clabels_db_p1  = preg_replace('/^(`,)/', '', $clabels_db_imp);
			$clabels_db     = preg_replace('/(,`)$/', '', $clabels_db_p1);

			if ($params->get('compare_data') == 1)
			{
				$clabels_db .= ', ' . $db->qn(FText::_('PLG_FORM_LOG_COMPARE_DATA_LABEL_DB'));
			}

			// Data for CSV & for DB
			$cdata_csv_imp = implode("\",\"", $cdata);
			$cdata_csv_p1  = preg_replace('/^(",)/', '', $cdata_csv_imp);
			$cdata_csv     = preg_replace('/(,")$/', '', $cdata_csv_p1);
			$cdata_csv     = preg_replace('/={1,}",/', '', $cdata_csv);
			$cdata_csv     = preg_replace('/""/', '"', $cdata_csv);

			if ($params->get('compare_data') == 1)
			{
				$cdata_csv .= ', "' . $result_compare . '"';
			}

			$cdata_db_imp = implode("','", $cdata);
			$cdata_db_p1  = preg_replace("/^(',)/", '', $cdata_db_imp);
			$cdata_db     = preg_replace("/(,')$/", '', $cdata_db_p1);
			$cdata_db     = preg_replace("/={1,}',/", '', $cdata_db);
			$cdata_db     = preg_replace("/''/", "'", $cdata_db);

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
			$clabelsDb       = array();
			$cdataDb         = array();

			$clabelsCreateDb[] = $db->qn('date') . " datetime NOT NULL";
			$clabelsDb[]       = $db->qn('date');
			$cdataDb[]         = "NOW()";

			$clabelsCreateDb[] = $db->qn('ip') . " varchar(32) NOT NULL";
			$clabelsDb[]       = $db->qn('ip');
			$cdataDb[]         = $params->get('logs_record_ip') == '1' ? $db->q(FabrikString::filteredIp()) : $db->q('');

			$clabelsCreateDb[] = $db->qn('referer') . " varchar(255) NOT NULL";
			$clabelsDb[]       = $db->qn('referer');
			$cdataDb[]         = $params->get('logs_record_referer') == '1' ? $db->q($http_referrer) : $db->q('');

			$clabelsCreateDb[] = $db->qn('user_agent') . " varchar(255) NOT NULL";
			$clabelsDb[]       = $db->qn('user_agent');
			$cdataDb[]         = $params->get('logs_record_useragent') == '1' ? $db->q($input->server->getString('HTTP_USER_AGENT')) : $db->q('');

			$clabelsCreateDb[] = $db->qn('data_comparison') . " TEXT NOT NULL";
			$clabelsDb[]       = $db->qn('data_comparison');
			$cdataDb[]         = $params->get('compare_data') == '1' ? $db->q($result_compare) : $db->q('');

			$clabelsCreateDb[] = $db->qn('rowid') . " INT(11) NOT NULL";
			$clabelsDb[]       = $db->qn('rowid');
			$cdataDb[]         = $db->q($rowId);

			$clabelsCreateDb[] = $db->qn('userid') . " INT(11) NOT NULL";
			$clabelsDb[]       = $db->qn('userid');
			$cdataDb[]         = $db->q((int) $userId);

			$clabelsCreateDb[] = $db->qn('tableid') . " INT(11) NOT NULL";
			$clabelsDb[]       = $db->qn('tableid');
			$cdataDb[]         = $db->q($formModel->getListModel()->getId());

			$clabelsCreateDb[] = $db->qn('formid') . " INT(11) NOT NULL";
			$clabelsDb[]       = $db->qn('formid');
			$cdataDb[]         = $db->q($formModel->getId());

			$clabels_createdb = implode(", ", $clabelsCreateDb);
			$clabels_db       = implode(", ", $clabelsDb);
			$cdata_db         = implode(", ", $cdataDb);
		}

		/* For CSV files
		 * If 'Append' method is used, you don't want to repeat the labels (Date, IP, ...)
		* each time you add a line in the file */
		$labels     = (!JFile::exists($logsFile) || $logsMode == 'w') ? 1 : 0;
		$buffer     = ($logsMode == 'a' && JFile::exists($logsFile)) ? file_get_contents($logsFile) : '';
		$send_email = $params->get('log_send_email') == '1';
		$make_file  = $params->get('make_file') == '1';

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
						$thisMsg = $buffer . $custom_msg . "\n" . $sep . "\n";
						JFile::write($logsFile, $thisMsg);
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
						JFile::write($logsFile, $custMsg);
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
						$htmlMsg .= "<b>IP Address:</b> " . FabrikString::filteredIp() . "<br/>";
					}

					if ($params->get('logs_record_referer') == 1)
					{
						$htmlMsg .= "<b>Referer:</b> " . $http_referrer . "<br/>";
					}

					if ($params->get('logs_record_useragent') == 1)
					{
						$htmlMsg .= "<b>UserAgent: </b>" . $input->server->getString('HTTP_USER_AGENT') . "<br/>";
					}

					$htmlMsg .= $result_compare . $sep . "<br/>";

					if ($send_email)
					{
						$email_msg = $htmlMsg;
					}

					if ($make_file)
					{
						$htmlMsg = $buffer . $htmlMsg;
						$res     = JFile::write($logsFile, $htmlMsg);

						if (!$res)
						{
							$this->app->enqueueMessage("error writing html to log file: " . $logsFile, 'notice');
						}
					}
				}
				// Making the TXT file
				elseif ($ext == 'txt')
				{
					$txtMsg = "Date: " . $date . "\n";
					$txtMsg .= "Form ID: " . $formModel->getId() . "\n";
					$txtMsg .= "Table ID: " . $formModel->getListModel()->getId() . "\n";
					$txtMsg .= "Row ID: " . $rowId . "\n";
					$txtMsg .= "User ID: $userId ($username)\n";

					if ($params->get('logs_record_ip') == 1)
					{
						$txtMsg .= "IP Address: " . FabrikString::filteredIp() . "\n";
					}

					if ($params->get('logs_record_referer') == 1)
					{
						$txtMsg .= "Referer: " . $http_referrer . "\n";
					}

					if ($params->get('logs_record_useragent') == 1)
					{
						$txtMsg .= "UserAgent: " . $input->server->getString('HTTP_USER_AGENT') . "\n";
					}

					$txtMsg .= $result_compare . $sep . "\n";

					if ($send_email)
					{
						$email_msg = $txtMsg;
					}

					if ($make_file)
					{
						$txtMsg = $buffer . $txtMsg;
						JFile::write($logsFile, $txtMsg);
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
							$csvMsg[] = "\"" . FText::_('PLG_FORM_LOG_COMPARE_DATA_LABEL_CSV') . "\"";
						}
					}
					// Inserting data in CSV with actual line break as row separator
					$csvMsg[] = "\n\"" . $date . "\"";

					if ($params->get('logs_record_ip') == 1)
					{
						$csvMsg[] = "\"" . FabrikString::filteredIp() . "\"";
					}

					if ($params->get('logs_record_referer') == 1)
					{
						$csvMsg[] = "\"" . $http_referrer . "\"";
					}

					if ($params->get('logs_record_useragent') == 1)
					{
						$csvMsg[] = "\"" . $input->server->getString('HTTP_USER_AGENT') . "\"";
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

						JFile::write($logsFile, $csvMsg);
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
				$form    = $formModel->getForm();
				$fid     = $form->id;
				$db
					->setQuery(
						"SELECT " . $db->qn('db_table_name') . " FROM " . $db->qn('#__fabrik_lists') . " WHERE "
						. $db->qn('form_id') . " = " . (int) $fid
					);
				$tname = $db->loadResult();
				$rdb   = $db->qn($tname . $db_suff);
			}

			// Making the message to record
			if ($params->get('custom_msg') != '')
			{
				$message = preg_replace('/<br\/>/', ' ', $custom_msg);
			}
			else
			{
				$message = $this->makeStandardMessage($result_compare);
			}

			/* $$$ hugh - FIXME - not sure about the option driven $create_custom_table stuff, as this won't work
			 * if they add an option to an existing log table.  We should probably just create all the optional columns
			* regardless.
			*/
			if ($params->get('record_in') == '')
			{
				$in_db = "INSERT INTO $rdb (" . $db->qn('referring_url') . ", " . $db->qn('message_type') . ", "
					. $db->qn('message') . ") VALUES (" . $db->q($http_referrer) . ", " . $db->q($messageType) . ", "
					. $db->q($message) . ");";
				$db->setQuery($in_db);
				$db->execute();
			}
			else
			{
				$create_custom_table = "CREATE TABLE IF NOT EXISTS $rdb (" . $db->qn('id')
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
			$emailFrom = $this->config->get('mailfrom');
			$emailTo   = explode(',', $w->parseMessageForPlaceholder($params->get('log_send_email_to', '')));
			$subject   = strip_tags($w->parseMessageForPlaceholder($params->get('log_send_email_subject', 'log event')));

			foreach ($emailTo as $email)
			{
				$email = trim($email);

				if (empty($email))
				{
					continue;
				}

				if (FabrikWorker::isEmail($email))
				{
					$mail = JFactory::getMailer();
					$res  = $mail->sendMail($emailFrom, $emailFrom, $email, $subject, $email_msg, true);
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
	 * @param   string $result_compare Not sure?!
	 *
	 * @return  string  json encoded objects
	 */
	protected function makeStandardMessage($result_compare)
	{
		$params = $this->getParams();
		$input  = $this->app->input;
		$msg    = new stdClass;

		if ($params->get('logs_record_ip') == 1)
		{
			$msg->ip = FabrikString::filteredIp();
		}

		if ($params->get('logs_record_useragent') == 1)
		{
			$msg->userAgent = $input->server->getString('HTTP_USER_AGENT');
		}

		if ($params->get('compare_data') == 1)
		{
			$result_compare  = preg_replace('/<br\/>/', '- ', $result_compare);
			$msg->comparison = preg_replace('/\\n/', '- ', $result_compare);
		}

		return json_encode($msg);
	}
}
