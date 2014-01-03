<?php
/**
 * Allow processing of CSV import / export on a per row basis
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.listcsv
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
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

		if ($file == -1 || $file == '')
		{
			$code = $params->get('listcsv_import_php_code', '');
			$ret = @eval($code);
			FabrikWorker::logEval($ret, 'Caught exception on eval in onImportCSVRow : %s');

			if ($ret === false)
			{
				return false;
			}
		}
		else
		{
			require JPATH_ROOT . '/plugins/fabrik_list/listcsv/scripts/' . $file;
		}

		return true;
	}
}
