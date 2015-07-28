<?php
/**
 * Create a Joomla user from the forms data
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.juser
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\ArrayHelper;
use Fabrik\Helpers\Worker;
use Fabrik\Helpers\String;
use Fabrik\Helpers\Text;

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Create a Joomla user from the forms data
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.juser
 * @since       3.0
 */

class PlgFabrik_FormJUser extends plgFabrik_Form
{
	/**
	 * Name field
	 *
	 * @var  string
	 */
	protected $namefield = '';

	/**
	 * Email field
	 *
	 * @var  string
	 */
	protected $emailfield = '';

	/**
	 * User name field
	 *
	 * @var  string
	 */
	protected $usernamefield = '';

	/**
	 * Group id field
	 *
	 * @var  string
	 */
	protected $gidfield = '';

	/**
	 * Password field
	 *
	 * @var  string
	 */
	protected $passwordfield = '';

	/**
	 * Block field
	 *
	 * @var  string
	 */
	protected $blockfield = '';

	/**
	 * Get an element name
	 *
	 * @param   string  $pname  Params property name to look up
	 * @param   bool    $short  Short (true) or full (false) element name, default false/full
	 *
	 * @return	string	element full name
	 */

	private function getFieldName($pname, $short = false)
	{
		$params = $this->getParams();

		if ($params->get($pname) == '')
		{
			return '';
		}

		$elementModel = Worker::getPluginManager()->getElementPlugin($params->get($pname));

		return $short ? $elementModel->getElement()->get('name') : $elementModel->getFullName();
	}

	/**
	 * Get the fields value regardless of whether its in joined data or no
	 *
	 * @param   string  $pname    Params property name to get the value for
	 * @param   array   $data     Posted form data
	 * @param   mixed   $default  Default value
	 *
	 * @return  mixed  value
	 */

	private function getFieldValue($pname, $data, $default = '')
	{
		$params = $this->getParams();

		if ($params->get($pname) == '')
		{
			return $default;
		}

		$elementModel = Worker::getPluginManager()->getElementPlugin($params->get($pname));
		$name = $elementModel->getFullName(true, false);

		return ArrayHelper::getValue($data, $name, $default);
	}

	/**
	 * Synchronize J! users with F! table if empty
	 *
	 * @return  void
	 */

