<?php
/**
 * Fabrik Connection Model
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
 * Fabrik Connection Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabrikFEModelConnection extends JModelLegacy
{
	/**
	 * Current connection
	 *
	 * @var JTable
	 */
	protected $connection = null;

	/**
	 * Default connection table
	 *
	 * @var JTable
	 */
	protected $defaultConnection = null;

	/**
	 * Containing db connections
	 *
	 * @var array
	 */
	protected static $dbs = array();

	/**
	 * Connection id
	 *
	 * @var int
	 */
	protected $id = null;

	/**
	 * Method to set the element id
	 *
	 * @param   int  $id  element ID number
	 *
	 * @return  null
	 */

	public function setId($id)
	{
		// Set new element ID
		$this->id = $id;
	}

	/**
	 * Is the connection table the default connection
	 *
	 * @deprecated - don't think its used
	 *
	 * @return  bool
	 */

	public function isDefault()
	{
		return $this->getConnection()->default;
	}

	/**
	 * Test if the connection is exactly the same as Joomla's db connection as
	 * defined in configuration.php
	 *
	 * @since  3.0.8
	 *
	 * @return boolean  True if the same
	 */

	public function isJdb()
	{
		// $$$rob lets see if we have an exact config match with J db if so just return that
		$conf = JFactory::getConfig();
		$host = $conf->get('host');
		$user = $conf->get('user');
		$password = $conf->get('password');
		$database = $conf->get('db');
		$prefix = $conf->get('dbprefix');
		$driver = $conf->get('dbtype');
		$debug = $conf->get('debug');

		$default_options = array('driver' => $driver, 'host' => $host, 'user' => $user, 'password' => $password, 'database' => $database,
				'prefix' => $prefix);

		$cn = $this->getConnection();
		$options = $this->getConnectionOptions($cn);

		return $this->compareConnectionOpts($default_options, $options);
	}

	/**
	 * Get driver instance based on J version
	 *
	 * @param   array  $options  Connection options
	 *
	 * @since  3.1b2
	 *
	 * @return  JDatabaseDriver|JDatabase
	 */

	public function getDriverInstance($options)
	{
		$version = new JVersion;

		return $version->RELEASE > 2.5 ? JDatabaseDriver::getInstance($options) : JDatabase::getInstance($options);
	}

	/**
	 * Creates a html dropdown box for the current connection
	 *
	 * @param   string  $javascript  to add to select box
	 * @param   string  $name        of dropdown box
	 * @param   string  $selected    the selected element in the list
	 * @param   string  $class       input class name
	 *
	 * @deprecated - don't think its used
	 *
	 * @return string
	 */

	public function getTableDdForThisConnection($javascript = '', $name = 'table_join', $selected = '', $class = 'inputbox')
	{
		$tableOptions = array();
		$cn = $this->getConnection();

		if ($cn->host and $cn->published == '1')
		{
			if (@mysql_connect($cn->host, $cn->user, $cn->password))
			{
				// Ensure db files are included
				jimport('joomla.database.database');
				$options = $this->getConnectionOptions($cn);
				$fabrikDb = $this->getDriverInstance($options);
				$tables = $fabrikDb->getTableList();
				$tableOptions[] = JHTML::_('select.option', '', '-');

				if (is_array($tables))
				{
					foreach ($tables as $table)
					{
						$tableOptions[] = JHTML::_('select.option', $table, $table);
					}
				}
			}
			else
			{
				$tableOptions[] = JHTML::_('select.option', 'couldn\'t connect');
			}
		}
		else
		{
			$tableOptions[] = JHTML::_('select.option', 'host not set');
		}

		$attribs = 'class="' . $class . '" size="1" id="' . $name . '" ' . $javascript;

		return JHTML::_('select.genericlist', $tableOptions, $name, $attribs, 'value', 'text', $selected);
	}

	/**
	 * Decrypt once a connection password - if its params->encryptedPw option is true
	 *
	 * @param   JTable  &$cnn  Connection
	 *
	 * @since   3.1rc1
	 *
	 * @return  void
	 */

	protected function decryptPw(&$cnn)
	{
		if (isset($cnn->decrypted) && $cnn->decrypted)
		{
			return;
		}

		$crypt = FabrikWorker::getCrypt();
		$params = json_decode($cnn->params);

		if (is_object($params) && $params->encryptedPw == true)
		{
			$cnn->password = $crypt->decrypt($cnn->password);
			$cnn->decrypted = true;
		}
	}

	/**
	 * Get a connection table object
	 *
	 * @param   int  $id  connection id
	 *
	 * @return  object  connection tables
	 */

	public function &getConnection($id = null)
	{
		if (!is_null($id))
		{
			$this->setId($id);
		}

		if (!is_object($this->connection))
		{
			if ($this->id == -1 || $this->id == '')
			{
				$this->connection = $this->loadDefaultConnection();
			}
			else
			{
				$this->connection = FabTable::getInstance('Connection', 'FabrikTable');
				$this->connection->load($this->id);
			}

			$this->decryptPw($this->connection);
		}

		return $this->connection;
	}

	/**
	 * Load the connection associated with the table
	 *
	 * @return  object  database object using connection details false if connection error
	 */

	public function &getDb()
	{
		if (!isset(self::$dbs))
		{
			self::$dbs = array();
		}

		$error = false;
		$cn = $this->getConnection();
		$app = JFactory::getApplication();
		$input = $app->input;

		if ($input->get('task') == 'test')
		{
			self::$dbs = array();
			$this->connection = null;
			$cn = $this->getConnection();
		}

		if (!array_key_exists($cn->id, self::$dbs))
		{
			if ($this->isJdb())
			{
				$db = FabrikWorker::getDbo();
			}
			else
			{
				$options = $this->getConnectionOptions($cn);
				$db = $this->getDriverInstance($options);
			}

			try
			{
				$db->connect();
			}
			catch (RuntimeException $e)
			{
				$error = true;
			}

			self::$dbs[$cn->id] = $db;

			if ($error)
			{
				/**
				 * $$$Rob - not sure why this is happening on badmintonrochelais.com (mySQL 4.0.24) but it seems like
				 * you can only use one connection on the site? As JDatabase::getInstance() forces a new connection if its options
				 * signature is not found, then fabrik's default connection won't be created, hence defaulting to that one
				 */
				if ($cn->default == 1 && $input->get('task') !== 'test')
				{
					self::$dbs[$cn->id] = FabrikWorker::getDbo();

					// $$$rob remove the error from the error stack
					// if we don't do this the form is not rendered
					JError::getError(true);
				}
				else
				{
					if (!$app->isAdmin())
					{
						throw new RuntimeException('Could not connection to database', E_ERROR);
					}
					else
					{
						// $$$ rob - unset the connection as caching it will mean that changes we make to the incorrect connection in admin, will not result
						// in the test connection link informing the user that the changed connection properties are now correct
						if ($input->get('task') == 'test')
						{
							$this->connection = null;
							$level = E_NOTICE;
						}
						else
						{
							$level = E_ERROR;
						}

						throw new RuntimeException('Could not connection to database cid = ' . $cn->id, $level);
					}
				}
			}
		}

		return self::$dbs[$cn->id];
	}

	/**
	 * Strip the _fab off of a driver name, to get the standard J! driver name
	 *
	 * Really just a wrapper round a helper rtrimword(), but wanted to make it'
	 * clear what it's doing.
	 *
	 * @param   string  $driverName  Database driver name
	 *
	 * @return string
	 */
	private function getBaseDriverName($driverName = '')
	{
		return FabrikString::rtrimword($driverName, '_fab');
	}

	/**
	 * Compare two arrays of connection details. Ignore prefix as this may be set to '' if using koowna
	 *
	 * @param   array  $opts1  first compare
	 * @param   array  $opts2  second compare
	 *
	 * @return  bool
	 */

	private function compareConnectionOpts($opts1, $opts2)
	{
		/**
		 * $$$ hugh - this is returning false when one of the options are defaults from the J! config, and the other is an
		 * actual connection, where the driver is (say) mysqli vs mysqli_fab.  The J! config will never have the _fab driver
		 * specified, so I'm pretty sure we want to strip that off, and just compare the base type.
		 */

		return (
			$this->getBaseDriverName($opts1['driver']) == $this->getBaseDriverName($opts2['driver'])
			&& $opts1['host'] == $opts2['host']
			&& $opts1['user'] == $opts2['user']
			&& $opts1['password'] == $opts2['password']
			&& $opts1['database'] == $opts2['database']
		);
	}

	/**
	 * Get the options to connect to a db
	 *
	 * @param   object  &$cn  connection table
	 *
	 * @return  array  connection options
	 */

	public function getConnectionOptions(&$cn)
	{
		$conf = JFactory::getConfig();
		$host = $cn->host;
		$user = $cn->user;
		$password = $cn->password;
		$database = $cn->database;
		$prefix = $conf->get('dbprefix');
		$driver = $conf->get('dbtype');

		// Test for swapping db table names
		$driver .= '_fab';
		$debug = $conf->get('debug');
		$options = array('driver' => $driver, 'host' => $host, 'user' => $user, 'password' => $password, 'database' => $database, 'prefix' => $prefix);

		return $options;
	}

	/**
	 * Gets object of connections
	 *
	 * @return  array  of connection tables id, description
	 */

	public function getConnections()
	{
		$db = FabrikWorker::getDbo();
		$query = $db->getQuery(true);
		$query->select('*, id AS value, description AS text')->from('#__fabrik_connections')->where('published = 1');
		$db->setQuery($query);
		$connections = $db->loadObjectList();

		foreach ($connections as &$cnn)
		{
			$this->decryptPw($cnn);
		}

		return $connections;
	}

	/**
	 * Gets dropdown list of published connections
	 *
	 * @param   object  $connections  connections stored in database
	 * @param   string  $javascript   to run on change
	 * @param   int     $selected     default value
	 * @param   string  $id           element id
	 * @param   string  $name         of connection drop down
	 * @param   string  $attribs      dropdown properties
	 *
	 * @deprecated - can't see that its used
	 *
	 * @return  string  connection dropdown
	 */

	public function getConnectionsDd($connections, $javascript, $selected, $id = '', $name = 'connection_id', $attribs = 'class="inputbox" size="1" ')
	{
		if ($id == '')
		{
			$id = $name;
		}

		$cnns[] = JHTML::_('select.option', '-1', FText::_('COM_FABRIK_PLEASE_SELECT'));
		$cnns = array_merge($cnns, $connections);
		$attribs .= $javascript;

		return JHTML::_('select.genericlist', $cnns, $name, $attribs, 'value', 'text', $selected, $id);
	}

	/**
	 * Queries all published connections and returns an multidimensional array
	 * of tables and fields for each connection
	 * WARNING: this is likely to
	 * exceed php script execution time if querying a larger remote database
	 *
	 * @param   object  $connections  all available connections
	 *
	 * @return  array
	 */

	public function getConnectionTableFields($connections)
	{
		$connectionTableFields = array();
		$connectionTableFields[-1] = array();
		$connectionTableFields[-1][] = JHTML::_('select.option', '-1', FText::_('COM_FABRIK_PLEASE_SELECT'));

		foreach ($connections as $cn)
		{
			$connectionTableFields[$cn->value] = array();

			if ($cn->host and $cn->published == '1')
			{
				$options = $this->getConnectionOptions($cn);
				$fabrikDb = $this->getDriverInstance($options);
				$tables = $fabrikDb->getTableList();
				$fields = $fabrikDb->getTableColumns($tables);
				$connectionTableFields[$cn->value][$key] = $fields;
			}
		}

		return $connectionTableFields;
	}

	/**
	 * Queries all published connections and returns an multidimensional array
	 * of tables for each connection
	 *
	 * @param   array  $connections  all available connections
	 *
	 * @return  array
	 */

	public function getConnectionTables($connections)
	{
		$connectionTables = array();
		$connectionTables[-1] = array();
		$db = FabrikWorker::getDbo();
		$connectionTables[-1][] = JHTML::_('select.option', '-1', FText::_('COM_FABRIK_PLEASE_SELECT'));

		foreach ($connections as $cn)
		{
			$connectionTables[$cn->value] = array();

			if ($cn->host and $cn->published == '1')
			{
				$this->connection = null;
				$this->id = $cn->id;
				$fabrikDb = $this->getDb();
				$tables = $fabrikDb->getTableList();
				$connectionTables[$cn->value][] = JHTML::_('select.option', '', '- Please select -');

				if (is_array($tables))
				{
					foreach ($tables as $table)
					{
						$connectionTables[$cn->value][] = JHTML::_('select.option', $table, $table);
					}
				}
			}
		}

		return $connectionTables;
	}

	/**
	 * Get the tables names in the loaded connection
	 *
	 * @param   bool  $addBlank  add an empty record to the beginning of the list
	 *
	 * @return array tables
	 */

	public function getThisTables($addBlank = false)
	{
		$cn = $this->getConnection();
		$fabrikDb = $this->getDb();
		$tables = $fabrikDb->getTableList();

		if (is_array($tables))
		{
			if ($addBlank)
			{
				$tables = array_merge(array(""), $tables);
			}

			return $tables;
		}
		else
		{
			return array();
		}
	}

	/**
	 * Tests if you can connect to the connection
	 *
	 * @return  bool  true if connection made otherwise false
	 */

	public function testConnection()
	{
		try
		{
			$db = $this->getDb();
		}
		catch (RuntimeException $e)
		{
			return false;
		}

		return true;
	}

	/**
	 * Load the default connection
	 *
	 * @return  object  default connection
	 */

	public function &loadDefaultConnection()
	{
		if (!$this->defaultConnection)
		{
			// $$$ rob connections are pooled for all packages - each package should use
			// jos_fabrik_connections and not jos_{package}_connections
			$row = FabTable::getInstance('Connection', 'FabrikTable');
			$row->load(array('default' => 1));
			$this->decryptPw($row);
			$this->defaultConnection = $row;
		}

		$this->connection = $this->defaultConnection;

		return $this->defaultConnection;
	}
}
