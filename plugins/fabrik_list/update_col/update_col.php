<?php
/**
 * Add an action button to the list to update selected columns to a given value
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.updatecol
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-list.php';

/**
 * Add an action button to the list to update selected columns to a given value
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.updatecol
 * @since       3.0
 */
class PlgFabrik_ListUpdate_col extends PlgFabrik_List
{
	/**
	 * Button prefix
	 *
	 * @var string
	 */
	protected $buttonPrefix = 'update_col';

	/**
	 * Number of send email notifications
	 *
	 * @var int
	 */
	protected $sent = 0;

	/**
	 * Number of NOT send email notifications
	 *
	 * @var int
	 */
	protected $notsent = 0;

	/**
	 * Number rows updated
	 *
	 * @var int
	 */
	protected $row_count = 0;

	/**
	 * Update message
	 *
	 * @var string
	 */
	protected $msg = null;

	/**
	 * Element containing email notification addresses
	 *
	 * @var  PlgFabrik_Element
	 */
	protected $emailElement = null;

	/**
	 * Prep the button if needed
	 *
	 * @param   array  &$args  Arguments
	 *
	 * @return  bool;
	 */
	public function button(&$args)
	{
		parent::button($args);

		return true;
	}

	/**
	 * Get the button label
	 *
	 * @return  string
	 */
	protected function buttonLabel()
	{
		return FText::_($this->getParams()->get('button_label', parent::buttonLabel()));
	}

	/**
	 * Get the parameter name that defines the plugins acl access
	 *
	 * @return  string
	 */
	protected function getAclParam()
	{
		return 'updatecol_access';
	}

	/**
	 * Can the plug-in select list rows
	 *
	 * @return  bool
	 */
	public function canSelectRows()
	{
		$access = $this->getParams()->get('updatecol_access');
		$name = $this->_getButtonName();

		return in_array($access, $this->user->getAuthorisedViewLevels());
	}

	/**
	 * Get the values to update the list with.
	 * Can be either/or preset values from plugin params, or user selected from popup form.
	 * If both are specified, values from user selects override those from plug-in parameters,
	 * so the plugin parameter pre-sets basically become defaults.
	 *
	 * @param   JParameters  $params  Plugin parameters
	 *
	 * @since   3.0.7
	 *
	 * @return  object|false
	 */
	protected function getUpdateCols($params)
	{
		$model = $this->getModel();

		// get the pre-set updates
		$update = json_decode($params->get('update_col_updates'));

		// if we allow user selected updates ...
		if ($params->get('update_user_select', 0))
		{
			// get the values from the form inputs
			$formModel = $model->getFormModel();
			$qs = $this->app->input->get('fabrik_update_col', '', 'string');
			parse_str($qs, $output);
			$key = 'list_' . $model->getRenderContext();

			$values = FArrayHelper::getValue($output, 'fabrik___filter', array());
			$values = FArrayHelper::getValue($values, $key, array());

			for ($i = 0; $i < count($values['elementid']); $i ++)
			{
				$id = $values['elementid'][$i];

				// meh, nasty hack to handle autocomplete values, which currently aren't working in this plugin
				if (array_key_exists('auto-complete' . $id, $output))
				{
					$values['value'][$i] = $output['auto-complete' . $id];
				}

				$elementModel = $formModel->getElement($id, true);
				$elementName = $elementModel->getFullName(false, false);

				// Was this element already pre-set?  Use array_search() rather than in_array() as we'll need the index if it exists.
				$index = array_search($elementName, $update->coltoupdate);

				if ($index === false)
				{
					// nope, wasn't preset, so just add it to the updates
					$update->coltoupdate[] = $elementName;
					$update->update_value[] = $values['value'][$i];
					$update->update_eval[] = '0';
				}
				else
				{
					// yes it was preset, so use the $index to modify the existing value
					$update->update_value[$index] = $values['value'][$i];
					$update->update_eval[$index] = '0';
				}
			}
		}

		// If no update input found return false to stop processing
		if (empty($update->coltoupdate) && empty($update->update_value))
		{
			return false;
		}

		return $update;
	}