	public function onLoad()
	{
		$params = $this->getParams();
		$formModel = $this->getModel();

		if ($params->get('synchro_users') == 1)
		{
			$listModel = $formModel->getListModel();
			$fabrikDb = $listModel->getDb();
			$tableName = $listModel->getTable()->get('list.db_table_name');

			$query = $fabrikDb->getQuery(true);
			$query->select('COUNT(*)')->from($fabrikDb->qn($tableName));

			// Is there already any record in our F! table Users
			$fabrikDb->setQuery($query);
			$count = (int) $fabrikDb->loadResult();

			if ($count === 0)
			{
				try
				{
					// Load the list of users from #__users
					$query->clear();
					$query->select('DISTINCT u.*, ug.group_id')->from($fabrikDb->qn('#__users') . 'AS u')
						->join('LEFT', '#__user_usergroup_map AS ug ON ug.user_id = u.id')->group('u.id')->order('u.id ASC');
					$fabrikDb->setQuery($query);
					$origUsers = $fabrikDb->loadObjectList();
					$count = 0;

					// @TODO really should batch this stuff up, maybe 100 at a time, rather than an insert for every user!
					foreach ($origUsers as $o_user)
					{
						// Insert into our F! table
						$query->clear();
						$fields = array($this->getFieldName('juser_field_userid', true) => $o_user->id,
							$this->getFieldName('juser_field_block', true) => $o_user->block,
							$this->getFieldName('juser_field_email', true) => $o_user->email,
							$this->getFieldName('juser_field_password', true) => $o_user->password,
							$this->getFieldName('juser_field_name', true) => $o_user->name,
							$this->getFieldName('juser_field_username', true) => $o_user->username);

						$query->insert($tableName);

						foreach ($fields as $key => $val)
						{
							$query->set($fabrikDb->qn($key) . ' = ' . $fabrikDb->q($val));
						}

						$fabrikDb->setQuery($query);
						$fabrikDb->execute();
						$count++;
					}

					$this->app->enqueueMessage(Text::sprintf('PLG_FABRIK_FORM_JUSER_MSG_SYNC_OK', $count, $tableName));
				}
				catch (Exception $e)
				{
					$this->app->enqueueMessage(Text::_('PLG_FABRIK_FORM_JUSER_MSG_SYNC_ERROR'));
				}
			}
		}

		// If we are editing a user, we need to make sure the password field is cleared
		if (Worker::getMenuOrRequestVar('rowid'))
		{
			$this->passwordfield = $this->getFieldName('juser_field_password');
			$formModel->data[$this->passwordfield] = '';
			$formModel->data[$this->passwordfield . '_raw'] = '';

			// $$$$ hugh - testing 'sync on edit'
			if ($params->get('juser_sync_on_edit', 0) == 1)
			{
				$this->useridfield = $this->getFieldName('juser_field_userid');
				$userid = (int) ArrayHelper::getValue($formModel->data, $this->useridfield . '_raw');
				/**
				 * $$$ hugh - after a validation failure, userid _raw is an array.
				 * Trying to work out why, and fix that, but need a bandaid for now.
				 */
				if (is_array($userid))
				{
					$userid = (int) ArrayHelper::getValue($userid, 0, 0);
				}

				if ($userid > 0)
				{
					// See https://github.com/Fabrik/fabrik/issues/1026 - don't use JFactory as this loads in session stored user
					$user = new JUser($userid);

					if ($user->get('id') == $userid)
					{
						$this->namefield = $this->getFieldName('juser_field_name');
						$formModel->data[$this->namefield] = $user->get('name');
						$formModel->data[$this->namefield . '_raw'] = $user->get('name');

						$this->usernamefield = $this->getFieldName('juser_field_username');
						$formModel->data[$this->usernamefield] = $user->get('username');
						$formModel->data[$this->usernamefield . '_raw'] = $user->get('username');

						$this->emailfield = $this->getFieldName('juser_field_email');
						$formModel->data[$this->emailfield] = $user->get('email');
						$formModel->data[$this->emailfield . '_raw'] = $user->get('email');
						// @FIXME this is out of date for J1.7 - no gid field
						if ($params->get('juser_field_usergroup') != '')
						{
							$groupElement = Worker::getPluginManager()->getElementPlugin($params->get('juser_field_usergroup'));
							$groupElementClass = get_class($groupElement);
							$gid = $user->groups;

							if ($groupElementClass !== 'PlgFabrik_ElementUsergroup')
							{
								$gid = array_shift($gid);
							}

							$this->gidfield = $this->getFieldName('juser_field_usergroup');
							$formModel->data[$this->gidfield] = $gid;
							$formModel->data[$this->gidfield . '_raw'] = $gid;
						}

						if ($params->get('juser_field_block') != '')
						{
							$this->blockfield = $this->getFieldName('juser_field_block');
							$formModel->data[$this->blockfield] = $user->get('block');
							$formModel->data[$this->blockfield . '_raw'] = $user->get('block');
						}
					}
				}
			}
		}
	}

	/**
	 * Run when a list is emptied/truncated
	 * Delete's all JUsers found in the list, except Admins
	 *
	 * @throws Exception
	 *
	 * @return bool
	 */
	public function onBeforeTruncate()
	{
		$params = $this->getParams();

		if ($params->get('juser_field_userid') != '' && $params->get('juser_delete_user', false))
		{
			$formModel = $this->getModel();
			$listModel = $formModel->getListModel();
			$db = $listModel->getDb();
			$query = $db->getQuery(true);
			$userIdField = String::safeColName($this->getFieldName('juser_field_userid'));
			$query->select($userIdField)
				->from($db->qn($listModel->getTable()->get('list.db_table_name')));
			$userIds = $db->setQuery($query)->loadColumn();

			foreach ($userIds as $userId)
			{
				$user = new JUser((int) $userId);

				$isRoot = $user->authorise('core.admin');

				if (!$isRoot)
				{
					if (!$user->delete())
					{
						$this->app->enqueueMessage('Unable to delete user id ' . $userId, 'error');
					}
				}
			}
		}

		return true;
	}

