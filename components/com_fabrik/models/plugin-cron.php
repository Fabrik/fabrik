<?php
/**
 * Fabrik Plugin Cron Model
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
 * Fabrik Plugin Cron Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */

class PlgFabrik_Cron extends FabrikPlugin
{
	/**
	 * Plugin item
	 *
	 * @var object
	 */
	protected $row = null;

	/**
	 * Log
	 *
	 * @var string
	 */
	protected $log = null;

	/**
	 * Get the db row
	 *
	 * @param   bool  $force  force reload
	 *
	 * @return  object
	 */

	public function &getTable($force = false)
	{
		if (!$this->row || $force)
		{
			JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_fabrik/tables');
			$row = FabTable::getInstance('Cron', 'FabrikTable');
			$row->load($this->id);
			$this->row = $row;
		}

		return $this->row;
	}

	/**
	 * Whether cron should automagically load table data
	 *
	 * @return  bool
	 */

	public function requiresTableData()
	{
		return true;
	}

	/**
	 * Get the log out put
	 *
	 * @return  string
	 */

	public function getLog()
	{
		return $this->log;
	}

	/**
	 * Only applicable to cron plugins but as there's no sub class for them
	 * the methods here for now
	 * Determines if the cron plug-in should be run - if require_qs is true
	 * then fabrik_cron=1 needs to be in the querystring
	 *
	 * @return  bool
	 */

	public function queryStringActivated()
	{
		$app = JFactory::getApplication();
		$params = $this->getParams();

		if (!$params->get('require_qs', false))
		{
			// Querystring not required so plugin should be activated
			return true;
		}

		return $app->input->getInt('fabrik_cron', 0);
	}
}