	/**
	 * Do the plug-in action
	 *
	 * @param   array  $opts  Custom options
	 *
	 * @return  bool
	 */
	public function process($opts = array())
	{
		$params = $this->getParams();
		$model = $this->getModel();
		$input = $this->app->input;
		$update = $this->getUpdateCols($params);
		$postEval = $params->get('update_post_eval', '');

		if (!$update && empty($postEval))
		{
			return false;
		}

		// $$$ rob moved here from bottom of func see http://fabrikar.com/forums/showthread.php?t=15920&page=7
		$dateCol = $params->get('update_date_element');
		$userCol = $params->get('update_user_element');

		$item = $model->getTable();

		// Array_unique for left joined table data
		$ids = array_unique($input->get('ids', array(), 'array'));
		$ids = ArrayHelper::toInteger($ids);
		$this->row_count = count($ids);
		$ids = implode(',', $ids);
		$model->reset();
		$model->setPluginQueryWhere('update_col', $item->db_primary_key . ' IN ( ' . $ids . ')');
		$data = $model->getData();

		// Needed to re-assign as getDate() messes the plugin params order
		$this->params = $params;

		if (!empty($dateCol))
		{
			$this->_process($model, $dateCol, $this->date->toSql(), false);
		}

		if (!empty($userCol))
		{
			$this->_process($model, $userCol, (int) $this->user->get('id'), false);
		}

		if ($params->get('update_email_after_update', '1') == '0')
		{
			$this->sendEmails($ids);
		}

		if (!empty($update))
		{
			foreach ($update->coltoupdate as $i => $col)
			{
				$this->_process($model, $col, $update->update_value[$i], $update->update_eval[$i]);
			}
		}

		if ($params->get('update_email_after_update', '1') == '1')
		{
			$this->sendEmails($ids);
		}

		$this->msg = $params->get('update_message', '');

		if (empty($this->msg))
		{
			$this->msg = JText::sprintf('PLG_LIST_UPDATE_COL_UPDATE_MESSAGE', $this->row_count, $this->sent);
		}
		else
		{
			$this->msg = JText::sprintf($this->msg, $this->row_count, $this->sent);
		}

		if (!empty($postEval))
		{
			$err = @eval($postEval);
			FabrikWorker::logEval($err, 'Caught exception on eval in updatecol::process() : %s');
		}

		// Clean the cache.
		$cache = JFactory::getCache($input->get('option'));
		$cache->clean();

		return true;
	}

	/**
	 * Send notification emails
	 *
	 * @param   string  $ids  csv list of row ids.
	 *
	 * @return  void
	 */
	protected function sendEmails($ids)
	{
		$params = $this->getParams();
		$model = $this->getModel();

		// Ensure that yesno exports text and not bootstrap icon.
		$model->setOutputFormat('csv');
		$emailColID = $params->get('update_email_element', '');
		$emailTo = $params->get('update_email_to', '');

		if (!empty($emailColID) || !empty($emailTo))
		{
			$w = new FabrikWorker;
			jimport('joomla.mail.helper');
			$aids = explode(',', $ids);
			$message = $params->get('update_email_msg');
			$subject = $params->get('update_email_subject');
			$eval = $params->get('eval', 0);
			$from = $this->config->get('mailfrom');
			$fromName = $this->config->get('fromname');

			$emailWhich = $this->emailWhich();

			foreach ($aids as $id)
			{
				$row = $model->getRow($id, true);

				/**
				 * hugh - hack to work around this issue:
				 * https://github.com/Fabrik/fabrik/issues/1499
				 */
				$this->params = $params;

				$to = trim($this->emailTo($row, $emailWhich));
				$tos = explode(',', $to);
				$cleanTo = true;

				foreach ($tos as &$email)
				{
					$email = trim($email);

					if (!(JMailHelper::cleanAddress($email) && FabrikWorker::isEmail($email)))
					{
						$cleanTo = false;
					}
				}

				if ($cleanTo)
				{
					if (count($tos) > 1)
					{
						$to = $tos;
					}

					$thisSubject = $w->parseMessageForPlaceholder($subject, $row);
					$thisMessage = $w->parseMessageForPlaceholder($message, $row);

					if ($eval)
					{
						$thisMessage = @eval($thisMessage);
						FabrikWorker::logEval($thisMessage, 'Caught exception on eval in updatecol::process() : %s');
					}

					$mail = JFactory::getMailer();
					$res = $mail->sendMail($from, $fromName, $to, $thisSubject, $thisMessage, true);

					if ($res)
					{
						$this->sent++;
					}
					else
					{
						$this->notsent++;
					}
				}
				else
				{
					$this->notsent++;
				}
			}
		}
	}