	/**
	 * Run from list model when deleting rows
	 *
	 * @param   array  &$groups  List data for deletion
	 *
	 * @return	bool
	 */

	public function onDeleteRowsForm(&$groups)
	{
		$params = $this->getParams();

		if ($params->get('juser_field_userid') != '' && $params->get('juser_delete_user', false))
		{
			$useridfield = $this->getFieldName('juser_field_userid');
			$useridfield .= '_raw';

			foreach ($groups as $group)
			{
				foreach ($group as $rows)
				{
					foreach ($rows as $row)
					{
						if (isset($row->$useridfield))
						{
							if (!empty($row->$useridfield))
							{
								$user = new JUser((int) $row->$useridfield);

								// Bail out now and return false, or just carry on?
								if (!$user->delete())
								{
									$this->app->enqueueMessage('Unable to delete user id ' . $row->$useridfield, 'error');
								}
							}
						}
					}
				}
			}
		}

		return true;
	}

	/**
	 * Before the record is stored, this plugin will see if it should process
	 * and if so store the form data in the session.
	 *
	 * NOTE: if your Fabrik list saves directly to #__users then you CAN NOT add additional fields to the list,
	 * instead add to a joined list to contain 'profile' information.
	 *
	 * @return  bool  should the form model continue to save
	 */

