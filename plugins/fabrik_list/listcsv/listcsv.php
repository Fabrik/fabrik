<?php
/**
 * Allow processing of CSV import / export on a per row basis
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.listcsv
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-list.php';

/**
 * Allow processing of CSV import / export on a per row basis
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.listcsv
 * @since       3.0
 */

class PlgFabrik_ListListcsv extends PlgFabrik_List
{
	/*
	 * for use by user code
	 */
	public $userClass = null;

	/*
	 * for use by user code
	 */
	public $userData = null;

	/**
	 * determine if the table plugin is a button and can be activated only when rows are selected
	 *
	 * @return bool
	 */

	public function canSelectRows()
	{
		return false;
	}

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

		return false;
	}

	/**
	 * Called when we import a csv row
	 *
	 * @return boolean
	 */

	public function onImportCSVRow()
	{
		$params = $this->getParams();
		$filter = JFilterInput::getInstance();
		$file = $params->get('listcsv_import_php_file');
		$file = $filter->clean($file, 'CMD');

		if ($file != -1 && $file != '')
		{

			require JPATH_ROOT . '/plugins/fabrik_list/listcsv/scripts/' . $file;
		}

		$code = trim($params->get('listcsv_import_php_code', ''));

		if (!empty($code))
		{
			$ret = @eval($code);
			FabrikWorker::logEval($ret, 'Caught exception on eval in onImportCSVRow : %s');

			if ($ret === false)
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Called after we import a csv row
	 *
	 * @return boolean
	 */

	public function onAfterImportCSVRow()
	{
		$params = $this->getParams();
		$filter = JFilterInput::getInstance();
		$file = $params->get('listcsv_after_import_php_file');
		$file = $filter->clean($file, 'CMD');

		if ($file != -1 && $file != '')
		{

			require JPATH_ROOT . '/plugins/fabrik_list/listcsv/scripts/' . $file;
		}

		$code = trim($params->get('listcsv_after_import_php_code', ''));

		if (!empty($code))
		{
			$ret = @eval($code);
			FabrikWorker::logEval($ret, 'Caught exception on eval in onAfterImportCSVRow : %s');

			if ($ret === false)
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Called when import is complete
	 *
	 * @return boolean
	 */

	public function onCompleteImportCSV()
	{
		$params = $this->getParams();
		$filter = JFilterInput::getInstance();
		$file = $params->get('listcsv_import_complete_php_file');
		$file = $filter->clean($file, 'CMD');

		if ($file != -1 && $file != '')
		{

			require JPATH_ROOT . '/plugins/fabrik_list/listcsv/scripts/' . $file;
		}

		$code = trim($params->get('listcsv_import_complete_php_code', ''));

		if (!empty($code))
		{
			$ret = @eval($code);
			FabrikWorker::logEval($ret, 'Caught exception on eval in onCompleteImportCSV : %s');

			if ($ret === false)
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Called before import is started
	 *
	 * @return boolean
	 */

	public function onStartImportCSV()
	{
		$params = $this->getParams();
		$filter = JFilterInput::getInstance();
		$file = $params->get('listcsv_import_start_php_file');
		$file = $filter->clean($file, 'CMD');

		if ($file != -1 && $file != '')
		{

			require JPATH_ROOT . '/plugins/fabrik_list/listcsv/scripts/' . $file;
		}

		$code = trim($params->get('listcsv_import_start_php_code', ''));

		if (!empty($code))
		{
			$ret = @eval($code);
			FabrikWorker::logEval($ret, 'Caught exception on eval in onStartImportCSV : %s');

			if ($ret === false)
			{
				return false;
			}
		}

		return true;
	}
}
