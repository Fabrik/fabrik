<?php
/**
 * Fabrik FTP
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.ftp
 * @copyright   Copyright (C) 2005 Hugh Messenger. All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * FTP Form results to a given location
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.ftp
 * @since       3.0.7
 */
class PlgFabrik_FormFtp extends PlgFabrik_Form
{
	/**
	 * Posted form keys that we don't want to include in the message
	 * This is basically the fileupload elements
	 *
	 * @var array
	 */
	protected $dontEmailKeys = null;

	/**
	 * Process the plugin, called when form is submitted
	 *
	 * @return  bool
	 */
	public function onAfterProcess()
	{
		$params = $this->getParams();
		jimport('joomla.mail.helper');
		$formModel = $this->getModel();
		$input = $this->app->input;
		$ftpTemplate = JPath::clean(JPATH_SITE . '/plugins/fabrik_form/ftp/tmpl/' . $params->get('ftp_template', ''));
		$this->data = $this->getProcessData();

		if (!$this->shouldProcess('ftp_conditon', null, $params))
		{
			return;
		}

		$contentTemplate = $params->get('ftp_template_content');
		$content = $contentTemplate != '' ? $this->_getContentTemplate($contentTemplate) : '';

		if (JFile::exists($ftpTemplate))
		{
			if (JFile::getExt($ftpTemplate) == 'php')
			{
				$message = $this->_getPHPTemplateFtp($ftpTemplate);

				if ($message === false)
				{
					return;
				}
			}
			else
			{
				$message = $this->_getTemplateFtp($ftpTemplate);
			}

			$message = str_replace('{content}', $content, $message);
		}
		else
		{
			$message = $contentTemplate != '' ? $content : $this->_getTextFtp();
		}

		$cc = null;
		$bcc = null;
		$w = new FabrikWorker;

		// $$$ hugh - test stripslashes(), should be safe enough.
		$message = stripslashes($message);
		$editURL = COM_FABRIK_LIVESITE . "index.php?option=com_fabrik&amp;view=form&amp;fabrik="
			. $formModel->get('id') . "&amp;rowid=" . $input->get('rowid', '', 'string');
		$viewURL = COM_FABRIK_LIVESITE . "index.php?option=com_fabrik&amp;view=details&amp;fabrik=" . $formModel->get('id')
		. "&amp;rowid=" . $input->get('rowid', '', 'string');
		$editLink = "<a href=\"$editURL\">" . FText::_('EDIT') . "</a>";
		$viewLink = "<a href=\"$viewURL\">" . FText::_('VIEW') . "</a>";
		$message = str_replace('{fabrik_editlink}', $editLink, $message);
		$message = str_replace('{fabrik_viewlink}', $viewLink, $message);
		$message = str_replace('{fabrik_editurl}', $editURL, $message);
		$message = str_replace('{fabrik_viewurl}', $viewURL, $message);

		$ftpFileName = $params->get('ftp_filename', '');
		$ftpFileName = $w->parseMessageForPlaceholder($ftpFileName, $this->data, false);
		$ftpEvalFileName = (int) $params->get('ftp_eval_filename', '0');

		if ($ftpEvalFileName)
		{
			$ftpFileName = @eval($ftpFileName);
			FabrikWorker::logEval($ftpEvalFileName, 'Caught exception on eval in ftp filename eval : %s');
		}

		if (empty($ftpFileName))
		{
			$ftpFileName = 'fabrik_ftp_' . md5(uniqid()) . '.txt';
		}

		$ftpHost = $w->parseMessageForPlaceholder($params->get('ftp_host', ''), $this->data, false);
		$ftpPort = $w->parseMessageForPlaceholder($params->get('ftp_port', '21'), $this->data, false);
		$ftpChDir = $w->parseMessageForPlaceholder($params->get('ftp_chdir', ''), $this->data, false);
		$ftpUser = $w->parseMessageForPlaceholder($params->get('ftp_user', ''), $this->data, false);
		$ftpPassword = $w->parseMessageForPlaceholder($params->get('ftp_password', ''), $this->data, false);

		//$tmpDir = rtrim($this->config->getValue('config.tmp_path'), '/');
		$tmpDir = rtrim($this->config->get('tmp_path'), '/');

		if (empty($tmpDir) || !JFolder::exists($tmpDir))
		{
			throw new RuntimeException('PLG_FORM_FTP_NO_JOOMLA_TEMP_DIR', 500);
		}

		$tmpFile = $tmpDir . '/fabrik_ftp_' . md5(uniqid());
		$message = $w->parseMessageForPlaceholder($message, $this->data, true, false);

		if (JFile::write($tmpFile, $message))
		{
			$conn_id = ftp_connect($ftpHost, $ftpPort);

			if ($conn_id)
			{
				if (@ftp_login($conn_id, $ftpUser, $ftpPassword))
				{
					if (!empty($ftpChDir))
					{
						if (!ftp_chdir($conn_id, $ftpChDir))
						{
							$this->app->enqueueMessage(FText::_('PLG_FORM_FTP_COULD_NOT_CHDIR'), 'notice');
							JFile::delete($tmpFile);

							return false;
						}
					}

					if (!ftp_put($conn_id, $ftpFileName, $tmpFile, FTP_ASCII))
					{
						$this->app->enqueueMessage(FText::_('PLG_FORM_FTP_COULD_NOT_SEND_FILE'), 'notice');
						JFile::delete($tmpFile);

						return false;
					}
				}
				else
				{
					$this->app->enqueueMessage(FText::_('PLG_FORM_FTP_COULD_NOT_LOGIN'), 'notice');
					JFile::delete($tmpFile);

					return false;
				}
			}
			else
			{
				throw new RuntimeException('PLG_FORM_FTP_COULD_NOT_CONNECT', 500);
				JFile::delete($tmpFile);

				return false;
			}
		}
		else
		{
			throw new RuntimeException('PLG_FORM_FTP_COULD_NOT_WRITE_TEMP_FILE', 500);
			JFile::delete($tmpFile);

			return false;
		}

		JFile::delete($tmpFile);

		return true;
	}