	public function onBeforeStore()
	{
		$input = $this->app->input;
		$formModel = $this->getModel();
		$params = $this->getParams();
		$mail = JFactory::getMailer();
		$mail->isHtml(true);

		// Load up com_users lang - used in email text
		$this->language->load('com_users', JPATH_SITE);
		/*
		 * If the fabrik table is set to be #__users and the this plugin is used
		 * we need to alter the form model to tell it not to store the main row
		 * but to still store any joined rows
		 */
		$prefix = $this->config->get('dbprefix');
		$ftable = str_replace('#__', $prefix, $formModel->getListModel()->getTable()->get('list.db_table_name'));
		$jos_users = $prefix . 'users';

		if ($ftable == $jos_users)
		{
			$formModel->storeMainRow = false;
		}

		// Needed for shouldProcess...
		$this->data = $this->getProcessData();

		if (!$this->shouldProcess('juser_conditon', null))
		{
			return true;
		}

		$usersConfig = JComponentHelper::getParams('com_users');
		$userActivation = $usersConfig->get('useractivation');

		// Initialize some variables
		$me = $this->user;

		$bypassActivation = $params->get('juser_bypass_activation', false);
		$bypassRegistration = $params->get('juser_bypass_registration', true);
		$autoLogin = $params->get('juser_auto_login', false);

		$data = $formModel->formData;

		// Check for request forgeries
		JSession::checkToken() or jexit('Invalid Token');
		$original_id = 0;

		if ($params->get('juser_field_userid') != '')
		{
			$this->useridfield = $this->getFieldName('juser_field_userid');

			/*
			 * This test would cause a fail if you were editing a record which contained the user data in a join
			 * E.g. Fabrikar.com/subscribe - user logged in but adding a new subscription
			 * $$$ hugh - AOOOOGA!  Removing the rowId test means that when an admin creates a new
			 * user when logged in, the admin's row in #__users will get overwritten with the new user
			 * details, because the user element has set itself to the currently logged in ID.
			 * Going to try looking at orig data instead, don't know if that'll cause the issue outlined above
			 * but have to do SOMETHING to fix this issue.
			 */
			// if (!empty($formModel->rowId))
			// {

				if ($formModel->origDataIsEmpty())
				{
					$original_id = 0;
				}
				else
				{
					$original_id = $formModel->formData[$this->useridfield];

					// $$$ hugh - if it's a user element, it'll be an array
					if (is_array($original_id))
					{
						$original_id = ArrayHelper::getValue($original_id, 0, 0);
					}
				}
			// }
		}
		else
		{
			$original_id = 0;
			$this->useridfield = '';
		}

		// Create a new JUser object
		$user = new JUser($original_id);

		// Are we dealing with a new user which we need to create?
		$isNew = ($user->get('id') < 1);

		if ($isNew && $usersConfig->get('allowUserRegistration') == '0' && !$bypassRegistration)
		{
			throw new RuntimeException(Text::_('Access Forbidden - Registration not enabled'), 400);

			return false;
		}

		$data = array();

		$this->passwordfield = $this->getFieldName('juser_field_password');
		$this->passwordvalue = $this->getFieldValue('juser_field_password', $formModel->formData);

		$this->namefield = $this->getFieldName('juser_field_name');
		$this->namevalue = $this->getFieldValue('juser_field_name', $formModel->formData);

		$this->usernamefield = $this->getFieldName('juser_field_username');
		$this->usernamevalue = $this->getFieldValue('juser_field_username', $formModel->formData);

		$this->emailfield = $this->getFieldName('juser_field_email');
		$this->emailvalue = $this->getFieldValue('juser_field_email', $formModel->formData);

		$data['id'] = $original_id;

		$data['gid'] = $this->setGroupIds($me, $user);
		$user->groups = (array) $data['gid'];

		if ($params->get('juser_field_block') != '')
		{
			$this->blockfield = $this->getFieldName('juser_field_block');
			$blocked = ArrayHelper::getValue($formModel->formData, $this->blockfield, '');

			if (is_array($blocked))
			{
				// Probably a dropdown
				$data['block'] = (int) $blocked[0];
			}
			else
			{
				$data['block'] = (int) $blocked;
			}
		}
		else
		{
			$data['block'] = 0;
		}

		$data['username'] = $this->usernamevalue;
		$data['password'] = $this->passwordvalue;
		$data['password2'] = $this->passwordvalue;
		$data['name'] = $this->namevalue;
		$data['email'] = $this->emailvalue;

		$ok = $this->check($data);

		if (!$ok)
		{
			// @TODO - add some error reporting
			return false;
		}

		if ($isNew)
		{
			// Set the registration timestamp
			$now = JFactory::getDate();
			$user->set('registerDate', $now->toSql());
			$this->setActivation($data);
		}

		$this->trimNamePassword($user, $data);

		if (($userActivation == '1' || $userActivation == '2') && !$bypassActivation)
		{
			jimport('joomla.user.helper');
			$data['activation'] = JApplication::getHash(JUserHelper::genRandomPassword());
			$data['block'] = 1;
		}
		// If Auto login is activated, we need to set activation and block to 0
		if ($autoLogin)
		{
			$data['activation'] = 0;
			$data['block'] = 0;
		}

		// Check that username is not greater than 150 characters
		$username = $data['username'];

		if (strlen($username) > 150)
		{
			$username = String::substr($username, 0, 150);
			$user->set('username', $username);
		}

		// Check that password is not greater than 100 characters @FIXME - 55 for j3.2
		if (strlen($data['password']) > 100)
		{
			$data['password'] = JString::substr($data['password'], 0, 100);
		}

		// End new
		if (!$user->bind($data))
		{
			$this->app->enqueueMessage(Text::_('CANNOT SAVE THE USER INFORMATION'), 'message');
			$this->app->enqueueMessage($user->getError(), 'error');

			return false;
		}

		// Lets save the JUser object
		if (!$user->save())
		{
			$this->app->enqueueMessage(Text::_('CANNOT SAVE THE USER INFORMATION'), 'message');
			$this->app->enqueueMessage($user->getError(), 'error');

			return false;
		}

		$session = JFactory::getSession();
		$input->set('newuserid', $user->id);
		$input->cookie->set('newuserid', $user->id);
		$session->set('newuserid', $user->id);
		$input->set('newuserid_element', $this->useridfield);
		$input->cookie->set('newuserid_element', $this->useridfield);
		$session->set('newuserid_element', $this->useridfield);
		/*
		 * Time for the email magic so get ready to sprinkle the magic dust...
		 */
		if ($params->get('juser_use_email_plugin') != 1)
		{
			$emailSubject = '';

			if ($isNew)
			{
				// Compile the notification mail values.
				$data = $user->getProperties();
				$data['fromname'] = $this->config->get('fromname');
				$data['mailfrom'] = $this->config->get('mailfrom');
				$data['sitename'] = $this->config->get('sitename');
				$data['siteurl'] = JUri::base();

				$uri = JURI::getInstance();
				$base = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port'));

				// Handle account activation/confirmation emails.
				if ($userActivation == 2 && !$bypassActivation && !$autoLogin)
				{
					// Set the link to confirm the user email.
					$data['activate'] = $base . JRoute::_('index.php?option=com_users&task=registration.activate&token=' . $data['activation'], false);

					$emailSubject = Text::sprintf('COM_USERS_EMAIL_ACCOUNT_DETAILS', $data['name'], $data['sitename']);

					$emailBody = Text::sprintf('COM_USERS_EMAIL_REGISTERED_WITH_ADMIN_ACTIVATION_BODY', $data['name'], $data['sitename'],
						$data['siteurl'] . 'index.php?option=com_users&task=registration.activate&token=' . $data['activation'], $data['siteurl'],
						$data['username'], $data['password_clear']
					);
				}
				elseif ($userActivation == 1 && !$bypassActivation && !$autoLogin)
				{
					// Set the link to activate the user account.
					$data['activate'] = $base . JRoute::_('index.php?option=com_users&task=registration.activate&token=' . $data['activation'], false);

					$emailSubject = Text::sprintf('COM_USERS_EMAIL_ACCOUNT_DETAILS', $data['name'], $data['sitename']);

					$emailBody = Text::sprintf('COM_USERS_EMAIL_REGISTERED_WITH_ACTIVATION_BODY', $data['name'], $data['sitename'],
						$data['siteurl'] . 'index.php?option=com_users&task=registration.activate&token=' . $data['activation'], $data['siteurl'],
						$data['username'], $data['password_clear']
					);
				}
				elseif ($autoLogin)
				{
					$emailSubject = Text::sprintf('COM_USERS_EMAIL_ACCOUNT_DETAILS', $data['name'], $data['sitename']);

					$emailBody = Text::sprintf('PLG_FABRIK_FORM_JUSER_AUTO_LOGIN_BODY', $data['name'], $data['sitename'],
						$data['siteurl'],
						$data['username'], $data['password_clear']
					);
				}
				elseif ($params->get('juser_bypass_accountdetails') != 1)
				{
					$emailSubject = Text::sprintf('COM_USERS_EMAIL_ACCOUNT_DETAILS', $data['name'], $data['sitename']);
					$emailBody = Text::sprintf('COM_USERS_EMAIL_REGISTERED_BODY', $data['name'], $data['sitename'], $data['siteurl']);
				}

				// Send the registration email.
				if ($emailSubject !== '')
				{
					$return = $mail->sendMail($data['mailfrom'], $data['fromname'], $data['email'], $emailSubject, $emailBody);

					/*
					 * Added email to admin code, but haven't had a chance to test it yet.
					 */
					// $this->emailToAdmin($data);

					// Check for an error.
					if ($return !== true)
					{
						$this->reportFailedMail($data);
					}
				}
			}
		}

		// If updating self, load the new user object into the session

		/* @FIXME - doesnt work in J1.7??
		if ($user->get('id') == $me->get('id'))
		{
		    $acl = &JFactory::getACL();

		    $grp = $acl->getAroGroup($user->get('id'));

		    $user->set('guest', 0);
		    $user->set('aid', 1);

		    if ($acl->is_group_child_of($grp->name, 'Registered')      ||
		    $acl->is_group_child_of($grp->name, 'Public Backend'))    {
		        $user->set('aid', 2);
		    }

		    $user->set('usertype', $grp->name);
		    $session->set('user', $user);
		} */

		if (!empty($this->useridfield))
		{
			$formModel->updateFormData($this->useridfield, $user->get('id'), true, true);
		}

		if ($ftable == $jos_users)
		{
			$formModel->rowId = $user->get('id');
		}

		return true;
	}

