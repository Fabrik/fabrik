<?php
/**
 * Fabrik Form Session Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

/**
 * Fabrik Form Session Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabrikFEModelFormsession extends FabModel
{
	/**
	 * User id
	 *
	 * @var int
	 */
	protected $userid = null;

	/**
	 * Unique reference for the form session
	 *
	 * @var string
	 */
	protected $hash = null;

	/**
	 * Form id
	 *
	 * @var int
	 */
	protected $formid = null;

	/**
	 * Row id
	 *
	 * @var string
	 */
	protected $rowid = null;

	/**
	 * Status message
	 *
	 * @var string
	 */
	public $status = null;

	/**
	 * Status id
	 *
	 * @var int
	 */
	protected $statusid = null;

	/**
	 * Formsession row
	 *
	 * @var JTable
	 */
	public $row = null;

	/**
	 * Should the form store a cookie with
	 * a reference to the incomplete form data
	 *
	 * @var bool
	 */
	protected $useCookie = true;

	/**
	 * cryptor
	 *
	 * @var object
	 */
	protected $crypt = null;

	/**
	 * Constructor
	 */

	public function __construct()
	{
		if (!defined('_FABRIKFORMSESSION_LOADED_FROM_COOKIE'))
		{
			define('_FABRIKFORMSESSION_LOADED_FROM_COOKIE', 1);
			define('_FABRIKFORMSESSION_LOADED_FROM_TABLE', 2);
		}

		parent::__construct();
	}

	/**
	 * Save the form data to #__{package}_form_session
	 *
	 * @param   object  &$formModel  form model
	 *
	 * @return  null
	 */

	public function savePage(&$formModel)
	{
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');

		// Need to check for encrypted vars, unencrypt them and place them back in the array
		$post = $formModel->setFormData();
		$app = JFactory::getApplication();
		$input = $app->input;
		$formModel->copyToRaw($post);
		$fabrik_vars = JArrayHelper::getValue($post, 'fabrik_vars', array());
		$querystring = JArrayHelper::getValue($fabrik_vars, 'querystring', array());
		$formModel->addEncrytedVarsToArray($post);

		if (array_key_exists('fabrik_vars', $post))
		{
			unset($post['fabrik_vars']);
		}

		$data = serialize($post);
		$hash = $this->getHash();
		$userid = $this->getUserId();
		$user = JFactory::getUser();
		$row = $this->load();
		$row->hash = $hash;
		$row->user_id = (int) $user->get('id');
		$row->form_id = $this->getFormId();
		$row->row_id = $this->getRowId();
		$row->last_page = $input->get('page');
		$row->referring_url = $input->server->get('HTTP_REFERER', '', 'string');
		$row->data = $data;
		$this->setCookie($hash);

		if (!$row->store())
		{
			echo $row->getError();
		}

		// $$$ hugh - if we're saving the formdata in the session, we should set 'session.on'
		// as per The New Way we're doing redirects, etc.
		$session = JFactory::getSession();
		$session->set('com_' . $package . '.form.' . $this->getFormId() . '.session.on', true);
	}

	/**
	 * Set the form session cookie
	 *
	 * @param   string  $hash  the actual key that is stored in the db table's hash field
	 *
	 * @return  null
	 */

	public function setCookie($hash)
	{
		if ($this->canUseCookie() === false)
		{
			return;
		}

		$crypt = $this->getCrypt();
		$lifetime = time() + 365 * 24 * 60 * 60;
		$user = JFactory::getUser();
		$key = (int) $user->get('id') . ':' . $this->getFormId() . ':' . $this->getRowId();
		$rcookie = $crypt->encrypt($hash);
		setcookie($key, $rcookie, $lifetime, '/');
	}

	/**
	 * Remove the form session cookie
	 *
	 * @return  null
	 */

	protected function removeCookie()
	{
		$user = JFactory::getUser();
		$lifetime = time() - 99986400;
		$key = (int) $user->get('id') . ':' . $this->getFormId() . ':' . $this->getRowId();
		$res = setcookie($key, false, $lifetime, '/');
	}

	/**
	 * Create the crypt class object
	 *
	 * @return  JSimpleCrypt
	 */

	protected function getCrypt()
	{
		/**
		 * $$$ hugh - might want to alter this to use FabrikWorker::getCrypt()
		 * as we now use that everywhere else.
		 */
		if (!isset($this->crypt))
		{
			jimport('joomla.utilities.simplecrypt');
			jimport('joomla.utilities.utility');

			// Create the encryption key, apply extra hardening using the user agent string
			$key = JApplication::getHash(@$_SERVER['HTTP_USER_AGENT']);
			$key = new JCryptKey('simple', $key, $key);
			$this->crypt = new JCrypt(new JCryptCipherSimple, $key);
		}

		return $this->crypt;
	}

	/**
	 * Set use cookie
	 *
	 * @param   bool  $bol  set use cookie true/false
	 *
	 * @return  void
	 */

	public function useCookie($bol)
	{
		$this->useCookie = $bol;
	}

	/**
	 * Load in the saved session
	 *
	 * @return object session table row
	 */

	public function load()
	{
		$user = JFactory::getUser();
		$row = $this->getTable('Formsession', 'FabrikTable');
		$row->data = '';
		$hash = '';

		if ((int) $user->get('id') !== 0)
		{
			$hash = $this->getHash();
			$this->status = FText::_('LOADING FROM DATABASE');
			$this->statusid = _FABRIKFORMSESSION_LOADED_FROM_TABLE;
		}
		else
		{
			if ($this->canUseCookie())
			{
				$crypt = $this->getCrypt();
				$cookiekey = $this->getCookieKey();
				$cookieval = JArrayHelper::getValue($_COOKIE, $cookiekey, '');

				if ($cookieval !== '')
				{
					$this->status = FText::_('COM_FABRIK_LOADING_FROM_COOKIE');
					$this->statusid = _FABRIKFORMSESSION_LOADED_FROM_COOKIE;
					$hash = $crypt->decrypt($cookieval);
				}
			}
		}

		if ($hash !== '')
		{
			// No point loading it if the hash is empty
			$row->load(array('hash' => $hash));
		}

		if (is_null($row->id))
		{
			$row->last_page = 0;
			$row->data = '';
		}

		$this->last_page = $row->last_page;
		$this->row = $row;

		return $row;
	}

	/**
	 * Get the cookie name
	 *
	 * @since 2.0.4
	 *
	 * @return  string
	 */

	protected function getCookieKey()
	{
		$user = JFactory::getUser();
		$key = (int) $user->get('id') . ':' . $this->getFormId() . ':' . $this->getRowId();

		return $key;
	}

	/**
	 * If a plug has set a session var com_fabrik.form.X.session.on then we should be
	 * using the session cookie, see form confirmation plugin for this in use
	 *
	 * @since 2.0.4
	 *
	 * @return  bool
	 */

	public function canUseCookie()
	{
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$session = JFactory::getSession();
		$formid = $this->getFormId();

		if ($session->get('com_' . $package . '.form.' . $formid . '.session.on'))
		{
			return true;
		}

		return $this->useCookie;
	}

	/**
	 * Remove the saved session
	 *
	 * @return  bool
	 */

	public function remove()
	{
		// $$$ hugh - need to clear the 'session.on'.  If we're zapping the stored
		// session form data, doesn't matter who or what set 'session.on' ... it ain't there any more.
		$session = JFactory::getSession();
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$session->clear('com_' . $package . '.form.' . $this->getFormId() . '.session.on');
		$user = JFactory::getUser();
		$row = $this->getTable('Formsession', 'FabrikTable');
		$hash = '';

		if ((int) $user->get('id') !== 0)
		{
			$hash = $this->getHash();
		}
		else
		{
			if ($this->useCookie)
			{
				$crypt = $this->getCrypt();
				$cookiekey = (int) $user->get('id') . ":" . $this->getFormId() . ":" . $this->getRowId();
				$cookieval = JArrayHelper::getValue($_COOKIE, $cookiekey, '');

				if ($cookieval !== '')
				{
					$hash = $crypt->decrypt($cookieval);
				}
			}
		}

		$db = $row->getDBO();
		$row->hash = $hash;
		$query = $db->getQuery(true);
		$query->delete($db->quoteName($row->getTableName()))->where('hash = ' . $db->quote($hash));
		$db->setQuery($query);
		$this->removeCookie();
		$this->row = $row;

		if ($db->execute())
		{
			return true;
		}
		else
		{
			$row->setError($db->getErrorMsg());

			return false;
		}
	}

	/**
	 * Get the hash identifier
	 * format userid:formid:rowid
	 *
	 * @return  string  hash
	 */

	public function getHash()
	{
		$userid = $this->getUserId();

		if (is_null($this->hash))
		{
			$this->hash = $userid . ':' . $this->getFormId() . ':' . $this->getRowId();
		}

		return $this->hash;
	}

	/**
	 * Get a the user id
	 *
	 * @return  mixed  user id if logged in, unique id if not
	 */

	protected function getUserId()
	{
		$user = JFactory::getUser();

		if ($user->get('id') == 0)
		{
			return uniqid();
		}

		return $user->get('id');
	}

	/**
	 * Det the form id whose record is being edited
	 *
	 * @param   int  $id  form id
	 *
	 * @return  null
	 */

	public function setFormId($id)
	{
		$this->formid = $id;
	}

	/**
	 * Set the row id that is being edited or saved
	 *
	 * @param   int  $id  Row id
	 *
	 * @return  null
	 */

	public function setRowId($id)
	{
		$this->rowid = (int) $id;
	}

	/**
	 * Gets the row id - if not set uses request 'rowid' var
	 *
	 * @return  int
	 */

	protected function getRowId()
	{
		$app = JFactory::getApplication();

		if (is_null($this->rowid))
		{
			$this->rowid = $app->input->getString('rowid', '', 'string');
		}

		return (int) $this->rowid;
	}

	/**
	 * Gets the row id - if not set uses request 'rowid' var
	 *
	 * @return int  form id
	 */

	public function getFormId()
	{
		$app = JFactory::getApplication();

		if (is_null($this->formid))
		{
			$this->formid = $app->input->getInt('formid');
		}

		return $this->formid;
	}
}
