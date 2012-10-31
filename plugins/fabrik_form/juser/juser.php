<?php
/**
 * Create a Joomla user from the forms data
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.juser
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Create a Joomla user from the forms data
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.juser
 * @since       3.0
 */

class plgFabrik_FormJUser extends plgFabrik_Form
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
	 * element mode
	 *
	 * @var	object
	 */
	protected $_elementModel = null;

	/**
	 * Get an element name
	 *
	 * @param   object  $params  plugin params
	 * @param   string  $pname   params property name to look up
	 * @param   bool    $short   short (true) or full (false) element name, default false/full
	 *
	 * @return	string	element full name
	 */

	private function getFieldName($params, $pname, $short = false)
	{
		if ($params->get($pname) == '')
		{
			return '';
		}
		$elementModel = FabrikWorker::getPluginManager()->getElementPlugin($params->get($pname));
		return $short ? $elementModel->getElement()->name : $elementModel->getFullName();
	}

	/**
	 * Get the fields value regardless of whether its in joined data or no
	 *
	 * @param   object  $params  plugin params
	 * @param   string  $pname   params property name to get the value for
	 * @param   array   $data    posted form data
	 * @param   mixed   $default default value
	 *
	 * @return  mixed  value
	 */

	private function getFieldValue($params, $pname, $data, $default = '')
	{
		if ($params->get($pname) == '')
		{
			return $default;
		}
		$elementModel = FabrikWorker::getPluginManager()->getElementPlugin($params->get($pname));
		$group = $elementModel->getGroup();
		if ($group->isJoin())
		{
			$data = $data['join'][$group->getGroup()->join_id];
		}
		$name = $elementModel->getFullName(false, true, false);
		return JArrayHelper::getValue($data, $name, $default);
	}

	/**
	 * Synchronize J! users with F! table if empty
	 *
	 * @param   object  $params      plugin parameters
	 * @param   object  &$formModel  form model
	 *
	 * @return  void
	 */

	public function onLoad($params, &$formModel)
	{
		if ($params->get('synchro_users') == 1)
		{
			$listModel = $formModel->getlistModel();
			$fabrikDb = $listModel->getDb();
			$tableName = $listModel->getTable()->db_table_name;

			$query = $fabrikDb->getQuery(true);
			$query->select('COUNT(*)')->from($tableName);

			// Is there already any record in our F! table Users
			$fabrikDb->setQuery($query);
			$count = (int) $fabrikDb->loadResult();
			if ($count === 0)
			{
				// Load the list of users from #__users
				$query->clear();
				$query->select('DISTINCT u.*, ug.group_id')->from($fabrikDb->quoteName('#__users') . 'AS u')
					->join('LEFT', '#__user_usergroup_map AS ug ON ug.user_id = u.id')->group('u.id')->order('u.id ASC');
				$fabrikDb->setQuery($query);
				$origUsers = $fabrikDb->loadObjectList();
				$count = 0;
				$import = true;

				// @TODO really should batch this stuff up, maybe 100 at a time, rather than an insert for every user!
				foreach ($origUsers as $o_user)
				{
					// Insert into our F! table
					$query->clear();
					$fields = array($this->getFieldName($params, 'juser_field_userid', true) => $o_user->id,
						$this->getFieldName($params, 'juser_field_block', true) => $o_user->block,
						$this->getFieldName($params, 'juser_field_email', true) => $o_user->email,
						$this->getFieldName($params, 'juser_field_password', true) => $o_user->password,
						$this->getFieldName($params, 'juser_field_name', true) => $o_user->name,
						$this->getFieldName($params, 'juser_field_username', true) => $o_user->username,
						$this->getFieldName($params, 'juser_field_usertype', true) => $o_user->group_id);
					$query->insert($tableName);
					foreach ($fields as $key => $val)
					{
						$query->set($fabrikDb->quoteName($key) . ' = ' . $fabrikDb->quote($val));
					}

					$fabrikDb->setQuery($query);
					if (!$fabrikDb->query())
					{
						JError::raiseNotice(400, $fabrikDb->getErrorMsg());
						$import = false;
					}
					// $import = $fabrikDb->query();
					$count++;
				}
				// @TODO - $$$rob - the $import test below only checks if the LAST query ran ok - should check ALL
				// Display synchonization result
				$app = JFactory::getApplication();
				if ($import)
				{
					$app->enqueueMessage(JText::sprintf('PLG_FABRIK_FORM_JUSER_MSG_SYNC_OK', $count, $tableName));
				}
				else
				{
					$app->enqueueMessage(JText::_('PLG_FABRIK_FORM_JUSER_MSG_SYNC_ERROR'));
				}
			}
		}

		// If we are editing a user, we need to make sure the password field is cleared
		if (FabrikWorker::getMenuOrRequestVar('rowid'))
		{
			$this->passwordfield = $this->getFieldName($params, 'juser_field_password');
			$formModel->_data[$this->passwordfield] = '';
			$formModel->_data[$this->passwordfield . '_raw'] = '';

			// $$$$ hugh - testing 'sync on edit'
			if ($params->get('juser_sync_on_edit', 0) == 1)
			{
				$this->useridfield = $this->getFieldName($params, 'juser_field_userid');
				$userid = (int) JArrayHelper::getValue($formModel->_data, $this->useridfield . '_raw');
				if ($userid > 0)
				{
					$user = JFactory::getUser($userid);
					if ($user->get('id') == $userid)
					{
						$this->namefield = $this->getFieldName($params, 'juser_field_name');
						$formModel->_data[$this->namefield] = $user->get('name');
						$formModel->_data[$this->namefield . '_raw'] = $user->get('name');

						$this->usernamefield = $this->getFieldName($params, 'juser_field_username');
						$formModel->_data[$this->usernamefield] = $user->get('username');
						$formModel->_data[$this->usernamefield . '_raw'] = $user->get('username');

						$this->emailfield = $this->getFieldName($params, 'juser_field_email');
						$formModel->_data[$this->emailfield] = $user->get('email');
						$formModel->_data[$this->emailfield . '_raw'] = $user->get('email');

						// @FIXME this is out of date for J1.7 - no gid field
						if ($params->get('juser_field_usertype') != '')
						{
							$groupElement = FabrikWorker::getPluginManager()->getElementPlugin($params->get('juser_field_usertype'));
							$groupElementClass = get_class($groupElement);
							$gid = $user->groups;
							if ($groupElementClass !== 'plgFabrik_ElementUsergroup')
							{
								$gid = array_shift($gid);
							}

							$this->gidfield = $this->getFieldName($params, 'juser_field_usertype');
							$formModel->_data[$this->gidfield] = $gid;
							$formModel->_data[$this->gidfield . '_raw'] = $gid;
						}
						if ($params->get('juser_field_block') != '')
						{
							$this->blockfield = $this->getFieldName($params, 'juser_field_block');
							$formModel->_data[$this->blockfield] = $user->get('block');
							$formModel->_data[$this->blockfield . '_raw'] = $user->get('block');
						}
					}
				}
			}
		}
	}

	/**
	 * Run from list model when deleting rows
	 *
	 * @param   object  $params      plugin parameters
	 * @param   object  &$formModel  form model
	 * @param   array   &$groups     list data for deletion
	 *
	 * @return	bool
	 */

	public function onDeleteRowsForm($params, &$formModel, &$groups)
	{
		if ($params->get('juser_field_userid') != '' && $params->get('juser_delete_user', false))
		{
			$useridfield = $this->getFieldName($params, 'juser_field_userid');
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
									JError::raiseWarning(500, 'Unable to delete user id ' . $row->$useridfield);
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
	 * @param   object  $params      params
	 * @param   object  &$formModel  form model
	 *
	 * @return  bool  should the form model continue to save
	 */

	public function onBeforeStore($params, &$formModel)
	{
		$app = JFactory::getApplication();
		$config = JFactory::getConfig();
		$lang = JFactory::getLanguage();

		// Load up com_users lang - used in email text
		$lang->load('com_users');
		/*
		 * if the fabrik table is set to be #__users and the this plugin is used
		 * we need to alter the form model to tell it not to store the main row
		 * but to still store any joined rows
		 */
		$ftable = str_replace('#__', $app->getCfg('dbprefix'), $formModel->getlistModel()->getTable()->db_table_name);
		$jos_users = $app->getCfg('dbprefix') . 'users';

		if ($ftable == $jos_users)
		{
			$formModel->_storeMainRow = false;
		}

		$usersConfig = JComponentHelper::getParams('com_users');

		// Initialize some variables
		$me = JFactory::getUser();
		$acl = JFactory::getACL();

		$siteURL = JURI::base();
		$bypassActivation = $params->get('juser_bypass_activation', false);
		$bypassRegistration = $params->get('juser_bypass_registration', true);
		$autoLogin = $params->get('juser_auto_login', false);

		// Load in the com_user language file
		$lang = JFactory::getLanguage();
		$lang->load('com_user');

		$data = $formModel->_formData;

		// Check for request forgeries
		JRequest::checkToken() or jexit('Invalid Token');

		$option = JRequest::getCmd('option');

		$original_id = 0;
		if ($params->get('juser_field_userid') != '')
		{
			$this->useridfield = $this->getFieldName($params, 'juser_field_userid');
			if (!empty($formModel->_rowId))
			{
				$original_id = $formModel->_formData[$this->useridfield];

				// $$$ hugh - if it's a user element, it'll be an array
				if (is_array($original_id))
				{
					$original_id = JArrayHelper::getValue($original_id, 0, 0);
				}
			}
		}
		else
		{
			$original_id = 0;
			$this->useridfield = '';
		}

		// Create a new JUser object
		$user = new JUser($original_id);
		$originalGroups = $user->getAuthorisedGroups();

		// Are we dealing with a new user which we need to create?
		$isNew = ($user->get('id') < 1);

		if ($isNew && $usersConfig->get('allowUserRegistration') == '0' && !$bypassRegistration)
		{
			JError::raiseError(403, JText::_('Access Forbidden - Registration not enabled'));
			return false;
		}
		$data = array();

		$this->passwordfield = $this->getFieldName($params, 'juser_field_password');
		$this->passwordvalue = $this->getFieldValue($params, 'juser_field_password', $formModel->_formData);

		$this->namefield = $this->getFieldName($params, 'juser_field_name');
		$this->namevalue = $this->getFieldValue($params, 'juser_field_name', $formModel->_formData);

		$this->usernamefield = $this->getFieldName($params, 'juser_field_username');
		$this->usernamevalue = $this->getFieldValue($params, 'juser_field_username', $formModel->_formData);

		$this->emailfield = $this->getFieldName($params, 'juser_field_email');
		$this->emailvalue = $this->getFieldValue($params, 'juser_field_email', $formModel->_formData);

		$data['id'] = $original_id;

		$data['gid'] = $this->setGroupIds($formModel, $me, $user);
		$user->groups = (array) $data['gid'];

		if ($params->get('juser_field_block') != '')
		{
			$this->blockfield = $this->getFieldName($params, 'juser_field_block');
			$blocked = JArrayHelper::getValue($formModel->_formData, $this->blockfield, '');
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

		// $$$tom get password field to use in $origdata object if editing user and not changing password
		$origdata = $formModel->_origData;
		$pwfield = $this->passwordfield;

		$data['username'] = $this->usernamevalue;
		$data['password'] = $this->passwordvalue;
		$data['password2'] = $this->passwordvalue;
		$data['name'] = $this->namevalue;
		$name = $this->namevalue;
		$data['email'] = $this->emailvalue;

		$ok = $this->check($data, $formModel, $params);
		if (!$ok)
		{
			// @TODO - add some error reporting
			return false;
		}
		// Set the registration timestamp

		if ($isNew)
		{
			$now = JFactory::getDate();
			$user->set('registerDate', $now->toSql());
		}

		if ($isNew)
		{
			// If user activation is turned on, we need to set the activation information
			$useractivation = $usersConfig->get('useractivation');
			if (($useractivation == '1' || $useractivation == '2') && !$bypassActivation)
			{
				jimport('joomla.user.helper');
				$data['activation'] = JUtility::getHash(JUserHelper::genRandomPassword());
				$data['block'] = 1;
			}
			// If Auto login is activated, we need to set activation and block to 0
			if( $autoLogin ) {
				$data['activation'] = 0;
				$data['block'] = 0;
			}
		}

		// Check that username is not greater than 150 characters
		$username = $data['username'];
		if (strlen($username) > 150)
		{
			$username = JString::substr($username, 0, 150);
			$user->set('username', $username);
		}

		// Check that password is not greater than 100 characters
		if (strlen($data['password']) > 100)
		{
			$data['password'] = JString::substr($data['password'], 0, 100);
		}

		// End new
		if (!$user->bind($data))
		{
			$app->enqueueMessage(JText::_('CANNOT SAVE THE USER INFORMATION'), 'message');
			$app->enqueueMessage($user->getError(), 'error');
			return false;
		}

		/*
		 * Lets save the JUser object
		 */
		if (!$user->save())
		{
			$app->enqueueMessage(JText::_('CANNOT SAVE THE USER INFORMATION'), 'message');
			$app->enqueueMessage($user->getError(), 'error');
			return false;
		}
		$session = JFactory::getSession();
		JRequest::setVar('newuserid', $user->id);
		JRequest::setVar('newuserid', $user->id, 'cookie');
		$session->set('newuserid', $user->id);
		JRequest::setVar('newuserid_element', $this->useridfield);
		JRequest::setVar('newuserid_element', $this->useridfield, 'cookie');
		$session->set('newuserid_element', $this->useridfield);
		/*
		 * Time for the email magic so get ready to sprinkle the magic dust...
		 */

		$emailSubject = '';
		if ($isNew)
		{
			// Compile the notification mail values.
			$data = $user->getProperties();
			$data['fromname'] = $config->get('fromname');
			$data['mailfrom'] = $config->get('mailfrom');
			$data['sitename'] = $config->get('sitename');
			$data['siteurl'] = JUri::base();

			$uri = JURI::getInstance();
			$base = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port'));

			// Handle account activation/confirmation emails.
			if ($useractivation == 2 && !$bypassActivation && !$autoLogin)
			{
				// Set the link to confirm the user email.
				$data['activate'] = $base . JRoute::_('index.php?option=com_users&task=registration.activate&token=' . $data['activation'], false);

				$emailSubject = JText::sprintf('COM_USERS_EMAIL_ACCOUNT_DETAILS', $data['name'], $data['sitename']);

				$emailBody = JText::sprintf('COM_USERS_EMAIL_REGISTERED_WITH_ADMIN_ACTIVATION_BODY', $data['name'], $data['sitename'],
					$data['siteurl'] . 'index.php?option=com_users&task=registration.activate&token=' . $data['activation'], $data['siteurl'],
					$data['username'], $data['password_clear']);
			}
			elseif ($useractivation == 1 && !$bypassActivation && !$autoLogin)
			{
				// Set the link to activate the user account.
				$data['activate'] = $base . JRoute::_('index.php?option=com_users&task=registration.activate&token=' . $data['activation'], false);

				$emailSubject = JText::sprintf('COM_USERS_EMAIL_ACCOUNT_DETAILS', $data['name'], $data['sitename']);

				$emailBody = JText::sprintf('COM_USERS_EMAIL_REGISTERED_WITH_ACTIVATION_BODY', $data['name'], $data['sitename'],
					$data['siteurl'] . 'index.php?option=com_users&task=registration.activate&token=' . $data['activation'], $data['siteurl'],
					$data['username'], $data['password_clear']
				);
			}
			elseif ( $autoLogin )
			{
				$emailSubject = JText::sprintf('COM_USERS_EMAIL_ACCOUNT_DETAILS', $data['name'], $data['sitename']);

				$emailBody = JText::sprintf('PLG_FABRIK_FORM_JUSER_AUTO_LOGIN_BODY', $data['name'], $data['sitename'],
					$data['siteurl'],
					$data['username'], $data['password_clear']
				);
			}
			elseif ($params->get('juser_bypass_accountdetails') != 1)
			{
				$emailSubject = JText::sprintf('COM_USERS_EMAIL_ACCOUNT_DETAILS', $data['name'], $data['sitename']);

				$emailBody = JText::sprintf('COM_USERS_EMAIL_REGISTERED_BODY', $data['name'], $data['sitename'], $data['siteurl']);
			}

			// Send the registration email.
			if ($emailSubject !== '')
			{
				$return = JUtility::sendMail($data['mailfrom'], $data['fromname'], $data['email'], $emailSubject, $emailBody);

				$db = JFactory::getDBO();
				/*
				 * Added email to admin code, but haven't had a chance to test it yet.
				 */
				/*
				// Send Notification mail to administrators
				if (($usersConfig->get('useractivation') < 2) && ($usersConfig->get('mail_to_admin') == 1))
				{
				    $emailSubject = JText::sprintf(
				        'COM_USERS_EMAIL_ACCOUNT_DETAILS',
				        $data['name'],
				        $data['sitename']
				    );

				    $emailBodyAdmin = JText::sprintf(
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
				            $app->enqueueMessage(JText::_('COM_USERS_REGISTRATION_ACTIVATION_NOTIFY_SEND_MAIL_FAILED'));
				        }
				    }
				}
				 */

				// Check for an error.
				if ($return !== true)
				{
					$this->setError(JText::_('COM_USERS_REGISTRATION_SEND_MAIL_FAILED'));

					// Send a system message to administrators receiving system mails
					$query = $db->getQuery(true);
					$query->select('id')->from('#__users')->where('block = 0 AND sendEmail = 1');
					$db->setQuery($query);
					$sendEmail = $db->loadColumn();
					if (count($sendEmail) > 0)
					{
						$jdate = new JDate;

						// Build the query to add the messages
						$q = "INSERT INTO `#__messages` (`user_id_from`, `user_id_to`, `date_time`, `subject`, `message`)
									VALUES ";
						$messages = array();
						foreach ($sendEmail as $userid)
						{
							$messages[] = "(" . $userid . ", " . $userid . ", '" . $jdate->toSql() . "', '"
								. JText::_('COM_USERS_MAIL_SEND_FAILURE_SUBJECT') . "', '"
								. JText::sprintf('COM_USERS_MAIL_SEND_FAILURE_BODY', $return, $data['username']) . "')";
						}
						$q .= implode(',', $messages);
						$db->setQuery($q);
						$db->query();
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
			$formModel->_rowId = $user->get('id');
		}
	}

	/**
	 * Set user group ids
	 *
	 * @param   object  $formModel  form model
	 * @param   object  $me         new joomla user
	 * @param   object  $user       joomla user before juser plugin run
	 *
	 * @return  array   group ids
	 */

	protected function setGroupIds($formModel, $me, $user)
	{
		$isNew = ($user->get('id') < 1);
		$params = $this->getParams();
		$this->gidfield = $this->getFieldName($params, 'juser_field_usertype');
		$defaultGroup = (int) $params->get('juser_field_default_group');
		//$groupIds = (array) JArrayHelper::getValue($formModel->_formData, $this->gidfield, $defaultGroup);
		$groupIds = $this->getFieldValue($params, 'juser_field_usertype', $formModel->_formData, $defaultGroup);

		// If the group ids where encrypted (e.g. user can't edit the element) they appear as an object in groupIds[0]
		if (!empty($groupIds) && is_object($groupIds[0]))
		{
			$groupIds = JArrayHelper::fromObject($groupIds[0]);
		}
		JArrayHelper::toInteger($groupIds);
		$data = array();
		if (!$isNew)
		{
			$authLevels = $me->getAuthorisedGroups();
			if ($params->get('juser_field_usertype') != '')
			{
				foreach ($groupIds as $groupId)
				{
					if (in_array($groupId, $authLevels) || $me->authorise('core.admin'))
					{
						$data[] = $groupId;
					}
					else
					{
						JError::raiseNotice(500, "could not alter user group to $groupId as you are not assigned to that group");
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
			if ($params->get('juser_field_usertype') != '')
			{
				if (count($groupIds) === 1 && $groupIds[0] == 0)
				{
					$data = (array) $defaultGroup;
				}
				else
				{
					$data = $groupIds;
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
	 * @param   object  $params      plugin params
	 * @param   object  &$formModel  form model
	 *
	 * @return	bool
	 */

	public function onAfterProcess($params, &$formModel)
	{
		$user = JFactory::getUser();
		if ((int) $user->get('id') !== 0)
		{
			return;
		}
		if ($params->get('juser_auto_login', false))
		{
			$this->autoLogin($formModel);
		}
	}

	/**
	 * Auto login in the user
	 *
	 * @param   object  $formModel  form model
	 *
	 * @return  bool
	 */

	protected function autoLogin($formModel)
	{
		$app = JFactory::getApplication();

		/* $$$ rob 04/02/2011 no longer used - instead a session var is set
		 * com_fabrik.form.X.juser.created with values true/false.
		 * these values can be used in the redirect plugin to route accordingly

		$success_page	= $params->get('juser_success_page', '');
		$failure_page	= $params->get('juser_failure_page', '');
		 */

		// $$$ rob - commented this block out as we have already got the values in
		// $this->usernamevalue and $this->passwordvalue

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

		// @FIXME not working - gives error JERROR_LOGIN_DENIED
		// Preform the login action
		$error = $app->login($credentials, $options);

		$session = JFactory::getSession();
		$context = 'com_fabrik.form.' . $formModel->getId() . '.juser.';
		$w = new FabrikWorker;
		if (!JError::isError($error))
		{
			$session->set($context . 'created', true);
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
	 * @param   array   $post        posted data
	 * @param   object  &$formModel  form model
	 * @param   object  $params      plugin params
	 *
	 * @return	bool
	 */

	protected function check($post, &$formModel, $params)
	{
		$db = FabrikWorker::getDbo(true);
		$ok = true;
		jimport('joomla.mail.helper');

		if ($post['name'] == '')
		{
			$formModel->_arErrors[$this->namefield][0][] = JText::_('JLIB_DATABASE_ERROR_PLEASE_ENTER_YOUR_NAME');
			$this->raiseError($formModel->_arErrors, $this->namefield, JText::_('JLIB_DATABASE_ERROR_PLEASE_ENTER_YOUR_NAME'));
			$ok = false;
		}

		if ($post['username'] == '')
		{
			$this->raiseError($formModel->_arErrors, $this->usernamefield, JText::_('JLIB_DATABASE_ERROR_PLEASE_ENTER_A_USER_NAME'));
			$ok = false;
		}

		if (preg_match("#[<>\"'%;()&]#i", $post['username']) || JString::strlen(utf8_decode($post['username'])) < 2)
		{
			$this->raiseError($formModel->_arErrors, $this->usernamefield, JText::sprintf('VALID_AZ09', JText::_('Username'), 2));
			$ok = false;
		}

		if ((trim($post['email']) == "") || !JMailHelper::isEmailAddress($post['email']))
		{
			$this->raiseError($formModel->_arErrors, $this->emailfield, JText::_('JLIB_DATABASE_ERROR_VALID_MAIL'));
			$ok = false;
		}
		if (empty($post['password']))
		{
			// $$$tom added a new/edit test
			if ((int) $post['id'] === 0)
			{
				$this->raiseError($formModel->_arErrors, $this->passwordfield, JText::_('Please enter a password'));
				$ok = false;
			}
		}
		else
		{
			if ($post['password'] != $post['password2'])
			{
				$this->raiseError($formModel->_arErrors, $this->passwordfield, JText::_('PASSWORD DO NOT MATCH.'));
				$ok = false;
			}
		}

		// Check for existing username
		$query = $db->getQuery(true);
		$query->select('id')->from('#__users')->where('username = ' . $db->quote($post['username']))->where('id != ' . (int) $post['id']);
		$db->setQuery($query);
		$xid = intval($db->loadResult());
		if ($xid && $xid != intval($post['id']))
		{
			$this->raiseError($formModel->_arErrors, $this->usernamefield, JText::_('JLIB_DATABASE_ERROR_USERNAME_INUSE'));
			$ok = false;
		}

		// Check for existing email
		$query->clear();
		$query->select('id')->from('#__users')->where('email = ' . $db->quote($post['email']))->where('id != ' . (int) $post['id']);
		$db->setQuery($query);
		$xid = intval($db->loadResult());
		if ($xid && $xid != intval($post['id']))
		{
			$this->raiseError($formModel->_arErrors, $this->emailfield, JText::_('JLIB_DATABASE_ERROR_EMAIL_INUSE'));
			$ok = false;
		}
		return $ok;
	}

	/**
	 * Raise an error - depends on whether ur in admin or not as to what to do
	 *
	 * @param   array   &$err   form models error array
	 * @param   string  $field  name
	 * @param   string  $msg    message
	 *
	 * @return  void
	 */

	protected function raiseError(&$err, $field, $msg)
	{
		if (JFactory::getApplication()->isAdmin())
		{
			JError::raiseNotice(500, $msg);
		}
		else
		{
			$err[$field][0][] = $msg;
		}
	}
}
