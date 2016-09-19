<?php
/**
 * Fabrik Plugin Cron Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
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
	 * Allow plugin to stop rescheduling
	 *
	 * @var bool
	 */
	public $reschedule = true;

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
	 * then fabrik_cron=1 needs to be in the query string
	 *
	 * @return  bool
	 */
	public function queryStringActivated()
	{
		$params = $this->getParams();
		
		// Felixkat
		$session = JFactory::getSession();
		$fabrikCron = new stdClass();
		$fabrikCron->dropData = $params->get('cron_importcsv_dropdata');
		$fabrikCron->requireJS = $params->get('require_qs');
		$secret = $params->get('require_qs_secret', '');
		$fabrikCron->secret = $this->app->input->getString('fabrik_cron', '') === $secret;
		$session->set('fabrikCron', $fabrikCron);
		// Felixkat

		if (!$params->get('require_qs', false))
		{
			// Querystring not required so plugin should be activated
			return true;
		}

		// check to see if a specific keyword is needed to run this plugin
		if ($secret = $params->get('require_qs_secret', ''))
		{
			return $this->app->input->getString('fabrik_cron', '') === $secret;
		}
		else
		{
			return $this->app->input->getInt('fabrik_cron', 0) === 1;
		}
	}

	/**
	 * Only applicable to cron plugins but as there's no sub class for them
	 * the methods here for now
	 *
	 * Check if we should do run gating for this cron job, whereby we set the task to unpublished
	 * until it has finished running, to prevent multiple copies running.
	 *
	 * @return  bool
	 */
	public function doRunGating()
	{
		$params = $this->getParams();

		return $params->get('cron_rungate', '0') === '1';
	}

	/**
	 * Allow plugin to decide if it wants to be rescheduled
	 *
	 * @param   bool  $reschedule  Switch to turn off rescheduling if set to false
	 *
	 * @return  bool
	 */
	public function shouldReschedule($reschedule = true)
	{
		if ($reschedule === false)
		{
			$this->reschedule = false;
		}

		return $this->reschedule;
	}

	/**
	 * Do the plugin action
	 *
	 * @param   array &$data data
	 * @param   object  &$listModel  List model
	 *
	 * @return  int  number of records updated
	 */
	public function process(&$data, &$listModel)
	{
		return 0;
	}
}
