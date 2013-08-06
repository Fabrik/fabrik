<?php
/**
 * The cron notification view, shows a list of the user's current notifications
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.notification
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
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

class FabrikViewNotification extends JView
{

	/**
	 * Still a wip access the view of subscribed notifcations with url:
	 * http://localhost/fabrik30x/index.php?option=com_fabrik&task=cron.display&id=3
	 *
	 * deletion not routing right yet
	 * langauge strings not loading either
	 *
	 * @param   string  $tpl  Template
	 *
	 *   @return  void
	 */

	public function display($tpl = 'default')
	{
		$this->rows = $this->get('UserNotifications');

		$viewName = $this->getName();

		$tmplpath = JPATH_ROOT . '/plugins/fabrik_cron/notification/views/notification/tmpl/' . $tpl;
		$this->_setPath('template', $tmplpath);

		$ab_css_file = $tmplpath . '/template.css';

		if (JFile::exists($ab_css_file))
		{
			JHTML::stylesheet('template.css', '/plugins/fabrik_cron/notification/views/notification/tmpl/' . $tpl . '/', true);
		}

		echo parent::display();
	}

}