	/**
	 * Get the email selection mode
	 *
	 * @return string
	 */
	private function emailWhich()
	{
		$params = $this->getParams();
		$emailColID = $params->get('update_email_element', '');

		if (!empty($emailColID))
		{
			$elementModel = $this->getEmailElement();
			$emailElement = $elementModel->getElement(true);
			$emailWhich = $emailElement->plugin == 'user' ? 'user' : 'field';
		}
		else
		{
			$emailWhich = 'to';
		}

		return $emailWhich;
	}

	/**
	 * Get list of user emails.
	 *
	 * @param   string  $ids  CSV list of ids
	 *
	 * @return  array
	 */
	private function getEmailUserIds($ids)
	{
		$elementModel = $this->getEmailElement();
		$model = $this->getModel();
		$item = $model->getTable();
		$emailColumn = $elementModel->getFullName(false, false);
		$tbl = array_shift(explode('.', $emailColumn));
		$db = $this->_db;
		$userIdsEmails = array();
		$query = $db->getQuery(true);
		$query->select('#__users.id AS id, #__users.email AS email')
		->from('#__users')->join('LEFT', $tbl . ' ON #__users.id = ' . $emailColumn)
		->where($item->db_primary_key . ' IN (' . $ids . ')');
		$db->setQuery($query);
		$results = $db->loadObjectList();

		foreach ($results as $result)
		{
			$userIdsEmails[(int) $result->id] = $result->email;
		}

		return $userIdsEmails;
	}

	/**
	 * Get Email Element
	 *
	 * @return PlgFabrik_Element
	 */
	private function getEmailElement()
	{
		if (isset($this->emailElement))
		{
			return $this->emailElement;
		}

		$params = $this->getParams();
		$emailColID = $params->get('update_email_element', '');

		return FabrikWorker::getPluginManager()->getElementPlugin($emailColID);
	}

	/**
	 * Get email address to send update notification to
	 *
	 * @param   object  $row         Current record row
	 * @param   string  $emailWhich  Mode for getting the user's email
	 *
	 * @return  string  Email address
	 */
	private function emailTo($row, $emailWhich)
	{
		$input = $this->app->input;
		$params = $this->getParams();
		$elementModel = $this->getEmailElement();
		$emailField = $elementModel->getFullName(true, false);

		if ($emailWhich == 'user')
		{
			$emailFieldRaw = $emailField . '_raw';
			$userId = (int) $row->$emailFieldRaw;
			$ids = array_unique($input->get('ids', array(), 'array'));
			$ids = ArrayHelper::toInteger($ids);
			$ids = implode(',', $ids);
			$userIdsEmails = $this->getEmailUserIds($ids);
			$to = FArrayHelper::getValue($userIdsEmails, $userId);
		}
		elseif ($emailWhich == 'field')
		{
			$to = $row->$emailField;
		}
		else
		{
			$to = $params->get('update_email_to', '');
		}

		return $to;
	}

	/**
	 * Get the message generated in process()
	 *
	 * @param   int  $c  Plugin render order
	 *
	 * @return  string
	 */
	public function process_result($c)
	{
		return $this->msg;
	}

