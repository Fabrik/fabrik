<?php
/**
 * Raw:  Cron controller class.
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

/**
 * Cron controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       1.6
 */

class FabrikControllerCron extends JControllerForm
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var	string
	 */
	protected $text_prefix = 'COM_FABRIK_CRON';

	/**
	 * Called via ajax to load in a given plugin's HTML settings
	 *
	 * @return  void
	 */

	public function getPluginHTML()
	{
		$plugin = JRequest::getCmd('plugin');
		$model = $this->getModel();
		$model->getForm();
		echo $model->getPluginHTML($plugin);
	}

}
