<?php
/**
 * Cron controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controllerform');

require_once 'fabcontrollerform.php';

/**
 * Cron controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       1.6
 */

class FabrikControllerCron extends FabControllerForm
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var	string
	 */
	protected $text_prefix = 'COM_FABRIK_CRON';

}