	/**
	 * If an error has occurred when sending an email, add an internal message
	 * to admins who have their send mail option turned on.
	 *
	 * @param   array  $data  User data
	 *
	 * @return  void
	 */
	protected function reportFailedMail($data)
	{
		$db = $this->db;
		$this->setError(Text::_('COM_USERS_REGISTRATION_SEND_MAIL_FAILED'));

		// Send a system message to administrators receiving system mails
		$query = $db->getQuery(true);
		$query->select('id')->from('#__users')->where('block = 0 AND sendEmail = 1');
		$db->setQuery($query);
		$sendEmail = $db->loadColumn();

		if (count($sendEmail) > 0)
		{
			$now = new JDate;

			// Build the query to add the messages
			$q = "INSERT INTO `#__messages` (`user_id_from`, `user_id_to`, `date_time`, `subject`, `message`)
										VALUES ";
			$messages = array();

			foreach ($sendEmail as $userid)
			{
				$messages[] = "(" . $userid . ", " . $userid . ", '" . $now->toSql() . "', "
					. $db->q(Text::_('COM_USERS_MAIL_SEND_FAILURE_SUBJECT')) . ", "
					. $db->q(Text::sprintf('COM_USERS_MAIL_SEND_FAILURE_BODY', false, $data['username'])) . ")";
			}

			$q .= implode(',', $messages);
			$db->setQuery($q);
			$db->execute();
		}
	}

