<?php
/**
 * Send a receipt
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.receipt
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Send a receipt
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.receipt
 * @since       3.0
 */

class PlgFabrik_FormReceipt extends PlgFabrik_Form
{
	protected $html = null;

	/**
	 * Sets up HTML to be injected into the form's bottom
	 *
	 * @return void
	 */

	public function getBottomContent()
	{
		$params = $this->getParams();

		if ($params->get('ask-receipt'))
		{
			$label = $params->get('receipt_button_label', '');

			if ($label === '')
			{
				$label = FText::_('PLG_FORM_RECEIPT_EMAIL_ME_A_COPY');
			}

			$this->html = "
			<label><input type=\"checkbox\" name=\"fabrik_email_copy\" class=\"contact_email_copy\" value=\"1\"  />
			 " . $label . "</label>";
		}
		else
		{
			$this->html = '';
		}
	}

	/**
	 * Inject custom html into the bottom of the form
	 *
	 * @param   int  $c  Plugin counter
	 *
	 * @return  string  html
	 */

	public function getBottomContent_result($c)
	{
		return $this->html;
	}

	/**
	 * Run right at the end of the form processing
	 * form needs to be set to record in database for this to hook to be called
	 *
	 * @return	bool
	 */

	public function onAfterProcess()
	{
		$params = $this->getParams();
		$app = JFactory::getApplication();
		$input = $app->input;
		$formModel = $this->getModel();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');

		if ($params->get('ask-receipt'))
		{
			if (!array_key_exists('fabrik_email_copy', $_POST))
			{
				return;
			}
		}

		$rowid = $input->get('rowid');
		$config = JFactory::getConfig();
		$w = new FabrikWorker;
		$form = $formModel->getForm();
		$data = $this->getProcessData();
		$message = $params->get('receipt_message');
		$editURL = COM_FABRIK_LIVESITE . "index.php?option=com_" . $package . "&amp;view=form&amp;fabrik=" . $formModel->get('id') . "&amp;rowid="
			. $rowid;
		$viewURL = COM_FABRIK_LIVESITE . "index.php?option=com_" . $package . "&amp;view=details&amp;fabrik=" . $formModel->get('id') . "&amp;rowid="
			. $rowid;
		$editlink = "<a href=\"$editURL\">" . FText::_('EDIT') . "</a>";
		$viewlink = "<a href=\"$viewURL\">" . FText::_('VIEW') . "</a>";
		$message = str_replace('{fabrik_editlink}', $editlink, $message);
		$message = str_replace('{fabrik_viewlink}', $viewlink, $message);
		$message = str_replace('{fabrik_editurl}', $editURL, $message);
		$message = str_replace('{fabrik_viewurl}', $viewURL, $message);

		$message = $w->parseMessageForPlaceHolder($message, $data, false);

		$to = $w->parseMessageForPlaceHolder($params->get('receipt_to'), $data, false);

		if (empty($to))
		{
			/* $$$ hugh - not much point trying to send if we don't have a To address
			 * (happens frequently if folk don't properly validate their form inputs and are using placeholders)
			 * @TODO - might want to add some feedback about email not being sent
			 */
			return;
		}

		$subject = html_entity_decode($params->get('receipt_subject', ''));
		$subject = JText::_($w->parseMessageForPlaceHolder($subject, $data, false));
		$from = $config->get('mailfrom', '');
		$fromname = $config->get('fromname', '');

		// Darn silly hack for poor joomfish settings where lang parameters are set to override joomla global config but not mail translations entered
		$rawconfig = new JConfig;

		if ($from === '')
		{
			$from = $rawconfig->mailfrom;
		}

		if ($fromname === '')
		{
			$fromname = $rawconfig->fromname;
		}

		$from = $params->get('from_email', $from);
		$mail = JFactory::getMailer();
		$res = $mail->sendMail($from, $fromname, $to, $subject, $message, true);

		if (!$res)
		{
			throw new RuntimeException('Couldn\'t send receipt', 500);
		}
	}
}
