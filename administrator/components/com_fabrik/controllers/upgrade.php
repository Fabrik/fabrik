<?php
/**
 * Upgrade controller
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controlleradmin');

require_once 'fabcontrollerform.php';

/**
 * Upgrade controller
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */
class FabrikAdminControllerUpgrade extends JControllerAdmin
{
	/**
	 * Delete all data from fabrik
	 *
	 * @return  null
	 */
	public function check()
	{
		$model = $this->getModel('Upgrade');
	}
}