	/**
	 * Check that username is not greater than 150 characters
	 *
	 * @param   JUser  $user
	 * @param   array  &$data
	 *
	 * @return array $data
	 */
	protected function trimNamePassword($user, &$data)
	{
		// Check that username is not greater than 150 characters
		$username = $data['username'];

		if (strlen($username) > 150)
		{
			$username = JString::substr($username, 0, 150);
			$user->set('username', $username);
		}

		// Check that password is not greater than 100 characters @FIXME - 55 for j3.2
		if (strlen($data['password']) > 100)
		{
			$data['password'] = JString::substr($data['password'], 0, 100);
		}

		return $data;
	}

	/**
	 * Set any activation code / block user info
	 *
	 * @param   array  &$data
	 *
	 * @return  array  $data
	 */
	protected function setActivation(&$data)
	{
		$usersConfig = JComponentHelper::getParams('com_users');
		$params = $this->getParams();
		$bypassActivation = $params->get('juser_bypass_activation', false);
		$autoLogin = $params->get('juser_auto_login', false);

		// If user activation is turned on, we need to set the activation information
		$userActivation = $usersConfig->get('useractivation');

		if (($userActivation == '1' || $userActivation == '2') && !$bypassActivation)
		{
			jimport('joomla.user.helper');
			$data['activation'] = JApplication::getHash(JUserHelper::genRandomPassword());
			$data['block'] = 1;
		}

		// If Auto login is activated, we need to set activation and block to 0
		if ($autoLogin)
		{
			$data['activation'] = 0;
			$data['block'] = 0;
		}

		return $data;
	}

	/**
	 * Email to admin
	 *
	 * @param   array  $data  Form data
	 *
	 * @return  void
	 */
	protected function emailToAdmin($data)
	{
		$usersConfig = JComponentHelper::getParams('com_users');
		$db = $this->db;

		// Send Notification mail to administrators
		if (($usersConfig->get('useractivation') < 2) && ($usersConfig->get('mail_to_admin') == 1))
		{
			$emailSubject = Text::sprintf(
				'COM_USERS_EMAIL_ACCOUNT_DETAILS',
				$data['name'],
				$data['sitename']
			);

			$emailBodyAdmin = Text::sprintf(
				'COM_USERS_EMAIL_REGISTERED_NOTIFICATION_TO_ADMIN_BODY',
				$data['name'],
				$data['username'],
				$data['siteurl']
			);

			// Get all admin users
			$query = 'SELECT name, email, sendEmail' .
				' FROM #__users' .
				' WHERE sendEmail=1';

			$db->setQuery($query);
			$rows = $db->loadObjectList();

			// Send mail to all superadministrators id
			foreach ($rows as $row)
			{
				$return = JFactory::getMailer()->sendMail($data['mailfrom'], $data['fromname'], $row->email, $emailSubject, $emailBodyAdmin);

				// Check for an error.
				if ($return !== true)
				{
					// $$$ hugh - should probably log this rather than enqueue it
					$this->app->enqueueMessage(Text::_('COM_USERS_REGISTRATION_ACTIVATION_NOTIFY_SEND_MAIL_FAILED'));
				}
			}
		}
	}

