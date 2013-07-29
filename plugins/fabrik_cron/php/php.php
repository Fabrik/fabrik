<?php
/**
 * A cron task to run PHP code
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-cron.php';

/**
 * A cron task to run PHP code
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */

class PlgFabrik_Cronphp extends PlgFabrik_Cron
{

	/**
	 * Check if the user can use the active element
	 *
	 * @param   object  &$model    calling the plugin list/form
	 * @param   string  $location  to trigger plugin on
	 * @param   string  $event     to trigger plugin on
	 *
	 * @return  bool can use or not
	 */

	public function canUse(&$model = null, $location = null, $event = null)
	{
		return true;
	}

	/**
	 * Do the plugin action
	 *
	 * @param   array   &$data       array data to process
	 * @param   object  &$listModel  plugin's list model
	 *
	 * @return  int  number of records run, you can set this by setting the varaible $processed
	 * in either your included script in php code.
	 */

	public function process(&$data, &$listModel)
	{
		$params = $this->getParams();
		$filter = JFilterInput::getInstance();
		$file = $filter->clean($params->get('cronphp_file'), 'CMD');
		eval($params->get('cronphp_params'));
		$file = JPATH_ROOT . '/plugins/fabrik_cron/php/scripts/' . $file;
		if (JFile::exists($file))
		{
			require_once $file;
		}
		if (isset($processed))
		{
			return (int) $processed;
		}
		return 0;
	}

}
