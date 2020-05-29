<?php
/**
 * The cron notification view, shows a list of the user's current notifications
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.notification
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

/**
 * The cron notification view, shows a list of the user's current notifications
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.notification
 * @since       3.0
 */

class FabrikViewNotification extends JViewLegacy
{
	/**
	 * Still a wip access the view of subscribed notifications with url:
	 * http://localhost/fabrik30x/index.php?option=com_fabrik&task=cron.display&id=3
	 *
	 * deletion not routing right yet
	 *
	 * @param   string  $tpl  Template
	 *
	 * @return  void
	 */

	public function display($tpl = 'default')
	{
		$model = $this->getModel();
		$model->loadLang();
		$this->rows = $model->getUserNotifications();
		$this->id = $model->getId();
		$j3 = FabrikWorker::j3();
		$viewName = $this->getName();
		$tpl = $j3 ? 'bootstrap' : 'default';
		$tmplpath = JPATH_ROOT . '/plugins/fabrik_cron/notification/views/notification/tmpl/' . $tpl;
		$this->_setPath('template', $tmplpath);
		FabrikHelperHTML::stylesheetFromPath('plugins/fabrik_cron/notification/views/notification/tmpl/' . $tpl . '/template.css');
		echo parent::display();
	}
}