	/**
	 * Set user group ids
	 *
	 * @param   object  $me    New joomla user
	 * @param   object  $user  Joomla user before juser plugin run
	 *
	 * @return  array   group ids
	 */

	protected function setGroupIds($me, $user)
	{
		$formModel = $this->getModel();
		$isNew = ($user->get('id') < 1);
		$params = $this->getParams();
		$this->gidfield = $this->getFieldName('juser_field_usergroup');
		$defaultGroup = (int) $params->get('juser_field_default_group');
		$groupIds = (array) $this->getFieldValue('juser_field_usergroup', $formModel->formData, $defaultGroup);

		// If the group ids where encrypted (e.g. user can't edit the element) they appear as an object in groupIds[0]
		if (!empty($groupIds) && is_object($groupIds[0]))
		{
			$groupIds = ArrayHelper::fromObject($groupIds[0]);
		}

		ArrayHelper::toInteger($groupIds);
		$data = array();
		$authLevels = $me->getAuthorisedGroups();
		
		if (!$isNew)
		{
			if ($params->get('juser_field_usergroup') != '')
			{
				foreach ($groupIds as $groupId)
				{
					if (in_array($groupId, $authLevels) || $me->authorise('core.admin','com_users'))
					{
						$data[] = $groupId;
					}
					else
					{
						throw new RuntimeException("could not alter user group to $groupId as you are not assigned to that group");
					}
				}
			}
			else
			{
				// If editing an existing user and no gid field being used,  use default group id
				$data[] = $defaultGroup;
			}
		}
		else
		{
			if ($params->get('juser_field_usergroup') != '')
			{
				//If array but empty (e.g. from an empty user_groups element)
				if (empty($groupIds))
				{
					$groupIds = (array) $defaultGroup;
				}
				
				if (count($groupIds) === 1 && $groupIds[0] == 0)
				{
					$data = (array) $defaultGroup;
				}
				else
				{
					//$data = $groupIds; defaultGroup is always valid
					foreach ($groupIds as $groupId)
					{
						if (in_array($groupId, $authLevels) || $me->authorise('core.admin','com_users') || $groupId == $defaultGroup)
						{
							$data[] = $groupId;
						}
						else
						{
							throw new RuntimeException("could not alter user group to $groupId as you are not assigned to that group");
						}
					}				
				}
			}
			else
			{
				$data = (array) $defaultGroup;
			}
		}

		return $data;
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

		if ((int) $this->user->get('id') !== 0)
		{
			return;
		}

		if ($params->get('juser_auto_login', false))
		{
			$this->autoLogin();
		}
	}

	/**
	 * Auto login in the user
	 *
	 * @return  bool
	 */

	protected function autoLogin()
	{
		$formModel = $this->getModel();
		$package = $this->app->getUserState('com_fabrik.package', 'fabrik');

		/* $$$ rob 04/02/2011 no longer used - instead a session var is set
		 * com_fabrik.form.X.juser.created with values true/false.
		 * these values can be used in the redirect plugin to route accordingly

		$success_page	= $params->get('juser_success_page', '');
		$failure_page	= $params->get('juser_failure_page', '');
		 */

		$username = $this->usernamevalue;
		$password = $this->passwordvalue;
		$options = array();
		$options['remember'] = true;
		$options['return'] = '';
		$options['action'] = '';
		$options['silent'] = true;

		$credentials = array();
		$credentials['username'] = $username;
		$credentials['password'] = $password;
		$credentials['secretkey'] = '';

		$session = JFactory::getSession();
		$context = 'com_' . $package . '.form.' . $formModel->getId() . '.juser.';

		if ($this->app->login($credentials, $options) === true)
		{
			$session->set($context . 'created', true);
			$this->user = JFactory::getUser();

			return true;
		}
		else
		{
			$session->set($context . 'created', false);

			return false;
		}
	}