	/**
	 * Process the update column
	 *
	 * @param   object  &$model  List model
	 * @param   string  $col     Update column
	 * @param   string  $val     Update val
	 *
	 * @return  void
	 */
	private function _process(&$model, $col, $val, $eval = false)
	{
		$ids = $this->app->input->get('ids', array(), 'array');

		if ($eval)
		{
			$val = @eval($val);
			FabrikWorker::logEval($val, 'Caught exception on eval in updatecol::_process() : %s');
		}

		$model->updateRows($ids, $col, $val);
	}

	/**
	 * Return the javascript to create an instance of the class defined in formJavascriptClass
	 *
	 * @param   array  $args  Array [0] => string table's form id to contain plugin
	 *
	 * @return bool
	 */
	public function onLoadJavascriptInstance($args)
	{
		parent::onLoadJavascriptInstance($args);
		$params = $this->getParams();
		$opts = $this->getElementJSOptions();
		$opts->userSelect = (bool) $params->get('update_user_select', 0);
		$opts->form = $this->userSelectForm();
		$opts->renderOrder = $this->renderOrder;
		$opts = json_encode($opts);
		$this->jsInstance = "new FbListUpdate_col($opts)";

		return true;
	}

	/**
	 * Build the form which allows the user to select which elements to update
	 *
	 * @return  string  HTML Form
	 */
	protected function userSelectForm()
	{
		$params = $this->getParams();
		$selectElements = json_decode($params->get('update_user_select_elements', "{}"));

		/** @var FabrikFEModelList $model */
		$model = $this->getModel();
		JText::script('PLG_LIST_UPDATE_COL_UPDATE');
		$options[] = '<option value="">' . FText::_('COM_FABRIK_PLEASE_SELECT') . '</option>';
		$elementModels = $model->getElements(0, false, true);

		foreach ($elementModels as $elementModel)
		{
			$element = $elementModel->getElement();

			/**
			 * Added "User updateable elements" selection, so admin can specify which elements
			 * can be chosen by the user.
			 */
			if (is_object($selectElements) && isset($selectElements->update_user_select_elements_list) && !in_array($element->id, $selectElements->update_user_select_elements_list))
			{
				continue;
			}

			if ($elementModel->canUse($this, 'list') && $element->plugin !== 'internalid')
			{
				$elName = $elementModel->getFilterFullName();
				$options[] = '<option value="' . $elName . '" data-id="' . $element->id . '" data-plugin="' . $element->plugin . '">'
					. strip_tags($element->label) . '</option>';
			}
		}

		$listRef = $model->getRenderContext();
		$prefix = 'fabrik___update_col[list_' . $listRef . '][';
		$elements = '<select class="inputbox key update_col_elements" size="1" name="' . $prefix . 'key][]">' . implode("\n", $options) . '</select>';
		$j3 = FabrikWorker::j3();
		$addImg = $j3 ? 'plus.png' : 'add.png';
		$removeImg = $j3 ? 'remove.png' : 'del.png';


		$layout = $this->getLayout('form');
		$layoutData = new stdClass;
		$layoutData->listRef = $listRef;
		$layoutData->renderOrder = $this->renderOrder;
		$layoutData->j3 = $j3;
		$layoutData->addImg = FabrikHelperHTML::image($addImg, 'list', $model->getTmpl());
		$layoutData->delImg = FabrikHelperHTML::image($removeImg, 'list', $model->getTmpl());
		$layoutData->elements = $elements;
		$layoutData->user_select_message = $params->get('update_user_select_message', '');

		return $layout->render($layoutData);
	}

	/**
	 * Get the name of the column to update
	 *
	 * @return string
	 */
	protected function _getColName()
	{
		$params = $this->getParams();
		$col = $params->get('coltoupdate');

		return $col . '-' . $this->renderOrder;
	}

	/**
	 * Load the AMD module class name
	 * 
	 * @return string
	 */
	public function loadJavascriptClassName_result()
	{
		return 'FbListUpdateCol';
	}

}
