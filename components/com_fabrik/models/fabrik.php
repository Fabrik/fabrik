<?php
/**
 * Fabrik Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

jimport('joomla.application.component.model');

/**
 * Fabrik Element List Model - Joomla 1.7 onwards
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */
class FabModel extends JModelLegacy
{
	/**
	 * @var JApplicationCms
	 */
	protected $app;

	/**
	 * @var JUser
	 */
	protected $user;

	/**
	 * @var JDate
	 */
	protected $date;

	/**
	 * App name
	 *
	 * @var string
	 */
	protected $package = 'fabrik';

	/**
	 * @var Registry
	 */
	protected $config;

	/**
	 * @var JLanguage
	 */
	protected $lang;

	/**
	 * Constructor
	 *
	 * @param   array  $config  An array of configuration options (name, state, dbo, table_path, ignore_request).
	 *
	 * @since   3.3.4
	 * @throws  Exception
	 */
	public function __construct($config = array())
	{
		$this->app = ArrayHelper::getValue($config, 'app', JFactory::getApplication());
		$this->user = ArrayHelper::getValue($config, 'user', JFactory::getUser());
		$this->config = ArrayHelper::getValue($config, 'config', JFactory::getConfig());
		$this->session = ArrayHelper::getValue($config, 'session', JFactory::getSession());
		$this->date = ArrayHelper::getValue($config, 'date', JFactory::getDate());
		$this->lang = ArrayHelper::getValue($config, 'lang', JFactory::getLanguage());
		$this->package = $this->app->getUserState('com_fabrik.package', 'fabrik');

		parent::__construct($config);
	}

	/**
	 * Method to load and return a model object.
	 *
	 * @param   string  $name    The name of the view
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  configuration
	 *
	 * @return	FabTable|false	Model object or boolean false if failed
	 */
	protected function _createTable($name, $prefix = 'Table', $config = array())
	{
		// Clean the model name
		$name = preg_replace('/[^A-Z0-9_]/i', '', $name);
		$prefix = preg_replace('/[^A-Z0-9_]/i', '', $prefix);

		// Make sure we are returning a DBO object
		if (!array_key_exists('dbo', $config))
		{
			$config['dbo'] = $this->getDbo();
		}

		return FabTable::getInstance($name, $prefix, $config);
	}

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return	JTable	The table
	 */
	public function getTable($name = '', $prefix = 'Table', $options = array())
	{
		if (empty($name))
		{
			$name = $this->getName();
		}

		if ($table = $this->_createTable($name, $prefix, $options))
		{
			return $table;
		}

		throw new RuntimeException(JText::sprintf('JLIB_APPLICATION_ERROR_TABLE_NAME_NOT_SUPPORTED', $name));

		return null;
	}
}