	/**
	 * Check if the submitted details are ok
	 *
	 * @param   array  $post  Posted data
	 *
	 * @return	bool
	 */

	protected function check($post)
	{
		$params = $this->getParams();
		$formModel = $this->getModel();
		$userElement = $formModel->getElement($params->get('juser_field_userid'), true);
		$userElName = $userElement === false ? false : $userElement->getFullName();
		$userId = (int) $post['id'];
		$db = Worker::getDbo(true);
		$ok = true;
		jimport('joomla.mail.helper');

		if ($post['name'] == '')
		{
			$formModel->errors[$this->namefield][0][] = Text::_('JLIB_DATABASE_ERROR_PLEASE_ENTER_YOUR_NAME');
			$this->raiseError($formModel->errors, $this->namefield, Text::_('JLIB_DATABASE_ERROR_PLEASE_ENTER_YOUR_NAME'));
			$ok = false;
		}

		if ($post['username'] == '')
		{
			$this->raiseError($formModel->errors, $this->usernamefield, Text::_('JLIB_DATABASE_ERROR_PLEASE_ENTER_A_USER_NAME'));
			$ok = false;
		}

		if (preg_match("#[<>\"'%;()&]#i", $post['username']) || String::strlen(utf8_decode($post['username'])) < 2)
		{
			$this->raiseError($formModel->errors, $this->usernamefield, Text::sprintf('VALID_AZ09', Text::_('Username'), 2));
			$ok = false;
		}

		if ((trim($post['email']) == "") || !Worker::isEmail($post['email']))
		{
			$this->raiseError($formModel->errors, $this->emailfield, Text::_('JLIB_DATABASE_ERROR_VALID_MAIL'));
			$ok = false;
		}

		if (empty($post['password']))
		{
			if ($userId === 0)
			{
				$this->raiseError($formModel->errors, $this->passwordfield, Text::_('Please enter a password'));
				$ok = false;
			}
		}
		else
		{
			if ($post['password'] != $post['password2'])
			{
				$this->raiseError($formModel->errors, $this->passwordfield, Text::_('PASSWORD DO NOT MATCH.'));
				$ok = false;
			}
		}

		// Check for existing username
		$query = $db->getQuery(true);
		$query->select('COUNT(*)')->from('#__users')->where('username = ' . $db->q($post['username']))->where('id != ' . (int) $userId);
		$db->setQuery($query);
		$xid = (int) $db->loadResult();

		if ($xid > 0)
		{
			$this->raiseError($formModel->errors, $this->usernamefield, Text::_('JLIB_DATABASE_ERROR_USERNAME_INUSE'));
			$ok = false;
		}

		// Check for existing email
		$query->clear();
		$query->select('COUNT(*)')->from('#__users')->where('email = ' . $db->q($post['email']))->where('id != ' . (int) $userId);
		$db->setQuery($query);
		$xid = (int) $db->loadResult();

		if ($xid > 0)
		{
			$this->raiseError($formModel->errors, $this->emailfield, Text::_('JLIB_DATABASE_ERROR_EMAIL_INUSE'));
			$ok = false;
		}

		return $ok;
	}

	/**
	 * Raise an error - depends on whether you are in admin or not as to what to do
	 *
	 * @param   array   &$err   Form models error array
	 * @param   string  $field  Name
	 * @param   string  $msg    Message
	 *
	 * @return  void
	 */

	protected function raiseError(&$err, $field, $msg)
	{
		if ($this->app->isAdmin())
		{
			$this->app->enqueueMessage($msg, 'notice');
		}
		else
		{
			$err[$field][0][] = $msg;
		}
	}
}