	/**
	 * Use a php template for advanced email templates, particularly for forms with repeat group data
	 *
	 * @param   string  $tmpl  Path to template
	 *
	 * @return  string  Email message
	 */
	protected function _getPHPTemplateFtp($tmpl)
	{
		// Start capturing output into a buffer
		ob_start();
		$result = require $tmpl;
		$message = ob_get_contents();
		ob_end_clean();

		if ($result === false)
		{
			return false;
		}

		return $message;
	}

	/**
	 * Get an array of keys we don't want to email to the user
	 *
	 * @return  array
	 */
	protected function getDontEmailKeys()
	{
		if (is_null($this->dontEmailKeys))
		{
			$this->dontEmailKeys = array();

			foreach ($_FILES as $key => $file)
			{
				$this->dontEmailKeys[] = $key;
			}
		}

		return $this->dontEmailKeys;
	}

	/**
	 * Template email handling routine, called if email template specified
	 *
	 * @param   string  $ftpTemplate  Path to template
	 *
	 * @return  string  Email message
	 */
	protected function _getTemplateFtp($ftpTemplate)
	{
		return file_get_contents($ftpTemplate);
	}

	/**
	 * Get content item template
	 *
	 * @param   int  $contentTemplate  Content template
	 *
	 * @return  string  Content item html (translated with Joomfish if installed)
	 */
	protected function _getContentTemplate($contentTemplate)
	{
		if ($this->app->isAdmin())
		{
			$db = $this->_db;
			$query = $db->getQuery(true);
			$query->select('introtext, ' . $db->qn('fulltext'))->from('#__content')->where('id = ' . (int) $contentTemplate);
			$db->setQuery($query);
			$res = $db->loadObject();
		}
		else
		{
			JModel::addIncludePath(COM_FABRIK_BASE . 'components/com_content/models');
			$articleModel = JModelLegacy::getInstance('Article', 'ContentModel');
			$res = $articleModel->getItem($contentTemplate);
		}

		return $res->introtext . ' ' . $res->fulltext;
	}

	/**
	 * Default template handling routine, called if no template specified
	 *
	 * @return  string  Email message
	 */
	protected function _getTextFtp()
	{
		$data = $this->getProcessData();
		$ignore = $this->getDontEmailKeys();
		$message = '';

		/** @var FabrikFEModelForm $formModel */
		$formModel = $this->getModel();
		$groupModels = $formModel->getGroupsHiarachy();

		foreach ($groupModels as &$groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();

			foreach ($elementModels as &$elementModel)
			{
				$element = $elementModel->getElement();

				// @TODO - how about adding a 'renderEmail()' method to element model, so specific element types  can render themselves?
				$key = (!array_key_exists($element->name, $data)) ? $elementModel->getFullName(true, false) : $element->name;

				if (!in_array($key, $ignore))
				{
					$val = '';

					if (is_array(FArrayHelper::getValue($data, $key)))
					{
						// Repeat group data
						foreach ($data[$key] as $k => $v)
						{
							if (is_array($v))
							{
								$val = implode(", ", $v);
							}

							$val .= count($data[$key]) == 1 ? ": $v<br />" : ($k++) . ": $v<br />";
						}
					}
					else
					{
						$val = FArrayHelper::getValue($data, $key);
					}

					$val = FabrikString::rtrimword($val, "<br />");
					$val = stripslashes($val);

					// Set $val to default value if empty
					if ($val == '')
					{
						$val = " - ";
					}

					// Don't add a second ":"
					$label = trim(strip_tags($element->label));
					$message .= $label;

					if (strlen($label) != 0 && JString::strpos($label, ':', JString::strlen($label) - 1) === false)
					{
						$message .= ':';
					}

					$message .= "<br />" . $val . "<br /><br />";
				}
			}
		}

		$message = FText::_('Email from') . ' ' . $this->config->get('sitename') . '<br />' . FText::_('Message') . ':'
			. "<br />===================================<br />" . "<br />" . stripslashes($message);

		return $message;
	}
}
