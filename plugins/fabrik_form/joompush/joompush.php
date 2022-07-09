<?php
/**
 * Send an push notification with JoomPush
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.joompush
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';


/**
 * Send an JoomPush notification
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.joompush
 * @since       3.0
 */
class PlgFabrik_FormJoompush extends PlgFabrik_Form
{
	/**
	 * Run right at the end of the form processing
	 * form needs to be set to record in database for this to hook to be called
	 *
	 * @return	bool
	 */
	public function onAfterProcess()
	{
		if (JComponentHelper::getComponent('com_joompush', true)->enabled)
		{
			require_once JPATH_ROOT . '/components/com_joompush/helpers/jpush.php';
			require_once JPATH_ROOT . '/components/com_joompush/helpers/joompush.php';
			return $this->process();
		}

		return false;
	}

	/**
	 * Send JoomPush notification
	 *
	 * @return	bool
	 */
	protected function process()
	{
		$formModel = $this->getModel();
		$params = $this->getParams();
		$this->data = $this->getProcessData();
		$data = $formModel->formData;

		if (!$this->shouldProcess('joompush_conditon', $data, $params))
		{
			return true;
		}

		$clientId          = $formModel->getId();
		$userId            = $this->getFieldValue('joompush_user', $formModel->formDataWithTableName);
		$userId            = is_array($userId) ? $userId[0] : $userId;
		$gid               = $params->get('joompush_group', '');
		$pushMsg           = new stdClass;
		$pushMsg->template = $this->getTemplate();
		$jPush             = new JoompushHelpersJpush;
		$jPushSite         = new JoompushHelpersJoompushsite;
		$subscribersData   = new stdClass();

		if (!empty($gid))
		{
			$pushMsg->gid               = $gid;
			$pushMsg->code              = $this->getTrackCode();
			$result = $jPush::jtopicPush($pushMsg);

			if ($result)
			{
				$jPushSite->saveNotification(
					$subscribersData,
					$pushMsg->gid,
					'group',
					$pushMsg->template,
					1,
					'com_fabrik.form',
					$clientId,
					$pushMsg->code,
					true,
					''
				);
			}
		}

		if (!empty($userId))
		{
			$subscriberKeys = $this->getSubscriberKeys($userId);
			$subscribersData->key = $subscriberKeys;

			foreach ($subscriberKeys as $key)
			{
				$pushMsg->key = array($key);
				$pushMsg->code = $key;

				$result = $jPush::jpush($pushMsg);

				if ($result)
				{
					$jPushSite->saveNotification(
						$subscribersData,
						0,
						'user',
						$pushMsg->template,
						1,
						'com_fabrik.form',
						$clientId,
						$pushMsg->code,
						true,
						''
					);
				}
			}
		}

		return true;
	}


	/**
	 * Default email handling routine, called if no email template specified
	 *
	 * @return	string	email message
	 */
	protected function getMessage()
	{
		$params = $this->getParams();
		$msg    = JText::_($params->get('joompush_message', ''));
		$w = new FabrikWorker;
		return $w->parseMessageForPlaceHolder($msg, $this->data);
	}


	/**
	 * Default email handling routine, called if no email template specified
	 *
	 * @return	string	email message
	 */
	protected function getTitle()
	{
		$params = $this->getParams();
		$msg    = JText::_($params->get('joompush_title', ''));
		$w = new FabrikWorker;
		return $w->parseMessageForPlaceHolder($msg, $this->data);
	}

	/**
	 * Get Admin user group.
	 *
	 * @return  string
	 *
	 * @since   3.8
	 */
	protected function getAdminGroupId()
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query
			->select('id')
			->from($db->quoteName('#__joompush_subscriber_groups'))
			->where($db->quoteName('is_default') . ' = 2');
		$db->setQuery($query);
		return $db->loadResult();
	}

	/**
	 * Get a random string to use a tracking code
	 *
	 * @return string
	 *
	 * @since 3.8
	 */
	protected function getTrackCode()
	{
		return md5(uniqid(rand(), true));
	}

	/**
	 * Get an array of subscriber keys from JoomPush
	 *
	 * @param  integer  $userId
	 *
	 * @return  array
	 */
	protected function getSubscriberKeys($userId)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('key'))
			->from($db->quoteName('#__joompush_subscribers'))
			->where('user_id = ' . (int)$userId)
			->where('state = 1');
		$db->setQuery($query);
		return $db->loadColumn();
	}

	protected function getJPTemplate($templateId)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query
			->select(
				$db->quoteName(
					array(
						'title',
						'message',
						'icon',
						'url'
					)
				)
			)
			->from($db->quoteName('#__joompush_notification_templates'))
			->where('id = ' . (int)$templateId);
		$db->setQuery($query);
		return $db->loadObject();
	}

	/**
	 * Get a template, either from our params, or a JoomPush
	 */
	protected function getTemplate()
	{
		$params = $this->getParams();
		$formModel = $this->getModel();
		$useJPTemplate = $params->get('joompush_use_jp_template', '0') === '1';

		if ($useJPTemplate)
		{
			$template = $this->getJPTemplate($params->get('joompush_template', ''));
		}
		else
		{
			$url = $params->get('joompush_url', '');

			if (empty($url))
			{
				$rowid = $this->app->input->get('rowid', '', 'string');
				$url   = 'index.php?option=com_' . $this->package . '&view=details&formid=' . $formModel->get('id') . '&rowid=' . $rowid;
			}

			$template          = new stdClass;
			$template->icon    = $params->get('joompush_notification_icon');
			$template->url     = JRoute::_($url);
			$template->title   = $this->getTitle();
			$template->message = $this->getMessage();
		}

		return $template;
	}
}
